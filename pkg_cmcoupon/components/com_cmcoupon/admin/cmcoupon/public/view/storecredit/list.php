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

/*<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo AC()->lang->__('Store Credit'); ?></h1>
</div>
<hr class="wp-header-end">*/
?>



<?php if ( $data->is_enabled ) { ?>

	<?php echo AC()->helper->render_layout( 'public.message' ); ?>

	<?php echo AC()->helper->render_layout( 'public.form.header' ); ?>

		<span><?php echo AC()->lang->__( 'Add a Gift Certificate' ); ?></span>
		<input type="text" name="voucher" />
		<input type="button" onclick="this.form.task.value='store'; this.form.submit(); " value="<?php echo AC()->lang->__( 'Apply' ); ?>" />

	<?php echo AC()->helper->render_layout( 'public.form.footer' ); ?>
	<br />
	<div><?php echo AC()->lang->__( 'Balance' ); ?>: <?php echo $data->balance; ?></div>
	<br />

<?php } ?>

<?php echo $data->table_html; ?>
