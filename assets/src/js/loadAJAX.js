/**
 * Load all you need via AJAX
 *
 * @since 4.2.5.7
 * @version 1.0.2
 */

import { lpAddQueryArgs, lpFetchAPI } from './utils';
import API from './api';

// Handle general parameter in the Frontend and Backend
let apiData = API.admin;
if ( 'undefined' === typeof apiData ) {
	apiData = API.frontend;
}
const urlAPI = apiData.apiAJAX;

let lpSettings = {};
if ( 'undefined' !== typeof lpDataAdmin ) {
	lpSettings = lpDataAdmin;
} else if ( 'undefined' !== typeof lpData ) {
	lpSettings = lpData;
}
// End Handle general parameter in the Frontend and Backend

const lpAJAX = ( () => {
	return {
		autoLoadAPIs: () => {
			console.log( 'autoLoadAPIs' );
		},
		fetchAPI: ( url, params, callBack ) => {
			const option = { headers: {} };
			if ( 0 !== parseInt( lpSettings.user_id ) ) {
				option.headers[ 'X-WP-Nonce' ] = lpSettings.nonce;
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
			const elements = document.querySelectorAll( '.lp-load-ajax-element:not(.loaded)' );
			if ( elements.length ) {
				elements.forEach( ( element ) => {
					//console.log( 'Element handing', element );
					element.classList.add( 'loaded' );
					let url = urlAPI;
					if ( lpSettings.urlParams.hasOwnProperty( 'lang' ) ) {
						url = lpAddQueryArgs( url, { lang: lpSettings.urlParams.lang } );
					}
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

// Case 1: file JS loaded, find all elements with the class '.lp-load-ajax-element' not have class 'loaded'
if ( 'undefined' === typeof window.lpAJAXG ) {
	window.lpAJAXG = lpAJAX();
	window.lpAJAXG.getElements();
}

// Case 2: readystatechange, find all elements with the class '.lp-load-ajax-element' not have class 'loaded'
document.addEventListener( 'readystatechange', ( event ) => {
	//console.log( 'readystatechange' );
	window.lpAJAXG.getElements();
} );

// Case 3: DOMContentLoaded, find all elements with the class '.lp-load-ajax-element' not have class 'loaded'
document.addEventListener( 'DOMContentLoaded', () => {
	window.lpAJAXG.getElements();
} );

export default lpAJAX;
