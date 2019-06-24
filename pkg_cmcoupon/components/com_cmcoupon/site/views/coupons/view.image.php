<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 * Originally created by Stanislav Scholtz, RuposTel.com
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class CmcouponSiteViewCoupons extends CmCouponSiteViewConnect {
	
	/**
	 * Display the view
	 *
	 * @return	mixed	False on error, null otherwise.
	 */
	function display($tpl = null) {
		$tpl = 'raw';
		$this->setLayout('image');

		$model = $this->getModel();
		
		$file = AC()->helper->get_request( 'file', '' );
		$b64 = $model->getRawCouponImage($file); 
		$image_raw = $extension = '';
		if (!empty($b64)) {
			$fi = pathinfo($file); 
			$extension = strtolower($fi['extension']);
			$image_raw = base64_decode($b64);  
		}
		
		
		$this->assignRef('image_raw', $image_raw); 
		$this->assignRef('extension', $extension); 
		parent::display($tpl);
	}

}
