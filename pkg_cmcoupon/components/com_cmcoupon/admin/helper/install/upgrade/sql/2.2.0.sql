/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
CREATE TABLE IF NOT EXISTS #__cmcoupon_image (
	`coupon_id` INT UNSIGNED NOT NULL,
	`user_id` INT UNSIGNED NOT NULL,
	`filename` varchar(255),
	PRIMARY KEY  (`coupon_id`)
);

ALTER TABLE #__cmcoupon_config ADD COLUMN `is_json` TINYINT AFTER `name`;
ALTER TABLE #__cmcoupon_giftcert_order ADD COLUMN user_id INT UNSIGNED NOT NULL AFTER estore;

/* PHP:cmcouponinstall_UPGRADE_220(); */;
