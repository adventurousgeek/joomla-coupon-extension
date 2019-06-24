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
class CmCoupon_Admin_Class_History_Giftcert extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'historygiftcert';
		$this->_id = $id;
		$this->_orderby = 'coupon_code';
		$this->_primary = 'coupon_code';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'coupon_code' => AC()->lang->__( 'Gift Certificate' ),
			'coupon_value' => AC()->lang->__( 'Value' ),
			'coupon_value_used' => AC()->lang->__( 'Value Used' ),
			'balance' => AC()->lang->__( 'Balance' ),
			'expiration' => AC()->lang->__( 'Expiration' ),
			'id' => AC()->lang->__( 'ID' ),
		);
		return $columns;
	}

	/**
	 * Sortable columns
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'id'  => 'c.id',
			'coupon_code' => 'c.coupon_code',
			'coupon_value' => 'c.coupon_value',
			'coupon_value_used' => 'coupon_value_used',
			'balance' => 'balance',
			'expiration' => 'c.expiration',
		);
		return $sortable_columns;
	}

	/**
	 * Action row column
	 *
	 * @param object $row the object.
	 */
	protected function get_row_action( $row ) {
		return array();
	}

	/**
	 * Checkbox column
	 *
	 * @param object $row the object.
	 */
	public function column_cb( $row ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%1$s" />', $row->id );
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
	 * Coupon value column
	 *
	 * @param object $row the object.
	 */
	public function column_coupon_value( $row ) {
		return number_format( $row->coupon_value, 2 );
	}

	/**
	 * Used value column
	 *
	 * @param object $row the object.
	 */
	public function column_coupon_value_used( $row ) {
		return number_format( $row->coupon_value_used, 2 );
	}

	/**
	 * Balance column
	 *
	 * @param object $row the object.
	 */
	public function column_balance( $row ) {
		return number_format( $row->balance, 2 );
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		$where = $this->buildquery_where();
		$orderby = $this->buildquery_orderby();
		$having = $this->buildquery_having();

		$sql = AC()->store->sql_history_giftcert( $where, $having, $orderby );

		return $sql;
	}

	/**
	 * Query where clause
	 */
	public function buildquery_where() {
		$filter_state = AC()->helper->get_userstate_request( $this->name . '.filter_state', 'filter_state', '' );
		$search = AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' );
		$search_type = AC()->helper->get_userstate_request( $this->name . '.search_type', 'search_type', '' );

		$where = array();

		if ( $filter_state ) {
			if ( 'published' === $filter_state ) {
				$current_date = date( 'Y-m-d H:i:s' );
				$where[] = 'c.state="published"
				   AND ( ((c.startdate IS NULL OR c.startdate="") 	AND (c.expiration IS NULL OR c.expiration="")) OR
						 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
						 ((c.startdate IS NULL OR c.startdate="") 	AND c.expiration>="' . $current_date . '") OR
						 (c.startdate<="' . $current_date . '"		AND c.expiration>="' . $current_date . '")
					   )
				';
			} elseif ( 'unpublished' === $filter_state ) {
				$current_date = date( 'Y-m-d H:i:s' );
				$where[] = '(c.state="unpublished" OR c.startdate>"' . $current_date . '" OR c.expiration<"' . $current_date . '")';
			} elseif ( 'balance' === $filter_state ) {
				$where[] = '(c.state="balance")';
			}
		}
		if ( $search ) {
			if ( 'coupon' === $search_type ) {
				$where[] = 'LOWER(c.coupon_code) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'email' === $search_type ) {
				$where[] = 'LOWER(u.user_email) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			}
		}

		return $where;
	}

	/**
	 * Query having clause
	 */
	public function buildquery_having() {
		$search = AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' );
		$search_type = AC()->helper->get_userstate_request( $this->name . '.search_type', 'search_type', '' );

		$having = array();

		if ( $search ) {
			if ( 'user' === $search_type ) {
				$having[] = 'LOWER(_username) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'last' === $search_type ) {
				$having[] = 'LOWER(_lname) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'first' === $search_type ) {
				$having[] = 'LOWER(_fname) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'order' === $search_type ) {
				$having[] = 'order_number LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'date' === $search_type ) {
				$having[] = '_created_on LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			}
		}

		return $having;
	}
}
