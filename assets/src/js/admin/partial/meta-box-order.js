( function( $ ) {
	'use strict';
	jQuery( function() {
		// eslint-disable-next-line no-var
		$( '.order-date.date-picker-backendorder' ).on( 'change', function() {
			const m = this.value.split( '-' );
			[ 'aa', 'mm', 'jj' ].forEach( function( v, k ) {
				$( 'input[name="' + v + '"]' ).val( m[ k ] );
			} );
		} ).datepicker( {
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 1,
			showButtonPanel: true,
			select() {
				console.log( arguments );
			},
		} );
	} );
}( jQuery ) );
