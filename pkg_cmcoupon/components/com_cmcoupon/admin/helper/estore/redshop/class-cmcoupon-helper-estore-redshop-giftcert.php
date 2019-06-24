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

class CmCoupon_Helper_Estore_Redshop_Giftcert  extends CmCoupon_Library_Giftcert {

	var $estore = 'redshop';
	var $order = null;

	public function __construct() {
		parent::__construct();

		// Load redSHOP Library
		JLoader::import('redshop.library');

		if ( ! class_exists( 'RedshopModelConfiguration' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_redshop/models/configuration.php';
		}
		$configClass = new RedshopModelConfiguration();
		$this->version = $configClass->getCurrentVersion();
		
		if ( version_compare( $this->version, '2.0.0', '>=' ) ) {
			JLoader::import('redshop.library');
		}
		else {
			require_once JPATH_ADMINISTRATOR . '/components/com_redshop/helpers/redshop.cfg.php';
		}
	}

	public function order_status_changed( $order_id ) {
		$this->order = $this->get_storeorder( $order_id );
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
					,u.firstname AS first_name
					,u.lastname AS last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,i.product_id
					,i.product_attribute AS product_attribute
					,i.order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,i.order_item_currency AS currency_code
					,i.product_item_price_excl_vat AS product_price_notax
					,i.product_item_price AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix
				  FROM #__redshop_order_item i 
				  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=i.product_id
				  JOIN #__redshop_order_users_info u ON u.order_id=i.order_id AND u.address_type="BT"
				  LEFT JOIN #__cmcoupon_voucher_customer g ON g.order_id=i.order_id AND g.estore=ap.estore
				 WHERE ap.estore="'. $this->estore . '"
				   AND i.order_id=' . $this->order->order_id . '
				   AND g.order_id IS NULL
				   AND ap.published=1
				   AND i.is_giftcard=0 
				 GROUP BY i.order_item_id

							UNION

				SELECT
					i.order_item_id
					,i.order_id
					,i.product_quantity AS product_quantity
					,u.user_id AS user_id
					,u.user_email AS email
					,u.firstname AS first_name
					,u.lastname AS last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,p.product_parent_id AS product_id
					,i.product_attribute AS product_attribute
					,i.order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,i.order_item_currency AS currency_code
					,i.product_item_price_excl_vat AS product_price_notax
					,i.product_item_price AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix
				  FROM #__redshop_order_item i 
				  JOIN #__redshop_product p ON p.product_id=i.product_id
				  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=p.product_parent_id 
				  JOIN #__redshop_order_users_info u ON u.order_id=i.order_id AND u.address_type="BT"
				  LEFT JOIN #__cmcoupon_voucher_customer g ON g.order_id=i.order_id AND g.estore="redshop"
				 WHERE ap.estore="'. $this->estore . '"
				   AND i.order_id=' . $this->order->order_id . '
				   AND g.order_id IS NULL
				   AND ap.published=1
				   AND i.is_giftcard=0 
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
					,u.firstname AS first_name
					,u.lastname AS last_name
					,ap.expiration_number
					,ap.expiration_type
					,ap.coupon_template_id
					,ap.profile_id
					,i.product_id
					,i.product_attribute AS product_attribute
					,i.order_item_name
					,ap.vendor_name
					,ap.vendor_email
					,i.order_item_currency AS currency_code
					,i.product_item_price_excl_vat AS product_price_notax
					,i.product_item_price AS product_price
					,0 as vendor_id
					,ap.price_calc_type
					,ap.coupon_code_prefix
					,ap.coupon_code_suffix

			  FROM #__cmcoupon_voucher_customer_code gcc
			  JOIN #__cmcoupon_voucher_customer gc ON gc.id=gcc.voucher_customer_id
			  JOIN #__cmcoupon_giftcert_product ap ON ap.product_id=gcc.product_id AND ap.estore=gc.estore
			  JOIN #__cmcoupon c ON c.coupon_code=gcc.code
			  LEFT JOIN #__redshop_order_item i ON i.order_id=gc.order_id AND i.order_item_id=gcc.order_item_id
			  LEFT JOIN #__redshop_order_users_info u ON u.order_id=i.order_id AND u.address_type="BT"
			 WHERE gc.id=' . (int) $voucher_customer_id . '
			   AND gc.estore="' . $this->estore . '"
			   AND ap.published=1
		');
	}

	protected function formatcurrency( $val ) {
		return AC()->storecurrency->format( $val );
	}

	protected function get_storeorder( $in ) {
		if(!is_object($in)) {
			$in = AC()->db->get_object( 'SELECT * FROM #__redshop_orders WHERE order_id=' . (int) $in );
		}

		$order = new stdClass();
		$order->order_id = $in->order_id;
		$order->order_number = $in->order_number;
		$order->order_status = $in->order_status;
		$order->order_total = $in->order_total;
		$order->created_on = $in->cdate;
		$order->currency = '';
		
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
		$url = 'index.php?option=com_redshop&view=order_detail&layout=receipt&oid=' . $this->order->order_id;
		return JRoute::_( JURI::root() . $url );
	}

	protected function getproductattributes( $row ) {
		$recipient_email = $row->email;
		$recipient_name = $row->first_name . ' ' . $row->last_name;
		$message = '';
		$from_name = $row->first_name . ' ' . $row->last_name;

		$name_id = (int) $this->params->get( 'redshop_giftcert_field_recipient_name', 0 );
		$email_id = (int) $this->params->get( 'redshop_giftcert_field_recipient_email', 0 );
		$message_id = (int) $this->params->get( 'redshop_giftcert_field_recipient_message', 0 );
		$fromname_id = (int) $this->params->get( 'redshop_giftcert_field_from_name', 0 );

		$custom_attr = AC()->db->get_objectlist( '
			SELECT fd.*,f.field_title,f.field_type,f.field_name 
				  FROM #__redshop_fields_data AS fd 
				  LEFT JOIN #__redshop_fields AS f ON f.field_id=fd.fieldid
				 WHERE fd.itemid=' . $row->order_item_id . '
				   AND fd.section=12
				   AND fd.fieldid IN (' . $name_id . ',' . $email_id . ',' . $message_id . ',' . $fromname_id . ')
		', 'fieldid' );
	
		$attrlist = array();
		if ( ! empty( $custom_attr ) ) {
			if ( ! empty( $custom_attr[ $email_id ] ) ) {
				$tmp = trim( $custom_attr[ $email_id ]->data_txt );
				if ( ! empty( $tmp ) && AC()->helper->is_email( $tmp ) ) {
					$recipient_email = $tmp;
				}
			}
			if ( ! empty( $custom_attr[ $name_id ] ) ) {
				$tmp = trim( $custom_attr[ $name_id ]->data_txt );
				if ( ! empty( $tmp ) ) {
					$recipient_name = $tmp;
				}
			}
			if ( ! empty( $custom_attr[ $message_id ] ) ) {
				$tmp = trim( $custom_attr[ $message_id ]->data_txt );
			}
			if ( ! empty( $custom_attr[ $fromname_id ] ) ) {
				$tmp = trim( $custom_attr[ $fromname_id ]->data_txt );
				if ( ! empty( $tmp ) ) {
					$from_name = $tmp;
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
		if ( empty( $row->price_calc_type ) ) {
			return;
		}
		if ( empty( $row->{$row->price_calc_type} ) ) {
			return;
		}
		if ( ! class_exists( 'producthelper' ) ) {
			if ( version_compare( $this->version, '2.0.0', '>=' ) ) {
				require JPATH_ROOT . '/components/com_redshop/helpers/producthelper.php';
			}
			else {
				JLoader::import( 'product', JPATH_ADMINISTRATOR . '/components/com_redshop/helpers' );
			}
		}
		$productHelper     = new producthelper;

		$product_price_notax = $productHelper->getProductPrice( $row->product_id, 0, $row->user_id );
		$product_price = $productHelper->getProductPrice( $row->product_id, 1, $row->user_id );

		$attributes = AC()->db->get_objectlist( 'SELECT section_price,section_vat,section_oprand FROM #__redshop_order_attribute_item WHERE order_item_id=' . (int) $row->order_item_id . ' ORDER BY section_oprand DESC' );
		foreach ( $attributes as $item ) {
			if ( $item->section_oprand == '=' ) {
				$product_price_notax = $item->section_price;
				$product_price = $item->section_price + $item->section_vat;
			}
			elseif ( $item->section_oprand == '-' ) {
				$product_price_notax -= $item->section_price;
				$product_price -= $item->section_price + $item->section_vat;
			}
			elseif ( $item->section_oprand == '+' ) {
				$product_price_notax += $item->section_price;
				$product_price += $item->section_price + $item->section_vat;
			}
		}

		$amount = ${$row->price_calc_type};

		if ( empty( $amount ) ) {
			return 0;
		}
		if ( empty( $row->currency_code ) ) {
			return 0;
		}

		return AC()->storecurrency->convert_to_default( $amount, $row->currency_code );
	}

}
