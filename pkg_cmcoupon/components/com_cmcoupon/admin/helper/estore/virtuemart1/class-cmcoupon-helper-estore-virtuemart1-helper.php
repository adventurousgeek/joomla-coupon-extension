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

class Cmcoupon_Helper_Estore_Virtuemart1_Helper {

	var $estore = 'virtuemart1';

	public function is_installed() {
		if ( file_exists(JPATH_ADMINISTRATOR . '/components/com_virtuemart/version.php' ) ) {
			require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/version.php';
			if ( isset( $VMVERSION->RELEASE ) ) {
				if ( substr( $VMVERSION->RELEASE, 0, 4 ) == '1.1.' ) {
					return true;
				}
				elseif ( substr( $VMVERSION->RELEASE, 0, 4 ) == '1.2.' ) {
					return true;
				}
			}
		}
		return false;
	}

	public function get_coupon_asset( $coupon ) {

		$coupon_ids = 0;
		$param_list = array();
		if ( is_object( $coupon ) && ! empty( $coupon->id ) ) {
			$coupon_ids = (int) $coupon->id;
			$param_list[ $coupon->id ] = ! empty( $coupon->params->asset ) ? json_decode( json_encode( $coupon->params->asset ), true ) : array();
		} elseif ( is_array( $coupon ) ) {
			$coupon_ids = AC()->helper->scrubids( $coupon );
			$tmp = AC()->db->get_objectlist( 'SELECT id,params FROM #__cmcoupon WHERE id IN (' . $coupon_ids . ')' );
			foreach ( $tmp as $row ) {
				$param_list[ $row->id ] = array();
				if ( empty( $row->params ) ) {
					continue;
				}
				$param = json_decode( $row->params, true );
				if ( empty( $param['asset'] ) ) {
					continue;
				}
				$param_list[ $row->id ] = $param['asset'];
			}
		}

		$sql = 'SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.product_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__vm_product b ON b.product_id=a.asset_id
				 WHERE a.asset_type="product" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.category_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__vm_category b ON b.category_id=a.asset_id
				 WHERE a.asset_type="category" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.mf_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__vm_manufacturer b ON b.manufacturer_id=a.asset_id
				 WHERE a.asset_type="manufacturer" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.vendor_store_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__vm_vendor b ON b.vendor_id=a.asset_id
				 WHERE a.asset_type="vendor" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(CONCAT(b.field1," - ",b.field2) USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__cmcoupon_vm1ids b ON b.id=a.asset_id
				 WHERE a.asset_type="shipping" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.coupon_code USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__cmcoupon b ON b.id=a.asset_id
				 WHERE a.asset_type="coupon" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__users b ON b.id=a.asset_id
				 WHERE a.asset_type="user" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.shopper_group_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__vm_shopper_group b ON b.shopper_group_id=a.asset_id
				 WHERE a.asset_type="usergroup" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.payment_method_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__vm_payment_method b ON b.payment_method_id=a.asset_id
				 WHERE a.asset_type="paymentmethod" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.country_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__vm_country b ON b.country_id=a.asset_id
				 WHERE a.asset_type="country" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.state_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__vm_state b ON b.state_id=a.asset_id
				 WHERE a.asset_type="countrystate" AND a.coupon_id IN (' . $coupon_ids . ')
				 
				 ORDER BY  ' . ( ! empty( $order_by ) ? $order_by : 'order_by,asset_name,asset_id' ) . '
		';
		$items = AC()->db->get_objectlist( $sql );

		$asset = array();
		foreach ( $items as $k => $row ) {
			$params = $param_list[ $row->coupon_id ];

			$key = (int) $row->asset_key;
			if ( ! isset( $asset[ $row->coupon_id ][ $key ] ) ) {
				$asset[ $row->coupon_id ][ $key ] = new stdClass();
				if ( isset( $params[ $key ]['qty'] ) ) {
					$asset[ $row->coupon_id ][ $key ]->qty = $params[ $key ]['qty'];
				}
				$asset[ $row->coupon_id ][ $key ]->rows = new stdClass();
			}
			if ( ! isset( $asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type} ) ) {
				$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type} = new stdClass();
				$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->type = $row->asset_type;
				if ( isset( $params[ $key ]['rows'][ $row->asset_type ]['mode'] ) ) {
					$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->mode = $params[ $key ]['rows'][ $row->asset_type ]['mode'];
				}
				if ( isset( $params[ $key ]['rows'][ $row->asset_type ]['qty'] ) ) {
					$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->qty = $params[ $key ]['rows'][ $row->asset_type ]['qty'];
				}
			}
			if ( 'countrystate' == $row->asset_type ) {
				if ( ! isset( $asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->country ) ) {
					$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->country = array();
				}
				$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->country = $param_list[ $row->coupon_id ][ $key ]['rows'][ $row->asset_type ]['country'];
			}
			$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->rows[ $row->asset_id ] = $row;
		}

		return is_object( $coupon ) && ! empty( $coupon->id ) ? ( isset( $asset[ $coupon->id ] ) ? $asset[ $coupon->id ] : array() ) : $asset;
	}

	public function get_products( $product_id = null, $search = null, $limit = null, $is_published = true, $limitstart = null, $orderby = null, $orderbydir = null, $is_notgift = false ) {
		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS
					p.product_id AS id,CONCAT(p.product_name," (",p.product_sku,")") AS label,p.product_sku as sku,p.product_name AS product_name
				  FROM #__vm_product p
				  LEFT JOIN #__cmcoupon_giftcert_product g ON g.product_id=p.product_id
				 WHERE 1=1
				 ' . ( $is_published ? ' AND p.product_publish="Y" ' : '' ) . '
				 ' . ( $is_notgift ? ' AND g.product_id IS NULL ' : '' ) . '
				 ' . ( ! empty( $product_id ) ? ' AND p.product_id IN (' . AC()->helper->scrubids( $product_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(p.product_name," (",p.product_sku,")") LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,sku' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_categorys( $category_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		if ( empty( $category_id ) && empty( $search ) && empty( $limit ) ) {
			return self::category_tree();
		}

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$display_unpublished = ( (int) AC()->param->get( 'display_category_unpublished', 0 ) ) == 1 ? true : false;

		$sql = 'SELECT SQL_CALC_FOUND_ROWS c.category_id AS id, c.category_name AS label
				  FROM #__vm_category c
				 WHERE 1=1
				 ' . ( ! $display_unpublished ? ' AND c.category_publish="Y" ' : '' ) . '
				 ' . ( ! empty( $category_id ) ? ' AND c.category_id IN (' . AC()->helper->scrubids( $category_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND c.category_name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	private function category_tree( $selected_categories = array(), $cid = 0, $level = 0, $disabled_fields = array() ) {
		static $category_tree_output = array();

		$cid = (int) $cid;

		$level++;

		$display_unpublished = ( (int) AC()->param->get( 'display_category_unpublished', 0 ) ) == 1 ? true : false;

		$sql = 'SELECT c.category_id AS category_id,c.category_name AS category_name, cx.category_child_id AS category_child_id, cx.category_parent_id AS category_parent_id
				  FROM `#__vm_category` c
				  LEFT JOIN #__vm_category_xref AS cx ON c.category_id = cx.category_child_id
				 WHERE cx.category_parent_id=' . (int) $cid . '
				 ' . ( ! $display_unpublished ? ' AND c.category_publish="Y" ' : '1=1' ) . '
		';
		$records = AC()->db->get_objectlist( $sql );

		$selected = '';
		if ( ! empty( $records ) ) {
			foreach ( $records as $key => $category ) {
				if ( empty( $category->category_child_id ) ) {
					continue;//$category->category_child_id = $category->category_id;
				}

				$child_id = $category->category_child_id;

				if ( $child_id != $cid ) {
					if ( in_array( $child_id, $selected_categories ) ) {
						$selected = 'selected=\"selected\"';
					} else {
						$selected = '';
					}

					$category_tree_output[ $child_id ] = (object) array(
						'category_id' => $child_id,
						'category_name' => $category->category_name,
						'id' => $child_id,
						'label' => str_repeat( '---', ( $level - 1 ) ) . $category->category_name,
					);
				}

				$test = (int) AC()->db->get_value( 'SELECT category_child_id FROM #__vm_category_xref WHERE category_parent_id=' . (int) $child_id );
				if ( ! empty( $test ) ) {
					self::category_tree( $selected_categories, $child_id, $level, $disabled_fields );
				}
			}
		}

		return $category_tree_output;
	}

	public function get_manufacturers( $manu_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS
					m.manufacturer_id AS id,m.mf_name AS label, m.mf_name as name
				  FROM #__vm_manufacturer m
				 WHERE 1=1
				 ' . ( ! empty( $manu_id ) ? ' AND m.manufacturer_id IN (' . AC()->helper->scrubids( $manu_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND m.mf_name LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_vendors( $vendor_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS v.vendor_id AS id,v.vendor_store_name AS label, v.vendor_store_name AS name
				  FROM #__vm_vendor v
				 WHERE 1=1
				 ' . ( ! empty( $vendor_id ) ? ' AND v.vendor_id IN (' . AC()->helper->scrubids( $vendor_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND v.vendor_store_name LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_assetcustoms( $id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		return array();
	}

	public function get_shippings( $shipping_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		if ( ! defined( 'VM_TABLEPREFIX' ) ) {
			require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/virtuemart.cfg.php';
		}
		global $PSHOP_SHIPPING_MODULES;

		// get virtuemart shipping
		$o = array();
		$file_path = dirname( __FILE__ );
		foreach ( $PSHOP_SHIPPING_MODULES as $shipping_module ) {
			if ( file_exists( $file_path . '/shipping/' . $shipping_module . '.php' ) ) {
				require_once $file_path . '/shipping/' . $shipping_module . '.php';
				$shipping_class = 'cm_' . $shipping_module;
				if ( class_exists( $shipping_class ) ) {
					$SHIPPING = new $shipping_class();
					$o = array_merge_recursive( $o, $SHIPPING->get_all_rates() );
				}
			}
		}
		// make sure items are marked in vm1 ids
		$shipping_list = AC()->db->get_objectlist( 'SELECT id,value FROM #__cmcoupon_vm1ids WHERE type="shipping_rate_id"', 'value' );

		// return published items
		$output = array();
		$search = strtolower( $search );
		foreach ( $o as $shipping_module => $row1 ) {
			if ( $shipping_module == '_raw' ) {
				continue;
			}
			$shipping_class = 'cm_' . $shipping_module;
			$shipping_class = new $shipping_class();
			$name_module = $shipping_class->get_module_name();

			foreach ( $row1 as $tmp ) {
				$id = isset( $shipping_list[ $tmp->dbshipper_id ]->id ) ? (int) $shipping_list[ $tmp->dbshipper_id ]->id : 0;
				if ( empty( $id ) ) {
					AC()->db->query( '
						INSERT INTO #__cmcoupon_vm1ids (type,value,field1,field2)
						 VALUE ("shipping_rate_id","' . $tmp->dbshipper_id . '","'.AC()->db->escape( $name_module ) . '","' . AC()->db->escape( $tmp->shipper_string ) . '")
					');
				}
			}
		}

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS id, field2 label, field2 AS name, field1 AS carrier
				  FROM #__cmcoupon_vm1ids
				 WHERE type="shipping_rate_id"
				 ' . ( ! empty( $shipping_id ) ? ' AND shipping_id IN (' . AC()->helper->scrubids( $shipping_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(field1, " - ", field2) LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_groups( $shoppergroup_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS shopper_group_id AS id,shopper_group_name AS label,shopper_group_name AS name 
				  FROM #__vm_shopper_group
				 WHERE 1=1
				 ' . ( ! empty( $shoppergroup_id ) ? ' AND shopper_group_id IN (' . AC()->helper->scrubids( $shoppergroup_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND shopper_group_name LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_group_ids( $user_id ) {
		$shopper_group_ids = AC()->db->get_column( 'SELECT shopper_group_id FROM #__vm_shopper_vendor_xref WHERE user_id=' . (int) $user_id );
		if ( empty( $shopper_group_ids ) ) {
			$shopper_group_ids = array( AC()->db->get_value( 'SELECT shopper_group_id FROM #__vm_shopper_vendor_xref WHERE `default`=1' ) );
		}
	}

	public function get_users( $user_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS
						u.id,CONCAT(u.username," - ",u.name) as label,
						u.username,
						IF(vu.last_name IS NULL,
								TRIM(SUBSTRING(TRIM(u.name),LENGTH(TRIM(u.name))-LOCATE(" ",REVERSE(TRIM(u.name)))+1)),
								vu.last_name) as lastname,
						IF(vu.first_name IS NULL,
								TRIM(REVERSE(SUBSTRING(REVERSE(TRIM(u.name)),LOCATE(" ",REVERSE(TRIM(u.name)))+1))),
								vu.first_name) as firstname
				  FROM #__users u
				  LEFT JOIN #__vm_user_info vu ON vu.user_id=u.id AND vu.address_type="BT"
				 WHERE 1=1
				 ' . ( ! empty( $user_id ) ? ' AND u.id IN (' . AC()->helper->scrubids( $user_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(u.username," - ",u.name) LIKE "%' . AC()->db->escape( trim( $search ), true ) . '%" ' : '' ) . '
				 GROUP BY u.id
				 ORDER BY ' . ( empty( $orderby ) ? 'label,u.id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_countrys() {
		$sql = 'SELECT country_id as id,country_id,country_name
				  FROM `#__vm_country`
				 ORDER BY country_name,country_id';
		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_countrystates( $country_id = null ) {
		$sql = 'SELECT state_id as id,state_name AS label
				  FROM `#__vm_state`
				 WHERE 1=1
				 ' . ( ! empty( $country_id ) ? ' AND`country_id`= "' . (int) $country_id . '" ' : '' ) . '
				 ORDER BY state_name,state_id';
		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_paymentmethods() {
		$sql = 'SELECT payment_method_id AS id,payment_method_name AS name
				  FROM #__vm_payment_method
				 WHERE payment_enabled="Y"
				 ORDER BY payment_method_name,payment_method_id';
		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_order( $order_id ) {
		return AC()->db->get_object('
			SELECT * FROM #__vm_orders WHERE order_id=' . (int) $order_id . '
		');
	}

	public function get_order_link( $order_id ) {
		return JRoute::_( 'index.php?option=com_virtuemart&page=order.order_print&order_id=' . (int) $order_id );
	}

	public function sql_history_order( $where, $having, $orderby ) {
		$sql = 'SELECT c.id,c.id AS voucher_customer_id, c.codes, c.user_id, c.user_id AS _user_id, uv.user_email as user_email,
					 ov.order_id AS order_id,LPAD(ov.order_id,8,"0") AS order_number,
					 u.username AS _username, uv.first_name as _fname, uv.last_name as _lname,FROM_UNIXTIME(ov.cdate) AS _created_on,
					 GROUP_CONCAT(cc.code ORDER BY cc.code SEPARATOR ", ") as coupon_codes
				 FROM #__cmcoupon_voucher_customer c
				 LEFT JOIN #__cmcoupon_voucher_customer_code cc ON cc.voucher_customer_id=c.id
				 LEFT JOIN #__users u ON u.id=c.user_id
				 LEFT JOIN #__vm_orders ov ON ov.order_id=c.order_id
				 LEFT JOIN #__vm_order_user_info uv ON uv.order_id=c.order_id AND uv.address_type="BT"
				WHERE c.estore="' . $this->estore . '"
				' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				GROUP BY c.id
				' . ( ! empty( $having ) && is_array( $having ) ? ' HAVING ' . implode( ' AND ', $having ) . ' ' : '' ) . ' 
				' . ( ! empty( $orderby ) ? ' ORDER BY ' . $orderby . ' ' : '' ) . '
				';

		return $sql;
	}

	public function sql_history_giftcert( $where, $having = array(), $orderby = array() ) {
		$sql = 'SELECT c.*,
					 uv.user_id AS user_id,uv.first_name AS first_name,uv.last_name AS last_name,
					 o.order_id AS order_id,SUM(au.total_product)+SUM(au.total_shipping) AS coupon_value_used,
					 c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance,au.user_email,
					 u.username as _username, uv.first_name as _fname, uv.last_name as _lname,FROM_UNIXTIME(o.cdate) AS _created_on,
					 LPAD(o.order_id,8,"0") AS order_number
				 FROM #__cmcoupon c
				 LEFT JOIN #__vm_orders o ON o.order_id=c.order_id
				 LEFT JOIN #__vm_user_info uv ON uv.user_id=o.user_id AND uv.address_type="BT"
				 LEFT JOIN #__users u ON u.id=o.user_id
				 LEFT JOIN #__cmcoupon_history au ON au.coupon_id=c.id
				WHERE c.estore="' . $this->estore . '" AND c.function_type="giftcert"
				' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				GROUP BY c.id
				' . ( ! empty( $having ) && is_array( $having ) ? ' HAVING ' . implode( ' AND ', $having ) . ' ' : '' ) . '
				' . ( ! empty( $orderby ) ? ' ORDER BY ' . $orderby . ' ' : '' ) . '
				';
		return $sql;
	}

	public function sql_history_coupon( $where, $having, $orderby ) {

		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.startdate,c.expiration,c.state,
					 uu.coupon_id,uu.coupon_entered_id,c2.coupon_code as coupon_entered_code,
					 uu.id as use_id,uv.first_name AS first_name,uv.last_name AS last_name,uu.user_id,
					 (uu.total_product+uu.total_shipping) AS discount,uu.productids,uu.timestamp,uu.user_email,
					 ov.order_id AS order_id,LPAD(ov.order_id,8,"0") AS order_number,
					 u.username as _username, uv.first_name AS _fname, uv.last_name AS _lname,FROM_UNIXTIME(ov.cdate) AS _created_on,
					 uu.is_customer_balance,
					 (uu.total_curr_product+uu.total_curr_shipping) AS discount_in_currency, uu.currency_code
				 FROM #__cmcoupon_history uu
				 JOIN #__cmcoupon c ON c.id=uu.coupon_id
				 LEFT JOIN #__cmcoupon c2 ON c2.id=uu.coupon_entered_id
				 LEFT JOIN #__cmcoupon_tag t ON t.coupon_id=c.id
				 LEFT JOIN #__vm_user_info uv ON uv.user_id=uu.user_id AND uv.address_type="BT"
				 LEFT JOIN #__users u ON u.id=uu.user_id
				 LEFT JOIN #__cmcoupon_vm1ids vm1 ON vm1.id=uu.order_id
				 LEFT JOIN #__vm_orders ov ON ov.order_number=vm1.value
				WHERE uu.estore="' . $this->estore . '" AND uu.session_id IS NULL
				' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				GROUP BY uu.id
				' . ( ! empty( $having ) && is_array( $having ) ? ' HAVING ' . implode( ' AND ', $having ) . ' ' : '' ) . '
				' . ( ! empty( $orderby ) ? ' ORDER BY ' . $orderby . ' ' : '' ) . '
				';
		return $sql;
	}

	public function sql_store_credit( $where = array(), $having = array(), $orderby = '' ) {

		$sql = 'SELECT u.id AS id,u.username AS user_login,u.email AS user_email,u.name AS display_name,
					SUM(b.total) AS total,SUM(b.paid) AS paid,SUM(b.balance) AS balance
				  FROM #__users u
				  LEFT JOIN (
						SELECT c.coupon_value AS total,IFNULL(SUM(h.total_product+h.total_shipping),0) AS paid,
								(c.coupon_value-IFNULL(SUM(h.total_product+h.total_shipping),0)) as balance,cb.user_id
						  FROM #__cmcoupon c
						  JOIN #__cmcoupon_customer_balance cb ON cb.coupon_id=c.id
						  LEFT JOIN #__cmcoupon_history h ON h.coupon_id=c.id AND h.estore=c.estore
						 WHERE c.estore="' . $this->estore . '"
						   AND c.state="balance"
						 GROUP BY cb.id
				  ) AS b ON b.user_id=u.id
				 WHERE 1=1
				' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY u.id
				' . ( ! empty( $having ) && is_array( $having ) ? ' HAVING ' . implode( ' AND ', $having ) . ' ' : '' ) . '
				 ORDER BY ' . ( ! empty( $orderby ) ? $orderby . ' ' : ' u.username' ) . '
				';
		return $sql;
	}

	public function sql_giftcert_product( $where, $having, $orderby ) {
		$sql = 'SELECT g.*,p.product_name as _product_name,p.product_sku AS product_sku,pr.title as profile, COUNT(pc.id) as codecount,c.coupon_code
				  FROM #__cmcoupon_giftcert_product g
				  LEFT JOIN #__cmcoupon c ON c.id=g.coupon_template_id
				  LEFT JOIN #__vm_product p ON p.product_id=g.product_id
				  LEFT JOIN #__cmcoupon_profile pr ON pr.id=g.profile_id
				  LEFT JOIN #__cmcoupon_giftcert_code pc ON pc.product_id=p.product_id
				 WHERE g.estore="' . $this->estore . '"
				' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				   GROUP BY g.id
				' . ( ! empty( $having ) && is_array( $having ) ? ' HAVING ' . implode( ' AND ', $having ) . ' ' : '' ) . '
				' . ( ! empty( $orderby ) ? ' ORDER BY ' . $orderby . ' ' : '' ) . '
				';
		return $sql;
	}

	public function sql_giftcert_product_single( $product_id ) {
		$sql = 'SELECT p.product_name AS product_name,p.product_sku AS  product_sku
				   FROM #__vm_product p
				 WHERE p.product_id = ' . (int) $product_id;
		return $sql;
	}

	public function sql_giftcert_code( $where, $having, $orderby ) {
		$sql = 'SELECT g.*,p.product_name as _product_name,p.product_sku AS product_sku
				  FROM #__cmcoupon_giftcert_code g
				  LEFT JOIN #__vm_product p ON p.product_id=g.product_id
				 WHERE 1=1
				' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				' . ( ! empty( $having ) && is_array( $having ) ? ' HAVING ' . implode( ' AND ', $having ) . ' ' : '' ) . '
				' . ( ! empty( $orderby ) ? ' ORDER BY ' . $orderby . ' ' : '' ) . '
				';
		return $sql;
	}

	public function rpt_purchased_giftcert_list( $start_date, $end_date, $order_status, $published ) {
		$datestr = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) <= "' . $end_date . '" ';
		}

		$sql = 'SELECT gc.codes,gcc.product_id,p.product_name AS product_name,
					 uv.user_id AS user_id,uv.first_name AS first_name,uv.last_name AS last_name,u.username AS username,u.email AS email,
					 c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 o.order_id AS order_id,FROM_UNIXTIME(o.cdate) AS created_on,gc.codes,					 
					 o.order_total AS order_total,o.order_tax AS order_tax,o.order_shipping AS order_shipment,o.order_shipping_tax AS order_shipment_tax,o.order_discount*-1 AS order_fee,
					 o.order_subtotal AS order_subtotal

				 FROM #__cmcoupon_voucher_customer_code gcc
				 JOIN #__cmcoupon_voucher_customer gc ON gc.id=gcc.voucher_customer_id
				 LEFT JOIN #__cmcoupon c ON c.id=gcc.coupon_id
				 LEFT JOIN #__vm_product as p ON p.product_id=gcc.product_id
				 LEFT JOIN #__vm_orders o ON o.order_id=gc.order_id
				 LEFT JOIN #__vm_user_info uv ON uv.user_id=o.user_id AND uv.address_type="BT"
				 LEFT JOIN #__users u ON u.id=o.user_id
				WHERE gc.estore="' . $this->estore . '"
				 ' . $datestr . '
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . ( ! empty( $published ) ? 'AND c.state="' . $published . '" ' : '' ) . '
				 GROUP BY gcc.code
				 ORDER BY gc.order_id
		';
		return $sql;
	}

	public function rpt_history_uses_coupons( $start_date, $end_date, $order_status, $where ) {
		$datestr = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 uu.coupon_id,uu.coupon_entered_id,c2.coupon_code as coupon_entered_code,
					 uv.first_name AS first_name,uv.last_name AS last_name,uu.user_id,u.username AS username,
					 (uu.total_product+uu.total_shipping) AS discount,uu.productids,uu.timestamp,
					 o.order_id AS order_id,FROM_UNIXTIME(o.cdate) AS created_on,uu.id as num_uses_id,
					 o.order_total AS order_total,o.order_tax AS order_tax,o.order_shipping AS order_shipment,o.order_shipping_tax AS order_shipment_tax,o.order_discount*-1 AS order_fee,
					 o.order_subtotal AS order_subtotal
				 FROM #__cmcoupon c
				 JOIN #__cmcoupon_history uu ON uu.coupon_id=c.id
				 JOIN #__vm_user_info uv ON uv.user_id=uu.user_id
				 LEFT JOIN #__cmcoupon c2 ON c2.id=uu.coupon_entered_id
				 LEFT JOIN #__users u ON u.id=uu.user_id
				 LEFT JOIN #__vm_orders o ON o.order_id=uu.order_id

				WHERE c.estore="' . $this->estore . '"
				 ' . $datestr . '
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 ORDER BY u.username
		';
		return $sql;
	}

	public function rpt_history_uses_giftcerts( $start_date, $end_date, $order_status, $published, $giftcert_product ) {
		$datestr = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 u.id as user_id,uv.first_name AS first_name,uv.last_name AS last_name,u.username AS username,
					 o.order_total AS order_total,FROM_UNIXTIME(o.cdate) AS created_on,gc.codes,
					 o.order_subtotal AS order_subtotal,
					 o.order_tax AS order_tax,o.order_shipping AS order_shipment,o.order_shipping_tax AS order_shipment_tax,o.order_discount*-1 AS order_fee,
					 SUM(au.total_product)+SUM(au.total_shipping) AS coupon_value_used,
					 c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance,gcc.product_id,p.product_name AS product_name
				 FROM #__cmcoupon c
				 LEFT JOIN #__vm_orders o ON o.order_id=c.order_id
				 LEFT JOIN #__vm_user_info uv ON uv.user_id=o.user_id AND uv.address_type="BT"
				 LEFT JOIN #__users u ON u.id=o.user_id
				 LEFT JOIN #__cmcoupon_history au ON au.coupon_id=c.id
				 
				 LEFT JOIN #__cmcoupon_voucher_customer gc ON gc.order_id=o.order_id
				 LEFT JOIN #__cmcoupon_voucher_customer_code gcc ON gcc.voucher_customer_id=gc.id AND gcc.coupon_id=c.id

				 LEFT JOIN #__vm_product p ON p.product_id=gcc.product_id
				 
				WHERE c.estore="' . $this->estore . '" AND c.function_type="giftcert"
				 ' . $datestr . '
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . ( ! empty( $published ) ? 'AND c.state="' . $published . '" ' : '' ) . '
				 ' . ( ! empty( $giftcert_product ) ? 'AND gcc.product_id="' . $giftcert_product . '" ' : '' ) . '
				 GROUP BY c.id
				 ORDER BY u.username
		';

		return $sql;
	}

	public function rpt_coupon_vs_total( $start_date, $end_date, $order_status, $where ) {
		$datestr = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id, SUM(o.order_total) as total, COUNT(c.id) as count
				  FROM #__cmcoupon c
				  JOIN (SELECT coupon_entered_id,order_id FROM #__cmcoupon_history GROUP BY order_id,coupon_entered_id) uu ON uu.coupon_entered_id=c.id
				  JOIN #__vm_orders o ON o.order_id=uu.order_id
				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id
			 ORDER BY c.coupon_code';
		return $sql;
	}

	public function rpt_coupon_vs_location( $start_date, $end_date, $order_status, $where ) {
		$datestr = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND FROM_UNIXTIME(o.cdate) <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id,c.coupon_code, SUM(o.order_total) as total, COUNT(uu.order_id) as count,u.country as country,u.state as state,u.city AS city,
					 CONCAT(c.id,"-",IF(ISNULL(u.country),"0",u.country),"-",IF(ISNULL(u.state),"0",u.state),"-",u.city) as realid
				  FROM #__cmcoupon c
				  JOIN (SELECT coupon_entered_id,order_id FROM #__cmcoupon_history GROUP BY order_id,coupon_entered_id) uu ON uu.coupon_entered_id=c.id
				  JOIN #__vm_orders o ON o.order_id=uu.order_id
				  JOIN #__vm_order_user_info u ON u.order_id=o.order_id AND u.address_type="BT"
				  
				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id,u.country,u.state,u.city';
		$order_details = AC()->db->get_objectlist( $sql, 'realid' );

		$sql = 'SELECT c.id,c.coupon_code, uu.productids,
						SUM(uu.total_product+uu.total_shipping) as discount,
						 u.country,u.state,u.city
				  FROM #__cmcoupon_history uu
				  JOIN #__cmcoupon c ON c.id=uu.coupon_entered_id 
				  JOIN #__vm_orders o ON o.order_id=uu.order_id
				  JOIN #__vm_order_user_info u ON u.order_id=o.order_id AND u.address_type="BT"

				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id,u.country,u.state,u.city
				 ORDER BY c.coupon_code';

		return (object) array(
			'sql' => $sql,
			'order_details' => $order_details,
		);
	}

	public function get_order_status() {
		return AC()->db->get_objectlist( 'SELECT order_status_id, order_status_code, order_status_name FROM #__vm_order_status' );
	}

	public function add_coupon_to_cart( $coupon_code ) {
		$cart = $_SESSION['cart'];
		if ( empty( $cart['idx'] ) ) {
			return;
		}

		AC()->helper->set_request( 'coupon_code', $coupon_code );
		$_POST['do_coupon'] = 1;

		return true;
	}

	public function get_name() {
		return AC()->db->get_value( 'SELECT vendor_name FROM #__vm_vendor ORDER BY vendor_id LIMIT 1' );
	}

	public function get_email() {
		return JFactory::getConfig()->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'} ( 'mailfrom' );
	}

	public function get_home_link() {
		if ( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
			$url = JFactory::getConfig()->get( 'live_site' );
		}
		else {
			global $mainframe;
			$url = $mainframe->getCfg( 'live_site' );
		}
		if ( empty( $url ) ) {
			$url = JURI::root();
		}
		return $url;
	}

	public function get_app_link() {
		return 'index.php?option=com_virtuemart';
	}

	public function get_itemsperpage() {
		return JFactory::getApplication()->getCfg( 'list_limit' );
	}
}

