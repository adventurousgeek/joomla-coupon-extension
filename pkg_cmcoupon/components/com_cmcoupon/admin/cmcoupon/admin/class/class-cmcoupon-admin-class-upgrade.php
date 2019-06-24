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
 **/
class CmCoupon_Admin_Class_Upgrade extends CmCoupon_Library_Class {


	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'upgrade';
		parent::__construct();
	}

	/**
	 * Download
	 *
	 * @param string $url to download.
	 **/
	public function download( $url ) {

		$filename = basename( parse_url( $url, PHP_URL_PATH ) );
		$ext = pathinfo( $filename, PATHINFO_EXTENSION );
		$name = pathinfo( $filename, PATHINFO_BASENAME );
		$dir = CMCOUPON_TEMP_DIR;

		if ( empty( $filename ) || '.' === $filename || '/' === $filename || '\\' === $filename ) {
			$filename = time();
		}

		// Use the basename of the given file without the extension as the name for the temporary directory.
		$temp_filename = basename( $filename );
		$temp_filename = preg_replace( '|\.[^.]*$|', '', $temp_filename );

		// Suffix some random data to avoid filename conflicts.
		$temp_filename .= '-' . AC()->coupon->generate_coupon_code();
		$temp_filename .= '.tmp';
		$number = '';
		while ( file_exists( $dir . '/' . $temp_filename ) ) {
			$new_number = (int) $number + 1;
			if ( '' === "$number$ext" ) {
				$temp_filename = "$temp_filename-" . $new_number;
			} else {
				$temp_filename = str_replace( array( "-$number$ext", "$number$ext" ), '-' . $new_number . $ext, $temp_filename );
			}
			$number = $new_number;
		}
		$temp_filename = $dir . '/' . $temp_filename;

		$fp = fopen( $temp_filename, 'x' );
		if ( ! file_exists( $temp_filename ) ) {
			return false;
		}
		if ( ! $fp && is_writable( $dir ) ) {
			return false;
		}
		if ( $fp ) {
			fclose( $fp );
		}

		$args = array(
			'timeout' => 300,
			'stream' => true,
			'filename' => $temp_filename,
			'method' => 'GET',
		);

		$http_class = AC()->helper->new_class( 'Cmcoupon_Helper_Http' );
		$response = $http_class->request( $url, $args );

		if ( ( $response instanceof Exception ) ) {
			unlink( $temp_filename );
			return $response;
		}

		return $temp_filename;
	}

	/**
	 * Extract
	 *
	 * @param string $package to extract.
	 **/
	public function extract( $package ) {

		$upgrade_folder = CMCOUPON_TEMP_DIR . '/';

		// We need a working directory - Strip off any .tmp or .zip suffixes.
		$working_dir = $upgrade_folder . basename( basename( $package, '.tmp' ), '.zip' );

		// Clean up working directory.
		if ( @ is_dir( $working_dir ) ) {
			$this->delete_directory( $working_dir );
		}

		// Unzip package to working directory.
		$result = $this->unzip_file( $package, $working_dir );

		// Once extracted, delete the package if required.
		unlink( $package );

		if ( ( $result instanceof Exception ) ) {
			$this->delete_directory( $working_dir );
			return $result;
		}

		return $working_dir;
	}

	/**
	 * Copy
	 *
	 * @param string $source link to copy.
	 **/
	public function copy( $source ) {

		$working_dir = $source;
		$dh = opendir( $working_dir );
		$i = 0;
		$last_file = '';
		while ( ( $file = readdir( $dh ) ) !== false ) {
			if ( in_array( $file, array( '.', '..' ), true ) ) {
				continue;
			}
			$i++;
			if ( @ is_dir( $working_dir . '/' . $file ) ) {
				$last_file = $file;
			}
		}
		if ( 1 === $i && ! empty( $last_file ) ) {
			$working_dir = $working_dir . '/' . $last_file;
		}

		$installer = AC()->helper->new_class( 'CmCoupon_Helper_Update' );
		if ( method_exists( $installer, 'upgrade_function_copy' ) ) {
			try {
				$installer->upgrade_function_copy( $working_dir );
			} catch ( Exception $e ) {
				$this->delete_directory( $source );
				return $e;
			}
		}
		else {
			// Copy files.
			try {
				$this->copy_recursive( $working_dir, CMCOUPON_DIR );
			} catch ( Exception $e ) {
				$this->delete_directory( $source );
				return $e;
			}
		}

		$this->delete_directory( $source );
		return true;
	}

	/**
	 * Run the database updates
	 **/
	public function dbupdate() {
		$installer = AC()->helper->new_class( 'CmCoupon_Helper_Update' );
		if ( method_exists( $installer, 'upgrade_function_dbupdate' ) ) {
			return $installer->upgrade_function_dbupdate();
		}

		return $installer->update();
	}

	/**
	 * Recursive delete directory
	 *
	 * @param string $dir directory to delete.
	 **/
	public function delete_directory( $dir ) {
		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			if ( is_dir( $dir . '/' . $file ) ) {
				$this->delete_directory( $dir . '/' . $file );
			} else {
				unlink( $dir . '/' . $file );
			}
		}
		return rmdir( $dir );
	}

	/**
	 * Create a file
	 *
	 * @param string $file to download.
	 * @param mixed  $buffer the data to write.
	 * @param string $type either directory or file.
	 **/
	public function write_file( $file, $buffer, $type = 'file' ) {
		@set_time_limit( ini_get( 'max_execution_time' ) );

		$perms = ( fileperms( dirname( __FILE__ ) ) & 0777 | 0755 );

		if ( 'directory' === $type ) {
			if ( @ is_dir( $file ) ) {
				return true;
			}
			if ( ! @ mkdir( $file, $perms ) && ! @ is_dir( $file ) ) {
				return false;
			}
			return true;
		}

		// If the destination directory doesn't exist we need to create it.
		if ( ! file_exists( dirname( $file ) ) ) {
			$dir = dirname( $file );
			if ( ! @ mkdir( $dir, $perm, true ) && ! @ is_dir( $dir ) ) {
				return false;
			}
		}

		$ret = is_int( file_put_contents( $file, $buffer ) ) ? true : false;
		return $ret;
	}

	/**
	 * Copy directory recursively
	 *
	 * @param string $src the source file/directory.
	 * @param string $dest the destination file/directory.
	 **/
	public function copy_recursive( $src, $dest ) {

		@set_time_limit( ini_get( 'max_execution_time' ) );

		// Eliminate trailing directory separators, if any.
		$src = rtrim( $src, '/' );
		$dest = rtrim( $dest, '/' );

		if ( ! @ is_dir( $src ) ) {
			throw new Exception( AC()->lang->__( 'Source folder not found' ) );
		}

		$dh = @opendir( $src );
		if ( false === $dh ) {
			throw new Exception( AC()->lang->__( 'Cannot open source folder' ) );
		}

		// Walk through the directory copying files and recursing into folders.
		while ( ( $file = readdir( $dh ) ) !== false ) {
			$file = urlencode( $file );
			$sfid = $src . '/' . $file;
			$dfid = $dest . '/' . $file;

			switch ( filetype( $sfid ) ) {
				case 'dir':
					if ( '.' !== $file && '..' !== $file ) {
						if ( ! $this->write_file( $dfid, null, 'directory' ) ) {
							closedir( $dh );
							// translators: %s directory path.
							throw new Exception( sprintf( AC()->lang->__( 'Could not create directory: %s' ), $dfid ) );
						}
						$ret = $this->copy_recursive( $sfid, $dfid );
						if ( true !== $ret ) {
							return $ret;
						}
					}
					break;

				case 'file':
					if ( ! @copy( $sfid, $dfid ) ) {
						closedir( $dh );
						// translators: %1$s file path source %2$s file path destination.
						throw new Exception( sprintf( AC()->lang->__( 'Copy file failed: %1$s - %2$s' ), $sfid, $dfid ) );
					}
					break;
			}
		}
		closedir( $dh );

		return true;
	}

	/**
	 * Extract file
	 *
	 * @param string $package filename of the zipped archive.
	 * @param string $working_dir location to unzip to.
	 **/
	public function unzip_file( $package, $working_dir ) {

		$working_dir = rtrim( $working_dir, '/\\' ) . '/';

		// METHOD 1.
		if ( class_exists( 'ZipArchive', false ) ) {
			$zip = new ZipArchive();
			$res = $zip->open( $package );
			if ( true === $res ) {
				$zip->extractTo( $working_dir );
				$zip->close();
			} else {
				return new Exception( AC()->lang->__( 'Unable to open archive' ) );
			}
		}

		// METHOD 2.
		if ( function_exists( 'zip_open' ) && function_exists( 'zip_read' ) ) {
			$zip = zip_open( $package );
			if ( ! is_resource( $zip ) ) {
				return new Exception( AC()->lang->__( 'Unable to open archive' ) );
			}

			// Make sure the destination folder exists.
			if ( ! $this->write_file( $working_dir, null, 'directory' ) ) {
				// translators: %s directory path.
				return new Exception( sprintf( AC()->lang->__( 'Could not create directory: %s' ), $working_dir ) );
			}

			// Read files in the archive.
			while ( $file = @zip_read( $zip ) ) {
				if ( ! zip_entry_open( $zip, $file, 'r' ) ) {
					return new Exception( AC()->lang->__( 'Unable to read entry' ) );
				}

				if ( substr( zip_entry_name( $file ), strlen( zip_entry_name( $file ) ) - 1 ) !== '/' ) {
					$buffer = zip_entry_read( $file, zip_entry_filesize( $file ) );

					if ( $this->write_file( $working_dir . zip_entry_name( $file ), $buffer ) === false ) {
						return new Exception( AC()->lang->__( 'Unable to write entry' ) );
					}
					zip_entry_close( $file );
				} else {
					if ( ! $this->write_file( $working_dir . zip_entry_name( $file ), null, 'directory' ) ) {
						return new Exception( AC()->lang->__( 'Unable to write entry' ) );
					}
				}
			}
			@zip_close( $zip );

			return $working_dir;

		}

		// METHOD 3.
		if ( extension_loaded( 'zlib' ) ) {
			$data = file_get_contents( $package );

			if ( ! $data ) {
				return new Exception( AC()->lang->__( 'Unable to read archive (zip)' ) );
			}

			$entries = array();
			// Find the last central directory header entry.
			$fh_last = strpos( $data, "\x50\x4b\x05\x06\x00\x00\x00\x00" );

			do {
				$last = $fh_last;
			} while ( ( $fh_last = strpos( $data, "\x50\x4b\x05\x06\x00\x00\x00\x00", $fh_last + 1 ) ) !== false );

			// Find the central directory offset.
			$offset = 0;

			if ( $last ) {
				$end_of_central_directory = unpack(
					'vNumberOfDisk/vNoOfDiskWithStartOfCentralDirectory/vNoOfCentralDirectoryEntriesOnDisk/' .
					'vTotalCentralDirectoryEntries/VSizeOfCentralDirectory/VCentralDirectoryOffset/vCommentLength',
					substr( $data, $last + 4 )
				);
				$offset = $end_of_central_directory['CentralDirectoryOffset'];
			}

			// Get details from central directory structure.
			$fh_start = strpos( $data, "\x50\x4b\x01\x02", $offset );
			$data_length = strlen( $data );
			$_methods = array(
				0x0 => 'None',
				0x1 => 'Shrunk',
				0x2 => 'Super Fast',
				0x3 => 'Fast',
				0x4 => 'Normal',
				0x5 => 'Maximum',
				0x6 => 'Imploded',
				0x8 => 'Deflated',
			);
			$_metadata = null;
			do {
				if ( $data_length < $fh_start + 31 ) {
					return new Exception( AC()->lang->__( 'Invalid zip data' ) );
				}

				$info = unpack( 'vMethod/VTime/VCRC32/VCompressed/VUncompressed/vLength', substr( $data, $fh_start + 10, 20 ) );
				$name = substr( $data, $fh_start + 46, $info['Length'] );

				$entries[ $name ] = array(
					'attr' => null,
					'crc' => sprintf( '%08s', dechex( $info['CRC32'] ) ),
					'csize' => $info['Compressed'],
					'date' => null,
					'_dataStart' => null,
					'name' => $name,
					'method' => $_methods[ $info['Method'] ],
					'_method' => $info['Method'],
					'size' => $info['Uncompressed'],
					'type' => null,
				);

				$entries[ $name ]['date'] = mktime(
					( ( $info['Time'] >> 11 ) & 0x1f ),
					( ( $info['Time'] >> 5 ) & 0x3f ),
					( ( $info['Time'] << 1 ) & 0x3e ),
					( ( $info['Time'] >> 21 ) & 0x07 ),
					( ( $info['Time'] >> 16 ) & 0x1f ),
					( ( ( $info['Time'] >> 25 ) & 0x7f ) + 1980 )
				);

				if ( $data_length < $fh_start + 43 ) {
					return new Exception( AC()->lang->__( 'Invalid zip data' ) );
				}

				$info = unpack( 'vInternal/VExternal/VOffset', substr( $data, $fh_start + 36, 10 ) );

				$entries[ $name ]['type'] = ( $info['Internal'] & 0x01 ) ? 'text' : 'binary';
				$entries[ $name ]['attr'] = ( ( $info['External'] & 0x10 ) ? 'D' : '-' ) . ( ( $info['External'] & 0x20 ) ? 'A' : '-' )
					. ( ( $info['External'] & 0x03 ) ? 'S' : '-' ) . ( ( $info['External'] & 0x02 ) ? 'H' : '-' ) . ( ( $info['External'] & 0x01 ) ? 'R' : '-' );
				$entries[ $name ]['offset'] = $info['Offset'];

				// Get details from local file header since we have the offset.
				$lfh_start = strpos( $data, "\x50\x4b\x03\x04", $entries[ $name ]['offset'] );

				if ( $data_length < $lfh_start + 34 ) {
					return new Exception( AC()->lang->__( 'Invalid zip data' ) );
				}

				$info = unpack( 'vMethod/VTime/VCRC32/VCompressed/VUncompressed/vLength/vExtraLength', substr( $data, $lfh_start + 8, 25 ) );
				$name = substr( $data, $lfh_start + 30, $info['Length'] );
				$entries[ $name ]['_dataStart'] = $lfh_start + 30 + $info['Length'] + $info['ExtraLength'];

				// Bump the max execution time because not using the built in php zip libs makes this process slow.
				@set_time_limit( ini_get( 'max_execution_time' ) );
			} while ( ( ( $fh_start = strpos( $data, "\x50\x4b\x01\x02", $fh_start + 46 ) ) !== false ) );

			$_metadata = array_values( $entries );

			$n = count( $_metadata );
			for ( $i = 0; $i < $n; $i++ ) {
				$last_path_character = substr( $_metadata[ $i ]['name'], -1, 1 );

				if ( '/' !== $last_path_character && '\\' !== $last_path_character ) {

					$buffer = '';
					$method = $_metadata[ $i ]['_method'];

					if ( 0x12 === $method && ! extension_loaded( 'bz2' ) ) {
						$buffer = '';
					} else {
						switch ( $method ) {
							case 0x8:
								$buffer = gzinflate( substr( $data, $_metadata[ $i ]['_dataStart'], $_metadata[ $i ]['csize'] ) );
								break;
							case 0x0:
								// Files that aren't compressed.
								$buffer = substr( $data, $_metadata[ $i ]['_dataStart'], $_metadata[ $i ]['csize'] );
								break;
							case 0x12:
								$buffer = bzdecompress( substr( $data, $_metadata[ $i ]['_dataStart'], $_metadata[ $i ]['csize'] ) );
								break;
						}
					}

					$path = $working_dir . '/' . $_metadata[ $i ]['name'];

					if ( false === $this->write_file( $path, $buffer ) ) {
						return new Exception( AC()->lang->__( 'Unable to write entry' ) );
					}
				} elseif ( '/' === $last_path_character ) {
					if ( $this->write_file( $working_dir . '/' . $_metadata[ $i ]['name'], null, 'directory' ) === false ) {
						return new Exception( AC()->lang->__( 'Unable to write entry' ) );
					}
				}
			}

			return $working_dir;
		}

		return new Exception( AC()->lang->__( 'Unzip not supported' ) );
	}

}
