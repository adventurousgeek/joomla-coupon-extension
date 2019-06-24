<?php
/**
 * @component CmCoupon Pro
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

if( ! defined( '_VALID_MOS' ) && ! defined( '_JEXEC' ) ) die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' ) ;


class plgVmCouponCmCoupon extends JPlugin {

	function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
	}

	public function onAfterDispatch() {
		$app = JFactory::getApplication();
		if ($app->isAdmin()) return; 
	  
		$option = JRequest::getCmd('option'); 
		if($option!='com_virtuemart') return;
		
		$task1 = JRequest::getCmd('task');
		$task2 = JRequest::getCmd('task2');
		if($task1!='deletecoupons' && $task2!='deletecoupons') return;
		
		$cm_file = JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helpers/estore/virtuemart/couponhandler.php';
		if(!file_exists($cm_file)) return;
		
		if(!class_exists('CmCouponVirtuemartCouponHandler')) require $cm_file;
		CmCouponVirtuemartCouponHandler::run_deleteCouponFromSession(JRequest::getInt('id'));
		
		$app->redirect('index.php?option=com_virtuemart&view=cart&Itemid='.JRequest::getInt('Itemid'));
		
		return;
	}


	function plgVmValidateCouponCode($_code,$_billTotal) {
		$cm_file = JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helpers/estore/virtuemart/couponhandler.php';
		if(!file_exists($cm_file)) return null;
		
		if(!class_exists('CmCouponVirtuemartCouponHandler')) require $cm_file;
		return CmCouponVirtuemartCouponHandler::validate_coupon($_code);
	}

	function plgVmCouponInUse($_code) {
		$cm_file = JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helpers/estore/virtuemart/couponhandler.php';
		if(!file_exists($cm_file)) return null;
		
		if(!class_exists('CmCouponVirtuemartCouponHandler')) require $cm_file;
		return CmCouponVirtuemartCouponHandler::remove_coupon_code($_code);
	}
	
	function plgVmRemoveCoupon($_code,$_force) {
		require_once(JPATH_VM_ADMINISTRATOR.DS.'version.php'); 
		$vmversion = VmVersion::$RELEASE;	
		preg_match('/^(.*?)([a-zA-Z])?$/',$vmversion,$vmversion);
		if(empty($vmversion[1])) $vmversion[1] = $vmversion[0];
		
		$process = false;
		if (version_compare($vmversion[1], '2.0.26', '<')) $process = true;
		elseif($vmversion[1]=='2.0.26' && empty($vmversion[2])) $process = true;

		if ( ! $process) return null;
		
		$cm_file = JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helpers/estore/virtuemart/couponhandler.php';
		if(!file_exists($cm_file)) return null;
		
		if(!class_exists('CmCouponVirtuemartCouponHandler')) require $cm_file;
		return CmCouponVirtuemartCouponHandler::remove_coupon_code($_code);
	}

	
	function plgVmCouponHandler($_code, & $_cartData, & $_cartPrices) {
		$cm_file = JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helpers/estore/virtuemart/couponhandler.php';
		if(!file_exists($cm_file)) return null;
		
		if(!class_exists('CmCouponVirtuemartCouponHandler')) require $cm_file;
		//return CmCouponVirtuemartCouponHandler::process_coupon_code($_code, $_cartData, $_cartPrices );
		list($rtn, $isrefresh) = CmCouponVirtuemartCouponHandler::process_coupon_code($_code, $_cartData, $_cartPrices );
		if($isrefresh) {
			JFactory::getApplication()->redirect('index.php?option=com_virtuemart&view=cart&Itemid='.JRequest::getInt('Itemid'));
			return;
		}
		return $rtn;
	}
	function plgVmUpdateTotals( & $_cartData, & $_cartPrices) {
		$cm_file = JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helpers/estore/virtuemart/couponhandler.php';
		if(!file_exists($cm_file)) return null;
		
		if(!class_exists('CmCouponVirtuemartCouponHandler')) require $cm_file;
		return CmCouponVirtuemartCouponHandler::finalize_updatetotals($_cartData, $_cartPrices );
	}
	
	function plgVmCouponUpdateOrderStatus($data, $order_status_code) {
		$cm_file_1 = JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helpers/estore/virtuemart/giftcerthandler.php';
		$cm_file_2 = JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helpers/estore/virtuemart/couponhandler.php';
		if(!file_exists($cm_file_1) || !file_exists($cm_file_2)) return null;
		
		if(!class_exists('CmCouponVirtuemartGiftcertHandler')) require $cm_file_1;
		CmCouponVirtuemartGiftcertHandler::process($data,$order_status_code);
		
		if(!class_exists('CmCouponVirtuemartCouponHandler')) require $cm_file_2;
		CmCouponVirtuemartCouponHandler::remove_coupon_code_ordercomplete($data);
		CmCouponVirtuemartCouponHandler::order_cancel_check($data);

		return null;
	}
	
}

// No closing tag
