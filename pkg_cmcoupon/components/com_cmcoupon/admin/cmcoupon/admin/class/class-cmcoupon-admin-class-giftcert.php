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
class CmCoupon_Admin_Class_Giftcert extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'giftcert';
		$this->_id = $id;
		$this->_orderby = 'product';
		$this->_primary = 'product';
		parent::__construct();

	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" onclick="jQuery(this.form).find(\'td.checkcolumn input:checkbox\').prop(\'checked\',this.checked);" />',
			'product' => AC()->lang->__( 'Product' ),
			'template' => AC()->lang->__( 'Coupon Template' ),
			'image' => AC()->lang->__( 'Email Image' ),
			'codes' => AC()->lang->__( 'Codes' ),
			'expiration' => AC()->lang->__( 'Expiration' ),
			'vendor' => AC()->lang->__( 'Vendor' ),
			'published' => AC()->lang->__( 'Published' ),
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
			'product' => '_product_name',
			'template' => 'c.coupon_code',
			'image' => 'pr.title',
			'vendor' => 'g.vendor_name',
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
			'edit' => '<a href="#/cmcoupon/giftcert/edit?id=' . $row->id . '">' . AC()->lang->__( 'Edit' ) . '</a>',
			'delete' => '<a href="#/cmcoupon/giftcert?task=delete&id=' . $row->id . '" class="submitdelete aria-button-if-js" onclick=\'return showNotice.warn();\'>' . AC()->lang->__( 'Delete' ) . '</a>',
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
	public function column_product( $row ) {
		return $row->_product_name;
	}

	/**
	 * Template column
	 *
	 * @param object $row the object.
	 */
	public function column_template( $row ) {
		return $row->coupon_code;
	}

	/**
	 * Image column
	 *
	 * @param object $row the object.
	 */
	public function column_image( $row ) {
		return $row->profile;
	}

	/**
	 * Code column
	 *
	 * @param object $row the object.
	 */
	public function column_codes( $row ) {
		$view = ! empty( $row->codecount ) ? '[ <a href="#/cmcoupon/giftcertcode?filter_product=' . $row->product_id . '&filter_state=&search=" ><span>' . AC()->lang->__( 'View' ) . '</span></a> ]' : '';
		return $row->codecount . ' ' . $view;
	}

	/**
	 * Expiration column
	 *
	 * @param object $row the object.
	 */
	public function column_expiration( $row ) {
		$expiration = ! empty( $row->expiration_number ) && ! empty( $row->expiration_type )
					? $row->expiration_number . ' ' . AC()->helper->vars( 'expiration_type', $row->expiration_type )
					: '';
		return $expiration;
	}

	/**
	 * Vendor column
	 *
	 * @param object $row the object.
	 */
	public function column_vendor( $row ) {
		return $row->vendor_name . ( ! empty( $row->vendor_email ) ? ' &lt;' . $row->vendor_email . '&gt;' : '' );
	}

	/**
	 * Published column
	 *
	 * @param object $row the object.
	 */
	public function column_published( $row ) {
		if ( 1 === (int) $row->published ) {
			$img = CMCOUPON_ASEET_URL . '/images/published.png';
			$alt = AC()->lang->__( 'Published' );
			$link = '#/cmcoupon/giftcert?task=unpublish&id=' . $row->id;
		} else {
			$img = CMCOUPON_ASEET_URL . '/images/unpublished.png';
			$alt = AC()->lang->__( 'Unpublished' );
			$link = '#/cmcoupon/giftcert?task=publish&id=' . $row->id;
		}
		return '<a href="' . $link . '"><img src="' . $img . '" width="16" height="16" class="hand" border="0" alt="' . $alt . '" title="' . $alt . '"/></a>';
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		// Get the WHERE, and ORDER BY clauses for the query.
		$where = $this->buildquery_where();
		$orderby = $this->buildquery_orderby();
		$having = $this->buildquery_having();

		$sql = AC()->store->sql_giftcert_product( $where, $having, $orderby );
		return $sql;
	}

	/**
	 * Query where clause
	 */
	public function buildquery_where() {

		$filter_state = AC()->helper->get_userstate_request( $this->name . '.filter_state', 'filter_state', '' );

		$where = array();
		if ( $filter_state ) {
			$where[] = 'g.published=' . (int) $filter_state;
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
			$search = '%' . AC()->db->escape( $search, true ) . '%';
			$having[] = ' _product_name LIKE "' . $search . '" OR c.coupon_code LIKE "' . $search . '" ';
		}

		return $having;
	}

	/**
	 * Publish or unpublish item
	 *
	 * @param array $cid the values.
	 * @param int   $publish publish or unpublish.
	 */
	public function publish( $cid = array(), $publish = 1 ) {

		if ( count( $cid ) ) {
			AC()->db->query( 'UPDATE #__cmcoupon_giftcert_product SET published = ' . (int) $publish . ' WHERE id IN (' . AC()->helper->scrubids( $cid ) . ') AND published IN (-1,1)' );
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

		AC()->db->query( 'DELETE FROM #__cmcoupon_giftcert_product WHERE id IN (' . $cids . ')' );

		return true;
	}

	/**
	 * Get item properties
	 */
	public function get_entry() {
		$this->_entry = AC()->db->get_table_instance( '#__cmcoupon_giftcert_product', 'id', $this->_id );

		if ( ! empty( $this->_entry->id ) ) {

			if ( ! empty( $this->_entry->id ) ) {
				$tmp = AC()->db->get_object( AC()->store->sql_giftcert_product_single( $this->_entry->product_id ) );
				$this->_entry->product_name = '';
				$this->_entry->product_sku = '';
				if ( ! empty( $tmp ) ) {
					$this->_entry->product_name = $tmp->product_name;
					$this->_entry->product_sku = $tmp->product_sku;
				}
			}
		} else {
			$entry = new stdClass();

			$this->_entry = AC()->db->get_table_columns( '#__cmcoupon_giftcert_product' );
			$this->_entry->product_name = '';
			$this->_entry->product_sku = '';
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

		// Set null fields.
		if ( empty( $data['profile_id'] ) ) {
			$data['profile_id'] = null;
		}
		if ( empty( $data['expiration_number'] ) ) {
			$data['expiration_number'] = null;
		}
		if ( empty( $data['expiration_type'] ) ) {
			$data['expiration_type'] = null;
		}
		if ( empty( $data['vendor_name'] ) ) {
			$data['vendor_name'] = null;
		}
		if ( empty( $data['vendor_email'] ) ) {
			$data['vendor_email'] = null;
		}
		if ( empty( $data['price_calc_type'] ) || 'template' === $data['price_calc_type'] ) {
			$data['price_calc_type'] = null;
		}

		$row = AC()->db->get_table_instance( '#__cmcoupon_giftcert_product', 'id', (int) $data['id'] );
		$row = AC()->db->bind_table_instance( $row, $data );
		if ( ! $row ) {
			$errors[] = AC()->lang->__( 'Unable to bind item' );
		}

		// Sanitise fields.
		$row->id = (int) $row->id;
		$row->estore = CMCOUPON_ESTORE;

		// Make sure the data is valid.
		$tmperr = $this->validate( $row, $data );
		foreach ( $tmperr as $err ) {
			$errors[] = $err;
		}

		// Take a break and return if there are any errors.
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		// Store the entry to the database.
		$row = AC()->db->save_table_instance( '#__cmcoupon_giftcert_product', $row );

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

		if ( empty( $row->product_id ) || ! AC()->helper->pos_int( $row->product_id ) ) {
			$err[] = AC()->lang->__( 'Product' ) . ': ' . AC()->lang->__( 'Select an Item' );
		}
		if ( empty( $row->coupon_template_id ) || ! AC()->helper->pos_int( $row->coupon_template_id ) ) {
			$err[] = AC()->lang->__( 'Coupon Template' ) . ': ' . AC()->lang->__( 'Select an Item' );
		}
		if ( ! empty( $row->price_calc_type ) && ! in_array( $row->price_calc_type, array( 'product_price_notax', 'product_price' ), true ) ) {
			$err[] = AC()->lang->_e_select( AC()->lang->__( 'Price Calculation Type' ) );
		}
		if ( ! empty( $row->profile_id ) && ! AC()->helper->pos_int( $row->profile_id ) ) {
			$err[] = AC()->lang->_e_select( AC()->lang->__( 'Email Image' ) );
		}
		if ( ! empty( $row->expiration_number ) || ! empty( $row->expiration_type ) ) {

			if ( empty( $row->expiration_number ) || empty( $row->expiration_type ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Expiration' ) );
			} elseif ( ! AC()->helper->pos_int( $row->expiration_number ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Expiration' ) );
			} elseif ( ! in_array( $row->expiration_type, array( 'day', 'month', 'year' ), true ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Expiration' ) );
			}
		}

		if ( empty( $row->published ) || ! in_array( (int) $row->published, array( 1, -1 ), true ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Published' ) );
		}

		return $err;
	}
}

