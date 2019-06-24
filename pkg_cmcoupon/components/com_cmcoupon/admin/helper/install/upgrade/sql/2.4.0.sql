/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

ALTER TABLE #__cmcoupon MODIFY estore enum('virtuemart','redshop','hikashop','virtuemart1','eshop') NOT NULL;
ALTER TABLE #__cmcoupon_history MODIFY estore enum('virtuemart','redshop','hikashop','virtuemart1','eshop') NOT NULL;
ALTER TABLE #__cmcoupon_giftcert_product MODIFY estore enum('virtuemart','redshop','hikashop','virtuemart1','eshop') NOT NULL;
ALTER TABLE #__cmcoupon_giftcert_code MODIFY estore enum('virtuemart','redshop','hikashop','virtuemart1','eshop') NOT NULL;
ALTER TABLE #__cmcoupon_voucher_customer MODIFY estore enum('virtuemart','redshop','hikashop','virtuemart1','eshop') NOT NULL;

