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
	getjqdd('recipient_customer','user_id','ajax_elements','user',base_url);
	getjqdd('coupon_coupon','coupon_id','ajax_elements','coupons_published',base_url);
	getjqdd('coupon_template','template_id','ajax_elements','coupons_template',base_url);
	
	jQuery('#email_body').addClass('no_jv_ignore');

	recipient_type_change();
	coupon_type_change();
	
	jQuery('select').not(".noselect2").select2({
		theme: 'classic',
		minimumResultsForSearch: 7,
		width: 'resolve'
	});
	
	
	var myvalidator = jQuery("#adminForm").validate({
		ignore: jquery_validate_setting_ignore, // validate hidden fields
		rules: {
			recipient_type:{ required:true },
			recipient_customer: { checkElementId: true },
			recipient_email: { required: function(element) {return jQuery("#recipient_email").is(":visible");}, email: true },
			coupon_type:{ required:true },
			coupon_coupon: { checkElementId: true },
			coupon_template: { checkElementId: true },
			profile_id: { required: true },
			email_subject: { required: true },
			email_body: { editorcheck: {required: true, getcontent: '<?php echo AC()->helper->get_editor_content_js( 'email_body' ); ?>'} }
		}
	});

	//If the change event fires we want to see if the form validates. But we don't want to check before the form has been submitted by the user initially.
	jQuery(document).on('change', '[name=profile_id],.ac_input, #email_body', function () {
		if (!jQuery.isEmptyObject(myvalidator.submitted)) {
			//myvalidator.form();  // validate whole form
			jQuery(this).valid();  // validate single field
			
			if(jQuery(this).attr('name')=='profile_id') {
				jQuery('[name=email_subject]' ).valid();
				jQuery('[name=email_body]' ).valid();
			}
		}
	});
	
	
	
});

function recipient_type_change() {
	v_recipient_type = jQuery('input[name=recipient_type]:checked', 'form[name="adminForm"]').val();
	jQuery('input[name=recipient_customer]', 'form[name="adminForm"]').hide();
	jQuery('input[name=recipient_email]', 'form[name="adminForm"]').hide();
	
	if(v_recipient_type=='customer') jQuery('input[name=recipient_customer]', 'form[name="adminForm"]').show().focus();
	else if(v_recipient_type=='email') jQuery('input[name=recipient_email]', 'form[name="adminForm"]').show().focus();
}
function coupon_type_change() {
	v_recipient_type = jQuery('input[name=coupon_type]:checked', 'form[name="adminForm"]').val();
	jQuery('input[name=coupon_coupon]', 'form[name="adminForm"]').hide();
	jQuery('input[name=coupon_template]', 'form[name="adminForm"]').hide();
	
	if(v_recipient_type=='coupon') jQuery('input[name=coupon_coupon]', 'form[name="adminForm"]').show().focus();
	else if(v_recipient_type=='template') jQuery('input[name=coupon_template]', 'form[name="adminForm"]').show().focus();
}
function profile_type_change(val) {
	jQuery('.profile_infos').hide();
	id = 'profile_info_'+val;
	if(jQuery('#'+id).length) {
		jQuery('input[name=email_subject]', 'form[name="adminForm"]').val(jQuery('#'+id+' .profile_subject').html());
		var email_body_html = jQuery('#'+id+' .profile_body').html()
		jQuery('input[name=email_body]', 'form[name="adminForm"]').val(email_body_html);

		<?php echo AC()->helper->set_editor_content_js( 'email_body', 'email_body_html' ); ?>
	}
}

</script>

<div style="background-color:#ffffff;">
<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>
	<div class="edit-panel">

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'Send a Voucher' ); ?></h1>
			<span>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Send a Voucher' ); ?></button>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
		<fieldset class="aw-row">

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Recipient' ); ?></label></div>
				<div class="aw-input">

					<div class="awcontrols">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" onclick="recipient_type_change()" id="recipient_type_rd_customer" name="recipient_type" value="customer" <?php echo 'customer' == $data->row->recipient_type ? 'checked="checked"' : ''; ?> />
							<label for="recipient_type_rd_customer" ><?php echo AC()->lang->__( 'Customer' ); ?></label>
							<input type="radio" class="no_jv_ignore" onclick="recipient_type_change()" id="recipient_type_rd_email" name="recipient_type" value="email"  <?php echo 'email' == $data->row->recipient_type ? 'checked="checked"' : ''; ?> />
							<label for="recipient_type_rd_email" ><?php echo AC()->lang->__( 'E-mail' ); ?></label>
						</span>
						<input type="text" id="recipient_customer" name="recipient_customer" data-id="user_id" class="inputbox hide ac_input" value="<?php echo $data->row->recipient_customer; ?>" />
						<input type="hidden" id="user_id" name="user_id" value="<?php echo $data->row->user_id; ?>" />
						<input class="inputbox hide" type="text" id="recipient_email" name="recipient_email" maxlength="255" value="<?php echo $data->row->recipient_email; ?>" />
					</div>

				</div>
			</div>

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Coupon' ); ?></label></div>
				<div class="aw-input">

					<div class="awcontrols">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" onclick="coupon_type_change()" id="coupon_type_rd_coupon" name="coupon_type" value="coupon"  <?php echo 'coupon' == $data->row->coupon_type ? 'checked="checked"' : ''; ?> />
							<label for="coupon_type_rd_coupon" ><?php echo AC()->lang->__( 'Coupon' ); ?></label>
							<input type="radio" class="no_jv_ignore" onclick="coupon_type_change()" id="coupon_type_rd_template" name="coupon_type" value="template"  <?php echo 'template' == $data->row->coupon_type ? 'checked="checked"' : ''; ?> />
							<label for="coupon_type_rd_template" ><?php echo AC()->lang->__( 'Coupon Template' ); ?></label>
						</span>
						<input class="inputbox ac_input hide" type="text" data-id="coupon_id" id="coupon_coupon" name="coupon_coupon" value="<?php echo $data->row->coupon_coupon; ?>" />
						<input type="hidden" name="coupon_id" value="<?php echo $data->row->coupon_id; ?>" />
						<input class="inputbox ac_input hide" type="text" data-id="template_id" id="coupon_template" name="coupon_template" value="<?php echo $data->row->coupon_template; ?>" />
						<input type="hidden" name="template_id" value="<?php echo $data->row->template_id; ?>" />
					</div>

				</div>
			</div>

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Email Template' ); ?></label></div>
				<div class="aw-input">
					<select name="profile_id" onchange="profile_type_change(this.value)" style="min-width:200px;">
						<option value=""></option>
						<?php foreach ( $data->profilelist as $item ) { ?>
							<option value="<?php echo $item->id; ?>" <?php echo $item->id == $data->row->profile_id ? 'SELECTED' : ''; ?>><?php echo $item->title; ?></option>
						<?php } ?>
					</select>
				</div>
			</div>

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Email Subject' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="email_subject" value="<?php echo $data->row->email_subject; ?>" />
				</div>
			</div>

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Email Body' ); ?></label></div>
				<div class="aw-input">
					<table width="100%"><tr>
					<td>
						<?php
						echo AC()->helper->get_editor( $data->row->email_body, 'email_body', array(
							'textarea_name' => 'email_body',
							'editor_height' => '500',
						) );
						?>
					</td>
					<td valign="top"><br /><br /><br /><br /><br /><br />
						<div><b><?php echo AC()->lang->__( 'Tags' ); ?></b></div>
						<div>{siteurl}</div>
						<div>{today_date}</div>
						<div>{coupon_code}</div>
						<div>{coupon_value}</div>
						<div>{secret_key}</div>
						<div>{coupon_expiration}</div>
						<div>{image_embed}</div>
						<div>{vouchers}</div>
					</td>
					</tr></table>
				</div>
			</div>
		</fieldset>
		</div>

		<div class="submitpanel"><span>
			<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Send a Voucher' ); ?></button>
		</span><div class="clear"></div></div>

	</div>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
</div>


<?php

foreach ( $data->profilelist as $profile ) {
	echo '
		<div id="profile_info_' . $profile->id . '" class="profile_infos" style="display:none;">
			<div class="profile_subject">' . $profile->email_subject . '</div>
			<div class="profile_body">' . $profile->email_body . '</div>
		</div>
	';

}
