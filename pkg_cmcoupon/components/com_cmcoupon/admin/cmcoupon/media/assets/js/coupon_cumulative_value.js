/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

var ext = 3;
function showvaluedefinition() {

	html = jQuery( '#cumulative_template' ).html();
	html = html.replace( /id\=\"\_\_\_template\_\_\_/g, 'id="' );
	html = html.replace( /id\=\'\_\_\_template\_\_\_/g, "id='" );
	html = html.replace( /name\=\"\_\_\_template\_\_\_/g, 'name="' );
	html = html.replace( /name\=\'\_\_\_template\_\_\_/g, "name='" );

	page_alert( 'modal_coupon_value_def',html );

	// focus, loose focus from input field.
	document.frmcumulative.elements['cumcount01'].focus()

	// populate if value exists.
	ext = 3;
	if (document.adminForm.coupon_value_def.value != '') {
		eachrow = document.adminForm.coupon_value_def.value.split( ';' );
		counting = 0;
		n = eachrow.length;
		for (i = 0; i < n; i++) {
			if (trim( eachrow[i] ) != '' && eachrow[i].substr( 0,1 ) != '[') {
				tmp = eachrow[i].split( '-' );
				if (tmp[0] != undefined && tmp[1] != undefined) {
					counting++;
					var extstr = PadDigits( counting,2 );
					if (counting > 3) {
						newline();
					}
					document.frmcumulative.elements['cumcount' + extstr].value = tmp[0];
					document.frmcumulative.elements['cumvalue' + extstr].value = tmp[1];
				}
			}
		}
		if (eachrow[eachrow.length - 1].substr( 0,1 ) == '[') {
			opts = eachrow[eachrow.length - 1].substr( 1 );
			opts = opts.substr( 0,opts.length - 1 );
			eachopt = opts.split( '&' );
			n = eachopt.length;
			for (i = 0; i < n; i++) {
				opt = eachopt[i].split( '=' );
				if (opt[0] != undefined && opt[1] != undefined) {
					if (opt[0] == 'type') {
						jQuery( '#frmcumulative select[name=valdef_process]' ).val( opt[1] );
					} else if (opt[0] == 'order') {
						jQuery( '#frmcumulative select[name=valdef_order]' ).val( opt[1] );
					} else if (opt[0] == 'qty_type' && opt[1] == 'distinct') {
						document.frmcumulative.elements['cumqtytype'].checked = true;
					}
				}
			}
		}
	}

}

function page_alert(id,html) {

	id = id == undefined ? 'tester' : id;

	// if page msg instance exists, return.
	if (jQuery( "#" + id ).length == 0) {
		jQuery( '<div id="' + id + '" style="display:none;"></div>' ).appendTo( 'body' );
	}

	jQuery( '#' + id ).html( html ).dialog(
		{
			close: function( event, ui ) {
				jQuery( 'input[name=coupon_value_def]', 'form[name="adminForm"]' ).blur();
				jQuery( "#" + id ).empty().remove();
			}
		}
	);
	return;
}

function newline() {
	ext++;
	var extstr = PadDigits( ext,2 );
	var tbl = document.getElementById( 'tbldata' );
	var row = document.createElement( "tr" );
	row.id = "trRow" + extstr;

	var cell = document.createElement( "td" );
	cell.innerHTML = '<input class="number_box a" type="text" name="cumcount' + extstr + '" value="" maxlength="15" size="4">';
	row.appendChild( cell );

	var cell = document.createElement( "td" );
	cell.innerHTML = '<input class="number_box b" type="text" name="cumvalue' + extstr + '" value="" maxlength="15" size="4">';
	row.appendChild( cell );

	var cell = document.createElement( "td" );
	cell.innerHTML = '<input type="button" onclick="deleterowT(\'trRow' + extstr + '\');" class="p" value="x">';
	cell.style.verticalAlign = 'top';
	row.appendChild( cell );

	var tbody = document.createElement( "tbody" );
	tbody.appendChild( row );

			   tbl.appendChild( tbody );

}
function deleterowT(id) {
	var tr = document.getElementById( id );
	tr.parentNode.removeChild( tr );
	runtotal();
}

function PadDigits(n, totalDigits) {
	var n = n.toString();
	var pd = '';
	if (totalDigits > n.length) {
		size = totalDigits - n.length;
		for (m = 0; m < size; m++) {
			pd += '0';
		}
	}
	return pd + n.toString();
}
function populateparent() {
	string = '';
	used = {};

	var key_array = []
	for (i = 1; i <= ext; i++) {
		myext = PadDigits( i,2 );
		if (document.frmcumulative.elements['cumcount' + myext] != undefined) {
			cnt = document.frmcumulative.elements['cumcount' + myext].value;
			if (cnt != '' && ! isNaN( cnt ) && cnt > 0) {
				key_array.push( [myext,cnt] );
			}
		}
	}
	key_array.sort( key_array_cmp );

	n = key_array.length;
	for (i = 0; i < n; i++) {
		myext = key_array[i][0];
		cnt = document.frmcumulative.elements['cumcount' + myext].value;
		val = document.frmcumulative.elements['cumvalue' + myext].value;

		if ( (Object.keys( used ).length == 0 && (val == '' || isNaN( val ) || val < 0))
		|| (Object.keys( used ).length > 0 && (val == '' || isNaN( val ) || val < 0))
		|| cnt == ''
		|| isNaN( cnt )
		|| cnt < 1 ) {
		} else {
			if (used[cnt] == undefined) {
				used[cnt] = 1;
				string += cnt + '-' + val + ';';
				if (val == 0 && Object.keys( used ).length > 1) {
					break;
				}
			}
		}
	}
	if (string != '') {
		xattr = [];
		if (document.frmcumulative.elements['valdef_process'].value != '') {
			xattr.push( 'type=' + document.frmcumulative.elements['valdef_process'].value );
		}
		if (document.frmcumulative.elements['valdef_order'].value != '') {
			xattr.push( 'order=' + document.frmcumulative.elements['valdef_order'].value );
		}
		if (document.frmcumulative.elements['cumqtytype'].checked) {
			xattr.push( 'qty_type=distinct' );
		}
		if (xattr.length > 0) {
			string += '[' + xattr.join( '&' ) + ']';
		}
	}
	document.adminForm.coupon_value_def.value = string;

	couponvalue_type_change();

	jQuery( 'input[name=coupon_value_def]', 'form[name="adminForm"]' ).blur();
	jQuery( "#modal_coupon_value_def" ).empty().remove();
}

if (typeof Object.keys != 'function') {
	Object.keys = function(obj) {
		if (typeof obj != "object" && typeof obj != "function" || obj == null) {
			throw TypeError( "Object.keys called on non-object" );
		}
		var keys = [];
		for (var p in obj) {
			obj.hasOwnProperty( p ) && keys.push( p );
		}
		return keys;
	}
}
function key_array_cmp(a, b) { return a[1] - b[1]; }
