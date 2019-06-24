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
	<h1 class="wp-heading-inline"><?php echo AC()->lang->__( 'Coupons' ); ?></h1>
	<a href="#/cmcoupon/coupon/edit" class="page-title-action"><?php echo AC()->lang->__( 'Add New' ); ?></a>
</div>
<hr class="wp-header-end">

<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

<table class="adminform">
	<tr valign="top">
		<td width="100%">
			<select name="bulkaction">
				<option value="-1"><?php echo AC()->lang->__( 'Bulk Actions' ); ?></option>
				<option value="publishbulk"><?php echo AC()->lang->__( 'Publish' ); ?></option>
				<option value="unpublishbulk"><?php echo AC()->lang->__( 'Unpublish' ); ?></option>
				<option value="deletebulk"><?php echo AC()->lang->__( 'Delete' ); ?></option>
			</select>
			<input id="doaction" class="button action" value="Apply" type="button" onclick="if(this.form.bulkaction.value!=-1) submitForm(this.form, this.form.bulkaction.value);">

			<input type="text" name="search" id="search" value="<?php echo $data->search; ?>" class="text_area" />
			<button class="button" onclick="submitForm(this.form,'');"><?php echo AC()->lang->__( 'Search' ); ?></button>
		</td>
		<td nowrap>
			<div>
				<select name="filter_function_type" onchange="submitForm(this.form,'');">
					<option value="">- <?php echo AC()->lang->__( 'Function Type' ); ?> -</option>
					<?php
					foreach ( AC()->helper->vars( 'function_type' ) as $key => $value ) {
						echo '<option value="' . $key . '" ' . ( $data->filter_function_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
					}
					?>
				</select>
				<select name="filter_coupon_value_type" onchange="submitForm(this.form,'');">
					<option value="">- <?php echo AC()->lang->__( 'Percent' ); ?> -</option>
					<?php
					foreach ( AC()->helper->vars( 'coupon_value_type' ) as $key => $value ) {
						echo '<option value="' . $key . '" ' . ( $data->filter_coupon_value_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
					}
					?>
				</select>
				<select name="filter_discount_type" onchange="submitForm(this.form,'');">
					<option value="">- <?php echo AC()->lang->__( 'Discount Type' ); ?> -</option>
					<?php
					foreach ( AC()->helper->vars( 'discount_type' ) as $key => $value ) {
						echo '<option value="' . $key . '" ' . ( $data->filter_discount_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
					}
					?>
				</select>
				<?php if ( ! empty( $data->template_list ) ) { ?>
					<select name="filter_template" onchange="submitForm(this.form,'');">
						<option value="">- <?php echo AC()->lang->__( 'Template' ); ?> -</option>
						<?php
						foreach ( $data->template_list as $key => $value ) {
							echo '<option value="' . $value->id . '" ' . ( $data->filter_template == $value->id ? 'SELECTED' : '' ) . '>' . $value->label . '</option>';
						}
						?>
					</select>
				<?php } ?>
				<?php if ( ! empty( $data->tags ) ) { ?>
					<select name="filter_tag" onchange="submitForm(this.form,'');">
						<option value="">- <?php echo AC()->lang->__( 'Tag' ); ?> -</option>
						<?php
						foreach ( $data->tags as $key => $value ) {
							echo '<option value="' . $value->id . '" ' . ( $data->filter_tag == $value->id ? 'SELECTED' : '' ) . '>' . $value->label . '</option>';
						}
						?>
					</select>
				<?php } ?>
				<select name="filter_state" onchange="submitForm(this.form,'');">
					<option value="">- <?php echo AC()->lang->__( 'Status' ); ?> -</option>
					<?php
					foreach ( AC()->helper->vars( 'state' ) as $key => $value ) {
						echo '<option value="' . $key . '" ' . ( $data->filter_state == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
					}
					?>
				</select>
			</div>
		</td>
	</tr>
</table>

<?php echo $data->table_html; ?>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>


<script type="text/javascript"> 
var base_url = "<?php echo AC()->ajax_url(); ?>";
jQuery(document).ready(function() {
	jQuery('#adminForm').on( 'click', 'button.cancel', function( e ) {
		$tr = jQuery(this).parent().parent();
		$tr.prev().show();
		$tr.hide();
	});
	
	jQuery('#adminForm').on( 'click', 'a.editinline', function( e ) {
		e.preventDefault();
		
		var $a = jQuery(this)
		var id = jQuery(this).data('id');
		var id_detail_row = 'id_detail_row_'+id;
		var $tr = jQuery(this).parent().parent().parent().parent();
		var $table = $tr.parent().parent().parent();
		var colspan = $table.find('th').length;

		if(jQuery("#" + id_detail_row).length == 0) {
			$a.css({display:'none'});
			$a.after('<img id="waitingimg_'+id+'" src="<?php echo CMCOUPON_ASEET_URL; ?>/images/loading.gif" height="15" />');
			jQuery.ajax({
				method: "POST",
				url: base_url,
				data: { type: "ajax", task: "coupondetail", id: id }
			})
			.done(function(data) {
				$tr.after('\
					<tr id="'+id_detail_row+'">\
						<td colspan="'+colspan+'" class="inline-edit-row inline-edit-row-post inline-edit-post quick-edit-row quick-edit-row-post inline-edit-post inline-editor">\
							<button type="button" class="button warning cancel alignleft"><?php echo AC()->lang->__( 'Close' ); ?></button><br class="clear" />\
							'+data+'\
						</td>\
					</tr>\
				');
				
				$a.css({display:''});
				jQuery('#waitingimg_'+id).remove();
				//$tr.find('.row-actions').hide();
				$tr.hide();
			})
			.fail(function() { /*alert( "error" );*/ })
			.always(function() { /*alert( "complete" );*/ });
		}
		else {
			jQuery('#'+id_detail_row).show();
			//$tr.find('.row-actions').hide();
			$tr.hide();
		}
		
	});

});

</script>
