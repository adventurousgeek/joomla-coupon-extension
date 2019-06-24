<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

function cmcouponinstall_UPGRADE_225() {
	$test = AC()->db->get_object( 'SHOW COLUMNS FROM #__cmcoupon_history LIKE "session_id"' );
	if ( empty( $test ) ) {
		AC()->db->query( 'ALTER TABLE #__cmcoupon_history ADD COLUMN `session_id` VARCHAR(200) AFTER order_id' );
	}
}
