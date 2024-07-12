( function( $ ) {
	'use strict';
	jQuery( function() {
		// eslint-disable-next-line no-var
		const $listItems = $( '#learn-press-order .list-order-items' ).find( 'tbody' );

		$listItems.on( 'click', '.remove-order-item', function( e ) {
			e.preventDefault();

			const $item = $( this ).closest( 'tr' ),
				item_id = $item.data( 'item_id' );

			$item.remove();

			if ( $listItems.children( ':not(.no-order-items)' ).length === 0 ) {
				$listItems.find( '.no-order-items' ).show();
			}

			$.ajax( {
				url: window.location.href,
				type: 'POST',
				data: {
					order_id: $( '#post_ID' ).val(),
					items: [ item_id ],
					'lp-ajax': 'remove_items_from_order',
					remove_nonce: $( this ).closest( '.order-item-row' ).data( 'remove_nonce' ),
				},
				dataType: 'text',
				success( response ) {
					const jsonString = response.replace( /<-- LP_AJAX_START -->|<-- LP_AJAX_END -->/g, '' ).trim();
					try {
						const data = JSON.parse( jsonString );
						$( '.order-subtotal' ).html( data.order_data.subtotal_html );
						$( '.order-total' ).html( data.order_data.total_html );
						$( '#item-container' ).html( data.item_html );
					} catch ( e ) {
						console.error( 'Error parsing JSON:', e );
					}
				},
				error( xhr, status, error ) {
					console.error( 'Request failed:', status, error );
				},
			} );
		} );

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
