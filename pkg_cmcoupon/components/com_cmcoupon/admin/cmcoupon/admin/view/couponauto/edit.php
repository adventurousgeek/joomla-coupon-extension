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
	getjqdd('coupon_id_search','coupon_id','ajax_elements','coupons_noauto',base_url);
	
	jQuery('select').not(".noselect2").select2({
		theme: 'classic',
		minimumResultsForSearch: 7,
		width: 'resolve'
	});
	
	jQuery("#adminForm").validate({
		ignore: jquery_validate_setting_ignore, // validate hidden fields
		rules: {
			coupon_id_search: { checkElementId: true },
			published: { required: true }
		}
	});

	
});

</script>

<div style="background-color:#ffffff;">
<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>
	<div class="edit-panel">

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'Add Automatic Discount' ); ?></h1>
			<span>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
		<fieldset class="aw-row">
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Coupon' ); ?></label></div>
				<div class="aw-input">
					<input type="text" size="30" value="" id="coupon_id_search" name="coupon_id_search" class="inputbox ac_input"/>
					<input type="hidden" name="coupon_id" value="<?php echo $data->row->coupon_id; ?>" />
				</div>
			</div>

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Published' ); ?></label></div>
				<div class="aw-input">
					<select name="published">
						<?php
						$items = AC()->helper->vars( 'published' );
						foreach ( $items as $key => $value ) {
							echo '<option value="' . $key . '" ' . ( $data->row->published == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
						}
						?>
					</select>
				</div>
			</div>
		</fieldset>
		</div>

		<div class="submitpanel"><span>
			<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
		</span><div class="clear"></div></div>

	</div>

<input type="hidden" name="id" value="<?php echo $data->row->id; ?>" />
<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
</div>


