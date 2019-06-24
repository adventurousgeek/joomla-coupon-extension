<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class CmcouponSiteViewGiftcerts extends CmCouponSiteViewConnect {
	
	function display($tpl = null) {
		$document = JFactory::getDocument();
		$pathway  = JFactory::getApplication()->getPathway();
		$params = JFactory::getApplication()->getParams();
		
		$page_title = JText::_('COM_CMCOUPON_GC_GIFTCERTS');

		//Set page title information
		$menus	= JFactory::getApplication()->getMenu();
		$menu	= $menus->getActive();
		if (is_object( $menu )) {
			if(version_compare( JVERSION, '1.6.0', 'ge' )) {
				$menu_params = json_decode($menu->params);
				if (!$menu_params->page_title) $params->set('page_title',	$page_title);
			} 
			else {
				if(!class_exists('JParameter')) jimport( 'joomla.html.parameter' );
				$menu_params = new JParameter( $menu->params );
				if (!$menu_params->get( 'page_title')) $params->set('page_title',	$page_title);
			}
		} else {
			$params->set('page_title',	$page_title);
		}
		$document->setTitle( $params->get( 'page_title' ) );

		
		$rows = $this->get( 'Data');
		$pageNav 	= $this->get( 'Pagination' );

		$balance = AC()->helper->customer_balance( CMCOUPON_ESTORE, true );
		$this->assignRef('rows', $rows); 
		$this->assignRef('pageNav' 		, $pageNav);
		$this->assignRef('balance', $balance ); 
		$this->assignRef('params',	$params);
		
		parent::display($tpl);
	}

}
