<?php
/**
 * @component CmCoupon Pro
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

defined( '_JEXEC' ) or die( 'Restricted access' );


jimport('joomla.event.plugin');
class plgSystemCmCoupon extends JPlugin {

	function onAfterRoute() {
		{ // verify config table exists
			$p__ = JFactory::getConfig()->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'} ( 'dbprefix' );
			if(! in_array($p__.'cmcoupon_config', JFactory::getDbo()->getTableList())) return;
		}
		if( ! class_exists('cmParams') ) require JPATH_ADMINISTRATOR.'/components/com_cmcoupon/helpers/cmparams.php';
		$cmparams = new cmParams();
		if((int)$cmparams->get('cron_enable', 0) != 1) return;

		$document = JFactory::getDocument();
		$cron_url = JURI::root(true).'/index.php?option=com_cmcoupon&task=cronjs&time='.time();
		$document->addScriptDeclaration ( '
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

	
}
