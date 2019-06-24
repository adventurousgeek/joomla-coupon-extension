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

class Cmcoupon_Helper_Estore_Virtuemart_Helper {

	var $estore = 'virtuemart';

	public function is_installed() {
		if ( file_exists( JPATH_ADMINISTRATOR . '/components/com_virtuemart/virtuemart.xml' ) ) {
			$parser = simplexml_load_file( JPATH_ADMINISTRATOR . '/components/com_virtuemart/virtuemart.xml' );
			$version = (string) $parser->version;
			if ( version_compare( $version, '2.0.0', '>=' ) ) {
				return true;
			}
		}
		return false;
	}

	public function get_vm_lang() {
		static $_language;
		if ( ! empty( $_language ) ) {
			return $_language;
		}
		if ( ! defined( 'VMLANG' ) ) {
			if ( ! class_exists( 'VmConfig' ) ) {
				require(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php');
			}
			VmConfig::loadConfig();
		}
		$vmlang = strtolower( strtr( VmConfig::get( 'vmDefLang', '' ), '-', '_' ) );
		if ( ! empty( $vmlang ) ) {
			$_language = $vmlang;
		}
		else {
			$_language = VMLANG;
		}
		return $_language;
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
		$lang = $this->get_vm_lang();

		$sql = 'SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(c.product_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__virtuemart_products b ON b.virtuemart_product_id=a.asset_id
				  JOIN #__virtuemart_products_'.$lang.' c USING (virtuemart_product_id)
				 WHERE a.asset_type="product" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(c.category_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__virtuemart_categories b ON b.virtuemart_category_id=a.asset_id
				  JOIN #__virtuemart_categories_'.$lang.' c USING (virtuemart_category_id)
				 WHERE a.asset_type="category" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(c.mf_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__virtuemart_manufacturers b ON b.virtuemart_manufacturer_id=a.asset_id
				  JOIN #__virtuemart_manufacturers_'.$lang.' c USING (virtuemart_manufacturer_id)
				 WHERE a.asset_type="manufacturer" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(c.vendor_store_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__virtuemart_vendors b ON b.virtuemart_vendor_id=a.asset_id
				  JOIN #__virtuemart_vendors_'.$lang.' c USING (virtuemart_vendor_id)
				 WHERE a.asset_type="vendor" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(c.shipment_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__virtuemart_shipmentmethods b ON b.virtuemart_shipmentmethod_id=a.asset_id
				  JOIN #__virtuemart_shipmentmethods_'.$lang.' c USING (virtuemart_shipmentmethod_id)
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
				  JOIN #__virtuemart_shoppergroups b ON b.virtuemart_shoppergroup_id=a.asset_id
				 WHERE a.asset_type="usergroup" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.payment_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__virtuemart_paymentmethods_'.$lang.' b ON b.virtuemart_paymentmethod_id=a.asset_id
				 WHERE a.asset_type="paymentmethod" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.country_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__virtuemart_countries b ON b.virtuemart_country_id=a.asset_id
				 WHERE a.asset_type="country" AND a.coupon_id IN (' . $coupon_ids . ')
								UNION
				SELECT a.coupon_id,a.asset_id,a.asset_key,a.asset_type,a.qty,CONVERT(b.state_name USING utf8) AS asset_name,a.order_by
				  FROM #__cmcoupon_asset a
				  JOIN #__virtuemart_states b ON b.virtuemart_state_id=a.asset_id
				  JOIN #__virtuemart_countries c ON c.virtuemart_country_id=b.virtuemart_country_id
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
					p.virtuemart_product_id AS id,CONCAT(lang.product_name," (",p.product_sku,")") AS label,p.product_sku as sku,lang.product_name
				  FROM #__virtuemart_products p
				  JOIN `#__virtuemart_products_' . $this->get_vm_lang() . '` as lang using (`virtuemart_product_id`)
				  LEFT JOIN #__cmcoupon_giftcert_product g ON g.product_id=p.virtuemart_product_id
				 WHERE 1=1
				 ' . ( $is_published ? ' AND p.published=1 ' : '' ) . '
				 ' . ( $is_notgift ? ' AND g.product_id IS NULL ' : '' ) . '
				 ' . ( ! empty( $product_id ) ? ' AND p.virtuemart_product_id IN (' . AC()->helper->scrubids( $product_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(lang.product_name," (",p.product_sku,")") LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,sku' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_categorys( $category_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {
		if ( empty( $category_id ) && empty( $search ) && empty( $limit ) ) {
			return $this->category_tree();
		}

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}
		$display_unpublished = ( (int) AC()->param->get( 'display_category_unpublished', 0 ) ) == 1 ? true : false;

		$sql = 'SELECT SQL_CALC_FOUND_ROWS c.virtuemart_category_id AS id,lang.category_name AS label
				  FROM #__virtuemart_categories c
				  JOIN `#__virtuemart_categories_' . $this->get_vm_lang() . '` as lang using (`virtuemart_category_id`)
				 WHERE 1=1
				 ' . ( ! $display_unpublished ? ' AND c.published=1 ' : '' ) . '
				 ' . ( ! empty( $category_id ) ? ' AND c.virtuemart_category_id IN (' . AC()->helper->scrubids( $category_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND lang.category_name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		return AC()->db->get_objectlist( $sql, 'id' );
	}

	private function category_tree( $selected_categories = array(), $cid = 0, $level = 0, $disabled_fields = array() ) {
		static $category_tree_output = array();

		$cid = (int) $cid;

		$level++;

		$display_unpublished = ( (int) AC()->param->get( 'display_category_unpublished', 0 ) ) == 1 ? true : false;

		$sql = 'SELECT c.virtuemart_category_id as category_id, l.category_name,cx.category_child_id, cx.category_parent_id
				  FROM `#__virtuemart_categories_' . $this->get_vm_lang() . '` l
				  JOIN `#__virtuemart_categories` AS c using (`virtuemart_category_id`)
				  LEFT JOIN `#__virtuemart_category_categories` AS cx ON l.`virtuemart_category_id` = cx.`category_child_id`
				  WHERE cx.category_parent_id = ' . (int) $cid . '
				 ' . ( ! $display_unpublished ? ' AND c.published=1 ' : '' ) . '
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

				$test = (int) AC()->db->get_value( 'SELECT category_child_id FROM #__virtuemart_category_categories WHERE category_parent_id=' . (int) $child_id );
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

		$sql = 'SELECT SQL_CALC_FOUND_ROWS m.virtuemart_manufacturer_id AS id,lang.mf_name AS label, lang.mf_name AS name
				  FROM #__virtuemart_manufacturers m
				  JOIN `#__virtuemart_manufacturers_' . $this->get_vm_lang() . '` as lang using (`virtuemart_manufacturer_id`)
				 WHERE m.published=1
				 ' . ( ! empty( $manu_id ) ? ' AND m.virtuemart_manufacturer_id IN (' . AC()->helper->scrubids( $manu_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND lang.mf_name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
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

		$sql = 'SELECT SQL_CALC_FOUND_ROWS v.virtuemart_vendor_id AS id,lang.vendor_store_name AS label,lang.vendor_store_name AS name
				  FROM #__virtuemart_vendors v
				  JOIN `#__virtuemart_vendors_' . $this->get_vm_lang() . '` as lang using (`virtuemart_vendor_id`)
				 WHERE 1=1
				 ' . ( ! empty( $vendor_id ) ? ' AND v.virtuemart_vendor_id IN (' . AC()->helper->scrubids( $vendor_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND lang.vendor_store_name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
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

		$sql = 'SELECT SQL_CALC_FOUND_ROWS s.virtuemart_shipmentmethod_id AS id, lang.shipment_name AS label,lang.shipment_name AS name,p.name as carrier
				  FROM #__virtuemart_shipmentmethods s
				  JOIN `#__virtuemart_shipmentmethods_' . $this->get_vm_lang() . '` as lang using (`virtuemart_shipmentmethod_id`)
				  '.(version_compare( JVERSION, '1.6.0', 'ge' )
						? 'LEFT JOIN #__extensions p ON p.extension_id=s.shipment_jplugin_id'
						: 'LEFT JOIN #__plugins p ON p.id=s.shipment_jplugin_id').'
				 WHERE s.published=1 
				 ' . ( ! empty( $shipping_id ) ? ' AND s.virtuemart_shipmentmethod_id IN (' . AC()->helper->scrubids( $shipping_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND lang.shipment_name LIKE "%' . AC()->db->escape( $search, true ) . '%" ' : '' ) . ' 
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

		$sql = 'SELECT SQL_CALC_FOUND_ROWS virtuemart_shoppergroup_id AS id,shopper_group_name AS label,shopper_group_name AS name 
				  FROM #__virtuemart_shoppergroups
				 WHERE 1=1
				 ' . ( ! empty( $shoppergroup_id ) ? ' AND virtuemart_shoppergroup_id IN (' . AC()->helper->scrubids( $shoppergroup_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND shopper_group_name LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
				 ORDER BY ' . ( empty( $orderby ) ? 'label,id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_group_ids( $user_id ) {
        if ( empty( $user_id ) ) {
			return array(1);
		}
		$shopper_group_ids = AC()->db->get_column( 'SELECT virtuemart_shoppergroup_id FROM #__virtuemart_vmuser_shoppergroups WHERE virtuemart_user_id=' . (int) $user_id );
		if ( empty( $shopper_group_ids ) ) {
			$shopper_group_ids = array ( (int) AC()->db->get_value( 'SELECT virtuemart_shoppergroup_id FROM #__virtuemart_shoppergroups WHERE published=1 AND `default`=1' ) );
		}
		return $shopper_group_ids;
	}

	public function get_users( $user_id = null, $search = null, $limit = null, $limitstart = null, $orderby = null, $orderbydir = null ) {

		$limit = (int) $limit;
		$limitstart = (int) $limitstart;
		if ( ! empty( $orderbydir ) && strtolower( $orderbydir ) != 'asc' && strtolower( $orderbydir ) != 'desc' ) {
			$orderbydir = '';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS
						u.id,CONCAT(u.username," - ",u.name) as label,
						u.id,CONCAT(u.username," - ",u.name) as name,
						u.username,
						IF(vu.last_name IS NULL,
								TRIM(SUBSTRING(TRIM(u.name),LENGTH(TRIM(u.name))-LOCATE(" ",REVERSE(TRIM(u.name)))+1)),
								vu.last_name) as lastname,
						IF(vu.first_name IS NULL,
								TRIM(REVERSE(SUBSTRING(REVERSE(TRIM(u.name)),LOCATE(" ",REVERSE(TRIM(u.name)))+1))),
								vu.first_name) as firstname
				  FROM #__users u
				  LEFT JOIN #__virtuemart_userinfos vu ON vu.virtuemart_user_id=u.id AND vu.address_type="BT"
				 WHERE 1=1
				 ' . ( ! empty( $user_id ) ? ' AND u.id IN (' . AC()->helper->scrubids( $user_id ) . ') ' : '' ) . '
				 ' . ( ! empty( $search ) ? ' AND CONCAT(u.username," - ",u.name) LIKE "%' . AC()->db->escape( trim( $search ), true ) . '%" ' : '' ) . '
				 ORDER BY ' . ( empty( $orderby ) ? 'label,u.id' : $orderby ) . ' ' . ( ! empty( $orderbydir ) ? $orderbydir : '' ) . '
				 ' . ( ! empty( $limit ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart . ',' : '' ) . ' ' . (int) $limit . ' ' : '' );

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_countrys() {

		if (!class_exists( 'VmConfig' )) require(JPATH_ADMINISTRATOR.'/components/com_virtuemart/helpers/config.php');
		VmConfig::loadConfig();
		$countryModel = VmModel::getModel ('country');
		$countries = $countryModel->getCountries (TRUE, TRUE, FALSE);
		
		$sorted_countries = array();
		$lang = JFactory::getLanguage();
		$prefix="COM_VIRTUEMART_COUNTRY_";
		foreach ($countries as  $country) {
			$country_string = $lang->hasKey($prefix.$country->country_3_code) ?   JText::_($prefix.$country->country_3_code)  : $country->country_name;
			$sorted_countries[$country->virtuemart_country_id] = $country_string;
		}

		asort($sorted_countries);

		$name = 'country_name';
		$id = 'country_id';
		$countries_list=array();
		foreach ($sorted_countries as  $key=>$value) {
			$countries_list[$key] = new stdClass();
			$countries_list[$key]->id = $key;
			$countries_list[$key]->$id = $key;
			$countries_list[$key]->$name = $value;
			$countries_list[$key]->label = $value;
			$countries_list[$key]->name = $value;
		}
		
		return $countries_list;
	}

	public function get_countrystates( $country_id = null ) {

		$sql = 'SELECT virtuemart_state_id as id,state_name AS label,state_name AS name
				  FROM `#__virtuemart_states`
				 WHERE 1=1
				 ' . ( ! empty( $country_id ) ? ' AND`virtuemart_country_id`= "' . (int) $country_id . '" ' : '' ) . '
				 ORDER BY `#__virtuemart_states`.`state_name`';

		 return AC()->db->get_objectlist( $sql, 'id' );
	}

	public function get_paymentmethods() {

		if (!class_exists( 'VmConfig' )) require(JPATH_ADMINISTRATOR.'/components/com_virtuemart/helpers/config.php');
		VmConfig::loadConfig();
		$countryModel = VmModel::getModel ('country');
		$countries = $countryModel->getCountries (TRUE, TRUE, FALSE);
		
		$model = VmModel::getModel('paymentmethod');
		$payments = $model->getPayments();
		
		if(empty($payments)) return array();
		
		foreach($payments as $k=>$payment) {
			$payments[$k]->id = $payment->virtuemart_paymentmethod_id;
			$payments[$k]->name = $payment->payment_name;
			$payments[$k]->label = $payment->payment_name;
		}
		
		$payment_keys = array();
		foreach($payments as $payment) {
			$payment_keys[$payment->id] = $payment;
		}
		
		return $payment_keys;
	}

	public function get_order( $order_id ) {
		return AC()->db->get_object('
			SELECT *,virtuemart_order_id AS order_id FROM #__virtuemart_orders WHERE virtuemart_order_id=' . (int) $order_id . '
		');
	}

	public function get_order_link( $order_id ) {
		return JRoute::_( 'index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id=' . (int) $order_id );
	}

	public function sql_history_order( $where, $having, $orderby ) {
		$sql = 'SELECT c.id,c.id AS voucher_customer_id, c.codes, uv.first_name,uv.last_name, c.user_id, c.user_id AS _user_id, u.username, uv.email as user_email,
					 ov.virtuemart_order_id AS order_id,ov.created_on AS cdate,CONCAT(ov.virtuemart_order_id," (",ov.order_number,")") AS order_number,
					 u.username as _username, uv.first_name as _fname, uv.last_name as _lname,ov.created_on AS _created_on,
					 GROUP_CONCAT(cc.code ORDER BY cc.code SEPARATOR ", ") as coupon_codes
				 FROM #__cmcoupon_voucher_customer c
				 LEFT JOIN #__cmcoupon_voucher_customer_code cc ON cc.voucher_customer_id=c.id
				 LEFT JOIN #__users u ON u.id=c.user_id
				 LEFT JOIN #__virtuemart_orders ov ON ov.virtuemart_order_id=c.order_id
				 LEFT JOIN #__virtuemart_order_userinfos uv ON uv.virtuemart_order_id=c.order_id AND uv.address_type="BT"
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
					 uv.virtuemart_user_id AS user_id,uv.first_name,uv.last_name,u.username,
					 o.virtuemart_order_id AS order_id,UNIX_TIMESTAMP(o.created_on) AS cdate,
					 SUM(au.total_product)+SUM(au.total_shipping) AS coupon_value_used,
					 c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance,au.user_email,
					 u.username as _username, uv.first_name as _fname, uv.last_name as _lname, o.created_on AS _created_on
				 FROM #__cmcoupon c
				 LEFT JOIN #__virtuemart_orders o ON o.virtuemart_order_id=c.order_id
				 LEFT JOIN #__virtuemart_userinfos uv ON uv.virtuemart_user_id=o.virtuemart_user_id AND uv.address_type="BT"
				 LEFT JOIN #__users u ON u.id=o.virtuemart_user_id
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

		# Query that speeds up history table dramatically:
		#
		#	ALTER TABLE `#__virtuemart_userinfos` ADD KEY(`virtuemart_user_id`,`address_type`);
		#

		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.startdate,c.expiration,c.state,
					 uu.coupon_id,uu.coupon_entered_id,c2.coupon_code as coupon_entered_code,
					 uu.id as use_id,uv.first_name,uv.last_name,uu.user_id,u.username,
					 (uu.total_product+uu.total_shipping) AS discount,uu.productids,uu.timestamp,uu.user_email,
					 ov.virtuemart_order_id AS order_id,ov.created_on AS cdate,ov.order_number,
					 u.username as _username, uv.first_name as _fname, uv.last_name as _lname,ov.created_on AS _created_on,
					 uu.is_customer_balance,
					 (uu.total_curr_product+uu.total_curr_shipping) AS discount_in_currency, uu.currency_code
				 FROM #__cmcoupon_history uu
				 JOIN #__cmcoupon c ON c.id=uu.coupon_id
				 LEFT JOIN #__cmcoupon c2 ON c2.id=uu.coupon_entered_id
				 LEFT JOIN #__cmcoupon_tag t ON t.coupon_id=c.id
				 LEFT JOIN #__virtuemart_userinfos uv ON uv.virtuemart_user_id=uu.user_id AND uv.address_type="BT"
				 LEFT JOIN #__users u ON u.id=uu.user_id
				 LEFT JOIN #__virtuemart_orders ov ON ov.virtuemart_order_id=uu.order_id
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
		$sql = 'SELECT g.*,lang.product_name as _product_name,p.product_sku,pr.title as profile, COUNT(pc.id) as codecount,c.coupon_code
				  FROM #__cmcoupon_giftcert_product g
				  LEFT JOIN #__cmcoupon c ON c.id=g.coupon_template_id
				  LEFT JOIN #__virtuemart_products p ON p.virtuemart_product_id=g.product_id
				  LEFT JOIN `#__virtuemart_products_' . $this->get_vm_lang() . '` as lang using (`virtuemart_product_id`)
				  LEFT JOIN #__cmcoupon_profile pr ON pr.id=g.profile_id
				  LEFT JOIN #__cmcoupon_giftcert_code pc ON pc.product_id=p.virtuemart_product_id
				 WHERE g.estore="' . $this->estore . '"
				' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				   GROUP BY g.id
				' . ( ! empty( $having ) && is_array( $having ) ? ' HAVING ' . implode( ' AND ', $having ) . ' ' : '' ) . '
				' . ( ! empty( $orderby ) ? ' ORDER BY ' . $orderby . ' ' : '' ) . '
				';
		return $sql;
	}

	public function sql_giftcert_product_single( $product_id ) {
		$sql = 'SELECT lang.product_name,p.product_sku
				   FROM #__virtuemart_products p
				   JOIN `#__virtuemart_products_' . $this->get_vm_lang() . '` as lang using (`virtuemart_product_id`)
				   WHERE p.virtuemart_product_id = ' . (int) $product_id;
		return $sql;
	}

	public function sql_giftcert_code( $where, $having, $orderby ) {
		$sql = 'SELECT g.*,lang.product_name as _product_name,p.product_sku
				  FROM #__cmcoupon_giftcert_code g
				  LEFT JOIN #__virtuemart_products p ON p.virtuemart_product_id=g.product_id
				  LEFT JOIN `#__virtuemart_products_' . $this->get_vm_lang() . '` as lang using (`virtuemart_product_id`)
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
			$datestr = ' AND o.created_on BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.created_on >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.created_on <= "' . $end_date . '" ';
		}

		$sql = 'SELECT gc.codes,gcc.product_id,p.product_name AS product_name,
					 uv.virtuemart_user_id AS user_id,uv.first_name AS first_name,uv.last_name AS last_name,u.username AS username,u.email AS email,
					 c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 o.virtuemart_order_id AS order_id,o.created_on AS created_on,gc.codes,					 
					 o.order_total AS order_total,o.order_tax AS order_tax,o.order_shipment AS order_shipment,o.order_shipment_tax AS order_shipment_tax,o.order_discount*-1 AS order_fee,
					 o.order_subtotal AS order_subtotal

				 FROM #__cmcoupon_voucher_customer_code gcc
				 JOIN #__cmcoupon_voucher_customer gc ON gc.id=gcc.voucher_customer_id
				 LEFT JOIN #__cmcoupon c ON c.id=gcc.coupon_id AND c.estore=gc.estore
				 LEFT JOIN `#__virtuemart_products_' . $this->get_vm_lang() . '` as p ON p.virtuemart_product_id=gcc.product_id
				 LEFT JOIN #__virtuemart_orders o ON o.virtuemart_order_id=gc.order_id
				 LEFT JOIN #__virtuemart_userinfos uv ON uv.virtuemart_user_id=o.virtuemart_user_id AND uv.address_type="BT"
				 LEFT JOIN #__users u ON u.id=o.virtuemart_user_id
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
			$datestr = ' AND o.created_on BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.created_on >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.created_on <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 uu.coupon_id,uu.coupon_entered_id,c2.coupon_code as coupon_entered_code,
					 uv.first_name AS first_name,uv.last_name AS last_name,uu.user_id,u.username AS username,
					 (uu.total_product+uu.total_shipping) AS discount,uu.productids,uu.timestamp,
					 ov.virtuemart_order_id AS order_id,ov.created_on AS created_on,uu.id as num_uses_id,
					 ov.order_total AS order_total,ov.order_tax AS order_tax,ov.order_shipment AS order_shipment,ov.order_shipment_tax AS order_shipment_tax,ov.order_discount*-1 AS order_fee,
					 ov.order_subtotal AS order_subtotal
				 FROM #__cmcoupon c
				 JOIN #__cmcoupon_history uu ON uu.coupon_id=c.id
				 LEFT JOIN #__cmcoupon c2 ON c2.id=uu.coupon_entered_id
				 
				 JOIN #__virtuemart_userinfos uv ON uv.virtuemart_user_id=uu.user_id
				 LEFT JOIN #__users u ON u.id=uu.user_id
				 LEFT JOIN #__virtuemart_orders ov ON ov.virtuemart_order_id=uu.order_id

				WHERE c.estore="' . $this->estore . '"
				 ' . $datestr . '
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND ov.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 ORDER BY u.username
		';
		return $sql;
	}

	public function rpt_history_uses_giftcerts( $start_date, $end_date, $order_status, $published, $giftcert_product ) {
		$datestr = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$datestr = ' AND o.created_on BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.created_on >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.created_on <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,c.coupon_value,
					 c.min_value,c.discount_type,c.function_type,c.expiration,c.state,
					 uv.virtuemart_user_id as user_id,uv.first_name AS first_name,uv.last_name AS last_name,u.username AS username,
					 o.order_total AS order_total,o.created_on AS created_on,gc.codes,
					 o.order_subtotal AS order_subtotal,
					 o.order_tax AS order_tax,o.order_shipment AS order_shipment,o.order_shipment_tax AS order_shipment_tax,o.order_discount*-1 AS order_fee,
					 SUM(au.total_product)+SUM(au.total_shipping) AS coupon_value_used,
					 c.coupon_value-IFNULL(SUM(au.total_product),0)-IFNULL(SUM(au.total_shipping),0) AS balance,gcc.product_id,p.product_name AS product_name
				 FROM #__cmcoupon c
				 LEFT JOIN #__cmcoupon_history au ON au.coupon_id=c.id
				 
				 LEFT JOIN #__virtuemart_orders o ON o.virtuemart_order_id=c.order_id
				 LEFT JOIN #__virtuemart_userinfos uv ON uv.virtuemart_user_id=o.virtuemart_user_id AND uv.address_type="BT"
				 LEFT JOIN #__users u ON u.id=o.virtuemart_user_id
				 
				 LEFT JOIN #__cmcoupon_voucher_customer gc ON gc.order_id=o.virtuemart_order_id
				 LEFT JOIN #__cmcoupon_voucher_customer_code gcc ON gcc.voucher_customer_id=gc.id AND gcc.coupon_id=c.id
				 
				 LEFT JOIN `#__virtuemart_products_' . $this->get_vm_lang() . '` as p ON p.virtuemart_product_id=gcc.product_id
				 
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
			$datestr = ' AND o.created_on BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.created_on >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.created_on <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id, SUM(o.order_total) as total, COUNT(c.id) as count
				  FROM #__cmcoupon c
				  JOIN (SELECT coupon_entered_id,order_id FROM #__cmcoupon_history GROUP BY order_id,coupon_entered_id) uu ON uu.coupon_entered_id=c.id
				  JOIN #__virtuemart_orders o ON o.virtuemart_order_id=uu.order_id
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
			$datestr = ' AND o.created_on BETWEEN "' . $start_date . '" AND "' . $end_date . '" ';
		} elseif ( ! empty( $start_date ) ) {
			$datestr = ' AND o.created_on >= "' . $start_date . '" ';
		} elseif ( ! empty( $end_date ) ) {
			$datestr = ' AND o.created_on <= "' . $end_date . '" ';
		}

		$sql = 'SELECT c.id,c.coupon_code, SUM(o.order_total) as total, COUNT(uu.order_id) as count,ctry.country_3_code as country,st.state_2_code as state,u.city AS city,
					 CONCAT(c.id,"-",IF(ISNULL(ctry.country_3_code),"0",ctry.country_3_code),"-",IF(ISNULL(st.state_2_code),"0",st.state_2_code),"-",u.city) as realid
				  FROM #__cmcoupon c
				  JOIN (SELECT coupon_entered_id,order_id FROM #__cmcoupon_history GROUP BY order_id,coupon_entered_id) uu ON uu.coupon_entered_id=c.id
				  
				  JOIN #__virtuemart_orders o ON o.virtuemart_order_id=uu.order_id
				  JOIN #__virtuemart_order_userinfos u ON u.virtuemart_order_id=o.virtuemart_order_id AND u.address_type="BT"
				  LEFT JOIN #__virtuemart_countries ctry ON ctry.virtuemart_country_id=u.virtuemart_country_id
				  LEFT JOIN #__virtuemart_states st ON st.virtuemart_state_id=u.virtuemart_state_id
				  
				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id,u.virtuemart_country_id,u.virtuemart_state_id,u.city';
		$order_details = AC()->db->get_objectlist( $sql, 'realid' );

		$sql = 'SELECT c.id,c.coupon_code, uu.productids,
						SUM(uu.total_product+uu.total_shipping) as discount,
						ctry.country_3_code as country,st.state_2_code as state,u.city AS city
				  FROM #__cmcoupon_history uu
				  JOIN #__cmcoupon c ON c.id=uu.coupon_entered_id 
				  
				  JOIN #__virtuemart_orders o ON o.virtuemart_order_id=uu.order_id
				  JOIN #__virtuemart_order_userinfos u ON u.virtuemart_order_id=o.virtuemart_order_id AND u.address_type="BT"
				  LEFT JOIN #__virtuemart_countries ctry ON ctry.virtuemart_country_id=u.virtuemart_country_id
				  LEFT JOIN #__virtuemart_states st ON st.virtuemart_state_id=u.virtuemart_state_id

				 WHERE c.estore="' . $this->estore . '"
				 ' . ( ! empty( $order_status ) && is_array( $order_status ) ? ' AND o.order_status IN ("' . implode( '","', $order_status ) . '") ' : '' ) . '
				 ' . $datestr . '
				 ' . ( ! empty( $where ) && is_array( $where ) ? ' AND ' . implode( ' AND ', $where ) . ' ' : '' ) . '
				 GROUP BY c.id,u.virtuemart_country_id,u.virtuemart_state_id,u.city
				 ORDER BY c.coupon_code';

		return (object) array(
			'sql' => $sql,
			'order_details' => $order_details,
		);
	}

	public function get_order_status() {
		if ( ! class_exists( 'VmConfig' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
		}
		if ( method_exists( 'VmConfig','loadJLang' ) ) {
			VmConfig::loadConfig();
			VmConfig::loadJLang( 'com_virtuemart_orders', true );
		}
		$items = AC()->db->get_objectlist( 'SELECT virtuemart_orderstate_id AS order_status_id, order_status_code, order_status_name FROM #__virtuemart_orderstates' );
		foreach ( $items as $k => $item ) {
			$items[$k]->order_status_name = JText::_( $item->order_status_name );
		}
		return $items;
	}

	public function add_coupon_to_cart( $coupon_code ) {
		if ( ! class_exists( 'VmConfig' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
		}
		VmConfig::loadConfig();
		//if (!class_exists('VmImage')) require(JPATH_VM_ADMINISTRATOR.'/helpers/image.php'); // fixes error http://stackoverflow.com/questions/13946085/my-site-has-been-crash-when-i-add-some-php-code
		if ( ! class_exists( 'VirtueMartCart' ) ) {
			require JPATH_VM_SITE . '/helpers/cart.php';
		}
		$cart = VirtueMartCart::getCart( false );
		if ( empty( $cart->products ) && empty( $cart->cartProductsData ) ) {
			return false;
		}
		
		$cart->setCouponCode( $coupon_code );
		return true;
	}

	public function get_name() {
		$config = JFactory::getConfig ();
		return $config->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'} ( 'sitename' );
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

