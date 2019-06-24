<?php
/**
 * CmCoupon
 *
 * @package Joomla CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined('_JEXEC') or die('Restricted access');
if ( ! defined( '_CM_' ) ) {
	exit;
}

class Cmcoupon_Helper_Estore_Redshop_Installation {

	var $estore = 'redshop';
	static $version;
	
	public function __construct() {
		if ( empty( self::$version ) ) {
			// Load redSHOP Library
			JLoader::import('redshop.library');

			if ( ! class_exists( 'RedshopModelConfiguration' ) ) {
				require JPATH_ADMINISTRATOR . '/components/com_redshop/models/configuration.php';
			}
			$configClass = new RedshopModelConfiguration();
			self::$version = $configClass->getCurrentVersion();
		}
	}

	public function is_installation() {
		return true;
	}

	public function get_definition_file() {
		if ( version_compare( self::$version, '2.0.0', '>=' ) ) {
			return	array(
				'cart_core' => array(
					'func' => 'inject_front_helper_cart',
					'index' => 'coupon',
					'file' => 'www/components/com_redshop/helpers/rscarthelper.php',
					'name' => AC()->lang->__( 'Core (Required)' ),
					'desc' => '',
				),
				'checkout_core' => array(
					'func' => 'inject_front_model_checkout',
					'index' => 'orderplugin',
					'file' => 'www/components/com_redshop/models/checkout.php',
					'name' => AC()->lang->__( 'Core (Required)' ),
					'desc' => '',
				),
				'giftcert_hook' => array(
					'func' => 'inject_front_model_checkout',
					'index' => 'giftcertplugin',
					'file' => 'www/components/com_redshop/models/checkout.php',
					'name' => AC()->lang->__( 'Sellable gift certificates' ),
					'desc' => '',
				),
				'autocoupon_hook' => array(
					'func' => 'inject_front_views_cart_view',
					'index' => 'autocouponhook',
					'file' => 'www/components/com_redshop/views/cart/view.html.php',
					'name' => AC()->lang->__( 'Automatic Discounts' ),
					'desc' => '',
				),
			);
		}
		else {
			return	array(
				//'orderhook_update'=>array('func'=>'inject_admin_helper_order','index'=>'orderupdate','name'=>'COM_CMCOUPON_FI_MSG_SELLABLE_GIFTCERT','file'=>'www/administrator/components/com_redshop/helpers/order.php','desc'=>''),
				'cart_core' => array(
					'func' => 'inject_front_helper_cart',
					'index' => 'coupon',
					'file' => 'www/components/com_redshop/helpers/cart.php',
					'name' => AC()->lang->__( 'Core (Required)' ),
					'desc' => '',
				),
				'checkout_core' => array(
					'func' => 'inject_front_model_checkout',
					'index' => 'orderplugin',
					'file' => 'www/components/com_redshop/models/checkout.php',
					'name' => AC()->lang->__( 'Core (Required)' ),
					'desc' => '',
				),
				'giftcert_hook' => array(
					'func' => 'inject_front_model_checkout',
					'index' => 'giftcertplugin',
					'file' => 'www/components/com_redshop/models/checkout.php',
					'name' => AC()->lang->__( 'Sellable gift certificates' ),
					'desc' => '',
				),
				'autocoupon_hook' => array(
					'func' => 'inject_front_views_cart_view',
					'index' => 'autocouponhook',
					'file' => 'www/components/com_redshop/views/cart/view.html.php',
					'name' => AC()->lang->__( 'Automatic Discounts' ),
					'desc' => '',
				),
			);
		}
	}	

	public function get_definition_sql() {
		return array();
	}
	
	public function get_definition_plugin() {
		return array(
			'redshop_coupon' => array(
				'func' => 'plugin_installer',
				'name' => 'Redshop Coupon - CmCoupon',
				'folder' => 'redshop_coupon',
				'dir' => JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/estore/redshop/extensions/plugins/redshop_coupon',
				'desc' => '',
			),
		);
	}

	public function plugin_installer( $type, $key ) {
		$install_class = AC()->helper->new_class( 'CmCoupon_Helper_Installation' );
		return $install_class->plugin_installer( $type, $key, $this->get_definition_plugin() );
	}

	public function inject_front_model_checkout( $type ) {

		switch( $type ) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						//'orderplugin' => '/(\$this\s*\->\s*_redshopMail\s*\->\s*sendOrderMail\s*\(\s*\$row\s*\->\s*order_id\s*,\s*\$sendreddesignmail\s*\)\s*;)/is',
						'orderplugin' => '/(\$this\s*\->\s*_redshopMail\s*\->\s*sendOrderMail\s*\(\s*\$row\s*\->\s*order_id\s*[^)]*\)\s*;)/is', // changed for version 1.3.x
						'giftcertplugin' => '/(function\s+sendGiftCard\s*\(\s*\$order_id\s*\)\s*{)/is',
					),
					'replacements' => array(
						'orderplugin' => '# cmcoupon_code START ===============================================================
		JPluginHelper::importPlugin(\'redshop_coupon\');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger(\'onCouponRemove\', array($row->order_id));
		# cmcoupon_code END =================================================================
		$1',
						'giftcertplugin' => '$1
		
		# cmcoupon_code START ===============================================================
		JPluginHelper::importPlugin(\'redshop_coupon\');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger(\'onOrderGiftcertSend\', array($order_id));
		if(!empty($returnValues)){ foreach ($returnValues as $returnValue) { if ($returnValue !== null ) return $returnValue; } }
		# cmcoupon_code END =================================================================',
		
					),
				);
				break;
			}
			case 'check':
			case 'reject': {

				$vars = array(
					'patterns' => array(
						'orderplugin' => '/\s*#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
									'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'redshop_coupon\'\s*\)\s*;\s*'.
									'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
									'\$returnValues\s*\=\s*\$dispatcher\->trigger\s*\(\s*\'onCouponRemove\'\s*,\s*array\s*\(\s*\$row\->order_id\s*\)\s*\);\s*'.
									'#\s*cmcoupon_code\s*END\s*=================================================================/is',
						'giftcertplugin' => '/\s*#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
									'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'redshop_coupon\'\s*\)\s*;\s*'.
									'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
									'\$returnValues\s*\=\s*\$dispatcher\s*\->\s*trigger\s*\(\s*\'onOrderGiftcertSend\'\s*,\s*array\s*\(\s*\$order_id\s*\)\s*\)\s*;\s*'.
									'if\s*\(\s*\!\s*empty\s*\(\s*\$returnValues\s*\)\s*\)\s*{\s*foreach\s*\(\s*\$returnValues\s*as\s*\$returnValue\s*\)\s*{\s*if\s*\(\s*\$returnValue\s*\!==\s*null\s*\)\s*return\s*\$returnValue\s*;\s*}\s*}\s*'.
									'#\s*cmcoupon_code\s*END\s*=================================================================/is',
					),
					'replacements' => array(
						'orderplugin' => '',
						'giftcertplugin' => '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_SITE . '/components/com_redshop/models/checkout.php',
			'vars' => $vars,
		);
	}

	public function inject_front_helper_cart( $type ) {

		switch( $type ) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						'coupon'=>'/(function\s+coupon\s*\(\s*\$c_data\s*=\s*array\s*\(\s*\)\s*\)\s*{)/is',
					),
					'replacements' => array(
						'coupon' => '$1
		# cmcoupon_code START ===============================================================
		JPluginHelper::importPlugin(\'redshop_coupon\');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger(\'onCouponProcess\', array($c_data));
		if(!empty($returnValues)){
			foreach ($returnValues as $returnValue) {
				if ($returnValue !== null  ) {
					return $returnValue;
				}
			}
		}
		# cmcoupon_code END =================================================================',
					),
				);
				break;
			}
			case 'check':
			case 'reject': {

				$vars = array(
					'patterns' => array(
						'coupon' => '/\s*#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
									'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'redshop_coupon\'\s*\)\s*;\s*'.
									'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
									'\$returnValues\s*\=\s*\$dispatcher\->trigger\s*\(\s*\'onCouponProcess\'\s*,\s*array\s*\(\s*\$c_data\s*\)\s*\)\s*;\s*'.
									'if\s*\(\s*\!empty\s*\(\s*\$returnValues\s*\)\s*\)\s*{\s*'.
										'foreach\s*\(\s*\$returnValues\s+as\s+\$returnValue\s*\)\s*{\s*'.
											'if\s*\(\s*\$returnValue\s*\!\=\=\s*null\s*\)\s*{\s*'.
												'return\s*\$returnValue\s*;\s*'.
											'}\s*'.
										'}\s*'.
									'}\s*'.
									'#\s*cmcoupon_code\s*END\s*=================================================================/is',
					),
					'replacements' => array(
						'coupon' => '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_SITE . '/components/com_redshop/helpers/' . ( version_compare( self::$version, '2.0.0', '>=' ) ? 'rscarthelper.php' : 'cart.php' ),
			'vars' => $vars,
		);
	}

	public function inject_admin_helper_order( $type ) {

		switch( $type ) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						'orderupdate' => '/(\$query\s*=\s*\'UPDATE\s*\'\s*\.\s*\$this\s*\->\s*_table_prefix\s*\.\s*\'orders\s*\'\s*\.\s*\'SET\s*order_status\s*=\s*"\'\s*\.\s*\$newstatus\s*\.\s*\'"\s*,\s*mdate\s*=\s*\'\s*\.\s*time\s*\(\s*\)\s*\.\s*\'\s*WHERE\s*order_id\s*IN\s*\(\s*\'\.\s*\$order_id\s*\.\s*\'\s*\)\s*\'\s*;\s*\$this\s*\->\s*_db\s*\->\s*setQuery\s*\(\s*\$query\s*\)\s*;\s*\$this\s*\->\s*_db\s*\->\s*query\s*\(\s*\)\s*;)/is',
					),
					'replacements' => array(
						'orderupdate' => '$1

		# cmcoupon_code START ===============================================================
		JPluginHelper::importPlugin(\'redshop_coupon\');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger(\'onOrderStatusUpdate\', array($order_id));
		# cmcoupon_code END =================================================================',

					),
				);
				break;
			}
			case 'check':
			case 'reject': {

				$vars = array(
					'patterns' => array(
						'orderupdate' => '/\s*#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
										'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'redshop_coupon\'\s*\)\s*;\s*'.
										'\$dispatcher\s*=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
										'\$returnValues\s*=\s*\$dispatcher\s*\->\s*trigger\s*\(\s*\'onOrderStatusUpdate\'\s*,\s*array\s*\(\s*\$order_id\s*\)\s*\)\s*;\s*'.
										'#\s*cmcoupon_code\s*END\s*=================================================================/is',
					),
					'replacements' => array(
						'orderupdate' => '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_SITE . '/administrator/components/com_redshop/helpers/order.php',
			'vars' => $vars,
		);
	}

	public function inject_front_views_cart_view( $type ) {
		switch( $type ) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						//'autocouponhook' => '/(function\s+coupon\s*\(\s*\$c_data\s*=\s*array\s*\(\s*\)\s*\)\s*{)/is',
						'autocouponhook' => '/(\$session\s*\=\s*\&?\s*JFactory\s*::\s*getSession\s*\(\s*\)\s*;\s*\$cart\s*\=\s*\$session\s*\->\s*get\s*\(\s*\'cart\'\s*\)\s*;)/is',
					),
					'replacements' => array(
						'autocouponhook' => '# cmcoupon_code START ===============================================================
		JPluginHelper::importPlugin(\'redshop_coupon\');
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger(\'onBeforeCartLoad\', array());
		# cmcoupon_code END =================================================================
		$1',
					),
				);
				break;
			}
			case 'check':
			case 'reject': {

				$vars = array(
					'patterns' => array(
						'autocouponhook' => '/#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
									'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'redshop_coupon\'\s*\)\s*;\s*'.
									'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
									'\$dispatcher\->trigger\s*\(\s*\'onBeforeCartLoad\'\s*,\s*array\s*\(\s*\)\s*\)\s*;\s*'.
									'#\s*cmcoupon_code\s*END\s*=================================================================\s*/is',
					),
					'replacements' => array(
						'autocouponhook' => '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_SITE . '/components/com_redshop/views/cart/view.html.php',
			'vars' => $vars,
		);
	}

}
