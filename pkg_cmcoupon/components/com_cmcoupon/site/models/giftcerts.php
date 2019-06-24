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
class CmcouponSiteModelGiftcerts extends CmCouponModelConnector {

	public function __construct() {
		$this->_type = 'giftcerts';
		parent::__construct();

		$limit		= JFactory::getApplication()->getUserStateFromRequest( 'com_cmcoupon.giftcerts.limit', 'limit', JFactory::getApplication()->getCfg( 'list_limit' ), 'int' );
		$limitstart = JFactory::getApplication()->getUserStateFromRequest( 'com_cmcoupon.giftcerts.limitstart', 'limitstart', 0, 'int' );

		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);

		$this->model = AC()->helper->new_class( 'CmCoupon_Public_Class_Storecredit' );
		$this->model->set_state( 'limit', $limit );
		$this->model->set_state( 'limitstart', $limitstart );
	}

	public function getData() {
		return $this->model->get_data();
	}

	public function _buildQuery() {
		return $this->model->buildquery();
	}

	public function voucher_to_balance( $coupon_code ) {
		$ret = $this->model->save( $coupon_code );
		if ( ! empty( $ret ) && is_array( $ret ) ) {
			foreach ( $ret as $error ) {
				JFactory::getApplication()->enqueueMessage( $error, 'error');
			}
			return;
		}

		// reset session
		$session = JFactory::getSession();
 		$session->set('customer_balance', null, 'cmcoupon');
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
