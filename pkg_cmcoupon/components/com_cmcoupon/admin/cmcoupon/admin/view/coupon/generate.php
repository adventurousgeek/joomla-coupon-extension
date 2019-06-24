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
	getjqdd('coupon_template','template','ajax_elements','coupons_template',base_url);
		
	var myvalidator = jQuery("#adminForm").validate({
		ignore: jquery_validate_setting_ignore, // validate hidden fields
		rules: {
			coupon_template: { checkElementId: true },
			number: { required: function(element) {return jQuery.trim(element.form.elements['coupon_codes'].value)=='' ? true: false;}, digits: true }
		}
	});	
	
});

</script>


<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

	<div class="edit-panel">

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'Generate Coupons' ); ?></h1>
			<span>
				<button type="button" onclick="submitForm(this.form, 'saveGenerate');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
		<fieldset class="aw-row">
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Select Coupon Template' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox ac_input hide" type="text" id="coupon_template" name="coupon_template" value="" />
					<input type="hidden" name="template" value="" />
				</div>
			</div>
			<div class="clear"></div>

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Copies' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="number" size="5" maxlength="10" value="" placeholder="<?php echo AC()->lang->__( 'Number' ); ?>" />
					&nbsp; &nbsp; &nbsp; <b><?php echo AC()->lang->__( 'OR' ); ?></b> &nbsp; &nbsp; &nbsp; 
					<textarea name="coupon_codes" placeholder="<?php echo AC()->lang->__( 'Coupon codes (one per line)' ); ?>" style="height:100px;width:200px;"></textarea>
				</div>
			</div>
		</fieldset>
		</div>

		<div class="submitpanel"><span>
			<button type="button" onclick="submitForm(this.form, 'saveGenerate');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
		</span><div class="clear"></div></div>

	</div>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
