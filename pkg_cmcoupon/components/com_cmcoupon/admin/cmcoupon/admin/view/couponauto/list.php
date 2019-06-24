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
	<h1 class="wp-heading-inline"><?php echo AC()->lang->__( 'Automatic Discounts' ); ?></h1>
	<a href="#/cmcoupon/couponauto/edit" class="page-title-action"><?php echo AC()->lang->__( 'Add New' ); ?></a>
</div>
<hr class="wp-header-end">

<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

<table class="adminform">
	<tr>
		<td width="100%">
			<select name="bulkaction">
				<option value="-1"><?php echo AC()->lang->__( 'Bulk Actions' ); ?></option>
				<option value="publishbulk"><?php echo AC()->lang->__( 'Publish' ); ?></option>
				<option value="unpublishbulk"><?php echo AC()->lang->__( 'Unpublish' ); ?></option>
				<option value="deletebulk"><?php echo AC()->lang->__( 'Delete' ); ?></option>
			</select>
			<input id="doaction" class="button action" value="Apply" type="button" onclick="if(this.form.bulkaction.value!=-1) submitForm(this.form, this.form.bulkaction.value);">

			<input type="text" name="search" id="search" value="<?php echo $data->search; ?>" class="text_area" />
			<button class="button" onclick="submitForm(this.form,'');"><?php echo AC()->lang->__( 'Search' ); ?></button>
		</td>
		<td nowrap="nowrap"></td>
		<td nowrap="nowrap">
			<select name="filter_state" onchange="submitForm(this.form,'');">
				<?php
				$items = array(
					'' => '- ' . AC()->lang->__( 'Status' ) . ' -',
				) + AC()->helper->vars( 'published' );
				foreach ( $items as $key => $value ) {
					echo '<option value="' . $key . '" ' . ( $data->filter_state == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
				}
				?>
			</select>
		</td>
	</tr>
</table>

<?php echo $data->table_html; ?>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
