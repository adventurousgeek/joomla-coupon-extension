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
class Cmcoupon_Library_Http {

	/**
	 * The transport.
	 *
	 * @var string
	 */
	public static $transport = array();

	/**
	 * Request
	 *
	 * @param string $url url.
	 * @param array  $args options.
	 **/
	public function request( $url, $args = array() ) {
		$defaults = array(
			'method' => 'GET',
			'timeout' => 5,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent' => $_SERVER['HTTP_HOST'] . ' checker',
			'CmCoupon/' . CMCOUPON_VERSION . '; ' . AC()->store->get_home_link(),
			'reject_unsafe_urls' => false,
			'blocking' => true,
			'headers' => array(),
			'cookies' => array(),
			'body' => null,
			'compress' => false,
			'decompress' => true,
			'sslverify' => false,
			'stream' => false,
			'filename' => null,
			'limit_response_size' => null,
		);

		// By default, Head requests do not cause redirections.
		if ( isset( $args['method'] ) && 'HEAD' === $args['method'] ) {
			$defaults['redirection'] = 0;
		}

		$r = array_merge( $defaults, $args );

		$arr_url = @parse_url( $url );
		if ( empty( $url ) || empty( $arr_url['scheme'] ) ) {
			return false;
		}

		// If we are streaming to a file but no filename was given drop it in the WP temp dir
		// and pick its name using the basename of the $url.
		if ( $r['stream'] ) {
			if ( empty( $r['filename'] ) ) {
				$r['filename'] = CMCOUPON_TEMP_DIR . '/' . basename( $url );
			}

			// Force some settings if we are streaming to a file and check for existence and perms of destination directory.
			$r['blocking'] = true;
			if ( ! is_writable( dirname( $r['filename'] ) ) ) {
				return false; // Destination directory for file streaming does not exist or is not writable.
			}
		}

		if ( is_null( $r['headers'] ) ) {
			$r['headers'] = array();
		}

		// Setup arguments.
		$headers = $r['headers'];
		$data = $r['body'];
		$type = $r['method'];
		$options = array(
			'timeout' => $r['timeout'],
			'useragent' => $r['user-agent'],
			'blocking' => $r['blocking'],
		);

		if ( $r['stream'] ) {
			$options['filename'] = $r['filename'];
		}
		if ( empty( $r['redirection'] ) ) {
			$options['follow_redirects'] = false;
		} else {
			$options['redirects'] = $r['redirection'];
		}

		// Use byte limit, if we can.
		if ( isset( $r['limit_response_size'] ) ) {
			$options['max_bytes'] = $r['limit_response_size'];
		}

		// SSL certificate handling.
		if ( ! $r['sslverify'] ) {
			$options['verify'] = false;
			$options['verifyname'] = false;
		} else {
			$options['verify'] = $r['sslcertificates'];
		}

		// All non-GET/HEAD requests should put the arguments in the form body.
		if ( ! empty( $r['data_format'] ) ) {
			$options['data_format'] = $r['data_format'];
		}
		if ( empty( $options['data_format'] ) && 'HEAD' !== $type && 'GET' !== $type ) {
			$options['data_format'] = 'body';
		}

		// Avoid issues where mbstring.func_overload is enabled.
		static $encodings = array();
		static $overloaded = null;

		if ( is_null( $overloaded ) ) {
			$overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );
		}

		if ( false !== $overloaded ) {
			if ( ! $reset ) {
				$encoding = mb_internal_encoding();
				array_push( $encodings, $encoding );
				mb_internal_encoding( 'ISO-8859-1' );
			}
		}

		try {
			$requests_response = $this->execute( $url, $headers, $data, $type, $options );

			$response = array(
				'headers' => $requests_response->headers,
				'body' => $requests_response->body,
				'response' => array(
					'code'    => $requests_response->status_code,
					'message' => $this->get_status_header_desc( $requests_response->status_code ),
				),
				'filename' => $r['filename'],
				'http_response' => $requests_response,
			);

		} catch ( Exception $e ) {
			$response = new Exception( 'http_request_failed: ' . $e->getMessage() );
		}

		if ( is_null( $overloaded ) ) {
			$overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );
		}
		if ( false !== $overloaded ) {
			if ( $reset && $encodings ) {
				$encoding = array_pop( $encodings );
				mb_internal_encoding( $encoding );
			}
		}

		/**
		 * Fires after an HTTP API response is received and before the response is returned.
		 *
		 * @since 2.8.0
		 *
		 * @param array|WP_Error $response HTTP response or WP_Error object.
		 * @param string         $context  Context under which the hook is fired.
		 * @param string         $class    HTTP transport used.
		 * @param array          $r        HTTP request arguments.
		 * @param string         $url      The request URL.
		 */
		if ( ( $response instanceof Exception ) ) {
			return $response;
		}

		if ( ! $r['blocking'] ) {
			return array(
				'headers' => array(),
				'body' => '',
				'response' => array(
					'code' => false,
					'message' => false,
				),
				'filename' => '',
				'http_response' => null,
			);
		}

		return $response;
	}


	/**
	 * Execute
	 *
	 * @param string $url url.
	 * @param array  $headers request headers.
	 * @param array  $data request data.
	 * @param string $type get/post/request.
	 * @param array  $options options.
	 **/
	public function execute( $url, $headers = array(), $data = array(), $type = 'GET', $options = array() ) {
		if ( empty( $options['type'] ) ) {
			$options['type'] = $type;
		}
		$defaults = array(
			'timeout' => 10,
			'connect_timeout' => 10,
			'useragent' => 'php-requests/1.7',
			'protocol_version' => 1.1,
			'redirected' => 0,
			'redirects' => 10,
			'follow_redirects' => true,
			'blocking' => true,
			'type' => 'GET',
			'filename' => false,
			'auth' => false,
			'proxy' => false,
			'cookies' => false,
			'max_bytes' => false,
			'idn' => true,
			'hooks' => null,
			'transport' => null,
			'verifyname' => true,
		);
		$options = array_merge( $defaults, $options );

		$type = strtoupper( $type );
		if ( ! isset( $options['data_format'] ) ) {
			if ( in_array( $type, array( 'HEAD', 'GET', 'DELETE' ), true ) ) {
				$options['data_format'] = 'query';
			} else {
				$options['data_format'] = 'body';
			}
		}

		$need_ssl = (0 === stripos( $url, 'https://' ));
		$capabilities = array(
			'ssl' => $need_ssl,
		);
		$transport = $this->get_transport( $capabilities );

		$response = $transport->request( $url, $headers, $data, $options );

		return $this->parse_response( $response, $url, $headers, $data, $options );
	}

	/**
	 * Get the transport
	 *
	 * @param array $capabilities the data.
	 *
	 * @throws Exception If no valid transport is found.
	 **/
	protected function get_transport( $capabilities = array() ) {
		ksort( $capabilities );
		$cap_string = serialize( $capabilities );

		// Don't search for a transport if it's already been done for these $capabilities.
		if ( isset( self::$transport[ $cap_string ] ) && null !== self::$transport[ $cap_string ] ) {
			return new self::$transport[ $cap_string ]();
		}

		$transports = array(
			'Cmcoupon_Library_Http_Curl',
			'Cmcoupon_Library_Http_Fsockopen',
		);

		// Find us a working transport.
		foreach ( $transports as $classname ) {

			$class = AC()->helper->new_class( $classname );

			$result = $class->test( $capabilities );
			if ( $result ) {
				self::$transport[ $cap_string ] = $class;
				break;
			}
		}
		if ( null === self::$transport[ $cap_string ] ) {
			throw new Exception( 'No working transports found' );
		}

		return new self::$transport[ $cap_string ]();
	}

	/**
	 * Parse response
	 *
	 * @param string $headers headers.
	 * @param string $url url.
	 * @param string $req_headers request headers.
	 * @param string $req_data request data.
	 * @param string $options options.
	 *
	 * @throws Exception If response cannot be parsed.
	 **/
	protected function parse_response( $headers, $url, $req_headers, $req_data, $options ) {
		$return = new stdClass();
		if ( ! $options['blocking'] ) {
			return $return;
		}

		$return->raw = $headers;
		$return->url = $url;

		if ( ! $options['filename'] ) {
			$pos = strpos( $headers, "\r\n\r\n" );
			if ( false === $pos ) {
				// Crap!
				throw new Exception( 'Missing header/body separator' );
			}

			$headers = substr( $return->raw, 0, $pos );
			$return->body = substr( $return->raw, $pos + strlen( "\n\r\n\r" ) );
		} else {
			$return->body = '';
		}
		// Pretend CRLF = LF for compatibility (RFC 2616, section 19.3).
		$headers = str_replace( "\r\n", "\n", $headers );
		// Unfold headers (replace [CRLF] 1*( SP | HT ) with SP) as per RFC 2616 (section 2.2).
		$headers = preg_replace( '/\n[ \t]/', ' ', $headers );
		$headers = explode( "\n", $headers );
		preg_match( '#^HTTP/(1\.\d)[ \t]+(\d+)#i', array_shift( $headers ), $matches );
		if ( empty( $matches ) ) {
			throw new Exception( 'Response could not be parsed' );
		}
		$return->protocol_version = (float) $matches[1];
		$return->status_code = (int) $matches[2];
		if ( $return->status_code >= 200 && $return->status_code < 300 ) {
			$return->success = true;
		}

		foreach ( $headers as $header ) {
			list($key, $value) = explode( ':', $header, 2 );
			$value = trim( $value );
			preg_replace( '#(\s+)#i', ' ', $value );
			$return->headers[ $key ] = $value;
		}
		if ( isset( $return->headers['transfer-encoding'] ) || isset( $return->headers['Transfer-Encoding'] ) ) {
			$return->body = $this->decode_chunked( $return->body );
			unset( $return->headers['transfer-encoding'] );
		}
		if ( isset( $return->headers['content-encoding'] ) ) {
			$return->body = $this->decompress( $return->body );
		}

		// fsockopen and cURL compatibility.
		if ( isset( $return->headers['connection'] ) ) {
			unset( $return->headers['connection'] );
		}

		if ( ( in_array( (int) $return->status_code, array( 300, 301, 302, 303, 307 ), true ) || $return->status_code > 307 && $return->status_code < 400 ) && true === $options['follow_redirects'] ) {
			if ( isset( $return->headers['location'] ) && $options['redirected'] < $options['redirects'] ) {
				if ( 303 === (int) $return->status_code ) {
					$options['type'] = 'GET';
				}
				$options['redirected']++;
				$location = $return->headers['location'];

				$hook_args = array(
					&$location,
					&$req_headers,
					&$req_data,
					&$options,
					$return,
				);
				$options['hooks']->dispatch( 'requests.before_redirect', $hook_args );
				$redirected = $this->execute( $location, $req_headers, $req_data, $options['type'], $options );
				$redirected->history[] = $return;
				return $redirected;
			} elseif ( $options['redirected'] >= $options['redirects'] ) {
				throw new Exception( 'Too many redirects' );
			}
		}

		$return->redirects = $options['redirected'];

		return $return;
	}

	/**
	 * Decode chuncked
	 *
	 * @param string $data data.
	 **/
	protected function decode_chunked( $data ) {
		if ( ! preg_match( '/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i', trim( $data ) ) ) {
			return $data;
		}

		$decoded = '';
		$encoded = $data;

		while ( true ) {
			$is_chunked = (bool) preg_match( '/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i', $encoded, $matches );
			if ( ! $is_chunked ) {
				// Looks like it's not chunked after all.
				return $data;
			}

			$length = hexdec( trim( $matches[1] ) );
			if ( 0 === $length ) {
				// Ignore trailer headers.
				return $decoded;
			}

			$chunk_length = strlen( $matches[0] );
			$decoded .= substr( $encoded, $chunk_length, $length );
			$encoded = substr( $encoded, $chunk_length + $length + 2 );

			if ( trim( $encoded ) === '0' || empty( $encoded ) ) {
				return $decoded;
			}
		}
	}

	/**
	 * Decompress
	 *
	 * @param mixed $data data.
	 **/
	public function decompress( $data ) {
		if ( substr( $data, 0, 2 ) !== "\x1f\x8b" && substr( $data, 0, 2 ) !== "\x78\x9c" ) {
			// Not actually compressed. Probably cURL ruining this for us.
			return $data;
		}

		if ( function_exists( 'gzdecode' ) ) {
			$decoded = @gzdecode( $data );
			if ( false !== $decoded ) {
				return $decoded;
			}
		}
		if ( function_exists( 'gzinflate' ) ) {
			$decoded = @gzinflate( $data );
			if ( false !== $decoded ) {
				return $decoded;
			}
		}
		$decoded = self::compatible_gzinflate( $data );
		if ( false !== $decoded ) {
			return $decoded;
		}
		if ( function_exists( 'gzuncompress' ) ) {
			$decoded = @gzuncompress( $data );
			if ( false !== $decoded ) {
				return $decoded;
			}
		}

		return $data;
	}

	/**
	 * Get gzinflate
	 *
	 * @param string $gz_data data.
	 **/
	public function compatible_gzinflate( $gz_data ) {
		// Compressed data might contain a full zlib header, if so strip it for
		// gzinflate().
		if ( substr( $gz_data, 0, 3 ) == "\x1f\x8b\x08" ) {
			$i = 10;
			$flg = ord( substr( $gz_data, 3, 1 ) );
			if ( $flg > 0 ) {
				if ( $flg & 4 ) {
					list($xlen) = unpack( 'v', substr( $gz_data, $i, 2 ) );
					$i = $i + 2 + $xlen;
				}
				if ( $flg & 8 ) {
					$i = strpos( $gz_data, "\0", $i ) + 1;
				}
				if ( $flg & 16 ) {
					$i = strpos( $gz_data, "\0", $i ) + 1;
				}
				if ( $flg & 2 ) {
					$i = $i + 2;
				}
			}
			$decompressed = $this->compatible_gzinflate( substr( $gz_data, $i ) );
			if ( false !== $decompressed ) {
				return $decompressed;
			}
		}

		// If the data is Huffman Encoded, we must first strip the leading 2
		// byte Huffman marker for gzinflate()
		// The response is Huffman coded by many compressors such as
		// java.util.zip.Deflater, Ruby’s Zlib::Deflate, and .NET's
		// System.IO.Compression.DeflateStream.
		//
		// See https://decompres.blogspot.com/ for a quick explanation of this
		// data type.
		$huffman_encoded = false;

		// low nibble of first byte should be 0x08.
		list(, $first_nibble)    = unpack( 'h', $gz_data );

		// First 2 bytes should be divisible by 0x1F.
		list(, $first_two_bytes) = unpack( 'n', $gz_data );

		if ( 0x08 == $first_nibble && 0 == ($first_two_bytes % 0x1F) ) {
			$huffman_encoded = true;
		}

		if ( $huffman_encoded ) {
			$decompressed = @gzinflate( substr( $gz_data, 2 ) );
			if ( false !== $decompressed ) {
				return $decompressed;
			}
		}

		if ( "\x50\x4b\x03\x04" == substr( $gz_data, 0, 4 ) ) {
			// ZIP file format header
			// Offset 6: 2 bytes, General-purpose field
			// Offset 26: 2 bytes, filename length
			// Offset 28: 2 bytes, optional field length
			// Offset 30: Filename field, followed by optional field, followed
			// immediately by data.
			list(, $general_purpose_flag) = unpack( 'v', substr( $gz_data, 6, 2 ) );

			// If the file has been compressed on the fly, 0x08 bit is set of
			// the general purpose field. We can use this to differentiate
			// between a compressed document, and a ZIP file.
			$zip_compressed_on_the_fly = (0x08 == (0x08 & $general_purpose_flag));

			if ( ! $zip_compressed_on_the_fly ) {
				// Don't attempt to decode a compressed zip file.
				return $gz_data;
			}

			// Determine the first byte of data, based on the above ZIP header
			// offsets.
			$first_file_start = array_sum( unpack( 'v2', substr( $gz_data, 26, 4 ) ) );
			$decompressed = @gzinflate( substr( $gz_data, 30 + $first_file_start ) );
			if ( false !== $decompressed ) {
				return $decompressed;
			}
			return false;
		}

		// Finally fall back to straight gzinflate.
		$decompressed = @gzinflate( $gz_data );
		if ( false !== $decompressed ) {
			return $decompressed;
		}

		// Fallback for all above failing, not expected, but included for
		// debugging and preventing regressions and to track stats.
		$decompressed = @gzinflate( substr( $gz_data, 2 ) );
		if ( false !== $decompressed ) {
			return $decompressed;
		}

		return false;
	}

	/**
	 * Get status header description
	 *
	 * @param int $code the code to check.
	 **/
	public function get_status_header_desc( $code ) {

		$wp_header_to_desc = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',

			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			226 => 'IM Used',

			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Reserved',
			307 => 'Temporary Redirect',
			308 => 'Permanent Redirect',

			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'I\'m a teapot',
			421 => 'Misdirected Request',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			424 => 'Failed Dependency',
			426 => 'Upgrade Required',
			428 => 'Precondition Required',
			429 => 'Too Many Requests',
			431 => 'Request Header Fields Too Large',
			451 => 'Unavailable For Legal Reasons',

			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage',
			510 => 'Not Extended',
			511 => 'Network Authentication Required',
		);

		if ( isset( $wp_header_to_desc[ $code ] ) ) {
			return $wp_header_to_desc[ $code ];
		} else {
			return '';
		}
	}
}
