<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined('_JEXEC') or die('Restricted access');

class CmCouponRouter {
	public function build(&$query) {
		static $items;
		static $id_coupon;
		static $id_giftcert;
		static $id_default;
		
		$segments = array();

		// Get the relevant menu items if not loaded.
		if (empty($items)) {

			// Get all relevant menu items.
			$app = JFactory::getApplication();
			$menu = $app->getMenu();
			$items = $menu->getItems('component', 'com_cmcoupon');

			// Build an array of serialized query strings to menu item id mappings.
			for ($i = 0, $n = count($items); $i < $n; $i++) {
				if(empty($items[$i]->query['view'])) continue;

				if (empty($id_coupon) && ($items[$i]->query['view'] == 'coupons')) $id_coupon = $items[$i]->id;
				if (empty($id_giftcert) && ($items[$i]->query['view'] == 'giftcerts')) $id_giftcert = $items[$i]->id;
			}

			// Set the default menu item to use for com_users if possible.
			if ($id_coupon) $id_default = $id_coupon;
			elseif ($id_giftcert) $id_default = $id_giftcert;
		}
	
		if (!empty($query['view'])) {
			switch ($query['view']) {
				case 'coupons':
					if ($query['Itemid'] = $id_coupon) unset ($query['view']);
					else {
						$query['Itemid'] = $id_default;
						$segments[] = $query['view'];
						unset ($query['view']);
					}
					break;

				case 'giftcerts':
					if ($query['Itemid'] = $id_giftcert) unset ($query['view']);
					else {
						$query['Itemid'] = $id_default;
						$segments[] = $query['view'];
						unset ($query['view']);
					}
					break;

				default:
					$query['Itemid'] = $id_default;
					$segments[] = $query['view'];
					unset ($query['view']);
					break;
			}
		}


		$total = count($segments);
		for ($i = 0; $i < $total; $i++) $segments[$i] = str_replace(':', '-', $segments[$i]);

		return $segments;

	}

	public function parse(&$segments){
		$vars = array();

		$count = count($segments);
		for ($i = 0; $i < $count; $i++) $segments[$i] = preg_replace('/-/', ':', $segments[$i], 1);

		if(!empty($count)) {
			$vars['view'] = $segments[0];
		}

		if($count > 1) {
			$vars['id']    = $segments[$count - 1];
		}

		return $vars;
	}
}

function CmCouponBuildRoute(&$query) {
	$router = new CmCouponRouter;
	return $router->build($query);
}

function CmCouponParseRoute($segments) {
		$router = new CmCouponRouter;
		return $router->parse($segments);
}

 
