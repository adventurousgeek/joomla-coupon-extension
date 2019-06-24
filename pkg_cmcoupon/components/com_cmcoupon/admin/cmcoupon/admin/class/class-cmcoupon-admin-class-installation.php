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
class CmCoupon_Admin_Class_Installation extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->name = 'installation';
		$this->_orderby = 'id';
		$this->_primary = 'name';
		parent::__construct();

		$this->store_file = array();
		$this->store_sql = array();
		$this->store_plugin = array();

		$this->installer = AC()->helper->new_class( 'Cmcoupon_Helper_Estore_' . CMCOUPON_ESTORE . '_Installation' );
		if ( ! empty( $this->installer ) ) {
			if ( $this->installer->is_installation() ) {
				$this->store_file = $this->installer->get_definition_file();
				$this->store_sql = $this->installer->get_definition_sql();
				$this->store_plugin = $this->installer->get_definition_plugin();
			}
		}

		$this->global_plugin = array();
		$this->global_installer = AC()->helper->new_class( 'CmCoupon_Helper_Installation' );
		if ( ! empty( $this->global_installer ) ) {
			$this->global_plugin = $this->global_installer->get_definition_plugin();
		}
	}

	/**
	 * Change estore
	 *
	 * @param string $estore store to change to.
	 **/
	public function change_estore( $estore ) {
		$this->store_file = array();
		$this->store_sql = array();
		$this->store_plugin = array();

		$this->installer = AC()->helper->new_class( 'Cmcoupon_Helper_Estore_' . $estore . '_Installation' );
		if ( ! empty( $this->installer ) ) {
			if ( $this->installer->is_installation() ) {
				$this->store_file = $this->installer->get_definition_file();
				$this->store_sql = $this->installer->get_definition_sql();
				$this->store_plugin = $this->installer->get_definition_plugin();
			}
		}
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" onclick="jQuery(this.form).find(\'td.checkcolumn input:checkbox\').prop(\'checked\',this.checked);" />',
			'status' => AC()->lang->__( 'Status' ),
			'type' => AC()->lang->__( 'Type' ),
			'name' => AC()->lang->__( 'Name' ),
			'desc' => AC()->lang->__( 'Description' ),
			'err' => AC()->lang->__( 'Error' ),
		);
		return $columns;
	}

	/**
	 * Sortable columns
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	/**
	 * Get installed items
	 */
	public function get_data() {
		if ( empty( $this->_data ) ) {
			$vars = array();

			foreach ( $this->global_plugin as $k => $row ) {
				$rtn = $this->global_installer->{$row['func']}( 'check', $k );
				$vars[ $k ] = (object) array(
					'id' => $k,
					'name' => $row['name'],
					'file' => '',
					'desc' => $row['desc'],
					'err' => ! empty( $rtn->error ) ? $rtn->error : '',
					'status' => ! empty( $rtn ) ? 'installed' : 'uninistalled',
					'type' => AC()->lang->__( 'Plugin' ),
				);
			}

			foreach ( $this->store_plugin as $k => $row ) {
				$rtn = $this->installer->{$row['func']}( 'check', $k );
				$vars[ $k ] = (object) array(
					'id' => $k,
					'name' => $row['name'],
					'file' => '',
					'desc' => $row['desc'],
					'err' => ! empty( $rtn->error ) ? $rtn->error : '',
					'status' => ! empty( $rtn ) ? 'installed' : 'uninistalled',
					'type' => AC()->lang->__( 'Plugin' ),
				);
			}

			$files = array();
			foreach ( $this->store_file as $k => $row ) {
				$files[ $row['func'] ]['indexes'][] = $row['index'];
			}
			foreach ( $files as $func => $row ) {
				$obj = $this->installer->{$func}( 'check', $row['indexes'] );
				$files[ $func ]['rtn'] = $this->inject_process( 'check', $obj->file, $obj->vars, $row['indexes'] );
				if ( ( $files[ $func ]['rtn'] instanceof Exception ) ) {
					$e = $files[ $func ]['rtn']->getMessage();
					$files[ $func ]['rtn'] = array();
					foreach( $row['indexes'] as $r_index ) {
						$files[ $func ]['rtn'][ $r_index ] = $e;
					}
				}
			}

			foreach ( $this->store_file as $k => $row ) {
				$vars[ $k ] = (object) array(
					'id' => $k,
					'name' => $row['name'],
					'file' => $row['file'],
					'desc' => $row['desc'],
					'err' => $files[ $row['func'] ]['rtn'][ $row['index'] ],
					'status' => empty( $files[ $row['func'] ]['rtn'][ $row['index'] ] ) ? 'installed' : 'uninistalled',
					'type' => AC()->lang->__( 'File update' ),
				);
			}

			foreach ( $this->store_sql as $k => $row ) {
				$rtn = $this->installer->{$row['func']}( 'check' );
				$vars[ $k ] = (object) array(
					'id' => $k,
					'name' => $row['name'],
					'file' => '',
					'desc' => $row['desc'],
					'err' => $rtn ? '' : '---',
					'status' => ! empty( $rtn ) ? 'installed' : 'uninistalled',
					'type' => AC()->lang->__( 'Database query' ),
				);
			}

			$this->_data = $vars;
		}
		return $this->_data;
	}

	/**
	 * Get total of installed items
	 */
	public function get_total() {
		return count( $this->store_file ) + count( $this->store_sql ) + count( $this->store_plugin ) + count( $this->global_plugin );
	}

	/**
	 * Action row column
	 *
	 * @param object $row the object.
	 */
	protected function get_row_action( $row ) {
		if ( 'installed' !== $row->status ) {
			return array(
				'install' => '<a href="#/cmcoupon/installation?task=install&id=' . $row->id . '">' . AC()->lang->__( 'Install' ) . '</a>',
			);
		}
		return array(
			'uninstall' => '<a href="#/cmcoupon/installation?task=uninstall&id=' . $row->id . '" class="submitdelete aria-button-if-js" onclick=\'return showNotice.warn();\'>' . AC()->lang->__( 'Uninstall' ) . '</a>',
		);
	}

	/**
	 * Checkbox column
	 *
	 * @param object $row the object.
	 */
	public function column_cb( $row ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%1$s" />', $row->id );
	}

	/**
	 * Default column behavior
	 *
	 * @param object $item the object.
	 * @param string $column_name the column.
	 */
	public function column_default( $item, $column_name ) {
		return $item->{$column_name};
	}

	/**
	 * Status column
	 *
	 * @param object $row the object.
	 */
	public function column_status( $row ) {
		$class = 'danger';
		$string = AC()->lang->__( 'Not installed' );
		if ( 'installed' === $row->status ) {
			if ( empty( $row->err ) ) {
				$class = 'success';
				$string = AC()->lang->__( 'Installed' );
			} else {
				$class = 'warning';
				$string = AC()->lang->__( 'Installed with error' );
			}
		}

		return '<label for="function_type_rd_coupon" class="awbtn active awbtn-' . $class . '">' . $string . '</label>';
	}

	/**
	 * Description column
	 *
	 * @param object $row the object.
	 */
	public function column_desc( $row ) {
		return ( ! empty( $row->file ) ? '<div>' . AC()->lang->__( 'File' ) . ': ' . $row->file . '</div>' : '' ) . '<div>' . $row->desc . '</div>';
	}

	/**
	 * Install selected items
	 *
	 * @param object $cids the object.
	 */
	public function install( $cids ) {
		if ( empty( $cids ) ) {
			return true;
		}

		$errors = array();

		foreach ( $cids as $file ) {
			if ( isset( $this->global_plugin[ $file ] ) ) {
				$ret = $this->global_installer->{$this->global_plugin[ $file ]['func']}( 'install', $file );
				if ( ! $ret ) {
					// translators: %s: plugin name.
					$errors[] = sprintf( AC()->lang->__( 'Unable to install plugin %s' ), $this->global_plugin[ $file ]['name'] );
				}
			}
		}

		foreach ( $cids as $file ) {
			if ( isset( $this->store_plugin[ $file ] ) ) {
				$ret = $this->installer->{$this->store_plugin[ $file ]['func']}( 'install', $file );
				if ( ! $ret ) {
					// translators: %s: plugin name.
					$errors[] = sprintf( AC()->lang->__( 'Unable to install plugin %s' ), $this->store_plugin[ $file ]['name'] );
				}
			}
		}

		$files = array();
		foreach ( $cids as $file ) {
			if ( isset( $this->store_file[ $file ] ) ) {
				$files[ $this->store_file[ $file ]['func'] ]['indexes'][] = $this->store_file[ $file ]['index'];
			}
		}
		// check all.
		foreach ( $files as $func => $row ) {
			$obj = $this->installer->{$func}( 'check', $row['indexes'] );
			$files[ $func ]['rtn'] = $this->inject_process( 'check', $obj->file, $obj->vars, $row['indexes'] );
			if ( ( $files[ $func ]['rtn'] instanceof Exception ) ) {
				return $files[ $func ]['rtn'];
			}
		}

		// find uninstalled.
		$uninstalled = array();
		foreach ( $this->store_file as $k => $row ) {
			if ( ! empty( $files[ $row['func'] ]['rtn'][ $row['index'] ] ) ) {
				$uninstalled[ $row['func'] ]['indexes'][] = $row['index'];
			}
		}

		// install uninstalled.
		foreach ( $uninstalled as $func => $row ) {
			$obj = $this->installer->{$func}( 'inject', $row['indexes'] );
			$uninstalled[ $func ]['rtn'] = $this->inject_process( 'inject', $obj->file, $obj->vars, $row['indexes'] );
			if ( ( $uninstalled[ $func ]['rtn'] instanceof Exception ) ) {
				return $uninstalled[ $func ]['rtn'];
			}
		}

		// check for errors.
		foreach ( $this->store_file as $k => $row ) {
			if ( ! empty( $uninstalled[ $row['func'] ]['rtn'] ) ) {
				$errors[] = $row['name'] . ': ' . $files[ $row['func'] ]['rtn'];
			}
		}

		foreach ( $cids as $file ) {
			if ( isset( $this->store_sql[ $file ] ) ) {
				$ret = $this->installer->{$this->store_sql[ $file ]['func']}( 'install' );
				if ( ! $ret ) {
					// translators: %s: query name.
					$errors[] = sprintf( AC()->lang->__( 'Unable to install database query %s' ), $this->store_sql[ $file ]['name'] );
				}
			}
		}

		if ( ! empty( $errors ) ) {
			return new Exception( implode( '<br />', $errors ) );
		}

		return true;
	}

	/**
	 * Uninstall selected items
	 *
	 * @param object $cids the object.
	 */
	public function uninstall( $cids ) {
		if ( empty( $cids ) ) {
			return true;
		}

		$errors = array();

		foreach ( $cids as $file ) {
			if ( isset( $this->global_plugin[ $file ] ) ) {
				$ret = $this->global_installer->{$this->global_plugin[ $file ]['func']}( 'uninstall', $file );
				if ( ! $ret ) {
					// translators: %s: plugin name.
					$errors[] = sprintf( AC()->lang->__( 'Unable to uninstall plugin %s' ), $this->global_plugin[ $file ]['name'] );
				}
			}
		}

		foreach ( $cids as $file ) {
			if ( isset( $this->store_plugin[ $file ] ) ) {
				$ret = $this->installer->{$this->store_plugin[ $file ]['func']}( 'uninstall', $file );
				if ( ! $ret ) {
					// translators: %s: plugin name.
					$errors[] = sprintf( AC()->lang->__( 'Unable to uninstall plugin %s' ), $this->store_plugin[ $file ]['name'] );
				}
			}
		}

		$files = array();
		foreach ( $cids as $file ) {
			if ( isset( $this->store_file[ $file ] ) ) {
				$files[ $this->store_file[ $file ]['func'] ]['indexes'][] = $this->store_file[ $file ]['index'];
			}
		}
		foreach ( $files as $func => $row ) {
			$obj = $this->installer->{$func}( 'reject', $row['indexes'] );
			$files[ $func ]['rtn'] = $this->inject_process( 'reject', $obj->file, $obj->vars, $row['indexes'] );
			if ( ( $files[ $func ]['rtn'] instanceof Exception ) ) {
				return $files[ $func ]['rtn'];
			}
		}

		foreach ( $cids as $file ) {
			if ( isset( $this->store_file[ $file ] ) ) {
				if ( ! empty( $files[ $this->store_file[ $file ]['func'] ]['rtn'] ) ) {
					$errors[] = $this->store_file[ $file ]['name'] . ': ' . $files[ $this->store_file[ $file ]['func'] ]['rtn'];
				}
			}
		}

		foreach ( $cids as $file ) {
			if ( isset( $this->store_sql[ $file ] ) ) {
				$ret = $this->installer->{$this->store_sql[ $file ]['func']}( 'uninstall' );
				if ( ! $ret ) {
					// translators: %s: query name.
					$errors[] = sprintf( AC()->lang->__( 'Unable to uninstall database query %s' ), $this->store_sql[ $file ]['name'] );
				}
			}
		}

		if ( ! empty( $errors ) ) {
			return new Exception( implode( '<br />', $errors ) );
		}

		return true;
	}

	/**
	 * Install all of a specific type
	 *
	 * @param string $type the type to install.
	 */
	public function install_all( $type ) {
		$cids = array();
		if ( isset( $this->{'store_' . $type} ) ) {
			$cids = array_keys( $this->{'store_' . $type} );
		}
		return $this->install( $cids );
	}

	/**
	 * Uninstall all
	 */
	public function uninstall_all() {
		$cids = array_merge( array_keys( $this->store_file ), array_keys( $this->store_sql ), array_keys( $this->store_plugin ), array_keys( $this->global_plugin ) );
		return $this->uninstall( $cids );
	}

	/**
	 * File injection processor
	 *
	 * @param string  $type inject/reject/check.
	 * @param string  $file the file to inject/reject/check.
	 * @param array   $vars array of defined injections.
	 * @param array   $indexes items within the file to check.
	 * @param boolean $is_backup make packup file if true.
	 * @param string  $backup_path the file to backup to.
	 */
	public function inject_process( $type, $file, $vars, $indexes, $is_backup = false, $backup_path = '' ) {
		if ( empty( $type ) || ! in_array( $type, array( 'inject', 'reject', 'check' ), true ) ) {
			return new Exception( AC()->lang->__( 'Invalid file type' ) );
		}
		if ( ! file_exists( $file ) || ! is_writable( $file ) ) {
			return new Exception( AC()->lang->__( 'File does not exist or is not writeable' ) );
		}

		$content = file_get_contents( $file );
		$patterns = array();
		$replacements = array();
		foreach ( $indexes as $index ) {
			if ( ! empty( $vars['patterns'][ $index ] ) && isset( $vars['replacements'][ $index ] ) ) {
				$patterns[] = $vars['patterns'][ $index ];
				$replacements[] = $vars['replacements'][ $index ];
			}
		}

		if ( 'check' === $type ) {
			$rtn = array_fill_keys( $indexes, -1 );
			if ( empty( $vars['patterntype'] ) || 'regex' === $vars['patterntype'] ) {
				if ( is_array( $vars['patterns'] ) ) {
					foreach ( $vars['patterns'] as $k => $pattern ) {
						if ( in_array( $k, $indexes, true ) ) {
							$rtn[ $k ] = ! preg_match( $pattern, $content ) ? AC()->lang->__( 'A match was not found' ) : '';
						}
					}
				}
			} elseif ( 'str' === $vars['patterntype'] ) {
				if ( is_array( $vars['patterns'] ) ) {
					foreach ( $vars['patterns'] as $pattern ) {
						if ( in_array( $k, $indexes, true ) ) {
							$rtn[ $k ] = strpos( $content, $pattern ) === false ? AC()->lang->__( 'A match was not found' ) : '';
						}
					}
				}
			} else {
				return new Exception( AC()->lang->__( 'Invalid pattern type' ) );
			}

			return $rtn;
		} else {
			if ( ! empty( $patterns ) ) {
				if ( $is_backup ) {
					$file_bak = ! empty( $backup_path ) ? $backup_path : substr( $file, 0, -4 ) . '.cmbak.php';
					copy( $file , $file_bak );
				}
				$count = 0;
				$countcheck = count( $patterns );
				if ( empty( $vars['patterntype'] ) || 'regex' === $vars['patterntype'] ) {
					$new_content = preg_replace( $patterns, $replacements, $content, -1, $count );
				} elseif ( 'str' === $vars['patterntype'] ) {
					$new_content = str_replace( $patterns, $replacements, $content, $count );
				} else {
					return new Exception( AC()->lang->__( 'Invalid pattern type' ) );
				}
				if ( $count < $countcheck ) {
					return AC()->lang->__( 'Could not find position to add/delete content' );
				}

				$handle = fopen( $file, 'w' );
				if ( ! $handle ) {
					return AC()->lang->__( 'Could not open file for writing' );
				}

				// Write $somecontent to our opened file.
				if ( fwrite( $handle, $new_content ) === false ) {
					return AC()->lang->__( 'Could not write to file' );
				}

				fclose( $handle );
			}
		}
	}

}

