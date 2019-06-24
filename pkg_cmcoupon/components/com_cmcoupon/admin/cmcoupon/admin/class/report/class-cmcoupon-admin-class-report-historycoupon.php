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
class CmCoupon_Admin_Class_Report_Historycoupon extends CmCoupon_Admin_Class_Report {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'reporthistorycoupon';
		$this->title = AC()->lang->__( 'History of Uses' ) . ' - ' . AC()->lang->__( 'Coupons' );
		$start_date = AC()->helper->get_request( 'start_date' );
		$end_date = AC()->helper->get_request( 'end_date' );
		$this->filename = 'history_uses_coupons_' . ( ! empty( $start_date ) || ! empty( $end_date ) ? str_replace( '-', '', $start_date ) . '-' . str_replace( '-', '', $end_date ) : '' ) . '.csv';
		$this->_orderby = '';
		$this->_primary = '';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'coupon_code_str' => AC()->lang->__( 'Coupon Code' ),
			'user_id' => AC()->lang->__( 'User ID' ),
			'username' => AC()->lang->__( 'Username' ),
			'last_name' => AC()->lang->__( 'Last Name' ),
			'first_name' => AC()->lang->__( 'First Name' ),
			'discountstr' => AC()->lang->__( 'Discount' ),
			'order_number' => AC()->lang->__( 'Order Number' ),
			'order_date' => AC()->lang->__( 'Order Date' ),
			'order_total' => AC()->lang->__( 'Order Total' ),
			'order_subtotal' => AC()->lang->__( 'Subtotal' ),
			'order_tax' => AC()->lang->__( 'Tax Total' ),
			'order_shipment' => AC()->lang->__( 'Shipping' ),
			'order_shipment_tax' => AC()->lang->__( 'Shipping Tax' ),
			'order_fee' => AC()->lang->__( 'Fee' ),
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

		$function_type = AC()->helper->get_request( 'function_type' );
		$coupon_value_type = AC()->helper->get_request( 'coupon_value_type' );
		$discount_type = AC()->helper->get_request( 'discount_type' );
		$published = AC()->helper->get_request( 'published' );
		$start_date = AC()->helper->get_request( 'start_date' );
		$end_date = AC()->helper->get_request( 'end_date' );
		$order_status = AC()->helper->get_request( 'order_status' );
		$template = (int) AC()->helper->get_request( 'templatelist' );

		$where = array();
		if ( ! empty( $function_type ) ) {
			$where[] = 'c.function_type="' . $function_type . '"';
		}
		if ( ! empty( $coupon_value_type ) ) {
			$where[] = 'c.coupon_value_type="' . $coupon_value_type . '"';
		}
		if ( ! empty( $discount_type ) ) {
			$where[] = 'c.discount_type="' . $discount_type . '"';
		}
		if ( ! empty( $template ) ) {
			$where[] = 'c.template_id="' . $template . '"';
		}

		if ( ! empty( $published ) ) {
			if ( 'balance' === $publised ) {
				$where[] = 'is_customer_balance=1';
			} else {
				$where[] = 'c.state="' . $published . '"';
			}
		}

		$sql = AC()->store->rpt_history_uses_coupons( $start_date, $end_date, $order_status, $where );

		return $sql;
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
			$row->order_date = ! empty( $row->created_on ) ? AC()->helper->get_date( $row->created_on, 'Y-m-d' ) : '';
			$row->order_number = ! empty( $row->order_id ) ? sprintf( '%08d', $row->order_id ) : '';
			$row->order_total = ! empty( $row->order_total ) ? number_format( $row->order_total, 2 ) : '';
			$row->order_subtotal = ! empty( $row->order_subtotal ) ? number_format( $row->order_subtotal, 2 ) : '';
			$row->order_tax = ! empty( $row->order_tax ) ? number_format( $row->order_tax, 2 ) : '';
			$row->order_shipment = ! empty( $row->order_shipment ) ? number_format( $row->order_shipment, 2 ) : '';
			$row->order_shipment_tax = ! empty( $row->order_shipment_tax ) ? number_format( $row->order_shipment_tax, 2 ) : '';
			$row->order_fee = ! empty( $row->order_fee ) ? number_format( $row->order_fee, 2 ) : '';
			$row->discountstr = number_format( $row->discount, 2 );
			$row->coupon_code_str = $row->coupon_entered_code . ( $row->coupon_id !== $row->coupon_entered_id ? ' (' . $row->coupon_code . ')' : '' );
			$this->_data[ $row->num_uses_id ] = $row;
		}
		return $this->_data;
	}
}
