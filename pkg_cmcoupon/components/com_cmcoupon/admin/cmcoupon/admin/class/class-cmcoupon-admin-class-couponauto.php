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
class CmCoupon_Admin_Class_Couponauto extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id coupon_id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'couponauto';
		$this->_id = $id;
		$this->_orderby = 'ordering';
		$this->_primary = 'coupon_code';
		parent::__construct();

	}

	/**
	 * Columns
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" onclick="jQuery(this.form).find(\'td.checkcolumn input:checkbox\').prop(\'checked\',this.checked);" />',
			'coupon_code' => AC()->lang->__( 'Coupon Code' ),
			'function_type' => AC()->lang->__( 'Function Type' ),
			'coupon_value_type' => AC()->lang->__( 'Value Type' ),
			'coupon_value' => AC()->lang->__( 'Value' ),
			'num_uses' => AC()->lang->__( 'Number of Uses' ),
			'discount_type' => AC()->lang->__( 'Discount Type' ),
			'ordering' => AC()->lang->__( 'Ordering' ),
			'published' => AC()->lang->__( 'Published' ),
			'id' => AC()->lang->__( 'ID' ),
		);
		return $columns;
	}

	/**
	 * Sort columns
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'id'  => 'a.id',
			'coupon_code' => 'c.coupon_code',
			'function_type' => 'c.function_type',
			'coupon_value_type' => 'c.coupon_value_type',
			'coupon_value' => 'c.coupon_value',
			'num_uses' => 'num_of_uses_order',
			'discount_type' => 'c.discount_type',
			'ordering' => 'a.ordering',
		);
		return $sortable_columns;
	}

	/**
	 * Column action row
	 *
	 * @param object $row the data.
	 */
	protected function get_row_action( $row ) {
		return array(
			'delete' => '<a href="#/cmcoupon/couponauto?task=delete&id=' . $row->id . '" class="submitdelete aria-button-if-js" onclick=\'return showNotice.warn();\'>' . AC()->lang->__( 'Delete' ) . '</a>',
		);
	}

	/**
	 * Column checkbox
	 *
	 * @param object $row the data.
	 */
	public function column_cb( $row ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%1$s" />', $row->id );
	}

	/**
	 * Column default
	 *
	 * @param object $item the data.
	 * @param string $column_name the name.
	 */
	public function column_default( $item, $column_name ) {
		return $item->{$column_name};
	}

	/**
	 * Column function type
	 *
	 * @param object $row the data.
	 */
	public function column_function_type( $row ) {
		return AC()->helper->vars( 'function_type', $row->function_type );
	}

	/**
	 * Column coupon value
	 *
	 * @param object $row the data.
	 */
	public function column_coupon_value( $row ) {
		if ( 'combination' === $row->function_type ) {
			return '--';
		} else {
			$coupon_value_type_ = '';
			if ( ! empty( $row->coupon_value_type ) ) {
				$coupon_value_type_ = 'percent' === $row->coupon_value_type ? '%' : ' ' . AC()->helper->vars( 'coupon_value_type', $row->coupon_value_type );
			}
			return ! empty( $row->coupon_value )
					? number_format( $row->coupon_value, 2 ) . $coupon_value_type_
					: AC()->coupon->get_value_print( $row->coupon_value_def, $row->coupon_value_type );
		}
	}

	/**
	 * Column coupon value
	 *
	 * @param object $row the data.
	 */
	public function column_num_uses( $row ) {
		$num_of_uses = '--';
		$discount_type = '--';
		$min_value = '--';
		if ( 'giftcert' !== $row->function_type ) {
			if ( empty( $row->num_of_uses_total ) && empty( $row->num_of_uses_customer ) ) {
				$num_of_uses = AC()->lang->__( 'Unlimited' );
			} else {
				$num_of_uses = '';
				if ( ! empty( $row->num_of_uses_total ) ) {
					$num_of_uses .= '<div>' . $row->num_of_uses_total . ' ' . AC()->helper->vars( 'num_of_uses_type', 'total' ) . '</div>';
				}
				if ( ! empty( $row->num_of_uses_customer ) ) {
					$num_of_uses .= '<div>' . $row->num_of_uses_customer . ' ' . AC()->helper->vars( 'num_of_uses_type', 'per_user' ) . '</div>';
				}
			}
		}
		return $num_of_uses;
	}

	/**
	 * Column coupon value
	 *
	 * @param object $row the data.
	 */
	public function column_discount_type( $row ) {
		$discount_type = '--';
		if ( ! in_array( $row->function_type, array( 'giftcert', 'combination' ), true ) ) {
			if ( ! empty( $row->discount_type ) ) {
				$discount_type = AC()->helper->vars( 'discount_type', $row->discount_type );
			}
		}
		return $discount_type;
	}

	/**
	 * Column coupon value
	 *
	 * @param object $row the data.
	 */
	public function column_published( $row ) {
		if ( 1 === (int) $row->published ) {
			$img = CMCOUPON_ASEET_URL . '/images/published.png';
			$alt = AC()->lang->__( 'Published' );
			$link = '#/cmcoupon/couponauto?task=unpublish&id=' . $row->id;
		} else {
			$img = CMCOUPON_ASEET_URL . '/images/unpublished.png';
			$alt = AC()->lang->__( 'Unpublished' );
			$link = '#/cmcoupon/couponauto?task=publish&id=' . $row->id;
		}
		return '<a href="' . $link . '"><img src="' . $img . '" width="16" height="16" class="hand" border="0" alt="' . $alt . '" title="' . $alt . '"/></a>';
	}

	/**
	 * The query
	 */
	public function buildquery() {
		$where = $this->buildquery_where();
		$orderby = $this->buildquery_orderby();
		if ( ! empty( $orderby ) ) {
			$orderby = ' ORDER BY ' . $orderby . ' ';
		}

		$sql = 'SELECT a.id,a.coupon_id,a.ordering,a.published,c.coupon_code,c.function_type,
						c.coupon_value_type,c.coupon_value,c.coupon_value_def,c.num_of_uses_total,c.num_of_uses_customer,c.discount_type,
						IF(c.function_type="giftcert",
							0,
							IF((c.num_of_uses_customer IS NULL or c.num_of_uses_customer="") AND (c.num_of_uses_total IS NULL or c.num_of_uses_total="") ,
								999999999,
								IFNULL(c.num_of_uses_customer,0) + IFNULL(c.num_of_uses_total,0)
							)
						) AS num_of_uses_order
				  FROM #__cmcoupon_auto a
				  JOIN #__cmcoupon c ON c.id=a.coupon_id
				 WHERE c.estore="' . CMCOUPON_ESTORE . '"
				 ' . $where . '
				 ' . $orderby;
		return $sql;
	}

	/**
	 * Query where clause
	 */
	public function buildquery_where() {
		$filter_state = AC()->helper->get_userstate_request( $this->name . '.filter_state', 'filter_state', '' );
		$search = AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' );

		$where = array();

		if ( $filter_state ) {
			$where[] = 'a.published=' . (int) $filter_state;
		}
		if ( $search ) {
			$where[] = ' LOWER(c.coupon_code) LIKE "%' . AC()->db->escape( $search, true ) . '"';
		}
		$where = count( $where ) ? ' AND ' . implode( ' AND ', $where ) : '';

		return $where;
	}

	/**
	 * Publish or unpublish item
	 *
	 * @param array $cid the values.
	 * @param int   $publish publish or unpublish.
	 */
	public function publish( $cid = array(), $publish = 1 ) {

		if ( count( $cid ) ) {
			AC()->db->query( 'UPDATE #__cmcoupon_auto SET published = ' . (int) $publish . ' WHERE id IN (' . AC()->helper->scrubids( $cid ) . ') AND published IN (-1,1)' );
		}
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

		AC()->db->query( 'DELETE FROM #__cmcoupon_auto WHERE id IN (' . $cids . ')' );

		return true;
	}

	/**
	 * Get item properties
	 */
	public function get_entry() {
		$this->_entry = AC()->db->get_table_instance( '#__cmcoupon_auto', 'id', $this->_id );

		$this->_entry->coupon_code = '';

		if ( ! empty( $this->_entry->id ) ) {
			$this->entry->coupon_code = AC()->lang->get_value( 'SELECT coupon_code FROM #__cmcoupon WHERE estore="' . CMCOUPON_ESTORE . '" AND id=' . $this->_entry->coupon_id );
		} else {
			$entry = new stdClass();

			$this->_entry = AC()->db->get_table_columns( '#__cmcoupon_auto' );
			$this->_entry->coupon_code = '';
		}
		return $this->_entry;
	}

	/**
	 * Save item
	 *
	 * @param array $data the data to save.
	 */
	public function save( $data ) {
		$errors = array();

		$row = AC()->db->get_table_instance( '#__cmcoupon_auto', 'id', (int) $data['id'] );
		$row = AC()->db->bind_table_instance( $row, $data );
		if ( ! $row ) {
			$errors[] = AC()->lang->__( 'Unable to bind item' );
		}

		$row->id = (int) $row->id;
		$row->published = (int) $row->published;
		$row->ordering = (int) AC()->db->get_value( 'SELECT MAX(ordering) FROM #__cmcoupon_auto' ) + 1;

		// Make sure the data is valid.
		$tmperr = $this->validate( $row, $data );
		foreach ( $tmperr as $err ) {
			$errors[] = $err;
		}

		// take a break and return if there are any errors.
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		// Store the entry to the database.
		$row = AC()->db->save_table_instance( '#__cmcoupon_auto', $row );

		// Clean out the products/users tables.
		$this->_entry = $row;
	}

	/**
	 * Check item before saving
	 *
	 * @param object $row table row.
	 * @param array  $post data turned in.
	 */
	public function validate( $row, $post ) {
		$err = array();

		if ( empty( $row->coupon_id ) || ! AC()->helper->pos_int( $row->coupon_id ) ) {
			$err[] = AC()->lang->__( 'Coupon' ) . ': ' . AC()->lang->__( 'Select an Item' );
		}
		if ( empty( $row->published ) || ! in_array( (int) $row->published, array( 1, -1 ), true ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Published' ) );
		}

		return $err;
	}


}

