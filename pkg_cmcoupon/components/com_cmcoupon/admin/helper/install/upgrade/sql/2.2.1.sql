/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/
 
ALTER TABLE #__cmcoupon MODIFY `estore` enum("virtuemart","redshop","hikashop","virtuemart1") NOT NULL;
ALTER TABLE #__cmcoupon_history MODIFY `estore` enum("virtuemart","redshop","hikashop","virtuemart1") NOT NULL;
ALTER TABLE #__cmcoupon_giftcert_product MODIFY `estore` enum("virtuemart","redshop","hikashop","virtuemart1") NOT NULL;
ALTER TABLE #__cmcoupon_giftcert_code MODIFY `estore` enum("virtuemart","redshop","hikashop","virtuemart1") NOT NULL;
ALTER TABLE #__cmcoupon_giftcert_order MODIFY `estore` enum("virtuemart","redshop","hikashop","virtuemart1") NOT NULL;
