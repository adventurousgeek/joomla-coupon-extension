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

class Cmcoupon_Helper_Estore_Hikashop_Currency {

	public function __construct() {
		if ( ! defined( 'DS' ) ) {
			define( 'DS', DIRECTORY_SEPARATOR );
		}
		if ( ! class_exists( 'hikashop' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_hikashop/helpers/helper.php';
		}
	}

	public function get_list() {

		$rows = AC()->db->get_objectlist( 'SELECT * FROM ' . hikashop_table('currency') . ' WHERE currency_published=1 OR currency_id = ' . (int) $this->get_default_currencyid() );
		if ( empty( $rows ) ) {
			return array();
		}

		$currencies = array();
		foreach( $rows as $row ) {
			$row->id = $row->currency_id;
			$row->code = $row->currency_code;
			$row->rate = $row->currency_rate;
			$currencies[ $row->currency_id ] = $row;
		}

		return $currencies;
	}

	public function format( $amount, $currency_id = null ) {
		if ( empty( $currency_id ) ) {
			$currency_id = $this->get_default_currencyid();
		}
		$currencyClass = hikashop_get( 'class.currency' );

		return $currencyClass->format( $amount, $currency_id );
	}

	public function format_currencycode( $amount, $currency_code ) {
		$currency_id = AC()->db->get_value( 'SELECT currency_id FROM #__hikashop_currency WHERE currency_code="' . AC()->db->escape( $currency_code ) . '"' );
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

// convertUniquePrice($price, $srcCurrency_id, $dstCurrency_id) {
		$currencyClass = hikashop_get( 'class.currency' );
		return $currencyClass->convertUniquePrice( $amount, $currency_id, $default_currency_id );
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

		$currencyClass = hikashop_get( 'class.currency' );
		return $currencyClass->convertUniquePrice( $amount, $default_currency_id, $currency_id );
	}

	public function get_current_currencyid() {
		return hikashop_getCurrency();
	}

	public function get_current_currencycode() {
		$currencyClass = hikashop_get('class.currency');
		$no_used = array();
		$currencies = $currencyClass->getCurrencies( hikashop_getCurrency(), $not_used );
		$currency = current( $currencies );
		return $currency->currency_code;
	}

	public function get_default_currencyid() {
		$currencyClass = hikashop_get('class.currency');
		return $currencyClass->mainCurrency();
	}

	public function get_default_currencycode() {
		$currencyClass = hikashop_get('class.currency');
		$no_used = array();
		$currencies = $currencyClass->getCurrencies( $currencyClass->mainCurrency(), $not_used );
		$currency = current( $currencies );
		return $currency->currency_code;
	}

}
