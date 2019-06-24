/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/


ALTER TABLE #__cmcoupon_asset1 MODIFY asset_type enum('coupon','product','category','manufacturer','vendor','shipping','country','countrystate','paymentmethod') NOT NULL;

ALTER TABLE #__cmcoupon MODIFY coupon_value decimal(20,5);
ALTER TABLE #__cmcoupon MODIFY min_value decimal(20,5);
ALTER TABLE #__cmcoupon_history MODIFY coupon_discount DECIMAL(20,5) DEFAULT 0 NOT NULL;
ALTER TABLE #__cmcoupon_history MODIFY shipping_discount DECIMAL(20,5) DEFAULT 0 NOT NULL;
ALTER TABLE #__cmcoupon_customer_balance MODIFY initial_balance decimal(20,5) NOT NULL;