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

/**
 * Class
 */
class CmCoupon_Admin_Controller_Emailcoupon extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Emailcoupon' );
	}

	/**
	 * Display default
	 **/
	public function show_default() {
		return $this->show_edit();
	}

	/**
	 * Show edit screen
	 **/
	public function show_edit() {
		$row = $this->model->get_entry();

		$post = AC()->helper->get_request();
		if ( $post ) {
			$row = (object) array_merge( (array) $row, (array) $post );
			$row->email_body = AC()->helper->get_request( 'email_body', '', 'post', 'rawhtml' );
		}

		$profilelist = AC()->db->get_objectlist( 'SELECT id,title,idlang_email_subject,idlang_email_body FROM #__cmcoupon_profile ORDER BY title,id' );
		foreach ( $profilelist as $k => $item ) {
			$profilelist[ $k ]->email_subject = AC()->lang->get_data( $item->idlang_email_subject );
			$profilelist[ $k ]->email_body = AC()->helper->fixpaths_relative_to_absolute( AC()->lang->get_data( $item->idlang_email_body ) );
		}
		$this->render( 'admin.view.emailcoupon.edit', array(
			'row' => $row,
			'profilelist' => $profilelist,
		) );
	}

	/**
	 * Send coupon via email
	 **/
	public function do_save() {
		$data = AC()->helper->get_request();
		$data['email_body'] = AC()->helper->get_request( 'email_body', '', 'post', 'rawhtml' );
		$errors = $this->model->save( $data );
		if ( empty( $errors ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Email(s) sent' ) );
			AC()->helper->redirect( 'emailcoupon' );
			return;
		}

		foreach ( $errors as $err ) {
			AC()->helper->set_message( $err, 'error' );
		}
	}

}

