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

<link rel='stylesheet'  href='<?php echo CMCOUPON_ASEET_URL; ?>/css/jquery.dataTables.min.css?ver=<?php echo CMCOUPON_VERSION; ?>' type='text/css' media='all' />
<link rel='stylesheet'  href='<?php echo CMCOUPON_ASEET_URL; ?>/css/select.dataTables.min.css?ver=<?php echo CMCOUPON_VERSION; ?>' type='text/css' media='all' />
<link rel='stylesheet'  href='<?php echo CMCOUPON_ASEET_URL; ?>/css/buttons.dataTables.min.css?ver=<?php echo CMCOUPON_VERSION; ?>' type='text/css' media='all' />
<script type='text/javascript' src='<?php echo CMCOUPON_ASEET_URL; ?>/js/jquery.dataTables.min.js?ver=<?php echo CMCOUPON_VERSION; ?>'></script>
<script type='text/javascript' src='<?php echo CMCOUPON_ASEET_URL; ?>/js/dataTables.select.min.js?ver=<?php echo CMCOUPON_VERSION; ?>'></script>
<script type='text/javascript' src='<?php echo CMCOUPON_ASEET_URL; ?>/js/dataTables.buttons.min.js?ver=<?php echo CMCOUPON_VERSION; ?>'></script>
<script type='text/javascript' src='<?php echo CMCOUPON_ASEET_URL; ?>/js/coupon.js?ver=<?php echo CMCOUPON_VERSION; ?>'></script>
<script type='text/javascript' src='<?php echo CMCOUPON_ASEET_URL; ?>/js/coupon_cumulative_value.js?ver=<?php echo CMCOUPON_VERSION; ?>'></script>



<script language="javascript" type="text/javascript">

var base_url = "<?php echo AC()->ajax_url(); ?>";
var is_negative_coupon_value = <?php echo 1 == $data->allow_negative_value ? 'true' : 'false'; ?>;


var str_add = '<?php echo addslashes( AC()->lang->__( 'Add' ) ); ?>';
var str_coupons = '<?php echo addslashes( AC()->lang->__( 'Coupons' ) ); ?>';
var str_product = '<?php echo addslashes( AC()->lang->__( 'Product' ) ); ?>';
var str_shipping = '<?php echo addslashes( AC()->lang->__( 'Shipping' ) ); ?>';
var str_name = '<?php echo addslashes( AC()->lang->__( 'Name' ) ); ?>';
var str_asset = '<?php echo addslashes( AC()->lang->__( 'Asset' ) ); ?>';
var str_buy_x = '<?php echo addslashes( AC()->lang->__( 'BuyX' ) ); ?>';
var str_get_y = '<?php echo addslashes( AC()->lang->__( 'GetY' ) ); ?>';
var str_pq_displaynum = '<?php echo addslashes( AC()->lang->__( 'Display #' ) ); ?>';
var str_shipping_module = '<?php echo addslashes( AC()->lang->__( 'Shipping Module' ) ); ?>';
var str_last_name = '<?php echo addslashes( AC()->lang->__( 'Last Name' ) ); ?>';
var str_first_name = '<?php echo addslashes( AC()->lang->__( 'First Name' ) ); ?>';
var str_username = '<?php echo addslashes( AC()->lang->__( 'Username' ) ); ?>';
var str_assetlist = {
	product: '<?php echo addslashes( AC()->lang->__( 'Product' ) ); ?>',
	category: '<?php echo addslashes( AC()->lang->__( 'Category' ) ); ?>',
	coupon: '<?php echo addslashes( AC()->lang->__( 'Coupon' ) ); ?>',
	vendor: '<?php echo addslashes( AC()->lang->__( 'Vendor' ) ); ?>',
	manufacturer: '<?php echo addslashes( AC()->lang->__( 'Manufacturer' ) ); ?>',
	custom: '<?php echo addslashes( AC()->lang->__( 'Custom' ) ); ?>'
};
var str_include = '<?php echo addslashes( AC()->lang->__( 'Include' ) ); ?>';
var str_exclude = '<?php echo addslashes( AC()->lang->__( 'Exclude' ) ); ?>';




jQuery(document).ready(function() {

	var form = document.adminForm;
	function_type_change(true);
	function_type_buyxy_change(true);
	valuedefinition_change(false);
	product_match_change();
		
	jQuery.getJSON(
		base_url, 
		{type:'ajax', task:'ajax_tags'}, 
		function(opts){
			jQuery("#e12")
				.select2({
					theme: 'classic',
					tags: opts,
					tokenSeparators: [',', ' ', ';']
				})
				<?php
				if ( ! empty( $data->row->tags ) ) {
					$items = array();
					foreach ( $data->row->tags as $tag ) {
						$items[] = addslashes( $tag );
					}
				?>
				.val(["<?php echo implode( '","', $items ); ?>"])
				.trigger("change")
				<?php } ?>
			;
		}
	);
	
	jQuery('select').not(".noselect2").select2({
		theme: 'classic',
		minimumResultsForSearch: 7,
		width: 'resolve'
	});
	
	
	jQuery(".js-data-couponcustomer-ajax").select2({
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
					element: 'user'
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
	

	
	countrystatechange('#countrylist','#statelist','<?php echo ! empty( $data->row->asset[0]->rows->countrystate->rows ) ? implode( ',', array_keys( $data->row->asset[0]->rows->countrystate->rows ) ) : ''; ?>');
	jQuery("#countrylist").on("change", function(e){ 
		ids = '';
		countrystatechange(this,'#statelist',ids);
	
	});
	
	
	
	{ // fixed menu and intricacies that need fixing
		//var padding_from_top = parseInt(
		//	jQuery('nav.navbar').outerHeight(true)
		//	+jQuery('div.subhead').outerHeight(true)
		//);
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
	
	
	jQuery.validator.addMethod('couponvaluedef', function (value, element, param) {
		form = element.form;
		if(form.couponvalue_hidden.value!='advanced') return true;
		return (!/^(\d+\-\d+([.]\d+)?;)+(\[[_a-z]+\=[a-z]+(\&[_a-z]+\=[a-z]+)*\])?$/.test(value)) ? false : true;
	}, glb_str_err_valid);
	jQuery.validator.addMethod('assetlist0check', function (value, element, param) {
		form = element.form;
		v_function_type = jQuery('input[name=function_type]:checked', form).val();
		
		if(v_function_type == 'combination') { if(typeof element.form.elements['asset0listadded[]']==='undefined') return false; }
		else if(v_function_type == 'coupon') { if(jQuery.trim(form.discount_type.value)=='specific' && typeof element.form.elements['asset0listadded[]']==='undefined') return false; }

		return true;
	}, glb_str_err_valid);
	jQuery.validator.addMethod('assetlist1check', function (value, element, param) {
		form = element.form;
		v_function_type = jQuery('input[name=function_type]:checked', form).val();
		
		if(v_function_type == 'buyxy') { 
			v_function_type_buyxy = jQuery('input[name=function_type_buyxy]:checked', form).val();
			if(v_function_type_buyxy=='buyxy1') {
				if(typeof element.form.elements['asset1listadded[]']==='undefined') return false;
			}
		}
		
		return true;
	}, glb_str_err_valid);
	jQuery.validator.addMethod('assetlist2check', function (value, element, param) {
		v_function_type = jQuery('input[name=function_type]:checked', form).val();
		if(v_function_type == 'buyxy') {
			v_function_type_buyxy = jQuery('input[name=function_type_buyxy]:checked', form).val();
			if(v_function_type_buyxy=='buyxy1') {
				is_product_match = jQuery('input[name=product_match]', form).is(':checked');
				if(!is_product_match) { if(typeof element.form.elements['asset2listadded[]']==='undefined') return false; }
			}
		}
		return true;
	}, glb_str_err_valid);
	jQuery.validator.addMethod('assetlist3check', function (value, element, param) {
		v_function_type = jQuery('input[name=function_type]:checked', form).val();
		
		if(v_function_type == 'buyxy') {
			v_function_type_buyxy = jQuery('input[name=function_type_buyxy]:checked', form).val();
			if(v_function_type_buyxy=='buyxy2') {
				if(typeof element.form.elements['asset3listadded[]']==='undefined') return false;
			}
		}
		return true;
	}, glb_str_err_valid);
	jQuery.validator.addMethod('assetlist4check', function (value, element, param) {
		v_function_type = jQuery('input[name=function_type]:checked', form).val();
		
		if(v_function_type == 'buyxy') {
			v_function_type_buyxy = jQuery('input[name=function_type_buyxy]:checked', form).val();
			if(v_function_type_buyxy=='buyxy2') {
				if(typeof element.form.elements['asset4listadded[]']==='undefined') return false;
			}
		}
		return true;
	}, glb_str_err_valid);
	jQuery.validator.addMethod('datecheck', function (value, element, param) {
		is_required = false;
		if(typeof param.required !== 'undefined') is_required = jQuery.isFunction(param.required) ? param.required(element) : param.required;
		if((value=='YYYY-MM-DD' || value=='') && !is_required) return true;
		
		if(!/^\d{4}\-\d{2}\-\d{2}$/.test(value)) return false;
		yyyy = value.substr(0,4);
		mm = value.substr(5,2);
		dd = value.substr(8,2);
		if(yyyy>2200 || mm>12 || dd>31) return false;
		
		return true;
	}, glb_str_err_valid);
	jQuery.validator.addMethod('timecheck', function (value, element, param) {
		is_required = false;
		if(typeof param.required !== 'undefined') is_required = jQuery.isFunction(param.required) ? param.required(element) : param.required;
		if((value=='hh:mm:ss' || value=='') && !is_required) return true;
		
		if(!/^\d{2}\:\d{2}\:\d{2}$/.test(value)) return false;
		hh = value.substr(0,2);
		mm = value.substr(3,2);
		ss = value.substr(6,2);
		if(hh>23 || mm>59 || ss>59) return false;
		
		return true;
	}, glb_str_err_valid);



	var myvalidator = jQuery("#adminForm").validate({
		ignore: jquery_validate_setting_ignore, // validate hidden fields
		rules: {
			function_type:{ required:true },
			state: { required:true },
			coupon_code: { required: true },
			coupon_value_type: { required: true },
			discount_type: { required: true },
			coupon_value: { required: true, number: true },
			coupon_value_def: { couponvaluedef: true },
			process_type_combination: { required: true },
			process_type_buyxy: { required: true },
			
			num_of_uses_total: { digits:true, min:1 },
			num_of_uses_customer: { digits:true, min:1 },
			min_value: { number:true, min:0 },
			max_discount_amt: { number: true, min: 0.01 },
			max_discount_qty: { digits: true, min: 1 },
			min_qty: { digits: true, min: 1 },
			
			startdate_date: { datecheck: {required: function(element) {
				if(!jQuery(element).is(":visible")) return false;
				if(element.form.startdate_time.value!='' && element.form.startdate_time.value!='hh:mm:ss') return true;
				return false;
			}} },
			startdate_time: { timecheck: {required: false} },
			expiration_date: { datecheck: {required: function(element) {
				if(!jQuery(element).is(":visible")) return false;
				if(element.form.expiration_time.value!='' && element.form.expiration_time.value!='hh:mm:ss') return true;
				return false;
			}} },
			expiration_time: { timecheck: {required: false} },
			
			'asset[1][rows][product][qty]': { required:true, digits:true, min:1 },
			'asset[2][rows][product][qty]': { required:true, digits:true, min:1 },
			
			asset0listvalidate: { assetlist0check: true },
			asset1listvalidate: { assetlist1check: true },
			asset2listvalidate: { assetlist2check: true },
			asset3listvalidate: { assetlist3check: true },
			asset4listvalidate: { assetlist4check: true },
			
		},
		focusInvalid: false,
		invalidHandler: function(form, validator) {
		// scroll down to first error
			if (!validator.numberOfInvalids()) return;
			
			//var padding_from_top = parseInt(
			//	jQuery('nav.navbar').outerHeight(true)
			//	+jQuery('div.subhead').outerHeight(true)
			//);
			padding_from_top = parseInt(jQuery('<?php echo AC()->helper->get_padding_from_top_element(); ?>').outerHeight(true));
			if( isNaN( padding_from_top ) ) padding_from_top = 0;
			if(!jQuery(validator.errorList[0].element).is(":visible")) {
				jQuery(validator.errorList[0].element).show();
				topv = jQuery(validator.errorList[0].element).offset().top;
				jQuery(validator.errorList[0].element).hide();
			}
			else topv = jQuery(validator.errorList[0].element).offset().top;
				
			
			jQuery('html, body').animate({
				scrollTop: topv-padding_from_top-10
			}, 500);

		}
	});

	jQuery('#startdate_date').datepicker({ dateFormat: 'yy-mm-dd' });
	jQuery('#expiration_date').datepicker({ dateFormat: 'yy-mm-dd' });

});

function submitbutton(pressbutton) {
	if (pressbutton == 'cancelcoupon') {
		jQuery("#adminForm").validate().settings.ignore = "*";
		submitcmform( pressbutton );
		return;
	}

	jQuery("#adminForm").validate().settings.ignore = jquery_validate_setting_ignore;
	submitcmform( pressbutton ); 
	return;
}




</script>
   
 <style>
 
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

form .aw-row { background-color: #f2f2f2; border-top: 1px solid #cccccc;  margin-bottom:0;}
.aw-label {  width:190px; padding:7px; height:100%; }
.aw-label label {font-weight:bold; }
.aw-input { border-left: 1px solid #cccccc; padding:7px; padding-bottom:20px; background-color:#fff; height:100%; }

.pq-grid-top.ui-widget-header { background:#f7f7f7; }
.pq-grid-title { display:none; }
.aw-row .pq-grid select, .aw-row .pq-grid input[type="text"] { height:auto; }
#asset_search_grid { border-radius: 0; }


.asset_holder { margin-top:20px; font-size:1.3em;border:1px solid #ddd; padding:10px; background-color:#f9f9f9;}

</style>


<div style="background-color:#ffffff;">
<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>

<div class="submitpanel">
	<h1><?php echo empty( $data->row->id ) ? AC()->lang->__( 'Add New Coupon' ) : AC()->lang->__( 'Edit Coupon' ); ?></h1>
	<span>
		<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'apply');" class="button button-large button-apply"><?php echo AC()->lang->__( 'Apply' ); ?></button>
		<button type="button" onclick="jQuery('#adminForm').validate();submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
	</span>
	<div class="clear"></div>
</div>

<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<div>
	<div class="sidebar_container_holder"></div>
	<div class="sidebar_container">
		<div class="wrapper nav_parent">
			<ul class="nav2 hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination" id="mysidebar"  >
				<li id="li_section_coupon_details" class="hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
					<a class="section_link" href="#section_coupon_details" data-section="section_coupon_details">
						<?php echo AC()->lang->__( 'Coupon Details' ); ?>
					</a>
				</li>
				<li id="li_section_optionals" class="hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
					<a class="section_link" href="#section_optionals" data-section="section_optionals">
						<?php echo AC()->lang->__( 'Optional Fields' ); ?>
					</a>
				</li>

				<li id="li_section_assets0" class="hide f_coupon f_giftcert f_shipping f_combination">
					<a class="section_link" href="#section_assets0" data-section="section_assets0">
						<?php echo AC()->lang->__( 'Product' ); ?>
					</a>
				</li>
				
				<li id="li_section_shipping" class="hide f_giftcert f_shipping">
					<a class="section_link" href="#section_shipping" data-section="section_shipping">
						<?php echo AC()->lang->__( 'Shipping' ); ?>
					</a>
				</li>
				
				<li id="li_section_assets1" class="hide f_buyxy">
					<a class="section_link" href="#section_assets1" data-section="section_assets1">
						<?php echo AC()->lang->__( 'BuyX' ); ?>
					</a>
				</li>
				

				<li id="li_section_assets2" class="hide f_buyxy">
					<a class="section_link" href="#section_assets2" data-section="section_assets2">
						<?php echo AC()->lang->__( 'GetY' ); ?>
					</a>
				</li>
				

				<li id="li_section_assets3" class="hide f_buyxy2">
					<a class="section_link" href="#section_assets3" data-section="section_assets3">
						<?php echo AC()->lang->__( 'BuyX' ); ?>
					</a>
				</li>
				<li id="li_section_assets4" class="hide f_buyxy2">
					<a class="section_link" href="#section_assets4" data-section="section_assets4">
						<?php echo AC()->lang->__( 'GetY' ); ?>
					</a>
				</li>

				<li id="li_section_otherrestrictions" class="hide f_coupon f_shipping f_buyxy f_buyxy2 f_combination">
					<a class="section_link" href="#section_otherrestrictions" data-section="section_otherrestrictions">
						<?php echo AC()->lang->__( 'Other Restrictions' ); ?>
					</a>
				</li>

				<li id="li_section_administration" class="hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
					<a class="section_link" href="#section_administration" data-section="section_administration">
						<?php echo AC()->lang->__( 'Administration' ); ?>
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



	<div class="major_section" >		
		<div class="awcontrols">
			<span class="awradio awbtn-group awbtn-group-yesno" >
				<?php
				foreach ( AC()->helper->vars( 'function_type' ) as $key => $value ) {

					if ( 'buyxy2' == $key ) {
						continue;
					}
					$in_array = array( $key );
					if ( 'buyxy' == $key ) {
						$in_array[] = 'buyxy2';
					}
					echo '
						<input type="radio" class="no_jv_ignore" onclick="function_type_change()" id="function_type_rd_' . $key . '" 
							name="function_type" value="' . $key . '" ' . ( in_array( $data->row->function_type, $in_array ) ? 'checked="checked"' : '' ) . ' />
						<label for="function_type_rd_' . $key . '" >' . $value . '</label>
					';
				}
				?>
			</span>
		</div>
		<div class="awcontrols hide f_buyxy f_buyxy2">
			<span class="awradio awbtn-group awbtn-group-yesno" >
				<input type="radio" class="no_jv_ignore" onclick="function_type_buyxy_change()" id="function_type_rd_buyxy1" 
					name="function_type_buyxy" value="buyxy1" <?php echo 'buyxy' == $data->row->function_type ? 'checked="checked"' : ''; ?> />
				<label for="function_type_rd_buyxy1" ><?php echo AC()->lang->__( 'Overall Quantity' ); ?></label>
				<input type="radio" class="no_jv_ignore" onclick="function_type_buyxy_change()" id="function_type_rd_buyxy2" 
					name="function_type_buyxy" value="buyxy2" <?php echo 'buyxy2' == $data->row->function_type ? 'checked="checked"' : ''; ?> />
				<label for="function_type_rd_buyxy2" ><?php echo AC()->lang->__( 'Each Quantity' ); ?></label>
			</span>
		</div>
	</div>

	<div id="section_coupon_details" class="major_section hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
		<fieldset class="adminform">
			<legend><?php echo AC()->lang->__( 'Coupon Details' ); ?></legend>
		
			<div class="aw-row hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Coupon Code' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="coupon_code" maxlength="255" value="<?php echo $data->row->coupon_code; ?>" />
					<button type="button" onclick="generate_code('<?php echo CMCOUPON_ESTORE; ?>')"><?php echo AC()->lang->__( 'Generate Code' ); ?></button>
				</div>
			</div>

			<div class="aw-row hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'State' ); ?></label></div>
				<div class="aw-input">
					<select name="state">
						<?php
						foreach ( AC()->helper->vars( 'state', null, 'balance' ) as $key => $value ) {
							echo '<option value="' . $key . '" ' . ( $data->row->state == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div class="aw-row hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Start Date' ); ?></label></div>
				<div class="aw-input">
					<input type="date" id="startdate_date" name="startdate_date" value="<?php echo $data->row->startdate_date; ?>" placeholder="YYYY-MM-DD" maxlength="10"/>
					<input type="text" name="startdate_time" size="1" class="inputbox" style="width:75px;" maxlength="8" value="<?php echo $data->row->startdate_time; ?>" placeholder="hh:mm:ss"  />
				</div>
			</div>

			<div class="aw-row hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Expiration' ); ?></label></div>
				<div class="aw-input">
					<input type="date" id="expiration_date" name="expiration_date" value="<?php echo $data->row->expiration_date; ?>" placeholder="YYYY-MM-DD" maxlength="10"/>
					<input type="text" name="expiration_time" size="1" class="inputbox" maxlength="8" value="<?php echo $data->row->expiration_time; ?>" style="width:75px;" placeholder="hh:mm:ss"  />
				</div>
			</div>

			<div class="aw-row hide f_combination">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Process Type' ); ?></label></div>
				<div class="aw-input">
					<select name="process_type_combination">
						<?php
						foreach ( AC()->helper->vars( 'process_type_combination' ) as $key => $value ) {
							echo '<option value="' . $key . '" ' . ( $data->row->process_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div class="aw-row hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Value' ); ?></label></div>
				<div class="aw-input">
					<span class="aw-row hide f_buyxy f_buyxy2" style="vertical-align:top;display:inline-block;">
					<select name="process_type_buyxy">
						<?php
						foreach ( AC()->helper->vars( 'process_type_buyxy' ) as $key => $value ) {
							echo '<option value="' . $key . '" ' . ( $data->row->process_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
						}
						?>
					</select>
					</span>
					<span class="aw-row hide f_coupon f_shipping f_buyxy f_buyxy2" style="vertical-align:top;display:inline-block;">
						<select name="coupon_value_type" onchange="couponvalue_type_change();">
							<?php
							foreach ( AC()->helper->vars( 'coupon_value_type' ) as $key => $value ) {
								echo '<option value="' . $key . '" ' . ( $data->row->coupon_value_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
							}
							?>
						</select>
					</span>
					<span id="couponvalue_basic">
						<input class="inputbox" type="text" name="coupon_value" maxlength="255" value="<?php echo $data->row->coupon_value; ?>" />
					</span>
					
					<span class="hide f_coupon" style="display:inline-block;">
						<span id="couponvalue_advanced" style="display:none;">
							<input type="text" name="coupon_value_def" onfocus="showvaluedefinition();" class="inputbox" maxlength="255" value="<?php echo $data->row->coupon_value_def; ?>" />
							<button type="button" onclick="showvaluedefinition();">...</button>
						</span>
						
						<a href="javascript:valuedefinition_change(true);" style="display:inline-block; vertical-align:middle;">
							<img id="couponvalue_image" src="<?php echo CMCOUPON_ASEET_URL; ?>/images/expand.png" style="height:20px;margin-bottom:8px;" />
						</a>
							<div id="value_definition_description">
								<?php echo AC()->coupon->get_value_print( $data->row->coupon_value_def, $data->row->coupon_value_type ); ?>
							</div>
						<input type="hidden" id="couponvalue_hidden" name="couponvalue_hidden" value="<?php echo empty( $data->row->coupon_value ) && ! empty( $data->row->coupon_value_def ) ? 'advanced' : 'basic'; ?>" />
					</span>
				</div>
			</div>

			<div class="aw-row hide f_coupon f_shipping f_buyxy f_buyxy2">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Maximum Discount Amount' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="max_discount_amt" maxlength="255" value="<?php echo $data->row->max_discount_amt; ?>" />
				</div>
			</div>

		</fieldset>
	</div>

	<div id="section_optionals" class="major_section hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
		<fieldset  class="adminform">
		<legend><?php echo AC()->lang->__( 'Optional Fields' ); ?></legend>

			<div class="aw-row hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label"><label for="id_checkbox_exclusive"><?php echo AC()->lang->__( 'Individual use only' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="checkbox" name="exclusive" value="1" <?php echo 1 == $data->row->exclusive ? 'CHECKED' : ''; ?> id="id_checkbox_exclusive" />
				</div>
			</div>

			<div class="aw-row hide f_coupon f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label">
					<label><?php echo AC()->lang->__( 'Number of Uses Total' ); ?></label>
				</div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="num_of_uses_total" maxlength="255" value="<?php echo $data->row->num_of_uses_total; ?>" />
				</div>
			</div>

			<div class="aw-row hide f_coupon f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label">
					<label><?php echo AC()->lang->__( 'Number of Uses per Customer' ); ?></label>
				</div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="num_of_uses_customer" maxlength="255" value="<?php echo $data->row->num_of_uses_customer; ?>" />
				</div>
			</div>

			<div class="aw-row hide f_coupon f_shipping f_buyxy f_buyxy2">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Minimum Value' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="min_value" maxlength="255" value="<?php echo $data->row->min_value; ?>" />
					<select name="min_value_type">
						<?php
						foreach ( AC()->helper->vars( 'min_value_type' ) as $key => $value ) {
							echo '<option value="' . $key . '" ' . ( $data->row->min_value_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div class="aw-row hide f_coupon f_shipping">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Minimum Product Quantity' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="min_qty" maxlength="255" value="<?php echo $data->row->min_qty; ?>" />
					<select name="min_qty_type">
						<?php
						foreach ( AC()->helper->vars( 'min_qty_type' ) as $key => $value ) {
							echo '<option value="' . $key . '" ' . ( $data->row->min_qty_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div class="aw-row hide f_buyxy f_buyxy2">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Maximum Discount Qty' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="max_discount_qty" maxlength="255" value="<?php echo $data->row->max_discount_qty; ?>" />
				</div>
			</div>

			<!--<div class="aw-row hide f_coupon f_buyxy f_buyxy2">
				<div class="aw-label"><label for="id_checkbox_excludespecial"><?php echo AC()->lang->__( 'Exclude Products on Special' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="checkbox" name="exclude_special" value="1" <?php echo 1 == $data->row->exclude_special ? 'CHECKED' : ''; ?> id="id_checkbox_excludespecial" />
				</div>
			</div>-->

			<div class="aw-row hide f_coupon f_buyxy f_buyxy2">
				<div class="aw-label"><label for="id_checkbox_excludediscounted"><?php echo AC()->lang->__( 'Exclude Discounted Products' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="checkbox" name="exclude_discounted" value="1" <?php echo 1 == $data->row->exclude_discounted ? 'CHECKED' : ''; ?> id="id_checkbox_excludediscounted" />
				</div>
			</div>

			<div class="aw-row hide f_coupon f_giftcert">
				<div class="aw-label"><label for="id_checkbox_excludesgiftcert"><?php echo AC()->lang->__( 'Exclude Gift Certificate Products' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="checkbox" name="exclude_giftcert" value="1" <?php echo 1 == $data->row->exclude_giftcert ? 'CHECKED' : ''; ?> id="id_checkbox_excludesgiftcert" />
				</div>
			</div>

		</fieldset>
	</div>

	<div id="section_assets0"		class="major_section hide f_coupon f_giftcert f_shipping f_combination">
		<fieldset  class="adminform" >
		<legend><?php echo AC()->lang->__( 'Product' ); ?></legend>

			<div class="aw-row hide f_coupon f_shipping">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Discount Type' ); ?></label></div>
				<div class="aw-input">
					<select name="discount_type">
						<?php
						foreach ( AC()->helper->vars( 'discount_type' ) as $key => $value ) {
							echo '<option value="' . $key . '" ' . ( $data->row->discount_type == $key ? 'SELECTED' : '' ) . '>' . $value . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div class="aw-row hide f_coupon f_giftcert f_shipping">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Type' ); ?></label></div>
				<div class="aw-input">
					<select name="asset0_function_type" onchange="asset_type_change(0, true);">
						<option value=""></option>
						<option value="product"><?php echo AC()->lang->__( 'Product' ); ?></option>
						<option value="category"><?php echo AC()->lang->__( 'Category' ); ?></option>
						<?php if ( AC()->helper->vars( 'asset_type', 'manufacturer' ) !== null ) { ?>
							<option value="manufacturer"><?php echo AC()->lang->__( 'Manufacturer' ); ?></option>
						<?php } ?>
						<?php if ( AC()->helper->vars( 'asset_type', 'vendor' ) !== null ) { ?>
							<option value="vendor"><?php echo AC()->lang->__( 'Vendor' ); ?></option>
						<?php } ?>
						<?php if ( AC()->helper->vars( 'asset_type', 'custom' ) !== null ) { ?>
							<option value="custom"><?php echo AC()->lang->__( 'Custom' ); ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="aw-row hide f_coupon f_giftcert f_shipping">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Mode' ); ?></label></div>
				<div class="aw-input">
					<div class="awcontrols hide aw-asset0-row f_asset0_product">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_0_product_mode_rd_include" name="asset[0][rows][product][mode]" value="include"
								onclick="jQuery('.aw-asset0-item-product-row').hide(); jQuery('.f_asset0_item_product_include').show()"
								<?php echo empty( $data->row->asset[0]->rows->product->mode ) || 'include' == $data->row->asset[0]->rows->product->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_product_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_0_product_mode_rd_exclude" name="asset[0][rows][product][mode]" value="exclude" 
								onclick="jQuery('.aw-asset0-item-product-row').hide(); jQuery('.f_asset0_item_product_exclude').show()"
								<?php echo ! empty( $data->row->asset[0]->rows->product->mode ) && 'exclude' == $data->row->asset[0]->rows->product->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_product_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset0-row f_asset0_category">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_0_category_mode_rd_include" name="asset[0][rows][category][mode]" value="include"
								onclick="jQuery('.aw-asset0-item-category-row').hide(); jQuery('.f_asset0_item_category_include').show()"
								<?php echo empty( $data->row->asset[0]->rows->category->mode ) || 'include' == $data->row->asset[0]->rows->category->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_category_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_0_category_mode_rd_exclude" name="asset[0][rows][category][mode]" value="exclude"
								onclick="jQuery('.aw-asset0-item-category-row').hide(); jQuery('.f_asset0_item_category_exclude').show()"
								<?php echo ! empty( $data->row->asset[0]->rows->category->mode ) && 'exclude' == $data->row->asset[0]->rows->category->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_category_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset0-row f_asset0_manufacturer">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_0_manufacturer_mode_rd_include" name="asset[0][rows][manufacturer][mode]" value="include"
								onclick="jQuery('.aw-asset0-item-manufacturer-row').hide(); jQuery('.f_asset0_item_manufacturer_include').show()"
								<?php echo empty( $data->row->asset[0]->rows->manufacturer->mode ) || 'include' == $data->row->asset[0]->rows->manufacturer->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_manufacturer_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_0_manufacturer_mode_rd_exclude" name="asset[0][rows][manufacturer][mode]" value="exclude"
								onclick="jQuery('.aw-asset0-item-manufacturer-row').hide(); jQuery('.f_asset0_item_manufacturer_exclude').show()"
								<?php echo ! empty( $data->row->asset[0]->rows->manufacturer->mode ) && 'exclude' == $data->row->asset[0]->rows->manufacturer->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_manufacturer_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset0-row f_asset0_vendor">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_0_vendor_mode_rd_include" name="asset[0][rows][vendor][mode]" value="include"
								onclick="jQuery('.aw-asset0-item-vendor-row').hide(); jQuery('.f_asset0_item_vendor_include').show()"
								<?php echo empty( $data->row->asset[0]->rows->vendor->mode ) || 'include' == $data->row->asset[0]->rows->vendor->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_vendor_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_0_vendor_mode_rd_exclude" name="asset[0][rows][vendor][mode]" value="exclude"
								onclick="jQuery('.aw-asset0-item-vendor-row').hide(); jQuery('.f_asset0_item_vendor_exclude').show()"
								<?php echo ! empty( $data->row->asset[0]->rows->vendor->mode ) && 'exclude' == $data->row->asset[0]->rows->vendor->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_vendor_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset0-row f_asset0_custom">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_0_custom_mode_rd_include" name="asset[0][rows][custom][mode]" value="include"
								onclick="jQuery('.aw-asset0-item-custom-row').hide(); jQuery('.f_asset0_item_custom_include').show()"
								<?php echo empty( $data->row->asset[0]->rows->custom->mode ) || 'include' == $data->row->asset[0]->rows->custom->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_custom_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_0_custom_mode_rd_exclude" name="asset[0][rows][custom][mode]" value="exclude"
								onclick="jQuery('.aw-asset0-item-custom-row').hide(); jQuery('.f_asset0_item_custom_exclude').show()"
								<?php echo ! empty( $data->row->asset[0]->rows->custom->mode ) && 'exclude' == $data->row->asset[0]->rows->custom->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_custom_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
				</div>
			</div>

			<div id="div_asset0_inner" class="hide">
				<div class="assetlistsection">
					<input type="text" name="asset0listvalidate" class="assetlistvalidator hide no_jv_ignore" value="" />

					<div class="aw-row">
						<div class="aw-label">
							<div style="padding-top:5px;">
								<a href="javascript:view_some('asset0');"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/c_table1.png" class="c_table_select" style="height:22px;" /></a>
								&nbsp; <a href="javascript:view_all('asset0');"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/c_table2.png" style="height:22px;" /></a>
								&nbsp; <a href="javascript:view_all_grid('asset0');"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/c_table3.png" style="height:22px;" /></a>
							</div>
						</div>
						<div class="aw-input">
							<div id="div_asset0_simple_table">
								<span style="width:70px;display:inline-block;"><?php echo AC()->lang->__( 'Search' ); ?>:</span>
								<input class="inputbox" type="text" id="asset0_search" name="asset0_name" size="60" maxlength="255" value="" />
								<input type="hidden" name="asset0_id" value="" />
								<button id="btn_asset0_search" type="button" onclick="dd_itemselectf_v3('0'); return false;"><?php echo AC()->lang->__( 'Add' ); ?></button>
							</div>

							<div id="div_asset0_advanced_table" class="hide">
								<div>
									<span style="width:70px;display:inline-block;"><?php echo AC()->lang->__( 'Search' ); ?>:</span>
									<input type="text" id="asset0_search_txt" size="60" onkeyup="dd_searchg('asset0')">
									<button onclick="dd_itemselectg_v3('0'); return false;"><?php echo AC()->lang->__( 'Add' ); ?></button>
								</div>
								<select name="_asset0list" MULTIPLE class="inputbox noselect2" size="2" style="width:100%; height:160px;" ondblclick="dd_itemselectg_v3('0')"></select>
								<div style="color:#777777;"><i><?php echo AC()->lang->__( 'Ctrl/Shift Key' ); ?></i></div>
								<br />
							</div>

							<div id="div_asset0_advanced_grid" class="hide">
								<div id="asset0_search_grid">
									<table id="asset0_search_grid_table" class="display" cellspacing="0" width="100%">
									 <thead>
										<tr>
											<th><?php echo AC()->lang->__( 'ID' ); ?></th>
											<th><?php echo AC()->lang->__( 'Name' ); ?></th>
										</tr>
									</thead>
									 <tfoot>
										<tr>
											<th><?php echo AC()->lang->__( 'ID' ); ?></th>
											<th><?php echo AC()->lang->__( 'Name' ); ?></th>
										</tr>
									</tfoot>
									</table>
								</div>
								<div style="color:#777777;"><i><?php echo AC()->lang->__( 'Ctrl/Shift Key' ); ?></i></div>
								<br />
							</div>

							<div class="asset_holder">
								<table id="tbl_assets0" class="adminlist wp-list-table tableinne widefat striped posts" cellspacing="1">
								<thead><tr>
									<th width="1">&nbsp;</th>
									<th width="1"><?php echo AC()->lang->__( 'ID' ); ?></th>
									<th><?php echo AC()->lang->__( 'Type' ); ?></th>
									<th width="1"><?php echo AC()->lang->__( 'Mode' ); ?></th>
									<th><?php echo AC()->lang->__( 'Name' ); ?></th>
								</tr></thead>
								<tbody>
								<?php
								if ( ! empty( $data->row->asset[0]->rows ) ) {
									foreach ( $data->row->asset[0]->rows as $asset_type => $row1 ) {
										if ( ! in_array( $asset_type, array( 'product', 'category', 'vendor', 'manufacturer', 'custom', 'coupon' ) ) ) {
											continue;
										}
										foreach ( $row1->rows as $row ) {
								?>
									<tr id="tbl_assets0_tr<?php echo $row->asset_id; ?>">
										<td class="last" align="right" nowrap>
											<?php if ( 'coupon' == $asset_type ) { ?>
												<button type="button" onclick="moverow('tbl_assets0_tr<?php echo $row->asset_id; ?>','up');" >&#8593;</button><button 
														type="button" onclick="moverow('tbl_assets0_tr<?php echo $row->asset_id; ?>','down');" >&#8595;</button>&nbsp; 
											<?php } ?>
											<button type="button" onclick="deleterow('tbl_assets0_tr<?php echo $row->asset_id; ?>');return false;" >X</button>
											<input type="hidden" name="asset0listadded[]" value="<?php echo htmlspecialchars( $row->asset_id ); ?>">
											<input type="hidden" name="asset[0][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][asset_id]" value="<?php echo $row->asset_id; ?>">
											<input type="hidden" name="asset[0][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][asset_name]" value="<?php echo htmlspecialchars( $row->asset_name ); ?>">
										</td>
										<td><?php echo $row->asset_id; ?></td>
										<td><?php echo AC()->helper->vars( 'asset_type', $asset_type ); ?></td>
										<td>
											<label class="awbtn active awbtn-success  aw-asset0-item-<?php echo $asset_type; ?>-row f_asset0_item_<?php echo $asset_type; ?>_include <?php echo 'combination' == $data->row->function_type || empty( $row1->mode ) || 'include' == $row1->mode ? '' : 'hide'; ?>"><?php echo AC()->lang->__( 'Include' ); ?></label>
											<label class="awbtn active awbtn-danger   aw-asset0-item-<?php echo $asset_type; ?>-row f_asset0_item_<?php echo $asset_type; ?>_exclude <?php echo 'combination' == $data->row->function_type || ( isset( $row1->mode ) && 'exclude' != $row1->mode ) ? 'hide' : ''; ?>"><?php echo AC()->lang->__( 'Exclude' ); ?></label>
										</td>
										<td><?php echo $row->asset_name; ?></td>
									</tr>
								<?php
										}
									}
								}
								?>
								</tbody>
								</table>

							</div>
						</div>
					</div>
					
				</div>
			</div>

		</fieldset>
	</div>

	<div id="section_assets1" class="major_section hide f_buyxy">
		<fieldset  class="adminform" >
		<legend><?php echo AC()->lang->__( 'Product' ); ?></legend>

			<div class="aw-row hide f_buyxy">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Number' ); ?></label></div>
				<div class="aw-input"><input type="text" name="asset[1][qty]" class="inputbox" value="<?php echo ! empty( $data->row->asset[1]->qty ) ? $data->row->asset[1]->qty : ''; ?>" /></div>
			</div>
			<div class="aw-row hide f_buyxy">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Type' ); ?></label></div>
				<div class="aw-input">
					<select name="asset1_function_type" onchange="asset_type_change(1, true);">
						<option value=""></option>
						<option value="product"><?php echo AC()->lang->__( 'Product' ); ?></option>
						<option value="category"><?php echo AC()->lang->__( 'Category' ); ?></option>
						<?php if ( AC()->helper->vars( 'asset_type', 'manufacturer' ) !== null ) { ?>
							<option value="manufacturer"><?php echo AC()->lang->__( 'Manufacturer' ); ?></option>
						<?php } ?>
						<?php if ( AC()->helper->vars( 'asset_type', 'vendor' ) !== null ) { ?>
							<option value="vendor"><?php echo AC()->lang->__( 'Vendor' ); ?></option>
						<?php } ?>
						<?php if ( AC()->helper->vars( 'asset_type', 'custom' ) !== null ) { ?>
							<option value="custom"><?php echo AC()->lang->__( 'Custom' ); ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="aw-row hide f_buyxy">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Mode' ); ?></label></div>
				<div class="aw-input">
					<div class="awcontrols hide aw-asset1-row f_asset1_product">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_1_product_mode_rd_include" name="asset[1][rows][product][mode]" value="include"
								onclick="jQuery('.aw-asset1-item-product-row').hide(); jQuery('.f_asset1_item_product_include').show()"
								<?php echo empty( $data->row->asset[1]->rows->product->mode ) || 'include' == $data->row->asset[1]->rows->product->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_1_product_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_1_product_mode_rd_exclude" name="asset[1][rows][product][mode]" value="exclude" 
								onclick="jQuery('.aw-asset1-item-product-row').hide(); jQuery('.f_asset1_item_product_exclude').show()"
								<?php echo ! empty( $data->row->asset[1]->rows->product->mode ) && 'exclude' == $data->row->asset[1]->rows->product->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_1_product_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset1-row f_asset1_category">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_1_category_mode_rd_include" name="asset[1][rows][category][mode]" value="include"
								onclick="jQuery('.aw-asset1-item-category-row').hide(); jQuery('.f_asset1_item_category_include').show()"
								<?php echo empty( $data->row->asset[1]->rows->category->mode ) || 'include' == $data->row->asset[1]->rows->category->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_1_category_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_1_category_mode_rd_exclude" name="asset[1][rows][category][mode]" value="exclude"
								onclick="jQuery('.aw-asset1-item-category-row').hide(); jQuery('.f_asset1_item_category_exclude').show()"
								<?php echo ! empty( $data->row->asset[1]->rows->category->mode ) && 'exclude' == $data->row->asset[1]->rows->category->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_1_category_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset1-row f_asset1_manufacturer">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_1_manufacturer_mode_rd_include" name="asset[1][rows][manufacturer][mode]" value="include"
								onclick="jQuery('.aw-asset1-item-manufacturer-row').hide(); jQuery('.f_asset1_item_manufacturer_include').show()"
								<?php echo empty( $data->row->asset[1]->rows->manufacturer->mode ) || 'include' == $data->row->asset[1]->rows->manufacturer->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_1_manufacturer_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_1_manufacturer_mode_rd_exclude" name="asset[1][rows][manufacturer][mode]" value="exclude"
								onclick="jQuery('.aw-asset1-item-manufacturer-row').hide(); jQuery('.f_asset1_item_manufacturer_exclude').show()"
								<?php echo ! empty( $data->row->asset[1]->rows->manufacturer->mode ) && 'exclude' == $data->row->asset[1]->rows->manufacturer->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_1_manufacturer_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset1-row f_asset1_vendor">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_1_vendor_mode_rd_include" name="asset[1][rows][vendor][mode]" value="include"
								onclick="jQuery('.aw-asset1-item-vendor-row').hide(); jQuery('.f_asset1_item_vendor_include').show()"
								<?php echo empty( $data->row->asset[1]->rows->vendor->mode ) || 'include' == $data->row->asset[1]->rows->vendor->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_1_vendor_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_1_vendor_mode_rd_exclude" name="asset[1][rows][vendor][mode]" value="exclude"
								onclick="jQuery('.aw-asset1-item-vendor-row').hide(); jQuery('.f_asset1_item_vendor_exclude').show()"
								<?php echo ! empty( $data->row->asset[1]->rows->vendor->mode ) && 'exclude' == $data->row->asset[1]->rows->vendor->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_1_vendor_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset1-row f_asset1_custom">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_1_custom_mode_rd_include" name="asset[1][rows][custom][mode]" value="include"
								onclick="jQuery('.aw-asset1-item-custom-row').hide(); jQuery('.f_asset1_item_custom_include').show()"
								<?php echo empty( $data->row->asset[1]->rows->custom->mode ) || 'include' == $data->row->asset[1]->rows->custom->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_1_custom_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_1_custom_mode_rd_exclude" name="asset[1][rows][custom][mode]" value="exclude"
								onclick="jQuery('.aw-asset1-item-custom-row').hide(); jQuery('.f_asset1_item_custom_exclude').show()"
								<?php echo ! empty( $data->row->asset[1]->rows->custom->mode ) && 'exclude' == $data->row->asset[1]->rows->custom->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_1_custom_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
				</div>
			</div>

			<div id="div_asset1_inner" class="hide">
				<div class="assetlistsection">
					<input type="text" name="asset1listvalidate" class="assetlistvalidator hide no_jv_ignore" value="" />

					<div class="aw-row">
						<div class="aw-label">
							<div style="padding-top:5px;">
								<a href="javascript:view_some('asset1');"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/c_table1.png" class="c_table_select" style="height:22px;" /></a>
								&nbsp; <a href="javascript:view_all('asset1');"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/c_table2.png" style="height:22px;" /></a>
								&nbsp; <a href="javascript:view_all_grid('asset1');"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/c_table3.png" style="height:22px;" /></a>
							</div>
						</div>
						<div class="aw-input">
							<div id="div_asset1_simple_table">
								<span style="width:70px;display:inline-block;"><?php echo AC()->lang->__( 'Search' ); ?>:</span>
								<input class="inputbox" type="text" id="asset1_search" name="asset1_name" size="60" maxlength="255" value="" />
								<input type="hidden" name="asset1_id" value="" />
								<button id="btn_asset1_search" type="button" onclick="dd_itemselectf_v3('1'); return false;"><?php echo AC()->lang->__( 'Add' ); ?></button>
							</div>

							<div id="div_asset1_advanced_table" class="hide">
								<div>
									<span style="width:70px;display:inline-block;"><?php echo AC()->lang->__( 'Search' ); ?>:</span>
									<input type="text" id="asset1_search_txt" size="60" onkeyup="dd_searchg('asset1')">
									<button onclick="dd_itemselectg_v3('1'); return false;"><?php echo AC()->lang->__( 'Add' ); ?></button>
								</div>
								<select name="_asset1list" MULTIPLE class="inputbox noselect2" size="2" style="width:100%; height:160px;" ondblclick="dd_itemselectg_v3('1')"></select>
								<div style="color:#777777;"><i><?php echo AC()->lang->__( 'Ctrl/Shift Key' ); ?></i></div>
								<br />
							</div>

							<div id="div_asset1_advanced_grid" class="hide">
								<div id="asset1_search_grid">
									<table id="asset1_search_grid_table" class="display" cellspacing="0" width="100%">
									 <thead>
										<tr>
											<th><?php echo AC()->lang->__( 'ID' ); ?></th>
											<th><?php echo AC()->lang->__( 'Name' ); ?></th>
										</tr>
									</thead>
									 <tfoot>
										<tr>
											<th><?php echo AC()->lang->__( 'ID' ); ?></th>
											<th><?php echo AC()->lang->__( 'Name' ); ?></th>
										</tr>
									</tfoot>
									</table>
								</div>
								<div style="color:#777777;"><i><?php echo AC()->lang->__( 'Ctrl/Shift Key' ); ?></i></div>
								<br />
							</div>

							<div class="asset_holder">
								<table id="tbl_assets1" class="adminlist wp-list-table tableinne widefat striped posts" cellspacing="1">
								<thead><tr>
										<th width="1">&nbsp;</th>
										<th width="1"><?php echo AC()->lang->__( 'ID' ); ?></th>
										<th><?php echo AC()->lang->__( 'Type' ); ?></th>
										<th width="1"><?php echo AC()->lang->__( 'Mode' ); ?></th>
										<th><?php echo AC()->lang->__( 'Name' ); ?></th>
								</tr></thead>
								<tbody>
								<?php
								if ( ! empty( $data->row->asset[1]->rows ) ) {
									foreach ( $data->row->asset[1]->rows as $asset_type => $row1 ) {
										if ( ! in_array( $asset_type, array( 'product', 'category', 'vendor', 'custom', 'manufacturer' ) ) ) {
											continue;
										}
										foreach ( $row1->rows as $row ) {
								?>
									<tr id="tbl_assets1_tr<?php echo $row->asset_id; ?>">
										<td class="last" align="right">
											<?php if ( 'coupon' == $asset_type ) { ?>
												<button type="button" onclick="moverow('tbl_assets1_tr<?php echo $row->asset_id; ?>','up');" >&#8593;</button><button 
														type="button" onclick="moverow('tbl_assets1_tr<?php echo $row->asset_id; ?>','down');" >&#8595;</button>&nbsp; 
											<?php } ?>
											<button type="button" onclick="deleterow('tbl_assets1_tr<?php echo $row->asset_id; ?>');return false;" >X</button>
											<input type="hidden" name="asset1listadded[]" value="<?php echo htmlspecialchars( $row->asset_id ); ?>">
											<input type="hidden" name="asset[1][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][asset_id]" value="<?php echo $row->asset_id; ?>">
											<input type="hidden" name="asset[1][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][asset_name]" value="<?php echo htmlspecialchars( $row->asset_name ); ?>">
										</td>
										<td><?php echo $row->asset_id; ?></td>
										<td><?php echo AC()->helper->vars( 'asset_type', $asset_type ); ?></td>
										<td>
											<label class="awbtn active awbtn-success  aw-asset1-item-<?php echo $asset_type; ?>-row f_asset1_item_<?php echo $asset_type; ?>_include <?php echo empty( $row1->mode ) || 'include' == $row1->mode ? '' : 'hide'; ?>"><?php echo AC()->lang->__( 'Include' ); ?></label>
											<label class="awbtn active awbtn-danger   aw-asset1-item-<?php echo $asset_type; ?>-row f_asset1_item_<?php echo $asset_type; ?>_exclude <?php echo isset( $row1->mode ) && 'exclude' != $row1->mode ? 'hide' : ''; ?>"><?php echo AC()->lang->__( 'Exclude' ); ?></label>
										</td>
										<td><?php echo $row->asset_name; ?></td>
									</tr>
								<?php
										}
									}
								}
								?>
								</tbody>
								</table>

							</div>
						</div>
					</div>

				</div>
			</div>

		</fieldset>
	</div>

	<div id="section_assets2" class="major_section hide f_buyxy">
		<fieldset  class="adminform" >
		<legend><?php echo AC()->lang->__( 'Product' ); ?></legend>

			<div class="aw-row hide f_buyxy">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Automatically add to cart \'Get Y\' product' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="checkbox" name="addtocart" value="1" <?php echo 1 == $data->row->addtocart ? 'CHECKED' : ''; ?> />
				</div>
			</div>

			<div id="div_product_match" class="aw-row hide f_buyxy">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Don\'t Mix Products' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="checkbox" onclick="product_match_change()" name="product_match" value="1" <?php echo 1 == $data->row->product_match ? 'CHECKED' : ''; ?> />
				</div>
			</div>

			<div class="aw-row hide f_buyxy">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Number' ); ?></label></div>
				<div class="aw-input"><input type="text" name="asset[2][qty]" class="inputbox" value="<?php echo ! empty( $data->row->asset[2]->qty ) ? $data->row->asset[2]->qty : ''; ?>" /></div>
			</div>
			<div id="div_asset2_type" class="aw-row hide f_buyxy">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Type' ); ?></label></div>
				<div class="aw-input">
					<select name="asset2_function_type" onchange="asset_type_change(2, true);">
						<option value=""></option>
						<option value="product"><?php echo AC()->lang->__( 'Product' ); ?></option>
						<option value="category"><?php echo AC()->lang->__( 'Category' ); ?></option>
						<?php if ( AC()->helper->vars( 'asset_type', 'manufacturer' ) !== null ) { ?>
							<option value="manufacturer"><?php echo AC()->lang->__( 'Manufacturer' ); ?></option>
						<?php } ?>
						<?php if ( AC()->helper->vars( 'asset_type', 'vendor' ) !== null ) { ?>
							<option value="vendor"><?php echo AC()->lang->__( 'Vendor' ); ?></option>
						<?php } ?>
						<?php if ( AC()->helper->vars( 'asset_type', 'custom' ) !== null ) { ?>
							<option value="custom"><?php echo AC()->lang->__( 'Custom' ); ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div id="div_asset2_mode" class="aw-row hide f_buyxy">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Mode' ); ?></label></div>
				<div class="aw-input">
					<div class="awcontrols hide aw-asset2-row f_asset2_product">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_2_product_mode_rd_include" name="asset[2][rows][product][mode]" value="include"
								onclick="jQuery('.aw-asset2-item-product-row').hide(); jQuery('.f_asset2_item_product_include').show()"
								<?php echo empty( $data->row->asset[2]->rows->product->mode ) || 'include' == $data->row->asset[2]->rows->product->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_2_product_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_2_product_mode_rd_exclude" name="asset[2][rows][product][mode]" value="exclude" 
								onclick="jQuery('.aw-asset2-item-product-row').hide(); jQuery('.f_asset2_item_product_exclude').show()"
								<?php echo ! empty( $data->row->asset[2]->rows->product->mode ) && 'exclude' == $data->row->asset[2]->rows->product->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_2_product_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset2-row f_asset2_category">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_2_category_mode_rd_include" name="asset[2][rows][category][mode]" value="include"
								onclick="jQuery('.aw-asset2-item-category-row').hide(); jQuery('.f_asset2_item_category_include').show()"
								<?php echo empty( $data->row->asset[2]->rows->category->mode ) || 'include' == $data->row->asset[2]->rows->category->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_2_category_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_2_category_mode_rd_exclude" name="asset[2][rows][category][mode]" value="exclude"
								onclick="jQuery('.aw-asset2-item-category-row').hide(); jQuery('.f_asset2_item_category_exclude').show()"
								<?php echo ! empty( $data->row->asset[2]->rows->category->mode ) && 'exclude' == $data->row->asset[2]->rows->category->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_2_category_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset2-row f_asset2_manufacturer">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_2_manufacturer_mode_rd_include" name="asset[2][rows][manufacturer][mode]" value="include"
								onclick="jQuery('.aw-asset2-item-manufacturer-row').hide(); jQuery('.f_asset2_item_manufacturer_include').show()"
								<?php echo empty( $data->row->asset[2]->rows->manufacturer->mode ) || 'include' == $data->row->asset[2]->rows->manufacturer->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_2_manufacturer_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_2_manufacturer_mode_rd_exclude" name="asset[2][rows][manufacturer][mode]" value="exclude"
								onclick="jQuery('.aw-asset2-item-manufacturer-row').hide(); jQuery('.f_asset2_item_manufacturer_exclude').show()"
								<?php echo ! empty( $data->row->asset[2]->rows->manufacturer->mode ) && 'exclude' == $data->row->asset[2]->rows->manufacturer->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_2_manufacturer_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset2-row f_asset2_vendor">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_2_vendor_mode_rd_include" name="asset[2][rows][vendor][mode]" value="include"
								onclick="jQuery('.aw-asset2-item-vendor-row').hide(); jQuery('.f_asset2_item_vendor_include').show()"
								<?php echo empty( $data->row->asset[2]->rows->vendor->mode ) || 'include' == $data->row->asset[2]->rows->vendor->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_2_vendor_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_2_vendor_mode_rd_exclude" name="asset[2][rows][vendor][mode]" value="exclude"
								onclick="jQuery('.aw-asset2-item-vendor-row').hide(); jQuery('.f_asset2_item_vendor_exclude').show()"
								<?php echo ! empty( $data->row->asset[2]->rows->vendor->mode ) && 'exclude' == $data->row->asset[2]->rows->vendor->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_2_vendor_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
					<div class="awcontrols hide aw-asset2-row f_asset2_custom">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" class="no_jv_ignore" id="asset_2_custom_mode_rd_include" name="asset[2][rows][custom][mode]" value="include"
								onclick="jQuery('.aw-asset2-item-custom-row').hide(); jQuery('.f_asset2_item_custom_include').show()"
								<?php echo empty( $data->row->asset[2]->rows->custom->mode ) || 'include' == $data->row->asset[2]->rows->custom->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_2_custom_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" class="no_jv_ignore" id="asset_2_custom_mode_rd_exclude" name="asset[2][rows][custom][mode]" value="exclude"
								onclick="jQuery('.aw-asset2-item-custom-row').hide(); jQuery('.f_asset2_item_custom_exclude').show()"
								<?php echo ! empty( $data->row->asset[2]->rows->custom->mode ) && 'exclude' == $data->row->asset[2]->rows->custom->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_2_custom_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
				</div>
			</div>

			<div id="div_asset2_inner" class="hide">
				<div class="assetlistsection">
					<input type="text" name="asset2listvalidate" class="assetlistvalidator hide no_jv_ignore" value="" />

					<div class="aw-row">
						<div class="aw-label">
							<div style="padding-top:5px;">
								<a href="javascript:view_some('asset2');"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/c_table1.png" class="c_table_select" style="height:22px;" /></a>
								&nbsp; <a href="javascript:view_all('asset2');"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/c_table2.png" style="height:22px;" /></a>
								&nbsp; <a href="javascript:view_all_grid('asset2');"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/c_table3.png" style="height:22px;" /></a>
							</div>
						</div>
						<div class="aw-input">
							<div id="div_asset2_simple_table">
								<span style="width:70px;display:inline-block;"><?php echo AC()->lang->__( 'Search' ); ?>:</span>
								<input class="inputbox" type="text" id="asset2_search" name="asset2_name" size="60" maxlength="255" value="" />
								<input type="hidden" name="asset2_id" value="" />
								<button id="btn_asset2_search" type="button" onclick="dd_itemselectf_v3('2'); return false;"><?php echo AC()->lang->__( 'Add' ); ?></button>
							</div>

							<div id="div_asset2_advanced_table" class="hide">
								<div>
									<span style="width:70px;display:inline-block;"><?php echo AC()->lang->__( 'Search' ); ?>:</span>
									<input type="text" id="asset2_search_txt" size="60" onkeyup="dd_searchg('asset2')">
									<button onclick="dd_itemselectg_v3('2'); return false;"><?php echo AC()->lang->__( 'Add' ); ?></button>
								</div>
								<select name="_asset2list" MULTIPLE class="inputbox noselect2" size="2" style="width:100%; height:160px;" ondblclick="dd_itemselectg_v3('2')"></select>
								<div style="color:#777777;"><i><?php echo AC()->lang->__( 'Ctrl/Shift Key' ); ?></i></div>
								<br />
							</div>

							<div id="div_asset2_advanced_grid" class="hide">
								<div id="asset2_search_grid">
									<table id="asset2_search_grid_table" class="display" cellspacing="0" width="100%">
									 <thead>
										<tr>
											<th><?php echo AC()->lang->__( 'ID' ); ?></th>
											<th><?php echo AC()->lang->__( 'Name' ); ?></th>
										</tr>
									</thead>
									 <tfoot>
										<tr>
											<th><?php echo AC()->lang->__( 'ID' ); ?></th>
											<th><?php echo AC()->lang->__( 'Name' ); ?></th>
										</tr>
									</tfoot>
									</table>
								</div>
								<div style="color:#777777;"><i><?php echo AC()->lang->__( 'Ctrl/Shift Key' ); ?></i></div>
								<br />
							</div>

							<div class="asset_holder">
								<table id="tbl_assets2" class="adminlist wp-list-table tableinne widefat striped posts" cellspacing="1">
								<thead><tr>
										<th width="1">&nbsp;</th>
										<th width="1"><?php echo AC()->lang->__( 'ID' ); ?></th>
										<th><?php echo AC()->lang->__( 'Type' ); ?></th>
										<th width="1"><?php echo AC()->lang->__( 'Mode' ); ?></th>
										<th><?php echo AC()->lang->__( 'Name' ); ?></th>
								</tr></thead>
								<tbody>
								<?php
								if ( ! empty( $data->row->asset[2]->rows ) ) {
									foreach ( $data->row->asset[2]->rows as $asset_type => $row1 ) {
										if ( ! in_array( $asset_type, array( 'product', 'category', 'vendor', 'manufacturer', 'custom' ) ) ) {
											continue;
										}
										foreach ( $row1->rows as $row ) {
								?>
									<tr id="tbl_assets2_tr<?php echo $row->asset_id; ?>">
										<td class="last" align="right">
											<?php if ( 'coupon' == $asset_type ) { ?>
												<button type="button" onclick="moverow('tbl_assets2_tr<?php echo $row->asset_id; ?>','up');" >&#8593;</button><button 
														type="button" onclick="moverow('tbl_assets2_tr<?php echo $row->asset_id; ?>','down');" >&#8595;</button>&nbsp; 
											<?php } ?>
											<button type="button" onclick="deleterow('tbl_assets2_tr<?php echo $row->asset_id; ?>');return false;" >X</button>
											<input type="hidden" name="asset2listadded[]" value="<?php echo htmlspecialchars( $row->asset_id ); ?>">
											<input type="hidden" name="asset[2][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][asset_id]" value="<?php echo $row->asset_id; ?>">
											<input type="hidden" name="asset[2][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][asset_name]" value="<?php echo htmlspecialchars( $row->asset_name ); ?>">
										</td>
										<td><?php echo $row->asset_id; ?></td>
										<td><?php echo AC()->helper->vars( 'asset_type', $asset_type ); ?></td>
										<td>
											<label class="awbtn active awbtn-success  aw-asset2-item-<?php echo $asset_type; ?>-row f_asset2_item_<?php echo $asset_type; ?>_include <?php echo empty( $row1->mode ) || 'include' == $row1->mode ? '' : 'hide'; ?>"><?php echo AC()->lang->__( 'Include' ); ?></label>
											<label class="awbtn active awbtn-danger   aw-asset2-item-<?php echo $asset_type; ?>-row f_asset2_item_<?php echo $asset_type; ?>_exclude <?php echo isset( $row1->mode ) && 'exclude' != $row1->mode ? 'hide' : ''; ?>"><?php echo AC()->lang->__( 'Exclude' ); ?></label>
										</td>
										<td><?php echo $row->asset_name; ?></td>
									</tr>
								<?php
										}
									}
								}
								?>
								</tbody>
								</table>

							</div>
						</div>
					</div>
					
				</div>
			</div>
		</fieldset>
	</div>

	<div id="section_assets3" class="major_section hide f_buyxy2">
		<fieldset  class="adminform" ><legend><?php echo AC()->lang->__( 'BuyX' ); ?></legend>
			<div class="assetlistsection">
				<input type="text" name="asset3listvalidate" class="assetlistvalidator hide no_jv_ignore" value="" />

				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Product' ); ?></label></div>
					<div class="aw-input">
						<div>
							<select name="asset3_function_type" onchange="asset_type_change(3, true);">
								<option value=""></option>
								<option value="product" SELECTED><?php echo AC()->lang->__( 'Product' ); ?></option>
								<option value="category"><?php echo AC()->lang->__( 'Category' ); ?></option>
								<?php if ( AC()->helper->vars( 'asset_type', 'manufacturer' ) !== null ) { ?>
									<option value="manufacturer"><?php echo AC()->lang->__( 'Manufacturer' ); ?></option>
								<?php } ?>
								<?php if ( AC()->helper->vars( 'asset_type', 'vendor' ) !== null ) { ?>
									<option value="vendor"><?php echo AC()->lang->__( 'Vendor' ); ?></option>
								<?php } ?>
								<?php if ( AC()->helper->vars( 'asset_type', 'custom' ) !== null ) { ?>
									<option value="custom"><?php echo AC()->lang->__( 'Custom' ); ?></option>
								<?php } ?>
							</select>
							&nbsp;&nbsp;&nbsp;

							<?php echo AC()->lang->__( 'Number' ); ?>: <input type="text" class="inputbox" name="asset3_qty" value="" style="width:50px;" />
							&nbsp;&nbsp;&nbsp;

							<?php echo AC()->lang->__( 'Search' ); ?>:
							<input class="inputbox" type="text" id="asset3_search" name="asset3_name" size="60" maxlength="255" value="" />
							<input type="hidden" name="asset3_id" value="" />
							&nbsp;&nbsp;&nbsp;

							<button id="btn_asset3_search" type="button" onclick="dd_itemselectf_v2('3'); return false;"><?php echo AC()->lang->__( 'Add' ); ?></button>
						</div>

						<div class="asset_holder">
							<table id="tbl_assets3" class="adminlist wp-list-table tableinne widefat striped posts" cellspacing="1">
							<thead><tr>
									<th width="1">&nbsp;</th>
									<th width="1"><?php echo AC()->lang->__( 'Number' ); ?></th>
									<th><?php echo AC()->lang->__( 'Name' ); ?></th>
							</tr></thead>
							<tbody>
								<?php
								if ( ! empty( $data->row->asset[3]->rows ) ) {
									foreach ( $data->row->asset[3]->rows as $asset_type => $row1 ) {
										if ( ! in_array( $asset_type, array( 'product', 'category', 'vendor', 'manufacturer', 'custom' ) ) ) {
											continue;
										}
										foreach ( $row1->rows as $row ) {
								?>
								<tr id="tbl_assets3_tr<?php echo $asset_type . '-' . $row->asset_id; ?>">
									<td class="last" align="right">
										<button type="button" onclick="deleterow('tbl_assets3_tr<?php echo $asset_type . '-' . $row->asset_id; ?>');return false;" >X</button>
										<input type="hidden" name="asset3listadded[]" value="<?php echo htmlspecialchars( $row->asset_id ); ?>">
										<input type="hidden" name="asset[3][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][asset_id]" value="<?php echo $row->asset_id; ?>">
										<input type="hidden" name="asset[3][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][asset_name]" value="<?php echo htmlspecialchars( $row->asset_name ); ?>">
										<input type="hidden" name="asset[3][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][qty]" value="<?php echo htmlspecialchars( $row->qty ); ?>">
									</td>
									<td><?php echo $row->qty; ?></td>
									<td><?php echo AC()->helper->vars( 'asset_type', $asset_type ); ?> => <?php echo $row->asset_name; ?> (<?php echo $row->asset_id; ?>)</td>
								</tr>
								<?php
										}
									}
								}
								?>
							</tbody>
							</table>

						</div>
					</div>
				</div>
			</div>
		</fieldset>
	</div>

	<div id="section_assets4" class="major_section hide f_buyxy2">
		<fieldset  class="adminform" ><legend><?php echo AC()->lang->__( 'BuyX' ); ?></legend>

			<div class="assetlistsection">
				<input type="text" name="asset4listvalidate" class="assetlistvalidator hide no_jv_ignore" value="" />

				<div class="aw-row">
					<div class="aw-label"><label><?php echo AC()->lang->__( 'Product' ); ?></label></div>
					<div class="aw-input">
						<div>
							<select name="asset4_function_type" onchange="asset_type_change(4, true);">
								<option value=""></option>
								<option value="product" SELECTED><?php echo AC()->lang->__( 'Product' ); ?></option>
								<option value="category"><?php echo AC()->lang->__( 'Category' ); ?></option>
								<?php if ( AC()->helper->vars( 'asset_type', 'manufacturer' ) !== null ) { ?>
									<option value="manufacturer"><?php echo AC()->lang->__( 'Manufacturer' ); ?></option>
								<?php } ?>
								<?php if ( AC()->helper->vars( 'asset_type', 'vendor' ) !== null ) { ?>
									<option value="vendor"><?php echo AC()->lang->__( 'Vendor' ); ?></option>
								<?php } ?>
								<?php if ( AC()->helper->vars( 'asset_type', 'custom' ) !== null ) { ?>
									<option value="custom"><?php echo AC()->lang->__( 'Custom' ); ?></option>
								<?php } ?>
							</select>
							&nbsp;&nbsp;&nbsp;

							<?php echo AC()->lang->__( 'Number' ); ?>: <input type="text" class="inputbox" name="asset4_qty" value="" style="width:50px;" />
							&nbsp;&nbsp;&nbsp;

							<?php echo AC()->lang->__( 'Search' ); ?>:
							<input class="inputbox" type="text" id="asset4_search" name="asset4_name" size="60" maxlength="255" value="" />
							<input type="hidden" name="asset4_id" value="" />
							&nbsp;&nbsp;&nbsp;

							<button id="btn_asset4_search" type="button" onclick="dd_itemselectf_v2('4'); return false;"><?php echo AC()->lang->__( 'Add' ); ?></button>
						</div>

						<div class="asset_holder">
							<table id="tbl_assets4" class="adminlist wp-list-table tableinne widefat striped posts" cellspacing="1">
							<thead><tr>
									<th width="1">&nbsp;</th>
									<th width="1"><?php echo AC()->lang->__( 'Number' ); ?></th>
									<th><?php echo AC()->lang->__( 'Name' ); ?></th>
							</tr></thead>
							<tbody>
								<?php
								if ( ! empty( $data->row->asset[4]->rows ) ) {
									foreach ( $data->row->asset[4]->rows as $asset_type => $row1 ) {
										if ( ! in_array( $asset_type, array( 'product', 'category', 'vendor', 'manufacturer', 'custom' ) ) ) {
											continue;
										}
										foreach ( $row1->rows as $row ) {
								?>
								<tr id="tbl_assets4_tr<?php echo $asset_type . '-' . $row->asset_id; ?>">
									<td class="last" align="right">
										<button type="button" onclick="deleterow('tbl_assets4_tr<?php echo $asset_type . '-' . $row->asset_id; ?>');return false;" >X</button>
										<input type="hidden" name="asset4listadded[]" value="<?php echo htmlspecialchars( $row->asset_id ); ?>">
										<input type="hidden" name="asset[4][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][asset_id]" value="<?php echo $row->asset_id; ?>">
										<input type="hidden" name="asset[4][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][asset_name]" value="<?php echo htmlspecialchars( $row->asset_name ); ?>">
										<input type="hidden" name="asset[4][rows][<?php echo $asset_type; ?>][rows][<?php echo $row->asset_id; ?>][qty]" value="<?php echo htmlspecialchars( $row->qty ); ?>">
									</td>
									<td><?php echo $row->qty; ?></td>
									<td><?php echo AC()->helper->vars( 'asset_type', $asset_type ); ?> => <?php echo $row->asset_name; ?> (<?php echo $row->asset_id; ?>)</td>
								</tr>
								<?php
										}
									}
								}
								?>
							</tbody>
							</table>

						</div>
					</div>
				</div>
			</div>
		</fieldset>
	</div>

	<div id="section_shipping" class="major_section hide f_giftcert f_shipping ">
		<fieldset  class="adminform">
		<legend><?php echo AC()->lang->__( 'Shipping' ); ?></legend>

			<div class="aw-row hide f_giftcert f_shipping">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Shipping' ); ?></label></div>
				<div class="aw-input">
					<?php $keys = empty( $data->row->asset[0]->rows->shipping->rows ) ? array() : array_keys( $data->row->asset[0]->rows->shipping->rows ); ?>
					<select name="asset[0][rows][shipping][rows][][asset_id]" MULTIPLE style="width:100%;">
						<?php
						foreach ( $data->shippinglist as $row ) {
							echo '<option value="' . $row->id . '" ' . ( in_array( $row->id, $keys ) ? 'SELECTED' : '' ) . '>' . $row->name . '</option>';
						}
						?>
					</select>
					<div class="awcontrols">
						<span class="awradio awbtn-group awbtn-group-yesno" >
							<input type="radio" id="asset_0_shipping_mode_rd_include" name="asset[0][rows][shipping][mode]" value="include" 
								<?php echo empty( $data->row->asset[0]->rows->shipping->mode ) || 'include' == $data->row->asset[0]->rows->shipping->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_shipping_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
							<input type="radio" id="asset_0_shipping_mode_rd_exclude" name="asset[0][rows][shipping][mode]" value="exclude"
								<?php echo ! empty( $data->row->asset[0]->rows->shipping->mode ) && 'exclude' == $data->row->asset[0]->rows->shipping->mode ? 'checked="checked"' : ''; ?> />
							<label for="asset_0_shipping_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						</span>
					</div>
				</div>
			</div>
		</fieldset>
	</div>

	<div id="section_otherrestrictions"		class="major_section hide f_coupon f_shipping f_buyxy f_buyxy2 f_combination">
		<fieldset  class="adminform">
		<legend><?php echo AC()->lang->__( 'Other Restrictions' ); ?></legend>

			<div class="aw-row hide f_coupon f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Customer' ); ?></label></div>
				<div class="aw-input">
					<table width="100%"><tr valign="top"><td>
						<select name="asset[0][rows][user][rows][][asset_id]" class="js-data-couponcustomer-ajax noselect2" MULTIPLE="multiple" style="width:100%;">
							<?php
							if ( ! empty( $data->row->asset[0]->rows->user ) ) {
								foreach ( $data->row->asset[0]->rows->user->rows as $row ) {
							?>
								<option value="<?php echo $row->asset_id; ?>" SELECTED><?php echo $row->asset_name; ?></option>
							<?php
								}
							}
							?>
						</select>
					</td><td width="1" nowrap>
						<div class="awcontrols">
							<span class="awradio awbtn-group awbtn-group-yesno" >
								<input type="radio" class="no_jv_ignore" id="user_mode_rd_include" name="asset[0][rows][user][mode]" value="include"
									<?php echo empty( $data->row->asset[0]->rows->user->mode ) || 'include' == $data->row->asset[0]->rows->user->mode ? 'checked="checked"' : ''; ?> />
								<label for="user_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
								<input type="radio" class="no_jv_ignore" id="user_mode_rd_exclude" name="asset[0][rows][user][mode]" value="exclude"
									<?php echo ! empty( $data->row->asset[0]->rows->user->mode ) && 'exclude' == $data->row->asset[0]->rows->user->mode ? 'checked="checked"' : ''; ?> />
								<label for="user_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
							</span>
						</div>
					</td></tr></table>
				</div>
			</div>

			<div class="aw-row hide f_coupon f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'User Group' ); ?></label></div>
				<div class="aw-input">
					<table width="100%"><tr valign="top"><td>
						<?php $keys = empty( $data->row->asset[0]->rows->usergroup->rows ) ? array() : array_keys( $data->row->asset[0]->rows->usergroup->rows ); ?>
						<select name="asset[0][rows][usergroup][rows][][asset_id]" MULTIPLE style="width:100%;">
							<?php
							if ( ! empty( $data->usergrouplist ) ) {
								foreach ( $data->usergrouplist as $row ) {
									echo '<option value="' . $row->id . '" ' . ( in_array( $row->id, $keys ) ? 'SELECTED' : '' ) . '>' . $row->name . '</option>';
								}
							}
							?>
						</select>
					</td><td width="1" nowrap>
						<div class="awcontrols">
							<span class="awradio awbtn-group awbtn-group-yesno" >
								<input type="radio" id="asset_0_usergroup_mode_rd_include" name="asset[0][rows][usergroup][mode]" value="include"
									<?php echo empty( $data->row->asset[0]->rows->usergroup->mode ) || 'include' == $data->row->asset[0]->rows->usergroup->mode ? 'checked="checked"' : ''; ?> />
								<label for="asset_0_usergroup_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
								<input type="radio" id="asset_0_usergroup_mode_rd_exclude" name="asset[0][rows][usergroup][mode]" value="exclude"
									<?php echo ! empty( $data->row->asset[0]->rows->usergroup->mode ) && 'exclude' == $data->row->asset[0]->rows->usergroup->mode ? 'checked="checked"' : ''; ?> />
								<label for="asset_0_usergroup_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
							</span>
						</div>
					</td></tr></table>
				</div>
			</div>

			<div class="aw-row hide f_coupon f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label">
					<label><?php echo AC()->lang->__( 'Country' ); ?></label>
				</div>
				<div class="aw-input">
					<?php $keys = empty( $data->row->asset[0]->rows->country->rows ) ? array() : array_keys( $data->row->asset[0]->rows->country->rows ); ?>
					<table width="100%"><tr valign="top"><td>
						<select name="asset[0][rows][country][rows][][asset_id]" MULTIPLE style="width:100%">
							<?php
							foreach ( $data->countrylist as $row ) {
								echo '<option value="' . $row->country_id . '" ' . ( in_array( $row->country_id, $keys ) ? 'SELECTED' : '' ) . '>' . $row->country_name . '</option>';
							}
							?>
						</select>
					</td><td width="1" nowrap>
						<div class="awcontrols">
							<span class="awradio awbtn-group awbtn-group-yesno" >
								<input type="radio" id="asset_0_country_mode_rd_include" name="asset[0][rows][country][mode]" value="include" 
									<?php echo empty( $data->row->asset[0]->rows->country->mode ) || 'include' == $data->row->asset[0]->rows->country->mode ? 'checked="checked"' : ''; ?> />
								<label for="asset_0_country_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
								<input type="radio" id="asset_0_country_mode_rd_exclude" name="asset[0][rows][country][mode]" value="exclude" 
									<?php echo ! empty( $data->row->asset[0]->rows->country->mode ) && 'exclude' == $data->row->asset[0]->rows->country->mode ? 'checked="checked"' : ''; ?> />
								<label for="asset_0_country_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
							</span>
						</div>
					</td></tr></table>
				</div>
			</div>

			<div class="aw-row hide f_coupon f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label">
					<label><?php echo AC()->lang->__( 'State/Province' ); ?></label>
				</div>
				<div class="aw-input">
					<?php $keys = empty( $data->row->asset[0]->rows->countrystate->country ) ? array() : $data->row->asset[0]->rows->countrystate->country; ?>
					<table width="100%"><tr valign="top"><td colspan="2">
						<select id="countrylist" name="asset[0][rows][countrystate][country][]" MULTIPLE style="width:100%;">
							<?php
							foreach ( $data->countrylist as $row ) {
								echo '<option value="' . $row->country_id . '" ' . ( in_array( $row->country_id, $keys ) ? 'SELECTED' : '' ) . '>' . $row->country_name . '</option>';
							}
							?>
						</select>
					</td></tr><tr><td>
						<select id="statelist" name="asset[0][rows][countrystate][rows][][asset_id]" style="width:100%;" MULTIPLE><option></option></select>
					</td><td width="1" nowrap>
						<div class="awcontrols">
							<span class="awradio awbtn-group awbtn-group-yesno" >
								<input type="radio" id="asset_0_countrystate_mode_rd_include" name="asset[0][rows][countrystate][mode]" value="include" 
									<?php echo empty( $data->row->asset[0]->rows->countrystate->mode ) || 'include' == $data->row->asset[0]->rows->countrystate->mode ? 'checked="checked"' : ''; ?> />
								<label for="asset_0_countrystate_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
								<input type="radio" id="asset_0_countrystate_mode_rd_exclude" name="asset[0][rows][countrystate][mode]" value="exclude"
									<?php echo ! empty( $data->row->asset[0]->rows->countrystate->mode ) && 'exclude' == $data->row->asset[0]->rows->countrystate->mode ? 'checked="checked"' : ''; ?> />
								<label for="asset_0_countrystate_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
							</span>
						</div>
					</td></tr></table>
				</div>
			</div>

			<div class="aw-row hide f_coupon f_shipping f_buyxy f_buyxy2 f_combination">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Payment Method' ); ?></label></div>
				<div class="aw-input">
					<table width="100%"><tr valign="top"><td>
						<?php $keys = empty( $data->row->asset[0]->rows->paymentmethod->rows ) ? array() : array_keys( $data->row->asset[0]->rows->paymentmethod->rows ); ?>
						<select name="asset[0][rows][paymentmethod][rows][][asset_id]" MULTIPLE style="width:100%;">
							<?php
							foreach ( $data->paymentmethodlist as $row ) {
								echo '<option value="' . $row->id . '" ' . ( in_array( $row->id, $keys ) ? 'SELECTED' : '' ) . '>' . $row->name . '</option>';
							}
							?>
						</select>
					</td><td width="1" nowrap>
						<div class="awcontrols">
							<span class="awradio awbtn-group awbtn-group-yesno" >
								<input type="radio" id="asset_0_paymentmethod_mode_rd_include" name="asset[0][rows][paymentmethod][mode]" value="include"
									<?php echo empty( $data->row->asset[0]->rows->paymentmethod->mode ) || 'include' == $data->row->asset[0]->rows->paymentmethod->mode ? 'checked="checked"' : ''; ?> />
								<label for="asset_0_paymentmethod_mode_rd_include" ><?php echo AC()->lang->__( 'Include' ); ?></label>
								<input type="radio" id="asset_0_paymentmethod_mode_rd_exclude" name="asset[0][rows][paymentmethod][mode]" value="exclude"
									<?php echo ! empty( $data->row->asset[0]->rows->paymentmethod->mode ) && 'exclude' == $data->row->asset[0]->rows->paymentmethod->mode ? 'checked="checked"' : ''; ?> />
								<label for="asset_0_paymentmethod_mode_rd_exclude" ><?php echo AC()->lang->__( 'Exclude' ); ?></label>
							</span>
						</div>
					</td></tr></table>
				</div>
			</div>
		</fieldset>
	</div>

	<div id="section_administration"		class="major_section hide f_coupon f_giftcert f_shipping f_buyxy f_buyxy2 f_combination">
		<fieldset  class="adminform">
		<legend><?php echo AC()->lang->__( 'Administration' ); ?></legend>

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Tag' ); ?></label></div>
				<div class="aw-input">
					<select multiple="true" id="e12" class="noselect2" name="tags[]" style="min-width:200px;max-width:500px;"></select>
				</div>
			</div>

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'UPC' ); ?></label></div>
				<div class="aw-input">
					<input class="inputbox" type="text" name="upc" maxlength="255" value="<?php echo $data->row->upc; ?>" />
				</div>
			</div>

			<div class="aw-row">
				<div class="aw-label"><label><?php echo AC()->lang->__( 'Admin Note' ); ?></label></div>
				<div class="aw-input">
					<textarea cols="18" rows="3" name="note" style="height:60px;"><?php echo $data->row->note; ?></textarea>
				</div>
			</div>

		</fieldset>
	</div>

	<div class="clr" style="padding-bottom:250px;">&nbsp;</div>

		</div>
	</div>
</div>
<input type="hidden" name="id" value="<?php echo $data->row->id; ?>" />

<div class="submitpanel"><span>
	<button type="button" onclick="submitForm(this.form, 'apply');" class="button button-large button-apply"><?php echo AC()->lang->__( 'Apply' ); ?></button>
	<button type="button" onclick="submitForm(this.form, 'save');" class="button button-primary button-large button-save"><?php echo AC()->lang->__( 'Save' ); ?></button>
</span><div class="clear"></div></div>

<?php echo AC()->helper->render_layout( 'admin.form.footer' ); ?>
</div>


<div id="cumulative_template" style="display:none;">
	<style>
		#cumulative_template_data input.number_box.a { width:90%; }
		#cumulative_template_data input.number_box.b { width:50px; }
	</style>
	<div id="___template___cumulative_template_data">
		<table style="width:100%;">
		<tr><td style="border-bottom:1px solid #cccccc" colspan="1" valign="bottom"><b><?php echo AC()->lang->__( 'Value Definition' ); ?></b></td></tr>
		<tr><td height="10"></td></tr>

		<tr><td colspan="1" style="width:100%;">
			<form id="___template___frmcumulative" name="___template___frmcumulative" method="post" onsubmit="return CheckForm();">
				<table style="width:100%;">
				<tr><td><label><?php echo AC()->lang->__( 'Process Type' ); ?></label></td><td>
						<select style="width:147px;" class="inputbox noselect2" name="valdef_process" id="valdef_process">
							<option value="progressive"><?php echo AC()->lang->__( 'Progressive' ); ?></option>
							<option value="step"><?php echo AC()->lang->__( 'Step' ); ?></option>
						</select>
					</td></tr>
				<tr><td><label><?php echo AC()->lang->__( 'Ordering' ); ?></label></td>
					<td><select name="valdef_order" class="noselect2">
							<?php
							foreach ( AC()->helper->vars( 'process_type_buyxy' ) as $key => $value ) {
								echo '<option value="' . $key . '">' . $value . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<tr><td colspan="2"><input type="checkbox" name="cumqtytype" value="1"> <?php echo AC()->lang->__( 'Apply Distinct Count' ); ?></td></tr>
				</table>

				<br />

				<table style="width:100%;">
				<tr><td style="width:100%;">
					<table id="___template___tbldata" style="width:100%;">
					<tr><th style="border-bottom:1px solid #cccccc;"><?php echo AC()->lang->__( 'Number of Products' ); ?></th>
						<th style="border-bottom:1px solid #cccccc;"><?php echo AC()->lang->__( 'Value' ); ?></th>
						<td style="width:1%;">&nbsp;</td>
					</tr>
					<tr valign="bottom">
						<td><input class="number_box a" type="text" name="cumcount01" value="" maxlength="15" size="4" ></td>
						<td><input class="number_box b" type="text" name="cumvalue01" value="" maxlength="15" size="4" ></td>
						<td></td>
					</tr>
					<tr id="___template___trRow02" valign="bottom">
						<td><input class="number_box a" type="text" name="cumcount02" value="" maxlength="15" size="4" ></td>
						<td><input class="number_box b" type="text" name="cumvalue02" value="" maxlength="15" size="4" ></td>
						<td style="vertical-align:top;"><input type="button" onclick="deleterowT('trRow02');" class="p" value="x"></td>
					</tr>
					<tr id="___template___trRow03" valign="bottom">
						<td><input class="number_box a" type="text" name="cumcount03" value="" maxlength="15" size="4" ></td>
						<td><input class="number_box b" type="text" name="cumvalue03" value="" maxlength="15" size="4" ></td>
						<td style="vertical-align:top;"><input type="button" onclick="deleterowT('trRow03');" class="p" value="x"></td>
					</tr>
					</table>
				</td></tr>
				<tr><td><input type="button" name="addaccount" value="<?php echo AC()->lang->__( 'Add New Entry' ); ?>" onclick="newline();"></td></tr>
				<tr><td height="10"></td></tr>
				<tr><td height="10"></td></tr>
				<tr><td align="right"><input type="button" value="<?php echo AC()->lang->__( 'Submit' ); ?>" style="width:100%;" onclick="populateparent();"></td></tr>
				</table>
			</form>
		</td></tr>
		</table>
	</div>
</div>


