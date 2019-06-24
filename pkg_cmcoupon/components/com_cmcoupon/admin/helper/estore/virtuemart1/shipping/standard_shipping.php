<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');

class cm_standard_shipping {
    var $classname = 'standard_shipping';
	
	function __construct() {
		if(!defined('VM_TABLEPREFIX')) require_once JPATH_ADMINISTRATOR.'/components/com_virtuemart/virtuemart.cfg.php';
		$this->_db =& JFactory::getDBO();
	}
	
	function get_all_rates() {
		
		$o = array();
		$sql = 'SELECT CONCAT("'.$this->classname.'-",r.shipping_rate_id) AS dbshipper_id,
						CONCAT(c.shipping_carrier_name," ",r.shipping_rate_name) as shipper_string,						CONCAT(c.shipping_carrier_name," ",r.shipping_rate_name) as dd_name						
				  FROM #__'.VM_TABLEPREFIX.'_shipping_carrier c
				  JOIN #__'.VM_TABLEPREFIX.'_shipping_rate r ON r.shipping_rate_carrier_id=c.shipping_carrier_id
				 ORDER BY c.shipping_carrier_list_order,r.shipping_rate_list_order,r.shipping_rate_name' ;
		$this->_db->setQuery($sql);
		$o[$this->classname] = $this->_db->loadObjectList('dbshipper_id');
		$o['_raw'] = $o[$this->classname];

		return $o;
	}

	
	function get_unused_rates($coupon_id,$current_rates) {
		$o = array();
		$sql = 'SELECT CONCAT("'.$this->classname.'-",sr.shipping_rate_id) AS dbshipper_id,
						CONCAT(sc.shipping_carrier_name," ",sr.shipping_rate_name) as shipper_string
				 FROM #__'.VM_TABLEPREFIX.'_shipping_rate sr
				 JOIN #__'.VM_TABLEPREFIX.'_shipping_carrier sc ON sc.shipping_carrier_id=sr.shipping_rate_carrier_id
				ORDER BY sr.shipping_rate_name,sc.shipping_carrier_name, sr.shipping_rate_id';
		$this->_db->setQuery($sql);
		$tmp = $this->_db->loadObjectList();
		
		foreach($tmp as $row) {
			if(!isset($current_rates[$row->dbshipper_id])) {
				$o[$this->classname][] = $row;
			}
		}
		return $o;
	}
	
	function get_module_name() { return 'Standard Shipping'; }
	function get_rate_name($rate_id) {
		$sql = 'SELECT shipping_rate_name FROM #__'.VM_TABLEPREFIX.'_shipping_rate WHERE shipping_rate_id='.(int)$rate_id;
		$this->_db->setQuery($sql);
		return $this->_db->loadResult();
	}	function get_rate_id($rate_array) { return (int)$rate_array[4]; }}