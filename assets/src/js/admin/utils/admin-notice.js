import extend from '../../utils/extend';
const $ = window.jQuery;

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

( function( $ ) {
	if ( typeof $ === 'undefined' ) {
		return;
	}

	$( document ).on( 'click', '.lp-notice [data-dismiss-notice]', function() {
		var data = $( this ).data();
		var notice = data.dismissNotice;

		delete data.dismissNotice;

		dismissNotice( notice, { data: data, el: $( this ).closest( '.lp-notice' ) } );
	} );
}( window.jQuery ) );

extend( 'Utils', { dismissNotice } );

export default dismissNotice;
