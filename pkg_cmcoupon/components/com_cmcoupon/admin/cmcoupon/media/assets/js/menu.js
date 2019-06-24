/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

jQuery( document ).ready(
	function () {
		jQuery( "#cmmenu .dropdown-toggle" ).dropdown();
		jQuery( "#cmmenu ul.nav li.dropdown" ).hover(
			function() {
				jQuery( this ).find( ".dropdown-menu" ).stop( true, true ).show();
				jQuery( this ).addClass( "active" );
			},
			function() {
				jQuery( this ).find( ".dropdown-menu" ).stop( true, true ).hide();
				jQuery( this ).removeClass( "active" );
			}
		);
	}
)
