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
class CmCoupon_Admin_Controller_Installation extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Installation' );
	}

	/**
	 * Show default
	 **/
	public function show_default() {
		$this->render( 'admin.view.installation.list', array(
			'table_html' => $this->model->display_list(),
			'filter_state' => AC()->helper->get_userstate_request( $this->model->name . '.filter_state', 'filter_state', '' ),
			'search' => AC()->helper->get_userstate_request( $this->model->name . '.search', 'search', '' ),
		) );
	}

	/**
	 * Install
	 **/
	public function do_install() {
		$ret = $this->model->install( array( AC()->helper->get_request( 'id' ) ) );
		if ( $ret instanceof Exception ) {
			AC()->helper->set_message( $ret->getMessage(), 'error' );
			AC()->helper->redirect( 'installation' );
		}
		AC()->helper->set_message( AC()->lang->__( 'Successfully installed' ) );
		AC()->helper->redirect( 'installation' );
	}

	/**
	 * Install bulk
	 **/
	public function do_installbulk() {
		$this->model->install( AC()->helper->get_request( 'ids' ) );
		AC()->helper->set_message( AC()->lang->__( 'Successfully installed' ) );
		AC()->helper->redirect( 'installation' );
	}

	/**
	 * Uninstall
	 **/
	public function do_uninstall() {
		$ret = $this->model->uninstall( array( AC()->helper->get_request( 'id' ) ) );
		if ( $ret instanceof Exception ) {
			AC()->helper->set_message( $ret->getMessage(), 'error' );
			AC()->helper->redirect( 'installation' );
		}
		AC()->helper->set_message( AC()->lang->__( 'Successfully installed' ) );
		AC()->helper->redirect( 'installation' );
	}

	/**
	 * Uninstall bulk
	 **/
	public function do_uninstallbulk() {
		$this->model->uninstall( AC()->helper->get_request( 'ids' ) );
		AC()->helper->set_message( AC()->lang->__( 'Successfully installed' ) );
		AC()->helper->redirect( 'installation' );
	}

	/**
	 * Unpublish
	 **/
	public function do_unpublish() {
		$this->model->publish( array( (int) AC()->helper->get_request( 'id' ) ), -1 );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) unpublished' ) );
		AC()->helper->redirect( 'giftcert' );
	}

	/**
	 * Unpublish bulk
	 **/
	public function do_unpublishbulk() {
		$this->model->publish( AC()->helper->get_request( 'ids' ), -1 );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) unpublished' ) );
		AC()->helper->redirect( 'giftcert' );
	}

}

