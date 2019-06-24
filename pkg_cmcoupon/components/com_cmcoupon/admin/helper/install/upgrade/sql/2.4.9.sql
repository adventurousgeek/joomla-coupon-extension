/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/


ALTER TABLE #__cmcoupon ADD INDEX coupon_code (coupon_code);

ALTER TABLE #__cmcoupon_history ADD INDEX coupon_id_user_id (coupon_id,user_email);
ALTER TABLE #__cmcoupon_history ADD INDEX coupon_entered_id_user_id (coupon_entered_id,user_email);
ALTER TABLE #__cmcoupon_history ADD INDEX order_id (order_id);
ALTER TABLE #__cmcoupon_history ADD INDEX user_id (user_id);
ALTER TABLE #__cmcoupon_history ADD INDEX session_id (session_id);
ALTER TABLE #__cmcoupon_history ADD INDEX user_email (user_email);
