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
	getjqdd('coupon_giftcert','coupon_id','ajax_elements','coupons_giftcert',base_url);
	getjqdd('coupon_template','template_id','ajax_elements','coupons_template',base_url);
	
	coupon_type_change();

	jQuery('select').not(".noselect2").select2({
		theme: 'classic',
		minimumResultsForSearch: 7,
		width: 'resolve'
	});
	
	
	var myvalidator = jQuery("#adminForm").validate({
		ignore: jquery_validate_setting_ignore, // validate hidden fields
		rules: {
			recipient_customer: { required:true, checkElementId: true },
			coupon_type:{ required:true },
			coupon_giftcert: { checkElementId: true },
			coupon_template: { checkElementId: true },
			coupon_value: { required: function(element) {return jQuery("#coupon_value").is(":visible");}, number:true, min:0.01 }
		}
	});
	
	
});

function coupon_type_change() {
	jQuery("#adminForm").validatorClearMyMessages();

	v_recipient_type = jQuery('input[name=coupon_type]:checked', 'form[name="adminForm"]').val();
	jQuery('input[name=coupon_giftcert]', 'form[name="adminForm"]').hide();
	jQuery('input[name=coupon_value]', 'form[name="adminForm"]').hide();
	jQuery('input[name=coupon_template]', 'form[name="adminForm"]').hide();
	jQuery('#label_coupon_value').hide();
	
	if(v_recipient_type=='giftcert') jQuery('input[name=coupon_giftcert]', 'form[name="adminForm"]').show().focus();
	else if(v_recipient_type=='template') {
		jQuery('#label_coupon_value').show();
		jQuery('input[name=coupon_template]', 'form[name="adminForm"]').show().focus();
		jQuery('input[name=coupon_value]', 'form[name="adminForm"]').show();
	}
}


function submitbutton(pressbutton) {
	if (pressbutton == 'CANCELcoupons') {
		jQuery("#adminForm").validate().settings.ignore = "*";
		submitcmform( pressbutton );
		return;
	}

	jQuery("#adminForm").validate().settings.ignore = jquery_validate_setting_ignore;
	submitcmform( pressbutton ); 
	return;
}

</script>

<div style="background-color:#ffffff;">
<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>
	<div class="edit-panel">

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'Add to Customer Balance' ); ?></h1>
			<span>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
			<fieldset class="aw-row">

				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Customer' ); ?></label></div>
					<div class="aw-input">

						<div class="awcontrols">
							<input type="text" id="recipient_customer" name="recipient_customer" data-id="user_id" class="inputbox ac_input" value="<?php echo $data->row->recipient_customer; ?>" />
							<input type="hidden" id="user_id" name="user_id" value="<?php echo $data->row->user_id; ?>" />
						</div>

					</div>
				</div>

				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Type' ); ?></label></div>
					<div class="aw-input">

						<div class="awcontrols">
							<span class="awradio awbtn-group awbtn-group-yesno" >
								<input type="radio" class="no_jv_ignore" onclick="coupon_type_change()" id="coupon_type_rd_giftcert" name="coupon_type" value="giftcert"  <?php echo 'giftcert' === $data->row->coupon_type ? 'checked="checked"' : ''; ?> />
								<label for="coupon_type_rd_giftcert" ><?php echo AC()->lang->__( 'Gift Certificate' ); ?></label>
								<input type="radio" class="no_jv_ignore" onclick="coupon_type_change()" id="coupon_type_rd_template" name="coupon_type" value="template"  <?php echo 'template' === $data->row->coupon_type ? 'checked="checked"' : ''; ?> />
								<label for="coupon_type_rd_template" ><?php echo AC()->lang->__( 'Coupon Template' ); ?></label>
							</span>
							<input class="inputbox ac_input hide" type="text" data-id="coupon_id" id="coupon_giftcert" name="coupon_giftcert" value="<?php echo $data->row->coupon_giftcert; ?>" />
							<input type="hidden" name="coupon_id" value="<?php echo $data->row->coupon_id; ?>" />

							<input class="inputbox ac_input hide" type="text" data-id="template_id" id="coupon_template" name="coupon_template" value="<?php echo $data->row->coupon_template; ?>" />
							<input type="hidden" name="template_id" value="<?php echo $data->row->template_id; ?>" />
							&nbsp; <span id="label_coupon_value" class="hide"><?php echo AC()->lang->__( 'Value' ); ?></span>:
							<input class="inputbox hide" type="text" style="width:50px;" id="coupon_value" name="coupon_value" value="<?php echo $data->row->coupon_value; ?>" />
						</div>

					</div>
				</div>

			</fieldset>
		</div>

		<div class="submitpanel"><span>
			<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
		</span><div class="clear"></div></div>

	</div>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
</div>
