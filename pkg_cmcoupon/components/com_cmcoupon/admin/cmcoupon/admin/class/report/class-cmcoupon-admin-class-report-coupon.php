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
class CmCoupon_Admin_Class_Report_Coupon extends CmCoupon_Admin_Class_Report {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'reportcoupon';
		$this->title = AC()->lang->__( 'Coupon List' );
		$this->filename = 'coupon_list.csv';
		$this->_orderby = '';
		$this->_primary = '';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		return $this->_columns;
	}

	/**
	 * Default column behavior
	 *
	 * @param object $item the object.
	 * @param string $column_name the column.
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		$function_type = AC()->helper->get_request( 'function_type' );
		$coupon_value_type = AC()->helper->get_request( 'coupon_value_type' );
		$discount_type = AC()->helper->get_request( 'discount_type' );
		$template = (int) AC()->helper->get_request( 'templatelist' );
		$published = AC()->helper->get_request( 'published' );
		$tag = AC()->helper->get_request( 'tag' );

		$sql = 'SELECT c.*
				  FROM #__cmcoupon c
				  LEFT JOIN #__cmcoupon_tag t ON t.coupon_id=c.id
				 WHERE c.estore="' . CMCOUPON_ESTORE . '"
				 ' . ( ! empty( $function_type ) ? 'AND c.function_type="' . $function_type . '" ' : '' ) . '
				 ' . ( ! empty( $coupon_value_type ) ? 'AND c.coupon_value_type="' . $coupon_value_type . '" ' : '' ) . '
				 ' . ( ! empty( $discount_type ) ? 'AND c.discount_type="' . $discount_type . '" ' : '' ) . '
				 ' . ( ! empty( $template ) ? 'AND c.template_id="' . $template . '" ' : '' ) . '
				 ' . ( ! empty( $published ) ? 'AND c.state="' . $published . '" ' : '' ) . '
				 ' . ( ! empty( $tag ) ? 'AND t.tag="' . $tag . '" ' : '' ) . '
				 GROUP BY c.id';

		 return $sql;
	}

	/**
	 * Data list
	 */
	public function get_data() {
		$rtn = isset( $this->_get_all_data ) && true === $this->_get_all_data
					? $this->get_list( $this->buildquery(), 'id' )
					: $this->get_list( $this->buildquery(), 'id', $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );

		$coupon_ids = array();
		$columns = array();

		$this->_data = array();
		foreach ( $rtn as $row ) {
			if ( empty( $coupon_ids ) ) {
				foreach ( $row as $c_key => $c_val ) {
					if ( in_array( $c_key, array( 'params' ), true ) ) {
						continue;
					}
					$columns[ $c_key ] = $c_key;
				}
			}
			$coupon_ids[] = $row->id;
			$row = (array) $row;
			$row['tags'] = array();
			$row['asset_keys'] = array();
			$row['asset_types'] = array();
			$row['asset_ids'] = array();
			$row['asset_qtys'] = array();
			$row['asset_order_bys'] = array();
			$row['params'] = json_decode( $row['params'] );

			$this->_data[ $row['id'] ] = $row;
		}

		if ( ! empty( $coupon_ids ) ) {

			$tmp = AC()->db->get_objectlist( 'SELECT coupon_id,tag FROM #__cmcoupon_tag WHERE coupon_id IN (' . implode( ',', $coupon_ids ) . ')' );
			foreach ( $tmp as $row ) {
				$columns['tags'] = 'tags';
				$this->_data[ $row->coupon_id ]['tags'][] = $row->tag;
			}

			$tmp = AC()->db->get_objectlist( 'SELECT * FROM #__cmcoupon_asset WHERE coupon_id IN (' . implode( ',', $coupon_ids ) . ')' );
			foreach ( $tmp as $row ) {
				$columns['asset_keys'] = 'asset_keys';
				$columns['asset_types'] = 'asset_types';
				$columns['asset_ids'] = 'asset_ids';
				$columns['asset_qtys'] = 'asset_qtys';
				$columns['asset_order_bys'] = 'asset_order_bys';
				$this->_data[ $row->coupon_id ]['asset_keys'][] = $row->asset_key;
				$this->_data[ $row->coupon_id ]['asset_types'][] = $row->asset_type;
				$this->_data[ $row->coupon_id ]['asset_ids'][] = $row->asset_id;
				$this->_data[ $row->coupon_id ]['asset_qtys'][] = $row->qty;
				$this->_data[ $row->coupon_id ]['asset_order_bys'][] = $row->order_by;
			}

			foreach ( $this->_data as $k => $row ) {
				if ( empty( $row['params'] ) ) {
					continue;
				}
				foreach ( $row['params'] as $p_key => $p_value ) {
					if ( 'asset' === $p_key ) {
						foreach ( $p_value as $p_asset_key => $a1 ) {
							if ( ! empty( $a1->qty ) ) {
								$gen_key = 'asset' . $p_asset_key . '_qty';
								$columns[ $gen_key ] = $gen_key;
								$this->_data[ $k ][ $gen_key ] = $a1->qty;
							}
							if ( ! empty( $a1->rows ) ) {
								foreach ( $a1->rows as $p_asset_type => $a2 ) {
									if ( ! empty( $a2->type ) ) {
										$gen_key = 'asset' . $p_asset_key . '_' . $p_asset_type . '_type';
										$columns[ $gen_key ] = $gen_key;
										$this->_data[ $k ][ $gen_key ] = $a2->type;
									}
									if ( ! empty( $a2->mode ) ) {
										$gen_key = 'asset' . $p_asset_key . '_' . $p_asset_type . '_mode';
										$columns[ $gen_key ] = $gen_key;
										$this->_data[ $k ][ $gen_key ] = $a2->mode;
									}
								}
							}
						}
					} else {
						$columns[ $p_key ] = $p_key;
						$this->_data[ $k ][ $p_key ] = $p_value;
					}
				}
			}
			foreach ( $this->_data as $k => $row ) {
				$this->_data[ $k ]['tags'] = implode( '|', $this->_data[ $k ]['tags'] );

				$this->_data[ $k ]['asset_keys'] = implode( '|', $this->_data[ $k ]['asset_keys'] );
				$this->_data[ $k ]['asset_types'] = implode( '|', $this->_data[ $k ]['asset_types'] );
				$this->_data[ $k ]['asset_ids'] = implode( '|', $this->_data[ $k ]['asset_ids'] );
				$this->_data[ $k ]['asset_qtys'] = implode( '|', $this->_data[ $k ]['asset_qtys'] );
				$this->_data[ $k ]['asset_order_bys'] = implode( '|', $this->_data[ $k ]['asset_order_bys'] );
			}
		}
		$this->_columns = $columns;
		return $this->_data;
	}
}
