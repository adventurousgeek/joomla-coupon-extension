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

class Cmcoupon_Helper_Estore_Virtuemart1_Currency {

	public function __construct() {
	}

	public function init() {
		require_once JPATH_ROOT . '/components/com_virtuemart/virtuemart_parser.php';
	}

	public function get_list() {
		$this->init();
		$currency_codes = AC()->db->get_value( 'SELECT CONCAT(`vendor_accepted_currencies`, \'","\',`vendor_currency`) FROM #__vm_vendor WHERE `virtuemart_vendor_id`=1' );
		if ( empty( $currency_codes ) ) {
			return array();
		}

		$currency_codes = explode( ',', $currency_codes );
		$rows = AC()->db->get_objectlist( 'SELECT * FROM #__vm_currency WHERE currency_code IN ("' . implode( '","', $currency_codes ) . '")' );
		if ( empty( $rows ) ) {
			return array();
		}

		$currencies = array();
		foreach( $rows as $row ) {
			$row->id = $row->currency_id;
			$row->currency_id = $row->currency_id;
			$row->code = $row->currency_code;
			$row->rate = 0;
			$currencies[ $row->currency_id ] = $row;
		}

		return $currencies;
	}

	public function format( $amount, $currency_code = null ) {
		$this->init();
		if ( empty( $currency_code ) ) {
			$currency_code = $this->get_default_currencycode();
		}

		$decimal = '';
		$symbol = $currency_code;
		return str_replace( '&euro;', '€', html_entity_decode( $GLOBALS['CURRENCY_DISPLAY']->getFullValue( $amount, $decimal, $symbol ) ) );

		//$currency_display_class = clone( $GLOBALS['CURRENCY_DISPLAY'] );
		//$currency_display_class->CurrencyDisplay();
		//
		//$currency = ps_vendor::get_currency_display_style( $vendor_currency_display_style );
		//if ( ! class_exists( 'CurrencyDisplay' ) ) {
		//	require CLASSPATH . 'currency/class_currency_display.php';
		//}
		//$currency_display_class = new CurrencyDisplay(
		//	$currency['id'],
		//	$currency['symbol'],
		//	$currency['nbdecimal'],
		//	$currency['sdecimal'],
		//	$currency['thousands'],
		//	$currency['positive'],
		//	$currency['negative']
		//);
		//
		//return str_replace( '&euro;', '€', html_entity_decode( $currency_display_class->getFullValue( $amount ) ) );
	}

	public function format_currencycode( $amount, $currency_code ) {
		return $this->format( $amount, $currency_code );
	}

	public function convert_to_default_format( $amount, $currency_code = null ) {
		$this->init();
		$amount = $this->convert_to_default( $amount, $currency_code );
		return $this->format( $amount );
	}

	public function convert_from_default_format( $amount, $currency_code = null ) {
		$this->init();
		if ( empty( $currency_code ) ) {
			$currency_code = $this->get_current_currencycode();
		}
		$amount = $this->convert_from_default( $amount, $currency_code );
		return $this->format( $amount, $currency_code );
	}

	public function convert_to_default( $amount, $currency_code = null ) {
		$this->init();
		if ( empty( $amount ) ) {
			return 0;
		}

		if ( empty( $currency_code ) ) {
			$currency_code = $this->get_current_currencycode();
		}

		$default_currency_code = $this->get_default_currencycode();
		if( $default_currency_code == $currency_code ) {
			return $amount;
		}

		return $GLOBALS['CURRENCY']->convert( $amount, $currency_code, $default_currency_code );		
	}

	public function convert_from_default( $amount, $currency_code = null ) {
		$this->init();
		if ( empty( $amount ) ) {
			return 0;
		}

		if ( empty( $currency_code ) ) {
			$currency_code = $this->get_current_currencycode();
		}

		$default_currency_code = $this->get_default_currencycode();
		if( $default_currency_code == $currency_code ) {
			return $amount;
		}

		return $GLOBALS['CURRENCY']->convert( $amount, $default_currency_code, $currency_code );		
	}

	public function get_current_currencycode() {
		$this->init();
		return $GLOBALS['product_currency'];
	}

	public function get_default_currencycode() {
		$this->init();
		global $vendor_currency;
		return $vendor_currency;
	}

}
