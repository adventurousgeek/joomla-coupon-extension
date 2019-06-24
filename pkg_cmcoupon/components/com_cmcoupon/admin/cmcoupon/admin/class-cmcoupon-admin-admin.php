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

/**
 * Admin class
 */
class CmCoupon_Admin_Admin {

	/**
	 * Displa admin
	 */
	public static function display() {
		AC()->helper->add_class( 'CmCoupon_Admin_Menu' );
		$menu = new CmCoupon_Admin_Menu();
		echo $menu->process();
		?>
		
		<script>
		var appsammyjs = null;
		var glb_str_err_valid = '<?php echo addslashes( AC()->lang->__( 'enter a valid value' ) ); ?>';
		var glb_delete_confirm = '<?php echo addslashes( AC()->lang->__( 'Permanently delete item(s)?' ) ); ?>';

		(function($) {

			<?php echo $menu->processjs(); ?>

			$(function() {
				appsammyjs.run('#/cmcoupon/');
			});
		
		})(jQuery);
		function ajaxCleanup(context) {
			
			// close menu items
			jQuery('#cmmenu ul.nav').find(".dropdown-menu").hide();
			jQuery('#cmmenu ul.nav').find("li.dropdown").removeClass('active');
			
			// empty the canvas and add waiting image
			jQuery(context.$element())
				.empty()
				.html('<div style="text-align:center;margin-top:20px;"><img id="waitingimg_parent" src="<?php echo CMCOUPON_ASEET_URL; ?>/images/loading.gif" height="60" /></div>')
			;

			if ( typeof( tinyMCE ) != "undefined" ) {
				if ( typeof tinyMCE.remove !== "undefined" ) { 
					try {
						tinyMCE.remove();
					} catch (e) {}
				}
			}
			
			// highlight the menu
			updateCmMenu();
		}
		function gotoUrl(url, ajax_url, context) {
			ajaxCleanup(context);
			params = context.params;
			params = Object.assign({}, params); // convert Sammy.object to normal object
			//parameters = jQuery.trim(jQuery.param(params));
			rand = Math.floor(Math.random() * 100000000);
			
			paramstring = '';
			for (var key in params) paramstring += '&'+key+'='+encodeURIComponent(params[key]);

			real_url = ajax_url+'&'+url+'&urlx='+encodeURIComponent(context.path)+paramstring+'&cache='+rand;
			//context.render(real_url, {}).appendTo(context.$element());
			jQuery.get(real_url, params, function(data) {
				jQuery(context.$element()).html(data);
			});
		}
		function postUrl(url, ajax_url, context) {
			ajaxCleanup(context);

			params = context.params;
			params = Object.assign({}, params); // convert Sammy.object to normal object
			parameters = '';
			x = context.path.split('?');
			if(x[1] != undefined) parameters = jQuery.trim(x[1]);
			rand = Math.floor(Math.random() * 100000000);

			real_url = ajax_url+'&'+url+'&urlx='+encodeURIComponent(context.path)+(parameters!='' ? '&parameters='+encodeURIComponent(parameters) : '')+'&cache='+rand;
			jQuery.post(real_url, params, function(data) {
				jQuery(context.$element()).html(data);
			});
		}
	
		function postUrlUpload(url, ajax_url, context) {
			// create formdata before destroying form
			var form = jQuery('#'+context.params.form_id)[0];
			var formData = new FormData(form);

			ajaxCleanup(context);

			parameters = '';
			x = context.path.split('?');
			if(x[1] != undefined) parameters = jQuery.trim(x[1]);
			rand = Math.floor(Math.random() * 100000000);

			real_url = ajax_url+'&'+url+'&urlx='+encodeURIComponent(context.path)+(parameters!='' ? '&parameters='+encodeURIComponent(parameters) : '')+'&cache='+rand;
			
			jQuery.ajax({
				url: real_url,
				type: 'POST',
				data: formData,
				async: false,
				cache: false,
				contentType: false,
				processData: false,
				success: function (data) {
					jQuery(context.$element()).html(data);
				}
			});

		}
	
		function updateCmMenu() {
			url = window.location.href;
			jQuery('#cmmenu').find('li').removeClass('active').removeClass('current');
			var $li = jQuery('#cmmenu').find("a[href='"+url+"']").parent();
			if($li.length==0) {
				parts1 = url.split('#');
				if(parts1[1]!=undefined) {
					parts2 = parts1[1].split('?');
					url = parts1[0]+'#'+parts2[0];
					var $li = jQuery('#cmmenu').find("a[href='"+url+"']").parent();
				}
			}
			if($li.length==0) {
				parts = url.split('#');
				if(parts[1]!=undefined) {
					last = parts[1].lastIndexOf('/');
					url = parts[0]+'#'+parts[1].substr(0,last);
					var $li = jQuery('#cmmenu').find("a[href='"+url+"']").parent();
				}
			}
			if($li.length!=0) {
				var $parentli = $li.parent().parent();
				$li.addClass('current');
				$parentli.addClass('current');
			}
		}
		
		function refreshPage() { appsammyjs.runRoute('get', window.location.hash); }
		</script>
		<?php
		// Load editor files without loading editor.
		AC()->helper->get_editor( '', 'klsdkljskldjsd-klsdlkdsksjdk_klskdslkdjlskldj_' );
		?>
	  
		<div>
			<div id="cm-main"></div>
		</div>
		
		
	  
		<?php
	}
}

