<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

if ( ! class_exists( 'cmcoupon' ) ) {
	if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php' ) ) {
		return false;
	}
	require JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php';
}
if ( ! class_exists( 'cmcoupon' ) ) {
	return false;
}
CmCoupon::instance();
AC()->init();

$task = AC()->helper->get_request( 'task' );
$valid_anonymouse_tasks = array(
	'cron',
	'cronjs',
);

if( ! in_array($task,$valid_anonymouse_tasks)) {
// check if logged in
	$app = JFactory::getApplication();
	$user = JFactory::getUser();
	if(empty($user->id)) { 
		$redirect_url = version_compare( JVERSION, '1.6.0', 'ge' ) 
				? 'index.php?option=com_users&view=login'
				: 'index.php?option=com_user&view=login'
				;

		$return_url = AC()->helper->get_request( 'return' );
		if (!empty($return_url)) {
			$return_url = base64_decode($return_url);
			if (!JURI::isInternal($return_url)) $return_url = '';
		}
		if (empty($return_url)) $return_url = 'index.php?'.JURI::buildQuery(JSite::getRouter()->getVars());
		$redirect_url .='&return='.urlencode(base64_encode($return_url));

		$app->enqueueMessage(JText::_( version_compare( JVERSION, '1.6.0', 'ge' ) ? 'JGLOBAL_YOU_MUST_LOGIN_FIRST' : 'YOU MUST LOGIN FIRST' ), 'error');
		$app->redirect( $redirect_url );
	}
}

$jlang = JFactory::getLanguage();
$jlang->load('com_cmcoupon', JPATH_SITE, 'en-GB', true);
$jlang->load('com_cmcoupon', JPATH_SITE, $jlang->getDefault(), true);
$jlang->load('com_cmcoupon', JPATH_SITE, null, true);
$jlang->load('com_cmcoupon', JPATH_SITE.'/components/com_cmcoupon/', 'en-GB', true);
$jlang->load('com_cmcoupon', JPATH_SITE.'/components/com_cmcoupon/', $jlang->getDefault(), true);
$jlang->load('com_cmcoupon', JPATH_SITE.'/components/com_cmcoupon/', null, true);

require_once JPATH_ROOT.'/components/com_cmcoupon/controller.php';

$controller = new CmCouponSiteController( );
$controller->registerTask( 'results', 'display' );
$controller->execute($task);
$controller->redirect();
