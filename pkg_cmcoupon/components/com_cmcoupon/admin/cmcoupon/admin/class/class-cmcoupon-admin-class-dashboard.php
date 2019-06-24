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
class CmCoupon_Admin_Class_Dashboard extends CmCoupon_Library_Class {

	/**
	 * Get statistics
	 */
	public function get_stats() {
		$_products = array();

		/*
		* Get total number of entries
		*/
		$_products['total'] = AC()->db->get_value( 'SELECT count(id)  FROM #__cmcoupon WHERE estore="' . CMCOUPON_ESTORE . '"' );

		/*
		* Get total number of approved entries
		*/
		$current_date = date( 'Y-m-d H:i:s' );
		$sql = 'SELECT count(id) 
				  FROM #__cmcoupon 
				 WHERE state="published"
				   AND estore="' . CMCOUPON_ESTORE . '"
				   AND ( ((startdate IS NULL OR startdate="") 	AND (expiration IS NULL OR expiration="")) OR
						 ((expiration IS NULL OR expiration="") AND startdate<="' . $current_date . '") OR
						 ((startdate IS NULL OR startdate="") 	AND expiration>="' . $current_date . '") OR
						 (startdate<="' . $current_date . '"		AND expiration>="' . $current_date . '")
					   )
				';
		$_products['active'] = AC()->db->get_value( $sql );

		$sql = 'SELECT count(id) 
				  FROM #__cmcoupon 
				 WHERE estore="' . CMCOUPON_ESTORE . '" AND (state="unpublished"  OR startdate>"' . $current_date . '" OR expiration<"' . $current_date . '")';
		$_products['inactive'] = AC()->db->get_value( $sql );

		$sql = 'SELECT count(id) 
				  FROM #__cmcoupon
				 WHERE estore="' . CMCOUPON_ESTORE . '" AND state="template"';
		$_products['templates'] = AC()->db->get_value( $sql );

		return (object) $_products;
	}

	/**
	 * Get license information
	 */
	public function get_license() {
		$license = null;
		$website = null;
		$expiration = null;
		$rows = AC()->db->get_objectlist( 'SELECT id,value FROM #__cmcoupon_license WHERE id IN ("license", "expiration","website")' );
		foreach ( $rows as $row ) {
			if ( 'license' === $row->id ) {
				$license = $row->value;
			} elseif ( 'expiration' === $row->id ) {
				$expiration = $row->value;
			} elseif ( 'website' === $row->id ) {
				$website = explode( '|', $row->value );
			}
		}
		return (object) array(
			'l' => $license,
			'url' => ! empty( $website ) ? current( $website ) : '',
			'exp' => $expiration,
		);
	}

	/**
	 * Get version information
	 */
	public function get_version() {

		// Cache.
		$check = AC()->helper->get_cache( 'cmcoupon_dashboard_versionupdate' );
		if ( false !== $check ) {
			return json_decode( $check, true );
		}

		$check = array(
			'connect' => 0,
			'current_version' => CMCOUPON_VERSION,
		);

		$license = AC()->helper->new_class( 'CmCoupon_Admin_Class_License' );
		$info = $license->get_version_info();

		if ( ! empty( $info ) && ! empty( $info->version ) ) {

			$check['version']  = $info->version;
			$check['released'] = $info->release_date;
			$check['release_notes'] = $info->info_url;
			$check['connect']  = 1;
			$check['enabled']  = 1;

			$check['current']  = version_compare( $check['current_version'], $check['version'] );

			// Cache for 3 days.
			AC()->helper->set_cache( 'cmcoupon_dashboard_versionupdate', AC()->helper->json_encode( $check ), 3600 * 72 );
		} else {
			// Cache for an hour.
			AC()->helper->set_cache( 'cmcoupon_dashboard_versionupdate', AC()->helper->json_encode( $check ), 3600 );
		}
		return $check;
	}

}

