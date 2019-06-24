<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

function cmcouponinstall_UPGRADE_220() {
		
	$p__ = JFactory::getConfig()->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'} ( 'dbprefix' );

	$tmp = AC()->db->get_value( 'SHOW TABLES LIKE "' . $p__ . 'virtuemart_orders"' );
	if ( ! empty( $tmp ) ) {
		AC()->db->query("UPDATE #__cmcoupon_giftcert_order g,#__virtuemart_orders o SET g.user_id=o.virtuemart_user_id WHERE g.estore='virtuemart' AND g.order_id=o.virtuemart_order_id" );
	}
	$tmp = AC()->db->get_value( 'SHOW TABLES LIKE "' . $p__ . 'hikashop_order"' );
	if ( ! empty( $tmp ) ) {
		AC()->db->query( "UPDATE #__cmcoupon_giftcert_order g,#__hikashop_order o,#__hikashop_user uu SET g.user_id=uu.user_cms_id WHERE g.estore='hikashop' AND g.order_id=o.order_id AND uu.user_id=o.order_user_id" );
	}
	$tmp = AC()->db->get_value( 'SHOW TABLES LIKE "' . $p__ . 'redshop_orders"' );
	if ( ! empty( $tmp ) ) {
		AC()->db->query( "UPDATE #__cmcoupon_giftcert_order g,#__redshop_orders o SET g.user_id=o.user_id WHERE g.estore='redshop' AND g.order_id=o.order_id" );
	}
}