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
class CmCoupon_Library_Discount {

	/**
	 * Instance of class.
	 *
	 * @var object
	 */
	protected static $_instance = null;

	/**
	 * If added to cart reprocess coupon.
	 *
	 * @var boolean
	 */
	var $reprocess = false;

	/**
	 * Error messages.
	 *
	 * @var array
	 */
	var $error_msgs = array();

	/**
	 * Indicator to see if errors should be displayed.
	 *
	 * @var boolean
	 */
	var $enqueue_error_msgs = false;

	/**
	 * Indicator to see if amount currencies should be converted or not.
	 *
	 * @var boolean
	 */
	var $is_currency_convert = true;

	/**
	 * Get instance of this object
	 *
	 * @param string $class classname.
	 *
	 * @throws Exception If class does not exist.
	 **/
	protected static function instance( $class = null ) {
		if ( is_null( self::$_instance ) ) {
			if ( class_exists( $class ) ) {
				self::$_instance = new $class();
			} else {
				throw new Exception( 'Cannot instantiate undefined class [' . $class . ']', 1 );
			}
		}
		return self::$_instance;
	}

	/**
	 * Construct
	 */
	public function __construct() {
		$this->params = AC()->param;
		if ( 'Cmcoupon_Library_Paraminstall' === get_class( $this->params ) ) {
			return;
		}

		$this->giftcert_discount_before_tax = 1 === (int) $this->params->get( 'enable_giftcert_discount_before_tax', 0 ) ? 1 : 0;
		$this->coupon_discount_before_tax = 1 === (int) $this->params->get( 'enable_coupon_discount_before_tax', 0 ) ? 1 : 0;
		$this->allow_zero_value = 1 === (int) $this->params->get( 'enable_zero_value_coupon', 0 ) ? 1 : 0;
		$this->is_casesensitive = AC()->coupon->is_case_sensitive();
		$this->coupon_code_balance = '___customer_balance___';
	}

	/**
	 * Process automatic coupon codes
	 **/
	protected function process_autocoupon_helper() {

		$db = AC()->db;

		// if cart is the same, do not reproccess coupon.
		$autosess = $this->get_coupon_auto();
		if ( ! empty( $autosess ) ) {
			if ( ! empty( $autosess->uniquecartstring ) && $autosess->uniquecartstring === $this->getuniquecartstringauto() ) {
				if ( empty( $cmsess ) ) {
					$this->finalize_autocoupon( $autosess->coupons );
				}
				return $autosess->coupons;
			}
		}

		$this->initialize_coupon_auto();

		// check coupons.
		$auto_coupon_code = array();
		$multiple_coupon_max_auto = (int) $this->params->get( 'multiple_coupon_max_auto', 100 );
		$current_date = AC()->helper->get_date( null, 'Y-m-d H:i:s', 'utc2utc' );
		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,c.min_value,c.discount_type,
					c.function_type,c.coupon_value_def,c.params,1 as isauto,note,c.state,0 as balance
				  FROM #__cmcoupon c
				  JOIN #__cmcoupon_auto a ON a.coupon_id=c.id
				 WHERE c.estore="' . $this->estore . '" AND c.state="published" AND a.published=1
				   AND ( ((c.startdate IS NULL OR c.startdate="")   AND (c.expiration IS NULL OR c.expiration="")) OR
						 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
						 ((c.startdate IS NULL OR c.startdate="")   AND c.expiration>="' . $current_date . '") OR
						 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
					   )
				 ORDER BY a.ordering';
		$coupon_rows = $db->get_objectlist( $sql );
		if ( empty( $coupon_rows ) ) {
			return false;
		}

		// retreive cart items.
		$this->define_cart_items();
		if ( empty( $this->cart->items ) ) {
			return false;
		}

		// update params.
		foreach ( $coupon_rows as $k => $coupon_row ) {
			$coupon_rows[ $k ]->params = ! empty( $coupon_row->params ) ? ( is_string( $coupon_row->params ) ? json_decode( $coupon_row->params ) : $coupon_row->params ) : new stdclass();
		}

		foreach ( $coupon_rows as $coupon_row ) {

			if ( empty( $coupon_row ) ) {
				// no record, so coupon_code entered was not valid.
				continue;
			}

			$r_err = $this->couponvalidate_daily_time_limit( $coupon_row );
			if ( ! empty( $r_err ) ) {
				continue;
			}

			// coupon returned.
			$this->coupon_row = $coupon_row;

			if ( 'combination' !== $coupon_row->function_type ) {

				$return = $this->checkdiscount( $coupon_row, false );
				if ( ! empty( $return ) && $return['redeemed'] ) {
					$auto_coupon_code[] = $coupon_row;
					if ( count( $auto_coupon_code ) >= $multiple_coupon_max_auto ) {
						break;
					}
				}
				continue;
			} else {

				$test = $this->couponvalidate_numuses( $coupon_row );
				if ( ! empty( $test ) ) {
					continue;
				}

				$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,c.min_value,c.discount_type,
							c.function_type,c.coupon_value_def,params,c.state,0 as balance
						  FROM #__cmcoupon c
						  JOIN #__cmcoupon_asset ch ON ch.asset_id=c.id
						 WHERE ch.asset_key=0 AND ch.asset_type="coupon" AND ch.coupon_id=' . $coupon_row->id . ' 
						   AND c.estore="' . $this->estore . '" AND c.state="published"
						   AND ( ((c.startdate IS NULL OR c.startdate="")   AND (c.expiration IS NULL OR c.expiration="")) OR
								 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
								 ((c.startdate IS NULL OR c.startdate="")   AND c.expiration>="' . $current_date . '") OR
								 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
							   )
						 ORDER BY ch.order_by';
				$coupon_children_rows = $db->get_objectlist( $sql );
				if ( empty( $coupon_children_rows ) ) {
					// no record, so coupon_code entered was not valid.
					continue;
				}

				if ( in_array( $coupon_row->params->process_type, array( 'first', 'lowest', 'highest', 'all' ), true ) ) {
					foreach ( $coupon_children_rows as $child_row ) {
						$return = $this->checkdiscount( $child_row, false );
						if ( ! empty( $return ) && $return['redeemed'] ) {
							// mark this order as having used a coupon so people cant go and use coupons over and over.
							$auto_coupon_code[] = $coupon_row;
							if ( count( $auto_coupon_code ) >= $multiple_coupon_max_auto ) {
								break 2;
							}
						}
					}
					continue;
				} elseif ( 'allonly' === $coupon_row->params->process_type ) {
					$found_valid_coupons = array();
					foreach ( $coupon_children_rows as $child_row ) {
						$return = $this->checkdiscount( $child_row, false );
						if ( ! empty( $return ) && $return['redeemed'] ) {
							// mark this order as having used a coupon so people cant go and use coupons over and over.
							$found_valid_coupons[] = $return;
						}
					}

					if ( 'allonly' === $coupon_row->params->process_type && count( $found_valid_coupons ) === count( $coupon_children_rows ) ) {
						$auto_coupon_code[] = $coupon_row;
						if ( count( $auto_coupon_code ) >= $multiple_coupon_max_auto ) {
							break;
						}
					}
					continue;
				}
			}
		}

		$this->set_coupon_auto( $auto_coupon_code );
		if ( ! empty( $auto_coupon_code ) ) {
			$this->finalize_autocoupon( $auto_coupon_code );
			return $auto_coupon_code;
		}
	}

	/**
	 * Finalize auto coupons
	 *
	 * @param array $coupon_codes codes.
	 **/
	protected function finalize_autocoupon( $coupon_codes ) {

		static $cache_checked = array();
		$uniquestring = '';
		$cmsess = $this->session_get( 'coupon' );
		if ( ! empty( $cmsess ) ) {
			$uniquestring = $this->getuniquecartstring( $cmsess->coupon_code_internal, false );
		}		

		foreach ( $coupon_codes as $coupon ) {
			if ( isset( $cached_checked[ $uniquestring ][ $coupon->coupon_code ] ) ) {
				continue;
			}
			$cached_checked[ $uniquestring ][ $coupon->coupon_code ] = 1;

			$this->set_submittedcoupon( $coupon->coupon_code );
			$this->process_coupon_helper();
		}
	}

	/**
	 * Process coupon code entered by user
	 **/
	protected function process_coupon_helper() {
		$this->error_msgs = array();
		$output = $this->start_processing_coupon();
		if ( $this->enqueue_error_msgs && ! empty( $this->error_msgs ) ) {
			foreach ( $this->error_msgs as $err ) {
				AC()->helper->set_message( $err, 'error' );
			}
		}
		$this->set_submittedcoupon( null );
		return $output;
	}

	/**
	 * Start processing coupon
	 **/
	private function start_processing_coupon() {
		if ( ! $this->cart_object_is_initialized() ) {
			return;
		}

		$user = AC()->helper->get_user();
		$db = AC()->db;
		$submitted_coupon_code = trim( $this->get_submittedcoupon() );

		// if cart is the same, do not reproccess coupon.
		$cmsess = $this->session_get( 'coupon' );
		if ( ! empty( $cmsess ) ) {
			$uniquestring = $this->getuniquecartstring( $cmsess->coupon_code_internal, false );
			if (
				(
					( ! empty( $submitted_coupon_code ) && false !== strpos( ';' . $cmsess->coupon_code_internal . ';', ';' . $submitted_coupon_code . ';' ) )
							||
					empty( $submitted_coupon_code )
				)
				&& ! empty( $cmsess->uniquecartstring ) && $cmsess->uniquecartstring === $uniquestring
			) {
				$this->finalize_coupon_store( $cmsess );
				return true;
			}
		}

		// ------START STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------.
		if ( $this->coupon_code_balance !== $submitted_coupon_code && empty( $cmsess ) ) {
			if ( 1 === (int) $this->params->get( 'enable_store_coupon', 0 ) ) {
				$tmp = $db->get_value( 'SELECT id FROM #__cmcoupon WHERE estore="' . $this->estore . '" AND coupon_code="' . $db->escape( $submitted_coupon_code ) . '"' );
				if ( empty( $tmp ) && $this->is_coupon_in_store( $submitted_coupon_code ) ) {
					$this->continue_execution = true;
					return null;
				}
			}
		}
		// ------END STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------
		// .

		$coupon_cm_entered_coupon_ids = array();
		$multiple_coupons['auto'] = array();
		$multiple_coupons['coupon'] = array();
		$multiple_coupons['giftcert'] = array();
		$customer_balance_coupon_rows = array();
		$use_customer_balance = false;
		$coupon_session = $this->session_get( 'coupon' );
		if ( ! empty( $coupon_session ) ) {
			if ( ! empty( $coupon_session->processed_coupons ) ) {
				foreach ( $coupon_session->processed_coupons as $k => $r ) {
					if ( $r->isauto ) {
						continue;
					}
					if ( $r->isbalance ) {
						$use_customer_balance = true;
						continue;
					}
					$coupon_cm_entered_coupon_ids[] = $r->coupon_code;
					$multiple_coupons[ $r->isgift ? 'giftcert' : 'coupon' ][] = $r->coupon_code;
				}
			}
		}
		if ( ! empty( $submitted_coupon_code ) ) {
			if ( $this->coupon_code_balance === $submitted_coupon_code ) {
				$use_customer_balance = true;
			} else {
				$submited_multiple_coupons = explode( ';', $submitted_coupon_code );
				foreach ( $submited_multiple_coupons as $___s_coupon ) {
					$___s_coupon = trim( $___s_coupon );
					if ( ! $this->giftcert_inuse( $___s_coupon ) ) {
						$coupon_cm_entered_coupon_ids[] = $db->escape( $___s_coupon );
					}
				}
			}
		}
		$coupon_cm_entered_coupon_ids = $this->array_unique_sensitive( $coupon_cm_entered_coupon_ids );

		$this->initialize_coupon();

		if ( $use_customer_balance && ! empty( $user->id ) ) {
			if ( 1 === (int) $this->params->get( 'enable_frontend_balance', 0 ) ) {
				$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,c.min_value,c.discount_type,
							c.function_type,c.coupon_value_def,"" AS params,"" AS note,c.state,
							(c.coupon_value-IFNULL(SUM(h.total_product+h.total_shipping),0)) as balance 
						  FROM #__cmcoupon c
						  JOIN #__cmcoupon_customer_balance cb ON cb.coupon_id=c.id
						  LEFT JOIN #__cmcoupon_history h ON h.coupon_id=c.id AND h.estore=c.estore
						 WHERE c.estore="' . $this->estore . '"
						   AND cb.user_id=' . (int) $user->id . '
						   AND c.state="balance"
						 GROUP BY c.id
						HAVING balance>0
						 ';
				$customer_balance_coupon_rows = $db->get_objectlist( $sql, 'id' );
				if ( ! empty( $customer_balance_coupon_rows ) ) {

					$balance_exclude_categorylist = $this->params->get( $this->estore . '_balance_category_exclude', '' );
					$balance_exclude_shippinglist = $this->params->get( $this->estore . '_balance_shipping_exclude', '' );

					if ( ! empty( $balance_exclude_categorylist ) ) {
						// exclude produce category.
						foreach ( $customer_balance_coupon_rows as $coupon_id => $row ) {
							if ( ! isset( $customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude ) ) {
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude = new stdclass();
							}
							if ( ! isset( $customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category ) ) {
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category = new stdclass();
							}
							$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category->asset_type = 'category';
							$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category->asset_mode = 'exclude';
							$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category->rows = array();
							foreach ( $balance_exclude_categorylist as $tmp ) {
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category->rows[ $tmp ] = new stdclass();
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category->rows[ $tmp ]->id = null;
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category->rows[ $tmp ]->coupon_id = $coupon_id;
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category->rows[ $tmp ]->asset_type = 'category';
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category->rows[ $tmp ]->asset_id = $tmp;
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category->rows[ $tmp ]->qty = null;
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->category->rows[ $tmp ]->order_by = null;
							}
						}
					}

					if ( ! empty( $balance_exclude_shippinglist ) ) {
						// exclude shipping.
						foreach ( $customer_balance_coupon_rows as $coupon_id => $row ) {
							if ( ! isset( $customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude ) ) {
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude = new stdclass();
							}
							if ( ! isset( $customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping ) ) {
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping = new stdclass();
							}
							$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping->asset_type = 'shipping';
							$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping->asset_mode = 'exclude';
							$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping->rows = array();
							foreach ( $balance_exclude_shippinglist as $tmp ) {
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping->rows[ $tmp ] = new stdclass();
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping->rows[ $tmp ]->id = null;
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping->rows[ $tmp ]->coupon_id = $coupon_id;
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping->rows[ $tmp ]->asset_type = 'shipping';
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping->rows[ $tmp ]->asset_id = $tmp;
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping->rows[ $tmp ]->count = null;
								$customer_balance_coupon_rows[ $coupon_id ]->_balance_exclude->shipping->rows[ $tmp ]->order_by = null;
							}
						}
					}
				}
			}
		}
		$auto_codes = $this->get_coupon_auto();
		$auto_codes = isset( $auto_codes->coupons ) ? $auto_codes->coupons : array();
		if ( empty( $coupon_cm_entered_coupon_ids ) && empty( $auto_codes ) && empty( $customer_balance_coupon_rows ) ) {
			if ( ! empty( $submitted_coupon_code ) ) {
				return $this->return_false( 'errNoRecord' );
			}
			return;
		}
		if ( empty( $auto_codes ) ) {
			$auto_codes = array();
		}
		if ( ! empty( $auto_codes ) ) {
			$reverse_auto_codes = array_reverse( $auto_codes );
			foreach ( $reverse_auto_codes as $auto_code ) {
				$key = $this->is_coupon_in_array( $auto_code->coupon_code, $multiple_coupons['coupon'] );
				if ( false !== $key ) {
					unset( $multiple_coupons['coupon'][ $key ] );
				}
				$key = $this->is_coupon_in_array( $auto_code->coupon_code, $multiple_coupons['giftcert'] );
				if ( false !== $key ) {
					unset( $multiple_coupons['giftcert'][ $key ] );
				}
				$key = $this->is_coupon_in_array( $db->escape( $auto_code->coupon_code ), $coupon_cm_entered_coupon_ids );
				if ( false !== $key ) {
					unset( $coupon_cm_entered_coupon_ids[ $key ] );
				}

				$multiple_coupons['auto'][] = $auto_code->coupon_code;
				//$coupon_cm_entered_coupon_ids[] = $db->escape( $auto_code->coupon_code );
				array_unshift( $coupon_cm_entered_coupon_ids, $db->escape( $auto_code->coupon_code ) ); // add auto coupons as first coupons processed
			}
		}
		$coupon_cm_entered_coupon_ids = $this->array_unique_sensitive( $coupon_cm_entered_coupon_ids );

		if ( 0 === (int) $this->params->get( 'enable_multiple_coupon', 0 ) ) {
			// remove all auto codes.
			$last_auto_code = '';
			foreach ( $auto_codes as $auto_code ) {
				$key = $this->is_coupon_in_array( $db->escape( $auto_code->coupon_code ), $coupon_cm_entered_coupon_ids );
				if ( false !== $key ) {
					unset( $coupon_cm_entered_coupon_ids[ $key ] );
					$last_auto_code = $auto_code->coupon_code;
				}
			}

			// get the last item in the coupon array.
			$coupon_cm_entered_coupon_ids = array( array_pop( $coupon_cm_entered_coupon_ids ) );

			// add the last auto code back.
			if ( ! empty( $last_auto_code ) ) {
				$coupon_cm_entered_coupon_ids[] = $last_auto_code;
			}
		} else {
			// remove coupons is maximums are set.
			if ( ! empty( $coupon_cm_entered_coupon_ids ) ) {

				$multiple_coupon_max_auto = (int) $this->params->get( 'multiple_coupon_max_auto', 0 );
				$multiple_coupon_max_coupon = (int) $this->params->get( 'multiple_coupon_max_coupon', 0 );
				$multiple_coupon_max_giftcert = (int) $this->params->get( 'multiple_coupon_max_giftcert', 0 );
				if ( $multiple_coupon_max_auto > 0 || $multiple_coupon_max_coupon > 0 || $multiple_coupon_max_giftcert > 0 ) {
					if ( ! empty( $submitted_coupon_code ) ) {
						$submitted_not_in_coupons = $this->array_intersect_diff( 'diff', $submited_multiple_coupons, array_merge( $multiple_coupons['coupon'], $multiple_coupons['giftcert'] ) );
						if ( ! empty( $submitted_not_in_coupons ) ) {
							// now add submitted coupon(s) not on any list to either automatic, giftcert, or coupon array.
							foreach ( $submitted_not_in_coupons as $current_coupon_not_in_coupons ) {
								$check_if_auto = false;
								foreach ( $auto_codes as $auto_code ) {
									if ( $this->is_couponcode_equal( trim( $auto_code->coupon_code ), $current_coupon_not_in_coupons ) ) {
										$check_if_auto = true;
										$multiple_coupons['auto'][] = $auto_code->coupon_code;
										break;
									}
								}
								if ( ! $check_if_auto ) {
									$test = $db->get_value( 'SELECT function_type FROM #__cmcoupon WHERE coupon_code="' . $db->escape( $current_coupon_not_in_coupons ) . '"' );
									if ( ! empty( $test ) ) {
										$multiple_coupons[ 'giftcert' === $test ? 'giftcert' : 'coupon' ][] = $current_coupon_not_in_coupons;
									}
								}
							}
						}
					}

					if ( $multiple_coupon_max_auto > 0 && count( $multiple_coupons['auto'] ) > 1 ) {
						$multiple_coupons['auto'] = $this->array_unique_sensitive( $multiple_coupons['auto'] );
						if ( count( $multiple_coupons['auto'] ) > $multiple_coupon_max_auto ) {
							$removecoupons = array_slice( $multiple_coupons['auto'], 0, count( $multiple_coupons['auto'] ) - $multiple_coupon_max_auto );
							if ( ! empty( $removecoupons ) ) {
								foreach ( $removecoupons as $r ) {
									$key = array_search( $r, $coupon_cm_entered_coupon_ids, true );
									if ( false !== $key ) {
										unset( $coupon_cm_entered_coupon_ids[ $key ] );
									}
								}
							}
						}
					}
					if ( $multiple_coupon_max_coupon > 0 && count( $multiple_coupons['coupon'] ) > 1 ) {
						$multiple_coupons['coupon'] = $this->array_unique_sensitive( $multiple_coupons['coupon'] );
						if ( count( $multiple_coupons['coupon'] ) > $multiple_coupon_max_coupon ) {
							$removecoupons = array_slice( $multiple_coupons['coupon'], 0, count( $multiple_coupons['coupon'] ) - $multiple_coupon_max_coupon );
							if ( ! empty( $removecoupons ) ) {
								foreach ( $removecoupons as $r ) {
									$key = array_search( $r, $coupon_cm_entered_coupon_ids, true );
									if ( false !== $key ) {
										unset( $coupon_cm_entered_coupon_ids[ $key ] );
									}
								}
							}
						}
					}
					if ( $multiple_coupon_max_giftcert > 0 && count( $multiple_coupons['giftcert'] ) > 1 ) {
						$multiple_coupons['giftcert'] = $this->array_unique_sensitive( $multiple_coupons['giftcert'] );
						if ( count( $multiple_coupons['giftcert'] ) > $multiple_coupon_max_giftcert ) {
							$removecoupons = array_slice( $multiple_coupons['giftcert'], 0, count( $multiple_coupons['giftcert'] ) - $multiple_coupon_max_giftcert );
							if ( ! empty( $removecoupons ) ) {
								foreach ( $removecoupons as $r ) {
									$key = array_search( $r, $coupon_cm_entered_coupon_ids, true );
									if ( false !== $key ) {
										unset( $coupon_cm_entered_coupon_ids[ $key ] );
									}
								}
							}
						}
					}
				}

				$multiple_coupon_max = (int) $this->params->get( 'multiple_coupon_max', 0 );
				if ( $multiple_coupon_max > 0 && count( $coupon_cm_entered_coupon_ids ) > $multiple_coupon_max ) {
					$coupon_cm_entered_coupon_ids = array_slice( $coupon_cm_entered_coupon_ids, count( $coupon_cm_entered_coupon_ids ) - $multiple_coupon_max );
				}
			}
		}

		if ( 1 === (int) $this->params->get( 'multiple_coupon_reorder_giftcert_last', 0 ) ) {
			// reorder giftcerts last.
			$sql = 'SELECT id,coupon_code,function_type FROM #__cmcoupon WHERE coupon_code IN ("' . implode( '","', $coupon_cm_entered_coupon_ids ) . '")';
			$entered_coupons_properties = $db->get_objectlist( $sql, 'coupon_code' );
			$gcert = array();
			foreach ( $coupon_cm_entered_coupon_ids as $current_key => $current_coupon ) {
				if ( ! isset( $entered_coupons_properties[ $current_coupon ] ) ) {
					continue;
				}
				if ( 'giftcert' !== $entered_coupons_properties[ $current_coupon ]->function_type ) {
					continue;
				}
				$gcert[] = $current_coupon;
				unset( $coupon_cm_entered_coupon_ids[ $current_key ] );
			}
			$coupon_cm_entered_coupon_ids = array_merge( $coupon_cm_entered_coupon_ids, $gcert );
		}

		// check coupons.
		$master_output = array();
		$coupon_rows = array();
		$current_date = AC()->helper->get_date( null, 'Y-m-d H:i:s', 'utc2utc' );
		if ( 1 === (int) $this->params->get( 'is_space_insensitive', 0 ) ) {
			foreach ( $coupon_cm_entered_coupon_ids as $k => $i ) {
				$coupon_cm_entered_coupon_ids[ $k ] = str_replace( ' ', '', $i );
			}
		}
		$coupon_codes = implode( '","', $coupon_cm_entered_coupon_ids );
		if ( ! empty( $coupon_codes ) ) {
			$sql = 'SELECT id,coupon_code,num_of_uses_total,num_of_uses_customer,coupon_value_type,coupon_value,min_value,discount_type,
						function_type,coupon_value_def,params,note,state,0 as balance
					  FROM #__cmcoupon 
					 WHERE estore="' . $this->estore . '"
					   AND state="published"
					   AND ( ((startdate IS NULL OR startdate="")   AND (expiration IS NULL OR expiration="")) OR
							 ((expiration IS NULL OR expiration="") AND startdate<="' . $current_date . '") OR
							 ((startdate IS NULL OR startdate="")   AND expiration>="' . $current_date . '") OR
							 (startdate<="' . $current_date . '"    AND expiration>="' . $current_date . '")
						   )
					   AND ' . ( 1 === (int) $this->params->get( 'is_space_insensitive', 0 ) ? 'REPLACE(coupon_code," ","")' : 'coupon_code' ) . ' IN ("' . $coupon_codes . '")
					  ORDER BY FIELD(coupon_code, "' . $coupon_codes . '")';
			$coupon_rows = $db->get_objectlist( $sql, 'id' );
		}

		if ( ! empty( $auto_codes ) ) {
			$valid_auto_codes = array();
			foreach ( $auto_codes as $auto_code ) {
				if ( isset( $coupon_rows[ $auto_code->id ] ) ) {
					$valid_auto_codes[] = $auto_code;
					unset( $coupon_rows[ $auto_code->id ] );
				}
			}
			$valid_auto_codes = array_reverse( $valid_auto_codes );
			foreach ( $valid_auto_codes as $auto_code ) {
				$tmp_array = array(
					$auto_code->id => $auto_code,
				);
				$coupon_rows = $tmp_array + $coupon_rows;  // need to preserve coupon_id as the key.
			}
		}

		if ( ! empty( $submitted_coupon_code ) && $this->coupon_code_balance !== $submitted_coupon_code ) {
			$is_found = false;
			foreach ( $submited_multiple_coupons as $_current_submitted_coupon ) {
				foreach ( $coupon_rows as $tmp ) {
					$test_db_code = 1 === (int) $this->params->get( 'is_space_insensitive', 0 ) ? str_replace( ' ', '', $tmp->coupon_code ) : trim( $tmp->coupon_code );
					$test_enter_code = 1 === (int) $this->params->get( 'is_space_insensitive', 0 ) ? str_replace( ' ', '', $_current_submitted_coupon ) : $_current_submitted_coupon;
					if ( $this->is_couponcode_equal( $test_db_code, $test_enter_code ) ) {
						$is_found = true;
						break 2;
					}
				}
			}
			if ( ! $is_found ) {
				$this->coupon_row = new stdclass();
				$this->coupon_row->id = -1;
				$this->coupon_row->coupon_code = $submitted_coupon_code;
				$this->coupon_row->function_type = 'coupon';
				$this->coupon_row->isauto = in_array( $submitted_coupon_code, $multiple_coupons['auto'], true ) ? true : false;
				$this->return_false( 'errNoRecord' );
			}
		}

		if ( ! empty( $customer_balance_coupon_rows ) ) {
			// add balance at end.
			$coupon_rows = $coupon_rows + $customer_balance_coupon_rows;
		}

		if ( empty( $coupon_rows ) ) {
			return false;
		}

		// get tags.
		$tmp = $db->get_objectlist( 'SELECT coupon_id,tag FROM #__cmcoupon_tag WHERE coupon_id IN (' . implode( ',', array_keys( $coupon_rows ) ) . ') AND tag LIKE "{_%}"' );
		foreach ( $tmp as $tmp_item ) {
			preg_match( '/{(.*):(.*)}/i', $tmp_item->tag, $match );
			if ( ! empty( $match[1] ) ) {
				$coupon_rows[ $tmp_item->coupon_id ]->tags[ $match[1] ] = $match[2];
			} else {
				$key = trim( $tmp_item->tag, '{}' );
				if ( ! empty( $key ) ) {
					$coupon_rows[ $tmp_item->coupon_id ]->tags[ $key ] = 1;
				}
			}
		}

		// update params.
		foreach ( $coupon_rows as $k => $coupon_row ) {
			$coupon_rows[ $k ]->params = ! empty( $coupon_row->params ) ? ( is_string( $coupon_row->params ) ? json_decode( $coupon_row->params ) : $coupon_row->params ) : new stdclass();
		}

		// check for coupon exclusivity.
		foreach ( $coupon_rows as $coupon_row ) {
			if ( ! empty( $coupon_row->params->exclusive ) && 1 === (int) $coupon_row->params->exclusive ) {
				// drop all other coupons and only use this one.
				$coupon_rows = array();
				$coupon_rows[ $coupon_row->id ] = $coupon_row;
				break;
			}
		}

		// retreive cart items.
		$this->define_cart_items();
		if ( empty( $this->cart->items ) ) {
			$this->initialize_coupon();
			$this->return_false( 'errDiscountedExclude' );
			return false;
		}

		foreach ( $coupon_rows as $coupon_row ) {

			if ( empty( $coupon_row ) ) {
				// no record, so coupon_code entered was not valid.
				continue;
			}

			$r_err = $this->couponvalidate_daily_time_limit( $coupon_row );
			if ( ! empty( $r_err ) ) {
				$this->return_false( $r_err );
				continue;
			}

			// coupon returned.
			$this->coupon_row = $coupon_row;

			if ( 'combination' !== $coupon_row->function_type ) {

				$return = $this->checkdiscount( $coupon_row, true );
				if ( ! empty( $return ) && $return['redeemed'] ) {
					$master_output[ $coupon_row->id ] = array( $coupon_row, $return );
					continue;
				}
				continue;
			} else {

				$coupon_row->asset = AC()->store->get_coupon_asset( $coupon_row );

				$user = AC()->helper->get_user();
				$coupon_row->customer = new stdClass();
				$coupon_row->customer->user_id = (int) $user->id;

				$r_err = $this->couponvalidate_user( $coupon_row );
				if ( ! empty( $r_err ) ) {
					$this->return_false( $r_err, 'key', 'force' );
					continue;
				}

				$r_err = $this->couponvalidate_usergroup( $coupon_row );
				if ( ! empty( $r_err ) ) {
					$this->return_false( $r_err, 'key', 'force' );
					continue;
				}

				$r_err = $this->couponvalidate_numuses( $coupon_row );
				if ( ! empty( $r_err ) ) {
					$this->return_false( $r_err, 'key', 'force' );
					continue;
				}

				// country state check.
				$r_err = $this->couponvalidate_country( $coupon_row );
				if ( ! empty( $r_err ) ) {
					$this->return_false( $r_err, 'key', 'force' );
					continue;
				}

				$r_err = $this->couponvalidate_countrystate( $coupon_row );
				if ( ! empty( $r_err ) ) {
					$this->return_false( $r_err, 'key', 'force' );
					continue;
				}

				// payment method check.
				$r_err = $this->couponvalidate_paymentmethod( $coupon_row );
				if ( ! empty( $r_err ) ) {
					$this->return_false( $r_err, 'key', 'force' );
					continue;
				}

				$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,c.min_value,c.discount_type,
							c.function_type,c.coupon_value_def,params,note,c.state,0 as balance
						  FROM #__cmcoupon c
						  JOIN #__cmcoupon_asset ch ON ch.asset_id=c.id
						 WHERE ch.asset_key=0 AND ch.asset_type="coupon" AND ch.coupon_id=' . $coupon_row->id . ' 
						   AND c.estore="' . $this->estore . '"
						   AND c.state="published"
						   AND ( ((c.startdate IS NULL OR c.startdate="") 	AND (c.expiration IS NULL OR c.expiration="")) OR
								 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
								 ((c.startdate IS NULL OR c.startdate="") 	AND c.expiration>="' . $current_date . '") OR
								 (c.startdate<="' . $current_date . '"		AND c.expiration>="' . $current_date . '")
							   )
						 ORDER BY ch.order_by';
				$coupon_children_rows = $db->get_objectlist( $sql, 'id' );
				if ( empty( $coupon_children_rows ) ) {
					// no record, so coupon_code entered was not valid.
					continue;
				}

				// get tags.
				$tmp = $db->get_objectlist( 'SELECT coupon_id,tag FROM #__cmcoupon_tag WHERE coupon_id IN (' . implode( ',', array_keys( $coupon_children_rows ) ) . ') AND tag LIKE "{_%}"' );
				foreach ( $tmp as $tmp_item ) {
					preg_match( '/{(.*):(.*)}/i', $tmp_item->tag, $match );
					if ( ! empty( $match[1] ) ) {
						$coupon_children_rows[ $tmp_item->coupon_id ]->tags[ $match[1] ] = $match[2];
					} else {
						$key = trim( $tmp_item->tag, '{}' );
						if ( ! empty( $key ) ) {
							$coupon_children_rows[ $tmp_item->coupon_id ]->tags[ $key ] = 1;
						}
					}
				}

				if ( 'first' === $coupon_row->params->process_type ) {
					foreach ( $coupon_children_rows as $child_row ) {
						$return = $this->checkdiscount( $child_row, true );
						if ( ! empty( $return ) && $return['redeemed'] ) {
							// mark this order as having used a coupon so people cant go and use coupons over and over.
							$return['coupon_entered_id'] = $coupon_row->id;
							$return['coupon_code'] = $coupon_row->coupon_code;
							$master_output[ $coupon_row->id ] = array( $coupon_row, $return );
							unset( $this->error_msgs[ $coupon_row->id ] );
							break;
						}
					}
					continue;
				} elseif ( in_array( $coupon_row->params->process_type, array( 'lowest', 'highest' ), true ) ) {
					$found_valid_coupons = array();
					foreach ( $coupon_children_rows as $child_row ) {
						$return = $this->checkdiscount( $child_row, false );
						if ( ! empty( $return ) && $return['redeemed'] ) {
							// mark this order as having used a coupon so people cant go and use coupons over and over.
							$found_valid_coupons[] = $return;
						}
					}
					if ( ! empty( $found_valid_coupons ) ) {
						$valid_id = -1;
						$valid_value = 0;
						foreach ( $found_valid_coupons as $k => $valid_coupon ) {
							if ( -1 === $valid_id ) {
								$valid_id = $k;
								$valid_value = $valid_coupon['product_discount'] + $valid_coupon['shipping_discount'];
							}
							if ( 'lowest' === $coupon_row->params->process_type ) {
								if ( $valid_value > ( $valid_coupon['product_discount'] + $valid_coupon['shipping_discount'] ) ) {
									$valid_id = $k;
									$valid_value = $valid_coupon['product_discount'] + $valid_coupon['shipping_discount'];
								}
							} elseif ( 'highest' === $coupon_row->params->process_type ) {
								if ( $valid_value < ( $valid_coupon['product_discount'] + $valid_coupon['shipping_discount'] ) ) {
									$valid_id = $k;
									$valid_value = $valid_coupon['product_discount'] + $valid_coupon['shipping_discount'];
								}
							}
						}
						if ( ! empty( $found_valid_coupons[ $valid_id ] ) ) {
							// mark this order as having used a coupon so people cant go and use coupons over and over.
							foreach ( $coupon_children_rows as $child_row ) {
								if ( $child_row->coupon_code === $found_valid_coupons[ $valid_id ]['coupon_code'] ) {
									$return = $this->checkdiscount( $child_row, true );
									if ( ! empty( $return ) && $return['redeemed'] ) {
										// mark this order as having used a coupon so people cant go and use coupons over and over.
										$return['coupon_entered_id'] = $coupon_row->id;
										$return['coupon_code'] = $coupon_row->coupon_code;
										$master_output[ $coupon_row->id ] = array( $coupon_row, $return );
										unset( $this->error_msgs[ $coupon_row->id ] );
									}
									break;
								}
							}
							continue;
						}
					}
					continue;
				} elseif ( in_array( $coupon_row->params->process_type, array( 'all', 'allonly' ), true ) ) {
					$found_valid_coupons = array();
					foreach ( $coupon_children_rows as $child_row ) {
						$return = $this->checkdiscount( $child_row, true );
						if ( ! empty( $return ) && $return['redeemed'] ) {
							// mark this order as having used a coupon so people cant go and use coupons over and over.
							$found_valid_coupons[] = $return;
						}
					}

					if ( 'allonly' === $coupon_row->params->process_type && count( $found_valid_coupons ) !== count( $coupon_children_rows ) ) {
						// all do not match, coupon not found
						// clean out cart items.
						foreach ( $found_valid_coupons as $v_coupon ) {
							foreach ( $this->cart->items as $k => $row ) {
								if ( isset( $row['coupons'][ $v_coupon['coupon_id'] ] ) ) {
									$this->cart->items[ $k ]['totaldiscount'] -= $row['coupons'][ $v_coupon['coupon_id'] ]['totaldiscount'];
									$this->cart->items[ $k ]['totaldiscount_notax'] -= $row['coupons'][ $v_coupon['coupon_id'] ]['totaldiscount_notax'];
									$this->cart->items[ $k ]['totaldiscount_tax'] -= $row['coupons'][ $v_coupon['coupon_id'] ]['totaldiscount_tax'];
									unset( $this->cart->items[ $k ]['coupons'][ $v_coupon['coupon_id'] ] );
								}
							}
							foreach ( $this->cart->items_breakdown as $k => $row ) {
								if ( isset( $row['coupons'][ $v_coupon['coupon_id'] ] ) ) {
									$this->cart->items_breakdown[ $k ]['totaldiscount'] -= $row['coupons'][ $v_coupon['coupon_id'] ]['totaldiscount'];
									$this->cart->items_breakdown[ $k ]['totaldiscount_notax'] -= $row['coupons'][ $v_coupon['coupon_id'] ]['totaldiscount_notax'];
									$this->cart->items_breakdown[ $k ]['totaldiscount_tax'] -= $row['coupons'][ $v_coupon['coupon_id'] ]['totaldiscount_tax'];
									unset( $this->cart->items_breakdown[ $k ]['coupons'][ $v_coupon['coupon_id'] ] );
								}
							}
						}

						$this->return_false( 'errNoRecord' );
						continue;
					}

					$return = array(
						'coupon_id' => $coupon_row->id,
						'coupon_code' => $coupon_row->coupon_code,
						'product_discount' => 0,
						'product_discount_notax' => 0,
						'product_discount_tax' => 0,
						'shipping_discount' => 0,
						'shipping_discount_notax' => 0,
						'shipping_discount_tax' => 0,
						'extra_discount' => 0,
						'extra_discount_notax' => 0,
						'extra_discount_tax' => 0,
						'usedproducts' => '',
					);
					$usedproducts = array();
					$processed_coupons = array();
					foreach ( $found_valid_coupons as $row ) {
						if ( ! empty( $row['force_add'] ) || ! empty( $row['product_discount'] ) || ! empty( $row['shipping_discount'] ) ) {
							if ( ! empty( $row['force_add'] ) ) {
								$return['force_add'] = 1;
							}
							$return['product_discount'] += $row['product_discount'];
							$return['product_discount_notax'] += $row['product_discount_notax'];
							$return['product_discount_tax'] += $row['product_discount_tax'];
							$return['shipping_discount'] += $row['shipping_discount'];
							$return['shipping_discount_notax'] += $row['shipping_discount_notax'];
							$return['shipping_discount_tax'] += $row['shipping_discount_tax'];
							$return['extra_discount'] += $row['extra_discount'];
							$return['extra_discount_notax'] += $row['extra_discount_notax'];
							$return['extra_discount_tax'] += $row['extra_discount_tax'];
							$return['is_discount_before_tax'] = $row['is_discount_before_tax'];
							$tmp = array();
							$tmpa = ! empty( $row['usedproducts'] ) ? explode( ',', $row['usedproducts'] ) : array();
							foreach ( $tmpa as $t ) {
								$tmp[ $t ] = 1;
							}
							$usedproducts = $usedproducts + $tmp;
							$isauto = false;
							if ( ! empty( $auto_codes ) ) {
								foreach ( $auto_codes as $auto_code ) {
									if ( $auto_code->id === $coupon_row->id ) {
										$isauto = true;
										break;
									}
								}
							}

							$processed_coupons[ $row['coupon_id'] ] = array(
								'coupon_entered_id' => $coupon_row->id,
								'coupon_code' => $coupon_row->coupon_code,
								'orig_coupon_id' => $row['coupon_id'],
								'orig_coupon_code' => $row['coupon_code'],
								'product_discount' => $row['product_discount'],
								'product_discount_notax' => $row['product_discount_notax'],
								'product_discount_tax' => $row['product_discount_tax'],
								'shipping_discount' => $row['shipping_discount'],
								'shipping_discount_notax' => $row['shipping_discount_notax'],
								'shipping_discount_tax' => $row['shipping_discount_tax'],
								'extra_discount' => $row['extra_discount'],
								'extra_discount_notax' => $row['extra_discount_notax'],
								'extra_discount_tax' => $row['extra_discount_tax'],
								'is_discount_before_tax' => $row['is_discount_before_tax'],
								'usedproducts' => $row['usedproducts'],
								'isauto' => $isauto,
								'isgift' => false,
								'isbalance' => false,
								'ischild' => true,
							);
						}
					}

					if ( ! empty( $return['force_add'] ) || ! empty( $return['product_discount'] ) || ! empty( $return['shipping_discount'] ) ) {
						// mark this order as having used a coupon so people cant go and use coupons over and over.
						$return['usedproducts'] = implode( ',', array_keys( $usedproducts ) );
						$master_output[ $coupon_row->id ] = array( $coupon_row, $return, $processed_coupons );
						unset( $this->error_msgs[ $coupon_row->id ] );
						continue;
					}
					continue;
				}
			}
		}

		if ( $this->finalize_coupon( $master_output ) ) {
			return true;
		}

		if ( ! isset( $this->error_msgs[ $this->coupon_row->id ] ) && 'combination' === $this->coupon_row->function_type ) {
			$this->return_false( 'errNoRecord' );
		}
		$this->coupon_row = null;
		$this->initialize_coupon();
		return false;
	}

	/**
	 * Process discount general
	 *
	 * @param object  $coupon_row coupon data.
	 * @param boolean $track_product_price track price.
	 **/
	protected function checkdiscount( $coupon_row, $track_product_price = false ) {
		$user = AC()->helper->get_user();

		if ( empty( $coupon_row ) ) {
			return;
		}
		if ( empty( $this->cart->items ) ) {
			return;
		}
		if ( empty( $this->cart->items_def ) ) {
			return;
		}

		$coupon_row->params = ! empty( $coupon_row->params ) ? ( is_string( $coupon_row->params ) ? json_decode( $coupon_row->params ) : $coupon_row->params ) : new stdclass();
		$coupon_row->asset = AC()->store->get_coupon_asset( $coupon_row );
		if ( isset( $coupon_row->_balance_exclude ) ) {
			$coupon_row->asset[0] = $coupon_row->_balance_exclude; // customer balance category and shipping restrictions.
		}

		$coupon_row->cart_items = $this->cart->items;
		$coupon_row->cart_items_breakdown = $this->cart->items_breakdown;
		$coupon_row->cart_items_def = $this->cart->items_def;
		$coupon_row->cart_shipping = $this->cart->shipping;
		$coupon_row->cart_extra = $this->cart->extra;

		if ( 1 === (int) $this->params->get( 'multiple_coupon_product_discount_limit', 0 ) ) {
			// stop product from being discounted more than once in the cart.
			foreach ( $coupon_row->cart_items_breakdown as $k => $a ) {
				if ( ! empty( $a['totaldiscount'] ) ) {
					unset( $coupon_row->cart_items_breakdown[ $k ] );
				}
			}
			if ( empty( $coupon_row->cart_items_breakdown ) ) {
				return;
			}
		}

		$coupon_row->is_discount_before_tax = 'giftcert' === $coupon_row->function_type ? $this->giftcert_discount_before_tax : $this->coupon_discount_before_tax;
		if ( ! empty( $coupon_row->tags['discount_before_tax'] ) && 1 === (int) $coupon_row->tags['discount_before_tax'] ) {
			$coupon_row->is_discount_before_tax = 1;
		} elseif ( ! empty( $coupon_row->tags['discount_after_tax'] ) && 1 === (int) $coupon_row->tags['discount_after_tax'] ) {
			$coupon_row->is_discount_before_tax = 0;
		} elseif ( ! empty( $coupon_row->note ) ) {
			$match = array();
			preg_match( '/{discount_before_tax:\s*(1|0)\s*}/i', $coupon_row->note, $match );
			if ( isset( $match[1] ) ) {
				$coupon_row->is_discount_before_tax = $match[1];
			}
		}

		$coupon_row->customer = new stdClass();
		$coupon_row->customer->user_id = (int) $user->id;

		$coupon_row->specific_min_value = 0;
		$coupon_row->specific_min_value_notax = 0;
		$coupon_row->specific_min_qty = 0;

		if ( ! empty( $coupon_row->min_value ) ) {
			$coupon_row->min_value = $this->get_amount_incurrency( $coupon_row->min_value );
		}
		if ( isset( $coupon_row->params->max_discount_amt ) ) {
			$coupon_row->params->max_discount_amt = $this->get_amount_incurrency( $coupon_row->params->max_discount_amt );
		}

		// ----------------------------------------------------
		// verify this coupon can be used in this circumstance
		// ----------------------------------------------------
		// .
		if ( 'giftcert' === $coupon_row->function_type ) {
			$r_err = '';
			AC()->helper->trigger( 'cmcouponOnAfterInitialCouponValidation', array( & $r_err, & $coupon_row ) );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err, 'custom_error' );
			}

			return $this->checkdiscount_giftcert( $coupon_row, $track_product_price );
		} elseif ( in_array( $coupon_row->function_type, array( 'coupon', 'shipping', 'buyxy', 'buyxy2' ), true ) ) {

			// verify total is up to the minimum value for the coupon.
			if ( ! empty( $coupon_row->min_value ) && round( $this->product_total, 4 ) < $coupon_row->min_value ) {
				return $this->return_false( 'errMinVal' );
			}
			if ( ! empty( $coupon_row->params->min_qty ) && $this->product_qty < $coupon_row->params->min_qty ) {
				return $this->return_false( 'errMinQty' );
			}

			$r_err = $this->couponvalidate_user( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			$r_err = $this->couponvalidate_usergroup( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// country state check.
			$r_err = $this->couponvalidate_country( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			$r_err = $this->couponvalidate_countrystate( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// payment method check.
			$r_err = $this->couponvalidate_paymentmethod( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// number of use check.
			$r_err = $this->couponvalidate_numuses( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// check for specials.
			$r_err = $this->couponvalidate_product_special( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// check for discounted products.
			$r_err = $this->couponvalidate_product_discounted( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			// check for giftcert products.
			$r_err = $this->couponvalidate_product_giftcert( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			$r_err = '';
			AC()->helper->trigger( 'cmcouponOnAfterInitialCouponValidation', array( & $r_err, & $coupon_row ) );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err, 'custom_error' );
			}

			switch ( $coupon_row->function_type ) {
				case 'coupon':
					return $this->checkdiscount_coupon( $coupon_row, $track_product_price );
				case 'shipping':
					return $this->checkdiscount_shipping( $coupon_row, $track_product_price );
				case 'buyxy':
					return $this->checkdiscount_buyxy( $coupon_row, $track_product_price );
				case 'buyxy2':
					return $this->checkdiscount_buyxy2( $coupon_row, $track_product_price );
			}
		}

		return $this->return_false( 'invalid function type' );
	}

	/**
	 * Process discount: giftcert
	 *
	 * @param object  $coupon_row coupon data.
	 * @param boolean $track_product_price track price.
	 **/
	private function checkdiscount_giftcert( $coupon_row, $track_product_price = false ) {

		$db = AC()->db;

		$_discount_product = 0;
		$_discount_product_notax = 0;
		$_discount_product_tax = 0;

		$_discount_extra = 0;
		$_discount_extra_notax = 0;
		$_discount_extra_tax = 0;

		$_discount_shipping = 0;
		$_discount_shipping_notax = 0;
		$_discount_shipping_tax = 0;

		$usedproductids = array();

		if ( empty( $coupon_row->function_type ) ) {
			return;
		}
		if ( 'giftcert' !== $coupon_row->function_type ) {
			return;
		}

		// ----------------------------------------------------
		// verify this coupon can be used in this circumstance
		// ----------------------------------------------------
		//
		// check value to make sure the full value of the gift cert has not been used.
		$giftcert_balance = AC()->coupon->get_giftcert_balance( $coupon_row->id );
		if ( empty( $giftcert_balance ) ) {
			// total value of gift cert is used up.
			return $this->return_false( 'errGiftUsed' );
		}

		// check for giftcert products to exclude.
		if ( ! empty( $coupon_row->params->exclude_giftcert ) ) {
			$ids = '';
			foreach ( $coupon_row->cart_items as $tmp ) {
				$ids .= $tmp['product_id'] . ',';
			}
			if ( ! empty( $ids ) ) {
				$test_list = $db->get_column( 'SELECT product_id FROM #__cmcoupon_giftcert_product WHERE estore="' . $this->estore . '" AND product_id IN (' . substr( $ids, 0, -1 ) . ')' );
				foreach ( $coupon_row->cart_items_breakdown as $k => $tmp ) {
					if ( in_array( $tmp['product_id'], $test_list ) ) {
						unset( $coupon_row->cart_items_breakdown[ $k ] );
					}
				}
			}
		}

		// check products to verify on asset list.
		$r_err = $this->couponvalidate_asset_producttype( $coupon_row );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}

		// remove products tha are not valid.
		foreach ( $coupon_row->cart_items_breakdown as $k => $row ) {
			if ( ! $this->is_product_eligible( $row['product_id'], $coupon_row ) ) {
				unset( $coupon_row->cart_items_breakdown[ $k ] );
			}
		}

		// check shipping.
		$total_shipping_notax = 0;
		$total_shipping = 0;
		$shipping_property = $coupon_row->cart_shipping;
		if ( ! empty( $shipping_property->total ) ) {
			foreach ( $coupon_row->cart_shipping->shippings as $k => $row ) {
				if ( ! empty( $coupon_row->asset[0]->rows->shipping->rows ) ) {

					$mode = empty( $coupon_row->asset[0]->rows->shipping->mode ) ? 'include' : $coupon_row->asset[0]->rows->shipping->mode;
					if ( ( 'include' === $mode && isset( $coupon_row->asset[0]->rows->shipping->rows[ $row->shipping_id ] ) )
					|| ( 'exclude' === $mode && ! isset( $coupon_row->asset[0]->rows->shipping->rows[ $row->shipping_id ] ) )
					) {
					} else {
						unset( $coupon_row->cart_shipping->shippings[ $k ] );
						continue;
					}
				}
				$total_shipping_notax += $row->total_notax;
				$total_shipping += $row->total;
			}
		}
		$coupon_row->giftcert_shipping = $total_shipping;
		$coupon_row->giftcert_shipping_notax = $total_shipping_notax;

		// check extra fee.
		$coupon_row->giftcert_extrafee = $coupon_row->cart_extra->total;
		$coupon_row->giftcert_extrafee_notax = $coupon_row->cart_extra->total_notax;

		// for zero value coupons.
		$coupon_row->coupon_value = (double) $coupon_row->coupon_value;
		if ( empty( $coupon_row->coupon_value ) && empty( $coupon_row->coupon_value_def ) ) {
			return $this->get_processed_discount_array( $coupon_row );
		}

		// ----------------------------------------------------
		// Compute Coupon Discount based on coupon parameters
		// ----------------------------------------------------
		//
		// gift certificate calculation.
		$coupon_value = $this->get_amount_incurrency( $giftcert_balance );
		if ( ! empty( $coupon_value ) && $coupon_value > 0 ) {
			$coupon_product_value = 0;
			$coupon_shipping_value = 0;
			$coupon_extrafee_value = 0;
			$coupon_product_value_notax = 0;
			$coupon_shipping_value_notax = 0;
			$coupon_extrafee_value_notax = 0;

			// product.
			$total_to_use = 0;
			$total_to_use_notax = 0;
			$qty = 0;
			foreach ( $coupon_row->cart_items_breakdown as $k => $row ) {
				if ( $row['product_price'] <= 0 ) {
					unset( $coupon_row->cart_items_breakdown[ $k ] );
					continue;
				}
				$total_to_use += $row['product_price'];
				$total_to_use_notax += $row['product_price_notax'];
				$usedproductids[ $row['product_id'] ] = $row['product_id'];
				$qty++;
			}
			$this->realtotal_verify( $total_to_use, $total_to_use_notax );

			if ( empty( $total_to_use ) && empty( $coupon_row->giftcert_shipping ) ) {
				return false;
			}

			$postfix = $coupon_row->is_discount_before_tax ? '_notax' : '';

			if ( ! empty( $total_to_use ) ) {
				// product calculation.
				$coupon_product_value = min( ${'total_to_use' . $postfix}, $coupon_value );
				$coupon_product_value_notax = $coupon_product_value;
				$tax_rate = round( ( $total_to_use - $total_to_use_notax ) / $total_to_use_notax, 4 );

				if ( $coupon_row->is_discount_before_tax ) {
					$coupon_product_value *= 1 + $tax_rate;
				} else {
					$coupon_product_value_notax /= 1 + $tax_rate;
				}
				if ( $coupon_product_value > $total_to_use ) {
					$coupon_product_value = $total_to_use;
				}
				if ( $coupon_product_value_notax > $total_to_use_notax ) {
					$coupon_product_value_notax = $total_to_use_notax;
				}
			}

			// shipping calculation.
			$total_shipping_notax = $coupon_row->giftcert_shipping_notax;
			$total_shipping = $coupon_row->giftcert_shipping;

			if ( ! empty( ${'total_shipping' . $postfix} ) && $coupon_value > ${'coupon_product_value' . $postfix} ) {
				$coupon_shipping_value = min( (float) ${'total_shipping' . $postfix}, $coupon_value - ${'coupon_product_value' . $postfix} );
				$coupon_shipping_value_notax = $coupon_shipping_value;
			}
			if ( ! empty( $coupon_shipping_value ) ) {
				$tax_rate = round( ( $total_shipping - $total_shipping_notax ) / $total_shipping_notax, 4 );

				if ( $coupon_row->is_discount_before_tax ) {
					$coupon_shipping_value *= 1 + $tax_rate;
				} else {
					$coupon_shipping_value_notax /= 1 + $tax_rate;
				}
				if ( $coupon_shipping_value > $total_shipping ) {
					$coupon_shipping_value = $total_shipping;
				}
				if ( $coupon_shipping_value_notax > $total_shipping_notax ) {
					$coupon_shipping_value_notax = $total_shipping_notax;
				}
			}

			// extrafee calculation.
			$total_extrafee_notax = $coupon_row->giftcert_extrafee_notax;
			$total_extrafee = $coupon_row->giftcert_extrafee;

			if ( ! empty( ${'total_extrafee' . $postfix} ) && $coupon_value > ${'coupon_product_value' . $postfix} ) {
				$coupon_extrafee_value = min( (float) ${'total_extrafee' . $postfix}, $coupon_value - ${'coupon_product_value' . $postfix} );
				$coupon_extrafee_value_notax = $coupon_extrafee_value;
			}
			if ( ! empty( $coupon_extrafee_value ) ) {
				$tax_rate = round( ( $total_extrafee - $total_extrafee_notax ) / $total_extrafee_notax, 4 );

				if ( $coupon_row->is_discount_before_tax ) {
					$coupon_extrafee_value *= 1 + $tax_rate;
				} else {
					$coupon_extrafee_value_notax /= 1 + $tax_rate;
				}
				if ( $coupon_extrafee_value > $total_extrafee ) {
					$coupon_extrafee_value = $total_extrafee;
				}
				if ( $coupon_extrafee_value_notax > $total_extrafee_notax ) {
					$coupon_extrafee_value_notax = $total_extrafee_notax;
				}
			}

			// Total Amount.
			$_discount_product = $coupon_product_value;
			$_discount_product_notax = $coupon_product_value_notax;
			$_discount_shipping = $coupon_shipping_value;
			$_discount_shipping_notax = $coupon_shipping_value_notax;
			$_discount_extrafee = $coupon_extrafee_value;
			$_discount_extrafee_notax = $coupon_extrafee_value_notax;
			if ( $coupon_row->is_discount_before_tax ) {
				$_discount_product_tax = $_discount_product - $_discount_product_notax;
				$_discount_shipping_tax = $_discount_shipping - $_discount_shipping_notax;
				$_discount_extrafee_tax = $_discount_extrafee - $_discount_extrafee_notax;
			}

			// track product/shipping discount.
			$this->cartitem_update( array(
				'track_product_price' => $track_product_price,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'coupon_row' => $coupon_row,
				'coupon_percent' => null,
				'discount_value' => $_discount_product,
				'discount_value_notax' => $_discount_product_notax,
				'shipping_discount_value' => $_discount_shipping,
				'shipping_discount_value_notax' => $_discount_shipping_notax,
				'extra_discount_value' => $_discount_extrafee,
				'extra_discount_value_notax' => $_discount_extrafee_notax,
				'qty' => $qty,
				'valid_items' => $coupon_row->cart_items_breakdown,
				'valid_ships' => $coupon_row->cart_shipping->shippings,
				'usedproductids' => $usedproductids,
			) );
		}

		if ( ! empty( $_discount_product ) || ! empty( $_discount_shipping ) ) {
			return array(
				'redeemed' => true,
				'coupon_id' => $coupon_row->id,
				'coupon_code' => $coupon_row->coupon_code,
				'product_discount' => $_discount_product,
				'product_discount_notax' => $_discount_product_notax,
				'product_discount_tax' => $_discount_product_tax,
				'shipping_discount' => $_discount_shipping,
				'shipping_discount_notax' => $_discount_shipping_notax,
				'shipping_discount_tax' => $_discount_shipping_tax,
				'extra_discount' => $_discount_extra,
				'extra_discount_notax' => $_discount_extra_notax,
				'extra_discount_tax' => $_discount_extra_tax,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'usedproducts' => ! empty( $usedproductids ) ? implode( ',', $usedproductids ) : '',
			);
		}
	}

	/**
	 * Process discount: coupon
	 *
	 * @param object  $coupon_row coupon data.
	 * @param boolean $track_product_price track price.
	 **/
	private function checkdiscount_coupon( $coupon_row, $track_product_price = false ) {

		$_discount_product = 0;
		$_discount_product_notax = 0;
		$_discount_product_tax = 0;

		$_discount_shipping = 0;
		$_discount_shipping_notax = 0;
		$_discount_shipping_tax = 0;

		$_discount_extra = 0;
		$_discount_extra_notax = 0;
		$_discount_extra_tax = 0;

		$usedproductids = array();

		if ( empty( $coupon_row->function_type ) ) {
			return;
		}
		if ( 'coupon' !== $coupon_row->function_type ) {
			return;
		}

		// ----------------------------------------------------
		// verify this coupon can be used in this circumstance
		// ----------------------------------------------------.
		// check specific to function type.
		$r_err = $this->couponvalidate_asset_producttype( $coupon_row );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}
		$r_err = $this->couponvalidate_min_total_qty( $coupon_row );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}

		// for zero value coupons.
		$coupon_row->coupon_value = (double) $coupon_row->coupon_value;
		if ( empty( $coupon_row->coupon_value ) && empty( $coupon_row->coupon_value_def ) ) {
			return $this->get_processed_discount_array( $coupon_row );
		}

		// ----------------------------------------------------
		// Compute Coupon Discount based on coupon parameters
		// ----------------------------------------------------.
		if ( ! empty( $coupon_row->coupon_value ) ) {
			// product/category discount.
			if ( in_array( $coupon_row->coupon_value_type, array( 'amount', 'amount_per' ), true ) ) {
				$coupon_row->coupon_value = $this->get_amount_incurrency( $coupon_row->coupon_value );
			}

			$total = 0;
			$total_notax = 0;
			$qty = 0;
			$valid_items = array();
			foreach ( $coupon_row->cart_items_breakdown as $breakdown_id => $row ) {
				if ( ! $this->is_product_eligible( $row['product_id'], $coupon_row ) ) {
					continue;
				}
				$usedproductids[] = $row['product_id'];
				$qty++;
				if ( $row['product_price'] <= 0 ) {
					continue;
				}
				$total += $row['product_price'];
				$total_notax += $row['product_price_notax'];

				$valid_items[ $breakdown_id ] = array(
					'key' => $row['key'],
					'product_id' => $row['product_id'],
					'product_price' => $row['product_price'],
					'product_price_notax' => $row['product_price_notax'],
				);
			}

			if ( ! empty( $total ) ) {
				$_discount_product = $coupon_row->coupon_value;
				$_discount_product_notax = $coupon_row->coupon_value;
				if ( 'percent' === $coupon_row->coupon_value_type ) {
					$_discount_product = round( $total * $_discount_product / 100, 4 );
					$_discount_product_notax = round( $total_notax * $_discount_product_notax / 100, 4 );
				} else {
					if ( 'amount_per' === $coupon_row->coupon_value_type ) {
						$_discount_product = 0;
						$_discount_product_notax = 0;
						$postfix = $coupon_row->is_discount_before_tax ? '_notax' : '';
						foreach ( $valid_items as $valid_item ) {
							$current_value = min( $coupon_row->coupon_value, $valid_item[ 'product_price' . $postfix ] );
							if ( $current_value <= 0 ) {
								continue;
							}
							$_discount_product += $current_value;
							$_discount_product_notax += $current_value;
						}
					}
					if ( $coupon_row->is_discount_before_tax ) {
						$_discount_product *= 1 + ( ( $total - $total_notax ) / $total_notax );
					} else {
						$_discount_product_notax /= 1 + ( ( $total - $total_notax ) / $total_notax );
					}
				}

				$this->get_max_discount_amount( $coupon_row, $_discount_product_notax, $_discount_product );

				if ( $total < $_discount_product ) {
					$_discount_product = (float) $total;
				}
				if ( $total_notax < $_discount_product_notax ) {
					$_discount_product_notax = (float) $total_notax;
				}

				$this->realtotal_verify( $_discount_product, $_discount_product_notax );

				if ( $coupon_row->is_discount_before_tax ) {
					$_discount_product_tax = $_discount_product - $_discount_product_notax;
				}

				// track product discount.
				$this->cartitem_update( array(
					'track_product_price' => $track_product_price,
					'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
					'coupon_row' => $coupon_row,
					'coupon_percent' => $coupon_row->coupon_value,
					'discount_value' => $_discount_product,
					'discount_value_notax' => $_discount_product_notax,
					'qty' => $qty,
					'valid_items' => $valid_items,
					'usedproductids' => $usedproductids,
				) );
			} elseif ( $qty > 0 && 1 === (int) $this->allow_zero_value ) {
				return $this->get_processed_discount_array( $coupon_row, $usedproductids );
			}
		} elseif ( empty( $coupon_row->coupon_value )
			&& ! empty( $coupon_row->coupon_value_def )
			&& preg_match( '/^(\d+\-\d+([.]\d+)?;)+(\[[_a-z]+\=[a-z]+(\&[_a-z]+\=[a-z]+)*\])?$/', $coupon_row->coupon_value_def ) ) {
			// cumulative coupon calculation.
			$vdef_table = array();
			$vdef_options = array();
			$each_row = explode( ';', $coupon_row->coupon_value_def );

			// options.
			$tmp = end( $each_row );
			if ( '[' === substr( $tmp, 0, 1 ) ) {
				parse_str( trim( $tmp, '[]' ), $vdef_options );
				array_pop( $each_row );
			}
			reset( $each_row );

			foreach ( $each_row as $row ) {
				if ( false !== strpos( $row, '-' ) ) {
					list( $p, $v ) = explode( '-', $row );
					$vdef_table[ $p ] = $v;
				}
			}
			$min_qty = 0;
			$max_qty = 0;
			if ( ! empty( $vdef_table ) ) {
				if ( count( $vdef_table ) > 1 ) {
					ksort( $vdef_table, SORT_NUMERIC );
					$tmp_table = $vdef_table;

					// test for min qty.
					reset( $tmp_table );
					$tmp = current( $tmp_table );
					if ( empty( $tmp ) ) {
						$min_qty = key( $tmp_table ) + 1;
					}
					// test for max qty.
					$tmp = end( $tmp_table ); // last element in array.
					if ( empty( $tmp ) ) {
						$max_qty = key( $tmp_table ) - 1; // last key in array - 1.
					}
				}

				$curr_qty = 0;
				$qty = 0;
				$total = 0;
				$total_notax = 0;
				$qty_distinct = array();
				$valid_items = array();

				$cart_items = $coupon_row->cart_items_breakdown;
				// reorder items in cart if needed.
				if ( ! empty( $vdef_options['order'] ) ) {
					if ( 'first' === $vdef_options['order'] ) {
					} else {
						$cart_items = array();
						$item_index = array();
						foreach ( $coupon_row->cart_items_breakdown as $key => $row ) {
							$item_index[ $key ] = $row['product_price'];
						}
						if ( 'lowest' === $vdef_options['order'] ) {
							asort( $item_index, SORT_NUMERIC );
						} elseif ( 'highest' === $vdef_options['order'] ) {
							arsort( $item_index, SORT_NUMERIC );
						}

						foreach ( $item_index as $key => $price ) {
							$this_item = $coupon_row->cart_items_breakdown[ $key ];
							$this_item['breakdown_id'] = $key;
							$cart_items[] = $this_item;
						}
					}
				}
				if ( empty( $vdef_options['type'] ) || 'progressive' === $vdef_options['type'] ) {
					foreach ( $cart_items as $breakdown_id => $row ) {
						if ( empty( $row['product_price'] ) ) {
							continue;
						}
						if ( ! $this->is_product_eligible( $row['product_id'], $coupon_row ) ) {
							continue;
						}
						$curr_qty++;
						$qty++;
						if ( ! isset( $qty_distinct[ $row['product_id'] ] ) ) {
							$qty_distinct[ $row['product_id'] ] = 0;
						}
						$qty_distinct[ $row['product_id'] ]++;
						if ( ! empty( $vdef_options['qty_type'] ) && 'distinct' === $vdef_options['qty_type'] ) {
							$curr_qty = count( $qty_distinct );
						}
						if ( ! empty( $min_qty ) && $curr_qty < $min_qty ) {
							continue;
						}
						if ( ! empty( $max_qty ) && $curr_qty > $max_qty ) {
							continue;
						}

						$usedproductids[] = $row['product_id'];
						$total += $row['product_price'];
						$total_notax += $row['product_price_notax'];
						if ( isset( $row['breakdown_id'] ) ) {
							$breakdown_id = $row['breakdown_id'];
						}
						$valid_items[ $breakdown_id ] = array(
							'key' => $row['key'],
							'product_id' => $row['product_id'],
							'product_price' => $row['product_price'],
							'product_price_notax' => $row['product_price_notax'],
						);
					}

					if ( ! empty( $qty ) ) {

						if ( ! empty( $max_qty ) ) {
							array_pop( $vdef_table );
						}
						krsort( $vdef_table, SORT_NUMERIC );
						if ( ! empty( $vdef_options['qty_type'] ) && 'distinct' === $vdef_options['qty_type'] ) {
							$qty = count( $qty_distinct );
						}

						foreach ( $vdef_table as $pcount => $val ) {
							if ( $pcount <= $qty ) {
								$coupon_value = $val;
								break;
							}
						}
						if ( ! empty( $coupon_value ) ) {

							if ( in_array( $coupon_row->coupon_value_type, array( 'amount', 'amount_per' ), true ) ) {
								$coupon_value = $this->get_amount_incurrency( $coupon_value );
							}
							if ( ! empty( $total ) ) {
								$_discount_product = $coupon_value;
								$_discount_product_notax = $coupon_value;

								if ( 'percent' === $coupon_row->coupon_value_type ) {
									$_discount_product = round( $total * $_discount_product / 100, 4 );
									$_discount_product_notax = round( $total_notax * $_discount_product_notax / 100, 4 );
								} else {
									if ( 'amount_per' === $coupon_row->coupon_value_type ) {
										$_discount_product = 0;
										$_discount_product_notax = 0;
										$postfix = $coupon_row->is_discount_before_tax ? '_notax' : '';
										foreach ( $valid_items as $valid_item ) {
											$current_value = min( $coupon_value, $valid_item[ 'product_price' . $postfix ] );
											if ( $current_value <= 0 ) {
												continue;
											}
											$_discount_product += $current_value;
											$_discount_product_notax += $current_value;
										}
									}
									if ( $coupon_row->is_discount_before_tax ) {
										$_discount_product *= 1 + ( ( $total - $total_notax ) / $total_notax );
									} else {
										$_discount_product_notax /= 1 + ( ( $total - $total_notax ) / $total_notax );
									}
								}

								$this->get_max_discount_amount( $coupon_row, $_discount_product_notax, $_discount_product );

								if ( $total < $_discount_product ) {
									$_discount_product = (float) $total;
								}
								if ( $total_notax < $_discount_product_notax ) {
									$_discount_product_notax = (float) $total_notax;
								}
								$this->realtotal_verify( $_discount_product, $_discount_product_notax );

								if ( $coupon_row->is_discount_before_tax ) {
									$_discount_product_tax = $_discount_product - $_discount_product_notax;
								}

								// track product discount.
								$this->cartitem_update( array(
									'track_product_price' => $track_product_price,
									'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
									'coupon_row' => $coupon_row,
									'coupon_percent' => $coupon_value,
									'discount_value' => $_discount_product,
									'discount_value_notax' => $_discount_product_notax,
									'qty' => $qty,
									'valid_items' => $valid_items,
									'usedproductids' => $usedproductids,
								) );
							} elseif ( 1 === $this->allow_zero_value ) {
								return $this->get_processed_discount_array( $coupon_row, $usedproductids );
							}
						} else {
							// cumulative coupon, threshold not reached.
							return $this->return_false( 'errProgressiveThreshold' );
						}
					}
				} elseif ( 'step' === $vdef_options['type'] ) {
					$_mapstep = array();

					$the_keys = array_keys( $vdef_table );
					foreach ( $vdef_table as $pcount => $val ) {
						if ( empty( $val ) ) {
							continue;
						}
						$_mapstep[ $pcount ] = $val;

						$j = array_search( $pcount, $the_keys, true );
						if ( ! isset( $the_keys[ $j + 1 ] ) ) {
							continue;
						}
						$forward = $the_keys[ $j + 1 ];
						for ( $k = $pcount + 1; $k < $the_keys[ $j + 1 ]; $k++ ) {
							$_mapstep[ $k ] = 'percent' === $coupon_row->coupon_value_type || 'amount_per' === $coupon_row->coupon_value_type ? $val : 0;
						}
					}
					if ( empty( $min_qty ) ) {
						$min_qty = min( array_keys( $_mapstep ) );
					}
					$value = 0;
					$value_notax = 0;
					foreach ( $cart_items as $breakdown_id => $row ) {
						if ( empty( $row['product_price'] ) ) {
							continue;
						}
						if ( ! $this->is_product_eligible( $row['product_id'], $coupon_row ) ) {
							continue;
						}
						$curr_qty++;
						$qty++;

						if ( ! empty( $vdef_options['qty_type'] ) && 'distinct' === $vdef_options['qty_type'] && isset( $qty_distinct[ $row['product_id'] ] ) ) {
							continue;
						}
						if ( ! isset( $qty_distinct[ $row['product_id'] ] ) ) {
							$qty_distinct[ $row['product_id'] ] = 0;
						}
						$qty_distinct[ $row['product_id'] ]++;
						if ( ! empty( $vdef_options['qty_type'] ) && 'distinct' === $vdef_options['qty_type'] ) {
							$curr_qty = count( $qty_distinct );
						}
						if ( ! empty( $min_qty ) && $curr_qty < $min_qty ) {
							continue;
						}
						if ( ! empty( $max_qty ) && $curr_qty > $max_qty ) {
							continue;
						}
						$usedproductids[] = $row['product_id'];
						$total += $row['product_price'];
						$total_notax += $row['product_price_notax'];

						$valtouse = isset( $_mapstep[ $curr_qty ] ) ? $_mapstep[ $curr_qty ] : end( $_mapstep );
						if ( 'percent' === $coupon_row->coupon_value_type ) {
							$value += round( $row['product_price'] * $valtouse / 100, 4 );
							$value_notax += round( $row['product_price_notax'] * $valtouse / 100, 4 );
						} else {
							$value += min( $valtouse, $coupon_row->is_discount_before_tax ? $row['product_price_notax'] : $row['product_price'] );
							$value_notax += min( $valtouse, $coupon_row->is_discount_before_tax ? $row['product_price_notax'] : $row['product_price'] );
						}
						if ( isset( $row['breakdown_id'] ) ) {
							$breakdown_id = $row['breakdown_id'];
						}
						$valid_items[ $breakdown_id ] = array(
							'key' => $row['key'],
							'product_id' => $row['product_id'],
							'product_price' => $row['product_price'],
							'product_price_notax' => $row['product_price_notax'],
						);
					}

					if ( ! empty( $value ) ) {

						if ( in_array( $coupon_row->coupon_value_type, array( 'amount', 'amount_per' ), true ) ) {
							$value = $this->get_amount_incurrency( $value );
							$value_notax = $this->get_amount_incurrency( $value_notax );
						}
						$_discount_product = $value;
						$_discount_product_notax = $value_notax;
						if ( 'percent' !== $coupon_row->coupon_value_type ) {
							if ( $coupon_row->is_discount_before_tax ) {
								$_discount_product *= 1 + ( ( $total - $total_notax ) / $total_notax );
							} else {
								$_discount_product_notax /= 1 + ( ( $total - $total_notax ) / $total_notax );
							}
						}

						$this->get_max_discount_amount( $coupon_row, $_discount_product_notax, $_discount_product );

						if ( $total < $_discount_product ) {
							$_discount_product = (float) $total;
						}
						if ( $total_notax < $_discount_product_notax ) {
							$_discount_product_notax = (float) $total_notax;
						}

						$this->realtotal_verify( $_discount_product, $_discount_product_notax );

						if ( $coupon_row->is_discount_before_tax ) {
							$_discount_product_tax = $_discount_product - $_discount_product_notax;
						}

						// track product discount.
						$this->cartitem_update( array(
							'track_product_price' => $track_product_price,
							'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
							'coupon_row' => $coupon_row,
							'coupon_percent' => null,
							'discount_value' => $_discount_product,
							'discount_value_notax' => $_discount_product_notax,
							'qty' => $qty,
							'valid_items' => $valid_items,
							'usedproductids' => $usedproductids,
						) );
					} else {
						// cumulative coupon, threshold not reached.
						return $this->return_false( 'errProgressiveThreshold' );
					}
				}
			}
		}

		if ( ! empty( $_discount_product ) || ! empty( $_discount_shipping ) ) {
			return array(
				'redeemed' => true,
				'coupon_id' => $coupon_row->id,
				'coupon_code' => $coupon_row->coupon_code,
				'product_discount' => $_discount_product,
				'product_discount_notax' => $_discount_product_notax,
				'product_discount_tax' => $_discount_product_tax,
				'shipping_discount' => $_discount_shipping,
				'shipping_discount_notax' => $_discount_shipping_notax,
				'shipping_discount_tax' => $_discount_shipping_tax,
				'extra_discount' => $_discount_extra,
				'extra_discount_notax' => $_discount_extra_notax,
				'extra_discount_tax' => $_discount_extra_tax,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'usedproducts' => ! empty( $usedproductids ) ? implode( ',', $usedproductids ) : '',
			);
		}
	}

	/**
	 * Process discount: buyxy
	 *
	 * @param object  $coupon_row coupon data.
	 * @param boolean $track_product_price track price.
	 **/
	private function checkdiscount_buyxy( $coupon_row, $track_product_price = false ) {

		$_discount_product = 0;
		$_discount_product_notax = 0;
		$_discount_product_tax = 0;

		$_discount_shipping = 0;
		$_discount_shipping_notax = 0;
		$_discount_shipping_tax = 0;

		$_discount_extra = 0;
		$_discount_extra_notax = 0;
		$_discount_extra_tax = 0;

		$usedproductids = array();

		if ( empty( $coupon_row->function_type ) ) {
			return;
		}
		if ( 'buyxy' !== $coupon_row->function_type ) {
			return;
		}

		// ----------------------------------------------------
		// verify this coupon can be used in this circumstance
		// ----------------------------------------------------.
		if ( 1 === 1 ) {

			// check specific to function type.
			$do_continue = false;
			$do_count = 0;
			do {
				$products_x_count = 0;
				$products_y_count = 0;
				$products_x_list = array();
				$products_y_list = array();

				// validate include xclude.
				$r_err = $this->couponvalidate_include_exclude( $coupon_row, 1, array(
					'error_include' => 'errBuyXYList1IncludeEmpty',
					'error_exclude' => 'errBuyXYList1ExcludeEmpty',
					'is_update_product_total' => true,
					'is_update_product_count' => false,
					'is_update_is_valid_type' => false,
				) );
				if ( ! empty( $r_err ) ) {
					return $this->return_false( $r_err );
				}
				$products_x_count = $coupon_row->temporary['products_count'];
				$products_x_list = $coupon_row->temporary['products_list'];

				$r_err = $this->couponvalidate_min_total_qty( $coupon_row );
				if ( ! empty( $r_err ) ) {
					return $this->return_false( $r_err );
				}

				if ( ! empty( $coupon_row->asset[2] ) ) {
					$asset_types = array( 'product', 'category', 'manufacturer', 'vendor', 'custom' );
					foreach ( $coupon_row->asset[2]->rows as $asset_type => $asset_row ) {
						if ( ! in_array( $asset_type, $asset_types, true ) ) {
							continue;
						}
						if ( empty( $asset_row->rows ) ) {
							continue;
						}

						$mode[ $asset_type ] = empty( $asset_row->mode ) ? 'include' : $asset_row->mode;
						$assetlist[ $asset_type ] = $asset_row->rows;

						$tmp = call_user_func( array( $this, 'get_store' . $asset_type ) , implode( ',', array_keys( $coupon_row->cart_items_def ) ) );
						foreach ( $tmp as $tmp2 ) {
							if ( isset( $assetlist[ $asset_type ][ $tmp2->asset_id ] ) ) {
								$coupon_row->cart_items_def[ $tmp2->product_id ][ $asset_type ] = $tmp2->asset_id;
							}
						}
					}
				}

				if ( ! $do_continue && ! empty( $coupon_row->params->addtocart ) ) {
					if ( $this->checkdiscount_buyxy_addtocart( $coupon_row, $products_x_count, $products_x_list ) ) {
						$do_continue = true;
					}
				}
				if ( $this->reprocess ) {
					return;
				}
				$do_count++;

			} while ( $do_count <= 1 && $do_continue );

			// validate include xclude.
			$r_err = $this->couponvalidate_include_exclude( $coupon_row, 2, array(
				'error_include' => 'errBuyXYList2IncludeEmpty',
				'error_exclude' => 'errBuyXYList2ExcludeEmpty',
				'is_update_product_total' => false,
				'is_update_product_count' => false,
				'is_update_is_valid_type' => false,
			) );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}
			$products_y_count = $coupon_row->temporary['products_count'];
			$products_y_list = $coupon_row->temporary['products_list'];

			// global checks.
			$r_err = $this->couponvalidate_min_total_qty( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}
		}

		// for zero value coupons.
		$coupon_row->coupon_value = (double) $coupon_row->coupon_value;
		if ( empty( $coupon_row->coupon_value ) && empty( $coupon_row->coupon_value_def ) ) {
			return $this->get_processed_discount_array( $coupon_row );
		}
		if ( in_array( $coupon_row->coupon_value_type, array( 'amount', 'amount_per' ), true ) ) {
			$coupon_row->coupon_value = $this->get_amount_incurrency( $coupon_row->coupon_value );
		}

		// ----------------------------------------------------
		// Compute Coupon Discount based on coupon parameters
		// ----------------------------------------------------.
		$valid_items = array();
		$potential_items = array();
		$potential_items_details = array();
		$asset1_qty = (int) $coupon_row->asset[1]->qty;
		$asset2_qty = (int) $coupon_row->asset[2]->qty;

		$i = 0;
		foreach ( $coupon_row->cart_items_breakdown as $key => $row ) {
			if ( $row['product_price'] <= 0 ) {
				continue;
			}
			if ( ! isset( $products_y_list[ $row['product_id'] ] ) ) {
				continue;
			}
			if ( ! empty( $coupon_row->params->product_match ) ) {
				$potential_items[ $row['product_id'] ][ $i ] = $row['product_price'];
				$potential_items_details[ $row['product_id'] ][ $i ] = array(
					'key' => $row['key'],
					'product_id' => $row['product_id'],
					'price' => $row['product_price'],
					'product_price_notax' => $row['product_price_notax'],
					'qty' => $coupon_row->cart_items[ $row['key'] ]['qty'],
				);
			} else {
				$potential_items[0][ $i ] = $row['product_price'];
				$potential_items_details[0][ $i ] = array(
					'key' => $row['key'],
					'product_id' => $row['product_id'],
					'price' => $row['product_price'],
					'product_price_notax' => $row['product_price_notax'],
					'qty' => $coupon_row->cart_items[ $row['key'] ]['qty'],
				);
			}
			$i++;
		}

		if ( ! empty( $potential_items )
		&& ! empty( $asset1_qty ) && $asset1_qty > 0
		&& ! empty( $asset2_qty ) && $asset2_qty > 0 ) {

			if ( ! empty( $coupon_row->params->product_match ) ) {
				if ( 'first' === $coupon_row->params->process_type ) {
				} else {
					$tester = array();
					foreach ( $potential_items as $k => $r1 ) {
						foreach ( $r1 as $r2 ) {
							$tester[ $k ] = $r2;
							break;
						}
					}
					if ( 'lowest' === $coupon_row->params->process_type ) {
						asort( $tester, SORT_NUMERIC );
					} elseif ( 'highest' === $coupon_row->params->process_type ) {
						arsort( $tester, SORT_NUMERIC );
					}

					$tmp = $potential_items;
					$potential_items = array();
					foreach ( $tester as $key => $val ) {
						$potential_items[ $key ] = $tmp[ $key ];
					}
				}
			} else {
				// reorder all x items that can be in y at the bottom.
				$tmp_potential_items = $potential_items;
				$tmp_potential_items_details = $potential_items_details;
				$potential_items = array();
				$potential_items_details = array();
				$potential_x_items = array();
				$potential_x_items_details = array();
				foreach ( $tmp_potential_items_details[0] as $ppindex => $_potential ) {
					if ( in_array( $_potential['product_id'], $products_x_list ) ) {
						$potential_x_items[] = $tmp_potential_items[0][ $ppindex ];
						$potential_x_items_details[] = $tmp_potential_items_details[0][ $ppindex ];
					} else {
						$potential_items[0][] = $tmp_potential_items[0][ $ppindex ];
						$potential_items_details[0][] = $tmp_potential_items_details[0][ $ppindex ];
					}
				}
				if ( ! empty( $potential_x_items ) ) {
					foreach ( $potential_x_items as $ppindex => $_potential_list ) {
						$potential_items[0][] = $potential_x_items[ $ppindex ];
						$potential_items_details[0][] = $potential_x_items_details[ $ppindex ];
					}
				}

				if ( 'first' === $coupon_row->params->process_type ) {
				} elseif ( 'lowest' === $coupon_row->params->process_type ) {
					asort( $potential_items[0], SORT_NUMERIC );
				} elseif ( 'highest' === $coupon_row->params->process_type ) {
					arsort( $potential_items[0], SORT_NUMERIC );
				}
			}

			$used_y_items = array();
			foreach ( $potential_items as $pindex => $current_potential_item ) {
				$t_products_x_count = ! empty( $coupon_row->params->product_match ) ? count( $current_potential_item ) : $products_x_count;

				while ( $t_products_x_count >= $asset1_qty ) {
					$t_products_x_count -= $asset1_qty;
					$items = array();
					for ( $j = 0; $j < $asset2_qty; $j++ ) {
						if ( empty( $current_potential_item ) ) {
							break 2;
						}
						$keys = array_keys( $current_potential_item );
						$pkey = array_shift( $keys );
						$item = $potential_items_details[ $pindex ][ $pkey ];
						unset( $current_potential_item[ $pkey ] );

						if ( in_array( $item['product_id'], $products_x_list ) ) {
							$t_products_x_count--;
						}

						if ( $t_products_x_count < 0 ) {
							break 2; // not enough products, error.
						}
						$items[] = $item;
						$used_y_items[ $item['product_id'] ] = $item['product_id'];
					}
					$valid_items = array_merge( $valid_items, $items );
				}
			}

			if ( ! empty( $coupon_row->params->max_discount_qty ) ) {
				$valid_items = array_slice( $valid_items, 0, $coupon_row->params->max_discount_qty * $asset2_qty );
			}
			if ( empty( $valid_items ) ) {
				return $this->return_false( 'errBuyXYList1IncludeEmpty' );
			}

			$total = 0;
			$total_notax = 0;
			$qty = count( $valid_items );
			foreach ( $valid_items as $product_key => $item ) {
				$total += $item['price'];
				$total_notax += $item['product_price_notax'];
				$usedproductids[ $item['product_id'] ] = $item['product_id'];
			}

			if ( ! empty( $total ) ) {
				$_discount_product = $coupon_row->coupon_value;
				$_discount_product_notax = $coupon_row->coupon_value;
				if ( 'percent' === $coupon_row->coupon_value_type ) {
					$_discount_product = round( $total * $_discount_product / 100, 4 );
					$_discount_product_notax = round( $total_notax * $_discount_product_notax / 100, 4 );
				} else {
					if ( 'amount_per' === $coupon_row->coupon_value_type ) {
						// set amount discount to the number of valid discounts.
						$_discount_product = $coupon_row->coupon_value * count( $valid_items );
						$_discount_product_notax = $coupon_row->coupon_value * count( $valid_items );
					}

					if ( $coupon_row->is_discount_before_tax ) {
						$_discount_product *= 1 + ( ( $total - $total_notax ) / $total_notax );
					} else {
						$_discount_product_notax /= 1 + ( ( $total - $total_notax ) / $total_notax );
					}
				}

				$this->get_max_discount_amount( $coupon_row, $_discount_product_notax, $_discount_product );

				if ( $total < $_discount_product ) {
					$_discount_product = (float) $total;
				}
				if ( $total_notax < $_discount_product_notax ) {
					$_discount_product_notax = (float) $total_notax;
				}

				$this->realtotal_verify( $_discount_product, $_discount_product_notax );

				if ( $coupon_row->is_discount_before_tax ) {
					$_discount_product_tax = $_discount_product - $_discount_product_notax;
				}

				// track product discount.
				$this->cartitem_update(array(
					'track_product_price' => $track_product_price,
					'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
					'coupon_row' => $coupon_row,
					'coupon_percent' => $coupon_row->coupon_value,
					'discount_value' => $_discount_product,
					'discount_value_notax' => $_discount_product_notax,
					'qty' => $qty,
					'valid_items' => $valid_items,
					'usedproductids' => $usedproductids,
				));
			} elseif ( $qty > 0 && 1 === $this->allow_zero_value ) {
				return $this->get_processed_discount_array( $coupon_row, $usedproductids );
			}
		}

		if ( ! empty( $_discount_product ) || ! empty( $_discount_shipping ) ) {
			return array(
				'redeemed' => true,
				'coupon_id' => $coupon_row->id,
				'coupon_code' => $coupon_row->coupon_code,
				'product_discount' => $_discount_product,
				'product_discount_notax' => $_discount_product_notax,
				'product_discount_tax' => $_discount_product_tax,
				'shipping_discount' => $_discount_shipping,
				'shipping_discount_notax' => $_discount_shipping_notax,
				'shipping_discount_tax' => $_discount_shipping_tax,
				'extra_discount' => $_discount_extra,
				'extra_discount_notax' => $_discount_extra_notax,
				'extra_discount_tax' => $_discount_extra_tax,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'usedproducts' => ! empty( $usedproductids ) ? implode( ',', $usedproductids ) : '',
			);
		}
	}

	/**
	 * Process discount: buyxy (add to cart)
	 *
	 * @param object $coupon_row coupon data.
	 * @param int    $products_x_count total asset x.
	 * @param array  $products_x_list list of asset x.
	 **/
	private function checkdiscount_buyxy_addtocart( &$coupon_row, $products_x_count, $products_x_list ) {

		$asset1_qty = (int) $coupon_row->asset[1]->qty;
		$asset2_qty = (int) $coupon_row->asset[2]->qty;
		if ( $asset1_qty < 1 || $asset2_qty < 1 ) {
			return;
		}
		$asset2list = $coupon_row->asset[2]->rows;
		// change format from asset2list[asset_id] = asset_id
		// to asset2list->asset_type->rows[asset_id] = cmcoupon_asset object.
		$potential_items = array();
		$products_y_list = array();
		$products_y_count = 0;

		foreach ( $coupon_row->cart_items_breakdown as $row ) {
			$is_valid = true;
			foreach ( $asset2list as $asset_type => $assetelement ) {
				if ( empty( $assetelement->rows ) ) {
					continue;
				}
				$mode = empty( $assetelement->mode ) ? 'include' : $assetelement->mode;

				if ( ! isset( $coupon_row->cart_items_def[ $row['product_id'] ] ) ) {
					$coupon_row->cart_items_def[ $row['product_id'] ] = -1;
				}
				if (
					( 'include' === $mode && ( ! isset( $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ) || ! isset( $assetelement->rows[ $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ] ) ) )
				|| ( 'exclude' === $mode && isset( $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ) && isset( $assetelement->rows[ $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ] ) )
				) {
					$is_valid = false;
				}
			}

			if ( $is_valid ) {
				$products_y_count++;
				$products_y_list[ $row['product_id'] ] = $row['product_id'];
			}
		}

		$i = 0;
		foreach ( $coupon_row->cart_items_breakdown as $product_id => $row ) {
			if ( ! empty( $coupon_row->params->product_match ) ) {
				$potential_items[ $row['product_id'] ][ $i ] = $row['product_id'];
			} else {
				$potential_items[0][ $i ] = $row['product_id'];
			}
			$i++;
		}

		if ( empty( $potential_items ) ) {
			return;
		}

		$adding = array();
		$added_y_list = array();
		$used_max_discount_qty = 0;
		foreach ( $potential_items as $pindex => $current_potential_item ) {

			$t_products_x_count = ! empty( $coupon_row->params->product_match ) ? count( $current_potential_item ) : $products_x_count;
			$t_products_y_count = 0;
			while ( $t_products_x_count > 0 ) {
				if ( ! empty( $coupon_row->params->max_discount_qty ) && $used_max_discount_qty >= $coupon_row->params->max_discount_qty ) {
					break;
				}
				for ( $i = 0; $i < $asset1_qty; $i++ ) {
					$is_unset = false;
					foreach ( $current_potential_item as $ppindex => $product_id ) {
						if ( $t_products_x_count <= 0 ) {
							break 3;
						}
						if ( isset( $products_x_list[ $product_id ] ) ) {
							$is_unset = true;
							unset( $current_potential_item[ $ppindex ] );
							$t_products_x_count--;
							break;
						}
					}
					if ( ! $is_unset ) {
						break 2;
					}
				}

				$used_max_discount_qty++;

				for ( $i = 0; $i < $asset2_qty; $i++ ) {
					$isfound_ppindex = -1;
					foreach ( $current_potential_item as $ppindex => $product_id ) {
						if ( isset( $products_y_list[ $product_id ] ) ) {
							$isfound_ppindex = $ppindex;
							$added_y_list[ $product_id ] = $product_id;
							unset( $current_potential_item[ $ppindex ] );
							break;
						}
					}
					if ( -1 === $isfound_ppindex ) {
						$t_products_y_count++;
					}
				}
			}
			if ( empty( $t_products_y_count ) || $t_products_y_count < 0 ) {
				continue;
			}
			if ( ! isset( $adding[ $pindex ] ) ) {
				$adding[ $pindex ] = 0;
			}
			$adding[ $pindex ] += $t_products_y_count;

			if ( ! empty( $coupon_row->params->max_discount_qty ) && $used_max_discount_qty >= $coupon_row->params->max_discount_qty ) {
				break;
			}
		}

		if ( empty( $adding ) ) {
			return;
		}

		foreach ( $adding as $item_id => $qty ) {
			if ( ! empty( $item_id ) ) {
				$this->add_to_cart( $item_id, $qty );
			} else {
				$product_id = $this->buyxy_getproduct( $coupon_row,$asset2list );
				if ( ! empty( $product_id ) ) {
					$this->add_to_cart( $product_id, $qty );
				}
			}
		}

		$this->define_cart_items( true );
		foreach ( $this->cart->items as $k => $r ) {
			$is_found = false;
			foreach ( $coupon_row->cart_items as $k2 => $item ) {
				if ( $item['product_id'] === $r['product_id'] && $item['product_price'] === $r['product_price'] && $item['discount'] === $r['discount'] ) {
					$is_found = true;
					$qty_difference = $r['qty'] - $coupon_row->cart_items[ $k2 ]['qty'];
					$coupon_row->cart_items[ $k2 ]['qty'] = $r['qty'];
					if ( $qty_difference > 0 ) {
						$r2 = $this->cart->items[ $k ];
						unset( $r2['qty'] );
						$r2['key'] = $k;
						for ( $i = 0; $i < $qty_difference; $i++ ) {
							$coupon_row->cart_items_breakdown[] = $r2;
						}
					}
					break;
				}
			}
			if ( ! $is_found ) {
				$coupon_row->cart_items[ $k ] = $this->cart->items[ $k ];
				$coupon_row->cart_items_def[ $r['product_id'] ]['product'] = $r['product_id'];

				$r2 = $this->cart->items[ $k ];
				unset( $r2['qty'] );
				$r2['key'] = $k;
				for ( $i = 0; $i < $r['qty']; $i++ ) {
					$coupon_row->cart_items_breakdown[] = $r2;
				}
			}
		}
		foreach ( $asset2list as $asset_type => $asset_row ) {

			$mode = empty( $asset_row->mode ) ? 'include' : $asset_row->mode;
			$assetlist = $asset_row->rows;

			$tmp = call_user_func( array( $this, 'get_store' . $asset_type ), implode( ',', array_keys( $coupon_row->cart_items_def ) ) );
			foreach ( $tmp as $tmp2 ) {
				if ( isset( $assetlist[ $tmp2->asset_id ] ) ) {
					$coupon_row->cart_items_def[ $tmp2->product_id ][ $asset_type ] = $tmp2->asset_id;
				}
			}
		}
		return true;
	}

	/**
	 * Process discount: buyxy2
	 *
	 * @param object  $coupon_row coupon data.
	 * @param boolean $track_product_price track price.
	 **/
	private function checkdiscount_buyxy2( $coupon_row, $track_product_price = false ) {

		$_discount_product = 0;
		$_discount_product_notax = 0;
		$_discount_product_tax = 0;

		$_discount_shipping = 0;
		$_discount_shipping_notax = 0;
		$_discount_shipping_tax = 0;

		$_discount_extra = 0;
		$_discount_extra_notax = 0;
		$_discount_extra_tax = 0;

		$usedproductids = array();

		if ( empty( $coupon_row->function_type ) ) {
			return;
		}
		if ( 'buyxy2' !== $coupon_row->function_type ) {
			return;
		}

		// ----------------------------------------------------
		// verify this coupon can be used in this circumstance
		// ----------------------------------------------------
		// check specific to function type.
		$do_continue = false;
		$do_count = 0;
		do {
			$products_x_count  = 0;
			$products_y_count = 0;
			$products_x_list = array();
			$products_y_list = array();

			// validate include xclude.
			$r_err = $this->couponvalidate_include_exclude( $coupon_row, 3, array(
				'error_include' => 'errBuyXYList1IncludeEmpty',
				'error_exclude' => 'errBuyXYList1ExcludeEmpty',
				'is_update_product_total' => true,
				'is_update_product_count' => false,
				'is_update_is_valid_type' => false,
				'buyxy2_section' => 'x',
			) );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}
			$products_x_count = $coupon_row->temporary['products_count'];
			$products_x_list = $coupon_row->temporary['products_list'];

			$r_err = $this->couponvalidate_min_total_qty( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}

			if ( $this->reprocess ) {
				return;
			}
			$do_count++;

		} while ( $do_count <= 1 && $do_continue );

		// validate include xclude.
		$r_err = $this->couponvalidate_include_exclude( $coupon_row, 4, array(
			'error_include' => 'errBuyXYList2IncludeEmpty',
			'error_exclude' => 'errBuyXYList2ExcludeEmpty',
			'is_update_product_total' => false,
			'is_update_product_count' => false,
			'is_update_is_valid_type' => false,
			'buyxy2_section' => 'y',
		) );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}
		$products_y_count = $coupon_row->temporary['products_count'];
		$products_y_list = $coupon_row->temporary['products_list'];

		// global checks.
		$r_err = $this->couponvalidate_min_total_qty( $coupon_row );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}

		// for zero value coupons.
		$coupon_row->coupon_value = (double) $coupon_row->coupon_value;
		if ( empty( $coupon_row->coupon_value ) && empty( $coupon_row->coupon_value_def ) ) {
			return $this->get_processed_discount_array( $coupon_row );
		}
		if ( in_array( $coupon_row->coupon_value_type, array( 'amount', 'amount_per' ), true ) ) {
			$coupon_row->coupon_value = $this->get_amount_incurrency( $coupon_row->coupon_value );
		}

		// ----------------------------------------------------
		// Compute Coupon Discount based on coupon parameters
		// ----------------------------------------------------.
		$valid_items = array();
		$potential_items = array();
		$potential_items_details = array();

		$i = 0;
		foreach ( $coupon_row->cart_items_breakdown as $key => $row ) {
			if ( $row['product_price'] <= 0 ) {
				continue;
			}
			if ( empty( $row['buyxy2']['belongs']['y'] ) ) {
				continue;
			}
			$potential_items[ $i ] = $row['product_price'];
			$potential_items_details[ $i ] = $row;
			$potential_items_details[ $i ]['breakdown_key'] = $key;
			$i++;
		}

		if ( empty( $potential_items ) ) {
			return;
		}

		$asset1list = $coupon_row->asset[3]->rows;
		$asset2list = $coupon_row->asset[4]->rows;
		if ( empty( $asset1list ) || empty( $asset2list ) ) {
			return;
		}
		// change format from asset2list[asset_type-asset_id] = cmcoupon_asset object
		// to asset2list->asset_type->rows[asset_id] = cmcoupon_asset object.
		//
		// reorder all x items that can be in y at the bottom.
		$tmp_potential_items = $potential_items;
		$tmp_potential_items_details = $potential_items_details;
		$potential_items = array();
		$potential_items_details = array();
		$potential_x_items = array();
		$potential_x_items_details = array();
		foreach ( $tmp_potential_items_details as $ppindex => $_potential ) {
			if ( ! empty( $_potential['buyxy2']['belongs']['x'] ) ) {
				$potential_x_items[] = $tmp_potential_items[ $ppindex ];
				$potential_x_items_details[] = $tmp_potential_items_details[ $ppindex ];
			} else {
				$potential_items[] = $tmp_potential_items[ $ppindex ];
				$potential_items_details[] = $tmp_potential_items_details[ $ppindex ];
			}
		}
		if ( ! empty( $potential_x_items ) ) {
			foreach ( $potential_x_items as $ppindex => $_potential_list ) {
				$potential_items[] = $potential_x_items[ $ppindex ];
				$potential_items_details[] = $potential_x_items_details[ $ppindex ];
			}
		}

		if ( 'first' === $coupon_row->params->process_type ) {
		} elseif ( 'lowest' === $coupon_row->params->process_type ) {
			asort( $potential_items, SORT_NUMERIC );
		} elseif ( 'highest' === $coupon_row->params->process_type ) {
			arsort( $potential_items, SORT_NUMERIC );
		}

		$current_cart_items_breakdown = $coupon_row->cart_items_breakdown;
		foreach ( $current_cart_items_breakdown as $k => $cartelement ) {
			if ( empty( $cartelement['buyxy2'] ) ) {
				unset( $current_cart_items_breakdown[ $k ] );
				continue;
			}
		}
		$current_potential_items = $potential_items;
		$valid_items = array();

		if ( 1 === 1 ) {
			// check x.
			$x_matches = array();
			$x_cart_matches = array();
			foreach ( $asset1list as $assetparent ) {
				foreach ( $assetparent->rows as $assetelement ) {
					for ( $i = 0; $i < $assetelement->qty; $i++ ) {
						$x_matches[] = $assetelement->asset_type . '-' . $assetelement->asset_id;
					}
				}
			}
			foreach ( $current_cart_items_breakdown as $k => $cartelement ) {
				if ( empty( $cartelement['buyxy2']['belongs']['x'] ) ) {
					continue;
				}
				foreach ( $cartelement['buyxy2']['specific'] as $item ) {
					$x_cart_matches[ $item ][] = array(
						'count' => count( $cartelement['buyxy2']['specific'] ),
						'key' => $k,
					);
				}
			}
			// order the key from least count to most.
			foreach ( $x_cart_matches as $k => $row ) {
				usort( $x_cart_matches[ $k ], function( $a, $b ) {
					return $a['count'] > $b['count'];
				} );
			}

			foreach ( $x_matches as $k => $x_match ) {
				if ( empty( $x_cart_matches[ $x_match ] ) ) {
					break;
				}
				foreach ( $x_cart_matches[ $x_match ] as $k2 => $match ) {
					if ( ! isset( $current_cart_items_breakdown[ $match['key'] ] ) ) {
						continue;
					}
					unset( $current_cart_items_breakdown[ $match['key'] ] );
					unset( $x_cart_matches[ $x_match ][ $k2 ] );
					unset( $x_matches[ $k ] );
					break;
				}
			}
			if ( ! empty( $x_matches ) ) {
				return $this->return_false( 'errBuyXYList1IncludeEmpty' );
			}
		}

		if ( 1 === 1 ) {
			// now check y.
			$fail_safe = 100;
			while ( $fail_safe > 0 ) {

				$items = array();
				foreach ( $asset2list as $assetparent ) {
					foreach ( $assetparent->rows as $assetelement ) {
						for ( $i = 0; $i < $assetelement->qty; $i++ ) {
							$is_valid_y = false;
							foreach ( $current_potential_items as $k => $current_potential_item ) {
								if ( empty( $current_cart_items_breakdown[ $potential_items_details[ $k ]['breakdown_key'] ] ) ) {
									continue; // does not exist in cart any more.
								}
								if ( empty( $potential_items_details[ $k ]['buyxy2']['specific'][ $assetelement->asset_type . '-' . $assetelement->asset_id ] ) ) {
									continue;
								}
								$is_valid_y = true;
								$items[] = $potential_items_details[ $k ];
								unset( $current_cart_items_breakdown[ $potential_items_details[ $k ]['breakdown_key'] ] );
								unset( $current_potential_items[ $k ] );
								break;
							}
							if ( ! $is_valid_y ) {
								break 4;  // no more valid x's get out.
							}
						}
					}
				}

				$valid_items[] = $items;
				$fail_safe--;
			}
			if ( empty( $valid_items ) ) {
				return $this->return_false( 'errBuyXYList2IncludeEmpty' );
			}
		}

		if ( ! empty( $coupon_row->params->max_discount_qty ) ) {
			$valid_items = array_slice( $valid_items, 0, $coupon_row->params->max_discount_qty );
		}

		$tmp = $valid_items;
		$valid_items = array();
		foreach ( $tmp as $tmp2 ) {
			$valid_items = array_merge( $valid_items, $tmp2 );
		}

		$total = 0;
		$total_notax = 0;
		$qty = count( $valid_items );
		foreach ( $valid_items as $product_key => $item ) {
			$total += $item['product_price'];
			$total_notax += $item['product_price_notax'];
			$usedproductids[ $item['product_id'] ] = $item['product_id'];
		}

		if ( ! empty( $total ) ) {
			$_discount_product = $coupon_row->coupon_value;
			$_discount_product_notax = $coupon_row->coupon_value;
			if ( 'percent' === $coupon_row->coupon_value_type ) {
				$_discount_product = round( $total * $_discount_product / 100, 4 );
				$_discount_product_notax = round( $total_notax * $_discount_product_notax / 100, 4 );
			} else {
				if ( 'amount_per' === $coupon_row->coupon_value_type ) {
					// set amount discount to the number of valid discounts.
					$_discount_product = $coupon_row->coupon_value * count( $valid_items );
					$_discount_product_notax = $coupon_row->coupon_value * count( $valid_items );
				}

				if ( $coupon_row->is_discount_before_tax ) {
					$_discount_product *= 1 + ( ( $total - $total_notax ) / $total_notax );
				} else {
					$_discount_product_notax /= 1 + ( ( $total - $total_notax ) / $total_notax );
				}
			}

			$this->get_max_discount_amount( $coupon_row, $_discount_product_notax, $_discount_product );

			if ( $total < $_discount_product ) {
				$_discount_product = (float) $total;
			}
			if ( $total_notax < $_discount_product_notax ) {
				$_discount_product_notax = (float) $total_notax;
			}
			$this->realtotal_verify( $_discount_product, $_discount_product_notax );

			if ( $coupon_row->is_discount_before_tax ) {
				$_discount_product_tax = $_discount_product - $_discount_product_notax;
			}

			// track product discount.
			$this->cartitem_update( array(
				'track_product_price' => $track_product_price,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'coupon_row' => $coupon_row,
				'coupon_percent' => $coupon_row->coupon_value,
				'discount_value' => $_discount_product,
				'discount_value_notax' => $_discount_product_notax,
				'qty' => $qty,
				'valid_items' => $valid_items,
				'usedproductids' => $usedproductids,
			) );
		} elseif ( $qty > 0 && 1 === $this->allow_zero_value ) {
			return $this->get_processed_discount_array( $coupon_row, $usedproductids );
		}

		if ( ! empty( $_discount_product ) || ! empty( $_discount_shipping ) ) {
			return array(
				'redeemed' => true,
				'coupon_id' => $coupon_row->id,
				'coupon_code' => $coupon_row->coupon_code,
				'product_discount' => $_discount_product,
				'product_discount_notax' => $_discount_product_notax,
				'product_discount_tax' => $_discount_product_tax,
				'shipping_discount' => $_discount_shipping,
				'shipping_discount_notax' => $_discount_shipping_notax,
				'shipping_discount_tax' => $_discount_shipping_tax,
				'extra_discount' => $_discount_extra,
				'extra_discount_notax' => $_discount_extra_notax,
				'extra_discount_tax' => $_discount_extra_tax,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'usedproducts' => ! empty( $usedproductids ) ? implode( ',', $usedproductids ) : '',
			);
		}
	}

	/**
	 * Process discount: shipping
	 *
	 * @param object  $coupon_row coupon data.
	 * @param boolean $track_product_price track price.
	 **/
	private function checkdiscount_shipping( $coupon_row, $track_product_price = false ) {

		$_discount_product = 0;
		$_discount_product_notax = 0;
		$_discount_product_tax = 0;

		$_discount_shipping = 0;
		$_discount_shipping_notax = 0;
		$_discount_shipping_tax = 0;

		$_discount_extra = 0;
		$_discount_extra_notax = 0;
		$_discount_extra_tax = 0;

		$usedproductids = array();

		if ( empty( $coupon_row->function_type ) ) {
			return;
		}
		if ( 'shipping' !== $coupon_row->function_type ) {
			return;
		}

		// ----------------------------------------------------
		// verify this coupon can be used in this circumstance
		// ----------------------------------------------------
		// check specific to function type.
		$r_err = $this->couponvalidate_asset_producttype( $coupon_row );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}

		if ( ! $this->get_storeshipping_isdefaultbypass( $coupon_row ) ) {

			$shipping_id = $coupon_row->cart_shipping->shipping_id;
			if ( empty( $shipping_id ) ) {
				$ret = $this->get_processed_discount_array( $coupon_row );
				$ret['force_add'] = 1;
				return $ret;
			}

			// verify the shipping is on the list for this coupon.
			$r_err = $this->couponvalidate_shipping( $coupon_row );
			if ( ! empty( $r_err ) ) {
				return $this->return_false( $r_err );
			}
		}

		$r_err = $this->couponvalidate_min_total_qty( $coupon_row );
		if ( ! empty( $r_err ) ) {
			return $this->return_false( $r_err );
		}

		// for zero value coupons.
		$coupon_row->coupon_value = (double) $coupon_row->coupon_value;
		if ( empty( $coupon_row->coupon_value ) && empty( $coupon_row->coupon_value_def ) ) {
			return $this->get_processed_discount_array( $coupon_row );
		}
		if ( in_array( $coupon_row->coupon_value_type, array( 'amount', 'amount_per' ), true ) ) {
			$coupon_row->coupon_value = $this->get_amount_incurrency( $coupon_row->coupon_value );
		}

		// ----------------------------------------------------
		// Compute Coupon Discount based on coupon parameters
		// ----------------------------------------------------.
		$total = 0;
		$total_notax = 0;
		$qty = 0;
		foreach ( $coupon_row->cart_shipping->shippings as $k => $row ) {
			if ( $row->total <= 0 ) {
				continue;
			}
			if ( ! empty( $coupon_row->asset[0]->rows->shipping->rows ) ) {
				$mode = empty( $coupon_row->asset[0]->rows->shipping->mode ) ? 'include' : $coupon_row->asset[0]->rows->shipping->mode;
				if ( ( 'include' === $mode && ! empty( $coupon_row->asset[0]->rows->shipping->rows[ $row->shipping_id ] ) )
				|| ( 'exclude' === $mode && empty( $coupon_row->asset[0]->rows->shipping->rows[ $row->shipping_id ] ) )
				) {
				} else {
					unset( $coupon_row->cart_shipping->shippings[ $k ] );
					continue;
				}
			}

			$total += (float) $row->total;
			$total_notax += (float) $row->total_notax;
		}

		if ( ! empty( $total ) ) {
			$coupon_value = $coupon_row->coupon_value;
			$_discount_shipping = $coupon_row->coupon_value;
			$_discount_shipping_notax = $coupon_row->coupon_value;
			if ( 'percent' === $coupon_row->coupon_value_type ) {
				$_discount_shipping = round( $total * $_discount_shipping / 100, 4 );
				$_discount_shipping_notax = round( $total_notax * $_discount_shipping_notax / 100, 4 );
			} else {
				if ( $coupon_row->is_discount_before_tax ) {
					$_discount_shipping *= 1 + ( ( $total - $total_notax ) / $total_notax );
				} else {
					$_discount_shipping_notax /= 1 + ( ( $total - $total_notax ) / $total_notax );
				}
			}

			$this->get_max_discount_amount( $coupon_row, $_discount_shipping_notax, $_discount_shipping );

			if ( $total < $_discount_shipping ) {
				$_discount_shipping = (float) $total;
			}
			if ( $total_notax < $_discount_shipping_notax ) {
				$_discount_shipping_notax = (float) $total_notax;
			}

			if ( $coupon_row->is_discount_before_tax ) {
				$_discount_shipping_tax = $_discount_shipping - $_discount_shipping_notax;
			}

			// track shipping discount.
			$this->cartitem_update( array(
				'track_product_price' => $track_product_price,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'coupon_row' => $coupon_row,
				'coupon_percent' => null,
				'discount_value' => null,
				'discount_value_notax' => null,
				'shipping_discount_value' => $_discount_shipping,
				'shipping_discount_value_notax' => $_discount_shipping_notax,
				'qty' => null,
				'valid_ships' => $coupon_row->cart_shipping->shippings,
				'usedproductids' => null,
			) );
		} elseif ( 1 === $this->allow_zero_value ) {
			return $this->get_processed_discount_array( $coupon_row, null );
		}

		if ( ! empty( $_discount_product ) || ! empty( $_discount_shipping ) ) {
			return array(
				'redeemed' => true,
				'coupon_id' => $coupon_row->id,
				'coupon_code' => $coupon_row->coupon_code,
				'product_discount' => $_discount_product,
				'product_discount_notax' => $_discount_product_notax,
				'product_discount_tax' => $_discount_product_tax,
				'shipping_discount' => $_discount_shipping,
				'shipping_discount_notax' => $_discount_shipping_notax,
				'shipping_discount_tax' => $_discount_shipping_tax,
				'extra_discount' => $_discount_extra,
				'extra_discount_notax' => $_discount_extra_notax,
				'extra_discount_tax' => $_discount_extra_tax,
				'is_discount_before_tax' => $coupon_row->is_discount_before_tax,
				'usedproducts' => ! empty( $usedproductids ) ? implode( ',', $usedproductids ) : '',
			);
		}
	}

	/**
	 * Create the cart object for parsing through coupon
	 *
	 * @param boolean $is_refresh force new.
	 **/
	protected function define_cart_items( $is_refresh = false ) {
		if ( empty( $this->cart->items ) ) {
			return false;
		}

		$this->cart->items_breakdown = array();
		$index_breakdown = 0;
		foreach ( $this->cart->items as $k => $r ) {
			$this->cart->items[ $k ]['orig_product_price'] = $this->cart->items[ $k ]['product_price'];
			$this->cart->items[ $k ]['orig_product_price_notax'] = $this->cart->items[ $k ]['product_price_notax'];
			$this->cart->items[ $k ]['orig_product_price_tax'] = $this->cart->items[ $k ]['product_price_tax'];
			$this->cart->items[ $k ]['tax_rate'] = round( $this->cart->items[ $k ]['tax_rate'], 4 );
			$this->cart->items[ $k ]['totaldiscount'] = 0;
			$this->cart->items[ $k ]['totaldiscount_notax'] = 0;
			$this->cart->items[ $k ]['totaldiscount_tax'] = 0;
			$this->cart->items[ $k ]['coupons'] = array();

			$this->cart->items[ $k ]['_marked_total'] = false;
			$this->cart->items[ $k ]['_marked_qty'] = false;

			$r2 = $this->cart->items[ $k ];
			unset( $r2['qty'] );
			$r2['key'] = $k;
			$r['qty'] = (float) $r['qty'];

			for ( $i = 0; $i < $r['qty']; $i++ ) {
				$index_breakdown++;
				$this->cart->items_breakdown[ $index_breakdown ] = $r2;

				if ( ! is_int( $r['qty'] ) && ( $i + 1 ) > $r['qty'] ) {
					$this->cart->items_breakdown[ $index_breakdown ]['product_price'] = ($r['qty'] - floor( $r['qty'] ) ) * $this->cart->items_breakdown[ $index_breakdown ]['product_price'];
					$this->cart->items_breakdown[ $index_breakdown ]['product_price_notax'] = ($r['qty'] - floor( $r['qty'] ) ) * $this->cart->items_breakdown[ $index_breakdown ]['product_price_notax'];
					$this->cart->items_breakdown[ $index_breakdown ]['product_price_tax'] = ($r['qty'] - floor( $r['qty'] ) ) * $this->cart->items_breakdown[ $index_breakdown ]['product_price_tax'];
					break;
				}
			}
		}

		$this->cart->shipping = $this->get_storeshipping();
		$this->cart->shipping->total_tax = $this->cart->shipping->total - $this->cart->shipping->total_notax;
		if ( ! isset( $this->cart->shipping->shippings ) || ! is_array( $this->cart->shipping->shippings ) ) {
			$this->cart->shipping->shippings = array(
				(object) array(
					'shipping_id' => $this->cart->shipping->shipping_id,
					'total_notax' => $this->cart->shipping->total_notax,
					'total_tax' => $this->cart->shipping->total_tax,
					'total' => $this->cart->shipping->total,
					'tax_rate' => empty( $this->cart->shipping->total_notax ) ? 0 : ( $this->cart->shipping->total - $this->cart->shipping->total_notax ) / $this->cart->shipping->total_notax,
					'totaldiscount' => 0,
					'totaldiscount_notax' => 0,
					'totaldiscount_tax' => 0,
					'coupons' => array(),
				),
			);
		} else {
			foreach ( $this->cart->shipping->shippings as $k => $item ) {
				$this->cart->shipping->shippings[ $k ]->total_tax = $item->total - $item->total_notax;
			}
		}
		$this->cart->shipping->orig_total = $this->cart->shipping->total;
		$this->cart->shipping->totaldiscount = 0;
		$this->cart->shipping->totaldiscount_notax = 0;
		$this->cart->shipping->totaldiscount_tax = 0;
		$this->cart->shipping->coupons = array();

		$this->cart->payment = $this->get_storepayment();
		$this->cart->payment->total_tax = $this->cart->payment->total - $this->cart->payment->total_notax;
		$this->cart->payment->orig_total = $this->cart->payment->total;
		$this->cart->payment->totaldiscount = 0;
		$this->cart->payment->totaldiscount_notax = 0;
		$this->cart->payment->totaldiscount_tax = 0;
		$this->cart->payment->coupons = array();

		$this->cart->extra = $this->get_storeextra();
		$this->cart->extra->totaldiscount = 0;
		$this->cart->extra->totaldiscount_notax = 0;
		$this->cart->extra->totaldiscount_tax = 0;
		$this->cart->extra->coupons = array();
	}

	/**
	 * Update cart in coupon
	 *
	 * @param array $params the data.
	 **/
	protected function cartitem_update( $params ) {
		if ( empty( $params['track_product_price'] ) ) {
			return;
		}

		if ( ! empty( $params['discount_value'] ) ) {
			// track product discount.
			$tracking_discount = 0;
			$tracking_discount_notax = 0;

			if ( $params['is_discount_before_tax'] ) {
				$recorded_discounts = $this->cartitem_update_each( $params, true );
				foreach ( $recorded_discounts as $k => $value ) {
					// calculate price after tax.
					$discount = $value * ( 1 + $this->cart->items_breakdown[ $k ]['tax_rate'] );
					$this->cart->items_breakdown[ $k ]['product_price'] -= $discount;
					$this->cart->items_breakdown[ $k ]['totaldiscount'] += $discount;
					if ( ! isset( $this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount'] ) ) {
						$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount'] = 0;
					}
					$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount'] += $discount;

					// calculate tax.
					$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_tax'] =
						$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount'] - $this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_notax'];
					$this->cart->items_breakdown[ $k ]['totaldiscount_tax'] += $this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_tax'];
				}

				$this->cartitem_update_line( $params );
			} else {
				$this->cartitem_update_each( $params, false );
				$this->cartitem_update_each( $params, true );

				$this->cartitem_update_line( $params );
			}
		}

		if ( ! empty( $params['shipping_discount_value'] ) ) {
			// track shipping discount.
			$this->cart->shipping->total -= $params['shipping_discount_value'];
			$this->cart->shipping->totaldiscount += $params['shipping_discount_value'];
			$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount'] = $params['shipping_discount_value'];

			$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'] = 0;
			$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;

			$this->cart->shipping->total_notax -= $params['shipping_discount_value_notax'];
			$this->cart->shipping->totaldiscount_notax += $params['shipping_discount_value_notax'];
			$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'] = $params['shipping_discount_value_notax'];

			// calculate tax.
			if ( $params['is_discount_before_tax'] ) {
				$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] =
					$this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount'] - $this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'];
				$this->cart->shipping->totaldiscount_tax += $this->cart->shipping->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'];
			}

			if ( $params['is_discount_before_tax'] ) {
				$recorded_discounts = $this->shippingitem_update_each( $params, true );
				foreach ( $recorded_discounts as $k => $value ) {
					// calculate price after tax.
					$discount = $value * ( 1 + $this->cart->shipping->shippings[ $k ]->tax_rate );
					$this->cart->shipping->shippings[ $k ]->total -= $discount;
					$this->cart->shipping->shippings[ $k ]->totaldiscount += $discount;
					if ( ! isset( $this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount'] ) ) {
						$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount'] = 0;
					}
					$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount'] += $discount;

					// calculate tax.
					$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] =
						$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount'] - $this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'];
					$this->cart->shipping->shippings[ $k ]->totaldiscount_tax += $this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'];
				}
			} else {
				$this->shippingitem_update_each( $params, false );
				$this->shippingitem_update_each( $params, true );
			}
		}

		if ( ! empty( $params['extra_discount_value'] ) ) {
			// track extrafee discount.
			$this->cart->extra->total -= $params['extra_discount_value'];
			$this->cart->extra->totaldiscount += $params['extra_discount_value'];
			$this->cart->extra->coupons[ $params['coupon_row']->id ]['totaldiscount'] = $params['extra_discount_value'];

			$this->cart->extra->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'] = 0;
			$this->cart->extra->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;

			$this->cart->extra->total_notax -= $params['extra_discount_value_notax'];
			$this->cart->extra->totaldiscount_notax += $params['extra_discount_value_notax'];
			$this->cart->extra->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'] = $params['extra_discount_value_notax'];

			// calculate tax.
			if ( $params['is_discount_before_tax'] ) {
				$this->cart->extra->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] =
					$this->cart->extra->coupons[ $params['coupon_row']->id ]['totaldiscount'] - $this->cart->extra->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'];
				$this->cart->extra->totaldiscount_tax += $this->cart->extra->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'];
			}
		}
	}

	/**
	 * Update cart in coupon each
	 *
	 * @param array   $params the data.
	 * @param boolean $is_beforetax is tax.
	 **/
	private function cartitem_update_each( $params, $is_beforetax ) {
		if ( empty( $params['discount_value'] ) ) {
			return;
		}
		$tracking_discount = 0;
		$fail_safe = 0;
		$tmp_discounts = array();
		$postfix = $is_beforetax ? '_notax' : '';

		$found_items = array();
		$product_total = 0;
		if ( ! in_array( $params['coupon_row']->function_type, array( 'buyxy', 'buyxy2' ), true ) ) {
			foreach ( $params['valid_items'] as $breakdown_id => $valid_item ) {
				if ( ! isset( $this->cart->items_breakdown[ $breakdown_id ] ) ) {
					continue;
				}
				if ( round( $this->cart->items_breakdown[ $breakdown_id ][ 'product_price' . $postfix ], 4 ) <= 0 ) {
					continue;
				}

				$product_total += $this->cart->items_breakdown[ $breakdown_id ][ 'product_price' . $postfix ];
				$found_items[ $breakdown_id ] = $this->cart->items_breakdown[ $breakdown_id ];
			}
		} else {
			$tmp_items_breakdown = $this->cart->items_breakdown;
			foreach ( $params['valid_items'] as $valid_item ) {
				foreach ( $tmp_items_breakdown as $k => $row ) {
					if ( $valid_item['key'] !== $row['key'] ) {
						continue;
					}
					if ( round( $row[ 'product_price' . $postfix ], 4 ) <= 0 ) {
						continue;
					}

					$product_total += $row[ 'product_price' . $postfix ];
					$found_items[ $k ] = $row;

					unset( $tmp_items_breakdown[ $k ] );
					break;
				}
			}
		}

		if ( empty( $product_total ) ) {
			return;
		}

		foreach ( $found_items as $k => $row ) {

			$each_discount = ( $params[ 'discount_value' . $postfix ] ) * ( $row[ 'product_price' . $postfix ] / $product_total );
			$discount = min( $each_discount, $row[ 'product_price' . $postfix ] );
			if ( ! isset( $tmp_discounts[ $k ] ) ) {
				$tmp_discounts[ $k ] = 0;
			}
			$tmp_discounts[ $k ] += $discount;

			$this->cart->items_breakdown[ $k ][ 'product_price' . $postfix ] -= $discount;
			$this->cart->items_breakdown[ $k ][ 'totaldiscount' . $postfix ] += $discount;
			if ( ! isset( $this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] ) ) {
				$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] = 0;
			}
			$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] += $discount;
			$tracking_discount += $discount;

			if ( $params['is_discount_before_tax'] && $is_beforetax ) {
				$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;
			} elseif ( ! $params['is_discount_before_tax'] && ! $is_beforetax ) {
				$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_notax'] = 0;
				$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;
			}
		}

		// penny problem.
		if ( $tracking_discount !== $params[ 'discount_value' . $postfix ] ) {
			foreach ( $found_items as $k => $row ) {
				$discount = min( ( $params[ 'discount_value' . $postfix ] - $tracking_discount ), $row[ 'product_price' . $postfix ] );
				if ( ! isset( $tmp_discounts[ $k ] ) ) {
					$tmp_discounts[ $k ] = 0;
				}
				$tmp_discounts[ $k ] += $discount;
				$this->cart->items_breakdown[ $k ][ 'product_price' . $postfix ] -= $discount;
				$this->cart->items_breakdown[ $k ][ 'totaldiscount' . $postfix ] += $discount;
				$this->cart->items_breakdown[ $k ]['coupons'][ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] += $discount;
				$tracking_discount += round( $discount, 4 );
			}
		}

		return $tmp_discounts;
	}

	/**
	 * Update cart in coupon line
	 *
	 * @param array $params the data.
	 **/
	private function cartitem_update_line( $params ) {

		$new_items = array();
		foreach ( $this->cart->items_breakdown as $k => $row ) {

			$this->cart->items_breakdown[ $k ]['product_price'] = round( $this->cart->items_breakdown[ $k ]['product_price'], 4 );
			$this->cart->items_breakdown[ $k ]['product_price_notax'] = round( $this->cart->items_breakdown[ $k ]['product_price_notax'], 4 );
			if ( 0 === (float) $this->cart->items_breakdown[ $k ]['product_price'] || 0 === (float) $this->cart->items_breakdown[ $k ]['product_price_notax'] ) {
				$this->cart->items_breakdown[ $k ]['product_price'] = 0;
				$this->cart->items_breakdown[ $k ]['product_price_notax'] = 0;
			}
			$this->cart->items_breakdown[ $k ]['totaldiscount'] = round( $this->cart->items_breakdown[ $k ]['totaldiscount'], 4 );
			$this->cart->items_breakdown[ $k ]['totaldiscount_notax'] = round( $this->cart->items_breakdown[ $k ]['totaldiscount_notax'], 4 );

			if ( ! isset( $new_items[ $row['key'] ] ) ) {
				$new_items[ $row['key'] ] = array(
					'product_price' => $this->cart->items[ $row['key'] ]['orig_product_price'] * $this->cart->items[ $row['key'] ]['qty'],
					'product_price_notax' => $this->cart->items[ $row['key'] ]['orig_product_price_notax'] * $this->cart->items[ $row['key'] ]['qty'],
					'totaldiscount' => 0,
					'totaldiscount_notax' => 0,
					'totaldiscount_tax' => 0,
					'coupons' => array(),
				);
			}

			$new_items[ $row['key'] ]['product_price'] -= $row['totaldiscount'];
			$new_items[ $row['key'] ]['totaldiscount'] += $row['totaldiscount'];
			$new_items[ $row['key'] ]['product_price_notax'] -= $row['totaldiscount_notax'];
			$new_items[ $row['key'] ]['totaldiscount_notax'] += $row['totaldiscount_notax'];
			$new_items[ $row['key'] ]['totaldiscount_tax'] += $row['totaldiscount_tax'];

			foreach ( $row['coupons'] as $coupon_id => $c_row ) {
				$this->cart->items_breakdown[ $k ]['coupons'][ $coupon_id ]['totaldiscount'] = round( $this->cart->items_breakdown[ $k ]['coupons'][ $coupon_id ]['totaldiscount'], 4 );
				$this->cart->items_breakdown[ $k ]['coupons'][ $coupon_id ]['totaldiscount_notax'] = round( $this->cart->items_breakdown[ $k ]['coupons'][ $coupon_id ]['totaldiscount_notax'], 4 );
				if ( ! isset( $new_items[ $row['key'] ]['coupons'][ $coupon_id ] ) ) {
					$new_items[ $row['key'] ]['coupons'][ $coupon_id ] = array(
						'totaldiscount' => 0,
						'totaldiscount_notax' => 0,
						'totaldiscount_tax' => 0,
					);
				}
				$new_items[ $row['key'] ]['coupons'][ $coupon_id ]['totaldiscount'] += $c_row['totaldiscount'];
				$new_items[ $row['key'] ]['coupons'][ $coupon_id ]['totaldiscount_notax'] += $c_row['totaldiscount_notax'];
				$new_items[ $row['key'] ]['coupons'][ $coupon_id ]['totaldiscount_tax'] += $c_row['totaldiscount_tax'];
			}
		}

		foreach ( $new_items as $k => $row ) {
			$this->cart->items[ $k ]['totaldiscount'] = $row['totaldiscount'];
			$this->cart->items[ $k ]['totaldiscount_notax'] = $row['totaldiscount_notax'];
			$this->cart->items[ $k ]['totaldiscount_tax'] = $row['totaldiscount_tax'];
			$this->cart->items[ $k ]['coupons'] = $row['coupons'];
		}
	}

	/**
	 * Update cart in coupon shipping each row
	 *
	 * @param array   $params the data.
	 * @param boolean $is_beforetax before tax.
	 **/
	private function shippingitem_update_each( $params, $is_beforetax ) {
		if ( empty( $params['shipping_discount_value'] ) ) {
			return;
		}

		$tracking_discount = 0;
		$fail_safe = 0;
		$tmp_discounts = array();
		$postfix = $is_beforetax ? '_notax' : '';

		$found_items = array();
		$shipping_total = 0;
		if ( empty( $params['valid_ships'] ) ) {
			$params['valid_ships'] = array();
		}
		foreach ( $params['valid_ships'] as $k => $row ) {
			if ( round( $row->{'total' . $postfix}, 4 ) <= 0 ) {
				continue;
			}
			$shipping_total += $row->{'total' . $postfix};
			$found_items[ $k ] = $row;
		}

		if ( empty( $shipping_total ) ) {
			return;
		}

		foreach ( $found_items as $k => $row ) {

			$each_discount = ( $params[ 'shipping_discount_value' . $postfix ] ) * ( $row->{'total' . $postfix} / $shipping_total );
			$discount = min( $each_discount, $row->{'total' . $postfix} );
			if ( ! isset( $tmp_discounts[ $k ] ) ) {
				$tmp_discounts[ $k ] = 0;
			}
			$tmp_discounts[ $k ] += $discount;

			$this->cart->shipping->shippings[ $k ]->{'total' . $postfix} -= $discount;
			$this->cart->shipping->shippings[ $k ]->{'totaldiscount' . $postfix} += $discount;
			if ( ! isset( $this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] ) ) {
				$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] = 0;
			}
			$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] += $discount;
			$tracking_discount += $discount;

			if ( $params['is_discount_before_tax'] && $is_beforetax ) {
				$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;
			} elseif ( ! $params['is_discount_before_tax'] && ! $is_beforetax ) {
				$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_notax'] = 0;
				$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ]['totaldiscount_tax'] = 0;
			}
		}

		// penny problem.
		if ( $tracking_discount !== $params[ 'shipping_discount_value' . $postfix ] ) {
			foreach ( $found_items as $k => $row ) {
				$discount = min( ( $params[ 'shipping_discount_value' . $postfix ] - $tracking_discount ), $row->{'total' . $postfix} );
				if ( ! isset( $tmp_discounts[ $k ] ) ) {
					$tmp_discounts[ $k ] = 0;
				}
				$tmp_discounts[ $k ] += $discount;
				$this->cart->shipping->shippings[ $k ]->{'total' . $postfix} -= $discount;
				$this->cart->shipping->shippings[ $k ]->{'totaldiscount' . $postfix} += $discount;
				$this->cart->shipping->shippings[ $k ]->coupons[ $params['coupon_row']->id ][ 'totaldiscount' . $postfix ] += $discount;
				$tracking_discount += round( $discount, 4 );
			}
		}
		return $tmp_discounts;
	}

	/**
	 * Add coupon(s) accepted to session
	 *
	 * @param array $master_output data from accepted coupon.
	 **/
	protected function save_discount_to_session( $master_output ) {

		$product_discount = 0;
		$product_discount_notax = 0;
		$product_discount_tax = 0;
		$shipping_discount = 0;
		$shipping_discount_notax = 0;
		$shipping_discount_tax = 0;
		$extra_discount = 0;
		$extra_discount_notax = 0;
		$extra_discount_tax = 0;
		$usedproducts = '';
		$coupon_codes = array();
		$coupon_codes_noauto = array();
		$usedcoupons = array();
		$auto_codes = isset( $this->get_coupon_auto()->coupons ) ? $this->get_coupon_auto()->coupons : array();
		$use_customer_balance = false;

		foreach ( $master_output as $coupon_id => $r ) {

			if ( empty( $r[1]['force_add'] ) && 1 !== $this->allow_zero_value && empty( $r[1]['product_discount'] ) && empty( $r[1]['shipping_discount'] ) ) {
				continue;
			}
			$coupon_codes[] = $r[1]['coupon_code'];

			$isauto = false;
			if ( ! empty( $auto_codes ) ) {
				foreach ( $auto_codes as $auto_code ) {
					if ( $auto_code->id === $r[1]['coupon_id'] ) {
						$isauto = true;
						break;
					}
				}
			}

			$coupon_entered_id = ! empty( $r[1]['coupon_entered_id'] ) ? $r[1]['coupon_entered_id'] : $r[1]['coupon_id'];

			$display_text = '';
			if ( ! empty( $r[0]->tags['customer_display_text'] ) ) {
				$display_text = $r[0]->tags['customer_display_text'];
			} elseif ( ! empty( $r[0]->note ) ) {
				$match = array();
				preg_match( '/{customer_display_text:(.*)?}/i', $r[0]->note, $match );
				if ( ! empty( $match[1] ) ) {
					$display_text = $match[1];
				}
			}

			if ( empty( $display_text ) && $isauto ) {
				$display_text = $this->get_frontend_lang( 'auto' );
			} else {
				if ( empty( $display_text ) ) {
					$display_text = $r[1]['coupon_code'];
				}
				if ( 'balance' !== $r[0]->state ) {
					$coupon_codes_noauto[] = $display_text;
				}
			}

			if ( 'balance' === $r[0]->state ) {
				$use_customer_balance = true;
				$coupon_entered_id = -3;
				$display_text = $this->get_frontend_lang( 'store_credit' );
			}

			$entered_coupon_ids[ $coupon_entered_id ] = 1;
			$product_discount += $r[1]['product_discount'];
			$product_discount_notax += $r[1]['product_discount_notax'];
			$product_discount_tax += $r[1]['product_discount_tax'];
			$shipping_discount += $r[1]['shipping_discount'];
			$shipping_discount_notax += $r[1]['shipping_discount_notax'];
			$shipping_discount_tax += $r[1]['shipping_discount_tax'];
			$extra_discount += $r[1]['extra_discount'];
			$extra_discount_notax += $r[1]['extra_discount_notax'];
			$extra_discount_tax += $r[1]['extra_discount_tax'];
			if ( ! empty( $r[1]['usedproducts'] ) ) {
				$usedproducts .= $r[1]['usedproducts'] . ',';
			}
			if ( ! empty( $r[2] ) ) {
				foreach ( $r[2] as $k => $row ) {
					$r[2][ $k ]['display_text'] = $display_text;
				}
				$usedcoupons = $usedcoupons + $r[2];
			} else {
				$usedcoupons[ $r[1]['coupon_id'] ] = array(
					'coupon_entered_id' => $coupon_entered_id,
					'coupon_code' => $r[1]['coupon_code'],
					'orig_coupon_code' => $r[1]['coupon_code'],
					'product_discount' => $r[1]['product_discount'],
					'product_discount_notax' => $r[1]['product_discount_notax'],
					'product_discount_tax' => $r[1]['product_discount_tax'],
					'shipping_discount' => $r[1]['shipping_discount'],
					'shipping_discount_notax' => $r[1]['shipping_discount_notax'],
					'shipping_discount_tax' => $r[1]['shipping_discount_tax'],
					'extra_discount' => $r[1]['extra_discount'],
					'extra_discount_notax' => $r[1]['extra_discount_notax'],
					'extra_discount_tax' => $r[1]['extra_discount_tax'],

					'is_discount_before_tax' => $r[1]['is_discount_before_tax'],
					'usedproducts' => $r[1]['usedproducts'],
					'display_text' => $display_text,
					'isauto' => $isauto,
					'isgift' => 'giftcert' === $r[0]->function_type ? true : false,
					'isbalance' => 'balance' === $r[0]->state ? true : false,
					'ischild' => false,
				);
			}
		}
		if ( empty( $usedcoupons ) ) {
			return null;
		}

		if ( ! empty( $auto_codes ) && count( $coupon_codes_noauto ) !== count( $coupon_codes ) ) {
			array_unshift( $coupon_codes_noauto, $this->get_frontend_lang( 'auto' ) );
		}
		if ( $use_customer_balance ) {
			$coupon_codes_noauto[] = $this->get_frontend_lang( 'store_credit' );
		}
		$user = AC()->helper->get_user();

		$session_array = (object) array(
			'redeemed' => true,
			'user_id' => $user->id,
			'uniquecartstring' => $this->getuniquecartstring( implode( ';', $coupon_codes ), true ),
			'coupon_id' => 1 === count( $coupon_codes ) ? key( $master_output ) : '--multiple--',
			'coupon_code' => implode( ', ', $coupon_codes_noauto ),
			'coupon_code_internal' => implode( ';', $coupon_codes ),
			'product_discount' => $product_discount,
			'product_discount_notax' => $product_discount_notax,
			'product_discount_tax' => $product_discount_tax,
			'shipping_discount' => $shipping_discount,
			'shipping_discount_notax' => $shipping_discount_notax,
			'shipping_discount_tax' => $shipping_discount_tax,
			'extra_discount' => $extra_discount,
			'extra_discount_notax' => $extra_discount_notax,
			'extra_discount_tax' => $extra_discount_tax,
			'use_customer_balance' => $use_customer_balance,
			'productids' => $usedproducts,
			'entered_coupon_ids' => $entered_coupon_ids,
			'processed_coupons' => $usedcoupons,
			'cart_items' => $this->cart->items,
			'cart_items_breakdown' => count( $this->cart->items_breakdown ) < 500 ? $this->cart->items_breakdown : array(), // can cause memory errors if too large.
		);
		$this->session_set( 'coupon', $session_array );

		return $this->get_coupon_session();
	}

	/**
	 * Add couopn to history after creating order
	 *
	 * @param int    $order_id the order.
	 * @param object $coupon_session the session.
	 **/
	protected function save_coupon_history( $order_id, $coupon_session = null ) {

		if ( empty( $coupon_session ) ) {
			$coupon_session = $this->session_get( 'coupon' );
		}
		if ( empty( $coupon_session ) ) {
			return null;
		}

		$this->session_set( 'coupon', null );

		$db = AC()->db;

		$order_id = (int) $order_id;
		$user_email = $this->get_orderemail( $order_id );

		if ( empty( $order_id ) ) {
			$order_id = 'NULL';
		}
		$user_email = empty( $user_email ) ? 'NULL' : '"' . $db->escape( $user_email ) . '"';

		$children_coupons = $coupon_session->processed_coupons;

		$coupon_ids = implode( ',', array_keys( $children_coupons ) );
		$sql = 'SELECT id,num_of_uses_total,num_of_uses_customer,function_type,coupon_value FROM #__cmcoupon WHERE estore="' . $this->estore . '" AND state IN ("published", "balance") AND id IN (' . $coupon_ids . ')';
		$rows = $db->get_objectlist( $sql );

		$coupons = array();

		$coupon_details = $db->escape( AC()->helper->json_encode( $coupon_session ) );

		foreach ( $rows as $coupon_row ) {

			// mark coupon used.
			$is_customer_balance = 'NULL';
			$coupon_entered_id = (int) $children_coupons[ $coupon_row->id ]->coupon_entered_id;
			if ( $children_coupons[ $coupon_row->id ]->isbalance ) {
				$is_customer_balance = 1;
				$coupon_entered_id = $coupon_row->id;
			}

			if ( $coupon_entered_id !== $coupon_row->id ) {
				$coupons[] = $coupon_entered_id;
			}
			$usedproducts = ! empty( $children_coupons[ $coupon_row->id ]->usedproducts )
							? $children_coupons[ $coupon_row->id ]->usedproducts
							: 'NULL';

			$postfix = $children_coupons[ $coupon_row->id ]->is_discount_before_tax ? '_notax' : '';
			$total_curr_product = (float) $children_coupons[ $coupon_row->id ]->{'product_discount' . $postfix};
			$total_curr_shipping = (float) $children_coupons[ $coupon_row->id ]->{'shipping_discount' . $postfix};
			$total_product = AC()->storecurrency->convert_to_default( $total_curr_product );
			$total_shipping = AC()->storecurrency->convert_to_default( $total_curr_shipping );

			$sql = 'INSERT INTO #__cmcoupon_history SET
						estore="' . $this->estore . '",
						coupon_entered_id=' . $coupon_entered_id . ',
						coupon_id=' . $coupon_row->id . ',
						is_customer_balance=' . $is_customer_balance . ',
						user_id=' . $coupon_session->user_id . ',
						user_email=' . $user_email . ',
						total_product=' . $total_product . ',
						total_shipping=' . $total_shipping . ',
						currency_code="' . AC()->db->escape( AC()->storecurrency->get_current_currencycode() ) . '",
						total_curr_product=' . $total_curr_product . ',
						total_curr_shipping=' . $total_curr_shipping . ',
						order_id=' . $order_id . ',
						productids="' . $usedproducts . '",
						details="' . $coupon_details . '",
						timestamp="' . gmdate( 'Y-m-d H:i:s' ) . '"
			';
			$db->query( $sql );

			$is_part_of_balance = false;
			if ( 'giftcert' === $coupon_row->function_type ) {
				// gift certificate.
				if ( ! $children_coupons[ $coupon_row->id ]->isbalance ) {
					if ( ! empty( $coupon_session->user_id ) && 1 === (int) $this->params->get( 'enable_frontend_balance', 0 ) && 1 === (int) $this->params->get( 'enable_frontend_balance_isauto', 0 ) ) {
						// add valid gift certificate to customer balance.
						if ( AC()->coupon->is_giftcert_valid_for_balance( $coupon_row->id, false ) ) {
							$is_part_of_balance = true;

							// get balance of gift certificate.
							$balance = AC()->coupon->get_giftcert_balance( $coupon_row->id );

							// add to customer balance.
							$db->query( 'INSERT INTO #__cmcoupon_customer_balance (user_id,coupon_id,initial_balance) 
											VALUES (' . $coupon_session->user_id . ',' . (int) $coupon_row->id . ',' . (float) $balance . ')' );

							// and unpublish the giftcert.
							$db->query( 'UPDATE #__cmcoupon SET state="balance",expiration=NULL WHERE id=' . (int) $coupon_row->id );
						}
					}

					if ( ! $is_part_of_balance ) {
						$balance = AC()->coupon->get_giftcert_balance( $coupon_row->id );
						if ( empty( $balance ) ) {
							// credits maxed out.
							$db->query( 'UPDATE #__cmcoupon SET state="unpublished" WHERE id=' . $coupon_row->id );
						}
					}
				}
			} else {
				$is_unpublished = false;
				if ( ! empty( $coupon_row->num_of_uses_total ) ) {
					// limited amount of uses so can be removed.
					$sql = 'SELECT COUNT(id) FROM #__cmcoupon_history WHERE estore="' . $this->estore . '" AND coupon_id=' . $coupon_row->id . ' GROUP BY coupon_id';
					$num = $db->get_value( $sql );
					if ( ! empty( $num ) && $num >= $coupon_row->num_of_uses_total ) {
						// already used max number of times.
						$is_unpublished = true;
						$db->query( 'UPDATE #__cmcoupon SET state="unpublished" WHERE id=' . $coupon_row->id );
					}
				}
			}

			if ( ! $is_part_of_balance && ! empty( $coupon_session->user_id ) ) {
				$giftcard_id = (int) $db->get_value( 'SELECT id FROM #__cmcoupon_voucher_customer_code WHERE coupon_id=' . $coupon_row->id . ' AND (recipient_user_id IS NULL OR recipient_user_id=0)' );
				if ( ! empty( $giftcard_id ) ) {
					// if purchased voucher transfer to recipient user account.
					$db->query( 'UPDATE #__cmcoupon_voucher_customer_code SET recipient_user_id=' . (int) $coupon_session->user_id . ' WHERE id=' . $giftcard_id );
				}
			}
		}

		foreach ( $coupons as $coupon_id ) {
			$sql = 'SELECT id,num_of_uses_total,function_type FROM #__cmcoupon WHERE estore="' . $this->estore . '" AND state="published" AND id=' . $coupon_id;
			$coupon_row = $db->get_object( $sql );
			if ( ! empty( $coupon_row ) && 'combination' === $coupon_row->function_type ) {
				if ( ! empty( $coupon_row->num_of_uses_total ) ) {
					// limited amount of uses so can be removed.
					$sql = 'SELECT COUNT(DISTINCT order_id) FROM #__cmcoupon_history WHERE estore="' . $this->estore . '" AND coupon_entered_id=' . $coupon_row->id . ' GROUP BY coupon_entered_id';
					$num = $db->get_value( $sql );
					if ( ! empty( $num ) && $num >= $coupon_row->num_of_uses_total ) {
						// already used max number of times.
						$db->query( 'UPDATE #__cmcoupon SET state="unpublished" WHERE estore="' . $this->estore . '" AND id=' . $coupon_row->id );
					}
				}
			}
		}

		$this->initialize_coupon();

		// reset customer balance session.
		$this->session_set( 'customer_balance', null );

		return true;
	}

	/**
	 * Delete coupon from cart
	 *
	 * @param int $coupon_id the coupon.
	 **/
	protected function delete_coupon_from_session( $coupon_id = '' ) {
		$coupon_id = (int) $coupon_id;
		if ( empty( $coupon_id ) ) {
			return $this->initialize_coupon(); // empty all coupon codes from cart.
		}

		$coupon_session = $this->session_get( 'coupon' );
		if ( empty( $coupon_session ) ) {
			return;
		}

		if ( ! isset( $coupon_session->entered_coupon_ids[ $coupon_id ] ) ) {
			return;
		}

		if ( 1 === count( $coupon_session->entered_coupon_ids ) ) {
			$this->initialize_coupon();
			$this->initialize_coupon_auto();
			return;
		}

		// remove coupon.
		$coupon_session->uniquecartstring = mt_rand();
		if ( -3 === (int) $coupon_id ) {
			$coupon_session->use_customer_balance = false;
		}
		unset( $coupon_session->entered_coupon_ids[ $coupon_id ] );
		foreach ( $coupon_session->processed_coupons as $k => $row ) {
			if ( (int) $row->coupon_entered_id === (int) $coupon_id ) {
				unset( $coupon_session->processed_coupons[ $k ] );
			}
		}

		// reprocess remaining coupons.
		$this->session_set( 'coupon', $coupon_session );

		// auto coupons.
		$autosess = $this->get_coupon_auto();
		if ( ! empty( $autosess ) ) {
			foreach ( $autosess->coupons as $k => $coupon ) {
				if ( $coupon->id !== $coupon_id ) {
					continue;
				}
				unset( $autosess->coupons[ $k ] );
			}
		}

		if ( empty( $autosess->coupons ) ) {
			$this->initialize_coupon_auto();
			return;
		}
		$autosess->uniquecartstring = mt_rand();
		$this->session_set( 'coupon_auto', $autosess );
	}

	/**
	 * If order is cancelled restore coupon by deleting from coupon history
	 *
	 * @param int    $order_id the order.
	 * @param string $order_status the status.
	 **/
	protected function cleanup_ordercancel_helper( $order_id, $order_status ) {

		$order_id = (int) $order_id;
		if ( empty( $order_id ) ) {
			return;
		}

		$_cancelled_statuses = $this->params->get( 'ordercancel_order_status', '' );
		if ( empty( $_cancelled_statuses ) ) {
			return;
		}
		if ( ! is_array( $_cancelled_statuses ) ) {
			$_cancelled_statuses = array( $_cancelled_statuses );
		}
		if ( ! in_array( $order_status, $_cancelled_statuses, true ) ) {
			return;
		}

		$db = AC()->db;
		$rows = $db->get_objectlist( 'SELECT h.id,h.coupon_id,c.state FROM #__cmcoupon_history h LEFT JOIN #__cmcoupon c ON c.id=h.coupon_id WHERE h.order_id=' . (int) $order_id );
		foreach ( $rows as $row ) {
			$history_id = (int) $row->id;
			if ( ! empty( $history_id ) ) {
				$db->query( 'DELETE FROM #__cmcoupon_history WHERE id=' . $history_id );
			}

			if ( 'unpublished' === $row->state ) {
				$db->query( 'UPDATE #__cmcoupon SET state="published" WHERE id=' . $row->coupon_id );
			}
		}
	}

	/**
	 * Initialize coupon session
	 **/
	protected function initialize_coupon() {
		$this->session_set( 'coupon', 0 );
	}

	/**
	 * Initialize coupon auto session
	 **/
	protected function initialize_coupon_auto() {
		$this->session_set( 'coupon_auto', 0 );
	}

	/**
	 * Set coupon auto to session
	 *
	 * @param object $coupon_rows the coupon object list.
	 **/
	protected function set_coupon_auto( $coupon_rows ) {
		if ( empty( $coupon_rows ) ) {
			$this->initialize_coupon_auto();
		} else {
			foreach ( $coupon_rows as & $coupon_row ) {
				unset( $coupon_row->cart_items_breakdown );
			}

			$master_list = new stdClass();
			$master_list->uniquecartstring = $this->getuniquecartstringauto();
			$master_list->coupons = $coupon_rows;
			$this->session_set( 'coupon_auto', $master_list );
		}
	}

	/**
	 * Get coupon auto from session
	 **/
	protected function get_coupon_auto() {
		$coupon_row = $this->session_get( 'coupon_auto' );
		if ( ! empty( $coupon_row ) ) {
			if ( ! empty( $coupon_row->coupons ) ) {
				return $coupon_row;
			}
		}
		return '';
	}

	/**
	 * Get coupon from msession
	 **/
	public function get_coupon_session() {
		return $this->session_get( 'coupon' );
	}

	/**
	 * Checks if coupon is in session, based on case sensitive setting
	 *
	 * @param string $coupon_code code.
	 **/
	public function is_couponcode_in_session( $coupon_code ) {
		$coupon_session = $this->get_coupon_session();
		if ( empty( $coupon_session->coupon_code_internal ) ) {
			return false;
		}
		if ( $coupon_code === $this->coupon_code_balance ) {
			return true;
		}

		return $this->is_coupon_in_array( $coupon_code, explode( ';', $coupon_session->coupon_code_internal ) );
	}

	/**
	 * Coupon failed return error
	 *
	 * @param string $key the coupon error key.
	 **/
	protected function return_false( $key, $type = 'key', $force = 'donotforce' ) {
		if ( $this->reprocess ) {
			return;
		}

		if ( empty( $this->coupon_row ) || ( ! empty( $this->coupon_row ) && empty( $this->coupon_row->isauto ) ) ) {

			// display error to screen, if coupon is being set.
			$err = 'custom_error' === $type ? $key : AC()->lang->get_data( $this->params->get( 'idlang_' . $key ) );
			if ( empty( $err ) ) {
				$err = $this->params->get( $key, $this->default_err_msg );
			}
			if ( ! empty( $this->coupon_row ) && 'force' !== $force && 'combination' === $this->coupon_row->function_type ) {
				$err = $this->params->get( $key, $this->default_err_msg );
			}
			if ( ! empty( $err ) ) {
				if ( empty( $this->coupon_row ) ) {
					$this->error_msgs[0] = $err;
				} else {
					$this->error_msgs[ $this->coupon_row->id ] = $this->coupon_row->coupon_code . ': ' . $err;
				}
			}
		}

		return false;
	}

	/**
	 * Is giftcert in use
	 *
	 * @param string $code coupon.
	 **/
	protected function giftcert_inuse( $code ) {
		return false;
	}

	/**
	 * Update the coupon values to the real values
	 *
	 * @param float $_product cart amount with tax.
	 * @param float $_product_notax cart amount without tax.
	 **/
	protected function realtotal_verify( &$_product, &$_product_notax ) {
		return null;
	}

	/**
	 * Get store shipping default bypass
	 *
	 * @param int $coupon_row coupon.
	 **/
	protected function get_storeshipping_isdefaultbypass( $coupon_row ) {
		return false;
	}

	/**
	 * Is cart initialized and ready to be processed through coupon
	 **/
	protected function cart_object_is_initialized() {
		return true;
	}

	/**
	 * Get store payment
	 **/
	protected function get_storepayment() {
		return (object) array(
			'payment_id' => 0,
			'total_notax' => 0,
			'total' => 0,
		);
	}

	/**
	 * Get store products
	 *
	 * @param array $ids asst ids.
	 **/
	protected function get_storeproduct( $ids ) {
		return array();
	}

	/**
	 * Extra params to add to coupon
	 **/
	protected function get_storeextra() {
		return (object) array(
			'extra_id' => 0,
			'total_notax' => 0,
			'total' => 0,
		);
	}

	/**
	 * Coupon maximum discount amount
	 *
	 * @param object $coupon_row coupon data.
	 * @param float  $total_notax cart amount without tax.
	 * @param float  $total cart amount with tax.
	 **/
	private function get_max_discount_amount( $coupon_row, &$total_notax, &$total ) {
		if ( ! isset( $coupon_row->params->max_discount_amt ) ) {
			return;
		}

		$coupon_row->params->max_discount_amt = (float) $coupon_row->params->max_discount_amt;
		if ( empty( $coupon_row->params->max_discount_amt ) ) {
			return;
		}

		if ( $coupon_row->is_discount_before_tax ) {
			if ( $total_notax <= $coupon_row->params->max_discount_amt ) {
				return;
			}

			$total = $coupon_row->params->max_discount_amt * $total / $total_notax;
			$total_notax = $coupon_row->params->max_discount_amt;
		} else {
			if ( $total <= $coupon_row->params->max_discount_amt ) {
				return;
			}

			$total_notax = $coupon_row->params->max_discount_amt * $total_notax / $total;
			$total = $coupon_row->params->max_discount_amt;
		}
	}

	/**
	 * Get data from session
	 *
	 * @param string $name key to get to.
	 * @param mixed  $default maixed value to return if data does not exist.
	 **/
	protected function session_get( $name, $default = null ) {
		$value = AC()->helper->get_session( 'site', $name, $default );
		if ( empty( $value ) ) {
			return $value;
		}

		if ( ! is_array( $value ) && ! is_object( $value ) ) {
			$tmp = $value;
			$value = json_decode( $value, true );
			$value = json_last_error() === JSON_ERROR_NONE ? $this->array_to_object( $value ) : $tmp;
		}

		return $value;
	}

	/**
	 * Set data to session
	 *
	 * @param string $name key to save to.
	 * @param mixed  $value maixed value to save.
	 **/
	protected function session_set( $name, $value ) {
		if ( is_object( $value ) ) {
			$value = AC()->helper->json_encode( $value );
		}
		AC()->helper->set_session( 'site', $name, $value );
	}

	/**
	 * Convert array to object
	 *
	 * @param array $array manipulate.
	 **/
	protected function array_to_object( $array ) {
		if ( ! is_array( $array ) && ! is_object( $array ) ) {
			return $array;
		}
		foreach ( $array as $k => $v ) {
			if ( null === $k ) {
				continue;
			}
			if ( is_array( $v ) ) {
				if ( is_numeric( $k ) ) {
					if ( ! isset( $obj ) ) {
						$obj = array();
					}
					$obj[ $k ] = $this->array_to_object( $v );
				} else {
					if ( ! isset( $obj ) ) {
						$obj = new stdClass();
					}
					$obj->{$k} = $this->array_to_object( $v );
				}
			} else {
				if ( is_numeric( $k ) ) {
					if ( ! isset( $obj ) ) {
						$obj = array();
					}
					$obj[ $k ] = $v;
				} else {
					if ( ! isset( $obj ) ) {
						$obj = new stdClass();
					}
					$obj->{$k} = $v;
				}
			}
		}
		if ( ! isset( $obj ) ) {
			$obj = array();
		}
		return $obj;
	}

	/**
	 * Case insensitive array_unique
	 *
	 * @param array $array manipulate.
	 **/
	private function array_unique_sensitive( $array ) {
		if ( false === $this->is_casesensitive ) {
			return array_intersect_key( $array, array_unique( array_map( 'strtolower', $array ) ) );
		}
		return array_unique( $array );
	}

	/**
	 * Check if coupon is in array
	 *
	 * @param string  $coupon_code the code.
	 * @param array   $array the haystack.
	 **/
	protected function is_coupon_in_array( $coupon_code, $array ) {
		if ( false === $this->is_casesensitive ) {
			return array_search( strtolower( $coupon_code ), array_map( 'strtolower', $array ), true );
		}
		return array_search( $coupon_code, $array, true );
	}

	/**
	 * Check if the codes are equal, using sensitivity configuration
	 *
	 * @param string  $code1 code1.
	 * @param array   $code2 code2.
	 **/
	protected function is_couponcode_equal( $code1, $code2 ) {
		if ( false === $this->is_casesensitive ) {
			return ( strtolower( $code1 ) === strtolower( $code2 ) );
		}
		return ( $code1 === $code2 );
	}

	/**
	 * Case sensitive array_intersect / array_diff
	 *
	 * @param string  $type options intersect | diff.
	 * @param array   $find what to find.
	 * @param array   $haystack what to look in.
	 **/
	private function array_intersect_diff( $type, $find, $haystack ) {
		return
			'intersect' === $type
				? ( $this->is_casesensitive ? array_intersect( $haystack, $find ) : array_uintersect( $haystack, $find, 'strcasecmp' ) )
				: ( $this->is_casesensitive ? array_diff( $find, $haystack ) : array_udiff( $find, $haystack, 'strcasecmp' ) );
	}

	/**
	 * Need to check payment method later in process
	 **/
	protected function is_check_payment_method_later() {
		return false;
	}

	/**
	 * Get language item translated
	 *
	 * @param object $key key.
	 **/
	protected function get_frontend_lang( $key ) {
		switch ( $key ) {
			case 'auto':
				return AC()->lang->__( '( Discount )' );
			case 'store_credit':
				return AC()->lang->__( 'Store credit' );
		}
		return '';
	}

	/**
	 * Is product eligible for discount
	 *
	 * @param int    $product_id the product.
	 * @param object $coupon_row coupon data.
	 **/
	protected function is_product_eligible( $product_id, $coupon_row ) {
		if ( isset( $coupon_row->asset[0]->rows ) ) {

			$asset_types = array( 'product', 'category', 'manufacturer', 'vendor', 'custom' );
			foreach ( $coupon_row->asset[0]->rows as $asset_type => $asset_row ) {
				if ( ! in_array( $asset_type, $asset_types, true ) ) {
					continue;
				}
				if ( empty( $asset_row->rows ) ) {
					continue;
				}

				if ( 'giftcert' !== $coupon_row->function_type && 'specific' !== $coupon_row->discount_type ) {
					continue;
				}

				if ( ( 'product' === $asset_type && ! empty( $coupon_row->cart_items_def[ $product_id ]['is_valid_product'] ) )
				|| ( 'category' === $asset_type && ! empty( $coupon_row->cart_items_def[ $product_id ]['is_valid_category'] ) )
				|| ( 'manufacturer' === $asset_type && ! empty( $coupon_row->cart_items_def[ $product_id ]['is_valid_manufacturer'] ) )
				|| ( 'vendor' === $asset_type && ! empty( $coupon_row->cart_items_def[ $product_id ]['is_valid_vendor'] ) )
				|| ( 'custom' === $asset_type && ! empty( $coupon_row->cart_items_def[ $product_id ]['is_valid_custom'] ) )
				) {
				} else {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Processed coupon empty return
	 *
	 * @param object $coupon_row coupon data.
	 * @param array  $usedproductids products affected by coupons.
	 **/
	private function get_processed_discount_array( $coupon_row = array(), $usedproductids = '' ) {
		return array(
			'redeemed' => true,
			'coupon_id' => ! empty( $coupon_row ) ? $coupon_row->id : 0,
			'coupon_code' => ! empty( $coupon_row ) ? $coupon_row->coupon_code : '',
			'product_discount' => 0,
			'product_discount_notax' => 0,
			'product_discount_tax' => 0,
			'shipping_discount' => 0,
			'shipping_discount_notax' => 0,
			'shipping_discount_tax' => 0,
			'extra_discount' => 0,
			'extra_discount_notax' => 0,
			'extra_discount_tax' => 0,
			'is_discount_before_tax' => ! empty( $coupon_row ) ? $coupon_row->is_discount_before_tax : 0,
			'usedproducts' => is_array( $usedproductids ) ? implode( ',', $usedproductids ) : $usedproductids,
		);
	}

	/**
	 * Get amount in currency
	 *
	 * @param float $amount the value.
	 **/
	private function get_amount_incurrency( $amount ) {
		if ( false === $this->is_currency_convert ) {
			return $amount;
		}
		return AC()->storecurrency->convert_from_default( $amount );
	}

	// -------------------------------------------------------
	// couponvalidate
	// -------------------------------------------------------
	/**
	 * Valitator: user_id
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_user( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->user->rows ) ) {
			return;
		}
		$userlist = $coupon_row->asset[0]->rows->user->rows;

		if ( empty( $coupon_row->customer->user_id ) ) {
			// not a logged in user.
			return 'errUserLogin';
		}

		// verify the user is on the list for this coupon.
		$mode = empty( $coupon_row->asset[0]->rows->user->mode ) ? 'include' : $coupon_row->asset[0]->rows->user->mode;
		if (
			( 'include' === $mode && ! isset( $userlist[ $coupon_row->customer->user_id ] ) )
							||
			( 'exclude' === $mode && isset( $userlist[ $coupon_row->customer->user_id ] ) )
		) {
			// not on user list.
			return 'errUserNotOnList';
		}
	}

	/**
	 * Valitator: shopper group
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_usergroup( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->usergroup->rows ) ) {
			return;
		}
		$usergrouplist = $coupon_row->asset[0]->rows->usergroup->rows;

		$customergroups = $this->get_storeshoppergroupids( $coupon_row->customer->user_id );

		$is_in_list = false;
		foreach ( $customergroups as $group_id ) {
			if ( isset( $usergrouplist[ $group_id ] ) ) {
				$is_in_list = true;
				break;
			}
		}
		$mode = empty( $coupon_row->asset[0]->rows->usergroup->mode ) ? 'include' : $coupon_row->asset[0]->rows->usergroup->mode;
		if (
			( 'include' === $mode && ! $is_in_list )
							||
			( 'exclude' === $mode && $is_in_list )
		) {
			// list restriction.
			return 'errUserGroupNotOnList';
		}
	}

	/**
	 * Valitator: country check
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_country( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->country->rows ) ) {
			return;
		}
		$countrylist = $coupon_row->asset[0]->rows->country->rows;

		if ( empty( $coupon_row->customer->address ) ) {
			$coupon_row->customer->address = $this->get_customeraddress();
		}
		if ( empty( $coupon_row->customer->address->country_id ) ) {
			// not on  list.
			return 'errCountryInclude';
		}

		$mode = empty( $coupon_row->asset[0]->rows->country->mode ) ? 'include' : $coupon_row->asset[0]->rows->country->mode;

		if ( 'include' === $mode && ! isset( $countrylist[ $coupon_row->customer->address->country_id ] ) ) {
			return 'errCountryInclude';
		}
		if ( 'exclude' === $mode && isset( $countrylist[ $coupon_row->customer->address->country_id ] ) ) {
			return 'errCountryExclude';
		}
	}

	/**
	 * Valitator: country's province check
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_countrystate( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->countrystate->rows ) ) {
			return;
		}
		$countrystatelist = $coupon_row->asset[0]->rows->countrystate->rows;

		if ( empty( $coupon_row->customer->address ) ) {
			$coupon_row->customer->address = $this->get_customeraddress();
		}
		if ( empty( $coupon_row->customer->address->state_id ) ) {
			// not on  list.
			return 'errCountrystateInclude';
		}

		$mode = empty( $coupon_row->asset[0]->rows->countrystate->mode ) ? 'include' : $coupon_row->asset[0]->rows->countrystate->mode;

		if ( 'include' === $mode && ! isset( $countrystatelist[ $coupon_row->customer->address->state_id ] ) ) {
			return 'errCountrystateInclude';
		}
		if ( 'exclude' === $mode && isset( $countrystatelist[ $coupon_row->customer->address->state_id ] ) ) {
			return 'errCountrystateExclude';
		}
	}

	/**
	 * Valitator: payment method check
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_paymentmethod( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->paymentmethod->rows ) ) {
			return;
		}
		$paymentmethodlist = $coupon_row->asset[0]->rows->paymentmethod->rows;

		if ( empty( $this->cart->payment->payment_id ) ) {
			// not on  list.
			if ( $this->is_check_payment_method_later() ) {
				return; // payment not selected yet, dont throw error.
			}
			return 'errPaymentMethodInclude';
		}

		$mode = empty( $coupon_row->asset[0]->rows->paymentmethod->mode ) ? 'include' : $coupon_row->asset[0]->rows->paymentmethod->mode;

		if ( 'include' === $mode && ! isset( $paymentmethodlist[ $this->cart->payment->payment_id ] ) ) {
			return 'errPaymentMethodInclude';
		}
		if ( 'exclude' === $mode && isset( $paymentmethodlist[ $this->cart->payment->payment_id ] ) ) {
			return 'errPaymentMethodExclude';
		}
	}

	/**
	 * Valitator: shipping as asset
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_shipping( &$coupon_row ) {

		if ( empty( $coupon_row->asset[0]->rows->shipping->rows ) ) {
			return;
		}
		$shippinglist = $coupon_row->asset[0]->rows->shipping->rows;

		if ( empty( $coupon_row->cart_shipping->shippings ) ) {
			return 'errShippingInclList';
		}

		$mode = empty( $coupon_row->asset[0]->rows->shipping->mode ) ? 'include' : $coupon_row->asset[0]->rows->shipping->mode;

		if ( 'include' === $mode ) {
			$is_in_list = false;
			foreach ( $coupon_row->cart_shipping->shippings as $row ) {
				if ( isset( $shippinglist[ $row->shipping_id ] ) ) {
					$is_in_list = true;
					break;
				}
			}
			if ( ! $is_in_list ) {
				// (include) not on list.
				return $this->return_false( 'errShippingInclList' );
			}
		} elseif ( 'exclude' === $mode ) {
			$is_not_in_list = false;
			foreach ( $coupon_row->cart_shipping->shippings as $row ) {
				if ( ! isset( $shippinglist[ $row->shipping_id ] ) ) {
					$is_not_in_list = true;
					break;
				}
			}
			if ( ! $is_not_in_list ) {
				// (exclude) all on list.
				return $this->return_false( 'errShippingExclList' );
			}
		}
	}

	/**
	 * Valitator: number of uses
	 *
	 * @param object $coupon_row coupon data.
	 **/
	protected function couponvalidate_numuses( &$coupon_row ) {
		// number of use check.
		$db = AC()->db;
		$is_combination = 'combination' !== $coupon_row->function_type ? false : true;

		if ( ! empty( $coupon_row->num_of_uses_total ) ) {
			// check to make sure it has not been used more than the limit.
			if ( ! $is_combination ) {
				$sql = 'SELECT COUNT(id) FROM #__cmcoupon_history WHERE estore="' . $this->estore . '" AND coupon_id=' . $coupon_row->id . ' GROUP BY coupon_id';
			} else {
				$sql = 'SELECT COUNT(DISTINCT order_id) FROM #__cmcoupon_history WHERE estore="' . $this->estore . '" AND coupon_entered_id=' . $coupon_row->id . ' GROUP BY coupon_entered_id';
			}
			$num = $db->get_value( $sql );
			if ( ! empty( $num ) && $num >= $coupon_row->num_of_uses_total ) {
				// total: already used max number of times.
				return 'errTotalMaxUse';
			}
		}

		if ( ! empty( $coupon_row->num_of_uses_customer ) ) {
			// check to make sure user has not used it more than the limit.
			$num = 0;
			$user_id = $coupon_row->customer->user_id;
			if ( ! empty( $user_id ) ) {
				if ( ! $is_combination ) {
					$sql = 'SELECT COUNT(id) FROM #__cmcoupon_history WHERE estore="' . $this->estore . '" AND coupon_id=' . $coupon_row->id . ' AND user_id=' . $user_id . ' AND (user_email IS NULL OR user_email="") GROUP BY coupon_id,user_id';
				} else {
					$sql = 'SELECT COUNT(DISTINCT order_id) FROM #__cmcoupon_history WHERE estore="' . $this->estore . '" AND coupon_entered_id=' . $coupon_row->id . ' AND user_id=' . $user_id . ' AND (user_email IS NULL OR user_email="") GROUP BY coupon_entered_id,user_id';
				}
				$customer_num_uses = (int) $db->get_value( $sql );
			}

			if ( empty( $coupon_row->customer->address ) ) {
				$coupon_row->customer->address = $this->get_customeraddress();
			}
			$email = $coupon_row->customer->address->email;
			$max_num_uses = (int) $coupon_row->num_of_uses_customer;

			if ( ! empty( $email ) ) {
				if ( ! $is_combination ) {
					$sql = 'SELECT COUNT(id) FROM #__cmcoupon_history
							 WHERE estore="' . $this->estore . '" AND coupon_id=' . $coupon_row->id . ' AND user_email="' . AC()->db->escape( $email ) . '"
							 GROUP BY coupon_id';
				} else {
					$sql = 'SELECT COUNT(DISTINCT order_id) FROM #__cmcoupon_history 
							 WHERE estore="' . $this->estore . '" AND coupon_entered_id=' . $coupon_row->id . ' AND user_email="' . AC()->db->escape( $email ) . '"
							 GROUP BY coupon_entered_id';
				}
				$customer_num_uses += (int) AC()->db->get_value( $sql );
			}

			if ( ! empty( $customer_num_uses ) && $customer_num_uses >= $max_num_uses ) {
				// per user: already used max number of times
				return 'errUserMaxUse';
			}
		}

		return null;
	}

	/**
	 * Valitator: is product special checked
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_product_special( &$coupon_row ) {

		if ( empty( $coupon_row->params->exclude_special ) ) {
			return;
		}

		foreach ( $coupon_row->cart_items_breakdown as $k => $tmp ) {
			if ( ! empty( $tmp['is_special'] ) ) {
				unset( $coupon_row->cart_items_breakdown[ $k ] );// remove specials.
			}
		}
		foreach ( $coupon_row->cart_items as $k => $tmp ) {
			if ( ! empty( $tmp['is_special'] ) ) {
				unset( $coupon_row->cart_items[ $k ] );// remove specials.
			}
		}
		if ( empty( $coupon_row->cart_items_breakdown ) ) {
			// all products in cart are on special.
			return 'errDiscountedExclude';
		}
	}

	/**
	 * Valitator: is product discount checked
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_product_discounted( &$coupon_row ) {

		if ( empty( $coupon_row->params->exclude_discounted ) ) {
			return;
		}

		foreach ( $coupon_row->cart_items_breakdown as $k => $tmp ) {
			if ( ! empty( $tmp['is_discounted'] ) ) {
				unset( $coupon_row->cart_items_breakdown[ $k ] );// remove specials.
			}
		}
		foreach ( $coupon_row->cart_items as $k => $tmp ) {
			if ( ! empty( $tmp['is_discounted'] ) ) {
				unset( $coupon_row->cart_items[ $k ] );// remove specials.
			}
		}
		if ( empty( $coupon_row->cart_items_breakdown ) ) {
			// all products in cart are on special.
			return 'errDiscountedExclude';
		}

		$coupon_row->params->exclude_special = 1;
		return $this->couponvalidate_product_special( $coupon_row );
	}

	/**
	 * Valitator: is giftcert checked
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_product_giftcert( &$coupon_row ) {

		if ( empty( $coupon_row->params->exclude_giftcert ) ) {
			return;
		}

		$test_list = AC()->db->get_column( 'SELECT product_id FROM #__cmcoupon_giftcert_product WHERE estore="' . $this->estore . '" AND product_id IN (' . implode( ',', array_keys( $coupon_row->cart_items_def ) ) . ')' );

		if ( empty( $test_list ) ) {
			return;
		}

		foreach ( $coupon_row->cart_items_breakdown as $k => $tmp ) {
			if ( in_array( $tmp['product_id'], $test_list ) ) {
				unset( $coupon_row->cart_items_breakdown[ $k ] );
			}
		}
		if ( empty( $coupon_row->cart_items_breakdown ) ) {
			// all products in cart are on special.
			return 'errGiftcertExclude';
		}
	}

	/**
	 * Valitator: include / exclude asset
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_asset_producttype( &$coupon_row ) {

		$r_err = $this->couponvalidate_include_exclude( $coupon_row, 0, array(
			'is_update_product_total' => true,
			'is_update_product_count' => true,
			'is_update_is_valid_type' => true,
		) );
		if ( ! empty( $r_err ) ) {
			return $r_err;
		}

		if ( 'shipping' === $coupon_row->function_type && 'specific' === $coupon_row->discount_type ) {

			$asset_types = array( 'product', 'category', 'manufacturer', 'vendor', 'custom' );
			foreach ( $coupon_row->asset[0]->rows as $asset_type => $asset_row ) {
				if ( ! in_array( $asset_type, $asset_types, true ) ) {
					continue;
				}
				if ( empty( $asset_row->rows ) ) {
					continue;
				}

				$mode = empty( $asset_row->mode ) ? 'include' : $asset_row->mode;
				$assetlist = $asset_row->rows;

				$r_err = '';
				if ( 'include' === $mode ) {
					$is_not_in_list = false;
					foreach ( $coupon_row->cart_items as $row ) {
						if ( ! isset( $coupon_row->cart_items_def[ $row['product_id'] ] ) ) {
							$coupon_row->cart_items_def[ $row['product_id'] ] = -1;
						}
						if (
							( 'product' === $asset_type && ! isset( $assetlist[ $row['product_id'] ] ) )
										||
							( 'product' !== $asset_type && ( ! isset( $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ) || ! isset( $assetlist[ $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ] ) ) )
						) {
							$is_not_in_list = true;
							break;
						}
					}
					if ( $is_not_in_list ) {
						$r_err = 'err' . ucfirst( strtolower( $asset_type ) ) . 'InclList';
					}
				} elseif ( 'exclude' === $mode ) {
					$is_in_list = false;
					foreach ( $coupon_row->cart_items as $row ) {
						if ( ! isset( $coupon_row->cart_items_def[ $row['product_id'] ] ) ) {
							$coupon_row->cart_items_def[ $row['product_id'] ] = -1;
						}
						if (
							( 'product' === $asset_type && isset( $assetlist[ $row['product_id'] ] ) )
										||
							( 'product' !== $asset_type && isset( $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ) && isset( $assetlist[ $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ] ) )
						) {
							$is_in_list = true;
							break;
						}
					}
					if ( $is_in_list ) {
						$r_err = 'err' . ucfirst( strtolower( $asset_type ) ) . 'ExclList';
					}
				}
				if ( ! empty( $r_err ) ) {
					return $r_err;
				}
			}
		}
	}

	/**
	 * Valitator: include / exclude specific asset
	 *
	 * @param object $coupon_row coupon data.
	 * @param int    $index check 1,2,3,4.
	 * @param object $_params params explained below.
	 *
	 * $coupon_row - the coupon properties and items being set.
	 *      required
	 *      - asset_mode: include/exclude
	 *      - asset_type: product/category/manufacturer/vendor/shipping...etc
	 *      - valid_list: the list of items to test against
	 *      - error_include: the error message for include
	 *      - error_exclude: the error message for exclude
	 *      optional
	 *      - is_update_product_total: update coupon_row with product total if matched, true/false
	 *      - is_update_product_count: update coupon_row with product quantity if matched, true/false
	 *      - is_update_is_valid_type: update coupon_row with type being valid if matched, true/false
	 *
	 * returns array in $coupon_row->temporary
	 *      - products_count: float with product total to update, used by buyxgety
	 *      - products_list: int with product quantity to update, used by buyxgety
	 **/
	private function couponvalidate_include_exclude( &$coupon_row, $index, $_params ) {

		$coupon_row->temporary = array(
			'products_count' => 0,
			'products_list' => array(),
		);

		if ( empty( $coupon_row->asset[ $index ]->rows ) ) {
			return;
		}

		$mode = array();
		$assetlist = array();
		$asset_types = array( 'product', 'category', 'manufacturer', 'vendor', 'custom' );
		foreach ( $coupon_row->asset[ $index ]->rows as $asset_type => $asset_row ) {
			if ( ! in_array( $asset_type, $asset_types, true ) ) {
				continue;
			}
			if ( empty( $asset_row->rows ) ) {
				continue;
			}

			$mode[ $asset_type ] = empty( $asset_row->mode ) ? 'include' : $asset_row->mode;
			$assetlist[ $asset_type ] = $asset_row->rows;

			$tmp = call_user_func( array( $this, 'get_store' . $asset_type ) , implode( ',', array_keys( $coupon_row->cart_items_def ) ) );
			foreach ( $tmp as $tmp2 ) {
				if ( isset( $assetlist[ $asset_type ][ $tmp2->asset_id ] ) ) {
					$coupon_row->cart_items_def[ $tmp2->product_id ][ $asset_type ] = $tmp2->asset_id;
				}
			}
		}

		if ( in_array( $index, array( 0, 1, 2 ), true ) ) {
			$error_string = '';
			$is_at_least_one = false;
			foreach ( $coupon_row->cart_items as $k => $row ) {

				$in_list = true;
				foreach ( $coupon_row->asset[ $index ]->rows as $asset_type => $asset_row ) {
					if ( empty( $assetlist[ $asset_type ] ) ) {
						continue;
					}
					if ( ! isset( $coupon_row->cart_items_def[ $row['product_id'] ] ) ) {
						$coupon_row->cart_items_def[ $row['product_id'] ] = -1;
					}
					if (
						( 'include' === $mode[ $asset_type ] && ( ! isset( $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ) || ! isset( $assetlist[ $asset_type ][ $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ] ) ) )
					|| ( 'exclude' === $mode[ $asset_type ] && isset( $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ) && isset( $assetlist[ $asset_type ][ $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ] ) )
					) {
						$in_list = false;
						if ( empty( $error_string ) ) {
							if ( 'include' === $mode[ $asset_type ] ) {
								$error_string = 'err' . ucfirst( strtolower( $asset_type ) ) . 'InclList';
							} else {
								$error_string = 'err' . ucfirst( strtolower( $asset_type ) ) . 'ExclList';
							}
						}
					} else {
						if ( 0 === $index && isset( $_params['is_update_is_valid_type'] ) && $_params['is_update_is_valid_type'] ) {
							$coupon_row->cart_items_def[ $row['product_id'] ][ 'is_valid_' . $asset_type ] = 1;
						}
					}
				}

				if ( $in_list ) {
					$is_at_least_one = true;
					if ( ! $row['_marked_total'] && isset( $_params['is_update_product_total'] ) && $_params['is_update_product_total'] ) {
						$coupon_row->cart_items[ $k ]['_marked_total'] = true;
						$coupon_row->specific_min_value += $row['qty'] * $row['product_price'];
						$coupon_row->specific_min_value_notax += $row['qty'] * $row['product_price_notax'];
					}
					if ( ! $row['_marked_qty'] && isset( $_params['is_update_product_count'] ) && $_params['is_update_product_count'] ) {
						$coupon_row->cart_items[ $k ]['_marked_qty'] = true;
						$coupon_row->specific_min_qty += $row['qty'];
					}
					$coupon_row->temporary['products_count'] += $row['qty'];
					$coupon_row->temporary['products_list'][ $row['product_id'] ] = $row['product_id'];
				}
			}

			if ( ! $is_at_least_one ) {
				return $error_string;
			}
		} elseif ( in_array( $index, array( 3, 4 ), true ) ) {

			$counted = array();
			foreach ( $assetlist as $asset_type => $assetparent ) {
				foreach ( $assetparent as $assetelement ) {
					$asset_id = $assetelement->asset_id;
					for ( $i = 0; $i < $assetelement->qty; $i++ ) {
						$is_in_list = false;
						foreach ( $coupon_row->cart_items_breakdown as $key => $row ) {

							if ( isset( $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] ) && $coupon_row->cart_items_def[ $row['product_id'] ][ $asset_type ] == $asset_id ) {
								$is_in_list = true;
								if ( ! isset( $row['buyxy2'] ) ) {
									$coupon_row->cart_items_breakdown[ $key ]['buyxy2'] = array(
										'specific' => array(),
										'belongs' => array(),
									);
								}
								$coupon_row->cart_items_breakdown[ $key ]['buyxy2']['belongs'][ $_params['buyxy2_section'] ] = $_params['buyxy2_section'];
								$coupon_row->cart_items_breakdown[ $key ]['buyxy2']['specific'][ $asset_type . '-' . $asset_id ] = $asset_type . '-' . $asset_id;
								if ( empty( $counted[ $key ] ) ) {
									$counted[ $key ] = 1;
									$row['qty'] = 1;
									if ( isset( $_params['is_update_is_valid_type'] ) && $_params['is_update_is_valid_type'] ) {
										$coupon_row->cart_items_def[ $row['product_id'] ][ 'is_valid_' . $asset_type ] = 1;
									}
									if ( ! $row['_marked_total'] && isset( $_params['is_update_product_total'] ) && $_params['is_update_product_total'] ) {
										$coupon_row->cart_items_breakdown[ $key ]['_marked_total'] = true;
										$coupon_row->specific_min_value += $row['qty'] * $row['product_price'];
										$coupon_row->specific_min_value_notax += $row['qty'] * $row['product_price_notax'];
									}
									if ( ! $row['_marked_qty'] && isset( $_params['is_update_product_count'] ) && $_params['is_update_product_count'] ) {
										$coupon_row->cart_items_breakdown[ $key ]['_marked_qty'] = true;
										$coupon_row->specific_min_qty += $row['qty'];
									}
									$coupon_row->temporary['products_count'] += $row['qty'];
									$coupon_row->temporary['products_list'][ $row['product_id'] ] = $row['product_id'];
								}
							}
						}

						if ( ! $is_in_list ) {
							return $_params['error_include'];
						}
					}
				}
			}
		}
	}

	/**
	 * Valitator: daily time limit
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_daily_time_limit( $coupon_row ) {

		if ( empty( $coupon_row->note ) ) {
			return;
		}

		$match = array();
		preg_match( '/{daily_time_limit:\s*(\d+)\s*,\s*(\d*)\s*}/i', $coupon_row->note, $match );
		if ( isset( $match[1] ) && 4 === strlen( $match[1] ) && 4 === strlen( $match[2] ) && $match[2] > $match[1] && $match[2] <= 2359 ) {
			$current_time = AC()->helper->get_date( null, 'Hi', 'utc2utc' );
			if ( (int) $current_time < (int) $match[1] || (int) $current_time > (int) $match[2] ) {
				return 'errNoRecord';
			}
		}
	}

	/**
	 * Valitator: minimum total quantity
	 *
	 * @param object $coupon_row coupon data.
	 **/
	private function couponvalidate_min_total_qty( $coupon_row ) {
		if ( ! empty( $coupon_row->params->min_value_type ) && ! empty( $coupon_row->min_value ) ) {
			if ( 'specific' === $coupon_row->params->min_value_type ) {
				if ( round( $coupon_row->specific_min_value, 4 ) < $coupon_row->min_value ) {
					return 'errMinVal';
				}
			} elseif ( 'specific_notax' === $coupon_row->params->min_value_type ) {
				if ( round( $coupon_row->specific_min_value_notax, 4 ) < $coupon_row->min_value ) {
					return 'errMinVal';
				}
			}
		}

		if ( ! empty( $coupon_row->params->min_qty_type ) && ! empty( $coupon_row->params->min_qty ) ) {
			if ( 'specific' === $coupon_row->params->min_qty_type ) {
				if ( $coupon_row->specific_min_qty < $coupon_row->params->min_qty ) {
					return 'errMinQty';
				}
			}
		}
	}

}
