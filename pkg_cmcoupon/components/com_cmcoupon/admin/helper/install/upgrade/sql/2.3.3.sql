/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

 
 
RENAME TABLE #__cmcoupon_giftcert_order TO #__cmcoupon_voucher_customer;
ALTER TABLE #__cmcoupon_voucher_customer DROP COLUMN email_sent;
ALTER TABLE #__cmcoupon_voucher_customer MODIFY order_id INT UNSIGNED AFTER user_id;
ALTER TABLE #__cmcoupon_voucher_customer DROP INDEX order_id;

RENAME TABLE #__cmcoupon_giftcert_order_code TO #__cmcoupon_voucher_customer_code;
ALTER TABLE #__cmcoupon_voucher_customer_code CHANGE giftcert_order_id voucher_customer_id INT UNSIGNED NOT NULL;

CREATE TABLE IF NOT EXISTS #__cmcoupon_customer_balance (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`user_id` INT UNSIGNED NOT NULL,
	`coupon_id` INT UNSIGNED NOT NULL,
	`initial_balance` decimal(12,5) NOT NULL,
	`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY  (`id`),
	UNIQUE KEY  (`coupon_id`)
); 

ALTER TABLE #__cmcoupon_history ADD COLUMN is_customer_balance TINYINT AFTER coupon_entered_id;

