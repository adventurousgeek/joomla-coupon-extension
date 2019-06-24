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
class CmCoupon_Admin_Class_History_Order extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'historyorder';
		$this->_id = $id;
		$this->_orderby = 'order_date';
		$this->_orderby_dir = 'desc';
		$this->_primary = 'order_number';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" onclick="jQuery(this.form).find(\'td.checkcolumn input:checkbox\').prop(\'checked\',this.checked);" />',
			'order_number' => AC()->lang->__( 'Order Number' ),
			'coupon_codes' => AC()->lang->__( 'Purchased Gift Certificate List' ),
			'user_email' => AC()->lang->__( 'E-mail' ),
			'username' => AC()->lang->__( 'Username' ),
			'lastname' => AC()->lang->__( 'Last Name' ),
			'firstname' => AC()->lang->__( 'First Name' ),
			'order_date' => AC()->lang->__( 'Order Date' ),
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
			'user_email' => 'user_email',
			'username' => '_username',
			'lastname' => '_lname',
			'firstname' => '_fname',
			'order_number' => 'c.order_id',
			'order_date' => '_created_on',
		);
		return $sortable_columns;
	}

	/**
	 * Action row column
	 *
	 * @param object $row the object.
	 */
	protected function get_row_action( $row ) {
		return array(
			'edit' => '<a href="#/cmcoupon/history?task=orderResend&id=' . $row->id . '">' . AC()->lang->__( 'Resend' ) . '</a>',
			'delete' => '<a href="#/cmcoupon/history?task=orderDelete&id=' . $row->id . '" class="submitdelete aria-button-if-js" onclick=\'return showNotice.warn();\'>' . AC()->lang->__( 'Delete' ) . '</a>',
		);
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
	 * Id column
	 *
	 * @param object $row the object.
	 */
	public function column_id( $row ) {
		return $row->id;
	}

	/**
	 * Username column
	 *
	 * @param object $row the object.
	 */
	public function column_username( $row ) {
		return $row->_username;
	}

	/**
	 * Last name column
	 *
	 * @param object $row the object.
	 */
	public function column_lastname( $row ) {
		return $row->_lname;
	}

	/**
	 * First name column
	 *
	 * @param object $row the object.
	 */
	public function column_firstname( $row ) {
		return $row->_fname;
	}

	/**
	 * Order number column
	 *
	 * @param object $row the object.
	 */
	public function column_order_number( $row ) {
		return ! empty( $row->order_id ) ? '<a href="' . AC()->store->get_order_link( $row->order_id ) . '">' . $row->order_number . '</a>' : '';
	}

	/**
	 * Order date column
	 *
	 * @param object $row the object.
	 */
	public function column_order_date( $row ) {
		return ! empty( $row->_created_on ) ? date( 'Y-m-d', strtotime( $row->_created_on ) ) : '';
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		$where = $this->buildquery_where();
		$orderby = $this->buildquery_orderby();
		$having = $this->buildquery_having();

		$sql = AC()->store->sql_history_order( $where, $having, $orderby );

		return $sql;
	}

	/**
	 * Query where clause
	 */
	public function buildquery_where() {
		return array();
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
			} elseif ( 'coupon' === $search_type ) {
				$having[] = 'LOWER(coupon_codes) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'email' === $search_type ) {
				$having[] = 'LOWER(user_email) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			}
		}

		return $having;
	}

	/**
	 * Delete items
	 *
	 * @param array $cids the items to delete.
	 */
	public function delete( $cids ) {

		$cids = AC()->helper->scrubids( $cids );
		if ( empty( $cids ) ) {
			return true;
		}

		AC()->db->query( 'DELETE FROM #__cmcoupon_voucher_customer_code WHERE voucher_customer_id IN (' . $cids . ')' );

		AC()->db->query( 'DELETE FROM #__cmcoupon_voucher_customer WHERE id IN (' . $cids . ')' );

		return true;
	}

	/**
	 * Resend purchased gift certificates
	 *
	 * @param int $id item id.
	 **/
	public function resend_giftcert( $id ) {

		return AC()->storegift->process_resend( $id );
	}
}
