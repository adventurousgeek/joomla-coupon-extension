/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

ALTER TABLE #__cmcoupon_history ADD COLUMN is_customer_balance TINYINT(1);

CREATE TABLE IF NOT EXISTS #__cmcoupon_image (
	`coupon_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`filename` varchar(255),
	PRIMARY KEY  (`coupon_id`,`user_id`)
);


CREATE TABLE IF NOT EXISTS #__cmcoupon_giftcert_product (
	`id` int(16) NOT NULL auto_increment,
	`estore` VARCHAR(255) NOT NULL,
	`product_id` INT NOT NULL,
	`coupon_template_id` INT NOT NULL,
	`profile_id` INT,
	`price_calc_type` ENUM('product_price_notax','product_price') DEFAULT NULL,
	`expiration_number` INT,
	`expiration_type` ENUM('day','month','year'),
	`vendor_name` VARCHAR(255),
	`vendor_email` VARCHAR(255),
	`coupon_code_prefix` VARCHAR(255),
	`coupon_code_suffix` VARCHAR(255),
	`published` TINYINT NOT NULL DEFAULT 1,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_giftcert_code (
	`id` int(16) NOT NULL auto_increment,
	`estore` VARCHAR(255) NOT NULL,
	`product_id` INT NOT NULL,
	`code` VARCHAR(255) BINARY NOT NULL default '',
	`status` ENUM('active','inactive','used') NOT NULL DEFAULT 'active',
	`note` TEXT,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_customer_balance (
	`id` int(16) NOT NULL auto_increment,
	`user_id` int(16) NOT NULL,
	`coupon_id` int(16) NOT NULL,
	`initial_balance` decimal(20,5) NOT NULL,
	`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY  (`id`),
	UNIQUE KEY  (`coupon_id`)
); 


CREATE TABLE IF NOT EXISTS #__cmcoupon_voucher_customer (
	`id` int(16) NOT NULL auto_increment,
	`estore` VARCHAR(255) NOT NULL,
	`user_id` int(11) NOT NULL,
	`order_id` int(11),
	`codes` text,
	PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_voucher_customer_code (
	`id` int(16) NOT NULL auto_increment,
	`voucher_customer_id` int(16) NOT NULL,
	`coupon_id` int(16) NOT NULL,
	`code` VARCHAR(255) NOT NULL,
	`recipient_user_id` INT,
	`order_item_id` int(16) NOT NULL,
	`product_id` int(16) NOT NULL,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_license (
	`id` VARCHAR(100) NOT NULL,
	`value` TEXT,
	PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS #__cmcoupon_profile (
	`id` int(16) NOT NULL auto_increment,
	`title` VARCHAR(255) NOT NULL,
	`is_default` TINYINT(1),
	`from_name` VARCHAR(255),
	`from_email` VARCHAR(255),
	`bcc_admin` TINYINT(1),
	`cc_purchaser` TINYINT(1),
	`idlang_email_subject` INT,
	`idlang_email_body` INT,
	`image` VARCHAR(255),
	`coupon_code_config` TEXT,
	`coupon_value_config` TEXT,
	`idlang_voucher_text` INT,
	`idlang_voucher_text_exp` INT,
	`idlang_voucher_filename` INT,
	`expiration_config` TEXT,
	`freetext1_config` TEXT,
	`freetext2_config` TEXT,
	`freetext3_config` TEXT,
	`is_pdf` TINYINT(1),
	`idlang_pdf_header` INT,
	`idlang_pdf_body` INT,
	`idlang_pdf_footer` INT,
	`idlang_pdf_filename` INT,
	PRIMARY KEY  (`id`)
);

INSERT INTO #__cmcoupon_profile (title,idlang_email_subject,image,coupon_code_config,coupon_value_config,expiration_config,freetext1_config,freetext2_config,idlang_email_body,idlang_voucher_text,idlang_voucher_text_exp,idlang_voucher_filename)
VALUES 
	("Christmas",6,"christmas.png",
		'{"align":"R","pad":"10","y":"72","font":"arialbd.ttf","size":"16","color":"#FFFF00"}',
		'{"align":"L","pad":"50","y":"110","font":"arialbd.ttf","size":"25","color":"#FFFF00"}',
		'{"text":"F j Y","align":"C","pad":"","y":"270","font":"arialbd.ttf","size":"14","color":"#FFA500"}',
		'{"text":"CODE:","align":"R","pad":"75","y":"50","font":"arialbd.ttf","size":"14","color":"#FFA500"}',
		'{"text":"www.yourwebsite.com","align":"C","pad":"","y":"200","font":"arialbd.ttf","size":"25","color":"#FFFAFA"}',
		10, 14, 18, 22
	),
	("Flower",7,"flower.png",
		'{"align":"C","pad":"","y":"280","font":"arialbd.ttf","size":"14","color":"#000000"}',
		'{"align":"C","pad":"","y":"250","font":"arialbd.ttf","size":"25","color":"#000000"}',
		NULL,
		'{"text":"www.yourwebsite.com","align":"C","pad":"","y":"30","font":"arialbd.ttf","size":"25","color":"#FFD700"}',
		'{"text":"Thank you!","align":"C","pad":"","y":"70","font":"arialbd.ttf","size":"20","color":"#FF69B4"}',
		11, 15, 19, 23
	),
	("Brown",8,"brown.png",
		'{"align":"R","pad":"20","y":"50","font":"arialbd.ttf","size":"18","color":"#FFFFFF"}',
		'{"align":"L","pad":"20","y":"50","font":"arialbd.ttf","size":"25","color":"#FFFFFF"}',
		'{"text":"j F Y","align":"R","pad":"50","y":"80","font":"arialbd.ttf","size":"15","color":"#F0F8FF"}',
		'{"text":"GIFT CARD","align":"C","pad":"","y":"260","font":"arialbd.ttf","size":"30","color":"#000000"}',
		'{"text":"www.yourwebsite.com","align":"C","pad":"","y":"180","font":"arialbd.ttf","size":"30","color":"#8B0000"}',
		12, 16, 20, 24
	),
	("Snowman",9,"snowman.png",
		'{"align":"R","pad":"30","y":"60","font":"arialbd.ttf","size":"14","color":"#000000"}',
		'{"align":"L","pad":"30","y":"60","font":"arialbd.ttf","size":"22","color":"#000000"}',
		'{"text":"j M Y","align":"R","pad":"30","y":"90","font":"arialbd.ttf","size":"14","color":"#000000"}',
		'{"text":"www.yourwebsite.com","align":"C","pad":"","y":"170","font":"arialbd.ttf","size":"32","color":"#0000FF"}',
		'{"text":"Enjoy your shopping with us!","align":"C","pad":"","y":"260","font":"arialbd.ttf","size":"20","color":"#B22222"}',
		13, 17, 21, 25
	)
;
UPDATE #__cmcoupon_profile SET is_default=1 WHERE id=3;


INSERT INTO #__cmcoupon_lang_text (elem_id,lang,text) 
VALUES 
	(6,"en-GB","Ordered Gift Certificate(s)"),
	(7,"en-GB","Ordered Gift Certificate(s)"),
	(8,"en-GB","Ordered Gift Certificate(s)"),
	(9,"en-GB","Ordered Gift Certificate(s)")
;
INSERT INTO #__cmcoupon_lang_text (elem_id,lang,text) 
VALUES 
	(10,"en-GB","Included is your gift certificate valid towards all products at {siteurl}.<br />Simply enter the code from your gift certificate in the coupon code entry form during checkout. Enjoy shopping!<br /><br />Thank you,<br />{store_name}"),
	(11,"en-GB","Included is your gift certificate valid towards all products at {siteurl}.<br />Simply enter the code from your gift certificate in the coupon code entry form during checkout. Enjoy shopping!<br /><br />Thank you,<br />{store_name}"),
	(12,"en-GB","Included is your gift certificate valid towards all products at {siteurl}.<br />Simply enter the code from your gift certificate in the coupon code entry form during checkout. Enjoy shopping!<br /><br />Thank you,<br />{store_name}"),
	(13,"en-GB","Included is your gift certificate valid towards all products at {siteurl}.<br />Simply enter the code from your gift certificate in the coupon code entry form during checkout. Enjoy shopping!<br /><br />Thank you,<br />{store_name}")
;

INSERT INTO #__cmcoupon_lang_text (elem_id,lang,text) 
VALUES 
	(14,"en-GB","Gift Certificate: {voucher}<br />Value: {price}{expiration_text}<br /><br />"),
	(15,"en-GB","Gift Certificate: {voucher}<br />Value: {price}{expiration_text}<br /><br />"),
	(16,"en-GB","Gift Certificate: {voucher}<br />Value: {price}{expiration_text}<br /><br />"),
	(17,"en-GB","Gift Certificate: {voucher}<br />Value: {price}{expiration_text}<br /><br />")
;

INSERT INTO #__cmcoupon_lang_text (elem_id,lang,text) 
VALUES 
	(18,"en-GB","<br />Expiration: {expiration}"),
	(19,"en-GB","<br />Expiration: {expiration}"),
	(20,"en-GB","<br />Expiration: {expiration}"),
	(21,"en-GB","<br />Expiration: {expiration}")
;

INSERT INTO #__cmcoupon_lang_text (elem_id,lang,text) 
VALUES 
	(22,"en-GB","voucher#"),
	(23,"en-GB","voucher#"),
	(24,"en-GB","voucher#"),
	(25,"en-GB","voucher#")
;

