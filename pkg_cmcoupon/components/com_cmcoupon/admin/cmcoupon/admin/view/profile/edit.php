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


<link rel='stylesheet'  href='<?php echo CMCOUPON_ASEET_URL; ?>/css/spectrum.css?ver=<?php echo CMCOUPON_VERSION; ?>' type='text/css' media='all' />
<script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/fabric.js/1.7.2/fabric.min.js?ver=<?php echo CMCOUPON_VERSION; ?>'></script>
<script type='text/javascript' src='<?php echo CMCOUPON_ASEET_URL; ?>/js/spectrum.js?ver=<?php echo CMCOUPON_VERSION; ?>'></script>
   
<style>
table.admintable td.top { width:auto; text-align:center; }
 
.major_section { margin-bottom:35px; }

#mysidebar li.active {
	border:0 #eee solid;
	border-right-width:4px;
}
.main_container {
	margin-left:250px;
}
.sidebar_container {
	float:left;width:250px;
	min-height: 1px;
	position: relative;
	font-size:1.5em;
}
.main_container .wrapper,.sidebar_container .wrapper {
	padding-left: 15px;
	padding-right: 15px;
}
.nav2 {
	padding-left: 0;
	margin-bottom: 0;
	list-style: none;
	overflow:hidden;
	border:1px solid #cccccc;
}
.nav2 > li {
	position: relative;
	display: block;
}
.nav2 > li > a {
	position: relative;
	display: block;
	padding: 10px 15px;
}
.nav2 > li > a:hover,
.nav2 > li > a:focus {
	text-decoration: none;
	background-color: #eee;
}
.nav2 > li.disabled > a {
	color: #999;
}
.nav2 > li.disabled > a:hover,
.nav2 > li.disabled > a:focus {
	color: #999;
	text-decoration: none;
	cursor: not-allowed;
	background-color: transparent;
}
.nav2 .open > a,
.nav2 .open > a:hover,
.nav2 .open > a:focus {
	background-color: #eee;
	border-color: #428bca;
}
.nav2 .nav-divider {
	height: 1px;
	margin: 9px 0;
	overflow: hidden;
	background-color: #e5e5e5;
}
.nav2 > li > a > img {
	max-width: none;
}

#pdf_header_ifr, #pdf_footer_ifr { height:150px !important; }
.small_editor .editor iframe { height:150px !important; }

.profileimagetext { width:50px; }
.profileimagedd { min-width:100px; }

.sp-picker-container input{ height: auto; }






<?php foreach ( $data->fontdd as $font => $display ) { ?>
	@font-face {
		font-family: '<?php echo str_replace( array( '.', ' ' ), '_', $font ); ?>';
		src: url('<?php echo CMCOUPON_GIFTCERT_URL . '/fonts/' . $font; ?>');
	}
<?php } ?>
input[type="radio"].toggle:checked + label {
	/*background-image: linear-gradient(to top,#969696,#727272);*/
	background-image: linear-gradient(to top,#727272,#727272);
	box-shadow: inset 0 1px 6px rgba(41, 41, 41, 0.2),
					  0 1px 2px rgba(0, 0, 0, 0.05);
	cursor: default;
	color: #E6E6E6;
	border-color: transparent;
	text-shadow: 0 1px 1px rgba(40, 40, 40, 0.75);
	border-radius: 20px 0 0 20px;
}

input[type="radio"].toggle + label {
	width:100%;
	padding:0;
	font-weight:bold;
}
input[type="radio"].toggle + label span {
	padding: 4px 12px;
	display:inline-block;
}

input[type="radio"].toggle:checked + label.btn:hover {
	background-color: inherit;
	background-position: 0 0;
	transition: none;
}

input[type="radio"].toggle {
	display:none;
}
.profileimagetable th {
	background-color: #727272;
	color: #E6E6E6;
	box-shadow: inset 0 1px 6px rgba(41, 41, 41, 0.2),
					  0 1px 2px rgba(0, 0, 0, 0.05);
	padding:8px;
}

.profileimagetable .btn {
	display: inline-block;
	padding: 4px 12px;
	margin-bottom: 0;
	font-size: 13px;
	line-height: 18px;
	text-align: center;
	vertical-align: middle;
	cursor: pointer;
	background-color: #f3f3f3;
	color: #333;
	border: 1px solid #b3b3b3;
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
	border-radius: 3px;
	box-shadow: 
}

.profileimagetable { border-collapse: collapse; }

.profileimagetable .table td {
	padding: 8px;
	line-height: 18px;
	text-align: left;
	vertical-align: top;
	border-top: 1px solid #ddd;
}

.profileimagetable .table {
	width: 100%;
	margin-bottom: 18px;
	max-width: 100%;
	background-color: transparent;
	border-collapse: collapse;
	border-spacing: 0;
}

#accordion { min-width:150px; }

</style>  

<div style="background-color:#ffffff;">
<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>
	<div class="edit-panel">

		<div class="submitpanel">
			<h1><?php echo AC()->lang->__( 'Email Template' ); ?></h1>
			<span>
				<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
			</span>
			<div class="clear"></div>
		</div>

		<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

		<div class="inner">

			<div>
				<div class="sidebar_container_holder"></div>
				<div class="sidebar_container">
					<div class="wrapper nav_parent">
						<ul class="nav2" id="mysidebar"  >
							<li id="li_section_general" class="">
								<a class="section_link" href="#section_general" data-section="section_general">
									<?php echo AC()->lang->__( 'General' ); ?>
								</a>
							</li>
							<li id="li_section_email" class="">
								<a class="section_link" href="#section_email" data-section="section_email">
									<?php echo AC()->lang->__( 'E-mail' ); ?>
								</a>
							</li>
							<li id="li_section_image" class="">
								<a class="section_link" href="#section_image" data-section="section_image">
									<?php echo AC()->lang->__( 'Image' ); ?>
								</a>
							</li>
							<li id="li_section_pdf" class="">
								<a class="section_link" href="#section_pdf" data-section="section_pdf">
									<?php echo AC()->lang->__( 'PDF' ); ?>
								</a>
							</li>
							<li style="text-align:center;">
								<a class="" href="javascript:jQuery('html,body').animate({ scrollTop: 0 }, 'fast');void(0);">
									<img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/arrow-up-64.png" style="height:25px;" />
								</a>
							</li>
						</ul>
					</div>
				</div>  
				<div class="main_container" >
					<div class="wrapper">


				<div id="section_general" class="major_section">
					<fieldset class="adminform">
						<legend><?php echo AC()->lang->__( 'General' ); ?></legend>

						<div class="aw-row">
							<div class="aw-label"><label><?php echo AC()->lang->__( 'Title' ); ?></label></div>
							<div class="aw-input">
								<input type="text" class="inputbox" name="title" value="<?php echo $data->row->title; ?>" maxlength="255" />
							</div>
						</div>

						<div class="aw-row">
							<div class="aw-label"><label><?php echo AC()->lang->__( 'From Name' ); ?></label></div>
							<div class="aw-input">
								<input type="text" class="inputbox" name="from_name" value="<?php echo $data->row->from_name; ?>" maxlength="255" />
							</div>
						</div>

						<div class="aw-row">
							<div class="aw-label"><label><?php echo AC()->lang->__( 'From Email' ); ?></label></div>
							<div class="aw-input">
								<input type="text" class="inputbox" name="from_email" value="<?php echo $data->row->from_email; ?>" maxlength="255" />
							</div>
						</div>

						<div class="aw-row">
							<div class="aw-label">
								<label><?php echo AC()->lang->__( 'Email Subject' ); ?></label>
							</div>
							<div class="aw-input">
								<?php echo AC()->lang->write_fields( 'text', 'email_subject', $data->row->languages ); ?>
							</div>
						</div>

						<div class="aw-row">
							<div class="aw-label"><label><?php echo AC()->lang->__( 'Bcc Admin' ); ?></label></div>
							<div class="aw-input">
								<input type="checkbox" class="inputbox" name="bcc_admin" <?php echo ! empty( $data->row->bcc_admin ) ? 'CHECKED' : ''; ?> value="1" />
							</div>
						</div>

						<div class="aw-row">
							<div class="aw-label"><label><?php echo AC()->lang->__( 'Cc Purchaser' ); ?></label></div>
							<div class="aw-input">
								<input type="checkbox" class="inputbox" name="cc_purchaser" <?php echo ! empty( $data->row->cc_purchaser ) ? 'CHECKED' : ''; ?> value="1" />
							</div>
						</div>
					</fieldset>
				</div>

				<div id="section_email" class="major_section">
					<fieldset class="adminform">
						<legend><?php echo AC()->lang->__( 'E-mail' ); ?></legend>

						<table class="" style="width:100%"><tr valign="top">
							<td>
								<div class="aw-input">
									<?php
									echo AC()->lang->write_fields( 'editor', 'email_body', $data->row->languages, array(
										'name' => 'email_body',
										'editor_width' => '100%',
										'editor_height' => '550',
									) );
									?>
								</div>

								<div class="aw-row">
									<div class="aw-label"><label>{vouchers}</label></div>
									<div class="aw-input">
										<?php echo AC()->lang->write_fields( 'text', 'voucher_text', $data->row->languages ); ?>
									</div>
								</div>

								<div class="aw-row">
									<div class="aw-label"><label>{expiration_text}</label></div>
									<div class="aw-input">
										<?php echo AC()->lang->write_fields( 'text', 'voucher_text_exp', $data->row->languages ); ?>
									</div>
								</div>

							</td>
							<td width="1%">
								<span class=""><br /><br /><br /><br /></span>

								<ul id="accordion" class="">
									<li><a class="toggle" href="javascript:void(0);"><span><?php echo AC()->lang->__( 'Gift Certificates' ); ?></span></a>
										<div class="inner show">
											<div style="padding:10px;">
												<div><b><?php echo AC()->lang->__( 'Tags' ); ?></b></div>
												<div>{store_name}</div>
												<div>{siteurl}</div>
												<div>{vouchers}</div>
												<div>{image_embed}</div>
												<div>{purchaser_username}</div>
												<div>{purchaser_first_name}</div>
												<div>{purchaser_last_name}</div>
												<div>{from_name}</div>
												<div>{recipient_name}</div>
												<div>{recipient_email}</div>
												<div>{recipient_message}</div>
												<div>{today_date}</div>
												<div>{order_id}</div>
												<div>{order_number}</div>
												<div>{order_status}</div>
												<div>{order_date}</div>
												<div>{order_link}</div>
												<div>{order_total}</div>
												<div>{product_name}</div>
												<div>{product_qty_name}</div>
											</div>
										</div>
									</li>
									<li><a class="toggle" href="javascript:void(0);"><span><?php echo AC()->lang->__( 'Reminders' ); ?></span></a>
										<div class="inner hide">
											<div style="padding:10px;">
												<div><b><?php echo AC()->lang->__( 'Tags' ); ?></b></div>
												<div>{user_name}</div>
												<div>{username}</div>
												<div>{voucher}</div>
												<div>{voucher_value}</div>
												<div>{vouchers}</div>
												<div>{image_embed}</div>
												<div>{today_date}</div>
												<div>{expiration}</div>
												<div>{expiration_year_4digit}</div>
												<div>{expiration_year_2digit}</div>
												<div>{expiration_month_namelong}</div>
												<div>{expiration_month_nameshort}</div>
												<div>{expiration_month_2digit}</div>
												<div>{expiration_month_1digit}</div>
												<div>{expiration_day_namelong}</div>
												<div>{expiration_day_nameshort}</div>
												<div>{expiration_day_2digit}</div>
												<div>{expiration_day_1digit}</div>
											</div>
										</div>
									</li>
								</ul>
							</td>
						</tr></table>
					</fieldset>
				</div>

				<div id="section_image" class="major_section">
					<fieldset class="adminform">
						<legend><?php echo AC()->lang->__( 'Image' ); ?></legend>

						<div class="aw-row">
							<div class="aw-label"><label><?php echo AC()->lang->__( 'Image' ); ?></label></div>
							<div class="aw-input">
								<select id="image" name="image" style="min-width:200px;" onchange="checkimage();">
									<option value="">----</option>
									<?php
									foreach ( $data->imagedd as $key => $value ) {
										echo '<option value="' . $key . '" ' . ( $key === $data->row->image ? 'SELECTED' : '' ) . ' >' . $value . '</option>';
									}
									?>
								</select>
								<button type="button" onclick="openImageManager()">
									<span><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/folder.png" style="height:28px" /></span>
								</button>
							</div>
						</div>

						<div class="aw-row">
							<div class="aw-label"><label><?php echo AC()->lang->__( 'Filename' ); ?></label></div>
							<div class="aw-input">
								<?php
								echo AC()->lang->write_fields( 'text', 'voucher_filename', $data->row->languages, array(
									'after_text' => '.(png|jpg)',
								) );
								?>
							</div>
						</div>

						<?php foreach ( $data->canvasitems as $item ) { ?>
							<input type="hidden" name="<?php echo $item['name']; ?>[align]" value="<?php echo isset( $item['db_values']->align ) ? $item['db_values']->align : ''; ?>" />
							<input type="hidden" name="<?php echo $item['name']; ?>[padding]" value="<?php echo isset( $item['db_values']->padding ) ? $item['db_values']->padding : ''; ?>">
							<input type="hidden" name="<?php echo $item['name']; ?>[y]" value="<?php echo $item['db_values']->y; ?>">
							<input type="hidden" name="<?php echo $item['name']; ?>[maxwidth]" value="<?php echo isset( $item['db_values']->maxwidth ) ? $item['db_values']->maxwidth : ''; ?>" />
							<input type="hidden" name="<?php echo $item['name']; ?>[font]" value="<?php echo isset( $item['db_values']->font ) ? $item['db_values']->font : ''; ?>" />
							<input type="hidden" name="<?php echo $item['name']; ?>[font_size]" value="<?php echo isset( $item['db_values']->font_size ) ? $item['db_values']->font_size : ''; ?>" />
							<input type='hidden' name="<?php echo $item['name']; ?>[font_color]" value="<?php echo isset( $item['db_values']->font_color ) ? $item['db_values']->font_color : ''; ?>" />
							<?php if ( isset( $item['db_values']->text ) ) { ?>
								<input type="hidden" name="<?php echo $item['name']; ?>[text]" value="<?php echo $item['db_values']->text; ?>" />
							<?php } ?>
						<?php } ?>
						
						<table class="profileimagetable" bgcolor="#ffffff" id="image_properties" style="display:none;">
						<tr valign="top">
							<td>
								<?php foreach ( $data->canvasitems as $item ) { ?>
									<div>
										<input type="radio" class="toggle" name="canvasItem" id="canvasItem<?php echo $item['index']; ?>" value="<?php echo $item['index']; ?>" onclick="setCanvasItem(this.value);"/>
										<label class="btn" for="canvasItem<?php echo $item['index']; ?>"><span><?php echo $item['title']; ?></span></label>
									</div>
								<?php } ?>
							</td>
							<td>
								<table class="table">
								<tr valign="top" id="canvasItemTextContainer">
									<th><?php echo AC()->lang->__( 'Text' ); ?></th>
									<td><input type="text" id="canvasItemText" onkeyup="updateCanvas()" /></td>
								</tr>
								<tr valign="top" id="canvasItemDateContainer">
									<th><?php echo AC()->lang->__( 'Text' ); ?></th>
									<td><select id="canvasItemDate" name="" class="class_updatecanvasselect">
											<option value=""><?php echo AC()->lang->__( 'Do not display' ); ?></option>
											<option value="Y-m-d"><?php echo AC()->helper->get_date( null, 'Y-m-d' ); ?></option>
											<option value="m/d/Y"><?php echo AC()->helper->get_date( null, 'm/d/Y' ); ?></option>
											<option value="d/m/Y"><?php echo AC()->helper->get_date( null, 'd/m/Y' ); ?></option>
											<option value="Y/m/d"><?php echo AC()->helper->get_date( null, 'Y/m/d' ); ?></option>
											<option value="d.m.Y"><?php echo AC()->helper->get_date( null, 'd.m.Y' ); ?></option>
											<option value="M j Y"><?php echo AC()->helper->get_date( null, 'M j Y' ); ?></option>
											<option value="j M Y"><?php echo AC()->helper->get_date( null, 'j M Y' ); ?></option>
											<option value="Y M j"><?php echo AC()->helper->get_date( null, 'Y M j' ); ?></option>
											<option value="F j Y"><?php echo AC()->helper->get_date( null, 'F j Y' ); ?></option>
											<option value="j F Y"><?php echo AC()->helper->get_date( null, 'j F Y' ); ?></option>
											<option value="Y F j"><?php echo AC()->helper->get_date( null, 'Y F j' ); ?></option>
										</select>
									</td>
								</tr>	
								<tr valign="top" id="canvasItemEnableContainer">
									<th><?php echo AC()->lang->__( 'Text' ); ?></th>
									<td><select id="canvasItemEnable" name="" class="class_updatecanvasselect">
											<option value=""><?php echo AC()->lang->__( 'No' ); ?></option>
											<option value="1"><?php echo AC()->lang->__( 'Yes' ); ?></option>
										</select>
									</td>
								</tr>	
								<?php
								foreach ( $data->canvasitems as $item ) {
									if ( 'imageDD' == $item['display'] && ! empty( $item['display_options'] ) && is_array( $item['display_options'] ) ) {
										$options_html = '';
										foreach ( $item['display_options'] as $option_value => $option_text ) {
											$options_html .= '<option value="' . htmlspecialchars( $option_value ) . '">' . $option_text['text'] . '</option>';
										}
										echo '
											<tr valign="top" id="canvasItemImageDD' . $item['index'] . 'Container">
												<th>' . AC()->lang->__( 'Text' ) . '</th>
												<td><select id="canvasItemImageDD' . $item['index'] . '" name="" class="class_updatecanvasselect">
														' . $options_html . '
													</select>
												</td>
											</tr>	
										';
									}
								}
								?>
								<?php
								foreach ( $data->canvasitems as $item ) {
									if ( 'custom' == $item['display'] && ! empty( $item['display_php'] ) ) {
										echo '
											<tr valign="top" id="canvasItemCustom' . $item['index'] . 'Container">
												<th>' . AC()->lang->__( 'Text' ) . '</th>
												<td>' . $item['display_php'] . '</td>
											</tr>	
										';
									}
								}
								?>
								<tr valign="top" id="canvasFontContainer">
									<th><?php echo AC()->lang->__( 'Font' ); ?></th>
									<td>
										<select id="canvasFont" class="class_updatecanvasselect">
											<?php
											foreach ( $data->fontdd as $font => $display ) {
												echo '<option value="' . str_replace( array( '.', ' ' ), '_', $font ) . '">' . $display . '</option>';
											}
											?>
										</select>
									</td>
								</tr>
								<tr valign="top" id="canvasFontSizeContainer">
									<th><?php echo AC()->lang->__( 'Font Size' ); ?></th>
									<td><input type="text" id="canvasFontSize" onkeyup="updateCanvas()" style="width:35px;" />px</td>
								</tr>
								<tr valign="top" id="canvasFontColorContainer">
									<th><?php echo AC()->lang->__( 'Font Color' ); ?></th>
									<td><input type='hidden' id="canvasFontColor" onchange="updateCanvas()" class="spectrumcolor"  /></td>
								</tr>
								<tr valign="top">
									<th><?php echo AC()->lang->__( 'Y-Axis' ); ?></th>
									<td><input type="text" id="canvasTop" onkeyup="updateCanvas()" style="width:35px;" />px</td>
								</tr>
								<tr valign="top">
									<th><?php echo AC()->lang->__( 'Align' ); ?></th>
									<td>
										<div><input type="radio" id="canvasAlignL" name="canvasAlign" value="L" onclick="updateCanvas()" /> <?php echo AC()->lang->__( 'Left' ); ?></div>
										<div><input type="radio" id="canvasAlignC" name="canvasAlign" value="C" onclick="updateCanvas()" /> <?php echo AC()->lang->__( 'Middle' ); ?></div>
										<div><input type="radio" id="canvasAlignR" name="canvasAlign" value="R" onclick="updateCanvas()" /> <?php echo AC()->lang->__( 'Right' ); ?>
											<input type="text" id="canvasAlignRightPad" onchange="updateCanvas();" style="width:35px;" value="" />px</div>
									</td>
								</tr>
								<tr valign="top">
									<th><?php echo AC()->lang->__( 'Max Width' ); ?></th>
									<td><input type="text" id="canvasMaxWidth" onchange="updateCanvas()" style="width:35px;" />px</td>
								</tr>
								</table>

							</td>
							<td><div id="image_canvas_container"></div></td>
						</tr>
						</table>
					</fieldset>
				</div>

				<div id="section_pdf" class="major_section">
					<fieldset class="adminform">
						<legend><?php echo AC()->lang->__( 'PDF' ); ?></legend>

						<div class="aw-row">
							<div class="aw-label"><label><?php echo AC()->lang->__( 'Active' ); ?></label></div>
							<div class="aw-input">
								<select name="is_pdf">
									<option value="0" <?php echo 0 == $data->row->is_pdf ? 'SELECTED' : ''; ?>><?php echo AC()->lang->__( 'No' ); ?></option>
									<option value="1" <?php echo 1 == $data->row->is_pdf ? 'SELECTED' : ''; ?>><?php echo AC()->lang->__( 'Yes' ); ?></option>
								</select>
							</div>
						</div>

						<div class="aw-row">
							<div class="aw-label">
								<label><?php echo AC()->lang->__( 'Filename' ); ?></label>
							</div>
							<div class="aw-input">
								<?php
								echo AC()->lang->write_fields( 'text', 'pdf_filename', $data->row->languages, array(
									'after_text' => '.pdf',
								) );
								?>
							</div>
						</div>			

						<div class="aw-row">
							<div class="aw-label">
								<label><?php echo AC()->lang->__( 'Header' ); ?></label>
							</div>
							<div class="aw-input">
								<div class="small_editor">
									<?php
									echo AC()->lang->write_fields( 'editor', 'pdf_header', $data->row->languages, array(
										'editor_width' => '100%',
										'editor_height' => 150,
									) );
									?>
								</div>
							</div>
						</div>

						<div class="aw-row">
							<div class="aw-label">
								<label><?php echo AC()->lang->__( 'Text' ); ?></label>
							</div>
							<div class="aw-input">
								<table style="width:100%;"><tr valign="top">
								<td>
									<?php
									echo AC()->lang->write_fields( 'editor', 'pdf_body', $data->row->languages, array(
										'editor_width' => '100%',
										'editor_height' => 400,
									) );
									?>
								</td>
								<td>
									<div style="padding:10px;">
										<div><b><?php echo AC()->lang->__( 'Tags' ); ?></b></div>
											<div>{store_name}</div>
											<div>{siteurl}</div>
											<div>{vouchers}</div>
											<div>{image_embed}</div>
											<div>{purchaser_first_name}</div>
											<div>{purchaser_last_name}</div>
											<div>{from_name}</div>
											<div>{recipient_name}</div>
											<div>{recipient_email}</div>
											<div>{recipient_message}</div>
											<div>{today_date}</div>
											<div>{order_id}</div>
											<div>{order_number}</div>
											<div>{order_status}</div>
											<div>{order_date}</div>
											<div>{order_link}</div>
											<div>{order_total}</div>
											<div>{product_name}</div>
											<div>{product_qty_name}</div>
									</div>
								</td>
								</tr></table>
							</div>
						</div>
						
						
						<div class="aw-row">
							<div class="aw-label">
								<label><?php echo AC()->lang->__( 'Footer' ); ?></label>
							</div>
							<div class="aw-input">
								<div class="small_editor">
									<?php
									echo AC()->lang->write_fields( 'editor', 'pdf_footer', $data->row->languages, array(
										'editor_width' => '100%',
										'editor_height' => 150,
									) );
									?>
								</div>
							</div>
						</div>
					</fieldset>
				</div>


					</div>
				</div>
			</div>

		</div>

		<div class="submitpanel"><span>
			<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
		</span><div class="clear"></div></div>

	</div>

<input type="hidden" name="id" value="<?php echo $data->row->id; ?>" />
<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
</div>


<?php
$font_names = array();
foreach ( $data->fontdd as $font => $display ) {
	$font_names[] = "'" . str_replace( array( '.', ' ' ), '_', $font ) . "'";
	// Preload funts
?>
	<div style="font-family:'<?php echo str_replace( array( '.', ' ' ), '_', $font ); ?>'">&nbsp;</div>
<?php } ?>


<script language="javascript" type="text/javascript">
<!--
var image_location = "<?php echo addslashes( CMCOUPON_GIFTCERT_URL . '/images' ); ?>";


//jQuery(window).load(function() {
jQuery(document).ready(function() {	

	waitForFabric(function() {
		total_fonts_notloaded = <?php echo count( $font_names ); ?>;
		if(total_fonts_notloaded==0) checkimage(); // only load once fonts are fully loaded
		else {
			waitForWebfonts([<?php echo implode( ',', $font_names ); ?>], function() {
				total_fonts_notloaded--;
				if(total_fonts_notloaded==0) checkimage(); // only load once fonts are fully loaded
			});
		}
	});

})

function waitForFabric(callback) {
	var interval;
	function checkFabricLoaded() {
		if (typeof fabric !== 'undefined') {
			if(interval) {
				clearInterval(interval);
			}
			callback();
			return true;
		}
	};

	if(!checkFabricLoaded()) {
		interval = setInterval(checkFabricLoaded, 50);
	}
}
function waitForWebfonts(fonts, callback) {
	var loadedFonts = 0;
	for(var i = 0, l = fonts.length; i < l; ++i) {
		(function(font) {
			var node = document.createElement('span');
			// Characters that vary significantly among different fonts
			node.innerHTML = 'giItT1WQy@!-/#';
			// Visible - so we can measure it - but not on the screen
			node.style.position      = 'absolute';
			node.style.left          = '-10000px';
			node.style.top           = '-10000px';
			// Large font size makes even subtle changes obvious
			node.style.fontSize      = '300px';
			// Reset any font properties
			node.style.fontFamily    = 'sans-serif';
			node.style.fontVariant   = 'normal';
			node.style.fontStyle     = 'normal';
			node.style.fontWeight    = 'normal';
			node.style.letterSpacing = '0';
			document.body.appendChild(node);

			// Remember width with no applied web font
			var width = node.offsetWidth;

			node.style.fontFamily = font + ', sans-serif';

			var interval;
			function checkFont() {
				// Compare current width with original width
				if(node && node.offsetWidth != width) {
					++loadedFonts;
					node.parentNode.removeChild(node);
					node = null;
				}

				// If all fonts have been loaded
				if(loadedFonts >= fonts.length) {
					if(interval) {
						clearInterval(interval);
					}
					if(loadedFonts == fonts.length) {
						callback();
						return true;
					}
				}
			};

			if(!checkFont()) {
				interval = setInterval(checkFont, 50);
			}
		})(fonts[i]);
	}
};

jQuery(document).ready(function() {
	
	var form = document.adminForm;
	
	<?php if ( empty( $data->row->id ) ) { ?>
	resetall();
	<?php } ?>
		
	jQuery('select').not(".noselect2").select2({
		theme: 'classic',
		minimumResultsForSearch: 7,
		width: 'resolve'
	});


	jQuery(".spectrumcolor").spectrum({
		allowEmpty: true,
		showInput: true,
		preferredFormat: "hex3",
		showInitial: true,
		showButtons: false,
		appendTo: '#cm-main'
	});


	{ // fixed menu and intricacies that need fixing
		padding_from_top = parseInt(jQuery('<?php echo AC()->helper->get_padding_from_top_element(); ?>').outerHeight(true));
		if( isNaN( padding_from_top ) ) padding_from_top = 0;
		
		jQuery(window).on('scroll', function() {
			
			var docViewTop = jQuery(window).scrollTop();
			var docViewBottom = docViewTop + jQuery(window).height();

			var elemTop = jQuery('.sidebar_container_holder').offset().top;
			var elemBottom = elemTop + jQuery('.sidebar_container_holder').outerHeight(true);

			
			if((docViewTop+padding_from_top)>elemBottom) {
				jQuery('.sidebar_container').css({'position':'fixed','top':padding_from_top});
			}
			else {
				jQuery('.sidebar_container').css({'position':'','top':''});
			}
			
		})

		// animite clicking an anchor link
		jQuery(".section_link").each(function() {
			jQuery(this).click(function () {	
				pos = parseInt(jQuery("#"+jQuery(this).data("section")).offset().top - padding_from_top);
				jQuery('html,body').animate({
					scrollTop: pos-10
				}, 800);
				return false;
			});
		});
	}
	
	
	
	hideOtherLanguage("<?php echo $data->default_language; ?>");


	
	


	jQuery('#adminForm textarea[name="lang[<?php echo $data->default_language; ?>][email_body]"]').addClass('no_jv_ignore');

	var myvalidator = jQuery("#adminForm").validate({
		ignore: jquery_validate_setting_ignore, // validate hidden fields
		rules: {
			title: { required: true }
			,'lang[<?php echo $data->default_language; ?>][email_body]': {
				editorcheck:{
					//required: true,
					required: false,
					<?php
					/*getcontent: '<?php echo trim(addslashes($this->editor->getContent( 'lang_'.str_replace('-','_',$data->default_language).'_email_body' ))); ?>' */
					?>
				}
			}
		}
	});

	//If the change event fires we want to see if the form validates. But we don't want to check before the form has been submitted by the user initially.
	jQuery(document).on('change', '#adminForm textarea[name="lang[<?php echo $data->default_language; ?>][email_body]"]', function () {
		if (!jQuery.isEmptyObject(myvalidator.submitted)) {
			jQuery(this).valid();  // validate single field		
		}
	});
	
	
	jQuery('.class_updatecanvasselect').on('change', function (event, json, string) {
		if(json != undefined) {
		// used because of select2, dont whant to trigger change event when just updating the select value through javascript
			if(json.processchange != undefined) {
				if(!json.processchange) return;
			}
		}
		updateCanvas();
	});
	
	jQuery('#accordion .toggle').click(function(e) {
		e.preventDefault();
	  
		var $this = jQuery(this);
	  
		if ($this.next().hasClass('show')) {
			$this.next().removeClass('show');
			$this.next().slideUp(350);
		} else {
			$this.parent().parent().find('li .inner').removeClass('show');
			$this.parent().parent().find('li .inner').slideUp(350);
			$this.next().toggleClass('show');
			$this.next().slideToggle(350);
		}
	});


});

function submitbutton(pressbutton) {
	if (pressbutton == 'CANCELprofile') {
		jQuery("#adminForm").validate().settings.ignore = "*";
		submitcmform( pressbutton );
		return;
	}

	jQuery("#adminForm").validate().settings.ignore = jquery_validate_setting_ignore;
	submitcmform( pressbutton );
	return;
}





function resetall() {
	var form = document.adminForm;
	form.image.selectedIndex = 0;
	
	<?php foreach ( $data->canvasitems as $item ) { ?>
		form.elements["<?php echo $item['name']; ?>[align]"].value = 'L';
		form.elements["<?php echo $item['name']; ?>[padding]"].value = '';
		form.elements["<?php echo $item['name']; ?>[y]"].value = '';
		form.elements["<?php echo $item['name']; ?>[maxwidth]"].value = '';
		form.elements["<?php echo $item['name']; ?>[font]"].value = 'arialbd_ttf';
		form.elements["<?php echo $item['name']; ?>[font_size]"].value = '25';
		form.elements["<?php echo $item['name']; ?>[font_color]"].value = '#000000';
		<?php if ( isset( $item['db_values']->text ) ) { ?>
		form.elements["<?php echo $item['name']; ?>[text]"].value = '';
		<?php } ?>
		setCanvasItem(<?php echo $item['index']; ?>);
	<?php } ?>
	setCanvasItem(0);
		
	hideall();
}
function hideall() {
	jQuery('.hide').hide();
	
}

















function checkimage() {
	elem = document.adminForm.image;
	if(elem.selectedIndex == 0) {
		document.getElementById('image_properties').style.display = 'none';
		return;
	}
	
	document.getElementById('image_properties').style.display = '';
		
	var img = new Image();
	img.onload = function() {
	
		// delete image container and add new canvas, otherwise cached version of old image remains
		jQuery('#image_canvas_container').empty();
		var canv_html = document.createElement('canvas');
		canv_html.id = 'image_canvas';
		document.getElementById('image_canvas_container').appendChild(canv_html);

		var canvas = new fabric.Canvas('image_canvas');
		document.getElementById("image_canvas").fabric = canvas;
		
		canvas.setBackgroundImage(image_location+'/'+elem.value, canvas.renderAll.bind(canvas), {
			backgroundImageOpacity: 1,
			backgroundImageStretch: false
		});
		
		<?php foreach ( $data->canvasitems as $item ) { ?>
			<?php
			if ( 'imageDD' == $item['display'] ) {
				$image_options = array();
				foreach ( $item['display_options'] as $option_value => $option_text ) {
					$image_options[] = '"' . $option_value . '":"' . addslashes( $option_text['url'] . '|' . $option_text['width'] . '|' . $option_text['height'] ) . '"';
				}
			?>
				var imgObj = new Image();
				var newImage<?php echo $item['index']; ?> = new fabric.Image(imgObj,{
					_item_index: <?php echo $item['index']; ?>,
					_form_itemname: '<?php echo $item['name']; ?>',
					_form_itemtype: '<?php echo $item['display']; ?>',
					_form_imagedd_options: {<?php echo ! empty( $image_options ) ? implode( ',', $image_options ) : ''; ?>},
					hasControls: false,
					hasBorders: false,
					selectable: false
				});
				canvas.add(newImage<?php echo $item['index']; ?>);
			<?php } else { ?>
				var text<?php echo $item['index']; ?> = new fabric.Text("<?php echo $item['text']; ?>", {
					_item_index: <?php echo $item['index']; ?>,
					_form_itemname: '<?php echo $item['name']; ?>',
					_form_itemtype: '<?php echo $item['display']; ?>',
					fontSize: 20,
					hasControls: false,
					hasBorders: false,
					selectable: false
				});
				canvas.add(text<?php echo $item['index']; ?>);
			<?php } ?>
		<?php } ?>
		
		canvas.selection = false; // disable group selection on canvas
		canvas.setHeight(this.height);
		canvas.setWidth(this.width);
		canvas.renderAll();
		
		<?php foreach ( $data->canvasitems as $item ) { ?>
		setCanvasItem(<?php echo $item['index']; ?>);
		<?php } ?>
		setCanvasItem(0);
		jQuery('#canvasItem0').attr('checked', 'checked');
		
		// make sure selected item is persistent until another item is selected
		var selectedObject;
		canvas.on('object:selected', function(options) { selectedObject = options.target; });
		canvas.on('selection:cleared', function() { canvas.setActiveObject(selectedObject); });
		canvas.on("object:modified", function (options) {
			if(options.target.isType('text')) {
				jQuery("#canvasTop").val(Math.round(options.target.getTop() + (jQuery.trim(document.getElementById('canvasFontSize').value)*options.target.lineHeight))); 
				options.target.setLeft(Math.round(options.target.getLeft()));
				options.target.setTop(jQuery("#canvasTop").val() - (jQuery.trim(document.getElementById('canvasFontSize').value)*options.target.lineHeight));
				updateCanvasHiddens(); 
			}
			else if(options.target.isType('image')) {
				jQuery("#canvasTop").val(Math.round(options.target.getTop())); 
				updateCanvasHiddens(); 
			}
		});
	}
	img.src = image_location+'/'+elem.value;
		
}

function setCanvasItem(val) {
	if(canvas = document.getElementById("image_canvas")==undefined) return;
	canvas = document.getElementById("image_canvas").fabric;
	if(canvas==undefined) return;

	jQuery('#canvasFontContainer').show();
	jQuery('#canvasFontSizeContainer').show();
	jQuery('#canvasFontColorContainer').show();
	jQuery('#canvasItemTextContainer').hide();
	jQuery('#canvasItemDateContainer').hide();
	jQuery('#canvasItemEnableContainer').hide();
	
	<?php
	foreach ( $data->canvasitems as $item ) {
		if ( 'imageDD' == $item['display'] ) {
			echo 'jQuery("#canvasItemImageDD' . $item['index'] . 'Container").hide();';
		}
		if ( 'custom' == $item['display'] ) {
			echo 'jQuery("#canvasItemCustom' . $item['index'] . 'Container").hide();';
		}
	}
	?>
	

	<?php foreach ( $data->canvasitems as $item ) { ?>
	canvas.item(<?php echo $item['index']; ?>).set({ padding: 0, selectable: false });
	<?php if ( isset( $item['ignore_font'] ) && $item['ignore_font'] ) { ?>
	if(val==<?php echo $item['index']; ?>) jQuery('#canvasFontContainer').hide();
	<?php } ?>
	<?php if ( isset( $item['ignore_fontsize'] ) && $item['ignore_fontsize'] ) { ?>
	if(val==<?php echo $item['index']; ?>) jQuery('#canvasFontSizeContainer').hide();
	<?php } ?>
	<?php if ( isset( $item['ignore_fontcolor'] ) && $item['ignore_fontcolor'] ) { ?>
	if(val==<?php echo $item['index']; ?>) jQuery('#canvasFontColorContainer').hide();
	<?php } ?>
	<?php if ( 'text' == $item['display'] ) { ?>
	if(val==<?php echo $item['index']; ?>) jQuery('#canvasItemTextContainer').show();
	<?php } ?>
	<?php if ( 'date' == $item['display'] ) { ?>
	if(val==<?php echo $item['index']; ?>) jQuery('#canvasItemDateContainer').show();
	<?php } ?>
	<?php if ( 'imageDD' == $item['display'] ) { ?>
	if(val==<?php echo $item['index']; ?>) jQuery('#canvasItemImageDD<?php echo $item['index']; ?>Container').show();
	<?php } ?>
	<?php if ( 'custom' == $item['display'] ) { ?>
	if(val==<?php echo $item['index']; ?>) jQuery('#canvasItemCustom<?php echo $item['index']; ?>Container').show();
	<?php } ?>
	<?php if ( 'textEnable' == $item['display'] ) { ?>
	if(val==<?php echo $item['index']; ?>) jQuery('#canvasItemEnableContainer').show();
	<?php } ?>
	<?php } ?>
	canvas.deactivateAll();
	canvas.item(val).set({padding: 999999, selectable: true });
	canvas.setActiveObject(canvas.item(val)); 
	canvas.renderAll();
	updateCanvasFunctions(canvas.item(val));
}

//    http://jsfiddle.net/Kienz/fgWNL/

function updateCanvas() {
	canvas = document.getElementById("image_canvas").fabric;
	element = canvas.getActiveObject()
	
	if(element.isType('text')) {
		element.setFontFamily(document.getElementById('canvasFont').value);
	
		fontsize = _scrubInt(document.getElementById('canvasFontSize').value, 10);
		elemFontsize = fontsize
		<?php if ( 2 == $data->gd_version ) { ?>
		elemFontsize = fabric.util.parseUnit(fontsize+'pt');
		<?php } ?>
		element.setFontSize(elemFontsize);
	
		fontcolor = document.getElementById('canvasFontColor').value;
		if(jQuery.trim(fontcolor)!='') element.setColor(fontcolor);
		
		if(jQuery('#canvasItemText').is(":visible")) {
			element.setText(document.getElementById('canvasItemText').value);
		}
		else if(jQuery('#canvasItemDate').is(":visible")) {
			element.setText('');
			if(jQuery.trim(document.getElementById('canvasItemDate').value)!='')
				element.setText(jQuery("#canvasItemDate option:selected").text());
		}
		else if(jQuery('#canvasItemEnable').is(":visible")) {
			element.setText('');
			if(jQuery.trim(document.getElementById('canvasItemEnable').value)!='') {
				element.setText(document.getElementById('canvasItemText').value);
				<?php
				foreach ( $data->canvasitems as $item ) {
					if ( 'textEnable' != $item['display'] ) {
						continue;
					}
				?>
				if(element._item_index==<?php echo $item['index']; ?>) element.setText("<?php echo $item['text']; ?>");
				<?php } ?>
			}
		}
		
		
		
		maxwidth = document.getElementById('canvasMaxWidth').value;
		if(jQuery.trim(maxwidth)!='' && _scrubInt(maxwidth)>0) {
			_fontsize = element.getFontSize();
			_fontfamily = element.getFontFamily();
			//_maxwidth = fabric.util.parseUnit(maxwidth+'pt');
			_maxwidth = maxwidth;
			_words = element.getText().split(" ");
			_wordline = [];
			_lineno = 0;
			_wordline[_lineno]=[];
			for (var i = 0; i < _words.length; i++) {
				_word = _words[i];
				_dimensions = measureText(_wordline[_lineno].join(' ')+' '+_word,_fontsize,_fontfamily);
				if (_dimensions.width>_maxwidth) {
					_lineno++;
					_wordline[_lineno] = [];
				}
				_wordline[_lineno].push(jQuery.trim(_word));
			}

			_final_join = [];
			for (var i = 0; i < _wordline.length; i++) {
				_final_join.push(_wordline[i].join(' '));
			}
			_text = _final_join.join('\n');
			element.setText(_text);
			
		}

		updateFunctionSetCoords(
			element,
			fontsize*element.lineHeight,
			jQuery('input[name=canvasAlign]:checked').val(),
			document.getElementById('canvasAlignRightPad').value,
			document.getElementById('canvasTop').value
		);

	}
	else if(element.isType('image')) {
		if(jQuery('#canvasItemImageDD'+element._item_index).is(":visible")) {
			element.setSrc('');
			if(jQuery.trim(document.getElementById('canvasItemImageDD'+element._item_index).value)!='') {
				if(element._form_imagedd_options[jQuery.trim(document.getElementById('canvasItemImageDD'+element._item_index).value)]!=undefined) {
					option_items = element._form_imagedd_options[jQuery.trim(document.getElementById('canvasItemImageDD'+element._item_index).value)].split('|');

					var imgObj = fabric.document.createElement('img');
					imgObj.fabric_item = element;
					imgObj.fabric_canvas_alignment = jQuery('input[name=canvasAlign]:checked').val();
					imgObj.fabric_canvas_alignrightpad = document.getElementById('canvasAlignRightPad').value;
					imgObj.fabric_canvas_top = document.getElementById('canvasTop').value;
					imgObj.onload = function () {
						imgObj.fabric_item.setElement(imgObj);
						imgObj.fabric_item.set({
							width: option_items[1]*1, 
							height: option_items[2]*1
						});
						updateFunctionSetCoords(imgObj.fabric_item, 0, imgObj.fabric_canvas_alignment, imgObj.fabric_canvas_alignrightpad, imgObj.fabric_canvas_top);
					};
					imgObj.src=option_items[0];
					
				}
			}
		}
	}
}

function updateFunctionSetCoords(element, the_height, alignment, alignRightPad, ttop) {
	
	element.set({
		textAlign: 'left',
		lockMovementX: false,
		originX: 'left'
		//,left: 0
	});
	if(alignment=='C') {
		element.lockMovementX = true;
		element.centerH();
	}
	else if(alignment=='R') {
		element.set({
			textAlign: 'right',
			lockMovementX: true,
			originX: 'right',
			left: _scrubInt(canvas.getWidth() - _scrubInt(alignRightPad), 10)
		});
	}
	
	ttop = parseInt(ttop);
	if(isNaN(ttop)) ttop = 0;
	else {
		ttop -= the_height;
		if(ttop < 0) ttop = 0;
	}
	element.setTop(ttop);
	
	
	element.setCoords();
	canvas.renderAll();
	updateCanvasHiddens();
}


function updateCanvasFunctions(item) {
	if(item == undefined) {
		canvas = document.getElementById("image_canvas").fabric;
		item = canvas.getActiveObject(); 
	}
	
	form = document.adminForm;
	
	align = form.elements[item._form_itemname+'[align]'].value;
	padding = _scrubInt(form.elements[item._form_itemname+'[padding]'].value);
	y = _scrubInt(form.elements[item._form_itemname+'[y]'].value);
	font = form.elements[item._form_itemname+'[font]'].value.replace(/.ttf$/i,'_ttf');
	fontsize = _scrubInt(form.elements[item._form_itemname+'[font_size]'].value, 10);
	fontcolor = form.elements[item._form_itemname+'[font_color]'].value;
	text = form.elements[item._form_itemname+'[text]']==undefined ? '' : form.elements[item._form_itemname+'[text]'].value;
	maxwidth = _scrubInt(form.elements[item._form_itemname+'[maxwidth]'].value, 0);
	
	if(jQuery.trim(align)!='') {
		jQuery("#canvasAlign" + align).attr('checked', 'checked');
		jQuery("#canvasAlign" + align).prop('checked', true);
	}
	jQuery("#canvasAlignRightPad").val('');
	if(align=='R') jQuery("#canvasAlignRightPad").val(padding);
	else if(align=='L') item.left = padding;
	
	jQuery('#canvasFont').val(font).trigger('change',[{processchange:false}]);//jQuery("#canvasFont").select2("val", font); //jQuery("#canvasFont").val(font);
	jQuery("#canvasFontSize").val(fontsize);
	jQuery("#canvasFontColor").spectrum("set", fontcolor); // jQuery("#canvasFontColor").val(fontcolor); 
	jQuery("#canvasTop").val(y);
	jQuery("#canvasMaxWidth").val(maxwidth);
	
	
	jQuery("#canvasItemText").val('');
	jQuery("#canvasItemDate").val('').trigger('change',[{processchange:false}]);
	jQuery("#canvasItemEnable").val('').trigger('change',[{processchange:false}]);
	
	<?php
	foreach ( $data->canvasitems as $item ) {
		if ( 'imageDD' == $item['display'] ) {
			echo 'jQuery("#canvasItemImageDD' . $item['index'] . '").val("").trigger("change",[{processchange:false}]);';
		}
	}
	?>
	

	if(item._form_itemtype=='text')
		jQuery("#canvasItemText").val(text);
	else if(item._form_itemtype=='date')
		jQuery("#canvasItemDate").val(text).trigger('change',[{processchange:false}]);
	else if(item._form_itemtype=='textEnable')
		jQuery("#canvasItemEnable").val(text).trigger('change',[{processchange:false}]);
	else if(item._form_itemtype=='imageDD')
		jQuery("#canvasItemImageDD"+item._item_index).val(text).trigger('change',[{processchange:false}]);
	
	updateCanvas();
	
}

function updateCanvasHiddens(item) {
	if(item == undefined) {
		canvas = document.getElementById("image_canvas").fabric;
		item = canvas.getActiveObject(); 
	}
	
	form = document.adminForm;
	
	form.elements[item._form_itemname+'[align]'].value = jQuery('input[name=canvasAlign]:checked').val();
	if(form.elements[item._form_itemname+'[align]'].value=='L') form.elements[item._form_itemname+'[padding]'].value = item.getLeft();
	else if(form.elements[item._form_itemname+'[align]'].value=='R') form.elements[item._form_itemname+'[padding]'].value = _scrubInt(jQuery("#canvasAlignRightPad").val());
	else if(form.elements[item._form_itemname+'[align]'].value=='M') form.elements[item._form_itemname+'[padding]'].value = '';
	form.elements[item._form_itemname+'[y]'].value = _scrubInt(jQuery("#canvasTop").val());
	form.elements[item._form_itemname+'[maxwidth]'].value = _scrubInt(jQuery("#canvasMaxWidth").val());
	form.elements[item._form_itemname+'[font]'].value = jQuery("#canvasFont").val()==null? '' : jQuery("#canvasFont").val().replace(/_ttf$/i,'.ttf');
	form.elements[item._form_itemname+'[font_size]'].value = _scrubInt(jQuery("#canvasFontSize").val(), 10);
	form.elements[item._form_itemname+'[font_color]'].value = jQuery("#canvasFontColor").val();
	if(item._form_itemtype=='text') {
		if(form.elements[item._form_itemname+'[text]']!=undefined) form.elements[item._form_itemname+'[text]'].value = jQuery("#canvasItemText").val();
	}
	else if(item._form_itemtype=='date') {
		if(form.elements[item._form_itemname+'[text]']!=undefined) form.elements[item._form_itemname+'[text]'].value = jQuery("#canvasItemDate").val();
	}
	else if(item._form_itemtype=='textEnable') {
		if(form.elements[item._form_itemname+'[text]']!=undefined) form.elements[item._form_itemname+'[text]'].value = jQuery("#canvasItemEnable").val();
	}
	else if(item._form_itemtype=='imageDD') {
		if(form.elements[item._form_itemname+'[text]']!=undefined) form.elements[item._form_itemname+'[text]'].value = jQuery("#canvasItemImageDD"+item._item_index).val();
	}

	form.elements[item._form_itemname+'[maxwidth]'].value = _scrubInt(jQuery("#canvasMaxWidth").val());
	
}

function _positiveNum(val) {
	if( isNaN(val) ) return false;
	if(val <= 0) return false;
	return true;
}

function _scrubInt(val, defaultval) {
	if(defaultval == undefined) defaultval = 0;
	
	val = jQuery.trim(val);
	if( ! /^\d+$/.test(val) ) return parseInt(defaultval);
	if(val < 1) return parseInt(defaultval);
	
	return parseInt(val);
}



// Multiline code:   https://k-r.io/blog/even-better-multiline-text-boundary-fitting-for-fabric-js-text-objects/
var clonedCanvasEle = null;
function measureText (text,fontsize,fontfamily) {
	var measure = { width: 0, height: 0 };

	if (clonedCanvasEle === null) {
		clonedCanvasEle = new fabric.Text("", {
			_form_itemname: 'clonedCanvasEle',
			hasControls: false,
			hasBorders: false,
			selectable: false,
			left: -2000,
			top: -2000
		});
	}

	clonedCanvasEle.setText(text);
	clonedCanvasEle.setFontSize(fontsize);
	clonedCanvasEle.setFontFamily(fontfamily);

	canvas.add(this.clonedCanvasEle);
	canvas.renderAll();

	//measure.width = clonedCanvasEle.getWidth();
	//measure.height = clonedCanvasEle.getHeight();
	measure.width = clonedCanvasEle.width;
	measure.height = clonedCanvasEle.height;

	canvas.remove(clonedCanvasEle);

	return measure;
}


function openImageManager() {
	<?php echo AC()->helper->get_modal_popup( AC()->ajax_url() . '&type=admin&view=profile&layout=imagemanager', AC()->lang->__( 'Image manager' ), false ); ?>
}


//-->
</script>
