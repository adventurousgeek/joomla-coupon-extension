<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined('_JEXEC') or die( 'Restricted access' );

if ( version_compare( JVERSION, '3.0.0', 'ge' ) ) {
	class CmCouponModelConnector extends JModelLegacy{}
}
else {
	jimport( 'joomla.application.component.model' );
	class CmCouponModelConnector extends JModel{}
}
class CmCouponSiteModelCoupons extends CmCouponModelConnector {

	public function __construct() {
		$this->_type = 'coupons';
		parent::__construct();

		$limit		= JFactory::getApplication()->getUserStateFromRequest( 'com_cmcoupon.limit', 'limit', JFactory::getApplication()->getCfg( 'list_limit' ), 'int' );
		$limitstart = JFactory::getApplication()->getUserStateFromRequest( 'com_cmcoupon.limitstart', 'limitstart', 0, 'int' );

		$this->setState( 'limit', $limit );
		$this->setState( 'limitstart', $limitstart );

		$this->model = AC()->helper->new_class( 'CmCoupon_Public_Class_Coupon' );
		$this->model->set_state( 'limit', $limit );
		$this->model->set_state( 'limitstart', $limitstart );
	}
	
	public function getData() {
		return $this->model->get_data();
	}

	public function _buildQuery() {
		$filter_order = AC()->helper->get_userstate_request( $this->model->name . '.site.orderby', 'filter_order', $this->model->_orderby );
		$filter_order_dir = AC()->helper->get_userstate_request( $this->model->name . '.site.order', 'filter_order_Dir', '' );
		return $this->model->buildquery();
	}

	public function getCouponImageUrl() {
		$coupon_code = AC()->helper->get_request( 'coupon_code', '' );
		$ret = $this->model->get_image_url( $coupon_code );
		$ret = explode( '/', $ret );
		$file = end( $ret );
		return JURI::base() . 'index.php?option=com_cmcoupon&amp;format=image&amp;view=coupons&amp;file=' . $file;
	}

	public function getRawCouponImage($filename, $user_id=0) {
		return $this->model->get_image_raw( $filename, $user_id );
	}

	public function getTotal() {
		if ( empty( $this->_total ) ) {
			$query = $this->_buildQuery();
			$this->_total = $this->_getListCount( $query );
		}
		return $this->_total;
	}

	public function getPagination() {
		if ( empty( $this->_pagination ) ) {
			jimport( 'joomla.html.pagination' );
			$this->_pagination = new JPagination( $this->getTotal(), $this->getState('limitstart'), $this->getState('limit') );
		}
		return $this->_pagination;
	}

}
