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
class CmCoupon_Admin_Controller_Profile extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Profile' );
	}

	/**
	 * Show default
	 **/
	public function show_default() {
		$this->render( 'admin.view.profile.list', array(
			'table_html' => $this->model->display_list(),
			'filter_state' => AC()->helper->get_userstate_request( $this->model->name . '.filter_state', 'filter_state', '' ),
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
			foreach ( $row->imgplugin as $k => $r1 ) {
				foreach ( $r1 as $k2 => $r2 ) {
					$row->imgplugin[ $k ][ $k2 ] = (object) $r2;
				}
			}
		}

		$canvas_items = array(
			array(
				'index' => 0,
				'is_plugin' => false,
				'name' => 'img[couponcode]',
				'title' => AC()->lang->__( 'Coupon Code' ),
				'text' => 'COUPONCODE',
				'display' => 'hidden',
				'db_values' => $row->couponcode,
			),
			array(
				'index' => 1,
				'is_plugin' => false,
				'name' => 'img[couponvalue]',
				'title' => AC()->lang->__( 'Value' ),
				'text' => '$25.00',
				'display' => 'hidden',
				'db_values' => $row->couponvalue,
			),
			array(
				'index' => 2,
				'is_plugin' => false,
				'name' => 'img[expiration]',
				'title' => AC()->lang->__( 'Expiration' ),
				'text' => '31 December 2020',
				'display' => 'date',
				'db_values' => $row->expiration,
			),
			array(
				'index' => 3,
				'is_plugin' => false,
				'name' => 'img[freetext1]',
				'title' => AC()->lang->__( 'Free Text' ) . ' 1',
				'text' => '',
				'display' => 'text',
				'db_values' => $row->freetext1,
			),
			array(
				'index' => 4,
				'is_plugin' => false,
				'name' => 'img[freetext2]',
				'title' => AC()->lang->__( 'Free Text' ) . ' 2',
				'text' => '',
				'display' => 'text',
				'db_values' => $row->freetext2,
			),
			array(
				'index' => 5,
				'is_plugin' => false,
				'name' => 'img[freetext3]',
				'title' => AC()->lang->__( 'Free Text' ) . ' 3',
				'text' => '',
				'display' => 'text',
				'db_values' => $row->freetext3,
			),
		);
		$index = 5;
		foreach ( $row->imgplugin as $k => $r ) {
			foreach ( $r as $k2 => $r2 ) {
				$index++;
				$item = array(
					'index' => $index,
					'is_plugin' => true,
					'name' => 'imgplugin[' . $k . '][' . $k2 . ']',
					'title' => isset( $r2->title ) ? $r2->title : '',
					'text' => isset( $r2->placeholder ) ? $r2->placeholder : '', //isset( $r2->js_display ) && 'textEnable' === $r2->js_display && isset( $r2->js_options ) && is_string( $r2->js_options ) ? $r2->js_options : '',
					'ignore_font' => ! empty( $r2->is_ignore_font ) ? true : false,
					'ignore_fontsize' => ! empty( $r2->is_ignore_font_size ) ? true : false,
					'ignore_fontcolor' => ! empty( $r2->is_ignore_font_color ) ? true : false,
					'display' => isset( $r2->display_type ) ? $r2->display_type : 'text',
					'display_options' => isset( $r2->js_options ) ? $r2->js_options : '',
					'display_php' => str_replace( '{text_name}', 'imgplugin[' . $k . '][' . $k2 . '][text]', isset( $r2->text_html ) ? $r2->text_html : '' ),
					'db_values' => $r2,
				);
				$canvas_items[] = $item;
			}
		}

		$gd_version = 0;
		if ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) {
			$gd_version = 1;
			$info = gd_info();

			preg_match( '/(\d\.?)+/', $info['GD Version'], $match );
			if ( ! empty( $match[0] ) && 2 === (int) substr( $match[0], 0, 1 ) ) {
				$gd_version = 2;
			}
		}

		$tagdesc = array();

		$this->render( 'admin.view.profile.edit', array(
			'row' => $row,
			'fontdd' => $this->model->get_fonts(),
			'imagedd' => $this->model->get_images(),
			'gd_version' => $gd_version,
			'canvasitems' => $canvas_items,
			'default_language' => AC()->lang->get_current(),
			'tagdesc' => $tagdesc,
		) );
	}

	/**
	 * Show image manager
	 **/
	public function show_imagemanager() {
		$this->render( 'admin.view.profile.imagemanager', array(
			'rows' => $this->model->get_images(),
		) );
	}

	/**
	 * Save profile
	 **/
	public function do_save() {
		$errors = $this->model->save( AC()->helper->get_request() );
		if ( empty( $errors ) ) {
			AC()->helper->redirect( 'profile' );
			return;
		}

		foreach ( $errors as $err ) {
			AC()->helper->set_message( $err, 'error' );
		}
	}

	/**
	 * Delete profile
	 **/
	public function do_delete() {
		$this->model->delete( array( (int) AC()->helper->get_request( 'id' ) ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'profile' );
	}

	/**
	 * Delete profile bulk
	 **/
	public function do_deletebulk() {
		$this->model->delete( AC()->helper->get_request( 'ids' ) );
		AC()->helper->set_message( AC()->lang->__( 'Item(s) deleted' ) );
		AC()->helper->redirect( 'profile' );
	}

	/**
	 * Set default profile
	 **/
	public function do_default() {
		$this->model->set_default( array( (int) AC()->helper->get_request( 'id' ) ) );
		AC()->helper->set_message( AC()->lang->__( 'Item set' ) );
		AC()->helper->redirect( 'profile' );
	}

	/**
	 * Copy profile
	 **/
	public function do_copy() {
		$is_copy = $this->model->copy( AC()->helper->get_request( 'id' ) );

		if ( false === $s_copy ) {
			AC()->helper->set_message( AC()->lang->__( 'Could not duplicate coupon' ), 'error' );
		} else {
			AC()->helper->set_message( AC()->lang->__( 'Coupon code' ) . ': ' . $coupon->coupon_code );
		}
		AC()->helper->redirect( 'profile' );
	}

	/**
	 * Upload image
	 **/
	public function do_imageupload() {
		$file = AC()->helper->get_request( 'upload', null, 'file' );
		if ( empty( $file ) || ( 1 === count( $file['name'] ) && empty( $file['size'][0] ) ) ) {
			exit( AC()->lang->__( 'Invalid' ) );
		}

		$dir = CMCOUPON_GIFTCERT_DIR . '/images';

		$uploaded_files = array();
		$accepted_types = array( 'image/png', 'image/jpeg' );

		// Count # of uploaded files in array.
		$total = count( $file['name'] );

		// Loop through each file.
		for ( $i = 0; $i < $total; $i++ ) {
			// Get the temp file path.
			$tmp_file_path = $file['tmp_name'][ $i ];

			// Make sure we have a filepath.
			if ( empty( $tmp_file_path ) ) {
				continue;
			}

			if ( false === in_array( $file['type'][ $i ], $accepted_types, true ) ) {
				continue;
			}

			$name = trim( preg_replace( array( '#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#' ), '', rtrim( pathinfo( $file['name'][ $i ], PATHINFO_FILENAME ),'.' ) ) ); // Makesafe.
			$extension = pathinfo( $file['name'][ $i ], PATHINFO_EXTENSION );
			while ( file_exists( $dir . '/' . $name . '.' . $extension ) ) {
				$name .= mt_rand( 0, 9 );
			}

			// Setup our new file path.
			$new_file_path = $dir . '/' . $name . '.' . $extension;

			// Upload the file into the temp dir.
			if ( move_uploaded_file( $tmp_file_path, $new_file_path ) ) {

				// Handle other code here.
				$uploaded_files[] = basename( $new_file_path );
			}
		}

		if ( empty( $uploaded_files ) ) {
			echo AC()->lang->__( 'No valid image files were found to upload' );
		}

		exit;
	}

	/**
	 * Delete image
	 **/
	public function do_imagedelete() {
		$ids = AC()->helper->get_request( 'ids' );
		if ( empty( $ids ) ) {
			exit;
		}

		$dir = CMCOUPON_GIFTCERT_DIR . '/images';
		foreach ( $ids as $name ) {
			if ( ! file_exists( $dir . '/' . $name ) ) {
				continue;
			}
			unlink( $dir . '/' . $name );
		}
		exit;
	}

	/**
	 * Get list of images
	 **/
	public function do_imagelist() {
		echo AC()->helper->json_encode( $this->model->get_images() );
		exit;
	}


}

