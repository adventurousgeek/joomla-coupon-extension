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

AC()->helper->add_class( 'CmCoupon_Library_Update' );

class CmCoupon_Helper_Update extends CmCoupon_Library_Update {

	public function __construct() {
		parent::__construct();

		$this->is_debug = false;
	}

	public function install() {

		AC()->init( true );
		$this->init();

		// database
		$this->run_sql_file( CMCOUPON_DIR . '/cmcoupon/library/install/mysql.install.sql', CMCOUPON_VERSION );
		AC()->init( false, true );

		// install injections
		$this->install_injections( 'file' );
		$this->install_injections( 'sql' );
		$this->install_injections( 'plugin' );

		// language install and template file
		$this->install_languages();

		$this->install_module_packages( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/extensions/modules' );

		if ( $this->install_plugin_packages( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/extensions/plugins' ) ) {
			AC()->helper->set_message( AC()->lang->__( 'CmCoupon Plugins Installation: <span style="color:green;">Successful</span>' ) );
		}

		$this->update_db_version( CMCOUPON_VERSION );

		// Clear Caches
		AC()->helper->reset_cache();

		return true;
	}

	public function update() {

		$p__ = JFactory::getConfig()->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'} ( 'dbprefix' );
		if ( $this->is_table_exists( $p__ . 'cmcoupon_config' ) ) {
			AC()->init( false, true );
		}
		else {
			AC()->init( true );
		}
		$this->init();

		$current_db_version = $this->get_db_version();

		$_max = 100;
		while ( version_compare( $current_db_version, '3.0.0', '<' ) ) {
			$_max --;
			$this->update_before_300();
			$current_db_version = $this->get_db_version();
			if ( $_max == 0 ) {
				exit( 'Something went very wrong' );
			}
		}
		$this->install_tableupdates( $current_db_version, CMCOUPON_VERSION );

		$this->install_injections( 'plugin' );

		// language install and template file
		$this->install_languages();

		$this->install_module_packages( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/extensions/modules' );

		if ( $this->install_plugin_packages( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/extensions/plugins' ) ) {
			AC()->helper->set_message( AC()->lang->__( 'CmCoupon Plugins Installation: <span style="color:green;">Successful</span>' ) );
		}

		// Clear Caches
		AC()->helper->reset_cache();

		return true;
	}

	public function uninstall() {

		AC()->init( false, true );
		$this->init();

		// install injections
		$this->uninstall_injections();

		$this->uninstall_module_packages( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/extensions/modules' );

		if ( $this->uninstall_plugin_packages( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/extensions/plugins' ) ) {
			AC()->helper->set_message( AC()->lang->__( 'CmCoupon Plugins Uninstallation: <span style="color:green;">Successful</span>' ) );
		}

		$this->uninstall_languages();

		# drop tables
		$this->run_sql_file( CMCOUPON_DIR . '/cmcoupon/library/install/mysql.uninstall.sql', CMCOUPON_VERSION );

		return true;
	}

	public function update_db_version( $version = null ) {
		AC()->param->set( 'CMCOUPON_VERSION_DB', is_null( $version ) ? CMCOUPON_VERSION : $version );
	}

	public function get_db_version() {
		$current_db_version = AC()->param->get( 'CMCOUPON_VERSION_DB' );
		if ( empty( $current_db_version ) ) {
			$current_db_version = 0;
		}
		return $current_db_version;
	}

	public function get_version() {
		$found_xml_file = '';
		if ( file_exists( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/cmcoupon_j3.xml' ) ) {
			$found_xml_file = 'cmcoupon_j3.xml';
		}
		elseif ( file_exists( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/cmcoupon.xml' ) ) {
			$found_xml_file = 'cmcoupon.xml';
		}
		if ( empty( $found_xml_file ) ) {
			$current_version = 0;
		}
		else {
			$contents = file_get_contents( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/' . $found_xml_file );
			preg_match( '/\<version\>(.*?)\<\/version\>/i', $contents, $matches );
			$current_version = $matches[1];
		}

		return $current_version;
	}

	public function install_languages() {
		jimport('joomla.filesystem.file');
		JFile::copy( JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_cmcoupon.ini', JPATH_ADMINISTRATOR . '/components/com_cmcoupon/assets/language.ini' );

		if ( version_compare( JVERSION, '1.6.0', '<' ) ) {
			return;
		}

		$rows = AC()->db->get_objectlist( 'SELECT DISTINCT element  FROM #__extensions WHERE type="language" AND element!="en-GB"' );
		if ( empty( $rows ) ) {
			return;
		}

		$languages = array(
			'bg-BG'=>(object) array( 'id'=>'bg-BG', 'name'=>'Bulgarian', 'is_installed'=>false, ),
			'ca-ES'=>(object) array( 'id'=>'ca-ES', 'name'=>'Catalan', 'is_installed'=>false, ),
			'cs-CZ'=>(object) array( 'id'=>'cs-CZ', 'name'=>'Czech', 'is_installed'=>false, ),
			'de-DE'=>(object) array( 'id'=>'de-DE', 'name'=>'German', 'is_installed'=>false, ),
			'en-GB'=>(object) array( 'id'=>'en-GB', 'name'=>'English', 'is_installed'=>true, ),
			'es-ES'=>(object) array( 'id'=>'es-ES', 'name'=>'Spanish', 'is_installed'=>false, ),
			'fr-FR'=>(object) array( 'id'=>'fr-FR', 'name'=>'French', 'is_installed'=>false, ),
			'it-IT'=>(object) array( 'id'=>'it-IT', 'name'=>'Italian', 'is_installed'=>false, ),
			'nl-NL'=>(object) array( 'id'=>'nl-NL', 'name'=>'Dutch', 'is_installed'=>false, ),
			'pl-PL'=>(object) array( 'id'=>'pl-PL', 'name'=>'Polish', 'is_installed'=>false, ),
			'ru-RU'=>(object) array( 'id'=>'ru-RU', 'name'=>'Russian', 'is_installed'=>false, ),
			'sk-SK'=>(object) array( 'id'=>'sk-SK', 'name'=>'Slovak', 'is_installed'=>false, ),
			'sv-SE'=>(object) array( 'id'=>'sv-SE', 'name'=>'Swedish', 'is_installed'=>false, ),
		);
		asort($languages);
		$admin_folder = JPATH_ADMINISTRATOR . '/language/%s/%s.com_cmcoupon.ini';
		$site_folder = JPATH_ROOT . '/language/%s/%s.com_cmcoupon.ini';
		foreach ( $languages as &$lang ) {
			$lang->is_override = false;
			if(	
					file_exists(str_replace('%s',$lang->id,$admin_folder))
				&&	file_exists(str_replace('%s',$lang->id,$site_folder))
			) $lang->is_installed = true;
			if(
					file_exists(JPATH_ADMINISTRATOR.'/components/com_cmcoupon/language/'.$lang->id.'/'.$lang->id.'.com_cmcoupon.ini')
				||	file_exists(JPATH_ROOT.'/components/com_cmcoupon/language/'.$lang->id.'/'.$lang->id.'.com_cmcoupon.ini')
			) $lang->is_override = true;

		}

		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.archive');
		$url = 'https://cmdev.com/extensions/download/language/joomla/cmcoupon';

		foreach ( $rows as $row ) {
			$code = $row->element;
		
			if ( ! isset( $languages[ $code ] ) ) {
				continue;
			}

			$vars = array(
				'download_url' => $url . '/'.$code,
				'target_path' => JPATH_SITE . '/tmp/com_cmcoupon_' . $code . '.zip',
				'temp_dir' => JPATH_SITE . '/tmp/com_cmcoupon_' . $code . '_update',
			);

			// delete folder and file if exists
			if ( is_dir( $vars['temp_dir'] ) ) {
				JFolder::delete( $vars['temp_dir'] );
			}
			if ( file_exists( $vars['target_path'] ) ) {
				JFile::delete( $vars['target_path'] );
			}

			// download
			$fp = fopen( $vars['target_path'], 'x' );
			if ( ! $fp ) {
				continue;
			}
			fclose( $fp );
			$args = array(
				'timeout' => 300,
				'stream' => true,
				'filename' => $vars['target_path'],
				'method' => 'GET',
			);
			$http_class = AC()->helper->new_class( 'Cmcoupon_Helper_Http' );
			$response = $http_class->request( $vars['download_url'], $args );
			if ( ( $response instanceof Exception ) ) {
				if ( is_dir( $vars['temp_dir'] ) ) {
					JFolder::delete( $vars['temp_dir'] );
				}
				if ( file_exists( $vars['target_path'] ) ) {
					JFile::delete( $vars['target_path'] );
				}
				continue;
			}

			// extract
			$result = JArchive::extract( $vars['target_path'], $vars['temp_dir']);
			if ( ! $result ) {
				if ( is_dir( $vars['temp_dir'] ) ) {
					JFolder::delete( $vars['temp_dir'] );
				}
				if ( file_exists( $vars['target_path'] ) ) {
					JFile::delete( $vars['target_path'] );
				}
				continue;
			}

			// install
			try {
				$ret = JFolder::copy( $vars['temp_dir'] . '/administrator/language', JPATH_ROOT . '/administrator/language', '', true );
			}
			catch ( Exception $e ) {
				$ret = false;
			}

			if ( ! $ret ) {
				if ( is_dir( $vars['temp_dir'] ) ) {
					JFolder::delete( $vars['temp_dir'] );
				}
				if ( file_exists( $vars['target_path'] ) ) {
					JFile::delete( $vars['target_path'] );
				}
				continue;
			}
			try {
				JFolder::copy( $vars['temp_dir'] . '/language', JPATH_ROOT . '/language', '', true );
			}
			catch ( Exception $e ) {
			}

			if ( is_dir( $vars['temp_dir'] ) ) {
				JFolder::delete( $vars['temp_dir'] );
			}
			if ( file_exists( $vars['target_path'] ) ) {
				JFile::delete( $vars['target_path'] );
			}
		}
	}

	public function uninstall_languages() {

		$files = array();
		foreach ( glob( JPATH_ROOT . '/administrator/language/*/*.com_cmcoupon*.ini' ) as $f ) {
			if ( strpos( $f, 'en-GB.' ) === false ) {
				$files[] = $f;
			}
		}
		foreach ( glob( JPATH_ROOT . '/language/*/*.com_cmcoupon*.ini' ) as $f ) {
			if ( strpos( $f, 'en-GB.' ) === false ) {
				$files[] = $f;
			}
		}

		if ( empty( $files ) ) {
			return;
		}

		jimport( 'joomla.filesystem.file' );
		JFile::delete( $files );
	}

	public function install_injections( $type ) {

		$estores = AC()->helper->get_installed_estores();
		if ( empty( $estores ) ) {
			return;
		}

		foreach($estores as $estore) {
			$estore_class = AC()->helper->new_class( 'Cmcoupon_Helper_Estore_' . $estore . '_Installation' );
			if ( ! empty( $estore_class ) && $estore_class->is_installation() ) {
				$inject_class = AC()->helper->new_class( 'CmCoupon_Admin_Class_Installation' );
				$inject_class->change_estore( $estore );
				$ret = $inject_class->install_all( $type );
				if ( $ret instanceof Exception ) {
					AC()->helper->set_message( sprintf(
						AC()->lang->__( '%s Installation: <span style="color:red;">Unsuccessful</span>' ),
						ucfirst( $estore )
					), 'error' );
				}
				else {
					AC()->helper->set_message( sprintf(
						AC()->lang->__( '%s Installation: <span style="color:green;">Successful</span>' ),
						ucfirst( $estore )
					) );
				}
			}
		}

	}

	public function uninstall_injections() {

		$estores = AC()->helper->get_installed_estores();
		if ( empty( $estores ) ) {
			return;
		}

		foreach($estores as $estore) {
			$estore_class = AC()->helper->new_class( 'Cmcoupon_Helper_Estore_' . $estore . '_Installation' );
			if ( ! empty( $estore_class ) && $estore_class->is_installation() ) {
				$inject_class = AC()->helper->new_class( 'CmCoupon_Admin_Class_Installation' );
				$inject_class->change_estore( $estore );
				$ret = $inject_class->uninstall_all();
				if ( $ret instanceof Exception ) {
					AC()->helper->set_message( sprintf(
						AC()->lang->__( '%s Uninstallation: <span style="color:red;">Unsuccessful</span>' ),
						ucfirst( $estore )
					), 'error' );
				}
				else {
					AC()->helper->set_message( sprintf(
						AC()->lang->__( '%s Uninstallation: <span style="color:green;">Successful</span>' ),
						ucfirst( $estore )
					) );
				}
			}
		}
	}

	public function install_plugin_packages( $dir ) {
		$plugins = array();

		if ( ! class_exists( 'JFolder' ) ) {
			jimport( 'joomla.filesystem.folder' );
		}
		$files = JFolder::files( $dir, '\.xml$', 1, true );
		if ( empty( $files ) ) {
			return false;
		}

		foreach ( $files as $file ) {
			$plugins = version_compare( JVERSION, '1.6.0', 'ge' )
						? $this->extension_attr_25( $plugins, $file )
						: $this->extension_attr_15( $plugins, $file );
		}

		if ( empty( $plugins ) ) {
			return false;
		}
		foreach ( $plugins as $plugin ) {
			if ( ! $this->install_plugin($plugin ) ) {
				return false;
			}
		}

		return true;
		
	}

	public function install_plugin($plugin) {
		if ( empty( $plugin['dir'] ) || empty( $plugin['name'] ) || empty( $plugin['group'] ) ) {
			return false;
		}

		jimport( 'joomla.installer.installer' );
		$installer = new JInstaller;

		if ( ! $installer->install( $plugin['dir'] ) ) {
			return false;
		}

		$sql = version_compare( JVERSION, '1.6.0', 'ge' )
					? 'UPDATE #__extensions SET enabled=1 WHERE type="plugin" AND element="' . AC()->db->escape( $plugin['name'] ) . '" AND folder="' . AC()->db->escape( $plugin['group'] ) . '"'
					: 'UPDATE #__plugins SET published=1 WHERE element="' . AC()->db->escape( $plugin['name'] ) . ' AND folder=' . AC()->db->escape( $plugin['group'] ) . '"'
		;
		AC()->db->query($sql);
		return true;
	}

	public function uninstall_plugin_packages( $dir ) {
		$plugins = array();

		if ( ! class_exists( 'JFolder' ) ) {
			jimport( 'joomla.filesystem.folder');
		}
		$files = JFolder::files( $dir, '\.xml$', 1, true );
		if ( ! count( $files ) ) {
			return false;
		}
		foreach ( $files as $file ) {
			$plugins = version_compare( JVERSION, '1.6.0', 'ge' )
						? $this->extension_attr_25( $plugins, $file )
						: $this->extension_attr_15( $plugins, $file );
		}

		if ( empty( $plugins ) ) {
			return false;
		}

		jimport('joomla.installer.installer');
		$installer = new JInstaller;

		foreach( $plugins as $plugin ) {
			$sql = version_compare( JVERSION, '1.6.0', 'ge' )
				? 'SELECT `extension_id` AS id FROM #__extensions WHERE `type`="plugin" AND element="' . AC()->db->escape( $plugin['name'] ) . '" AND folder="' . AC()->db->escape( $plugin['group'] ) . '"'
				: 'SELECT `id` FROM #__plugins WHERE element = "' . AC()->db->escape( $plugin['name'] ) . '" AND folder="' . AC()->db->escape( $plugin['group'] ) . '"'
			;
			$ids = AC()->db->get_objectlist( $sql );
			foreach ( $ids as $id ) {
				if ( ! $installer->uninstall( 'plugin', $id->id ) ) {
					return false;
				}
			}
		}

		return true;
	}

	public function install_module_packages( $module_source ) {

		if ( ! class_exists( 'JFolder' ) ) {
			jimport( 'joomla.filesystem.folder');
		}
		$files = JFolder::files( $module_source, '\.xml$', 1, true );
		if ( empty( $files ) ) {
			return false;
		}

		$plugins = array();

		foreach ( $files as $file ) {
			$plugins = version_compare( JVERSION, '1.6.0', 'ge' )
						? self::extension_attr_25( $plugins, $file, 'module' )
						: self::extension_attr_15( $plugins, $file, 'module' );
		}
		if ( empty( $plugins ) ) {
			return false;
		}

		if ( ! empty( $plugins ) ) {		
			jimport( 'joomla.installer.installer' );
			$installer = new JInstaller;
			foreach ( $plugins as $plugin ) { 
				if ( ! $installer->install( $plugin['dir'] ) ) {
					return false;
				}
			}
		}
		return true;
	}

	public function uninstall_module_packages( $module_source ) {

		if ( ! class_exists( 'JFolder' ) ) {
			jimport( 'joomla.filesystem.folder' );
		}
		$files = JFolder::files( $module_source, '\.xml$', 1, true );
		if ( empty( $files ) ) {
			return false;
		}

		$plugins = array();

		foreach ( $files as $file ) {
			$plugins = version_compare( JVERSION, '1.6.0', 'ge' )
				? self::extension_attr_25( $plugins, $file, 'module' )
				: self::extension_attr_15( $plugins, $file, 'module' )
			;
		}
		if ( empty( $plugins ) ) {
			return false;
		}

		if ( ! empty( $plugins ) ) {
			//$installer = JInstaller::getInstance();
			jimport( 'joomla.installer.installer' );
			$installer = new JInstaller;

			foreach ( $plugins as $plugin ) {
				$sql = version_compare( JVERSION, '1.6.0', 'ge' )
					? 'SELECT `extension_id` AS id FROM #__extensions WHERE `type`="module" AND element="' . AC()->db->escape( $plugin['name'] ) . '"'
					: 'SELECT `id` FROM #__modules WHERE module = "' . AC()->db->escape( $plugin['name'] ) . '"'
				;
				$ids = AC()->db->get_objectlist($sql);
				foreach ( $ids as $id ) {
					if ( ! $installer->uninstall( 'module', $id->id ) ) {
						return false;
					}
				}
			}
		}
		return true;
	}

	public function upgrade_function_copy( $tmp_dir ) {
		// install from folder

		jimport( 'joomla.installer.installer' );
		jimport( 'joomla.installer.helper' );
		$installer = JInstaller::getInstance();
		$package_type = JInstallerHelper::detectType( $tmp_dir );

		if ( ! $package_type ) {
			throw new Exception( AC()->lang->__( 'Invalid package type. The update can not proceed.' ) );
		}
		if ( ! $installer->install( $tmp_dir ) ) {
			throw new Exception( AC()->lang->__( 'Error installing component' ) );
		}

		AC()->helper->reset_cache();
		return true;
	}

	public function upgrade_function_dbupdate() {
		$this->init();
		$current_db_version = $this->get_db_version();
		$this->install_tableupdates( $current_db_version, CMCOUPON_VERSION );
		return true;
	}

	private function extension_attr_15( $plugins, $file,$extension_type = 'plugin' ) {

		$xmlDoc = & JFactory::getXMLParser();
		$xmlDoc->resolveErrors( true );

		if ( ! $xmlDoc->loadXML( $file, false, true ) ) {
			unset ( $xmlDoc );
			return $plugins;
		}
		$root = & $xmlDoc->documentElement;
		if ( ! is_object( $root ) || ( $root->getTagName() != "install" && $root->getTagName() != 'mosinstall' ) ) {
			unset( $xmlDoc );
			return $plugins;
		}

		$p_group = $p_name = '';
		$type = $root->getAttribute( 'type' );
		$p_group = $root->getAttribute( 'group' );
		foreach ( $root->childNodes as $k => $item ) {
			if ( $item->nodeName == 'files' ) {
				$item2 = $item->toArray();
				foreach( $item2['files'] as $item2_row ) {
					if ( ! empty( $item2_row['filename']['attributes'][ $extension_type ] ) ) {
						$p_name = $item2_row['filename']['attributes'][ $extension_type ];
						break 2;
					}
				}
			}
		}

		unset ( $xmlDoc );

		if ( $extension_type == 'plugin' ) {
			if ( ! empty( $p_group ) && ! empty( $p_name ) ) {
				$plugins[] = array( 'dir' => dirname( $file ), 'group' => $p_group, 'name' => $p_name );
			}
		}
		elseif ( $extension_type == 'module' ) {
			if ( ! empty( $p_name ) ) {
				$plugins[] = array( 'dir' => dirname( $file ), 'group' => $p_group, 'name' => $p_name );
			}
		}

		return $plugins;
	}

	private function extension_attr_25( $plugins, $file, $extension_type='plugin' ) {

		if ( ! $xml = JFactory::getXML( $file ) ) {
			return;
		}
		if ( $xml->getName() != 'install' && $xml->getName() != 'extension') {
			unset( $xml );
			return;
		}

		$p_group = $p_name = '';
		$type = (string) $xml->attributes()->type;
		$p_group = (string) $xml->attributes()->group;
		$p_name = (string) $xml->files->filename->attributes()->{$extension_type};
		unset( $xml );

		if ( $extension_type == 'plugin' ) {
			if ( ! empty( $p_group ) && ! empty( $p_name ) ) {
				$plugins[] = array( 'dir' => dirname( $file ), 'group' => $p_group, 'name' => $p_name );
			}
		}
		elseif ( $extension_type == 'module' ) {
			if ( ! empty( $p_name ) ) {
				$plugins[] = array( 'dir' => dirname( $file ), 'group' => $p_group, 'name' => $p_name );
			}
		}

		return $plugins;
	}

	public function on_license_activate() {
		if ( version_compare( JVERSION, '2.5.0', '<' ) ) {
			return;
		}

		$extension_id = AC()->db->get_value( 'SELECT extension_id FROM #__extensions WHERE type="component" AND element="com_cmcoupon"' );

		$update_site_id = (int) AC()->db->get_value( 'SELECT update_site_id FROM #__update_sites_extensions WHERE extension_id=' . (int) $extension_id );
		if ( empty( $update_site_id ) ) {
			$location = '';
			$found_xml_file = '';
			if ( file_exists( CMCOUPON_DIR . '/cmcoupon_j3.xml' ) ) {
				$found_xml_file = 'cmcoupon_j3.xml';
			}
			elseif ( file_exists( CMCOUPON_DIR . '/cmcoupon.xml' ) ) {
				$found_xml_file = 'cmcoupon.xml';
			}
			if ( ! empty( $found_xml_file ) ) {
				$xml = simplexml_load_file( CMCOUPON_DIR . '/' . $found_xml_file );
				$location = (string) $xml->updateservers->server;
			}

			AC()->db->query( '
				INSERT INTO #__update_sites ( name, type, location, enabled, last_check_timestamp, extra_query )
				VALUES ( "CmCoupon Update", "extension", "' . AC()->db->escape( $location ) . '", 1, 0, NULL )
			' );
			$update_site_id = AC()->db->get_insertid();

			AC()->db->query( 'INSERT INTO #__update_sites_extensions ( update_site_id, extension_id ) VALUES ( ' . (int) $update_site_id . ', ' . (int) $extension_id . ' )' );
		}

		$extra_query = 'NULL';
		$license_class = AC()->helper->new_class( 'CmCoupon_Admin_Class_License' );
		$mycm = $license_class->get_entry();
		if ( ! empty( $mycm->license ) && ! empty( $mycm->website ) ) {
			$extra_query = '"' . AC()->db->escape( http_build_query( array(
				'l' => $mycm->license,
				'w' => $mycm->website,
				'p' => $license_class->product,
			) ) ) . '"';
		}
		AC()->db->query( 'UPDATE #__update_sites SET extra_query=' . $extra_query . ' WHERE update_site_id=' . (int) $update_site_id );
	}

	private function update_before_300() {

		$is_free_update = false;
		$is_free_update_cm1 = false;
		$p__ = JFactory::getConfig()->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'} ( 'dbprefix' );
		$tmp = AC()->db->get_value( 'SHOW TABLES LIKE "' . $p__ . 'cmcoupon_vm"' );
		if(!empty($tmp)) {
			$is_free_update = true;
		}
		else {
			$tmp = AC()->db->get_value( 'SHOW TABLES LIKE "' . $p__ . 'cmcoupon_user_uses"' );
			if(!empty($tmp)) {
				$is_free_update_cm1 = true;
			}
		}

		// install sql
		$orig_upgrade_sql_folder = $this->upgrade_sql_folder;
		$orig_upgrade_php_folder = $this->upgrade_php_folder;

		$this->upgrade_sql_folder = CMCOUPON_DIR . '/helper/install/upgrade/sql';
		$this->upgrade_php_folder = CMCOUPON_DIR . '/helper/install/upgrade/php';

		if ( $is_free_update ) {
			$current_db_version = '1.9.9';
		}
		else {
			$current_db_version = $this->get_db_version();
			if ( empty( $current_db_version ) ) {
				$version = $this->get_version();
				if ( $version !== CMCOUPON_VERSION ) {
					$current_db_version = trim( str_replace( 'pro', '', $version ) );
				}
			}
		}
		$this->install_tableupdates( $current_db_version, '3.0.0' );

		$this->upgrade_sql_folder = $orig_upgrade_sql_folder;
		$this->upgrade_php_folder = $orig_upgrade_php_folder;


		if( $is_free_update_cm1) {
			AC()->db->query( 'UPDATE #__cmcoupon SET estore="virtuemart1"' );
			AC()->db->query( 'UPDATE #__cmcoupon_history SET estore="virtuemart1"' );
		}

		if( $is_free_update || $is_free_update_cm1) {
			$this->install_injections( 'file' );
			$this->install_injections( 'sql' );
		}

		$this->update_before_300_FINAL();

		AC()->init( false, true );
		$this->update_db_version( '3.5.0.1' ); // have to bypass free to pro update
	}
	
	private function update_before_300_FINAL() {

		//$this->run_sql_file( CMCOUPON_DIR . '/helper/install/upgrade/sql/3.0.0.sql', '3.0.0' ); # taken care of in update_before_300, called install_tableupdates

		AC()->db->query( 'UPDATE #__cmcoupon_config SET name="idlang_errCountrystateInclude" WHERE name="idlang_errCountryStateInclude"' );
		AC()->db->query( 'UPDATE #__cmcoupon_config SET name="idlang_errCountrystateExclude" WHERE name="idlang_errCountryStateExclude"' );
		
		$rows = AC()->db->get_objectList('SELECT * FROM #__cmcoupon_profile');
		$elem_ids = array();
		foreach($rows as $row) {
			$coupon_code_config = $row->coupon_code_config;
			if ( empty( $coupon_code_config ) ) {
				$coupon_code_config = 'NULL';
			}
			else {
				$coupon_code_config = unserialize( $coupon_code_config );
				$coupon_code_config = '"' . AC()->db->escape( json_encode( $coupon_code_config ) ) . '"';
			}

			$coupon_value_config = $row->coupon_value_config;
			if ( empty( $coupon_value_config ) ) {
				$coupon_value_config = 'NULL';
			}
			else {
				$coupon_value_config = unserialize( $coupon_value_config );
				$coupon_value_config = '"' . AC()->db->escape( json_encode( $coupon_value_config ) ) . '"';
			}

			$expiration_config = $row->expiration_config;
			if ( empty( $expiration_config ) ) {
				$expiration_config = 'NULL';
			}
			else {
				$expiration_config = unserialize( $expiration_config );
				$expiration_config = '"' . AC()->db->escape( json_encode( $expiration_config ) ) . '"';
			}

			$freetext1_config = $row->freetext1_config;
			if ( empty( $freetext1_config ) ) {
				$freetext1_config = 'NULL';
			}
			else {
				$freetext1_config = unserialize( $freetext1_config );
				$freetext1_config = '"' . AC()->db->escape( json_encode( $freetext1_config ) ) . '"';
			}

			$freetext2_config = $row->freetext2_config;
			if ( empty( $freetext2_config ) ) {
				$freetext2_config = 'NULL';
			}
			else {
				$freetext2_config = unserialize( $freetext2_config );
				$freetext2_config = '"' . AC()->db->escape( json_encode( $freetext2_config ) ) . '"';
			}

			$freetext3_config = $row->freetext3_config;
			if ( empty( $freetext3_config ) ) {
				$freetext3_config = 'NULL';
			}
			else {
				$freetext3_config = unserialize( $freetext3_config );
				$freetext3_config = '"' . AC()->db->escape( json_encode( $freetext3_config ) ) . '"';
			}

			AC()->db->query( '
				UPDATE #__cmcoupon_profile SET
					coupon_code_config=' . $coupon_code_config . '
					,coupon_value_config=' . $coupon_value_config . '
					,expiration_config=' . $expiration_config . '
					,freetext1_config=' . $freetext1_config . '
					,freetext2_config=' . $freetext2_config . '
					,freetext3_config=' . $freetext3_config . '
				 WHERE id=' . $row->id . '
			');
		}

		$items = AC()->db->get_objectlist('SELECT * FROM #__cmcoupon WHERE function_type NOT IN ("buyxy","buyxy2")','id');
		foreach($items as $item) {
			if( ! empty( $item->params ) ) {
				$item->params = json_decode($item->params);
			}
			if ( in_array( $item->function_type, array( 'coupon', 'giftcert' ) ) && (int) $item->exclude_giftcert === 1 ) {
				if ( empty( $item->params ) ) {
					$item->params = new stdclass();
				}
				$item->params->exclude_giftcert = 1;
			}

			if(isset($item->params->asset1_mode) && isset($item->params->asset1_type)) {
				if(!isset($item->params->asset[0]->rows->{$item->params->asset1_type})) @$item->params->asset[0]->rows->{$item->params->asset1_type} = new stdclass;
				$item->params->asset[0]->rows->{$item->params->asset1_type}->type = $item->params->asset1_type;
				$item->params->asset[0]->rows->{$item->params->asset1_type}->mode = $item->params->asset1_mode;
				unset($item->params->asset1_mode);
				unset($item->params->asset1_type);
			}
			if(isset($item->params->asset2_mode) && isset($item->params->asset2_type)) {
				if(!isset($item->params->asset[0]->rows->{$item->params->asset2_type})) @$item->params->asset[0]->rows->{$item->params->asset2_type} = new stdclass;
				$item->params->asset[0]->rows->{$item->params->asset2_type}->type = $item->params->asset2_type;
				$item->params->asset[0]->rows->{$item->params->asset2_type}->mode = $item->params->asset2_mode;
				unset($item->params->asset2_mode);
				unset($item->params->asset2_type);
			}
			if(isset($item->params->user_mode) && in_array($item->user_type, array('user','usergroup'))) {
				if(!isset($item->params->asset[0]->rows->{$item->user_type})) @$item->params->asset[0]->rows->{$item->user_type} = new stdclass;
				$item->params->asset[0]->rows->{$item->user_type}->type = $item->user_type;
				$item->params->asset[0]->rows->{$item->user_type}->mode = $item->params->user_mode;
				unset($item->params->user_mode);
			}
			if(isset($item->params->countrystate_mode)) {
				if(!isset($item->params->asset[0]->rows->countrystate)) @$item->params->asset[0]->rows->countrystate = new stdclass;
				$item->params->asset[0]->rows->countrystate->type = 'countrystate';
				$item->params->asset[0]->rows->countrystate->mode = $item->params->countrystate_mode;
				unset($item->params->countrystate_mode);
			}
			if(isset($item->params->country_mode)) {
				if ( ! isset( $item->params->asset[0]->rows->country ) ) @$item->params->asset[0]->rows->country = new stdclass;
				$item->params->asset[0]->rows->country->type = 'country';
				$item->params->asset[0]->rows->country->mode = $item->params->countrystate_mode;
				unset($item->params->country_mode);
			}
			if(isset($item->params->paymentmethod_mode)) {
				if ( ! isset( $item->params->asset[0]->rows->paymentmethod ) ) @$item->params->asset[0]->rows->paymentmethod = new stdclass;
				$item->params->asset[0]->rows->paymentmethod->type = 'paymentmethod';
				$item->params->asset[0]->rows->paymentmethod->mode = $item->params->paymentmethod_mode;
				unset($item->params->paymentmethod_mode);
			}
			if($item->function_type == 'combination') {
				$item->params->process_type = $item->parent_type;
				if(!isset($item->params->asset[0]->rows->coupon)) @$item->params->asset[0]->rows->coupon = new stdclass;
				$item->params->asset[0]->rows->coupon->type = 'coupon';
				unset($item->params->asset1_type);
			}

			if ( ! empty( $item->params ) ) {
				$item->params = (array)$item->params;
				$params = empty($item->params) ? 'NULL' : '"'.AC()->db->escape(json_encode($item->params)).'"';
				$this->log_query('UPDATE #__cmcoupon SET params='.$params.' WHERE id='.$item->id, '3.0.0');
			}
		}
		$items = AC()->db->get_objectlist('SELECT * FROM #__cmcoupon WHERE function_type IN ("buyxy","buyxy2")','id');
		foreach($items as $item) {
			if(empty($item->params)) continue;
			$item->params = json_decode($item->params);

			if( isset( $item->params->buy_xy_process_type ) ) {
				$item->params->process_type = $item->params->buy_xy_process_type;
				unset($item->params->buy_xy_process_type);
			}
			if(isset($item->params->asset1_mode) && isset($item->params->asset1_type)) {
				if($item->function_type=='buyxy') {
					if(!isset($item->params->asset[1])) $item->params->asset[1] = new stdclass();
					$item->params->asset[1]->qty = $item->params->asset1_qty;
					if(!isset($item->params->asset[1]->rows->{$item->params->asset1_type})) @$item->params->asset[1]->rows->{$item->params->asset1_type} = new stdclass();
					$item->params->asset[1]->rows->{$item->params->asset1_type}->type = $item->params->asset1_type;
					$item->params->asset[1]->rows->{$item->params->asset1_type}->mode = $item->params->asset1_mode;
					$this->log_query('UPDATE #__cmcoupon_asset SET asset_key=1 WHERE coupon_id='.$item->id.' AND asset_type="'.$item->params->asset1_type.'" AND asset_key=100', '3.0.0');
				}
				elseif($item->function_type=='buyxy2') {
					if(!isset($item->params->asset[3]->rows->{$item->params->asset1_type})) @$item->params->asset[3]->rows->{$item->params->asset1_type} = new stdclass();
					$item->params->asset[3]->rows->{$item->params->asset1_type}->type = $item->params->asset1_type;
					$item->params->asset[3]->rows->{$item->params->asset1_type}->mode = $item->params->asset1_mode;
				}
				unset($item->params->asset1_mode);
				unset($item->params->asset1_type);
				unset($item->params->asset1_qty);
			}
			if(isset($item->params->asset2_mode) && isset($item->params->asset2_type)) {
				if($item->function_type=='buyxy') {
					if(!isset($item->params->asset[2])) $item->params->asset[2] = new stdclass();
					$item->params->asset[2]->qty = $item->params->asset2_qty;
					if(!isset($item->params->asset[2]->rows->{$item->params->asset2_type})) @$item->params->asset[2]->rows->{$item->params->asset2_type} = new stdclass();
					$item->params->asset[2]->rows->{$item->params->asset2_type}->type = $item->params->asset2_type;
					$item->params->asset[2]->rows->{$item->params->asset2_type}->mode = $item->params->asset2_mode;
					$this->log_query('UPDATE #__cmcoupon_asset SET asset_key=2 WHERE coupon_id='.$item->id.' AND asset_type="'.$item->params->asset2_type.'" AND asset_key=200', '3.0.0');
				}
				elseif($item->function_type=='buyxy2') {
					if(!isset($item->params->asset[4]->rows->{$item->params->asset2_type})) @$item->params->asset[4]->rows->{$item->params->asset2_type} = new stdclass();
					$item->params->asset[4]->rows->{$item->params->asset2_type}->type = $item->params->asset2_type;
					$item->params->asset[4]->rows->{$item->params->asset2_type}->mode = $item->params->asset2_mode;
				}
				unset($item->params->asset2_mode);
				unset($item->params->asset2_type);
				unset($item->params->asset2_qty);
			}
			if(isset($item->params->user_mode) && in_array($item->user_type, array('user','usergroup'))) {
				if ( ! isset( $item->params->asset[0]->rows->{$item->user_type} ) ) @$item->params->asset[0]->rows->{$item->user_type} = new stdclass();
				$item->params->asset[0]->rows->{$item->user_type}->type = $item->user_type;
				$item->params->asset[0]->rows->{$item->user_type}->mode = $item->params->user_mode;
				unset($item->params->user_mode);
			}
			if(isset($item->params->countrystate_mode)) {
				if(!isset($item->params->asset[0]->rows->countrystate)) @$item->params->asset[0]->rows->countrystate = new stdclass();
				$item->params->asset[0]->rows->countrystate->type = 'countrystate';
				$item->params->asset[0]->rows->countrystate->mode = $item->params->countrystate_mode;
				unset($item->params->countrystate_mode);
			}
			if(isset($item->params->country_mode)) {
				if(!isset($item->params->asset[0]->rows->country)) @$item->params->asset[0]->rows->country = new stdclass();
				$item->params->asset[0]->rows->country->type = 'country';
				$item->params->asset[0]->rows->country->mode = $item->params->countrystate_mode;
				unset($item->params->country_mode);
			}
			if(isset($item->params->paymentmethod_mode)) {
				if(!isset($item->params->asset[0]->rows->paymentmethod)) @$item->params->asset[0]->rows->paymentmethod = new stdclass();
				$item->params->asset[0]->rows->paymentmethod->type = 'paymentmethod';
				$item->params->asset[0]->rows->paymentmethod->mode = $item->params->paymentmethod_mode;
				unset($item->params->paymentmethod_mode);
			}

			$item->params = (array)$item->params;
			$params = empty($item->params) ? 'NULL' : '"'.AC()->db->escape(json_encode($item->params)).'"';
			$this->log_query('UPDATE #__cmcoupon SET params='.$params.' WHERE id='.$item->id, '3.0.0');
		}
		$this->log_query('UPDATE #__cmcoupon_asset SET asset_key=0 WHERE asset_key IN (100,200)', '3.0.0');
		
	}

}
