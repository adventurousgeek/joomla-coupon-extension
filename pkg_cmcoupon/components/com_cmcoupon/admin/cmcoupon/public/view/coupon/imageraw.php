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


if ( empty( $data->image_raw ) ) {
	return;
}

Header( 'Content-Type: image/' . $data->extension );
echo $data->image_raw;

