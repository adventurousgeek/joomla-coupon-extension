<?php
/**
 * @module CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined('_JEXEC') or die;

$user = JFactory::getUser();
if(empty($user->id)) return;

if ( ! class_exists( 'cmcoupon' ) ) {
	if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php' ) ) {
		return;
	}
	require JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php';
}
if ( ! class_exists( 'cmcoupon' ) ) {
	return;
}
CmCoupon::instance();
AC()->init();

if ( AC()->param->get( 'enable_frontend_balance', 0 ) != 1 ) {
	return;
}

AC()->helper->loadLanguageSite();

$balance = AC()->helper->customer_balance( CMCOUPON_ESTORE );
$balance_str = AC()->storecurrency->convert_from_default_format( $balance );

$router = JSite::getRouter();
$var = $router->getVars();
$current_url = 'index.php?' . JURI::buildQuery( $router->getVars() );
$current_url = str_replace( '&removecoupon=1', '', $current_url ); // hikashop - needed otherwise balance coupon removed if customer has just deleted coupons
$current_url = base64_encode( $current_url );

$is_show_apply_button = true;
if( $balance <= 0 ) {
	$is_show_apply_button = false;
}
else {
	$coupon_session = AC()->storediscount->get_coupon_session();
	if ( ! empty( $coupon_session ) ) {
		if ( isset( $coupon_session->use_customer_balance ) && true === $coupon_session->use_customer_balance ) {
			$is_show_apply_button = false;
		}
	}
}

require JModuleHelper::getLayoutPath( 'mod_cmcoupon_balance', $params->get( 'layout', 'default' ) );

