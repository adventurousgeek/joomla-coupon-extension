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
class Cmcoupon_Library_Pagination_Object {

	/**
	 * The text.
	 *
	 * @var string
	 */
	public $text;

	/**
	 * The base.
	 *
	 * @var string
	 */
	public $base;

	/**
	 * The link.
	 *
	 * @var string
	 */
	public $link;

	/**
	 * The prefix.
	 *
	 * @var string
	 */
	public $prefix;

	/**
	 * Is active.
	 *
	 * @var boolean
	 */
	public $active;

	/**
	 * Initialise
	 *
	 * @param string  $text the text.
	 * @param string  $prefix the prefix.
	 * @param string  $base the base.
	 * @param string  $link the link.
	 * @param boolean $active is active.
	 **/
	public function init( $text, $prefix = '', $base = null, $link = null, $active = false ) {
		$this->text   = $text;
		$this->prefix = $prefix;
		$this->base   = $base;
		$this->link   = $link;
		$this->active = $active;
	}

}
