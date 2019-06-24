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
class CmCoupon_Admin_Controller_Coupon extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Coupon' );
	}

	/**
	 * Display list
	 **/
	public function show_default() {
		$this->render( 'admin.view.coupon.list', array(
			'table_html' => $this->model->display_list(),
			'filter_function_type' => AC()->helper->get_userstate_request( 'com_cmcoupon.coupons.filter_function_type', 'filter_function_type', '', 'cmd' ),
			'filter_coupon_value_type' => AC()->helper->get_userstate_request( $this->model->name . '.coupon_value_type', 'filter_coupon_value_type', '' ),
			'filter_state' => AC()->helper->get_userstate_request( $this->model->name . '.filter_state', 'filter_state', '' ),
			'filter_discount_type' => AC()->helper->get_userstate_request( $this->model->name . '.discount_type', 'filter_discount_type', '' ),
			'filter_template' => AC()->helper->get_userstate_request( $this->model->name . '.template', 'filter_template', '' ),
			'filter_tag' => AC()->helper->get_userstate_request( $this->model->name . '.tag', 'filter_tag', '' ),
			'search' => AC()->helper->get_userstate_request( $this->model->name . '.search', 'search', '' ),
			'template_list' => AC()->coupon->get_templates(),
			'tags' => AC()->db->get_objectlist( 'SELECT DISTINCT tag AS id, tag as label FROM #__cmcoupon_tag ORDER BY tag' ),
		));
	}

	/**
	 * Display detail
	 **/
	public function show_detail() {
		$this->model->set_id( AC()->helper->get_request( 'id' ) );
		$this->model->get_entry();

		$this->render( 'admin.view.coupon.detail', array(
			'row' => $this->model->_entry,
		));
	}

	/**
	 * Display new/edit screen
	 **/
	public function show_edit() {
		$row = $this->model->get_entry();
		$post = AC()->helper->get_request();
		if ( ! empty( $post ) ) {
			$row = (object) array_merge( (array) $row, (array) $post );
			if ( ! empty( $post['asset'] ) ) {
				$row->asset = $this->model->asset_post_to_db( $post['asset'] );
			}
		}

		$this->render( 'admin.view.coupon.edit', array(
			'row' => $row,
			'allow_negative_value' => AC()->param->get( 'enable_negative_value_coupon', 0 ),
			'countrylist' => AC()->store->get_countrys(),
			'paymentmethodlist' => AC()->store->get_paymentmethods(),
			'shippinglist' => AC()->store->get_shippings(),
			'usergrouplist' => AC()->store->get_groups(),
		));
	}

	/**
	 * Display generate coupon screen
	 **/
	public function show_generate() {
		$this->render( 'admin.view.coupon.generate', array() );
	}

	/**
	 * Save coupon
	 **/
	public function do_save() {
		$errors = $this->model->save( AC()->helper->get_request() );
		if ( empty( $errors ) ) {
			AC()->helper->redirect( 'coupon' );
			return;
		}

		foreach ( $errors as $err ) {
			AC()->helper->set_message( $err, 'error' );
		}
	}

	/**
	 * Save coupon
	 **/
	public function do_apply() {
		$errors = $this->model->save( AC()->helper->get_request() );
		if ( empty( $errors ) ) {
			AC()->helper->redirect( 'coupon/edit?id=' . $this->model->_entry->id );
			return;
		}

		foreach ( $errors as $err ) {
			AC()->helper->set_message( $err, 'error' );
		}
	}

	/**
	 * Publish coupon
	 **/
	public function do_publish() {
		$this->model->publish( array( (int) AC()->helper->get_request( 'id' ) ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) published' ) );
		AC()->helper->redirect( 'coupon' );
	}

	/**
	 * Publish coupon
	 **/
	public function do_publishbulk() {
		$this->model->publish( AC()->helper->get_request( 'ids' ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) published' ) );
		AC()->helper->redirect( 'coupon' );
	}

	/**
	 * Unpublish coupon
	 **/
	public function do_unpublish() {
		$this->model->publish( array( (int) AC()->helper->get_request( 'id' ) ), 'unpublished' );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) unpublished' ) );
		AC()->helper->redirect( 'coupon' );
	}

	/**
	 * Unpublish coupon
	 **/
	public function do_unpublishbulk() {
		$this->model->publish( AC()->helper->get_request( 'ids' ), 'unpublished' );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) unpublished' ) );
		AC()->helper->redirect( 'coupon' );
	}

	/**
	 * Delete coupon
	 **/
	public function do_delete() {
		$this->model->delete( array( (int) AC()->helper->get_request( 'id' ) ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'coupon' );
	}

	/**
	 * Delete coupon
	 **/
	public function do_deletebulk() {
		$this->model->delete( AC()->helper->get_request( 'ids' ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'coupon' );
	}

	/**
	 * Copy coupon
	 **/
	public function do_copy() {
		$coupon = $this->model->copy( AC()->helper->get_request( 'id' ) );

		if ( false === $coupon ) {
			AC()->helper->set_message( AC()->lang->__( 'Could not duplicate coupon' ), 'error' );
		} else {
			AC()->helper->set_message( AC()->lang->__( 'Coupon code' ) . ': ' . $coupon->coupon_code );
		}

		AC()->helper->redirect( 'coupon' );
	}

	/**
	 * Generate coupons
	 **/
	public function do_savegenerate() {
		$errors = $this->model->generate_multiple( AC()->helper->get_request() );
		if ( empty( $errors ) ) {
			AC()->helper->redirect( 'coupon' );
			return;
		}

		foreach ( $errors as $err ) {
			AC()->helper->set_message( $err, 'error' );
		}
	}

}

