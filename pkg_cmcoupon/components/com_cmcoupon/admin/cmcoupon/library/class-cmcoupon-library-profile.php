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
class Cmcoupon_Library_Profile {

	/**
	 * Generate email template image
	 *
	 * @param string $code coupon code.
	 * @param float  $value amount.
	 * @param string $expiration date.
	 * @param string $output type screen or file.
	 * @param array  $profile email template.
	 * @param int    $profile_id id.
	 * @param string $dynamic_text text.
	 **/
	public function generate_image( $code, $value, $expiration, $output, $profile = null, $profile_id = null, $dynamic_text = null ) {

		if ( ! empty( $profile_id ) ) {
			$profile = AC()->db->get_arraylist('
				SELECT id,image,coupon_code_config,coupon_value_config,expiration_config,freetext1_config,freetext2_config,freetext3_config
				  FROM #__cmcoupon_profile
				  WHERE id=' . (int) $profile_id . '
			');
			if ( ! empty( $profile ) ) {
				$profile = $this->decrypt_profile( current( $profile ) );
			}
		}

		if ( empty( $profile ) ) {
			return false;
		}
		if ( is_null( $profile['image'] ) ) {
			return false;
		}

		$baseimg = CMCOUPON_GIFTCERT_DIR . '/images/' . $profile['image'];

		if ( ! file_exists( $baseimg ) ) {
			return false;
		}
		$image_parts = pathinfo( $baseimg );
		$accepted_formats = array( 'png', 'jpg' );
		if ( ! in_array( $image_parts['extension'], $accepted_formats, true ) ) {
			return false;
		}

		// create image.
		switch ( $image_parts['extension'] ) {
			case 'png':
				$im = imagecreatefrompng( $baseimg );
				if ( ! $im ) {
					return false;
				}
				imagealphablending( $im, true ); // setting alpha blending on.
				imagesavealpha( $im, true ); // save alphablending setting (important).
				break;

			case 'jpg':
				$im = imagecreatefromjpeg( $baseimg );
				if ( ! $im ) {
					return false;
				}
				break;

		}

		if ( $this->write_on_image( $im, $code, $profile['coupon_code_config'] ) === false ) {
			return false;
		}
		$value = str_replace( '€', '&#8364;', $value );
		$value = str_replace( '&pound;', '&#8364;', $value );
		if ( $this->write_on_image( $im, $value, $profile['coupon_value_config'] ) === false ) {
			return false;
		}
		if ( ! empty( $expiration ) && ! empty( $profile['expiration_config'] ) ) {
			$str = AC()->helper->get_date( $expiration, $profile['expiration_config']['text'] );
			if ( $this->write_on_image( $im, $str,$profile['expiration_config'] ) === false ) {
				return false;
			}
		}
		if ( ! empty( $profile['freetext1_config'] ) ) {
			if ( ! empty( $dynamic_text['find'] ) ) {
				$profile['freetext1_config']['text'] = str_replace( $dynamic_text['find'], $dynamic_text['replace'], $profile['freetext1_config']['text'] );
			}
			if ( $this->write_on_image( $im, $profile['freetext1_config']['text'], $profile['freetext1_config'] ) === false ) {
				return false;
			}
		}
		if ( ! empty( $profile['freetext2_config'] ) ) {
			if ( ! empty( $dynamic_text['find'] ) ) {
				$profile['freetext2_config']['text'] = str_replace( $dynamic_text['find'], $dynamic_text['replace'], $profile['freetext2_config']['text'] );
			}
			if ( $this->write_on_image( $im, $profile['freetext2_config']['text'], $profile['freetext2_config'] ) === false ) {
				return false;
			}
		}
		if ( ! empty( $profile['freetext3_config'] ) ) {
			if ( ! empty( $dynamic_text['find'] ) ) {
				$profile['freetext3_config']['text'] = str_replace( $dynamic_text['find'], $dynamic_text['replace'], $profile['freetext3_config']['text'] );
			}
			if ( $this->write_on_image( $im, $profile['freetext3_config']['text'], $profile['freetext3_config'] ) === false ) {
				return false;
			}
		}

		$args = (object) array(
			'code' => $code,
			'value' => $value,
			'expiration' => $expiration,
			'dynamic_text' => $dynamic_text,
			'profile' => $profile,
			'is_preview' => 'screen' === $output ? true : false,
			'is_request' => 'email' === $output || ! empty( $profile_id ) ? true : false,
		);
		AC()->helper->trigger( 'cmcouponOnBeforeCreateGiftcertImage', array( & $im, & $args ) );

		if ( 'screen' === $output ) {
			return $im;
		} elseif ( 'email' === $output ) {
			$path = CMCOUPON_TEMP_DIR;
			if ( ! is_dir( $path ) ) {
				if ( ! mkdir( $path, 0777, true ) ) {
					return false;
				}
			}

			// write coupon code.
			switch ( $image_parts['extension'] ) {
				case 'png':
					$filename = time() . mt_rand() . '.png';
					imagepng( $im, $path . '/' . $filename ); // save image to file.
					break;

				case 'jpg':
					$filename = time() . mt_rand() . '.jpg';
					imagejpeg( $im, $path . '/' . $filename, 82 ); // save image to file.
					break;

			}

			imagedestroy( $im ); // destroy resource.

			return $path . '/' . $filename;
		}

		imagedestroy( $im ); // destroy resource.
	}

	/**
	 * Write to image main function
	 *
	 * @param object $im image object.
	 * @param string $text the text.
	 * @param array  $config options.
	 **/
	public function write_on_image( &$im, $text, $config ) {
		// write coupon code.
		$font = CMCOUPON_GIFTCERT_DIR . '/fonts/' . $config['font'];
		if ( ! file_exists( $font ) ) {
			return false;
		}
		$rgb = $this->html2rgb( $config['color'] );
		$color = imagecolorallocate( $im, $rgb[0], $rgb[1], $rgb[2] ); // create the text color.
		$align_func = 'imagettftext_l';
		if ( 'R' === $config['align'] ) {
			$align_func = 'imagettftext_r';
		} elseif ( 'C' === $config['align'] ) {
			$align_func = 'imagettftext_c';
		}
		$this->$align_func(
			$im,
			$config['size'],
			$config['y'],
			$color,
			$font,
			$text,
			$config['pad'],
			! empty( $config['maxwidth'] ) ? $config['maxwidth'] : 99999
		);
		return true;
	}

	/**
	 * Write to image right align
	 *
	 * @param object $image image object.
	 * @param int    $fontsize size.
	 * @param int    $y vertical align.
	 * @param string $fontcolor color hex.
	 * @param string $font font.
	 * @param string $str text.
	 * @param int    $padding padding.
	 * @param int    $max_width line break after with.
	 **/
	private function imagettftext_r( $image, $fontsize, $y, $fontcolor, $font, $str, $padding = 1, $max_width = 99999 ) {
		$text = $this->imagetext_wordwrap( $fontsize, 0, $font, $str, $max_width );
		$bbox = imagettfbbox( $fontsize, 0, $font, $text );
		$text_width = $bbox[2] - $bbox[0];
		imagettftext( $image, $fontsize, 0, ImageSX( $image ) - $text_width - $padding, $y, $fontcolor, $font, $text );
	}

	/**
	 * Write to image left align
	 *
	 * @param object $image image object.
	 * @param int    $fontsize size.
	 * @param int    $y vertical align.
	 * @param string $fontcolor color hex.
	 * @param string $font font.
	 * @param string $str text.
	 * @param int    $padding padding.
	 * @param int    $max_width line break after with.
	 **/
	private function imagettftext_l( $image, $fontsize, $y, $fontcolor, $font, $str, $padding = 1, $max_width = 99999 ) {
		$text = $this->imagetext_wordwrap( $fontsize, 0, $font, $str, $max_width );
		imagettftext( $image, $fontsize, 0, $padding, $y, $fontcolor, $font, $text );
	}

	/**
	 * Write to image center align
	 *
	 * @param object $image image object.
	 * @param int    $fontsize size.
	 * @param int    $y vertical align.
	 * @param string $fontcolor color hex.
	 * @param string $font font.
	 * @param string $str text.
	 * @param int    $padding padding.
	 * @param int    $max_width line break after with.
	 **/
	private function imagettftext_c( $image, $fontsize, $y, $fontcolor, $font, $str, $padding = 1, $max_width = 99999 ) {
		$text = $this->imagetext_wordwrap( $fontsize, 0, $font, $str, $max_width );
		$bbox = imagettfbbox( $fontsize, 0, $font, $text );
		$text_width = $bbox[2] - $bbox[0];
		imagettftext( $image, $fontsize, 0, (int) ( ImageSX( $image ) - $text_width ) / 2, $y, $fontcolor, $font, $text );
	}

	/**
	 * Write to image wordwrap on max width
	 *
	 * @param int    $size size.
	 * @param int    $angle degree.
	 * @param string $font font.
	 * @param string $text text.
	 * @param int    $max_width line break after with.
	 **/
	private function imagetext_wordwrap( $size, $angle, $font, $text, $max_width ) {

		$wordline = array();
		$lineno = 0;
		$words = explode( ' ', trim( $text ) );
		$wordline[ $lineno ] = array();

		foreach ( $words as $word ) {
			$dimensions = imagettfbbox( $size, $angle, $font, implode( ' ', $wordline[ $lineno ] ) . ' ' . $word );
			$line_width = $dimensions[2] - $dimensions[0];

			if ( $line_width > $max_width ) {
				$lineno++;
				$wordline[ $lineno ] = array();
			}
			$wordline[ $lineno ][] = $word;
		}

		$lines = array();
		foreach ( $wordline as $line ) {
			$lines[] = implode( ' ', $line );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Profile data from db to array
	 *
	 * @param object $profile data.
	 **/
	public function decrypt_profile( $profile ) {
		$profile['coupon_code_config'] = json_decode( $profile['coupon_code_config'], true );
		$profile['coupon_value_config'] = json_decode( $profile['coupon_value_config'], true );
		if ( ! empty( $profile['expiration_config'] ) ) {
			$profile['expiration_config'] = json_decode( $profile['expiration_config'], true );
		}
		if ( ! empty( $profile['freetext1_config'] ) ) {
			$profile['freetext1_config'] = json_decode( $profile['freetext1_config'], true );
		}
		if ( ! empty( $profile['freetext2_config'] ) ) {
			$profile['freetext2_config'] = json_decode( $profile['freetext2_config'], true );
		}
		if ( ! empty( $profile['freetext3_config'] ) ) {
			$profile['freetext3_config'] = json_decode( $profile['freetext3_config'], true );
		}
		return $profile;
	}

	/**
	 * Change color format from html code to rdb
	 *
	 * @param string $color color.
	 **/
	private function html2rgb( $color ) {
		if ( '#' === $color[0] ) {
			$color = substr( $color, 1 );
		}
		if ( strlen( $color ) === 6 ) {
			list( $r, $g, $b ) = array(
				$color[0] . $color[1],
				$color[2] . $color[3],
				$color[4] . $color[5],
			);
		} elseif ( strlen( $color ) === 3 ) {
			list( $r, $g, $b ) = array(
				$color[0] . $color[0],
				$color[1] . $color[1],
				$color[2] . $color[2],
			);
		} else {
			return false;
		}

		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );
		return array( $r, $g, $b );
	}

	/**
	 * Change color format from rgb to html format
	 *
	 * @param int $r r.
	 * @param int $g g.
	 * @param int $b b.
	 **/
	private function rgb2html( $r, $g = -1, $b = -1 ) {
		if ( is_array( $r ) && count( $r ) === 3 ) {
			list( $r, $g, $b ) = $r;
		}
		$r = intval( $r );
		$g = intval( $g );
		$b = intval( $b );
		$r = dechex( $r < 0 ? 0 : ( $r > 255 ? 255 : $r ) );
		$g = dechex( $g < 0 ? 0 : ( $g > 255 ? 255 : $g ) );
		$b = dechex( $b < 0 ? 0 : ( $b > 255 ? 255 : $b ) );
		$color = ( strlen( $r ) < 2 ? '0' : '' ) . $r;
		$color .= ( strlen( $g ) < 2 ? '0' : '' ) . $g;
		$color .= ( strlen( $b ) < 2 ? '0' : '' ) . $b;
		return '#' . $color;
	}

	/**
	 * Send email template
	 *
	 * @param object  $user user.
	 * @param array   $codes codes.
	 * @param array   $profile profile.
	 * @param array   $tag_replace replace tags.
	 * @param boolean $force_send send even if image is not created.
	 * @param string  $to email.
	 * @param boolean $is_entry_new is resending or new item.
	 * @param object  $params options.
	 **/
	public function send_email( $user, $codes, $profile, $tag_replace, $force_send = false, $to = '', $is_entry_new = true, $params = array() ) {
		// email codes.
		if ( empty( $to ) ) {
			$to = $user->email;
		}
		if ( ! AC()->helper->is_email( $to ) ) {
			return false;
		}

		$pdf_tag_replace = $tag_replace;

		if ( empty( $user->id ) ) {
			$user->id = 0;
		}
		$profile['lang_email_subject'] = AC()->lang->get_data( $profile['idlang_email_subject'], $user->id, null, true );
		$profile['lang_email_body'] = AC()->helper->fixpaths_relative_to_absolute( AC()->lang->get_data( $profile['idlang_email_body'], $user->id, null, true ) );
		$profile['lang_voucher_syntax'] = AC()->lang->get_data( $profile['idlang_voucher_text'], $user->id, null, true );
		$profile['lang_voucher_exp_syntax'] = AC()->lang->get_data( $profile['idlang_voucher_text_exp'], $user->id, null, true );
		$profile['lang_voucher_filename'] = AC()->lang->get_data( $profile['idlang_voucher_filename'], $user->id, null, true );
		$profile['lang_pdf_header'] = AC()->helper->fixpaths_relative_to_absolute( AC()->lang->get_data( $profile['idlang_pdf_header'], $user->id, null, true ) );
		$profile['lang_pdf_body'] = AC()->helper->fixpaths_relative_to_absolute( AC()->lang->get_data( $profile['idlang_pdf_body'], $user->id, null, true ) );
		$profile['lang_pdf_footer'] = AC()->helper->fixpaths_relative_to_absolute( AC()->lang->get_data( $profile['idlang_pdf_footer'], $user->id, null, true ) );
		$profile['lang_pdf_filename'] = AC()->lang->get_data( $profile['idlang_pdf_filename'], $user->id );

		if ( empty( $profile['lang_voucher_filename'] ) ) {
			$profile['lang_voucher_filename'] = 'voucher#';
		}
		if ( strpos( $profile['lang_voucher_filename'],'#' ) === false ) {
			$profile['lang_voucher_filename'] .= '#';
		}
		if ( empty( $profile['lang_pdf_filename'] ) ) {
			$profile['lang_pdf_filename'] = 'attachment';
		}

		// print codes.
		$text_gift = '';
		$attachments = array();
		$myprofiles = array();
		$cleanup_data = array();

		// generate images.
		$i = 0;
		foreach ( $codes as $k => $row ) {
			$expiration_text = ! empty( $row->expiration ) ? str_replace( '{expiration}', AC()->helper->get_date( $row->expiration ), $profile['lang_voucher_exp_syntax'] ) : '';
			$text_gift .= str_replace(
				array( '{voucher}', '{price}', '{expiration_text}' ),
				array( $row->coupon_code, $row->coupon_price, $expiration_text ),
				$profile['lang_voucher_syntax']
			);

			if ( ! is_null( $profile['image'] ) ) {

				// update tags.
				$image_tag_replace = $tag_replace;
				if ( ! empty( $row->tag_replace['find'] ) && ! empty( $row->tag_replace['replace'] ) && count( $row->tag_replace['find'] ) === count( $row->tag_replace['replace'] ) ) {
					foreach ( $row->tag_replace['find'] as $key_image_tag => $value_image_tag ) {
						$_index = array_search( $value_image_tag, $image_tag_replace['find'], true );
						if ( false !== $_index ) {
							$image_tag_replace['replace'][ $_index ] = $row->tag_replace['replace'][ $key_image_tag ];
						} else {
							array_push( $image_tag_replace['find'], $value_image_tag, '{vouchers}' );
							array_push( $image_tag_replace['replace'], $row->tag_replace['replace'][ $key_image_tag ] );
						}
					}
				}

				$r_file = $this->generate_image( $row->coupon_code, $row->coupon_price, ! empty( $row->expiration ) ? $row->expiration : 0, 'email', empty( $row->profile ) ? $profile : $row->profile, null, $image_tag_replace );
				if ( false === $r_file ) {
					if ( ! $force_send ) {
						$this->cleanup( $cleanup_data, 'cannot create voucher images' );
						return false;
					}
				} else {
					$i++;
					$filename = str_replace( '#', count( $codes ) !== 1 ? $i : '', $profile['lang_voucher_filename'] );
					$codes[ $k ]->tmp_file_path = $r_file;
					$attachments[ $filename ] = $r_file;
					$cleanup_data['files'][] = $r_file;
				}
			}
		}

		// vendor info.
		$from_name = $profile['from_name'];
		if ( empty( $from_name ) ) {
			$from_name = AC()->store->get_name();
		}
		$from_email = $profile['from_email'];
		if ( empty( $from_email ) ) {
			$from_email = AC()->store->get_email();
		}

		$subject = ! empty( $params['override_email_subject'] ) ? $params['override_email_subject'] : $profile['lang_email_subject'];
		$cc = ! empty( $profile['cc_purchaser'] ) && $to !== $user->email ? $user->email : null;
		$bcc = ! empty( $profile['bcc_admin'] ) ? $from_email : null;
		$message = ! empty( $params['override_email_message'] ) ? $params['override_email_message'] : $profile['lang_email_body'];

		// message info.
		$is_embed = false;
		$embed_text = '';
		$text_gift = nl2br( $text_gift );
		if ( ! empty( $attachments ) ) {
			if ( strpos( $message, '{image_embed}' ) !== false ) {
				$is_embed = true;
				$i = 0;
				foreach ( $attachments as $attachment ) {
					$embed_text .= '<div><img src="cid:couponimageembed' . ( ++$i ) . '"></div>';
				}
			}
		}

		// pdf attachment.
		$string_attachments = array();
		if ( ! empty( $profile['is_pdf'] ) && ! empty( $profile['lang_pdf_body'] ) ) {
			$pdf_image_embed = '';
			if ( ! is_null( $profile['image'] ) && strpos( $profile['lang_pdf_body'], '{image_embed}' ) !== false ) {
				$pdf_text_without_space = trim( preg_replace( '/^\p{Z}+|\p{Z}+$/u', '', strip_tags( $profile['lang_pdf_body'] ) ) ); // remove normal and unicode whitespace.
				$is_first_image_in_pdf = true;
				foreach ( $attachments as $attachment ) {
					if ( $is_first_image_in_pdf ) {
						$is_first_image_in_pdf = false;
						if ( substr( $pdf_text_without_space, 0, 13 ) !== '{image_embed}' ) {
							$pdf_image_embed .= '<div style="page-break-before:always;"></div>';
						}
					} else {
						$pdf_image_embed .= '<div style="page-break-before:always;"></div>';
					}
					$pdf_image_embed .= '<img src="' . $attachment . '">';
				}
				if ( ! empty( $pdf_image_embed ) && substr( $pdf_text_without_space, -13 ) !== '{image_embed}' ) {
					$pdf_image_embed .= '<div style="page-break-after:always;"></div>';
				}
			}

			array_push( $pdf_tag_replace['find'], '{vouchers}' );
			array_push( $pdf_tag_replace['replace'], $text_gift );
			if ( ! empty( $pdf_image_embed ) ) {
				array_push( $pdf_tag_replace['find'], '{image_embed}' );
				array_push( $pdf_tag_replace['replace'], $pdf_image_embed );
			}
			$pdf_body = str_replace( $pdf_tag_replace['find'], $pdf_tag_replace['replace'], $profile['lang_pdf_body'] );

			AC()->helper->add_class( 'Cmcoupon_Library_Html2pdf' );
			$html2pdf = new Cmcoupon_Library_Html2pdf( $profile['lang_pdf_header'], $pdf_body,$profile['lang_pdf_footer'] );
			$pdf_data = $html2pdf->processpdf( 'S' );

			if ( ! empty( $pdf_data ) ) {
				$string_attachments[ $profile['lang_pdf_filename'] . '.pdf' ] = $pdf_data;
			}
		}

		$subject = str_replace( $tag_replace['find'], $tag_replace['replace'], $subject );
		array_push( $tag_replace['find'], '{image_embed}', '{vouchers}' );
		array_push( $tag_replace['replace'], $embed_text, $text_gift );
		$message = str_replace( $tag_replace['find'], $tag_replace['replace'], $message );

		if ( AC()->helper->send_email(
			$from_email,
			$from_name,
			$to,
			$subject,
			$message,
			$bcc,
			$attachments,
			$is_embed,
			$string_attachments,
			$cc
		) !== true ) {
			$this->cleanup( $cleanup_data, 'cannot send joomla email' );
			return false;
		}

		if ( $is_entry_new ) {
			// save for display in front end.
			if ( ! empty( $attachments ) && (int) AC()->param->get( 'enable_frontend_image', 0 ) === 1 ) {

				$dir = CMCOUPON_CUSTOMER_DIR;
				if ( ! file_exists( $dir ) ) {
					mkdir( $dir, 0755, true ); // recursive.
				}
				$user->id = (int) $user->id;
				if ( ! empty( $user->id ) ) {
					$dir = $dir . '/' . $user->id;
					if ( ! file_exists( $dir ) ) {
						mkdir( $dir, 0755 );
						// basic security.
						file_put_contents( $dir . '/.htaccess', 'error 500 !' );
						file_put_contents( $dir . '/index.html', 'error!' );
					}

					foreach ( $codes as $k => $row ) {
						$coupon_id = $row->id;
						$file = $row->tmp_file_path;
						if ( empty( $coupon_id ) ) {
							continue;
						}
						if ( empty( $file ) || ! file_exists( $file ) ) {
							continue;
						}

						$f2 = file_get_contents( $file );
						$fi = pathinfo( $file );

						{ // make file safe.
							$filename = rtrim( $fi['basename'], '.' );
							$filename = trim( preg_replace( array( '#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#' ), '', $filename ) );
						}

						$codes[ $k ]->filename = $filename;
						$fcontent = urldecode( '%3c%3fphp+die()%3b+%3f%3e' ) . base64_encode( $f2 );

						// might not be compatible with FTP-based access.
						file_put_contents( $dir . '/' . $filename . '.php', urldecode( '%3c%3fphp+die()%3b+%3f%3e' ) . base64_encode( $f2 ) );

						// add table link.
						AC()->db->query('
							INSERT INTO #__cmcoupon_image (coupon_id,user_id,filename) VALUES (' . $coupon_id . ',' . $user->id . ',"' . AC()->db->escape( $filename ) . '")
								ON DUPLICATE KEY UPDATE filename="' . AC()->db->escape( $filename ) . '"
						');
					}
				}
			}
		}

		// delete created images.
		if ( ! empty( $cleanup_data['files'] ) ) {
			foreach ( $cleanup_data['files'] as $file ) {
				if ( ! empty( $file ) && file_exists( $file ) ) {
					unlink( $file );
				}
			}
		}

		return $codes;
	}

	/**
	 * Cleanup if error occurs
	 *
	 * @param array  $cleanup_data data.
	 * @param string $message log.
	 **/
	private function cleanup( $cleanup_data, $message ) {

		if ( ! empty( $cleanup_data['files'] ) ) {
			foreach ( $cleanup_data['files'] as $file ) {
				if ( ! empty( $file ) && file_exists( $file ) ) {
					unlink( $file );
				}
			}
		}

		return false;
	}
}

