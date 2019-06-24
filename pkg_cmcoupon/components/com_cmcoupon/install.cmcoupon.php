<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined('_JEXEC') or die('Restricted access');

function com_install(){ 
	$installer = new cmcouponInstall();
	$installer->install_or_update();
}

function com_uninstall(){

	echo '<div><b>Database Tables Uninstallation: <font color="green">Successful</font></b></div>';

	$installer = new cmcouponInstall();
	$installer->uninstall();
}

class com_cmcouponInstallerScript {
	public function install($parent) {
		$installer = new cmcouponInstall();
		$installer->install();
	}
	public function update($parent) {
		$installer = new cmcouponInstall();
		$installer->install_or_update();
	}
	public function uninstall($parent) {
		$installer = new cmcouponInstall();
		$installer->uninstall();
	}
	public function preflight($type, $parent) {}
	public function postflight($type, $parent) {}
}

class cmcouponInstall {

	public function __construct() {
		if ( ! class_exists( 'cmcoupon' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php';
		}
		CmCoupon::instance();
		AC()->init( true );

		$this->model = AC()->helper->new_class( 'CmCoupon_Helper_Update' );
	}

	public function install() {
		$this->model->install();
	}

	public function update() {
		$this->model->update();
	}

	public function uninstall() {
		$this->model->uninstall();
	}

	public function install_or_update() {
		$version = $this->model->get_version();
		if ( empty( $version ) ) {
			$this->install();
		}
		else {
			$this->update();
		}
	}

}

