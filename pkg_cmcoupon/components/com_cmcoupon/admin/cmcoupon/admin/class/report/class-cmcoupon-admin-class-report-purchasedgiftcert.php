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
class CmCoupon_Admin_Class_Report_Purchasedgiftcert extends CmCoupon_Admin_Class_Report {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'reportpurchasedgiftcert';
		$this->title = AC()->lang->__( 'Purchased Gift Certificate List' );
		$start_date = AC()->helper->get_request( 'start_date' );
		$end_date = AC()->helper->get_request( 'end_date' );
		$this->filename = 'purchased_giftcert_list_' . ( ! empty( $start_date ) || ! empty( $end_date ) ? str_replace( '-', '', $start_date ) . '-' . str_replace( '-', '', $end_date ) : '' ) . '.csv';
		$this->_orderby = '';
		$this->_primary = '';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'coupon_code' => AC()->lang->__( 'Coupon Code' ),
			'product_name' => AC()->lang->__( 'Product' ),
			'coupon_valuestr' => AC()->lang->__( 'Value' ),
			'expiration' => AC()->lang->__( 'Expiration' ),
			'user_id' => AC()->lang->__( 'User ID' ),
			'username' => AC()->lang->__( 'Username' ),
			'last_name' => AC()->lang->__( 'Last Name' ),
			'first_name' => AC()->lang->__( 'First Name' ),
			'email' => AC()->lang->__( 'E-mail' ),
			'order_number' => AC()->lang->__( 'Order Number' ),
			'order_date' => AC()->lang->__( 'Order Date' ),
			'order_total' => AC()->lang->__( 'Order Total' ),
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

		$this->_data = new stdClass();
		$published = AC()->helper->get_request( 'published' );
		$start_date = AC()->helper->get_request( 'start_date' );
		$end_date = AC()->helper->get_request( 'end_date' );
		$order_status = AC()->helper->get_request( 'order_status' );
		$giftcert_product = (int) AC()->helper->get_request( 'giftcert_product' );

		$sql = AC()->store->rpt_purchased_giftcert_list( $start_date, $end_date, $order_status, $published );
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
			$row->order_date = ! empty( $row->created_on ) ? AC()->helper->get_date( $row->created_on,'Y-m-d' ) : '';
			$row->order_number = ! empty( $row->order_id ) ? sprintf( '%08d', $row->order_id ) : '';
			$row->order_total = ! empty( $row->order_total ) ? number_format( $row->order_total, 2 ) : '';
			$row->order_subtotal = ! empty( $row->order_subtotal ) ? number_format( $row->order_subtotal, 2 ) : '';
			$row->order_tax = ! empty( $row->order_tax ) ? number_format( $row->order_tax, 2 ) : '';
			$row->order_shipment = ! empty( $row->order_shipment ) ? number_format( $row->order_shipment, 2 ) : '';
			$row->order_shipment_tax = ! empty( $row->order_shipment_tax ) ? number_format( $row->order_shipment_tax, 2 ) : '';
			$row->order_fee = ! empty( $row->order_fee ) ? number_format( $row->order_fee, 2 ) : '';
			$row->coupon_valuestr = number_format( $row->coupon_value, 2 );
			$this->_data[ $row->id ] = $row;
		}
		return $this->_data;
	}
}
