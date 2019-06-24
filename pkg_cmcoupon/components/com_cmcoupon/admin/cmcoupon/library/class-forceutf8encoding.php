<?php
if ( ! defined( '_CM_' ) ) {
	exit;
}
/**
Copyright (c) 2008 Sebastián Grignoli
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:
1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. Neither the name of copyright holders nor the names of its
   contributors may be used to endorse or promote products derived
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL COPYRIGHT HOLDERS OR CONTRIBUTORS
BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
 *
 * @author   "Sebastián Grignoli" <grignoli@framework2.com.ar>
 * @package  Encoding
 * @version  1.2
 * @link     https://github.com/neitanod/forceutf8
 * @example  https://github.com/neitanod/forceutf8
 * @license  Revised BSD
 **/

/**
 * Class
 */
class ForceUTF8Encoding {

	/**
	 * Win1258 to utf8 table
	 *
	 * @var array
	 */
	protected static $win1252_to_utf8 = array(
		128 => "\xe2\x82\xac",

		130 => "\xe2\x80\x9a",
		131 => "\xc6\x92",
		132 => "\xe2\x80\x9e",
		133 => "\xe2\x80\xa6",
		134 => "\xe2\x80\xa0",
		135 => "\xe2\x80\xa1",
		136 => "\xcb\x86",
		137 => "\xe2\x80\xb0",
		138 => "\xc5\xa0",
		139 => "\xe2\x80\xb9",
		140 => "\xc5\x92",

		142 => "\xc5\xbd",


		145 => "\xe2\x80\x98",
		146 => "\xe2\x80\x99",
		147 => "\xe2\x80\x9c",
		148 => "\xe2\x80\x9d",
		149 => "\xe2\x80\xa2",
		150 => "\xe2\x80\x93",
		151 => "\xe2\x80\x94",
		152 => "\xcb\x9c",
		153 => "\xe2\x84\xa2",
		154 => "\xc5\xa1",
		155 => "\xe2\x80\xba",
		156 => "\xc5\x93",

		158 => "\xc5\xbe",
		159 => "\xc5\xb8",
	);

	/**
	 * Broken utf8 to utf8 table.
	 *
	 * @var array
	 */
	protected static $broken_utf8_to_utf8 = array(
		"\xc2\x80" => "\xe2\x82\xac",

		"\xc2\x82" => "\xe2\x80\x9a",
		"\xc2\x83" => "\xc6\x92",
		"\xc2\x84" => "\xe2\x80\x9e",
		"\xc2\x85" => "\xe2\x80\xa6",
		"\xc2\x86" => "\xe2\x80\xa0",
		"\xc2\x87" => "\xe2\x80\xa1",
		"\xc2\x88" => "\xcb\x86",
		"\xc2\x89" => "\xe2\x80\xb0",
		"\xc2\x8a" => "\xc5\xa0",
		"\xc2\x8b" => "\xe2\x80\xb9",
		"\xc2\x8c" => "\xc5\x92",

		"\xc2\x8e" => "\xc5\xbd",


		"\xc2\x91" => "\xe2\x80\x98",
		"\xc2\x92" => "\xe2\x80\x99",
		"\xc2\x93" => "\xe2\x80\x9c",
		"\xc2\x94" => "\xe2\x80\x9d",
		"\xc2\x95" => "\xe2\x80\xa2",
		"\xc2\x96" => "\xe2\x80\x93",
		"\xc2\x97" => "\xe2\x80\x94",
		"\xc2\x98" => "\xcb\x9c",
		"\xc2\x99" => "\xe2\x84\xa2",
		"\xc2\x9a" => "\xc5\xa1",
		"\xc2\x9b" => "\xe2\x80\xba",
		"\xc2\x9c" => "\xc5\x93",

		"\xc2\x9e" => "\xc5\xbe",
		"\xc2\x9f" => "\xc5\xb8",
	);

	/**
	 * Utf8 to win1252 table.
	 *
	 * @var array
	 */
	protected static $utf8_to_win1252 = array(
		"\xe2\x82\xac" => "\x80",

		"\xe2\x80\x9a" => "\x82",
		"\xc6\x92"     => "\x83",
		"\xe2\x80\x9e" => "\x84",
		"\xe2\x80\xa6" => "\x85",
		"\xe2\x80\xa0" => "\x86",
		"\xe2\x80\xa1" => "\x87",
		"\xcb\x86"     => "\x88",
		"\xe2\x80\xb0" => "\x89",
		"\xc5\xa0"     => "\x8a",
		"\xe2\x80\xb9" => "\x8b",
		"\xc5\x92"     => "\x8c",

		"\xc5\xbd"     => "\x8e",


		"\xe2\x80\x98" => "\x91",
		"\xe2\x80\x99" => "\x92",
		"\xe2\x80\x9c" => "\x93",
		"\xe2\x80\x9d" => "\x94",
		"\xe2\x80\xa2" => "\x95",
		"\xe2\x80\x93" => "\x96",
		"\xe2\x80\x94" => "\x97",
		"\xcb\x9c"     => "\x98",
		"\xe2\x84\xa2" => "\x99",
		"\xc5\xa1"     => "\x9a",
		"\xe2\x80\xba" => "\x9b",
		"\xc5\x93"     => "\x9c",

		"\xc5\xbe"     => "\x9e",
		"\xc5\xb8"     => "\x9f",
	);

	/**
	 * To utf8
	 *
	 * @param string $text text.
	 **/
	static function to_utf8( $text ) {
		/**
		 * Function Encoding::toUTF8
		 *
		 * This function leaves UTF8 characters alone, while converting almost all non-UTF8 to UTF8.
		 *
		 * It assumes that the encoding of the original string is either Windows-1252 or ISO 8859-1.
		 *
		 * It may fail to convert characters to UTF-8 if they fall into one of these scenarios:
		 *
		 * 1) when any of these characters:   ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞß
		 *    are followed by any of these:  ("group B")
		 *                                    ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶•¸¹º»¼½¾¿
		 * For example:   %ABREPRESENT%C9%BB. «REPRESENTÉ»
		 * The "«" (%AB) character will be converted, but the "É" followed by "»" (%C9%BB)
		 * is also a valid unicode character, and will be left unchanged.
		 *
		 * 2) when any of these: àáâãäåæçèéêëìíîï  are followed by TWO chars from group B,
		 * 3) when any of these: ðñòó  are followed by THREE chars from group B.
		 *
		 * @name toUTF8
		 * @param string $text  Any string.
		 * @return string  The same string, UTF8 encoded
		 */

		if ( is_array( $text ) ) {
			foreach ( $text as $k => $v ) {
				$text[ $k ] = self::to_utf8( $v );
			}
			return $text;
		} elseif ( is_string( $text ) ) {

			$max = strlen( $text );
			$buf = '';
			for ( $i = 0; $i < $max; $i++ ) {
				$c1 = $text{$i};
				if ( $c1 >= "\xc0" ) { // Should be converted to UTF8, if it's not UTF8 already.
					$c2 = $i + 1 >= $max ? "\x00" : $text{$i + 1};
					$c3 = $i + 2 >= $max ? "\x00" : $text{$i + 2};
					$c4 = $i + 3 >= $max ? "\x00" : $text{$i + 3};
					if ( $c1 >= "\xc0" & $c1 <= "\xdf" ) { // looks like 2 bytes UTF8.
						if ( $c2 >= "\x80" && $c2 <= "\xbf" ) { // yeah, almost sure it's UTF8 already.
							$buf .= $c1 . $c2;
							$i++;
						} else { // not valid UTF8.  Convert it.
							$cc1 = (chr( ord( $c1 ) / 64 ) | "\xc0");
							$cc2 = ($c1 & "\x3f") | "\x80";
							$buf .= $cc1 . $cc2;
						}
					} elseif ( $c1 >= "\xe0" & $c1 <= "\xef" ) { // looks like 3 bytes UTF8.
						if ( $c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" ) { // yeah, almost sure it's UTF8 already.
							$buf .= $c1 . $c2 . $c3;
							$i = $i + 2;
						} else { // not valid UTF8.  Convert it.
							$cc1 = (chr( ord( $c1 ) / 64 ) | "\xc0");
							$cc2 = ($c1 & "\x3f") | "\x80";
							$buf .= $cc1 . $cc2;
						}
					} elseif ( $c1 >= "\xf0" & $c1 <= "\xf7" ) { // looks like 4 bytes UTF8.
						if ( $c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf" ) { // yeah, almost sure it's UTF8 already.
							$buf .= $c1 . $c2 . $c3;
							$i = $i + 2;
						} else { // not valid UTF8.  Convert it.
							$cc1 = (chr( ord( $c1 ) / 64 ) | "\xc0");
							$cc2 = ($c1 & "\x3f") | "\x80";
							$buf .= $cc1 . $cc2;
						}
					} else { // doesn't look like UTF8, but should be converted.
						$cc1 = (chr( ord( $c1 ) / 64 ) | "\xc0");
						$cc2 = (($c1 & "\x3f") | "\x80");
						$buf .= $cc1 . $cc2;
					}
				} elseif ( ($c1 & "\xc0") == "\x80" ) { // needs conversion.
					if ( isset( self::$win1252_to_utf8[ ord( $c1 ) ] ) ) { // found in Windows-1252 special cases.
						$buf .= self::$win1252_to_utf8[ ord( $c1 ) ];
					} else {
						$cc1 = (chr( ord( $c1 ) / 64 ) | "\xc0");
						$cc2 = (($c1 & "\x3f") | "\x80");
						$buf .= $cc1 . $cc2;
					}
				} else { // it doesn't need convesion.
					$buf .= $c1;
				}
			}
			return $buf;
		} else {
			return $text;
		}
	}

	/**
	 * To win1252
	 *
	 * @param string $text text.
	 **/
	static function to_win1252( $text ) {
		if ( is_array( $text ) ) {
			foreach ( $text as $k => $v ) {
				$text[ $k ] = self::to_win1252( $v );
			}
			return $text;
		} elseif ( is_string( $text ) ) {
			return utf8_decode( str_replace( array_keys( self::$utf8_to_win1252 ), array_values( self::$utf8_to_win1252 ), self::to_utf8( $text ) ) );
		} else {
			return $text;
		}
	}

	/**
	 * To iso8859
	 *
	 * @param string $text text.
	 **/
	static function to_iso8859( $text ) {
		return self::to_win1252( $text );
	}

	/**
	 * To latin1
	 *
	 * @param string $text text.
	 **/
	static function to_latin1( $text ) {
		return self::to_win1252( $text );
	}

	/**
	 * Fix utf8
	 *
	 * @param string $text text.
	 **/
	static function fix_utf8( $text ) {
		if ( is_array( $text ) ) {
			foreach ( $text as $k => $v ) {
				$text[ $k ] = self::fix_utf8( $v );
			}
			return $text;
		}

		$last = '';
		while ( $last !== $text ) {
			$last = $text;
			$text = self::to_utf8( utf8_decode( str_replace( array_keys( self::$utf8_to_win1252 ), array_values( self::$utf8_to_win1252 ), $text ) ) );
		}
		$text = self::to_utf8( utf8_decode( str_replace( array_keys( self::$utf8_to_win1252 ), array_values( self::$utf8_to_win1252 ), $text ) ) );
		return $text;
	}

	/**
	 * Convert win1252 chars to utf9
	 *
	 * @param string $text text.
	 **/
	static function utf8_fix_win1252_chars( $text ) {
		// If you received an UTF-8 string that was converted from Windows-1252 as it was ISO8859-1
		// (ignoring Windows-1252 chars from 80 to 9F) use this function to fix it.
		// See: http://en.wikipedia.org/wiki/Windows-1252
		// .
		return str_replace( array_keys( self::$broken_utf8_to_utf8 ), array_values( self::$broken_utf8_to_utf8 ), $text );
	}

	/**
	 * Remove BOM
	 *
	 * @param string $str string.
	 **/
	static function remove_bom( $str = '' ) {
		if ( substr( $str, 0,3 ) == pack( 'CCC',0xef,0xbb,0xbf ) ) {
			$str = substr( $str, 3 );
		}
		return $str;
	}

	/**
	 * Normalize encoding
	 *
	 * @param string $encoding_label label.
	 **/
	public static function normalize_encoding( $encoding_label ) {
		$encoding = strtoupper( $encoding_label );
		$enc = preg_replace( '/[^a-zA-Z0-9\s]/', '', $encoding );
		$equivalences = array(
			'ISO88591' => 'ISO-8859-1',
			'ISO8859'  => 'ISO-8859-1',
			'ISO'      => 'ISO-8859-1',
			'LATIN1'   => 'ISO-8859-1',
			'LATIN'    => 'ISO-8859-1',
			'UTF8'     => 'UTF-8',
			'UTF'      => 'UTF-8',
			'WIN1252'  => 'ISO-8859-1',
			'WINDOWS1252' => 'ISO-8859-1',
		);

		if ( empty( $equivalences[ $encoding ] ) ) {
			return 'UTF-8';
		}

		return $equivalences[ $encoding ];
	}

	/**
	 * Encode
	 *
	 * @param string $encoding_label label.
	 * @param string $text text.
	 **/
	public static function encode( $encoding_label, $text ) {
		$encoding_label = self::normalize_encoding( $encoding_label );
		if ( 'UTF-8' === $encoding_label ) {
			return ForceUTF8Encoding::to_utf8( $text );
		}
		if ( 'ISO-8859-1' === $encoding_label ) {
			return ForceUTF8Encoding::to_latin1( $text );
		}
	}

}
