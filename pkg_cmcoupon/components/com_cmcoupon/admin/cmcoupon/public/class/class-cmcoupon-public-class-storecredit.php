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

class CmCoupon_Public_Class_Storecredit extends CmCoupon_Library_Class {

	public function __construct( $id = 0 ) {
		$this->name = 'storecredit';
		parent::__construct();
	}

	public function get_columns() {
		$columns = array(
			'date' => AC()->lang->__( 'Date' ),
			'description' => AC()->lang->__( 'Description' ),
			'amount' => AC()->lang->__( 'Amount' ),
		);
		return $columns;
	}

	public function column_default( $row, $column_name ) {
		return $row->{$column_name};
	}

	public function column_date( $row ) {
		return ! empty( $row->timestamp ) ? AC()->helper->get_date( $row->timestamp ) : '';
	}

	public function column_description( $row ) {
		$description = '';
		if ( 'credit' == $row->type ) {
			$description = AC()->lang->__( 'Gift certificate claim' ) . ' (' . $row->coupon_code . ')';
		} elseif ( 'debit' == $row->type ) {
			$description = AC()->lang->__( 'Payment towards order' ) . ' (' . $row->order_obj->order_number . ')';
		}
		return $description;
	}

	public function column_amount( $row ) {
		return AC()->storecurrency->format( 'debit' == $row->type ? ( $row->amount * -1 ) : $row->amount );
	}

	public function get_data() {
		// Lets load the files if it doesn't already exist
		if ( empty( $this->_data ) ) {
			$this->_data = $this->get_list( $this->buildquery(), '', $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );

			foreach ( $this->_data as $k => $row ) {
				$this->_data[ $k ]->order_obj = empty( $row->order_id )
					? new stdclass()
					: AC()->store->get_order( $row->order_id );
			}
		}

		return $this->_data;
	}

	public function buildquery() {

		$sql = 'SELECT id FROM #__cmcoupon WHERE 1!=1';

		$user = AC()->helper->get_user();
		if ( ! empty( $user->id ) ) {

			$sql = 'SELECT "credit" as type,cb.timestamp,cb.initial_balance AS amount,c.coupon_code,"" AS order_id
					  FROM #__cmcoupon_customer_balance cb
					  JOIN #__cmcoupon c ON c.id=cb.coupon_id
					 WHERE c.estore="' . CMCOUPON_ESTORE . '"
					   AND cb.user_id=' . (int) $user->id . '
					   AND c.state="balance"
					 GROUP BY cb.id
					 
										UNION 
					 
					SELECT "debit" as type, h.timestamp,SUM(h.total_product+h.total_shipping) AS amount,
							c.coupon_code,h.order_id
					  FROM #__cmcoupon_history h
					  JOIN #__cmcoupon c ON c.id=h.coupon_id
					  JOIN #__cmcoupon_customer_balance cb ON cb.coupon_id=c.id AND cb.user_id=h.user_id
					 WHERE c.estore="' . CMCOUPON_ESTORE . '"
					   AND h.user_id=' . (int) $user->id . '
					   AND c.state="balance"
					   AND h.is_customer_balance=1
					   AND (h.order_id IS NOT NULL AND h.order_id!=0)
					 GROUP BY h.order_id
					 
					 ORDER BY timestamp DESC
			';
		}
		return $sql;
	}

	public function save( $coupon_code ) {
		$errors = array();

		$row = new stdClass();
		$row->user = AC()->helper->get_user();
		$row->coupon_row = AC()->db->get_object( 'SELECT c.* FROM #__cmcoupon c WHERE c.estore="' . CMCOUPON_ESTORE . '" AND c.coupon_code="' . AC()->db->escape( $coupon_code ) . '" AND c.state="published"' );

		// Make sure the data is valid
		$tmperr = $this->validate( $row, $row );
		foreach ( $tmperr as $err ) {
			$errors[] = $err;
		}

		// take a break and return if there are any errors
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		//----- passed all tests -----------------------

		// get balance of gift certificate
		$balance = AC()->coupon->get_giftcert_balance( $row->coupon_row->id );

		// add to customer balance
		AC()->db->query( 'INSERT INTO #__cmcoupon_customer_balance (user_id,coupon_id,initial_balance) VALUES (' . $row->user->id . ',' . (int) $row->coupon_row->id . ',' . (float) $balance . ')' );

		// and unpublish the giftcert
		AC()->db->query( 'UPDATE #__cmcoupon SET state="balance",expiration=NULL WHERE id=' . (int) $row->coupon_row->id );

		// reset session
		AC()->helper->reset_session( 'site', 'customer_balance' );

		return;
	}

	public function validate( $row, $post ) {
		$err = array();

		if ( empty( $row->user->id ) ) {
			$err[] = AC()->lang->__( 'Please log in' );
		}

		if ( AC()->param->get( 'enable_frontend_balance', 0 ) != 1 ) {
			$err[] = AC()->lang->__( 'Store credits is not enabled' );
		}

		if ( empty( $row->coupon_row ) ) {
			$err[] = AC()->lang->__( 'Could not add the voucher to your balance.  Please contact your system administrator if you feel this is an error.' );
		} elseif ( ! AC()->coupon->is_giftcert_valid_for_balance( $row->coupon_row->id ) ) {
			$err[] = AC()->lang->__( 'Error' );
		}

		return $err;
	}

}

