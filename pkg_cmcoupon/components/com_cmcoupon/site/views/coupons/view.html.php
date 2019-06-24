<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class CmCouponSiteViewCoupons extends CmCouponSiteViewConnect {
	function display($tpl = null) {
	
		$db 	  = JFactory::getDBO();
		$document = JFactory::getDocument();
		$pathway  = JFactory::getApplication()->getPathway();
		$params = JFactory::getApplication()->getParams();
		
		JHTML::_('behavior.modal');
		
		$layoutName = AC()->helper->get_request( 'layout', 'default' );

		switch($layoutName) {
			case 'image': {
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

				
				$url = $this->get( 'CouponImageUrl');
				$this->assignRef('url', $url); 
				$this->assignRef('params',	$params);
				break;
			}
			default: {
				$page_title = JText::_('COM_CMCOUPON_CP_COUPONS');
				$rows      	= $this->get( 'Data');
				$pageNav 	= $this->get( 'Pagination' );

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


				$filter_order		= JFactory::getApplication()->getUserStateFromRequest( 'com_cmcoupon.coupons.filter_order', 	'filter_order', 	'coupon_code', 'cmd' );
				$filter_order_Dir	= JFactory::getApplication()->getUserStateFromRequest( 'com_cmcoupon.coupons.filter_order_Dir',	'filter_order_Dir',	'', 'word' );
				
				$lists['order_Dir'] = $filter_order_Dir;
				$lists['order'] = $filter_order;


				//$this->assignRef('lists',	$lists);

				$this->assignRef('rows',	$rows);
				$this->assignRef('pageNav' 		, $pageNav);
				$this->assignRef('params',	$params);
				$this->assignRef('lists',	$lists);
			}
		}


		
		
		parent::display($tpl);
	}
}
