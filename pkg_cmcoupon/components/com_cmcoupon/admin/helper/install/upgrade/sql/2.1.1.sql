/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
CREATE TABLE IF NOT EXISTS #__cmcoupon_auto (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`coupon_id` varchar(32) NOT NULL default '',
	`ordering` INT NULL,
	`published` TINYINT NOT NULL DEFAULT 1,
	PRIMARY KEY  (`id`)
);
