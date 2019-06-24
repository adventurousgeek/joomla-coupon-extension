/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/


ALTER TABLE #__cmcoupon MODIFY coupon_value_type enum('percent','amount','amount_per') DEFAULT NULL;
UPDATE #__cmcoupon SET coupon_value_type='amount_per' WHERE function_type='buy_x_get_y' AND coupon_value_type='amount';
