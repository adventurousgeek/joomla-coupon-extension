<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

class cm_fedexv2 {
    var $classname = 'fedexv2';
	
	function __construct () {
	
		$this->shipping_list = array();
		if(file_exists(JPATH_ADMINISTRATOR.'/components/com_virtuemart/classes/shipping/fedexv2.cfg.php')) {
			require_once JPATH_ADMINISTRATOR.'/components/com_virtuemart/classes/shipping/fedexv2.cfg.php';
			$list = array(
					'FEDEX_GROUND'=>'FedEx Ground',
					'FEDEX_2_DAY'=>'FedEx 2 Day',
					'FEDEX_EXPRESS_SAVER'=>'FedEx Express Saver',
					'FIRST_OVERNIGHT'=>'FedEx First Overnight',
					'GROUND_HOME_DELIVERY'=>'FedEx Ground (Home Delivery)',
					'PRIORITY_OVERNIGHT'=>'FedEx Priority Overnight',
					'SMART_POST'=>'FedEx Smart Post',
					'STANDARD_OVERNIGHT'=>'FedEx Standard Overnight',
					'INTERNATIONAL_ECONOMY'=>'FedEx International Economy',
					'INTERNATIONAL_ECONOMY_DISTRIBUTION'=>'FedEx International Economy Distribution',
					'INTERNATIONAL_FIRST'=>'FedEx International First',
					'INTERNATIONAL_PRIORITY'=>'FedEx International Priority', 
					'INTERNATIONAL_PRIORITY_DISTRIBUTION'=>'FedEx International Priority Distribution',
					'EUROPE_FIRST_INTERNATIONAL_PRIORITY'=>'FedEx Europe First',
				);
			foreach($list as $item=>$name) {
				if(defined($item)) {
					$enabled = constant($item);
					if(!empty($enabled)) $this->shipping_list[$name] = 1;
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
	
	function get_module_name() { return 'Fedex v2'; }
	function get_rate_name($rate_id) {
		return $rate_id;
	}
	function get_rate_id($rate_array) { return trim($rate_array[2]); }
}

