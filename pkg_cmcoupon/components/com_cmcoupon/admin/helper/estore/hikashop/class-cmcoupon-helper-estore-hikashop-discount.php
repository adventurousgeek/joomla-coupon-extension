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

class CmCoupon_Helper_Estore_Hikashop_Discount extends CmCoupon_Library_Discount {

	var $params = null;

	var $cart = null;
	var $o_cart = null;
	var $coupon_code = null;
	var $posted_order = null;

	var $product_total = 0;
	var $product_qty = 0;
	var $default_err_msg = '';
	var $isrefresh = false;
	var $loaded_coupon = null;
	var $submitted_coupon = null;

	public static function instance( $class = null ) {
		return parent::instance( get_class() );
	}

	public function __construct() {
		parent::__construct();

		$this->estore = 'hikashop';
		$this->default_err_msg = JText::_('COUPON_NOT_VALID');
		$this->enqueue_error_msgs = true;
		$this->loaded_coupon = new stdclass();

		if ( file_exists( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop_j3.xml' ) ) {
			$parser = simplexml_load_file( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop_j3.xml' );
		}
		elseif ( file_exists( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop.xml' ) ) {
			$parser = simplexml_load_file( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop.xml' );
		}
		if ( ! empty( $parser ) ) {
			$this->hikashop_version = (string) $parser->version;
		}

		if ( ! defined( 'DS' ) ) {
			define( 'DS', DIRECTORY_SEPARATOR );
		}
		if ( ! class_exists( 'hikashop' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_hikashop/helpers/helper.php';
		}
		if ( ! class_exists( 'hikashopCartClass' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_hikashop/classes/cart.php';
		}
		if ( ! class_exists( 'CmCouponHikashopCouponHandlerCart' ) ) {
			require dirname( __FILE__ ) . '/couponhandlercart.php';
		}

		$this->store_config = & hikashop_config();

		$this->default_currency = $this->store_config->get( 'main_currency', 1 );
		if ( $this->params->get( 'disable_currency_exchange', 0 ) == 1 ) {
			$this->default_currency = hikashop_getCurrency();	
		}

		//customer
		$hika_user_id = hikashop_loadUser();
		if ( $hika_user_id ) {
			$userClass = hikashop_get( 'class.user' );
			$this->customer = $userClass->get( $hika_user_id );
		}

	}

	public function init() {
		$current_cart_id = ! empty( $this->o_cart->cart_id ) ? $this->o_cart->cart_id : 0;
		if ( empty( $current_cart_id ) ) {
			if ( version_compare( $this->hikashop_version, '3.0.0', '>=' ) ) {
				$cartClass = new hikashopCartClass();
				$current_cart_id = $cartClass->getCurrentCartId();
			}
			else {
				$current_cart_id =  JFactory::getApplication()->getUserState( HIKASHOP_COMPONENT . '.cart_id', 0, 'int' );
			}
		}
		$this->cart_id = $current_cart_id;

		$coupon_session = $this->session_get( 'coupon', '' );
		if ( ! empty( $coupon_session ) ) {
			if ( $coupon_session->cart_id != $this->cart_id ) {
				$this->initialize_coupon();
			}
		}

		if ( version_compare( $this->hikashop_version, '3.0.0', '>=' ) ) {
			if ( ! empty( $this->o_cart ) ) {
				$this->cart_shipping = $this->o_cart->shipping;
			}
			else {
				$shipping_ids = AC()->db->get_value( 'SELECT cart_shipping_ids FROM #__hikashop_cart WHERE cart_id=' . (int) $this->cart_id );
				$shipping_ids = empty( $shipping_ids ) ? array() : explode( ',', $shipping_ids );
				foreach( $shipping_ids as $s ) {
					if ( strpos( $s, '@' ) === false ) {
						$this->the_shipping_id = $s;
						break;
					}
					list($s_id, $w_id) = explode('@', $s, 2);
					$this->the_shipping_id = $s_id;
					break;
				}
			}
		}
		else {
			$this->cart_shipping = JFactory::getApplication()->getUserState( HIKASHOP_COMPONENT . '.shipping_data' );
			if ( ! empty( $this->cart_shipping ) && ! isset( $this->cart_shipping->shipping_price_with_tax ) ) {
				$currencyClass = hikashop_get( 'class.currency' );
				if ( version_compare( $this->hikashop_version, '2.2.0', '>=' ) ) {
					$currencyClass->processShippings( $this->cart_shipping, $this->o_cart );
				}
				else {
					$shippings = array( & $this->cart_shipping );
					$currencyClass->processShippings( $shippings );
				}
			}
		}

		$payment_id = JFactory::getApplication()->getUserState( HIKASHOP_COMPONENT . '.payment_id' );
		if ( ! empty( $payment_id ) ) {
			$this->cart_payment = JFactory::getApplication()->getUserState( HIKASHOP_COMPONENT . '.payment_data' );
			if ( ! empty( $this->o_cart->payment ) ) {
				$currencyClass = hikashop_get( 'class.currency' );
				$payment = & $this->cart_payment;
				$payments = array( & $payment );
				$currencyClass->processPayments( $payments, $this->o_cart );
			}
		}
	}

	public function cart_coupon_validate( $coupon_code, & $continue_execution ) {
		if ( ! AC()->is_request( 'frontend' ) ) {
			return;
		}
		$this->init();

		if ( is_array( $coupon_code ) ) {
			$coupon_code = array_values( array_slice( $coupon_code, -1 ) )[0]; // get last element in array
		}
		$coupon_code = trim($coupon_code);

		$hikashop_coupons = $this->session_get( 'hikashop_coupons', array() );
		if ( isset( $hikashop_coupons[ $coupon_code ] ) ) {
			$continue_execution = true;
			return;
		}

		//------START STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------
		if ( $this->params->get( 'enable_store_coupon', 0 ) == 1 ) {
			$tmp = AC()->db->get_value( 'SELECT id FROM #__cmcoupon WHERE estore="hikashop" AND coupon_code="'.AC()->db->escape( trim( $coupon_code ) ) . '"' );
			if ( empty( $tmp ) && $this->is_coupon_in_store( $coupon_code ) ) {
				$continue_execution = true;
				$hikashop_coupons[ $coupon_code ] = 1;
				$this->session_set( 'hikashop_coupons', $hikashop_coupons );
				return;
			}
		}
		//------END   STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------

		$continue_execution = false;
		return (object) array(
			'coupon_code' => $coupon_code,
			'discount_auto_load' => false,
		);
	}

	public function cart_coupon_validate_auto( & $cart ) {
		if ( ! empty( $cart ) && $cart->cart_type !== 'cart' ) {
			return;
		}
		$this->init();

		$coupon = 'discount_auto_load';
		$total = & $cart->full_total;
		$zones = null;
		$products = & $cart->products;
		$display_error = false;
		$error_message = '';
		$continue_execution = true;
		$rtn = $this->cart_coupon_process( $coupon, $total, $zones, $products, $display_error, $error_message, $continue_execution );			
		if ( empty( $rtn ) ) {
			return;
		}
		$cart->cmcoupon_discount = array( 1 );
		$cmsess = $this->get_coupon_session();
		if ( ! empty( $cmsess->product_discount ) ) {
		// needed to account for product discount in shipping min/max calculations
			if ( ! isset( $cart->coupon ) ) {
				$cart->coupon = new stdclass();
			}
			$cart->coupon->discount_flat_amount = $cmsess->product_discount;
		}
	}

	public function cart_coupon_validate_balance() {
		$this->init();
		$cartClass = hikashop_get( 'class.cart' );
		$cart = $cartClass->loadFullCart( true );
		
		$this->set_submittedcoupon( $this->coupon_code_balance );
		$this->cart_products = $cart->products;
		$this->order_total = & $cart->full_total;
		$this->is_display_error = false;
		$this->continue_execution = true;

	  	$this->process_coupon_helper();

		// check if coupons exist
		$coupon_session = $this->session_get( 'coupon', '' );
		if ( empty( $coupon_session ) ) {
			return;
		}
		if ( empty( $coupon_session->coupon_code_internal ) ) {
			return;
		}

		$new_coupon = $coupon_session->coupon_code_internal;

		$cart2 = $cartClass->initCart();
		$cart2->cart_coupon = $new_coupon;
		if ( $cartClass->save( $cart2 ) ) {
			JFactory::getApplication()->setUserState( HIKASHOP_COMPONENT . '.coupon_code', $new_coupon );
		}
	}

	public function cart_calculate_totals( & $cart ) {
		if ( ! empty( $cart ) && $cart->cart_type !== 'cart' ) {
			return;
		}
		$this->o_cart = $cart;
		$this->init();
		$this->process_coupon_helper();

		//if ( empty( $cart->coupon->cmcoupon ) && empty( $cart->cmcoupon_discount ) ) {
		//	$this->initialize_coupon();
		//	return null;
		//}

		$coupon_session = $this->session_get( 'coupon', '' );
		if ( empty( $coupon_session ) ) {
			return;
		}

		{ // remove referencing of cart->total to cart->full_total before connecting to order_total
			$cart_total = (object) ( (array) $cart->total );
			unset( $cart->total );
			$cart->total = $cart_total;
		}
		if ( ! HIKASHOP_PHP5 ){
			$this->order_total = $cart->full_total;
		}
		else {
			$this->order_total = new stdClass();
			if ( ! empty( $cart->full_total->prices ) ) {
				$this->order_total->prices = array( clone( reset( $cart->full_total->prices ) ) );
			}
			elseif ( ! empty( $cart->total->prices ) ) {
				$this->order_total->prices = array( clone( reset( $cart->total->prices ) ) );
			}
			else {
				$this->order_total->prices = array( 0 => null );
			}
		}

		$currencyClass = hikashop_get( 'class.currency' );
		$round = $currencyClass->getRounding( $this->order_total->prices[0]->price_currency_id );

		/*if ( $this->default_currency != $this->order_total->prices[0]->price_currency_id ) {
			$coupon_session->product_discount = $currencyClass->convertUniquePrice( $coupon_session->product_discount, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
			$coupon_session->product_discount_notax = $currencyClass->convertUniquePrice( $coupon_session->product_discount_notax, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
			$coupon_session->product_discount_tax = $currencyClass->convertUniquePrice( $coupon_session->product_discount_tax, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
			$coupon_session->shipping_discount = $currencyClass->convertUniquePrice( $coupon_session->shipping_discount, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
			$coupon_session->shipping_discount_notax = $currencyClass->convertUniquePrice( $coupon_session->shipping_discount_notax, $this->default_currency,$this->order_total->prices[0]->price_currency_id );
			$coupon_session->shipping_discount_tax = $currencyClass->convertUniquePrice( $coupon_session->shipping_discount_tax, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
		}*/
		$discount_value_without_tax = 0;
		foreach ( $coupon_session->processed_coupons as $c ) { 
			/*if ( $this->default_currency != $this->order_total->prices[0]->price_currency_id ) {
				$c->product_discount = $currencyClass->convertUniquePrice( $c->product_discount, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
				$c->product_discount_notax = $currencyClass->convertUniquePrice( $c->product_discount_notax, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
				$c->product_discount_tax = $currencyClass->convertUniquePrice( $c->product_discount_tax, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
				$c->shipping_discount = $currencyClass->convertUniquePrice( $c->shipping_discount, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
				$c->shipping_discount_notax = $currencyClass->convertUniquePrice($c->shipping_discount_notax, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
				$c->shipping_discount_tax = $currencyClass->convertUniquePrice( $c->shipping_discount_tax, $this->default_currency, $this->order_total->prices[0]->price_currency_id );
			}*/
			if ( $c->is_discount_before_tax ) {
				$discount_value_without_tax += $c->product_discount_notax + $c->shipping_discount_notax; 
			}
			else {
				$discount_value_without_tax += $c->product_discount + $c->shipping_discount; 
			}
		}
		
		$is_discount_before_tax = round( $discount_value_without_tax, $round ) != round( $coupon_session->product_discount + $coupon_session->shipping_discount, $round ) ? true : false;
		$price_with_tax = $this->store_config->get( 'price_with_tax' );
		$object = (object) array(
			'cmcoupon' => 1,
			'discount_code' => $coupon_session->coupon_code,
			'discount_currency_id' => $this->order_total->prices[0]->price_currency_id,
			'total' => clone( $this->order_total ),
			'discount_value_without_tax' => round(
				empty( $price_with_tax) && ! $is_discount_before_tax ? $coupon_session->product_discount + $coupon_session->shipping_discount : $discount_value_without_tax,
				$round
			),
			'discount_value' => round( $coupon_session->product_discount + $coupon_session->shipping_discount, $round ),
			'taxes'=>array(),
		);

		if ( version_compare( $this->hikashop_version, '3.0.0', '>=' ) ) {
			$object->total->prices[0]->price_value = round( $object->total->prices[0]->price_value, $round );
			$object->total->prices[0]->price_value_with_tax = round( $object->total->prices[0]->price_value_with_tax, $round );
		}
		$object->total->prices[0]->price_value_without_discount_with_tax = $object->total->prices[0]->price_value_with_tax;
		$object->total->prices[0]->price_value_without_discount = $object->total->prices[0]->price_value;
		$object->total->prices[0]->price_value_with_tax -= $object->discount_value;
		
		if ( version_compare( $this->hikashop_version, '2.2.0', '>=' ) ) {
			if ( $is_discount_before_tax ) {
			// one or more coupons have a discount before tax
				$object->total->prices[0]->price_value -= $object->discount_value_without_tax;
			}
			else {
				if ( ! empty( $price_with_tax ) ) {
					$object->total->prices[0]->price_value -= $object->discount_value;
				}
				else {
					$object->total->prices[0]->price_value -= $object->discount_value_without_tax;
				}
			}
		}
		else {
			if ( ! empty( $price_with_tax ) || $is_discount_before_tax ) {
				$object->total->prices[0]->price_value -= $object->discount_value_without_tax;
			}
		}

		if ( ! empty( $object->total->prices[0]->taxes ) ) {
			$object->total->prices[0]->taxes_without_discount = array();
			$coupon_tax_amount = $coupon_session->product_discount_tax + $coupon_session->shipping_discount_tax;
			$total_tax_before_discount = 0;
			foreach ( $object->total->prices[0]->taxes as $namekey => $tax ) {
				$object->total->prices[0]->taxes_without_discount[ $namekey ] = clone( $tax ); 
				$total_tax_before_discount += $tax->tax_amount;
			}

			if ( ! empty ( $coupon_tax_amount ) ) {
				foreach ( $object->total->prices[0]->taxes as $namekey => $tax ) { 
					$current_tax = $object->total->prices[0]->taxes[$namekey]->tax_amount;
					$current_tax_discount = empty( $total_tax_before_discount ) ? 0 : $coupon_tax_amount / $total_tax_before_discount * $current_tax;
					$object->total->prices[0]->taxes[ $namekey ]->tax_amount = round( $current_tax - $current_tax_discount, $round );
					$object->taxes[] = (object) array( 'tax_namekey' => $namekey, 'tax_amount' => $current_tax_discount );
				}
			}
		}

		$object->discount_flat_amount = round( $coupon_session->product_discount, $round );
		$object->discount_flat_amount_without_tax = round( $coupon_session->product_discount_notax, $round );
		$object->discount_percent_amount = 0;

		if ( class_exists( 'hikamarket' ) ) {
			$object->discount_target_vendor = 1;
			$hikamarket_config = hikamarket::config();
			$totaldiscount_str = $hikamarket_config->get( 'calculate_vendor_price_with_tax', false ) ? 'totaldiscount' : 'totaldiscount_notax';
			$object->products = array();
			foreach ( $cart->products as $product ) {           
				foreach ( $coupon_session->cart_items as $item ) {
					if ( $product->product_id == $item->product_id ) {
						if ( isset( $product->variants ) ) {
							foreach ( $product->variants as $variant ) {
								$tmp_product = clone( $variant );
								$tmp_product->processed_discount_value = round( $item->{$totaldiscount_str}, 2 );
								$object->products[] = $tmp_product;
							}
						}
						else {
							$tmp_product = clone( $product );
							$tmp_product->processed_discount_value = round( $item->{$totaldiscount_str}, 2 );
							$object->products[] = $tmp_product;
						}
						break;
					}
				}
			}
		}
//printr(array($this->order_total,$object->total));

		$cart->coupon = $object;
		$cart->full_total = $object->total;
//printrx($object);		
		return null;		
	}

	public function cart_coupon_delete( $cart ) {
		$cmsess = $this->get_coupon_session();
		if ( empty( $cmsess ) ) {
			return;
		}

		$this->delete_coupon_from_session();

		//$cart->cart_coupon = '';
		//hikashop_get( 'class.cart' )->save( $cart );
	}

	public function cart_coupon_process( & $coupon, & $total, & $zones, & $products, & $display_error, & $error_message, & $continue_execution ) {
		$this->init();

		if ( is_string( $coupon ) && $coupon === 'discount_auto_load' ) {
			$this->discount_auto_load = true;
		}
		else {
			$this->discount_auto_load = false;
			$this->loaded_coupon = & $coupon;
			$this->set_submittedcoupon( trim( isset( $coupon->discount_code ) ? $coupon->discount_code : @ $coupon->coupon_code ) );
		}

		$this->isrefresh = false;
		$this->cart_products = & $products;
		$this->order_total = & $total;
		$this->continue_execution = & $continue_execution;
		$this->is_display_error = $display_error;
		$this->continue_execution = false;

		if ( true === $this->discount_auto_load ) {
			$rtn = $this->process_autocoupon_helper();
		}
		else {
			if( $this->call_process_coupon_helper() ) {
				$rtn = $this->process_coupon_helper();

				if ( ! empty( $this->error_msgs ) ) {
					$error_message = implode( ' | ', $this->error_msgs );
				}
			}
			else {
				$rtn = false;
			}
		}


		return $rtn;
	}

	public function cart_coupon_displayname( $coupon_code ) {
	}

	public function order_new( & $order ) {
		if ( empty( $order ) ) {
			return;
		}
		$this->init();

		$coupon_session = $this->session_get( 'coupon', '' );
		if ( empty( $coupon_session ) ) {
			return;
		}
		if ( $coupon_session->cart_id != $order->cart->cart_id ) {
			return;
		}
		$this->save_coupon_history( (int) $order->order_id );

		return true;
	}

	public function order_status_changed( $data ) {
		$order_id = @ $data->order_id;
		$status_to = @ $data->order_status;
		$this->cleanup_ordercancel_helper( $order_id, $status_to );
		return true;
	}

	public function get_hikashop_coupons() {
		return $this->session_get( 'hikashop_coupons', array() );
	}

	protected function initialize_coupon() {

		parent::initialize_coupon();
		JFactory::getApplication()->setUserState( HIKASHOP_COMPONENT . '.coupon_code', '' );		

		// remove from session so coupon code is not called constantly
		$this->loaded_coupon = new stdClass();
	}

	protected function return_false( $key, $type = 'key', $force = 'donotforce' ) {
		if ( $this->is_display_error ) {
			if ( ! empty( $this->coupon_row ) ) {
				$hikashop_coupons = $this->get_hikashop_coupons();
				if ( isset( $hikashop_coupons[ $this->coupon_row->coupon_code ] ) ) {
					return false;
				}
			}
			return parent::return_false( $key, $type, $force );
		}
		return false;
	}

	protected function finalize_coupon( $master_output ) {
		$session_array = $this->save_discount_to_session( $master_output );
		if ( empty( $session_array ) ) {
			return false;
		}

		$session_array->cart_id = $this->cart_id;
		$this->session_set( 'coupon', $session_array );

		$this->finalize_coupon_store($session_array);

		return true;
	}

	protected function finalize_coupon_store( $coupon_session, $extra = array() ) {
		if ( ! $this->discount_auto_load ) {
			if ( empty( $this->loaded_coupon ) ) {
				$this->loaded_coupon = new stdclass;
			}
			if ( ! is_array( $extra ) ) {
				$extra = array();
			}
			if ( ! in_array( 'do_not_populate_discount_flat_amount', $extra ) ) {
				$this->loaded_coupon->cmcoupon = 1;
				$this->loaded_coupon->total = clone( $this->order_total );
				if ( ! empty( $coupon_session->product_discount ) ) {
				// needed to account for product discount in shipping min/max calculations
					$this->loaded_coupon->discount_flat_amount = $coupon_session->product_discount;
				}
			}
		}
	}

	protected function getuniquecartstring( $coupon_code = null ) {
		if ( empty( $coupon_code ) ) {
			@$coupon_code = $this->get_submittedcoupon();
		}
		if ( ! empty( $coupon_code ) ) {

			$order_total = $this->order_total->prices[0]->price_value_with_tax;
			/*if(version_compare($this->hikashop_version,'3.0.0','>=')) {
				if(!class_exists('hikashopCartClass')) require JPATH_ADMINISTRATOR.'/components/com_hikashop/classes/cart.php';
				$cartClass = new hikashopCartClass();
				$cart = $cartClass->getFullCart($this->cart_id,false);
				//printrx($cart->full_total->prices);
				if(!empty($cart->full_total->prices)) $order_total =  $cart->full_total->prices[0]->price_value_with_tax;
			}*/

			$user = AC()->helper->get_user();
			$user_email = ! empty( $this->customer->user_email ) ? $this->customer->user_email : '';
			@$shipping_id = version_compare( $this->hikashop_version, '2.2.0', '>=' ) ? $this->cart_shipping[0]->shipping_id : $this->cart_shipping->shipping_id;
			if ( ! empty( $this->the_shipping_id ) && empty( $shipping_id ) ) $shipping_id = $this->the_shipping_id;
			@$payment_id = $this->cart_payment->payment_id;
			$address = $this->get_customeraddress();
			$string = $order_total . '|' . $coupon_code . '|' . $user->id . '|' . $user_email;
			foreach ( $this->cart_products as $product ) {
				$string .= '|' . $product->cart_product_id . '|' . $product->product_id . '|' . $product->cart_product_quantity;
			}
			return $string . '|ship|' . $shipping_id . '|' . $address->country_id . '|' . $address->state_id . '|' . $payment_id . '|currency|' . $this->order_total->prices[0]->price_currency_id;
		}
		return;
	}

	protected function getuniquecartstringauto() {
		$user = AC()->helper->get_user();
		@$shipping_id = version_compare( $this->hikashop_version, '2.2.0', '>=' ) ? $this->cart_shipping[0]->shipping_id : $this->cart_shipping->shipping_id;
		if ( ! empty( $this->the_shipping_id ) && empty( $shipping_id ) ) $shipping_id = $this->the_shipping_id;
		@$payment_id = $this->cart_payment->payment_id;
		$address = $this->get_customeraddress();
		$string = $this->order_total->prices[0]->price_value_with_tax . '|' . $user->id;
		foreach ( $this->cart_products as $product ) {
			$string .= '|' . $product->cart_product_id . '|' . $product->product_id . '|' . $product->cart_product_quantity;
		}
		return $string . '|ship|' . $shipping_id . '|' . $address->country_id . '|' . $address->state_id . '|' . $payment_id . '|currency|' . $this->order_total->prices[0]->price_currency_id;
	}

	protected function get_storeshoppergroupids( $user_id ) {
		return AC()->store->get_group_ids( $user_id );
	}

	protected function get_storeproduct( $ids ) {
		$products = array();
		foreach ( $this->cart_products as $cartpricekey => $product ){
			$products[] = (object) array(
				'product_id' => $product->product_id,
				'asset_id' => $product->product_id,
			);
			if ( 'variant' === $product->product_type ) {
				if ( isset( $this->cart_products[ $product->cart_product_parent_id ] ) ) {
					$products[] = (object) array(
						'product_id' => $product->product_id,
						'asset_id' => $this->cart_products[ $product->cart_product_parent_id ]->product_id,
					);
				}
			}
		}
		return $products;
	}

	protected function get_storecategory( $ids ) {
		$items1 = AC()->db->get_objectlist( '
			SELECT c.category_id AS asset_id,c.product_id
			  FROM #__hikashop_product_category c
			  JOIN #__hikashop_product p ON p.product_id=c.product_id
			 WHERE c.product_id IN (' . $ids . ') AND p.product_type="main"
		' );
		//if ( $this->params->get( 'disable_coupon_product_children', 0 ) == 1 ) {
		//	return $items1;
		//}

		$items2 = AC()->db->get_objectlist( '
			SELECT c.category_id AS asset_id, p.product_id
			  FROM #__hikashop_product p
			  JOIN #__hikashop_product p2 ON p2.product_id=p.product_parent_id
			  JOIN #__hikashop_product_category c ON c.product_id=p2.product_id
			 WHERE p.product_id IN (' . $ids . ') AND p.product_type="variant"
		' );

		$categorys = array_merge( $items1, $items2 );
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
			$sql = 'SELECT category_parent_id, category_id FROM #__hikashop_category WHERE category_id IN (' . implode( ',', array_keys( $category_index ) ) . ')';
			$items = AC()->db->get_objectlist( $sql );
			$tmp_category_index = array();
			foreach ( $items as $item ) {
				if ( empty( $item->category_parent_id ) ) {
					continue;
				}
				foreach ( $category_index[ $item->category_id ] as $product_id ) {
					$categorys[] = (object) array(
						'asset_id' => $item->category_parent_id,
						'product_id' => $product_id,
					);
				}
				$tmp_category_index[ $item->category_parent_id ] = $category_index[ $item->category_id ];
			}
			$category_index = $tmp_category_index;
		}
		return $categorys;
	}

	protected function get_storemanufacturer( $ids ) {
		$items1 = AC()->db->get_objectlist( 'SELECT product_manufacturer_id AS asset_id, product_id FROM #__hikashop_product WHERE product_id IN (' . $ids . ') AND product_type="main"' );
		//if ( $this->params->get( 'disable_coupon_product_children', 0 ) == 1 ) {
		//	return $items1;
		//}

		$items2 = AC()->db->get_objectlist( '
			SELECT p2.product_manufacturer_id AS asset_id, p.product_id
			  FROM #__hikashop_product p
			  JOIN #__hikashop_product p2 ON p2.product_id=p.product_parent_id
			 WHERE p.product_id IN (' . $ids . ') AND p.product_type="variant"
		' );
		
		return array_merge($items1,$items2);		
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

		if ( version_compare( $this->hikashop_version, '3.0.0', '>=' ) ) {
			if ( empty( $this->cart_shipping ) ) {
				//$cartClass = new hikashopCartClass(); $cart = $cartClass->getFullCart( $this->cart_id, false );
				$cartClass = new CmCouponHikashopCouponHandlerCart();
				$cart = $cartClass->refreshHikashopCartProducts( $this->hikashop_version );
				$this->cart_shipping = $cart->shipping;
				if ( empty( $this->cart_shipping ) ) {
					$shipping_ids = AC()->db->get_value( 'SELECT cart_shipping_ids FROM #__hikashop_cart WHERE cart_id=' . (int) $this->cart_id );
					$shipping_ids = empty( $shipping_ids ) ? array() : explode( ',', $shipping_ids );

					$shipping_data = array();
					foreach ( $shipping_ids as $s ) {
						if ( strpos( $s, '@' ) === false ) {
							$shipping_data[0] = $s;
							continue;
						}
						list( $s_id, $w_id ) = explode( '@', $s, 2 );
						if ( is_numeric( $w_id ) ) {
							$w_id = (int) $w_id;
						}
						if ( is_numeric( $s_id ) ) {
							$s_id = (int) $s_id;
						}
						$shipping_data[ $w_id ] = $s_id;
					}
					$cart_shipping = array();
					foreach ( $shipping_data as $k => $id ) {
						foreach ( $cart->usable_methods->shipping as $item ) {
							if ( $item->shipping_id != $id ) {
								continue;
							}
							$cart_shipping[ $k ] = $item;
							break;
						}
					}
					$this->cart_shipping = $cart_shipping;
				}
			}
		}

		if ( ! empty( $this->cart_shipping ) ) {
			$shipping_objects = version_compare( $this->hikashop_version, '2.2.0', '<' ) ? array( $this->cart_shipping ) : $this->cart_shipping;
			$total_shipping_notax = $total_shipping_notax = $total_shipping = 0; 
			$shippings = array();
			foreach ( $shipping_objects as $shipping_object ) {
				$this_total_shipping_notax = $shipping_object->shipping_price;
				$this_total_shipping = isset( $shipping_object->shipping_price_with_tax ) ? $shipping_object->shipping_price_with_tax : $shipping_object->shipping_price;
				/*if ( $this->default_currency != $shipping_object->shipping_currency_id ) {
					// use default currency
					$currencyClass = hikashop_get( 'class.currency' );
					$this_total_shipping_notax = $currencyClass->convertUniquePrice( (float) $shipping_object->shipping_price, $shipping_object->shipping_currency_id, $this->default_currency );
					$this_total_shipping = $currencyClass->convertUniquePrice( (float) ( isset($shipping_object->shipping_price_with_tax) ? $shipping_object->shipping_price_with_tax : $shipping_object->shipping_price), $shipping_object->shipping_currency_id, $this->default_currency );
				}*/
				$total_shipping_notax += $this_total_shipping_notax;
				$total_shipping += $this_total_shipping;
				$shipping_ids = explode( '-', $shipping_object->shipping_id, 2 );
				$shipping_id = $shipping_ids[0];
				$this_total_shipping_notax = round( $this_total_shipping_notax, 4 );
				$shippings[] = (object) array(
					'shipping_id' => $shipping_id,
					'total_notax' => $this_total_shipping_notax,
					'total' => $this_total_shipping,
					'tax_rate' => empty( $this_total_shipping_notax ) ? 0 : ( $this_total_shipping - $this_total_shipping_notax ) / $this_total_shipping_notax,
					'totaldiscount' => 0,
					'totaldiscount_notax' => 0,
					'totaldiscount_tax' => 0,
					'coupons' => array(),
				);
			}
			$shipping = (object) array(
				'shipping_id' => $shipping_id,
				'total_notax' => $total_shipping_notax,
				'total' => $total_shipping,
				'shippings' => $shippings,
			);
		}

		return $shipping;
	}

	protected function get_storepayment() {
		$payment = (object) array(
			'payment_id' => 0,
			'total_notax' => 0,
			'total' => 0,
		);

		if ( ! empty( $this->cart_payment ) ) {
			$payment = (object) array(
				'payment_id' => $this->cart_payment->payment_id,
				'total_notax' => 0,
				'total' => 0,
			);
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

		$app = JFactory::getApplication();
		$shipping_address_id = $app->getUserState( HIKASHOP_COMPONENT . '.shipping_address', 0 );
		$billing_address_id = $app->getUserState( HIKASHOP_COMPONENT . '.billing_address', 0 );

		if ( empty( $billing_address_id ) ) {
			return $address;
		}

		$addressClass = hikashop_get( 'class.address' );
		$this->customer->billing_address = $addressClass->get( $billing_address_id );
		if ( empty( $this->customer->billing_address ) ) {
			return $address;
		}

		$array = array( &$this->customer->billing_address );
		$addressClass->loadZone( $array, 'parent' );
		if ( ! empty( $addressClass->fields ) ) {
			$this->customer->fields =& $addressClass->fields;
		}

		if ( ! empty( $this->customer->billing_address->address_country ) ) {
			$zone_key = $this->customer->billing_address->address_country;
			if ( is_array( $zone_key ) ) {
				$zone_key = reset( $zone_key );
			}

			$tmp = explode( '_', $zone_key );
			$country_id = array_pop( $tmp );

			$address->country_id = $country_id;
			if ( ! empty( $this->customer->fields['address_country']->field_value ) ) {
				$address->country_name = $this->customer->fields['address_country']->field_value[ $zone_key ]->value;
			}
		}

		if ( ! empty( $this->customer->billing_address->address_state ) ) {
			$zone_key = $this->customer->billing_address->address_state;
			if ( is_array( $zone_key ) ) {
				$zone_key = reset( $zone_key );
			}
			$tmp = explode( '_', $zone_key );
			$state_id = array_pop( $tmp );

			$address->state_id = $state_id;
			if ( ! empty( $this->customer->fields['address_state']->field_value ) ) {
				$address->state_name = $this->customer->fields['address_state']->field_value[ $zone_key ]->value ;
			}
		}

		return $address;
	}

	protected function get_submittedcoupon() {
		if ( empty( $this->submitted_coupon ) ) {
			return '';
		}

		if ( $this->submitted_coupon == $this->get_frontend_lang( 'auto' ) ) {
			return '';
		}

		$cmsess = $this->get_coupon_session();
		if ( ! empty( $cmsess ) ) {
			if ( $cmsess->coupon_code == $this->submitted_coupon ) {
				return '';
			}
		}

		return $this->submitted_coupon; 
	}

	protected function set_submittedcoupon( $coupon_code ) {
		$this->submitted_coupon = $coupon_code;
	}

	protected function get_orderemail( $order_id ) {
		$sql = 'SELECT u.user_email
				  FROM #__hikashop_order o
				  JOIN #__hikashop_user u on u.user_id=o.order_user_id
				 WHERE o.order_id=' . (int) $order_id;
		return AC()->db->get_value( $sql );
	}

	protected function is_coupon_in_store( $coupon_code ) {
		if ( empty( $coupon_code ) ) {
			return false;
		}
		$coupon_id = (int) AC()->db->get_value( 'SELECT discount_id FROM #__hikashop_discount WHERE discount_code="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );
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
			$this->refresh_hikashop_cartproducts();
		}

		JPluginHelper::importPlugin( 'cmcoupon' );
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger( 'cmDefineCartItemsBefore', array( & $this->cart_products, $this ) );

		$group = hikashop_config()->get( 'group_options', 0 );
		$currencyClass = hikashop_get( 'class.currency' );

		foreach ( $this->cart_products as $cartpricekey => $product ){
			$product_id = $product->product_id;

			if ( empty( $product_id ) ) {
				continue;
			}
			if ( $group && $product->cart_product_option_parent_id ) {
				continue;
			}
			if ( empty( $product->cart_product_quantity ) ) {
				continue;
			}

			if ( ! HIKASHOP_PHP5 ){
				$price_object = $cart->full_total;
			}
			else {
				$price_object = new stdClass();
				if ( ! empty( $product->prices ) ) {
					$price_object = json_decode( json_encode( $product->prices[0], JSON_FORCE_OBJECT ) );
					// cannot use clone because it does not deep clone
				}
			}
			//if ( $product->product_type == 'variant' ) {
			//	if ( isset( $this->cart_products[ $product->cart_product_parent_id ] ) ) {
			//		$product_id = $this->cart_products[ $product->cart_product_parent_id ]->product_id;
			//	}
			//}			

			if ( $group ){
				foreach ( $this->cart_products as $j => $optionElement ){
					if ( $optionElement->cart_product_option_parent_id != $product->cart_product_id ) {
						continue;
					}
					if ( ! empty( $optionElement->prices[0] ) ) {
						if ( ! isset( $price_object ) ) {
							$price_object = new stdClass();
							$price_object->price_value = 0;
							$price_object->price_value_with_tax = 0;
							$price_object->price_currency_id = hikashop_getCurrency();
						}
						foreach ( get_object_vars( $price_object ) as $key => $value ) {
							if ( is_object( $value ) ) {
								foreach ( get_object_vars( $value ) as $key2 => $var2 ) {
									if ( strpos( $key2, 'price_value' ) !== false ) {
										$price_object->$key->$key2 += @ $optionElement->prices[0]->$key->$key2;
									}
								}
							}
							else {
								if ( strpos( $key, 'price_value' ) !== false ) {
									$price_object->$key += @ $optionElement->prices[0]->$key;
								}
							}
						}
					}
				}
			}

			if ( empty( $price_object->unit_price ) ) {
				$price_notax = 0;
				$price = 0;
				$product_discount = 0;
			}
			else {
				$price_notax = $price_object->unit_price->price_value;
				$price = $price_object->unit_price->price_value_with_tax;
				/*if ( $this->default_currency != $price_object->unit_price->price_currency_id ) {
					// price in default currency
					$price_notax = $currencyClass->convertUniquePrice( (float) $price_object->unit_price->price_value, $price_object->unit_price->price_currency_id, $this->default_currency );
					$price = $currencyClass->convertUniquePrice( (float) $price_object->unit_price->price_value_with_tax, $price_object->unit_price->price_currency_id, $this->default_currency );
				}*/
				$product_discount = ! empty( $price_object->unit_price->price_value_without_discount ) && $price_object->unit_price->price_value_without_discount > $price_object->unit_price->price_value
					? $price_object->unit_price->price_value_without_discount - $price_object->unit_price->price_value
					: 0;
			}

			$this->cart->items_def[ $product_id ]['product'] = $product_id;
			$this->cart->items [] = array(
				'product_id' => $product_id,
				'cartpricekey' => $cartpricekey,
				'cart_product_id' => $product->cart_product_id,
				'actual_product_id' => $product_id,
				'discount' => $product_discount,
				'product_price' => $price,
				'product_price_notax' => $price_notax,
				'product_price_tax' => $price - $price_notax,
				'tax_rate' => empty( $price_notax ) ? 0 : ( $price - $price_notax ) / $price_notax,
				'qty' => $product->cart_product_quantity,
				'is_special' => 0,
				'is_discounted' => ! empty( $product_discount ) ? 1 : 0,
			);
			$this->product_total += $product->cart_product_quantity * $price;
			$this->product_qty += $product->cart_product_quantity;
		}

		JPluginHelper::importPlugin( 'cmcoupon' );
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger( 'cmDefineCartItemsAfter', array( & $this->cart->items_def, & $this->cart->items, $this->cart_products, $this ) );

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
						$sql = 'SELECT product_id
								  FROM #__hikashop_product p
								 WHERE product_published=1
								 '.$where_product.'
								   AND product_id IN (' . $ids . ')
								   AND product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'category' == $asset_type ) {
						$sql = 'SELECT c.product_id
								  FROM #__hikashop_product_category c
								  JOIN #__hikashop_product p ON p.product_id=c.product_id
								 WHERE p.product_published=1
								   AND c.category_id IN (' . $ids . ')
								   AND c.product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'manufacturer' == $asset_type ) {
						$sql = 'SELECT product_id
								  FROM #__hikashop_product p
								 WHERE product_published=1
								   AND product_manufacturer_id IN (' . $ids . ')
								   AND product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'vendor' == $asset_type ) {
					}
				} elseif ( 'exclude' == $mode ) {
					if ( 'product' == $asset_type ) {
						$sql = 'SELECT product_id
								  FROM #__hikashop_product p
								 WHERE product_published=1
								 '.$where_product.'
								   AND product_id NOT IN (' . $ids . ')
								   AND product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'category' == $asset_type ) {
						$sql = 'SELECT c.product_id
								  FROM #__hikashop_product_category c
								  JOIN #__hikashop_product p ON p.product_id=c.product_id
								 WHERE p.product_published=1
								   AND c.category_id NOT IN (' . $ids . ')
								   AND c.product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					} elseif ( 'manufacturer' == $asset_type ) {
						$sql = 'SELECT product_id
								  FROM #__hikashop_product p
								 WHERE product_published=1
								   AND product_manufacturer_id NOT IN (' . $ids . ')
								   AND product_id NOT IN (' . implode( ',', $used_ids ) . ')
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
				}
				if ( ! empty( $the_product ) && ! empty( $coupon_row->params->exclude_discounted ) ) {
					$product = $this->get_hikashop_product($the_product);
					$product_discount = ! empty( $product->prices[0]->price_value_without_discount ) && $product->prices[0]->price_value_without_discount > $product->prices[0]->price_value ? 1 : 0;
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

		$product_id_toadd = $product_id;
		$variants = AC()->db->get_objectlist( 'SELECT * FROM #__hikashop_product WHERE product_parent_id =' . $product_id . ' AND product_published=1' );
		if ( ! empty( $variants ) ) {
			$is_found = false;
			foreach ( $variants as $variant ) {
				foreach ( $this->cart->items as $k2 => $item ) {
					if ( $item['actual_product_id'] == $variant->product_id ) {
						$is_found = true;
						$product_id_toadd = $variant->product_id;
						break;
					}
				}
			}
			if ( ! $is_found ) {
				$product_id_toadd = $variants[0]->product_id;
			}
		}

		$add = 1;
		$class = hikashop_get( 'class.cart' );
		$status = $class->update( $product_id_toadd, $qty, $add, 'product', false, true, $this->cart_id );

		if ( $status ) {
			if ( empty( $class->cart ) ) {
				$class->cart = new stdclass;
			}
			$class->cart->cart_id = $this->cart_id; // needed or receive a cart is empty error message

			$this->isrefresh = true;
		}

		return $status;
	}

	private function refresh_hikashop_cartproducts() {

		$this->cart_products = array();

		$cartClass = new CmCouponHikashopCouponHandlerCart();
		$cart = $cartClass->refreshHikashopCartProducts( $this->hikashop_version );
		if ( empty( $cart ) ) {
			return;
		}

		if ( isset( $cart->cart_products ) ) {
			foreach ( $cart->products as $k => $product ) {
				$cart->products[ $k ]->cart_id = $cart->cart_products[ $k ]->cart_id;
			}
		}
		//if ( empty( $this->order_total ) ) {
		$this->order_total = $cart->full_total;
		//}
		$this->cart_products = $cart->products;
	}

	private function get_hikashop_product( $product_id ) {
		$database = JFactory::getDBO();
		$database->setQuery('
			SELECT a.*, b.product_category_id, b.category_id, b.ordering FROM '.hikashop_table('product').' AS a
			  LEFT JOIN '.hikashop_table('product_category').' AS b ON a.product_id = b.product_id
			 WHERE a.product_id=' . (int)$product_id.' LIMIT 1
		');
		$element = $database->loadObject();
		if(empty($element)) return;

		$config =& hikashop_config();
		$currency_id = hikashop_getCurrency();
		$zone_id = hikashop_getZone(null);
		$main_currency = (int)$config->get('main_currency',1);
		$discount_before_tax = (int)$config->get('discount_before_tax',0);


		$currencyClass = hikashop_get('class.currency');
		$productClass = hikashop_get('class.product');
		$cart = hikashop_get('helper.cart');

		if($element->product_type == 'variant') {
			$filters=array('a.product_id='.$element->product_parent_id);
			hikashop_addACLFilters($filters,'product_access','a');
			$query = 'SELECT a.*,b.* FROM '.hikashop_table('product').' AS a LEFT JOIN '.hikashop_table('product_category').' AS b ON a.product_id = b.product_id WHERE '.implode(' AND ',$filters). ' ORDER BY product_category_id ASC LIMIT 1';
			$database->setQuery($query);
			$element = $database->loadObject();

			if(empty($element))
				return;

			$product_id = $element->product_id;
		}


		$productClass->addAlias($element);
		if(!$element->product_published) return;


		$filters = array('a.product_id ='.$product_id,'a.product_related_type=\'options\'','b.product_published=1','(b.product_sale_start=\'\' OR b.product_sale_start<='.time().')','(b.product_sale_end=\'\' OR b.product_sale_end>'.time().')');
		hikashop_addACLFilters($filters,'product_access','b');
		$query = 'SELECT b.* FROM '.hikashop_table('product_related').' AS a LEFT JOIN '.hikashop_table('product').' AS b ON a.product_related_id	= b.product_id WHERE '.implode(' AND ',$filters).' ORDER BY a.product_related_ordering ASC, a.product_related_id ASC';
		$database->setQuery($query);
		$element->options = $database->loadObjectList('product_id');

		$ids = array($product_id);
		if(!empty($element->options)) {
			foreach($element->options as $optionElement) {
				$ids[] = $optionElement->product_id;
			}
		}

		$filters = array('product_parent_id IN ('.implode(',',$ids).')');
		hikashop_addACLFilters($filters,'product_access');
		$query = 'SELECT * FROM '.hikashop_table('product').' WHERE '.implode(' AND ',$filters);
		$database->setQuery($query);
		$variants = $database->loadObjectList();
		if(!empty($variants)) {
			foreach($variants as $variant) {
				$ids[] = $variant->product_id;
				if($variant->product_parent_id == $product_id) {
					$element->variants[$variant->product_id] = $variant;
				}
				if(!empty($element->options)) {
					foreach($element->options as $k => $optionElement) {
						if($variant->product_parent_id == $optionElement->product_id) {
							$element->options[$k]->variants[$variant->product_id] = $variant;
							break;
						}
					}
				}
			}
		}
		$sort = $config->get('characteristics_values_sorting');
		if($sort == 'old') {
			$order = 'characteristic_id ASC';
		} elseif($sort == 'alias') {
			$order = 'characteristic_alias ASC';
		} elseif($sort == 'ordering') {
			$order = 'characteristic_ordering ASC';
		} else {
			$order = 'characteristic_value ASC';
		}



		$currencyClass->getPrices($element,$ids,$currency_id,$main_currency,$zone_id,$discount_before_tax);

		return $element;
	}

	private function call_process_coupon_helper() {
		$cmsess = $this->session_get( 'coupon', '' );
		if ( empty( $cmsess ) ) {
			return true;
		}

		$submitted_coupon_code = $this->get_submittedcoupon();
		if ( empty( $submitted_coupon_code ) ) {
			return true;
		}

		$tmp = AC()->db->get_value( 'SELECT discount_id FROM #__hikashop_discount WHERE discount_code="' . AC()->db->escape( $submitted_coupon_code ) . '"' );
		if ( empty( $tmp ) ) {
			return true;
		}

		$tmp = AC()->db->get_value( 'SELECT id FROM #__cmcoupon WHERE estore="' . $this->estore . '" AND coupon_code="' . AC()->db->escape( $submitted_coupon_code ) . '"' );
		if ( ! empty( $tmp ) ) {
			return true;
		}

		$this->continue_execution = false;
		$this->finalize_coupon_store( $cmsess, array( 'do_not_populate_discount_flat_amount' ) );

		return false;
	}
}

