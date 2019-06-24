<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
  
class plgHikashopCmCouponInstallerScript {

	function preflight($route, $adapter) {}

	function install($adapter) {}

	function update($adapter) {}

	function uninstall($adapter) {}

	function postflight($route, $adapter) {
		if (stripos($route, 'install') !== false) {
		// order the plugin before "free order" hikashop plugin
			$db = JFactory::getDBO();
			$db->setQuery('SELECT extension_id FROM #__extensions WHERE folder="hikashop" AND element="cmcoupon"');
			$extension_id = (int)$db->loadResult();
			if(!empty($extension_id)) {
				$db->setQuery('UPDATE #__extensions SET ordering=-100 WHERE extension_id='.(int)$extension_id);
				$db->query();
			}
		}
		
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