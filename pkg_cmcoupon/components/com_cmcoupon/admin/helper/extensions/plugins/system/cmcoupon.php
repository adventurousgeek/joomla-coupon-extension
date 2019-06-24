<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined( '_JEXEC' ) or die( 'Restricted access' );


jimport('joomla.event.plugin');
class plgSystemCmCoupon extends JPlugin {

	public function onAfterRoute() {
		if ( JFactory::getApplication()->isAdmin() ) {
			$option = version_compare( JVERSION, '2.5', '>=' ) ? JFactory::getApplication()->input->getCmd( 'option' ) : JRequest::getCmd( 'option' );
			if ( in_array( $option, array( 'com_installer' ) ) ) {
				return;
			}
		}
		if ( ! class_exists( 'cmcoupon' ) ) {
			if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php' ) ) {
				return;
			}
			require JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php';
		}
		if ( ! class_exists( 'cmcoupon' ) ) {
			return;
		}
		CmCoupon::instance();
		AC()->init();

		$this->checkCron();
		$this->checkCouponFromLink();
	}

	protected function checkCron() {
		if ( (int) AC()->param->get('cron_enable', 0) != 1 ) {
			return;
		}

		$cron_url = JURI::root(true).'/index.php?option=com_cmcoupon&task=cronjs&time='.time();
		JFactory::getDocument()->addScriptDeclaration ( '
			window.addEventListener("load", function (){
				var myelement = document.createDocumentFragment();
				var temp = document.createElement("div");
				temp.innerHTML = \'<img src="'.$cron_url.'" alt="" width="0" height="0" style="border:none;margin:0; padding:0"/>\';
				while (temp.firstChild) {
					myelement.appendChild(temp.firstChild);
				}
				document.body.appendChild(myelement);
			});
		');
	}

    protected function checkCouponFromLink() {

		if ( ! AC()->is_request( 'frontend' ) ) {
			return;
		}

		$code = trim( AC()->helper->get_request( 'addcoupontocart' ) );
		if ( ! empty( $code ) ) {
			AC()->helper->set_session( 'site', 'link_coupon_code', $code );
		}

		$coupon_code = AC()->helper->get_session( 'site', 'link_coupon_code', '' );
		if ( empty( $coupon_code ) ) {
			return;
		}

		$is_processed = AC()->helper->get_session( 'site', 'link_coupon_code_processed-' . $coupon_code, false );
		if ( ! $is_processed ) {
			if ( AC()->store->add_coupon_to_cart( $coupon_code ) ) {
				AC()->helper->set_session( 'site', 'link_coupon_code_processed-' . $coupon_code, true );
				AC()->helper->reset_session( 'site', 'link_coupon_code' );
			}
		}

		return;
	}

}
