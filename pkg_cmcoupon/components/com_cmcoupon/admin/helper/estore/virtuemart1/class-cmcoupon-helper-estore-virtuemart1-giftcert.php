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

class CmCoupon_Helper_Estore_Virtuemart1_Giftcert  extends CmCoupon_Library_Giftcert {

	var $estore = 'virtuemart1';
	var $order = null;

	public function __construct() {
		parent::__construct();
	}

	public function order_status_changed( $order, $status_from ) {
		$this->order = $this->get_storeorder( $order );
		$auto_order_status = $this->params->get( 'giftcert_order_status', 'C' );
		if ( empty( $auto_order_status ) ) {
			return false;
		}
		if ( ! is_array( $auto_order_status ) ) {
			$auto_order_status = array( $auto_order_status );
		}
		if ( ! in_array( $this->order->order_status, $auto_order_status ) ) {
			return false;
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
					i.order_item_id
					,i.order_id
					,i.product_quantity AS product_quantity
					,u.user_id AS user_id
					,u.user_email AS email
					,u.first_name AS first_name
					,u.last_name AS last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,ap.product_id
					,i.product_attribute AS product_attribute
					,i.order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,o.order_currency AS currency_code
					,i.product_item_price AS product_price_notax
					,i.product_final_price AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix
				  FROM #__vm_order_item i 
				  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=i.product_id
				  JOIN #__vm_product p ON p.product_id=i.product_id
				  JOIN #__vm_orders o ON o.order_id=i.order_id
				  JOIN #__vm_order_user_info u ON u.order_id=i.order_id AND u.address_type="BT"
				  LEFT JOIN #__cmcoupon_voucher_customer g ON g.order_id=i.order_id AND g.estore=ap.estore
				 WHERE ap.estore="'. $this->estore . '"
				   AND i.order_id=' . $this->order->order_id . '
				   AND g.order_id IS NULL
				   AND ap.published=1
				 GROUP BY i.order_item_id

							UNION

				SELECT 
					i.order_item_id
					,i.order_id
					,i.product_quantity AS product_quantity
					,u.user_id AS user_id
					,u.user_email AS email
					,u.first_name AS first_name
					,u.last_name AS last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,p.product_parent_id AS product_id
					,i.product_attribute AS product_attribute
					,i.order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,o.order_currency AS currency_code
					,i.product_item_price AS product_price_notax
					,i.product_final_price AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix
				  FROM #__vm_order_item i 
				  JOIN #__vm_product p ON p.product_id=i.product_id
				  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=p.product_parent_id 
				  JOIN #__vm_orders o ON o.order_id=i.order_id
				  JOIN #__vm_order_user_info u ON u.order_id=i.order_id AND u.address_type="BT"
				  LEFT JOIN #__cmcoupon_voucher_customer g ON g.order_id=i.order_id AND g.estore="virtuemart1"
				 WHERE ap.estore="'. $this->estore . '"
				   AND i.order_id=' . $this->order->order_id . '
				   AND g.order_id IS NULL
				   AND ap.published=1
				   AND p.product_parent_id!=0
				 GROUP BY i.order_item_id
			) t
			GROUP BY order_item_id;
		';
		$rows = AC()->db->get_objectlist( $sql );
		if ( empty( $rows ) ) {
			$this->loginfo( print_r( $sql, 1 ) );
		}
		$this->generate_auto_email( $rows );
	}

	protected function get_resend_orderitem_rows( $voucher_customer_id ) {
		return AC()->db->get_objectlist('
			SELECT  gc.codes,c.id AS coupon_id,c.coupon_code,c.expiration,c.coupon_value,c.coupon_value_type,

					i.order_item_id
					,i.order_id
					,i.product_quantity AS product_quantity
					,u.user_id AS user_id
					,u.user_email AS email
					,u.first_name AS first_name
					,u.last_name AS last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,ap.product_id
					,i.product_attribute AS product_attribute
					,i.order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,o.order_currency AS currency_code
					,i.product_item_price AS product_price_notax
					,i.product_final_price AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix

			  FROM #__cmcoupon_voucher_customer_code gcc
			  JOIN #__cmcoupon_voucher_customer gc ON gc.id=gcc.voucher_customer_id
			  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=gcc.product_id AND ap.estore=gc.estore
			  JOIN #__cmcoupon c ON c.coupon_code=gcc.code
			  LEFT JOIN #__vm_order_item i ON i.order_id=gc.order_id AND i.order_item_id=gcc.order_item_id
			  LEFT JOIN #__vm_orders o ON o.order_id=i.order_id
			  LEFT JOIN #__vm_order_user_info u ON u.order_id=i.order_id AND u.address_type="BT"
			 WHERE gc.id=' . (int) $voucher_customer_id . '
			   AND gc.estore="' . $this->estore . '"
			   AND ap.published=1
		');
	}

	protected function formatcurrency( $val ) {
		return AC()->storecurrency->format( $val );
	}

	protected function get_storeorder( $in ) {
		$order_id = 0;
		if ( is_object( $in ) ) {
			$order_id = (int) $in->order_id;
		}
		elseif ( is_array( $in ) ) {
			$order_id = (int) $in['order_id'];
		}
		$order = AC()->db->get_object( '
			SELECT order_id,order_number,vendor_id,order_status,order_total,FROM_UNIXTIME(cdate) AS created_on, order_currency AS currenty
			  FROM #__vm_orders
			 WHERE order_id=' . (int) $order_id . '
		');
		if ( empty( $order ) ) {
			$order = new stdClass();
			$order->order_id = 0;
			$order->order_number = '';
			$order->order_status = '';
			$order->order_total = 0;
			$order->created_on = '';
			$order->currency = '';
		}
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
		$url = 'index.php?option=com_virtuemart&page=account.order_details&order_id=' . $this->order->order_id;
		return JRoute::_( JURI::root() . $url );
	}

	protected function getproductattributes( $row ) {
		$recipient_email = $row->email;
		$recipient_name = $row->first_name . ' ' . $row->last_name;
		$message = '';
		$from_name = $row->first_name . ' ' . $row->last_name;

		if ( ! empty( $row->product_attribute ) ) {
			$attrlist = array();
			foreach ( explode( '<br/>', $row->product_attribute ) as $v ) {
				list( $a, $b ) = explode( ':', $v );
				$attrlist[ trim( $a ) ] = trim( $b );
			}
			$name_field = trim( $this->params->get( 'virtuemart1_giftcert_field_recipient_name', 'recipient name' ) );
			$email_field = trim( $this->params->get( 'virtuemart1_giftcert_field_recipient_email', 'recipient email' ) );
			$message_field = trim( $this->params->get( 'virtuemart1_giftcert_field_recipient_message', 'message' ) );
			$fromname_field = trim( $this->params->get( 'virtuemart1_giftcert_field_from_name', 'from_name' ) );
			if ( ! empty( $attrlist[ $email_field ] ) && AC()->helper->is_email( $attrlist[ $email_field ] ) ) {
				$recipient_email = $attrlist[ $email_field ];
				if ( ! empty( $attrlist[ $name_field ] ) ) {
					$recipient_name = $attrlist[ $name_field ];
				}
				if ( ! empty( $attrlist[ $message_field ] ) ) {
					$message = $attrlist[ $message_field ];
				}
				if ( ! empty( $attrlist[ $fromname_field ] ) ) {
					$from_name = $attrlist[ $fromname_field ];
				}
			}
		}

		return array(
			'recipient_name' => trim( $recipient_name ),
			'email' => trim( $recipient_email ),
			'message' => trim( $message ),
			'from_name' => trim( $from_name ),
		);
	}

	protected function get_product_price_in_default_currency( $amount, $row ) {
		if ( empty( $amount ) ) {
			return 0;
		}
		if ( empty( $row->currency_code ) ) {
			return 0;
		}
		return AC()->storecurrency->convert_to_default( $amount, $row->currency_code );
	}

}
