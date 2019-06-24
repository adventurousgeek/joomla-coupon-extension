<?php
/**
 * @module CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined('_JEXEC') or die;

$document = JFactory::getDocument();
//$document->addStyleSheet(JURI::root(true).'/media/com_cmcoupon/css/style.css');


?>
<span><?php echo JText::_('COM_CMCOUPON_GC_GIFTCERT_BALANCE'); ?>: <?php echo $balance_str; ?></span>

<?php if($is_show_apply_button) { ?>
<?php
/*
<form id="frm_cmcoupon_customer_balance" name="frm_cmcoupon_customer_balance" action="<?php echo JRoute::_( 'index.php' ); ?>" method="post" style="display:inline;">
	<input type="submit" value="<?php echo JText::_('COM_CMCOUPON_GBL_APPLY'); ?>" />
	<input type="hidden" name="option" value="com_cmcoupon" />
	<input type="hidden" name="view" value="coupons" />
	<input type="hidden" name="task" value="apply_voucher_balance" />
	<input type="hidden" name="return" value="<?php echo $current_url; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
*/
?>
	<a 
		class="cmcoupon_balance_apply"
		href="<?php echo JRoute::_('index.php?option=com_cmcoupon&view=coupons&task=apply_voucher_balance&return='.$current_url); ?>"
	><?php echo JText::_('COM_CMCOUPON_GBL_APPLY'); ?></a>

<?php } ?>
<br />
<div class="clear"></div>
