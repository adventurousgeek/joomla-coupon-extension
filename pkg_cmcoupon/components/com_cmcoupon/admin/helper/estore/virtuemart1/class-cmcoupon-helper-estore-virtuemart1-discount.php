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

AC()->helper->add_class( 'CmCoupon_Library_Discount' );

class CmCoupon_Helper_Estore_Virtuemart1_Discount extends CmCoupon_Library_Discount {

	var $params = null;

	var $cart = null;
	var $o_cart = null;
	var $coupon_code = null;

	var $product_total = 0;
	var $product_qty = 0;
	var $default_err_msg = '';

	public static function instance( $class = null ) {
		return parent::instance( get_class() );
	}

	public function __construct() {
		parent::__construct();

		$this->estore = 'virtuemart1';

	}

	public function init() {
		require_once JPATH_ROOT . '/components/com_virtuemart/virtuemart_parser.php';
		if ( ! class_exists( 'ps_cart' ) ) {
			require CLASSPATH . 'ps_cart.php';
		}
		if ( ! class_exists( 'ps_product' ) ) {
			require CLASSPATH . 'ps_product.php';
		}
		if ( ! class_exists( 'ps_checkout' ) ) {
			require CLASSPATH . 'ps_checkout.php';
		}

		global $VM_LANG;
		$this->default_err_msg = $VM_LANG->_('PHPSHOP_COUPON_CODE_INVALID');

		$this->o_cart = new ps_cart();
		$this->refresh_cart = false;
		$this->coupon_code = trim( vmGet( $_REQUEST, 'coupon_code' ) );
		$this->payment_method_id = (int) vmGet( $_REQUEST, 'payment_method_id' );
	}

	public function cart_coupon_validate( $d ) {
		if ( ! AC()->is_request( 'frontend' ) ) {
			return;
		}

		$this->init();
		return $this->process_coupon_helper();
	}

	public function cart_coupon_validate_auto( $d ) {
		$this->init();
		if ( empty( $_SESSION['cart']['idx'] ) ) {
			return;
		}

		$codes = $this->process_autocoupon_helper();
	}

	public function cart_coupon_validate_balance() {
		$this->init();
		// process discount

		$this->coupon_code = $this->coupon_code_balance;
		$this->process_coupon_helper();
	}

	public function cart_calculate_totals( &$cart ) {
	}

	public function cart_coupon_delete( $coupon_id ) {
		$this->init();
		$this->delete_coupon_from_session( $coupon_id );
	}

	public function cart_coupon_displayname( $coupon_code ) {
	}

	public function order_new( $d ) {
		$this->init();

		$order_id = 0;
		if ( ! empty( $d['order_number'] ) ) {
			AC()->db->query( 'INSERT INTO #__cmcoupon_vm1ids (type,value) VALUES ("order_number","' . AC()->db->escape( $d['order_number'] ) . '")' );
			$order_id = AC()->db->get_insertid();
		}
		$coupon_code = $_SESSION['coupon_code'];
		$this->save_coupon_history( (int) $order_id );
		$_SESSION['coupon_code'] = $coupon_code;
		return true;
	}

	public function order_status_changed( $d, $order_status ) {
		$order_id = 0;
		$status_to = '';
		$rtn = AC()->db->get_object( '
			SELECT o.order_status,vm1.id AS order_id 
			  FROM #__vm_orders o 
			  JOIN #__cmcoupon_vm1ids vm1 ON vm1.value=o.order_number
			 WHERE o.order_id=' . @ (int) $d['order_id'] . '
		' );
		if ( ! empty( $rtn->order_id ) ) {
			$order_id = $rtn->order_id;
			$status_to = trim( $rtn->order_status );
		}

		$this->cleanup_ordercancel_helper( $order_id, $status_to );
		return true;
	}

	protected function initialize_coupon() {
		parent::initialize_coupon();

		// remove from vm session so coupon code is not called constantly
		unset(	
			$_SESSION['coupon_id'],
			$_SESSION['coupon_discount'],
			$_SESSION['coupon_redeemed'],
			$_SESSION['coupon_code'],
			$_SESSION['coupon_type']
		);
	}

	protected function finalize_coupon( $master_output ) {
		$session_array = $this->save_discount_to_session( $master_output );
		if ( empty( $session_array ) ) {
			return false;
		}

		$coupon_discount_total = 0;
		foreach ( $session_array->processed_coupons as $coupon ) {
			$coupon_discount_total += $coupon['is_discount_before_tax'] == 1 ? $coupon['product_discount_notax'] : $coupon['product_discount'];
		}
		$coupon_discount_total = round( $coupon_discount_total, 2 );
		
		$_SESSION['coupon_redeemed'] = true;
		$_SESSION['coupon_id'] = $session_array->coupon_id;
		$_SESSION['coupon_code'] = $session_array->coupon_code;
		$_SESSION['coupon_discount'] = $coupon_discount_total ;
		$_SESSION['coupon_type'] = 'gift'; // always call cleanup function

		// shipping discount
		if ( ! empty( $session_array->shipping_discount ) && ! empty( $_REQUEST['shipping_rate_id'] ) ) {
			$original_shipping_object = $this->get_storeshipping();

			if ( ! empty( $original_shipping_object->total ) ) {
				$coupon_value = $session_array->shipping_discount;
				if ( $original_shipping_object->total < $coupon_value ) {
					$coupon_value = (float) $original_shipping_object->total;
				}

				// insert new shipping total back into cart
				$rate_array = explode( '|', urldecode( vmGet( $_REQUEST, 'shipping_rate_id' ) ) );
				$rate_array[3] = $original_shipping_object->total - $coupon_value;
				$_REQUEST['shipping_rate_id'] = urlencode( implode( '|', $rate_array ) ); // reconstruct shipping id
				$_SESSION[ $_REQUEST['shipping_rate_id'] ] = 1; // needed to register shipping as valid

				// save original shipping object for later use so we do not over discount shipping
				$this->session_set( 'shipping_obj_' . $_REQUEST['shipping_rate_id'], $original_shipping_object );
			}
		}

		if ( $this->refresh_cart ) {
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart&page=shop.cart&Itemid=' . (int) AC()->helper->get_request( 'Itemid' ) );
		}
		$this->finalize_coupon_store( $session_array );
		return true;
	}

	protected function finalize_coupon_store( $coupon_session ) {
	}

	protected function getuniquecartstring( $coupon_code = null ) {
		return mt_rand();
	}

	protected function getuniquecartstringauto() {
		global $auth;
		$ps_checkout = new ps_checkout();

		$d = $_REQUEST;
		$totals = $ps_checkout->calc_order_totals( $d );

		$string = ( $totals['order_subtotal'] + $totals['order_tax'] ) . '|' . @ $auth['user_id'];
		$session_cart = $_SESSION['cart'];
		for ( $i = 0; $i < $session_cart['idx']; $i++ ) {
			$string .= '|' . $i . '|' . $session_cart[ $i ]['product_id'] . '|' . $session_cart[ $i ]['description'] . '|' . $session_cart[ $i ]['quantity'];
		}
		$address = $this->get_customeraddress();
		return $string . '|ship|' . @ $_REQUEST['shipping_rate_id'] . '|' . $address->country_id . '|' . $address->state_id . '|' . $this->payment_method_id;
	}

	protected function get_storeshoppergroupids( $user_id ) {
		return AC()->store->get_group_ids( $user_id );
	}

	protected function get_storeproduct( $ids ) {
		if ( $this->params->get( 'disable_coupon_product_children', 0 ) == 1 ) {
			return array();
		}
		return AC()->db->get_objectlist( 'SELECT product_id AS asset_id, product_id FROM #__vm_product WHERE product_parent_id IN (' . $ids . ')' );
	}

	protected function get_storecategory( $ids ) {
		$tmp1 = AC()->db->get_objectlist( 'SELECT category_id AS asset_id,product_id FROM #__vm_product_category_xref WHERE product_id IN (' . $ids . ')' );
		$tmp2 = array();
		if ( $this->params->get( 'disable_coupon_product_children', 0 ) !== 1 ) {
			// get category list of parent products
			$tmp2 = AC()->db->get_objectlist( '
				SELECT c.category_id AS asset_id,p.product_id
				  FROM #__vm_product p 
				  JOIN #__vm_product_category_xref c ON c.product_id=p.product_parent_id
				 WHERE p.product_id IN (' . $ids . ')
			' );
		}

		$categorys = array_merge( $tmp1, $tmp2 );
		if ( empty( $categorys ) ) {
			return array();
		}

		$category_index = array();
		foreach ( $categorys as $cat ) {
			if ( ! isset( $category_index[ $cat->asset_id ] ) ) {
				$category_index[ $cat->asset_id ] = array();
			}
			$category_index[ $cat->asset_id ][] = $cat->product_id;
		}

		// get parent categories
		for ( $i = 0; $i < 5; $i ++ ) {
			if ( empty( $category_index ) ) {
				break;
			}
			$sql = 'SELECT category_parent_id, category_child_id FROM #__vm_category_xref WHERE category_child_id IN (' . implode( ',', array_keys( $category_index ) ) . ')';
			$items = AC()->db->get_objectlist( $sql );
			$tmp_category_index = array();
			foreach ( $items as $item ) {
				if ( empty( $item->category_parent_id ) ) {
					continue;
				}
				foreach ( $category_index[ $item->category_child_id ] as $product_id ) {
					$categorys[] = (object) array(
						'asset_id' => $item->category_parent_id,
						'product_id' => $product_id,
					);
				}
				$tmp_category_index[ $item->category_parent_id ] = $category_index[ $item->category_child_id ];
			}
			$category_index = $tmp_category_index;
		}
		return $categorys;
	}

	protected function get_storemanufacturer( $ids ) {
		return AC()->db->get_objectlist( 'SELECT manufacturer_id AS asset_id,product_id FROM #__vm_product_mf_xref WHERE product_id IN (' . $ids . ')' );
	}

	protected function get_storevendor( $ids ) {
		return AC()->db->get_objectlist( 'SELECT vendor_id AS asset_id,product_id FROM #__vm_product WHERE product_id IN (' . $ids . ')' );
	}

	protected function get_storecustom( $ids ) {
		return array();
	}

	protected function get_storeshipping() {

		if ( empty( $_REQUEST['shipping_rate_id'] ) ) {
			return (object) array(
				'shipping_id' => 0,
				'total_notax' => 0,
				'total' => 0,
				'shippings' => array(),
			);
		}

		$stored_obj = $this->session_get( urlencode( 'shipping_obj_' . $_REQUEST['shipping_rate_id'] ), '', 'cmcoupon' );
		if ( ! empty( $stored_obj ) ){
			return $stored_obj;
		}

		$ps_checkout = new ps_checkout();

		$d = $_REQUEST;
		$totals = $ps_checkout->calc_order_totals( $d );
		$rate_array = explode( '|', urldecode( vmGet( $_REQUEST, "shipping_rate_id" ) ) );

		$id = 0;
		$filename = basename( $rate_array[0] );
		if ( file_exists( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helpers/estore/virtuemart1/shipping/' . $filename . '.php' ) ) {
			require_once JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helpers/estore/virtuemart1/shipping/' . $filename . '.php';
			if ( class_exists( 'cm_' . $filename ) ) {
				$shipping_class = 'cm_' . $filename;
				$shipping_class = new $shipping_class();
				$shipping_rate_id = $shipping_class->get_rate_id( $rate_array ) ;

				$value = $filename . '-' . $shipping_rate_id;
				$id = AC()->db->get_value( 'SELECT id FROM #__cmcoupon_vm1ids WHERE type="shipping_rate_id" AND value="' . $value . '"' );
			}
		}
		$test = round( $totals['order_shipping'], 10 );
		$shippings = array();
		$shippings[] = (object) array(
			'shipping_id' => ! empty( $id ) ? $id : -1,
			'total_notax' => (float) $totals['order_shipping'],
			'total' => (float) $rate_array[3],
			'tax_rate' => empty( $test ) ? 0 : ( (float) $rate_array[3] - (float) $totals['order_shipping'] ) / $totals['order_shipping'],
			'totaldiscount' => 0,
			'totaldiscount_notax' => 0,
			'totaldiscount_tax' => 0,
			'coupons' => array(),
		);

		$shipping = (object) array(
			'shipping_id' => $shippings[0]->shipping_id,
			'total_notax' => $shippings[0]->total_notax,
			'total' => $shippings[0]->total,
			'shippings' => $shippings,
		);

		return $shipping;
	}

	protected function get_storepayment() {
		$payment = (object) array(
			'payment_id' => 0,
			'total_notax' => 0,
			'total' => 0,
		);

		if ( ! empty( $this->payment_method_id ) ) {
			$payment->payment_id = $this->payment_method_id;
		}

		return $payment;
	}

	protected function get_customeraddress() {
		$address = (object) array(
			'email' => '',
			'state_id' => 0,
			'state_name' => '',
			'country_id' => 0,
			'country_name' => '',
		);

		$auth = $_SESSION['auth'];

		if ( ! empty( $auth['user_id'] ) ) {
			$address->email = AC()->db->get_value( 'SELECT user_email FROM #__vm_user_info WHERE user_id=' . (int) $auth['user_id'] . ' AND address_type="BT"' );
		}
		
		$item = AC()->db->get_object( '
			SELECT i.*,c.country_id,c.country_name,s.state_id,s.state_name
			  FROM #__vm_user_info i
			  JOIN #__vm_country c ON (i.country=c.country_3_code OR i.country=c.country_2_code)
			  LEFT JOIN #__vm_state s ON (i.state=s.state_2_code AND s.country_id=c.country_id)
			 WHERE user_id=' . (int) $auth['user_id'] . '
			   AND address_type="BT"
		' );
		if ( empty( $item ) ) {
			return $address;
		}

		$address->country_id = $item->country_id;
		$address->country_name = $item->country_name;
		$address->state_id = $item->state_id;
		$address->state_name = $item->state_name;

		return $address;
	}

	protected function get_submittedcoupon() {
		$vmcode = $this->coupon_code;

		if ( $vmcode == $this->get_frontend_lang( 'auto' ) ) {
			return '';
		}

		$cmsess = $this->session_get( 'coupon' );
		if ( ! empty( $cmsess ) && $cmsess->coupon_code == $vmcode ) {
			return '';
		}
		return $vmcode; 
	}

	protected function set_submittedcoupon( $coupon_code ) {
		$_REQUEST['coupon_code'] = $coupon_code;
	}

	protected function get_orderemail( $order_id ) {
		global $auth;

		$email = '';
		if ( ! empty( $auth['user_id'] ) ) {
			$email = AC()->db->get_value( 'SELECT user_email FROM #__vm_user_info WHERE user_id=' . (int) $auth['user_id'] . ' AND address_type="BT"' );
		}
		return $email;
	}

	protected function is_coupon_in_store( $coupon_code ) {
		if ( empty( $coupon_code ) ) {
			return false;
		}
		$coupon_id = (int) AC()->db->get_value( 'SELECT coupon_id FROM #__vm_coupons WHERE coupon_code="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );
		return $coupon_id > 0 ? true : false;
	}

	protected function define_cart_items( $is_refresh = false ) {
		// retreive cart items
		$this->cart = new stdClass();
		$this->cart->items = array();
		$this->cart->items_def = array();
		$this->product_total = 0;
		$this->product_qty  = 0;

		$ps_product = new ps_product;
		$session_cart = $_SESSION['cart'];

		JPluginHelper::importPlugin( 'cmcoupon' );
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger( 'cmDefineCartItemsBefore', array( & $session_cart, $this ) );

		for ( $i = 0; $i < $session_cart['idx']; $i++ ) {
			$product_id = $session_cart[ $i ]['product_id'];
			$qty = (int) $session_cart[ $i ]['quantity'];

			if ( empty( $product_id ) ) {
				continue;
			}
			if ( empty( $qty ) ) {
				continue;
			}

			$discount = $ps_product->get_discount( $product_id );
			$price = $ps_product->get_adjusted_attribute_price( $product_id, $session_cart[$i]['description'] );
						
			// retrieve and add tax to product price
			$my_taxrate =  $this->get_product_taxrate( $product_id );
			$product_price = round( $price['product_price'] * ( 1 + $my_taxrate ), 2 );

			$this->cart->items_def[ $product_id ]['product'] = $product_id;
			$this->cart->items [] = array(
				'product_id' => $product_id,
				'cartpricekey' => $i,
				'description' => $session_cart[ $i ]['description'],
				'discount' => empty( $discount ) ? 0 : $discount['amount'],
				'product_price' => $product_price,
				'product_price_notax' => $price['product_price'],
				'product_price_tax' => $product_price - $price['product_price'],
				'tax_rate' => $my_taxrate,
				'qty' => $qty,
				'is_special' => $ps_product->get_field( $product_id, 'product_special' ) == 'Y' ? 1 : 0,
				'is_discounted' => ! empty( $discount['amount'] ) ? 1 : 0,
			);
			$this->product_total += $qty * $product_price;
			$this->product_qty += $qty;
		}

		JPluginHelper::importPlugin( 'cmcoupon' );
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger( 'cmDefineCartItemsAfter', array( & $this->cart->items_def, & $this->cart->items, $session_cart, $this ) );

		parent::define_cart_items();
	}

	protected function get_frontend_lang( $key ) {
		AC()->helper->loadLanguageSite();
		switch ( $key ) {
			case 'auto':
				return JText::_( 'COM_CMCOUPON_CP_DISCOUNT_AUTO' );
			case 'store_credit':
				return JText::_( 'COM_CMCOUPON_GC_STORECREDIT' );
		}
		return '';
	}

	protected function buyxy_getproduct( $coupon_row, $assetlist, $exclude_products = array() ) {
		$db = AC()->db;

		$the_product = 0;

		if ( empty( $exclude_products ) ) {
			$exclude_products = array();
		}
		$where_product = empty( $exclude_products ) ? '' : ' AND p.product_id NOT IN (' . implode( ',', $exclude_products ) . ') ';
		$where_product_parent = empty( $exclude_products ) ? '' : ' AND p.product_parent_id NOT IN (' . implode( ',', $exclude_products ) . ') ';

		$parents = AC()->db->get_column( 'SELECT DISTINCT product_parent_id FROM #__vm_product WHERE product_parent_id IS NOT NULL AND product_parent_id!=0' );
		if ( empty( $parents ) ) {
			$parents = array( 0 );
		}

		$ps_product= new ps_product;

		foreach ( $assetlist as $asset_type => $assetelement ) {
			if ( empty( $assetelement->rows ) ) {
				continue;
			}
			$mode = empty( $assetelement->mode ) ? 'include' : $assetelement->mode;
			$ids = implode( ',', array_keys( $assetelement->rows ) );

			$max_tries = 200;
			$used_ids = array( 0 );

			do {
				if ( 'include' == $mode ) {
					if ( 'product' == $asset_type ) {
						$sql = 'SELECT product_id
								  FROM #__vm_product p
								 WHERE product_publish="Y"
								   AND product_id IN (' . $ids . ')
								   AND product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="") 
								   AND p.product_id NOT IN (' . implode( ',', $parents ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					}
					elseif ( 'category' == $asset_type ) {
						$sql = 'SELECT c.product_id
								  FROM #__vm_product_category_xref c
								  JOIN #__vm_product p ON p.product_id=c.product_id
								 WHERE p.product_publish="Y"
								   AND c.category_id IN (' . $ids . ')
								   AND c.product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="")
								   AND p.product_id NOT IN (' . implode( ',', $parents ) . ')
								   ' . $where_product . '
													UNION
								SELECT p.product_id
								  FROM #__vm_product_category_xref c
								  JOIN #__vm_product p ON p.product_parent_id=c.product_id
								 WHERE p.product_publish="Y"
								   AND c.category_id IN (' . $ids . ')
								   AND p.product_parent_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="")
								   AND p.product_parent_id IN (' . implode( ',', $parents ) . ')
								   ' . $where_product_parent . '
								   LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					}
					elseif ( 'manufacturer' == $asset_type ) {
						$sql = 'SELECT m.product_id
								  FROM #__vm_product_mf_xref m
								  JOIN #__vm_product p ON p.product_id=m.product_id
								 WHERE p.product_publish="Y"
								   AND m.manufacturer_id IN (' . $ids . ')
								   AND m.product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="") 
								   AND p.product_id NOT IN (' . implode( ',', $parents ) . ')
								   ' . $where_product . '
													UNION
								SELECT p.product_id
								  FROM #__vm_product_mf_xref m
								  JOIN #__vm_product p ON p.product_parent_id=m.product_id
								 WHERE p.product_publish="Y"
								   AND m.manufacturer_id IN (' . $ids . ')
								   AND p.product_parent_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="") 
								   AND p.product_parent_id IN (' . implode( ',', $parents ) . ')
								   ' . $where_product_parent . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					}
					elseif ( 'vendor' == $asset_type ) {
						$sql = 'SELECT product_id
								  FROM #__vm_product p
								 WHERE product_publish="Y"
								   AND vendor_id IN (' . $ids . ')
								   AND product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="") 
								   AND p.product_id NOT IN (' . implode( ',', $parents ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					}
				}
				elseif ( 'exclude' == $mode ) {
					if ( 'product' == $asset_type ) {
						$sql = 'SELECT product_id
								  FROM #__vm_product p
								 WHERE product_publish="Y"
								   AND product_id NOT IN (' . $ids . ')
								   AND product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="") 
								   AND p.product_id NOT IN (' . implode( ',', $parents ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					}
					elseif ( 'category' == $asset_type ) {
						$sql = 'SELECT c.product_id
								  FROM #__vm_product_category_xref c
								  JOIN #__vm_product p ON p.product_id=c.product_id
								 WHERE p.product_publish="Y"
								   AND c.category_id NOT IN (' . $ids . ')
								   AND c.product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="")
								   AND p.product_id NOT IN (' . implode( ',', $parents ) . ')
								   ' . $where_product . '
													UNION
								SELECT p.product_id
								  FROM #__vm_product_category_xref c
								  JOIN #__vm_product p ON p.product_parent_id=c.product_id
								 WHERE p.product_publish="Y"
								   AND c.category_id NOT IN (' . $ids . ')
								   AND p.product_parent_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="")
								   AND p.product_parent_id IN (' . implode( ',', $parents ) . ')
								   ' . $where_product_parent . '
								   LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					}
					elseif ( 'manufacturer' == $asset_type ) {
						$sql = 'SELECT m.product_id
								  FROM #__vm_product_mf_xref m
								  JOIN #__vm_product p ON p.product_id=m.product_id
								 WHERE p.product_publish="Y"
								   AND m.manufacturer_id NOT IN (' . $ids . ')
								   AND m.product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="") 
								   AND p.product_id NOT IN (' . implode( ',', $parents ) . ')
								   ' . $where_product . '
													UNION
								SELECT p.product_id
								  FROM #__vm_product_mf_xref m
								  JOIN #__vm_product p ON p.product_parent_id=m.product_id
								 WHERE p.product_publish="Y"
								   AND m.manufacturer_id NOT IN (' . $ids . ')
								   AND p.product_parent_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="") 
								   AND p.product_parent_id IN (' . implode( ',', $parents ) . ')
								   ' . $where_product_parent . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					}
					elseif ( 'vendor' == $asset_type ) {
						$sql = 'SELECT product_id
								  FROM #__vm_product p
								 WHERE product_publish="Y"
								   AND vendor_id IN (' . $ids . ')
								   AND product_id NOT IN (' . implode( ',', $used_ids ) . ')
								   AND (p.attribute IS NULL OR p.attribute="")
								   AND (p.custom_attribute IS NULL OR p.custom_attribute="") 
								   AND p.product_id NOT IN (' . implode( ',', $parents ) . ')
								   ' . $where_product . '
								 LIMIT 1
						';
						$the_product = $db->get_value( $sql );
					}
				}

				if ( empty( $the_product ) ) {
					break;
				}

				if ( ! empty( $the_product ) && ! empty( $coupon_row->params->exclude_special ) ) {
					if ( $ps_product->get_field( $the_product, 'product_special' ) == 'Y' ) {
						$used_ids[] = $the_product;
						$the_product = 0;
					}
				}
				if ( ! empty( $the_product ) && ! empty( $coupon_row->params->exclude_discounted ) ) {
					$discount = $ps_product->get_discount( $the_product );
					if ( ! empty( $discount['amount'] ) ) {
						$used_ids[] = $the_product;
						$the_product = 0;
					}
				}

				$max_tries --;
				if ( $max_tries <= 0 ) {
					break;
				}
			} while ( 0 == $the_product );

			if ( ! empty( $the_product ) ) {
				break;
			}
		}
		return $the_product;
	}

	protected function add_to_cart( $product_id, $qty ) {
		$qty = (int) $qty;
		$product_id = (int) $product_id;
		if ( empty( $qty ) || empty( $product_id ) ) {
			return;
		}

		$is_update = false;
		$description = '';
		foreach ( $this->cart->items as $k => $r ) {			
			if ( $r['product_id'] != $product_id ) {
				continue;
			}
			$is_update = true;
			$qty += $r['qty'];
			$description = $r['description'];
			break;
		}

		if ( $is_update ) {
			global $func;
			$func = 'cartupdate';
			$d = array(
				'quantity' => $qty,
				'product_id' => $product_id,
				'prod_id' => $product_id,
				'description' => $description, 
			);
			$ok = $this->o_cart->update( $d );
		}
		else {
			$product_row = AC()->db->get_object( 'SELECT product_id,product_parent_id, product_name FROM #__vm_product WHERE product_id = ' . (int) $product_id );
			$product_id = (int) $product_row->product_id;
			$product_parent_id = (int) $product_row->product_parent_id;
			$product_name = $product_row->product_name;
			if ( empty( $product_id ) ) {
				return;
			}
			$d = array (
				'product_id' => empty( $product_parent_id ) ? $product_id : $product_parent_id,
				'prod_id' => array( $product_id ),
				'quantity' => array( $qty ),
				'category_id' => 0,
				'manufacturer_id' => 0,
				'Itemid' => 1,
			);
			$ok = $this->o_cart->add( $d );
		}

		if( $ok ) {
			$this->refresh_cart = true;
			return true;
		}
		return;
	}
	
	private function get_product_taxrate( $product_id ) {
		//if( $this->is_discount_before_tax && PAYMENT_DISCOUNT_BEFORE=='1') return 0;

		$ps_product = new ps_product;
		$my_taxrate = $ps_product->get_product_taxrate( $product_id );

		$auth = $_SESSION['auth'];
		// If discounts are applied after tax, but prices are shown without tax,
		// AND tax is EU mode and shopper is not in the EU,
		// then ps_product::get_product_taxrate() returns 0, so $my_taxrate = 0.
		// But, the discount still needs to be reduced by the shopper's tax rate, so we obtain it here:
		if ( $auth["show_price_including_tax"] != 1 && ! ps_checkout::tax_based_on_vendor_address() ) {
			if ( $auth["user_id"] > 0 ) {

				$tmp = AC()->db->get_object( 'SELECT state, country FROM #__vm_user_info WHERE user_id=' . (int) $auth['user_id'] );
				@$state = $tmp->state;
				@$country = $tmp->country;

				$sql = 'SELECT tax_rate FROM #__vm_tax_rate WHERE tax_country="' . AC()->db->escape( $country ) . '"';
				if ( ! empty( $state ) ) {
					$sql .= 'AND (tax_state="' . AC()->db->escape( $state ) . '" OR tax_state="' . AC()->db->escape( $state ) . '" OR tax_state="-")';
				}
				$my_taxrate = (float)AC()->db->get_value( $sql );
			}
			else {
				$my_taxrate = 0;
			}
		}
		return $my_taxrate;
	}

}

