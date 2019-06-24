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
class CmCoupon_Admin_Class_Emailcoupon extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id coupon_id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'emailcoupon';
		$this->_id = $id;
		$this->_orderby = '';
		$this->_primary = '';
		parent::__construct();
	}

	/**
	 * Get item properties
	 */
	public function get_entry() {
		$this->_entry = new stdClass();

		$this->_entry->recipient_type = '';
		$this->_entry->recipient_customer = '';
		$this->_entry->user_id = '';
		$this->_entry->recipient_email = '';

		$this->_entry->coupon_type = '';
		$this->_entry->coupon_coupon = '';
		$this->_entry->coupon_id = '';
		$this->_entry->coupon_template = '';
		$this->_entry->template_id = '';

		$this->_entry->profile_id = '';
		$this->_entry->email_subject = '';
		$this->_entry->email_body = '';
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

		// Get profile.
		$profile = AC()->db->get_arraylist( 'SELECT * FROM #__cmcoupon_profile WHERE id=' . (int) $row->profile_id );
		$row->profile = AC()->profile->decrypt_profile( current( $profile ) );

		// Get the sending user.
		if ( 'customer' === $row->recipient_type ) {
			$row->send_user = AC()->helper->get_user( $row->user_id );
		} else {
			$user = AC()->helper->get_user_by_email( $row->recipient_email );
			if ( ! empty( $user->id ) ) {
				$row->send_user = $user;
			} else {
				$row->send_user = (object) array(
					'id' => 0,
					'email' => $row->recipient_email,
				);
			}
		}

		// Get the coupon data.
		if ( 'coupon' === $row->coupon_type ) {
			$coupon_id = (int) $row->coupon_id;
		} elseif ( 'template' === $row->coupon_type ) {
			$rtn = AC()->coupon->generate( $row->template_id );
			$coupon_id = $rtn->coupon_id;
		}

		$coupon_row = AC()->db->get_object( 'SELECT * FROM #__cmcoupon WHERE id=' . (int) $coupon_id );
		$coupon_row->coupon_price = '';
		if ( ! empty( $coupon_row->coupon_value ) ) {
			$coupon_row->coupon_price = 'amount' === $coupon_row->coupon_value_type
						? AC()->storecurrency->format( $coupon_row->coupon_value )
						: round( $coupon_row->coupon_value ) . '%';
		}
		$coupon_row->profile = $row->profile;
		$row->coupon_row = $coupon_row;

		// Get dynamic tags.
		$row->tags = array(
			'find' => array(
				'{siteurl}',
				'{today_date}',
				'{coupon_code}',
				'{coupon_value}',
				'{secret_key}',
				'{coupon_expiration}',
			),
			'replace' => array(
				AC()->store->get_home_link(),
				AC()->helper->get_date(),
				$row->coupon_row->coupon_code,
				$row->coupon_row->coupon_price,
				$row->coupon_row->passcode,
				! empty( $row->coupon_row->expiration ) ? AC()->helper->get_date( $row->coupon_row->expiration ) : '',
			),
		);

		$params = array(
			'override_email_subject' => $row->email_subject,
			'override_email_message' => AC()->helper->fixpaths_relative_to_absolute( $row->email_body ),
		);

		$codes = AC()->profile->send_email( $row->send_user, array( $row->coupon_row ), $row->profile, $row->tags, false, null, true, $params );

		if ( false === $codes ) {
			if ( ! empty( $row->coupon_row->id ) && 'coupon' !== $row->coupon_type ) {
				AC()->db->query( 'DELETE FROM #__cmcoupon WHERE id=' . (int) $row->coupon_row->id );
			}
			$errors[] = AC()->lang->__( 'Error sending email' );
			return $errors;
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

		if ( empty( $row->recipient_type ) || ! in_array( $row->recipient_type, array( 'customer', 'email' ), true ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Recipient' ) );
		} else {
			if ( 'customer' === $row->recipient_type ) {
				if ( empty( $row->user_id ) || ! AC()->helper->pos_int( $row->user_id ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Customer' ) );
				} else {
					$test = AC()->helper->get_user( $row->user_id );
					if ( empty( $test->id ) ) {
						$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Customer' ) );
					}
				}
			}
			if ( 'email' === $row->recipient_type ) {
				if ( empty( $row->recipient_email ) || ! AC()->helper->is_email( $row->recipient_email ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'E-mail' ) );
				}
			}
		}

		if ( empty( $row->coupon_type ) || ! in_array( $row->coupon_type, array( 'coupon', 'template' ), true ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon' ) );
		} else {
			if ( 'coupon' === $row->coupon_type ) {
				if ( empty( $row->coupon_id ) || ! AC()->helper->pos_int( $row->coupon_id ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon' ) );
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
			}
		}

		if ( empty( $row->profile_id ) || ! AC()->helper->pos_int( $row->profile_id ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Email Template' ) );
		} else {
			$test = (int) AC()->db->get_value( 'SELECT id FROM #__cmcoupon_profile WHERE id=' . (int) $row->profile_id );
			if ( $test <= 0 ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Email Template' ) );
			}
		}
		if ( empty( $row->email_subject ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Email Subject' ) );
		}
		if ( empty( $row->email_subject ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Email Body' ) );
		}

		return $err;
	}

}
