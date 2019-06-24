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
class CmCoupon_Library_Giftcert {

	/**
	 * Cmcoupon config
	 *
	 * @var object
	 */
	var $params = null;

	/**
	 * Data to clean up if fails
	 *
	 * @var array
	 */
	var $cleanup_data = array();

	/**
	 * New entry or resending
	 *
	 * @var boolean
	 */
	var $is_entry_new = true;

	/**
	 * Constructor
	 **/
	public function __construct() {

		$this->db = AC()->db;
		$this->params = AC()->param;

		$this->log_file = CMCOUPON_TEMP_DIR . '/cmcoupon_giftcert.log';

	}

	/**
	 * Generate automatic email
	 *
	 * @param array $rows giftcert products found.
	 **/
	protected function generate_auto_email( $rows ) {
		if ( empty( $rows ) ) {
			$this->cleanup_error( 'no giftcert found in order' );
			return;
		}

		$allcodes = array();
		$mail_key = array();

		// retreive gift cert profile.
		$profiles = array();
		$profile_default = $this->db->get_arraylist( 'SELECT * FROM #__cmcoupon_profile WHERE is_default=1' );
		if ( empty( $profile_default ) ) {
			$profile_default = $this->db->get_arraylist( 'SELECT * FROM #__cmcoupon_profile LIMIT 1' );
			if ( empty( $profile_default ) ) {
				$this->cleanup_error( 'could not find a gift certificate profile' );
				return false;
			}
		}
		$profile_default = AC()->profile->decrypt_profile( current( $profile_default ) );

		$purchaser_user_id = '';
		$customer_first_name = '';
		$customer_last_name = '';
		foreach ( $rows as $row ) {
			$mail_items = $this->getproductattributes( $row );
			$this_mail_key = $mail_items['email'] . '-' . $mail_items['recipient_name'] . '-' . base64_encode( $mail_items['message'] );
			$mail_key[ $this_mail_key ] = $mail_items;

			$current_profile = $profile_default;
			if ( ! empty( $row->profile_id ) ) {
				if ( ! empty( $profiles[ $row->profile_id ] ) ) {
					$current_profile = $profiles[ $row->profile_id ];
				} else {
					$current_profile = $this->db->get_arraylist( 'SELECT * FROM #__cmcoupon_profile WHERE id=' . (int) $row->profile_id );
					$current_profile = empty( $current_profile )
							? $profile_default
							: AC()->profile->decrypt_profile( current( $current_profile ) );
				}
			}
			$profiles[ $current_profile['id'] ] = $current_profile;

			$purchaser_user_id = $row->user_id;
			$customer_first_name = $row->first_name;
			$customer_last_name = $row->last_name;
			if ( $this->is_entry_new ) {
				for ( $i = 0; $i < $row->product_quantity; $i++ ) {

					if ( $this->check_product_attribute( $mail_items ) === false ) {
						break;
					}

					$code = $this->get_giftcertcode( $row );
					if ( empty( $code->coupon_id ) ) {
						$this->cleanup_error( 'could not create coupon code' );
						return;
					}

					$coupon_row = $this->db->get_object( 'SELECT id,coupon_code,expiration,coupon_value,coupon_value_type FROM #__cmcoupon WHERE id=' . $code->coupon_id );
					if ( empty( $coupon_row ) ) {
						$this->cleanup_error( 'could not find coupon' );
						return;
					}

					if ( ! empty( $row->price_calc_type ) && isset( $row->{$row->price_calc_type} ) ) {
						// set value to value of product.
						$gift_value = (float) $this->get_product_price_in_default_currency( $row->{$row->price_calc_type}, $row );
						if ( ! empty( $gift_value ) ) {
							$this->db->query( 'UPDATE #__cmcoupon SET coupon_value=' . (float) $gift_value . ',coupon_value_type="amount" WHERE id=' . $code->coupon_id );
							$coupon_row->coupon_value_type = 'amount';
							$coupon_row->coupon_value = $gift_value;
						}
					}

					$this->db->query( 'UPDATE #__cmcoupon SET order_id=' . $row->order_id . ' WHERE id=' . $code->coupon_id );

					$price = '';
					if ( ! empty( $coupon_row->coupon_value ) ) {
						$price = 'amount' === $coupon_row->coupon_value_type
										? $this->formatcurrency( $coupon_row->coupon_value )
										: round( $coupon_row->coupon_value ) . '%';
					}
					$allcodes[ $this_mail_key ][] = array(
						'id' => $coupon_row->id,
						'order_item_id' => $row->order_item_id,
						'user_id' => $row->user_id,
						'product_id' => $row->product_id,
						'product_name' => $row->order_item_name,
						'product_qty_name' => $row->product_quantity . ' ' . $row->order_item_name,
						'email' => $row->email,
						'to_email' => $mail_items['email'],
						'code' => $coupon_row->coupon_code,
						'price' => $price,
						'expiration' => $coupon_row->expiration,
						'expirationraw' => ! empty( $coupon_row->expiration ) ? strtotime( $coupon_row->expiration ) : 0,
						'profile' => $current_profile,
						'file' => '',
						'coupon_row' => $coupon_row,
					);
					if ( ! empty( $row->vendor_email ) ) {
						$vendor_codes[ $row->vendor_email ]['name'] = $row->vendor_name;
						$vendor_codes[ $row->vendor_email ]['codes'][] = $coupon_row->coupon_code . ' - ' . $price;
					}
					if ( ! empty( $row->vendor_email ) && 1 === (int) $this->params->get( 'giftcert_vendor_enable', 0 ) ) {
						$code_format = $this->params->get( 'giftcert_vendor_voucher_format', '<div>{voucher} - {price} - {product_name}</div>' );
						if ( strpos( $code_format, '{voucher}' ) === false ) {
							$message .= '<div>{voucher}</div>';
						}
						$vendor_codes[ $row->vendor_email ]['name'] = $row->vendor_name;
						$vendor_codes[ $row->vendor_email ]['codes'][] = str_replace(
							array( '{voucher}', '{price}', '{product_name}', '{purchaser_first_name}', '{purchaser_last_name}', '{today_date}', '{order_id}', '{order_number}' ),
							array( $coupon_row->coupon_code, $price, $row->order_item_name, $row->first_name, $row->last_name, AC()->helper->get_date(), $this->order->order_id, $this->order->order_number ),
							$code_format
						);
					}
				}
			} else {
				if ( empty( $row->coupons ) ) {
					continue;
				}

				foreach ( $row->coupons as $kc => $crow ) {
					$crow['profile'] = $current_profile;
					if ( substr( $crow['price'], -1 ) !== '%' ) {
						$crow['price'] = $this->formatcurrency( $crow['price'] );
					}

					$crow['to_email'] = $mail_items['email'];
					$allcodes[ $this_mail_key ][] = $crow;
				}
			}
		}

		if ( empty( $allcodes ) ) {
			$this->cleanup_error( 'no giftcert processed' );
			return;
		}

		$codes = array();

		$store_name = AC()->store->get_name();
		$vendor_from_email = AC()->store->get_email();
		$orderstatuses = $this->get_orderstatuslist();

		foreach ( $allcodes as $this_mail_key => $mycodes ) {

			// print codes.
			$myprofiles = array();
			$products = array();
			$coupon_rows = array();
			foreach ( $mycodes as $k => $row ) {
				$products[ $row['product_name'] ] = 1;
				$products_qty[ $row['product_qty_name'] ] = 1;
				$myprofiles[ $row['profile']['id'] ] = $row['profile'];

				$row['coupon_row']->order_item_id = $row['order_item_id'];
				$row['coupon_row']->product_id = $row['product_id'];
				$row['coupon_row']->product_name = $row['product_name'];
				$row['coupon_row']->product_qty_name = $row['product_qty_name'];
				$row['coupon_row']->profile = $row['profile'];

				$row['coupon_row']->coupon_price = '';
				if ( ! empty( $row['coupon_row']->coupon_value ) ) {
					$row['coupon_row']->coupon_price = 'amount' === $row['coupon_row']->coupon_value_type
							? $this->formatcurrency( $row['coupon_row']->coupon_value )
							: round( $row['coupon_row']->coupon_value ) . '%';
				}
				$coupon_rows[ $row['coupon_row']->id ] = $row['coupon_row'];
			}
			$allcodes[ $this_mail_key ]['profiles'] = $myprofiles;
			$allcodes[ $this_mail_key ]['products'] = $products;
			$allcodes[ $this_mail_key ]['products_qty'] = $products_qty;
			$allcodes[ $this_mail_key ]['coupon_rows'] = $coupon_rows;

			// email codes.
			if ( ! AC()->helper->is_email( $mycodes[0]['to_email'] ) ) {
				$this->cleanup_error( 'invalid to email' );
				return false;
			}
		}

		$order_coupon_rows = array();
		foreach ( $allcodes as $this_mail_key => $mycodes ) {

			$user = AC()->helper->get_user( $mycodes[0]['user_id'] );
			$to_email = $mycodes[0]['to_email'];

			// profile logic.
			$profile = $profile_default;
			if ( ! isset( $mycodes['profiles'][ $profile_default['id'] ] ) ) {
				$profile = count( $mycodes['profiles'] ) === 1 ? current( $mycodes['profiles'] ) : $profile_default;
			}
			if ( empty( $profile['from_name'] ) ) {
				$profile['from_name'] = $store_name;
			}
			if ( empty( $profile['from_email'] ) ) {
				$profile['from_email'] = $vendor_from_email;
			}

			$the_replacements = array(
				'{siteurl}' => AC()->store->get_home_link(),
				'{store_name}' => $store_name,
				'{purchaser_username}' => $user->username,
				'{purchaser_first_name}' => $customer_first_name,
				'{purchaser_last_name}' => $customer_last_name,
				'{from_name}' => $mail_key[ $this_mail_key ]['from_name'],
				'{recipient_name}' => $mail_key[ $this_mail_key ]['recipient_name'],
				'{recipient_email}' => $to_email,
				'{recipient_message}' => nl2br( $mail_key[ $this_mail_key ]['message'] ),
				'{order_id}' => $this->order->order_id,
				'{order_number}' => $this->order->order_number,
				'{order_status}' => $orderstatuses[ $this->order->order_status ],
				'{order_total}' => $this->formatcurrency( $this->order->order_total ),
				'{order_date}' => AC()->helper->get_date( $this->order->created_on ),
				'{order_link}' => $this->get_orderlink(),
				'{today_date}' => AC()->helper->get_date(),
				'{product_name}' => implode( ', ', array_keys( $mycodes['products'] ) ),
				'{product_qty_name}' => implode( ', ', array_keys( $mycodes['products_qty'] ) ),
			);
			$dynamic_tags = array(
				'find' => array_keys( $the_replacements ),
				'replace' => array_values( $the_replacements ),
			);

			foreach ( $mycodes['coupon_rows'] as $k => $r ) {
				$mycodes['coupon_rows'][ $k ]->tag_replace = array(
					'find' => array( '{product_name}', '{product_qty_name}' ),
					'replace' => array( $r->product_name, $r->product_qty_name ),
				);
			}

			$coupon_rows = AC()->profile->send_email( $user, $mycodes['coupon_rows'], $profile, $dynamic_tags, false, $to_email, $this->is_entry_new );
			if ( false === $coupon_rows ) {
				$this->cleanup_error( 'cannot send profile email' );
				return false;
			}
			$order_coupon_rows = $order_coupon_rows + $coupon_rows;
		}

		if ( $this->is_entry_new ) {
			// update giftcert table so we dont send them more coupons by mistake.
			$codes = array();
			foreach ( $order_coupon_rows as $row ) {
				$tmp = array(
					'i' => $row->order_item_id,
					'p' => $row->product_id,
					'c' => $row->coupon_code,
					'cid' => $row->id,
				);
				if ( isset( $row->filename ) ) {
					$tmp['f'] = $row->filename;
				}
				$codes[] = $tmp;
			}
			$codes_compact = urldecode( http_build_query( $codes ) );
			$this->db->query( 'INSERT INTO #__cmcoupon_voucher_customer (estore,user_id,order_id,codes)
								 VALUES ("' . $this->estore . '",' . $purchaser_user_id . ',' . $this->order->order_id . ',"' . $this->db->escape( $codes_compact ) . '")');
			$voucher_customer_id = $this->db->get_insertid();

			// insert each code into its own row.
			$insert_sql = array();
			foreach ( $codes as $code ) {
				$insert_sql[] = '(' . (int) $voucher_customer_id . ',' . (int) $code['i'] . ',' . (int) $code['p'] . ',' . (int) $code['cid'] . ',"' . $this->db->escape( $code['c'] ) . '")';
			}
			if ( ! empty( $insert_sql ) ) {
				$this->db->query( 'INSERT INTO #__cmcoupon_voucher_customer_code (voucher_customer_id,order_item_id,product_id,coupon_id,code) VALUES ' . implode( ',', $insert_sql ) );
			}

			if ( (int) $this->params->get( 'giftcert_vendor_enable', 0 ) === 1 && ! empty( $vendor_codes ) ) {

				$is_send_vendor_email = true;

				if ( $is_send_vendor_email ) {
					$t_subject = $this->params->get( 'giftcert_vendor_subject', 'Vendor Email - Codes' );
					$t_message = $this->params->get( 'giftcert_vendor_email', '' );
					if ( strpos( $t_message, '{vouchers}' ) === false ) {
						$t_message .= '<br /><br />{vouchers}<br />';
					}
					foreach ( $vendor_codes as $vendor_email => $codes ) {
						if ( empty( $vendor_email ) || ! AC()->helper->is_email( $vendor_email ) ) {
							continue;
						}
						$subject = str_replace(
							array( '{vendor_name}', '{purchaser_first_name}', '{purchaser_last_name}', '{order_id}', '{order_number}', '{today_date}' ),
							array( $codes['name'], $customer_first_name, $customer_last_name, $this->order->order_id, $this->order->order_number, AC()->helper->get_date() ),
							$t_subject
						);
						$message = str_replace(
							array( '{vendor_name}', '{vouchers}', '{purchaser_first_name}', '{purchaser_last_name}', '{order_id}', '{order_number}', '{today_date}' ),
							array( $codes['name'], implode( '', $codes['codes'] ), $customer_first_name, $customer_last_name, $this->order->order_id, $this->order->order_number, AC()->helper->get_date() ),
							$t_message
						);
						AC()->helper->send_email( $vendor_from_email, $store_name, $vendor_email, $subject, $message, 1 );
					}
				}
			}
		}
		return true;
	}

	/**
	 * Parse through rows for resending automatic email
	 *
	 * @param array $rows rows.
	 **/
	protected function parse_resend_orderitem_rows( $rows ) {
		$select_rows = array();
		foreach ( $rows as $k => $row ) {
			if ( ! isset( $select_rows[ $row->order_item_id ] ) ) {
				$select_rows[ $row->order_item_id ] = $row;
				$select_rows[ $row->order_item_id ]->coupons = array();
			}

			$coupon_row = AC()->db->get_object( 'SELECT id,coupon_code,expiration,coupon_value,coupon_value_type FROM #__cmcoupon WHERE id=' . $row->coupon_id );
			if ( empty( $coupon_row ) ) {
				return false;
			}

			$price = '';
			if ( ! empty( $row->coupon_value ) ) {
				$price = 'amount' === $row->coupon_value_type
								? $row->coupon_value
								: round( $row->coupon_value ) . '%';
			}

			$select_rows[ $row->order_item_id ]->coupons[] = array(
				'id' => $row->coupon_id,
				'order_item_id' => $row->order_item_id,
				'user_id' => $row->user_id,
				'product_id' => $row->product_id,
				'product_name' => $row->order_item_name,
				'product_qty_name' => $row->product_quantity . ' ' . $row->order_item_name,
				'email' => $row->email,
				'code' => $row->coupon_code,
				'price' => $price,
				'expiration' => $row->expiration,
				'expirationraw' => ! empty( $row->expiration ) ? strtotime( $row->expiration ) : 0,
				'profile' => '',
				'file' => '',
				'coupon_row' => $coupon_row,
			);
		}

		return $select_rows;
	}

	/**
	 * Format the currency
	 *
	 * @param float $val the amount.
	 **/
	protected function formatcurrency( $val ) {
		return $val;
	}

	/**
	 * Get product attributes
	 *
	 * @param object $row the item.
	 **/
	protected function getproductattributes( $row ) {
		return array(
			'recipient_name' => $row->first_name . ' ' . $row->last_name,
			'email' => $row->email,
			'message' => '',
			'from_name' => $row->first_name . ' ' . $row->last_name,
		);
	}

	/**
	 * Clean up errors
	 *
	 * @param string $message info to log.
	 **/
	protected function cleanup_error( $message ) {

		$this->loginfo( $message );

		if ( ! empty( $this->cleanup_data['files'] ) ) {
			foreach ( $this->cleanup_data['files'] as $file ) {
				if ( ! empty( $file ) && file_exists( $file ) ) {
					unlink( $file );
				}
			}
		}

		if ( ! $this->is_entry_new ) {
			return false;
		}

		if ( ! empty( $this->cleanup_data['coupon_codes'] ) ) {
			foreach ( $this->cleanup_data['coupon_codes'] as $coupon_id ) {
				$this->db->query( 'DELETE FROM #__cmcoupon WHERE id=' . $coupon_id );
			}
		}

		if ( ! empty( $this->cleanup_data['manual_codes'] ) ) {
			foreach ( $this->cleanup_data['manual_codes'] as $product_id => $codes ) {
				$this->db->query( 'UPDATE #__cmcoupon_giftcert_code SET status="active" WHERE estore="' . $this->estore . '" AND status="used" AND product_id=' . $product_id . ' AND code IN ("' . implode( '","', $codes ) . '")' );
			}
		}
		return false;
	}

	/**
	 * Get giftcert code
	 *
	 * @param object $order_row the order.
	 **/
	protected function get_giftcertcode( $order_row ) {

		$update_codes = false;

		$coupon_code = null;
		$expirationdays = null;

		$usedstr = ! empty( $this->cleanup_data['manual_codes'][ $order_row->product_id ] ) ? ' AND code NOT IN ("' . implode( '","', $this->cleanup_data['manual_codes'][ $order_row->product_id ] ) . '")' : '';
		$sql = 'SELECT code FROM #__cmcoupon_giftcert_code WHERE estore="' . $this->estore . '" AND product_id=' . $order_row->product_id . ' AND status="active" ' . $usedstr;
		$tmp = $this->db->get_value( $sql );
		if ( ! empty( $tmp ) ) {
			$coupon_code = trim( $order_row->coupon_code_prefix ) . $tmp . trim( $order_row->coupon_code_suffix );
		}

		$min_length = $this->params->get( 'giftcert_min_length', 8 );
		$max_length = $this->params->get( 'giftcert_max_length', 12 );
		if ( empty( $coupon_code ) ) {
			$coupon_code = AC()->coupon->generate_coupon_code( $order_row->coupon_code_prefix, $order_row->coupon_code_suffix, $min_length, $max_length );
		}

		if ( ! empty( $order_row->expiration_number ) && ! empty( $order_row->expiration_type ) ) {
			if ( 'day' === $order_row->expiration_type ) {
				$expirationdays = (int) $order_row->expiration_number;
			} elseif ( 'month' === $order_row->expiration_type ) {
				$expirationdays = (int) $order_row->expiration_number * 30;
			} elseif ( 'year' === $order_row->expiration_type ) {
				$expirationdays = (int) $order_row->expiration_number * 365;
			}
		}

		$rtn = AC()->coupon->generate( $order_row->coupon_template_id, $coupon_code, $expirationdays, null );
		if ( empty( $rtn->coupon_id ) ) {
			return;
		}
		$this->cleanup_data['coupon_codes'][] = $rtn->coupon_id;

		if ( ! empty( $coupon_code ) && $rtn->coupon_code === $coupon_code ) {
			$this->cleanup_data['manual_codes'][ $order_row->product_id ][] = $rtn->coupon_code;
			$this->db->query( 'UPDATE #__cmcoupon_giftcert_code SET status="used" WHERE estore="' . $this->estore . '" AND product_id=' . $order_row->product_id . ' AND code="' . $rtn->coupon_code . '" AND status="active"' );
		}

		return $rtn;
	}

	/**
	 * Get order status list
	 **/
	protected function get_orderstatuslist() {
		return array();
	}

	/**
	 * Check attribute
	 *
	 * @param array $mail_items what to log.
	 **/
	protected function check_product_attribute( &$mail_items ) {
		return true;
	}

	/**
	 * Log information
	 *
	 * @param string/array $text what to log.
	 **/
	protected function loginfo( $text ) {
		if ( (int) $this->params->get( 'enable_giftcert_debug', 0 ) !== 1 ) {
			return;
		}

		$type = 'message';

		$the_date = AC()->helper->get_date( null, 'Y-m-d H:i:s', 'utc2utc' );

		$fp = fopen( $this->log_file, 'a+' );
		if ( $fp ) {
			if ( is_array( $text ) ) {
				$text = print_r( $text, 1 );
			}
			$text = "\n" . $the_date . ' ' . strtoupper( $type )
						. "\norder[" . $this->order->order_id . ']'
						. "\n" . $text;
			fwrite( $fp, $text );
			fclose( $fp );
		}
	}

}
