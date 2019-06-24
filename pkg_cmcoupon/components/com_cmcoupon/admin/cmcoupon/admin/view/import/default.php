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
<!--

jQuery(document).ready(function() {
	
	if(typeof window.FormData === 'undefined') {
		jQuery('#imagemanagerFieldsetUploadnosupport').show();
	}
	else {
		jQuery('#imagemanagerFieldsetUpload').show();
	}

});

function export_coupons(coupon_ids) {
	coupon_ids = encodeURIComponent( jQuery.trim( coupon_ids ) );
	window.location.href = "<?php echo AC()->ajax_url(); ?>&type=admin&view=import&task=export&coupon_ids="+coupon_ids;
}

//-->
</script>



<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

	<div class="edit-panel">

		<div id="imagemanagerFieldsetUploadnosupport" class="hide">
			<div class="inner">
		<fieldset id="" class="adminform hide"><legend><?php echo AC()->lang->__( 'Upload' ); ?></legend>
			&nbsp; &nbsp; &nbsp;<?php echo AC()->lang->__( 'Your browser does not support ajax upload' ); ?><br /><br />
		</fieldset>
			</div>
		</div>

		<div id="imagemanagerFieldsetUpload">
		
			<div class="submitpanel">
				<h1><?php echo AC()->lang->__( 'Import' ); ?></h1>
				<span>
					<button type="button" onclick="submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
				</span>
				<div class="clear"></div>
			</div>
				
			<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

			<div class="inner">
			<fieldset class="aw-row">

				<div class="aw-row">
					<div class="aw-label" style="width:200px;"><label><?php echo AC()->lang->__( 'If there are any errors still save coupons with no errors' ); ?></label></div>
					<div class="aw-input">
						<input value="1" name="store_none_errors" type="checkbox">
					</div>
				</div>

				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'File' ); ?></label></div>
					<div class="aw-input">
						<input id="imagemanagerupload" name="file" type="file"  accept=".csv" />
					</div>
				</div>

			</fieldset>
			</div>
			
			<div class="submitpanel"><span>
				<button type="button" onclick="submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
			</span><div class="clear"></div></div>
		
		</div>


	</div>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>


<div class="edit-panel">
	<div>
		<div class="inner">
		<fieldset class="aw-row">
			<ul>
				<li>
					<div><label><?php echo AC()->lang->__( 'Export sample file' ); ?></label></div>
					<div style="padding-left:20px;">
						<form method="post" action="<?php echo AC()->ajax_url(); ?>">
							<input type="hidden" name="type" value="admin" />
							<input type="hidden" name="view" value="import" />
							<input type="hidden" name="task" value="export" />
							<table><tr valign="top">
							<td>
								<input type="text" name="coupon_ids" value="" class="inputbox" style="width:100%;" size="1" />
								<p class="helper_text"><?php echo AC()->lang->__( 'coupon_ids comma separated or leave blank for all' ); ?></p>
							</td>
							<td>
								<button type="submit" value="submit" class="button button-large"><?php echo AC()->lang->__( 'Export' ); ?></button>
							</td>
							</tr></table>
						</form>
					</div>
				</li>
				<li><?php echo AC()->lang->__( 'To ADD new entry, leave id column blank' ); ?></li>
				<li><?php echo AC()->lang->__( 'To EDIT an entry, enter the coupon_id in the id column' ); ?></li>
			</ul>
		</fieldset>
		</div>
	</div>
</div>
