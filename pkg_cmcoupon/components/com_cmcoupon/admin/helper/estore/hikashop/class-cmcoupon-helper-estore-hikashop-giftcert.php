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

class CmCoupon_Helper_Estore_Hikashop_Giftcert  extends CmCoupon_Library_Giftcert {

	var $estore = 'hikashop';
	var $order = null;

	public function __construct() {
		parent::__construct();

		if ( ! defined( 'DS' ) ) {
			define( 'DS', DIRECTORY_SEPARATOR );
		}
		if ( ! class_exists( 'hikashop' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_hikashop/helpers/helper.php';
		}
	}

	public function order_status_changed( $order ) {
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

		$sql = 'SELECT
					i.order_product_id AS order_item_id
					,i.order_id AS order_id
					,i.order_product_quantity AS product_quantity
					,uh.user_cms_id AS user_id
					,uh.user_email AS email
					,u.address_firstname AS first_name
					,u.address_lastname as last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,ap.product_id
					,i.order_product_name AS order_item_name
					,i.order_product_options AS product_attribute
					,ap.vendor_name
					,ap.vendor_email
					,o.order_currency_id AS currency_id
					,i.order_product_price AS product_price_notax
					,(i.order_product_price+i.order_product_tax) AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix

					,ap.from_name_id
					,ap.recipient_email_id
					,ap.recipient_name_id
					,ap.recipient_mesg_id

					,i.product_id AS child_product_id
					,o.order_user_id

				  FROM #__hikashop_order_product i 
				  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=i.product_id
				  JOIN #__hikashop_product p ON p.product_id=i.product_id
				  JOIN #__hikashop_order o ON o.order_id=i.order_id
				  LEFT JOIN #__hikashop_address u ON u.address_id=o.order_billing_address_id
				  LEFT JOIN #__hikashop_user uh ON uh.user_id=o.order_user_id
				  LEFT JOIN #__cmcoupon_voucher_customer g ON g.order_id=i.order_id AND g.estore="hikashop"
				 WHERE i.order_id=' . $this->order->order_id . ' AND p.product_type="main" AND g.order_id IS NULL AND ap.published=1 AND ap.estore="hikashop"
				 GROUP BY i.order_product_id
				 
												UNION
				 
				SELECT
					i.order_product_id AS order_item_id
					,i.order_id AS order_id
					,i.order_product_quantity AS product_quantity
					,uh.user_cms_id AS user_id
					,uh.user_email AS email
					,u.address_firstname AS first_name
					,u.address_lastname as last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,p.product_parent_id AS product_id
					,i.order_product_name AS order_item_name
					,i.order_product_options AS product_attribute
					,ap.vendor_name
					,ap.vendor_email
					,o.order_currency_id AS currency_id
					,i.order_product_price AS product_price_notax
					,(i.order_product_price+i.order_product_tax) AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix

					,ap.from_name_id
					,ap.recipient_email_id
					,ap.recipient_name_id
					,ap.recipient_mesg_id

					,i.product_id AS child_product_id
					,o.order_user_id

				  FROM #__hikashop_order_product i 
				  JOIN #__hikashop_product p ON p.product_id=i.product_id
				  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=p.product_parent_id
				  JOIN #__hikashop_order o ON o.order_id=i.order_id
				  LEFT JOIN #__hikashop_address u ON u.address_id=o.order_billing_address_id
				  LEFT JOIN #__hikashop_user uh ON uh.user_id=o.order_user_id
				  LEFT JOIN #__cmcoupon_voucher_customer g ON g.order_id=i.order_id AND g.estore="hikashop"
				 WHERE i.order_id=' . $this->order->order_id . ' AND p.product_type="variant" AND g.order_id IS NULL AND ap.published=1 AND ap.estore="hikashop"
				 GROUP BY i.order_product_id					 
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

					i.order_product_id AS order_item_id
					,i.order_id AS order_id
					,i.order_product_quantity AS product_quantity
					,uh.user_cms_id AS user_id
					,uh.user_email AS email
					,u.address_firstname AS first_name
					,u.address_lastname as last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,ap.product_id
					,i.order_product_name AS order_item_name
					,i.order_product_options AS product_attribute
					,ap.vendor_name
					,ap.vendor_email
					,o.order_currency_id AS currency_id
					,i.order_product_price AS product_price_notax
					,(i.order_product_price+i.order_product_tax) AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix

					,ap.from_name_id
					,ap.recipient_email_id
					,ap.recipient_name_id
					,ap.recipient_mesg_id

					,i.product_id AS child_product_id
					,o.order_user_id

			  FROM #__cmcoupon_voucher_customer_code gcc
			  JOIN #__cmcoupon_voucher_customer gc ON gc.id=gcc.voucher_customer_id
			  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=gcc.product_id AND ap.estore=gc.estore
			  JOIN #__cmcoupon c ON c.coupon_code=gcc.code
			  LEFT JOIN #__hikashop_order_product i ON i.order_id=gc.order_id AND i.order_product_id=gcc.order_item_id
			  LEFT JOIN #__hikashop_order o ON o.order_id=i.order_id
			  LEFT JOIN #__hikashop_address u ON u.address_id=o.order_billing_address_id
			  LEFT JOIN #__hikashop_user uh ON uh.user_id=o.order_user_id
			 WHERE gc.id=' . (int) $voucher_customer_id . '
			   AND gc.estore="' . $this->estore . '"
			   AND ap.published=1
		');
	}

	protected function formatcurrency( $val ) {
		return AC()->storecurrency->format( $val );
	}

	protected function get_storeorder( $in ) {

		if ( ! isset( $in->order_number ) ) {
			$tmp = AC()->db->get_object( 'SELECT order_number,order_full_price,order_created FROM #__hikashop_order WHERE order_id=' . $in->order_id );
			if ( ! empty( $tmp ) ) {
				$in->order_number = $tmp->order_number;
				$in->order_full_price = $tmp->order_full_price;
				$in->order_created = $tmp->order_created;
			}
		}

		$order = new stdClass();
		$order->order_id = $in->order_id;
		$order->order_number = $in->order_number;
		$order->order_status = $in->order_status;
		$order->order_total = isset( $in->order_full_price ) ? $in->order_full_price : 0;
		$order->created_on = $in->order_created;
		
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
		$url = 'index.php?option=com_hikashop&ctrl=order&task=show&cid=' . $this->order->order_id;
		return JRoute::_( JURI::root() . $url );
	}

	protected function getproductattributes( $row ) {
		$recipient_email = $row->email;
		$recipient_name = $row->first_name.' '.$row->last_name;
		$message = '';
		$from_name = $row->first_name.' '.$row->last_name;

		$row->from_name_id = $row->from_name_id;				// fromname_id
		$row->recipient_email_id = $row->recipient_email_id;	// email_id
		$row->recipient_name_id = $row->recipient_name_id;	// name_id
		$row->recipient_mesg_id = $row->recipient_mesg_id;	// message_id
		
		if ( ! empty( $row->recipient_email_id ) ) {
			$tmp = AC()->db->get_value( 'SELECT ' . $row->recipient_email_id . ' FROM #__hikashop_order_product WHERE order_product_id=' . $row->order_item_id );
			if ( ! empty( $tmp ) && AC()->helper->is_email( $tmp ) ) {
				$recipient_email = $tmp;
			}
		}

		if ( ! empty( $row->recipient_name_id ) ) {
			$tmp = AC()->db->get_value( 'SELECT ' . $row->recipient_name_id . ' FROM #__hikashop_order_product WHERE order_product_id=' . $row->order_item_id );
			if ( ! empty( $tmp ) ) {
				$recipient_name = $tmp;
			}
		}

		if ( ! empty( $row->recipient_mesg_id ) ) {
			$message = AC()->db->get_value( 'SELECT ' . $row->recipient_mesg_id . ' FROM #__hikashop_order_product WHERE order_product_id=' . $row->order_item_id );
		}

		if ( ! empty( $row->from_name_id ) ) {
			$from_name = AC()->db->get_value( 'SELECT ' . $row->from_name_id . ' FROM #__hikashop_order_product WHERE order_product_id=' . $row->order_item_id );
		}
		
		return array(
			'from_name' => trim( $from_name ), 
			'email' => trim( $recipient_email ),
			'recipient_name' => trim( $recipient_name ),
			'message' => trim( $message ),
		); 
		return array('recipient_name'=>trim($recipient_name),'email'=>trim($recipient_email),'message'=>trim($message),'from_name'=>trim($from_name));
	}

	protected function get_product_price_in_default_currency( $amount, $row ) {
		if ( empty( $amount ) ) {
			return 0;
		}
		if ( empty( $row->currency_id ) ) {
			return 0;
		}

		if ( ! defined( 'DS' ) ) {
			define( 'DS', DIRECTORY_SEPARATOR );
		}
		if ( ! class_exists( 'hikashop' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_hikashop/helpers/helper.php';
		}
		$currencyClass = hikashop_get( 'class.currency' );
		$config = hikashop_config();

		$filters = array( 'a.product_id=' . $row->child_product_id,'a.product_type!="main"' ); // need to make sure main products are filtered out, otherwise plugins like donation plugin will not work
		hikashop_addACLFilters( $filters, 'product_access', 'a' );
		$element = AC()->db->get_object( '
			SELECT a.*, b.product_category_id, b.category_id, b.ordering
			  FROM ' . hikashop_table( 'product' ) . ' AS a
			  LEFT JOIN ' . hikashop_table( 'product_category' ) . ' AS b ON a.product_id = b.product_id
			 WHERE ' . implode( ' AND ', $filters ) . ' LIMIT 1
		' );
		if ( ! empty( $element ) ) {
			$zone_id = $config->get( 'tax_zone_type', 'shipping' ) == 'billing' ? hikashop_getZone( 'billing' ) : hikashop_getZone( 'shipping' );
			$discount_before_tax = (int) $config->get( 'discount_before_tax', 0 );
			$ids = array( $row->child_product_id );
			$currencyClass->getPrices( $element, $ids, $row->currency_id, $currencyClass->mainCurrency(), $zone_id, $discount_before_tax, $row->order_user_id );
			if ( ! empty( $element->prices ) ) {
				$tax_rate = ( $row->product_price - $row->product_price_notax ) / $row->product_price_notax;
				$product_price_notax = ! empty( $element->prices[0]->price_value_without_discount )
					? $element->prices[0]->price_value_without_discount
					: $element->prices[0]->price_value
				;
				$product_price = $product_price_notax * ( 1 + $tax_rate );
				$amount = round( ${$row->price_calc_type}, 2 );
			}
		}
		return AC()->storecurrency->convert_to_default( $amount, $row->currency_id );
	}

}
