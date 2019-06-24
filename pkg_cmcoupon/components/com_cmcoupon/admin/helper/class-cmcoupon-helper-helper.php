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

if ( ! class_exists( 'CmCoupon_Helper_Helper' ) ) {
	require dirname( __FILE__ ) . '/../cmcoupon/library/class-cmcoupon-library-helper.php';
}
class CmCoupon_Helper_Helper extends Cmcoupon_Library_Helper {

	protected $is_check_dbupdate = true;

	public function vars( $type, $item = null, $excludes = null, $extra_vars = null ) {

		if ( 'asset_type' == $type ) {
			if ( in_array( $item, array( 'custom' ), true ) ) {
				return null;
			}
		}

		$vars = array(
			'estore' => array(
				'eshop'=>'EShop',
				'hikashop'=>'Hikashop',
				'redshop'=>'redShop',
				'virtuemart'=>'Virtuemart',
				'virtuemart1'=>'Virtuemart 1',
			),
		);
		$vars = parent::vars( $type, $item, $excludes, $vars );

		if ( '__all__' === $type ) {
			unset( $vars['asset_type']['custom'] );
		}
		elseif ( ! isset( $item ) ) {
			if ( 'asset_type' === $type ) {
				unset( $vars['custom'] );
			}
		}

		return $vars;
	}

	public function get_request( $key = null, $default = '', $type = null, $mask = 'standard' ) {
		$request_type = 'none';
		$mask_value = 0;
		if ( $mask === 'rawhtml' ) {
			$mask_value = JREQUEST_ALLOWRAW;
		}
		if ( is_null( $key ) ) {
			if ( empty( $type ) ) {
				$type = 'post';
			}
			if ( 'request' == $type ) {
				return JRequest::get( 'request' );
			} elseif ( 'post' == $type ) {
				return JRequest::get( 'post' );
			} elseif ( 'get' == $type ) {
				return JRequest::get( 'get' );
			} elseif ( 'file' == $type ) {
				return JRequest::get( 'files' );
			}
		} elseif ( strpos( $key, '.' ) === false ) {
			if ( empty( $type ) ) {
				$type = 'request';
			}

			if ( 'request' == $type ) {
				return JRequest::getVar( $key, $default, 'request', $request_type, $mask_value );
			} elseif ( 'post' == $type ) {
				return JRequest::getVar( $key, $default, 'post', $request_type, $mask_value );
			} elseif ( 'get' == $type ) {
				return JRequest::getVar( $key, $default, 'get', $request_type, $mask_value );
			} elseif ( 'file' == $type ) {
				return JRequest::getVar( $key, $default, 'files', $request_type, $mask_value );
			}
		} else {
			if ( empty( $type ) ) {
				$type = 'request';
			}

			$pos = strrpos( $key, '.' );
			$part1 = substr( $key, 0, $pos );
			$part2 = substr( $key, $pos + 1 );
			if ( 'request' == $type ) {
				$items = JRequest::getVar( $part1, array(), 'request', $request_type, $mask_value );
				return isset( $items[ $part2 ] ) ? $items[ $part2 ] : $default;
			} elseif ( 'post' == $type ) {
				$items = JRequest::getVar( $part1, array(), 'post', $request_type, $mask_value );
				return isset( $items[ $part2 ] ) ? $items[ $part2 ] : $default;
			} elseif ( 'get' == $type ) {
				$items = JRequest::getVar( $part1, array(), 'get', $request_type, $mask_value );
				return isset( $items[ $part2 ] ) ? $items[ $part2 ] : $default;
			} elseif ( 'file' == $type ) {
				$items = JRequest::getVar( $part1, array(), 'files', $request_type, $mask_value );
				return isset( $items[ $part2 ] ) ? $items[ $part2 ] : $default;
			}
		}
		return $default;
	}
	
	public function set_request( $key, $value = null, $type = 'method' ) {
		return JRequest::setVar( $key, $value, $type );
	}

	public function is_email( $email ) {
		jimport('joomla.mail.helper');
		return JMailHelper::isEmailAddress( $email );
	}

	public function get_user( $id = null ) {
		$user = new stdClass();
		if ( ! empty( $id ) ) {
			$customer = JFactory::getUser( $id );
			if ( empty( $customer->id ) ) {
				return;
			}
			$user->id = $customer->id;
			$user->username = $customer->username;
			$user->email = $customer->email;
			$user->name = $customer->name;
			return $user;
		}
		
		$customer = JFactory::getUser();
		if ( empty( $customer->id ) ) {
			$user->id = 0;
			$user->username = '';
			$user->email = '';
			$user->name = '';
			return $user;
		}

		$user->id = $customer->id;
		$user->username = $customer->username;
		$user->email = $customer->email;
		$user->name = $customer->name;

		return $user;
	}

	public function get_user_by_email( $email ) {
		$user_id = (int) AC()->db->get_value( 'SELECT id FROM #__users WHERE email="' . AC()->db->escape( $email ) . '"' );
		if ( empty( $user_id ) ) {
			return null;
		}
		return AC()->helper->get_user( $user_id );
	}

	/*
	$type = utc2loc (utc to localtime)
	$type = utc2utc (utc to utc)
	$type = loc2utc (localtime to utc)
	*/
	public function get_date( $date = null, $format = null, $type = 'utc2loc' ) {

		if ( 'date' === $format ) {
			$format = 'Y-m-d';
		} elseif ( 'datetime' === $format ) {
			$format = 'Y-m-d H:i:s';
		}

		if($type=='utc2locEN' && empty( $format ) ) $format = 'Y-m-d H:i:s';
		if(version_compare(JVERSION,'1.6.0','ge')) {
			if(empty($date)) $date = 'now';
		}
		else {
			if(empty($date)) $date = time();
			$d1 = stristr(PHP_OS,"win") ? '%#d' : '%e';
			$format = str_replace(
				array( 'M',  'Y', 'm', 'n', 'd', 'j', 'H', 'i', 's' ,'F', 'y', 'l', 'D',),
				array('%b', '%Y','%m','%m','%d', $d1,'%H','%M','%S','%B','%y','%A','%a',),
				$format
			);
		}
		
		if(is_numeric($date)) $date = gmdate('c', $date);

		if(in_array($type, array('utc2loc','utc2utc'))) {
			if($type=='utc2loc') {
				$tz = true;
				$offset = null;
			}
			elseif($type=='utc2utc') {
				$tz = null;
				$offset = 0;
			}
			return version_compare( JVERSION, '1.6.0', 'ge' )
				? JHTML::_('date',$date,$format,$tz)
				: JHTML::_('date',strtotime($date),$format,$offset)
			;
		}
		elseif($type=='loc2utc') {
			$local = false;
			$current_timezone = JFactory::getUser()->getParam('timezone', JFactory::getConfig()->get('offset'));
			return version_compare( JVERSION, '1.6.0', 'ge' ) 
				? JFactory::getDate($date, $current_timezone)->format($format,$local)
				: JFactory::getDate($date, JFactory::getConfig()->getValue ( 'offset' )*1)->toFormat($format)
			;
		}
		elseif($type=='utc2locEN') {
			$current_timezone = JFactory::getUser()->getParam('timezone', JFactory::getConfig()->get('offset'));
			if ( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
				$dateobj = new \DateTime( $date, new \DateTimeZone( 'UTC' ) );
				$dateobj->setTimeZone( new DateTimeZone( $current_timezone ) );
				return $dateobj->format( $format );
			}
			else {
				$offset = JFactory::getConfig()->getValue ( 'offset' )*1;
				$mktime = strtotime($date);
				$mktime = $mktime + ($offset * 3600);
				return strftime( $format, $mktime );
			}
		}
	}

	public function send_email( $from, $fromname, $to, $subject, $body, $bcc = null, $image_attachments = null, $is_embed = false, $string_attachments = array(), $cc = null ) {

	 	// Get a JMail instance
		$mail = JFactory::getMailer();

		$mail->setSender(array($from, $fromname));
		$mail->setSubject($subject);
		$mail->setBody($body);

		// Are we sending the email as HTML?
		$mail->IsHTML(true);

		$mail->addRecipient($to);
		$mail->addCC($cc);
		$mail->addBCC($bcc);
		
		if(!empty($image_attachments) && is_array($image_attachments)) {
			$i = 0;
			foreach($image_attachments as $filename=>$attachment) {
				$image_part = pathinfo($attachment);
				if($is_embed) $mail->AddEmbeddedImage($attachment,'couponimageembed'.(++$i),$filename.'.'.$image_part['extension']);
				else $mail->AddAttachment($attachment,$filename.'.'.$image_part['extension']);
			}
		}
		
		if(!empty($string_attachments) && is_array($string_attachments)) {
			$i = 0;
			foreach($string_attachments as $name=>$attachment) {
				$mail->AddStringAttachment($attachment,$name);
			}
		}

		return  $mail->Send();
	}

	public function get_cache( $key ) {
		$cache = JFactory::getCache( 'com_cmcoupon', '' );
		$cache->setCaching( true );
		$cache_id = md5( $key );
		return $cache->get( $cache_id );
	}

	public function set_cache( $key, $value, $seconds ) {
		$cache = JFactory::getCache( 'com_cmcoupon', '' );
		$cache->setCaching( true );
		$cache->setLifeTime( version_compare( JVERSION, '1.6.0', 'ge' ) ? $seconds / 60 : $seconds );  //24 hours
		$cache_id = md5( $key );
		$cache->store( $value, $cache_id );
	}

	public function reset_cache( $key = null ) {
		if ( empty( $key ) ) {
			JFactory::getCache()->clean('com_cmcoupon');
		}
		else {
			$cache = JFactory::getCache( 'com_cmcoupon', '' );
			$cache_id = md5( $key );
			$cache->remove( $cache_id );
		}
	}
	

	public function get_session( $group, $key, $default ) {
		$session = JFactory::getSession();
		return $session->get( $group . '.' . $key, $default, 'com_cmcoupon' );
	}

	public function set_session( $group, $key, $value) {
		$session = JFactory::getSession();
		$session->set( $group . '.' . $key, $value, 'com_cmcoupon' );
	}

	public function reset_session( $group, $key ) {
		$session = JFactory::getSession();
		$session->set( $group . '.' . $key, null, 'com_cmcoupon' );
	}

	public function get_editor( $content, $id, $settings = array() ) {
		$editor		= JFactory::getEditor();
		$name = isset( $settings['textarea_name'] ) ? $settings['textarea_name'] : '';
		return version_compare( JVERSION, '1.6.0', '>=' )
			? $editor->display( $name, $content , '100%', '350', '75', '20', true, $id )
			: $editor->display( $name, $content , '100%', '350', '75', '20', true, array( 'id' => $id ) )
		;
	}

	public function get_editor_content_js( $id ) {
		$config = JFactory::getConfig ();
		$e = $config->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'} ( 'editor' );

		if ( $e === 'none' ) {
			return 'jQuery("#' . $id . '").val();';
		}
		else {
			return 'if ( typeof tinyMCE !== "undefined" ) { tinyMCE.get("' . $id . '").getContent(); } else { ' . trim ( addslashes( JFactory::getEditor()->getContent( $id ) ) ) . ' }';
		}
	}

	public function set_editor_content_js( $id, $variable_email_body_html ) {
		$config = JFactory::getConfig ();
		$e = $config->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'} ( 'editor' );

		if ( $e === 'none' ) {
			return 'jQuery("#' . $id . '").val( ' . $variable_email_body_html . ' );';
		}
		else {
			return '
				if ( typeof tinyMCE !== "undefined" ) { 
					var editor = tinyMCE.get("' . $id. '"); // use your own editor id here - equals the id of your textarea
					editor.setContent(' . $variable_email_body_html . ');
				}
				else {
					// need to eval because the second argumen in setContent is sometimes taken as the actual html and somtimes taken as an element name in javascript
					blank_text = "";
					my_eval = "' . trim( addslashes( JFactory::getEditor()->setContent( $id, 'replace_text_holder' ) ) ) . '";
					my_eval = my_eval.replace(\'"replace_text_holder"\',\'""\');
					my_eval = my_eval.replace("\'replace_text_holder\'","\'\'");
					my_eval = my_eval.replace(\'replace_text_holder\', \'blank_text\');
					eval(my_eval);

					jInsertEditorText(' . $variable_email_body_html . ', "' . $id . '");
				}
			';
		}
	}

	public function get_modal_popup( $url, $title='', $is_iframe = true ) {
		if ( version_compare( JVERSION, '1.6', '<' ) ) {
			return '
				jQuery.fancybox.open({
					"href"				: "'.$url.'",
					"width"				: "75%",
					"height"			: "75%",
					"autoScale"     	: false,
					"transitionIn"		: "none",
					"transitionOut"		: "none",
					"type"				: "' . ( $is_iframe ? 'iframe' : 'ajax' ) . '"
				});
			';
		}
		if ( false === $is_iframe ) {
			return '
				SqueezeBox.open("' . $url . '");
			';
		}
		return '
			SqueezeBox.setContent("iframe", "' . $url . '");
		';
	}

	public function get_padding_from_top_element() {
		return 'nav.navbar';
	}

	public function get_cm_product() {
		return 'cmcoupon-jm';
	}

	public function get_cron_url() {
		return JURI::root() . 'index.php?option=com_cmcoupon&task=cron&key=';
	}

	public function cms_redirect( $url ) {
		JFactory::getApplication()->redirect( $url );
		exit;
	}

	public function get_link() {
		$router = JSite::getRouter();
		$current_url = 'index.php?' . JURI::buildQuery( $router->getVars() );
		return JRoute::_( $current_url );
	}

	public function loadLanguageSite() {
		static $is_loaded = false;
		if ( $is_loaded ) {
			return;
		}
		$is_loaded = true;
		$jlang = JFactory::getLanguage();
		$jlang->load( 'com_cmcoupon', JPATH_SITE, 'en-GB', true );
		$jlang->load( 'com_cmcoupon', JPATH_SITE, $jlang->getDefault(), true );
		$jlang->load( 'com_cmcoupon', JPATH_SITE, null, true );
		$jlang->load( 'com_cmcoupon', JPATH_SITE . '/components/com_cmcoupon/', 'en-GB', true );
		$jlang->load( 'com_cmcoupon', JPATH_SITE . '/components/com_cmcoupon/', $jlang->getDefault(), true );
		$jlang->load( 'com_cmcoupon', JPATH_SITE . '/components/com_cmcoupon/', null, true );
	}

	public function get_installed_estores() {
		$valid_estores = AC()->helper->vars( 'estore' );
		$estores = array();
		$dir = JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/estore';
		$dh  = opendir( $dir) ;
		while ( false !== ( $name = readdir( $dh ) ) ) {
			if ( $name == '.' ) continue;
			if ( $name == '..' ) continue;
			if ( ! is_dir( $dir . '/' . $name ) ) continue;
			if ( isset( $valid_estores[ $name ] ) ) $estores[] = $name;
		}

		$installedestores = array();
		foreach ( $estores as $estore ) {
			$class = AC()->helper->new_class( 'Cmcoupon_Helper_Estore_' . $estore . '_Helper' );
			if ( empty( $class ) ) {
				continue;
			}
			if ( method_exists( $class, 'is_installed' ) ) {
				if ( $class->is_installed() ) {
					$installedestores[] = $estore;
				}
			}
		}
		return $installedestores;
	}

	public function get_html_global( $item ) {
		switch( $item ) {
			case 'table_class': return 'table table-striped adminlist';
		}
		return '';
	}

	public function set_message( $msg, $type = 'notice' ) {
		if ( ! AC()->is_request( 'frontend' ) ) {
			return parent::set_message( $msg, $type );
		}

		JFactory::getApplication()->enqueueMessage($msg, $type);
	}

	public function trigger( $function, $input = array() ) {
		if ( empty( $function ) ) {
			return;
		}
		JPluginHelper::importPlugin( 'cmcoupon' );
		$dispatcher = JDispatcher::getInstance();
		return $dispatcher->trigger( $function, $input );
	}

}
