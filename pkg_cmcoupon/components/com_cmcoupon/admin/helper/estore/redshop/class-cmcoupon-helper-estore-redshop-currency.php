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

class Cmcoupon_Helper_Estore_Redshop_Currency {
# does not REALLY support multiple currency

	public function __construct() {
		// Load redSHOP Library
		JLoader::import('redshop.library');

		if ( ! class_exists( 'RedshopModelConfiguration' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_redshop/models/configuration.php';
		}
		$configClass = new RedshopModelConfiguration();
		$this->version = $configClass->getCurrentVersion();

		if ( file_exists( JPATH_ROOT . '/components/com_redshop/helpers/currency.php' ) ) {
			require_once JPATH_ROOT . '/components/com_redshop/helpers/currency.php';
		}
		elseif ( file_exists( JPATH_ROOT . '/components/com_redshop/currency.php' ) ) {
			require_once JPATH_ROOT . '/components/com_redshop/currency.php';
		}
		$this->currency_helper = null;
		if ( class_exists( 'CurrencyHelper' ) ) {
			$this->currency_helper = new CurrencyHelper();
		}
		elseif ( class_exists( 'convertPrice' ) ) {
			$this->currency_helper = new convertPrice();
		}
	}

	public function get_list() {
		$rows = AC()->db->get_objectlist( 'SELECT * FROM #__redshop_currency ORDER BY currency_code,currency_id' );
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
		$price = '';
		if ( version_compare( $this->version, '2.0.0', '>=' ) ) {
			JLoader::import( 'redshop.library' );
			$currency_symbol = empty( $currency_code ) || $this->get_default_currencycode() == $currency_code
				? Redshop::getConfig()->get( 'REDCURRENCY_SYMBOL' )
				: $currency_code
			;
			if ( is_numeric( $amount ) ) {
				$priceDecimal = (int) Redshop::getConfig()->get( 'PRICE_DECIMAL' );
				$productPrice = (double) $amount;

				if ( Redshop::getConfig()->get( 'CURRENCY_SYMBOL_POSITION' ) == 'front' ) {
					$price = $currency_symbol . number_format( $productPrice, $priceDecimal, Redshop::getConfig()->get( 'PRICE_SEPERATOR' ), Redshop::getConfig()->get( 'THOUSAND_SEPERATOR' ) );
				}
				elseif ( Redshop::getConfig()->get( 'CURRENCY_SYMBOL_POSITION' ) == 'behind' ) {
					$price = number_format( $productPrice, $priceDecimal, Redshop::getConfig()->get( 'PRICE_SEPERATOR' ), Redshop::getConfig()->get( 'THOUSAND_SEPERATOR' ) ) . $currency_symbol;
				}
				elseif ( Redshop::getConfig()->get( 'CURRENCY_SYMBOL_POSITION' ) == 'none' ) {
					$price = number_format( $productPrice, $priceDecimal, Redshop::getConfig()->get( 'PRICE_SEPERATOR' ), Redshop::getConfig()->get( 'THOUSAND_SEPERATOR' ) );
				}
				else {
					$price = $currency_symbol . number_format( $productPrice, $priceDecimal, Redshop::getConfig()->get( 'PRICE_SEPERATOR' ), Redshop::getConfig()->get( 'THOUSAND_SEPERATOR' ) );
				}
			}
		}
		else {
			$currency_symbol = empty( $currency_code ) || $this->get_default_currencycode() == $currency_code
				? REDCURRENCY_SYMBOL
				: $currency_code
			;
			if ( is_numeric( $amount ) ) {
				if ( CURRENCY_SYMBOL_POSITION == 'front' ){
					$price = $currency_symbol . number_format( (double) $amount, PRICE_DECIMAL, PRICE_SEPERATOR, THOUSAND_SEPERATOR );
				}
				elseif ( CURRENCY_SYMBOL_POSITION == 'behind' ) {
					$price = number_format( (double) $amount, PRICE_DECIMAL, PRICE_SEPERATOR, THOUSAND_SEPERATOR ) . $currency_symbol;
				}
				elseif ( CURRENCY_SYMBOL_POSITION == 'none' ) {
					$price = number_format( (double) $amount, PRICE_DECIMAL, PRICE_SEPERATOR, THOUSAND_SEPERATOR );
				}
				else{
					$price = $currency_symbol . number_format( (double) $amount, PRICE_DECIMAL, PRICE_SEPERATOR, THOUSAND_SEPERATOR );
				}
			}
		}
		return $price;
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
		$currency_code = trim( $currency_code );

		$default_currency_code = $this->get_default_currencycode();
		if( $default_currency_code == $currency_code ) {
			return $amount;
		}

		if ( empty( $this->currency_helper ) ) {
			return 0;
		}

		return $this->currency_helper->convert( $amount, $currency_code, $default_currency_code );		
	}

	public function convert_from_default( $amount, $currency_code = null ) {
		if ( empty( $amount ) ) {
			return 0;
		}

		if ( empty( $currency_code ) ) {
			$currency_code = $this->get_current_currencycode();
		}
		$currency_code = trim( $currency_code );

		$default_currency_code = $this->get_default_currencycode();
		if( $default_currency_code == $currency_code ) {
			return $amount;
		}

		if ( empty( $this->currency_helper ) ) {
			return 0;
		}

		return $this->currency_helper->convert( $amount, $default_currency_code, $currency_code );		
	}

	public function get_current_currencycode() {
		$currency = JFactory::getSession()->get( 'product_currency' );
		if ( empty( $currency ) ) {
			$currency = $this->get_default_currencycode();
		}
		return trim( $currency );
	}

	public function get_default_currencycode() {
		return trim( version_compare($this->version, '2.0.0', '>=') ? Redshop::getConfig()->get( 'CURRENCY_CODE' ) : CURRENCY_CODE );
	}

}
