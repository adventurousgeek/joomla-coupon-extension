<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

if( ! defined( '_VALID_MOS' ) && ! defined( '_JEXEC' ) ) die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' ) ;


class plgRedshop_CouponCmCoupon extends JPlugin {

	public function __construct(& $subject, $config){
		parent::__construct($subject, $config);
	}

	public function onOrderGiftcertSend($order_id) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		AC()->storegift->order_status_changed( $order_id );
	}

	public function onCouponRemove($order_id) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		AC()->storediscount->order_new($order_id);
	}

	public function onCouponProcess( $c_data ) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		$coupon_code = AC()->helper->get_request( 'discount_code', '' );
		AC()->storediscount->cart_coupon_validate( $c_data, $coupon_code );
	}

	public function onBeforeCartLoad() {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		AC()->storediscount->cart_coupon_validate_auto();
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