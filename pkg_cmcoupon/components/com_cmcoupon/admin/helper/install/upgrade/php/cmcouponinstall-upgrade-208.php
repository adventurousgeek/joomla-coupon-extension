<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

function cmcouponinstall_UPGRADE_208() {

	AC()->db->query( 'UPDATE #__cmcoupon_config SET name="virtuemart_giftcert_field_recipient_name" WHERE name="giftcert_field_recipient_name"' );

	AC()->db->query( 'UPDATE #__cmcoupon_config SET name="virtuemart_giftcert_field_recipient_email" WHERE name="giftcert_field_recipient_email"' );

	AC()->db->query( 'UPDATE #__cmcoupon_config SET name="virtuemart_giftcert_field_recipient_message" WHERE name="giftcert_field_recipient_message"' );
}