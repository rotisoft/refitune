/**
 * WP Refiner - Admin script.
 *
 * "Összes be-/kikapcsolása" checkbox logika csoportos beállításokhoz.
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var groupAlls = document.querySelectorAll( '.refitune-group-all' );

		groupAlls.forEach( function ( allCheckbox ) {
			var group = allCheckbox.dataset.group;
			var items = document.querySelectorAll( '.refitune-group-item[data-group="' + group + '"]' );

			/**
			 * Az "összes" checkbox állapotát frissíti az egyedi checkboxok alapján.
			 */
			function updateAllState() {
				var checkedCount = Array.prototype.filter.call( items, function ( cb ) {
					return cb.checked;
				} ).length;

				if ( checkedCount === 0 ) {
					allCheckbox.checked       = false;
					allCheckbox.indeterminate = false;
				} else if ( checkedCount === items.length ) {
					allCheckbox.checked       = true;
					allCheckbox.indeterminate = false;
				} else {
					allCheckbox.checked       = false;
					allCheckbox.indeterminate = true;
				}
			}

			allCheckbox.addEventListener( 'change', function () {
				items.forEach( function ( cb ) {
					cb.checked = allCheckbox.checked;
				} );
			} );

			items.forEach( function ( cb ) {
				cb.addEventListener( 'change', updateAllState );
			} );

			updateAllState();
		} );

		// WordPress Color Picker inicializálása.
		if ( typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker ) {
			jQuery( '.refitune-color-picker' ).wpColorPicker();
		}
	} );
}() );
