<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

function cmcouponinstall_UPGRADE_228() {

	$rows = AC()->db->get_objectlist( 'SELECT * FROM #__cmcoupon_giftcert_order' );

	$insert_sql = array();
	foreach ( $rows as $k => $row ) {
		$codes = array();
		@parse_str( $row->codes, $codes );
		if ( empty( $codes[0]['i'] ) ) {
			continue;
		}

		foreach ( $codes as $code ) {
			$insert_sql[] = '(' . (int) $row->id . ',' . (int) $code['i'] . ',' . (int) $code['p'] . ',0,"' . AC()->db->escape( $code['c'] ) . '")';
		}
	}
	if ( ! empty( $insert_sql ) ) {
		$insert_sql_array = array_chunk( $insert_sql, 100 );
		foreach ( $insert_sql_array as $insert_sql ) {
			AC()->db->query( 'INSERT INTO #__cmcoupon_giftcert_order_code (giftcert_order_id,order_item_id,product_id,coupon_id,code) VALUES ' . implode( ',', $insert_sql ) );
		}

		AC()->db->query( 'UPDATE #__cmcoupon_giftcert_order_code go,#__cmcoupon c SET go.coupon_id=c.id WHERE go.code=c.coupon_code' );
	}
}
