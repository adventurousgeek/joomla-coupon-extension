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

class CmCoupon_Public_Class_Coupon extends CmCoupon_Library_Class {

	public function __construct( $id = 0 ) {
		$this->name = 'coupon';
		$this->_id = $id;
		$this->_orderby = 'coupon_code';
		parent::__construct();
	}

	public function get_columns() {
		$columns = array(
			'coupon_code' => AC()->lang->__( 'Coupon Code' ),
			'coupon_value_type' => AC()->lang->__( 'Value Type' ),
			'coupon_value' => AC()->lang->__( 'Value' ),
			'balance' => AC()->lang->__( 'Balance' ),
			'startdate' => AC()->lang->__( 'Start Date' ),
			'expiration' => AC()->lang->__( 'Expiration' ),
		);
		return $columns;
	}

	protected function get_sortable_columns() {
		$sortable_columns = array(
			'id'  => 'c.id',
			'coupon_code' => 'coupon_code',
			'coupon_value_type' => 'coupon_value_type',
			'coupon_value' => 'coupon_value',
			'startdate' => 'startdate',
			'expiration' => 'expiration',
		);
		return $sortable_columns;
	}

	public function column_default( $item, $column_name ) {
		return $item->{$column_name};
	}

	public function column_coupon_code( $row ) {
		$html = $row->coupon_code;
		if ( ! empty( $row->filename ) ) {
			$html .= ' <a class="modal" href="' . AC()->store->get_home_link() . '/cmcoupon/image/' . $row->coupon_code . '">
						<img src="' . CMCOUPON_ASEET_URL . '/images/icon_view.png" style="height:20px;" >
					</a>';
		}
		return $html;
	}

	public function column_coupon_value_type( $row ) {
		return AC()->helper->vars( 'function_type', 'combination' == $row->function_type ? 'coupon' : $row->function_type );
	}

	public function column_coupon_value( $row ) {
		$price = '';
		if ( ! empty( $row->coupon_value ) ) {
			$price = 'amount' == $row->coupon_value_type
				? AC()->storecurrency->format( $row->coupon_value )
				: round( $row->coupon_value ) . '%';
		}
		return $price;
	}

	public function column_balance( $row ) {
		return isset( $row->balance ) ? $row->str_balance : '---';
	}

	public function column_startdate( $row ) {
		return ! empty( $row->startdate ) ? AC()->helper->get_date( $row->startdate ) : '';
	}

	public function column_expiration( $row ) {
		return ! empty( $row->expiration ) ? AC()->helper->get_date( $row->expiration ) : '';
	}

	public function get_data() {
		// Lets load the files if it doesn't already exist
		if ( empty( $this->_data ) ) {
			$this->_data = $this->get_list( $this->buildquery(), 'id', $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );
			if ( empty( $this->_data ) ) {
				return $this->_data;
			}
			$user = AC()->helper->get_user();

			$coupon_ids = array();
			foreach ( $this->_data as $row ) {
				$coupon_ids[] = $row->id;
			}

			// get gift cert balance
			$giftcards = AC()->db->get_objectlist( AC()->store->sql_history_giftcert( array( 'c.id IN (' . implode( ',', $coupon_ids ) . ')' ) ), 'id' );

			foreach ( $this->_data as $i => $row ) {

				if ( ! empty( $giftcards[ $row->id ] ) ) {
					$this->_data[ $i ]->balance = $giftcards[ $row->id ]->balance;
					$this->_data[ $i ]->str_balance = AC()->storecurrency->format( $giftcards[ $row->id ]->balance );
				}
				$full_filename = CMCOUPON_CUSTOMER_DIR . '/' . $user->id . '/' . $row->filename . '.php';
				if ( ! file_exists( $full_filename ) ) {
					$this->_data[ $i ]->filename = '';
				}
			}
		}

		return $this->_data;
	}

	public function buildquery() {

		$sql_master = 'SELECT id FROM #__cmcoupon WHERE 1!=1';

		$user = AC()->helper->get_user();
		if ( empty( $user->id ) ) {
			return $sql_master;
		}

		$cc_codes = array();
		$gc_codes = array();
		$im_codes = array();
		$current_date = AC()->helper->get_date( null, 'Y-m-d H:i:s', 'utc2utc' );

		// find all coupons assigned to specific customer or customer group
		$sql = 'SELECT u.coupon_id,c.num_of_uses_total,c.num_of_uses_customer
				  FROM #__cmcoupon c
				  JOIN #__cmcoupon_asset u ON u.coupon_id=c.id AND u.asset_key=0 AND u.asset_type="user"
				 WHERE c.estore="' . CMCOUPON_ESTORE . '" AND u.asset_id=' . $user->id . ' AND c.state="published"
				   AND ( ((c.startdate IS NULL OR c.startdate="")   AND (c.expiration IS NULL OR c.expiration="")) OR
						 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
						 ((c.startdate IS NULL OR c.startdate="")   AND c.expiration>="' . $current_date . '") OR
						 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
					   )
		';
		$shoppergroups = AC()->store->get_group_ids( $user->id );
		if ( ! empty( $shoppergroups ) ) {
			$sql .= '    
											UNION
						 
					SELECT u.coupon_id,c.num_of_uses_total,c.num_of_uses_customer
					  FROM #__cmcoupon c
					  JOIN #__cmcoupon_asset u ON u.coupon_id=c.id AND u.asset_key=0 AND u.asset_type="usergroup"
					 WHERE c.estore="' . CMCOUPON_ESTORE . '" AND u.asset_id IN (' . implode( ',', $shoppergroups ) . ') AND c.state="published"
					   AND ( ((c.startdate IS NULL OR c.startdate="")   AND (c.expiration IS NULL OR c.expiration="")) OR
							 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
							 ((c.startdate IS NULL OR c.startdate="")   AND c.expiration>="' . $current_date . '") OR
							 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
						   )
			';
		}
		$cc_codes = AC()->db->get_objectlist( $sql, 'coupon_id' );

		// find all purchased coupons
		$sql = '
				SELECT c.id AS coupon_id,c.num_of_uses_total,c.num_of_uses_customer
				  FROM #__cmcoupon c
				  JOIN #__cmcoupon_voucher_customer_code gc ON gc.coupon_id=c.id
				  JOIN #__cmcoupon_voucher_customer g ON g.id=gc.voucher_customer_id
				 WHERE c.estore="' . CMCOUPON_ESTORE . '"
				   AND g.user_id=' . (int) $user->id . '
				   AND (gc.recipient_user_id IS NULL OR gc.recipient_user_id=0)
				   AND c.state="published"
				   AND ( ((c.startdate IS NULL OR c.startdate="")   AND (c.expiration IS NULL OR c.expiration="")) OR
						 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
						 ((c.startdate IS NULL OR c.startdate="")   AND c.expiration>="' . $current_date . '") OR
						 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
					   )
				 GROUP BY c.id
				 
									UNION 
				 
				SELECT c.id,c.num_of_uses_total,c.num_of_uses_customer
				  FROM #__cmcoupon c
				  JOIN #__cmcoupon_voucher_customer_code gc ON gc.coupon_id=c.id
				  JOIN #__cmcoupon_voucher_customer g ON g.id=gc.voucher_customer_id
				 WHERE c.estore="' . CMCOUPON_ESTORE . '"
				   AND gc.recipient_user_id=' . (int) $user->id . '
				   AND c.state="published"
				   AND ( ((c.startdate IS NULL OR c.startdate="")   AND (c.expiration IS NULL OR c.expiration="")) OR
						 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
						 ((c.startdate IS NULL OR c.startdate="")   AND c.expiration>="' . $current_date . '") OR
						 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
					   )
				 GROUP BY c.id
		';
		$gc_codes = AC()->db->get_objectlist( $sql,'coupon_id' );

		// find all coupons where images are created
		$sql = 'SELECT i.coupon_id,c.num_of_uses_total,c.num_of_uses_customer
				  FROM #__cmcoupon c
				  JOIN #__cmcoupon_image i ON i.coupon_id=c.id
				 WHERE c.estore="' . CMCOUPON_ESTORE . '" AND i.user_id=' . $user->id . ' AND c.state="published"
				   AND ( ((c.startdate IS NULL OR c.startdate="")   AND (c.expiration IS NULL OR c.expiration="")) OR
						 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
						 ((c.startdate IS NULL OR c.startdate="")   AND c.expiration>="' . $current_date . '") OR
						 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
					   )
		';
		$im_codes = AC()->db->get_objectlist( $sql, 'coupon_id' );

		$all_found_codes = $cc_codes + $gc_codes + $im_codes;

		// remove all coupons where the number of uses have been used up
		foreach ( $all_found_codes as $row ) {
			if ( ! empty( $row->num_of_uses_customer ) ) {
				$userlist = array();
				$cnt = AC()->db->get_value( 'SELECT COUNT(id) AS cnt FROM #__cmcoupon_history WHERE coupon_id=' . $row->coupon_id . ' AND user_id=' . $user->id . ' GROUP BY coupon_id,user_id' );
				if ( ! empty( $cnt ) && $cnt >= $row->num_of_uses_customer ) {
					unset( $all_found_codes[ $row->coupon_id ] );
					continue;
				}
			}
			if ( ! empty( $row->num_of_uses_total ) ) {
				$num = AC()->db->get_value( 'SELECT COUNT(id) FROM #__cmcoupon_history WHERE coupon_id=' . $row->coupon_id . ' GROUP BY coupon_id' );
				if ( ! empty( $num ) && $num >= $row->num_of_uses_total ) {
					unset( $all_found_codes[ $row->coupon_id ] );
					continue;
				}
			}
		}

		if ( ! empty( $all_found_codes ) ) {
			$orderby = $this->buildquery_orderby();
			if ( ! empty( $orderby ) ) {
				$orderby = ' ORDER BY ' . $orderby . ' ';
			}
			$sql_master = '
				SELECT c.id,c.function_type,c.coupon_code,c.coupon_value_type,c.coupon_value,c.startdate,c.expiration,i.filename,c.note
				  FROM #__cmcoupon c
				  LEFT JOIN #__cmcoupon_image i ON i.coupon_id=c.id AND i.user_id=' . $user->id . '
				 WHERE c.id IN (' . implode( ',', array_keys( $all_found_codes ) ) . ')
				 GROUP BY c.id
				 ' . $orderby . '
			';
		}

		return $sql_master;
	}

	public function get_image_url( $coupon_code ) {

		$user = AC()->helper->get_user();
		if ( empty( $user->id ) ) {
			return '';
		}

		$coupon_id = (int) AC()->db->get_value( 'SELECT id FROM #__cmcoupon WHERE estore="' . CMCOUPON_ESTORE . '" AND coupon_code="' . AC()->db->escape( $coupon_code ) . '"' );

		$filename = AC()->db->get_value( 'SELECT filename FROM #__cmcoupon_image WHERE user_id=' . (int) $user->id . ' AND coupon_id=' . (int) $coupon_id );
		if ( empty( $filename ) ) {
			return '';
		}

		$file = str_replace( '.php', '', $filename );
		return AC()->store->get_home_link() . '/cmcoupon/image/raw/' . $file;
	}

	public function get_image_raw( $filename, $user_id = 0 ) {
		// security
		$filename = rtrim( $filename, '.' );
		$filename = trim( preg_replace( array( '#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#' ), '', $filename ) ); // makesafe

		if ( strpos( $filename, '/' ) !== false ) {
			die( 'security alert sent to administrator' );
		}
		if ( strpos( $filename, '..' ) !== false ) {
			die( 'security alert sent to administrator' );
		}
		$fi = pathinfo( $filename );
		$filename = $fi['basename'];
		// end of security

		if ( empty( $user_id ) ) {
			$user = AC()->helper->get_user();
			$user_id = $user->id;
		}
		if ( empty( $user_id ) ) {
			return false;
		}

		$fi = pathinfo( $filename );
		if ( empty( $fi['extension'] ) ) {
			return false;
		}
		$type = $fi['extension'];
		$full_filename = CMCOUPON_CUSTOMER_DIR . '/' . $user_id . '/' . $filename . '.php';

		if ( ! file_exists( $full_filename ) ) {
			return false;
		}
		$fcontent = file_get_contents( $full_filename );
		$fcontent = str_replace( urldecode( '%3c%3fphp+die()%3b+%3f%3e' ), '', $fcontent );
		return $fcontent; // base64_encoded content of the file
	}

}
