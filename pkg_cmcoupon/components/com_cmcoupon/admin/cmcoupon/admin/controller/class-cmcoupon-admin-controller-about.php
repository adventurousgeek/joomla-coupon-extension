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
class CmCoupon_Admin_Controller_About extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
	}

	/**
	 * Display page
	 **/
	public function show_default() {
		$this->render( 'admin.view.about.default' );
	}

	/**
	 * Display error no estore page
	 **/
	public function show_err_estore() {
		$this->render( 'admin.view.about.err_estore' );
	}
}

