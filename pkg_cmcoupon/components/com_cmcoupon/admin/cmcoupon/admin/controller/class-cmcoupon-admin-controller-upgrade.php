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
class CmCoupon_Admin_Controller_Upgrade extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Upgrade' );
	}

	/**
	 * Show default
	 **/
	public function show_default() {
		$dashboard_model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Dashboard' );
		$info = $dashboard_model->get_version();
		$this->render( 'admin.view.upgrade.default', array(
			'has_update' => 1 === $info['enabled'] && -1 === $info['current'] ? true : false,
			'row' => $info,
		) );
	}

	/**
	 * Get release info
	 **/
	public function do_info() {
		AC()->helper->reset_cache( 'cmcoupon_dashboard_versionupdate' );
		$dashboard_model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Dashboard' );
		AC()->helper->set_message( AC()->lang->__( 'Information updated' ) );
		AC()->helper->redirect( 'upgrade' );
	}

	/**
	 * Start upgrade
	 **/
	public function do_start() {

		$license = AC()->helper->new_class( 'CmCoupon_Admin_Class_License' );
		$info = $license->get_license_info();

		/*
			Example info object:
			$info = (object) array(
				'version' => '3.5.3',
				'release_date' => '2017-07-20',
				'info_url' => '',
				'download_link' => '',
			);
		*/

		if ( empty( $info->version ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Error retrieving update information' ), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		if ( version_compare( $info->version, CMCOUPON_VERSION, '<=' ) ) {
			AC()->helper->set_message( AC()->lang->__( 'No new update available' ), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		if ( empty( $info->download_link ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Please update your subscription to get latest download' ), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		AC()->helper->set_session( 'upgrade', 'link', $info->download_link );
		Header( 'Location: ' . AC()->ajax_url() . '&type=admin&view=upgrade&task=download' );
		exit;
	}

	/**
	 * Upgradeing, start download
	 **/
	public function do_download() {
		$download_link = AC()->helper->get_session( 'upgrade', 'link', null );
		AC()->helper->reset_session( 'upgrade', 'link' );

		if ( empty( $download_link ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Error' ), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		$file = $this->model->download( $download_link );

		if ( ( $file instanceof Exception ) ) {
			AC()->helper->set_message( $file->getMessage(), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		AC()->helper->set_session( 'upgrade', 'file', $file );
		Header( 'Location: ' . AC()->ajax_url() . '&type=admin&view=upgrade&task=extract' );
		exit;
	}

	/**
	 * Upgradeing extract
	 **/
	public function do_extract() {
		$download_file = AC()->helper->get_session( 'upgrade', 'file', null );
		AC()->helper->reset_session( 'upgrade', 'file' );

		if ( empty( $download_file ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Error downloading file 1' ), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		if ( ! file_exists( $download_file ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Error downloading file 2' ), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		$directory = $this->model->extract( $download_file );

		if ( ( $directory instanceof Exception ) ) {
			AC()->helper->set_message( $directory->getMessage(), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		AC()->helper->set_session( 'upgrade', 'directory', $directory );
		Header( 'Location: ' . AC()->ajax_url() . '&type=admin&view=upgrade&task=copy' );
		exit;
	}

	/**
	 * Upgrading, copy to temp directory
	 **/
	public function do_copy() {
		$working_dir = AC()->helper->get_session( 'upgrade', 'directory', null );
		AC()->helper->reset_session( 'upgrade', 'directory' );

		if ( empty( $working_dir ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Error creating temp directory' ), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		if ( ! file_exists( $working_dir ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Error creating temp directory' ), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		$ret = $this->model->copy( $working_dir );

		if ( ( $ret instanceof Exception ) ) {
			AC()->helper->set_message( $ret->getMessage(), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		AC()->helper->set_session( 'upgrade', 'copy_success', true );
		Header( 'Location: ' . AC()->ajax_url() . '&type=admin&view=upgrade&task=dbupdate' );
		exit;
	}

	/**
	 * Upgrading, run database updates
	 **/
	public function do_dbupdate() {
		$is_copy = AC()->helper->get_session( 'upgrade', 'copy_success', false );
		AC()->helper->reset_session( 'upgrade', 'copy_success' );

		if ( true !== $is_copy ) {
			AC()->helper->set_message( AC()->lang->__( 'Error copying files' ), 'error' );
			AC()->helper->redirect( 'upgrade' );
		}

		$this->model->dbupdate();
		AC()->helper->set_message( AC()->lang->__( 'Update completed' ) );

		// Now reload the full page, not just ajax page.
		echo '
			<script>
			jQuery( document ).ready(function() {
				window.parent.location.replace(window.location.href.substr(0, window.location.href.indexOf("#")));
			});
			</script>
		';
		exit;
	}


}

