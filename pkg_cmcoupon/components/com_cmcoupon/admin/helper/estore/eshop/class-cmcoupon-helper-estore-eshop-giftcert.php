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

class CmCoupon_Helper_Estore_Eshop_Giftcert  extends CmCoupon_Library_Giftcert {

	var $estore = 'eshop';
	var $order = null;

	public function __construct() {
		parent::__construct();
	}

	public function order_status_changed( $order_id ) {
		$this->order = $this->get_storeorder( $order_id );
		$auto_order_status = $this->params->get( 'giftcert_order_status', '' );
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
		$this->order = $this->get_storeorder( $order_id );

		$this->is_entry_new = false;
		return $this->generate_auto_email( $select_rows );
	}

	protected function get_order_orderitem_rows() {

		// order confirmed now mail out gift certs if any

		$sql = '
			SELECT
					i.id AS order_item_id
					,i.order_id
					,i.quantity AS product_quantity
					,o.customer_id AS user_id
					,o.email AS email
					,o.firstname AS first_name
					,o.lastname AS last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,ap.product_id
					,"" AS product_attribute
					,i.product_name AS order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,o.currency_code AS currency_code
					,i.price AS product_price_notax
					,(i.price+i.tax)  AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix

					,ap.from_name_id
					,ap.recipient_email_id
					,ap.recipient_name_id
					,ap.recipient_mesg_id

			  FROM #__eshop_orderproducts i 
			  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=i.product_id
			  LEFT JOIN #__eshop_orders o ON o.id=i.order_id
			  LEFT JOIN #__cmcoupon_voucher_customer g ON g.order_id=i.order_id AND g.estore="' . $this->estore . '"
			 WHERE i.order_id=' . $this->order->order_id . ' 
			   AND g.order_id IS NULL
			   AND ap.published=1
			   AND ap.estore="' . $this->estore . '"
			 GROUP BY i.id
		';
		$rows = AC()->db->get_objectlist( $sql );
		if ( empty( $rows ) ) {
			$this->loginfo( print_r( $sql, 1 ) );
		}
		$this->generate_auto_email( $rows );
	}

	protected function get_resend_orderitem_rows( $voucher_customer_id ) {
		return AC()->db->get_objectlist('
			SELECT	gc.codes,c.id AS coupon_id,c.coupon_code,c.expiration,c.coupon_value,c.coupon_value_type,

					i.id AS order_item_id
					,i.order_id
					,i.quantity AS product_quantity
					,o.customer_id AS user_id
					,o.email AS email
					,o.firstname AS first_name
					,o.lastname AS last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,i.product_id
					,"" AS product_attribute
					,i.product_name AS order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,o.currency_code AS currency_code
					,i.price AS product_price_notax
					,(i.price+i.tax)  AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix

					,ap.from_name_id
					,ap.recipient_email_id
					,ap.recipient_name_id
					,ap.recipient_mesg_id

			  FROM #__cmcoupon_voucher_customer_code gcc
			  JOIN #__cmcoupon_voucher_customer gc ON gc.id=gcc.voucher_customer_id
			  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=gcc.product_id AND ap.estore=gc.estore
			  JOIN #__cmcoupon c ON c.coupon_code=gcc.code
			  LEFT JOIN #__eshop_orderproducts i ON i.order_id=gc.order_id AND i.id=gcc.order_item_id
			  LEFT JOIN #__eshop_orders o ON o.id=i.order_id
			 WHERE gc.id=' . (int) $voucher_customer_id . '
			   AND gc.estore="' . $this->estore . '"
			   AND ap.published=1
		');
	}

	protected function formatcurrency( $val ) {
		return AC()->storecurrency->format( $val );
	}

	protected function get_storeorder( $order_id ) {
		$order = new stdClass();
		$order->order_id = 0;
		$order->order_number = '';
		$order->order_status = 0;
		$order->order_total = 0;
		$order->created_on = 1;
		$order->currency = 0;

		$dborder = AC()->db->get_object( 'SELECT id,order_number,order_status_id,total,created_date,currency_code FROM #__eshop_orders WHERE id=' . (int) $order_id ); 
		if ( empty( $dborder ) ) {
			return $order;
		}

		$order->order_id = $dborder->id;
		$order->order_number = $dborder->order_number;
		$order->order_status = $dborder->order_status_id;
		$order->order_total = $dborder->total;
		$order->created_on = strtotime($dborder->created_date);
		$order->currency = $dborder->currency_code;

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
		$url = 'index.php?option=com_eshop&view=customer&layout=order&order_id=' . $this->order->order_id;
		return JRoute::_( JURI::root() . $url );
	}

	protected function getproductattributes( $row ) {
		$recipient_email = $row->email;
		$recipient_name = $row->first_name . ' ' . $row->last_name;
		$message = '';
		$from_name = $row->first_name . ' ' . $row->last_name;

		$fromname_id = (int) $row->from_name_id;		// fromname_id
		$email_id = (int) $row->recipient_email_id;		// email_id
		$name_id = (int) $row->recipient_name_id;		// name_id
		$message_id = (int) $row->recipient_mesg_id;	// message_id

		$options = AC()->db->get_objectlist( '
			SELECT p.option_id,o.option_value
			  FROM #__eshop_orderoptions o
			  JOIN #__eshop_productoptions p ON p.id=o.product_option_id
			 WHERE o.order_id=' . (int) $row->order_id . '
			   AND o.order_product_id=' . (int) $row->order_item_id . '
			   AND p.option_id IN (' . $name_id . ',' . $email_id . ',' . $message_id . ',' . $fromname_id . ')
		', 'option_id' );

		if ( ! empty( $email_id )
		&& ! empty( $options[ $email_id ] )
		&& ! empty( $options[ $email_id ]->option_value )
		&& AC()->helper->is_email( $options[ $email_id ]->option_value ) ) {
			$recipient_email = trim( $options[ $email_id ]->option_value );
		}

		if ( ! empty( $name_id )
		&& ! empty( $options[ $name_id ] )
		&& ! empty( $options[ $name_id ]->option_value ) ) {
			$recipient_name = trim( $options[ $name_id ]->option_value );
		}

		if ( ! empty( $message_id )
		&& ! empty( $options[ $message_id ] )
		&& ! empty( $options[ $message_id ]->option_value ) ) {
			$message = trim( $options[ $message_id ]->option_value );
		}

		if ( ! empty( $fromname_id )
		&& ! empty( $options[ $fromname_id ] )
		&& ! empty( $options[ $fromname_id ]->option_value ) ) {
			$from_name = trim( $options[ $fromname_id ]->option_value );
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
		if ( empty( $row->currency_id ) ) {
			return 0;
		}
		return AC()->storecurrency->convert_to_default( $amount, $row->currency_id );
	}

}
