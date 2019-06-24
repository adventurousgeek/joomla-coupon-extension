<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

if( ! defined( '_VALID_MOS' ) && ! defined( '_JEXEC' ) ) die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' ) ;

class plgEshopCmCoupon extends JPlugin {

	public function __construct(& $subject, $config){ parent::__construct($subject, $config); }

	public function onAfterDispatch() {
		$app = JFactory::getApplication();
		if ( $app->isAdmin() ) {
			return; 
		}
		$option = $app->input->getCmd( 'option' ); 
		if ( $option != 'com_eshop' ) {
			return;
		}
		$task1 = $app->input->get->get( 'task' );
		$task2 = $app->input->get->get( 'task2' );
		if ( $task1 != 'deletecoupons' && $task2 != 'deletecoupons' ) {
			return;
		}

		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		AC()->storediscount->cart_coupon_delete( $app->input->getInt( 'cid' ) );

		$app->redirect( 'index.php?option=com_eshop&view=cart&Itemid=' . $app->input->getInt( 'Itemid' ) );

		return;
	}

	public function onGetCouponData($coupon_code) { 
		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		return AC()->storediscount->cart_coupon_validate( $coupon_code );
	}

	public function onGetCouponCosts(&$totalData, &$total, &$taxes) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}

		static $auto_called = false;
		if ( ! $auto_called ) {
		// automatic  coupon process, only call once per load
			$auto_called = true;
			AC()->storediscount->cart_coupon_validate_auto();
		}
		return AC()->storediscount->cart_calculate_totals( $totalData, $total, $taxes );
	}

	public function onAfterStoreOrder($order) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		AC()->storediscount->order_new( $order );
		
		AC()->storegift->order_status_changed( $order->id );
	}

	public function onAfterCompleteOrder($order) {
		if ( ! $this->init_cmcoupon() ) {
			return;
		}
		AC()->storegift->order_status_changed( $order->id );
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

