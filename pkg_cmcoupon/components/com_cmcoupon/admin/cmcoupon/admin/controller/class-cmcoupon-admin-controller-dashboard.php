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
class CmCoupon_Admin_Controller_Dashboard extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Dashboard' );
	}

	/**
	 * Display list
	 **/
	public function show_default() {
		$is_installation = false;
		$installer = AC()->helper->new_class( 'Cmcoupon_Helper_Estore_' . CMCOUPON_ESTORE . '_Installation' );
		if ( ! empty( $installer ) ) {
			$is_installation = $installer->is_installation();
		}

		$this->render( 'admin.view.dashboard.default', array(
			'versionchecker' => 1 === (int) AC()->param->get( 'disable_version_checker', 0 ) ? false : true,
			'check' => $this->model->get_version(),
			'license' => $this->model->get_license(),
			'genstats' => $this->model->get_stats(),
			'is_installation' => $is_installation,
		) );
	}

}
