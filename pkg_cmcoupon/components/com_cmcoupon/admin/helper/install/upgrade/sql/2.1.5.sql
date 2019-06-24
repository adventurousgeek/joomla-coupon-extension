/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 

/* PHP:cmcouponinstall_UPGRADE_215(); */;

ALTER TABLE #__cmcoupon MODIFY function_type enum('coupon','shipping','buy_x_get_y','parent','giftcert') NOT NULL DEFAULT 'coupon';
UPDATE #__cmcoupon SET function_type='shipping' WHERE function_type2='shipping';
UPDATE #__cmcoupon SET function_type='buy_x_get_y' WHERE function_type2='buy_x_get_y';
UPDATE #__cmcoupon SET function_type='parent' WHERE function_type2='parent';
