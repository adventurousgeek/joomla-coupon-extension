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
class CmCoupon_Admin_Class_Report_Coupontotal extends CmCoupon_Admin_Class_Report {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'reportcoupontotal';
		$this->title = AC()->lang->__( 'Coupon Usage vs. Total Sales' );
		$start_date = AC()->helper->get_request( 'start_date' );
		$end_date = AC()->helper->get_request( 'end_date' );
		$this->filename = 'coupon_usage_total_' . ( ! empty( $start_date ) || ! empty( $end_date ) ? str_replace( '-', '', $start_date ) . '-' . str_replace( '-', '', $end_date ) : '' ) . '.csv';
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
			'discountstr' => AC()->lang->__( 'Discount' ),
			'totalstr' => AC()->lang->__( 'Revenue' ),
			'count' => AC()->lang->__( 'Volume' ),
			'alltotal' => AC()->lang->__( '% Revenue' ),
			'allcount' => AC()->lang->__( '% Volumne' ),
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
			$where[] = 'c.state="' . $published . '"';
		}

		$sql = AC()->store->rpt_coupon_vs_total( $start_date, $end_date, $order_status, $where );

		return $sql;
	}

	/**
	 * Data list
	 */
	public function get_data() {
		$order_details = isset( $this->_get_all_data ) && true === $this->_get_all_data
					? $this->get_list( $this->buildquery(), 'id' )
					: $this->get_list( $this->buildquery(), 'id', $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );

		if ( empty( $order_details ) ) {
			return;
		}

		$rtn = AC()->db->get_objectlist('
			SELECT c.id,c.coupon_code, uu.productids,SUM(uu.total_product+uu.total_shipping) as discount
			  FROM #__cmcoupon_history uu
			  JOIN #__cmcoupon c ON c.id=uu.coupon_entered_id 
			 WHERE c.id IN (' . implode( ',', array_keys( $order_details ) ) . ')
			 GROUP BY c.id
			 ORDER BY c.coupon_code
		');

		$this->_data = array();
		$graph = array(
			'total' => 0,
			'count' => 0,
		);
		$productids = array();
		foreach ( $rtn as $row ) {
			$row->total = $order_details[ $row->id ]->total;
			$row->count = $order_details[ $row->id ]->count;

			$graph['total'] += $row->total;
			$graph['count'] += $row->count;

			$row->products = array();
			if ( ! empty( $row->productids ) ) {
				$tmp = explode( ',', $row->productids );
				foreach ( $tmp as $tmprow ) {
					$tmpid = (int) $tmprow;
					$productids[ $tmpid ] = '';
					$row->products[ $tmpid ] = &$productids[ $tmpid ];
				}
			}
			$row->totalstr = number_format( $row->total, 2 );
			$row->discountstr = number_format( $row->discount, 2 );
			$row->alltotal = 0;
			$row->allcount = 0;
			$this->_data[] = $row;
		}
		if ( ! empty( $this->_data ) ) {
			foreach ( $this->_data as $k => $row ) {
				$this->_data[ $k ]->alltotal = empty( $graph['total'] ) ? 0 : round( $this->_data[ $k ]->total / $graph['total'] * 100, 2 ) . '%';
				$this->_data[ $k ]->allcount = empty( $graph['count'] ) ? 0 : round( $this->_data[ $k ]->count / $graph['count'] * 100, 2 ) . '%';
			}

			if ( ! empty( $productids ) ) {
				$tmp = AC()->store->get_products( array_keys( $productids ), null, null, false );
				foreach ( $tmp as $row ) {
					$productids[ $row->id ] = $row->label;
				}
			}
		}

		return $this->_data;
	}
}
