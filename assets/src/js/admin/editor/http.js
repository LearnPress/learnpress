export default function HTTP( options ) {
	const $ = window.jQuery || jQuery;
	const $VueHTTP = Vue.http;

	options = $.extend( {
		ns: 'LPRequest',
		store: false,
	}, options || {} );

	let $publishingAction = null;

	LP.Request = function( payload ) {
		$publishingAction = $( '#publishing-action' );

		payload.id = options.store.getters.id;
		payload.nonce = options.store.getters.nonce;
		payload[ 'lp-ajax' ] = options.store.getters.action;
		payload.code = options.store.getters.code;

		$publishingAction.find( '#publish' ).addClass( 'disabled' );
		$publishingAction.find( '.spinner' ).addClass( 'is-active' );
		$publishingAction.addClass( 'code-' + payload.code );

		return $VueHTTP.post( options.store.getters.urlAjax,
			payload,
			{
				emulateJSON: true,
				params: {
					namespace: options.ns,
					code: payload.code,
				},
			} );
	};

	$VueHTTP.interceptors.push( function( request, next ) {
		if ( request.params.namespace !== options.ns ) {
			next();
			return;
		}

		options.store.dispatch( 'newRequest' );

		next( function( response ) {
			if ( ! jQuery.isPlainObject( response.body ) ) {
				response.body = LP.parseJSON( response.body );
			}

			const body = response.body;
			const result = body.success || false;

			if ( result ) {
				options.store.dispatch( 'requestCompleted', 'successful' );
			} else {
				options.store.dispatch( 'requestCompleted', 'failed' );
			}

			$publishingAction.removeClass( 'code-' + request.params.code );

			if ( ! $publishingAction.attr( 'class' ) ) {
				$publishingAction.find( '#publish' ).removeClass( 'disabled' );
				$publishingAction.find( '.spinner' ).removeClass( 'is-active' );
			}
		} );
	} );
}
