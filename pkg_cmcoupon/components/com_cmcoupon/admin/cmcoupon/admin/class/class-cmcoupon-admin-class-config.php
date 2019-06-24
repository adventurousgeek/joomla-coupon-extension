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
class CmCoupon_Admin_Class_Config extends CmCoupon_Library_Class {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name = 'config';
		parent::__construct();

		$this->idlangs = array(
			'errNoRecord',
			'errMinVal',
			'errMinQty',
			'errUserLogin',
			'errUserNotOnList',
			'errUserGroupNotOnList',
			'errUserMaxUse',
			'errTotalMaxUse',
			'errProductInclList',
			'errProductExclList',
			'errCategoryInclList',
			'errCategoryExclList',
			'errManufacturerInclList',
			'errManufacturerExclList',
			'errVendorInclList',
			'errVendorExclList',
			'errCustomInclList',
			'errCustomExclList',
			'errShippingSelect',
			'errShippingValid',
			'errShippingInclList',
			'errShippingExclList',
			'errGiftUsed',
			'errProgressiveThreshold',
			'errDiscountedExclude',
			'errGiftcertExclude',
			'errBuyXYList1IncludeEmpty',
			'errBuyXYList1ExcludeEmpty',
			'errBuyXYList2IncludeEmpty',
			'errBuyXYList2ExcludeEmpty',
			'errCountryInclude',
			'errCountryExclude',
			'errCountrystateInclude',
			'errCountrystateExclude',
			'errPaymentMethodInclude',
			'errPaymentMethodExclude',
		);
	}

	/**
	 * Get language information
	 */
	public function get_languagedata() {
		$rtn = array();
		foreach ( $this->idlangs as $key ) {
			$elem_id = (int) AC()->param->get( 'idlang_' . $key );
			if ( ! empty( $elem_id ) ) {
				$rows = AC()->db->get_objectlist( 'SELECT lang,text FROM #__cmcoupon_lang_text WHERE elem_id=' . $elem_id );
				foreach ( $rows as $row ) {
					if ( ! isset( $rtn[ $row->lang ] ) ) {
						$rtn[ $row->lang ] = new stdclass();
					}
					$rtn[ $row->lang ]->{$key} = $row->text;
				}
			}
		}

		return $rtn;
	}

	/**
	 * Store configuration
	 *
	 * @param array $data info to stor.
	 */
	public function store( $data ) {

		if ( isset( $data['params']['is_case_sensitive'] ) ) {
			$data['is_case_sensitive'] = $data['params']['is_case_sensitive'];
			unset( $data['params']['is_case_sensitive'] );
		}
		if ( ! empty( $data['params'] ) ) {
			$params = AC()->param;

			if ( ! isset( $data['params'][ CMCOUPON_ESTORE . '_orderupdate_coupon_process' ] )
			|| ( is_array( $data['params'][ CMCOUPON_ESTORE . '_orderupdate_coupon_process' ] ) && current( $data['params'][ CMCOUPON_ESTORE . '_orderupdate_coupon_process' ] ) === '' )
			) {
				$data['params'][ CMCOUPON_ESTORE . '_orderupdate_coupon_process' ] = '';
			}
			if ( ! isset( $data['params']['ordercancel_order_status'] ) ) {
				$data['params']['ordercancel_order_status'] = '';
			}
			if ( ! isset( $data['params'][ CMCOUPON_ESTORE . '_balance_category_exclude' ] ) ) {
				$data['params'][ CMCOUPON_ESTORE . '_balance_category_exclude' ] = '';
			}
			if ( ! isset( $data['params'][ CMCOUPON_ESTORE . '_balance_shipping_exclude' ] ) ) {
				$data['params'][ CMCOUPON_ESTORE . '_balance_shipping_exclude' ] = '';
			}

			// Store normal data.
			foreach ( $data['params'] as $name => $value ) {
				$html = 'giftcert_vendor_email' === $name ? true : false;
				$params->set( $name, $value, $html );
			}

			// Store language data.
			foreach ( $data['idlang'] as $iso => $langarray ) {
				foreach ( $langarray as $field => $value ) {
					if ( ! in_array( $field, $this->idlangs, true ) ) {
						continue;
					}

					$name = 'idlang_' . $field;
					$elem_id = AC()->lang->save_data( $params->get( $name ), $value, $iso );
					if ( ! empty( $elem_id ) ) {
						$params->set( $name, $elem_id );
					}
				}
			}
		}

		$data['is_case_sensitive'] = ! isset( $data['is_case_sensitive'] ) ? 0 : (int) $data['is_case_sensitive'];
		$data['casesensitiveold'] = ! isset( $data['casesensitiveold'] ) ? 0 : (int) $data['casesensitiveold'];
		if ( isset( $data['is_case_sensitive'], $data['casesensitiveold'] )
		&& $data['is_case_sensitive'] !== $data['casesensitiveold']
		&& ( 1 === $data['is_case_sensitive'] || 0 === $data['is_case_sensitive'] ) ) {
			$sql = 0 === $data['is_case_sensitive']
					? 'ALTER TABLE `#__cmcoupon` MODIFY `coupon_code` VARCHAR(255) NOT NULL DEFAULT ""'
					: 'ALTER TABLE `#__cmcoupon` MODIFY `coupon_code` VARCHAR(255) BINARY NOT NULL DEFAULT ""'
					;
			AC()->db->query( $sql );
		}

		return true;
	}

	/**
	 * Reset all cmcoupon tables
	 */
	public function reset_tables() {
		$updater = AC()->helper->new_class( 'CmCoupon_Library_Update' );

		// Get license inforamtion.
		$license_info = AC()->db->get_objectlist( 'SELECT * FROM #__cmcoupon_license' );

		// Delete all.
		$rtn = $updater->run_sql_file( CMCOUPON_DIR . '/cmcoupon/library/install/mysql.uninstall.sql', CMCOUPON_VERSION );

		// Install all.
		if ( $rtn ) {
			$rtn = $updater->run_sql_file( CMCOUPON_DIR . '/cmcoupon/library/install/mysql.install.sql', CMCOUPON_VERSION );

			// Load license information back in.
			if ( ! empty( $license_info ) ) {
				foreach ( $license_info as $row ) {
					AC()->db->query( 'INSERT INTO #__cmcoupon_license (id,value) VALUES ("' . AC()->db->escape( $row->id ) . '","' . AC()->db->escape( $row->value ) . '")' );
				}
			}
		}

		return $rtn;
	}

	/**
	 * Get configs from estores
	 */
	public function get_injection() {
		$injection = (object) array(
			'general' => array(),
			'multiplecoupon' => array(),
			'balance' => array(),
			'trigger' => array(),
			'giftcert' => array(),
			'reminder' => array(),
			'errormsg' => array(),
			'advanced' => array(),
		);

		$installer = AC()->helper->new_class( 'Cmcoupon_Helper_Estore_' . CMCOUPON_ESTORE . '_Installation' );
		if ( ! method_exists( $installer, 'get_definition_config' ) ) {
			return $injection;
		}

		$ret = $installer->get_definition_config();

		if ( ! is_array( $ret ) ) {
			return $injection;
		}

		foreach ( $ret as $key => $items ) {
			if ( ! isset( $injection->{$key} ) || ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item ) {
				if ( ! isset( $item['label'] ) || ! isset( $item['field'] ) ) {
					continue;
				}
				$injection->{$key}[] = (object) array(
					'label' => $item['label'],
					'field' => $item['field'],
				);
			}
		}
		return $injection;
	}
}
