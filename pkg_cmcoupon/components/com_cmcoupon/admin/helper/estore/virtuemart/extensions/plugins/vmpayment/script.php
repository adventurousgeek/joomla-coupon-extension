<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
  
class plgVmPaymentCmCouponInstallerScript {

	function preflight($route, $adapter) {}

	function install($adapter) {}

	function update($adapter) {}

	function uninstall($adapter) {}

	function postflight($route, $adapter) {
		if (stripos($route, 'install') !== false || stripos($route, 'update') !== false) {
			return $this->fixManifest($adapter);
		}
	}
	
	private function fixManifest($adapter) {
		$filesource = $adapter->get('parent')->getPath('source').'/cmcoupon_j3.xml';
		$filedest = $adapter->get('parent')->getPath('extension_root').'/cmcoupon.xml';
		
		if (!(JFile::copy($filesource, $filedest))) {
			JLog::add(JText::sprintf('JLIB_INSTALLER_ERROR_FAIL_COPY_FILE', $filesource, $filedest), JLog::WARNING, 'jerror');
             
			if (class_exists('JError')) {
				JFactory::getApplication()->enqueueMessage( 'JInstaller::install: ' . JText::sprintf( 'Failed to copy file to', $filesource, $filedest ), 'error' );
			}
			else {
				throw new Exception('JInstaller::install: '.JText::sprintf('Failed to copy file to', $filesource, $filedest));
			}
			return false;
		}
		
		@unlink($adapter->get('parent')->getPath('extension_root').'/cmcoupon_j3.xml');
         
		return true;
	}
}