/*
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

 
UPDATE #__cmcoupon SET params=CONCAT(SUBSTRING(params,1, LENGTH(params)-1),',"exclude_special":1,"exclude_discounted":1', "}") WHERE (params IS NOT NULL AND params!="") AND exclude_special=1;
UPDATE #__cmcoupon SET params='{"exclude_special":1,"exclude_discounted":1}' WHERE (params IS NULL or params="") AND exclude_special=1;
ALTER TABLE #__cmcoupon DROP COLUMN exclude_special;
