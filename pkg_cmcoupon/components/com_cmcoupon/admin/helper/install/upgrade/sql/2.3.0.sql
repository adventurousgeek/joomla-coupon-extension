/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/


ALTER TABLE #__cmcoupon_profile ADD COLUMN is_pdf TINYINT;
ALTER TABLE #__cmcoupon_profile ADD COLUMN pdf_header_lang_id INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile ADD COLUMN pdf_body_lang_id INT UNSIGNED;
ALTER TABLE #__cmcoupon_profile ADD COLUMN pdf_footer_lang_id INT UNSIGNED;
