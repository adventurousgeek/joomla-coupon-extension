/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

RENAME TABLE 
	#__cmcoupon_vm TO #__cmcoupon,
	#__cmcoupon_vm_config TO #__cmcoupon_config,
	#__cmcoupon_vm_license TO #__cmcoupon_license,
	#__cmcoupon_vm_children TO #__cmcoupon_children,
	#__cmcoupon_vm_product TO #__cmcoupon_product,
	#__cmcoupon_vm_category TO #__cmcoupon_category,
	#__cmcoupon_vm_manufacturer TO #__cmcoupon_manufacturer,
	#__cmcoupon_vm_vendor TO #__cmcoupon_vendor,
	#__cmcoupon_vm_shipping TO #__cmcoupon_shipping,
	#__cmcoupon_vm_user TO #__cmcoupon_user,
	#__cmcoupon_vm_usergroup TO #__cmcoupon_usergroup,
	#__cmcoupon_vm_history TO #__cmcoupon_history,
	#__cmcoupon_vm_profile TO #__cmcoupon_profile,
	#__cmcoupon_vm_giftcert_product TO #__cmcoupon_giftcert_product,
	#__cmcoupon_vm_giftcert_code TO #__cmcoupon_giftcert_code,
	#__cmcoupon_vm_giftcert_order TO #__cmcoupon_giftcert_order
	;

/* PHP:cmcouponinstall_UPGRADE_208(); */;


ALTER TABLE `#__cmcoupon` ADD COLUMN `estore` enum('virtuemart','redshop') NOT NULL AFTER id;
ALTER TABLE `#__cmcoupon_history` ADD COLUMN `estore` enum('virtuemart','redshop') NOT NULL AFTER id;
ALTER TABLE `#__cmcoupon_giftcert_product` ADD COLUMN `estore` enum('virtuemart','redshop') NOT NULL AFTER id;
ALTER TABLE `#__cmcoupon_giftcert_code` ADD COLUMN `estore` enum('virtuemart','redshop') NOT NULL AFTER id;
ALTER TABLE `#__cmcoupon_giftcert_order` ADD COLUMN `estore` enum('virtuemart','redshop') NOT NULL AFTER order_id;
