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

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'About' ); ?></h1>
			<span>&nbsp;</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
			<fieldset class="aw-row">
				<table cellpadding="4" cellspacing="0" border="0" width="100%">
					<tr><td width="100%"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/logo.png" style="margin-left:10px;" /></td></tr>
					<tr><td>
							<blockquote>
								<p><?php echo AC()->lang->__( 'CmCoupon is created by Seyi Cmfadeju.' ); ?></p>
								<p><?php echo AC()->lang->__( 'Please visit <a href="http://cmdev.com">http://cmdev.com</a> to find out more about us.' ); ?></p>
								<p>&nbsp;</p>
							</blockquote>
						</td>
					</tr>
					<tr>
						<td>
							<div style="font-weight: 700;">
								<?php echo AC()->lang->__( 'Version' ) . ': ' . CMCOUPON_VERSION; ?>
							</div>
						</td>
					</tr>
				</table>
			</fieldset>
		</div>
		<div class="submitpanel"><span>&nbsp;</span><div class="clear"></div></div>
		
	</div>
</div>
