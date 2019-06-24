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

class Cmcoupon_Helper_Estore_Virtuemart_Currency {

	public function __construct() {
		if ( ! class_exists( 'VmConfig' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
		}
		VmConfig::loadConfig();
		VmConfig::get( 'tester_for_vm2016', '', true); // needed in vm2016 otherwise the configuration variables may not have been loaded and never will be if called

		if ( ! class_exists( 'CurrencyDisplay' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/currencydisplay.php';
		}
	}

	public function get_list() {
		$currency_ids = AC()->db->get_value( 'SELECT CONCAT(`vendor_accepted_currencies`, ",",`vendor_currency`) FROM #__virtuemart_vendors WHERE `virtuemart_vendor_id`=1' );
		if ( empty( $currency_ids ) ) {
			return array();
		}

		$rows = AC()->db->get_objectlist( 'SELECT * FROM #__virtuemart_currencies WHERE virtuemart_currency_id IN (' . $currency_ids . ')' );
		if ( empty( $rows ) ) {
			return array();
		}

		$currencies = array();
		foreach( $rows as $row ) {
			$row->id = $row->virtuemart_currency_id;
			$row->currency_id = $row->virtuemart_currency_id;
			$row->code = $row->currency_code_3;
			$row->rate = $row->currency_exchange_rate;
			$currencies[ $row->virtuemart_currency_id ] = $row;
		}

		return $currencies;
	}

	public function format( $amount, $currency_id = null ) {
		if ( empty( $currency_id ) ) {
			$currency_id = $this->get_default_currencyid();
		}
		$currency_class = CurrencyDisplay::getInstance( $currency_id );

		return $currency_class->getFormattedCurrency( $amount );
		//return $currency_class->priceDisplay( $amount );
	}

	public function format_currencycode( $amount, $currency_code ) {
		$currency_id = AC()->db->get_value( 'SELECT virtuemart_currency_id FROM #__virtuemart_currencies WHERE currency_code_3="' . AC()->db->escape( $currency_code ) . '"' );
		return $this->format( $amount, $currency_id );
	}

	public function convert_to_default_format( $amount, $currency_id = null ) {
		$amount = $this->convert_to_default( $amount, $currency_id );
		return $this->format( $amount );
	}

	public function convert_from_default_format( $amount, $currency_id = null ) {
		if ( empty( $currency_id ) ) {
			$currency_id = $this->get_current_currencyid();
		}
		$amount = $this->convert_from_default( $amount, $currency_id );
		return $this->format( $amount, $currency_id );
	}

	public function convert_to_default( $amount, $currency_id = null ) {
		if ( empty( $amount ) ) {
			return 0;
		}

		if ( empty( $currency_id ) ) {
			$currency_id = $this->get_current_currencyid();
		}

		$default_currency_id = $this->get_default_currencyid();
		if( $default_currency_id == $currency_id ) {
			return $amount;
		}

		$currency_class = CurrencyDisplay::getInstance( $currency_id );
		return $currency_class->convertCurrencyTo( $default_currency_id, $amount, false );
		
	}

	public function convert_from_default( $amount, $currency_id = null ) {
		if ( empty( $amount ) ) {
			return 0;
		}

		if ( empty( $currency_id ) ) {
			$currency_id = $this->get_current_currencyid();
		}

		$default_currency_id = $this->get_default_currencyid();
		if( $default_currency_id == $currency_id ) {
			return $amount;
		}

		$currency_class = CurrencyDisplay::getInstance( $default_currency_id );
		return $currency_class->convertCurrencyTo( $currency_id, $amount, false );
	}

	public function get_current_currencyid() {
		$currency_class = CurrencyDisplay::getInstance();
		return $currency_class->getId();

		//if ( ! class_exists( 'ShopFunctions' ) ) {
		//	require VMPATH_ADMIN . '/helpers/shopfunctions.php';
		//}
		//return ShopFunctions::getCurrencyByID( $currency_class->getId(), 'currency_code_3' );
	}

	public function get_default_currencyid() {
		$vendor_class = VmModel::getModel( 'vendor' )->getVendorCurrency(1);
		return $vendor_class->vendor_currency;
	}

	public function get_id_from_code( $currency_code ) {
		if ( ! class_exists( 'ShopFunctions' ) ) {
			require VMPATH_ADMIN . '/helpers/shopfunctions.php';
		}
		return ShopFunctions::getCurrencyIDByName( $value, 'currency_code_3' );
	}

	public function get_current_currencycode() {
		$currency_class = CurrencyDisplay::getInstance();
		return AC()->db->get_value( 'SELECT currency_code_3 FROM #__virtuemart_currencies WHERE virtuemart_currency_id=' . (int) $currency_class->getId() );
	}

	public function get_default_currencycode() {
		$vendor_class = VmModel::getModel( 'vendor' )->getVendorCurrency(1);
		return AC()->db->get_value( 'SELECT currency_code_3 FROM #__virtuemart_currencies WHERE virtuemart_currency_id=' . (int) $vendor_class->vendor_currency );
	}

}
