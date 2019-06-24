<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

if(empty($this->layout_type)) $this->layout_type = 'div';


foreach($this->coupons as $coupon) {
	if($this->layout_type == 'div') {
	?>
		<div>
			<?php if(!empty($coupon['link'])) { ?>
				<a rel="nofollow" href="<?php echo JRoute::_($coupon['link']); ?>" align="middle" title="<?php echo JText::_('COM_CMCOUPON_CP_DELETETOOLTIP'); ?>">
					<img src="<?php echo JURI::root(true).'/media/com_cmcoupon/images/x-48.png'; ?>" alt="x" style="height:14px;" /></a>
			<?php } ?>
			<?php echo $coupon['text']; ?>
		</div>
	<?php 
	}
	elseif($this->layout_type == 'span') {
	?>
		<span>
			<?php if(!empty($coupon['link'])) { ?>
				<a rel="nofollow" href="<?php echo JRoute::_($coupon['link']); ?>" align="middle" title="<?php echo JText::_('COM_CMCOUPON_CP_DELETETOOLTIP'); ?>">
					<img src="<?php echo JURI::root(true).'/media/com_cmcoupon/images/x-48.png'; ?>" alt="x" style="height:14px;" /></a>
			<?php } ?>
			<?php echo $coupon['text']; 
		?></span>
	<?php 
	}
}

