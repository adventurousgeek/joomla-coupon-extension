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

class CmCoupon_Helper_Estore_Virtuemart_Discount extends CmCoupon_Library_Discount {

	var $params = null;

	var $cart = null;
	var $o_cart = null;
	var $coupon_code = null;
	var $posted_order = null;

	var $product_total = 0;
	var $product_qty = 0;
	var $default_err_msg = '';
	var $vmversion = null;
	var $isrefresh = false;

	public static function instance( $class = null ) {
		return parent::instance( get_class() );
	}

	public function __construct() {
		parent::__construct();

		$this->estore = 'virtuemart';
		$this->default_err_msg = JText::_('COM_VIRTUEMART_COUPON_CODE_INVALID');
		$this->is_validateprocess = false;
		$this->is_currency_convert = false;

		if ( ! class_exists( 'VmConfig' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
		}
		VmConfig::loadConfig();

		if ( ! class_exists( 'VmVersion' ) ) {
			require JPATH_VM_ADMINISTRATOR . '/version.php';
		}			
		$this->vmversion = VmVersion::$RELEASE;	
		if ( preg_match( '/\d/', substr( $this->vmversion, -1 ) ) == false ) {
			$this->vmversion_letter = substr( $this->vmversion, -1 );
			$this->vmversion = substr( $this->vmversion, 0, -1 );
		}
		if ( ! class_exists( 'VirtueMartCart' ) ) {
			require JPATH_ROOT . '/components/com_virtuemart/helpers/cart.php';
		}

		if ( version_compare( $this->vmversion, '3.0.4', '>=' ) ) {
			$is_rupostel_opc = JFactory::getApplication()->get('is_rupostel_opc', false);
			$is_vmonepagecheckout = class_exists('plgSystemVPOnePageCheckout') ? true : false;
			if ( $is_rupostel_opc ) {
				// do  nothing
			}
			elseif( $is_vmonepagecheckout ) {
				if ( AC()->helper->get_request( 'ctask' ) != 'setcoupon' ) {
					$this->is_validateprocess = true;
				}
			}
			else {
				$this->is_validateprocess = true;
				$this->enqueue_error_msgs = false;
			}
		}

		// check injection
		$test = $this->session_get( 'virtuemart_use_injection_mode', null );
		if ( null === $test ) {
			$use_injection_mode = 0;
			if ( (int) $this->params->get( 'virtuemart_inject_totals', 0 ) == 1 ) {

				$installer = AC()->helper->new_class( 'CmCoupon_Admin_Class_Installation' );
				$test = $installer->get_data();

				if( isset( $test['calculationh']->err ) && empty( $test['calculationh']->err ) ) {
					$use_injection_mode = 1;
				}
				elseif ( ! empty( $test['calculationh']->err ) ) {
					// try to install
					$backup_path = JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/calculationh.cmcoupon.auto.vm' . $this->vmversion.'.php';
					$obj = $installer->installer->inject_admin_helper_calculationh( 'inject', 'pricetotal' );
					$result = $installer->inject_process( 'inject', $obj->file, $obj->vars, array( 'pricetotal' ), true, $backup_path );
					if ( empty( $result ) ) {
						$use_injection_mode = 1;
					}
				}
			}
			$this->session_set( 'virtuemart_use_injection_mode', $use_injection_mode );
		}

	}

	public function init( $prepare_data = true ) {

		$this->o_cart = VirtueMartCart::getCart();
		if ( $prepare_data && version_compare( $this->vmversion, '2.0.22', '>=' ) ) {
			$original_paymentid = $this->o_cart->virtuemart_paymentmethod_id; // save payment id as it can be lost with max_amount set with no coupon discount
			$original_coupon = $this->o_cart->couponCode;
			$this->o_cart->couponCode = ''; // remove coupon code so we do not end up in infinite loop
			if ( version_compare( $this->vmversion, '2.9.8', '>=' ) ) {
				$this->o_cart->prepareCartData();
			}
			$this->o_cart->couponCode = $original_coupon;
			$this->o_cart->virtuemart_paymentmethod_id = $original_paymentid;

			$this->vmcartPrices = $this->o_cart->getCartPrices();
			if ( isset( $this->o_cart->cartData ) ) {
				$this->vmcartData = $this->o_cart->cartData;
			}
		}

		$vmcart_class_vars = get_class_vars( get_class( $this->o_cart ) );
		if ( isset( $vmcart_class_vars['_triesValidateCoupon'] ) ) {
			// disable virtuemart coupon re-try limit of 8
			$this->o_cart->_triesValidateCoupon = array();
			$this->o_cart->setCartIntoSession();
		}

		$this->vmcartProducts = $this->o_cart->products;
		if ( version_compare( $this->vmversion, '3.0.19', '>=' ) ) {
			$this->vmcartProducts = array();
			foreach ( $this->o_cart->cartProductsData as $k => $item ) {
				$this->vmcartProducts[ $k ] = (object) $item;
			}
		}
	}

	public function cart_coupon_validate( $coupon_code ) {
		$this->init( false );
		$this->vmcartPrices = $this->o_cart->getCartPrices();
		if ( isset( $this->o_cart->cartData ) ) {
			$this->vmcartData = $this->o_cart->cartData;
		}

		if ( ! AC()->is_request( 'frontend' ) ) {
			$is_backend_vminvoice = AC()->helper->get_request( 'option' ) == 'com_vminvoice3' && in_array( AC()->helper->get_request( 'task' ), array( 'couponajax', 'orderajax' ) ) ? true : false;
			if ( ! $is_backend_vminvoice ) {
				return;
			}
		}

		$this->coupon_code = $coupon_code;
		if ( empty( $this->o_cart ) ) {
			return;
		}
		//------START STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------
		if ( $this->params->get( 'enable_store_coupon', 0 ) == 1 
		&& ! in_array( $coupon_code, array( $this->get_frontend_lang( 'auto' ), JText::_( 'COM_VIRTUEMART_COUPON_CODE_CHANGE' ) ) )
		&& strpos( $coupon_code, ';' ) === false ) {
			$tmp = AC()->db->get_value( 'SELECT id FROM #__cmcoupon WHERE estore="' . $this->estore . '" AND coupon_code="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );
			if ( empty( $tmp ) ) {
				if ( $this->is_coupon_in_store( $coupon_code ) ) {
					// run coupon through virtuemart system
					parent::initialize_coupon();
					$this->clearSessionCmcouponHistory();
					return null;
				}
				if ( ! $this->is_validateprocess ) {
					$_cm_displaying_coupon = '';
					$coupon_session = $this->session_get( 'coupon', '' );
					if ( ! empty( $coupon_session ) ) {
						 $_cm_displaying_coupon = $coupon_session->coupon_code;
					}
					if ( $coupon_code != $_cm_displaying_coupon ) {
						return JText::_( 'COM_VIRTUEMART_COUPON_CODE_INVALID' );
					}
				}
			}
		}
		//------END   STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------

		if ( $this->is_validateprocess ) {
			$this->coupon_code = $coupon_code;
			if ( empty( $this->o_cart ) ) {
				return;
			}
			$rtn = $this->process_coupon_helper();

			if(empty($this->error_msgs)) {
			// success
				return '';
			}
			else {
			// error
				return implode( '<br />', $this->error_msgs );
			}
		}
		else {
			// reset cache so coupon code can be processed
			//if(!class_exists('calculationHelper')) require(JPATH_VM_ADMINISTRATOR.'/helpers/calculationh.php');
			//$calculator = calculationHelper::getInstance();
			//if(method_exists($calculator, 'setCartPrices')) $calculator->setCartPrices(array()); //this line deletes the cache	
			
			// reset cache so coupon code can be processed
			
			if ( version_compare( $this->vmversion, '3.0.9', '>=' ) ) {
				$this->o_cart->_calculated = false;
				//$this->o_cart->cartPrices = array();
				$this->o_cart->setCartIntoSession( true, true );
			}
			else $prices = $this->o_cart->getCartPrices();

			return '';
		}
	}

	public function cart_coupon_validate_auto() {
		$this->init();

		if ( empty( $this->o_cart ) ) {
			return;
		}

		$codes = $this->process_autocoupon_helper();
		if ( empty( $codes ) ) {
			return;
		}

		foreach ( $codes as $coupon ) {
			$this->o_cart->setCouponCode( $coupon->coupon_code );
			break;
		}
	}

	public function cart_coupon_validate_balance() {
		$this->init();
		if ( empty( $this->o_cart ) ) {
			return;
		}

		$this->coupon_code = $this->coupon_code_balance;

		$this->process_coupon_helper();

		if ( version_compare( $this->vmversion, '2.9.8', '>=' ) ) {
			$this->o_cart->prepareCartData();
		}

		return;
	}

	public function cart_calculate_totals( &$data, &$prices ) {

		if ( (int) $this->params->get( 'virtuemart_inject_totals', 0 ) != 1 ) {
			return;
		}

		if ( (int) $this->session_get( 'virtuemart_use_injection_mode', 0 ) != 1 ) {
			return;
		}

		//if ( isset( $prices['cmcoupon_processed'] ) && $prices['cmcoupon_processed'] == 1 ) {
		//	return;
		//}

		$coupon_session = $this->session_get( 'coupon', '' );
		if ( empty( $coupon_session ) ) {
			return;
		}

		$this->init( false );
		$this->vmcartData =& $data;
		$this->vmcartPrices =& $prices;
		$this->process_coupon_helper();

		// get coupon session again, in case it has changed
		$coupon_session = $this->session_get( 'coupon', '' );
		if ( empty( $coupon_session ) ) {
			return;
		}

		static $___vmcart_totals = array();
		$history_data = array();

		if ( ! empty( $___vmcart_totals[ $coupon_session->uniquecartstring ] ) ) {
			$history_data = $___vmcart_totals[ $coupon_session->uniquecartstring ];
		}

		if ( empty( $history_data ) && isset( $prices['cmcoupon_processed'] ) && is_array( $prices['cmcoupon_processed'] ) && isset( $prices['cmcoupon_processed']['billTotal'] ) ) {
			$history_data = $prices['cmcoupon_processed'];
		}

		if ( ! empty( $history_data ) ) {
			if ( ! empty( $history_data['taxRulesBill'] ) ) {
				foreach ( $history_data['taxRulesBill'] as $k => $v ) {
					$this->vmcartPrices[ $k ] = $v;
				}
			}
			if ( ! empty( $history_data['shipmentTaxPerID'] ) ) {
				foreach ( $history_data['shipmentTaxPerID'] as $k => $v ) {
					$this->vmcartPrices['shipmentTaxPerID'][ $k ] = $v;
				}
			}
			if ( isset( $history_data['shipmentTax'] ) ) {
				$this->vmcartPrices['shipmentTax'] = $history_data['shipmentTax'];
			}
			if ( isset( $history_data['salesPriceShipment'] ) ) {
				$this->vmcartPrices['salesPriceShipment'] = $history_data['salesPriceShipment'];
			}
			if ( ! empty( $history_data['dataTaxRulesBill'] ) ) {
				foreach ( $history_data['dataTaxRulesBill'] as $k => $v ) {
					$this->vmcartData['taxRulesBill'][ $k ]['subTotal'] = $v;
				}
			}
			if ( ! empty( $history_data['VatTax'] ) ) {
				foreach ( $history_data['VatTax'] as $k => $r ) {
					if ( isset( $r['discountTaxAmount'] ) ) {
						$this->vmcartData['VatTax'][ $k ]['discountTaxAmount'] = $r['discountTaxAmount'];
					}
				}
			}

			$this->vmcartPrices['couponTax'] = $history_data['couponTax'];
			$this->vmcartPrices['couponValue'] = $history_data['couponValue'];
			$this->vmcartPrices['salesPriceCoupon'] = $history_data['salesPriceCoupon'];
			$this->vmcartPrices['billSub'] = $history_data['billSub'];
			$this->vmcartPrices['billTaxAmount'] = $history_data['billTaxAmount'];
			$this->vmcartPrices['billTotal'] = $history_data['billTotal'];

			$this->vmcartPrices['cmcoupon_processed'] = $history_data;
			return;
		}

		$negative_multiplier = version_compare( $this->vmversion, '2.0.21', '>=' ) ? -1 : 1;

		if ( ! empty( $this->vmcartData['taxRulesBill'] ) ) {

			$total_taxbill_tax = 0;
			foreach ( $this->vmcartData['taxRulesBill'] as $key => $item ) {
				if ( empty( $this->vmcartPrices[ $key . 'Diff' ] ) ) {
					$this->vmcartPrices[ $key . 'Diff' ] = 0;
				}
				$total_taxbill_tax += $this->vmcartPrices[ $key . 'Diff' ];
			}

			$product_coupon_tax = $product_coupon_value = $product_coupon_subtotal = 0;
			$coupon_tax = $coupon_value = $coupon_subtotal = 0;
			foreach ( $coupon_session->processed_coupons as $item ) {
				if ( ! empty( $item->product_discount_tax ) ) {
					$product_coupon_tax += $item->product_discount_tax;
					$product_coupon_value += $item->product_discount_notax;
					$product_coupon_subtotal += $item->product_discount_notax;
				}
				else {
					$product_coupon_value += $item->product_discount;
				}
			}

			$coupon_tax = round( $product_coupon_tax + $coupon_session->shipping_discount_tax, 2 ) * $negative_multiplier;
			$coupon_value = round( $product_coupon_value + $coupon_session->shipping_discount_notax, 2 ) * $negative_multiplier;
			$coupon_subtotal = round( $product_coupon_subtotal + $coupon_session->shipping_discount_notax, 2 ) * $negative_multiplier;

			// update taxes for product
			$product_coupon_tax = round( $product_coupon_tax, 2 ) * $negative_multiplier;
			foreach ( $this->vmcartData['taxRulesBill'] as $key => $item ) {
				$this->vmcartPrices[ $key . 'Diff' ] -= round( ( $this->vmcartPrices[ $key . 'Diff' ] / $total_taxbill_tax ) * $product_coupon_tax, 2 ) * $negative_multiplier;
				$___vmcart_totals[ $coupon_session->uniquecartstring ]['taxRulesBill'][ $key . 'Diff' ] = $this->vmcartPrices[ $key . 'Diff' ];
			}

			// update taxes for shipping
			if ( ! empty( $coupon_session->shipping_discount_tax ) ) {
				$shipment_tax = round( $coupon_session->shipping_discount_tax, 2 ) * $negative_multiplier;
				if ( isset( $this->vmcartPrices['shipmentTaxPerID'] ) ) {
					foreach ( $this->vmcartPrices['shipmentTaxPerID'] as $key => $amt ) {
						$this->vmcartPrices['shipmentTaxPerID'][ $key ] -= round( ( $amt / $this->vmcartPrices['shipmentTax'] ) * $shipment_tax, 2 ) * $negative_multiplier;
						$___vmcart_totals[ $coupon_session->uniquecartstring ]['shipmentTaxPerID'][ $key ] = $this->vmcartPrices['shipmentTaxPerID'][ $key ];
					}
				}
				$this->vmcartPrices['shipmentTax'] -= $shipment_tax * $negative_multiplier;
				$this->vmcartPrices['salesPriceShipment'] -= $shipment_tax * $negative_multiplier;
				$___vmcart_totals[ $coupon_session->uniquecartstring ]['shipmentTax'] = $this->vmcartPrices['shipmentTax'];
				$___vmcart_totals[ $coupon_session->uniquecartstring ]['salesPriceShipment'] = $this->vmcartPrices['salesPriceShipment'];
			}

			if ( ! empty( $coupon_subtotal ) ) {
				foreach ( $this->vmcartData['taxRulesBill'] as $key => $item ) {
					$this->vmcartData['taxRulesBill'][ $key ]['subTotal'] += $coupon_subtotal;
					$___vmcart_totals[ $coupon_session->uniquecartstring ]['dataTaxRulesBill'][ $key ] = $this->vmcartData['taxRulesBill'][ $key ]['subTotal'];
				}
			}

			$this->vmcartPrices['couponTax'] = 0;
			$this->vmcartPrices['couponValue'] = $coupon_value;
			$this->vmcartPrices['salesPriceCoupon'] = $coupon_value;
			$this->vmcartPrices['billSub'] -= $coupon_value * $negative_multiplier;
			$this->vmcartPrices['billTaxAmount'] -= $coupon_tax * $negative_multiplier;
			$this->vmcartPrices['billTotal'] -= ( $coupon_value + $coupon_tax ) * $negative_multiplier;

		}
		else {
			// update cart objects
			$coupon_taxbill = 0;
			$this->vmcartPrices['couponTax'] = round( $coupon_session->product_discount_tax + $coupon_session->shipping_discount_tax + $coupon_taxbill,2 ) * $negative_multiplier;
			$this->vmcartPrices['couponValue'] = round( $coupon_session->product_discount_notax + $coupon_session->shipping_discount_notax,2 ) * $negative_multiplier;
			$this->vmcartPrices['salesPriceCoupon'] = round( $coupon_session->product_discount + $coupon_session->shipping_discount + $coupon_taxbill,2 ) * $negative_multiplier;
			$this->vmcartPrices['billSub'] -= $this->vmcartPrices['couponValue'] * $negative_multiplier;
			$this->vmcartPrices['billTaxAmount'] -= $this->vmcartPrices['couponTax'] * $negative_multiplier;
			$this->vmcartPrices['billTotal'] -= $this->vmcartPrices['salesPriceCoupon'] * $negative_multiplier;
		}

		if ( ! empty( $this->vmcartData['VatTax'] ) ) {
			$is_update_vattax = true;
			foreach ( $coupon_session->cart_items as $item ) {
				if ( isset( $this->vmcartPrices[ $item->cartpricekey ]['VatTax'] ) && count( $this->vmcartPrices[ $item->cartpricekey ]['VatTax'] ) > 1 ) {
					$is_update_vattax = false;
					break;
				}
			}
			if ( $is_update_vattax ) {
				$discountTaxAmount = array();
				foreach ( $coupon_session->cart_items as $item ) {
					if ( isset( $this->vmcartPrices[ $item->cartpricekey ]['VatTax'] ) ) {
						$virtuemart_calc_id = key( $this->vmcartPrices[ $item->cartpricekey ]['VatTax'] );
						if ( ! isset( $discountTaxAmount[ $virtuemart_calc_id ] ) ) {
							$discountTaxAmount[ $virtuemart_calc_id ] = 0;
						}
						$discountTaxAmount[ $virtuemart_calc_id ] += $item->totaldiscount_tax;
					}
				}
				foreach( $discountTaxAmount as $virtuemart_calc_id => $value ) {
					$this->vmcartData['VatTax'][ $virtuemart_calc_id ]['discountTaxAmount'] = -1 * $value;
					$___vmcart_totals[ $coupon_session->uniquecartstring ]['VatTax'][ $virtuemart_calc_id ]['discountTaxAmount'] = $this->vmcartData['VatTax'][ $virtuemart_calc_id ]['discountTaxAmount'];
				}
			}
		}

		$___vmcart_totals[ $coupon_session->uniquecartstring ]['couponTax'] = $this->vmcartPrices['couponTax'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['couponValue'] = $this->vmcartPrices['couponValue'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['salesPriceCoupon'] = $this->vmcartPrices['salesPriceCoupon'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['billSub'] = $this->vmcartPrices['billSub'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['billTaxAmount'] = $this->vmcartPrices['billTaxAmount'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['billTotal'] = $this->vmcartPrices['billTotal'];

		$this->vmcartPrices['cmcoupon_processed'] = $___vmcart_totals[ $coupon_session->uniquecartstring ];
		return;
	}

	public function cart_coupon_delete( $coupon_id = 0 ) {
		$this->init();
		$this->delete_coupon_from_session( $coupon_id );

		$coupon_session = $this->session_get( 'coupon', '' );

		$coupon_code = '';
		if ( ! empty( $coupon_session ) ) {
			$coupon_code = current( $coupon_session->processed_coupons );
			$coupon_code = $coupon_code->coupon_code;
		}

		$this->o_cart->couponCode = $coupon_code;
		$this->o_cart->setCartIntoSession();
	}

	public function cart_coupon_displayname( $coupon_code ) {
	}

	public function cart_coupon_handler( $code, &$data, &$prices, $inparams=null ) {
		$this->init();
		$this->coupon_code = $code;
		$this->vmcartData =& $data;
		$this->vmcartPrices =& $prices;
		$this->inparams = $inparams;

		$this->isrefresh = false;
		if ( $this->is_validateprocess ) {		
			$coupon_session = $this->session_get( 'coupon', '' );
			if ( empty( $coupon_session ) ) {
				if ( AC()->param->get( 'enable_store_coupon', 0 ) == 1 && $this->is_coupon_in_store( $code ) ) {
					return null;
				}
				return false;
			}

			if( $coupon_session->uniquecartstring == $this->getuniquecartstring( $coupon_session->coupon_code_internal, false ) ) {
				$this->finalize_coupon_store( $coupon_session );
				return true;
			}
		}

		/*if ( version_compare( $this->vmversion, '2.9.8', '>=' ) ) {
			$cart = VirtueMartCart::getCart();
			if ( ! isset( $this->vmcartPrices['salesPriceShipment'] ) ) {
			// calculate shipping
				if ( empty( $cart ) ) {
					return null;
				}
				$orig_coupon = $cart->couponCode;
				$cart->couponCode = '';
				$cart->prepareCartData();
				$cart->couponCode = $orig_coupon;
				$this->o_cart = $cart;
				$this->vmcartPrices = $cart->cartPrices;
			}
		}*/

		$rtn = $this->process_coupon_helper();
		$is_rupostel_opc = JFactory::getApplication()->get( 'is_rupostel_opc', false );
		$is_vmonepagecheckout = class_exists( 'plgSystemVPOnePageCheckout' ) ? true : false;
		$is_onestepcheckout = class_exists( 'plgSystemOneStepCheckout' ) ? true : false;
		$is_vmuikitonepage = class_exists( 'plgSystemVmuikit_onepage' ) ? true : false;
		if ( ! $is_rupostel_opc && ! $is_vmonepagecheckout && ! $is_onestepcheckout && ! $is_vmuikitonepage ) {
			if ( version_compare( $this->vmversion, '2.9.8', '>=' ) ) {
				$this->o_cart->prepareCartData();
			}
		}

		return $rtn;
	}

	public function order_new( $order_id ) {
		$this->init();

		$coupon_session = $this->session_get( 'coupon', '' );
		if ( empty( $coupon_session ) ) {
			return null;
		}

		$_statuses = AC()->param->get('virtuemart_orderupdate_coupon_process', '');
		if ( ! empty( $_statuses ) ) {
			$session_id = JFactory::getSession()->getId();

			$user = JFactory::getUser ();

			$order_id = (int)$order_id;
			$user_email = $this->get_orderemail( $order_id );
			$coupon_session->coupon_code = $coupon_session->coupon_code_db;
			if ( ! empty( $order_id ) ) {
				AC()->db->query('UPDATE #__virtuemart_orders SET coupon_code="' . AC()->db->escape( $coupon_session->coupon_code_db ) . '" WHERE virtuemart_order_id=' . (int) $order_id );
			}
			else {
				$order_id = 'NULL';
			}
			$user_email = empty( $user_email ) ? 'NULL' : '"' . AC()->db->escape( $user_email ) . '"';
			
			AC()->db->query( 'DELETE FROM #__cmcoupon_history WHERE session_id="' . AC()->db->escape( $session_id ) . '"' );

			$coupon_details = AC()->db->escape( json_encode( $coupon_session ) );
			AC()->db->query( '
				INSERT INTO #__cmcoupon_history (estore,user_id,user_email,order_id,details,session_id)
				VALUES ("' . $this->estore . '",' . $user->id . ',' . $user_email . ',"' . $order_id . '","' . $coupon_details . '","' . $session_id . '")
			');
			return true;
		}


		$coupon_session->coupon_code = $coupon_session->coupon_code_db;
		
		// update virtuemart order coupon code
		AC()->db->query('UPDATE #__virtuemart_orders SET coupon_code="' . AC()->db->escape( $coupon_session->coupon_code_db ) . '" WHERE virtuemart_order_id=' . (int) $order_id );

		$this->save_coupon_history( (int) $order_id, $coupon_session );
	}

	public function order_status_changed( $order ) {
		$this->init();
		
		$_statuses = AC()->param->get('virtuemart_orderupdate_coupon_process', '');
		if ( ! empty( $_statuses ) ) {
			if ( ! is_array( $_statuses ) ) {
				$_statuses = array( $_statuses );
			}
			if ( in_array( $order->order_status, $_statuses ) ) {

				$order_id = (int)@$order->virtuemart_order_id;
				$session_id = JFactory::getSession()->getId();
				$coupon_session = AC()->db->get_value( 'SELECT details FROM #__cmcoupon_history WHERE order_id=' . (int) $order_id . ' AND total_product=0 AND total_shipping=0' );
				if ( ! empty( $coupon_session ) ) {
					$coupon_session = $this->array_to_object( json_decode( $coupon_session, true ) );
					if ( ! empty( $coupon_session ) ) {
						// remove temporary history row
						AC()->db->query( 'DELETE FROM #__cmcoupon_history WHERE session_id="' . $session_id . '" AND total_product=0 AND total_shipping=0' );

						// clean up
						$this->save_coupon_history( $order_id, $coupon_session );
					}
				}
			}
		}

		// check restoring coupon codes (delete history)
		$order_id = @$order->virtuemart_order_id;
		$order_status = @$order->order_status;
		$this->cleanup_ordercancel_helper( $order_id, $order_status );
		return true;
	}

	protected function initialize_coupon() {
		parent::initialize_coupon();
		
		// remove from vm session so coupon code is not called constantly
		$this->o_cart->couponCode = '';
		if ( isset( $this->o_cart->cartData ) ) {
			$this->o_cart->cartData['couponCode'] = '';
			$this->o_cart->cartData['couponDescr'] = '';
		}
		$this->o_cart->setCartIntoSession();

		$this->clearSessionCmcouponHistory();

		// clear current processing coupon
 		//$this->session_set( 'virtuemart_current_coupon', null );
	}

	protected function finalize_coupon( $master_output ) {
		$session_array = $this->save_discount_to_session( $master_output );
		if ( empty( $session_array ) ) {
			return false;
		}

		$is_auto = false;
		$display_arr = array();
		foreach ( $session_array->processed_coupons as $coupon ) {
			if ( $coupon->isauto && empty( $coupon->display_text ) ) {
				$is_auto = true;
				continue;
			}
			$link = !$coupon->isauto ? 'index.php?option=com_virtuemart&view=cart&task=deletecoupons&task2=deletecoupons&id=' . $coupon->coupon_entered_id : '';
			$display_arr[$coupon->coupon_entered_id] = array( 'text' => $coupon->display_text, 'link' => $link );
		}
		if( $is_auto ) {
			array_unshift( $display_arr, array( 'text' => $this->get_frontend_lang( 'auto' ), 'link' => '' ) );
		}
		$session_array->coupon_code_db = $session_array->coupon_code;

		$is_rupostel_opc = JFactory::getApplication()->get( 'is_rupostel_opc', false );
		if(!$is_rupostel_opc) {
		// load coupon view
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
			$coupondelete_view->setLayout( $this->params->get('virtuemart_coupondelete_layout', 'default') );
			$coupondelete_view->coupons = $display_arr;
			$coupondelete_view->coupons_original_text = $session_array->coupon_code_db;
			ob_start();
			$coupondelete_view->display();
			$session_array->coupon_code = ob_get_contents();
			ob_end_clean();
		}

		$this->session_set( 'coupon', $session_array );

		// update vm session so coupon code
		$this->o_cart->couponCode = $session_array->coupon_code;
		$this->o_cart->setCartIntoSession();

		$this->finalize_coupon_store( $session_array );

		return true;
	}

	protected function finalize_coupon_store( $coupon_session ) {

		if ( (int) $this->params->get( 'virtuemart_inject_totals', 0 ) == 1 && (int) $this->session_get( 'virtuemart_use_injection_mode', 0 ) == 1 ) {

			$this->vmcartData['couponCode'] = $coupon_session->coupon_code;
			$this->o_cart->cartData['couponCode'] = $coupon_session->coupon_code;
			$this->vmcartData['couponDescr'] = '';
			return;

		}
		
		
		static $___vmcart_totals = array();
		$history_data = array();

		if ( ! empty( $___vmcart_totals[ $coupon_session->uniquecartstring ] ) ) {
			$history_data = $___vmcart_totals[ $coupon_session->uniquecartstring ];
		}

		if ( empty( $history_data ) && isset( $prices['cmcoupon_processed'] ) && is_array( $prices['cmcoupon_processed'] ) && isset( $prices['cmcoupon_processed']['billTotal'] ) ) {
			$history_data = $prices['cmcoupon_processed'];
		}

		if ( ! empty( $history_data ) ) {
			if ( ! empty( $history_data['taxRulesBill'] ) ) {
				foreach ( $history_data['taxRulesBill'] as $k => $v ) {
					$this->vmcartPrices[ $k ] = $v;
				}
			}
			if ( ! empty( $history_data['vatTax-DbTax'] ) ) {
				foreach ( $history_data['vatTax-DbTax'] as $k => $v ) {
					$this->vmcartData['VatTax'][ $k ]['DBTax'] = $v;
				}
			}
			if ( ! empty( $history_data['VatTax'] ) ) {
				foreach ( $history_data['VatTax'] as $k => $r ) {
					if ( isset( $r['discountTaxAmount'] ) ) {
						$this->vmcartData['VatTax'][ $k ]['discountTaxAmount'] = $r['discountTaxAmount'];
					}
				}
			}

			$this->vmcartData['vmVat'] = $history_data['isVmVat'];
			$this->vmcartPrices['couponTax'] = $history_data['couponTax'];
			$this->vmcartPrices['couponValue'] = $history_data['couponValue'];
			$this->vmcartPrices['salesPriceCoupon'] = $history_data['salesPriceCoupon'];
			$this->vmcartPrices['billSub'] = $history_data['billSub'];
			$this->vmcartPrices['billTaxAmount'] = $history_data['billTaxAmount'];
			$this->vmcartPrices['billTotal'] = $history_data['billTotal'];

			$this->vmcartPrices['cmcoupon_processed'] = $history_data;
			return;
		}


		$coupon_taxbill = 0;
		$negative_multiplier = version_compare( $this->vmversion, '2.0.21', '>=' ) ? -1 : 1;

		// update cart objects
		$this->vmcartData['couponCode'] = $this->o_cart->cartData['couponCode'] = $coupon_session->coupon_code;
		$this->vmcartData['couponDescr'] = '';

		$this->vmcartPrices['couponTax'] = round( $coupon_session->product_discount_tax + $coupon_session->shipping_discount_tax + $coupon_taxbill, 2 ) * $negative_multiplier;
		$this->vmcartPrices['couponValue'] = round( $coupon_session->product_discount_notax + $coupon_session->shipping_discount_notax, 2 ) * $negative_multiplier;
		$this->vmcartPrices['salesPriceCoupon'] = round( $coupon_session->product_discount + $coupon_session->shipping_discount + $coupon_taxbill, 2 ) * $negative_multiplier;
		if ( isset( $this->vmcartPrices['billSub'] ) ) {
			$this->vmcartPrices['billSub'] -= $this->vmcartPrices['couponValue'] * $negative_multiplier;
		}
		if ( isset( $this->vmcartPrices['billTaxAmount'] ) ) {
			$this->vmcartPrices['billTaxAmount'] -= $this->vmcartPrices['couponTax'] * $negative_multiplier;
		}
		if ( isset( $this->vmcartPrices['billTotal'] ) ) {
			$this->vmcartPrices['billTotal'] -= $this->vmcartPrices['salesPriceCoupon'] * $negative_multiplier;
		}

		$this->vmcartData['vmVat'] = false;

		if (version_compare($this->vmversion, '2.0.20', '>=')) {
			{ # get vm tax calculation
				$vm_calculated_taxes_coupon = 0;
				
				if (!class_exists('CurrencyDisplay')) require JPATH_VM_ADMINISTRATOR.'/helpers/currencydisplay.php';
				$currencyDisplay = CurrencyDisplay::getInstance();
			
				if(!empty($this->vmcartData['VatTax'])) {
					foreach($this->vmcartData['VatTax'] as $vattax){
						if ( ! isset( $vattax['calc_kind'] ) ) {
							continue;
						}
						unset($couponamt);
						if (isset($vattax['subTotal'])) $vattax['percentage'] = $vattax['subTotal'] / $this->vmcartPrices['salesPrice'];
						$vattax['DBTax'] = isset($vattax['DBTax']) ? $vattax['DBTax'] : 0;
						if (!isset($vattax['discountTaxAmount']) && isset($vattax['calc_value'])) {
							$couponamt = round(($this->vmcartPrices['salesPriceCoupon'] * $vattax['percentage'] + abs($vattax['DBTax'])) / (100 + $vattax['calc_value']) * $vattax['calc_value'],$currencyDisplay->_priceConfig['taxAmount'][1]);
						}
						if (isset($couponamt)) $vm_calculated_taxes_coupon += $couponamt;
					}
				}
			}
			
			$coupon_offset = $this->vmcartPrices['couponTax'] - $vm_calculated_taxes_coupon ;
			if (version_compare($this->vmversion, '3.0.10', '>=')) $coupon_offset = 0;
			//if(!empty($coupon_offset)) {
			if(!empty($coupon_offset)
				&& empty($this->vmcartData['taxRulesBill']) // added because taxbill tax get doubled if not
			) {
				$this->vmcartData['vmVat'] = true;
				if(!isset($this->vmcartData['VatTax'])) $this->vmcartData['VatTax'] = array();
				$this->vmcartData['VatTax'][] = array(
					'virtuemart_calc_id'=>0,
					'calc_name'=>'',
					'calc_value_mathop'=>'',
					'calc_value'=>0,
					'calc_currency'=>'',
					'ordering'=>'',
					'discountTaxAmount'=>$coupon_offset,
					'cmcoupon_vatoffset'=>1,
				);
			}
			
			if(!empty($this->vmcartData['taxRulesBill'])) {
				reset($this->vmcartData['taxRulesBill']);
				$key = key($this->vmcartData['taxRulesBill']);
				if(empty($this->vmcartData['VatTax'][$key]['DBTax'])) $this->vmcartData['VatTax'][$key]['DBTax'] = 0;
				
				$includes_shipping = false;
				if(!empty($this->o_cart->virtuemart_shipmentmethod_id)) {
					if(!class_exists('VirtueMartModelShipmentmethod'))require(JPATH_ADMINISTRATOR . '/components/com_virtuemart/models/shipmentmethod.php');
					$shipment_model = VmModel::getModel('shipmentmethod');
					$shipment_model->setId($this->o_cart->virtuemart_shipmentmethod_id);
					$method = $shipment_model->getShipment();
					if(isset($method->tax_id) and (int)$method->tax_id === -1){
						// no tax
						$includes_shipping = false;
					} 
					else if (!empty($method->tax_id)) {
						// specific tax
						$includes_shipping = false;
					} 
					else {
						// default tax
						$includes_shipping = true;
					}
				}
				$this->vmcartData['VatTax'][$key]['DBTax'] += $coupon_session->product_discount-$coupon_session->product_discount_notax + $coupon_session->shipping_discount;
				$percentage = ($this->vmcartData['taxRulesBill'][$key]['subTotal']/$this->vmcartPrices['salesPrice']-1);
				$this->vmcartData['VatTax'][$key]['DBTax'] += ($coupon_session->product_discount+$coupon_session->shipping_discount)*$percentage;

				if($includes_shipping) {
					$this->vmcartData['VatTax'][$key]['DBTax'] -= $coupon_session->shipping_discount_notax;
				}
				$___vmcart_totals[ $coupon_session->uniquecartstring ]['vatTax-DbTax'][ $key ] = $this->vmcartData['VatTax'][$key]['DBTax'];

				$total_taxbill_tax = 0;
				foreach ( $this->vmcartData['taxRulesBill'] as $key => $item ) {
					if ( empty( $this->vmcartPrices[ $key . 'Diff' ] ) ) {
						$this->vmcartPrices[ $key . 'Diff' ] = 0;
						$___vmcart_totals[ $coupon_session->uniquecartstring ]['taxRulesBill'][ $key . 'Diff' ] = $this->vmcartPrices[ $key . 'Diff' ];
					}
					$total_taxbill_tax += $this->vmcartPrices[ $key . 'Diff' ];
				}

				$product_coupon_tax = 0;
				foreach ( $coupon_session->processed_coupons as $item ) {
					if ( ! empty( $item->product_discount_tax ) ) {
						$product_coupon_tax += $item->product_discount_tax;
					}
				}

				// update taxes for product
				$product_coupon_tax = round( $product_coupon_tax, 2 ) * $negative_multiplier;
				foreach ( $this->vmcartData['taxRulesBill'] as $key => $item ) {
					$this->vmcartPrices[ $key . 'Diff' ] -= round( ( $this->vmcartPrices[ $key . 'Diff' ] / $total_taxbill_tax ) * $product_coupon_tax, 2 ) * $negative_multiplier;
					$___vmcart_totals[ $coupon_session->uniquecartstring ]['taxRulesBill'][ $key . 'Diff' ] = $this->vmcartPrices[ $key . 'Diff' ];
				}
			}
		}

		$___vmcart_totals[ $coupon_session->uniquecartstring ]['isVmVat'] = $this->vmcartData['vmVat'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['couponTax'] = $this->vmcartPrices['couponTax'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['couponValue'] = $this->vmcartPrices['couponValue'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['salesPriceCoupon'] = $this->vmcartPrices['salesPriceCoupon'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['billSub'] = $this->vmcartPrices['billSub'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['billTaxAmount'] = $this->vmcartPrices['billTaxAmount'];
		$___vmcart_totals[ $coupon_session->uniquecartstring ]['billTotal'] = $this->vmcartPrices['billTotal'];

		$this->vmcartPrices['cmcoupon_processed'] = $___vmcart_totals[ $coupon_session->uniquecartstring ];
	}

	protected function getuniquecartstring( $coupon_code = null, $is_setting = true ) {
		if ( ! $is_setting ) {
			$option = AC()->helper->get_request( 'option' );
			$task = AC()->helper->get_request( 'task' );
			if ( $option == 'com_virtuemart' && $task == 'confirm' ) {
				static $is_run = false;
				if ( ! $is_run ) {
					$is_run = true;
					return; // confirming order, recheck code for validity
				}
			}
		}
		if ( empty( $coupon_code ) ) {
			$coupon_code = isset( $this->o_cart->couponCode ) ? $this->o_cart->couponCode : '';
		}
		if ( empty( $coupon_code ) ) {
			return;
		}

		if ( $this->is_validateprocess ) {	
			static $count_getuniquecartstring_calculateShipmentPrice = 0;
			$count_getuniquecartstring_calculateShipmentPrice++;
			if ( $count_getuniquecartstring_calculateShipmentPrice < 100 && ! empty( $this->o_cart->virtuemart_shipmentmethod_id ) && empty( $this->o_cart->cartPrices['salesPriceShipment'] ) ) {
			// calculate the shipping price
				$taxbill_orig = array();
				if ( isset( $this->vmcartData['taxRulesBill'] ) ) {
					foreach ( $this->vmcartData['taxRulesBill'] as $k => $data ) {
						$taxbill_orig[ $k ] = $data;
					}
				}
				if ( ! class_exists( 'calculationHelper' ) ) {
					require JPATH_VM_ADMINISTRATOR . '/helpers/calculationh.php';
				}
				$calculator = calculationHelper::getInstance();
				//$oldcart = clone($calculator->_cart);
				$calculator->_cart = $this->o_cart;
				if ( version_compare( $this->vmversion, '2.9.8', '>=' ) ) {
					$calculator->calculateShipmentPrice();
				}
				else {
					$calculator->calculateShipmentPrice( $this->o_cart, isset($this->o_cart->virtuemart_shipmentmethod_id) ? $this->o_cart->virtuemart_shipmentmethod_id : 0 );
				}
				if ( ! empty( $taxbill_orig ) ) {
					$this->o_cart->cartData['taxRulesBill'] = $taxbill_orig;
				}
				//$calculator->_cart = $oldcart;  # comment out, causes payment method not to change it it is required to
			}
		}

		$user = JFactory::getUser();
		$user_email = ! empty( $this->o_cart->BT['email'] ) ? $this->o_cart->BT['email'] : '';
		$string = $this->vmcartPrices['basePriceWithTax'] . 
			'|' . ( isset( $this->o_cart->cartPrices['salesPriceShipment'] ) ? $this->o_cart->cartPrices['salesPriceShipment'] : 0 ) . 
			'|' . $coupon_code . 
			'|' . $user->id . 
			'|' . $user_email
		;
		foreach ( $this->vmcartProducts as $k => $r ) {
			$string .= '|' . $k . '|' . $r->quantity;
		}
		$address = $this->get_customeraddress();
		return $string . '|ship|' . @$this->o_cart->virtuemart_shipmentmethod_id . '|' . $address->country_id . '|' . $address->state_id . '|' . @$this->o_cart->virtuemart_paymentmethod_id;
	}

	protected function getuniquecartstringauto() {
		$user = JFactory::getUser();
		$string = $this->vmcartPrices['basePriceWithTax'] . '|' . $this->vmcartPrices['salesPriceShipment'] . '|' . $user->id;
		foreach ( $this->vmcartProducts as $k => $r ) {
			$string .= '|' . $k . '|' . $r->quantity;
		}
		$address = $this->get_customeraddress();
		return $string . '|ship|' . @$this->o_cart->virtuemart_shipmentmethod_id . '|' . $address->country_id . '|' . $address->state_id . '|' . @$this->o_cart->virtuemart_paymentmethod_id;
	}

	protected function get_storeshoppergroupids( $user_id ) {
		return AC()->store->get_group_ids( $user_id );
	}

	protected function get_storeproduct( $ids ) {
		if ( $this->params->get( 'disable_coupon_product_children', 0 ) == 1 ) {
			return array();
		}
		$sql = 'SELECT m.virtuemart_product_id AS asset_id,p.virtuemart_product_id AS product_id
				  FROM #__virtuemart_products p 
				  JOIN #__virtuemart_products m ON m.virtuemart_product_id=p.product_parent_id
				 WHERE p.virtuemart_product_id IN ('.$ids.')';
		return AC()->db->get_objectlist( $sql );
	}

	protected function get_storecategory( $ids ) {

		$sql = 'SELECT virtuemart_category_id AS asset_id,virtuemart_product_id AS product_id
				  FROM #__virtuemart_product_categories
				 WHERE virtuemart_product_id IN (' . $ids . ')';
		$cats1 = AC()->db->get_objectlist( $sql );
		$cats2 = array();
		$cats3 = array();
		if ( $this->params->get( 'disable_coupon_product_children', 0 ) !== 1 ) {
			// get category list of parent products
			$sql = 'SELECT c.virtuemart_category_id AS asset_id,p.virtuemart_product_id AS product_id
					  FROM #__virtuemart_products p 
					  JOIN #__virtuemart_product_categories c ON c.virtuemart_product_id=p.product_parent_id
					 WHERE p.virtuemart_product_id IN (' . $ids . ')';
			$cats2 = AC()->db->get_objectlist( $sql );

			// get category list of parent products second level
			$sql = 'SELECT c.virtuemart_category_id AS asset_id,p.virtuemart_product_id AS product_id
					  FROM #__virtuemart_products p 
					  JOIN #__virtuemart_products p2 ON p2.virtuemart_product_id=p.product_parent_id
					  JOIN #__virtuemart_product_categories c ON c.virtuemart_product_id=p2.product_parent_id
					 WHERE p.virtuemart_product_id IN (' . $ids . ')';
			$cats3 = AC()->db->get_objectlist( $sql );
		}

		$categorys = array_merge( $cats1, $cats2, $cats3 );
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
		for ( $i = 0; $i < 10; $i ++ ) {
			if ( empty( $category_index ) ) {
				break;
			}
			$sql = 'SELECT category_parent_id, category_child_id FROM #__virtuemart_category_categories WHERE category_child_id IN (' . implode( ',', array_keys( $category_index ) ) . ')';
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
		$sql = 'SELECT virtuemart_manufacturer_id AS asset_id,virtuemart_product_id AS product_id
				  FROM #__virtuemart_product_manufacturers WHERE virtuemart_product_id IN (' . $ids . ')';
		$tmp1 = AC()->db->get_objectlist( $sql );
		if ( $this->params->get( 'disable_coupon_product_children', 0 ) == 1 ) {
			return $tmp1;
		}

		// get category list of parent products
		$sql = 'SELECT m.virtuemart_manufacturer_id AS asset_id,p.virtuemart_product_id AS product_id
				  FROM #__virtuemart_products p 
				  JOIN #__virtuemart_product_manufacturers m ON m.virtuemart_product_id=p.product_parent_id
				 WHERE p.virtuemart_product_id IN (' . $ids . ')';
		$tmp2 = AC()->db->get_objectlist( $sql );

		return array_merge($tmp1,$tmp2);
	}

	protected function get_storevendor( $ids ) {
		$sql = 'SELECT virtuemart_vendor_id AS asset_id,virtuemart_product_id AS product_id 
				  FROM #__virtuemart_products WHERE virtuemart_product_id IN (' . $ids . ')';
		return AC()->db->get_objectlist( $sql );
	}

	protected function get_storecustom( $ids ) {
		return array();
	}

	protected function get_storeshipping() {
		if (version_compare($this->vmversion, '2.0.16', '>=') && version_compare($this->vmversion, '2.6.0', '<')) {
		
			$cart_code = $this->o_cart->couponCode;
			//if(!empty($this->coupon_code)) $this->o_cart->couponCode = $this->coupon_code;
			
			$this->o_cart->couponCode = null; //ZPL
			$this->o_cart->getCartPrices(); // triggers taxbill tax
			$this->o_cart->couponCode = $cart_code; //ZPL
			
			//if(!empty($this->coupon_code)) $this->o_cart->couponCode = $cart_code;
		}
		
		//if(!empty($this->o_cart->virtuemart_shipmentmethod_id) && !isset($this->vmcartPrices['shipmentValue'])) {
		//	if(!class_exists('VirtueMartCart')) require JPATH_ROOT.'/components/com_virtuemart/helpers/cart.php';
		//	$cart = VirtueMartCart::getCart();
		//
		//	//$this->vmcartPrices = $cart->getCartPrices();
		//	if (version_compare($this->vmversion, '2.9.8', '>=')) {
		//		$cart->prepareCartData();
		//		$this->o_cart = $cart;
		//		$this->vmcartPrices = $cart->cartPrices;
		//	}
		//}
		
		$obj = (object) array(
			'shipping_id'=>isset($this->o_cart->virtuemart_shipmentmethod_id) ? $this->o_cart->virtuemart_shipmentmethod_id : 0,
			'total_notax'=>isset($this->vmcartPrices['shipmentValue']) ? (float)$this->vmcartPrices['shipmentValue'] : 0,
			'total'=>isset($this->vmcartPrices['salesPriceShipment']) ? (float)$this->vmcartPrices['salesPriceShipment'] : 0,
		);
		
		if(round($obj->total_notax,2)==round($obj->total,2) && !empty($this->vmcartPrices['shipment_calc_id'])) {
		// added for situations where the shipment tax is not calculated but we know there is a tax
		
			if(!class_exists('calculationHelper')) require(JPATH_VM_ADMINISTRATOR.'/helpers/calculationh.php');
			$calculator = calculationHelper::getInstance();
			if(method_exists($calculator, 'calculateShipmentPrice')) {
				if(version_compare($this->vmversion, '2.9.8', '>=')) $x = $calculator->calculateShipmentPrice();
				else  $x = $calculator->calculateShipmentPrice($this->o_cart, $obj->shipping_id);
				
				$this->vmcartPrices['shipmentValue'] = $x['shipmentValue'];
				$this->vmcartPrices['shipmentTax'] = $x['shipmentTax'];
				$this->vmcartPrices['salesPriceShipment'] = $x['salesPriceShipment'];
				if(isset($this->vmcartPrices['shipmentTaxPerID'])) $this->vmcartPrices['shipmentTaxPerID'] = $x['shipmentTaxPerID'];
				
				
				$obj = (object) array(
					'shipping_id'=>isset($this->o_cart->virtuemart_shipmentmethod_id) ? $this->o_cart->virtuemart_shipmentmethod_id : 0,
					'total_notax'=>isset($this->vmcartPrices['shipmentValue']) ? (float)$this->vmcartPrices['shipmentValue'] : 0,
					'total'=>isset($this->vmcartPrices['salesPriceShipment']) ? (float)$this->vmcartPrices['salesPriceShipment'] : 0,
				);
			}
		}

		$obj->total_notax = round( $obj->total_notax, 10 );
		$shippings = array();
		$shippings[] = (object) array(
			'shipping_id' => $obj->shipping_id,
			'total_notax' => $obj->total_notax,
			'total' => $obj->total,
			'tax_rate' => empty( $obj->total_notax ) ? 0 : ( $obj->total - $obj->total_notax ) / $obj->total_notax,
			'totaldiscount' => 0,
			'totaldiscount_notax' => 0,
			'totaldiscount_tax' => 0,
			'coupons' => array(),
		);
		$obj->shippings = $shippings;
		
		return $obj;
	}

	protected function get_storepayment() {
		$obj = (object) array(
			'payment_id' => isset( $this->o_cart->virtuemart_paymentmethod_id ) ? $this->o_cart->virtuemart_paymentmethod_id : 0,
			'total_notax'=>0,
			'total'=>0,
		);
		return $obj;
	}

	protected function get_customeraddress() {
		$address = (object) array(
			'email' => ! empty( $this->o_cart->BT['email'] ) ? $this->o_cart->BT['email'] : '',
			'state_id'=>0,
			'state_name'=>'',
			'country_id'=>0,
			'country_name'=>'',
		);

		if ( empty( $this->o_cart->BT ) ) {
			return $address;
		}

		$ids = $this->o_cart->BT;

		$address->state_id = isset( $ids['virtuemart_state_id'] ) ? (int) $ids['virtuemart_state_id'] : 0;
		$address->country_id = isset( $ids['virtuemart_country_id'] ) ? (int) $ids['virtuemart_country_id'] : 0;

		if ( ! empty( $address->country_id ) ) {
			$address->country_name = AC()->db->get_value( 'SELECT country_name FROM #__virtuemart_countries WHERE virtuemart_country_id=' . (int) $address->country_id );
		}

		if(!empty($address->state_id)) {
			$address->state_name = AC()->db->get_value('SELECT state_name FROM #__virtuemart_states WHERE virtuemart_state_id=' . (int) $address->state_id );
		}

		return $address;
	}

	protected function get_submittedcoupon() {
		if ( empty( $this->coupon_code ) ) {
			return '';
		}
		if ( version_compare( $this->vmversion, '2.9.8', '>=' ) && $this->coupon_code == JText::_( 'COM_VIRTUEMART_COUPON_CODE_CHANGE' ) ) {
			return '';
		}

		if ( $this->coupon_code == $this->get_frontend_lang( 'auto' ) ) {
			return '';
		}

		if ( $this->coupon_code != strip_tags( $this->coupon_code ) ) {
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
		$email = $this->o_cart->BT['email'];

		if ( JFactory::getApplication()->isAdmin() || empty( $email ) ) {
			$email = AC()->db->get_value( 'SELECT email FROM #__virtuemart_order_userinfos WHERE virtuemart_order_id='.$order_id.' AND address_type="BT"' );
		}
		return ! empty( $email ) ? $email : '';
	}

	protected function is_coupon_in_store( $coupon_code ) {
		if ( empty( $coupon_code ) ) {
			return false;
		}

		$coupon_id = (int) AC()->db->get_value('SELECT virtuemart_coupon_id FROM #__virtuemart_coupons WHERE coupon_code="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );

		return $coupon_id > 0 ? true : false;
	}

	protected function cart_object_is_initialized() {
		if ( version_compare( $this->vmversion, '2.9.8', '>=' )
		&& empty( $this->o_cart->products )
		&& !empty( $this->o_cart->cartProductsData )
		) return false; // the cart prices object has not yet been initialized

		return true;
	}

	protected function define_cart_items( $is_refresh = false ) {
		// retreive cart items
		$this->cart = new stdClass();
		$this->cart->items = array();
		$this->cart->items_def = array();
		$this->product_total = 0;
		$this->product_qty  = 0;

		if ( $is_refresh ) {
			$this->init(); // refresh the cart
		}

		$cart_products = $this->vmcartProducts;

		JPluginHelper::importPlugin( 'cmcoupon' );
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger( 'cmDefineCartItemsBefore', array( & $cart_products, $this ) );

		unset($cart_products['virtual']); // when on product page, virtuemart is adding the product not yet added to the cart as a virtual product
		foreach ($cart_products as $cartpricekey=>$product){
			$productId = (int)$product->virtuemart_product_id;
			if(!empty($product->param)) {
			// stockable check
				foreach($product->param as $productparam) {
					if(!empty($productparam['stockable']['child_id'])) {
						$tmp = (int)$productparam['stockable']['child_id'];
						if($tmp>0) $productId = $tmp;
					}
				}
			}
			if (empty($product->quantity) || empty( $productId )){
				continue;
			}
			$this->vmcartPrices[$cartpricekey]['discountAmount'] = round($this->vmcartPrices[$cartpricekey]['discountAmount'],2);
			$product_discount = empty($this->vmcartPrices[$cartpricekey]['discountAmount']) ? 0 : $this->vmcartPrices[$cartpricekey]['discountAmount'];
			
			$product_table = $this->get_product($productId);
			$product_special = $product_table->product_special;

			$billtaxrate = $this->get_billtaxrate_product( $cartpricekey );
			$product_price = $this->vmcartPrices[$cartpricekey]['salesPrice'] * $billtaxrate;
			if ( ! empty( $this->vmcartPrices[ $cartpricekey ]['discountedPriceWithoutTax'] ) ) {
				$tax_rate = ( $product_price - ( $this->vmcartPrices[ $cartpricekey ]['discountedPriceWithoutTax'] * $billtaxrate ) ) / ( $this->vmcartPrices[ $cartpricekey ]['discountedPriceWithoutTax'] * $billtaxrate );
			}
			else {
				if ( empty( $this->vmcartPrices[ $cartpricekey ]['priceWithoutTax'] ) ) {
					$tax_rate = 0;
				}
				else {
					$tax_rate = ( $product_price - ( $this->vmcartPrices[ $cartpricekey ]['priceWithoutTax'] * $billtaxrate ) ) / ( $this->vmcartPrices[ $cartpricekey ]['priceWithoutTax'] * $billtaxrate );
				}
			}
			$product_price_notax = $product_price/(1+$tax_rate);
			$this->cart->items_def[$productId]['product'] = $productId;
			$this->cart->items [] = array(
				'product_id' => $productId,
				'cartpricekey' => $cartpricekey,
				'discount' => $product_discount,
				'product_price' => $product_price,
				'product_price_notax' => $product_price_notax,
				'product_price_tax' => $product_price-$product_price_notax,
				'qty' => $product->quantity,
				'tax_rate' =>$tax_rate,
				'is_special'=>$product_special,
				'is_discounted'=>!empty($product_discount) ? 1 : 0,
			);
			$this->product_total += $product->quantity*$this->vmcartPrices[$cartpricekey]['salesPrice'];
			$this->product_qty += $product->quantity;
		}	

		JPluginHelper::importPlugin( 'cmcoupon' );
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger( 'cmDefineCartItemsAfter', array( & $this->cart->items_def, & $this->cart->items, $cart_products, $this ) );

		parent::define_cart_items();
	}

	private function get_billtaxrate_product( $cartpricekey ) {
		$billtaxrate = 1;
		if ( version_compare($this->vmversion, '2.0.14', '<') ) {
			return $billtaxrate;
		}

		if ( ! isset( $this->o_cart->products[ $cartpricekey ] ) ) {
			return $billtaxrate;
		}
		$product = $this->o_cart->products[ $cartpricekey ];

		$taxbillarray = $this->vmcartData['taxRulesBill'];
		if ( empty( $taxbillarray ) ) {
			$taxbillarray = $this->o_cart->cartData['taxRulesBill'];
		}
		$billtaxrate_1 = 1;
		if ( ! empty( $taxbillarray ) ) {
			$billtaxrate_1 = 0;
			foreach ( $taxbillarray as $rate ) {

				$apply_rule = false;
				if ( ! empty( $rate['calc_categories']) || ! empty( $rate['virtuemart_manufacturers'] ) ) {
					$check_category = ! empty( $rate['calc_categories'] ) ? array_intersect( $rate['calc_categories'], $product->categories ) : array();
					$check_manufacturer = ! empty( $rate['virtuemart_manufacturers'] ) ? array_intersect( $rate['virtuemart_manufacturers'], $product->virtuemart_manufacturer_id ) : array();
					if ( ! empty( $rate['calc_categories'] ) && ! empty( $rate['virtuemart_manufacturers'] ) ) {
						if ( count( $check_category ) > 0 && count( $check_manufacturer ) > 0 ) {
							$apply_rule = true;
						}
					}
					else {
						if ( count( $check_category ) > 0 || count( $check_manufacturer ) > 0 ) {
							$apply_rule = true;
						}
					}
				}
				else {
					$apply_rule = true;
				}
				if ( ! $apply_rule ) {
					continue;
				}

				if ( $rate['calc_value_mathop'] == '+%' ) {
					$billtaxrate_1 += $rate['calc_value'];
				}
				elseif ( $rate['calc_value_mathop'] == '-%' ) {
					$billtaxrate_1 -= $rate['calc_value'];
				}
			}
			$billtaxrate_1 = ( 1 + $billtaxrate_1 / 100 );
		}

		$taxbillarray = $this->vmcartData['DBTaxRulesBill'];
		if ( empty( $taxbillarray ) ) {
			$taxbillarray = $this->o_cart->cartData['DBTaxRulesBill'];
		}
		$billtaxrate_2 = 1;
		if ( ! empty( $taxbillarray ) ) {
			$billtaxrate_2 = 0;
			foreach ( $taxbillarray as $rate ) {

				$apply_rule = false;
				if ( ! empty( $rate['calc_categories']) || ! empty( $rate['virtuemart_manufacturers'] ) ) {
					$check_category = ! empty( $rate['calc_categories'] ) ? array_intersect( $rate['calc_categories'], $product->categories ) : array();
					$check_manufacturer = ! empty( $rate['virtuemart_manufacturers'] ) ? array_intersect( $rate['virtuemart_manufacturers'], $product->virtuemart_manufacturer_id ) : array();
					if ( ! empty( $rate['calc_categories'] ) && ! empty( $rate['virtuemart_manufacturers'] ) ) {
						if ( count( $check_category ) > 0 && count( $check_manufacturer ) > 0 ) {
							$apply_rule = true;
						}
					}
					else {
						if ( count( $check_category ) > 0 || count( $check_manufacturer ) > 0 ) {
							$apply_rule = true;
						}
					}
				}
				else {
					$apply_rule = true;
				}
				if ( ! $apply_rule ) {
					continue;
				}

				if ( $rate['calc_value_mathop'] == '+%' ) {
					$billtaxrate_2 += $rate['calc_value'];
				}
				elseif ( $rate['calc_value_mathop'] == '-%' ) {
					$billtaxrate_2 -= $rate['calc_value'];
				}
			}
			$billtaxrate_2 = ( 1 + $billtaxrate_2 / 100 );
		}
		$billtaxrate = $billtaxrate_1 * $billtaxrate_2;

		return $billtaxrate;
	}

	protected function return_false( $key, $type = 'key', $force = 'donotforce' ) {
		if($this->is_validateprocess) {
			return parent::return_false( $key, $type, $force );
		}
		else {
			// strip out Virtuemart successful message
			$sessionqueue = JFactory::getSession()->get('application.queue');
					
			$orig_messages = $messages = JFactory::getApplication()->getMessageQueue();
			if(!empty($messages)) {
				foreach($messages as $k=>$message) {
					if($message['message']==JText::_('COM_VIRTUEMART_CART_COUPON_VALID')) {
						unset($messages[$k]);
					}
				}
				if($orig_messages != $messages) {
					if(!empty($sessionqueue)) JFactory::getSession()->set('application.queue', empty($messages) ? null : $messages);
					JFactory::getApplication()->set('_messageQueue',empty($messages) ? array() : $messages);
					if (class_exists('ReflectionClass')) {
						$app = JFactory::getApplication(); 
						$appReflection = new ReflectionClass($app);
						$_messageQueue = $appReflection->getProperty('_messageQueue');
						$_messageQueue->setAccessible(true);
						$_messageQueue->setValue($app, empty($messages) ? array() : $messages);
					}
				}
			}
			
			return parent::return_false( $key, $type, $force );
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
		$where_product = empty( $exclude_products ) ? '' : ' AND p.virtuemart_product_id NOT IN (' . implode( ',', $exclude_products ) . ') ';

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
						$sql = 'SELECT virtuemart_product_id
								  FROM #__virtuemart_products p
								 WHERE published=1
								   AND virtuemart_product_id IN (' . $ids . ')
								   AND virtuemart_product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'category' == $asset_type ) {
						$sql = 'SELECT c.virtuemart_product_id
								  FROM #__virtuemart_product_categories c
								  JOIN #__virtuemart_products p ON p.virtuemart_product_id=c.virtuemart_product_id
								 WHERE p.published=1
								   AND c.virtuemart_category_id IN (' . $ids . ')
								   AND c.virtuemart_product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								   LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'manufacturer' == $asset_type ) {
						$sql = 'SELECT m.virtuemart_product_id
								  FROM #__virtuemart_product_manufacturers m
								  JOIN #__virtuemart_products p ON p.virtuemart_product_id=m.virtuemart_product_id
								 WHERE p.published=1
								   AND m.virtuemart_manufacturer_id IN (' . $ids . ')
								   AND m.virtuemart_product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'vendor' == $asset_type ) {
						$sql = 'SELECT virtuemart_product_id
								  FROM #__virtuemart_products p
								 WHERE published=1
								   AND virtuemart_vendor_id IN (' . $ids . ')
								   AND virtuemart_product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					}
				}
				elseif ( 'exclude' == $mode ) {
					if ( 'product' == $asset_type ) {
						$sql = 'SELECT virtuemart_product_id
								  FROM #__virtuemart_products p
								 WHERE published=1
								   AND virtuemart_product_id NOT IN (' . $ids . ')
								   AND virtuemart_product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'category' == $asset_type ) {
						$sql = 'SELECT c.virtuemart_product_id
								  FROM #__virtuemart_product_categories c
								  JOIN #__virtuemart_products p ON p.virtuemart_product_id=c.virtuemart_product_id
								 WHERE p.published=1
								   AND c.virtuemart_category_id NOT IN (' . $ids . ')
								   AND c.virtuemart_product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								   LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'manufacturer' == $asset_type ) {
						$sql = 'SELECT m.virtuemart_product_id
								  FROM #__virtuemart_product_manufacturers m
								  JOIN #__virtuemart_products p ON p.virtuemart_product_id=m.virtuemart_product_id
								 WHERE p.published=1
								   AND m.virtuemart_manufacturer_id NOT IN (' . $ids . ')
								   AND m.virtuemart_product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'vendor' == $asset_type ) {
						$sql = 'SELECT virtuemart_product_id
								  FROM #__virtuemart_products p
								 WHERE published=1
								   AND virtuemart_vendor_id NOT IN (' . $ids . ')
								   AND virtuemart_product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					}
				}

				if ( empty( $the_product ) ) {
					break;
				}

				if ( ! empty( $the_product ) && ! empty( $coupon_row->params->exclude_special ) ) {
					$product_table = $this->get_product( $the_product );
					if ( ! empty( $product_table->product_special ) ) {
						$used_ids[] = $the_product;
						$the_product = 0;
					}
				}
				if ( ! empty( $the_product ) && ! empty( $coupon_row->params->exclude_discounted ) ) {
					if ( ! class_exists( 'VirtueMartModelProduct' ) ) {
						require JPATH_VM_ADMINISTRATOR . '/models/product.php';
					}
					$model = VmModel::getModel( 'product' );
					$product = $model->getProduct( $the_product, true, true );
					if ( ! empty( $product->allPrices['discountAmount'] ) ) {
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

		$cart = VirtueMartCart::getCart();
		if ( ! $cart ) {
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

		if($is_update) {
			$_REQUEST['quantity'] = version_compare( $this->vmversion, '2.9.8', '>=' ) ? array( $cartpricekey => $qty ) : $qty;
			$status = $cart->updateProductCart( $cartpricekey );
		}
		else {
			$_REQUEST['quantity'][0] = $qty;
			$status = $cart->add( array( $product_id ) );
		}
		
		if ( ! $status ) {
			return;
		}

		if ( version_compare( $this->vmversion, '2.9.8', '>=' ) ) {
			$cart->couponCode = $this->o_cart->couponCode;
			$cart->_productAdded = true; // forces ->products to be recalculated in prepareCartData
			$cart->prepareCartData();
			//$cart->getCartPrices(true);
			$cart->setCartIntoSession();
			$this->o_cart = $cart;
			$this->vmcartPrices = $cart->cartPrices;
			$this->isrefresh = true;
		}
		else {
			$cart->couponCode = '';
			if ( ! class_exists( 'calculationHelper' ) ) {
				require JPATH_VM_ADMINISTRATOR . '/helpers/calculationh.php';
			}
			$calculator = calculationHelper::getInstance();
			if ( method_exists( $calculator, 'setCartPrices' ) ) {
				$calculator->setCartPrices( array() ); //this line deletes the cache	
			}
			$this->vmcartPrices = $calculator->getCheckoutPrices( $cart );
		}

		return $status;
	}

	private function clearSessionCmcouponHistory() {

		// clear current session if any in db
		$session_id = JFactory::getSession()->getId();
		AC()->db->query( 'DELETE FROM #__cmcoupon_history WHERE session_id="' . $session_id . '" AND (order_id=0 OR order_id IS NULL)' );

		// clean out old history that is more than 15 minuites old
		AC()->db->query( 'DELETE FROM #__cmcoupon_history WHERE session_id IS NOT NULL AND session_id!="" AND coupon_entered_id IS NULL AND TIMESTAMPDIFF(MINUTE,timestamp,now())>15' );
	}

	private function get_product( $product_id ) {
		if ( ! class_exists( 'VirtueMartModelProduct' ) ) {
			require JPATH_VM_ADMINISTRATOR . '/models/product.php';
		}
		$model = VmModel::getModel( 'product' );
		//$product = $model->getProduct($virtuemart_product_id, true, false); // problem with oldver versions (2.0.18), causes price changes in cart

		$product = $model->getTable( 'products' );
		$product->load( $product_id );

		return $product;
	}
}

