<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

class cm_fedex {
    var $classname = 'fedex';
	
	function __construct() {
		$this->shipping_list = array();
		if(file_exists(JPATH_ADMINISTRATOR.'/components/com_virtuemart/classes/shipping/fedex/fedex-tags.php')) {
			require_once JPATH_ADMINISTRATOR.'/components/com_virtuemart/classes/shipping/fedex/fedex-tags.php';
			$fed = new FedExTags();
			foreach($fed->FE_ST as $rate) $this->shipping_list[$rate] = 1;
			foreach($fed->FE_ST_INTL as $rate) $this->shipping_list[$rate] = 1;
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
	
	function get_module_name() { return 'FedEx'; }
	function get_rate_name($rate_id) {
		return $rate_id;
	}
	function get_rate_id($rate_array) { return trim($rate_array[2]); }
}