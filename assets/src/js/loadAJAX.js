/**
 * Load all you need via AJAX
 *
 * @since 4.2.5.7
 */

import { lpAddQueryArgs, lpFetchAPI } from './utils';
import API from './api';

const lpAJAX = ( () => {
	return {
		autoLoadAPIs: () => {
			console.log( 'autoLoadAPIs' );
		},
		fetchAPI: ( url, params, callBack ) => {
			const option = { headers: {} };
			if ( 0 !== parseInt( lpData.user_id ) ) {
				option.headers[ 'X-WP-Nonce' ] = lpData.nonce;
			}

			if ( 'undefined' !== typeof params.args.method_request ) {
				option.method = params.args.method_request;
			} else {
				option.method = 'POST';
			}

			params.args = { ...params.args, ...lpData.urlParams };

			if ( 'POST' === option.method ) {
				option.body = JSON.stringify( params );
				option.headers[ 'Content-Type' ] = 'application/json';
			} else {
				params.args = JSON.stringify( params.args );
				params.callback = JSON.stringify( params.callback );
				url = lpAddQueryArgs( url, params );
			}

			lpFetchAPI( url, option, callBack );
		},
		getElements: () => {
			// Finds all elements with the class '.lp-load-ajax-element'
			const elements = document.querySelectorAll( '.lp-load-ajax-element' );
			if ( elements.length ) {
				elements.forEach( ( element ) => {
					const url = API.frontend.apiAJAX;
					const dataObj = JSON.parse( element.dataset.send );
					const dataSend = { ...dataObj };
					const elLoadingFirst = element.querySelector( '.loading-first' );

					const callBack = {
						success: ( response ) => {
							const { status, message, data } = response;
							const args = dataObj.args;
							const elTarget = element.querySelector( args.el_target || '' );
							if ( ! elTarget ) {
								console.log( 'elTarget load ajax content not found' );
								return;
							}

							if ( 'success' === status ) {
								elTarget.innerHTML = data.content;
							} else if ( 'error' === status ) {
								elTarget.innerHTML = message;
							}
						},
						error: ( error ) => {
							console.log( error );
						},
						completed: () => {
							console.log( 'completed' );
							if ( elLoadingFirst ) {
								elLoadingFirst.remove();
							}
						},
					};

					window.lpAJAXG.fetchAPI( url, dataSend, callBack );
				} );
			}
		},
	};
} );

if ( 'undefined' === typeof window.lpAJAXG ) {
	window.lpAJAXG = lpAJAX();
	window.lpAJAXG.getElements();
}
export default lpAJAX;
