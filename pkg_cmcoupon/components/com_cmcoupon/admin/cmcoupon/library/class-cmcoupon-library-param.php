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
class Cmcoupon_Library_Param {

	/**
	 * Parameters loaded from db.
	 *
	 * @var object
	 */
	private static $params;

	/**
	 * Constructor
	 **/
	public function __construct() {
		if ( empty( self::$params ) ) {
			self::$params = AC()->db->get_objectlist( 'SELECT id,name,is_json,value FROM #__cmcoupon_config', 'name' );
		}
	}

	/**
	 * Get a parameter
	 *
	 * @param string $param key.
	 * @param mixed  $default if not found return this.
	 **/
	public function get( $param, $default = '' ) {
		$value = isset( self::$params[ $param ]->value ) ? self::$params[ $param ]->value : '';
		if ( ! empty( $value ) && ! empty( self::$params[ $param ]->is_json ) ) {
			$value = json_decode( $value );
		}
		return ( empty( $value ) && ( 0 !== $value ) && ( '0' !== $value ) ) ? $default : $value;
	}

	/**
	 * Set a parameter
	 *
	 * @param string  $key key.
	 * @param mixed   $value val.
	 * @param boolean $html is html.
	 **/
	public function set( $key, $value = '', $html = false ) {
		if ( empty( $key ) ) {
			return;
		}

		$is_json = 'NULL';
		if ( is_array( $value ) ) {
			$value = AC()->helper->json_encode( $value );
			$is_json = 1;
		}

		$is_insert = false;
		if ( ! isset( self::$params[ $key ] ) ) {
			$is_insert = true;
			self::$params[ $key ] = new stdClass();
		}
		self::$params[ $key ]->value = $value;
		self::$params[ $key ]->is_json = 'NULL' === $is_json ? 0 : 1;

		$value = ( empty( $value ) && ( 0 !== $value ) && ( '0' !== $value ) ) ? 'NULL' : '"' . AC()->db->escape( $value, false, $html ) . '"';
		$sql = $is_insert
			? 'INSERT INTO #__cmcoupon_config (name,value,is_json) VALUES ("' . $key . '",' . $value . ',' . $is_json . ')'
			: 'UPDATE #__cmcoupon_config SET value=' . $value . ',is_json=' . $is_json . ' WHERE name="' . $key . '"'
		;
		AC()->db->query( $sql );
	}


}

/**
 * Class
 */
class Cmcoupon_Library_Paraminstall {

	/**
	 * Parameters loaded from db.
	 *
	 * @var object
	 */
	private static $params;

	/**
	 * Constructor
	 **/
	public function __construct() {
	}

	/**
	 * Get a parameter
	 *
	 * @param string $param key.
	 * @param mixed  $default if not found return this.
	 **/
	public function get( $param, $default = '' ) {
		return '';
	}

	/**
	 * Set a parameter
	 *
	 * @param string  $key key.
	 * @param mixed   $value val.
	 * @param boolean $html is html.
	 **/
	public function set( $key, $value = '', $html = false ) {
	}
}
