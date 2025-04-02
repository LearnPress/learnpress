/**
 * Load all you need via AJAX
 *
 * @since 4.2.5.7
 * @version 1.0.5
 */

import { lpAddQueryArgs, lpFetchAPI, listenElementCreated, lpOnElementReady, lpGetCurrentURLNoParam } from './utils.js';
import API from './api.js';

// Handle general parameter in the Frontend and Backend
const apiData = API.admin || API.frontend;
const urlAPI = apiData?.apiAJAX || '';

let lpSettings = {};
if ( 'undefined' !== typeof lpDataAdmin ) {
	lpSettings = lpDataAdmin;
} else if ( 'undefined' !== typeof lpData ) {
	lpSettings = lpData;
}
// End Handle general parameter in the Frontend and Backend

const lpAJAX = ( () => {
	const classLPTarget = '.lp-target';
	const urlCurrent = lpGetCurrentURLNoParam();

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
		fetchAJAX: ( params, callBack, urlAjax = '' ) => {
			// Call via ajax.
			urlAjax = urlAjax || lpSettings.lpAjaxUrl;
			if ( params.args.id_url ) {
				urlAjax = lpAddQueryArgs( urlAjax, { id_url: params.args.id_url } );
			}

			const formData = new FormData();
			formData.append( 'nonce', lpSettings.nonce );
			formData.append( 'lp-load-ajax', 'load_content_via_ajax' );
			formData.append( 'data', JSON.stringify( params ) );
			const dataSend = {
				method: 'POST',
				headers: {},
				body: formData,
			};

			if ( 0 !== parseInt( lpSettings.user_id ) ) {
				dataSend.headers[ 'X-WP-Nonce' ] = lpSettings.nonce;
			}

			lpFetchAPI( urlAjax, dataSend, callBack );
		},
		getElements: () => {
			// Finds all elements with the class '.lp-load-ajax-element'
			const elements = document.querySelectorAll( '.lp-load-ajax-element:not(.loaded)' );
			//console.log( 'getElements', elements );
			if ( elements.length ) {
				elements.forEach( ( element ) => {
					//console.log( 'Element handing', element );
					element.classList.add( 'loaded' );
					let url = urlAPI;
					if ( lpSettings.urlParams.hasOwnProperty( 'lang' ) ) {
						url = lpAddQueryArgs( url, { lang: lpSettings.urlParams.lang } );
					}

					const elTarget = element.querySelector( `${ classLPTarget }` );
					if ( ! elTarget ) {
						return;
					}

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

					// Call via API
					//window.lpAJAXG.fetchAPI( url, dataSend, callBack );

					// Call via AJAX
					window.lpAJAXG.fetchAJAX( dataSend, callBack );
				} );
			}
		},
		clickNumberPage: ( e, target ) => {
			const btnNumber = target.closest( '.page-numbers:not(.disabled)' );
			if ( ! btnNumber ) {
				return;
			}

			const elLPTarget = btnNumber.closest( `${ classLPTarget }` );
			if ( ! elLPTarget ) {
				return;
			}

			const dataObj = JSON.parse( elLPTarget.dataset.send );
			const dataSend = { ...dataObj };
			if ( ! dataSend.args.hasOwnProperty( 'paged' ) ) {
				dataSend.args.paged = 1;
			}

			e.preventDefault();

			if ( btnNumber.classList.contains( 'prev' ) ) {
				dataSend.args.paged--;
			} else if ( btnNumber.classList.contains( 'next' ) ) {
				dataSend.args.paged++;
			} else {
				dataSend.args.paged = btnNumber.textContent;
			}

			elLPTarget.dataset.send = JSON.stringify( dataSend );

			// Set url params to reload page.
			// Todo: need check allow set url params.
			lpData.urlParams.paged = dataSend.args.paged;
			window.history.pushState( {}, '', lpAddQueryArgs( urlCurrent, lpData.urlParams ) );
			// End.

			// Show loading
			const elLoading = elLPTarget.closest( `div:not(${ classLPTarget })` ).querySelector( '.lp-loading-change' );
			if ( elLoading ) {
				elLoading.style.display = 'block';
			}
			// End

			// Scroll to archive element
			const elLPTargetY = elLPTarget.getBoundingClientRect().top + window.scrollY - 100;
			window.scrollTo( { top: elLPTargetY } );

			const callBack = {
				success: ( response ) => {
					//console.log( 'response', response );
					const { status, message, data } = response;
					elLPTarget.innerHTML = data.content || '';
				},
				error: ( error ) => {
					console.log( error );
				},
				completed: () => {
					//console.log( 'completed' );
					if ( elLoading ) {
						elLoading.style.display = 'none';
					}
				},
			};

			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		},
	};
} );

window.lpAJAXG = lpAJAX();
window.lpAJAXG.getElements();

// Events
document.addEventListener( 'click', function( e ) {
	const target = e.target;

	window.lpAJAXG.clickNumberPage( e, target );
} );

// Listen element created
listenElementCreated( ( node ) => {
	if ( node.classList.contains( 'lp-load-ajax-element' ) ) {
		//console.log( 'Element created', node );
		window.lpAJAXG.getElements();
	}
} );

// Listen element ready
lpOnElementReady( '.lp-load-ajax-element', ( element ) => {
	//console.log( 'Element ready', element );
	window.lpAJAXG.getElements();
} );

// Case 2: readystatechange, find all elements with the class '.lp-load-ajax-element' not have class 'loaded'
document.addEventListener( 'readystatechange', ( event ) => {
	//console.log( 'readystatechange' );
	window.lpAJAXG.getElements();
} );

// Case 3: DOMContentLoaded, find all elements with the class '.lp-load-ajax-element' not have class 'loaded'
document.addEventListener( 'DOMContentLoaded', () => {
	//console.log( 'DOMContentLoaded' );
	window.lpAJAXG.getElements();
} );

export default lpAJAX;
