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

class Cmcoupon_Helper_Estore_Virtuemart_Installation {

	var $estore = 'virtuemart';
	static $virtuemart_version;
	
	public function __construct() {

		if ( empty( self::$virtuemart_version ) ) {
			require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/version.php'; 
			self::$virtuemart_version = VmVersion::$RELEASE;	
			if ( preg_match( '/\d/', substr( self::$virtuemart_version, -1 ) ) == false ) {
				self::$virtuemart_version = substr( self::$virtuemart_version, 0, -1 );
			}
		}
	}

	public function is_installation() {
		return true;
	}

	public function get_definition_file() {
		$_is_inject = true;
		//if ( version_compare( self::$virtuemart_version, '2.0.20', '>=' ) ) {
		//	$_is_inject = true;
		//}

		//if ( $_is_inject ) {
		//	$config_inject = (int) AC()->param->get( 'virtuemart_inject_totals', 0 );
		//	if ( $config_inject != 1 ) {
		//		$_is_inject = false;
		//	}
		//}

		//if ( AC()->helper->get_request( 'vmx' ) == 1 ) {
		//	$_is_inject = true;
		//}

		if ( ! $_is_inject ) {
			return array();
		}

		return array (
			'calculationh'=> array(
				'func' => 'inject_admin_helper_calculationh',
				'index' => 'pricetotal',
				'name' => AC()->lang->__( 'Needed in some cases' ),
				'file' => 'www/administrator/components/com_virtuemart/helpers/calculationh.php',
				'desc' => '',
			),
		);
	}	

	public function get_definition_sql() {
		return array();
	}
	
	public function get_definition_plugin() {
		return array(
			'vmcoupon' => array(
				'func' => 'plugin_installer',
				'name' => 'VMCoupon - CmCoupon',
				'folder' => 'vmcoupon',
				'dir' => JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/estore/virtuemart/extensions/plugins/vmcoupon',
				'desc' => '',
			),
			'vmpayment' => array(
				'func' => 'plugin_installer',
				'name' => 'VMPayment - CmCoupon',
				'folder' => 'vmpayment',
				'dir' => JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/estore/virtuemart/extensions/plugins/vmpayment',
				'desc' => '',
			),
			'vmshipment' => array(
				'func' => 'plugin_installer',
				'name' => 'VmShipment - Cmcoupon Free Gift Certificates',
				'folder' => 'vmshipment',
				'dir' => JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/estore/virtuemart/extensions/plugins/vmshipment',
				'desc' => '',
			),
		);
	}

	public function get_definition_config() {
		$order_statuses = AC()->store->get_order_status();
		$selected = AC()->param->get( '' . CMCOUPON_ESTORE . '_orderupdate_coupon_process', array() );
		if ( ! is_array( $selected ) ) {
			$selected = array( $selected );
		}
		$select_html = '';
		$select_html .= '<option value="" ' . ( empty( $selected ) ? 'SELECTED' : '' ) . '>' . AC()->lang->__( 'Order creation' ) . '</option>';
		foreach ( $order_statuses as $orderstatus ) {
			$select_html .= '<option value="' . $orderstatus->order_status_code . '"';
			if ( in_array( $orderstatus->order_status_code, $selected ) ) {
				$select_html .= 'SELECTED';
			}
			$select_html .= '>' . $orderstatus->order_status_name . '</option>';
		}
		return array(
			'trigger' => array(
				array(
					'label' => AC()->lang->__( 'Order state to trigger coupon processing' ),
					'field' => '
						<select id="params' . CMCOUPON_ESTORE . '_orderupdate_coupon_process" name="params[' . CMCOUPON_ESTORE . '_orderupdate_coupon_process][]" multiple="" class="inputbox" size="7" style="width:100%;">
							' . $select_html . '
						</select>
						<div style="width:250px;"></div>
					',
				),
			),
		);
	}

	public function get_definition_cron() {
	}

	public function inject_admin_helper_calculationh( $type ) {
		/*
		}
		//Calculate VatTax result
		if ($this->_cartPrices['shipment_calc_id']) $this->_cartData['VatTax'][$this->_cartPrices['shipment_calc_id']]['shipmentTax'] = $this->_cartPrices['shipmentTax'];
		if ($this->_cartPrices['payment_calc_id']) $this->_cartData['VatTax'][$this->_cartPrices['payment_calc_id']]['paymentTax'] = $this->_cartPrices['paymentTax'];
		*/

		if ( $type == 'inject' ) {
			AC()->param->set( 'virtuemart_inject_totals', 1 );
		}
		elseif ( $type == 'reject' ) {
			AC()->param->set( 'virtuemart_inject_totals', 0 );
		}

		return version_compare( self::$virtuemart_version, '2.9.8', '>=' )
			? $this->inject_admin_helper_calculationh_3x( $type )
			: $this->inject_admin_helper_calculationh_2x( $type )
		;
	}

	private function inject_admin_helper_calculationh_2x( $type ) {
		switch($type) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						'pricetotal'=>'/(if\s*\(\s*\$this\s*\-\>\s*\_cartPrices\s*\[\s*\'billTaxAmount\'\s*\]\s*<\s*0\s*\)\s*{\s*'.
											'\$this\s*\-\>\s*\_cartPrices\s*\[\s*\'billTaxAmount\'\s*\]\s*\=\s*0.0\s*;\s*'.
									  '})(\s*})/is',
					),
					'replacements' => array(
						'pricetotal'=>'$1
			{ # cmcoupon_code ===============================================================
				JPluginHelper::importPlugin(\'vmcoupon\');
				$dispatcher = JDispatcher::getInstance();
				$dispatcher->trigger(\'plgVmUpdateTotals\', array(&$this->_cartData, &$this->_cartPrices));
			}$2',
					),
				);
				break;
			}
			case 'check':
			case 'reject': {
				$vars = array(
					'patterns' => array(
					
						'pricetotal'=>'/\s*\{\s*#\s*cmcoupon_code\s*===============================================================\s*'.
											'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'vmcoupon\'\s*\)\s*;\s*'.
											'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
											'\$dispatcher\s*\->\s*trigger\s*\(\s*\'plgVmUpdateTotals\'\s*,\s*array\s*\(\s*\&\s*\$this\s*\-\>\s*\_cartData\s*,\s*\&\s*\$this\s*\-\>\s*\_cartPrices\s*\)\s*\)\s*;\s*'.
										'\}/is',

					),
					'replacements' => array(
						'pricetotal'=>'',
						'orderplugin'=>'',
						'giftcertplugin'=>'',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/calculationh.php',
			'vars' => $vars,
		);
	}

	private function inject_admin_helper_calculationh_3x($type) {
	
			/*if($this->_cart->cartPrices['billTaxAmount'] < 0){
				$this->_cart->cartPrices['billTaxAmount'] = 0.0;
			}
			if($this->_cartPrices['billTaxAmount'] < 0){
				$this->_cartPrices['billTaxAmount'] = 0.0;
			}*/

		switch($type) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						'pricetotal'=>'/(if\s*\(\s*\$this\s*\-\>\s*\_cart\s*\-\>\s*cartPrices\s*\[\s*\'billTaxAmount\'\s*\]\s*<\s*0\s*\)\s*{\s*'.
											'\$this\s*\-\>\s*\_cart\s*\-\>\s*cartPrices\s*\[\s*\'billTaxAmount\'\s*\]\s*\=\s*0.0\s*;\s*'.
									  '})(\s*})/is',
					),
					'replacements' => array(
						'pricetotal'=>'$1
			{ # cmcoupon_code ===============================================================
				JPluginHelper::importPlugin(\'vmcoupon\');
				$dispatcher = JDispatcher::getInstance();
				$dispatcher->trigger(\'plgVmUpdateTotals\', array(&$this->_cart->cartData, &$this->_cart->cartPrices));
			}$2',
					),
				);
				break;
			}
			case 'check':
			case 'reject': {

				$vars = array(
					'patterns' => array(
					
						'pricetotal'=>'/\s*\{\s*#\s*cmcoupon_code\s*===============================================================\s*'.
											'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'vmcoupon\'\s*\)\s*;\s*'.
											'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
											'\$dispatcher\s*\->\s*trigger\s*\(\s*\'plgVmUpdateTotals\'\s*,\s*array\s*\(\s*\&\s*\$this\s*\-\>\s*\_cart\s*\-\>\s*cartData\s*,\s*\&\s*\$this\s*\-\>\s*\_cart\s*\-\>\s*cartPrices\s*\)\s*\)\s*;\s*'.
										'\}/is',

					),
					'replacements' => array(
						'pricetotal'=>'',
						'orderplugin'=>'',
						'giftcertplugin'=>'',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/calculationh.php',
			'vars' => $vars,
		);
	}

	public function plugin_installer( $type, $key ) {
		$install_class = AC()->helper->new_class( 'CmCoupon_Helper_Installation' );
		return $install_class->plugin_installer( $type, $key, $this->get_definition_plugin() );
	}

}
