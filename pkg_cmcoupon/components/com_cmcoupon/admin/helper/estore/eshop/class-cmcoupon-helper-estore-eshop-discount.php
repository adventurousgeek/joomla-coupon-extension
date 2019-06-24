<?php
/**
 * CmCoupon
 *
 * @package Joomla CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined('_JEXEC') or die('Restricted access');
if ( ! defined( '_CM_' ) ) {
	exit;
}

AC()->helper->add_class( 'CmCoupon_Library_Discount' );

class CmCoupon_Helper_Estore_Eshop_Discount extends CmCoupon_Library_Discount {

	var $params = null;

	var $cart = null;
	var $o_cart = null;
	var $coupon_code = null;
	var $posted_order = null;

	var $product_total = 0;
	var $product_qty = 0;
	var $default_err_msg = '';

	public static function instance( $class = null ) {
		return parent::instance( get_class() );
	}

	public function __construct() {
		parent::__construct();

		$this->estore = 'eshop';
		$this->default_err_msg = JText::_('ESHOP_COUPON_APPLY_ERROR');

		jimport('joomla.filesystem.file');
		require_once JPATH_ROOT . '/administrator/components/com_eshop/libraries/defines.php';
		if ( ! class_exists( 'EShopInflector' ) ) {
			require JPATH_ROOT . '/administrator/components/com_eshop/libraries/inflector.php';
		}
		require_once JPATH_ROOT . '/administrator/components/com_eshop/libraries/autoload.php';
		if ( ! class_exists( 'EShopModelCheckout' ) ) {
			require JPATH_ROOT . '/components/com_eshop/models/checkout.php';
		}
		if ( ! class_exists( 'EshopCart' ) ) {
			require JPATH_ROOT . '/components/com_eshop/helpers/cart.php';
		}
		if ( ! class_exists( 'EshopHelper' ) ) {
			require JPATH_ROOT . '/components/com_eshop/helpers/helper.php';
		}
		if ( ! class_exists( 'EshopCurrency' ) ) {
			require JPATH_ROOT . '/components/com_eshop/helpers/currency.php';
		}
	}

	public function init() {
		$user = JFactory::getUser();
		$model = new EShopModelCheckout();
		$cartData = $model->getCartData();

		$totalData = array();
		$total = 0;
		$taxes = array();
		$model->getSubTotalCosts( $totalData, $total, $taxes );
		$model->getVoucherCosts( $totalData, $total, $taxes );
		$model->getShippingCosts( $totalData, $total, $taxes );
		$model->getPaymentFeeCosts( $totalData, $total, $taxes );
		$model->getDonateCosts( $totalData, $total, $taxes );
		//$model->getCouponCosts($ totalData, $total, $taxes );
		$model->getTaxesCosts( $totalData, $total, $taxes );
		$model->getTotalCosts( $totalData, $total, $taxes );

		$totaldatareset = array();
		foreach ( $totalData as $item ) {
			$totaldatareset[ $item['name'] ] = (object) $item;
		}
		$totaldatareset = (object) $totaldatareset;

		$shippingMethod = JFactory::getSession()->get( 'shipping_method' );
		if ( ! empty( $shippingMethod ) ) {
			$shipping_tax = 0;
			if ( ! empty( $shippingMethod['taxclass_id'] ) ) {
				$tax = new EshopTax(EshopHelper::getConfig());
				$shppingtaxRates = $tax->getTaxRates( $shippingMethod['cost'], $shippingMethod['taxclass_id'] );
				foreach ( $shppingtaxRates as $k => $taxRate ) {
					if ( $taxRate['amount'] <= 0 ) {
						unset( $shppingtaxRates[ $k ] );
						continue;
					}
					$shipping_tax += $taxRate['amount'];
				}
			}
			$shippingMethod['tax'] = $shipping_tax;
			$shippingMethod['tax_rates'] = empty( $shppingtaxRates ) ? array() : $shppingtaxRates;

			list( $shipping_name, $shipping_item ) = explode( '.', $shippingMethod['name'] );
			$shippingMethod['shipping_id'] = AC()->db->get_value( 'SELECT id FROM #__eshop_shippings WHERE name="' . AC()->db->escape( $shipping_name ) . '"' );
		}

		$paymentAddress = EshopHelper::getAddress( JFactory::getSession()->get( 'payment_address_id' ) );
		$shippingAddress = EshopHelper::getAddress( JFactory::getSession()->get( 'shipping_address_id' ) );
		if ( ! $user->get('id') || ! $paymentAddress ) {
			$guest = JFactory::getSession()->get( 'guest' );
			$paymentAddress = isset( $guest['payment'] ) ? $guest['payment'] : '';
		}
		if ( ! $user->get( 'id' ) ) {
			$guest = JFactory::getSession()->get( 'guest' );
			$shippingAddress = ! empty( $guest['shipping'] ) ? $guest['shipping'] : '';
		}
		$paymentAddress = empty( $paymentAddress ) ? new stdclass : (object) $paymentAddress;
		$shippingAddress = empty( $shippingAddress ) ? new stdclass : (object) $shippingAddress;

		foreach ( $cartData as $k => $v ) {
			$cartData[ $k ] = (object) $v;
		}

		$paymentMethod = JFactory::getSession()->get( 'payment_method' );
		if ( ! empty( $paymentMethod ) ) {
			$paymentMethod = AC()->db->get_object( 'SELECT * FROM #__eshop_payments WHERE published=1 AND name="' . AC()->db->escape( $paymentMethod ) . '" ORDER BY ordering' );
		}

		$this->o_cart = (object) array(
			'cart' => new EshopCart(),
			'addressBill' => $paymentAddress,
			'addressShip' => $shippingAddress,
			'products' => $cartData,
			'shipping'=> empty( $shippingMethod ) ? new stdclass: (object) $shippingMethod,
			'payment_method'=> empty( $paymentMethod ) ? new stdclass: $paymentMethod,
			'taxes' => $taxes,
			'totals' => $totaldatareset,
			'total' => $total,
		);
	}

	public function cart_coupon_validate( $coupon_code ) {
		if ( ! AC()->is_request( 'frontend' ) ) {
			return;
		}
		if ( is_numeric( $coupon_code ) ) {
			return;
		}

		$this->init();
		$this->coupon_code = $coupon_code;
		if ( ! $this->o_cart->cart->hasProducts() ) {
			return;
		}

		$coupon_row = $this->session_get( 'coupon' );
		if ( empty( $coupon_row ) ) {
			//------START STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------
			if ( $this->params->get( 'enable_store_coupon', 0 ) == 1 ) {
				$tmp = AC()->db->get_value( 'SELECT id FROM #__cmcoupon WHERE estore="' . $this->estore . '" AND coupon_code="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );
				if ( empty( $tmp ) && $this->is_coupon_in_store( $coupon_code ) ) {
					return;
				}
			}
			//------END   STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------
		}

	  	$rtn = $this->process_coupon_helper();

		return $rtn
			? array('cmcoupon_processing')
			: array()
		;
	}

	public function cart_coupon_validate_auto() {
		$this->init();
		if ( ! $this->o_cart->cart->hasProducts() ) {
			return;
		}
		$codes = $this->process_autocoupon_helper();
	}

	public function cart_coupon_validate_balance() {
		$this->init();
		if ( ! $this->o_cart->cart->hasProducts() ) {
			return;
		}

		$this->coupon_code = $this->coupon_code_balance;
	  	$this->process_coupon_helper();
	}

	public function cart_calculate_totals( & $totalData, & $total, & $taxes ) {
		$cmsess = $this->get_coupon_session();
		if ( empty( $cmsess ) ) {
			return;
		}
		$this->init();
		$this->process_coupon_helper();

		// remove coupon code from session so eshop does not try to process through its system
		JFactory::getSession()->clear('coupon_code');

		// check for warning messages, error handling
		$error_msgs = $this->session_get( 'warning' );
		if ( ! empty( $error_msgs ) ) {
			JFactory::getSession()->set( 'warning', $error_msgs );
			$this->session_set( 'warning', null );
			JFactory::getSession()->clear( 'success' );
		}

		// get the session again in case it has changed
		$cmsess = $this->get_coupon_session();
		{ // check if all cupons are auto 
			$isAutoCheck = true;
			foreach ( $cmsess->processed_coupons as $item ) {
				if ( $item->isauto != 1 ) {
					$isAutoCheck = false;
					break;
				}
			}
			$this->isAutoCheck = true;
		}

		$this->finalize_coupon_store( $cmsess );

		if ( ! empty( $this->eshopCouponCosts ) ) {
			$total -= $this->eshopCouponCosts['total'];
			foreach ( $this->eshopCouponCosts['taxes'] as $id => $amount ) {
				if ( ! isset( $taxes[ $id ] ) ) {
					$taxes[ $id ] = 0;
				}
				$taxes[ $id ] += $amount;
			}
			foreach ( $this->eshopCouponCosts['totalData'] as $line ) {
				$totalData[] = $line;
			}
			return true;
		}
	}

	public function cart_coupon_delete( $coupon_id = 0 ) {
		$this->init();
		$this->delete_coupon_from_session( $coupon_id );

		$this->process_coupon_helper();
	}

	public function cart_coupon_displayname( $coupon_code ) {
	}

	public function order_new( $order ) {
		$this->init();

		if ( empty( $order->id ) ) {
			return null;
		}

		$coupon_session = $this->session_get( 'coupon' );
		if ( empty( $coupon_session ) ) {
			return null;
		}

		{ // update the ordertotals table and remove html
			$group_entered_coupons = array();
			foreach ( $coupon_session->processed_coupons as $id => $coupon ) {
				if ( ! isset( $group_entered_coupons[ $coupon->coupon_entered_id ] ) ) {
					$group_entered_coupons[ $coupon->coupon_entered_id ] = array();
				}
				$group_entered_coupons[ $coupon->coupon_entered_id ][] = $coupon;
			}
			foreach ( $group_entered_coupons as $id => $coupons ) {

				$ordertotals_id = AC()->db->get_value( 'SELECT id FROM #__eshop_ordertotals WHERE order_id=' . (int) $order->id . ' AND name="cmcoupon' . $id . '"' );
				if ( empty( $ordertotals_id ) ) {
					continue;
				}
				// fix title for db purposes
				$coupon = current( $coupons );
				$title = $coupon->isauto ? $this->get_frontend_lang( 'auto' ) : JText::_( 'COM_CMCOUPON_CP_COUPON' ) . ' (' . $coupon->coupon_code . ')';
				AC()->db->query( 'UPDATE #__eshop_ordertotals SET title="' . $title . '" WHERE id=' . (int) $ordertotals_id );
			}
		}

		$this->save_coupon_history( (int) $order->id );
		return true;
	}

	public function order_status_changed( $order ) {
		$order_id = @$order->id;
		$status_to = @$order->order_status_id;
		$this->cleanup_ordercancel_helper( $order_id, $status_to );
		return true;
	}

	protected function initialize_coupon() {
		parent::initialize_coupon();
		JFactory::getSession()->clear( 'coupon_code' );
	}

	protected function finalize_coupon( $master_output ) {
		$session_array = $this->save_discount_to_session( $master_output );
		if ( empty( $session_array ) ) {
			return false;
		}
		$this->finalize_coupon_store( $session_array );

		return true;
	}

	protected function finalize_coupon_store( $coupon_session ) {

		$currency = new EshopCurrency();
		$taxes = array();
		$totalData = array();
		$total = 0;

		{ // totals
			$group_entered_coupons = array();
			
			{ // load coupon view
				AC()->helper->loadLanguageSite();
				if ( ! class_exists( 'CmCouponSiteController' ) ) {
					require JPATH_ROOT . '/components/com_cmcoupon/controller.php';
				}
				$frontcontroller = new CmCouponSiteController( array( 'base_path' => JPATH_ROOT . '/components/com_cmcoupon' ) );
				$frontcontroller->addViewPath( JPATH_ROOT . '/components/com_cmcoupon/views' );
				$coupondelete_view = $frontcontroller->getView( 'coupondelete', 'html' );
				$coupondelete_view->addTemplatePath( JPATH_ROOT . '/components/com_cmcoupon/views/coupondelete/tmpl' );
				$template = JFactory::getApplication()->getTemplate();
				if ( $template ) {
					$coupondelete_view->addTemplatePath( JPATH_ROOT . '/templates/' . $template . '/html/com_cmcoupon/coupondelete' );
				}
			}

			foreach ( $coupon_session->processed_coupons as $id => $coupon ) {

				if ( ! isset( $group_entered_coupons[ $coupon->coupon_entered_id ] ) ) {
					$group_entered_coupons[ $coupon->coupon_entered_id ] = array();
				}
				$group_entered_coupons[ $coupon->coupon_entered_id ][] = $coupon;
			}
			foreach ( $group_entered_coupons as $id => $coupons ) {
				$tmp_total = 0;
				foreach ( $coupons as $coupon ) {
					$product_amount = empty( $coupon->product_discount_tax ) ? $coupon->product_discount : $coupon->product_discount_notax;
					$shipping_amount = empty( $coupon->shipping_discount_tax ) ? $coupon->shipping_discount : $coupon->shipping_discount_notax;
					$tmp_total += $product_amount + $shipping_amount;
				}

				$title = $coupon->display_text;
				if ( ! $coupon->isauto ) { 
				// load coupon view
					$link = 'index.php?option=com_eshop&view=cart&task=deletecoupons&task2=deletecoupons&cid=' . $coupon->coupon_entered_id;
					$coupondelete_view->coupons = array( $coupon->coupon_entered_id => array( 'text' => $title, 'link' => $link ) );
					$coupondelete_view->coupons_original_text = $coupon->coupon_code;
					$coupondelete_view->layout_type = 'span';
					$title = $coupondelete_view->loadTemplate( null );
				}

				if ( empty( $title ) && $coupon->isauto ) {
					$title = $this->get_frontend_lang( 'auto' );
				}

				$totalData[] = array(
					'name'		=> 'cmcoupon' . $id,
					'title'		=> $title,
					'text'		=> AC()->storecurrency->format( -1 * $tmp_total, AC()->storecurrency->get_current_currencycode() ), 
					'value'		=> -1 * $tmp_total,
				);
				$total += $tmp_total;
			}
		}
		
		// product tax
		foreach ( $coupon_session->cart_items_breakdown as $product ) {
			if ($coupon_session->product_discount_tax > 0 && ! empty( $product->product_taxclass_id ) ) {

				$total_tax_percent = 0;
				foreach ( $product->tax_rates as $rate ) {
					if ( $rate->tax_type != 'P' ) {
						continue;
					}
					$total_tax_percent += $rate->amount;
				}

				foreach ( $product->tax_rates as $rate ) {
					if ( $rate->tax_type != 'P' ) {
						continue;
					}
					$tmp_amount = $total_tax_percent >= $product->totaldiscount_tax
						? $product->totaldiscount_tax * $rate->amount / $total_tax_percent
						: $rate->amount
					;
					if ( empty( $tmp_amount ) ) {
						continue;
					}
					if ( ! isset( $taxes[ $rate->tax_rate_id ] ) ) {
						$taxes[ $rate->tax_rate_id ] = 0;
					}
					$taxes[ $rate->tax_rate_id ] -= $tmp_amount;
				}

				if ( $total_tax_percent < $product->totaldiscount_tax ) {
					$remaining_tax_discounts = $product->totaldiscount_tax - $total_tax_percent;
					foreach ( $product->tax_rates as $rate ) {
						if ( $rate->tax_type != 'F' ) {
							continue;
						}
						if ( ! isset( $taxes[ $rate->tax_rate_id ] ) ) {
							$taxes[ $rate->tax_rate_id ] = 0;
						}
						$tmp_value = min( $remaining_tax_discounts, $rate->amount );
						$remaining_tax_discounts -= $tmp_value;
						$taxes[ $rate->tax_rate_id ] -= $tmp_value;
						if ( $remaining_tax_discounts <= 0 ) {
							break;
						}
					}
				}
			}
		}

		{ // shipping tax
			if ( $coupon_session->shipping_discount_tax > 0 ) {
				$total_tax_percent = 0;
				foreach ( $this->o_cart->shipping->tax_rates as $taxRate ) {
					if ( $taxRate['tax_type'] != 'P' ) {
						continue;
					}
					$total_tax_percent += $taxRate['amount'];
				}

				foreach( $this->o_cart->shipping->tax_rates as $taxRate ) {
					if ($taxRate['tax_type'] != 'P') continue;

					$tmp_amount = $total_tax_percent >= $coupon_session->shipping_discount_tax
						? $coupon_session->shipping_discount_tax * $taxRate['amount'] / $total_tax_percent
						: $taxRate['amount']
					;
					if ( empty( $tmp_amount ) ) {
						continue;
					}
					if ( ! isset( $taxes[ $taxRate['tax_rate_id'] ] ) ) {
						$taxes[ $taxRate['tax_rate_id'] ] = 0;
					}
					$taxes[ $taxRate['tax_rate_id'] ] -= $tmp_amount;
				}

				if ( $total_tax_percent < $coupon_session->shipping_discount_tax ) {
					$remaining_tax_discounts = $coupon_session->shipping_discount_tax - $total_tax_percent;
					foreach ( $this->o_cart->shipping->tax_rates as $taxRate ) {
						if ( $taxRate['tax_type'] != 'F' ) {
							continue;
						}
						if ( ! isset( $taxes[ $taxRate['tax_rate_id'] ] ) ) {
							$taxes[ $taxRate['tax_rate_id'] ] = 0;
						}
						$tmp_value = min( $remaining_tax_discounts, $taxRate['amount'] );
						$remaining_tax_discounts -= $tmp_value;
						$taxes[ $taxRate['tax_rate_id'] ] -= $tmp_value;
						if ( $remaining_tax_discounts <= 0 ) {
							break;
						}
					}
				}
			}
		}

		$this->eshopCouponCosts = array(
			'total'=>$total,
			'totalData'=>$totalData,
			'taxes'=>$taxes,
		);
	}

	protected function getuniquecartstring( $coupon_code = null ) {
		if ( empty( $coupon_code ) ) {
			$coupon_code = JFactory::getSession()->get('coupon_code');
		}
		if ( empty( $coupon_code ) ) {
			return;
		}

		$user = JFactory::getUser();
		$user_email = ! empty( $this->o_cart->addressBill->email ) ? $this->o_cart->addressBill->email : '';
		$address = $this->get_customeraddress();
		$string = $this->o_cart->total . '|'
			. ( isset( $this->o_cart->shipping->name ) ? $this->o_cart->shipping->name : '' ) . '|'
			. ( isset( $this->o_cart->shipping->name ) ? $this->o_cart->shipping->cost : 0 ) . '|'
			. $address->country_id . '|'
			. $address->state_id . '|'
			. ( isset( $this->o_cart->payment_method->id ) ? $this->o_cart->payment_method->id : 0 ) . '|'
			. $coupon_code . '|'
			. $user->id . '|'
			. $user_email . '|'
			. AC()->storecurrency->get_current_currencycode()
		;
		foreach ( $this->o_cart->products as $k => $r ) {
			$string .= '|' . $k . '|' . $r->quantity;
		}
		return $string;
	}

	protected function getuniquecartstringauto() {
		$user = JFactory::getUser();
		$user_email = ! empty( $this->o_cart->addressBill->email ) ? $this->o_cart->addressBill->email : '';
		$address = $this->get_customeraddress();
		$string = $this->o_cart->total . '|'
			. ( isset( $this->o_cart->shipping->name ) ? $this->o_cart->shipping->name : '' ) . '|'
			. ( isset( $this->o_cart->shipping->cost ) ? $this->o_cart->shipping->cost : 0 ) . '|'
			. $address->country_id . '|'
			. $address->state_id . '|'
			. ( isset( $this->o_cart->payment_method->id ) ? $this->o_cart->payment_method->id : 0 ) . '|'
			. $user->id . '|'
			. $user_email . '|'
			. AC()->storecurrency->get_current_currencycode()
		;
		foreach ( $this->o_cart->products as $k => $r ) {
			$string .= '|' . $k . '|' . $r->quantity;
		}
		return $string;
	}

	protected function get_storeshoppergroupids( $user_id ) {
		return AC()->store->get_group_ids( $user_id );
	}

	protected function get_storecategory( $ids ) {
		$categorys = AC()->db->get_objectlist( 'SELECT category_id AS asset_id, product_id FROM #__eshop_productcategories WHERE product_id IN (' . $ids . ')' );
		if ( empty( $categorys ) ) {
			return array();
		}

		$category_index = array();
		foreach ( $categorys as $cat ) {
			if ( ! isset( $category_index[ $cat->asset_id ] ) ) {
				$category_index[ $cat->asset_id ] = array();
			}
			$category_index[ $cat->asset_id ][] = $cat->product_id;
		}

		// get parent categories
		for ( $i = 0; $i < 5; $i ++ ) {
			if ( empty( $category_index ) ) {
				break;
			}
			$sql = 'SELECT category_parent_id, id FROM #__eshop_categories WHERE id IN (' . implode( ',', array_keys( $category_index ) ) . ')';
			$items = AC()->db->get_objectlist( $sql );
			$tmp_category_index = array();
			foreach ( $items as $item ) {
				if ( empty( $item->category_parent_id ) ) {
					continue;
				}
				foreach ( $category_index[ $item->id ] as $product_id ) {
					$categorys[] = (object) array(
						'asset_id' => $item->category_parent_id,
						'product_id' => $product_id,
					);
				}
				$tmp_category_index[ $item->category_parent_id ] = $category_index[ $item->id ];
			}
			$category_index = $tmp_category_index;
		}
		return $categorys;
	}

	protected function get_storemanufacturer( $ids ) {
		return AC()->db->get_objectlist( 'SELECT manufacturer_id AS asset_id, id AS product_id FROM #__eshop_products WHERE id IN (' . $ids . ')' );
	}

	protected function get_storevendor( $ids ) {
		return array();
	}

	protected function get_storecustom( $ids ) {
		return array();
	}

	protected function get_storeshipping() {
		$shipping = (object) array(
			'shipping_id' => 0,
			'total_notax' => 0,
			'total' => 0,
			'shippings' => array(),
		);
		if ( ! empty( $this->o_cart->shipping->shipping_id ) ) {
			$shippings = array();
			$shippings[] = (object) array(
				'shipping_id' => (int) $this->o_cart->shipping->shipping_id,
				'total_notax' => (float) $this->o_cart->shipping->cost,
				'total' => (float) $this->o_cart->shipping->cost + (float) $this->o_cart->shipping->tax,
				'tax_rate' => empty( round( $this->o_cart->shipping->cost, 10 ) ) ? 0 : ( $this->o_cart->shipping->tax ) / $this->o_cart->shipping->cost,
				'totaldiscount' => 0,
				'totaldiscount_notax' => 0,
				'totaldiscount_tax' => 0,
				'coupons' => array(),
			);
			$shipping = (object) array(
				'shipping_id' => $shippings[0]->shipping_id,
				'total_notax' => $shippings[0]->total_notax,
				'total' => $shippings[0]->total,
				'shippings' => $shippings,
			);
		}
		return $shipping;
	}

	protected function get_storepayment() {
		$payment = (object) array(
			'payment_id' => isset( $this->o_cart->payment_method->id ) ? (int) $this->o_cart->payment_method->id : 0,
			'total_notax' => 0,
			'total' => 0,
		);
		
		return $payment;
	}

	protected function get_customeraddress() {
		$address = (object) array(
			'email' =>  isset( $this->o_cart->addressBill->email ) ? $this->o_cart->addressBill->email : '',
			'state_id' => 0,
			'state_name' => '',
			'country_id' => 0,
			'country_name' => '',
		);

		if ( empty( $this->o_cart->addressBill->id ) ) {
			return $address;
		}

		$address->state_id = $this->o_cart->addressBill->zone_id;
		$address->state_name = $this->o_cart->addressBill->zone_name;
		$address->country_id = $this->o_cart->addressBill->country_id;
		$address->country_name = $this->o_cart->addressBill->country_name;

		return $address;
	}

	protected function get_submittedcoupon() {
		if ( empty( $this->coupon_code ) ) {
			return '';
		}

		if ( $this->coupon_code == $this->get_frontend_lang( 'auto' ) ) {
			return '';
		}

		$cmsess = $this->get_coupon_session();
		if ( ! empty( $cmsess ) ) {
			if ( $cmsess->coupon_code == $this->coupon_code ) {
				return '';
			}
		}

		return $this->coupon_code; 
	}

	protected function set_submittedcoupon( $coupon_code ) {
		$this->coupon_code = $coupon_code;
	}

	protected function get_orderemail( $order_id ) {
		return AC()->db->get_value( 'SELECT email FROM #__eshop_orders WHERE id=' . (int) $order_id );
	}

	protected function is_coupon_in_store( $coupon_code ) {
		if ( empty( $coupon_code ) ) {
			return false;
		}
		$coupon_id = (int) AC()->db->get_value( 'SELECT id FROM #__eshop_coupons WHERE coupon_code="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );
		return $coupon_id > 0 ? true : false;
	}

	protected function define_cart_items( $is_refresh = false ) {
		// retreive cart items
		$this->cart = new stdClass();
		$this->cart->items = array();
		$this->cart->items_def = array();
		$this->product_total = 0;
		$this->product_qty  = 0;

		if ( $is_refresh ) {
			$this->o_cart->cart = new EshopCart();
		}

		$this->o_cart->products = $this->o_cart->cart->getCartData();
		foreach ( $this->o_cart->products as $k => $v ) {
			$this->o_cart->products[ $k ] = (object) $v;
		}
		$cart_products = $this->o_cart->products;

		JPluginHelper::importPlugin( 'cmcoupon' );
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger( 'cmDefineCartItemsBefore', array( & $cart_products, $this ) );

		$user = JFactory::getUser();
		$tax = new EshopTax( EshopHelper::getConfig() );
		if ( $user->get( 'id' ) ) {
			$customer = new EshopCustomer();
			$customerGroupId = $customer->getCustomerGroupId();
		}
		else {
			$customerGroupId = EshopHelper::getConfigValue( 'customergroup_id' );
		}

		foreach ( $cart_products as $cartpricekey => $product ) {
			$product_id = $product->product_id;

			if ( empty( $product_id ) ) {
				continue;
			}
			if ( empty( $product->quantity ) ) {
				continue;
			}

			$is_special = false;
			$is_discounted = false;
			$tmp = (float) AC()->db->get_value( '
				SELECT price
				  FROM #__eshop_productspecials
				 WHERE product_id = ' . $product_id . '
				   AND customergroup_id = ' . (int) $customerGroupId . '
				   AND date_start = "0000-00-00" OR date_start < NOW()
				   AND date_end = "0000-00-00" OR date_end > NOW()
				   AND published = 1
				 ORDER BY priority ASC, price ASC LIMIT 1
			' );
			if ( $tmp > 0) {
				$is_special = true;
			}

			if(!$is_special) {
				//Check discount price
				$discountQuantity = 0;
				foreach ( $cart_products as $key2 => $product2 ) {
					if ( $product2->product_id == $product->product_id ) {
						$discountQuantity += $product2->quantity;
					}
				}
				$tmp = (float) AC()->db->get_value( '
					SELECT price
					  FROM #__eshop_productdiscounts
					 WHERE product_id = ' . $product_id . '
					   AND customergroup_id = ' . (int) $customerGroupId . '
					   AND quantity <= ' . (int) $discountQuantity . '
					   AND date_start = "0000-00-00" OR date_start < NOW()
					   AND date_end = "0000-00-00" OR date_end > NOW()
					   AND published = 1
					 ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1
				' );
				if ( $tmp > 0) {
					$is_discounted = true;
				}
			}

			$product_tax = 0;
			if ( $product->product_taxclass_id ) {
				$taxRates = $tax->getTaxRates( $product->price, $product->product_taxclass_id );
				foreach ( $taxRates as $k => $taxRate ) {
					if ( $taxRate['amount'] <= 0 ) {
						unset( $taxRates[ $k ] );
						continue;
					}
					$product_tax += $taxRate['amount'];
				}
			}

			$this->cart->items_def[ $product_id ]['product'] = $product_id;
			$this->cart->items[] = array(
				'product_id' => $product_id,
				'cartpricekey' => $cartpricekey,
				'discount' => 0,
				'product_price' => $product->price + $product_tax,
				'product_price_notax' => $product->price,
				'product_price_tax' => $product_tax,
				'tax_rate' => $product_tax / $product->price,
				'qty' => $product->quantity,
				'is_special' => $is_special,
				'is_discounted' => $is_discounted,

				'product_taxclass_id' => $product->product_taxclass_id,
				'tax_rates' => isset( $taxRates ) ? $taxRates : array(),
			);
			$this->product_total += $product->quantity * ( $product->price + $product_tax );
			$this->product_qty += $product->quantity;
		}

		JPluginHelper::importPlugin( 'cmcoupon' );
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger( 'cmDefineCartItemsAfter', array( & $this->cart->items_def, & $this->cart->items, $cart_products, $this ) );

		parent::define_cart_items();
	}

	protected function return_false( $key, $type = 'key', $force = 'donotforce' ) {
		if ( isset( $this->isAutoCheck ) && $this->isAutoCheck==true && empty( $this->coupon_row ) ) {
			return;
		}

		parent::return_false( $key, $type, $force );
		if ( ! empty( $this->error_msgs ) ) {
			$this->session_set( 'warning', '<div>' . implode( '</div><div>', $this->error_msgs ) . '</div>' );
		}

	}

	protected function get_frontend_lang( $key ) {
		AC()->helper->loadLanguageSite();
		switch ( $key ) {
			case 'auto':
				return JText::_( 'COM_CMCOUPON_CP_DISCOUNT_AUTO' );
			case 'store_credit':
				return JText::_( 'COM_CMCOUPON_GC_STORECREDIT' );
		}
		return '';
	}

	protected function buyxy_getproduct( $coupon_row, $assetlist, $exclude_products = array() ) {
		$db = AC()->db;

		$the_product = 0;

		if ( empty( $exclude_products ) ) {
			$exclude_products = array();
		}
		$where_product = empty( $exclude_products ) ? '' : ' AND p.id NOT IN (' . implode( ',', $exclude_products ) . ') ';

		foreach ( $assetlist as $asset_type => $assetelement ) {
			if ( empty( $assetelement->rows ) ) {
				continue;
			}
			$mode = empty( $assetelement->mode ) ? 'include' : $assetelement->mode;
			$ids = implode( ',', array_keys( $assetelement->rows ) );

			$max_tries = 200;
			$used_ids = array( 0 );

			$user = JFactory::getUser();
			if ( $user->get( 'id' ) ) {
				$customer = new EshopCustomer();
				$customerGroupId = $customer->getCustomerGroupId();
			}
			else {
				$customerGroupId = EshopHelper::getConfigValue( 'customergroup_id' );
			}

			do {
				if ( 'include' == $mode ) {
					if ( 'product' == $asset_type ) {
						$sql = 'SELECT p.id
								  FROM #__eshop_products p
								 WHERE p.published=1
								   AND id IN (' . $ids . ')
								   AND p.id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
								 ';
						$the_product = $db->get_value( $sql );
					} elseif ( 'category' == $asset_type ) {
						$sql = 'SELECT c.product_id
								  FROM #__eshop_productcategories c
								  JOIN #__eshop_products p ON p.id=c.product_id
								 WHERE p.published=1
								   AND c.category_id IN (' . $ids . ')
								   AND p.id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
								 ';
						$the_product = $db->get_value( $sql );
					} elseif ( 'manufacturer' == $asset_type ) {
						$sql = 'SELECT p.id
								  FROM #__eshop_products p
								 WHERE p.published=1
								   AND manufacturer_id IN (' . $ids . ')
								   AND p.id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
								 ';
						$the_product = $db->get_value( $sql );
					} elseif ( 'vendor' == $asset_type ) {
					}
				} elseif ( 'exclude' == $mode ) {
					if ( 'product' == $asset_type ) {
						$sql = 'SELECT p.id
								  FROM #__eshop_products p
								 WHERE p.published=1
								   AND id NOT IN (' . $ids . ')
								   AND p.id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
								 ';
						$the_product = $db->get_value( $sql );
					} elseif ( 'category' == $asset_type ) {
						$sql = 'SELECT c.product_id
								  FROM #__eshop_productcategories c
								  JOIN #__eshop_products p ON p.id=c.product_id
								 WHERE p.published=1
								   AND c.category_id NOT IN (' . $ids . ')
								   AND p.id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
								 ';
						$the_product = $db->get_value( $sql );
					} elseif ( 'manufacturer' == $asset_type ) {
						$sql = 'SELECT p.id
								  FROM #__eshop_products p
								 WHERE p.published=1
								   AND manufacturer_id NOT IN (' . $ids . ')
								   AND p.id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
								 ';
						$the_product = $db->get_value( $sql );
					} elseif ( 'vendor' == $asset_type ) {
					}
				}

				if ( empty( $the_product ) ) {
					break;
				}

				if ( ! empty( $the_product ) && ! empty( $coupon_row->params->exclude_special ) ) {
					$tmp = (float) AC()->db->get_value( '
						SELECT price
						  FROM #__eshop_productspecials
						 WHERE product_id = ' . $the_product . '
						   AND customergroup_id = ' . (int) $customerGroupId . '
						   AND date_start = "0000-00-00" OR date_start < NOW()
						   AND date_end = "0000-00-00" OR date_end > NOW()
						   AND published = 1
						 ORDER BY priority ASC, price ASC LIMIT 1
					' );
					if ( $tmp > 0) {
						$used_ids[] = $the_product;
						$the_product = 0;
					}
				}
				if ( ! empty( $the_product ) && ! empty( $coupon_row->params->exclude_discounted ) ) {
					$tmp = (float) AC()->db->get_value( '
						SELECT price
						  FROM #__eshop_productdiscounts
						 WHERE product_id = ' . $the_product . '
						   AND customergroup_id = ' . (int) $customerGroupId . '
						   AND quantity <= 1
						   AND date_start = "0000-00-00" OR date_start < NOW()
						   AND date_end = "0000-00-00" OR date_end > NOW()
						   AND published = 1
						 ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1
					' );
					if ( $tmp > 0) {
						$used_ids[] = $the_product;
						$the_product = 0;
					}
				}

				$max_tries --;
				if ( $max_tries <= 0 ) {
					break;
				}
			} while ( 0 == $the_product );

			if ( ! empty( $the_product ) ) {
				break;
			}
		}
		return $the_product;
	}

	protected function add_to_cart( $product_id, $qty ) {
		$qty = (int) $qty;
		$product_id = (int) $product_id;
		if ( empty( $qty ) || empty( $product_id ) ) {
			return;
		}

		$is_update = false;
		$cartpricekey = 0;
		foreach ( $this->cart->items as $k => $r ) {			
			if ( $r['product_id'] == $product_id ) {
				$is_update = true;
				$qty += $r['qty'];
				$cartpricekey = $r['cartpricekey'];
				break;
			}
		}

		if ( $is_update ) {
			$this->o_cart->cart->update( $cartpricekey, $qty );
		}
		else {
			$productOptions = EshopHelper::getProductOptions( $product_id, JFactory::getLanguage()->getTag() );
			$options = array();
			foreach ( $productOptions as $productOption ) {
				if ( $productOption->required ) {
					 $productOptionValues = EshopHelper::getProductOptionValues( $product_id, $productOption->id );

					$selection = current( $productOptionValues );
					if ( $productOption->option_type == 'Checkbox' ) {
						$options[ $productOption->product_option_id ][] = $selection->id;
					}
					else {
						$options[ $productOption->product_option_id ] = $selection->id;
					}
				}
			}
			$this->o_cart->cart->add( $product_id, $qty, $options );
		}

		return true;
	}
}

