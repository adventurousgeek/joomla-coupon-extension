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
 **/
class CmCoupon_Admin_Controller_Report extends CmCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		AC()->helper->add_class( 'CmCoupon_Admin_Class_Report' );
	}

	/**
	 * Show report form
	 **/
	public function show_default() {
		$this->render( 'admin.view.report.request', array(
			'form_action' => '/cmcoupon/report/run',
			'form_method' => 'get',
			'orderstatuslist' => AC()->store->get_order_status(),
			'functiontypelist' => AC()->helper->vars( 'function_type' ),
			'valuetypelist' => AC()->helper->vars( 'coupon_value_type' ),
			'discounttypelist' => AC()->helper->vars( 'discount_type' ),
			'templatelist' => AC()->db->get_objectlist( 'SELECT id,coupon_code FROM #__cmcoupon WHERE estore="' . CMCOUPON_ESTORE . '" AND state="template" ORDER BY coupon_code,id', 'id' ),
			'publishedlist' => AC()->helper->vars( 'state' ),
			'giftcertproductlist' => AC()->db->get_objectlist( AC()->store->sql_giftcert_product( '', '', '_product_name,g.product_id ' ) ),
		) );
	}

	/**
	 * Show report
	 **/
	public function show_list() {
		$report_type = AC()->helper->get_request( 'report_type' );

		switch ( $report_type ) {
			case 'coupon_list':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Coupon' );
				break;
			case 'purchased_giftcert_list':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Purchasedgiftcert' );
				break;
			case 'coupon_vs_total':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Coupontotal' );
				break;
			case 'coupon_vs_location':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Couponlocation' );
				break;
			case 'history_uses_coupons':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Historycoupon' );
				break;
			case 'history_uses_giftcerts':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Historygiftcert' );
				break;
			case 'coupon_tags':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Tag' );
				break;
			case 'customer_balance':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Storecredit' );
				break;
		}

		if ( empty( $model ) ) {
			return;
		}

		$query_array = array(
			'report_type' => AC()->helper->get_request( 'report_type' ),
			'start_date' => AC()->helper->get_request( 'start_date' ),
			'end_date' => AC()->helper->get_request( 'end_date' ),
			'order_status' => AC()->helper->get_request( 'order_status' ),
			'function_type' => AC()->helper->get_request( 'function_type' ),
			'coupon_value_type' => AC()->helper->get_request( 'coupon_value_type' ),
			'discount_type' => AC()->helper->get_request( 'discount_type' ),
			'templatelist' => AC()->helper->get_request( 'templatelist' ),
			'published' => AC()->helper->get_request( 'published' ),
			'giftcert_product' => AC()->helper->get_request( 'giftcert_product' ),
			'filename' => $model->filename,
		);
		$queryparam = http_build_query( $query_array );

		$criteria = (object) $query_array;
		if ( ! empty( $criteria->order_status ) ) {
			$str = '';
			$status_map = array();
			$db_order_status = AC()->store->get_order_status();
			foreach ( $db_order_status as $val ) {
				$status_map[ $val->order_status_code ] = $val->order_status_name;
			}
			foreach ( $criteria->order_status as $val ) {
				if ( isset( $status_map[ $val ] ) ) {
					$str .= $status_map[ $val ] . ', ';
					unset( $status_map[ $val ] );
				}
			}
			$criteria->order_status_str = substr( $str, 0, -2 );
		}

		$this->render( 'admin.view.report.list', array(
			'title' => $model->title,
			'table_html' => $model->display_list(),
			'criteria' => $criteria,
			'queryparam' => $queryparam,
			'count' => count( $model->_data ),
		) );
	}

	/**
	 * Export
	 **/
	public function do_export() {
		$report_type = AC()->helper->get_request( 'report_type' );

		switch ( $report_type ) {
			case 'coupon_list':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Coupon' );
				break;
			case 'purchased_giftcert_list':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Purchasedgiftcert' );
				break;
			case 'coupon_vs_total':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Coupontotal' );
				break;
			case 'coupon_vs_location':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Couponlocation' );
				break;
			case 'history_uses_coupons':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Historycoupon' );
				break;
			case 'history_uses_giftcerts':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Historygiftcert' );
				break;
			case 'coupon_tags':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Tag' );
				break;
			case 'customer_balance':
				$model = AC()->helper->new_class( 'CmCoupon_Admin_Class_Report_Storecredit' );
				break;
		}

		if ( ! empty( $model ) ) {
			$file = $model->export();
		}

		if ( empty( $file ) ) {
			AC()->helper->set_message( 'Error producing export', 'error' );
			AC()->helper->redirect( 'report' );
			return;
		}

		$filename = AC()->helper->get_request( 'filename', 'file.csv' );

		// Required for IE, otherwise Content-disposition is ignored.
		if ( ini_get( 'zlib.output_compression' ) ) {
			ini_set( 'zlib.output_compression', 'Off' );
		}

		header( 'Pragma: public' ); // Required.
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', false ); // Required for certain browsers.
		header( 'Content-Type: application/vnd.ms-excel' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . strlen( $file ) );
		echo $file;
		exit();
	}

}

