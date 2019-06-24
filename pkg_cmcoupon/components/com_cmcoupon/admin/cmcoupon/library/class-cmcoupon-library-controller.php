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
class CmCoupon_Library_Controller {

	/**
	 * Render the html from file
	 *
	 * @param string $file the file to display.
	 * @param object $data replacement variables to parse through the file.
	 **/
	public function render( $file, $data = null ) {

		// Check possible overrides, and build the full path to layout file.
		$path = CMCOUPON_DIR . '/cmcoupon';
		$tmp = explode( '.', $file );
		foreach ( $tmp as $tmp2 ) {
			$path .= '/' . $tmp2;
		}
		$path .= '.php';

		// Nothing to show.
		if ( ! file_exists( $path ) ) {
			return '';
		}
		if ( ! empty( $data ) && ! is_object( $data ) ) {
			$data = (object) $data;
		}

		ob_start();
		include $path;
		$html = ob_get_contents();
		ob_end_clean();

		echo $html;
	}

}
