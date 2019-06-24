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
 **/
class CmCoupon_Admin_Controller_License extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Admin_Class_License' );
	}

	/**
	 * Show default
	 **/
	public function show_default() {
		$this->render( 'admin.view.license.default', array(
			'row' => $this->model->get_entry(),
		) );
	}

	/**
	 * Delete
	 **/
	public function do_delete() {
		$this->model->delete();
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'license' );
	}

	/**
	 * Activate
	 **/
	public function do_activate() {
		$license = AC()->helper->get_request( 'license' );

		if ( $this->model->activate( $license ) ) {
			$installer = AC()->helper->new_class( 'CmCoupon_Helper_Update' );
			$installer->on_license_activate();

			AC()->helper->set_message( AC()->lang->__( 'License Activated' ) );
			AC()->helper->redirect( 'dashboard' );
		} else {
			AC()->helper->set_message( AC()->lang->__( 'Error Activating License' ), 'error' );
		}
	}

}

