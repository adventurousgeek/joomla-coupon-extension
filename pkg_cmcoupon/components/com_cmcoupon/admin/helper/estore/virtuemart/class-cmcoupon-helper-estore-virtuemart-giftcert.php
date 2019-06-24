<?php
/**
 * CmCoupon
 *
 * @package Joomla CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined('_JEXEC') or die('Restricted access');
if ( ! defined( '_CM_' ) ) {
	exit;
}

AC()->helper->add_class( 'CmCoupon_Library_Giftcert' );

class CmCoupon_Helper_Estore_Virtuemart_Giftcert  extends CmCoupon_Library_Giftcert {

	var $estore = 'virtuemart';
	var $order = null;

	public function __construct() {
		parent::__construct();
		if ( ! class_exists( 'VmConfig' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
		}
		VmConfig::get( 'tester_for_vm2016', '', true); // needed in vm2016 otherwise the configuration variables may not have been loaded and never will be if called
	}

	public function order_status_changed( $order, $status_to ) {
		$this->order = $this->get_storeorder( $order );
		$auto_order_status = $this->params->get( 'giftcert_order_status', 'C' );
		if ( empty( $auto_order_status ) ) {
			return;
		}
		if ( ! is_array( $auto_order_status ) ) {
			$auto_order_status = array( $auto_order_status );
		}
		if ( ! in_array( $this->order->order_status, $auto_order_status ) ) {
			return;
		}
		$this->get_order_orderitem_rows();
		return true;
	}

	public function process_resend( $voucher_customer_id ) {
		$rows = $this->get_resend_orderitem_rows( $voucher_customer_id );
		if ( empty( $rows ) ) {
			return false;
		}

		$select_rows = $this->parse_resend_orderitem_rows( $rows );
		if ( empty( $select_rows ) ) {
			return false;
		}

		$first_item = current( $rows );
		$order_id = $first_item->order_id;
		$order = AC()->store->get_order( $order_id );
		$this->order = $this->get_storeorder( $order );

		$this->is_entry_new = false;
		return $this->generate_auto_email( $select_rows );
	}

	protected function get_order_orderitem_rows() {

		// order confirmed now mail out gift certs if any

		$sql = '
			SELECT * FROM (
				SELECT 
					i.virtuemart_order_item_id AS order_item_id
					,i.virtuemart_order_id AS order_id
					,i.product_quantity
					,u.virtuemart_user_id AS user_id
					,u.email
					,u.first_name
					,u.last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,ap.product_id
					,i.product_attribute
					,i.order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,o.order_currency AS currency_id
					,i.product_item_price AS product_price_notax
					,(i.product_item_price+i.product_tax) AS product_price
					,o.virtuemart_vendor_id as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix

					,ap.from_name_id
					,ap.recipient_email_id
					,ap.recipient_name_id
					,ap.recipient_mesg_id

					,p.product_parent_id

				  FROM #__virtuemart_order_items i 
				  JOIN #__cmcoupon_giftcert_product ap ON (
														ap.product_id=i.virtuemart_product_id 
																	OR 
														i.product_attribute LIKE CONCAT("%""child_id"":""",ap.product_id,"""%")
													)
				  JOIN #__virtuemart_orders o ON o.virtuemart_order_id=i.virtuemart_order_id
				  JOIN #__virtuemart_products p ON p.virtuemart_product_id=ap.product_id
				  JOIN #__virtuemart_order_userinfos u ON u.virtuemart_order_id=i.virtuemart_order_id AND u.address_type="BT"
				  LEFT JOIN #__cmcoupon_voucher_customer g ON g.order_id=i.virtuemart_order_id AND g.estore="' . $this->estore . '"
				 WHERE i.virtuemart_order_id=' . $this->order->order_id . '
				   AND g.order_id IS NULL
				   AND ap.published=1
				   AND ap.estore="' . $this->estore . '"
				 GROUP BY i.virtuemart_order_item_id

								UNION

				SELECT 
					i.virtuemart_order_item_id AS order_item_id
					,i.virtuemart_order_id AS order_id
					,i.product_quantity
					,u.virtuemart_user_id AS user_id
					,u.email
					,u.first_name
					,u.last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,p.product_parent_id AS product_id
					,i.product_attribute
					,i.order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,o.order_currency AS currency_id
					,i.product_item_price AS product_price_notax
					,(i.product_item_price+i.product_tax) AS product_price
					,o.virtuemart_vendor_id as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix

					,ap.from_name_id
					,ap.recipient_email_id
					,ap.recipient_name_id
					,ap.recipient_mesg_id

					,p.product_parent_id

				  FROM #__virtuemart_order_items i 
				  JOIN #__virtuemart_products p ON p.virtuemart_product_id=i.virtuemart_product_id
				  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=p.product_parent_id 
				  JOIN #__virtuemart_orders o ON o.virtuemart_order_id=i.virtuemart_order_id
				  JOIN #__virtuemart_order_userinfos u ON u.virtuemart_order_id=i.virtuemart_order_id AND u.address_type="BT"
				  LEFT JOIN #__cmcoupon_voucher_customer g ON g.order_id=i.virtuemart_order_id AND g.estore="' . $this->estore . '"
				 WHERE i.virtuemart_order_id=' . $this->order->order_id . '
				   AND g.order_id IS NULL
				   AND ap.published=1
				   AND ap.estore="' . $this->estore . '"
				   AND p.product_parent_id!=0
				 GROUP BY i.virtuemart_order_item_id
			) t
			GROUP BY order_item_id
		';
		$rows = AC()->db->get_objectlist( $sql );
		if ( empty( $rows ) ) {
			$this->loginfo( print_r( $sql, 1 ) );
		}
		$this->generate_auto_email( $rows );
	}

	protected function get_resend_orderitem_rows( $voucher_customer_id ) {
		return AC()->db->get_objectlist('
			SELECT gc.codes,c.id AS coupon_id,c.coupon_code,c.expiration,c.coupon_value,c.coupon_value_type,
					
					i.virtuemart_order_item_id AS order_item_id
					,i.virtuemart_order_id AS order_id
					,i.product_quantity
					,u.virtuemart_user_id AS user_id
					,u.email
					,u.first_name
					,u.last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,i.virtuemart_product_id AS product_id
					,i.product_attribute
					,i.order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,o.order_currency AS currency_id
					,i.product_item_price AS product_price_notax
					,(i.product_item_price+i.product_tax) AS product_price
					,o.virtuemart_vendor_id as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix

					,ap.from_name_id
					,ap.recipient_email_id
					,ap.recipient_name_id
					,ap.recipient_mesg_id

					,p.product_parent_id

			  FROM #__cmcoupon_voucher_customer_code gcc
			  JOIN #__cmcoupon_voucher_customer gc ON gc.id=gcc.voucher_customer_id
			  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=gcc.product_id AND ap.estore=gc.estore
			  JOIN #__cmcoupon c ON c.coupon_code=gcc.code
			  LEFT JOIN #__virtuemart_order_items i ON i.virtuemart_order_id=gc.order_id AND i.virtuemart_order_item_id=gcc.order_item_id
			  LEFT JOIN #__virtuemart_orders o ON o.virtuemart_order_id=i.virtuemart_order_id
			  LEFT JOIN #__virtuemart_products p ON p.virtuemart_product_id=ap.product_id
			  LEFT JOIN #__virtuemart_order_userinfos u ON u.virtuemart_order_id=i.virtuemart_order_id AND u.address_type="BT"
			 WHERE gc.id=' . (int) $voucher_customer_id . '
			   AND gc.estore="' . $this->estore . '"
			   AND ap.published=1
		');
	}

	protected function formatcurrency( $val ) {
		return AC()->storecurrency->format( $val );
	}

	protected function get_storeorder( $in ) {

		$order = new stdClass();
		$order->order_id = $in->virtuemart_order_id;
		$order->order_number = $in->order_number;
		$order->order_status = $in->order_status;
		$order->order_total = $in->order_total;
		$order->created_on = strtotime( ! empty( $in->created_on ) ? $in->created_on : $in->modified_on);

		$order->vendor_id = $in->virtuemart_vendor_id;
		$order->order_pass = $in->order_pass;

		return $order;
	}

	protected function get_orderstatuslist() {
		$list = AC()->store->get_order_status();
		$orderstatuses = array();
		foreach ( $list as $_ordstat ) {
			$orderstatuses[ $_ordstat->order_status_code ] = $_ordstat->order_status_name;
		}
		return $orderstatuses;
	}

	protected function get_orderlink() {
		$url = 'index.php?option=com_virtuemart&controller=orders&task=details&order_number=' . $this->order->order_number . '&order_pass=' . $this->order->order_pass;
		return JRoute::_( JURI::root() . $url );
	}

	protected function getproductattributes( $row ) {
		$attrlist = array();

		$recipient_email = $row->email;
		$recipient_name = $row->first_name . ' ' . $row->last_name;
		$message = '';
		$from_name = $row->first_name.' '.$row->last_name;

		$row->from_name_id = (int) $row->from_name_id;				// fromname_id
		$row->recipient_email_id = (int) $row->recipient_email_id;	// email_id
		$row->recipient_name_id = (int) $row->recipient_name_id;	// name_id
		$row->recipient_mesg_id = (int) $row->recipient_mesg_id;	// message_id

		$attrlist = array();
		if ( ! empty( $row->product_attribute ) ) {
			$custom_arr = AC()->db->get_objectlist( '
				SELECT virtuemart_custom_id,virtuemart_customfield_id as id
				  FROM #__virtuemart_product_customfields 
				 WHERE virtuemart_product_id=' . $row->product_id . '
				   AND virtuemart_custom_id IN (' . $row->from_name_id . ',' . $row->recipient_email_id . ',' . $row->recipient_name_id . ',' . $row->recipient_mesg_id . ')
			', 'virtuemart_custom_id' );

			if ( empty( $custom_arr ) && ! empty( $row->product_parent_id ) ) {
				$custom_arr = AC()->db->get_objectlist( '
					SELECT virtuemart_custom_id,virtuemart_customfield_id as id
					  FROM #__virtuemart_product_customfields 
					 WHERE virtuemart_product_id=' . $row->product_parent_id . '
					   AND virtuemart_custom_id IN (' . $row->from_name_id . ',' . $row->recipient_email_id . ',' . $row->recipient_name_id . ',' . $row->recipient_mesg_id . ')
				', 'virtuemart_custom_id' );
			}

			$attr = json_decode( $row->product_attribute );
			if ( ! empty( $custom_arr ) && ! empty( $attr ) ) {
				if ( isset( $custom_arr[ $row->from_name_id ] ) ) {
					if ( ! empty( $attr->{$custom_arr[ $row->from_name_id ]->id} ) && ! empty( $attr->{$custom_arr[ $row->from_name_id ]->id}->textinput->comment ) ) {
						$from_name = $attr->{$custom_arr[ $row->from_name_id ]->id}->textinput->comment;
					}
					elseif ( ! empty( $attr->{$custom_arr[ $row->from_name_id ]->virtuemart_custom_id} )
					&& ! empty( $attr->{$custom_arr[ $row->from_name_id ]->virtuemart_custom_id}->{$custom_arr[ $row->from_name_id ]->id}->comment ) ) {
						$from_name = $attr->{$custom_arr[ $row->from_name_id ]->virtuemart_custom_id}->{$custom_arr[ $row->from_name_id ]->id}->comment;
					}
				}

				if ( isset( $custom_arr[ $row->recipient_email_id ] ) ) {
					@$tmp_recipient_email = ! empty( $attr->{$custom_arr[ $row->recipient_email_id ]->id}) && ! empty( $attr->{$custom_arr[ $row->recipient_email_id ]->id}->textinput->comment )
						? $attr->{$custom_arr[ $row->recipient_email_id ]->id}->textinput->comment 
						: ( ! empty( $attr->{$custom_arr[ $row->recipient_email_id ]->virtuemart_custom_id}->{$custom_arr[ $row->recipient_email_id ]->id} )
							? $attr->{$custom_arr[ $row->recipient_email_id ]->virtuemart_custom_id}->{$custom_arr[ $row->recipient_email_id ]->id}->comment 
							: ''
						)
					;
					if ( ! empty( $tmp_recipient_email ) && AC()->helper->is_email( $tmp_recipient_email ) ) {
						$recipient_email = $tmp_recipient_email;
					}
				}

				if ( isset( $custom_arr[ $row->recipient_name_id ] ) ) {
					if ( ! empty( $attr->{$custom_arr[ $row->recipient_name_id ]->id} ) && ! empty( $attr->{$custom_arr[ $row->recipient_name_id ]->id}->textinput->comment ) ) {
						$recipient_name = $attr->{$custom_arr[ $row->recipient_name_id ]->id}->textinput->comment;
					}
					elseif ( ! empty( $attr->{$custom_arr[ $row->recipient_name_id ]->virtuemart_custom_id } )
					&& ! empty( $attr->{$custom_arr[ $row->recipient_name_id ]->virtuemart_custom_id}->{$custom_arr[ $row->recipient_name_id ]->id}->comment ) ) {
						$recipient_name = $attr->{$custom_arr[ $row->recipient_name_id ]->virtuemart_custom_id}->{$custom_arr[ $row->recipient_name_id ]->id}->comment;
					}
				}

				if ( isset( $custom_arr[ $row->recipient_mesg_id ] ) ) {
					if ( ! empty( $attr->{$custom_arr[ $row->recipient_mesg_id ]->id} ) && ! empty( $attr->{$custom_arr[ $row->recipient_mesg_id ]->id}->textinput->comment ) ) {
						$message =  $attr->{$custom_arr[ $row->recipient_mesg_id ]->id}->textinput->comment;
					}
					elseif ( ! empty( $attr->{$custom_arr[ $row->recipient_mesg_id ]->virtuemart_custom_id} )
					&& ! empty( $attr->{$custom_arr[ $row->recipient_mesg_id ]->virtuemart_custom_id}->{$custom_arr[ $row->recipient_mesg_id ]->id}->comment ) ) {
						$message = $attr->{$custom_arr[ $row->recipient_mesg_id ]->virtuemart_custom_id}->{$custom_arr[ $row->recipient_mesg_id ]->id}->comment;
					}
				}
			}
		}

		return array(
			'from_name' => trim( html_entity_decode( $from_name ) ), 
			'email' => trim( html_entity_decode( $recipient_email ) ),
			'recipient_name' => trim( html_entity_decode( $recipient_name ) ),
			'message' => trim( html_entity_decode( $message ) ),
		); 
	}

	protected function get_product_price_in_default_currency( $amount, $row ) {
		if ( empty( $amount ) ) {
			return 0;
		}
		if ( empty( $row->currency_id ) ) {
			return 0;
		}
		return AC()->storecurrency->convert_to_default( $amount, $row->currency_id );
	}

}
