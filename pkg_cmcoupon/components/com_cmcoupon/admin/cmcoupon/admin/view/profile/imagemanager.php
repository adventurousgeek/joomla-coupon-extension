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

?>

<script language="javascript" type="text/javascript">
<!--

jQuery(document).ready(function() {
	document.querySelector('#imagemanagerupload').addEventListener('change', handleFileSelect, false);
	
	if(typeof window.FormData === 'undefined') {
		jQuery('#imagemanagerFieldsetUploadnosupport').show();
	}
	else {
		jQuery('#imagemanagerFieldsetUpload').show();
	}

});

function handleFileSelect(e) {
	
	if(!e.target.files) return;
	if(e.target.files.length==1) return;
	
	thehtml = '';
	var files = e.target.files;
	for(var i=0; i<files.length; i++) {
		var f = files[i];
		thehtml += f.name + "<br/>";
	}
	jQuery('#imagemanagerSelectedFiles').html(thehtml);
	
}


function imagemanagerDoUpload() {
	imagemanagerFormsubmitStart();
	var formData = new FormData(document.adminFormImagemanagerUpload);
	
	jQuery.ajax({
		url: "<?php echo AC()->ajax_url(); ?>",
		type: 'POST',
		data: formData,
		async: false,
		cache: false,
		contentType: false,
		processData: false,
		success: function (returndata) {
			imagemanagerFormsubmitEnd()
			if(jQuery.trim(returndata)=='') {
				//refreshPage();
				return;
			}
			alert(returndata);
		}
	});
}

function imagemanagerDoDelete(form) {
	form_data = jQuery(form).serialize();
	imagemanagerFormsubmitStart();
	
	jQuery.ajax({
		url: "<?php echo AC()->ajax_url(); ?>",
		type: 'POST',
		data: form_data,
		success: function (returndata) { imagemanagerFormsubmitEnd(); }
	});
}


function imagemanagerFormsubmitStart() {
	jQuery('#imagemanagertbody').empty();
	jQuery('#imagemanagerImagewaiting')
		.empty()
		.html('<div style="text-align:center;margin-top:20px;"><img id="waitingimg_parent" src="<?php echo CMCOUPON_ASEET_URL; ?>/images/loading.gif" height="60" /></div>')
	;

}

function imagemanagerFormsubmitEnd() {
	
	jQuery.ajax({
		url: "<?php echo AC()->ajax_url(); ?>",
		type: 'get',
		data: 'type=admin&view=profile&layout=imagemanager&task=imagelist',
		dataType: 'json',
		success: function (list) {
			imagepath = "<?php echo CMCOUPON_GIFTCERT_URL; ?>/images";
			
			if(typeof(document.getElementById('image')) != "undefined" && document.getElementById('image') !== null) {
				imageSelect = document.getElementById('image');
				selectedItem = imageSelect[imageSelect.selectedIndex].value;
				imageSelect.length = 1;
			}

			
			html = '';
			countme = 0;
			for (var key in list) {
				if (! list.hasOwnProperty(key)) continue;
				countme++;
				
				
				html += '\
					<tr valign="top">\
						<td>'+countme+'</td>\
						<td width="7" class="checkcolumn"><input name="ids[]" value="'+jQuery("<div/>").text(key).html()+'" type="checkbox" /></td>\
						<td width="1%">\
							<img src="'+imagepath+'/'+key+'" style="max-height:50px;max-width:75px;" />\
						</td>\
						<td>'+key+'&nbsp;</td>\
					</tr>\
				';
				
				if(typeof(document.getElementById('image')) != "undefined" && document.getElementById('image') !== null) {
					var opt = document.createElement('option');
					opt.innerHTML = key;
					opt.value = key;
					imageSelect.appendChild(opt);
					
					// set the default
					if(selectedItem==key) imageSelect.selectedIndex = opt.index;
				}
				
			}
			
			jQuery('#imagemanagertbody').html(html);
			jQuery('#imagemanagerImagewaiting').empty();
			
			
		}
	});

}
//-->
</script>

<div class="edit-panel">
<div class="inner">

<fieldset id="imagemanagerFieldsetUpload" class="adminform hide"><legend><?php echo AC()->lang->__( 'Upload' ); ?></legend>

	<form id="adminFormImagemanagerUpload" name="adminFormImagemanagerUpload">
		<input type="hidden" name="type" value="admin" />
		<input type="hidden" name="view" value="profile" />
		<input type="hidden" name="layout" value="imagemanager" />
		<input type="hidden" name="task" value="imageupload" />

		<input id="imagemanagerupload" name="upload[]" type="file" multiple="multiple" accept=".png, .jpg, image/png, image/jpeg" />

		<button type="button" class="btn btn-success" onclick="imagemanagerDoUpload();"><?php echo AC()->lang->__( 'Upload' ); ?></button>
		<div id="imagemanagerSelectedFiles"></div>
	</form>

</fieldset>

<fieldset id="imagemanagerFieldsetUploadnosupport" class="adminform hide"><legend><?php echo AC()->lang->__( 'Upload' ); ?></legend>
	&nbsp; &nbsp; &nbsp;<?php echo AC()->lang->__( 'Your browser does not support ajax upload' ); ?><br /><br />
</fieldset>

<fieldset class="adminform"><legend></legend>

	<form action="#/cmcoupon/profile/imagemanager" method="post" id="adminFormImagemanagerDelete" name="adminFormImagemanagerDelete" enctype="multipart/form-data">
		<input type="hidden" name="type" value="admin" />
		<input type="hidden" name="view" value="profile" />
		<input type="hidden" name="layout" value="imagemanager" />
		<input type="hidden" name="task" value="imagedelete" />

		<button type="button" class="btn btn-default"  onclick="if( confirm('<?php echo htmlentities( AC()->lang->__( 'Are you sure you want to delete the items?' ) ); ?>')) { imagemanagerDoDelete(this.form); }">
			<img style="height:32px;" src="<?php echo CMCOUPON_ASEET_URL; ?>/images/trash-can.png" />
		</button>
		<table class="adminlist" cellspacing="1">
		<thead>
			<tr>
				<th width="5"><?php echo AC()->lang->__( 'Num' ); ?></th>
				<th width="5"><input type="checkbox" name="toggle" value="" onClick="jQuery(this.form).find('td.checkcolumn input:checkbox').prop('checked',this.checked);" /></th>
				<th width="" nowrap="nowrap" colspan="2"><?php echo AC()->lang->__( 'Filename' ); ?></th>
			</tr>
		</thead>

		<tbody id="imagemanagertbody">
			<?php
			$imagepath = CMCOUPON_GIFTCERT_URL . '/images';

			$countme = 0;
			$listcount = count( $data->rows );
			foreach ( $data->rows as $key => $row ) {
				$countme++;
			?>
			<tr class="row<?php echo ( $countme % 2 ); ?>" valign="top">
				<td><?php echo $countme; ?></td>
				<td width="7" class="checkcolumn"><input name="ids[]" value="<?php echo htmlentities( $key ); ?>" type="checkbox" /></td>
				<td width="1%">
					<?php if ( ! empty( $imagepath ) && $listcount <= 50 ) { ?>
					<img src="<?php echo $imagepath . '/' . $key; ?>" style="max-height:50px;max-width:75px;" />
					<?php } else { ?>
					&nbsp;
					<?php } ?>
				</td>
				<td><?php echo $key; ?>&nbsp;</td>
			</tr>
			<?php } ?>
		</tbody>

		</table>
		<div id="imagemanagerImagewaiting"></div>

	</form>
</fieldset>

</div>
</div>
