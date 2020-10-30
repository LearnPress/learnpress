( function( $ ) {
	'use strict';
	function _ready() {
		$( 'form.purchase-course' ).submit( function() {
			alert();
			const $button = $( 'button.purchase-button', this ),
				$view_cart = $( '.view-cart-button', this );
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
					$view_cart.removeClass( 'hide-if-js' );
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
