/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

 
 
ALTER TABLE #__cmcoupon_giftcert_product ADD COLUMN `price_calc_type` ENUM('product_price_notax','product_price') DEFAULT NULL AFTER profile_id;
