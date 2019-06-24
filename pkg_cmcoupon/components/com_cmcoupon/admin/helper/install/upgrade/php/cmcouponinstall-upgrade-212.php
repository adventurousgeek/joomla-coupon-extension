<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

function cmcouponinstall_UPGRADE_212() {
	$elem_id = 1;
	$tmp = AC()->db->get_objectlist( 'SELECT id,email_subject,email_body FROM #__cmcoupon_profile' );
	if ( ! empty( $tmp ) ) {
		foreach ( $tmp as $row ) {
			if ( ! empty( $row->email_subject ) ) {
				AC()->db->query( 'INSERT INTO #__cmcoupon_lang_text (elem_id,lang,text) VALUES (' . $elem_id . ',"en-GB","' . AC()->db->escape( $row->email_subject ) . '")' );
				AC()->db->query( 'UPDATE #__cmcoupon_profile SET email_subject_lang_id=' . $elem_id . ' WHERE id=' . $row->id );
				$elem_id++;
			}
			if ( ! empty( $row->email_body ) ) {
				AC()->db->query( 'INSERT INTO #__cmcoupon_lang_text (elem_id,lang,text) VALUES (' . $elem_id . ',"en-GB","' . AC()->db->escape( $row->email_body ) . '")' );
				AC()->db->query( 'UPDATE #__cmcoupon_profile SET email_body_lang_id=' . $elem_id . ' WHERE id=' . $row->id );
				$elem_id++;
			}
		}
	}
}