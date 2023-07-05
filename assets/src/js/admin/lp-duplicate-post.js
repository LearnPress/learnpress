( function( $ ) {
	const duplicatePost = function duplicatePost( e ) {
		e.preventDefault();

		const _self = $( this ),
			_id = _self.data( 'post-id' );

		$.ajax( {
			url: '',
			data: {
				'lp-ajax': 'duplicator',
				id: _id,
				nonce: lpGlobalSettings.nonce,
			},
			success( response ) {
				response = LP.parseJSON( response );

				if ( response.success ) {
					window.location.href = response.data;
				} else {
					alert( response.data );
				}
			},
		} );
	};

	$( function() {
		$( document ).on( 'click', '.lp-duplicate-row-action .lp-duplicate-post', duplicatePost );
	} );
}( jQuery ) );
