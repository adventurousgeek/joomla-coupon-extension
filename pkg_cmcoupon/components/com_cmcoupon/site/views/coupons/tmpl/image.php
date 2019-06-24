<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 * Originally created by Stanislav Scholtz, RuposTel.com
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

?>
<style>
@media print {
	.noprint { display:none; }
}
</style>

<div style="text-align:center;">
	<?php 
		if(!empty($this->url))
			echo '	
				<div><img src="'.$this->url.'" alt="coupon" /></div>
				<br />
				<div class="noprint">
					<button onclick="window.print();returnfalse;" type="button">'.JText::_('COM_CMCOUPON_GBL_PRINT').'</button>
				</div>
			';
	?>

</div>