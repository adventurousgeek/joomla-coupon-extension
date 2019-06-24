/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
CREATE TABLE IF NOT EXISTS #__cmcoupon_lang_text (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`elem_id` INT UNSIGNED NOT NULL,
	`lang` varchar(32) NOT NULL default '',
	`text` TEXT,
	PRIMARY KEY  (`id`)
);

ALTER TABLE #__cmcoupon_profile ADD COLUMN `email_subject_lang_id` INT UNSIGNED AFTER bcc_admin;
ALTER TABLE #__cmcoupon_profile ADD COLUMN `email_body_lang_id` INT UNSIGNED AFTER email_subject_lang_id;


/* PHP:cmcouponinstall_UPGRADE_212(); */;
