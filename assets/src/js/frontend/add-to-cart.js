( function( $ ) {
	'use strict';
	function _ready() {
		$( 'form.purchase-course' ).on( 'submit', function() {
			const $button = $( 'button.purchase-button', this ),
				$viewCart = $( '.view-cart-button', this );

			$button.removeClass( 'added' ).addClass( 'loading' );

			$.ajax( {
				url: $( 'input[name="_wp_http_referer"]', this ).val() + '?lp-ajax=add-to-cart',
				data: $( this ).serialize(),
				error() {
					$button.removeClass( 'loading' );
				},
				dataType: 'html',
				success( response ) {
					response = LP.parseJSON( response );
					$button.addClass( 'added' ).removeClass( 'loading' );
					$viewCart.removeClass( 'hide-if-js' );

					if ( typeof response.redirect === 'string' ) {
						window.location.href = response.redirect;
					}
				},
			} );

			return false;
		} );
	}

	$( document ).ready( _ready );
}( jQuery ) );
