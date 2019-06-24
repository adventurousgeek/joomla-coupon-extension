<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

function cmcouponinstall_UPGRADE_241() {
	AC()->db->query( AC()->coupon->is_case_sensitive()
		? 'ALTER TABLE `#__cmcoupon` MODIFY `coupon_code` VARCHAR(255) BINARY NOT NULL DEFAULT ""'
		: 'ALTER TABLE `#__cmcoupon` MODIFY `coupon_code` VARCHAR(255) NOT NULL DEFAULT ""'
	);
}
