<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

if( ! defined( '_VALID_MOS' ) && ! defined( '_JEXEC' ) ) die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' ) ;


class plgVmCouponCmCoupon extends JPlugin {

	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
	}

	public function onAfterDispatch() {
		$app = JFactory::getApplication();
		if ($app->isAdmin()) return; 

		if ( ! $this->init_cmcoupon() ) {
			return;
		}

		$option = AC()->helper->get_request( 'option' );
		if($option!='com_virtuemart') return;

		$task1 = AC()->helper->get_request( 'task' );
		$task2 = AC()->helper->get_request( 'task2' );
		if($task1!='deletecoupons' && $task2!='deletecoupons') return;

		AC()->storediscount->cart_coupon_delete( (int) AC()->helper->get_request( 'id' ) );

		$app->redirect( JRoute::_( 'index.php?option=com_virtuemart&view=cart&Itemid=' . (int) AC()->helper->get_request( 'Itemid' ) ) );
		
		return;
	}

	public function plgVmValidateCouponCode( $coupon_code, $_billTotal ) {
		if ( ! $this->init_cmcoupon() ) {
			return null;
		}
		return AC()->storediscount->cart_coupon_validate( $coupon_code );
	}

	public function plgVmCouponInUse( $_code ) {
		if ( ! $this->init_cmcoupon() ) {
			return null;
		}

		$order_id = AC()->helper->get_request( 'virtuemart_order_id' );
		return AC()->storediscount->order_new( $order_id );
	}

	public function plgVmRemoveCoupon( $_code, $_force ) {
		if ( ! $this->init_cmcoupon() ) {
			return null;
		}

		$process = false;
		if ( version_compare( AC()->storediscount->vmversion, '2.0.26', '<' ) || version_compare( AC()->storediscount->vmversion, '2.0.26', '=' ) && empty( AC()->storediscount->vmversion_letter ) ) {
			$process = true;
		}

		if ( ! $process ) {
			return null;
		}

		$order_id = AC()->helper->get_request( 'virtuemart_order_id' );
		return AC()->storediscount->order_new( $order_id );
	}

	public function plgVmCouponHandler( $_code, & $_cartData, & $_cartPrices ) {
		if ( ! $this->init_cmcoupon() ) {
			return null;
		}

		$rtn = AC()->storediscount->cart_coupon_handler( $_code, $_cartData, $_cartPrices );
		if ( AC()->storediscount->isrefresh ) {
			$is_vmonepagecheckout = class_exists('plgSystemVPOnePageCheckout') ? true : false;
			if ( ! $is_vmonepagecheckout ) {
				JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart&view=cart&Itemid=' . (int) AC()->helper->get_request( 'Itemid' ) );
				return;
			}
		}
		return $rtn;
	}

	public function plgVmUpdateTotals( & $_cartData, & $_cartPrices ) {
		if ( ! $this->init_cmcoupon() ) {
			return null;
		}
		return AC()->storediscount->cart_calculate_totals( $_cartData, $_cartPrices );
	}
	
	public function plgVmCouponUpdateOrderStatus( $data, $order_status_code ) {
		if ( ! $this->init_cmcoupon() ) {
			return null;
		}

		AC()->storegift->order_status_changed( $data, $order_status_code );

		AC()->storediscount->order_status_changed( $data );

		return null;
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

	private function clear_joomla_message_queue() {
		JFactory::getSession()->set( 'application.queue', null );
		JFactory::getApplication()->set( '_messageQueue', null );
		if ( ! class_exists( 'ReflectionClass' ) ) {
			return;
		}
		$app = JFactory::getApplication(); 
		$appReflection = new ReflectionClass( $app );
		$_messageQueue = $appReflection->getProperty( '_messageQueue' );
		$_messageQueue->setAccessible( true );
		$_messageQueue->setValue( $app, array() );
	}
}

// No closing tag