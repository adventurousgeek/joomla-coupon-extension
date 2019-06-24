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
class CmCoupon_Admin_Controller_Giftcertcode extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Giftcertcode' );
	}

	/**
	 * Display default
	 **/
	public function show_default() {
		$this->render( 'admin.view.giftcertcode.list', array(
			'table_html' => $this->model->display_list(),
			'productlist' => AC()->db->get_objectlist( AC()->store->sql_giftcert_product( '', '', '_product_name, g.product_id' ) ),
			'statuslist' => AC()->helper->vars( 'status' ),
			'filter_state' => AC()->helper->get_userstate_request( $this->model->name . '.filter_state', 'filter_state', '' ),
			'filter_product' => AC()->helper->get_userstate_request( $this->model->name . '.filter_product', 'filter_product', '' ),
			'search' => AC()->helper->get_userstate_request( $this->model->name . '.search', 'search', '' ),
		) );
	}

	/**
	 * Show edit screen
	 **/
	public function show_edit() {
		$row = $this->model->get_entry();

		$post = AC()->helper->get_request();
		if ( $post ) {
			$row = (object) array_merge( (array) $row, (array) $post );
		}

		$this->render( 'admin.view.giftcertcode.edit', array(
			'row' => $row,
			'productlist' => AC()->db->get_objectlist( AC()->store->sql_giftcert_product( '', '', '_product_name,g.product_id' ) ),
		) );
	}

	/**
	 * Save
	 **/
	public function do_save() {
		$errors = $this->model->save( AC()->helper->get_request() );
		if ( empty( $errors ) ) {
			AC()->helper->redirect( 'giftcertcode' );
			return;
		}

		foreach ( $errors as $err ) {
			AC()->helper->set_message( $err, 'error' );
		}
	}

	/**
	 * Activate
	 **/
	public function do_activate() {
		$this->model->publish( array( (int) AC()->helper->get_request( 'id' ) ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) published' ) );
		AC()->helper->redirect( 'giftcertcode' );
	}

	/**
	 * Deactivate
	 **/
	public function do_activatebulk() {
		$this->model->publish( AC()->helper->get_request( 'ids' ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) published' ) );
		AC()->helper->redirect( 'giftcertcode' );
	}
	/**
	 * Mark used
	 **/
	public function do_markused() {
		$this->model->publish( array( (int) AC()->helper->get_request( 'id' ) ), 'used' );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) unpublished' ) );
		AC()->helper->redirect( 'giftcertcode' );
	}

	/**
	 * Mark used bulk
	 **/
	public function do_markusedbulk() {
		$this->model->publish( AC()->helper->get_request( 'ids' ), 'used' );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) unpublished' ) );
		AC()->helper->redirect( 'giftcertcode' );
	}

	/**
	 * Delete
	 **/
	public function do_delete() {
		$this->model->delete( array( (int) AC()->helper->get_request( 'id' ) ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'giftcertcode' );
	}

	/**
	 * Delete bulk
	 **/
	public function do_deletebulk() {
		$this->model->delete( AC()->helper->get_request( 'ids' ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'giftcertcode' );
	}

}

