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

class CmCoupon_Public_Controller_Storecredit extends CmCoupon_Library_Controller {

	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Public_Class_Storecredit' );
	}

	public function show_default() {

		$this->render( 'public.view.storecredit.list', array(
			'table_html' => $this->model->display_list(),
			'balance' => AC()->storecurrency->format( AC()->helper->customer_balance( CMCOUPON_ESTORE, true ) ),
			'is_enabled' => AC()->param->get( 'enable_frontend_balance', 0 ) == 1 ? true : false,
		) );
	}

	public function do_store() {
		$errors = $this->model->save( AC()->helper->get_request( 'voucher' ) );
		if ( empty( $errors ) ) {
			return;
		}

		foreach ( $errors as $err ) {
			AC()->helper->set_message( $err, 'error' );
		}
	}

	public function do_applybalance() {
		AC()->storediscount->cart_coupon_validate_balance();

		$return = AC()->helper->get_request( 'return' );
		if ( ! empty( $return ) ) {
			$return = base64_decode( $return );
		}
		if ( empty( $return ) ) {
			$return = AC()->store->get_home_link();
		}

		AC()->helper->cms_redirect( $return );

	}

}

