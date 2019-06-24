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

class Cmcoupon_Helper_Estore_Eshop_Currency {

	public function __construct() {
		require_once JPATH_ROOT . '/administrator/components/com_eshop/libraries/defines.php';
		require_once JPATH_ROOT . '/administrator/components/com_eshop/libraries/autoload.php';
		if ( ! class_exists( 'EshopHelper' ) ) {
			require JPATH_ROOT . '/components/com_eshop/helpers/helper.php';
		}
		if ( ! class_exists( 'EshopCurrency' ) ) {
			require JPATH_ROOT . '/components/com_eshop/helpers/currency.php';
		}
	}

	public function get_list() {
		$rows = AC()->db->get_objectlist( 'SELECT * FROM #__eshop_currencies' );
		if ( empty( $rows ) ) {
			return array();
		}

		$currencies = array();
		foreach( $rows as $row ) {
			$row->id = $row->id;
			$row->currency_id = $row->id;
			$row->code = $row->currency_code;
			$row->rate = $row->exchanged_value;
			$currencies[ $row->id ] = $row;
		}

		return $currencies;
	}

	public function format( $amount, $currency_code = null ) {
		if ( empty( $currency_code ) ) {
			$currency_code = $this->get_default_currencycode();
		}

		$currency_class = new EshopCurrency();
		return $currency_class->format( $amount, $currency_code, 1 );
	}

	public function format_currencycode( $amount, $currency_code ) {
		return $this->format( $amount, $currency_code );
	}

	public function convert_to_default_format( $amount, $currency_code = null ) {
		$amount = $this->convert_to_default( $amount, $currency_code );
		return $this->format( $amount );
	}

	public function convert_from_default_format( $amount, $currency_code = null ) {
		if ( empty( $currency_code ) ) {
			$currency_code = $this->get_current_currencycode();
		}
		$amount = $this->convert_from_default( $amount, $currency_code );
		return $this->format( $amount, $currency_code );
	}

	public function convert_to_default( $amount, $currency_code = null ) {
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

		$currency_class = new EshopCurrency();
		return $currency_class->convert( $amount, $currency_code, $default_currency_code );
	}

	public function convert_from_default( $amount, $currency_code = null ) {
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

		$currency_class = new EshopCurrency();
		return $currency_class->convert( $amount, $default_currency_code, $currency_code );
	}

	public function get_current_currencycode() {
		$currency_code = JFactory::getSession()->get( 'currency_code', '' );
		if ( empty( $currency_code ) ) {
			$currency_code = JFactory::getApplication()->input->cookie->getString( 'currency_code', '' );
		}
		if ( empty( $currency_code ) ) {
			$currency_code = $this->get_default_currencycode();
		}
		return $currency_code;
	}

	public function get_default_currencycode() {
		return EshopHelper::getConfigValue( 'default_currency_code' );
	}
}
