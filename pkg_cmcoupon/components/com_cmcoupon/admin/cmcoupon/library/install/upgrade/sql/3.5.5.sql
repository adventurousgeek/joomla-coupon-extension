/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

ALTER TABLE #__cmcoupon_history CHANGE coupon_discount total_product decimal(20,5) NOT NULL DEFAULT 0 AFTER order_id;
ALTER TABLE #__cmcoupon_history CHANGE shipping_discount total_shipping decimal(20,5) NOT NULL DEFAULT 0 AFTER total_product;

ALTER TABLE #__cmcoupon_history ADD COLUMN currency_code VARCHAR(3) NOT NULL AFTER total_shipping;
ALTER TABLE #__cmcoupon_history ADD COLUMN total_curr_product DECIMAL(20,5) NOT NULL DEFAULT 0 AFTER currency_code;
ALTER TABLE #__cmcoupon_history ADD COLUMN total_curr_shipping DECIMAL(20,5) NOT NULL DEFAULT 0 AFTER total_curr_product;

UPDATE #__cmcoupon_history SET total_curr_product=total_product, total_curr_shipping=total_shipping;

/* PHP:Cmcoupon_Library_Install_Upgrade_Php_355(); */;
