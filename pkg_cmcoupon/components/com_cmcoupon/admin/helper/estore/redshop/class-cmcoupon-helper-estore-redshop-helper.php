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

class Cmcoupon_Helper_Estore_Redshop_Helper {

	var $estore = 'Redshop';

	public function is_installed() {
		return file_exists( JPATH_ADMINISTRATOR . '/components/com_redshop/redshop.xml' ) ? true : ( file_exists(JPATH_ADMINISTRATOR . '/components/com_redshop/com_redshop.xml' ) ? true : false );
	}

	public function __construct() {
		// Load redSHOP Library
		JLoader::import('redshop.library');

		if ( ! class_exists( 'RedshopModelConfiguration' ) ) {
			if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_redshop/models/configuration.php' ) ) {
				return;
			}
			require JPATH_ADMINISTRATOR . '/components/com_redshop/models/configuration.php';
		}
		$configClass = new RedshopModelConfiguration();
		$this->version = $configClass->getCurrentVersion();
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

		$supplier_name_column = version_compare( $this->version, '2.0.0', '>=' ) ? 'name' : 'supplier_name';
		$supplier_id_column = version_compare( $this->version, '2.0.0', '>=' ) ? 'id' : 'supplier_id';

		$state_id_name = version_compare( $this->version, '2.0.0', '>=' ) ? 'id' : 'state_id';
		$country_id_name = version_compare( $this->version, '2.0.0', '>=' ) ? 'id' : 'country_id';

		$sql = 'SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.product_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__redshop_product b ON b.product_id=a.asset_id
				 WHERE a.asset_type="product" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.category_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__redshop_category b ON b.category_id=a.asset_id
				 WHERE a.asset_type="category" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION

				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.manufacturer_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__redshop_manufacturer b ON b.manufacturer_id=a.asset_id
				 WHERE a.asset_type="manufacturer" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.' . $supplier_name_column . ' USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__redshop_supplier b ON b.' . $supplier_id_column . '=a.asset_id
				 WHERE a.asset_type="vendor" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.shipping_rate_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__redshop_shipping_rate b ON b.shipping_rate_id=a.asset_id
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
				  JOIN #__redshop_shopper_group b ON b.shopper_group_id=a.asset_id
				 WHERE a.asset_type="usergroup" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				 ' . ( version_compare( JVERSION, '1.6.0', 'ge' )
					? 'JOIN #__extensions b ON b.extension_id=a.asset_id AND b.type="plugin" AND b.folder="redshop_payment"'
					: 'JOIN #__plugins b ON b.id=a.asset_id AND b.folder="redshop_payment"' ) . '
				 WHERE a.asset_type="paymentmethod" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.country_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__redshop_country b ON b.' . $country_id_name . '=a.asset_id
				 WHERE a.asset_type="country" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				 SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.state_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__redshop_state b ON b.' . $state_id_name . '=a.asset_id
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
					p.product_id AS id,CONCAT(p.product_name," (",p.product_number,")") AS label,p.product_number as sku,p.product_name AS product_name
				  FROM #__redshop_product p
				  LEFT JOIN #__cmcoupon_giftcert_product g ON g.product_id=p.product_id
				 WHERE 1=1
				 ' . ( $is_published ? ' AND p.published=1 ' : '' ) . '
				 ' . ( $is_notgift ? ' AND g.product_id IS NULL ' : '' ) . '
				 ' . ( ! empty( $product_id ) ? ' AND p.product_id IN (' . AC()->helper->scrubids( $product_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(p.product_name," (",p.product_number,")") LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
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

		$sql = 'SELECT SQL_CALC_FOUND_ROWS category_id AS id, category_name AS label, category_name AS name
				  FROM #__redshop_category
				 WHERE 1=1
				 ' . ( ! $display_unpublished ? ' AND published=1 ' : '' ) . '
				 ' . ( ! empty( $category_id ) ? ' AND category_id IN (' . AC()->helper->scrubids( $category_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND category_name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	private function category_tree( $selected_categories = array(), $cid = 0, $level = 0, $disabled_fields = array() ) {
		static $category_tree_output = array();

		$cid = (int) $cid;

		$level++;

		$display_unpublished = ( (int) AC()->param->get( 'display_category_unpublished', 0 ) ) == 1 ? true : false;

		$sql = 'SELECT c.category_id,c.category_name, cx.category_child_id, cx.category_parent_id
				  FROM #__redshop_category c
				  LEFT JOIN #__redshop_category_xref AS cx ON c.category_id = cx.category_child_id
				 WHERE 1=1
				 ' . ( ! $display_unpublished ? ' AND c.published=1 ' : '' ) . '
				   AND cx.category_parent_id=' . (int) $cid;
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

				$test = (int) AC()->db->get_value( 'SELECT category_child_id FROM #__redshop_category_xref WHERE category_parent_id=' . (int) $child_id );
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
					m.manufacturer_id AS id,m.manufacturer_name AS label, m.manufacturer_name as name
				  FROM #__redshop_manufacturer m
				 WHERE 1=1
				 ' . ( ! empty( $manu_id ) ? ' AND m.manufacturer_id IN (' . AC()->helper->scrubids( $manu_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND m.manufacturer_name LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
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

		$supplier_name_column = version_compare( $this->version, '2.0.0', '>=' ) ? 'name' : 'supplier_name';
		$supplier_id_column = version_compare( $this->version, '2.0.0', '>=' ) ? 'id' : 'supplier_id';

		$sql = 'SELECT SQL_CALC_FOUND_ROWS v.' . $supplier_id_column . ' AS id,v.' . $supplier_name_column . ' AS label, v.' . $supplier_name_column . ' AS name
				  FROM #__redshop_supplier v
				 WHERE 1=1
				 ' . ( ! empty( $vendor_id ) ? ' AND v.' . $supplier_id_column . ' IN (' . AC()->helper->scrubids( $vendor_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND v.' . $supplier_name_column . ' LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
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

		if ( version_compare( JVERSION, '1.6.0', 'ge' ) )
			$sql = 'SELECT SQL_CALC_FOUND_ROWS r.shipping_rate_id AS id, r.shipping_rate_name AS label,p.name as carrier, r.shipping_rate_name AS name
					  FROM #__extensions p
					  JOIN #__redshop_shipping_rate r ON r.shipping_class=p.element
					 WHERE p.enabled=1 AND p.type="plugin" AND p.folder="redshop_shipping"
					 ' . ( ! empty( $shipping_id ) ? ' AND s.shipping_rate_id IN (' . AC()->helper->scrubids( $shipping_id ) . ') ' : '' ) . '
					HAVING 1=1 ' . ( ! empty( $search ) ? ' AND label LIKE "%' . AC()->db->escape( trim( $search ), true ) . '%" ' : '' ) . ' 
					 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
					 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );
		else
			$sql = 'SELECT SQL_CALC_FOUND_ROWS r.shipping_rate_id AS id, r.shipping_rate_name AS label,p.name as carrier, r.shipping_rate_name AS name
					  FROM #__plugins p
					  JOIN #__redshop_shipping_rate r ON r.shipping_class=p.element
					 WHERE p.published=1 AND p.folder="redshop_shipping"
					 ' . ( ! empty( $shipping_id ) ? ' AND s.shipping_rate_id IN (' . AC()->helper->scrubids( $shipping_id ) . ') ' : '' ) . '
					HAVING 1=1 ' . ( ! empty( $search ) ? ' AND label LIKE "%' . AC()->db->escape( trim( $search ), true ) . '%" ' : '' ) . ' 
					 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
					 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		$shippings = AC()->db->get_objectlist( $sql, 'id' );
		foreach ( $shippings as $k => $shipping ) {
			$shippings[ $k ]->name = $shipping->label;
		}
		return $shippings;
	}

	public function get_groups( $shoppergroup_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS shopper_group_id AS id,shopper_group_name AS label,shopper_group_name AS name 
				  FROM #__redshop_shopper_group
				 WHERE published=1
				 ' . ( ! empty( $shoppergroup_id ) ? ' AND shopper_group_id IN (' . AC()->helper->scrubids( $shoppergroup_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND shopper_group_name LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_group_ids( $user_id ) {
		return AC()->db->get_column( 'SELECT shopper_group_id FROM #__redshop_users_info WHERE user_id=' . (int) $user_id );
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
						IF(ru.lastname IS NULL,
								TRIM(SUBSTRING(TRIM(u.name),LENGTH(TRIM(u.name))-LOCATE(" ",REVERSE(TRIM(u.name)))+1)),
								ru.lastname) as lastname,
						IF(ru.firstname IS NULL,
								TRIM(REVERSE(SUBSTRING(REVERSE(TRIM(u.name)),LOCATE(" ",REVERSE(TRIM(u.name)))+1))),
								ru.firstname) as firstname
				  FROM #__users u
				  LEFT JOIN #__redshop_users_info ru ON ru.user_id=u.id AND ru.address_type="BT"
				 WHERE 1=1
				 ' . ( ! empty( $user_id ) ? ' AND u.id IN (' . AC()->helper->scrubids( $user_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(u.username," - ",u.name) LIKE "%' . AC()->db->escape( trim( $search ), true ) . '%" ' : '' ) . '
				 GROUP BY u.id
				 ORDER BY ' . ( empty( $orderby ) ? 'label,u.id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_countrys() {

		$country_id_name = version_compare( $this->version, '2.0.0', '>=' ) ? 'id' : 'country_id';
		return AC()->db->get_objectlist( '
			SELECT ' . $country_id_name . ' as id,' . $country_id_name . ' AS country_id,country_name
			  FROM `#__redshop_country`
			 ORDER BY country_name,' . $country_id_name . '
		', 'id' );
	}

	public function get_countrystates( $country_id = null ) {

		$state_id_name = version_compare( $this->version, '2.0.0', '>=' ) ? 'id' : 'state_id';
		return AC()->db->get_objectlist( '
			SELECT ' . $state_id_name . ' as id,state_name AS label,state_name AS name
			  FROM `#__redshop_state`
			 WHERE 1=1
			 ' . ( ! empty( $country_id ) ? ' AND`country_id`= "' . (int) $country_id . '" ' : '' ) . '
			 ORDER BY state_name,'.$state_id_name . '
		', 'id' );
	}

	public function get_paymentmethods() {

		if ( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
			$sql = 'SELECT extension_id AS id,name,element,folder
					  FROM #__extensions
					 WHERE type="plugin" AND folder="redshop_payment"
					 ORDER BY name,extension_id';
		}
		else {
			$sql = 'SELECT id,name,element,folder
					  FROM #__plugins
					 WHERE folder="redshop_payment"
					 ORDER BY name,id';
		}
		$rows = AC()->db->get_objectlist( $sql, 'id' );

		foreach ( $rows as $k => $row ) {
			JFactory::getLanguage()->load( 'plg_' . $row->folder . '_' . $row->element );
			JFactory::getLanguage()->load( 'plg_' . $row->folder . '_' . $row->element, JPATH_ROOT . '/plugins/' . $row->folder . '/' . $row->element );
			$rows[ $k ]->name = JText::_( $row->name );
		}

		return $rows;
	}

	public function get_order( $order_id ) {
		return AC()->db->get_object('
			SELECT o.*, o.order_id, o.order_number, o.order_total, i.order_item_currency AS order_currency
			  FROM #__redshop_orders o
			  LEFT JOIN #__redshop_order_item i ON i.order_id=o.order_id
			 WHERE o.order_id=' . (int) $order_id . '
			 GROUP BY o.order_id
		');
	}

	public function get_order_link( $order_id ) {
		return JRoute::_( 'iindex.php?option=com_redshop&view=order_detail&task=edit&cid[]=' . (int) $order_id );
	}

	public function sql_history_order( $where, $having, $orderby ) {
		$sql = 'SELECT c.id,c.id AS voucher_customer_id, c.codes, c.user_id, c.user_id AS _user_id, uv.user_email,
					 ov.order_id, CONCAT(ov.order_id," (",ov.order_number,")") AS order_number,
					 u.username AS _username, uv.firstname as _fname, uv.lastname as _lname,FROM_UNIXTIME(ov.cdate) AS _created_on,
					 GROUP_CONCAT(cc.code ORDER BY cc.code SEPARATOR ", ") as coupon_codes
				 FROM #__cmcoupon_voucher_customer c
				 LEFT JOIN #__cmcoupon_voucher_customer_code cc ON cc.voucher_customer_id=c.id
				 LEFT JOIN #__users u ON u.id=c.user_id
				 LEFT JOIN #__redshop_orders ov ON ov.order_id=c.order_id
				 LEFT JOIN #__redshop_order_users_info uv ON uv.order_id=c.order_id AND uv.address_type="BT"
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
					 uv.user_id,uv.firstname AS first_name,uv.lastname AS last_name,
					 o.order_id,SUM(au.total_product)+SUM(au.total_shipping) AS coupon_value_used,
					 c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance,au.user_email,
					 u.username as _username, uv.firstname as _fname, uv.lastname as _lname,FROM_UNIXTIME(o.cdate) AS _created_on,
					 o.order_number
				 FROM #__cmcoupon c
				 LEFT JOIN #__redshop_orders o ON o.order_id=c.order_id
				 LEFT JOIN #__redshop_users_info uv ON uv.user_id=o.user_id AND uv.address_type="BT"
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
					 uu.id as use_id,uv.firstname AS first_name,uv.lastname AS last_name,uu.user_id,
					 (uu.total_product+uu.total_shipping) AS discount,uu.productids,uu.timestamp,uu.user_email,
					 ov.order_id,ov.order_number,
					 u.username as _username, uv.firstname AS _fname, uv.lastname AS _lname,FROM_UNIXTIME(ov.cdate) AS _created_on,
					 uu.is_customer_balance,
					 (uu.total_curr_product+uu.total_curr_shipping) AS discount_in_currency, uu.currency_code
				 FROM #__cmcoupon_history uu
				 JOIN #__cmcoupon c ON c.id=uu.coupon_id
				 LEFT JOIN #__cmcoupon c2 ON c2.id=uu.coupon_entered_id
				 LEFT JOIN #__cmcoupon_tag t ON t.coupon_id=c.id
				 LEFT JOIN #__redshop_users_info uv ON uv.user_id=uu.user_id AND uv.address_type="BT"
				 LEFT JOIN #__users u ON u.id=uu.user_id
				 LEFT JOIN #__redshop_orders ov ON ov.order_id=uu.order_id
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
		$sql = 'SELECT g.*,p.product_name as _product_name,p.product_number AS product_sku,pr.title as profile, COUNT(pc.id) as codecount,c.coupon_code
				  FROM #__cmcoupon_giftcert_product g
				  LEFT JOIN #__cmcoupon c ON c.id=g.coupon_template_id
				  LEFT JOIN #__redshop_product p ON p.product_id=g.product_id
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
		$sql = 'SELECT p.product_name,p.product_number AS product_sku
				  FROM #__redshop_product p
				 WHERE p.product_id = ' . (int) $product_id;
		return $sql;
	}

	public function sql_giftcert_code( $where, $having, $orderby ) {
		$sql = 'SELECT g.*,p.product_name as _product_name,p.product_number AS product_sku
				  FROM #__cmcoupon_giftcert_code g
				  LEFT JOIN #__redshop_product p ON p.product_id=g.product_id
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

		$sql = 'SELECT gc.codes,gcc.product_id,p.product_name,
					 UV.user_id,uv.firstname AS first_name,uv.lastname AS last_name,u.username,uv.user_email AS email,
					 c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 o.order_id,FROM_UNIXTIME(o.cdate) AS created_on,gc.codes,					 
					 o.order_total,o.order_tax,o.order_shipping AS order_shipment,o.order_shipping_tax AS order_shipment_tax,o.order_discount*-1 AS order_fee,
					 o.order_subtotal

				 FROM #__cmcoupon_voucher_customer_code gcc
				 JOIN #__cmcoupon_voucher_customer gc ON gc.id=gcc.voucher_customer_id
				 LEFT JOIN #__cmcoupon c ON c.id=gcc.coupon_id AND c.estore=gc.estore
				 LEFT JOIN #__redshop_product as p ON p.product_id=gcc.product_id
				 LEFT JOIN #__redshop_orders o ON o.order_id=gc.order_id
				 LEFT JOIN #__redshop_users_info uv ON uv.user_id=o.user_id AND uv.address_type="BT"
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
					 uv.firstname AS first_name,uv.lastname AS last_name,uu.user_id,u.username,
					 (uu.total_product+uu.total_shipping) AS discount,uu.productids,uu.timestamp,
					 o.order_id,FROM_UNIXTIME(o.cdate) AS created_on,uu.id as num_uses_id,
					 o.order_total,o.order_tax,o.order_shipping AS order_shipment,o.order_shipping_tax AS order_shipment_tax,o.order_discount*-1 AS order_fee,
					 o.order_subtotal
				 FROM #__cmcoupon c
				 JOIN #__cmcoupon_history uu ON uu.coupon_id=c.id
				 LEFT JOIN #__cmcoupon c2 ON c2.id=uu.coupon_entered_id
				 JOIN #__redshop_users_info uv ON uv.user_id=uu.user_id
				 LEFT JOIN #__users u ON u.id=uu.user_id
				 LEFT JOIN #__redshop_orders o ON o.order_id=uu.order_id
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
					 uv.user_id,uv.firstname AS first_name,uv.lastname AS last_name,u.username,
					 o.order_total, FROM_UNIXTIME(o.cdate) AS created_on,gc.codes,o.order_subtotal,
					 o.order_tax,o.order_shipping AS order_shipment,o.order_shipping_tax AS order_shipment_tax,o.order_discount*-1 AS order_fee,
					 SUM(au.total_product)+SUM(au.total_shipping) AS coupon_value_used,
					 c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance,gcc.product_id,p.product_name
				 FROM #__cmcoupon c
				 
				 LEFT JOIN #__redshop_orders o ON o.order_id=c.order_id
				 LEFT JOIN #__redshop_users_info uv ON uv.user_id=o.user_id AND uv.address_type="BT"
				 LEFT JOIN #__users u ON u.id=o.user_id
				 LEFT JOIN #__cmcoupon_history au ON au.coupon_id=c.id
				 
				 LEFT JOIN #__cmcoupon_voucher_customer gc ON gc.order_id=o.order_id
				 LEFT JOIN #__cmcoupon_voucher_customer_code gcc ON gcc.voucher_customer_id=gc.id AND gcc.coupon_id=c.id

				 LEFT JOIN #__redshop_product p ON p.product_id=gcc.product_id
				 
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
				  JOIN #__redshop_orders o ON o.order_id=uu.order_id
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

		$sql = 'SELECT c.id,c.coupon_code, SUM(o.order_total) as total, COUNT(uu.order_id) as count,u.country_code as country,u.state_code as state,u.city,
					 CONCAT(c.id,"-",IF(ISNULL(u.country_code),"0",u.country_code),"-",IF(ISNULL(u.state_code),"0",u.state_code),"-",IF(ISNULL(u.city),"0",u.city)) as realid
				  FROM #__cmcoupon c
				  JOIN (SELECT coupon_entered_id,order_id FROM #__cmcoupon_history GROUP BY order_id,coupon_entered_id) uu ON uu.coupon_entered_id=c.id

				  JOIN #__redshop_orders o ON o.order_id=uu.order_id
				  JOIN #__redshop_order_users_info u ON u.order_id=o.order_id AND u.address_type="BT"
				  
				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id,u.country_code,u.state_code,u.city';
		$order_details = AC()->db->get_objectlist( $sql, 'realid' );

		$sql = 'SELECT c.id,c.coupon_code, uu.productids,
						SUM(uu.total_product+uu.total_shipping) as discount,
						u.country_code as country,u.state_code as state,u.city
				  FROM #__cmcoupon_history uu
				  JOIN #__cmcoupon c ON c.id=uu.coupon_entered_id 
				  
				  JOIN #__redshop_orders o ON o.order_id=uu.order_id
				  JOIN #__redshop_order_users_info u ON u.order_id=o.order_id AND u.address_type="BT"

				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id,u.country_code,u.state_code,u.city
				 ORDER BY c.coupon_code';

		return (object) array(
			'sql' => $sql,
			'order_details' => $order_details,
		);
	}

	public function get_order_status() {
		return AC()->db->get_objectlist( 'SELECT order_status_id, order_status_code, order_status_name FROM #__redshop_order_status WHERE published=1' );
	}

	public function add_coupon_to_cart( $coupon_code ) {

		$cart = JFactory::getSession()->get( 'cart' );
		if ( empty( $cart['idx'] ) ) {
			return;
		}
		
		if ( ! class_exists( 'rsCarthelper' ) ) {
			if ( version_compare( $this->version, '2.0.0', '>=' ) ) {
				JLoader::import( 'redshop.library' );
				require JPATH_ROOT . '/components/com_redshop/helpers/rscarthelper.php';
			}
			else {
				require_once JPATH_ADMINISTRATOR . '/components/com_redshop/helpers/redshop.cfg.php';
				require_once JPATH_ROOT . '/components/com_redshop/helpers/cart.php';
			}
		}

		AC()->helper->set_request( 'discount_code', $coupon_code );
		$carthelper = new rsCarthelper;
		$carthelper->coupon();
		$carthelper->cartFinalCalculation();
		return true;
	}

	public function get_name() {
		return version_compare( $this->version, '2.0.0', '>=' ) ? Redshop::getConfig()->get( 'SHOP_NAME' ) : SHOP_NAME;
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
		return 'index.php?option=com_redshop';
	}

	public function get_itemsperpage() {
		return JFactory::getApplication()->getCfg( 'list_limit' );
	}
}

