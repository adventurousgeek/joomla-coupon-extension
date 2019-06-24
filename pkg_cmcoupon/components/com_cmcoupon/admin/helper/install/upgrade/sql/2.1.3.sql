/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
ALTER TABLE #__cmcoupon ADD COLUMN passcode VARCHAR(10) AFTER coupon_code;
ALTER TABLE #__cmcoupon_history ADD COLUMN user_email VARCHAR(255) AFTER user_id;
UPDATE #__cmcoupon SET passcode=SUBSTRING(MD5(CONCAT(UNIX_TIMESTAMP(),FLOOR(1+RAND()*1000),coupon_code)),1,6);
