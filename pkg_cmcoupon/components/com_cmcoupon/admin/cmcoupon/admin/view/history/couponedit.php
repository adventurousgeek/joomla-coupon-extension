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
	getjqdd('username','user_id','ajax_elements','user',base_url);
	getjqdd('coupon_coupon','coupon_id','ajax_elements','coupons_published',base_url);
	
	
	var myvalidator = jQuery("#adminForm").validate({
		ignore: jquery_validate_setting_ignore, // validate hidden fields
		rules: {
			coupon_coupon: { checkElementId: true },
			username: { required: false },
			user_email: { required: true, email: true },
			total_product:{ required:false, number:true, min:0.01 },
			total_shipping:{ required:false, number:true, min:0.01 },
			currency_code:{ required:false },
			total_curr_product:{ required:false, number:true, min:0.01 },
			total_curr_shipping:{ required:false, number:true, min:0.01 },
			order_id:{ required:false, digits:true }
		}
	});
	
});

</script>

<div style="background-color:#ffffff;">
<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>
	<div class="edit-panel">

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'History of Uses' ); ?></h1>
			<span>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'couponsave');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">
		<fieldset class="aw-row">
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Coupon Code' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox ac_input" type="text" id="coupon_coupon" name="coupon_coupon" value="<?php echo $data->row->coupon_coupon; ?>" />
					<input type="hidden" name="coupon_id" value="<?php echo $data->row->coupon_id; ?>" />				
				</div>
			</div>
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Username' ); ?></label></div>
				<div class="aw-input">
					<input type="text" id="username" name="username" class="inputbox ac_input" value="<?php echo $data->row->username; ?>" />
					<input type="hidden" id="user_id" name="user_id" value="<?php echo $data->row->user_id; ?>" />
				</div>
			</div>
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'E-mail' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="user_email" size="30" maxlength="255" value="<?php echo $data->row->user_email; ?>" />
				</div>
			</div>
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Discount (Product)' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="total_product" size="30" maxlength="255" value="<?php echo $data->row->total_product; ?>" />
				</div>
			</div>
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Discount (Shipping)' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="total_shipping" size="30" maxlength="255" value="<?php echo $data->row->total_shipping; ?>" />
				</div>
			</div>
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Currency Code' ); ?></label></div>
				<div class="aw-input">
					<select name="currency_code">
						<?php foreach ( $data->currency_list as $currency ) {
								$selected = ( $currency->code == $data->row->currency_code ) ? 'SELECTED' : '';
						?>
							<option value="<?php echo $currency->code; ?>" <?php echo $selected; ?>><?php echo $currency->code; ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Discount (Product)' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="total_curr_product" size="30" maxlength="255" value="<?php echo $data->row->total_curr_product; ?>" />
				</div>
			</div>
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Discount (Shipping)' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="total_curr_shipping" size="30" maxlength="255" value="<?php echo $data->row->total_curr_shipping; ?>" />
				</div>
			</div>
			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Order ID' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="order_id" size="30" maxlength="255" value="<?php echo $data->row->order_id; ?>" />
				</div>
			</div>
		</fieldset>
		</div>

		<div class="submitpanel"><span>
			<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'couponsave');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
		</span><div class="clear"></div></div>

	</div>

<input type="hidden" name="id" value="<?php echo $data->row->id; ?>" />
<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
</div>
