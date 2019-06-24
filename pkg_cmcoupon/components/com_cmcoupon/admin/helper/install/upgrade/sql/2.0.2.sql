/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
ALTER TABLE #__cmcoupon_vm MODIFY parent_type ENUM('first','lowest','highest','all','allonly');
ALTER TABLE #__cmcoupon_vm_giftcert_product ADD COLUMN `coupon_template_id` INT UNSIGNED NOT NULL AFTER `product_id`;
ALTER TABLE #__cmcoupon_vm_giftcert_product DROP COLUMN `coupon_value`;
ALTER TABLE #__cmcoupon_vm_giftcert_product DROP COLUMN `exclude_giftcert`;
DELETE FROM #__cmcoupon_vm_giftcert_product;
ALTER TABLE #__cmcoupon_vm_profile DROP COLUMN `min_code_len`;
ALTER TABLE #__cmcoupon_vm_profile DROP COLUMN `max_code_len`;
