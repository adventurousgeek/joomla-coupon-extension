/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
ALTER TABLE #__cmcoupon_profile ADD COLUMN `voucher_text_lang_id` INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile ADD COLUMN `voucher_text_exp_lang_id` INT UNSIGNED;
