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

<script>
var base_url = "<?php echo AC()->ajax_url(); ?>";

jQuery(document).ready(function() {
	
	update_cron_url();
	
	jQuery(".js-data-balancecategoryexclude-ajax").select2({
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
					element: 'category'
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

	jQuery(".js-data-balanceshippingexclude-ajax").select2({
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
					element: 'shipping'
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
	
	hideOtherLanguage("<?php echo $data->default_language; ?>");
	
});


function error_messages_debug() {
	jQuery("input.error_message").each(function() {
		var data = jQuery(this).closest('td').parent().find('.key span').html();
		jQuery(this).val(data);
	});
}
function error_messages_clear() {
	jQuery("input.error_message").each(function() {
		jQuery(this).val('');
	});
}

function update_cron_url() {
	key = adminForm.elements['params[cron_key]'].value;
	if( document.getElementById('cronkey_in_url') != undefined ) document.getElementById('cronkey_in_url').innerHTML = key;
	
}

function resetTables() {
	form = document.adminForm2;
	form.task.value = 'configResetTables';
	form.submit();
}
</script>
<style>
	.tabs-wrap { border-left; 1px solid #eee; border-right: 1px solid #eee; }
	.awcontrols .awbtn-group.awbtn-group-yesno > .awbtn { min-width: 25px; }
	input.error_message { width: 300px; }
	.panel .panel_table>tbody>tr>td  { padding: 7px; }
	.panel .panel_table td.key { background-color: #f8f8f8; border-bottom: 1px solid #ddd; }
		
</style>

<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

<div class="inside">

	<div class="tabs-wrap">

		<div class="submitpanel" style="margin-bottom:0;">
			<h1><?php echo AC()->lang->__( 'Configuration' ); ?></h1>
			<span>
				<button type="button" onclick="submitForm(this.form, 'apply');" class="button button-large button-apply"><?php echo AC()->lang->__( 'Apply' ); ?></button>
				<button type="button" onclick="submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
			</span>
			<div class="clear"></div>
		</div>

		<ul class="wc-tabs" style="">
			<li class=""><a href="#tab_div_general"><span><?php echo AC()->lang->__( 'General' ); ?></span></a></li>
			<li class=""><a href="#tab_div_multiplecoupon"><span><?php echo AC()->lang->__( 'Multiple Coupons' ); ?></span></a></li>
			<li class=""><a href="#tab_div_balance"><span><?php echo AC()->lang->__( 'CmCoupon Balance' ); ?></span></a></li>
			<li class=""><a href="#tab_div_trigger"><span><?php echo AC()->lang->__( 'Triggers' ); ?></span></a></li>
			<li class=""><a href="#tab_div_giftcert"><span><?php echo AC()->lang->__( 'Gift Certificate Products' ); ?></span></a></li>
			<li class=""><a href="#tab_div_reminder"><span><?php echo AC()->lang->__( 'Reminders' ); ?></span></a></li>
			<li class=""><a href="#tab_div_errormsg"><span><?php echo AC()->lang->__( 'Coupon Code Error Description' ); ?></span></a></li>
			<li class=""><a href="#tab_div_advanced"><span><?php echo AC()->lang->__( 'Advanced' ); ?></span></a></li>
			
		</ul>
			
		<div id="tab_div_general" class="panel">
			<table class="panel_table">
			<?php if ( ! empty( $data->estorelist ) && is_array( $data->estorelist ) && count( $data->estorelist ) > 1 ) { ?>
				<tr>
					<td><?php echo AC()->lang->__( 'Shop' ); ?></td>
					<td><select name="params[estore]">
							<?php foreach ( $data->estorelist as $estore ) { ?>
								<option value="<?php echo $estore; ?>"
								<?php if ( AC()->param->get( 'estore' ) == $estore ) { ?>
									SELECTED
								<?php } ?>
								><?php echo ucfirst( $estore ); ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			<?php } ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Enable Store Coupons' ),'enable_store_coupon' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Case Sensitive Coupon Code' ),'is_case_sensitive' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Calculate the discount before tax' ) . ' (' . AC()->lang->__( 'Gift Certificates' ) . ')','enable_giftcert_discount_before_tax' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Calculate the discount before tax' ) . ' (' . AC()->lang->__( 'Coupons' ) . ')','enable_coupon_discount_before_tax' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Enable zero value coupon' ),'enable_zero_value_coupon' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Enable negative value coupon' ),'enable_negative_value_coupon' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Store generated vouchers for front end display' ),'enable_frontend_image' ); ?>
			<tr><td class="key"><?php echo AC()->lang->__( 'CSV Delimiter' ); ?></td>
				<td><select name="params[csvDelimiter]">
						<option value="," <?php if( AC()->param->get( 'csvDelimiter', ',' )==',' ) echo 'SELECTED'; ?>>,</option>
						<option value=";" <?php if( AC()->param->get( 'csvDelimiter', ',' )==';' ) echo 'SELECTED'; ?>>;</option>
					</select>
				</td>
			</tr>
			<?php
			if ( ! empty( $data->inject->general ) ) {
				foreach ( $data->inject->general as $item ) {
			?>
				<tr><td class="key"><?php echo $item->label; ?></td>
					<td><?php echo $item->field; ?></td>
				</tr>
			<?php
				}
			}
			?>
			</table>

		</div>
		<div id="tab_div_multiplecoupon" class="panel">
			<table class="panel_table">
			<?php echo $this->display_yes_no( AC()->lang->__( 'Activate' ),'enable_multiple_coupon' ); ?>
			<tr><td class="key"><?php echo AC()->lang->__( 'All' ); ?> (<?php echo AC()->lang->__( 'Max' ); ?>)</td>
				<td nowrap><input type="text" size="4" name="params[multiple_coupon_max]" value="<?php echo AC()->param->get( 'multiple_coupon_max', '' ); ?>" > &nbsp;</td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Automatic Discounts' ); ?> (<?php echo AC()->lang->__( 'Max' ); ?>)</td>
				<td nowrap><input type="text" size="4" name="params[multiple_coupon_max_auto]" value="<?php AC()->param->get( 'multiple_coupon_max_auto', '' ); ?>" > &nbsp;</td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Gift Certificates' ); ?> (<?php echo AC()->lang->__( 'Max' ); ?>)</td>
				<td nowrap><input type="text" size="4" name="params[multiple_coupon_max_giftcert]" value="<?php echo AC()->param->get( 'multiple_coupon_max_giftcert', '' ); ?>" > &nbsp;</td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Coupons' ); ?> (<?php echo AC()->lang->__( 'Max' ); ?>)</td>
				<td nowrap><input type="text" size="4" name="params[multiple_coupon_max_coupon]" value="<?php echo AC()->param->get( 'multiple_coupon_max_coupon', '' ); ?>" > &nbsp;</td>
			</tr>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Apply only one discount per product' ),'multiple_coupon_product_discount_limit' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Reorder gift certificates last' ),'multiple_coupon_reorder_giftcert_last' ); ?>
			<?php
			if ( ! empty( $data->inject->multiplecoupon ) ) {
				foreach ( $data->inject->multiplecoupon as $item ) {
			?>
				<tr><td class="key"><?php echo $item->label; ?></td>
					<td><?php echo $item->field; ?></td>
				</tr>
			<?php
				}
			}
			?>
			</table>


		</div>
		<div id="tab_div_balance" class="panel">
			<table class="panel_table">
			<?php echo $this->display_yes_no( AC()->lang->__( 'Activate' ),'enable_frontend_balance' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Automatically add valid Gift Certificate to Balance on coupon usage' ),'enable_frontend_balance_isauto' ); ?>
			<tr><td class="key"><?php echo AC()->lang->__( 'Category' ); ?></td>
				<td>
					<select name="params[<?php echo CMCOUPON_ESTORE; ?>_balance_category_exclude][]"  class="js-data-balancecategoryexclude-ajax noselect2 select" MULTIPLE="multiple" style="width:250px;">
						<?php
						$tmp = AC()->param->get( CMCOUPON_ESTORE . '_balance_category_exclude', '' );
						if ( ! empty( $tmp ) ) {
							foreach ( $tmp as $tmp2 ) {
								$dbresult = AC()->store->get_categorys( $tmp2 );
								if ( ! empty( $dbresult[ $tmp2 ] ) ) {
									echo '<option value="' . $dbresult[ $tmp2 ]->id . '" SELECTED>' . $dbresult[ $tmp2 ]->label . '</option>';
								}
							}
						}
						?>
					</select> <label for="asset1_mode_rd_exclude" class="awbtn active awbtn-danger"><?php echo AC()->lang->__( 'Exclude' ); ?></label>
				</td>
			</tr>
			<tr>
				<td class="key"><?php echo AC()->lang->__( 'Shipping' ); ?></td>
				<td>
					<select name="params[<?php echo CMCOUPON_ESTORE; ?>_balance_shipping_exclude][]"  class="js-data-balanceshippingexclude-ajax noselect2 inputbox" MULTIPLE="multiple" style="width:250px;">
						<?php
						$tmp = AC()->param->get( CMCOUPON_ESTORE . '_balance_shipping_exclude', '' );
						if ( ! empty( $tmp ) ) {
							foreach ( $tmp as $tmp2 ) {
								$dbresult = AC()->store->get_shippings( $tmp2 );
								if ( ! empty( $dbresult[ $tmp2 ] ) ) {
									echo '<option value="' . $dbresult[ $tmp2 ]->id . '" SELECTED>' . $dbresult[ $tmp2 ]->label . '</option>';
								}
							}
						}
						?>
					</select> <label for="asset1_mode_rd_exclude" class="awbtn active awbtn-danger"><?php echo AC()->lang->__( 'Exclude' ); ?></label>
				</td>
			</tr>
			<?php
			if ( ! empty( $data->inject->balance ) ) {
				foreach ( $data->inject->balance as $item ) {
			?>
				<tr><td class="key"><?php echo $item->label; ?></td>
					<td><?php echo $item->field; ?></td>
				</tr>
			<?php
				}
			}
			?>
			</table>
		
		
		
		</div>
		<div id="tab_div_trigger" class="panel">
	
			<table class="panel_table">
			<tr valign="top"><td class="key"><?php echo AC()->lang->__( 'Restore coupon\'s number of uses if order is not processed' ); ?></td>
				<td>
					<select id="paramsordercancel_order_status" name="params[ordercancel_order_status][]" multiple="" class="inputbox" size="7" style="width:100%;">
						<?php foreach ( $data->orderstatuses as $orderstatus ) { ?>
							<option value="<?php echo $orderstatus->order_status_code; ?>"  
								<?php
								if ( in_array( $orderstatus->order_status_code, AC()->param->get( 'ordercancel_order_status', array() ) ) ) {
									echo 'SELECTED';
								}
								?>
							><?php echo $orderstatus->order_status_name; ?></option>
						<?php } ?>
					</select>
					<div style="width:250px;"></div>
				</td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Delete expired coupons x days after expiration' ); ?></td>
				<td><input type="text" size="4" name="params[delete_expired]" value="<?php echo AC()->param->get( 'delete_expired', '' ); ?>" ></td>
			</tr>
			<?php
			if ( ! empty( $data->inject->trigger ) ) {
				foreach ( $data->inject->trigger as $item ) {
			?>
				<tr><td class="key"><?php echo $item->label; ?></td>
					<td><?php echo $item->field; ?></td>
				</tr>
			<?php
				}
			}
			?>
			</table>
		</div>

		<div id="tab_div_giftcert" class="panel">
		
			<table class="panel_table">
			<tr><th colspan="2"><?php echo AC()->lang->__( 'General' ); ?></th></tr>

			<tr><td class="key" valign="top"><?php echo AC()->lang->__( 'Order status for sending automatic email' ); ?></td>
				<td><select id="paramsgiftcert_order_status" name="params[giftcert_order_status][]" multiple="" class="inputbox" size="7" style="width:100%;">
						<?php foreach ( $data->orderstatuses as $orderstatus ) { ?>
							<option value="<?php echo $orderstatus->order_status_code; ?>"  
								<?php
								if ( in_array( $orderstatus->order_status_code, AC()->param->get( 'giftcert_order_status', array() ) ) ) {
									echo 'SELECTED';
								}
								?>
							><?php echo $orderstatus->order_status_name; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Minimum code length' ); ?></td>
				<td><input type="text" size="75" name="params[giftcert_min_length]" value="<?php echo AC()->param->get( 'giftcert_min_length', 8 ); ?>" ></td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Maximum code length' ); ?></td>
				<td><input type="text" size="75" name="params[giftcert_max_length]" value="<?php echo AC()->param->get( 'giftcert_max_length', 12 ); ?>" ></td>
			</tr>
			
			<?php
			/*
			<tr><td class="key"><?php echo AC()->lang->__( 'Custom Attribute Recipient Name Field' ); ?></td>
				<td><input type="text" size="75" name="params[<?php echo CMCOUPON_ESTORE; ?>_giftcert_field_recipient_name]" value="<?php echo AC()->param->get(CMCOUPON_ESTORE . '_giftcert_field_recipient_name', 0); ?>" ></td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Custom Attribute Recipient Email Field' ); ?></td>
				<td><input type="text" size="75" name="params[<?php echo CMCOUPON_ESTORE; ?>_giftcert_field_recipient_email]" value="<?php echo AC()->param->get(CMCOUPON_ESTORE . '_giftcert_field_recipient_email', 0); ?>" ></td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Custom Attribute Recipient Message Field' ); ?></td>
				<td><input type="text" size="75" name="params[<?php echo CMCOUPON_ESTORE; ?>_giftcert_field_recipient_message]" value="<?php echo AC()->param->get(CMCOUPON_ESTORE . '_giftcert_field_recipient_message', 0); ?>" ></td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Custom Attribute From Name Field' ); ?></td>
				<td><input type="text" size="75" name="params[<?php echo CMCOUPON_ESTORE; ?>_giftcert_field_from_name]" value="<?php echo AC()->param->get(CMCOUPON_ESTORE . '_giftcert_field_from_name', 0); ?>" ></td>
			</tr>
			*/
			?>
			
			<?php
			if ( ! empty( $data->inject->giftcert ) ) {
				foreach ( $data->inject->giftcert as $item ) {
			?>
				<tr><td class="key"><?php echo $item->label; ?></td>
					<td><?php echo $item->field; ?></td>
				</tr>
			<?php
				}
			}
			?>
			</table>
			
			<br /><br />
		
		
			<table class="panel_table">
			<tr><th colspan="2"><?php echo AC()->lang->__( 'Vendor' ); ?></th></tr>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Activate' ), 'giftcert_vendor_enable' ); ?>
			<tr><td class="key"><?php echo AC()->lang->__( 'Email Subject' ); ?></td>
				<td><input type="text" name="params[giftcert_vendor_subject]" value="<?php echo AC()->param->get( 'giftcert_vendor_subject', '' ); ?>" size="35"></td>
			</tr>
			<tr valign="top">
				<td class="key"><?php echo AC()->lang->__( 'Email Body' ); ?>
					<br /><br /><table style="text-align:left;font-weight:normal;" align="right" cellspacing=0><tr><th><?php echo AC()->lang->__( 'Tags' ); ?></th></tr>
					<tr><td>{vendor_name}</td></tr>
					<tr><td>{vouchers}</td></tr>
					<tr><td>{purchaser_first_name}</td></tr>
					<tr><td>{purchaser_last_name}</td></tr>
					<tr><td>{today_date}</td></tr>
					<tr><td>{order_id}</td></tr>
					<tr><td>{order_number}</td></tr>
					</table>
				</td>
				<td>
					<?php
					echo AC()->helper->get_editor( AC()->param->get( 'giftcert_vendor_email', '' ), 'paramsgiftcert_vendor_email', array(
						'textarea_name' => 'params[giftcert_vendor_email]',
					) );
					?>
				</td>
			</tr>
			<tr><td class="key">{vouchers}</td>
				<td><input type="text" name="params[giftcert_vendor_voucher_format]" value="<?php echo AC()->param->get( 'giftcert_vendor_voucher_format', '<div>{voucher} - {price} - {product_name}</div>' ); ?>" size="65"></td>
			</tr>

			</table>
			
			<br /><br />

		
		
			<table class="panel_table">
			<tr><th colspan="2"><?php echo AC()->lang->__( 'Debug' ); ?></th></tr>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Activate' ),'enable_giftcert_debug' ); ?>
			<tr valign="top"><td class="key"><?php echo AC()->lang->__( 'Output' ); ?></td>
				<td><textarea cols="100" rows="7" style="width:500px;">
					<?php
					if ( file_exists( CMCOUPON_TEMP_DIR . '/cmcoupon_giftcert.log' ) ) {
						echo file_get_contents( CMCOUPON_TEMP_DIR . '/cmcoupon_giftcert.log' );
					}
					?>
				</textarea></td>
			</tr>
			</table>
	
		</div>
		<div id="tab_div_reminder" class="panel">

			<div class="card">
				<h2><?php echo AC()->lang->__( 'Cron' ); ?></h2>
				<table class="panel_table">
				<?php echo $this->display_yes_no( AC()->lang->__( 'Activate poorman\'s cron' ), 'cron_enable' ); ?>
				<tr valign="top"><td class="key"><?php echo AC()->lang->__( 'Run poorman\'s cron every (minutes)' ); ?></td>
					<td><input type="text" name="params[cron_minutes]" value="<?php echo AC()->param->get( 'cron_minutes', 30 ); ?>" size="35"></td>
				</tr>
				<tr valign="top"><td class="key"><?php echo AC()->lang->__( 'Cron key' ); ?></td>
					<td><input type="text" name="params[cron_key]" value="<?php echo AC()->param->get( 'cron_key', AC()->coupon->generate_coupon_code() ); ?>" size="35" onkeyup="update_cron_url();">
						<div>
							<?php
							$cron_url = AC()->helper->get_cron_url();
							if ( ! empty( $cron_url ) ) {
								echo $cron_url;
								?>
								<span id="cronkey_in_url"></span>
								<?php
							}
							?>
						</div>
					</td>
				</tr>
				<?php
				if ( ! empty( $data->inject->reminder ) ) {
					foreach ( $data->inject->reminder as $item ) {
				?>
					<tr><td class="key"><?php echo $item->label; ?></td>
						<td><?php echo $item->field; ?></td>
					</tr>
				<?php
					}
				}
				?>
				<tr valign="top"><td class="key"><?php echo AC()->lang->__( 'Last run' ); ?></td>
					<td style="padding-top:7px;"><?php echo $data->cron_last_run; ?></td>
				</tr>
				</table>
			</div>

			<div class="card">
				<h2><?php echo AC()->lang->__( 'Reminder' ); ?> 1</h2>
				<table class="panel_table">
				<?php echo $this->display_yes_no( AC()->lang->__( 'Activate' ),'reminder_1_enable' ); ?>
				<tr><td class="key"><?php echo AC()->lang->__( 'Day(s)' ); ?></td>
					<td><input type="text" name="params[reminder_1_days]" value="<?php echo AC()->param->get( 'reminder_1_days', '' ); ?>" size="10"></td>
				</tr>
				<tr><td class="key"><?php echo AC()->lang->__( 'Email Template' ); ?></td>
					<td><select name="params[reminder_1_profile]">
							<?php foreach ( $data->profilelist as $row ) { ?>
								<option value="<?php echo $row->id; ?>"
									<?php if ( AC()->param->get( 'reminder_1_profile' ) == $row->id ) { ?>
										SELECTED
									<?php } ?>
								><?php echo $row->title; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				</table>
			</div>

			<div class="card">
				<h2><?php echo AC()->lang->__( 'Reminder' ); ?> 2</h2>
				<table class="panel_table">
				<?php echo $this->display_yes_no( AC()->lang->__( 'Activate' ), 'reminder_2_enable' ); ?>
				<tr><td class="key"><?php echo AC()->lang->__( 'Day(s)' ); ?></td>
					<td><input type="text" name="params[reminder_2_days]" value="<?php echo AC()->param->get( 'reminder_2_days', '' ); ?>" size="10"></td>
				</tr>
				<tr><td class="key"><?php echo AC()->lang->__( 'Email Template' ); ?></td>
					<td><select name="params[reminder_2_profile]">
							<?php foreach ( $data->profilelist as $row ) { ?>
								<option value="<?php echo $row->id; ?>" 
									<?php if ( AC()->param->get( 'reminder_2_profile' ) == $row->id ) { ?>
										SELECTED
									<?php } ?>
								><?php echo $row->title; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				</table>
			</div>
	
			<div class="card">
				<h2><?php echo AC()->lang->__( 'Reminder' ); ?> 3</h2>
				<table class="panel_table">
				<?php echo $this->display_yes_no( AC()->lang->__( 'Activate' ), 'reminder_3_enable' ); ?>
				<tr><td class="key"><?php echo AC()->lang->__( 'Day(s)' ); ?></td>
					<td><input type="text" name="params[reminder_3_days]" value="<?php echo AC()->param->get( 'reminder_3_days', '' ); ?>" size="10"></td>
				</tr>
				<tr><td class="key"><?php echo AC()->lang->__( 'Email Template' ); ?></td>
					<td><select name="params[reminder_3_profile]">
							<?php foreach ( $data->profilelist as $row ) { ?>
								<option value="<?php echo $row->id; ?>"
								<?php if ( AC()->param->get( 'reminder_3_profile' ) == $row->id ) { ?>
									SELECTED
								<?php } ?>
								><?php echo $row->title; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				</table>
			</div>
			
	
		</div>
		<div id="tab_div_errormsg" class="panel">
			<table class="panel_table">
			<tr><td align="right" colspan="2">
				<button type="button" onclick="error_messages_debug();return false;"><?php echo AC()->lang->__( 'Debug' ); ?></button>
				<button type="button" onclick="error_messages_clear();return false;"><?php echo AC()->lang->__( 'Clear' ); ?></button>
			</td></tr>
			<?php
			$lang_params = array(
				'class' => 'error_message',
			);
			?>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'No record, unpublished or expired' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errNoRecord', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Minimum value not reached' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errMinVal', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Minimum product quantity not reached' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errMinQty', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Customer not logged in' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errUserLogin', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Customer not on customer list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errUserNotOnList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Customer not on shopper group list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errUserGroupNotOnList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Per user: already used coupon max number of times' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errUserMaxUse', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Total: already used coupon max number of times' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errTotalMaxUse', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Product(s) not on product list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errProductInclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Product(s) on product list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errProductExclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Product(s) not on category list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCategoryInclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Product(s) on category list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCategoryExclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<?php if ( AC()->helper->vars( 'asset_type', 'manufacturer' ) !== null ) { ?>
				<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Product(s) not on manufacturer list' ); ?></span></td>
					<td><?php echo AC()->lang->write_fields( 'text', 'errManufacturerInclList', $data->language_data, $lang_params ); ?></td>
				</tr>
				<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Product(s) on manufacturer list' ); ?></span></td>
					<td><?php echo AC()->lang->write_fields( 'text', 'errManufacturerExclList', $data->language_data, $lang_params ); ?></td>
				</tr>
			<?php } ?>
			<?php if ( AC()->helper->vars( 'asset_type', 'vendor' ) !== null ) { ?>
				<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Product(s) not on vendor list' ); ?></span></td>
					<td><?php echo AC()->lang->write_fields( 'text', 'errVendorInclList', $data->language_data, $lang_params ); ?></td>
				</tr>
				<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Product(s) on vendor list' ); ?></span></td>
					<td><?php echo AC()->lang->write_fields( 'text', 'errVendorExclList', $data->language_data, $lang_params ); ?></td>
				</tr>
			<?php } ?>
			<?php if ( AC()->helper->vars( 'asset_type', 'custom' ) !== null ) { ?>
				<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Product(s) not on custom list' ); ?></span></td>
					<td><?php echo AC()->lang->write_fields( 'text', 'errCustomInclList', $data->language_data, $lang_params ); ?></td>
				</tr>
				<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Product(s) on custom list' ); ?></span></td>
					<td><?php echo AC()->lang->write_fields( 'text', 'errCustomExclList', $data->language_data, $lang_params ); ?></td>
				</tr>
			<?php } ?>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'No shipping selected' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errShippingSelect', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'No valid shipping selected' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errShippingValid', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Selected shipping not on shipping list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errShippingInclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Selected shipping on shipping list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errShippingExclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Gift certificate already used' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errGiftUsed', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Coupon value definition, threshold not reached' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errProgressiveThreshold', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Discounted Products' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errDiscountedExclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Exclude Gift Certificate Products' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errGiftcertExclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'BuyXY (include) Product(s) not on list 1' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errBuyXYList1IncludeEmpty', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'BuyXY (exclude) Product(s) on list 1' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errBuyXYList1ExcludeEmpty', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'BuyXY (include) Product(s) not on list 2' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errBuyXYList2IncludeEmpty', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'BuyXY (exclude) Product(s) on list 2' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errBuyXYList2ExcludeEmpty', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Include' ); ?>) <?php echo AC()->lang->__( 'Country' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCountryInclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Exclude' ); ?>) <?php echo AC()->lang->__( 'Country' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCountryExclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Include' ); ?>) <?php echo AC()->lang->__( 'State' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCountrystateInclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Exclude' ); ?>) <?php echo AC()->lang->__( 'State' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCountrystateExclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Include' ); ?>) <?php echo AC()->lang->__( 'Payment Method' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errPaymentMethodInclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Exclude' ); ?>) <?php echo AC()->lang->__( 'Payment Method' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errPaymentMethodExclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<?php
			if ( ! empty( $data->inject->errormsg ) ) {
				foreach ( $data->inject->errormsg as $item ) {
			?>
				<tr><td class="key"><?php echo $item->label; ?></td>
					<td><?php echo $item->field; ?></td>
				</tr>
			<?php
				}
			}
			?>
			

			</table>
		</div>
		<div id="tab_div_advanced" class="panel">

	
			<div class="card">
				<table class="panel_table">
				<?php
				/*
				<tr><td class="key"><?php echo AC()->lang->__( 'Reset all tables' ); ?></td>
					<td><input type="button" onclick="resetTables();" value="<?php echo AC()->lang->__( 'Submit' ); ?>" /></td>
				</tr>
				*/
				?>
				<?php echo $this->display_yes_no( AC()->lang->__( 'Show unpublished Categories' ), 'display_category_unpublished' ); ?>
				<?php echo $this->display_yes_no( AC()->lang->__( 'Coupon list extended view' ), 'enable_coupon_list_extended' ); ?>
				<?php echo $this->display_yes_no( AC()->lang->__( 'Exclude children products from discount' ), 'disable_coupon_product_children' ); ?>
				<?php echo $this->display_yes_no( AC()->lang->__( 'Remove space when processing coupon' ), 'is_space_insensitive' ); ?>

				<?php
				if ( ! empty( $data->inject->advanced ) ) {
					foreach ( $data->inject->advanced as $item ) {
				?>
					<tr><td class="key"><?php echo $item->label; ?></td>
						<td><?php echo $item->field; ?></td>
					</tr>
				<?php
					}
				}
				?>
				</table>
			</div>

			<?php
			/*
			<div class="card">
				<h2><?php echo AC()->lang->__( 'External Script' ); ?></h2>
				<table class="panel_table">
					<?php echo 	$this->display_yes_no(AC()->lang->__( 'Activate' ),'enable_webservice' ); ?>

					<tr><td class="key"><?php echo AC()->lang->__( 'Password' ); ?></td>
						<td nowrap><input type="text" size="" name="params[webservice_password]" value="<?php echo AC()->param->get( 'webservice_password', '' ); ?>" > &nbsp;</td>
					</tr>
					<tr valign="top"><td class="key"><?php echo AC()->lang->__( 'User' ); ?></td>
						<td nowrap>
							<textarea style="width:200px;height:50px;" cols="100" rows="4" name="params[webservice_users]"><?php echo AC()->param->get( 'webservice_users', '' ); ?></textarea>
							<div style="display:inline-block;">
							Ex:<br /> user1:password1<br>user2:password2
							</div>
						</td>
					</tr>
					<tr valign="top"><td class="key"><?php echo AC()->lang->__( 'Allowed IPs' ); ?></td>
						<td nowrap>
							<textarea style="width:200px;height:50px;" cols="100" rows="4" name="params[webservice_ips_allowed]"><?php echo AC()->param->get( 'webservice_ips_allowed', '' ); ?></textarea>
							<div style="display:inline-block;">
							Ex:<br /> 196.168.11.1<br>255.255.255.11
							</div>
						</td>
					</tr>
				</table>
			</div>
			*/
			?>
	
	
	
		</div>
		
		<div class="clear"></div>
		

	</div>
	<div class="submitpanel"><span>
		<button type="button" onclick="submitForm(this.form, 'apply');" class="button button-large button-apply"><?php echo AC()->lang->__( 'Apply' ); ?></button>
		<button type="button" onclick="submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
	</span><div class="clear"></div></div>
	</div>


<input type="hidden" name="casesensitiveold" value="<?php echo $data->is_case_sensitive ? 1 : 0; ?>'" />
<?php
echo AC()->helper->render_layout( 'admin.form.footer' );
