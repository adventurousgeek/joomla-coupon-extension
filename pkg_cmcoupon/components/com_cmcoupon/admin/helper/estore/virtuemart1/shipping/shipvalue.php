<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

class cm_shipvalue {
    var $classname = 'shipvalue';
	
	function __construct() {
		$this->shipping_list = array();
		if(file_exists(JPATH_ADMINISTRATOR.'/components/com_virtuemart/classes/shipping/shipvalue.cfg.php')) {
			require_once JPATH_ADMINISTRATOR.'/components/com_virtuemart/classes/shipping/shipvalue.cfg.php';
			$this->shipping_list = array();
			for($i=1; $i<11; $i++) {
				$rate_id = constant('BASE_SHIP'.$i);
				if(!empty($rate_id)) $this->shipping_list[$rate_id] = 1;
			}
		}

	}
	
	function get_all_rates() {
		
		$o = array();
		foreach($this->shipping_list as $rate_id=>$v) {
			$o['_raw'][$this->classname.'-'.$rate_id] = (object) array(
						'dbshipper_id'=>$this->classname.'-'.$rate_id,
						'shipper_string'=>JText::_('ORDER TOTAL').': '.$rate_id,
						'dd_name'=>JText::_('ORDER TOTAL').': '.$rate_id,
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
							'shipper_string'=>JText::_('ORDER TOTAL').': '.$rate_id,
						);
			}
		}
		return $o;
	}
	
	function get_module_name() { return 'Shipping based on order totals'; }
	function get_rate_name($rate_id) {
		foreach($this->shipping_list as $constant_rate_id=>$v) {
			if($rate_id==$constant_rate_id) return JText::_('ORDER TOTAL').': '.$rate_id;
		}
		return '';
	}
	function get_rate_id($rate_array) { return trim(str_replace('Standard Shipping under','',$rate_array[2])); /*Standard Shipping under 10000.00*/}
}