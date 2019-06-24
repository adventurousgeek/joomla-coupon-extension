<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

function cmcouponinstall_UPGRADE_215() {

	$filtered_coupons = array();
	$coupons = AC()->db->get_objectlist( 'SELECT *,"" as usercount,"" as usergroupcount,"" as asset1count,"" as asset2count FROM #__cmcoupon WHERE function_type2 IN ("product","category","manufacturer","vendor","shipping","parent")', 'id');
	foreach( $coupons as $coupon ) {
		if ( ! empty( $coupon->params ) ) {
			if ( $coupon->function_type2 == 'parent' ) {
				continue;
			}
			elseif ( $coupon->function_type2 != 'shipping' ) {
				if ( strpos( $coupon->params, 'asset1_type' ) !== false ) {
					continue;
				}
			}
		}
		$filtered_coupons[$coupon->id] = $coupon;
	}

	if ( empty( $filtered_coupons ) ) {
		return;
	}

	$ids = implode( ',', array_keys( $filtered_coupons ) );

	$rows = AC()->db->get_objectlist( 'SELECT coupon_id,count(user_id) as cnt FROM #__cmcoupon_user WHERE coupon_id IN (' . $ids . ') GROUP BY coupon_id' );
	foreach( $rows as $row ) {
		$filtered_coupons[ $row->coupon_id ]->usercount = $row->cnt;
	}

	$rows = AC()->db->get_objectlist( 'SELECT coupon_id,count(shopper_group_id) as cnt FROM #__cmcoupon_usergroup WHERE coupon_id IN (' . $ids . ') GROUP BY coupon_id' );
	foreach( $rows as $row ) {
		$filtered_coupons[ $row->coupon_id ]->usergroupcount = $row->cnt;
	}

	$rows = AC()->db->get_objectlist( 'SELECT coupon_id,asset_type,count(asset_id) as cnt FROM #__cmcoupon_asset1 WHERE coupon_id IN (' . $ids . ') GROUP BY coupon_id,asset_type' );
	foreach( $rows as $row ) {
		$filtered_coupons[ $row->coupon_id ]->asset1count = $row->cnt;
	}

	$rows = AC()->db->get_objectlist( 'SELECT coupon_id,asset_type,count(asset_id) as cnt FROM #__cmcoupon_asset2 WHERE coupon_id IN (' . $ids . ') GROUP BY coupon_id,asset_type' );
	foreach( $rows as $row ) {
		$filtered_coupons[ $row->coupon_id ]->asset2count = $row->cnt;
	}

	foreach ( $filtered_coupons as $coupon ) {
 
		if ( in_array( $coupon->function_type2, array( 'product', 'category', 'manufacturer', 'vendor' ) ) && empty( $coupon->asset1count ) ) {
			continue;
		}

		//correct invalid data
		$params = empty( $coupon->params ) ? array() : (array) json_decode( $coupon->params );

		if ( empty( $params['user_mode'] ) && ( ! empty( $coupon_row->usercount ) || ! empty( $coupon_row->usergroupcount ) ) ) {
			$params['user_mode'] = 'include';
		}
		if ( in_array( $coupon->function_type2, array( 'product', 'category', 'manufacturer', 'vendor' ) ) ) {
			if ( ! empty( $coupon->asset1count ) ) {
				$params['asset1_type'] = $coupon->function_type2;
				$params['asset1_mode'] = $coupon->function_type2_mode;
			}			
		}
		elseif ( $coupon->function_type2 == 'parent' ) {
			$params['asset1_type'] = 'coupon';
		}		
		elseif( $coupon->function_type2 == 'shipping' ) {
			if ( ! empty( $coupon->asset1count ) ) {
				$params['asset1_type'] = 'shipping';
				$params['asset1_mode'] = $coupon->function_type2_mode;
			}
			if ( ! empty( $coupon->asset2count ) ) {
				$params['asset2_type'] = 'product';
				$params['asset2_mode'] = ! empty( $params['product_inc_exc'] ) ? $params['product_inc_exc'] : 'include';
			}
		}
		if ( empty( $params['min_value_type'] ) && ! empty( $coupon->min_value ) ) {
			$params['min_value_type'] = 'overall';
		}

		AC()->db->query( 'UPDATE #__cmcoupon SET params=\'' . json_encode( $params ) . '\' WHERE id=' . $coupon->id );
	}
}