<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

function cmcouponinstall_UPGRADE_200() {

	$cmcoupon_version = 0;

	$p__ = JFactory::getConfig()->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'} ( 'dbprefix' );

	$tmp = AC()->db->get_value( 'SHOW TABLES LIKE "' . $p__ . 'cmcoupon_vm_history"' );
	if(!empty($tmp)) {
		$cmcoupon_version = 2;
	}
	else {
		$tmp = AC()->db->get_value( 'SHOW TABLES LIKE "' . $p__ . 'cmcoupon_user_uses"' );
		if(!empty($tmp)) {
			$cmcoupon_version = 1;
		}
	}

	if($cmcoupon_version==2) {
		$columns = AC()->db->get_objectlist( 'DESC #__cmcoupon_vm', 'Field' );
		$is_function_type2 = isset($columns['function_type2']) ? true : false;

		if(!$is_function_type2) {
			AC()->db->query( 'ALTER TABLE `#__cmcoupon_vm` ADD COLUMN `function_type2` enum("product","category","manufacturer","vendor","shipping","parent") AFTER `function_type`' );
			AC()->db->query( 'UPDATE `#__cmcoupon_vm` SET `function_type2`="product"' );
		}
		else {
			AC()->db->query( 'ALTER TABLE `#__cmcoupon_vm` MODIFY `function_type2` enum("product","category","manufacturer","vendor","shipping","parent") AFTER `function_type`' );
		}
	}
	elseif($cmcoupon_version==1) {
		AC()->db->query( 'RENAME TABLE #__cmcoupon TO #__cmcoupon_vm' );
		AC()->db->query( 'RENAME TABLE #__cmcoupon_product TO #__cmcoupon_vm_product' );
		AC()->db->query( 'RENAME TABLE #__cmcoupon_user TO #__cmcoupon_vm_user;' );
		AC()->db->query( 'RENAME TABLE #__cmcoupon_user_uses TO #__cmcoupon_vm_history;' );

		AC()->db->query( 'ALTER TABLE `#__cmcoupon_vm` ADD COLUMN `function_type2` enum("product","category") AFTER `function_type`' );
		AC()->db->query( 'ALTER TABLE `#__cmcoupon_vm` ADD COLUMN `startdate` DATE AFTER `function_type2`;' );
		AC()->db->query( 'UPDATE `#__cmcoupon_vm` SET function_type2="product";' );
	}
}

