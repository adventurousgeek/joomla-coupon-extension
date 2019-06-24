<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

class cm_upsv2 {
    var $classname = 'upsv2';
	
	function __construct() {
		$this->shipping_list = array(
			'UPS Next Day Air'=>1,
			'UPS 2nd Day Air'=>1,
			'UPS Ground'=>1,
			'UPS Worldwide Express SM'=>1,
			'UPS Worldwide Expedited SM'=>1,
			'UPS Standard'=>1,
			'UPS 3 Day Select'=>1,
			'UPS Next Day Air Saver'=>1,
			'UPS Next Day Air Early A.M.'=>1,
			'UPS Worldwide Express Plus SM'=>1,
			'UPS 2nd Day Air A.M'=>1,
			'UPS Express Saver'=>1,
		);
	}
	
	function get_all_rates() {
		
		$o = array();
		foreach($this->shipping_list as $rate_id=>$v) {
			$o['_raw'][$this->classname.'-'.$rate_id] = (object) array(
						'dbshipper_id'=>$this->classname.'-'.$rate_id,
						'shipper_string'=>$rate_id,
						'dd_name'=>$rate_id,
					);
			$o[$this->classname][] = $o['_raw'][$this->classname.'-'.$rate_id];
		}
		
		return $o;
	}

	
	function get_unused_rates($coupon_id,$current_rates) {
		$o = array();
		foreach($this->shipping_list as $rate_id=>$v) {
			if(!isset($current_rates[$this->classname.'-'.$rate_id])) {
				$o[$this->classname][] = (object) array(
							'dbshipper_id'=>$this->classname.'-'.$rate_id,
							'shipper_string'=>$rate_id,
						);
			}
		}
		return $o;
	}
	
	function get_module_name() { return 'UPS V2'; }
	function get_rate_name($rate_id) {
		return $rate_id;
	}
	function get_rate_id($rate_array) { return trim($rate_array[2]); }
}