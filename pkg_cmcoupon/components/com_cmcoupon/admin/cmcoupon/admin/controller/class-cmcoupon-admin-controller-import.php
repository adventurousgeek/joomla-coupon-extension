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
class CmCoupon_Admin_Controller_Import extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Import' );
	}

	/**
	 * Show default
	 **/
	public function show_default() {
		$this->render( 'admin.view.import.default', array() );
	}

	/**
	 * Import
	 **/
	public function do_save() {

		$lines = array();
		$file = AC()->helper->get_request( 'file', array(), 'file' );
		$store_none_errors = AC()->helper->get_request( 'store_none_errors' );

		if ( strtolower( substr( $file['name'], -4 ) ) === '.csv' ) {
			ini_set( 'auto_detect_line_endings', true ); // needed for mac users.
			$handle = fopen( $file['tmp_name'], 'r' );
			if ( false !== $handle ) {
				$delimiter = AC()->param->get( 'csvDelimiter', ',' );
				$keys = array();
				while ( false !== ( $row = fgetcsv( $handle, 10000, $delimiter ) ) ) {
					if ( empty( $row ) ) {
						continue;
					}
					if ( empty( $keys ) ) {
						$keys = $row;
						continue;
					}
					$lines[] = array_combine( $keys, $row );
				}
				fclose( $handle );
			}
		}

		if ( empty( $lines ) ) {
			AC()->helper->set_message( 'Empty import file', 'error' );
			return;
		}

		$data = array(
			'store_none_errors' => $store_none_errors,
			'lines' => $lines,
		);
		$errors = $this->model->save( $data );
		if ( empty( $errors ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Item(s) imported' ) );
			AC()->helper->redirect( 'coupon' );
			return;
		}

		foreach ( $errors as $id => $errarray ) {
			$err_text = '<br /><div>ID: ' . $id . '<hr /></div>';
			foreach ( $errarray as $err ) {
				$err_text .= '<div style="padding-left:20px;">-- ' . $err . '</div>';
			}
			AC()->helper->set_message( $err_text, 'error' );
		}
	}

	/**
	 * Export
	 **/
	public function do_export() {
		$data = $this->model->export( AC()->helper->get_request( 'coupon_ids', null ) );

		$filename = AC()->helper->get_request( 'filename', 'file.csv' );

		// Required for IE, otherwise Content-disposition is ignored.
		if ( ini_get( 'zlib.output_compression' ) ) {
			ini_set( 'zlib.output_compression', 'Off' );
		}

		header( 'Pragma: public' ); // Required.
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', false ); // Required for certain browsers.
		header( 'Content-Type: application/vnd.ms-excel' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . strlen( $data ) );
		echo $data;
		exit();
	}
}

