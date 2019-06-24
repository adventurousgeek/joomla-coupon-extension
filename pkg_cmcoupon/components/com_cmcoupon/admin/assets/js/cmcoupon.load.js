/**
 * CmCoupon
 *
 * @package Joomla CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/


jQuery(document).ready(function () {

	if ( typeof Joomla !== "undefined" && typeof Joomla.JoomlaTinyMCE !== "undefined" && typeof Joomla.JoomlaTinyMCE.setupEditors !== "undefined" ) { 
	// Editor: TinyMCE
		Joomla.JoomlaTinyMCE.setupEditors();
	}
	else if (typeof WFEditor !== "undefined") { 
	// Editor: JCE
		if (typeof WFEditor.load !== "undefined") {
			if ( typeof tinyMCE && typeof tinyMCE.editors !== 'undefined' ) {
				tinyMCE.editors = [];
			}
			WFEditor.load();
		}
		else if ( typeof tinyMCE !== "undefined" ) { 
			jQuery( 'textarea.wfEditor' ).each( function( i, obj ) {
				id = jQuery( this ).attr( 'id' );
				tinyMCE.execCommand( 'mceAddEditor', false, id );
			} );
		}
	}
	else if ( typeof tinyMCE !== "undefined" ) { 
	// Editor: TinyMCE
		jQuery( 'textarea.mce_editable' ).each( function( i, obj ) {
			id = jQuery( this ).attr( 'id' );
			tinyMCE.execCommand( 'mceAddEditor', false, id );
		} );
	}
	else if (typeof CodeMirror !== "undefined") { 
	// Editor: CodeMirror

		// show panels, need to be visible while initializing otherwise display improperly
		jQuery( 'div.panel', '.tabs-wrap' ).show();

		if (typeof codemirror_cmcoupon_options !== "undefined") { 
			jQuery( '.btn-toolbar' ).each( function( i, obj ) {
				if (  jQuery( this ).attr( 'id' ) != 'editor-xtd-buttons' ) {
					return;
				}

				id = jQuery( this ).prev().attr( 'id' );
				if ( id == undefined ) return;

				Joomla.editors.instances[id] = CodeMirror.fromTextArea(document.getElementById(id), codemirror_cmcoupon_options);
			});
		}
		else {
			jQuery(document).find('textarea.codemirror-source').each(function () {
				var input = jQuery(this).removeClass('codemirror-source');
				var id = input.prop('id');

				Joomla.editors.instances[id] = CodeMirror.fromTextArea(this, input.data('options'));
			});
		}

		// hide panels again
		jQuery( document.body ).trigger( 'wc-init-tabbed-panels' );
	}

});
