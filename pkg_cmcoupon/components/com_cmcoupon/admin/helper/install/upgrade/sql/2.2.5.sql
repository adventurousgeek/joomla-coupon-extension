/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
ALTER TABLE #__cmcoupon ADD COLUMN `num_of_uses_total` INT UNSIGNED AFTER num_of_uses;
ALTER TABLE #__cmcoupon ADD COLUMN `num_of_uses_customer` INT UNSIGNED AFTER num_of_uses_total;
UPDATE #__cmcoupon SET num_of_uses_total=num_of_uses WHERE num_of_uses_type="total";
UPDATE #__cmcoupon SET num_of_uses_customer=num_of_uses WHERE num_of_uses_type="per_user";
ALTER TABLE #__cmcoupon DROP COLUMN num_of_uses_type;
ALTER TABLE #__cmcoupon DROP COLUMN num_of_uses;

/* PHP:cmcouponinstall_UPGRADE_225(); */;
