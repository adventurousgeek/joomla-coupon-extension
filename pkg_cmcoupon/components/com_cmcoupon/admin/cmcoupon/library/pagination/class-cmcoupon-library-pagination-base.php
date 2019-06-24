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
class Cmcoupon_Library_Pagination_Base {

	/**
	 * Raw body data.
	 *
	 * @var string
	 */
	public $limitstart = null;

	/**
	 * The total number per page to return.
	 *
	 * @var int
	 */
	public $limit = null;

	/**
	 * The total number of items.
	 *
	 * @var int
	 */
	public $total = null;

	/**
	 * Url prefix.
	 *
	 * @var string
	 */
	public $prefix = null;

	/**
	 * The first page.
	 *
	 * @var int
	 */
	public $pages_start;

	/**
	 * The last page.
	 *
	 * @var int
	 */
	public $pages_stop;

	/**
	 * The current page.
	 *
	 * @var int
	 */
	public $pages_current;

	/**
	 * Total pages.
	 *
	 * @var int
	 */
	public $pages_total;

	/**
	 * Show all.
	 *
	 * @var boolean
	 */
	protected $viewall = false;

	/**
	 * Additional url parameters.
	 *
	 * @var string
	 */
	protected $additional_url_params = array();

	/**
	 * App.
	 *
	 * @var string
	 */
	protected $app = null;

	/**
	 * Data.
	 *
	 * @var string
	 */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @param integer $total       The total number of items.
	 * @param integer $limitstart  The offset of the item to start at.
	 * @param integer $limit       The number of items to display per page.
	 * @param string  $prefix      The prefix used for request variables.
	 */
	public function init( $total, $limitstart, $limit, $prefix = '' ) {

		$this->total = (int) $total;
		$this->limitstart = (int) max( $limitstart, 0 );
		$this->limit = (int) max( $limit, 0 );
		$this->prefix = $prefix;
		$this->current_url = AC()->helper->get_request( 'urlx' );

		if ( $this->limit > $this->total ) {
			$this->limitstart = 0;
		}

		if ( ! $this->limit ) {
			$this->limit = $total;
			$this->limitstart = 0;
		}

		// If limitstart is greater than total (i.e. we are asked to display records that don't exist)
		// then set limitstart to display the last natural page of results.
		if ( $this->limitstart > $this->total - $this->limit ) {
			$this->limitstart = max( 0, (int) ( ceil( $this->total / $this->limit ) - 1 ) * $this->limit );
		}

		// Set the total pages and current page values.
		if ( $this->limit > 0 ) {
			$this->pages_total = ceil( $this->total / $this->limit );
			$this->pages_current = ceil( ( $this->limitstart + 1 ) / $this->limit );
		}

		// Set the pagination iteration loop values.
		$displayed_pages = 10;
		$this->pages_start = $this->pages_current - ( $displayed_pages / 2 );

		if ( $this->pages_start < 1 ) {
			$this->pages_start = 1;
		}

		if ( $this->pages_start + $displayed_pages > $this->pages_total ) {
			$this->pages_stop = $this->pages_total;

			if ( $this->pages_total < $displayed_pages ) {
				$this->pages_start = 1;
			} else {
				$this->pages_start = $this->pages_total - $displayed_pages + 1;
			}
		} else {
			$this->pages_stop = $this->pages_start + $displayed_pages - 1;
		}

		// If we are viewing all records set the view all flag to true.
		if ( 0 === (int) $limit ) {
			$this->viewall = true;
		}
	}

	/**
	 * Method to set an additional URL parameter to be added to all pagination class generated
	 * links.
	 *
	 * @param   string $key    The name of the URL parameter for which to set a value.
	 * @param   mixed  $value  The value to set for the URL parameter.
	 *
	 * @return  mixed  The old value for the parameter.
	 */
	public function set_additional_url_param( $key, $value ) {
		// Get the old value to return and set the new one for the URL parameter.
		$result = isset( $this->additional_url_params[ $key ] ) ? $this->additional_url_params[ $key ] : null;

		// If the passed parameter value is null unset the parameter, otherwise set it to the given value.
		if ( null === $value ) {
			unset( $this->additional_url_params[ $key ] );
		} else {
			$this->additional_url_params[ $key ] = $value;
		}

		return $result;
	}

	/**
	 * Method to get an additional URL parameter (if it exists) to be added to
	 * all pagination class generated links.
	 *
	 * @param string $key  The name of the URL parameter for which to get the value.
	 *
	 * @return  mixed  The value if it exists or null if it does not.
	 */
	public function get_additional_url_param( $key ) {
		$result = isset( $this->additional_url_params[ $key ] ) ? $this->additional_url_params[ $key ] : null;

		return $result;
	}

	/**
	 * Return the rationalised offset for a row with a given index.
	 *
	 * @param integer $index  The row index.
	 *
	 * @return  integer  Rationalised offset for a row with a given index.
	 */
	public function get_row_offset( $index ) {
		return $index + 1 + $this->limitstart;
	}

	/**
	 * Return the pagination data object, only creating it if it doesn't already exist.
	 *
	 * @return  stdClass  Pagination data object.
	 */
	public function get_data() {
		if ( ! $this->data ) {
			$this->data = $this->build_data_object();
		}

		return $this->data;
	}

	/**
	 * Create and return the pagination pages counter string, ie. Page 2 of 4.
	 *
	 * @return  string   Pagination pages counter string.
	 */
	public function get_pages_counter() {
		$html = null;

		if ( $this->pages_total > 1 ) {
			// translators: %1$s: current page  %2$s: total pages.
			$html .= sprintf( AC()->lang->__( 'Page %1$s of %2$s' ), $this->pages_current, $this->pages_total );
		}

		return $html;
	}

	/**
	 * Create and return the pagination result set counter string, e.g. Results 1-10 of 42
	 *
	 * @return  string   Pagination result set counter string.
	 */
	public function get_results_counter() {
		$html = null;
		$from_result = $this->limitstart + 1;

		// If the limit is reached before the end of the list.
		if ( $this->limitstart + $this->limit < $this->total ) {
			$to_result = $this->limitstart + $this->limit;
		} else {
			$to_result = $this->total;
		}

		// If there are results found.
		if ( $this->total > 0 ) {
			// translators: %1$s: start page  %2$s: end page %3$s total pages.
			$msg = sprintf( AC()->lang->__( 'Results %1$s - %2$s of %3$s' ), $from_result, $to_result, $this->total );
			$html .= "\n" . $msg;
		} else {
			$html .= "\n" . AC()->lang->__( 'No records found.' );
		}

		return $html;
	}

	/**
	 * Get the pagination links
	 *
	 * @param string $layout_id  Layout to render the links.
	 * @param array  $options   Optional array with settings for the layout.
	 *
	 * @return  string  Pagination links.
	 *
	 * @since   3.3
	 */
	public function get_pagination_links( $layout_id = 'pagination.links', $options = array() ) {
		// Allow to receive a null layout.
		$layout_id = 'pagination.links';

		$list = array(
			'prefix'       => $this->prefix,
			'limit'        => $this->limit,
			'limitstart'   => $this->limitstart,
			'total'        => $this->total,
			'limitfield'   => $this->get_list_box(),
			'pagescounter' => $this->get_pages_counter(),
			'pages'        => $this->get_pagination_pages(),
			'pages_total'   => $this->pages_total,
		);
		return AC()->helper->render_layout( $layout_id, array(
			'list' => $list,
			'options' => $options,
		) );
	}

	/**
	 * Create and return the pagination pages list, ie. Previous, Next, 1 2 3 ... x.
	 *
	 * @return  array  Pagination pages list.
	 */
	public function get_pagination_pages() {
		$list = array();

		if ( $this->total > $this->limit ) {
			// Build the page navigation list.
			$data = $this->build_data_object();

			// All.
			$list['all']['active'] = ( null !== $data->all->base );
			$list['all']['data']   = $data->all;

			// Start.
			$list['start']['active'] = ( null !== $data->start->base );
			$list['start']['data']   = $data->start;

			// Previous link.
			$list['previous']['active'] = ( null !== $data->previous->base );
			$list['previous']['data']   = $data->previous;

			// Make sure it exists.
			$list['pages'] = array();

			foreach ( $data->pages as $i => $page ) {
				$list['pages'][ $i ]['active'] = ( null !== $page->base );
				$list['pages'][ $i ]['data']   = $page;
			}

			$list['next']['active'] = ( null !== $data->next->base );
			$list['next']['data']   = $data->next;

			$list['end']['active'] = ( null !== $data->end->base );
			$list['end']['data']   = $data->end;
		}

		return $list;
	}

	/**
	 * Return the pagination footer.
	 *
	 * @return  string  Pagination footer.
	 */
	public function get_list_footer() {
		return $this->get_pagination_links();
	}

	/**
	 * Creates a dropdown box for selecting how many records to show per page.
	 *
	 * @return  string  The HTML for the limit # input box.
	 */
	public function get_list_box() {
		$limits = array();

		// Make the option list.
		for ( $i = 5; $i <= 30; $i += 5 ) {
			$limits[] = $this->select_option( "$i" );
		}

		$limits[] = $this->select_option( '50', AC()->lang->__( '50' ) );
		$limits[] = $this->select_option( '100', AC()->lang->__( '100' ) );
		$limits[] = $this->select_option( '0', AC()->lang->__( 'All' ) );

		$selected = $this->viewall ? 0 : $this->limit;

		// Build the select list.
		$html = $this->select_genericlist(
			$limits,
			$this->prefix . 'limit',
			'class="inputbox input-mini" size="1" onchange="jQuery(this.form).submit();"',
			'value',
			'text',
			$selected
		);

		return $html;
	}

	/**
	 * Configure a select option
	 *
	 * @param array   $value value of the select item.
	 * @param string  $text label of the select item.
	 * @param string  $opt_key the key of $arr to use for option value.
	 * @param string  $opt_text the key of $arr to use for option label.
	 * @param boolean $disable is the select option disabled.
	 **/
	public function select_option( $value, $text = '', $opt_key = 'value', $opt_text = 'text', $disable = false ) {
		$options = array(
			'attr' => null,
			'disable' => false,
			'option.attr' => null,
			'option.disable' => 'disable',
			'option.key' => 'value',
			'option.label' => null,
			'option.text' => 'text',
		);

		if ( is_array( $opt_key ) ) {
			// Merge in caller's options.
			$options = array_merge( $options, $opt_key );
		} else {
			// Get options from the parameters.
			$options['option.key'] = $opt_key;
			$options['option.text'] = $opt_text;
			$options['disable'] = $disable;
		}

		$obj = new stdClass();
		$obj->{$options['option.key']}  = $value;
		$obj->{$options['option.text']} = trim( $text ) ? $text : $value;

		// If a label is provided, save it. If no label is provided and there is
		// a label name, initialise to an empty string.
		$has_property = null !== $options['option.label'];

		if ( isset( $options['label'] ) ) {
			$label_property = $has_property ? $options['option.label'] : 'label';
			$obj->{$label_property} = $options['label'];
		} elseif ( $has_property ) {
			$obj->{$options['option.label']} = '';
		}

		// Set attributes only if there is a property and a value.
		if ( null !== $options['attr'] ) {
			$obj->{$options['option.attr']} = $options['attr'];
		}

		// Set disable only if it has a property and a value.
		if ( null !== $options['disable'] ) {
			$obj->{$options['option.disable']} = $options['disable'];
		}

		return $obj;
	}

	/**
	 * Create select options
	 *
	 * @param array   $arr select iteems.
	 * @param string  $opt_key the key of $arr to use for option value.
	 * @param string  $opt_text the key of $arr to use for option label.
	 * @param string  $selected selected item.
	 * @param boolean $translate if there needs to be a translation.
	 **/
	public static function select_options( $arr, $opt_key = 'value', $opt_text = 'text', $selected = null, $translate = false ) {
		$format_options = array(
			'format.depth' => 0,
			'format.eol' => "\n",
			'format.indent' => "\t",
		);
		$option_defaults = array(
			'option' => array(
				'option.attr' => null,
				'option.disable' => 'disable',
				'option.id' => null,
				'option.key' => 'value',
				'option.key.toHtml' => true,
				'option.label' => null,
				'option.label.toHtml' => true,
				'option.text' => 'text',
				'option.text.toHtml' => true,
				'option.class' => 'class',
				'option.onclick' => 'onclick',
			),
		);
		$options = array_merge(
			$format_options,
			$option_defaults['option'],
			array(
				'format.depth' => 0,
				'groups' => true,
				'list.select' => null,
				'list.translate' => false,
			)
		);

		if ( is_array( $opt_key ) ) {
			// Set default options and overwrite with anything passed in.
			$options = array_merge( $options, $opt_key );
		} else {
			// Get options from the parameters.
			$options['option.key'] = $opt_key;
			$options['option.text'] = $opt_text;
			$options['list.select'] = $selected;
			$options['list.translate'] = $translate;
		}

		$html = '';
		$base_indent = str_repeat( $options['format.indent'], $options['format.depth'] );

		foreach ( $arr as $element_key => &$element ) {
			$attr = '';
			$extra = '';
			$label = '';
			$id = '';

			if ( is_array( $element ) ) {
				$key = null === $options['option.key'] ? $element_key : $element[ $options['option.key'] ];
				$text = $element[ $options['option.text'] ];

				if ( isset( $element[ $options['option.attr'] ] ) ) {
					$attr = $element[ $options['option.attr'] ];
				}

				if ( isset( $element[ $options['option.id'] ] ) ) {
					$id = $element[ $options['option.id'] ];
				}

				if ( isset( $element[ $options['option.label'] ] ) ) {
					$label = $element[ $options['option.label'] ];
				}

				if ( isset( $element[ $options['option.disable'] ] ) && $element[ $options['option.disable'] ] ) {
					$extra .= ' disabled="disabled"';
				}
			} elseif ( is_object( $element ) ) {

				$key = null === $options['option.key'] ? $element_key : $element->{$options['option.key']};
				$text = $element->{$options['option.text']};

				if ( isset( $element->{$options['option.attr']} ) ) {
					$attr = $element->{$options['option.attr']};
				}

				if ( isset( $element->{$options['option.id']} ) ) {
					$id = $element->{$options['option.id']};
				}

				if ( isset( $element->{$options['option.label']} ) ) {
					$label = $element->{$options['option.label']};
				}

				if ( isset( $element->{$options['option.disable']} ) && $element->{$options['option.disable']} ) {
					$extra .= ' disabled="disabled"';
				}

				if ( isset( $element->{$options['option.class']} ) && $element->{$options['option.class']} ) {
					$extra .= ' class="' . $element->{$options['option.class']} . '"';
				}

				if ( isset( $element->{$options['option.onclick']} ) && $element->{$options['option.onclick']} ) {
					$extra .= ' onclick="' . $element->{$options['option.onclick']} . '"';
				}
			} else {
				// This is a simple associative array.
				$key = $element_key;
				$text = $element;
			}

			$key = (string) $key;

			// If no string after hyphen - take hyphen out.
			$split_text = explode( ' - ', $text, 2 );
			$text = $split_text[0];

			if ( isset( $split_text[1] ) && '' !== $split_text[1] && ! preg_match( '/^[\s]+$/', $split_text[1] ) ) {
				$text .= ' - ' . $split_text[1];
			}

			if ( $options['option.label.toHtml'] ) {
				$label = htmlentities( $label );
			}

			if ( is_array( $attr ) ) {
				$attr = ArrayHelper::toString( $attr );
			} else {
				$attr = trim( $attr );
			}

			$extra = ( $id ? ' id="' . $id . '"' : '' ) . ( $label ? ' label="' . $label . '"' : '' ) . ( $attr ? ' ' . $attr : '' ) . $extra;

			if ( is_array( $options['list.select'] ) ) {
				foreach ( $options['list.select'] as $val ) {
					$key2 = is_object( $val ) ? $val->{$options['option.key']} : $val;

					if ( $key === $key2 ) {
						$extra .= ' selected="selected"';
						break;
					}
				}
			} elseif ( (string) $key === (string) $options['list.select'] ) {
				$extra .= ' selected="selected"';
			}

			// Generate the option, encoding as required.
			$html .= $base_indent . '<option value="' . ( $options['option.key.toHtml'] ? htmlspecialchars( $key, ENT_COMPAT, 'UTF-8' ) : $key ) . '"'
				. $extra . '>';
			$html .= $options['option.text.toHtml'] ? htmlentities( html_entity_decode( $text, ENT_COMPAT, 'UTF-8' ), ENT_COMPAT, 'UTF-8' ) : $text;
			$html .= '</option>' . $options['format.eol'];
		}

		return $html;
	}

	/**
	 * Create a select box
	 *
	 * @param array   $data select iteems.
	 * @param string  $name select name.
	 * @param string  $attribs attribute of the select box.
	 * @param string  $opt_key the key of $data to use for option value.
	 * @param string  $opt_text the key of $data to use for option label.
	 * @param string  $selected selected item.
	 * @param boolean $idtag if there needs to be an id tag.
	 * @param boolean $translate if there needs to be a translation.
	 **/
	public function select_genericlist( $data, $name, $attribs = null, $opt_key = 'value', $opt_text = 'text', $selected = null, $idtag = false, $translate = false ) {
		// Set default options.
		$format_options = array(
			'format.depth' => 0,
			'format.eol' => "\n",
			'format.indent' => "\t",
		);
		$options = array_merge( $format_options, array(
			'format.depth' => 0,
			'id' => false,
		) );

		if ( is_array( $attribs ) && func_num_args() === 3 ) {
			// Assume we have an options array.
			$options = array_merge( $options, $attribs );
		} else {
			// Get options from the parameters.
			$options['id'] = $idtag;
			$options['list.attr'] = $attribs;
			$options['list.translate'] = $translate;
			$options['option.key'] = $opt_key;
			$options['option.text'] = $opt_text;
			$options['list.select'] = $selected;
		}

		$attribs = '';

		if ( isset( $options['list.attr'] ) ) {
			if ( is_array( $options['list.attr'] ) ) {
				$attribs = ArrayHelper::toString( $options['list.attr'] );
			} else {
				$attribs = $options['list.attr'];
			}

			if ( '' !== $attribs ) {
				$attribs = ' ' . $attribs;
			}
		}

		$id = false !== $options['id'] ? $options['id'] : $name;
		$id = str_replace( array( '[', ']', ' ' ), '', $id );

		$base_indent = str_repeat( $options['format.indent'], $options['format.depth']++ );
		$html = $base_indent . '<select' . ( '' !== $id ? ' id="' . $id . '"' : '' ) . ' name="' . $name . '"' . $attribs . '>' . $options['format.eol']
			. $this->select_options( $data, $options ) . $base_indent . '</select>' . $options['format.eol'];

		return $html;
	}

	/**
	 * Create the html for a list footer
	 *
	 * @param array $list  Pagination list data structure.
	 *
	 * @return  string  HTML for a list start, previous, next,end
	 */
	protected function _list_render( $list ) {
		return AC()->helper->render_layout( 'pagination.list', array(
			'list' => $list,
		) );
	}


	/**
	 * Create and return the pagination data object.
	 *
	 * @return  stdClass  Pagination data object.
	 */
	protected function build_data_object() {
		$data = new stdClass();

		// Build the additional URL parameters string.
		$params = '';
		$this->current_url = $this->remove_query_arg( 'paged', $this->current_url );
		$query_separator = $this->query_and_or_questionmark( $this->current_url );

		if ( ! empty( $this->additional_url_params ) ) {
			foreach ( $this->additional_url_params as $key => $value ) {
				$params .= '&' . $key . '=' . $value;
			}
		}

		$data->all = AC()->helper->new_class( 'Cmcoupon_Library_Pagination_Object' );
		$data->all->init( AC()->lang->__( 'View All' ), $this->prefix );

		if ( ! $this->viewall ) {
			$data->all->base = '0';
			$data->all->link = $this->current_url . $params . $query_separator . $this->prefix;
		}

		// Set the start and previous data objects.
		$data->start = AC()->helper->new_class( 'Cmcoupon_Library_Pagination_Object' );
		$data->start->init( AC()->lang->__( 'Start' ), $this->prefix );

		$data->previous = AC()->helper->new_class( 'Cmcoupon_Library_Pagination_Object' );
		$data->previous->init( AC()->lang->__( 'Prev' ), $this->prefix );

		if ( $this->pages_current > 1 ) {
			$page = ( $this->pages_current - 2 ) * $this->limit;

			// Set the empty for removal from route.
			$data->start->base = '0';
			$data->start->link = $this->current_url . $params . $query_separator . $this->prefix . 'paged=1';
			$data->previous->base = $page;
			$data->previous->link = $this->current_url . $params . $query_separator . $this->prefix . 'paged=' . max( 1, $this->pages_current - 1 );
		}

		// Set the next and end data objects.
		$data->next = AC()->helper->new_class( 'Cmcoupon_Library_Pagination_Object' );
		$data->next->init( AC()->lang->__( 'Next' ), $this->prefix );

		$data->end = AC()->helper->new_class( 'Cmcoupon_Library_Pagination_Object' );
		$data->end->init( AC()->lang->__( 'End' ), $this->prefix );

		if ( $this->pages_current < $this->pages_total ) {
			$next = $this->pages_current * $this->limit;
			$end = ( $this->pages_total - 1 ) * $this->limit;

			$data->next->base = $next;
			$data->next->link = $this->current_url . $params . $query_separator . $this->prefix . 'paged=' . ( $this->pages_current + 1 );
			$data->end->base = $end;
			$data->end->link = $this->current_url . $params . $query_separator . $this->prefix . 'paged=' . $this->pages_total;
		}

		$data->pages = array();
		$stop = $this->pages_stop;

		for ( $i = $this->pages_start; $i <= $stop; $i++ ) {
			$offset = ($i - 1) * $this->limit;

			$data->pages[ $i ] = AC()->helper->new_class( 'Cmcoupon_Library_Pagination_Object' );
			$data->pages[ $i ]->init( $i, $this->prefix );

			if ( (int) $i !== (int) $this->pages_current || $this->viewall ) {
				$data->pages[ $i ]->base = $offset;
				$data->pages[ $i ]->link = $this->current_url . $params . $query_separator . $this->prefix . 'paged=' . $i;
			} else {
				$data->pages[ $i ]->active = true;
			}
		}

		return $data;
	}

	/**
	 * Modifies a property of the object, creating it if it does not already exist.
	 *
	 * @param  string $property  The name of the property.
	 * @param  mixed  $value     The value of the property to set.
	 *
	 * @return  void
	 */
	public function set( $property, $value = null ) {
		if ( strpos( $property, '.' ) ) {
			$prop = explode( '.', $property );
			$prop[1] = ucfirst( $prop[1] );
			$property = implode( $prop );
		}

		$this->$property = $value;
	}

	/**
	 * Returns a property of the object or the default value if the property is not set.
	 *
	 * @param string $property  The name of the property.
	 * @param mixed  $default   The default value.
	 *
	 * @return  mixed    The value of the property.
	 */
	public function get( $property, $default = null ) {

		if ( strpos( $property, '.' ) ) {
			$prop = explode( '.', $property );
			$prop[1] = ucfirst( $prop[1] );
			$property = implode( $prop );
		}

		if ( isset( $this->$property ) ) {
			return $this->$property;
		}

		return $default;
	}

	/**
	 * Determines if to use a ? or & for next variable in querystring
	 *
	 * @param string $url the url to manipulate.
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
	 * Remove extras from the query string
	 *
	 * @param string $key the key to remove.
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
		parse_str( $query_string,$tmp );
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


}
