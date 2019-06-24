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
	<h1 class="wp-heading-inline"><?php echo AC()->lang->__( 'History of Uses' ); ?> (<?php echo AC()->lang->__( 'Coupons' ); ?>)</h1>
	<a href="#/cmcoupon/history/edit" class="page-title-action"><?php echo AC()->lang->__( 'Add New' ); ?></a>
</div>
<hr class="wp-header-end">

<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

<table class="adminform">
	<tr>
		<td width="100%">
			<select name="bulkaction">
				<option value="-1"><?php echo AC()->lang->__( 'Bulk Actions' ); ?></option>
				<option value="couponDeleteBulk"><?php echo AC()->lang->__( 'Delete' ); ?></option>
			</select>
			<input id="doaction" class="button action" value="Apply" type="button" onclick="if(this.form.bulkaction.value!=-1) submitForm(this.form, this.form.bulkaction.value);">

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
			<select name="filter_function_type" onchange="submitForm(this.form,'');">
				<option value=""><?php echo '- ' . AC()->lang->__( 'Function Type' ) . ' -'; ?>
				<?php
				foreach ( AC()->helper->vars( 'function_type' ) as $key => $value ) {
					echo '<option value="' . $key . '" ' . ( $data->filter_function_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
				}
				?>
			</select>
			<select name="filter_coupon_value_type" onchange="submitForm(this.form,'');">
				<option value=""><?php echo '- ' . AC()->lang->__( 'Percent' ) . ' -'; ?>
				<?php
				foreach ( AC()->helper->vars( 'coupon_value_type' ) as $key => $value ) {
					echo '<option value="' . $key . '" ' . ( $data->filter_coupon_value_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
				}
				?>
			</select>
			<select name="filter_discount_type" onchange="submitForm(this.form,'');">
				<option value=""><?php echo '- ' . AC()->lang->__( 'Discount Type' ) . ' -'; ?>
				<?php
				foreach ( AC()->helper->vars( 'discount_type' ) as $key => $value ) {
					echo '<option value="' . $key . '" ' . ( $data->filter_discount_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
				}
				?>
			</select>
			<?php if ( ! empty( $data->tags ) ) { ?>
				<select name="filter_tag" onchange="submitForm(this.form,'');">
					<option value=""><?php echo '- ' . AC()->lang->__( 'Tag' ) . ' -'; ?>
					<?php
					foreach ( $data->tags as $key => $value ) {
						echo '<option value="' . $value->id . '" ' . ( $data->filter_tag == $value->id ? 'SELECTED' : '' ) . '>' . $value->label . '</option>';
					}
					?>
				</select>
			<?php } ?>
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
