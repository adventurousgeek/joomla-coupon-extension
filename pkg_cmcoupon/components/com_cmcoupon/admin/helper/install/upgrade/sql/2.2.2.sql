/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
ALTER TABLE #__cmcoupon_history ADD COLUMN `details` TEXT;

/* PHP:cmcouponinstall_UPGRADE_222(); */;
