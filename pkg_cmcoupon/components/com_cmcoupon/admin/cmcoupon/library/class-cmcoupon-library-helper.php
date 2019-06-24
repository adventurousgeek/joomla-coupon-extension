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
class CmCoupon_Library_Helper {

	/**
	 * Check if input is a positive int
	 *
	 * @param mixed $value check this.
	 **/
	public function pos_int( $value ) {
		return ( (int) $value == $value && $value > 0 ) ? true : false;
	}

	/**
	 * Check variables and return the language version
	 *
	 * @param string $type the key.
	 * @param string $item the item set.
	 * @param array  $excludes exclude some items.
	 * @param array  $extra_vars extra.
	 **/
	public function vars( $type, $item = null, $excludes = null, $extra_vars = null ) {
		$vars = array(
			'function_type' => array(
				'coupon' => AC()->lang->__( 'Coupon' ),
				'giftcert' => AC()->lang->__( 'Gift Certificate' ),
				'shipping' => AC()->lang->__( 'Shipping' ),
				'buyxy' => AC()->lang->__( 'BuyX GetY' ),
				'buyxy2' => AC()->lang->__( 'BuyX GetY 2' ),
				'combination' => AC()->lang->__( 'Combination' ),
			),
			'asset_mode' => array(
				'include' => AC()->lang->__( 'Include' ),
				'exclude' => AC()->lang->__( 'Exclude' ),
			),
			'asset_type' => array(
				'product' => AC()->lang->__( 'Product' ),
				'category' => AC()->lang->__( 'Category' ),
				'manufacturer' => AC()->lang->__( 'Manufacturer' ),
				'vendor' => AC()->lang->__( 'Vendor' ),
				'custom' => AC()->lang->__( 'Custom' ),
				'shipping' => AC()->lang->__( 'Shipping' ),
				'coupon' => AC()->lang->__( 'Coupon' ),
				'country' => AC()->lang->__( 'Country' ),
				'countrystate' => AC()->lang->__( 'State/Province' ),
				'paymentmethod' => AC()->lang->__( 'Payment Method' ),
				'user' => AC()->lang->__( 'Customer' ),
				'usergroup' => AC()->lang->__( 'User Group' ),
			),
			'process_type_combination' => array(
				'first' => AC()->lang->__( 'First found match' ),
				'lowest' => AC()->lang->__( 'Lowest value' ),
				'highest' => AC()->lang->__( 'Highest value' ),
				'all' => AC()->lang->__( 'All that apply' ),
				'allonly' => AC()->lang->__( 'Only if ALL apply' ),
			),
			'process_type_buyxy' => array(
				'first' => AC()->lang->__( 'First found match' ),
				'lowest' => AC()->lang->__( 'Lowest value' ),
				'highest' => AC()->lang->__( 'Highest value' ),
			),
			'published' => array(
				'1' => AC()->lang->__( 'Published' ),
				'-1' => AC()->lang->__( 'Unpublished' ),
			),
			'state' => array(
				'published' => AC()->lang->__( 'Published' ),
				'unpublished' => AC()->lang->__( 'Unpublished' ),
				'template' => AC()->lang->__( 'Template' ),
				'balance' => AC()->lang->__( 'Balance' ),
			),
			'coupon_value_type' => array(
				'percent' => AC()->lang->__( 'Percent' ),
				'amount' => AC()->lang->__( 'Amount' ),
				'amount_per' => AC()->lang->__( 'Amount per item' ),
			),
			'discount_type' => array(
				'overall' => AC()->lang->__( 'Overall' ),
				'specific' => AC()->lang->__( 'Specific' ),
			),
			'min_value_type' => array(
				'overall' => AC()->lang->__( 'Overall' ),
				'specific' => AC()->lang->__( 'Specific' ),
				'specific_notax' => AC()->lang->__( 'Specific no tax' ),
			),
			'min_qty_type' => array(
				'overall' => AC()->lang->__( 'Overall' ),
				'specific' => AC()->lang->__( 'Specific' ),
			),
			'num_of_uses_type' => array(
				'total' => AC()->lang->__( 'Total' ),
				'per_user' => AC()->lang->__( 'Per customer' ),
			),
			'expiration_type' => array(
				'day' => AC()->lang->__( 'Day(s)' ),
				'month' => AC()->lang->__( 'Month(s)' ),
				'year' => AC()->lang->__( 'Year(s)' ),
			),
			'status' => array(
				'active' => AC()->lang->__( 'Active' ),
				'inactive' => AC()->lang->__( 'Inactive' ),
				'used' => AC()->lang->__( 'Used' ),
			),
			'productpricetype' => array(
				'template' => AC()->lang->__( 'Template' ),
				'product_price_notax' => AC()->lang->__( 'Base price' ),
				'product_price' => AC()->lang->__( 'Base price with tax' ),
			),
		);
		if ( is_array( $extra_vars ) ) {
			$vars = array_merge( $extra_vars, $vars );
		}

		if ( '__all__' === $type ) {
			return $vars;
		}

		if ( isset( $vars[ $type ] ) ) {
			if ( isset( $item ) ) {
				if ( isset( $vars[ $type ][ $item ] ) ) {
					return $vars[ $type ][ $item ];
				} else {
					return '';
				}
			} else {
				$return_obj = $vars[ $type ];
				if ( ! is_array( $excludes ) ) {
					$excludes = array( $excludes );
				}
				if ( ! empty( $excludes ) && is_array( $excludes ) ) {
					foreach ( $excludes as $exclude ) {
						if ( isset( $return_obj[ $exclude ] ) ) {
							unset( $return_obj[ $exclude ] );
						}
					}
				}
				return $return_obj;
			}
		}
	}

	/**
	 * User session save and request settings
	 *
	 * @param string $key the user key.
	 * @param string $request the requeset key.
	 * @param mixed  $default returned if not found.
	 **/
	public function get_userstate_request( $key, $request, $default = null ) {

		$cur_state = $this->get_session( 'userConfigSettings', $key, $default );
		$new_state = $this->get_request( $request, null );

		if ( null === $new_state ) {
			return $cur_state;
		}

		// Save the new value only if it was set in this request.
		$this->set_session( 'userConfigSettings', $key, $new_state );

		return $new_state;
	}

	/**
	 * Get a GET POST REQUEST FILE request
	 *
	 * @param string $key the key.
	 * @param mixed  $default if not found return this.
	 * @param string $type type of request.
	 **/
	public function get_request( $key = null, $default = '', $type = null, $mask = 'standard' ) {
		$x_request = $_REQUEST;
		$x_post = $_POST;
		$x_get = $_GET;
		$x_file = $_FILES;
		if ( is_null( $key ) ) {
			if ( empty( $type ) ) {
				$type = 'post';
			}
			if ( 'request' === $type ) {
				return $x_request;
			} elseif ( 'post' === $type ) {
				return $x_post;
			} elseif ( 'get' === $type ) {
				return $x_get;
			} elseif ( 'file' === $type ) {
				return $x_file;
			}
		} elseif ( strpos( $key, '.' ) === false ) {
			if ( empty( $type ) ) {
				$type = 'request';
			}

			if ( 'request' === $type ) {
				return isset( $x_request[ $key ] ) ? $x_request[ $key ] : $default;
			} elseif ( 'post' === $type ) {
				return isset( $x_post[ $key ] ) ? $x_post[ $key ] : $default;
			} elseif ( 'get' === $type ) {
				return isset( $x_get[ $key ] ) ? $x_get[ $key ] : $default;
			} elseif ( 'file' === $type ) {
				return isset( $x_file[ $key ] ) ? $x_file[ $key ] : $default;
			}
		} else {
			if ( empty( $type ) ) {
				$type = 'request';
			}

			$pos = strrpos( $key, '.' );
			$part1 = substr( $key, 0, $pos );
			$part2 = substr( $key, $pos + 1 );
			if ( 'request' === $type ) {
				return isset( $x_request[ $part1 ][ $part2 ] ) ? $x_request[ $part1 ][ $part2 ] : $default;
			} elseif ( 'post' === $type ) {
				return isset( $x_post[ $part1 ][ $part2 ] ) ? $x_post[ $part1 ][ $part2 ] : $default;
			} elseif ( 'get' === $type ) {
				return isset( $x_get[ $part1 ][ $part2 ] ) ? $x_get[ $part1 ][ $part2 ] : $default;
			} elseif ( 'file' === $type ) {
				return isset( $x_file[ $part1 ][ $part2 ] ) ? $x_file[ $part1 ][ $part2 ] : $default;
			}
		}
		return $default;
	}

	/**
	 * Make sure the ids are ints
	 *
	 * @param array $ids array of items to be converted to ints.
	 **/
	public function scrubids( $ids ) {
		if ( ! is_array( $ids ) ) {
			$ids = explode( ',', $ids );
		}
		$ids = array_map( 'intval', $ids );
		if ( empty( $ids ) ) {
			$ids = array( 0 );
		}
		return implode( ',', $ids );
	}

	/**
	 * Redirect page
	 *
	 * @param string $path the page to redirect to.
	 **/
	public function redirect( $path ) {
		$separator = strpos( $path, '?' ) !== false ? '&' : '?';
		$html = '
			<script>
			jQuery( document ).ready(function() {
				window.location.hash = "#/cmcoupon/' . $path . $separator . 'cache=' . mt_rand() . '";
			});
			</script>
		';
		echo $html;
		exit;
	}

	/**
	 * Ajax route
	 *
	 * @param array $parts the querystring.
	 **/
	public function route( $parts ) {

		if ( empty( $parts['type'] ) ) {
			return;
		}
		switch ( $parts['type'] ) {
			case 'admin':
				if ( empty( $parts['view'] ) ) {
					return;
				}

				$class = 'CmCoupon_Admin_Controller_' . $parts['view'];
				$class = AC()->helper->new_class( $class );

				if ( ! empty( $parts['task'] ) ) {
					$function = 'do_' . $parts['task'];
					if ( method_exists( $class, $function ) ) {
						$class->$function();
					}
				}

				$layout = ! empty( $parts['layout'] ) ? $parts['layout'] : 'default';
				$function = 'show_' . $layout;

				if ( $this->is_check_dbupdate ) {
					if ( 'cmcoupon_admin_controller_dashboard' === trim( strtolower( get_class( $class ) ) ) && 'show_default' === $function ) {
						$installer = AC()->helper->new_class( 'CmCoupon_Helper_Update' );
						if ( version_compare( $installer->get_version(), CMCOUPON_VERSION, '<' ) ) {
							// update cmcoupon version.
							$installer->update();
						}
					}
				}

				$class->$function();

				return;

			case 'public':
				if ( empty( $parts['view'] ) ) {
					return;
				}

				$class = 'CmCoupon_Public_Controller_' . $parts['view'];
				$class = AC()->helper->new_class( $class );

				if ( ! empty( $parts['task'] ) ) {
					$function = 'do_' . $parts['task'];
					if ( method_exists( $class, $function ) ) {
						$class->$function();
					}
				}

				$layout = ! empty( $parts['layout'] ) ? $parts['layout'] : 'default';
				$function = 'show_' . $layout;
				$class->$function();

				return;
		}
	}

	/**
	 * Get html from layout file
	 *
	 * @param string $layout_file the file path.
	 * @param object $data variables to replace.
	 **/
	public function render_layout( $layout_file, $data = null ) {

		// Check possible overrides, and build the full path to layout file.
		$path = CMCOUPON_DIR . '/cmcoupon/layout';
		$tmp = explode( '.', $layout_file );
		foreach ( $tmp as $tmp2 ) {
			$path .= '/' . $tmp2;
		}
		$path .= '.php';

		// Nothing to show.
		if ( ! file_exists( $path ) ) {
			return '';
		}

		ob_start();
		include $path;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Require class to page
	 *
	 * @param string $class the name of the class to add.
	 **/
	public function add_class( $class ) {
		if ( class_exists( $class ) ) {
			return $class;
		}
		if ( strtolower( substr( $class, 0, 10 ) ) !== 'cmcoupon_' ) {
			return false;
		}

		if ( strpos( $class, '_' ) !== false ) {
			$pieces = explode( '_' , $class );
			array_shift( $pieces );
		} else {
			$pieces = preg_split( '/(?=[A-Z])/', trim( substr( $class, 9 ) ), -1, PREG_SPLIT_NO_EMPTY );
		}
		if ( empty( $pieces ) ) {
			return false;
		}
		$classname = 'Cmcoupon_' . implode( '_', $pieces );
		if ( class_exists( $classname ) ) {
			return $classname;
		}

		$pieces = array_map( 'strtolower', $pieces ); // lowercase items in array.
		$the_filename = array_pop( $pieces );
		$filename = 'class-cmcoupon-' . implode( '-', $pieces ) . '-' . $the_filename . '.php';

		$_cmcoupon_folders = array(
			'admin',
			'layout',
			'library',
			'media',
			'public',
		);
		$extra_folder = in_array( $pieces[0], $_cmcoupon_folders, true ) ? '/cmcoupon' : '';

		$path = CMCOUPON_DIR . $extra_folder . '/' . implode( '/', $pieces ) . '/' . $filename;
		//echo('$path = '.$path);
		if ( ! file_exists( $path ) ) {
			if ( count( $pieces ) === 1 && 'helper' === $pieces[0] ) {
				$classname = 'Cmcoupon_Library_' . $the_filename;
				if ( class_exists( $classname ) ) {
					return $classname;
				}
				$filename = 'class-cmcoupon-library-' . $the_filename . '.php';
				$path = CMCOUPON_DIR . '/cmcoupon/library/' . $filename;
				if ( ! file_exists( $path ) ) {
					return false;
				}

				require $path;
				return $classname;
			}
			return false;
		}

		require $path;
		return $classname;
	}

	/**
	 * Get a new class
	 *
	 * @param object $class the class to initialize.
	 **/
	public function new_class( $class ) {
		if ( class_exists( $class ) ) {
			return new $class();
		}

		$class_name = $this->add_class( $class );
		return false !== $class_name ? new $class_name() : null;
	}

	/**
	 * Set a system message
	 *
	 * @param string $msg message.
	 * @param string $type notice/error.
	 **/
	public function set_message( $msg, $type = 'notice' ) {
		$messages = $this->get_session( 'admin', 'messages', array() );
		if ( ! isset( $messages[ $type ] ) ) {
			$messages[ $type ] = array();
		}
		$messages[ $type ][] = $msg;
		$this->set_session( 'admin', 'messages', $messages );
	}

	/**
	 * Get enqueued messages and then clear it out
	 **/
	public function get_messages_and_flush() {
		$messages = $this->get_session( 'admin', 'messages', array() );
		if ( empty( $messages ) ) {
			return '';
		}
		$this->reset_session( 'admin', 'messages' );
		return $messages;
	}

	/**
	 * Fix relative paths in emails
	 *
	 * @param string $str the content.
	 **/
	public function fixpaths_relative_to_absolute( $str ) {

		$site_url = AC()->store->get_home_link();
		$site_url = rtrim( $site_url, '/' ) . '/';

		// image path.
		$str = preg_replace( '/src=\"(?!cid)(?!http).*/Uis', 'src="' . $site_url, $str );
		$str = str_replace( 'url(components', 'url(' . $site_url . 'components', $str );

		// links.
		$str = str_replace( 'href="..', 'href="', $str );
		$str = preg_replace( '/href=\"(?!cid)(?!http).*/Uis', 'href="' . $site_url, $str );

		return $str;
	}

	/**
	 * Get license info from db
	 **/
	public function getliveupdateinfo() {
		$license = '';
		$localkey = '';
		$rows = AC()->db->get_objectlist( 'SELECT id,value FROM #__cmcoupon_license' );
		foreach ( $rows as $row ) {
			if ( 'license' === $row->id ) {
				$license = $row->value;
			} elseif ( 'local_key' === $row->id ) {
				$localkey = $row->value;
			}
		}
		return array(
			'host' => AC()->store->get_home_link(),
			'license' => $license,
			'local_key' => $localkey,
		);
	}

	/**
	 * Get current customer balance
	 *
	 * @param string  $estore the store.
	 * @param boolean $refresh by pass cache.
	 **/
	public function customer_balance( $estore, $refresh = false ) {
		$user = AC()->helper->get_user();
		if ( empty( $user->id ) ) {
			return 0;
		}

		if ( ! $refresh ) {
			$balance = $this->get_session( 'site', 'customer_balance', null );
			if ( ! is_null( $balance ) ) {
				return $balance;
			}
		}

		$tmp = AC()->db->get_objectlist( '
			SELECT (c.coupon_value-IFNULL(SUM(h.total_product+h.total_shipping),0)) as balance 
			  FROM #__cmcoupon c
			  JOIN #__cmcoupon_customer_balance cb ON cb.coupon_id=c.id
			  LEFT JOIN #__cmcoupon_history h ON h.coupon_id=c.id AND h.estore=c.estore
			 WHERE c.estore="' . AC()->db->escape( $estore ) . '"
			   AND cb.user_id=' . (int) $user->id . '
			   AND c.state="balance"
			 GROUP BY cb.id
		' );
		$balance = 0;
		foreach ( $tmp as $row ) {
			$balance += (float) $row->balance;
		}

		$this->set_session( 'site', 'customer_balance', $balance );
		return $balance;
	}

	/**
	 * Convert line for csv
	 *
	 * @param array   $fields fieldlist.
	 * @param string  $delimiter comma or semi-colon.
	 * @param string  $enclosure generally quote.
	 * @param boolean $mysql_null allow nulls.
	 **/
	public function fputcsv2( array $fields, $delimiter = ',', $enclosure = '"', $mysql_null = false ) {
		$delimiter_esc = preg_quote( $delimiter, '/' );
		$enclosure_esc = preg_quote( $enclosure, '/' );

		$output = array();
		foreach ( $fields as $field ) {
			if ( null === $field && $mysql_null ) {
				$output[] = 'NULL';
				continue;
			}

			$output[] = preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field ) ? (
				$enclosure . str_replace( $enclosure, $enclosure . $enclosure, $field ) . $enclosure
			) : $field;
		}

		return join( $delimiter, $output ) . "\n";
	}

	/**
	 * Get global html
	 *
	 * @param string $item the type.
	 **/
	public function get_html_global( $item ) {
		return '';
	}

	/**
	 * Json encode
	 *
	 * @param array/object $data data.
	 **/
	public function json_encode( $data ) {
		return json_encode( $data );
	}

	/**
	 * Get the installed eshops
	 **/
	public function get_installed_estores() {
		return array();
	}

	/**
	 * Software trigger
	 *
	 * @param array/object $function function.
	 * @param array/object $input input.
	 **/
	public function trigger( $function, $input = array() ) {
		return null;
	}
}

if ( ! function_exists( 'AC' ) ) {
	/**
	 * Define CmCoupon function
	 **/
	function AC() {
		return CmCoupon::instance();
	}
}

if ( ! function_exists( 'printr' ) ) {
	/**
	 * Pretty print
	 *
	 * @param object/array $a item.
	 **/
	function printr( $a ) {
		echo '<pre>' . print_r( $a, 1 ) . '</pre>';
	}
}

if ( ! function_exists( 'printrx' ) ) {
	/**
	 * Pretty print and exit
	 *
	 * @param object/array $a item.
	 **/
	function printrx( $a ) {
		echo '<pre>' . print_r( $a, 1 ) . '</pre>';
		exit;
	}
}

if ( ! function_exists( 'cmtrace' ) ) {
	/**
	 * Tracer
	 **/
	function cmtrace() {
		ob_start();
		debug_print_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		$rtn = ob_get_contents();
		ob_end_clean();
		return $rtn;
	}
}
