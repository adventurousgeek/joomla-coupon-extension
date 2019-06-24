/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/


ALTER TABLE #__cmcoupon_giftcert_order DROP PRIMARY KEY;
ALTER TABLE #__cmcoupon_giftcert_order ADD COLUMN id INT UNSIGNED NOT NULL PRIMARY KEY auto_increment AFTER order_id;
ALTER TABLE #__cmcoupon_giftcert_order MODIFY order_id INT UNSIGNED NOT NULL AFTER Id;
ALTER TABLE #__cmcoupon_giftcert_order ADD UNIQUE INDEX (order_id,estore);


CREATE TABLE IF NOT EXISTS #__cmcoupon_giftcert_order_code (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`giftcert_order_id` INT UNSIGNED NOT NULL,
	`order_item_id` INT UNSIGNED NOT NULL,
	`product_id` INT UNSIGNED NOT NULL,
	`coupon_id` INT UNSIGNED NOT NULL,
	`code` VARCHAR(255) NOT NULL,
	`recipient_user_id` INT UNSIGNED,
	PRIMARY KEY  (`id`)
);

/* PHP:cmcouponinstall_UPGRADE_228(); */;
