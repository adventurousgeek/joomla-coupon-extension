/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
ALTER TABLE `#__cmcoupon_vm_giftcert_product` ADD COLUMN `vendor_name` VARCHAR(255) AFTER expiration_type;
ALTER TABLE `#__cmcoupon_vm_giftcert_product` ADD COLUMN `vendor_email` VARCHAR(255) AFTER vendor_name;
