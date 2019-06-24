<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

function cmcouponinstall_UPGRADE_222() {
	$file = JPATH_ADMINISTRATOR . '/components/com_cmcoupon/toolbar.cmcoupon.php';
	if ( file_exists( $file ) ) {
		unlink( $file );
	}
}