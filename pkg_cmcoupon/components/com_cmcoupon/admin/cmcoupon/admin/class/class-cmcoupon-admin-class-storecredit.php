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
class CmCoupon_Admin_Class_Storecredit extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'storecredit';
		$this->_id = $id;
		$this->_orderby = 'user_login';
		$this->_primary = 'display_name';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'display_name' => AC()->lang->__( 'Name' ),
			'user_login' => AC()->lang->__( 'Username' ),
			'user_email' => AC()->lang->__( 'E-mail' ),
			'total' => AC()->lang->__( 'Total' ),
			'paid' => AC()->lang->__( 'Value Used' ),
			'balance' => AC()->lang->__( 'Balance' ),
			'id' => AC()->lang->__( 'ID' ),
		);
		return $columns;
	}

	/**
	 * Sortable columns
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'id'  => 'id',
			'display_name' => 'display_name',
			'user_login' => 'user_login',
			'user_email' => 'user_email',
			'total' => 'total',
			'paid' => 'paid',
			'balance' => 'balance',
		);
		return $sortable_columns;
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
	 * Total column
	 *
	 * @param object $row the object.
	 */
	public function column_total( $row ) {
		return number_format( (float) $row->total, 2 );
	}

	/**
	 * Paid column
	 *
	 * @param object $row the object.
	 */
	public function column_paid( $row ) {
		return number_format( (float) $row->paid, 2 );
	}

	/**
	 * Balance column
	 *
	 * @param object $row the object.
	 */
	public function column_balance( $row ) {
		return number_format( (float) $row->balance, 2 );
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		$having = $this->buildquery_having();
		$orderby = $this->buildquery_orderby();
		$sql = AC()->store->sql_store_credit( '', $having, $orderby );
		return $sql;
	}

	/**
	 * Query where clause
	 */
	public function buildquery_having() {
		$search = AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' );

		$where = array();

		if ( $search ) {
			$search = '"%' . AC()->db->escape( $search, true ) . '%"';
			$where[] = ' (user_login LIKE ' . $search . ' OR display_name LIKE ' . $search . ' OR user_email LIKE ' . $search . ') ';
		}

		return $where;
	}

	/**
	 * Get item properties
	 */
	public function get_entry() {
		$this->_entry = new stdClass();

		$this->_entry->recipient_customer = '';
		$this->_entry->user_id = '';
		$this->_entry->coupon_type = '';
		$this->_entry->coupon_giftcert = '';
		$this->_entry->coupon_template = '';
		$this->_entry->coupon_id = '';
		$this->_entry->template_id = '';
		$this->_entry->coupon_value = '';
		return $this->_entry;
	}

	/**
	 * Save item
	 *
	 * @param array $data the data to save.
	 */
	public function save( $data ) {
		$errors = array();

		$row = (object) $data;

		// Make sure the data is valid.
		$tmperr = $this->validate( $row, $data );
		foreach ( $tmperr as $err ) {
			$errors[] = $err;
		}

		// Take a break and return if there are any errors.
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		if ( 'giftcert' === $row->coupon_type ) {
			$coupon_id = (int) $row->coupon_id;
		} elseif ( 'template' === $row->coupon_type ) {
			$rtn = AC()->coupon->generate( $row->template_id );
			$coupon_id = $rtn->coupon_id;
		}

		$coupon_row = AC()->db->get_object( 'SELECT * FROM #__cmcoupon WHERE id=' . (int) $coupon_id );

		if ( 'template' === $row->coupon_type ) {
			$coupon_row->coupon_value = $row->coupon_value;
			$coupon_row->coupon_value_type = 'amount';
			AC()->db->query( 'UPDATE #__cmcoupon SET  coupon_value=' . (float) $coupon_row->coupon_value . ', coupon_value_type="amount" WHERE id=' . (int) $coupon_row->id );
		}

		if ( ! AC()->coupon->is_giftcert_valid_for_balance( $coupon_row->id ) ) {
			$errors[] = AC()->lang->__( 'Error' );
			return $errors;
		}

		/* ----- passed all tests ----------------------- */

		// Get balance of gift certificate.
		$balance = AC()->coupon->get_giftcert_balance( $coupon_row->id );

		// Add to customer balance.
		AC()->db->query( 'INSERT INTO #__cmcoupon_customer_balance (user_id,coupon_id,initial_balance) VALUES (' . $row->user_id . ',' . (int) $coupon_row->id . ',' . (float) $balance . ')' );

		// Unpublish the giftcert.
		AC()->db->query( 'UPDATE #__cmcoupon SET state="balance",expiration=NULL WHERE id=' . (int) $coupon_row->id );
	}

	/**
	 * Check item before saving
	 *
	 * @param object $row table row.
	 * @param array  $post data turned in.
	 */
	public function validate( $row, $post ) {
		$err = array();

		if ( 1 !== (int) AC()->param->get( 'enable_frontend_balance', 0 ) ) {
			$err[] = AC()->lang->__( 'Customer balance is not enabled' );
		}

		if ( empty( $row->user_id ) || ! AC()->helper->pos_int( $row->user_id ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Customer' ) );
		} else {
			$user = AC()->helper->get_user( $row->user_id );
			if ( empty( $user->id ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Customer' ) );
			}
		}

		if ( empty( $row->coupon_type ) || ! in_array( $row->coupon_type, array( 'giftcert', 'template' ), true ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Type' ) );
		} else {
			if ( 'giftcert' === $row->coupon_type ) {
				if ( ( empty( $row->coupon_id ) || ! AC()->helper->pos_int( $row->coupon_id ) ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Gift Certificate' ) );
				} else {
					$db_coupon_id = (int) AC()->db->get_value( 'SELECT id FROM #__cmcoupon WHERE id=' . (int) $row->coupon_id . ' AND state="published"' );
					if ( $db_coupon_id <= 0 ) {
						$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon' ) );
					}
				}
			} elseif ( 'template' === $row->coupon_type ) {
				if ( empty( $row->template_id ) || ! AC()->helper->pos_int( $row->template_id ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon Template' ) );
				} else {
					$db_coupon_id = (int) AC()->db->get_value( 'SELECT id FROM #__cmcoupon WHERE id=' . (int) $row->template_id . ' AND state="template"' );
					if ( $db_coupon_id <= 0 ) {
						$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon Template' ) );
					}
				}
				if ( empty( $row->coupon_value ) || ! is_numeric( $row->coupon_value ) || $row->coupon_value <= 0 ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Value' ) );
				}
			}
		}

		return $err;
	}
}
