<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );

if(!class_exists('hikashopCartClass')) require JPATH_ADMINISTRATOR.'/components/com_hikashop/classes/cart.php';

class CmCouponHikashopCouponHandlerCart extends hikashopCartClass {


	public function refreshHikashopCartProducts($hikashop_version) {
	//public function &getFullCart($cart_id = 0,$checkcoupon = true) { # seyi_code
		if(version_compare($hikashop_version,'3.0.0','<')) {
			return $this->refreshHikashopCartProducts2x();
		}



		$cart_id = $this->getCurrentCartId();
		if($cart_id === false) {
			$ret = false;
			return $ret;
		}
		$cart_id = (int)$cart_id;

		if(isset(self::$cache['full'][$cart_id]))
			return $this->getCloneCache('full', $cart_id);

		$cart = $this->get($cart_id, null);
		if(empty($cart))
			return $cart;

		$cart->total = new stdClass();
		$cart->full_total = new stdClass();
		$cart->messages = array();
		$cart->package = array();
		$cart->usable_methods = new stdClass;

		if(isset(self::$cache['msg'][$cart_id])) {
			$cart->messages = self::$cache['msg'][$cart_id];
			unset(self::$cache['msg'][$cart_id]);
		}

		$discount_before_tax = (int)$this->config->get('discount_before_tax', 0);
		$zones = array();
		$zone_id = 0;
		$tax_zone_id = 0;

		if($cart->cart_type != 'wishlist') {
			$addressClass = hikashop_get('class.address');

			$address = null;
			if(!empty($cart->cart_billing_address_id))
				$address = $addressClass->get((int)$cart->cart_billing_address_id);

			if(empty($address)) {
				$addresses = $addressClass->loadUserAddresses((int)$cart->user_id);
				if(!empty($addresses) && is_array($addresses)) {
					$address = reset($addresses);
					$cart->cart_billing_address_id = (int)$address->address_id;
				}
			}

			if(!empty($address)) {
				$cart->billing_address = $address;
				$zone_id = $this->extractZone($address, $zones, true);
				$tax_zone_id = $zone_id;
			} else {
				$cart->cart_billing_address_id = 0;
			}
			unset($address);

			$address = null;
			if(!empty($cart->cart_shipping_address_ids))
				$address = $addressClass->get((int)$cart->cart_shipping_address_ids);

			if(empty($address)) {
				$addresses = $addressClass->loadUserAddresses((int)$cart->user_id);
				if(!empty($addresses) && is_array($addresses)) {
					$address = reset($addresses);
					$cart->cart_shipping_address_ids = (int)$address->address_id;
				}
			}

			if(!empty($address)) {
				$cart->shipping_address = $address;
				$zone_id = $this->extractZone($address, $zones, $zone_id);
				if($this->config->get('tax_zone_type', 'shipping') == 'shipping')
					$tax_zone_id = $zone_id;
			} else {
				$cart->cart_shipping_address_ids = 0;
			}
			unset($address);

			if(empty($zone_id)) {
				$zone_id = $this->extractZone(null, $zones, true);
				$tax_zone_id = $zone_id;
			}

			$cart->package['zone'] = $zone_id;

			if(!empty($cart->billing_address) || !empty($cart->shipping_address)) {
				$addressArray = array();
				if(!empty($cart->billing_address))
					$addressArray[] =& $cart->billing_address;
				if(!empty($cart->shipping_address) && !is_array($cart->shipping_address))
					$addressArray[] =& $cart->shipping_address;
				if(!empty($cart->shipping_address) && is_array($cart->shipping_address)) {
					foreach($cart->shipping_address	as &$addr) {
						$addressArray[] =& $addr;
					}
					unset($addr);
				}
				$addressClass->loadZone($addressArray, 'parent');

				if(!empty($addressClass->fields))
					$cart->address_fields = $addressClass->fields;
			}
		}

		if($cart->cart_type == 'wishlist' && !empty($cart->user_id) && (empty($this->user) || $cart->user_id != $this->user->user_id)) {
			$userClass = hikashop_get('class.user');
			$user = $userClass->get($cart->user_id);
			$cart->user = new stdClass();
			$cart->user->user_id = (int)$user->user_id;
			$cart->user->user_cms_id = (int)$user->user_cms_id;
			$cart->user->user_email = $user->user_email;
			if(!empty($user->username))
				$cart->user->username = $user->username;
			if(!empty($user->email))
				$cart->user->email = $user->email;
		}

		if(empty($cart->cart_products)) {
			$p = new stdClass();
			$p->price_value_with_tax = 0;
			$p->price_value = 0;
			$p->price_currency_id = 0;
			$cart->full_total->prices = array(
				0 => $p
			);
			$cart->products = array();

			return $cart;
		}

		$currencyClass = hikashop_get('class.currency');
		$productClass = hikashop_get('class.product');
		$main_currency = (int)$this->config->get('main_currency', 1);
		$currency_id = hikashop_getCurrency();
		$quantityDisplayType = null;

		if(!in_array($currency_id, $currencyClass->publishedCurrencies()))
			$currency_id = $main_currency;

		$filters = array(
			'cart_product.cart_id = '.(int)$cart->cart_id,
			'cart_product.product_id > 0'
		);
		hikashop_addACLFilters($filters, 'product_access', 'product');

		$query = 'SELECT cart_product.cart_product_id, cart_product.cart_product_quantity, cart_product.cart_product_option_parent_id, cart_product.cart_product_parent_id, cart_product.cart_product_wishlist_id, product.* '.
			' FROM ' . hikashop_table('cart_product').' AS cart_product '.
			' LEFT JOIN ' . hikashop_table('product').' AS product ON cart_product.product_id = product.product_id '.
			' WHERE (' . implode(') AND (', $filters) . ') '.
			' ORDER BY cart_product.cart_product_id ASC';
		$this->db->setQuery($query);
		$cart->products = $this->db->loadObjectList('cart_product_id');

		$parent_product_ids = array();
		foreach($cart->products as $product) {
			if(!empty($product->product_parent_id))
				$parent_product_ids[$product->cart_product_id] = (int)$product->product_parent_id;
		}
		$parent_products = null;
		if(!empty($parent_product_ids)) {
			$query = 'SELECT product.* '.
				' FROM ' .  hikashop_table('product').' AS product '.
				' WHERE product.product_id IN ('.implode(',', $parent_product_ids) .') AND product.product_type = ' . $this->db->Quote('main');
			$this->db->setQuery($query);
			$parent_products = $this->db->loadObjectList('product_id');

			foreach($parent_product_ids as $k => $v) {
				if(!isset($parent_products[$v]))
					continue;
				$p = clone($parent_products[$v]);
				if(!isset($p->cart_product_id)) {
					$p->cart_product_id = 'p'.$k;
					$p->cart_product_quantity = 0;
					$p->cart_product_option_parent_id = 0;
					$p->cart_product_parent_id = 0;
				}

				$cart->products['p'.$k] = $p;
				$cart->products[$k]->cart_product_parent_id = 'p'.$k;
				unset($p);
			}
		}

		$checkCart = $this->checkCartQuantities($cart, $parent_products);

		$updateCart = false;
		foreach($cart->products as $cart_product_id => $product) {
			if(empty($product->product_id))
				continue;

			if(empty($product->cart_product_quantity) && empty($product->old->quantity))
				continue;

			if(isset($product->old->quantity) && (int)$product->old->quantity != (int)$product->cart_product_quantity) {
				$cart->messages[] = array(
					'msg' => JText::sprintf('PRODUCT_QUANTITY_CHANGED', $product->product_name, $product->old->quantity, (int)$product->cart_product_quantity),
					'product_id' => (int)$product->product_id,
					'type' => 'notice'
				);
				$updateCart = true;

				if(isset($cart->cart_products[$cart_product_id]) && $cart->cart_products[$cart_product_id]->cart_product_quantity != $product->cart_product_quantity && $cart->cart_products[$cart_product_id]->cart_product_quantity == $product->old->quantity)
					$cart->cart_products[$cart_product_id]->cart_product_quantity = $product->cart_product_quantity;
			}

		}
		if($updateCart) {
			$this->save($cart);
		}

		$ids = array();
		$mainIds = array();
		foreach($cart->products as $product) {
			$ids[] = (int)$product->product_id;
			$mainIds[] = (empty($product->product_parent_id) || (int)$product->product_parent_id == 0) ? (int)$product->product_id : (int)$product->product_parent_id;
		}

		if(!empty($ids)) {
			$query = 'SELECT * FROM '.hikashop_table('file').
				' WHERE file_ref_id IN (' . implode(',', $ids) . ') AND file_type IN (' . $this->db->Quote('product') . ',' . $this->db->Quote('file') . ') '.
				' ORDER BY file_ref_id ASC, file_ordering ASC, file_id ASC';
			$this->db->setQuery($query);
			$images = $this->db->loadObjectList();
			if(!empty($images)) {
				foreach($cart->products as &$product) {
					$productClass->addFiles($product, $images);
				}
			}
		}

		if(!empty($mainIds)) {
			$query = 'SELECT product_category.*, category.* '.
				' FROM ' . hikashop_table('product_category') . ' AS product_category '.
				' LEFT JOIN ' . hikashop_table('category') . ' AS category ON product_category.category_id = category.category_id '.
				' WHERE product_category.product_id IN (' . implode(',', $mainIds) . ')'.
				' ORDER BY product_category.ordering ASC';
			$this->db->setQuery($query);
			$categories = $this->db->loadObjectList();
		}

		$product_quantities = array();

		foreach($cart->products as &$product) {
			$product->categories = array();
			if(!empty($categories)) {
				foreach($categories as $category) {
					if($category->product_id == $product->product_id)
						$product->categories[] = $category;
				}
			}

			if(!empty($product->product_parent_id) && isset($parent_products[ (int)$product->product_parent_id ]))
				$product->parent_product = $parent_products[ (int)$product->product_parent_id ];

			if(empty($product_quantities[$product->product_id]))
				$product_quantities[$product->product_id] = 0;
			$product_quantities[$product->product_id] += (int)@$product->cart_product_quantity;

			if($product->product_parent_id > 0) {
				if(empty($product_quantities[$product->product_parent_id]))
					$product_quantities[$product->product_parent_id] = 0;
				$product_quantities[$product->product_parent_id] += (int)@$product->cart_product_quantity;
			}

			if($product->product_parent_id != 0 && isset($product->main_product_quantity_layout))
				$product->product_quantity_layout = $product->main_product_quantity_layout;

			if(empty($product->product_quantity_layout) || $product->product_quantity_layout == 'inherit') {
				$product->product_quantity_layout = $this->config->get('product_quantity_display', 'show_default');
				if(!empty($product->categories) ) {
					if(empty($quantityDisplayType))
						$quantityDisplayType = hikashop_get('type.quantitydisplay');
					foreach($product->categories as $category) {
						if(!empty($category->category_quantity_layout) && $quantityDisplayType->check($category->category_quantity_layout, $this->app->getTemplate())) {
							$product->product_quantity_layout = $category->category_quantity_layout;
							break;
						}
					}
				}
			}

			if($product->product_type == 'variant') {
				foreach($cart->products as &$product2) {
					if((int)$product->product_parent_id != (int)$product2->product_id)
						continue;

					if(!isset($product2->variants))
						$product2->variants = array();
					$product2->variants[] =& $product;
					break;
				}
				unset($product2);
			}
		}
		unset($product);
		unset($categories);

		if(!empty($ids)) {
			$query = 'SELECT hk_variant.*, hk_characteristic.* '.
				' FROM '.hikashop_table('variant').' AS hk_variant '.
				' LEFT JOIN '.hikashop_table('characteristic').' AS hk_characteristic ON hk_variant.variant_characteristic_id = hk_characteristic.characteristic_id '.
				' WHERE hk_variant.variant_product_id IN (' . implode(',', $ids) . ') '.
				' ORDER BY hk_variant.ordering, hk_characteristic.characteristic_value';
			$this->db->setQuery($query);
			$characteristics = $this->db->loadObjectList();
		}
		if(!empty($characteristics)) {
			foreach($cart->products as &$product) {

				$mainCharacteristics = array();
				foreach($characteristics as $characteristic) {
					if($product->product_id == $characteristic->variant_product_id) {
						if(empty($mainCharacteristics[$product->product_id]))
							$mainCharacteristics[$product->product_id] = array();
						if(empty($mainCharacteristics[$product->product_id][$characteristic->characteristic_parent_id]))
							$mainCharacteristics[$product->product_id][$characteristic->characteristic_parent_id] = array();
						$mainCharacteristics[$product->product_id][$characteristic->characteristic_parent_id][$characteristic->characteristic_id] = $characteristic;

						if($product->product_type === 'variant') {
							if(empty($product->characteristics))
								$product->characteristics = array();
							$product->characteristics[] = $characteristic;
						}
					}
					if(!empty($product->options)) {
						foreach($product->options as $optionElement) {
							if((int)$optionElement->product_id != (int)$characteristic->variant_product_id)
								continue;

							if(empty($mainCharacteristics[$optionElement->product_id]))
								$mainCharacteristics[$optionElement->product_id] = array();
							if(empty($mainCharacteristics[$optionElement->product_id][$characteristic->characteristic_parent_id]))
								$mainCharacteristics[$optionElement->product_id][$characteristic->characteristic_parent_id] = array();
							$mainCharacteristics[$optionElement->product_id][$characteristic->characteristic_parent_id][$characteristic->characteristic_id] = $characteristic;
						}
					}
				}

				if($product->product_type === 'variant')
					continue;

				JPluginHelper::importPlugin('hikashop');
				$dispatcher = JDispatcher::getInstance();
				$dispatcher->trigger('onAfterProductCharacteristicsLoad', array( &$product, &$mainCharacteristics, &$characteristics ) );

				if(!empty($element->variants)) {
					$this->addCharacteristics($product, $mainCharacteristics, $characteristics);
				}

				if(!empty($product->options)) {
					foreach($product->options as &$optionElement) {
						if(!empty($optionElement->variants)) {
							$this->addCharacteristics($optionElement, $mainCharacteristics, $characteristics);
						}
					}
					unset($optionElement);
				}
			}
			unset($product);
		}


		foreach($cart->products as &$product) {
			$product->cart_product_total_quantity = $product_quantities[$product->product_id];
			if($product->product_parent_id > 0)
				$product->cart_product_total_variants_quantity = $product_quantities[$product->product_parent_id];
			else
				$product->cart_product_total_variants_quantity = $product->cart_product_total_quantity;
		}
		unset($product);

		$cart_product_ids = array_merge($ids, $parent_product_ids);
		$cart_products = array();
		foreach($cart->products as &$product) {
			if(!isset($product->parent_product))
				$cart_products[] =& $product;
		}
		unset($product);

		$currencyClass->getPrices($cart_products, $cart_product_ids, $currency_id, $main_currency, $tax_zone_id, $discount_before_tax);

		unset($cart_products);

		foreach($cart->products as &$product) {
			if(empty($product->variants))
				continue;
			foreach($product->variants as &$variant) {
				$productClass->checkVariant($variant, $product);
			}
			unset($variant);
		}
		unset($product);

		foreach($cart->products as &$product) {
			if(empty($product->parent_product))
				continue;
			$productClass->checkVariant($product, $product->parent_product);
		}
		unset($product);

		if(!$this->config->get('display_add_to_cart_for_free_products', 0)) {
			$notUsable = array();
			foreach($cart->products as $cart_product_id => $product) {
				if(empty($product->product_id) || empty($product->cart_product_quantity) || !empty($product->prices))
					continue;

				$notUsable[$cart_product_id] = array('id' => $cart_product_id, 'qty' => 0);
				$cart->messages[] = array('msg' => JText::sprintf('PRODUCT_NOT_AVAILABLE', $product->product_name), 'product_id' => $product->product_id, 'type' => 'notice');
			}
			if(!empty($notUsable)) {
				$saveCart = $this->updateProduct($cart, $notUsable);
				if($saveCart)
					$this->save($cart);
			}
		}

		foreach($cart->products as &$product) {
			{ # stop rsvp pro from redirecting if rsvptransactionid is not found
				if(isset(self::$cache['get'][$cart_id]->cart_products[$product->cart_product_id]->rsvptransactionid)) {
					$product->rsvptransactionid = self::$cache['get'][$cart_id]->cart_products[$product->cart_product_id]->rsvptransactionid;
				}
			}
			$currencyClass->calculateProductPriceForQuantity($product);
		}
		unset($product);

		$currencyClass->calculateTotal($cart->products, $cart->total, $currency_id);
		$cart->full_total =& $cart->total;

		JPluginHelper::importPlugin('hikashop');
		JPluginHelper::importPlugin('hikashoppayment');
		JPluginHelper::importPlugin('hikashopshipping');
		$dispatcher = JDispatcher::getInstance();
		if ( (int) AC()->param->get( 'enable_hikashop_couponprocess_onAfterCartProductsLoad', 0 ) === 1 ) {
			$dispatcher->trigger('onAfterCartProductsLoad', array( &$cart ) ); # comment out or infinite loop			
		}

		if(!empty($cart->additional)) {
			$currencyClass->addAdditionals($cart->additional, $cart->additional_total, $cart->full_total, $currency_id);
			$cart->full_total =& $cart->additional_total;
		}

		$cart->package = $this->getWeightVolume($cart);
		$cart->quantity = new stdClass();
		$cart->quantity->total = $cart->package['total_quantity'];
		$cart->quantity->items = $cart->package['total_items'];

		$this->calculateWeightAndVolume($cart);

		if(!empty($cart->cart_type) && $cart->cart_type == 'wishlist' && !empty($cart->cart_coupon))
			$cart->cart_coupon = '';

		if(!empty($this->cart->cart_coupon)) {
			if(is_string($cart->cart_coupon))
				$cart->cart_coupon = explode("\r\n", $cart->cart_coupon);

			$discountClass = hikashop_get('class.discount');
			foreach($cart->cart_coupon as $k => $coupon) {
				$discount = $discountClass->load($coupon);
				if(empty($discount->discount_auto_load)) {
					$current_auto_coupon_key = $this->generateHash($cart->products, $zone_id);
					$previous_auto_coupon_key = $this->app->getUserState(HIKASHOP_COMPONENT.'.auto_coupon_key');
					if($current_auto_coupon_key != $previous_auto_coupon_key)
						unset($cart->cart_coupon[$k]);
				}
			}
		}


		if(!empty($cart->cart_coupon) && $cart->cart_type != 'wishlist') {
			if(empty($discountClass))
				$discountClass = hikashop_get('class.discount');
			if(empty($zones)) {
				$zoneClass = hikashop_get('class.zone');
				$zones = $zoneClass->getZoneParents($zone_id);
			}

			if(is_array($cart->cart_coupon) && count($cart->cart_coupon) == 1)
				$cart->cart_coupon = reset($cart->cart_coupon);

			//$cart->coupon = $discountClass->loadAndCheck($cart->cart_coupon, $cart->full_total, $zones, $cart->products, true);
			//
			//if(empty($cart->coupon) && hikashop_level(1) && $this->loadAutoCoupon($cart)) {
			//	$cart->coupon = $discountClass->loadAndCheck($cart->cart_coupon, $cart->full_total, $zones, $cart->products, true);
			//}
			// commented out so we dont end up in infinite loop

			if(!empty($cart->coupon))
				$cart->full_total =& $cart->coupon->total;
			else
				$cart->cart_coupon = array();
		}

		$this->checkNegativeTax($cart->full_total);

		$cart->shipping = null;
		$force_shipping = (int)$this->config->get('force_shipping', 0);
		if($cart->cart_type != 'wishlist' && ($force_shipping || $cart->package['weight']['value'] > 0)) {
			if(!empty($cart->cart_shipping_ids))
				$cart->cart_shipping_ids = explode(',', $cart->cart_shipping_ids);
			else
				$cart->cart_shipping_ids = array();

			$shippingClass = hikashop_get('class.shipping');
			$cart->usable_methods->shipping = $shippingClass->getShippings($cart, true);

			if(empty($cart->usable_methods->shipping) && !empty($shippingClass->errors)) {
				$cart->usable_methods->shipping_errors = $shippingClass->errors;
			}

			$checkShipping = $shippingClass->checkCartMethods($cart, true);
			if(!$checkShipping) {
				$query = 'UPDATE '.hikashop_table('cart').' SET cart_shipping_ids = '.$this->db->Quote(implode(',', $cart->cart_shipping_ids)).' WHERE cart_id = '.(int)$cart_id;
				$this->db->setQuery($query);
				$this->db->query();
				if(isset(self::$cache['get'][$cart_id]))
					self::$cache['get'][$cart_id]->cart_shipping_ids = implode(',', $cart->cart_shipping_ids);
			}

			$currencyClass->processShippings($cart->usable_methods->shipping, $cart, $zone_id);

			$cart->shipping = array();
			foreach($cart->cart_shipping_ids as $k => $shipping_id) {
				$warehouse_struct = array();
				if(strpos($shipping_id, '@') !== false) {
					list($shipping_id, $warehouse_id) = explode('@', $shipping_id, 2);
					if(preg_match_all('#([a-zA-Z])*([0-9]+)#iu', $warehouse_id, $keys))
						$warehouse_struct = array_combine($keys[1], $keys[2]);
					if(is_numeric($warehouse_id))
						$warehouse_id = (int)$warehouse_id;
				} else {
					$shipping_id = $shipping_id;
					$warehouse_id = 0;
				}
				$f = false;
				foreach($cart->usable_methods->shipping as $shipping) {
					if($shipping->shipping_id != $shipping_id)
						continue;

					if(((is_string($warehouse_id) || is_int($warehouse_id)) && $warehouse_id === $shipping->shipping_warehouse_id) || (is_array($shipping->shipping_warehouse_id) && $shipping->shipping_warehouse_id === $warehouse_struct)) {
						$cart->shipping[] = $shipping;
						$f = true;
						break;
					}
				}
				if(!$f)
					unset($cart->cart_shipping_ids[$k]);
			}

			$cart->usable_methods->shipping_valid = true;
			if(empty($cart->shipping_groups) || count($cart->shipping_groups) == 1) {
				$cart->usable_methods->shipping_valid = empty($cart->usable_methods->shipping_errors);
			} else {
				foreach($cart->shipping_groups as $group) {
					if(empty($group->shippings) && !empty($group->errors)) {
						$cart->usable_methods->shipping_valid = false;
						break;
					}
				}
			}

			if(!empty($cart->shipping))
				$cart->full_total =& $currencyClass->addShipping($cart->shipping, $cart->full_total);
		} else {
			$cart->cart_shipping_ids = '';
			$cart->usable_methods->shipping_valid = true;
		}

		$before_additional = !empty($cart->additional);

		//$dispatcher->trigger('onAfterCartShippingLoad', array( &$cart ) ); # comment out or infinite loop

		if(!$before_additional && !empty($cart->additional)) {
			$currencyClass->addAdditionals($cart->additional, $cart->additional_total, $cart->full_total, $currency_id);
			$cart->full_total =& $cart->additional_total;
		}

		$cart->payment = null;
		if($cart->cart_type != 'wishlist' && !empty($cart->full_total->prices[0]) && $cart->full_total->prices[0]->price_value_with_tax > 0.0) {
			$paymentClass = hikashop_get('class.payment');
			$cart->usable_methods->payment = $paymentClass->getPayments($cart);

			if(empty($cart->usable_methods->payment) && !empty($paymentClass->errors)) {
				$cart->usable_methods->payment_errors = $paymentClass->errors;
			}

			$checkPayment = $paymentClass->checkCartMethods($cart, true);
			if(!$checkPayment) {
				$query = 'UPDATE '.hikashop_table('cart').' SET cart_payment_id = '.(int)$cart->cart_payment_id.' WHERE cart_id = '.(int)$cart_id;
				$this->db->setQuery($query);
				$this->db->query();
				if(isset(self::$cache['get'][$cart_id]))
					self::$cache['get'][$cart_id]->cart_payment_id = (int)$cart->cart_payment_id;
			}

			$currencyClass->processPayments($cart->usable_methods->payment, $zone_id);

			if(empty($cart->cart_payment_id) && !empty($cart->usable_methods->payment)) {
				$firstPayment = reset($cart->usable_methods->payment);
				$cart_payment_id = (int)$firstPayment->payment_id;
				unset($firstPayment);

				$dispatcher->trigger('onHikaShopCartSelectPayment', array( $cart, &$cart_payment_id ));

				$cart->cart_payment_id = (int)$cart_payment_id;
			}

			if(!empty($cart->cart_payment_id)) {
				foreach($cart->usable_methods->payment as $payment) {
					if($payment->payment_id == $cart->cart_payment_id) {
						$cart->payment = $payment;
						break;
					}
				}
			}

			if(!empty($cart->payment)) {
				$price_all = @$cart->full_total->prices[0]->price_value_with_tax;
				if(isset($cart->full_total->prices[0]->price_value_without_payment_with_tax))
					$price_all = $cart->full_total->prices[0]->price_value_without_payment_with_tax;

				$payment_price = $cart->payment->payment_price;
				if(isset($cart->payment->payment_price_without_percentage))
					$payment_price = $cart->payment->payment_price_without_percentage;

				$cart->payment->payment_price = $paymentClass->computePrice($cart, $cart->payment, $price_all, $payment_price, (int)$cart->cart_currency_id);
				if(isset($cart->payment->payment_tax))
					$cart->full_total->prices[0]->payment_tax = $cart->payment->payment_tax;

				$currencyClass->addPayment($cart->payment, $cart->full_total);
			}
		} else
			$cart->usable_methods->payment_valid = true;

		$this->checkNegativeTax($cart->full_total);

		$dispatcher->trigger('onAfterFullCartLoad', array( &$cart ) );

		self::$cache['full'][$cart_id] =& $cart;
		return $this->getCloneCache('full', $cart_id);
	}



	private function refreshHikashopCartProducts2x() {
		$cartclass = hikashop_get('class.cart');
		$keepEmptyCart = false;
		
		$cart_products = $cartclass->get( @$cartclass->cart->cart_id, $keepEmptyCart,  isset($cartclass->cart->cart_type)?$cartclass->cart->cart_type:'');
		if(empty($cart_products)) {
			return;
		}
		
		$app = JFactory::getApplication();
		$database	= JFactory::getDBO();
		$config =& hikashop_config();
		$currencyClass = hikashop_get('class.currency');
		$productClass = hikashop_get('class.product');
		$main_currency = (int)$config->get('main_currency',1);
		$currency_id = hikashop_getCurrency();
		$zone_id = hikashop_getZone('shipping');
		$tax_zone_id = $config->get('tax_zone_type','shipping')=='billing' ? hikashop_getZone('billing') : $zone_id;
		$discount_before_tax = (int)$config->get('discount_before_tax',0);
		
		$ids = array();
		$mainIds = array();
		foreach($cart_products as $product){
			$ids[]=$product->product_id;
			if($product->product_parent_id == '0')
				$mainIds[]=(int)$product->product_id;
			else
				$mainIds[]=(int)$product->product_parent_id;
		}


		

		foreach($cart_products as $k => $row){
			if($row->product_type=='variant'){
				foreach($cart_products as $k2 => $row2){
					if($row->product_parent_id==$row2->product_id){
						$cart_products[$k2]->variants[]=&$cart_products[$k];
						break;
					}
				}
			}
		}

		$query = 'SELECT a.*,b.* FROM '.hikashop_table('variant').' AS a LEFT JOIN '.hikashop_table('characteristic').' AS b ON a.variant_characteristic_id=b.characteristic_id WHERE a.variant_product_id IN ('.implode(',',$ids).') ORDER BY a.ordering,b.characteristic_value';
		$database->setQuery($query);
		$characteristics = $database->loadObjectList();
		if(!empty($characteristics)){
			foreach($cart_products as $key => $product){
				if($product->product_type!='variant'){
					$element =& $cart_products[$key];
					$product_id=$product->product_id;
					$mainCharacteristics = array();
					foreach($characteristics as $characteristic){
						if($product_id==$characteristic->variant_product_id){
							$mainCharacteristics[$product_id][$characteristic->characteristic_parent_id][$characteristic->characteristic_id]=$characteristic;
						}
						if(!empty($element->options)){
							foreach($element->options as $k => $optionElement){
								if($optionElement->product_id==$characteristic->variant_product_id){
									$mainCharacteristics[$optionElement->product_id][$characteristic->characteristic_parent_id][$characteristic->characteristic_id]=$characteristic;
								}
							}
						}
					}

					JPluginHelper::importPlugin('hikashop');
					$dispatcher = JDispatcher::getInstance();
					$dispatcher->trigger('onAfterProductCharacteristicsLoad', array( &$element, &$mainCharacteristics, &$characteristics ) );

					if(!empty($element->variants)){
						$cartclass->addCharacteristics($element,$mainCharacteristics,$characteristics);
					}

					if(!empty($element->options)){
						foreach($element->options as $k => $optionElement){
							if(!empty($optionElement->variants)){
								$cartclass->addCharacteristics($element->options[$k],$mainCharacteristics,$characteristics);
							}
						}
					}
				}
			}
		}

		$product_quantities = array();
		foreach($cart_products as $row){
			if(empty($product_quantities[$row->product_id])){
				$product_quantities[$row->product_id] = (int)@$row->cart_product_quantity;
			}else{
				$product_quantities[$row->product_id]+=(int)@$row->cart_product_quantity;
			}
			if(empty($product_quantities[$row->product_parent_id])){
				$product_quantities[$row->product_parent_id] = (int)@$row->cart_product_quantity;
			}else{
				$product_quantities[$row->product_parent_id] += (int)@$row->cart_product_quantity;
			}
		}
		foreach($cart_products as $k => $row){
			$cart_products[$k]->cart_product_total_quantity = $product_quantities[$row->product_id];
			if($row->product_parent_id){
				$cart_products[$k]->cart_product_total_variants_quantity = $product_quantities[$row->product_parent_id];
			}else{
				$cart_products[$k]->cart_product_total_variants_quantity = $cart_products[$k]->cart_product_total_quantity;
			}
		}

		$currencyClass->getPrices($cart_products,$ids,$currency_id,$main_currency,$tax_zone_id,$discount_before_tax);
		foreach($cart_products as $k => $row){
			if(!empty($row->variants)){
				foreach($row->variants as $k2 => $variant){
					$productClass->checkVariant($cart_products[$k]->variants[$k2],$row);
				}
			}
		}

		$group = $config->get('group_options',0);
		foreach($cart_products as $k => $row){
			unset($cart_products[$k]->cart_modified);
			unset($cart_products[$k]->cart_coupon);

			$currencyClass->calculateProductPriceForQuantity($cart_products[$k]);
		}

		$cart_total = null;
		$currencyClass->calculateTotal($cart_products, $cart_total, $currency_id);
	
	
	
		//$this->order_total =& $cart_total;
		//$this->cart_products = $cart_products;


		$cart = new stdClass();
		$cart->products = &$cart_products;
		$cart->cart_id = (int)@$cartclass->cart->cart_id;
		$cart->cart_type = @$cartclass->cart->cart_type;
		$cart->cart_params = @$cartclass->cart->cart_params;
		$cart->coupon = null;
		$cart->shipping = null;
		$cart->total = &$cart_total;
		$cart->full_total = &$cart_total;
		$cart->additional = array();
		$cart->cmcoupon_bypass = true;

		JPluginHelper::importPlugin('hikashop');
		JPluginHelper::importPlugin('hikashoppayment');
		JPluginHelper::importPlugin('hikashopshipping');
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('onAfterCartProductsLoad', array( &$cart ) );

		return $cart;

	}

}