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

class CmCoupon_Public_Controller_Coupon extends CmCoupon_Library_Controller {

	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Public_Class_Coupon' );
	}

	public function show_default() {
		$this->render( 'public.view.coupon.list', array(
			'table_html' => $this->model->display_list(),
		));
	}

	public function show_image() {
		$this->render( 'public.view.coupon.image', array(
			'url' => $this->model->get_image_url( AC()->helper->get_request( 'coupon_code', '' ) ),
		));
	}

	public function show_imageraw() {

		$file = AC()->helper->get_request( 'file' );
		$b64 = $this->model->get_image_raw( $file );
		$image_raw = '';
		$extension = '';
		if ( ! empty( $b64 ) ) {
			$fi = pathinfo( $file );
			$extension = strtolower( $fi['extension'] );
			$image_raw = base64_decode( $b64 );
		}

		$this->render( 'public.view.coupon.imageraw', array(
			'image_raw' => $image_raw,
			'extension' => $extension,
		) );

	}

}

