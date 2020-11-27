( function( $ ) {
	const dismissNotice = function dismissNotice( notice, options ) {
		const hooks = wp.hooks;

		options = $.extend( { el: null }, options || {} );

		hooks && hooks.doAction( 'before-dismiss-notice', 'LP', notice, options );
		$.ajax( {
			url: '',
			data: $.extend( { 'lp-dismiss-notice': notice }, options.data || {} ),
			dataType: 'text',
			success: ( response ) => {
				response = LP.parseJSON( response );

				if ( response.dismissed === notice ) {
					$( options.el ).fadeOut();
				}

				hooks && hooks.doAction( 'dismissed-notice', 'LP', notice, options );
			},
		} );
	};

	$( document ).on( 'click', '.lp-notice [data-dismiss-notice]', function() {
		const data = $( this ).data();
		const notice = data.dismissNotice;

		delete data.dismissNotice;

		dismissNotice( notice, { data, el: $( this ).closest( '.lp-notice' ) } );
	} );
}( jQuery ) );
