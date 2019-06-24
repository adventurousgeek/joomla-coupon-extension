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
	<h1 class="wp-heading-inline"><?php echo AC()->lang->__( 'History of Uses' ); ?> (<?php echo AC()->lang->__( 'Gift Certificates' ); ?>)</h1>
</div>
<hr class="wp-header-end">

<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

<table class="adminform">
	<tr>
		<td width="100%">

			<input type="text" name="search" id="search" value="<?php echo $data->search; ?>" class="text_area" />
			<select name="search_type">
				<option value="coupon" <?php echo 'coupon' == $data->search_type ? 'SELECTED' : ''; ?>><?php echo AC()->lang->__( 'Coupon Code' ); ?></option>
				<option value="user" <?php echo 'user' == $data->search_type ? 'SELECTED' : ''; ?>><?php echo AC()->lang->__( 'Username' ); ?></option>
				<option value="last" <?php echo 'last' == $data->search_type ? 'SELECTED' : ''; ?>><?php echo AC()->lang->__( 'Last Name' ); ?></option>
				<option value="first" <?php echo 'first' == $data->search_type ? 'SELECTED' : ''; ?>><?php echo AC()->lang->__( 'First Name' ); ?></option>
				<option value="email" <?php echo 'email' == $data->search_type ? 'SELECTED' : ''; ?>><?php echo AC()->lang->__( 'E-mail' ); ?></option>
				<option value="order" <?php echo 'order' == $data->search_type ? 'SELECTED' : ''; ?>><?php echo AC()->lang->__( 'Order Number' ); ?></option>
				<option value="date" <?php echo 'date' == $data->search_type ? 'SELECTED' : ''; ?>><?php echo AC()->lang->__( 'Order Date' ); ?></option>
			</select>
			<button class="button" onclick="submitForm(this.form,'');"><?php echo AC()->lang->__( 'Search' ); ?></button>
		</td>
		<td nowrap="nowrap"></td>
		<td nowrap="nowrap">
			<select name="filter_state" onchange="submitForm(this.form,'');">
				<option value=""><?php echo '- ' . AC()->lang->__( 'Status' ) . ' -'; ?>
				<?php
				foreach ( AC()->helper->vars( 'state', null, array( 'template' ) ) as $key => $value ) {
					echo '<option value="' . $key . '" ' . ( $data->filter_state == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
				}
				?>
			</select>
		</td>
	</tr>
</table>


<?php echo $data->table_html; ?>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
