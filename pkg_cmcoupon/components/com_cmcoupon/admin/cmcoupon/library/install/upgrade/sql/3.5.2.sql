/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

ALTER TABLE #__cmcoupon MODIFY `state` ENUM('published', 'unpublished', 'template', 'balance') NOT NULL DEFAULT 'published';

ALTER TABLE #__cmcoupon_giftcert_product ADD COLUMN from_name_id VARCHAR(255) AFTER coupon_code_suffix;
ALTER TABLE #__cmcoupon_giftcert_product ADD COLUMN recipient_email_id VARCHAR(255) AFTER from_name_id;
ALTER TABLE #__cmcoupon_giftcert_product ADD COLUMN recipient_name_id VARCHAR(255) AFTER recipient_email_id;
ALTER TABLE #__cmcoupon_giftcert_product ADD COLUMN recipient_mesg_id VARCHAR(255) AFTER recipient_name_id;
