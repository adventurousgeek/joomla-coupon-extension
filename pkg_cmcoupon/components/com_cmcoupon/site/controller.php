<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

if(version_compare( JVERSION, '3.0.0', 'ge' )) {
	class CmCouponSiteViewConnect extends JViewLegacy {}
	class CmCouponControllerConnect extends JControllerForm {}
}
else {
	jimport( 'joomla.application.component.view');
	class CmCouponSiteViewConnect extends JView {}
	jimport('joomla.application.component.controller');
	class CmCouponControllerConnect extends JController {}
}


class CmCouponSiteController extends CmCouponControllerConnect {

	public function display( $cachable = false, $urlparams = false ) {
		AC()->helper->set_request( 'view', AC()->helper->get_request( 'view', 'coupons' ) );
		parent::display();
	}

	public function voucher_to_balance() {
		if ( method_exists( 'JSession', 'checkToken' ) ) {
			JSession::checkToken() or jexit( 'Invalid Token' );
		}
		else {
			JRequest::checkToken() or jexit( 'Invalid Token' );
		}

		$user = JFactory::getUser();
		$db = JFactory::getDBO();
		if( empty( $user->id ) ) {
			return;
		}
		$coupon_code = AC()->helper->get_request( 'voucher', '' );
		$model = $this->getModel( 'giftcerts' );
		$model->voucher_to_balance( $coupon_code );

		$this->setRedirect( JRoute::_( 'index.php?option=com_cmcoupon&view=giftcerts' ), JText::_( 'COM_CMCOUPON_MSG_DATA_SAVED' ) );
	}

	public function apply_voucher_balance() {

		AC()->storediscount->cart_coupon_validate_balance();
		
		if ( $return = AC()->helper->get_request( 'return', '' ) ) {
			$return = base64_decode( $return );
			if ( ! JUri::isInternal( $return ) ) {
				$data['return'] = '';
			}
		}

		if ( empty( $return ) ) {
			$return = 'index.php?';
		}
		JFactory::getApplication()->redirect (JRoute::_( $return ) );
	}
	
	public function cron() {
		$key = AC()->helper->get_request( 'key' );
		if ( empty( $key ) ) {
			return;
		}

		if ( $key != AC()->param->get( 'cron_key' ) ) {
			return;
		}

		$model = AC()->helper->new_class( 'CmCoupon_Library_Cron' );
		$model->process( false );
		exit;
	}

	public function cronjs() {
	
		$model = AC()->helper->new_class( 'CmCoupon_Library_Cron' );

		{// generate empty picture http://www.nexen.net/articles/dossier/16997-une_image_vide_sans_gd.php
			$hex = "47494638396101000100800000ffffff00000021f90401000000002c00000000010001000002024401003b";
			$img = '';
			$t = strlen($hex) / 2;
			for($i = 0; $i < $t; $i++) $img .= chr(hexdec(substr($hex, $i * 2, 2) ));
			header('Last-Modified: Fri, 01 Jan 1999 00:00 GMT', true, 200);
			header('Content-Length: '.strlen($img));
			header('Content-Type: image/gif');
			echo $img;
		}

		$model->process();

		exit;
	}

	private function cronjstrigger() {
		$model = AC()->helper->new_class( 'CmCoupon_Library_Cron' );
		$model->process( false );
		exit('triggered: cronjstrigger');
	}

}
