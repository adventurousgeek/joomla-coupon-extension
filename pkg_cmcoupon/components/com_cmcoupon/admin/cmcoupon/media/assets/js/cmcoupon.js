/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

var jquery_validate_setting_ignore = ':hidden:not(.no_jv_ignore)';

function trim(str) { return jQuery.trim( str ); }

function isUnsignedInteger(s) {
	return ( s.toString().search( /^[0-9]+$/ ) == 0);
}

function monkeyPatchAutocomplete() {

	jQuery.ui.autocomplete.prototype._renderItem = function (ul, item) {

		var keywords = jQuery.trim( this.term ).split( ' ' ).join( '|' );
		var output = item.label.replace( new RegExp( "(" + keywords + ")", "gi" ), '<span style="font-weight:bold;background-color:yellow;">$1</span>' );

		return jQuery( "<li>" )
			.append( jQuery( "<a>" ).html( output ) )
			.appendTo( ul );
	};
}

function submitForm(form, task) {
	form.task.value = task;
	action = form.action.split( '#' );

	if ( action[1] == undefined || jQuery.trim( action[1] ) == '' ) {
		// normal form submit.
		submitFormEditors();				
		form.submit();
	} else {
		// sammy form submit.
		var is_submit_form = true;
		if ( jQuery( form ).data( 'validator' ) ) {
			jQuery( form ).validate().settings.ignore = jquery_validate_setting_ignore;
			if ( ! jQuery( form ).valid() ) {
				is_submit_form = false;
			}
		}

		if ( is_submit_form ) {
			submitFormEditors();				
			appsammyjs.runRoute( form.method, '#' + action[1], appsammyjs._parseFormParams( jQuery( form ) ) );
			// appsammyjs._checkFormSubmission(form);.
		}
	}
}

function submitFormEditors() {
	if ( typeof( tinyMCE ) != "undefined" ) {
		if ( typeof tinyMCE.triggerSave !== "undefined" ) {
			tinyMCE.triggerSave();
		}
	}
	else if (typeof CodeMirror !== "undefined" ) {
		if ( typeof Joomla !== "undefined" && typeof Joomla.editors !== "undefined" && typeof Joomla.editors.instances !== "undefined" ) {
			for (id in Joomla.editors.instances) {
				if ( document.getElementById( id ) == undefined ) {
					continue;
				}
				document.getElementById( id ).value = Joomla.editors.instances[ id ].getValue();
			}
		}
	}				

}

function getjqdd( elem_id, hidden_field, param_task, param_type, ajax_url, validator, autoaddbtn ) {
	monkeyPatchAutocomplete();
	jQuery( "#" + elem_id )
		.data( 'id', hidden_field )
		.autocomplete(
			{
				source: function( request, response ) {
					jQuery.getJSON(
						ajax_url,
						{ type:'ajax', task:param_task, element:param_type, term: request.term },
						response
					);
				},
				autoFocus: true,
				minLength: 2,
				selectFirst: true,
				delay: 0,
				select: function( event, ui ) {
					if ( ui.item ) {
						jQuery( this ).val( ui.item.label );
						jQuery( this ).closest( 'form' ).find( 'input[name="' + hidden_field + '"]' ).val( ui.item.id ).triggerHandler('change');
						jQuery( this ).trigger( "autoadd" );

						jQuery( 'li.ui-menu-item' ).remove();  // remove result set.
						jQuery( this ).select(); // highlight the text.
						return false;
					}
				},
				close: function ( event, ui ) { jQuery( this ).trigger( "validate" ); }
			}
		)
		.attr( "parameter_id", jQuery( this ).closest( 'form' ).find( 'input[name="' + hidden_field + '"]' ).val() )
		.bind( "empty_value", function( event ) { jQuery( this ).closest( 'form' ).find( 'input[name="' + hidden_field + '"]' ).val( '' ); } )
		.bind( "validate", function( event ) {} )
		.bind( "autoadd", function( event ) {} )
		.focus(
			function() {
				jQuery( this )
					.select()
					.mouseup(
						function ( e ) {
							e.preventDefault();
							jQuery( this ).unbind( "mouseup" );
						}
					);
			}
		);
	if ( validator != undefined && validator == 'check_user' ) {
		jQuery( "#" + elem_id )
			.bind( "validate", function ( event ) {
				var html = jQuery.ajax( {
					url: ajax_url,
					data: "type=ajax&task=ajax_user&term=" + jQuery( '#' + elem_id ).val(),
					//async: false ,
					success: function ( rtn ) {
						user_id = rtn * 1;
						if ( isNaN( user_id ) || user_id <= 0 ) {
							jQuery( this ).closest( 'form' ).find( 'input[name="' + hidden_field + '"]' ).val( '' );
							jQuery( '#' + elem_id ).val( '' );
						}
					}
				} ).responseText
			} );
	}
	if ( autoaddbtn != undefined ) {
		jQuery( "#" + elem_id )
			.bind( "autoadd", function ( event ) {
				jQuery( "#" + autoaddbtn ).click();
			} );
	}
}

function hideOtherLanguage( id ) {
	jQuery( '.translatable-field' ).hide();
	jQuery( '.lang-' + id ).show();
}

if (typeof showNotice === 'undefined') {
	showNotice = {
		warn : function() {
			var msg = glb_delete_confirm;
			if ( confirm( msg ) ) {
				return true;
			}

			return false;
		},

		note : function(text) {
			alert( text );
		}
	};
}
