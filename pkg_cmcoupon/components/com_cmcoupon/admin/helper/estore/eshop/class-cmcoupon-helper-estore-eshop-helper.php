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

class Cmcoupon_Helper_Estore_Eshop_Helper {

	var $estore = 'eshop';

	public function is_installed() {
		return file_exists( JPATH_ADMINISTRATOR . '/components/com_eshop/eshop.php' ) ? true : false;
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

		$lang_where = ' AND c.language = "' . JComponentHelper::getParams('com_languages')->get('site', 'en-GB') . '" ';
		$sql = 'SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(c.product_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__eshop_products b ON b.id=a.asset_id
				  JOIN #__eshop_productdetails c ON c.product_id=b.id
				 WHERE a.asset_type="product" AND a.coupon_id IN (' . $coupon_ids . ') ' . $lang_where . '
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(c.category_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__eshop_categories b ON b.id=a.asset_id
				  JOIN #__eshop_categorydetails c ON c.category_id=b.id
				 WHERE a.asset_type="category" AND a.coupon_id IN (' . $coupon_ids . ') ' . $lang_where . '
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(c.manufacturer_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__eshop_manufacturers b ON b.id=a.asset_id
				  JOIN #__eshop_manufacturerdetails c ON c.manufacturer_id=b.id
				 WHERE a.asset_type="manufacturer" AND a.coupon_id IN (' . $coupon_ids . ') ' . $lang_where . '
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(B.title USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__eshop_shippings b ON b.id=a.asset_id
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
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(c.customergroup_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__eshop_customergroups b ON b.id=a.asset_id
				  join #__eshop_customergroupdetails c ON b.id = c.customergroup_id
				 WHERE a.asset_type="usergroup" AND a.coupon_id IN (' . $coupon_ids . ') ' . $lang_where . '
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.title USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__eshop_payments b ON b.id=a.asset_id
				 WHERE a.asset_type="paymentmethod" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.country_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__eshop_countries b ON b.id=a.asset_id
				 WHERE a.asset_type="country" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.zone_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__eshop_zones b ON b.id=a.asset_id
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
				//$asset[ $row->coupon_id ][ $key ]->rows->{$row->asset_type}->country[] = substr( $row->asset_id, 0, strpos( $row->asset_id, '-' ) );
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
					p.id AS id, CONCAT(b.product_name," (",p.product_sku,")") AS label,p.product_sku as sku,b.product_name AS product_name,CONCAT(b.product_name," (",p.product_sku,")") AS name
				  FROM #__eshop_products p
				  LEFT JOIN #__eshop_productdetails AS b ON p.id = b.product_id
				  LEFT JOIN #__cmcoupon_giftcert_product g ON g.product_id=p.id
				 WHERE b.language = "' . JComponentHelper::getParams('com_languages')->get('site', 'en-GB') . '"
				 ' . ( $is_published ? ' AND p.published=1 ' : '' ) . '
				 ' . ( $is_notgift ? ' AND g.product_id IS NULL ' : '' ) . '
				 ' . ( ! empty( $product_id ) ? ' AND p.id IN (' . AC()->helper->scrubids( $product_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(b.product_name," (",p.product_sku,")") LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
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

		$sql = 'SELECT SQL_CALC_FOUND_ROWS c.id AS id, b.category_name AS label, b.category_name AS name
				  FROM #__eshop_categories c
				  JOIN #__eshop_categorydetails b ON b.category_id=c.id
				 WHERE b.language = "' . JComponentHelper::getParams('com_languages')->get('site', 'en-GB') . '"
				 ' . ( ! empty( $display_unpublished ) ? ' AND c.published=1 ' : '' ) . '
				 ' . ( ! empty( $category_id ) ? ' AND c.id IN (' . AC()->helper->scrubids( $category_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND b.category_name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	private function category_tree( $selected_categories = array(), $cid = 0, $level = 0, $disabled_fields = array() ) {
		static $category_tree_output = array();

		$cid = (int) $cid;

		$level++;

		$display_unpublished = ( (int) AC()->param->get( 'display_category_unpublished', 0 ) ) == 1 ? true : false;

		$sql = 'SELECT c.id AS category_id,b.category_name AS category_name, c.id AS category_child_id, c.category_parent_id AS category_parent_id
				  FROM #__eshop_categories c
				  JOIN #__eshop_categorydetails b ON b.category_id=c.id
				 WHERE c.category_parent_id=' . (int) $cid . '
				 ' . ( ! empty( $display_unpublished ) ? ' AND c.published=1 ' : '' ) . '
				   AND b.language = "' . JComponentHelper::getParams('com_languages')->get('site', 'en-GB') . '"';
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

				$test = (int) AC()->db->get_value( 'SELECT id FROM #__eshop_categories WHERE category_parent_id=' . (int) $child_id );
				if ( ! empty( $test ) ) {
					$this->category_tree( $selected_categories, $child_id, $level, $disabled_fields );
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
					a.id AS id, b.manufacturer_name AS label,b.manufacturer_name AS name
				  FROM #__eshop_manufacturers a
				  LEFT JOIN #__eshop_manufacturerdetails AS b ON a.id = b.manufacturer_id
				 WHERE a.published=1
				   AND b.language = "' . JComponentHelper::getParams('com_languages')->get('site', 'en-GB') . '"
				 ' . ( ! empty( $manu_id ) ? ' AND a.id IN (' . AC()->helper->scrubids( $manu_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND b.manufacturer_name LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_vendors( $vendor_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		return array();
	}

	public function get_assetcustoms( $id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		return array();
	}

	public function get_shippings( $shipping_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS
					a.id AS id, a.title AS label,a.title AS name
				  FROM #__eshop_shippings a
				 WHERE a.published=1
				 ' . ( ! empty( $shipping_id ) ? ' AND a.id IN (' . AC()->helper->scrubids( $shipping_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND a.title LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
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

		$sql = 'SELECT SQL_CALC_FOUND_ROWS a.id AS id,b.customergroup_name AS label,b.customergroup_name AS name 
				  FROM #__eshop_customergroups a
				  JOIN #__eshop_customergroupdetails b ON a.id = b.customergroup_id
				 WHERE 1=1
				 ' . ( ! empty( $shoppergroup_id ) ? ' AND a.id IN (' . AC()->helper->scrubids( $shoppergroup_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND b.customergroup_name LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_group_ids( $user_id ) {
		return array ( (int) AC()->db->get_value( 'SELECT customergroup_id FROM #__eshop_customers WHERE customer_id=' . (int) $user_id ) );
	}

	public function get_users( $user_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS
						u.id,CONCAT(u.username," - ",u.name) as label,
						u.username AS username,
						IF(e.lastname IS NULL,
								TRIM(SUBSTRING(TRIM(u.name),LENGTH(TRIM(u.name))-LOCATE(" ",REVERSE(TRIM(u.name)))+1)),
								e.lastname) as lastname,
						IF(e.firstname IS NULL,
								TRIM(REVERSE(SUBSTRING(REVERSE(TRIM(u.name)),LOCATE(" ",REVERSE(TRIM(u.name)))+1))),
								e.firstname) as firstname
				  FROM #__users u
				  LEFT JOIN #__eshop_customers e ON e.customer_id=u.id
				 WHERE 1=1
				 ' . ( ! empty( $user_id ) ? ' AND u.id IN (' . AC()->helper->scrubids( $user_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(u.username," - ",u.name) LIKE "%' . AC()->db->escape( trim( $search ), true ) . '%" ' : '' ) . '
				 GROUP BY u.id
				 ORDER BY ' . ( empty( $orderby ) ? 'label,u.id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_countrys() {
		return AC()->db->get_objectlist( '
			SELECT id, id AS country_id, country_name, country_name AS name
			  FROM #__eshop_countries
			 WHERE published = 1
			 ORDER BY country_name, id
		', 'id' );
	}

	public function get_countrystates( $country_id = null ) {
		return AC()->db->get_objectlist( '
			SELECT id, zone_name AS label, zone_name AS name
			  FROM #__eshop_zones
			 WHERE published = 1
			 ' . ( ! empty( $country_id ) ? ' AND`country_id`= "' . (int) $country_id . '" ' : '' ) . '
			 ORDER BY zone_name, id
		', 'id' );
	}

	public function get_paymentmethods() {
		return AC()->db->get_objectlist( 'SELECT id, title AS label, title AS name FROM #__eshop_payments ORDER BY title, id', 'id' );
	}

	public function get_order( $order_id ) {
		return AC()->db->get_object('
			SELECT o.*,o.id AS order_id,o.order_number AS order_number, o.total AS order_total,o.currency_code AS order_currency
			  FROM #__eshop_orders o
			 WHERE o.id=' . (int) $order_id . '
		');
	}

	public function get_order_link( $order_id ) {
		return JRoute::_( 'index.php?option=com_eshop&task=order.edit&cid[]=' . (int) $order_id );
	}

	public function sql_history_order( $where, $having, $orderby ) {
		$sql = 'SELECT c.id,c.id AS voucher_customer_id, c.codes, c.user_id, c.user_id AS _user_id, o.email as user_email,
					 o.id AS order_id,CONCAT(o.id," (",o.order_number,")") AS order_number,
					 u.username AS _username, o.firstname as _fname, o.lastname as _lname,o.created_date AS _created_on,
					 GROUP_CONCAT(cc.code ORDER BY cc.code SEPARATOR ", ") as coupon_codes
				 FROM #__cmcoupon_voucher_customer c
				 LEFT JOIN #__cmcoupon_voucher_customer_code cc ON cc.voucher_customer_id=c.id
				 LEFT JOIN #__users u ON u.id=c.user_id
				 LEFT JOIN #__eshop_orders o ON o.id=c.order_id
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
					 e.customer_id AS user_id,e.firstname AS first_name,e.lastname AS last_name,
					 o.id AS order_id,SUM(au.total_product)+SUM(au.total_shipping) AS coupon_value_used,
					 c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance,au.user_email,
					 u.username as _username, e.firstname as _fname, e.lastname as _lname,o.created_date AS _created_on,
					 o.order_number AS order_number
				 FROM #__cmcoupon c
				 LEFT JOIN #__eshop_orders o ON o.id=c.order_id
				 LEFT JOIN #__eshop_customers e ON e.customer_id=o.customer_id
				 LEFT JOIN #__users u ON u.id=o.customer_id
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
					 uu.id as use_id,e.firstname AS first_name,e.lastname AS last_name,uu.user_id,
					 (uu.total_product+uu.total_shipping) AS discount,uu.productids,uu.timestamp,uu.user_email,
					 o.id AS order_id,o.order_number AS order_number,
					 u.username as _username, e.firstname AS _fname, e.lastname AS _lname,o.created_date AS _created_on,
					 uu.is_customer_balance,
					 (uu.total_curr_product+uu.total_curr_shipping) AS discount_in_currency, uu.currency_code
				 FROM #__cmcoupon_history uu
				 JOIN #__cmcoupon c ON c.id=uu.coupon_id
				 LEFT JOIN #__cmcoupon c2 ON c2.id=uu.coupon_entered_id
				 LEFT JOIN #__cmcoupon_tag t ON t.coupon_id=c.id
				 LEFT JOIN #__eshop_customers e ON e.customer_id=uu.user_id
				 LEFT JOIN #__users u ON u.id=uu.user_id
				 LEFT JOIN #__eshop_orders o ON o.id=uu.order_id
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
		$sql = 'SELECT g.*,pd.product_name as _product_name,p.product_sku,pr.title as profile, COUNT(pc.id) as codecount,c.coupon_code
				  FROM #__cmcoupon_giftcert_product g
				  LEFT JOIN #__cmcoupon c ON c.id=g.coupon_template_id
				  LEFT JOIN #__eshop_products p ON p.id=g.product_id
				  LEFT JOIN #__eshop_productdetails pd ON pd.product_id=p.id AND pd.language = "' . JComponentHelper::getParams('com_languages')->get('site', 'en-GB') . '"
				  LEFT JOIN #__cmcoupon_profile pr ON pr.id=g.profile_id
				  LEFT JOIN #__cmcoupon_giftcert_code pc ON pc.product_id=p.id
				 WHERE g.estore="' . $this->estore . '"
				' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				   GROUP BY g.id
				' . ( ! empty( $having ) && is_array( $having ) ? ' HAVING ' . implode( ' AND ', $having ) . ' ' : '' ) . '
				' . ( ! empty( $orderby ) ? ' ORDER BY ' . $orderby . ' ' : '' ) . '
				';
		return $sql;
	}

	public function sql_giftcert_product_single( $product_id ) {
		$sql = 'SELECT pd.product_name,p.product_sku
				  FROM #__eshop_products p
				  LEFT JOIN #__eshop_productdetails pd ON pd.product_id=p.id
				 WHERE p.id = ' . (int) $product_id;
		return $sql;
	}

	public function sql_giftcert_code( $where, $having, $orderby ) {
		$sql = 'SELECT g.*,c.product_name as _product_name, p.product_sku
				  FROM #__cmcoupon_giftcert_code g
				  LEFT JOIN #__eshop_products p ON p.id=g.product_id
				  LEFT JOIN #__eshop_productdetails c ON c.product_id=p.id AND c.language = "' . JComponentHelper::getParams('com_languages')->get('site', 'en-GB') . '"
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
			$datestr = ' AND o.created_date BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.created_date >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.created_date <= "' . $end_date . '" ';
		}

		$sql = 'SELECT gc.codes,gcc.product_id,p.product_name,
					 o.customer_id AS user_id,o.firstname AS first_name,o.lastname AS last_name,u.username,u.email,
					 c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 o.id AS order_id,o.created_date AS created_on,gc.codes,					 
					 o.total AS order_total,0 AS order_tax,0 AS order_shipment,0 AS order_shipment_tax,0 AS order_fee,
					 0 AS order_subtotal

				  FROM #__cmcoupon_voucher_customer_code gcc
				  JOIN #__cmcoupon_voucher_customer gc ON gc.id=gcc.voucher_customer_id
				  LEFT JOIN #__cmcoupon c ON c.id=gcc.coupon_id AND c.estore=gc.estore
				 
				  LEFT JOIN #__eshop_productdetails p ON p.product_id=gcc.product_id
				  LEFT JOIN #__eshop_orders o ON o.id=gc.order_id
				  LEFT JOIN #__users u ON u.id=o.customer_id
				WHERE gc.estore="' . $this->estore . '"
				  AND p.language = "' . JComponentHelper::getParams( 'com_languages' )->get('site', 'en-GB' ) . '"
				 ' . $datestr . '
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status_id IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . ( ! empty( $published ) ? 'AND c.state="' . $published . '" ' : '' ) . '
				 GROUP BY gcc.code
				 ORDER BY gc.order_id
		';
		return $sql;
	}

	public function rpt_history_uses_coupons( $start_date, $end_date, $order_status, $where ) {
		$datestr = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$datestr = ' AND o.created_date BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.created_date >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.created_date <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 uu.coupon_id,uu.coupon_entered_id,c2.coupon_code as coupon_entered_code,
					 e.firstname AS first_name,e.lastname AS last_name,uu.user_id,u.username,
					 (uu.total_product+uu.total_shipping) AS discount,uu.productids,uu.timestamp,
					 o.id AS order_id,o.created_date AS created_on,uu.id as num_uses_id,
					 o.total AS order_total,0 AS order_tax,0 AS order_shipment,0 AS order_shipment_tax,0 AS order_fee,
					 0 AS order_subtotal
				  FROM #__cmcoupon c
				  JOIN #__cmcoupon_history uu ON uu.coupon_id=c.id
				  LEFT JOIN #__cmcoupon c2 ON c2.id=uu.coupon_entered_id
				 
				  LEFT JOIN #__users u ON u.id=uu.user_id
				  LEFT JOIN #__eshop_customers e ON e.customer_id=uu.user_id
				  LEFT JOIN #__eshop_orders o ON o.id=uu.order_id

				WHERE c.estore="' . $this->estore . '"
				 ' . $datestr . '
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status_id IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 ORDER BY u.username
		';
		return $sql;
	}

	public function rpt_history_uses_giftcerts( $start_date, $end_date, $order_status, $published, $giftcert_product ) {
		$datestr = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$datestr = ' AND o.created_date BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.created_date >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.created_date <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 o.customer_id as user_id,o.firstname AS first_name,o.lastname AS last_name,u.username,
					 o.total AS order_total,o.created_date AS created_on,gc.codes,
					 0 AS order_subtotal,
					 0 AS order_tax,0 AS order_shipment,0 AS order_shipment_tax,0 AS order_fee,
					 SUM(au.total_product)+SUM(au.total_shipping) AS coupon_value_used,
					 c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance,gcc.product_id,p.product_name
				  FROM #__cmcoupon c
				 
				  LEFT JOIN #__eshop_orders o ON o.id=c.order_id
				  LEFT JOIN #__users u ON u.id=o.customer_id
				  LEFT JOIN #__cmcoupon_history au ON au.coupon_id=c.id
				  LEFT JOIN #__cmcoupon_voucher_customer gc ON gc.order_id=o.id
				  LEFT JOIN #__cmcoupon_voucher_customer_code gcc ON gcc.voucher_customer_id=gc.id AND gcc.coupon_id=c.id
				  LEFT JOIN #__eshop_productdetails p ON p.product_id=gcc.product_id
				 
				WHERE c.estore="' . $this->estore . '" AND c.function_type="giftcert"
				  AND p.language = "' . JComponentHelper::getParams( 'com_languages' )->get( 'site', 'en-GB' ) . '"
				 ' . $datestr . '
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status_id IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
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
			$datestr = ' AND o.created_date BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.created_date >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.created_date <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id, SUM(o.total) as total, COUNT(c.id) as count
				  FROM #__cmcoupon c
				  JOIN (SELECT coupon_entered_id,order_id FROM #__cmcoupon_history GROUP BY order_id,coupon_entered_id) uu ON uu.coupon_entered_id=c.id
				 LEFT JOIN #__eshop_orders o ON o.id=uu.order_id
				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status_id IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id
			 ORDER BY c.coupon_code';
		return $sql;
	}

	public function rpt_coupon_vs_location( $start_date, $end_date, $order_status, $where ) {
		$datestr = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$datestr = ' AND o.created_date BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.created_date >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.created_date <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id,c.coupon_code, SUM(o.total) as total, COUNT(uu.order_id) as count,o.payment_country_id as country,o.payment_zone_id as state,o.payment_city AS city,
					 CONCAT(c.id,"-",IF(ISNULL(o.payment_country_id),"0",o.payment_country_id),"-",IF(ISNULL(o.payment_zone_id),"0",o.payment_zone_id),"-",o.payment_city) AS realid
				  FROM #__cmcoupon c
				  JOIN (SELECT coupon_entered_id,order_id FROM #__cmcoupon_history GROUP BY order_id,coupon_entered_id) uu ON uu.coupon_entered_id=c.id
				  LEFT JOIN #__eshop_orders o ON o.id=uu.order_id
				  
				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status_id IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id,o.payment_country_id,o.payment_zone_id,o.payment_city';
		$order_details = AC()->db->get_objectlist( $sql, 'realid' );

		$sql = 'SELECT c.id,c.coupon_code, uu.productids,
						SUM(uu.total_product+uu.total_shipping) as discount,
						o.payment_country_id as country,o.payment_zone_id as state,o.payment_city AS city
				  FROM #__cmcoupon_history uu
				  JOIN #__cmcoupon c ON c.id=uu.coupon_entered_id 
				  
				  LEFT JOIN #__eshop_orders o ON o.id=uu.order_id

				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status_id IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id,o.payment_country_id,o.payment_zone_id,o.payment_city
				 ORDER BY c.coupon_code';

		return (object) array(
			'sql' => $sql,
			'order_details' => $order_details,
		);
	}

	public function get_order_status() {
		return AC()->db->get_objectlist( '
			SELECT a.id AS order_status_id, a.id AS order_status_code, b.orderstatus_name AS order_status_name
			  FROM #__eshop_orderstatuses a
			  JOIN #__eshop_orderstatusdetails AS b ON a.id = b.orderstatus_id
			 WHERE a.published = 1
			   AND b.language = "' . JComponentHelper::getParams( 'com_languages' )->get( 'site', 'en-GB' ) . '"
		' );
	}

	public function add_coupon_to_cart( $coupon_code ) {

		if ( ! class_exists( 'EshopHelper' ) ) {
			require JPATH_ROOT . '/components/com_eshop/helpers/helper.php';
		}
		if ( ! class_exists( 'EshopCart' ) ) {
			require JPATH_ROOT . '/components/com_eshop/helpers/cart.php';
		}
		if ( ! class_exists( 'EshopCoupon' ) ) {
			require JPATH_ROOT . '/components/com_eshop/helpers/coupon.php';
		}
		$cart = new EshopCart();
		if ( ! $cart->hasProducts() ) {
			return false;
		}

		if ( ! class_exists( 'EShopControllerCart' ) ) {
			require JPATH_ROOT . '/components/com_eshop/controllers/cart.php';
		}
		$cartController = new EShopControllerCart( array( 'base_path' => JPATH_ROOT . '/components/com_eshop') );

		AC()->helper->set_request( 'coupon_code', $coupon_code );
		$cartController->applyCoupon();

		return true;
	}

	public function get_name() {
		if ( ! class_exists( 'EshopHelper' ) ) {
			require JPATH_ROOT . '/components/com_eshop/helpers/helper.php';
		}
		return EshopHelper::getConfigValue( 'store_name' );
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
		return 'index.php?option=com_eshop';
	}

	public function get_itemsperpage() {
		return JFactory::getApplication()->getCfg( 'list_limit' );
	}
}

