<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

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
