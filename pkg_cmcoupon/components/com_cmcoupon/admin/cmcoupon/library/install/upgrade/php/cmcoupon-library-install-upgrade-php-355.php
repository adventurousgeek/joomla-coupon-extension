<?php
/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

if ( ! defined( '_CM_' ) ) {
	exit;
}

function Cmcoupon_Library_Install_Upgrade_Php_355() {
	AC()->db->query( 'UPDATE #__cmcoupon_history SET currency_code="' . AC()->db->escape( AC()->storecurrency->get_default_currencycode() ) . '"' );
}
