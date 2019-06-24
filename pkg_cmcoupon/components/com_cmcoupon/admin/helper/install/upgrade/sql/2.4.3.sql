/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

ALTER TABLE #__cmcoupon_profile ADD COLUMN voucher_filename_lang_id INT UNSIGNED AFTER voucher_text_exp_lang_id;
ALTER TABLE #__cmcoupon_profile ADD COLUMN pdf_filename_lang_id INT UNSIGNED AFTER pdf_footer_lang_id;
