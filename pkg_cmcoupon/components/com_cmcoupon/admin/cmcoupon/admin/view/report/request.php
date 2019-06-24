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

<script language="javascript" type="text/javascript">
var base_url = "<?php echo AC()->ajax_url(); ?>";
jQuery(document).ready(function() {
	jQuery('#start_date').datepicker({ dateFormat: 'yy-mm-dd' });
	jQuery('#end_date').datepicker({ dateFormat: 'yy-mm-dd' });

	jQuery(".js-data-coupontag-ajax").select2({
		theme: 'classic',
		ajax: {
			url: base_url,
			dataType: 'json',
			delay: 250,
			data: function (params) {
				return {
					term: params.term, // search term
					page: params.page,
					type: 'ajax',
					task: 'ajax_elements',
					element: 'coupontag'
				};
			},

			processResults: function (data, params) {
				// parse the results into the format expected by Select2
				// since we are using custom formatting functions we do not need to
				// alter the remote JSON data, except to indicate that infinite
				// scrolling can be used
				params.page = params.page || 1;
				return {
					results: data,
					pagination: {
						more: (params.page * 30) < data.total_count
					}
				};
			},
			cache: true
		},
		escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
		minimumInputLength: 2,
		templateResult: function (data) { return data.label; },
		templateSelection: function (data) { if(typeof(data.label) != "undefined") return data.label; else return data.text; }
	});

	report_type_change();
});

function report_type_change() {
	val = document.adminForm.report_type.value;
	jQuery( '.c_element' ).hide();
	jQuery( '.c_' + val ).show();
}

</script>

<div style="background-color:#ffffff;">
<?php echo AC()->helper->render_layout( 'admin.form.header', $data ); ?>
	<div class="edit-panel">

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'Reports' ); ?></h1>
			<span>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'report');" class="button button-primary button-large"><?php echo AC()->lang->__( 'Run Report' ); ?></button>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
			<div class="card">
				<fieldset class="aw-row">
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Report' ); ?></label></div>
					<div class="aw-input">
						<select name="report_type" onchange="report_type_change()">
							<option value="coupon_list"><?php echo AC()->lang->__( 'Coupon List' ); ?></option>
							<option value="purchased_giftcert_list"><?php echo AC()->lang->__( 'Purchased Gift Certificate List' ); ?></option>
							<option value="coupon_vs_total"><?php echo AC()->lang->__( 'Coupon Usage vs. Total Sales' ); ?></option>
							<option value="coupon_vs_location"><?php echo AC()->lang->__( 'Coupon Usage vs. Location' ); ?></option>
							<option value="history_uses_coupons"><?php echo AC()->lang->__( 'History of Uses' ) . ' - ' . AC()->lang->__( 'Coupons' ); ?></option>
							<option value="history_uses_giftcerts"><?php echo AC()->lang->__( 'History of Uses' ) . ' - ' . AC()->lang->__( 'Gift Certificate' ); ?></option>
							<option value="coupon_tags"><?php echo AC()->lang->__( 'Tags Report' ); ?></option>
							<option value="customer_balance"><?php echo AC()->lang->__( 'Customer Balance' ); ?></option>
						</select>
					</div>
				</div>
				</fieldset>
			</div>

			<div class="card c_element c_purchased_giftcert_list c_coupon_vs_total c_coupon_vs_location c_history_uses_coupons c_history_uses_giftcerts">
				<h2><?php echo AC()->lang->__( 'Order Filters' ); ?></h2>
				<fieldset class="aw-row">
				<div class="aw-row c_element c_purchased_giftcert_list c_coupon_vs_total c_coupon_vs_location c_history_uses_coupons c_history_uses_giftcerts">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Start Date' ); ?></label></div>
					<div class="aw-input">
						<input type="date" id="start_date" name="start_date" value="" placeholder="YYYY-MM-DD" maxlength="10"/>
					</div>
				</div>
				<div class="aw-row c_element c_purchased_giftcert_list c_coupon_vs_total c_coupon_vs_location c_history_uses_coupons c_history_uses_giftcerts">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'End Date' ); ?></label></div>
					<div class="aw-input">
						<input type="date" id="end_date" name="end_date" value="" placeholder="YYYY-MM-DD" maxlength="10"/>
					</div>
				</div>

				<div class="aw-row c_element c_purchased_giftcert_list c_coupon_vs_total c_coupon_vs_location c_history_uses_coupons c_history_uses_giftcerts">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Status' ); ?></label></div>
					<div class="aw-input">
						<select name="order_status[]" size="5" MULTIPLE>
							<?php
							foreach ( $data->orderstatuslist as $r ) {
								echo '<option value="' . $r->order_status_code . '">' . $r->order_status_name . '</option>';
							}
							?>
						</select>
					</div>
				</div>
				</fieldset>
			</div>
			<div class="card c_element c_coupon_list c_purchased_giftcert_list c_coupon_vs_total c_coupon_vs_location c_history_uses_coupons c_history_uses_giftcerts c_coupon_tags">
				<h2><?php echo AC()->lang->__( 'Coupon Filters' ); ?></h2>

				<fieldset class="aw-row ">
					<div class="aw-row c_element c_coupon_list c_coupon_vs_total c_coupon_vs_location c_history_uses_coupons c_coupon_tags">
						<div class="aw-label"><label><?php echo AC()->lang->__( 'Function Type' ); ?></label></div>
						<div class="aw-input">
							<select name="function_type" style="width:100%;">
								<option value="">- <?php echo AC()->lang->__( 'Select Function Type' ); ?> -</option>
								<?php
								foreach ( $data->functiontypelist as $k => $v ) {
									echo '<option value="' . $k . '">' . $v . '</option>';
								}
								?>
							</select>
						</div>
					</div>

					<div class="aw-row c_element c_coupon_list c_coupon_vs_total c_coupon_vs_location c_history_uses_coupons c_coupon_tags">
						<div class="aw-label"><label><?php echo AC()->lang->__( 'Percent or Amount' ); ?></label></div>
						<div class="aw-input">
							<select name="coupon_value_type" style="width:100%;">
								<option value="">- <?php echo AC()->lang->__( 'Select Percent or Amount' ); ?> -</option>
								<?php
								foreach ( $data->valuetypelist as $k => $v ) {
									echo '<option value="' . $k . '">' . $v . '</option>';
								}
								?>
							</select>
						</div>
					</div>

					<div class="aw-row c_element c_coupon_list c_coupon_vs_total c_coupon_vs_location c_history_uses_coupons c_coupon_tags">
						<div class="aw-label"><label><?php echo AC()->lang->__( 'Discount Type' ); ?></label></div>
						<div class="aw-input">
							<select name="discount_type" style="width:100%;">
								<option value="">- <?php echo AC()->lang->__( 'Select Discount Type' ); ?> -</option>
								<?php
								foreach ( $data->discounttypelist as $k => $v ) {
									echo '<option value="' . $k . '">' . $v . '</option>';
								}
								?>
							</select>
						</div>
					</div>

					<div class="aw-row c_element c_coupon_list c_coupon_vs_total c_coupon_vs_location c_history_uses_coupons c_coupon_tags">
						<div class="aw-label"><label><?php echo AC()->lang->__( 'Coupon Template' ); ?></label></div>
						<div class="aw-input">
							<select name="templatelist" style="width:100%;">
								<option value="">- <?php echo AC()->lang->__( 'Select Coupon Template' ); ?> -</option>
								<?php
								foreach ( $data->templatelist as $k => $v ) {
									echo '<option value="' . $v->id . '">' . $v->coupon_code . '</option>';
								}
								?>
							</select>
						</div>
					</div>

					<div class="aw-row c_element c_coupon_list c_purchased_giftcert_list c_coupon_vs_total c_coupon_vs_location c_history_uses_coupons c_history_uses_giftcerts c_coupon_tags">
						<div class="aw-label"><label><?php echo AC()->lang->__( 'Status' ); ?></label></div>
						<div class="aw-input">
							<select name="published" style="width:100%;">
								<option value="">- <?php echo AC()->lang->__( 'Select Status' ); ?> -</option>
								<?php
								foreach ( $data->publishedlist as $k => $v ) {
									echo '<option value="' . $k . '">' . $v . '</option>';
								}
								?>
							</select>
						</div>
					</div>

					<div class="aw-row c_element c_purchased_giftcert_list c_history_uses_giftcerts">
						<div class="aw-label"><label><?php echo AC()->lang->__( 'Gift Certificate Product' ); ?></label></div>
						<div class="aw-input">
							<select name="giftcert_product" style="width:100%;">
								<option value="">- <?php echo AC()->lang->__( 'Select Gift Certificate Product' ); ?> -</option>
								<?php
								foreach ( $data->giftcertproductlist as $k => $v ) {
									echo '<option value="' . $v->product_id . '">' . $v->_product_name . '</option>';
								}
								?>
							</select>
						</div>
					</div>
						
					<div class="aw-row c_element c_coupon_list">
						<div class="aw-label"><label><?php echo AC()->lang->__( 'Tag' ); ?></label></div>
						<div class="aw-input">
							<select name="tag" class="js-data-coupontag-ajax " style="width:100%;"></select>
						</div>
					</div>

				</fieldset>
			</div>

			<div class="submitpanel"><span>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'report');" class="button button-primary button-large"><?php echo AC()->lang->__( 'Run Report' ); ?></button>
			</span><div class="clear"></div></div>

		</div>
	</div>
<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
</div>


