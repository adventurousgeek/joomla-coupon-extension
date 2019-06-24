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
class CmCoupon_Admin_Class_Giftcertcode extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'giftcertcode';
		$this->_id = $id;
		$this->_orderby = 'product_name';
		$this->_primary = 'product_name';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" onclick="jQuery(this.form).find(\'td.checkcolumn input:checkbox\').prop(\'checked\',this.checked);" />',
			'product_name' => AC()->lang->__( 'Product' ),
			'code' => AC()->lang->__( 'Code' ),
			'status' => AC()->lang->__( 'Status' ),
			'id' => AC()->lang->__( 'ID' ),
		);
		return $columns;
	}

	/**
	 * Sortable columns
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'id'  => 'g.id',
			'product_name' => '_product_name',
			'code' => 'g.code',
			'status' => 'g.status',
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
			'publish' => '<a href="#/cmcoupon/giftcertcode?task=activate&id=' . $row->id . '">' . AC()->lang->__( 'Activate' ) . '</a>',
			'unpublish' => '<a href="#/cmcoupon/giftcertcode?task=markused&id=' . $row->id . '">' . AC()->lang->__( 'Mark Used' ) . '</a>',
			'delete' => '<a href="#/cmcoupon/giftcertcode?task=delete&id=' . $row->id . '" class="submitdelete aria-button-if-js" onclick=\'return showNotice.warn();\'>' . AC()->lang->__( 'Delete' ) . '</a>',
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
	 * Product column
	 *
	 * @param object $row the object.
	 */
	public function column_product_name( $row ) {
		return $row->_product_name;
	}

	/**
	 * Status column
	 *
	 * @param object $row the object.
	 */
	public function column_status( $row ) {
		return AC()->helper->vars( 'status', $row->status );
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		// Get the WHERE, and ORDER BY clauses for the query.
		$where = $this->buildquery_where();
		$orderby = $this->buildquery_orderby();
		$having = $this->buildquery_having();

		$sql = AC()->store->sql_giftcert_code( $where, $having, $orderby );
		return $sql;
	}

	/**
	 * Query where clause
	 */
	public function buildquery_where() {

		$filter_state = AC()->helper->get_userstate_request( $this->name . '.filter_state', 'filter_state', '' );
		$filter_product = AC()->helper->get_userstate_request( $this->name . '.filter_product', 'filter_product', '' );

		$where = array();

		if ( $filter_state ) {
			$where[] = 'g.status="' . $filter_state . '"';
		}
		if ( $filter_product ) {
			$where[] = 'g.product_id="' . (int) $filter_product . '"';
		}

		return $where;
	}

	/**
	 * Query having clause
	 */
	public function buildquery_having() {
		$search = AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' );

		$having = array();
		if ( ! empty( $search ) ) {
			$search = '"%' . AC()->db->escape( $search, true ) . '%"';
			$having[] = ' LOWER(_product_name) LIKE ' . $search . ' OR LOWER(g.note) LIKE ' . $search . ' OR LOWER(g.code) LIKE ' . $search;
		}

		return $having;
	}

	/**
	 * Publish or unpublish item
	 *
	 * @param array $cids the values.
	 * @param int   $publish publish or unpublish.
	 */
	public function publish( $cids = array(), $publish = 'active' ) {
		if ( ! in_array( $publish, array( 'active', 'inactive', 'used' ), true ) ) {
			return false;
		}

		$cids = AC()->helper->scrubids( $cids );
		if ( empty( $cids ) ) {
			return true;
		}

		AC()->db->query( 'UPDATE #__cmcoupon_giftcert_code SET status = "' . $publish . '" WHERE id IN (' . $cids . ')' );

		return true;
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

		AC()->db->query( 'DELETE FROM #__cmcoupon_giftcert_code WHERE id IN (' . $cids . ')' );

		return true;
	}

	/**
	 * Get item properties
	 */
	public function get_entry() {
		$this->_entry = new stdClass();
		$this->_entry->product_id = '';
		$this->_entry->codes = '';

		return $this->_entry;
	}

	/**
	 * Save item
	 *
	 * @param array $data the data to save.
	 */
	public function save( $data ) {
		$errors = array();

		$row = new stdClass();
		$row->product_id = isset( $data['product_id'] ) ? $data['product_id'] : 0;
		$row->codes = isset( $data['codes'] ) ? explode( "\r\n", $data['codes'] ) : array();

		// Make sure the data is valid.
		$tmperr = $this->validate( $row, $data );
		foreach ( $tmperr as $err ) {
			$errors[] = $err;
		}

		// Take a break and return if there are any errors.
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		foreach ( $row->codes as $code ) {
			$sql_arr[] = '("' . CMCOUPON_ESTORE . '",' . $row->product_id . ',"' . $code . '","active",NULL)';
		}

		$len = count( $sql_arr );
		for ( $i = 0; $i < $len; $i = $i + 300 ) {
			AC()->db->query( 'INSERT INTO #__cmcoupon_giftcert_code (estore,product_id,code,status,note) VALUES ' . implode( ',', array_slice( $sql_arr, $i, 300 ) ) );
		}
	}

	/**
	 * Check item before saving
	 *
	 * @param object $row table row.
	 * @param array  $post data turned in.
	 */
	public function validate( $row, $post ) {

		$err = array();

		if ( empty( $row->product_id ) || ! AC()->helper->pos_int( $row->product_id ) ) {
			$err[] = AC()->lang->__( 'Product' ) . ': ' . AC()->lang->__( 'Select an Item' );
		}
		if ( empty( $row->codes ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Codes' ) );
		}
		if ( empty( $err ) ) {

			$_map_code = AC()->db->get_objectlist( 'SELECT code FROM #__cmcoupon_giftcert_code WHERE product_id=' . $row->product_id , 'code' );

			$datadistinct = array();
			foreach ( $row->codes as $code ) {
				if ( isset( $datadistinct[ $code ] ) ) {
					$err[] = $code . ': ' . AC()->lang->__( 'That coupon code already exists. Please try again' );
					continue;
				}
				$datadistinct[ $code ] = 1;

				if ( isset( $_map_code[ $code ] ) ) {
					$err[] = $code . ': ' . AC()->lang->__( 'That coupon code already exists. Please try again' );
				}
			}
		}
		return $err;
	}

}
