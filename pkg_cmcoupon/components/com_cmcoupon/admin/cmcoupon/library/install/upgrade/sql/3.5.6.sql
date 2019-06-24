/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

ALTER TABLE #__cmcoupon_asset MODIFY coupon_id INT NOT NULL;
ALTER TABLE #__cmcoupon_asset ADD INDEX coupon_id (coupon_id);
