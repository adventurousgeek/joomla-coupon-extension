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

class CmCoupon_Helper_Language {

	public function get_languages() {
		static $languages = array();
		if ( ! empty( $languages ) ) {
			return $languages;
		}
		
		jimport('joomla.language.helper');
		$tmp =  JLanguageHelper::createLanguageList( JComponentHelper::getParams( 'com_languages' )->get( 'site' ), JPATH_BASE, true, true );
		$languages = array();
		foreach ( $tmp as $k=>$language ) {
			$language = (object) $language;
			$language->id = $language->value;
			$language->id_lang = $language->value;
			$language->locale = $language->value;
			$language->name = $language->text;
			$languages[ $language->id ] = (object) $language;
		}

		return $languages;
	}

	public function write_fields( $type, $field, $data, $params = null ) {
		$languages = $this->get_languages();

		$r = '';

		$editor_html_1 = '';
		$editor_html_2 = '';
		$editor_html_3 = '';
		$editor_html_4 = '';
		$editor_html_5 = '';
		if ( 'editor' == $type ) {
			$editor_html_1 = 'style="width:100%;margin-right:-100px;float:left;"';
			$editor_html_2 = '<div style="margin-right:104px;">';
			$editor_html_3 = '</div>';
			$editor_html_4 = 'style="float:left;width:100px;"';
			$editor_html_5 = '<div class="clear"></div>';
		}
		
		foreach ( $languages as $language ) {
			if ( count( $languages ) > 1 ) {
				$r .= '
					<div class="translatable-field lang-' . $language->locale . '">
						<div class="col-lg-9" ' . $editor_html_1 . '>' . $editor_html_2 . '
				';
			}
			if ( 'text' == $type ) {
				$r .= '
							<input type="text" 
								name="idlang[' . $language->locale . '][' . ( isset( $params['name'] ) ? $params['name'] : $field ) . ']"
								class="inputbox ' . ( isset( $params['class'] ) ? $params['class'] : '' ) . '"
								' . ( isset( $params['style'] ) ? 'style="' . $params['style'] . '"' : '' ) . '
								value="' . ( isset( $data[ $language->locale ]->{$field} ) ? $data[ $language->locale ]->{$field} : '' ) . '" />
				';
			} elseif ( 'editor' == $type ) {
				$name = isset( $params['name'] ) ? $params['name'] : $field;
				$content = isset( $data[ $language->locale ]->{$field} ) ? $data[ $language->locale ]->{$field} : '';
				$id = 'idlang_' . $language->locale . '_' . ( isset( $params['id'] ) ? $params['id'] : $name );
				$textarea_name = 'idlang[' . $language->locale . '][' . $name . ']';

				$r .= version_compare( JVERSION, '1.6.0', '>=' )
					? JFactory::getEditor()->display(
						$textarea_name,
						$content,
						isset( $params['editor_width'] ) ? $params['editor_width'] : '650',
						isset( $params['editor_height'] ) ? $params['editor_height'] : '200',
						isset( $params['cols'] ) ? $params['cols'] : '75',
						isset( $params['rows'] ) ? $params['rows'] : '20',
						true,
						$id
					)
					: JFactory::getEditor()->display(
						$textarea_name,
						$content,
						isset( $params['editor_width'] ) ? $params['editor_width'] : '650',
						isset( $params['editor_height'] ) ? $params['editor_height'] : '200',
						isset( $params['cols'] ) ? $params['cols'] : '75',
						isset( $params['rows'] ) ? $params['rows'] : '20',
						true,
						array( 'id' => $id )
					)
				;
			} elseif ( 'textarea' == $type ) {
				$r .= '
							<textarea 
								name="idlang[' . $language->locale . '][' . ( isset( $params['name'] ) ? $params['name'] : $field ) . ']"
								' . ( isset( $params['class'] ) ? 'class="' . $params['class'] . '"' : '' ) . '
								' . ( isset( $params['style'] ) ? 'style="' . $params['style'] . '"' : '' ) . '
								' . ( isset( $params['rows'] ) ? 'rows="' . $params['rows'] . '"' : '' ) . '
								' . ( isset( $params['cols'] ) ? 'cols="' . $params['cols'] . '"' : '' ) . '
								>' . ( isset( $data[ $language->locale ]->{$field} ) ? $data[ $language->locale ]->{$field} : '' ) . '</textarea>
				';
			}
			if ( ! empty( $params['after_text'] ) ) {
				$r .= $params['after_text'];
			}
			if ( count( $languages ) > 1 ) {
				$r .= '
						' . $editor_html_3 . '
						</div>
						<div class="col-lg-2" ' . $editor_html_4 . '>
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
								' . $language->locale . '
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu language">
				';
				foreach ( $languages as $language2 ) {
					$r .= '
								<li><a href="javascript:hideOtherLanguage(\'' . $language2->locale . '\');">' . $language2->name . '</a></li>
					';
				}
				$r .= '
							</ul>
						</div>
					</div>
					' . $editor_html_5 . '
				';
			}
		}
		return $r;
	}

	public function get_current() {
		return JFactory::getLanguage()->getTag();
	}

	public function trx( $text ) {
		return __( $text, 'cmcoupon' );
	}

	public function __( $text ) {
		static $language_master = null;
		if ( is_null( $language_master ) ) {
			$file = JPATH_ADMINISTRATOR . '/components/com_cmcoupon/assets/language.ini';
			if ( ! function_exists( 'parse_ini_file' ) ) {
				$contents = file_get_contents( $file );
				$contents = str_replace( '_QQ_', '"\""', $contents );
				$strings = @parse_ini_string( $contents );
			}
			else {
				$strings = @parse_ini_file( $file );
			}
			if ( ! is_array( $strings ) ) {
				$strings = array();
			}
			$language_master = array_flip( $strings );
		}

		$key = '';
		if ( isset( $language_master[ $text ] ) ) {
			$key = $language_master[ $text ];
		}
		if ( empty( $key ) ) {
			return JText::_( $text );
		}
		$string = JText::_( $key );
		if ( $string === $key ) {
			$string = JText::_( $text );
		}
		return $string;
	}

	public function oldversion__( $text ) {
		static $language_master = null;
		//if ( $this->get_current() == 'en-GB' ) {
		//	return JText::_( $text );
		//}

		$lang = JFactory::getLanguage();
		$lang->load('com_cmcoupon', JPATH_ADMINISTRATOR, 'en-GB', true);  // Load English (British)
		$lang->load('com_cmcoupon', JPATH_ADMINISTRATOR, null, true); // Load the currently selected language
		$lang->load('com_cmcoupon', JPATH_ADMINISTRATOR.'/components/com_cmcoupon', 'en-GB', true);
		$tmp = $lang->getPaths();

		$files = array();
		foreach($tmp as $tmp1) {
			foreach($tmp1 as $file=>$throwaway) {
				if( strpos( $file, 'en-GB' ) !== false ) {
					$files[ $file ] = $file;
				}
			}
		}
		if ( empty( $files ) ) {
			return JText::_( $text );
		}

		if ( is_null( $language_master ) ) {
			$variables = array();
			foreach ( $files as $file ) {
				if ( ! function_exists( 'parse_ini_file' ) ) {
					$contents = file_get_contents( $file );
					$contents = str_replace( '_QQ_', '"\""', $contents );
					$strings = @parse_ini_string( $contents );
				}
				else {
					$strings = @parse_ini_file( $file );
				}
				if ( ! is_array( $strings ) ) {
					$strings = array();
				}
				$variables = array_replace( $variables, $strings );
			}
			$variables = array_flip( $variables );
			
			$overrides = array();
			$filename = JPATH_BASE . "/language/overrides/en-GB.override.ini";
			if ( ! function_exists( 'parse_ini_file' ) ) {
				$contents = file_get_contents( $filename );
				$contents = str_replace( '_QQ_', '"\""', $contents );
				$strings = @parse_ini_string( $contents );
			}
			else {
				$strings = @parse_ini_file( $filename );
			}
			if ( ! is_array( $strings ) ) {
				$strings = array();
			}
			$overrides = $strings;
			$overrides = array_flip( $overrides );
		
			$language_master = array(
				'normal' => $variables,
				'override' => $overrides,
			);
		}
		
		$key = '';
		if ( isset( $language_master['normal'][ $text ] ) ) {
			$key = $language_master['normal'][ $text ];
		}
		if ( empty( $key ) && isset( $language_master['override'][ $text ] ) ) {
			$key = $language_master['override'][ $text ];
		}
		if ( empty( $key ) ) {
			return JText::_( $text );
		}

		return JText::_( $key );
	}

	public function _e_valid( $text ) {
		return sprintf( AC()->lang->__( '%1$s: enter a valid value' ), $text );
	}

	public function _e_select( $text ) {
		return sprintf( AC()->lang->__( '%1$s: make a selection' ), $text );
	}

	public function get_user_lang( $user_id = 0, $can_be_anonymous = false ) {
		$languages = array();
		$user_id = (int)$user_id;
		
		if ( ! JFactory::getApplication()->isAdmin() ) {
			$languages[] = JFactory::getLanguage()->getTag(); // current front end language
		}

		if ( empty( $user_id ) ) {
			if ( ! $can_be_anonymous ) {
				$user = JFactory::getUser();
				$lang = $user->getParam( 'language' );
				if ( ! empty( $lang ) ) {
					$languages[] = $lang;
				}
			}
		}
		else {
			$user = JFactory::getUser( $user_id );
			$lang = $user->getParam( 'language' );
			if ( ! empty( $lang ) ) {
				$languages[] = $lang;
			}
		}

		$params = JComponentHelper::getParams( 'com_languages' );
		$languages[] = $params->get( 'site' );
		$languages[] = $params->get( 'administrator' );

		$languages[] = 'en-GB';

		return array_unique($languages);
	}

	public function get_data( $elem_id, $user_id = 0, $default = null, $can_be_anonymous = false ) {
		$elem_id = (int) $elem_id;
		if ( empty( $elem_id ) ) {
			return;
		}

		static $stored_languages;
		if ( ! isset( $stored_languages[ $user_id ] ) ) {
			$stored_languages[ $user_id ] = $this->get_user_lang( $user_id, $can_be_anonymous );
		}

		$languages = implode( '","', $stored_languages[ $user_id ] );
		$text = AC()->db->get_value( 'SELECT text FROM #__cmcoupon_lang_text WHERE elem_id=' . $elem_id . ' AND lang IN ("' . $languages . '") ORDER BY FIELD(lang,"' . $languages . '")' );

		return ! empty( $text ) ? $text : $default;
	}

	public function save_data( $elem_id, $text, $lang = null ) {
		$elem_id = (int) $elem_id;

		$text = AC()->db->escape( $text, false, true );

		if ( empty( $lang ) ) {
			$lang = $this->get_current();
		}

		if ( empty( $text ) && ! empty( $elem_id ) ) {
			// delete the data from db
			AC()->db->query( 'DELETE FROM #__cmcoupon_lang_text WHERE elem_id=' . (int) $elem_id . ' AND lang="' . $lang . '"' );
			return;
		}

		if ( empty( $elem_id ) ) {
			if ( empty( $text ) ) {
				return;
			}

			$elem_id = (int) AC()->db->get_value( 'SELECT MAX(elem_id) FROM #__cmcoupon_lang_text' );
			$elem_id++;
		}

		AC()->db->query( 'INSERT INTO #__cmcoupon_lang_text (elem_id,lang,text) VALUES (' . $elem_id . ',"' . $lang . '","' . $text . '") ON DUPLICATE KEY UPDATE text="' . $text . '"' );

		return $elem_id;
	}

}
