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

<style>
table.wp-list-table { width:100%; overflow-x:auto; display:block; }
table.wp-list-table thead th { vertical-align:top; }
</style>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo AC()->lang->__( 'Report' ); ?>: <?php echo $data->title; ?></h1>
	<?php if ( ! empty( $data->count ) ) { ?>
		<a href="<?php echo AC()->ajax_url(); ?>&type=admin&view=report&task=export&<?php echo $data->queryparam; ?>"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/excel.gif" /></a>
	<?php } ?>
</div>
<hr class="wp-header-end">
<table class="criteria">
<?php if ( ! empty( $data->criteria->start_date ) ) { ?>
	<tr><td><b><?php echo AC()->lang->__( 'Start Date' ); ?>:</b></td><td><?php echo $data->criteria->start_date; ?></td></tr>
<?php } ?>
<?php if ( ! empty( $data->criteria->end_date ) ) { ?>
	<tr><td><b><?php echo AC()->lang->__( 'End Date' ); ?>:</b></td><td><?php echo $data->criteria->end_date; ?></td></tr>
<?php } ?>
<?php if ( ! empty( $data->criteria->order_status ) ) { ?>
	<tr><td><b><?php echo AC()->lang->__( 'Status' ); ?>:</b></td><td><?php echo $data->criteria->order_status_str; ?></td></tr>
<?php } ?>
</table>


<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

<?php echo $data->table_html; ?>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
