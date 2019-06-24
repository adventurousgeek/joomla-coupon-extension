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


<script>
function previewImage(id) {
	<?php echo AC()->helper->get_modal_popup( AC()->ajax_url() . '&type=ajax&task=profile_preview&id="+id+"&TB_iframe=true', AC()->lang->__( 'Preview' ), true ); ?>
}
</script>


<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo AC()->lang->__( 'Email Templates' ); ?></h1>
	<a href="#/cmcoupon/profile/edit" class="page-title-action"><?php echo AC()->lang->__( 'Add New' ); ?></a>
</div>
<hr class="wp-header-end">

<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

<table class="adminform">
	<tr>
		<td width="100%">
			<select name="bulkaction">
				<option value="-1"><?php echo AC()->lang->__( 'Bulk Actions' ); ?></option>
				<option value="deletebulk"><?php echo AC()->lang->__( 'Delete' ); ?></option>
			</select>
			<input id="doaction" class="button action" value="Apply" type="button" onclick="if(this.form.bulkaction.value!=-1) submitForm(this.form, this.form.bulkaction.value);">

			<input type="text" name="search" id="search" value="<?php echo $data->search; ?>" class="text_area" />
			<button class="button" onclick="submitForm(this.form,'');"><?php echo AC()->lang->__( 'Search' ); ?></button>
		</td>
		<td nowrap="nowrap"></td>
		<td nowrap="nowrap">
		</td>
	</tr>
</table>


<?php echo $data->table_html; ?>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
