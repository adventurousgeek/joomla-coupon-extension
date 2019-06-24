/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/


ALTER TABLE #__cmcoupon_profile CHANGE email_subject_lang_id idlang_email_subject INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile CHANGE email_body_lang_id idlang_email_body INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile CHANGE voucher_text_lang_id idlang_voucher_text INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile CHANGE voucher_text_exp_lang_id idlang_voucher_text_exp INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile CHANGE voucher_filename_lang_id idlang_voucher_filename INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile CHANGE pdf_header_lang_id idlang_pdf_header INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile CHANGE pdf_body_lang_id idlang_pdf_body INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile CHANGE pdf_footer_lang_id idlang_pdf_footer INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile CHANGE pdf_filename_lang_id idlang_pdf_filename INT UNSIGNED;


UPDATE #__cmcoupon_config SET name=CONCAT('idlang_',SUBSTRING(name,1,LENGTH(name)-8)) WHERE name LIKE '%_lang_id' AND LENGTH(name)>8;


