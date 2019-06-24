/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/


ALTER TABLE #__cmcoupon_asset1 ADD COLUMN `count` INT UNSIGNED AFTER asset_id;
ALTER TABLE #__cmcoupon_asset2 ADD COLUMN `count` INT UNSIGNED AFTER asset_id;

ALTER TABLE #__cmcoupon MODIFY function_type VARCHAR(255) NOT NULL DEFAULT 'coupon';
UPDATE #__cmcoupon SET function_type='buyxy' WHERE function_type='buy_x_get_y';
