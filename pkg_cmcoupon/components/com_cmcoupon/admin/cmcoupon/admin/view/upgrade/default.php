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
			<h1><?php echo AC()->lang->__( 'Upgrade' ); ?></h1>
			<span>
				<button type="submit" onclick="this.form.task.value='info';return true;" ><?php echo AC()->lang->__( 'Refresh update information' ); ?></button>
				<?php if ( $data->has_update ) { ?>
				<button type="submit" onclick="this.form.task.value='start';return true;" ><?php echo AC()->lang->__( 'Update to the latest version' ); ?></button>
				<?php } ?>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
		<fieldset class="aw-row">
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Installed version' ); ?></label></div>
					<div class="aw-input"><?php echo $data->row['current_version']; ?></div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Latest version' ); ?></label></div>
					<div class="aw-input"><?php echo $data->row['version']; ?></div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Latest release date' ); ?></label></div>
					<div class="aw-input"><?php echo $data->row['released']; ?></div>
				</div>
			<?php if ( ! empty( $data->row['release_notes'] ) ) { ?>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Information' ); ?></label></div>
					<div class="aw-input"><a href="<?php echo $data->row['release_notes']; ?>" target="_blank"><?php echo AC()->lang->__( 'Read more' ); ?></a></div>
				</div>
			<?php } ?>
		</fieldset>
		</div>

		<div class="submitpanel"><span>
			<button type="submit" onclick="this.form.task.value='info';return true;" ><?php echo AC()->lang->__( 'Refresh update information' ); ?></button>
			<?php if ( $data->has_update ) { ?>
			<button type="submit" onclick="this.form.task.value='start';return true;" ><?php echo AC()->lang->__( 'Update to the latest version' ); ?></button>
			<?php } ?>
		</span><div class="clear"></div></div>
	</div>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
</div>
