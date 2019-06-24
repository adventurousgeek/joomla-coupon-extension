/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

ALTER TABLE #__cmcoupon_image DROP PRIMARY KEY;
ALTER TABLE #__cmcoupon_image ADD PRIMARY KEY (coupon_id, user_id);

