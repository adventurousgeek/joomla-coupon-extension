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


$msgs = AC()->helper->get_messages_and_flush();
if ( empty( $msgs ) ) {
	return;
}

if ( ! empty( $msgs['error'] ) ) {
	echo '<div class="error">';
	foreach ( $msgs['error'] as $err ) {
		echo '<div>' . $err . '</div>';
	}
	echo '</div>';
}
if ( ! empty( $msgs['notice'] ) ) {
	echo '<div class="notice">';
	foreach ( $msgs['notice'] as $notice ) {
		echo '<div>' . $notice . '</div>';
	}
	echo '</div>';
}
