<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

class cm_uspsv5 {
    var $classname = 'uspsv5';
	
	function __construct() {
		$this->shipping_list = array();
		if(file_exists(JPATH_ADMINISTRATOR.'/components/com_virtuemart/classes/shipping/uspsv5.cfg.php')) {
			require_once JPATH_ADMINISTRATOR.'/components/com_virtuemart/classes/shipping/uspsv5.cfg.php';
			$this->shipping_list = array();
			for($i=0; $i<24; $i++) {
				if(defined('USPS_SHIP'.$i)) {
					$enabled = constant('USPS_SHIP'.$i);
					if(!empty($enabled)) $this->shipping_list[str_replace('&lt;sup&gt;&amp;reg;&lt;/sup&gt;','',constant('USPS_SHIP'.$i.'_TEXT'))] = 1;
				}
			}
			for($i=0; $i<21; $i++) {
				if(defined('USPS_INTL'.$i)) {
					$enabled = constant('USPS_INTL'.$i);
					if(!empty($enabled)) $this->shipping_list[str_replace('&lt;sup&gt;&amp;reg;&lt;/sup&gt;','',constant('USPS_INTL'.$i.'_TEXT'))] = 1;
				}
			}
		}
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
	
	function get_module_name() { return 'USPS V5'; }
	function get_rate_name($rate_id) { return $rate_id; }
	function get_rate_id($rate_array) { return trim($rate_array[2]); }
}