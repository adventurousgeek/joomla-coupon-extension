<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

$class = new cmcoupon_admin_view();
return $class->display_content();

class cmcoupon_admin_view {

	public function display_content() {

		if ( version_compare( JVERSION, '1.6.0', 'ge' ) && ! JFactory::getUser()->authorise( 'core.manage', 'com_cmcoupon' ) ) {
			JFactory::getApplication()->enqueueMessage( JText::_( 'JERROR_ALERTNOAUTHOR' ), 'error' );
			return;
		}

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

		$this->load_language();

		$ajax = AC()->helper->get_request( 'ajax', '' );
		if ( $ajax == 'yes' ) {
			$this->_display_ajax();
			exit;
		}

		JToolBarHelper::title( 'CmCoupon', 'coupons' );
		$this->load_admin_jscss();
		$admin_class = AC()->helper->new_class( 'CmCoupon_Admin_Admin' );
		ob_start();
		$admin_class->display();
		$this->content = ob_get_contents();
		ob_end_clean();

		$script = JFactory::getDocument()->_script['text/javascript'];
		if ( ! empty( $script ) && strpos( $script, 'CodeMirror' ) !== false ) {
			$script = str_replace( 'Joomla.editors.instances[id] = CodeMirror.fromTextArea(document.getElementById(id), options);', '', $script );
			preg_match( '/\"klsdkljskldjsd\-klsdlkdsksjdk\_klskdslkdjlskldj\_\"\s*,\s*options\s*\=\s*(\{.*?\})\;/s', $script, $match );
			if ( ! empty( $match[1] ) ) {
				$script .= "\n\r".'var codemirror_cmcoupon_options = ' . $match[1] . ';';
			}
			JFactory::getDocument()->_script['text/javascript'] = $script;
		}

		echo $this->content;
	}

	private function _display_ajax() {
		$this->ajax = true;

		$type = AC()->helper->get_request( 'type' );
		if ( empty( $type ) ) {
			exit;
		}
		switch ( $type ) {
			case 'admin':
				if ( empty( CMCOUPON_ESTORE ) ) {
					AC()->helper->set_request( 'view', 'about', 'get' );
					AC()->helper->set_request( 'task', '', 'get' );
					AC()->helper->set_request( 'layout', 'err_estore', 'get' );
				}
				$parameters = AC()->helper->get_request( 'parameters', '', 'get' );
				if ( ! empty( $parameters ) ) {
					parse_str( $parameters, $parameters );
					AC()->helper->set_request( 'parameters', $parameters, 'get' );
					AC()->helper->set_request( 'parameters', $parameters, 'request' );
				}
				$parameters = AC()->helper->get_request( 'parameters', '', 'post' );
				if ( ! empty( $parameters ) ) {
					parse_str( $parameters, $parameters );
					AC()->helper->set_request( 'parameters', $parameters, 'post' );
					AC()->helper->set_request( 'parameters', $parameters, 'request' );
				}

				AC()->helper->route( array(
					'type' => $type,
					'view' => AC()->helper->get_request( 'view' ),
					'task' => AC()->helper->get_request( 'task' ),
					'layout' => AC()->helper->get_request( 'layout', 'default' ),
				) );

				exit;

			case 'ajax':
				$class = AC()->helper->new_class( 'Cmcoupon_Library_Ajax' );
				$task = AC()->helper->get_request( 'task' );
				if ( ! method_exists( $class, $task ) ) {
					exit;
				}

				$class->$task();
				exit;
		}

	}
	
	private function load_language() {
		$jlang = JFactory::getLanguage();
		$jlang->load( 'com_cmcoupon', JPATH_ADMINISTRATOR, 'en-GB', true );  // Load English (British)
		$jlang->load( 'com_cmcoupon', JPATH_ADMINISTRATOR, $jlang->getDefault(), true ); // Load the site's default language
		$jlang->load( 'com_cmcoupon', JPATH_ADMINISTRATOR, null, true ); // Load the currently selected language
		$jlang->load( 'com_cmcoupon', JPATH_ADMINISTRATOR . '/components/com_cmcoupon/', 'en-GB', true );
		$jlang->load( 'com_cmcoupon', JPATH_ADMINISTRATOR . '/components/com_cmcoupon/', $jlang->getDefault(), true );
		$jlang->load( 'com_cmcoupon', JPATH_ADMINISTRATOR . '/components/com_cmcoupon/', null, true );
	}

	private function load_admin_jscss() {

		if ( version_compare( JVERSION, '3.4.4', '>=' ) ) {
			JHtml::_( 'bootstrap.framework' );
			JHtmlBehavior::framework();
		}
		//JHtml::_( 'jquery.ui' );
		//JHtml::_('jquery.ui', array('core', 'autocomplete'));

		JFactory::getEditor();
		$document	= JFactory::getDocument();
		$media_url = JURI::root(true).'/media/com_cmcoupon';

		if ( version_compare( JVERSION, '1.6', '<' ) ) {
			$undesirables = array(
				'mootools.js',
				'mootools-uncompressed.js',
			);
			foreach ( $document->_scripts as $key => $script ) {
				$basename = strtolower( basename( $key ) );
				if ( in_array( $basename, $undesirables ) ) {
					unset( $document->_scripts[ $key ] );
				}
			}
			$document->addScript( 'https://cdnjs.cloudflare.com/ajax/libs/mootools/1.4.5/mootools-core-full-nocompat.min.js' );
		}

		$document->addStyleSheet( CMCOUPON_ASEET_URL . '/css/style.css' );
		$document->addStyleSheet( AC()->plugin_url() . '/assets/css/style.css' );
		$document->addStyleSheet( '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css' );
		$document->addStyleSheet( CMCOUPON_ASEET_URL . '/css/tab.css' );
		$document->addStyleSheet( CMCOUPON_ASEET_URL . '/css/buttons.css' );
		$document->addStyleSheet( CMCOUPON_ASEET_URL . '/css/select2.css' );
		$document->addStyleSheet( CMCOUPON_ASEET_URL . '/css/language.css' );
		$document->addStyleSheet( CMCOUPON_ASEET_URL . '/css/pagination.css' );
		$document->addStyleSheet( CMCOUPON_ASEET_URL . '/css/menu.css' );
		$document->addStyleSheet( '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );

		if ( version_compare( JVERSION, '3.0.0', '<' ) ) {
			$document->addScript( '//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.js' );
			$document->addScript( CMCOUPON_ASEET_URL . '/js/jquery.noconflict.js' );
			$document->addScript( CMCOUPON_ASEET_URL . '/js/bootstrap.min.js' );
		}
		$document->addScript( 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.js' );
		//$document->addScript( CMCOUPON_ASEET_URL . '/js/jquery.ui.autocomplete.ext.js' );
		$document->addScript( CMCOUPON_ASEET_URL . '/js/sammy-min.js' );
		$document->addScript( CMCOUPON_ASEET_URL . '/js/jquery.validate.min.js' );
		$document->addScript( CMCOUPON_ASEET_URL . '/js/cmcoupon.js' );
		$document->addScript( CMCOUPON_ASEET_URL . '/js/select2.min.js' );
		$document->addScript( CMCOUPON_ASEET_URL . '/js/menu.js' );

		if ( version_compare( JVERSION, '3.4.4', '>=' ) ) {
			$undesirables = array(
				'jquery.ui.core.js',
				'jquery.ui.core.min.js',
				'jquery.ui.sortable.js',
				'jquery.ui.sortable.min.js',
			);
			foreach ( $document->_scripts as $key => $script ) {
				$basename = strtolower( basename( $key ) );
				if ( in_array( $basename, $undesirables ) ) {
					unset( $document->_scripts[ $key ] );
				}
			}
		}

		if ( version_compare( JVERSION, '1.6', '>=' ) ) {
			JHTML::_('behavior.modal');  // for popups
		}
		else {
			$document->addScript( AC()->plugin_url() . '/assets/js/joomla15.js' );
			$document->addStyleSheet( 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css' );
			$document->addScript( 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.js' );
		}
	}
}


