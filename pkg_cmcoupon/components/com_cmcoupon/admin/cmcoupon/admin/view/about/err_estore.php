<?php
/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

if ( ! defined( '_CM_' ) ) {
	exit;
}

echo AC()->helper->render_layout( 'admin.header' );
?>
   

<div style="background-color:#ffffff;">
	<div class="edit-panel">
		<br />
		<br />
		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
			<fieldset class="aw-row">
				<div style="font-weight:bold;font-size:2em;color:red;padding-left:20px;"><?php echo AC()->lang->__( 'Shop not detected' ); ?></div>
			</fieldset>
		</div>
		<div class="submitpanel"><span>&nbsp;</span><div class="clear"></div></div>
		
	</div>
</div>
