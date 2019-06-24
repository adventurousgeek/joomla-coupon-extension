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
	<h1 class="wp-heading-inline"><?php echo AC()->lang->__( 'Installation Check' ); ?></h1>
</div>
<hr class="wp-header-end">

<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

<table class="adminform">
	<tr>
		<td width="100%">
			<select name="bulkaction">
				<option value="-1"><?php echo AC()->lang->__( 'Bulk Actions' ); ?></option>
				<option value="installbulk"><?php echo AC()->lang->__( 'Install' ); ?></option>
				<option value="uninstallbulk"><?php echo AC()->lang->__( 'Uninstall' ); ?></option>
			</select>
			<input id="doaction" class="button action" value="Apply" type="button" onclick="if(this.form.bulkaction.value!=-1) submitForm(this.form, this.form.bulkaction.value);">
		</td>
		<td nowrap="nowrap"></td>
		<td nowrap="nowrap">&nbsp;</td>
	</tr>
</table>


<?php echo $data->table_html; ?>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
