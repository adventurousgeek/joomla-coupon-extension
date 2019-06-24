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

class Cmcoupon_Helper_Estore_Eshop_Installation {

	var $estore = 'eshop';
	
	public function __construct() {
	}

	public function is_installation() {
		return true;
	}

	public function get_definition_file() {
		return	array(
			'helper_coupon_getcost'=>array(
				'func' => 'inject_site_helper_coupon',
				'index' => 'getCosts',
				'file' => 'www/components/com_eshop/helpers/coupon.php',
				'name' => AC()->lang->__( 'Core (Required)' ),
				'desc' => '',
			),
			
			'helper_coupon_getdata'=>array(
				'func' => 'inject_site_helper_coupon',
				'index' => 'getCouponData',
				'file' => 'www/components/com_eshop/helpers/coupon.php',
				'name' => AC()->lang->__( 'Core (Required)' ),
				'desc' => '',
			),
		);
	}	

	public function get_definition_sql() {
		return array();
	}
	
	public function get_definition_plugin() {
		return array(
			'eshop' => array(
				'func' => 'plugin_installer',
				'name' => 'Eshop - CmCoupon',
				'folder' => 'eshop',
				'dir' => JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/estore/eshop/extensions/plugins/eshop',
				'desc' => '',
			),
		);
	}

	public function get_definition_cron() {
		$statuses = AC()->param->get( 'giftcert_order_status', '' );
		if ( ! empty( $statuses ) ) {
			if ( ! is_array( $statuses ) ) {
				$statuses = array($statuses);
			}

			$rows = AC()->db->get_objectlist( '
				SELECT i.order_id
				  FROM #__eshop_orderproducts i
				  JOIN #__eshop_orders o ON o.id=i.order_id
				  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=i.product_id
				  LEFT JOIN #__cmcoupon_voucher_customer g ON g.order_id=i.order_id AND g.estore="eshop"
				 WHERE g.order_id IS NULL AND ap.published=1 AND ap.estore="eshop" AND o.order_status_id IN (' . implode( ',', $statuses ) . ')
				 GROUP BY i.order_id
				 LIMIT 10
			' );
			foreach($rows as $row) {
				AC()->storegift->order_status_changed( $row->order_id );
			}
		}

		$statuses = AC()->param->get( 'ordercancel_order_status', '' );
		if ( ! empty( $statuses ) ) {
			if ( ! is_array( $statuses ) ) {
				$statuses = array( $statuses );
			}

			$rows = AC()->db->get_objectlist( '
				SELECT o.id, o.order_status_id
				  FROM #__eshop_orders o
				  JOIN #__cmcoupon_history h ON h.order_id=o.id
				 WHERE h.estore="eshop" AND o.order_status_id IN (' . implode( ',', $statuses ) . ')
				 GROUP BY o.id
				 LIMIT 100
			' );
			foreach ( $rows as $row ) {
				AC()->storediscount->order_status_changed( $row );
			}
		}
	}

	public function plugin_installer( $type, $key ) {
		$install_class = AC()->helper->new_class( 'CmCoupon_Helper_Installation' );
		return $install_class->plugin_installer( $type, $key, $this->get_definition_plugin() );
	}

	public function inject_site_helper_coupon( $type ) {

		switch( $type ) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						'getCosts' => '/(public\s+function\s+getCosts\s*\(\s*\&\s*\$totalData\s*,\s*\&\s*\$total\s*,\s*\&\s*\$taxes\s*\)\s*{)/is',
						'getCouponData' => '/(public\s+function\s+getCouponData\s*\(\s*\$code\s*\)\s*{)/is',
					),
					'replacements' => array(
						'getCosts' => '$1
		{ # cmcoupon_code
			JPluginHelper::importPlugin(\'eshop\');
			$dispatcher = JDispatcher::getInstance();
			$rtn = $dispatcher->trigger(\'onGetCouponCosts\', array(&$totalData, &$total, &$taxes));
			if(!empty($rtn)){
				foreach ($rtn as $returnValue) {
					if ($returnValue !== null) 
						return $returnValue;
				}
			}
		}',
						'getCouponData' => '$1
		{ # cmcoupon_code
			JPluginHelper::importPlugin(\'eshop\');
			$dispatcher = JDispatcher::getInstance();
			$rtn = $dispatcher->trigger(\'onGetCouponData\', array($code));
			if(!empty($rtn)){
				foreach ($rtn as $returnValue) {
					if ($returnValue !== null) 
						return $returnValue;
				}
			}
		}',
		
					),
				);
				break;
			}
			case 'check':
			case 'reject': {

				$vars = array(
					'patterns' => array(

						'getCosts' => '/\s*{\s*#\s*cmcoupon_code\s*'.
											'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'eshop\'\s*\)\s*;\s*'.
											'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
											'\$rtn\s*\=\s*\$dispatcher\s*\->\s*trigger\s*\(\s*\'onGetCouponCosts\'\s*,\s*array\s*\(\s*\&\s*\$totalData\s*,\s*\&\s*\$total\s*,\s*\&\s*\$taxes\s*\)\s*\)\s*;\s*'.
											'if\s*\(\s*\!\s*empty\s*\(\s*\$rtn\s*\)\s*\)\s*{\s*foreach\s*\(\s*\$rtn\s*as\s*\$returnValue\s*\)\s*{\s*if\s*\(\s*\$returnValue\s*\!==\s*null\s*\)\s*return\s*\$returnValue\s*;\s*}\s*}\s*'.
										'}/is',
						'getCouponData' => '/\s*{\s*#\s*cmcoupon_code\s*'.
											'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'eshop\'\s*\)\s*;\s*'.
											'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
											'\$rtn\s*\=\s*\$dispatcher\s*\->\s*trigger\s*\(\s*\'onGetCouponData\'\s*,\s*array\s*\(\s*\$code\s*\)\s*\)\s*;\s*'.
											'if\s*\(\s*\!\s*empty\s*\(\s*\$rtn\s*\)\s*\)\s*{\s*foreach\s*\(\s*\$rtn\s*as\s*\$returnValue\s*\)\s*{\s*if\s*\(\s*\$returnValue\s*\!==\s*null\s*\)\s*return\s*\$returnValue\s*;\s*}\s*}\s*'.
										'}/is',

					),
					'replacements' => array(
						'getCosts' => '',
						'getCouponData' => '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_SITE . '/components/com_eshop/helpers/coupon.php',
			'vars' => $vars,
		);
	}


}
