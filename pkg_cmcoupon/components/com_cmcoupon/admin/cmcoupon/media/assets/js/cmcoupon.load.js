/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

jQuery( document ).ready(function () {

	jQuery( window ).unbind( "scroll" ); // dsiable any scroll attachments initially.

	// Turn radios into btn-group.
	jQuery( '.awradio.awbtn-group label' ).addClass( 'awbtn' );
	jQuery( '.awbtn-group label:not(.active)' ).click( function() {
		var label = jQuery( this );
		var input = jQuery( '#' + label.attr( 'for' ) );
		label.closest( '.awbtn-group' ).find( 'label' ).removeClass( 'active awbtn-success awbtn-danger awbtn-primary' );
		if ( input.val() == '' ) {
			label.addClass( 'active awbtn-primary' );
		} else if ( input.val() == 0 || input.val() == 'exclude' ) {
			label.addClass( 'active awbtn-danger' );
		} else {
			label.addClass( 'active awbtn-success' );
		}
		input.prop( 'checked', true );
		input.click().change(); // needed for ie and hidden elements not being recognized.
	} );
	jQuery( '.awbtn-group input[checked=checked]' ).each( function() {
		if ( jQuery( this ).val() == '' ) {
			jQuery( 'label[for=' + jQuery( this ).attr( 'id' ) + ']' ).addClass( 'active awbtn-primary' );
		} else if ( jQuery( this ).val() == 0 || jQuery( this ).val() == 'exclude' ) {
			jQuery( 'label[for=' + jQuery( this ).attr( 'id' ) + ']' ).addClass( 'active awbtn-danger' );
		} else {
			jQuery( 'label[for=' + jQuery( this ).attr( 'id' ) + ']' ).addClass( 'active awbtn-success' );
		}
	});

	if ( typeof glb_str_err_valid == 'undefined' ) {
		glb_str_err_valid = 'xxxxxx';
	}

	// set jquery validation defaults.
	jQuery.validator.setDefaults( {
		errorPlacement: function( error, element ) {
			if ( element.is( ":radio" ) ) {
				error.appendTo( element.parents( '.awcontrols' ) );
			} else if ( element.is( "textarea" ) ) {
				error.prependTo( element.parents( '.aw-input' ) );
			} else {
				error.insertAfter( element ); // This is the default behavior.
			}
		},
		highlight: function( element, errorClass, validClass ) {
			elem = jQuery( element );
			if ( element.type === "radio" && elem.parent().hasClass( 'awradio' ) ) {
				elem.parents( 'span.awradio' ).addClass( errorClass ).removeClass( validClass );
			} else if ( element.type === "radio" ) {
				elem.addClass( errorClass ).removeClass( validClass );
			} else if ( elem.hasClass( 'select2-offscreen' ) ) {
				jQuery( '#s2id_' + elem.attr( 'id' ) ).find( 'a' ).addClass( errorClass ).removeClass( validClass );
			} else if ( element.type === 'textarea' ) {
				elem.parents( 'div.editor' ).addClass( errorClass ).removeClass( validClass );
			} else if ( element.type === "text" && elem.hasClass( 'assetlistvalidator' ) && elem.parent().hasClass( 'assetlistsection' ) ) {
				elem.parent().addClass( errorClass ).removeClass( validClass );
			} else {
				elem.addClass( errorClass ).removeClass( validClass );
			}
		},
		unhighlight: function( element, errorClass, validClass ) {
			elem = jQuery( element );
			if ( element.type === "radio" && elem.parent().hasClass( 'awradio' ) ) {
				elem.parents( 'span.awradio' ).removeClass( errorClass ).addClass( validClass );
			} else if ( element.type === "radio" ) {
				elem.removeClass( errorClass ).addClass( validClass );
			} else if ( elem.hasClass( 'select2-offscreen' ) ) {
				jQuery( '#s2id_' + elem.attr( 'id' ) ).find( 'a' ).removeClass( errorClass ).addClass( validClass );
			} else if ( element.type === 'textarea' ) {
				elem.parents( 'div.editor' ).removeClass( errorClass ).addClass( validClass );
			} else if ( element.type === "text" && elem.hasClass( 'assetlistvalidator' ) && elem.parent().hasClass( 'assetlistsection' ) ) {
				elem.parent().removeClass( errorClass ).addClass( validClass );
			} else {
				elem.removeClass( errorClass ).addClass( validClass );
			}
		}
	});

	jQuery.extend( jQuery.validator.messages, { required: glb_str_err_valid, email: glb_str_err_valid, number: glb_str_err_valid, digits: glb_str_err_valid, min: glb_str_err_valid } );

	jQuery.validator.addMethod( 'checkElementId', function ( value, element, param ) {
		is_required = false;
		if ( typeof param.required !== 'undefined' ) {
			is_required = jQuery.isFunction( param.required ) ? param.required( element ) : param.required;
		}
		if ( value=='' && !is_required ) return true;

		if ( ! jQuery( element ).is( ":visible" ) ) {
			return true;
		}

		elem = element.form.elements[ jQuery( "#" + element.name ).data( 'id' ) ];
		if ( elem == undefined ) {
			return true;
		}

		v_to_check = parseInt( elem.value );
		if ( ! isNaN( v_to_check ) && v_to_check > 0 ) {
			return true;
		}
		return false;
	}, glb_str_err_valid );

	jQuery.validator.addMethod( 'editorcheck', function ( value, element, param ) {
		the_content = '';
		is_required = false;
		if ( typeof param.required !== 'undefined' ) {
			if ( jQuery.isFunction( param.required ) ) {
				is_required = param.required( element );
			} else {
				is_required = param.required;
			}
		}

		if ( typeof param.getcontent !== 'undefined' ) {
			the_content = eval( param.getcontent );
		}

		if ( ! is_required && jQuery.trim( the_content ) == '' ) {
			return true;
		}

		return jQuery.trim( the_content ) != '' ? true : false;
	}, glb_str_err_valid );

	jQuery.fn.validatorClearMyMessages = function() {
		// remove all errors from screen.
		jQuery( this ).find( 'label.error' ).remove();
		jQuery( this ).find( '.error,.valid' ).css( 'border-color', '' ).removeClass( 'error' ).removeClass( 'valid' );
		jQuery( this ).find( '.form-error' ).remove();
	};

	jQuery( 'select' ).not( ".noselect2" ).select2( {
		theme: 'classic',
		minimumResultsForSearch: 10,
		width: 'resolve'
	} );

	// Tabbed Panels.
	// called after select2 since tabs hide, and problem with select2 width resolve on hidden elements
	jQuery( document.body )
		.on( 'wc-init-tabbed-panels', function() {
			jQuery( 'ul.wc-tabs' ).show();
			jQuery( 'ul.wc-tabs a' ).click( function( e ) {
				e.preventDefault();
				var panel_wrap = jQuery( this ).closest( 'div.tabs-wrap' );
				jQuery( 'ul.wc-tabs li', panel_wrap ).removeClass( 'active' );
				jQuery( this ).parent().addClass( 'active' );
				jQuery( 'div.panel', panel_wrap ).hide();
				jQuery( jQuery( this ).attr( 'href' ) ).show();
			} );
			jQuery( 'div.tabs-wrap' ).each( function() {
				jQuery( this ).find( 'ul.wc-tabs li' ).eq( 0 ).find( 'a' ).click();
			} );
		} )
		.trigger( 'wc-init-tabbed-panels' );

	jQuery( 'textarea.wp-editor-area' ).each( function( i, obj ) {
		id = jQuery( this ).attr( 'id' );
		tinymce.execCommand( 'mceRemoveEditor', true, id );
		var init = tinymce.extend( {}, tinyMCEPreInit.mceInit[ id ] );
		try {
			tinymce.init( init );
		} catch ( e ) {
		}
		tinyMCE.execCommand( 'mceAddEditor', false, id );
	} );
});
