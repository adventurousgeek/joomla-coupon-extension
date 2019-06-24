<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Check to ensure this file is within the rest of the framework
defined('_JEXEC') or die('Restricted access');

class cmAutoGenerate  {

	public static function getCouponTemplates($estore='virtuemart') {
		if ( ! class_exists( 'cmcoupon' ) ) require JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helper/cmcoupon.php';
		CmCoupon::instance();
		AC()->init();

		return AC()->coupon->get_templates();
	}

	public static function generateCoupon($coupon_id,$coupon_code=null,$expiration=null,$override_user=null,$estore=null) {
		if ( ! class_exists( 'cmcoupon' ) ) require JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helper/cmcoupon.php';
		CmCoupon::instance();
		AC()->init();

		return AC()->coupon->generate( $coupon_id, $coupon_code, $expiration, $override_user );
	}

}
