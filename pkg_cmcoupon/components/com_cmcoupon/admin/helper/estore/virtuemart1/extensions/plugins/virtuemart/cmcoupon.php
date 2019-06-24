<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

if( ! defined( '_VALID_MOS' ) && ! defined( '_JEXEC' ) ) die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' ) ;


class plgVirtuemartCmCoupon extends JPlugin {

	public function __construct( & $subject, $config ){
		parent::__construct( $subject, $config );		
	}

	public function onCouponRemove( $d ) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		AC()->storediscount->order_new( $d );
	}

	public function onCouponProcess( $d ) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		$ret = AC()->storediscount->cart_coupon_validate( $d );
		if ( $ret !== null ) {
			$errors = AC()->storediscount->error_msgs;
			if ( ! empty( $errors ) ) {
				$GLOBALS['coupon_error'] = implode( '<br />', $errors );
			}
		}
		return $ret;
	}

	public function onCouponProcessAuto( $d ) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		AC()->storediscount->cart_coupon_validate_auto( $d );
	}

	public function onOrderStatusUpdate( $d, $order_status_code ) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}

		AC()->storegift->order_status_changed( $d, $order_status_code );

		AC()->storediscount->order_status_changed( $d, $order_status_code );
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

}

// No closing tag