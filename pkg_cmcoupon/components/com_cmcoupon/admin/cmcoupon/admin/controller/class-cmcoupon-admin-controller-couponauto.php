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
class CmCoupon_Admin_Controller_Couponauto extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Couponauto' );
	}

	/**
	 * Display list
	 **/
	public function show_default() {
		$this->render( 'admin.view.couponauto.list', array(
			'table_html' => $this->model->display_list(),
			'filter_state' => AC()->helper->get_userstate_request( $this->model->name . '.filter_state', 'filter_state', '' ),
			'search' => AC()->helper->get_userstate_request( $this->model->name . '.search', 'search', '' ),
		) );
	}

	/**
	 * Display edit screen
	 **/
	public function show_edit() {
		$row = $this->model->get_entry();

		$post = AC()->helper->get_request();
		if ( $post ) {
			$row = (object) array_merge( (array) $row, (array) $post );
		}

		$this->render( 'admin.view.couponauto.edit', array(
			'row' => $row,
		) );
	}

	/**
	 * Save
	 **/
	public function do_save() {
		$errors = $this->model->save( AC()->helper->get_request() );
		if ( empty( $errors ) ) {
			AC()->helper->redirect( 'couponauto' );
			return;
		}

		foreach ( $errors as $err ) {
			AC()->helper->set_message( $err, 'error' );
		}
	}

	/**
	 * Publish
	 **/
	public function do_publish() {
		$this->model->publish( array( (int) AC()->helper->get_request( 'id' ) ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) published' ) );
		AC()->helper->redirect( 'couponauto' );
	}

	/**
	 * Publish bulk
	 **/
	public function do_publishbulk() {
		$this->model->publish( AC()->helper->get_request( 'ids' ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) published' ) );
		AC()->helper->redirect( 'couponauto' );
	}

	/**
	 * Unpublish
	 **/
	public function do_unpublish() {
		$this->model->publish( array( (int) AC()->helper->get_request( 'id' ) ), -1 );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) unpublished' ) );
		AC()->helper->redirect( 'couponauto' );
	}

	/**
	 * Unpublish bulk
	 **/
	public function do_unpublishbulk() {
		$this->model->publish( AC()->helper->get_request( 'ids' ), -1 );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) unpublished' ) );
		AC()->helper->redirect( 'couponauto' );
	}

	/**
	 * Delete
	 **/
	public function do_delete() {
		$this->model->delete( array( (int) AC()->helper->get_request( 'id' ) ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'couponauto' );
	}

	/**
	 * Delete bulk
	 **/
	public function do_deletebulk() {
		$this->model->delete( AC()->helper->get_request( 'ids' ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'couponauto' );
	}

}

