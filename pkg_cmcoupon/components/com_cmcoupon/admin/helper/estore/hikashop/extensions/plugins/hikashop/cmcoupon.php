<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

if( ! defined( '_VALID_MOS' ) && ! defined( '_JEXEC' ) ) die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' ) ;

class plgHikashopCmCoupon extends JPlugin {

	public function __construct(& $subject, $config){ 
		parent::__construct($subject, $config);
	}

	public function onBeforeCouponLoad( $coupon_code, & $continue_execution ) {
		if ( JFactory::getApplication()->isAdmin() ) {
			return;
		}

		if ( ! $this->init_cmcoupon() ) {
			return;
		}

		return AC()->storediscount->cart_coupon_validate( $coupon_code, $continue_execution );
	}

	public function onAfterCartProductsLoad ( &$cart ) {
		static $_called_number = 0;
		$_called_number++;
		if ( $_called_number >= 60 ) {
			AC()->param->set( 'disable_hikashop_couponprocess_onAfterCartProductsLoad', 1 );
		}

		if ( ! empty( $cart->cmcoupon_bypass ) && $cart->cmcoupon_bypass ) {
			return;
		}
		if ( ! empty( $cart->coupon ) ) {
			return;
		}
		if ( JFactory::getApplication()->isAdmin() ) {
			return;
		}
		if ( ! $this->init_cmcoupon() ) {
			return;
		}

		AC()->storediscount->cart_coupon_validate_auto( $cart );
	}

	public function onBeforeCouponCheck( & $coupon, & $total, & $zones, & $products, & $display_error, & $error_message, & $continue_execution ) {
		if ( ! empty( $cart->cmcoupon_bypass ) && $cart->cmcoupon_bypass ) {
			return;
		}

		$coupon_orig = $coupon;
		$rtn = AC()->storediscount->cart_coupon_process( $coupon, $total, $zones, $products, $display_error, $error_message, $continue_execution );

		// needed because php not evaluating empty object as empty
		$test = (array) $coupon;
		if ( empty( $test ) ) {
			$coupon = null;
		}

		if ( AC()->storediscount->isrefresh ) {
		}

		{ // update cart with no coupon or first coupon depending on what cmcoupon returns
			$cartClass = hikashop_get( 'class.cart' );

			if ( version_compare( $this->get_version(), '3.0.0', '>=' ) ) {
				$cart = $cartClass->get( 0 );
			}
			else {
				$cart = $cartClass->loadCart();
				if ( empty( $cart ) ) {
					$cart = $cartClass->cart;
				}
			}

			if ( ! empty( $cart->cart_coupon ) ) {
				$hikashop_coupons = AC()->storediscount->get_hikashop_coupons();
				if ( isset( $hikashop_coupons[ $cart->cart_coupon ] ) ) {
					if ( AC()->param->get( 'enable_store_coupon', 0 ) == 1 ) {
						$continue_execution = true;
					}
					return;
				}
				$first_coupon = '';
				$coupon_session = AC()->storediscount->get_coupon_session();
				if ( ! empty( $coupon_session ) ) {
					$first_coupon = current( explode( ';', $coupon_session->coupon_code_internal ) );
				}
				$cart->cart_coupon = $first_coupon;
				if ( $cartClass->save( $cart ) ) {
					JFactory::getApplication()->setUserState( HIKASHOP_COMPONENT . '.coupon_code', $first_coupon );
				}
			}
		}
	}

	public function onAfterCartShippingLoad( & $cart ) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}

		if ( version_compare( $this->get_version(), '3.0.0', '<' ) ) {
			if ( AC()->helper->get_request( 'removecoupon', 0 ) || AC()->helper->get_request( 'checkout.removecoupon', 0 ) ) {
				AC()->storediscount->cart_coupon_delete( $cart );
				return;
			}
		}

		AC()->storediscount->cart_calculate_totals( $cart );
	}

	public function onAfterCartSave ( & $cart ) {
		// this trigger is available in Hikashop 3.0.0 and up
		if ( ! $this->init_cmcoupon() ) {
			return;
		}

		if ( AC()->helper->get_request( 'removecoupon', 0 ) || AC()->helper->get_request( 'checkout.removecoupon', 0 ) ) {
			if ( empty( $cart->cart_coupon ) ) {
				AC()->storediscount->cart_coupon_delete( $cart );
			}
		}
	}

	public function onAfterOrderCreate( & $order, & $send_email ) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}

		AC()->storediscount->order_new( $order );
		AC()->storegift->order_status_changed( $order );
	}

	public function onAfterOrderUpdate(& $order,&$send_email) {
		if ( ! $this->init_cmcoupon() ) {
			return null;
		}

		AC()->storegift->order_status_changed( $order );
		AC()->storediscount->order_status_changed( $order );
	}

	public function onHikashopBeforeDisplayView( & $view ){
		if ( @ $view->ctrl == 'checkout' ) {
			ob_start();
		}
	}

	public function onHikashopAfterDisplayView(&$view){
		if ( @ $view->ctrl != 'checkout' ) {
			return;
		}
		$html = ob_get_clean();

		if ( strpos( $html, 'window.checkout.removeCoupon' ) !== false ) {
			if ( strpos( $html, 'checkout[coupon]' ) === false ) {
				if ( empty( $view->module_position ) ) {
					$view->module_position = 1;
				}
				$html = preg_replace(
					'/(<a href="#removeCoupon".*?\<\/a\>)/is',
					'$1
					<label for="hikashop_checkout_coupon_input_' . $view->step . '_' . $view->module_position . '">' . JText::_( 'HIKASHOP_ENTER_COUPON' ) . '</label>
					<div class="input-append">
						<input class="hikashop_checkout_coupon_field" id="hikashop_checkout_coupon_input_' . $view->step . '_' . $view->module_position . '" type="text" name="checkout[coupon]" value=""/>
						<button type="submit" onclick="return window.checkout.submitCoupon(' . $view->step . ',' . $view->module_position . ');" class="' . $view->config->get( 'css_button', 'hikabtn' ) . ' hikabtn_checkout_coupon_add">
							' . JText::_( 'ADD' ) . '
						</button>
					</div>
',
					$html
				);
			}
		}
		elseif ( strpos( $html, 'id="hikashop_checkout_coupon"' ) !== false ) {
			if ( strpos( $html, 'hikashop_checkout_coupon_input' ) === false ) {
				$html = str_replace(
					'<span class="hikashop_checkout_coupon" id="hikashop_checkout_coupon">',
					'<span class="hikashop_checkout_coupon" id="hikashop_checkout_coupon">
						' . JText::_( 'HIKASHOP_ENTER_COUPON' ) . ' <input id="hikashop_checkout_coupon_input" type="text" name="coupon" value="" />
						<input type="submit" class="button hikashop_cart_input_button" name="refresh" value="' . JText::_( 'ADD' ) . '" onclick="return hikashopCheckCoupon(\'hikashop_checkout_coupon_input\');"/><br />',
					$html
				);
			}
		}
		echo $html;
	}

	private function init_cmcoupon() {
		if ( ! class_exists( 'cmcoupon' ) ) {
			if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php' ) ) {
				return false;
			}
			require JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php';
		}
		if ( ! class_exists( 'cmcoupon' ) ) {
			return false;
		}
		CmCoupon::instance();
		AC()->init();
		return true;
	}

	private function get_version() {
		static $version;
		if ( ! empty( $version ) ) {
			return $version;
		}
		if ( file_exists( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop_j3.xml' ) ) {
			$parser = simplexml_load_file( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop_j3.xml' );
		}
		elseif ( file_exists( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop.xml' ) ) {
			$parser = simplexml_load_file( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop.xml' );
		}
		if ( ! empty( $parser ) ) {
			$version = (string) $parser->version;
		}

		return $version;
	}
}
