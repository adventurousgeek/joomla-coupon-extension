/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

ALTER TABLE #__cmcoupon_giftcert_product ADD COLUMN coupon_code_prefix VARCHAR(255) AFTER vendor_email;
ALTER TABLE #__cmcoupon_giftcert_product ADD COLUMN coupon_code_suffix VARCHAR(255) AFTER coupon_code_prefix;
