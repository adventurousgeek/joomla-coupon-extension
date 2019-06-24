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
class CmCoupon_Library_Coupon {

	/**
	 * Get list of coupon templates
	 */
	public function get_templates() {
		return AC()->db->get_objectlist( 'SELECT id,coupon_code,coupon_code AS label FROM #__cmcoupon WHERE estore="' . CMCOUPON_ESTORE . '" AND state="template" ORDER BY coupon_code,id','id' );
	}

	/**
	 * Generate a coupon from another coupon
	 *
	 * @param int    $coupon_id the coupon toe duplicate.
	 * @param string $coupon_code coupon code to use.
	 * @param int    $expiration nuber of days it takes to expire.
	 * @param int    $override_user the user to assign the coupon to.
	 */
	public function generate( $coupon_id, $coupon_code = null, $expiration = null, $override_user = null ) {

		$coupon_id = (int) $coupon_id;
		if ( ! is_null( $override_user ) ) {
			$override_user = trim( $override_user );
		}
		if ( ! is_null( $expiration ) ) {
			$expiration = trim( $expiration );
		}

		$crow = AC()->db->get_object( 'SELECT * FROM #__cmcoupon WHERE id=' . $coupon_id );
		if ( empty( $crow ) ) {
			return false;  // template coupon does not exist.
		}

		if ( empty( $coupon_code ) || $this->is_code_used( $coupon_code ) ) {
			$coupon_code = $this->generate_coupon_code();
		}

		$db_expiration = ! empty( $crow->expiration ) ? '"' . $crow->expiration . '"' : 'NULL';
		if ( ! empty( $expiration ) && ctype_digit( $expiration ) ) {
			$db_expiration = '"' . date( 'Y-m-d 23:59:59', time() + ( 86400 * (int) $expiration ) ) . '"';
		}

		$passcode = substr( md5( (string) time() . rand( 1, 1000 ) . $coupon_code ), 0, 6 );

		$sql = 'INSERT INTO #__cmcoupon (	
					estore,template_id,coupon_code,upc,passcode,coupon_value_type,coupon_value,coupon_value_def,
					function_type,num_of_uses_total,num_of_uses_customer,min_value,discount_type,startdate,
					expiration,note,params,state
				)
				VALUES ("' . CMCOUPON_ESTORE . '",
						' . $coupon_id . ',
						"' . $coupon_code . '",
						' . ( ! empty( $crow->upc ) ? '"' . $crow->upc . '"' : 'NULL' ) . ',
						"' . $passcode . '",
						' . ( ! empty( $crow->coupon_value_type ) ? '"' . $crow->coupon_value_type . '"' : 'NULL' ) . ',
						' . ( ! empty( $crow->coupon_value ) ? $crow->coupon_value : 'NULL' ) . ',
						' . ( ! empty( $crow->coupon_value_def ) ? '"' . $crow->coupon_value_def . '"' : 'NULL' ) . ',
						"' . $crow->function_type . '",
						' . ( ! empty( $crow->num_of_uses_total ) ? $crow->num_of_uses_total : 'NULL' ) . ',
						' . ( ! empty( $crow->num_of_uses_customer ) ? $crow->num_of_uses_customer : 'NULL' ) . ',
						' . ( ! empty( $crow->min_value ) ? $crow->min_value : 'NULL' ) . ',
						' . ( ! empty( $crow->discount_type ) ? '"' . $crow->discount_type . '"' : 'NULL' ) . ',
						' . ( ! empty( $crow->startdate ) ? '"' . $crow->startdate . '"' : 'NULL' ) . ',
						' . $db_expiration . ',
						' . ( ! empty( $crow->note ) ? '"' . $crow->note . '"' : 'NULL' ) . ',
						' . ( ! empty( $crow->params ) ? '"' . AC()->db->escape( $crow->params ) . '"' : 'NULL' ) . ',
						"published"
					)';
		AC()->db->query( $sql );
		$gen_coupon_id = AC()->db->get_insertid();

		$new_children_coupons = array();
		if ( 'combination' === $crow->function_type ) {
			$children = AC()->db->get_objectlist('
				 SELECT b.id,asset_key,a.asset_type,a.asset_id,a.qty,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__cmcoupon b ON b.id=a.asset_id
				 WHERE a.asset_key=0 AND a.asset_type="coupon" AND a.coupon_id=' . $coupon_id . ' AND b.state="template"
			');
			foreach ( $children as $child ) {
				$new_children_coupons[ $child->id ] = $child;
				$new_children_coupons[ $child->id ]->generated = $this->generate( $child->id, null, $expiration, $override_user );
			}
		}

		AC()->db->query('
				INSERT INTO #__cmcoupon_asset (coupon_id,asset_key,asset_type,asset_id,qty,order_by) 
				SELECT ' . $gen_coupon_id . ',asset_key,asset_type,asset_id,qty,order_by FROM #__cmcoupon_asset WHERE coupon_id=' . $coupon_id . '
				' . ( ! empty( $new_children_coupons ) ? ' AND asset_id NOT IN (' . implode( ',', array_keys( $new_children_coupons ) ) . ') ' : '' ) . '
		');
		if ( ! empty( $new_children_coupons ) ) {
			$insert = array();
			foreach ( $new_children_coupons as $new_entry ) {
				$insert[] = '(' . $gen_coupon_id . ',' . (int) $new_entry->asset_key . ',"' . $new_entry->asset_type . '",' . (int) $new_entry->generated->coupon_id . ',' . (int) $new_entry->count . ',' . (int) $new_entry->order_by . ')';
			}
			AC()->db->query( 'INSERT INTO #__cmcoupon_asset (coupon_id,asset_key,asset_type,asset_id,qty,order_by) VALUES ' . implode( ',', $insert ) );
		}

		if ( ! in_array( $crow->function_type, array( 'combination', 'giftcert' ), true ) && ! empty( $override_user ) && ctype_digit( trim( $override_user ) ) ) {
			AC()->db->query( 'DELETE FROM #__cmcoupon_asset WHERE asset_key=0 AND asset_type IN ("user","usergroup") AND coupon_id=' . $gen_coupon_id );
			AC()->db->query( 'INSERT INTO #__cmcoupon_asset ( coupon_id,asset_key,asset_type,asset_id ) VALUES ( ' . $gen_coupon_id . ',0,"user",' . $override_user . ' )' );

			$params = json_decode( $crow->params, true );
			$params['asset'][0]['rows']['user']['type'] = 'user';
			$params['asset'][0]['rows']['user']['mode'] = 'include';
			unset( $params['asset'][0]['rows']['usergroup'] );

			AC()->db->query( 'UPDATE #__cmcoupon SET params="' . AC()->db->escape( AC()->helper->json_encode( $params ) ) . '" WHERE id=' . $gen_coupon_id );
		}

		AC()->db->query( 'INSERT INTO #__cmcoupon_tag (coupon_id,tag) SELECT ' . $gen_coupon_id . ',tag FROM #__cmcoupon_tag WHERE coupon_id=' . $coupon_id );

		$obj = new stdClass();
		$obj->coupon_id = $gen_coupon_id;
		$obj->coupon_code = $coupon_code;
		return $obj;
	}

	/**
	 * Generate a coupon code
	 *
	 * @param string $prefix coupon code prefix.
	 * @param string $suffix coupon code suffix.
	 * @param int    $min_length minimum length of coupon.
	 * @param int    $max_length maximum length of coupon.
	 * @param array  $salt_type salt to use.
	 */
	public function generate_coupon_code( $prefix = '', $suffix = '', $min_length = 8, $max_length = 12, $salt_type = array() ) {
		$salt = '';
		if ( ! empty( $salt_type ) ) {
			if ( in_array( 'lower', $salt_type, true ) ) {
				$salt .= 'abcdefghjkmnpqrstuvwxyz';
			}
			if ( in_array( 'upper', $salt_type, true ) ) {
				$salt .= 'ABCDEFGHJKLMNPQRSTUVWXYZ';
			}
			if ( in_array( 'number', $salt_type, true ) ) {
				$salt .= empty( $salt ) ? '1234567890' : '23456789';
			}
		}
		if ( empty( $salt ) ) {
			$salt = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // all.
		}

		$min_length = (int) $min_length;
		if ( $min_length < 4 ) {
			$min_length = 4;
		}

		$max_length = (int) $max_length;
		if ( $max_length < 4 ) {
			$max_length = 4;
		}

		do {
			$coupon_code = trim( $prefix ) . $this->random_code( rand( $min_length, $max_length ), $salt ) . trim( $suffix );
		} while ( $this->is_code_used( $coupon_code ) );

		return $coupon_code;
	}

	/**
	 * Check if coupon code is already used
	 *
	 * @param string $code the coupon code.
	 */
	public function is_code_used( $code ) {
		AC()->db->get_value( 'SELECT id FROM #__cmcoupon WHERE estore="' . CMCOUPON_ESTORE . '" AND coupon_code="' . $code . '"' );

		if ( empty( $id ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Geneerate a random code
	 *
	 * @param int    $length length of the code.
	 * @param string $chars characters to use for generating a randon code.
	 */
	private function random_code( $length, $chars ) {
		$rand_id = '';
		$char_length = strlen( $chars );
		if ( $length > 0 ) {
			for ( $i = 1; $i <= $length; $i++ ) {
				$rand_id .= $chars[ mt_rand( 0, $char_length - 1 ) ];
			}
		}
		return $rand_id;
	}

	/**
	 * Value definition html
	 *
	 * @param string $valuedef value from the database.
	 * @param string $coupon_value_type value type.
	 */
	public function get_value_print( $valuedef, $coupon_value_type ) {
		if ( empty( $valuedef ) ) {
			return '';
		}

		$vdef_table = array();
		$vdef_options = array();
		$each_row = explode( ';', $valuedef );

		// options.
		$tmp = end( $each_row );
		if ( substr( $tmp, 0, 1 ) === '[' ) {
			parse_str( trim( $tmp, '[]' ), $vdef_options );
			array_pop( $each_row );
		}
		reset( $each_row );

		foreach ( $each_row as $row ) {
			if ( strpos( $row, '-' ) !== false ) {
				list( $p, $v ) = explode( '-', $row );
				$vdef_table[ $p ] = $v;
			}
		}

		$vdef_table_tmp = $vdef_table;

		$curr = 0;
		$text = '';
		if ( ! empty( $vdef_options['order'] ) && 'first' !== $vdef_options['order'] ) {
			$text .= '<div>' . AC()->lang->__( 'Ordering' ) . ': ' . AC()->helper->vars( 'process_type_buyxy', $vdef_options['order'] ) . '</div>';
		}
		if ( ! empty( $vdef_options['qty_type'] ) && 'distinct' === $vdef_options['qty_type'] ) {
			$text .= '<div>' . AC()->lang->__( 'Apply Distinct Count' ) . '</div>';
		}
		if ( ! empty( $text ) ) {
			$text .= '<hr>';
		}
		foreach ( $vdef_table as $qty => $val ) {
			$curr++;
			$qty_str = str_repeat( '&nbsp;', max( 0, 3 - strlen( $qty ) ) ) . $qty;
			if ( empty( $vdef_options['type'] ) || 'progressive' === $vdef_options['type'] ) {
				if ( empty( $val ) ) {
					// translators: %s: querystring.
					$string_minus = sprintf( AC()->lang->__( '%1$s- item(s) >> exclude from discount' ), $qty_str );
					// translators: %s: querystring.
					$string_plus = sprintf( AC()->lang->__( '%1$s+ item(s) >> exclude from discount' ), $qty_str );
					$text .= 1 === $curr
						? '<div>' . $string_minus . '</div>'
						: '<div>' . $string_plus . '</div>';
				} else {
					$val = 'percent' === $coupon_value_type ? round( $val, 2 ) . '%' : number_format( $val, 2 ) . ' ' . AC()->helper->vars( 'coupon_value_type', $coupon_value_type );
					// translators: %s: querystring.
					$text .= '<div>' . sprintf( AC()->lang->__( '%1$s+ item(s) >> total discount %2$s' ), $qty_str,$val ) . '</div>';
				}
			} elseif ( 'step' === $vdef_options['type'] ) {
				if ( empty( $val ) ) {
					continue;
				}
				$val = 'percent' === $coupon_value_type ? round( $val, 2 ) . '%' : number_format( $val, 2 ) . ' ' . AC()->helper->vars( 'coupon_value_type', $coupon_value_type );

				$qty2 = 0;
				$found = false;
				foreach ( $vdef_table_tmp as $j => $throwaway ) {
					if ( $found ) {
						$qty2 = $j;
						break;
					}
					if ( $qty !== $j ) {
						continue;
					}
					$found = true;
				}

				$qty2_str = empty( $qty2 ) ? '---' : str_repeat( '&nbsp;', max( 0, 3 - strlen( $qty2 - 1 ) ) ) . ( $qty2 - 1 );
				// translators: %s: querystring.
				$text .= '<div>' . sprintf( AC()->lang->__( '%1$s to %2$s item(s) >> discount %3$s' ), $qty_str, $qty2_str, $val ) . '</div>';
			}
		}

		return '<pre class="valuedef">' . $text . '</pre>';
	}

	/**
	 * Is coupon configuration case sensitive
	 */
	public function is_case_sensitive() {
		$rtn = array_change_key_case( (array) AC()->db->get_object( 'SHOW FULL COLUMNS FROM #__cmcoupon LIKE "coupon_code"' ) );
		return substr( $rtn['collation'], -4 ) === '_bin' ? true : false;
	}

	/**
	 * Get gift certificate balance
	 *
	 * @param int     $coupon_id the coupon.
	 **/
	public function get_giftcert_balance( $coupon_id ) {
		$coupon_row = AC()->db->get_object( 'SELECT * FROM #__cmcoupon WHERE id=' . (int) $coupon_id . ' AND function_type="giftcert"' );
		if ( empty( $coupon_row->id ) ) {
			return 0;
		}

		$total_used = (float) AC()->db->get_value( 'SELECT SUM(total_product+total_shipping) FROM #__cmcoupon_history WHERE coupon_id=' . $coupon_row->id . ' GROUP BY coupon_id' );
		$balance = max( 0, $coupon_row->coupon_value - $total_used );

		return $balance;
	}

	/**
	 * Is gift certificate valid for customer balance
	 *
	 * @param int     $coupon_id the coupon.
	 * @param boolean $is_display_error display error.
	 **/
	public function is_giftcert_valid_for_balance( $coupon_id, $is_display_error = true ) {
		$coupon_id = (int) $coupon_id;

		// check to see if gift cert exists and is published.
		$current_date = AC()->helper->get_date( null, 'Y-m-d H:i:s', 'utc2utc' );
		$coupon_row = AC()->db->get_object( '
			SELECT c.*
			 FROM #__cmcoupon c
			WHERE c.id=' . $coupon_id . '
			  AND c.state="published"
			  AND c.function_type="giftcert"
			  AND (c.params IS NULL OR c.params="")
			  AND ( ((startdate IS NULL OR startdate="")   AND (expiration IS NULL OR expiration="")) OR
					((expiration IS NULL OR expiration="") AND startdate<="' . $current_date . '") OR
					((startdate IS NULL OR startdate="")   AND expiration>="' . $current_date . '") OR
					(startdate<="' . $current_date . '"    AND expiration>="' . $current_date . '")
				)
		' );
		if ( empty( $coupon_row ) ) {
			if ( $is_display_error ) {
				AC()->helper->set_message( AC()->lang->__( 'Restricted Gift Certificate' ), 'error' );
			}
			return false;
		}

		$test = AC()->db->get_value( 'SELECT id FROM #__cmcoupon_asset WHERE coupon_id=' . $coupon_row->id );
		if ( ! empty( $test ) ) {
			if ( $is_display_error ) {
				AC()->helper->set_message( AC()->lang->__( 'Restricted Gift Certificate' ), 'error' );
			}
			return false;
		}

		// verify gift certificate is not tied to somebody.
		$test = AC()->db->get_value( 'SELECT id FROM #__cmcoupon_customer_balance WHERE coupon_id=' . $coupon_row->id );
		if ( ! empty( $test ) ) {
			if ( $is_display_error ) {
				AC()->helper->set_message( AC()->lang->__( 'Restricted Gift Certificate' ), 'error' );
			}
			return false;
		}

		$test = AC()->db->get_value( 'SELECT id FROM #__cmcoupon_voucher_customer_code WHERE coupon_id=' . $coupon_row->id . ' AND recipient_user_id IS NOT NULL AND recipient_user_id!=0' );
		if ( ! empty( $test ) ) {
			if ( $is_display_error ) {
				AC()->helper->set_message( AC()->lang->__( 'Restricted Gift Certificate' ), 'error' );
			}
			return false;
		}

		// get balance of gift certificate.
		$balance = AC()->coupon->get_giftcert_balance( $coupon_row->id );
		if ( empty( $balance ) ) {
			if ( $is_display_error ) {
				AC()->helper->set_message( AC()->lang->__( 'Gift certificate balance is already 0' ), 'error' );
			}
			return false;
		}

		return true;
	}

}
