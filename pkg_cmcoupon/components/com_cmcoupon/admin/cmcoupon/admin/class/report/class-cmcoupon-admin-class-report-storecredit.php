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

AC()->helper->add_class( 'CmCoupon_Admin_Class_Report' );

/**
 * Class
 */
class CmCoupon_Admin_Class_Report_Storecredit extends CmCoupon_Admin_Class_Report {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'reportstorecredit';
		$this->title = AC()->lang->__( 'Customer Balance' );
		$this->filename = 'customer_balance.csv';
		$this->_orderby = '';
		$this->_primary = '';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'id' => AC()->lang->__( 'ID' ),
			'user_login' => AC()->lang->__( 'Username' ),
			'display_name' => AC()->lang->__( 'Name' ),
			'user_email' => AC()->lang->__( 'E-mail' ),
			'total' => AC()->lang->__( 'Total' ),
			'paid' => AC()->lang->__( 'Value Used' ),
			'balance' => AC()->lang->__( 'Balance' ),
		);
		return $columns;
	}

	/**
	 * Default column behavior
	 *
	 * @param object $item the object.
	 * @param string $column_name the column.
	 */
	public function column_default( $item, $column_name ) {
		return $item->{$column_name};
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		return AC()->store->sql_store_credit();
	}

	/**
	 * Data list
	 */
	public function get_data() {
		$rtn = isset( $this->_get_all_data ) && true === $this->_get_all_data
					? $this->get_list( $this->buildquery(), 'id' )
					: $this->get_list( $this->buildquery(), 'id', $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );

		$this->_data = array();
		foreach ( $rtn as $row ) {
			$row->total = ! empty( $row->total ) ? number_format( $row->total, 2 ) : '';
			$row->paid = ! empty( $row->paid ) ? number_format( $row->paid, 2 ) : '';
			$row->balance = ! empty( $row->balance ) ? number_format( $row->balance, 2 ) : '';
			$this->_data[] = $row;
		}
		return $this->_data;
	}

}

