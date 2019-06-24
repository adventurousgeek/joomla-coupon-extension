<?php
/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

if ( ! defined( '_CM_' ) ) {
	exit;
}

/**
 * Class
 */
class Cmcoupon_Library_Ajax {

	/**
	 * Get asset list pagination
	 **/
	public static function ajax_elements() {
		$q = trim( AC()->helper->get_request( 'term' ) );
		if ( empty( $q ) || strlen( $q ) < 2 ) {
			exit;
		}

		$type = AC()->helper->get_request( 'element' );

		$result = array();
		$dbresults = array();
		switch ( $type ) {
			case 'product':
				$dbresults = AC()->store->get_products( null, $q, 4 );
				break;
			case 'productgift':
				$dbresults = AC()->store->get_products( null, $q, 25, true, null, null, null, true );
				break;
			case 'category':
				$dbresults = AC()->store->get_categorys( null, $q, 25 );
				break;
			case 'manufacturer':
				$dbresults = AC()->store->get_manufacturers( null, $q, 25 );
				break;
			case 'vendor':
				$dbresults = AC()->store->get_vendors( null, $q, 25 );
				break;
			case 'custom':
				$dbresults = AC()->store->get_assetcustoms( null, $q, 25 );
				break;
			case 'shipping':
				$dbresults = AC()->store->get_shippings( null, $q, 25 );
				break;
			case 'user':
				$dbresults = AC()->store->get_users( null, $q, 25 );
				break;
			case 'usergroup':
				$dbresults = AC()->store->get_groups( null, $q, 25 );
				break;
			case 'coupon':
			case 'combination':
				$q = '%' . AC()->db->escape( trim( $q ), true ) . '%';
				$dbresults = AC()->db->get_objectlist('
						SELECT id,coupon_code AS label
						  FROM #__cmcoupon
						 WHERE estore="' . CMCOUPON_ESTORE . '" AND state="published" AND function_type NOT IN ("combination") AND LOWER(coupon_code) LIKE "' . strtolower( $q ) . '" ORDER BY label,id LIMIT 25
				' );
				break;
			case 'coupons_noauto':
				$q = '%' . AC()->db->escape( trim( $q ), true ) . '%';
				$dbresults = AC()->db->get_objectlist( '
						SELECT c.id,c.coupon_code AS label
						  FROM #__cmcoupon c
						  LEFT JOIN #__cmcoupon_auto a ON a.coupon_id=c.id
						 WHERE c.estore="' . CMCOUPON_ESTORE . '" AND c.state="published" AND a.id IS NULL AND LOWER(c.coupon_code) LIKE "' . strtolower( $q ) . '" ORDER BY label,id LIMIT 25
				' );
				break;
			case 'coupons_template':
				$q = '%' . AC()->db->escape( trim( $q ), true ) . '%';
				$dbresults = AC()->db->get_objectlist( '
						SELECT id,coupon_code AS label
						  FROM #__cmcoupon
						 WHERE estore="' . CMCOUPON_ESTORE . '" AND state="template" AND LOWER(coupon_code) LIKE "' . strtolower( $q ) . '" ORDER BY label,id LIMIT 25
				');
				break;
			case 'coupons_published':
				$q = '%' . AC()->db->escape( trim( $q ), true ) . '%';
				$dbresults = AC()->db->get_objectlist( '
						SELECT id,coupon_code AS label
						  FROM #__cmcoupon
						 WHERE estore="' . CMCOUPON_ESTORE . '" AND state="published" AND LOWER(coupon_code) LIKE "' . strtolower( $q ) . '" ORDER BY label,id LIMIT 25
				' );
				break;
			case 'coupons_giftcert':
				$q = '%' . AC()->db->escape( trim( $q ), true ) . '%';
				$dbresults = AC()->db->get_objectlist( '
						SELECT id,coupon_code AS label
						  FROM #__cmcoupon
						 WHERE estore="' . CMCOUPON_ESTORE . '" AND function_type="giftcert" AND state="published" AND LOWER(coupon_code) LIKE "' . strtolower( $q ) . '" ORDER BY label,id LIMIT 25
				' );
				break;
			case 'coupontag':
				$q = '%' . AC()->db->escape( trim( $q ), true ) . '%';
				$dbresults = AC()->db->get_objectlist( 'SELECT DISTINCT tag AS id, tag AS label FROM #__cmcoupon_tag WHERE LOWER(tag) LIKE "' . strtolower( $q ) . '" ORDER BY tag LIMIT 25' );
				break;

		}
		if ( ! empty( $dbresults ) ) {
			foreach ( $dbresults as $row ) {
				array_push( $result, array(
					'id' => $row->id,
					'label' => $row->label,
					'value' => strip_tags( $row->label ),
				) );
			}
		}

		echo AC()->helper->json_encode( $result );
		exit;
	}

	/**
	 * Get asset list all
	 **/
	public static function ajax_elements_all() {
		$type = AC()->helper->get_request( 'element' );

		$result = array();
		$dbresults = array();
		switch ( $type ) {
			case 'product':
				$dbresults = AC()->store->get_products();
				break;
			case 'category':
				$dbresults = AC()->store->get_categorys();
				break;
			case 'manufacturer':
				$dbresults = AC()->store->get_manufacturers();
				break;
			case 'vendor':
				$dbresults = AC()->store->get_vendors();
				break;
			case 'custom':
				$dbresults = AC()->store->get_assetcustoms();
				break;
			case 'shipping':
				$dbresults = AC()->store->get_shippings();
				break;
			case 'user':
				$dbresults = AC()->store->get_users();
				break;
			case 'usergroup':
				$dbresults = AC()->store->get_groups();
				break;
			case 'coupon':
				$dbresults = AC()->db->get_objectlist( '
						SELECT id,coupon_code AS label
						  FROM #__cmcoupon
						 WHERE estore="' . CMCOUPON_ESTORE . '" AND state="published" AND function_type NOT IN ("combination") ORDER BY label,id
				' );
				break;
			case 'countrystate':
				$country_ids = AC()->helper->get_request( 'country_id' );
				foreach ( $country_ids as $country_id ) {
					$result[ $country_id ] = AC()->store->get_countrystates( $country_id );
				}
				break;
		}
		if ( ! empty( $dbresults ) ) {
			foreach ( $dbresults as $row ) {
				array_push( $result, array(
					'id' => $row->id,
					'label' => $row->label,
					'value' => strip_tags( $row->label ),
				) );
			}
		}

		echo AC()->helper->json_encode( $result );
		exit;
	}

	/**
	 * Gat asset table
	 **/
	public static function ajax_elements_datatables() {

		$type = AC()->helper->get_request( 'category' );
		$records_per_page = (int) AC()->helper->get_request( 'length', 10 );
		$limitstart = (int) AC()->helper->get_request( 'start', 0 );

		$sort_by = '';
		$dir = '';
		$in_order = AC()->helper->get_request( 'order', array() );
		if ( isset( $in_order[0] ) ) {
			$columns = AC()->helper->get_request( 'columns', array() );
			if ( isset( $columns[ $in_order[0]['column'] ]['name'] ) ) {
				$sort_by = $columns[ $in_order[0]['column'] ]['name'];
				$dir = $in_order[0]['dir'];
			}
		}
		$search = AC()->helper->get_request( 'search', array() );
		$search = empty( $search['value'] ) ? '' : $search['value'];

		$result = array();
		$dbresults = array();
		switch ( $type ) {
			case 'product':
				$dbresults = AC()->store->get_products( null, $search, $records_per_page, true, $limitstart, $sort_by, $dir );
				break;
			case 'category':
				$dbresults = AC()->store->get_categorys( null, $search, $records_per_page, $limitstart, $sort_by, $dir );
				break;
			case 'manufacturer':
				$dbresults = AC()->store->get_manufacturers( null, $search, $records_per_page, $limitstart, $sort_by, $dir );
				break;
			case 'vendor':
				$dbresults = AC()->store->get_vendors( null, $search, $records_per_page, $limitstart, $sort_by, $dir );
				break;
			case 'custom':
				$dbresults = AC()->store->get_assetcustoms( null, $search, $records_per_page, $limitstart, $sort_by, $dir );
				break;
			case 'shipping':
				$dbresults = AC()->store->get_shippings( null, $search, $records_per_page, $limitstart, $sort_by, $dir );
				break;
			case 'user':
				$dbresults = AC()->store->get_users( null, $search, $records_per_page, $limitstart, $sort_by, $dir );
				break;
			case 'usergroup':
				$dbresults = AC()->store->get_groups( null, $search, $records_per_page, $limitstart, $sort_by, $dir );
				break;
			case 'coupon':
				$dbresults = AC()->db->get_objectlist( '
						SELECT id,coupon_code AS label
						  FROM #__cmcoupon
						 WHERE estore="' . CMCOUPON_ESTORE . '" AND state="published" AND function_type NOT IN ("combination","giftcert")
						' . ( ! empty( $search ) ? ' AND coupon_code LIKE "%' . AC()->db->escape( trim( strtolower( $search ) ), true ) . '%" ' : '' ) . ' 
						 ORDER BY ' . ( empty( $sort_by ) ? 'label,id' : $sort_by ) . ' ' . ( ! empty( $dir ) ? $dir : '' ) . '
						' . ( ! empty( $records_per_page ) ? ' LIMIT ' . ( ! empty( $limitstart ) ? $limitstart : '' ) . ' ' . (int) $records_per_page . ' ' : '' ) . '
				');
				break;
		}
		if ( ! empty( $dbresults ) ) {
			$totalrecords = AC()->db->get_value( 'SELECT FOUND_ROWS()' );

			$result = array(
				'recordsTotal' => $totalrecords,
				'recordsFiltered' => $totalrecords,
				'data' => array(),
			);
			foreach ( $dbresults as $r ) {
				$result['data'][] = array_values( (array) $r );
			}
		}

		echo AC()->helper->json_encode( $result );
		exit;
	}

	/**
	 * Get tags
	 **/
	public static function ajax_tags() {
		$output = array();

		$dbresults = AC()->db->get_objectlist( 'SELECT DISTINCT tag FROM #__cmcoupon_tag ORDER by tag' );
		foreach ( $dbresults as $r ) {
			$output[] = $r->tag;
		}

		echo AC()->helper->json_encode( $output );
		exit;
	}

	/**
	 * Generate unique coupon code that does not already exist
	 **/
	public static function ajax_generate_coupon_code() {
		echo AC()->coupon->generate_coupon_code();
		exit;
	}

	/**
	 * Get value definition of specific coupon
	 **/
	public static function ajax_value_definition() {
		echo AC()->coupon->get_value_print( AC()->helper->get_request( 'string' ), AC()->helper->get_request( 'vtype' ) );
		exit;
	}

	/**
	 * Preview image of email template
	 **/
	public static function profile_preview() {
		$profile_id = (int) AC()->helper->get_request( 'id' );
		$image = AC()->profile->generate_image( 'COUPONCODE', '$25.00', AC()->helper->get_date( null, 'Y-m-d H:i:s' ), 'screen', null, $profile_id );
		if ( false === $image ) {
			exit( AC()->lang->__( 'No image setup' ) );
		}
		header( 'Cache-Control: no-cache, must-revalidate' ); // HTTP/1.1.
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); // Date in the past.
		header( 'Content-type: image/png' );
		if ( false === $image ) {
			echo 'error';
		} else {
			imagepng( $image ); // save image to file.
			imagedestroy( $image ); // destroy resource.
		}
		exit;
	}

	/**
	 * Get coupon detail
	 **/
	public static function coupondetail() {
		$controller = AC()->helper->new_class( 'CmCoupon_Admin_Controller_Coupon' );
		$controller->show_detail();
		exit;
	}

}
