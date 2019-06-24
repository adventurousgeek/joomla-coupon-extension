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

AC()->helper->add_class( 'CmCoupon_Admin_Class_Report' );

/**
 * Class
 */
class CmCoupon_Admin_Class_Report_Tag extends CmCoupon_Admin_Class_Report {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'reporttag';
		$this->title = AC()->lang->__( 'Tags Report' );
		$this->filename = 'coupon_tags.csv';
		$this->_orderby = '';
		$this->_primary = '';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'tag' => AC()->lang->__( 'Tag' ),
			'number' => AC()->lang->__( 'Number' ),
			'coupon_codes' => AC()->lang->__( 'Coupon Code' ),
		);
		return $columns;
	}

	/**
	 * Default column behavior
	 *
	 * @param object $item the object.
	 * @param string $column_name the column.
	 */
	public function column_default( $item, $column_name ) {
		return $item->{$column_name};
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		$function_type = AC()->helper->get_request( 'function_type' );
		$coupon_value_type = AC()->helper->get_request( 'coupon_value_type' );
		$discount_type = AC()->helper->get_request( 'discount_type' );
		$template = (int) AC()->helper->get_request( 'templatelist' );
		$published = AC()->helper->get_request( 'published' );

		$sql = 'SELECT CONCAT(t.coupon_id,"-",t.tag) AS id, t.tag,COUNT(t.coupon_id) as number,GROUP_CONCAT(c.coupon_code separator ", ") as coupon_codes
				  FROM #__cmcoupon_tag t
				  LEFT JOIN #__cmcoupon c ON c.id=t.coupon_id AND c.estore="' . CMCOUPON_ESTORE . '"
				 WHERE 1=1
				 ' . ( ! empty( $function_type ) ? 'AND c.function_type="' . $function_type . '" ' : '' ) . '
				 ' . ( ! empty( $coupon_value_type ) ? 'AND c.coupon_value_type="' . $coupon_value_type . '" ' : '' ) . '
				 ' . ( ! empty( $discount_type ) ? 'AND c.discount_type="' . $discount_type . '" ' : '' ) . '
				 ' . ( ! empty( $template ) ? 'AND c.template_id="' . $template . '" ' : '' ) . '
				 ' . ( ! empty( $state ) ? 'AND c.published="' . $published . '" ' : '' ) . '
				 GROUP BY t.tag'
				 ;
		return $sql;
	}

	/**
	 * Data list
	 */
	public function get_data() {
		$this->_data = isset( $this->_get_all_data ) && true === $this->_get_all_data
					? $this->get_list( $this->buildquery(), 'id' )
					: $this->get_list( $this->buildquery(), 'id', $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );
		return $this->_data;
	}

}

