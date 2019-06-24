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
class CmCoupon_Admin_Class_History_Coupon extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'historycoupon';
		$this->_id = $id;
		$this->_orderby = 'order_date';
		$this->_orderby_dir = 'desc';
		$this->_primary = 'coupon_code';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" onclick="jQuery(this.form).find(\'td.checkcolumn input:checkbox\').prop(\'checked\',this.checked);" />',
			'coupon_code' => AC()->lang->__( 'Coupon Code' ),
			'user_email' => AC()->lang->__( 'E-mail' ),
			'user_id' => AC()->lang->__( 'User ID' ),
			'username' => AC()->lang->__( 'Username' ),
			'lastname' => AC()->lang->__( 'Last Name' ),
			'firstname' => AC()->lang->__( 'First Name' ),
			'discount' => AC()->lang->__( 'Discount' ),
			'order_number' => AC()->lang->__( 'Order Number' ),
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
			'id'  => 'uu.id',
			'coupon_code' => 'c.coupon_code',
			'user_id' => 'uu.user_id',
			'user_email' => 'uu.user_email',
			'username' => '_username',
			'lastname' => '_lname',
			'firstname' => '_fname',
			'discount' => 'discount',
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
			'delete' => '<a href="#/cmcoupon/history?task=couponDelete&id=' . $row->use_id . '" class="submitdelete aria-button-if-js" onclick=\'return showNotice.warn();\'>' . AC()->lang->__( 'Delete' ) . '</a>',
		);
	}

	/**
	 * Checkbox column
	 *
	 * @param object $row the object.
	 */
	public function column_cb( $row ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%1$s" />', $row->use_id );
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
		return $row->use_id;
	}

	/**
	 * Coupon code column
	 *
	 * @param object $row the object.
	 */
	public function column_coupon_code( $row ) {
		$extra = $row->coupon_id !== $row->coupon_entered_id ? ' (' . $row->coupon_code . ')' : '';
		if ( 1 === (int) $row->is_customer_balance ) {
			$extra = ' (' . AC()->lang->__( 'Gift Certificate Balance' ) . ')';
		}
		return $row->coupon_entered_code . $extra;
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
	 * Lastname column
	 *
	 * @param object $row the object.
	 */
	public function column_lastname( $row ) {
		return $row->_lname;
	}

	/**
	 * Firstname column
	 *
	 * @param object $row the object.
	 */
	public function column_firstname( $row ) {
		return $row->_fname;
	}

	/**
	 * Discount column
	 *
	 * @param object $row the object.
	 */
	public function column_discount( $row ) {
		return AC()->storecurrency->format_currencycode( $row->discount_in_currency, $row->currency_code );
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

		$sql = AC()->store->sql_history_coupon( $where, $having, $orderby );

		return $sql;
	}

	/**
	 * Query where clause
	 */
	public function buildquery_where() {
		$filter_state = AC()->helper->get_userstate_request( $this->name . '.filter_state', 'filter_state', '' );
		$filter_coupon_value_type = AC()->helper->get_userstate_request( $this->name . '.filter_coupon_value_type', 'filter_coupon_value_type', '' );
		$filter_discount_type = AC()->helper->get_userstate_request( $this->name . '.filter_discount_type', 'filter_discount_type', '' );
		$filter_function_type = AC()->helper->get_userstate_request( $this->name . '.filter_function_type', 'filter_function_type', '' );
		$filter_tag = AC()->helper->get_userstate_request( $this->name . '.filter_tag', 'filter_tag', '' );
		$search = strtolower( AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' ) );
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
				$where[] = 'is_customer_balance=1';
			} else {
				$where[] = 'c.state="' . AC()->db->escape( $filter_state ) . '"';
			}
		}
		if ( $filter_coupon_value_type ) {
			$where[] = 'c.coupon_value_type = \'' . $filter_coupon_value_type . '\'';
		}
		if ( $filter_discount_type ) {
			$where[] = 'c.discount_type = \'' . $filter_discount_type . '\'';
		}
		if ( $filter_function_type ) {
			$where[] = 'c.function_type = \'' . $filter_function_type . '\'';
		}
		if ( $filter_tag ) {
			$where[] = 't.tag = \'' . $filter_tag . '\'';
		}
		if ( $search ) {
			if ( 'coupon' === $search_type ) {
				$where[] = 'LOWER(c.coupon_code) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			} elseif ( 'email' === $search_type ) {
				$where[] = 'LOWER(uu.user_email) LIKE "%' . AC()->db->escape( $search, true ) . '%"';
			}
		}

		return $where;
	}

	/**
	 * Query having clause
	 */
	public function buildquery_having() {
		$search = strtolower( AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' ) );
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

		AC()->db->query( 'DELETE FROM #__cmcoupon_history WHERE id IN (' . $cids . ')' );

		return true;
	}

	/**
	 * Get item properties
	 */
	public function get_entry() {
		$this->_entry = AC()->db->get_table_columns( '#__cmcoupon_history' );
		$this->_entry->username = '';
		$this->_entry->coupon_coupon = '';
		$this->_entry->total_product = '';
		$this->_entry->total_shipping = '';
		$this->_entry->total_curr_product = '';
		$this->_entry->total_curr_shipping = '';

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
		$data['coupon_entered_id'] = null;
		$data['productids'] = null;
		if ( empty( $data['coupon_id'] ) ) {
			$data['coupon_id'] = 0;
		}
		if ( empty( $data['total_product'] ) ) {
			$data['total_product'] = 0;
		}
		if ( empty( $data['total_shipping'] ) ) {
			$data['total_shipping'] = 0;
		}
		if ( empty( $data['total_curr_product'] ) ) {
			$data['total_curr_product'] = 0;
		}
		if ( empty( $data['total_curr_shipping'] ) ) {
			$data['total_curr_shipping'] = 0;
		}
		if ( empty( $data['order_id'] ) ) {
			$data['order_id'] = null;
		}

		if ( ! empty( $data['user_id'] ) ) {
			$user = AC()->helper->get_user( $data['user_id'] );
			$data['user_id'] = empty( $user->id ) ? 0 : $user->id;
		}

		$row = AC()->db->get_table_instance( '#__cmcoupon_history', 'id', (int) $data['id'] );
		$row = AC()->db->bind_table_instance( $row, $data );
		if ( ! $row ) {
			$errors[] = AC()->lang->__( 'Unable to bind item' );
		}

		$row->estore = CMCOUPON_ESTORE;
		if ( empty( $row->total_product ) ) {
			$row->total_product = 0;
		}
		if ( empty( $row->total_shipping ) ) {
			$row->total_shipping = 0;
		}
		if ( empty( $row->total_curr_product ) ) {
			$row->total_curr_product = 0;
		}
		if ( empty( $row->total_curr_shipping ) ) {
			$row->total_curr_shipping = 0;
		}

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
		$row = AC()->db->save_table_instance( '#__cmcoupon_history', $row );

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

		if ( ! empty( $post['order_id'] ) ) {
			$tmp = AC()->store->get_order( $post['order_id'] );
			if ( empty( $tmp ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Order ID' ) );
			}
		}

		$coupon_row = AC()->db->get_object( 'SELECT * FROM #__cmcoupon WHERE id=' . (int) $post['coupon_id'] . ' AND estore="' . CMCOUPON_ESTORE . '" AND state="published" AND function_type!="combination"' );
		if ( empty( $coupon_row->id ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon Code' ) );
		}

		if ( 'giftcert' === $coupon_row->function_type ) {
			// Check balance.
			$sql = 'SELECT c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance
					 FROM #__cmcoupon c
					 LEFT JOIN #__cmcoupon_history au ON au.coupon_id=c.id
					WHERE c.id=' . $coupon_row->id . '
					GROUP BY c.id';
			$balance = (float) AC()->db->get_value( $sql );

			if ( ( $post['total_product'] + $post['total_shipping'] ) > $balance ) {
				$err[] = AC()->lang->__( 'Gift Certificate Balance' ) . ': ' . number_format( $balance, 2 );
			}
		}

		if ( empty( $row->coupon_id ) || ! AC()->helper->pos_int( $row->coupon_id ) ) {
			$err[] = AC()->lang->_e_select( AC()->lang->__( 'Coupon' ) );
		}
		if ( ! empty( $row->user_id ) && ! AC()->helper->pos_int( $row->user_id ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Username' ) );
		}
		if ( empty( $row->user_email ) || ! AC()->helper->is_email( $row->user_email ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'E-mail' ) );
		}
		if ( ! empty( $row->total_product ) && ( ! is_numeric( $row->total_product ) || $row->total_product < 0 ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Product Discount' ) );
		}
		if ( ! empty( $row->total_shipping ) && ( ! is_numeric( $row->total_shipping ) || $row->total_shipping < 0 ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Shipping Discount' ) );
		}
		if ( ! empty( $row->total_curr_product ) && ( ! is_numeric( $row->total_curr_product ) || $row->total_curr_product < 0 ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Product Discount in Currency' ) );
		}
		if ( ! empty( $row->total_curr_shipping ) && ( ! is_numeric( $row->total_curr_shipping ) || $row->total_curr_shipping < 0 ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Shipping Discount in Currency' ) );
		}

		return $err;
	}
}
