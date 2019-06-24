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

class Cmcoupon_Helper_Estore_Hikashop_Helper {

	var $estore = 'hikashop';

	public function is_installed() {
		return file_exists( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop.xml' ) || file_exists( JPATH_ADMINISTRATOR . '/components/com_hikashop/hikashop_j3.xml' ) ? true : false;
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

		$sql = 'SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,
						IFNULL( CONCAT(CONVERT(c.product_name USING utf8), ": ", CONVERT(b.product_name USING utf8)," (",b.product_code,")"), CONCAT(CONVERT(b.product_name USING utf8)," (",b.product_code,")") ) AS asset_name,
						a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__hikashop_product b ON b.product_id=a.asset_id
				  LEFT JOIN #__hikashop_product c ON c.product_id=b.product_parent_id
				 WHERE a.asset_type="product" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.category_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__hikashop_category b ON b.category_id=a.asset_id AND b.category_type="product"
				 WHERE a.asset_type="category" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.shipping_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__hikashop_shipping b ON b.shipping_id=a.asset_id
				 WHERE a.asset_type="shipping" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.coupon_code USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__cmcoupon b ON b.id=a.asset_id
				 WHERE a.asset_type="coupon" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.category_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__hikashop_category b ON b.category_id=a.asset_id AND b.category_type="manufacturer"
				 WHERE a.asset_type="manufacturer" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__users b ON b.id=a.asset_id
				 WHERE a.asset_type="user" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
			' . ( version_compare( JVERSION, '1.6.0', '>=' ) ? '
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.title USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__usergroups b ON b.id=a.asset_id
				 WHERE a.asset_type="usergroup" AND a.coupon_id IN (' . $coupon_ids . ')
			' : '
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__core_acl_aro_groups b ON b.id=a.asset_id
				 WHERE a.asset_type="usergroup" AND a.coupon_id IN (' . $coupon_ids . ')
			' ) . '
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.payment_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__hikashop_payment b ON b.payment_id=a.asset_id
				 WHERE a.asset_type="paymentmethod" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.zone_name_english USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__hikashop_zone b ON b.zone_id=a.asset_id
				 WHERE a.asset_type="country" AND a.coupon_id IN (' . $coupon_ids . ') AND b.zone_type="country"
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.zone_name_english USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__hikashop_zone b ON b.zone_id=a.asset_id
				  JOIN #__hikashop_zone_link l ON l.zone_child_namekey=b.zone_namekey
				  JOIN #__hikashop_zone c ON c.zone_namekey=l.zone_parent_namekey
				 WHERE a.asset_type="countrystate" AND a.coupon_id IN (' . $coupon_ids . ') AND b.zone_type="state"
				 
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
					p.product_id AS id,
					IFNULL( CONCAT(parent.product_name, ": ", p.product_name," (",p.product_code,")"), CONCAT(p.product_name," (",p.product_code,")") ) AS label,
					p.product_code as sku,
					p.product_name
				  FROM #__hikashop_product p
				  LEFT JOIN #__hikashop_product parent ON parent.product_id=p.product_parent_id
				  LEFT JOIN #__cmcoupon_giftcert_product g ON g.product_id=p.product_id
				 WHERE p.product_type IN ( "main", "variant" )
				 ' . ( $is_published ? ' AND p.product_published=1 ' : '' ) . '
				 ' . ( $is_notgift ? ' AND g.product_id IS NULL ' : '' ) . '
				 ' . ( ! empty( $product_id ) ? ' AND p.product_id IN (' . AC()->helper->scrubids( $product_id ) . ') ' : '' ) . '
				 HAVING 1=1
				 ' . ( ! empty( $search ) ? ' AND label LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
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

		$sql = 'SELECT SQL_CALC_FOUND_ROWS c.category_id AS id,c.category_name AS label
				  FROM #__hikashop_category c
				 WHERE c.category_type="product"
				 ' . ( ! $display_unpublished ? ' AND c.category_published=1 ' : '' ) . '
				 ' . ( ! empty( $category_id ) ? ' AND c.category_id IN (' . AC()->helper->scrubids( $category_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND c.category_name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	private function category_tree( $selected_categories = array(), $cid = 0, $level = 0, $disabled_fields = array() ) {
		static $category_tree_output = array();

		$cid = (int) $cid;
		if ( empty( $cid ) ) {
			$cid = 1;
		}

		$level++;

		$display_unpublished = ( (int) AC()->param->get( 'display_category_unpublished', 0 ) ) == 1 ? true : false;

		$sql = 'SELECT c.`category_id`, c.`category_description`, c.`category_name`, c.`category_ordering`, c.`category_published`, c.category_id AS category_child_id, c.category_parent_id 
				  FROM #__hikashop_category c
				 WHERE c.category_type="product"
				   AND c.`category_parent_id` = ' . (int)$cid . '
				 ' . ( ! $display_unpublished ? ' AND c.category_published=1 ' : '' ) . '
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

				$test = (int) AC()->db->get_value( 'SELECT category_id FROM #__hikashop_category WHERE category_type="product" AND category_parent_id=' . (int) $child_id );
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

		$sql = 'SELECT SQL_CALC_FOUND_ROWS category_id AS id,category_name AS label, category_name AS name
				  FROM #__hikashop_category
				 WHERE category_published=1 AND category_type="manufacturer"
				 ' . ( ! empty( $manu_id ) ? ' AND category_id IN (' . AC()->helper->scrubids( $manu_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND category_name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
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

		$sql = 'SELECT SQL_CALC_FOUND_ROWS s.shipping_id AS id, s.shipping_name AS label, s.shipping_name AS name, s.shipping_type as carrier
				  FROM #__hikashop_shipping s
				 WHERE s.shipping_published=1
				 ' . ( ! empty( $shipping_id ) ? ' AND s.shipping_id IN (' . AC()->helper->scrubids( $shipping_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND s.shipping_name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
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

		if(version_compare( JVERSION, '1.6.0', 'ge' )) {
			$sql = 'SELECT SQL_CALC_FOUND_ROWS id AS id,title AS label, title as name
					  FROM #__usergroups
					 WHERE 1=1
					 ' . ( ! empty( $shoppergroup_id ) ? ' AND id IN (' . AC()->helper->scrubids( $shoppergroup_id ) . ') ' : '' ) . '
					 ' . ( ! empty( $search ) ? ' AND title LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
					 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
					 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );
		}
		else {
			$sql = 'SELECT SQL_CALC_FOUND_ROWS id AS id,name AS label,name AS name
					  FROM #__core_acl_aro_groups
					 WHERE 1=1
					 ' . ( ! empty( $shoppergroup_id ) ? ' AND id IN (' . AC()->helper->scrubids( $shoppergroup_id ) . ') ' : '' ) . '
					 ' . ( ! empty( $search ) ? ' AND name LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
					 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
					 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );
		}
		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_group_ids( $user_id ) {
		if ( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
			$sql = 'SELECT group_id FROM #__user_usergroup_map WHERE user_id=' . (int) $user_id;
		}
		else {
			$sql = 'SELECT m.group_id 
					  FROM #__core_acl_aro a
					  JOIN #__core_acl_groups_aro_map m ON m.aro_id=a.id
					 WHERE a.section_value="users" AND a.value=' . (int) $user_id;
		}
		return AC()->db->get_column( $sql );
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
						IF(ha.address_lastname IS NULL,
								TRIM(SUBSTRING(TRIM(u.name),LENGTH(TRIM(u.name))-LOCATE(" ",REVERSE(TRIM(u.name)))+1)),
								ha.address_lastname) as lastname,
						IF(ha.address_firstname IS NULL,
								TRIM(REVERSE(SUBSTRING(REVERSE(TRIM(u.name)),LOCATE(" ",REVERSE(TRIM(u.name)))+1))),
								ha.address_firstname) as firstname
				  FROM #__users u
				  LEFT JOIN #__hikashop_user hu ON hu.user_cms_id=u.id
				  LEFT JOIN #__hikashop_address ha ON ha.address_user_id=hu.user_id AND ha.address_published=1 AND ha.address_default=1
				 WHERE 1=1
				 ' . ( ! empty( $user_id ) ? ' AND u.id IN (' . AC()->helper->scrubids( $user_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(u.username," - ",u.name) LIKE "%' . AC()->db->escape( trim( $search ), true ) . '%" ' : '' ) . '
				 GROUP BY u.id
				 ORDER BY ' . ( empty( $orderby ) ? 'label,u.id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );
		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_countrys() {
		$sql = 'SELECT zone_id as id,zone_id AS country_id,zone_name_english AS country_name
				  FROM #__hikashop_zone
				 WHERE zone_type="country"
				 ORDER BY zone_name_english,zone_id';

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_countrystates( $country_id = null ) {
		$sql = 'SELECT z.zone_id as id,z.zone_name_english AS label
				  FROM #__hikashop_zone z
				  LEFT JOIN #__hikashop_zone_link l ON l.zone_child_namekey=z.zone_namekey
				  LEFT JOIN #__hikashop_zone c ON c.zone_namekey=l.zone_parent_namekey
				 WHERE z.zone_type="state"
				 ' . ( ! empty( $country_id ) ? ' AND c.zone_id="' . $country_id . '" ' : '' ) . '
				 ORDER BY z.zone_name_english,z.zone_id';

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_paymentmethods() {
		$sql = 'SELECT payment_id as id,payment_name AS label,payment_name AS name
				  FROM #__hikashop_payment
				 ORDER BY payment_name,payment_id';

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_order( $order_id ) {
		return AC()->db->get_object( '
			SELECT *, order_id, order_number, order_full_price AS order_total, order_full_price AS order_currency
			  FROM #__hikashop_order
			 WHERE order_id=' . (int) $order_id . '
		' );
	}

	public function get_order_link( $order_id ) {
		return JRoute::_( 'index.php?option=com_hikashop&ctrl=order&task=edit&cid[]=' . (int) $order_id );
	}

	public function sql_history_order( $where, $having, $orderby ) {
		$sql = 'SELECT c.id,c.id AS voucher_customer_id, c.codes, uv.address_firstname AS first_name,uv.address_lastname AS last_name,
					c.user_id, c.user_id as _user_id, u.username, uh.user_email,
					 ov.order_id,FROM_UNIXTIME(ov.order_created) AS cdate,CONCAT(ov.order_id," (",ov.order_number,")") AS order_number,
					 u.username as _username, uv.address_firstname as _fname, uv.address_lastname as _lname,FROM_UNIXTIME(ov.order_created) AS _created_on,
					 GROUP_CONCAT(cc.code ORDER BY cc.code SEPARATOR ", ") as coupon_codes		
				 FROM #__cmcoupon_voucher_customer c
				 LEFT JOIN #__cmcoupon_voucher_customer_code cc ON cc.voucher_customer_id=c.id
				 LEFT JOIN #__users u ON u.id=c.user_id
				 LEFT JOIN #__hikashop_user uh ON uh.user_cms_id=u.id
				 LEFT JOIN #__hikashop_order ov ON ov.order_id=c.order_id
				 LEFT JOIN #__hikashop_address uv ON uv.address_id=ov.order_billing_address_id
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
					 u.id AS user_id,uv.address_firstname AS first_name,uv.address_lastname AS last_name,u.username,
					 o.order_id,o.order_created AS cdate,
					 SUM(au.total_product)+SUM(au.total_shipping) AS coupon_value_used,
					 c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance,au.user_email,
					 u.username as _username, uv.address_firstname as _fname, uv.address_lastname as _lname,
					 FROM_UNIXTIME(o.order_created) AS _created_on,CONCAT(o.order_id," (",o.order_number,")") AS order_number
				 FROM #__cmcoupon c
				 LEFT JOIN #__hikashop_order o ON o.order_id=c.order_id
				 LEFT JOIN #__hikashop_address uv ON uv.address_id=o.order_billing_address_id
				 LEFT JOIN #__hikashop_user uu ON uu.user_id=o.order_user_id
				 LEFT JOIN #__users u ON u.id=uu.user_cms_id
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
					 uu.id as use_id,uv.address_firstname AS first_name,uv.address_lastname AS last_name,uu.user_id,u.username,
					 (uu.total_product+uu.total_shipping) AS discount,uu.productids,uu.timestamp,uu.user_email,
					 ov.order_id,FROM_UNIXTIME(ov.order_created) AS cdate,CONCAT(ov.order_id," (",ov.order_number,")") AS order_number,
					 u.username as _username, uv.address_firstname as _fname, uv.address_lastname as _lname,FROM_UNIXTIME(ov.order_created) AS _created_on,
					 uu.is_customer_balance,
					 (uu.total_curr_product+uu.total_curr_shipping) AS discount_in_currency, uu.currency_code
				 FROM #__cmcoupon_history uu
				 JOIN #__cmcoupon c ON c.id=uu.coupon_id
				 LEFT JOIN #__cmcoupon c2 ON c2.id=uu.coupon_entered_id
				 LEFT JOIN #__cmcoupon_tag t ON t.coupon_id=c.id
				 LEFT JOIN #__users u ON u.id=uu.user_id
				 LEFT JOIN #__hikashop_user uh ON uh.user_cms_id=u.id
				 LEFT JOIN #__hikashop_order ov ON ov.order_id=uu.order_id
				 LEFT JOIN #__hikashop_address uv ON uv.address_id=ov.order_billing_address_id
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
		$sql = 'SELECT g.*,p.product_name as _product_name,p.product_code AS product_sku,pr.title as profile, COUNT(pc.id) as codecount,c.coupon_code
				  FROM #__cmcoupon_giftcert_product g
				  LEFT JOIN #__cmcoupon c ON c.id=g.coupon_template_id
				  LEFT JOIN #__hikashop_product p ON p.product_id=g.product_id
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
		return 'SELECT p.product_name,p.product_code AS product_sku FROM #__hikashop_product p WHERE p.product_id = ' . $product_id;
	}

	public function sql_giftcert_code( $where, $having, $orderby ) {
		$sql = 'SELECT g.*,p.product_name as _product_name,p.product_code AS product_sku
				  FROM #__cmcoupon_giftcert_code g
				  LEFT JOIN #__hikashop_product p ON p.product_id=g.product_id
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
			$datestr = ' AND o.order_created BETWEEN ' . strtotime( $start_date ) . ' AND ' . ( strtotime( $end_date ) + ( 3600 * 24 ) - 1 ) . ' ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.order_created >= ' . strtotime( $start_date ) . ' ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.order_created <= ' . ( strtotime( $end_date ) + ( 3600 * 24 ) - 1 ) . ' ';
		}

		$sql = 'SELECT gc.codes,gcc.product_id,p.product_name,
					 uh.user_cms_id AS user_id,uv.address_firstname AS first_name,uv.address_lastname AS last_name,u.username,u.email,
					 o.order_id,o.order_full_price AS order_total,o.order_created AS ocdate,gc.codes,
					 (o.order_full_price-o.order_shipping_price) AS order_subtotal,0 AS order_tax,o.order_shipping_price  AS order_shipment,
					 o.order_shipping_tax AS order_shipment_tax,o.order_discount_price*-1 AS order_fee,
					 c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state, FROM_UNIXTIME(o.order_created) AS created_on

				 FROM #__cmcoupon_voucher_customer_code gcc
				 JOIN #__cmcoupon_voucher_customer gc ON gc.id=gcc.voucher_customer_id
				 LEFT JOIN #__cmcoupon c ON c.id=gcc.coupon_id
				 LEFT JOIN #__hikashop_product p ON p.product_id=gcc.product_id
				 LEFT JOIN #__hikashop_order o ON o.order_id=gc.order_id
				 LEFT JOIN #__hikashop_address uv ON uv.address_id=o.order_billing_address_id
				 LEFT JOIN #__hikashop_user uh ON uh.user_id=o.order_user_id

				 LEFT JOIN #__users u ON u.id=uh.user_cms_id
				WHERE c.estore=gc.estore AND gc.estore="' . $this->estore . '"
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
			$datestr = ' AND o.order_created BETWEEN ' . strtotime( $start_date ) . ' AND ' . ( strtotime( $end_date ) + ( 3600 * 24 ) - 1 ) . ' ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.order_created >= ' . strtotime( $start_date ) . ' ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.order_created <= ' . ( strtotime( $end_date ) + ( 3600 * 24 ) - 1 ) . ' ';
		}

		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 uu.coupon_id,uu.coupon_entered_id,c2.coupon_code as coupon_entered_code,
					 uv.address_firstname AS first_name,uv.address_lastname AS last_name,uu.user_id,u.username,
					 (uu.total_product+uu.total_shipping) AS discount,uu.productids,uu.timestamp,
					 ov.order_id,FROM_UNIXTIME(ov.order_created) AS created_on,ov.order_full_price AS order_total,ov.order_created AS cdate,uu.id as num_uses_id,
					 (ov.order_full_price-ov.order_shipping_price) AS order_subtotal,0 AS order_tax,
					 ov.order_shipping_price AS order_shipment,ov.order_shipping_tax AS order_shipment_tax,
					 ov.order_discount_price*-1 AS order_fee
				 FROM #__cmcoupon c
				 JOIN #__cmcoupon_history uu ON uu.coupon_id=c.id
				 LEFT JOIN #__cmcoupon c2 ON c2.id=uu.coupon_entered_id
				 
				 LEFT JOIN #__hikashop_order ov ON ov.order_id=uu.order_id
				 LEFT JOIN #__users u ON u.id=uu.user_id
				 LEFT JOIN #__hikashop_user uh ON uh.user_cms_id=u.id
				 LEFT JOIN #__hikashop_address uv ON uv.address_id=ov.order_billing_address_id

				WHERE c.estore="' . $this->estore . '"
				 ' . $datestr . '
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND ov.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY uu.id
				 ORDER BY u.username
		';
		return $sql;
	}

	public function rpt_history_uses_giftcerts( $start_date, $end_date, $order_status, $published, $giftcert_product ) {
		$datestr = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$datestr = ' AND o.order_created BETWEEN ' . strtotime( $start_date ) . ' AND ' . ( strtotime( $end_date ) + ( 3600 * 24 ) - 1 ) . ' ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.order_created >= ' . strtotime( $start_date ) . ' ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.order_created <= ' . ( strtotime( $end_date ) + ( 3600 * 24 ) - 1 ) . ' ';
		}

		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 u.id as user_id,uv.address_firstname AS first_name,uv.address_lastname AS last_name,u.username,
					 o.order_full_price AS order_total,FROM_UNIXTIME(o.order_created) AS created_on,gc.codes,
					 (o.order_full_price-o.order_shipping_price) AS order_subtotal,0 AS order_tax,
					 o.order_shipping_price AS order_shipment,o.order_shipping_tax AS order_shipment_tax,o.order_discount_price*-1 AS order_fee,
					 SUM(au.total_product)+SUM(au.total_shipping) AS coupon_value_used,
					 c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance,gcc.product_id,p.product_name
				 FROM #__cmcoupon c
				 LEFT JOIN #__hikashop_order o ON o.order_id=c.order_id
				 LEFT JOIN #__hikashop_address uv ON uv.address_id=o.order_billing_address_id
				 LEFT JOIN #__hikashop_user uh ON uh.user_id=o.order_user_id
				 LEFT JOIN #__users u ON u.id=uh.user_cms_id
				 LEFT JOIN #__cmcoupon_history au ON au.coupon_id=c.id
				 
				 LEFT JOIN #__cmcoupon_voucher_customer gc ON gc.order_id=o.order_id
				 LEFT JOIN #__cmcoupon_voucher_customer_code gcc ON gcc.voucher_customer_id=gc.id AND gcc.coupon_id=c.id

				 LEFT JOIN #__hikashop_product p ON p.product_id=gcc.product_id
				 
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
			$datestr = ' AND o.order_created BETWEEN ' . strtotime( $start_date ) . ' AND ' . ( strtotime( $end_date ) + ( 3600 * 24 ) - 1 ) . ' ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.order_created >= ' . strtotime( $start_date ) . ' ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.order_created <= ' . ( strtotime( $end_date ) + ( 3600 * 24 ) - 1 ) . ' ';
		}

		$sql = 'SELECT c.id, SUM(o.order_full_price) as total, COUNT(c.id) as count
				  FROM #__cmcoupon c
				  JOIN (SELECT coupon_entered_id,order_id FROM #__cmcoupon_history GROUP BY order_id,coupon_entered_id) uu ON uu.coupon_entered_id=c.id
				  JOIN #__hikashop_order o ON o.order_id=uu.order_id
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
			$datestr = ' AND o.order_created BETWEEN ' . strtotime( $start_date ) . ' AND ' . ( strtotime( $end_date ) + ( 3600 * 24 ) - 1 ) . ' ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.order_created >= ' . strtotime( $start_date ) . ' ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.order_created <= ' . ( strtotime( $end_date ) + ( 3600 * 24 ) - 1 ) . ' ';
		}

		$sql = 'SELECT c.id,c.coupon_code, SUM(o.order_full_price) as total, COUNT(uu.order_id) as count,uv.address_country as country,uv.address_state as state,uv.address_city AS city,
					 CONCAT(c.id,"-",IF(ISNULL(zc.zone_name),"0",zc.zone_name),"-",IF(ISNULL(zs.zone_name),"0",zs.zone_name),"-",uv.address_city) as realid
				  FROM #__cmcoupon c
				  JOIN (SELECT coupon_entered_id,order_id FROM #__cmcoupon_history GROUP BY order_id,coupon_entered_id) uu ON uu.coupon_entered_id=c.id
				  JOIN #__hikashop_order o ON o.order_id=uu.order_id
				  JOIN #__hikashop_address uv ON uv.address_id=o.order_billing_address_id
				  LEFT JOIN #__hikashop_zone zc ON zc.zone_namekey=uv.address_country
				  LEFT JOIN #__hikashop_zone zs ON zs.zone_namekey=uv.address_state
				  
				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id,uv.address_country,uv.address_state,uv.address_city';
		$order_details = AC()->db->get_objectlist( $sql, 'realid' );

		$sql = 'SELECT c.id,c.coupon_code, uu.productids,
						SUM(uu.total_product+uu.total_shipping) as discount,
						zc.zone_name as country,zs.zone_name as state,uv.address_city AS city
				  FROM #__cmcoupon_history uu
				  JOIN #__cmcoupon c ON c.id=uu.coupon_entered_id 
				  JOIN #__hikashop_order o ON o.order_id=uu.order_id
				  JOIN #__hikashop_address uv ON uv.address_id=o.order_billing_address_id
				  LEFT JOIN #__hikashop_zone zc ON zc.zone_namekey=uv.address_country
				  LEFT JOIN #__hikashop_zone zs ON zs.zone_namekey=uv.address_state

				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id,uv.address_country,uv.address_state,uv.address_city
				 ORDER BY c.coupon_code';

		return (object) array(
			'sql' => $sql,
			'order_details' => $order_details,
		);
	}

	public function get_order_status() {
		$sql = 'SELECT category_id AS order_status_id, category_namekey AS order_status_code, category_name AS order_status_name
				  FROM #__hikashop_category
				 WHERE category_type="status" 
				 ORDER BY category_ordering';
		return AC()->db->get_objectlist( $sql );
	}

	public function add_coupon_to_cart( $coupon_code ) {
		if ( JFactory::getApplication()->isAdmin() ) {
			return; 
		}

		if ( ! defined( 'DS' ) ) {
			define( 'DS', DIRECTORY_SEPARATOR );
		}
		if ( ! class_exists( 'hikashop' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_hikashop/helpers/helper.php';
		}
		$cartClass = hikashop_get( 'class.cart' );
		$cart = $cartClass->loadFullCart( true );
		if ( empty( $cart->products ) ) {
			return;
		}

		$qty = 1;
		$cartClass->update( $coupon_code, $qty, 0, 'coupon' );
		return true;
	}

	public function get_name() {
		return JFactory::getConfig()->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'}( 'sitename' );
	}

	public function get_email() {
		return hikashop_config()->get( 'from_email', JFactory::getConfig()->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'}( 'mailfrom' ) );
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
		return 'index.php?option=com_hikashop';
	}

	public function get_itemsperpage() {
		return JFactory::getApplication()->getCfg( 'list_limit' );
	}
}

