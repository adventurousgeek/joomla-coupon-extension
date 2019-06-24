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

class CmCoupon_Helper_Database {

	public function get_objectlist( $sql, $key = null ) {
		$db  = JFactory::getDBO();
		$db->setQuery( $sql );
		if ( is_null( $key ) ) {
			return $db->loadObjectList();
		}
		else {
			return $db->loadObjectList( $key );
		}
	}

	public function get_object( $sql ) {
		$db  = JFactory::getDBO();
		$db->setQuery( $sql );
		return $db->loadObject();
	}

	public function get_arraylist( $sql, $key = null ) {
		$db  = JFactory::getDBO();
		$db->setQuery( $sql );
		if ( ! is_null( $key ) ) {
			return $db->loadAssocList();
		}
		else {
			return $db->loadAssocList( $key );
		}
	}

	public function get_value( $sql ) {
		$db  = JFactory::getDBO();
		$db->setQuery( $sql );
		return $db->loadResult();
	}

	public function get_column( $sql ) {
		$db  = JFactory::getDBO();
		$db->setQuery( $sql );
		return version_compare( JVERSION, '2.5.0', 'ge' ) ? $db->loadColumn() : $db->loadResultArray();
	}

	public function query( $sql ) {
		$db  = JFactory::getDBO();
		$db->setQuery( $sql );
		return $db->query();
	}

	public function escape( $s, $extra = false, $htmlOK = false ) {
		$db = JFactory::getDBO();
		$s = version_compare( JVERSION, '1.6.0', 'ge' ) 
			? $db->escape( $s, $extra )
			: $db->getEscaped( $s, $extra )
		;

		return $s;
	}

	public function get_table_columns( $table ) {
		$columns = new stdClass();

		$rows = $this->get_objectlist( 'DESCRIBE ' . $table );
		foreach ( $rows as $row ) {
			$val = strtolower( $row->Null ) == 'yes' ? null : $row->Default;
			if ( 'CURRENT_TIMESTAMP' === $val ) {
				$val = gmdate( 'Y-m-d H:i:s' );
			}
			$columns->{$row->Field} = $val;
		}

		return $columns;
	}

	public function get_insertid() {
		return JFactory::getDBO()->insertid();
	}

	public function get_errormsg() {
		return JFactory::getDBO()->stderr( true );
	}

	public function get_table_instance( $table, $key, $id ) {

		$o = $this->get_object( 'SELECT * FROM ' . $table . ' WHERE ' . $key . '="' . $this->escape( $id ) . '"' );
		if ( ! empty( $o ) ) {
			return (object) $o;
		}

		return $this->get_table_columns( $table );
	}

	public static function bind_table_instance( $prop, $from ) {
		$is_array = is_array( $from );
		$is_object = is_object( $from );
		if ( ! $is_array && ! $is_object ) {
			return false;
		}

		foreach ( $prop as $k => $v ) {
			if ( $is_array ) {
				if ( array_key_exists( $k, $from ) ) {
					// use this because isset returns false on NULL
					$prop->{$k} = $from[ $k ];
				}
			} elseif ( $is_object ) {
				if ( property_exists( $from,$k ) ) {
					// use this because isset returns false on NULL
					$prop->{$k} = $from->{$k};
				}
			}
		}
		return $prop;
	}

	public function save_table_instance( $table, $row, $extra = null ) {

		if ( empty( $row->id ) ) {
			$columns = array();
			$values = array();

			foreach ( $row as $c => $item ) {
				if ( 'id' == $c ) {
					continue;
				}
				$columns[] = $c;
				$values[] = is_null( $item ) ? 'NULL' : '"' . $this->escape( $item ) . '"';
			}
			$sql = 'INSERT INTO ' . $table . ' (' . implode( ',', $columns ) . ') VALUES (' . implode( ',', $values ) . ')';

			$this->query( $sql );
			$row->id = $this->get_insertid();
		} else {
			$cols = array();
			foreach ( $row as $c => $item ) {
				if ( 'id' == $c ) {
					continue;
				}
				$value = is_null( $item ) ? 'NULL' : '"' . $this->escape( $item ) . '"';
				$cols[] = $c . '=' . $value;
			}
			$sql = 'UPDATE ' . $table . ' SET ' . implode( ',', $cols ) . ' WHERE id=' . $row->id;
			$this->query( $sql );
		}
		return $row;
	}

}
