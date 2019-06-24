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
class CmCoupon_Admin_Class_Report extends CmCoupon_Library_Class {

	/**
	 * Export to csv
	 */
	public function export() {

		$this->_data = null;

		$this->_get_all_data = true;
		$this->get_data();
		$columns = $this->get_columns();
		$columns_blank = array_fill_keys( array_keys( $columns ), '' );

		if ( empty( $this->_data ) ) {
			return;
		}

		$delimiter = AC()->param->get( 'csvDelimiter', ',' );

		$output = '';
		$output .= AC()->helper->fputcsv2( $columns, $delimiter );

		foreach ( $this->_data as $row ) {
			if ( ! is_array( $row ) ) {
				$row = (array) $row;
			}
			$row = array_intersect_key( $row, $columns_blank );
			$d = array_merge( $columns_blank, $row );

			$output .= AC()->helper->fputcsv2( $d, $delimiter );
		}
		return $output;
	}

}
