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
 * Class fsockopen HTTP transport
 */
class Cmcoupon_Library_Http_Fsockopen {
	/**
	 * Second to microsecond conversion
	 *
	 * @var integer
	 */
	const SECOND_IN_MICROSECONDS = 1000000;

	/**
	 * Raw HTTP data
	 *
	 * @var string
	 */
	public $headers = '';

	/**
	 * Stream metadata
	 *
	 * @var array Associative array of properties, see {@see https://secure.php.net/stream_get_meta_data}
	 */
	public $info;

	/**
	 * What's the maximum number of bytes we should keep?
	 *
	 * @var int|bool Byte count, or false if no limit.
	 */
	protected $max_bytes = false;

	/**
	 * Connection error
	 *
	 * @var string.
	 */
	protected $connect_error = '';

	/**
	 * Perform a request
	 *
	 * @throws Exception On failure to connect to socket (`fsockopenerror`).
	 * @throws Exception On socket timeout (`timeout`).
	 *
	 * @param string       $url URL to request.
	 * @param array        $headers Associative array of request headers.
	 * @param string|array $data Data to send either as the POST body, or as parameters in the URL for a GET/HEAD.
	 * @param array        $options Request options, see {@see Requests::response()} for documentation.
	 * @return string Raw HTTP result
	 */
	public function request( $url, $headers = array(), $data = array(), $options = array() ) {

		$url_parts = parse_url( $url );
		if ( empty( $url_parts ) ) {
			throw new Exception( 'Invalid URL: ' . $url );
		}
		$host = $url_parts['host'];
		$context = stream_context_create();
		$verifyname = false;

		$case_insensitive_header = $headers;

		// HTTPS support.
		if ( isset( $url_parts['scheme'] ) && strtolower( $url_parts['scheme'] ) === 'https' ) {
			$remote_socket = 'ssl://' . $host;
			if ( ! isset( $url_parts['port'] ) ) {
				$url_parts['port'] = 443;
			}

			$context_options = array(
				'verify_peer' => true,
				'capture_peer_cert' => true,
			);
			$verifyname = true;

			// SNI, if enabled (OpenSSL >=0.9.8j).
			if ( defined( 'OPENSSL_TLSEXT_SERVER_NAME' ) && OPENSSL_TLSEXT_SERVER_NAME ) {
				$context_options['SNI_enabled'] = true;
				if ( isset( $options['verifyname'] ) && false === $options['verifyname'] ) {
					$context_options['SNI_enabled'] = false;
				}
			}

			if ( isset( $options['verify'] ) ) {
				if ( false === $options['verify'] ) {
					$context_options['verify_peer'] = false;
				} elseif ( is_string( $options['verify'] ) ) {
					$context_options['cafile'] = $options['verify'];
				}
			}

			if ( isset( $options['verifyname'] ) && false === $options['verifyname'] ) {
				$context_options['verify_peer_name'] = false;
				$verifyname = false;
			}

			stream_context_set_option(
				$context, array(
					'ssl' => $context_options,
				)
			);
		} else {
			$remote_socket = 'tcp://' . $host;
		}

		$this->max_bytes = $options['max_bytes'];

		if ( ! isset( $url_parts['port'] ) ) {
			$url_parts['port'] = 80;
		}
		$remote_socket .= ':' . $url_parts['port'];

		set_error_handler( array( $this, 'connect_error_handler' ), E_WARNING | E_NOTICE );

		$socket = stream_socket_client( $remote_socket, $errno, $errstr, ceil( $options['connect_timeout'] ), STREAM_CLIENT_CONNECT, $context );

		restore_error_handler();

		if ( $verifyname && ! $this->verify_certificate_from_context( $host, $context ) ) {
			throw new Exception( 'SSL certificate did not match the requested domain name' );
		}

		if ( ! $socket ) {
			if ( 0 === $errno ) {
				// Connection issue.
				throw new Exception( rtrim( $this->connect_error ) );
			}

			throw new Exception( $errstr . ', ' . $errno );
		}

		$data_format = $options['data_format'];

		if ( 'query' === $data_format ) {
			$path = self::format_get( $url_parts, $data );
			$data = '';
		} else {
			$path = self::format_get( $url_parts, array() );
		}

		$request_body = '';
		$out = sprintf( "%s %s HTTP/%.1f\r\n", $options['type'], $path, $options['protocol_version'] );

		if ( 'TRACE' !== $options['type'] ) {
			if ( is_array( $data ) ) {
				$request_body = http_build_query( $data, null, '&' );
			} else {
				$request_body = $data;
			}

			if ( ! empty( $data ) ) {
				if ( ! isset( $case_insensitive_headers['Content-Length'] ) ) {
					$headers['Content-Length'] = strlen( $request_body );
				}

				if ( ! isset( $case_insensitive_headers['Content-Type'] ) ) {
					$headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
				}
			}
		}

		if ( ! isset( $case_insensitive_headers['Host'] ) ) {
			$out .= sprintf( 'Host: %s', $url_parts['host'] );

			if ( ( 'http' === strtolower( $url_parts['scheme'] ) && 80 !== $url_parts['port'] ) || ( 'https' === strtolower( $url_parts['scheme'] ) && 443 !== $url_parts['port'] ) ) {
				$out .= ':' . $url_parts['port'];
			}
			$out .= "\r\n";
		}

		if ( ! isset( $case_insensitive_headers['User-Agent'] ) ) {
			$out .= sprintf( "User-Agent: %s\r\n", $options['useragent'] );
		}

		if ( ! empty( $options['filename'] ) ) {
			$accept_encoding = $this->accept_encoding();
			if ( ! isset( $case_insensitive_headers['Accept-Encoding'] ) && ! empty( $accept_encoding ) ) {
				$out .= sprintf( "Accept-Encoding: %s\r\n", $accept_encoding );
			}
		}

		$return = array();
		foreach ( $headers as $key => $value ) {
			$return[] = sprintf( '%s: %s', $key, $value );
		}
		$headers = $return;

		if ( ! empty( $headers ) ) {
			$out .= implode( $headers, "\r\n" ) . "\r\n";
		}

		if ( substr( $out, -2 ) !== "\r\n" ) {
			$out .= "\r\n";
		}

		if ( ! isset( $case_insensitive_headers['Connection'] ) ) {
			$out .= "Connection: Close\r\n";
		}

		$out .= "\r\n" . $request_body;

		fwrite( $socket, $out );

		if ( ! $options['blocking'] ) {
			fclose( $socket );
			$fake_headers = '';
			return '';
		}

		$timeout_sec = (int) floor( $options['timeout'] );
		if ( $timeout_sec === $options['timeout'] ) {
			$timeout_msec = 0;
		} else {
			$timeout_msec = self::SECOND_IN_MICROSECONDS * $options['timeout'] % self::SECOND_IN_MICROSECONDS;
		}
		stream_set_timeout( $socket, $timeout_sec, $timeout_msec );

		$response = '';
		$body = '';
		$headers = '';
		$this->info = stream_get_meta_data( $socket );
		$size = 0;
		$doingbody = false;
		$download = false;
		if ( $options['filename'] ) {
			$download = fopen( $options['filename'], 'wb' );
		}

		while ( ! feof( $socket ) ) {
			$this->info = stream_get_meta_data( $socket );
			if ( $this->info['timed_out'] ) {
				throw new Exception( 'fsocket timed out' );
			}

			$block = fread( $socket, 1160 );
			if ( ! $doingbody ) {
				$response .= $block;
				if ( strpos( $response, "\r\n\r\n" ) ) {
					list($headers, $block) = explode( "\r\n\r\n", $response, 2 );
					$doingbody = true;
				}
			}

			// Are we in body mode now?
			if ( $doingbody ) {
				$data_length = strlen( $block );
				if ( $this->max_bytes ) {
					// Have we already hit a limit?
					if ( $size === $this->max_bytes ) {
						continue;
					}
					if ( ($size + $data_length) > $this->max_bytes ) {
						// Limit the length.
						$limited_length = ($this->max_bytes - $size);
						$block = substr( $block, 0, $limited_length );
					}
				}

				$size += strlen( $block );
				if ( $download ) {
					fwrite( $download, $block );
				} else {
					$body .= $block;
				}
			}
		}
		$this->headers = $headers;

		if ( $download ) {
			fclose( $download );
		} else {
			$this->headers .= "\r\n\r\n" . $body;
		}
		fclose( $socket );

		return $this->headers;
	}

	/**
	 * Send multiple requests simultaneously
	 *
	 * @param array $requests Request data (array of 'url', 'headers', 'data', 'options') as per {@see Transport::request}.
	 * @param array $options Global options, see {@see Requests::response()} for documentation.
	 * @return array Array of Response objects (may contain Exception or string responses as well)
	 */
	public function request_multiple( $requests, $options ) {
		$responses = array();
		$class = get_class( $this );
		foreach ( $requests as $id => $request ) {
			try {
				$handler = new $class();
				$responses[ $id ] = $handler->request( $request['url'], $request['headers'], $request['data'], $request['options'] );
			} catch ( Exception $e ) {
				$responses[ $id ] = $e;
			}
		}

		return $responses;
	}

	/**
	 * Retrieve the encodings we can accept
	 *
	 * @return string Accept-Encoding header value
	 */
	protected static function accept_encoding() {
		$type = array();
		if ( function_exists( 'gzinflate' ) ) {
			$type[] = 'deflate;q=1.0';
		}

		if ( function_exists( 'gzuncompress' ) ) {
			$type[] = 'compress;q=0.5';
		}

		$type[] = 'gzip;q=0.5';

		return implode( ', ', $type );
	}

	/**
	 * Format a URL given GET data
	 *
	 * @param array        $url_parts the parts of a url.
	 * @param array|object $data Data to build query using, see {@see https://secure.php.net/http_build_query}.
	 * @return string URL with data
	 */
	protected static function format_get( $url_parts, $data ) {
		if ( ! empty( $data ) ) {
			if ( empty( $url_parts['query'] ) ) {
				$url_parts['query'] = '';
			}

			$url_parts['query'] .= '&' . http_build_query( $data, null, '&' );
			$url_parts['query'] = trim( $url_parts['query'], '&' );
		}
		if ( isset( $url_parts['path'] ) ) {
			if ( isset( $url_parts['query'] ) ) {
				$get = $url_parts['path'] . '?' . $url_parts['query'];
			} else {
				$get = $url_parts['path'];
			}
		} else {
			$get = '/';
		}
		return $get;
	}

	/**
	 * Error handler for stream_socket_client()
	 *
	 * @param int    $errno Error number (e.g. E_WARNING).
	 * @param string $errstr Error message.
	 */
	public function connect_error_handler( $errno, $errstr ) {
		// Double-check we can handle it.
		if ( ($errno & E_WARNING) === 0 && ($errno & E_NOTICE) === 0 ) {
			// Return false to indicate the default error handler should engage.
			return false;
		}

		$this->connect_error .= $errstr . "\n";
		return true;
	}

	/**
	 * Verify the certificate against common name and subject alternative names
	 *
	 * Unfortunately, PHP doesn't check the certificate against the alternative
	 * names, leading things like 'https://www.github.com/' to be invalid.
	 * Instead
	 *
	 * @see https://tools.ietf.org/html/rfc2818#section-3.1 RFC2818, Section 3.1
	 *
	 * @throws Exception On failure to connect via TLS (`fsockopen.ssl.connect_error`).
	 * @throws Exception On not obtaining a match for the host (`fsockopen.ssl.no_match`).
	 * @param string   $host Host name to verify against.
	 * @param resource $context Stream context.
	 * @return bool
	 */
	public function verify_certificate_from_context( $host, $context ) {
		$meta = stream_context_get_options( $context );

		// If we don't have SSL options, then we couldn't make the connection at
		// all.
		if ( empty( $meta ) || empty( $meta['ssl'] ) || empty( $meta['ssl']['peer_certificate'] ) ) {
			throw new Exception( rtrim( $this->connect_error ) );
		}

		$cert = openssl_x509_parse( $meta['ssl']['peer_certificate'] );

		return false;
	}

	/**
	 * Whether this transport is valid
	 *
	 * @param array $capabilities what it can do.
	 * @return boolean True if the transport is valid, false otherwise.
	 */
	public static function test( $capabilities = array() ) {
		if ( ! function_exists( 'fsockopen' ) ) {
			return false;
		}

		// If needed, check that streams support SSL.
		if ( isset( $capabilities['ssl'] ) && $capabilities['ssl'] ) {
			if ( ! extension_loaded( 'openssl' ) || ! function_exists( 'openssl_x509_parse' ) ) {
				return false;
			}

			// Currently broken, thanks to https://github.com/facebook/hhvm/issues/2156.
			if ( defined( 'HHVM_VERSION' ) ) {
				return false;
			}
		}

		return true;
	}
}
