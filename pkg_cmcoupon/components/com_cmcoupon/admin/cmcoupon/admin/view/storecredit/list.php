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

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo AC()->lang->__( 'Customer Balance' ); ?></h1>
	<a href="#/cmcoupon/storecredit/edit" class="page-title-action"><?php echo AC()->lang->__( 'Add New' ); ?></a>
</div>
<hr class="wp-header-end">

<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

<table class="adminform">
	<tr>
		<td width="100%">			
			<input type="text" name="search" id="search" value="<?php echo $data->search; ?>" class="text_area" />
			<button class="button" onclick="submitForm(this.form,'');"><?php echo AC()->lang->__( 'Search' ); ?></button>
		</td>
		<td nowrap="nowrap"></td>
		<td nowrap="nowrap"></td>
	</tr>
</table>


<?php echo $data->table_html; ?>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
