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
	
	var myvalidator = jQuery("#adminForm").validate({
		ignore: jquery_validate_setting_ignore, // validate hidden fields
		rules: {
			product_id:{ required:true, digits:true },
			codes: { required: true }
		}
	});
	
	//If the change event fires we want to see if the form validates. But we don't want to check before the form has been submitted by the user initially.
	jQuery(document).on('change', '[name=product_id]', function () {
		if (!jQuery.isEmptyObject(myvalidator.submitted)) {
			//myvalidator.form();  // validate whole form
			jQuery(this).valid();  // validate single field
		}
	});
	
	
});

</script>

<div style="background-color:#ffffff;">
<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>
	<div class="edit-panel">

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'Gift Certificate Code' ); ?></h1>
			<span>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
		<fieldset class="aw-row">
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Product' ); ?></label></div>
				<div class="aw-input">
					<select name="product_id">
						<option value=""></option>
						<?php
						foreach ( $data->productlist as $key => $value ) {
							echo '<option value="' . $value->product_id . '" ' . ( $data->row->product_id == $value->product_id ? 'SELECTED' : '' ) . '>' . $value->_product_name . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Codes' ); ?></label></div>
				<div class="aw-input">
					<textarea name="codes" placeholder="<?php echo AC()->lang->__( 'Coupon codes (one per line)' ); ?>" style="height:100px;width:200px;"><?php echo $data->row->codes; ?></textarea>
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
