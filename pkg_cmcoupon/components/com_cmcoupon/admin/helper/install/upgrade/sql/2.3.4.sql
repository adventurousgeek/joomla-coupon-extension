/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

 
 
CREATE TABLE IF NOT EXISTS #__cmcoupon_cron (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`coupon_id` varchar(32) NOT NULL default '',
	`user_id` INT UNSIGNED NOT NULL,
	`type` varchar(255),
	`status` VARCHAR(200),
	`notes` TEXT,
	`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY  (`id`)
);
