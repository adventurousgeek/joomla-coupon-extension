/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
 CREATE TABLE IF NOT EXISTS #__cmcoupon_asset1 (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`coupon_id` varchar(32) NOT NULL default '',
	`asset_type` enum('coupon','product','category','manufacturer','vendor','shipping') NOT NULL,
	`asset_id` INT UNSIGNED NOT NULL,
	`order_by` INT NULL,
	PRIMARY KEY  (`id`)
);
CREATE TABLE IF NOT EXISTS #__cmcoupon_asset2 (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`coupon_id` varchar(32) NOT NULL default '',
	`asset_type` enum('coupon','product','category','manufacturer','vendor','shipping') NOT NULL,
	`asset_id` INT UNSIGNED NOT NULL,
	`order_by` INT NULL,
	PRIMARY KEY  (`id`)
);

ALTER TABLE `#__cmcoupon` MODIFY `function_type2` enum('product','category','manufacturer','vendor','shipping','parent','buy_x_get_y') AFTER `function_type`;
INSERT INTO #__cmcoupon_asset1 (coupon_id,asset_type,asset_id)
		SELECT coupon_id,"product",product_id 
		  FROM #__cmcoupon_product p
		  JOIN #__cmcoupon c ON c.id=p.coupon_id
		 WHERE function_type2="product";
INSERT INTO #__cmcoupon_asset2 (coupon_id,asset_type,asset_id)
		SELECT coupon_id,"product",product_id 
		  FROM #__cmcoupon_product p
		  JOIN #__cmcoupon c ON c.id=p.coupon_id
		 WHERE function_type2="shipping";
INSERT INTO #__cmcoupon_asset1 (coupon_id,asset_type,asset_id) SELECT coupon_id,"category",category_id FROM #__cmcoupon_category;
INSERT INTO #__cmcoupon_asset1 (coupon_id,asset_type,asset_id) SELECT coupon_id,"manufacturer",manufacturer_id FROM #__cmcoupon_manufacturer;
INSERT INTO #__cmcoupon_asset1 (coupon_id,asset_type,asset_id) SELECT coupon_id,"vendor",vendor_id FROM #__cmcoupon_vendor;
INSERT INTO #__cmcoupon_asset1 (coupon_id,asset_type,asset_id) SELECT coupon_id,"shipping",shipping_rate_id FROM #__cmcoupon_shipping;
INSERT INTO #__cmcoupon_asset1 (coupon_id,asset_type,asset_id,order_by) SELECT parent_coupon_id,"coupon",coupon_id,order_by FROM #__cmcoupon_children;




DROP TABLE IF EXISTS #__cmcoupon_children;
DROP TABLE IF EXISTS #__cmcoupon_product;
DROP TABLE IF EXISTS #__cmcoupon_category;
DROP TABLE IF EXISTS #__cmcoupon_manufacturer;
DROP TABLE IF EXISTS #__cmcoupon_vendor;
DROP TABLE IF EXISTS #__cmcoupon_shipping;

