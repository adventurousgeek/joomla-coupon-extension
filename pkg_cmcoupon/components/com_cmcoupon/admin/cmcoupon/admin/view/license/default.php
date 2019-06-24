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
<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>
	<div class="edit-panel">

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'Subscription' ); ?></h1>
			<span>
				<?php if ( empty( $data->row->license ) ) { ?>
					<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'activate');" class="button button-primary button-large"><?php echo AC()->lang->__( 'Activate' ); ?></button>
				<?php } else { ?>
					<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'delete');" class="button button-primary button-large"><?php echo AC()->lang->__( 'Delete Subscription' ); ?></button>
				<?php } ?>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
		<fieldset class="aw-row">
			<?php if ( empty( $data->row->license ) ) { ?>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Key' ); ?></label></div>
					<div class="aw-input">
						<input type="text" class="inputbox" style="width:auto;" size="75" name="license" value="<?php echo $data->row->license; ?>" />
					</div>
				</div>
			<?php } else { ?>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Key' ); ?></label></div>
					<div class="aw-input"><?php echo $data->row->license; ?></div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Website' ); ?></label></div>
					<div class="aw-input"><?php echo $data->row->website; ?></div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Expiration' ); ?></label></div>
					<div class="aw-input"><?php echo $data->row->expiration; ?></div>
				</div>
			<?php } ?>
		</fieldset>
		</div>

		<div class="submitpanel"><span>
			<?php if ( empty( $data->row->license ) ) { ?>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'activate');" class="button button-primary button-large"><?php echo AC()->lang->__( 'Activate' ); ?></button>
			<?php } else { ?>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'delete');" class="button button-primary button-large"><?php echo AC()->lang->__( 'Delete Subscription' ); ?></button>
			<?php } ?>
		</span><div class="clear"></div></div>
	</div>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
</div>
