<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

function tmplogger($msg){
	$logfile = '/home/webadmin/dev2.ekerner.com/public_html/j3dev2/administrator/logs/test.log';
	file_put_contents($logfile, "$msg\n", FILE_APPEND);
}
tmplogger('test');

$_paramsfile = JPATH_ADMINISTRATOR.'/components/com_cmcoupon/cmcoupon/library/class-cmcoupon-library-param.php';
if (file_exists($_paramsfile))
	require_once $_paramsfile;
else
	die('Cant find params file '.$_paramsfile.', cwd = '.getcwd());

error_reporting(E_ALL);
ini_set('display_errors', 1);

class CmCoupon {

	public $version = '3.5.6.4';

	protected static $_instance = null;

	public $db = null;
	public $helper = null;
	public $param = null;
	public $store = null;
	public $storecurrency = null;
	public $storediscount = null;
	public $storegift = null;
	public $coupon = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->define( '_CM_', 1 );

		if ( ! class_exists( 'CmCoupon_Helper_Helper' ) ) {
			require dirname( __FILE__ ) . '/../helper/class-cmcoupon-helper-helper.php';
		}
		$this->helper = new CmCoupon_Helper_Helper();

	}
	public function init( $is_install = false, $is_force = false ) {
		if ( false === $is_force && defined( 'CMCOUPON_DIR' ) ) {
			return;
		}

		// Define constants.
		$this->define( 'CMCOUPON_PLUGIN_FILE', __FILE__ );
		$this->define( 'CMCOUPON_DIR', dirname( dirname( __FILE__ ) ) );
		$this->define( 'CMCOUPON_VERSION', $this->version );

		$this->define( 'CMCOUPON_ASEET_URL', $this->plugin_url() . '/cmcoupon/media/assets' );
		$this->define( 'CMCOUPON_GIFTCERT_DIR', CMCOUPON_DIR . '/cmcoupon/media/giftcert' );
		$this->define( 'CMCOUPON_GIFTCERT_URL', $this->plugin_url() . '/cmcoupon/media/giftcert' );
		//$this->define( 'CMCOUPON_CUSTOMER_DIR', CMCOUPON_DIR . '/cmcoupon/media/customer' );
		$this->define( 'CMCOUPON_CUSTOMER_DIR', JPATH_SITE . '/media/com_cmcoupon/customers' );
		$this->define( 'CMCOUPON_TEMP_DIR', JPATH_ROOT . '/tmp' );

		// Add includes.
		$this->helper->add_class( 'CmCoupon_Library_Class' );
		$this->helper->add_class( 'CmCoupon_Library_Controller' );

		$this->helper->add_class( 'CmCoupon_Admin_Admin' );

		// Define variables.

		tmplogger("this->helper->add_class( 'Cmcoupon_Library_Param' )\n");
		$this->db = $this->helper->new_class( 'CmCoupon_Helper_Database' );
		if ( ! $is_install ) {
			$this->param = $this->helper->new_class( 'Cmcoupon_Library_Param' );
		}
		else {
			//require '../cmcoupon/library/class-cmcoupon-library-param.php';
			$this->helper->add_class( 'Cmcoupon_Library_Param' );
			$this->param = new Cmcoupon_Library_Paraminstall();
		}
		tmplogger("2 this->helper->add_class( 'Cmcoupon_Library_Param' )\n");

		$this->lang = $this->helper->new_class( 'CmCoupon_Helper_Language' );
		$this->coupon = $this->helper->new_class( 'CmCoupon_Library_Coupon' );
		$this->profile = $this->helper->new_class( 'Cmcoupon_Library_Profile' );

		$estore = $this->get_current_estore();
		$this->define( 'CMCOUPON_ESTORE', $estore );
		if ( empty( $estore ) ) {
			return;
		}
		$this->store = $this->helper->new_class( 'Cmcoupon_Helper_Estore_' . $estore . '_Helper' );
		$this->storecurrency = $this->helper->new_class( 'CmCoupon_Helper_Estore_' . $estore . '_Currency' );
		$this->storediscount = $this->helper->new_class( 'CmCoupon_Helper_Estore_' . $estore . '_Discount' );
		$this->storegift = $this->helper->new_class( 'CmCoupon_Helper_Estore_' . $estore . '_Giftcert' );

	}
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	public function plugin_url() {
		return JURI::root( true ) . '/administrator/components/com_cmcoupon';
	}
	
	public function admin_url() {
		return JURI::root() . 'administrator/index.php?option=com_cmcoupon';
	}
	
	public function ajax_url() {
		return JURI::root( true ) . '/administrator/index.php?option=com_cmcoupon&tmpl=component' . ( version_compare( JVERSION, '3.0.0', '>=' ) ? '&no_html=1' : '' ) . '&ajax=yes';
	}
	
	public function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return JFactory::getApplication()->isAdmin() ? true : false;
			case 'frontend':
				return JFactory::getApplication()->isAdmin() ? false : true;
		}
	}

	public function get_current_estore() {
		$estore = JFactory::getApplication()->getUserStateFromRequest( 'com_cmcoupon.global.estore', 	'estore', 	'', 'cmd' );
		if ( ! empty( $estore ) ) {
			return $estore;
		}

		$estore = $this->param->get('estore') ;
		if ( ! empty( $estore ) ) {
			return $estore;
		}

		$estores = $this->helper->get_installed_estores();
		if ( ! empty( $estores ) ) {
			$estore = current( $estores );
			JFactory::getApplication()->setUserState( 'com_cmcoupon.global.estore', $estore );
			$this->param->set( 'estore', $estore );
		}

		return $estore;
	}

}

