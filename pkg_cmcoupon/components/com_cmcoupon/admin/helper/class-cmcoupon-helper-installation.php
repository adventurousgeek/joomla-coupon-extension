<?php
/**
 * CmCoupon
 *
 * @package Joomla CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined('_JEXEC') or die('Restricted access');
if ( ! defined( '_CM_' ) ) {
	exit;
}

class CmCoupon_Helper_Installation {

	public function __construct() {
	}

	public function is_installation() {
		return true;
	}

	public function get_definition_file() {
		return	array();
	}	

	public function get_definition_sql() {
		return array();
	}
	
	public function get_definition_plugin() {
		return array(
			'GLOBAL_system_cmcoupon' => array(
				'func' => 'plugin_installer',
				'name' => 'System - CmCoupon',
				'folder' => 'system',
				'dir' => JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/extensions/plugins/system',
				'desc' => '',
			),
			'GLOBAL_mod_cmcoupon_balance' => array(
				'func' => 'module_installer',
				'name' => 'CmCoupon Customer Balance (Module)',
				'module' => 'mod_cmcoupon_balance',
				'dir' => JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/extensions/modules/mod_cmcoupon_balance',
				'desc' => '',
			),
		);
	}

	public function plugin_installer( $type, $key, $plugins = null ) {
		if ( ! in_array( $type, array( 'check', 'install', 'uninstall' ) ) ) {
			return new Exception( AC()->lang->__( 'Invalid type' ) );
		}
		if ( is_null( $plugins ) ) {
			$plugins = $this->get_definition_plugin();
			if ( ! isset( $plugins[ $key ] ) ) {
				return new Exception( AC()->lang->__( 'Invalid key' ) );
			}
		}

		switch( $type ) {
			case 'install': {
				$install_class = AC()->helper->new_class( 'CmCoupon_Helper_Update' );
				return $install_class->install_plugin_packages( $plugins[ $key ]['dir'] );
			}				
			case 'uninstall': {
				$install_class = AC()->helper->new_class( 'CmCoupon_Helper_Update' );
				return $install_class->uninstall_plugin_packages( $plugins[ $key ]['dir'] );
			}
			case 'check': {
				$folder = $plugins[ $key ]['folder'];
				if(version_compare( JVERSION, '1.6.0', 'ge' )) {
					$data = AC()->db->get_object( '
						SELECT extension_id as id,name,element,folder,client_id,enabled,access,params,checked_out,checked_out_time,ordering,CONCAT(folder,"-",element) as keyid
						  FROM #__extensions
						 WHERE type="plugin" AND element="cmcoupon" AND folder="' . $folder . '"
					' );
				}
				else {
					$data = AC()->db->get_object( '
						SELECT id,name,element,folder,client_id,published as enabled,access,params,checked_out,checked_out_time,ordering,CONCAT(folder,"-",element) as keyid
						  FROM #__plugins 
						 WHERE element="cmcoupon" AND folder="' . $folder . '"
					' );
				}
				if ( ! empty( $data ) && $data->enabled != 1 ) {
					$data->error = AC()->lang->__( 'Not published' );
				}

				return $data;
			}
		}
	}

	public function module_installer( $type, $key, $modules = null ) {
		if ( ! in_array( $type, array( 'check', 'install', 'uninstall' ) ) ) {
			return new Exception( AC()->lang->__( 'Invalid type' ) );
		}
		if ( is_null( $modules ) ) {
			$modules = $this->get_definition_plugin();
			if ( ! isset( $modules[ $key ] ) ) {
				return new Exception( AC()->lang->__( 'Invalid key' ) );
			}
		}

		switch( $type ) {
			case 'install': {
				$install_class = AC()->helper->new_class( 'CmCoupon_Helper_Update' );
				return $install_class->install_module_packages( $modules[ $key ]['dir'] );
			}				
			case 'uninstall': {
				$install_class = AC()->helper->new_class( 'CmCoupon_Helper_Update' );
				return $install_class->uninstall_module_packages( $modules[ $key ]['dir'] );
			}
			case 'check': {
				$module = $modules[ $key ]['module'];
				if(version_compare( JVERSION, '1.6.0', 'ge' )) {
					$data = AC()->db->get_object( '
						SELECT extension_id as id,name,element,client_id,enabled,access,params,checked_out,checked_out_time,ordering,CONCAT(element) as keyid
						  FROM #__extensions
						 WHERE type="module" AND element="' . $module . '"
					' );
				}
				else {
					$data = AC()->db->get_object( '
						SELECT id,title AS name,module AS element,client_id,published as enabled,access,params,checked_out,checked_out_time,ordering,CONCAT(module) as keyid
						  FROM #__modules 
						 WHERE module="' . $module . '"
					' );
				}
				if ( ! empty( $data ) && $data->enabled != 1 ) {
					$data->error = AC()->lang->__( 'Not published' );
				}

				return $data;
			}
		}
	}

}
