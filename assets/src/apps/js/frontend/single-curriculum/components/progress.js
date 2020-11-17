const $ = jQuery;

export const progressBar = () => {
	$( '.learn-press-progress' ).each( function() {
		const $progress = $( this );
		const $active = $progress.find( '.learn-press-progress__active' );
		const value = $active.data( 'value' );

		if ( value === undefined ) {
			return;
		}

		$active.css( 'left', -( 100 - parseInt( value ) ) + '%' );
	} );
};
