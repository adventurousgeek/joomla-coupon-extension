/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/



CREATE TABLE IF NOT EXISTS #__cmcoupon_tag (
	`coupon_id` INT UNSIGNED NOT NULL,
	`tag` VARCHAR(255) NOT NULL,
	PRIMARY KEY  (`coupon_id`,`tag`)
);

ALTER TABLE #__cmcoupon_lang_text ADD UNIQUE INDEX (elem_id,lang);
