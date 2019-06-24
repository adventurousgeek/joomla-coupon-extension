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
class CmCoupon_Library_Update {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->is_debug = false;
		$this->debug_file = 'cmcoupon_install.xml';
	}

	/**
	 * Initialize
	 **/
	public function init() {
		$this->upgrade_sql_folder = CMCOUPON_DIR . '/cmcoupon/library/install/upgrade/sql';
		$this->upgrade_php_folder = CMCOUPON_DIR . '/cmcoupon/library/install/upgrade/php';
	}
	/**
	 * Install updates
	 *
	 * @param string $current_version version.
	 * @param string $new_version version.
	 **/
	protected function install_tableupdates( $current_version, $new_version ) {

		if ( version_compare( $current_version, $new_version ) !== -1 ) {
			return;
		}

		AC()->helper->reset_cache();

		$upgrade_files = array();
		$handle = opendir( $this->upgrade_sql_folder );
		if ( $handle ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( '.' !== $file && '..' !== $file ) {
					$upgrade_files[] = str_replace( '.sql', '', $file );
				}
			}
			closedir( $handle );
		}
		if ( empty( $upgrade_files ) ) {
			$this->update_db_version( $new_version );
			// translators: %s: version.
			AC()->helper->set_message( sprintf( AC()->lang->__( 'Updated Database to %s' ), $new_version ) );
			return;
		}
		natcasesort( $upgrade_files );

		$needed_upgrade_files = array();
		foreach ( $upgrade_files as $version ) {
			if ( version_compare( $version, $current_version ) === 1 && version_compare( $new_version, $version ) !== -1 ) {
				$needed_upgrade_files[] = $version;
			}
		}
		if ( empty( $needed_upgrade_files ) ) {
			$this->update_db_version( $new_version );
			// translators: %s: version.
			AC()->helper->set_message( sprintf( AC()->lang->__( 'Updated Database to %s' ), $new_version ) );
			return;
		}

		$sql_content_version = array();
		foreach ( $needed_upgrade_files as $version ) {
			$file = $this->upgrade_sql_folder . '/' . $version . '.sql';
			if ( file_exists( $file ) ) {
				$sql_content = file_get_contents( $file );
				if ( $sql_content ) {
					$sql_content = trim( preg_replace( '/\/\*.*?\@copyright.*?\*\*\//is', '', $sql_content ) );
					$sql_content = preg_split( "/;\s*[\r\n]+/", $sql_content );
					$sql_content_version[ $version ] = $sql_content;
				}
			}
		}

		foreach ( $sql_content_version as $version => $sql_content ) {
			foreach ( $sql_content as $query ) {
				$query = trim( $query );
				if ( empty( $query ) ) {
					continue;
				}

				if ( strpos( $query, '/* PHP:' ) === false ) {
					$this->log_query( $query, $version );
				} else {
					/* If php code have to be executed */

					/* Parsing php code */
					$pos = strpos( $query, '/* PHP:' ) + strlen( '/* PHP:' );
					$php_string = substr( $query, $pos, strlen( $query ) - $pos - strlen( ' */;' ) );
					$php = explode( '::', $php_string );
					preg_match( '/\((.*)\)/', $php_string, $pattern );
					$params_string = trim( $pattern[0], '()' );
					preg_match_all( '/([^,]+),? ?/', $params_string, $parameters );
					if ( isset( $parameters[1] ) ) {
						$parameters = $parameters[1];
					} else {
						$parameters = array();
					}
					if ( is_array( $parameters ) ) {
						foreach ( $parameters as &$parameter ) {
							$parameter = str_replace( '\'', '', $parameter );
						}
					}

					if ( strpos( $php_string, '::' ) === false ) {
						/* Call a simple function */
						$func_name = trim( str_replace( $pattern[0], '', $php[0] ),' ;' );
						$file_name = str_replace( '_', '-', strtolower( $func_name ) );
						require_once $this->upgrade_php_folder . '/' . $file_name . '.php';
						$php_res = call_user_func_array( $func_name, $parameters );
					} else {
						/* Or an object method */
						$func_name = array( $php[0], str_replace( $pattern[0], '', $php[1] ) );
						$php_res = call_user_func_array( $func_name, $parameters );
					}

					if ( ( is_array( $php_res ) && ! empty( $php_res['error'] ) ) || false === $php_res ) {
						$this->log( '
							<request result="fail" sqlfile="' . $version . '">
								<sqlQuery><![CDATA[' . htmlentities( $query ) . ']]></sqlQuery>
								<phpMsgError><![CDATA[' . ( empty( $php_res['msg'] ) ? '' : $php_res['msg'] ) . ']]></phpMsgError>
								<phpNumberError><![CDATA[' . ( empty( $php_res['error'] ) ? '' : $php_res['error'] ) . ']]></phpNumberError>
							</request>' . "\n" );
					} else {
						$this->log( '
							<request result="ok" sqlfile="' . $version . '">
								<sqlQuery><![CDATA[' . htmlentities( $query ) . ']]></sqlQuery>
							</request>' . "\n" );
					}
				}
			}
			$this->update_db_version( $version );
			// translators: %s: version.
			AC()->helper->set_message( sprintf( AC()->lang->__( 'Updated Database to %s' ), $version ) );
		}

		if ( ! empty( $sql_content_version ) && $version !== $new_version ) {
			$this->update_db_version( $new_version );
			// translators: %s: version.
			AC()->helper->set_message( sprintf( AC()->lang->__( 'Updated Database to %s' ), $new_version ) );
		}
	}

	/**
	 * Execute the sql file
	 *
	 * @param string $sqlfile filename.
	 * @param string $version version.
	 */
	public function run_sql_file( $sqlfile, $version = CMCOUPON_VERSION ) {
		$buffer = file_get_contents( $sqlfile );
		if ( false !== $buffer ) {
			$queries = $this->split_sql( $buffer );
			if ( count( $queries ) !== 0 ) {
				$upgrader = AC()->helper->new_class( 'CmCoupon_Library_Update' );
				foreach ( $queries as $query ) {
					$query = trim( $query );
					if ( '' !== $query && '#' !== $query{0} ) {
						$this->log_query( $query, $version );
					}
				}
			}
		}
		return true;
	}

	/**
	 * Split sql statements
	 *
	 * @param string $sql the statements.
	 */
	protected function split_sql( $sql ) {

		$start = 0;
		$open = false;
		$comment = false;
		$end_string = '';
		$end = strlen( $sql );
		$queries = array();
		$query = '';

		for ( $i = 0; $i < $end; $i++ ) {
			$current = substr( $sql, $i, 1 );
			$current2 = substr( $sql, $i, 2 );
			$current3 = substr( $sql, $i, 3 );
			$len_end_string = strlen( $end_string );
			$test_end = substr( $sql, $i, $len_end_string );

			if ( '"' === $current || "'" === $current || '--' === $current2
				|| ( '/*' === $current2 && '/*!' !== $current3 && '/*+' !== $current3 )
				|| ( '#' === $current && '#__' !== $current3 )
				|| ($comment && $test_end === $end_string ) ) {
				// Check if quoted with previous backslash.
				$n = 2;

				while ( substr( $sql, $i - $n + 1, 1 ) === '\\' && $n < $i ) {
					$n++;
				}

				// Not quoted.
				if ( 0 === ( $n % 2 ) ) {
					if ( $open ) {
						if ( $test_end === $end_string ) {
							if ( $comment ) {
								$comment = false;
								if ( $len_end_string > 1 ) {
									$i += ( $len_end_string - 1 );
									$current = substr( $sql, $i, 1 );
								}
								$start = $i + 1;
							}
							$open = false;
							$end_string = '';
						}
					} else {
						$open = true;
						if ( '--' === $current2 ) {
							$end_string = "\n";
							$comment = true;
						} elseif ( '/*' === $current2 ) {
							$end_string = '*/';
							$comment = true;
						} elseif ( '#' === $current ) {
							$end_string = "\n";
							$comment = true;
						} else {
							$end_string = $current;
						}
						if ( $comment && $start < $i ) {
							$query = $query . substr( $sql, $start, ( $i - $start ) );
						}
					}
				}
			}

			if ( $comment ) {
				$start = $i + 1;
			}

			if ( ( ';' === $current && ! $open ) || ( $end - 1 ) === $i ) {
				if ( $start <= $i ) {
					$query = $query . substr( $sql, $start, ( $i - $start + 1 ) );
				}
				$query = trim( $query );

				if ( $query ) {
					if ( ( ( $end - 1 ) === $i ) && ( ';' !== $current ) ) {
						$query = $query . ';';
					}
					$queries[] = $query;
				}

				$query = '';
				$start = $i + 1;
			}
		}

		return $queries;
	}

	/**
	 * Log query
	 *
	 * @param string $query query.
	 * @param string $version version.
	 **/
	public function log_query( $query, $version ) {
		$run_value = AC()->db->query( $query );
		if ( false === $run_value ) {
			// Upgrade failed
			// mysql error 1060 is Duplicate column name
			// .
			$this->log('
				<request result="fail" sqlfile="' . $version . '" >
					<sqlQuery><![CDATA[' . htmlentities( $query ) . ']]></sqlQuery>
					<sqlMsgError><![CDATA[' . htmlentities( AC()->db->get_errormsg() ) . ']]></sqlMsgError>
				</request>' . "\n");
		} else {
			$this->log( '
				<request result="ok" sqlfile="' . $version . '">
					<sqlQuery><![CDATA[' . htmlentities( $query ) . ']]></sqlQuery>
				</request>' . "\n" );
		}
	}

	public function is_table_exists( $table_name ) {
		$tmp = AC()->db->get_value( 'SHOW TABLES LIKE "' . $table_name . '"' );
		return ! empty( $tmp ) ? true : false;
	}

	/**
	 * Log to file
	 *
	 * @param string $data log.
	 **/
	public function log( $data ) {
		$this->logger[] = $data;
		if ( $this->is_debug ) {
			file_put_contents( CMCOUPON_TEMP_DIR . '/' . $this->debug_file, $data, FILE_APPEND );
		}
	}

	public function on_license_activate() {
	}
}

