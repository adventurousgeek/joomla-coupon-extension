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

class CmCoupon_Helper_Estore_Redshop_Discount extends CmCoupon_Library_Discount {

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

		$this->estore = 'redshop';
		$this->default_err_msg = JText::_( 'Coupon not found' );

		// Load redSHOP Library
		JLoader::import('redshop.library');

		if ( ! class_exists( 'RedshopModelConfiguration' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_redshop/models/configuration.php';
		}
		$configClass = new RedshopModelConfiguration();
		$this->version = $configClass->getCurrentVersion();

		if ( version_compare( $this->version, '2.0.0', '>=' ) ) {
			if ( ! class_exists( 'rsCarthelper' ) ) {
				require JPATH_ROOT . '/components/com_redshop/helpers/rscarthelper.php';
			}
			if ( ! class_exists( 'order_functions' ) ) {
				require JPATH_ADMINISTRATOR . '/components/com_redshop/helpers/order_functions.php';
			}
			if ( ! class_exists( 'producthelper' ) ) {
				JLoader::import( 'redshop.library' );
				require JPATH_ROOT . '/components/com_redshop/helpers/producthelper.php';
			}
		}
		else {
			if ( ! class_exists( 'rsCarthelper' ) ) {
				require JPATH_ROOT . '/components/com_redshop/helpers/helper.php';
				require JPATH_ROOT . '/components/com_redshop/helpers/cart.php';
			}
			if ( ! class_exists( 'order_functions' ) ) {
				require JPATH_ADMINISTRATOR . '/components/com_redshop/helpers/order.php';
			}
			if ( ! class_exists( 'producthelper' ) ) {
				require_once JPATH_ADMINISTRATOR . '/components/com_redshop/helpers/redshop.cfg.php';
				JLoader::import( 'product', JPATH_ADMINISTRATOR . '/components/com_redshop/helpers' );
			}
		}
	}

	public function init() {
		$this->o_cart = JFactory::getSession()->get('cart');

		$this->product_total = (float) @$this->o_cart['product_subtotal'];
		if ( ! empty( $this->o_cart['coupon'] ) ) {
			foreach ( $this->o_cart['coupon'] as $c ) {
				$this->product_total += $c['product_discount'];
			}
		}

		$order_func = new order_functions();
		$user = JFactory::getUser ();
		if ( ! empty( $user->id ) ) {
			$this->customer = $order_func->getBillingAddress( $user->id );
		}
		else {
			$auth = JFactory::getSession()->get( 'auth') ;
			if ( $auth['users_info_id'] ) {
				$uid = -$auth['users_info_id'];
				$this->customer = $order_func->getBillingAddress( $uid );
			}
		}
	}

	public function cart_coupon_validate( $cart, $coupon_code ) {
		$this->init();

		if ( ! AC()->is_request( 'frontend' ) ) {
			return;
		}

		if ( empty( $coupon_code ) ) {
			return;
		}

		if ( ! empty( $cart ) && count( $cart ) > 0 ) {
			$this->o_cart = $cart;
		}

		//------START STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------
		if ( $this->params->get( 'enable_store_coupon', 0 ) == 1 ) {
			$tmp = AC()->db->get_value( 'SELECT id FROM #__cmcoupon WHERE estore="' . $this->estore . '" AND coupon_code="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );
			if ( empty( $tmp ) && $this->is_coupon_in_store( $coupon_code ) ) {
				return null;
			}
		}
		//------END   STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------

		$this->rscoupon_code = $coupon_code;
		$bool = $this->process_coupon_helper( );
		return ! empty( $cart ) ? $this->o_cart : $bool;
	}

	public function cart_coupon_validate_auto() {
		$this->init();

		// process discount
		$codes = $this->process_autocoupon_helper();
		if ( empty( $codes ) ) {
			return;
		}

		// recalculate totals in cart
		$cart_class = new rsCarthelper();
		$cart_class->modifyDiscount( $this->o_cart );
	}

	public function cart_coupon_validate_balance() {
		$this->init();

		// process discount
		$this->rscoupon_code	= $this->coupon_code_balance;
		if ( ! $this->process_coupon_helper() ) {
			return;
		}

		// recalculate totals in cart
		$cart_class = new rsCarthelper();
		$cart_class->modifyDiscount( $this->o_cart );
	}

	public function cart_calculate_totals( &$cart ) {
	}

	public function cart_coupon_delete( $coupon_id = '' ) {
		$this->init();
		parent::delete_coupon_from_session( $coupon_id );
	}

	public function cart_coupon_displayname( $coupon_code ) {
	}

	public function order_new( $order_id ) {
		$this->init();
		$this->save_coupon_history( (int) $order_id );
		return true;
	}

	public function order_status_changed( $order_id, $status_to ) {
		$this->cleanup_ordercancel_helper( $order_id, $status_to );
		return true;
	}

	protected function initialize_coupon() {
		parent::initialize_coupon();

		if ( empty( $this->o_cart['coupon'] ) ) {
			$this->o_cart['coupon'] = array();
		}
		foreach ( $this->o_cart['coupon'] as $k => $row ) {
			if ( empty( $row['cmcoupon'] ) ) {
				continue;
			}
			unset( $this->o_cart['coupon'][ $k ] );
		}
		$this->o_cart['coupon'] = array_values( $this->o_cart['coupon'] );
		$this->o_cart['free_shipping'] = 0;

		// store data
		JFactory::getSession()->set('cart',$this->o_cart);		
	}

	protected function finalize_coupon( $master_output ) {
		$session_array = $this->save_discount_to_session( $master_output );
		if ( empty( $session_array ) ) {
			return false;
		}

		$data = array(
			'coupon_code' => $session_array->coupon_code,
			'coupon_id' => -1,
			'used_coupon' => 1,
			'coupon_value' => $session_array->product_discount + $session_array->shipping_discount,
			'remaining_coupon_discount' => 0,
			'transaction_coupon_id' => 0,
			'cmcoupon' => true,
			'product_discount' => $session_array->product_discount,
			'shipping_discount' => $session_array->shipping_discount,
		);
		$current_coupon_arr = ! empty( $this->o_cart['coupon'] ) ? $this->o_cart['coupon'] : array();
		$current_coupon_arr = array_merge( array( $data ),$current_coupon_arr );
		$this->o_cart['coupon_discount'] = $data['coupon_value'];
		$this->o_cart['coupon'] = $current_coupon_arr;
		$this->o_cart['free_shipping'] = 0;

		// store data
		JFactory::getSession()->set('cart',$this->o_cart);

		return true;
	}

	protected function finalize_coupon_store( $coupon_session ) {
	}

	protected function getuniquecartstring( $coupon_code = null ) {
		return mt_rand();
	}

	protected function getuniquecartstringauto() {
		return mt_rand();
	}

	protected function get_storeshoppergroupids( $user_id ) {
		return AC()->store->get_group_ids( $user_id );
	}

	protected function get_storeproduct( $ids ) {
		if ( $this->params->get( 'disable_coupon_product_children', 0 ) == 1 ) {
			return array();
		}
		return AC()->db->get_objectlist( 'SELECT product_id FROM #__redshop_product WHERE product_parent_id IN (' . $ids . ')' );
	}

	protected function get_storecategory( $ids ) {
		$tmp1 = AC()->db->get_objectlist( 'SELECT category_id AS asset_id,product_id FROM #__redshop_product_category_xref WHERE product_id IN (' . $ids . ')' );
		$tmp2 = array();
		if ( $this->params->get( 'disable_coupon_product_children', 0 ) !== 1 ) {
			// get category list of parent products
			$tmp2 = AC()->db->get_objectlist( '
				SELECT category_id AS asset_id,p.product_id 
				  FROM #__redshop_product p 
				  JOIN #__redshop_product_category_xref c ON c.product_id=p.product_parent_id
				 WHERE p.product_id IN (' . $ids . ')
			' );
		}

		$categorys = array_merge( $tmp1, $tmp2 );
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
			$sql = 'SELECT category_parent_id, category_child_id FROM #__redshop_category_xref WHERE category_child_id IN (' . implode( ',', array_keys( $category_index ) ) . ')';
			$items = AC()->db->get_objectlist( $sql );
			$tmp_category_index = array();
			foreach ( $items as $item ) {
				if ( empty( $item->category_parent_id ) ) {
					continue;
				}
				foreach ( $category_index[ $item->category_child_id ] as $product_id ) {
					$categorys[] = (object) array(
						'asset_id' => $item->category_parent_id,
						'product_id' => $product_id,
					);
				}
				$tmp_category_index[ $item->category_parent_id ] = $category_index[ $item->category_child_id ];
			}
			$category_index = $tmp_category_index;
		}
		return $categorys;
	}

	protected function get_storemanufacturer( $ids ) {
		return AC()->db->get_objectlist( 'SELECT manufacturer_id AS asset_id,product_id FROM #__redshop_product WHERE product_id IN (' . $ids . ')' );
	}

	protected function get_storevendor( $ids ) {
		return AC()->db->get_objectlist( 'SELECT supplier_id AS asset_id,product_id FROM #__redshop_product WHERE product_id IN (' . $ids . ')' );
	}

	protected function get_storecustom( $ids ) {
		return array();
	}

	protected function get_storeshipping_isdefaultbypass( $coupon_row ) { 
		$shipping = $this->get_storeshipping();
		$shippinglist = empty( $coupon_row->asset[0]->rows->shipping->rows ) ? array() : $coupon_row->asset[0]->rows->shipping->rows;
		return ( empty( $shippinglist ) && empty( $shipping->shipping_id ) && ! empty( $this->o_cart['shipping'] ) ) ? true : false;
	}

	protected function get_storeshipping() {
		$shipping = (object) array(
			'shipping_id' => 0,
			'total_notax' => $this->realcart['shipping_total_notax'],
			'total' => $this->realcart['shipping_total'],
			'tax_rate' => empty( round( $this->realcart['shipping_total_notax'], 10 ) ) ? 0 : ( $this->realcart['shipping_total'] - $this->realcart['shipping_total_notax'] ) / $this->realcart['shipping_total_notax'],
			'totaldiscount' => 0,
			'totaldiscount_notax' => 0,
			'totaldiscount_tax' => 0,
			'coupons' => array(),
		);

		include_once JPATH_ADMINISTRATOR.'/components/com_redshop/helpers/shipping.php';
		$shippinghelper = new shipping();

		$shipping_rate_id = AC()->helper->get_request( 'shipping_rate_id' );
		if ( ! empty( $shipping_rate_id ) ) {
			if ( version_compare( $this->version, '2.0.0', '>=' ) ) { 
				$order_shipping   = RedshopShippingRate::decrypt( $shipping_rate_id );
			}
			else {
				$order_shipping = explode ( "|", $shippinghelper->decryptShipping( str_replace( " ", "+", $shipping_rate_id ) ) );
			}
		}
		if ( ! empty( $order_shipping ) ) {
			$this->o_cart['shipping'] = $order_shipping[3]+$order_shipping [6];
			$this->o_cart['shipping_tax'] = $order_shipping [6];
			$shipping = (object) array(
				'shipping_id' => $order_shipping[4],
				'total_notax' => $order_shipping[3],
				'total' => $order_shipping[3]+$order_shipping [6] ,
				'tax_rate' => empty( round( $order_shipping[3], 10 ) ) ? 0 : ( $order_shipping[6] ) / $order_shipping[3],
				'totaldiscount' => 0,
				'totaldiscount_notax' => 0,
				'totaldiscount_tax' => 0,
				'coupons' => array(),
			);
		}

		return (object) array(
			'shipping_id' => $shipping->shipping_id,
			'total_notax' => $shipping->total_notax,
			'total' => $shipping->total,
			'shippings' => array( $shipping ),
		);
	}

	protected function get_storepayment() {
		$payment = (object) array(
			'payment_id' => 0,
			'total_notax' => 0,
			'total' => 0,
		);

		$payment_method_id = AC()->helper->get_request( 'payment_method_id' );
		if ( empty( $payment_method_id ) ) {
			return $payment;
		}

		$order_functions = new order_functions;
		$paymentMethod = $order_functions->getPaymentMethodInfo( $payment_method_id );
		if ( ! empty( $paymentMethod[0]->extension_id ) ) {
			$payment->payment_id = $paymentMethod[0]->extension_id;
		}

		return $payment;
	}

	protected function get_customeraddress() {
		$address = (object) array(
			'email' => ! empty( $this->customer->user_email ) ? $this->customer->user_email : '',
			'state_id' => 0,
			'state_name' => '',
			'country_id' => 0,
			'country_name' => '',
		);

		if ( empty( $this->customer->country_code ) ) {
			return $address;
		}

		$country_id_name = version_compare( $this->version, '2.0.0', '>=' ) ? 'id' : 'country_id';
		$state_id_name = version_compare( $this->version, '2.0.0', '>=' ) ? 'id' : 'state_id';

		$item = AC()->db->get_object( 'SELECT ' . $country_id_name . ' AS country_id,country_name FROM #__redshop_country WHERE country_3_code="' . AC()->db->escape( $this->customer->country_code ) . '"');
		if ( empty( $item ) ) {
			return $address;
		}

		$address->country_id = $item->country_id;
		$address->country_name = $item->country_name;

		if ( empty( $this->customer->state_code ) ) {
			return $address;
		}

		$item = AC()->db->get_object('SELECT ' . $state_id_name . ' AS state_id,state_name FROM #__redshop_state WHERE country_id=' . $address->country_id . ' AND state_2_code="' . AC()->db->escape( $this->customer->state_code ) . '"');
		if ( empty( $item ) ) {
			return $address;
		}

		$address->state_id = $item->state_id;
		$address->state_name = $item->state_name;
				
		return $address;
	}

	protected function get_submittedcoupon() {
		return $this->rscoupon_code;
	}

	protected function set_submittedcoupon( $coupon_code ) {
		$this->rscoupon_code = $coupon_code;
	}

	protected function realtotal_verify( &$_product, &$_product_notax ) {
		$orig_product = $_product;
		if ( $this->realcart['product_total'] < $_product ) {
			$_product_notax = $this->realcart['product_total'] * $_product_notax / $_product;
			$_product = $this->realcart['product_total'];
		}
	}

	protected function get_orderemail( $order_id ) {
		if ( ! empty( $this->customer->user_email ) ) {
			return $this->customer->user_email;
		}
		return AC()->db->get_value( '
			SELECT u.user_email
			  FROM #__redshop_orders o 
			  JOIN #__redshop_order_users_info u on u.order_id=o.order_id
			 WHERE o.order_id=' . (int) $order_id . ' AND u.address_type="BT"
		' );
	}

	protected function is_coupon_in_store( $coupon_code ) {
		if ( empty( $coupon_code ) ) {
			return false;
		}
		$coupon_id = (int) AC()->db->get_value( 'SELECT coupon_id FROM #__redshop_coupons WHERE coupon_code="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );
		if(empty($coupon_id)) {
			$coupon_id = (int) AC()->db->get_value( 'SELECT voucher_id FROM #__redshop_product_voucher WHERE voucher_code="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );
		}
		return $coupon_id > 0 ? true : false;
	}

	protected function define_cart_items( $is_refresh = false ) {
		// retreive cart items
		$this->cart = new stdClass();
		$this->cart->items = array();
		$this->cart->items_def = array();
		$this->product_total = 0;
		$this->product_qty  = 0;

		JPluginHelper::importPlugin('cmcoupon');
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('cmDefineCartItemsBefore', array(& $this->o_cart, $this));

		for ( $i = 0; $i < $this->o_cart['idx']; $i++ ) {
			$product_id = $this->o_cart[ $i ]['product_id'];

			if ( empty( $product_id ) ) {
				continue;
			}
			if ( empty( $this->o_cart[ $i ]['quantity'] ) ) {
				continue;
			}

			$product_special = AC()->db->get_value( 'SELECT product_special FROM #__redshop_product WHERE product_id=' . (int) $product_id );
			$product_discount = max( 0, $this->o_cart[ $i ]['product_old_price'] - $this->o_cart[ $i ]['product_price'] );

			$this->cart->items_def[ $product_id ]['product'] = $product_id;
			$this->cart->items[] = array(
				'product_id' => $product_id,
				'cartpricekey' => $i,
				'discount' => $product_discount,
				'product_price' => $this->o_cart[ $i ]['product_price'],
				'product_price_notax' => $this->o_cart[ $i ]['product_price_excl_vat'],
				'product_price_tax' => $this->o_cart[ $i ]['product_price'] - $this->o_cart[ $i ]['product_price_excl_vat'],
				'tax_rate' => empty( $this->o_cart[ $i ]['product_price_excl_vat'] ) ? 0 : ( $this->o_cart[ $i ]['product_price'] - $this->o_cart[ $i ]['product_price_excl_vat'] ) / $this->o_cart[ $i ]['product_price_excl_vat'],
				'qty' => $this->o_cart[ $i ]['quantity'],
				'is_special' => $product_special,
				'is_discounted' => ! empty( $product_discount ) ? 1 : 0,
			);
			$this->product_total += $this->o_cart[ $i ]['quantity'] * $this->o_cart[ $i ]['product_price'];
			$this->product_qty += $this->o_cart[ $i ]['quantity'];
		}
		// get real totals
		$this->get_real_cart_total();

		JPluginHelper::importPlugin('cmcoupon');
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('cmDefineCartItemsAfter', array(& $this->cart->items_def, & $this->cart->items, $this->o_cart, $this));

		parent::define_cart_items();
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
		$where_product = empty( $exclude_products ) ? '' : ' AND p.product_id NOT IN (' . implode( ',', $exclude_products ) . ') ';

		foreach ( $assetlist as $asset_type => $assetelement ) {
			if ( empty( $assetelement->rows ) ) {
				continue;
			}
			$mode = empty( $assetelement->mode ) ? 'include' : $assetelement->mode;
			$ids = implode( ',', array_keys( $assetelement->rows ) );

			$max_tries = 200;
			$used_ids = array( 0 );

			do {
				if ( 'include' == $mode ) {
					if ( 'product' == $asset_type ) {
						$sql = 'SELECT product_id FROM #__redshop_product p WHERE published=1 '.$where_product.' AND product_id IN ('.$ids.') AND product_id NOT IN ('.implode(',',$used_ids).') LIMIT 1';
						$the_product = $db->get_value( $sql );
					} elseif ( 'category' == $asset_type ) {
						$sql = 'SELECT p.product_id
								  FROM #__redshop_product_category_xref c
								  JOIN #__redshop_product p ON p.product_id=c.product_id
								 WHERE p.published=1
								   AND c.category_id IN (' . $ids . ')
								   AND p.product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
								 ';
						$the_product = $db->get_value( $sql );
					} elseif ( 'manufacturer' == $asset_type ) {
						$sql = 'SELECT product_id FROM #__redshop_product p WHERE published=1 ' . $where_product . ' AND manufacturer_id IN (' . $ids . ') AND product_id NOT IN (' . implode( ',', $used_ids ) . ') LIMIT 1';
						$the_product = $db->get_value( $sql );
					} elseif ( 'vendor' == $asset_type ) {
						$sql = 'SELECT product_id FROM #__redshop_product p WHERE published=1 ' . $where_product . ' AND supplier_id IN (' . $ids . ') AND product_id NOT IN (' . implode( ',', $used_ids ) . ') LIMIT 1';
						$the_product = $db->get_value( $sql );
					}
				} elseif ( 'exclude' == $mode ) {
					if ( 'product' == $asset_type ) {
						$sql = 'SELECT product_id FROM #__redshop_product p WHERE published=1 ' . $where_product . ' AND product_id NOT IN (' . $ids . ') AND product_id NOT IN (' . implode( ',', $used_ids ) . ') LIMIT 1';
						$the_product = $db->get_value( $sql );
					} elseif ( 'category' == $asset_type ) {
						$sql = 'SELECT p.product_id
								  FROM #__redshop_product_category_xref c
								  JOIN #__redshop_product p ON p.product_id=c.product_id
								 WHERE p.published=1
								   AND c.category_id NOT IN (' . $ids . ')
								   AND p.product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
								 ';
						$the_product = $db->get_value( $sql );
					} elseif ( 'manufacturer' == $asset_type ) {
						$sql = 'SELECT product_id FROM #__redshop_product p WHERE published=1 ' . $where_product . ' AND manufacturer_id NOT IN (' . $ids . ') AND product_id NOT IN (' . implode( ',', $used_ids ) . ') LIMIT 1';
						$the_product = $db->get_value( $sql );
					} elseif ( 'vendor' == $asset_type ) {
						$sql = 'SELECT product_id FROM #__redshop_product p WHERE published=1 ' . $where_product . ' AND supplier_id NOT IN (' . $ids . ') AND product_id NOT IN (' . implode( ',', $used_ids ) . ') LIMIT 1';
						$the_product = $db->get_value( $sql );
					}
				}

				if ( empty( $the_product ) ) {
					break;
				}

				if ( ! empty( $the_product ) && ! empty( $coupon_row->params->exclude_special ) ) {
					$product_special = AC()->db->get_value( 'SELECT product_special FROM #__redshop_product WHERE product_id=' . (int) $the_product );
					if ( ! empty( $product_special ) ) {
						$used_ids[] = $the_product;
						$the_product = 0;
					}
				}
				if ( ! empty( $the_product ) && ! empty( $coupon_row->params->exclude_discounted ) ) {
					$producthelper = new producthelper;
					$product_prices = $producthelper->getProductNetPrice( $the_product );
					$product_discount = max( 0, $this->o_cart[ $i ]['product_old_price'] - $this->o_cart[ $i ]['product_price'] );
					if ( ! empty( $product_discount ) ) {
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

		$carthelper = new rsCarthelper();
		$result = $carthelper->addProductToCart( array( 'product_id' => $product_id, 'quantity' => $qty, 'sel_wrapper_id' => 0, ) );

		$this->o_cart = JFactory::getSession()->get('cart');
		return $result;
	}

	protected function get_real_cart_total() {
		$subtotal = 0;
		for ( $i = 0; $i < $this->o_cart['idx']; $i++ ) {
			$subtotal += $this->o_cart[ $i ]['product_price'] * $this->o_cart[ $i ]['quantity'];
		}
		$this->realcart['product_total'] = $subtotal - $this->o_cart['voucher_discount'] - $this->o_cart['cart_discount'] ;// - $cart['coupon_discount'];
		$this->realcart['shipping_total'] = $this->o_cart['shipping'];
		$this->realcart['shipping_total_notax'] = $this->o_cart['shipping'] - $this->o_cart['shipping_tax'];
		
		$product_discount = $shipping_total = 0;
		if ( ! empty( $this->o_cart['coupon'] ) ) {
			foreach ( $this->o_cart['coupon'] as $row ) {
				if ( ! empty( $row['cmcoupon'] ) && $row['cmcoupon'] ) {
					continue;
				}
				$product_discount += $row['coupon_value'];
			}
		}
		
		$this->realcart['product_total'] -= $product_discount;
		$this->realcart['product_total'] = max( 0, $this->realcart['product_total'] );
	}
}

