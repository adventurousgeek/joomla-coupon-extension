/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/


/* PHP:cmcouponinstall_UPGRADE_200(); */;

CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_config (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`name` VARCHAR(255) NOT NULL,
	`value` TEXT,
	PRIMARY KEY  (`id`),
	UNIQUE (`name`)
);
CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_license (
	`id` VARCHAR(100) NOT NULL,
	`value` TEXT,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_children (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`parent_coupon_id` INT UNSIGNED NOT NULL,
	`coupon_id` INT UNSIGNED NOT NULL,
	`order_by` INT NOT NULL,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_category (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`coupon_id` varchar(32) NOT NULL default '',
	`category_id` INT UNSIGNED NOT NULL,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_manufacturer (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`coupon_id` varchar(32) NOT NULL default '',
	`manufacturer_id` INT UNSIGNED NOT NULL,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_vendor (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`coupon_id` varchar(32) NOT NULL default '',
	`vendor_id` INT UNSIGNED NOT NULL,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_shipping (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`coupon_id` varchar(32) NOT NULL default '',
	`shipping_rate_id` INT UNSIGNED NOT NULL,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_usergroup (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`coupon_id` varchar(32) NOT NULL default '',
	`shopper_group_id` INT UNSIGNED NOT NULL,
	PRIMARY KEY  (`id`)
);


CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_profile (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`title` VARCHAR(255) NOT NULL,
	`is_default` TINYINT,
	`min_code_len` INT UNSIGNED NOT NULL DEFAULT 8,
	`max_code_len` INT UNSIGNED NOT NULL DEFAULT 12,
	`from_name` VARCHAR(255),
	`from_email` VARCHAR(255),
	`bcc_admin` TINYINT,
	`email_subject` VARCHAR(255),
	`message_type` ENUM ('text','html') NOT NULL DEFAULT 'text',
	`image` VARCHAR(255),
	`coupon_code_config` TEXT,
	`coupon_value_config` TEXT,
	`expiration_config` TEXT,
	`freetext1_config` TEXT,
	`freetext2_config` TEXT,
	`freetext3_config` TEXT,
	`email_body` TEXT,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_giftcert_product (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`product_id` INT UNSIGNED NOT NULL,
	`profile_id` INT UNSIGNED,
	`coupon_value` decimal(12,5) NOT NULL,
	`expiration_number` INT UNSIGNED,
	`expiration_type` ENUM('day','month','year'),
	`exclude_giftcert` TINYINT,
	`published` TINYINT NOT NULL DEFAULT 1,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_giftcert_code (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`product_id` INT UNSIGNED NOT NULL,
	`code` VARCHAR(255) BINARY NOT NULL default '',
	`status` ENUM('active','inactive','used') NOT NULL DEFAULT 'active',
	`note` TEXT,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_vm_giftcert_order (
	`order_id` INT UNSIGNED NOT NULL,
	`email_sent` tinyint NOT NULL DEFAULT '0',
	`codes` text,
	PRIMARY KEY (`order_id`)
);


 

 
 
 


ALTER TABLE `#__cmcoupon_vm` MODIFY `coupon_value_type` VARCHAR(50) DEFAULT NULL AFTER `coupon_code`;
UPDATE `#__cmcoupon_vm` SET `coupon_value_type`='amount' WHERE `coupon_value_type`='total';
ALTER TABLE `#__cmcoupon_vm` MODIFY `coupon_value_type` enum('percent','amount') DEFAULT NULL AFTER `coupon_code`;
ALTER TABLE `#__cmcoupon_vm` MODIFY `coupon_value` decimal(12,5) AFTER `coupon_value_type`;
ALTER TABLE `#__cmcoupon_vm` ADD COLUMN `coupon_value_def` TEXT AFTER `coupon_value`;
ALTER TABLE `#__cmcoupon_vm` MODIFY `function_type` ENUM('coupon','giftcert') NOT NULL DEFAULT 'coupon' AFTER `coupon_value_def`;
ALTER TABLE `#__cmcoupon_vm` ADD COLUMN `num_of_uses_type` ENUM('total','per_user') AFTER `function_type2`;
ALTER TABLE `#__cmcoupon_vm` MODIFY `num_of_uses` INT UNSIGNED AFTER `num_of_uses_type`;
ALTER TABLE `#__cmcoupon_vm` MODIFY `min_value` decimal(12,5) AFTER `num_of_uses`;
ALTER TABLE `#__cmcoupon_vm` MODIFY `discount_type` enum('specific','overall') AFTER `min_value`;
ALTER TABLE `#__cmcoupon_vm` ADD COLUMN `function_type2_mode` ENUM('include','exclude') AFTER `discount_type`;
ALTER TABLE `#__cmcoupon_vm` MODIFY `startdate` DATETIME AFTER `function_type2_mode`;
ALTER TABLE `#__cmcoupon_vm` MODIFY `expiration` DATETIME AFTER `startdate`;
ALTER TABLE `#__cmcoupon_vm` MODIFY `published` TINYINT NOT NULL DEFAULT 1 AFTER `expiration`;
UPDATE `#__cmcoupon_vm` SET `num_of_uses_type`='total' WHERE `function_type`='giftcert';
UPDATE `#__cmcoupon_vm` SET `num_of_uses_type`='per_user' WHERE `function_type`='coupon' AND `num_of_uses`>0;
UPDATE `#__cmcoupon_vm` SET `function_type`='coupon';
UPDATE `#__cmcoupon_vm` SET `num_of_uses`=NULL WHERE `num_of_uses`=0;
UPDATE `#__cmcoupon_vm` SET `min_value`=NULL WHERE `min_value`=0;
UPDATE `#__cmcoupon_vm` SET `function_type2_mode`='include' WHERE `discount_type`='specific';
ALTER TABLE `#__cmcoupon_vm` ADD COLUMN `user_type` enum('user','usergroup') AFTER discount_type;
UPDATE `#__cmcoupon_vm` SET `user_type`='user' WHERE `function_type`='coupon';
ALTER TABLE `#__cmcoupon_vm` MODIFY `coupon_code` VARCHAR(32) BINARY NOT NULL DEFAULT '';
ALTER TABLE `#__cmcoupon_vm` ADD COLUMN `order_id` INT UNSIGNED AFTER expiration;
ALTER TABLE `#__cmcoupon_vm` ADD COLUMN `exclude_special` TINYINT AFTER expiration;
ALTER TABLE `#__cmcoupon_vm` ADD COLUMN `exclude_giftcert` TINYINT AFTER exclude_special;
ALTER TABLE #__cmcoupon_vm ADD COLUMN `parent_type` enum('first','lowest','highest','all') DEFAULT NULL AFTER coupon_code;
ALTER TABLE #__cmcoupon_vm ADD COLUMN `note` TEXT;
ALTER TABLE #__cmcoupon_vm ADD COLUMN `template_id` INT UNSIGNED AFTER order_id;
		
ALTER TABLE `#__cmcoupon_vm_history` DROP PRIMARY KEY;
ALTER TABLE `#__cmcoupon_vm_history` ADD COLUMN `id` INT UNSIGNED NOT NULL PRIMARY KEY auto_increment AFTER `coupon_id`;
ALTER TABLE `#__cmcoupon_vm_history` MODIFY `coupon_id` varchar(32) NOT NULL default '' AFTER `id`;
ALTER TABLE `#__cmcoupon_vm_history` ADD COLUMN `coupon_discount` DECIMAL(12,5) DEFAULT 0 NOT NULL AFTER `num`;
ALTER TABLE `#__cmcoupon_vm_history` ADD COLUMN `shipping_discount` DECIMAL(12,5) DEFAULT 0 NOT NULL AFTER `coupon_discount`;
ALTER TABLE `#__cmcoupon_vm_history` ADD COLUMN `order_id` INT UNSIGNED DEFAULT NULL AFTER `shipping_discount`;
ALTER TABLE `#__cmcoupon_vm_history` ADD COLUMN `productids` TEXT AFTER `order_id`;
ALTER TABLE `#__cmcoupon_vm_history` ADD COLUMN `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE #__cmcoupon_vm_history ADD COLUMN coupon_entered_id VARCHAR(32) AFTER coupon_id;
UPDATE #__cmcoupon_vm_history SET coupon_entered_id=coupon_id;

		
		
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;
UPDATE `#__cmcoupon_vm_history` SET `num`=`num`-1;
INSERT INTO `#__cmcoupon_vm_history` (`coupon_id`, `user_id`,`timestamp`) SELECT `coupon_id`, `user_id`,'0000-00-00 00:00:00' FROM #__cmcoupon_vm_history WHERE num>0;


ALTER TABLE `#__cmcoupon_vm_history` DROP COLUMN num;






INSERT INTO #__cmcoupon_vm_profile (title,is_default,min_code_len,max_code_len,email_subject,message_type,email_body)
	VALUES ("Profile 1",1,8,12,"Ordered Gift Certificate(s)","text",
			"Included is your gift certificate valid towards all products at {siteurl}.\r\nSimply enter the code from your gift certificate in the coupon code entry form during checkout. Enjoy shopping!\r\n\r\n{vouchers}\r\n\r\nThank you,\r\n{store_name}"
		);
			
INSERT INTO #__cmcoupon_vm_profile (title,min_code_len,max_code_len,email_subject,message_type,image,coupon_code_config,coupon_value_config,expiration_config,freetext1_config,freetext2_config,email_body)
	VALUES ("Christmas",8,12,"Ordered Gift Certificate(s)","html","christmas.png",
			'a:6:{s:5:"align";s:1:"R";s:3:"pad";s:2:"10";s:1:"y";s:2:"72";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"16";s:5:"color";s:7:"#FFFF00";}',
			'a:6:{s:5:"align";s:1:"L";s:3:"pad";s:2:"50";s:1:"y";s:3:"110";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"25";s:5:"color";s:7:"#FFFF00";}',
			'a:7:{s:4:"text";s:5:"F j Y";s:5:"align";s:1:"C";s:3:"pad";s:0:"";s:1:"y";s:3:"270";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"14";s:5:"color";s:7:"#FFA500";}',
			'a:7:{s:4:"text";s:5:"CODE:";s:5:"align";s:1:"R";s:3:"pad";s:2:"75";s:1:"y";s:2:"50";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"14";s:5:"color";s:7:"#FFA500";}',
			'a:7:{s:4:"text";s:19:"www.yourwebsite.com";s:5:"align";s:1:"C";s:3:"pad";s:0:"";s:1:"y";s:3:"200";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"25";s:5:"color";s:7:"#FFFAFA";}',
			"Included is your gift certificate valid towards all products at {siteurl}.<br />Simply enter the code from your gift certificate in the coupon code entry form during checkout. Enjoy shopping!<br /><br />Thank you,<br />{store_name}"
		),
		("Flower",8,12,"Ordered Gift Certificate(s)","html","flower.png",
			'a:6:{s:5:"align";s:1:"C";s:3:"pad";s:0:"";s:1:"y";s:3:"280";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"14";s:5:"color";s:7:"#000000";}',
			'a:6:{s:5:"align";s:1:"C";s:3:"pad";s:0:"";s:1:"y";s:3:"250";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"25";s:5:"color";s:7:"#000000";}',
			NULL,
			'a:7:{s:4:"text";s:19:"www.yourwebsite.com";s:5:"align";s:1:"C";s:3:"pad";s:0:"";s:1:"y";s:2:"30";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"25";s:5:"color";s:7:"#FFD700";}',
			'a:7:{s:4:"text";s:10:"Thank you!";s:5:"align";s:1:"C";s:3:"pad";s:0:"";s:1:"y";s:2:"70";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"20";s:5:"color";s:7:"#FF69B4";}',
			"Included is your gift certificate valid towards all products at {siteurl}.<br />Simply enter the code from your gift certificate in the coupon code entry form during checkout. Enjoy shopping!<br /><br />Thank you,<br />{store_name}"
		),
		("Brown",8,12,"Ordered Gift Certificate(s)","html","brown.png",
			'a:6:{s:5:"align";s:1:"R";s:3:"pad";s:2:"20";s:1:"y";s:2:"50";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"18";s:5:"color";s:7:"#FFFFFF";}',
			'a:6:{s:5:"align";s:1:"L";s:3:"pad";s:2:"20";s:1:"y";s:2:"50";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"25";s:5:"color";s:7:"#FFFFFF";}',
			'a:7:{s:4:"text";s:5:"j F Y";s:5:"align";s:1:"R";s:3:"pad";s:2:"50";s:1:"y";s:2:"80";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"15";s:5:"color";s:7:"#F0F8FF";}',
			'a:7:{s:4:"text";s:9:"GIFT CARD";s:5:"align";s:1:"C";s:3:"pad";s:0:"";s:1:"y";s:3:"260";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"30";s:5:"color";s:7:"#000000";}',
			'a:7:{s:4:"text";s:19:"www.yourwebsite.com";s:5:"align";s:1:"C";s:3:"pad";s:0:"";s:1:"y";s:3:"180";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"30";s:5:"color";s:7:"#8B0000";}',
			"Included is your gift certificate valid towards all products at {siteurl}.<br />Simply enter the code from your gift certificate in the coupon code entry form during checkout. Enjoy shopping!<br /><br />Thank you,<br />{store_name}"
		),
		("Snowman",8,12,"Ordered Gift Certificate(s)","html","snowman.png",
			'a:6:{s:5:"align";s:1:"R";s:3:"pad";s:2:"30";s:1:"y";s:2:"60";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"14";s:5:"color";s:7:"#000000";}',
			'a:6:{s:5:"align";s:1:"L";s:3:"pad";s:2:"30";s:1:"y";s:2:"60";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"22";s:5:"color";s:7:"#000000";}',
			'a:7:{s:4:"text";s:5:"j M Y";s:5:"align";s:1:"R";s:3:"pad";s:2:"30";s:1:"y";s:2:"90";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"14";s:5:"color";s:7:"#000000";}',
			'a:7:{s:4:"text";s:19:"www.yourwebsite.com";s:5:"align";s:1:"C";s:3:"pad";s:0:"";s:1:"y";s:3:"170";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"32";s:5:"color";s:7:"#0000FF";}',
			'a:7:{s:4:"text";s:28:"Enjoy your shopping with us!";s:5:"align";s:1:"C";s:3:"pad";s:0:"";s:1:"y";s:3:"260";s:4:"font";s:11:"arialbd.ttf";s:4:"size";s:2:"20";s:5:"color";s:7:"#B22222";}',
			"Included is your gift certificate valid towards all products at {siteurl}.<br />Simply enter the code from your gift certificate in the coupon code entry form during checkout. Enjoy shopping!<br /><br />Thank you,<br />{store_name}"
		)
	;
