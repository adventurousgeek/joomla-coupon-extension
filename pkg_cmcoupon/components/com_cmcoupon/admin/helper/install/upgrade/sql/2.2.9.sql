/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/


ALTER TABLE #__cmcoupon_asset1 MODIFY asset_type enum('coupon','product','category','manufacturer','vendor','shipping','country','countrystate') NOT NULL;
