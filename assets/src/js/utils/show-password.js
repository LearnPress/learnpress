const $ = jQuery;

$( function() {
	$( '.rwmb-password-wrapper .rwmb-input input[type="password"]' ).wrap( '<span class="lp-password-input"></span>' );
	$( '.rwmb-password-wrapper .rwmb-input' ).filter( ':password' ).parent( 'span' ).addClass( 'lp-password-input' );
	$( '.lp-password-input' ).append( '<span class="lp-show-password-input"></span>' );

	$( '.lp-show-password-input' ).on( 'click', function() {
		$( this ).toggleClass( 'display-password' );
		if ( $( this ).hasClass( 'display-password' ) ) {
			$( this ).siblings( [ 'input[type="password"]' ] ).prop( 'type', 'text' );
		} else {
			$( this ).siblings( 'input[type="text"]' ).prop( 'type', 'password' );
		}
	} );
} );

