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

class Cmcoupon_Helper_Estore_Hikashop_Installation {

	var $estore = 'hikashop';
	static $version;
	
	public function __construct() {

		if ( empty( self::$version ) ) {
			if ( file_exists( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop_j3.xml' ) ) {
				$parser = simplexml_load_file( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop_j3.xml' );
			}
			elseif ( file_exists( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop.xml' ) ) {
				$parser = simplexml_load_file( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop.xml' );
			}
			if ( ! empty( $parser ) ) {
				self::$version = (string) $parser->version;
			}
		}
	}

	public function is_installation() {
		return true;
	}

	public function get_definition_file() {
		if ( empty( self::$version ) ) {
			return array();
		}

		if ( version_compare( self::$version, '1.5.8', '>' ) ) {
			return array();
		}

		return	array(
			'discount_core' => array(
				'func' => 'inject_admin_class_discount',
				'index' => 'onBeforeCouponLoad',
				'name' => AC()->lang->__( 'Core (Required)' ),
				'file' => 'www/administrator/components/com_hikashop/classes/discount.php',
				'desc' => '',
			),
			'cart_core' => array(
				'func' => 'inject_admin_class_cart',
				'index' => 'onAfterCartShippingLoad',
				'name' => AC()->lang->__( 'Core (Required)' ),
				'file' => 'www/administrator/components/com_hikashop/classes/cart.php',
				'desc' => '',
			),
		);
	}	

	public function get_definition_sql() {
		return array();
	}
	
	public function get_definition_plugin() {
		return array(
			'hikashop' => array(
				'func' => 'plugin_installer',
				'name' => 'Hikashop - CmCoupon',
				'folder' => 'hikashop',
				'dir' => JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/estore/hikashop/extensions/plugins/hikashop',
				'desc' => '',
			),
		);
	}

	public function get_definition_config() {
		$dbname = 'enable_hikashop_couponprocess_onAfterCartProductsLoad';
		$value = (int) AC()->param->get( $dbname, 0 );
		return array(
			'advanced' => array(
				array(
					'label' => AC()->lang->__( 'Enable onAfterCartProductsLoad trigger when processing coupoin' ),
					'field' => '
					<div class="awcontrols">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" id="params' . $dbname . '_yes" name="params[' . $dbname . ']" value="1" ' . ( 1 === $value ? 'checked="checked"' : '' ) . ' />
							<label for="params' . $dbname . '_yes" >' . AC()->lang->__( 'Yes' ) . '</label>
							<input type="radio" id="params' . $dbname . '_no" name="params[' . $dbname . ']" value="0" ' . ( 1 !== $value ? 'checked="checked"' : '' ) . ' />
							<label for="params' . $dbname . '_no" >' . AC()->lang->__( 'No' ) . '</label>
						</span>
					</div>',
				),
			),
		);
	}

	public function inject_admin_class_discount( $type ) {

		switch( $type ) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						'onBeforeCouponLoad' => '/(function\s+load\s*\(\s*\$coupon\s*\)\s*{)/is',
					),
					'replacements' => array(
						'onBeforeCouponLoad' => '$1
# cmcoupon_code START ===============================================================
$do=true;
JPluginHelper::importPlugin( \'hikashop\' );
$dispatcher =& JDispatcher::getInstance();
$item = $dispatcher->trigger( \'onBeforeCouponLoad\', array( &$coupon, & $do) );
if(!$do) return current($item);
# cmcoupon_code END =================================================================',
		
					),
				);
				break;
			}
			case 'check':
			case 'reject': {

				$vars = array(
					'patterns' => array(
						'onBeforeCouponLoad'=>'/\s*#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
									'\$do\s*\=\s*true\s*;\s*'.
									'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'hikashop\'\s*\)\s*;\s*'.
									'\$dispatcher\s*\=\s*\&\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
									'\$item\s*\=\s*\$dispatcher\s*\->\s*trigger\s*\(\s*\'onBeforeCouponLoad\'\s*,\s*array\s*\(\s*\&\s*\$coupon\s*,\s*\&\s*\$do\s*\)\s*\)\s*;\s*'.
									'if\s*\(\s*\!\s*\$do\s*\)\s*return\s+current\s*\(\s*\$item\s*\)\s*;\s*'.
									'#\s*cmcoupon_code\s*END\s*=================================================================/is',
					),
					'replacements' => array(
						'onBeforeCouponLoad' => '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_ADMINISTRATOR . '/components/com_hikashop/classes/discount.php',
			'vars' => $vars,
		);
	}

	public function inject_admin_class_cart( $type ) {

		switch( $type ) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						'onAfterCartShippingLoad'=>'/(\$shipping_id\s*\=\s*\$app\s*\->\s*getUserState\s*\(\s*HIKASHOP_COMPONENT\s*\.\s*\'\.shipping_id\'\s*\)\s*;.*?)'.
												'(if\s*\(\s*bccomp\s*\(\s*\$cart\s*\->\s*full_total)'.
												'/is',
					),
					'replacements' => array(
						'onAfterCartShippingLoad'=>'$1
# cmcoupon_code START ===============================================================
$dispatcher->trigger(\'onAfterCartShippingLoad\', array( &$cart ) );
# cmcoupon_code END =================================================================

			$2',

					),
				);
				break;
			}
			case 'check':
			case 'reject': {

				$vars = array(
					'patterns' => array(
						'onAfterCartShippingLoad'=>'/\n?#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
										'\$dispatcher\s*\->\s*trigger\s*\(\s*\'onAfterCartShippingLoad\'\s*,\s*array\s*\(\s*\&\s*\$cart\s*\)\s*\)\s*;\s*'.
										'#\s*cmcoupon_code\s*END\s*=================================================================\s*/is',
					),
					'replacements' => array(
						'onAfterCartShippingLoad'=>'',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_ADMINISTRATOR . '/components/com_hikashop/classes/cart.php',
			'vars' => $vars,
		);
	}

	public function plugin_installer( $type, $key ) {
		$install_class = AC()->helper->new_class( 'CmCoupon_Helper_Installation' );
		return $install_class->plugin_installer( $type, $key, $this->get_definition_plugin() );
	}

}
