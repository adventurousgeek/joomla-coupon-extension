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
	getjqdd('product_id_search','product_id','ajax_elements','productgift',base_url);
	
	jQuery('select').not(".noselect2").select2({
		theme: 'classic',
		minimumResultsForSearch: 7,
		width: 'resolve',
	});
	jQuery('select[name="profile_id"], select[name="expiration_type"]').not(".noselect2").select2({
		theme: 'classic',
		minimumResultsForSearch: 7,
		width: 'resolve',
		allowClear: true
	});
	
	var myvalidator = jQuery("#adminForm").validate({
		ignore: jquery_validate_setting_ignore, // validate hidden fields
		rules: {
			product_id_search: { checkElementId: true },
			coupon_template_id:{ required:true, digits:true, min:1 },
			profile_id: {digits: true, min:1 },
			published: {required: true},
			expiration_number: { digits: true, min:1 },
			expiration_type: { required: function(element) {return jQuery.trim(jQuery(element).closest('form').find('input[name=expiration_number]').val())=='' ? false: true;} },
			vendor_email: { email: true }
		}
	});

	//If the change event fires we want to see if the form validates. But we don't want to check before the form has been submitted by the user initially.
	jQuery(document).on('change', '[name=coupon_template_id],[name=expiration_type]', function () {
		if (!jQuery.isEmptyObject(myvalidator.submitted)) {
			jQuery(this).valid();  // validate single field
		}
	});
	
});

</script>
   
<div style="background-color:#ffffff;">
<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>
	<div class="edit-panel">

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'Gift Certificate Product' ); ?></h1>
			<span>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
			<div class="card">
				<h2><?php echo AC()->lang->__( 'General' ); ?></h2>
				<fieldset class="aw-row">
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Product' ); ?></label></div>
					<div class="aw-input">
						<?php
						if ( ! empty( $data->row->id ) ) {
							echo $data->row->product_name . ' (' . $data->row->product_sku . ')';
						} else {
							echo '<input type="text" size="30" value="" id="product_id_search" name="product_id_search" class="inputbox ac_input"/>';
						}
						?>
						<input type="hidden" name="product_id" value="<?php echo $data->row->product_id; ?>" />
					</div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Coupon Template' ); ?></label></div>
					<div class="aw-input">
						<select name="coupon_template_id">
							<option value=""></option>
							<?php
							foreach ( $data->templatelist as $row ) {
								echo '<option value="' . $row->id . '" ' . ( $data->row->coupon_template_id == $row->id ? 'SELECTED' : '' ) . '>' . $row->coupon_code . '</option>';
							}
							?>
						</select>
						<a href="http://cmdev.com/documentation/cmcoupon-pro/tutorials/how-create-coupon-template" target="_blank"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/question_mark.png" alt="question mark" height="23" /></a>
					</div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Price Calculation Type' ); ?></label></div>
					<div class="aw-input">
						<select name="price_calc_type">
							<?php
							foreach ( $data->pricecalclist as $key => $value ) {
								echo '<option value="' . $key . '" ' . ( $data->row->price_calc_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
							}
							?>
						</select>
					</div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Email Image' ); ?></label></div>
					<div class="aw-input">
						<select name="profile_id">
							<option value=""></option>
							<?php
							foreach ( $data->profilelist as $row ) {
								echo '<option value="' . $row->id . '" ' . ( $data->row->profile_id == $row->id ? 'SELECTED' : '' ) . '>' . $row->title . '</option>';
							}
							?>
						</select>
					</div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Published' ); ?></label></div>
					<div class="aw-input">
						<select name="published">
							<?php
							foreach ( $data->publishlist as $key => $value ) {
								echo '<option value="' . $key . '" ' . ( $data->row->published == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
							}
							?>
						</select>
					</div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Expiration' ); ?></label></div>
					<div class="aw-input">
						<input class="inputbox" type="text" name="expiration_number" size="10" value="<?php echo $data->row->expiration_number; ?>" />
						<select name="expiration_type">
							<option value=""></option>
							<?php
							foreach ( $data->expirationlist as $key => $value ) {
								echo '<option value="' . $key . '" ' . ( $data->row->expiration_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
							}
							?>
						</select>
					</div>
				</div>
				</fieldset>
			</div>

			<div class="card">
				<h2><?php echo AC()->lang->__( 'Coupon Code' ); ?></h2>
				<fieldset class="aw-row">
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Prefix' ); ?></label></div>
					<div class="aw-input">
						<input class="inputbox" type="text" name="coupon_code_prefix" size="30" maxlength="255" value="<?php echo $data->row->coupon_code_prefix; ?>" />
					</div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Suffix' ); ?></label></div>
					<div class="aw-input">
						<input class="inputbox" type="text" name="coupon_code_suffix" size="30" maxlength="255" value="<?php echo $data->row->coupon_code_suffix; ?>" />
					</div>
				</div>
				</fieldset>
			</div>

			<div class="card" id="cmcoupon_giftcert_edit_personal_msg">
				<h2><?php echo AC()->lang->__( 'Personal Message' ); ?></h2>
				<fieldset class="aw-row">
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'From Name ID' ); ?></label></div>
					<div class="aw-input">
						<input class="inputbox" type="text" name="from_name_id" size="30" maxlength="255" value="<?php echo $data->row->from_name_id; ?>" />
					</div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Recipient Name ID' ); ?></label></div>
					<div class="aw-input">
						<input class="inputbox" type="text" name="recipient_name_id" size="30" maxlength="255" value="<?php echo $data->row->recipient_name_id; ?>" />
					</div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Recipient Email ID' ); ?></label></div>
					<div class="aw-input">
						<input class="inputbox" type="text" name="recipient_email_id" size="30" maxlength="255" value="<?php echo $data->row->recipient_email_id; ?>" />
					</div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Recipient Message ID' ); ?></label></div>
					<div class="aw-input">
						<input class="inputbox" type="text" name="recipient_mesg_id" size="30" maxlength="255" value="<?php echo $data->row->recipient_mesg_id; ?>" />
					</div>
				</div>
				</fieldset>
			</div>

			<div class="card">
				<h2><?php echo AC()->lang->__( 'Vendor' ); ?></h2>
				<fieldset class="aw-row">
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Name' ); ?></label></div>
					<div class="aw-input">
						<input class="inputbox" type="text" name="vendor_name" size="30" maxlength="255" value="<?php echo $data->row->vendor_name; ?>" />
					</div>
				</div>
				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'E-mail' ); ?></label></div>
					<div class="aw-input">
						<input class="inputbox" type="text" name="vendor_email" size="30" maxlength="255" value="<?php echo $data->row->vendor_email; ?>" />
					</div>
				</div>
				</fieldset>
			</div>

		</div>

		<div class="submitpanel"><span>
			<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
		</span><div class="clear"></div></div>

	</div>

<input type="hidden" name="id" value="<?php echo $data->row->id; ?>" />
<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
</div>
