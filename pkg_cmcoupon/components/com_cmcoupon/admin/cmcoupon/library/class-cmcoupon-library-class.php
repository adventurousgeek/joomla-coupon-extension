<?php
/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

if ( ! defined( '_CM_' ) ) {
	exit;
}

/**
 * Class
 */
class CmCoupon_Library_Class {

	/**
	 * On edit the data.
	 *
	 * @var object
	 */
	var $_entry = null;

	/**
	 * The id of the edited item.
	 *
	 * @var int
	 */
	var $_id = null;

	/**
	 * The class type.
	 *
	 * @var string
	 */
	var $_type = null;

	/**
	 * The pagination class.
	 *
	 * @var class
	 */
	var $_pagination = null;

	/**
	 * The name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The state.
	 *
	 * @var string
	 */
	protected $state;

	/**
	 * The order.
	 *
	 * @var string
	 */
	public $_orderby = '';

	/**
	 * The order direction.
	 *
	 * @var string
	 */
	public $_orderby_dir = '';

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->state = new stdclass();

		$this->_layout = AC()->helper->get_request( 'layout', 'default' );

		$this->limit = AC()->helper->get_userstate_request( 'global.limit', 'limit', AC()->store->get_itemsperpage() );
		$current_page = AC()->helper->get_userstate_request( $this->name . '.page', 'paged', 1 );
		$current_page = max( 1, (int) $current_page );
		$this->limitstart = 1 < $current_page ? $this->limit * ( $current_page - 1 ) : 0;

		$this->current_url = AC()->helper->get_request( 'urlx' );

		$this->set_state( 'limit', $this->limit );
		$this->set_state( 'limitstart', $this->limitstart );

		$cid = AC()->helper->get_request( 'id', 0 );
		$this->set_id( (int) $cid );
	}

	/**
	 * Get the state
	 *
	 * @param string $property the key.
	 * @param string $default the default.
	 **/
	public function get_state( $property = null, $default = null ) {
		return null === $property ? $this->state : ( isset( $this->state->{$property} ) ? $this->state->{$property} : null );
	}

	/**
	 * Set the state
	 *
	 * @param string $property the key.
	 * @param string $value the value.
	 **/
	public function set_state( $property, $value = null ) {
		$this->state->{$property} = $value;
	}

	/**
	 * Get the order by for the list
	 **/
	public function buildquery_orderby() {

		$prefix = '.site';
		if ( AC()->is_request( 'admin' ) ) {
			$prefix = '.admin';
		}
		$filter_order = AC()->helper->get_userstate_request( $this->name . $prefix . '.orderby', 'orderby', $this->_orderby );
		$filter_order_dir = AC()->helper->get_userstate_request( $this->name . $prefix . '.order', 'order', $this->_orderby_dir );

		$sortable = $this->get_sortable_columns();
		$orderby = isset( $sortable[ $filter_order ] ) ? $sortable[ $filter_order ] . ' ' . $filter_order_dir : '';

		return $orderby;
	}

	/**
	 * Generate the html for Listing
	 **/
	public function display_list() {

		$this->get_data();
		$columns = $this->get_columns();

		$this->current_url = AC()->helper->get_request( 'urlx' );
		$current_url = $this->current_url;
		$current_url = $this->remove_query_arg( 'orderby', $current_url );
		$current_url = $this->remove_query_arg( 'order', $current_url );
		$query_separator = $this->query_and_or_questionmark( $current_url );
		$sortable = $this->get_sortable_columns();

		$html_header = '';
		foreach ( $columns as $key => $title ) {
			$class_sortable = '';
			$class_ordering = '';
			$class_td = '';
			if ( isset( $sortable[ $key ] ) ) {
				$filter_order = AC()->helper->get_userstate_request( $this->name . '.orderby', 'orderby', $this->_orderby );
				$filter_order_dir = AC()->helper->get_userstate_request( $this->name . '.order', 'order', $this->_orderby_dir );
				if ( $key === $filter_order ) {
					$class_sortable = 'sorted';
					$class_ordering = empty( $filter_order_dir ) || 'asc' === $filter_order_dir ? 'asc' : 'desc';
					$direction = empty( $filter_order_dir ) || 'asc' === $filter_order_dir ? 'desc' : 'asc';
				} else {
					$class_sortable = 'sortable';
					$class_ordering = empty( $filter_order_dir ) || 'asc' === $filter_order_dir ? 'desc' : 'asc';
					$direction = empty( $filter_order_dir ) || 'asc' === $filter_order_dir ? 'asc' : 'desc';
				}
				$title = '<a href="' . $current_url . $query_separator . 'orderby=' . $key . '&order=' . $direction . '"><span>' . $title . '</span><span class="sorting-indicator"></span></a>';
			}
			if ( 'cb' === $key ) {
				$class_td .= ' check-column ';
			}
			$html_header .= '<th class="manage-column column-primary ' . $class_sortable . ' ' . $class_ordering . ' ' . $class_td . '">' . $title . '</th>';
		}

		$html_rows = '';
		if ( isset( $this->_data ) && is_array( $this->_data ) ) {
			foreach ( $this->_data as $row ) {
				$html_rows .= '<tr>';
				foreach ( $columns as $key => $title ) {
					$class_td = '';
					if ( 'cb' === $key ) {
						$class_td .= ' checkcolumn ';
					}
					$func = 'column_' . $key;
					$html_rows .= '<td class="' . $class_td . '">';
					$html_rows .= method_exists( $this, $func ) ? $this->$func( $row ) : $this->column_default( $row, $key );
					if ( AC()->is_request( 'admin' ) ) {
						if ( $key === $this->_primary ) {
							$html_rows .= $this->row_actions( $this->get_row_action( $row ) );
						}
					}
					$html_rows .= '</td>';
				}
				$html_rows .= '</tr>';
			}
		}

		return '
			<div>
				<table class="wp-list-table tableinne widefat striped posts ' . AC()->helper->get_html_global( 'table_class' ) . '" cellspacing="1">
				<thead>' . $html_header . '</thead>
				<tbody>' . $html_rows . '</tbody>
				</table>
			</div>
			<div>' . $this->get_pagination()->get_list_footer() . '</div>
		';
	}

	/**
	 * Generate the html for row actions
	 *
	 * @param array   $actions the actions to create url for.
	 * @param boolean $always_visible if false, only visible when moused over.
	 **/
	protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i = 0;

		if ( ! $action_count ) {
			return '';
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( $i === $action_count ) ? $sep = '' : $sep = ' | ';
			$out .= '<span class="' . $action . '">' . $link . $sep . '</span>';
		}
		$out .= '</div>';

		return $out;
	}

	/**
	 * Get language ids for the sql table
	 *
	 * @param string $table the table to check.
	 * @param int    $id get data of specific row in table.
	 **/
	public function get_language_ids( $table, $id = 0 ) {
		$columns = AC()->db->get_column( 'DESC ' . $table );

		$idlang_fields = array();
		foreach ( $columns as $column ) {
			if ( substr( $column, 0, 7 ) === 'idlang_' ) {
				$idlang_fields[ $column ] = 0;
			}
		}

		$rows = AC()->db->get_objectlist( 'SELECT ' . implode( ',', array_keys( $idlang_fields ) ) . ' FROM ' . $table . ' WHERE id=' . (int) $id );
		foreach ( $rows as $row ) {
			foreach ( $idlang_fields as $k => $v ) {
				$idlang_fields[ $k ] = array(
					'name' => substr( $k, 7 ),
					'elem_id' => $row->{$k},
				);
			}
		}

		return $idlang_fields;
	}

	/**
	 * Check if to use ? or & with querystring
	 *
	 * @param string $url the url to check.
	 **/
	public function query_and_or_questionmark( $url ) {
		$query_separator = '?';
		if ( ! empty( $url ) ) {
			list( $part1, $part2 ) = explode( '#', $url );
			if ( ! empty( $part2 ) ) {
				if ( strpos( $part2, '?' ) !== false ) {
					$query_separator = '&';
				}
			} elseif ( strpos( $part1, '?' ) !== false ) {
				$query_separator = '&';
			}
		}
		return $query_separator;
	}

	/**
	 * Remove args from querystring
	 *
	 * @param string $key key of the item to remove.
	 * @param string $query the query to manipulate.
	 **/
	public function remove_query_arg( $key, $query ) {
		if ( empty( $query ) ) {
			return $query;
		}

		list( $part1, $part2 ) = explode( '#', $query );
		$part_to_use = empty( $part2 ) ? $part1 : $part2;
		if ( strpos( $part_to_use, '?' ) === false ) {
			return $query;
		}
		list( $link, $query_string ) = explode( '?', $part_to_use );
		$tmp = array();
		parse_str( $query_string, $tmp );
		unset( $tmp[ $key ] );

		if ( empty( $tmp ) ) {
			return empty( $part2 )
				? $link
				: $part1 . '#' . $link;
		} else {
			return empty( $part2 )
				? $link . '?' . http_build_query( $tmp )
				: $part1 . '#' . $link . '?' . http_build_query( $tmp );
		}
	}

	/**
	 * Prepare url
	 *
	 * @param string|array $queries the query string.
	 **/
	public function prepare_url( $queries ) {
		if ( ! is_array( $queries ) ) {
			$queries = array( $queries );
		}

		$current_url = $this->current_url;
		foreach ( $queries as $query ) {
			$current_url = $this->remove_query_arg( $query, $current_url );
		}
		$query_separator = $this->query_and_or_questionmark( $current_url );

		return $current_url . $query_separator;
	}

	/**
	 * Get the columns to sort by
	 **/
	protected function get_sortable_columns() {
		return array();
	}

	/**
	 * Get an item from a data row
	 *
	 * @param string $property the key.
	 * @param string $default if not found return this.
	 **/
	public function get( $property, $default = null ) {
		if ( $this->_loadEntry() ) {
			if ( isset( $this->_entry->{$property} ) ) {
				return $this->_entry->{$property};
			}
		}
		return $default;
	}

	/**
	 * Set the item id
	 *
	 * @param int $id the id.
	 **/
	public function set_id( $id ) {
		// Set entry id and wipe data.
		$this->_id = $id;
		$this->_entry = null;
	}

	/**
	 * Get the list from query
	 *
	 * @param string $query the sql query to run.
	 * @param string $key the sql key to arrange array by.
	 * @param int    $limitstart the position to start list.
	 * @param int    $limit the number of items to return.
	 **/
	public function get_list( $query, $key = null, $limitstart = 0, $limit = 0 ) {
		$query = trim( $query );
		$_iscount = false;
		if ( strtolower( substr( $query, 0, 7 ) ) === 'select ' ) {
			$_iscount = true;
			$query = 'SELECT SQL_CALC_FOUND_ROWS ' . substr( $query, 7 );
		}

		if ( ! empty( $limit ) ) {
			$query .= ' LIMIT ' . (int) $limit;
		}
		$query_offset = '';
		if ( ! empty( $limitstart ) ) {
			$query_offset = ' OFFSET ' . (int) $limitstart;
		}
		$results = AC()->db->get_objectlist( $query . $query_offset, $key );

		if ( $_iscount ) {
			$this->_total = AC()->db->get_value( 'SELECT FOUND_ROWS() as totalRows' );
			if ( $this->_total < $this->limitstart ) {
				$this->set_state( 'limitstart', 0 );
				$results = AC()->db->get_objectlist( $query, $key );
			}
		}

		return $results;
	}

	/**
	 * Get the list
	 **/
	public function get_data() {
		if ( empty( $this->_data ) ) {
			$query = $this->buildquery();
			$this->_data = $this->get_list( $query, null, $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );
		}
		return $this->_data;
	}

	/**
	 * Get the total items
	 **/
	public function get_total() {
		if ( empty( $this->_total ) && ! ctype_digit( $this->_total ) ) {
			$query = $this->buildquery();
			$this->_total = $this->get_list_count( $query );
		}
		return $this->_total;
	}

	/**
	 * Get the list count
	 *
	 * @param string $query the sql query to get list.
	 **/
	protected function get_list_count( $query ) {
		$rows = AC()->db->get_arraylist( $query );
		return (int) count( $rows );
	}

	/**
	 * Get the pagination class
	 **/
	public function get_pagination() {
		if ( empty( $this->_pagination ) ) {
			$this->_pagination = AC()->helper->new_class( 'Cmcoupon_Library_Pagination_Base' );
			$this->_pagination->init( $this->get_total(), $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );
		}
		return $this->_pagination;
	}

	/**
	 * Get the buttons needed for each row delete/publish/edit... etc
	 *
	 * @param object $row the item.
	 **/
	protected function get_row_action( $row ) {
		return array();
	}

	/**
	 * On edit get the data
	 **/
	public function get_entry() {

		$row = JTable::getInstance( $this->_type, 'CmCouponTable' );
		$row->load( $this->_id );
		$this->_entry = $row;

		return $this->_entry;
	}

}
