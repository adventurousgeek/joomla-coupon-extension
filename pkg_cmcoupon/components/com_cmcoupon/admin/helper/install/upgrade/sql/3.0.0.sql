/**
 * CmCoupon
 *
 * @package Joomla CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

DELETE FROM #__cmcoupon_license;

ALTER TABLE #__cmcoupon MODIFY `estore` VARCHAR(255) NOT NULL;
ALTER TABLE #__cmcoupon ADD COLUMN `state` ENUM('published', 'unpublished', 'template', 'balance') NOT NULL DEFAULT 'published' AFTER template_id;
UPDATE #__cmcoupon SET state="published" WHERE published=1;
UPDATE #__cmcoupon SET state="unpublished" WHERE published=-1;
UPDATE #__cmcoupon SET state="template" WHERE published=-2;
UPDATE #__cmcoupon SET state="balance" WHERE published=-3;
UPDATE #__cmcoupon SET function_type='combination' WHERE function_type='parent';

CREATE TABLE `#__cmcoupon_asset` (
	`id` int(16) NOT NULL AUTO_INCREMENT,
	`coupon_id` varchar(32) NOT NULL DEFAULT '',
	`asset_key` INT NOT NULL DEFAULT 0,
	`asset_type` enum('user','usergroup','coupon','product','category','manufacturer','vendor','shipping','country','countrystate','paymentmethod') NOT NULL,
	`asset_id` varchar(255) not null,
	`qty` int(11) DEFAULT NULL,
	`order_by` int(11) DEFAULT NULL,
	PRIMARY KEY (`id`)
);
INSERT INTO #__cmcoupon_asset (coupon_id,asset_key,asset_type,asset_id,qty,order_by) SELECT coupon_id,100,asset_type,asset_id,`count`,order_by FROM #__cmcoupon_asset1;
INSERT INTO #__cmcoupon_asset (coupon_id,asset_key,asset_type,asset_id,qty,order_by) SELECT coupon_id,200,asset_type,asset_id,`count`,order_by FROM #__cmcoupon_asset2;
INSERT INTO #__cmcoupon_asset (coupon_id,asset_key,asset_type,asset_id) SELECT coupon_id,0,'user',user_id FROM #__cmcoupon_user;
INSERT INTO #__cmcoupon_asset (coupon_id,asset_key,asset_type,asset_id) SELECT coupon_id,0,'usergroup',shopper_group_id FROM #__cmcoupon_usergroup;
DROP TABLE #__cmcoupon_asset1;
DROP TABLE #__cmcoupon_asset2;
DROP TABLE #__cmcoupon_user;
DROP TABLE #__cmcoupon_usergroup;

ALTER TABLE #__cmcoupon_history MODIFY `estore` VARCHAR(255) NOT NULL;

ALTER TABLE #__cmcoupon_giftcert_product MODIFY `estore` VARCHAR(255) NOT NULL;

ALTER TABLE #__cmcoupon_giftcert_code MODIFY `estore` VARCHAR(255) NOT NULL;

ALTER TABLE #__cmcoupon_voucher_customer MODIFY `estore` VARCHAR(255) NOT NULL;

ALTER TABLE #__cmcoupon_profile DROP COLUMN `message_type`;

