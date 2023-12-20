/**
 * Load all you need via AJAX
 *
 * @since 4.2.5.7
 * @version 1.0.1
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

			//params.args = { ...params.args, ...lpData.urlParams };

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
			//console.log( 'getElements' );
			// Finds all elements with the class '.lp-load-ajax-element'
			const elements = document.querySelectorAll( '.lp-load-ajax-element' );
			if ( elements.length ) {
				elements.forEach( ( element ) => {
					const url = API.frontend.apiAJAX;
					const elTarget = element.querySelector( '.lp-target' );
					const dataObj = JSON.parse( elTarget.dataset.send );
					const dataSend = { ...dataObj };
					const elLoadingFirst = element.querySelector( '.loading-first' );

					const callBack = {
						success: ( response ) => {
							const { status, message, data } = response;

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
							//console.log( 'completed' );
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

/*document.addEventListener( 'readystatechange', ( event ) => {
	console.log( 'readystatechange' );
	const elements = document.querySelectorAll( '.lp-load-ajax-element' );
	elements.forEach( ( element ) => {
		if ( ! element.classList.contains( 'loading-first' ) ) {
			console.log( 'okokkk' );
			element.classList.add( 'loading-first' );
		} else {
			console.log( 'nooooo' );
		}
	} );
} );

document.addEventListener( 'DOMContentLoaded', () => {
	console.log( 'DOMContentLoaded' );
} );*/

export default lpAJAX;
