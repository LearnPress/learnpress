/**
 * Load all you need via AJAX
 *
 * @since 4.2.5.7
 */

import { lpFetchAPI } from './utils';

const lpAJAX = ( () => {
	return {
		autoLoadAPIs: () => {
			console.log( 'autoLoadAPIs' );
		},
		fetchAPI: ( url, params, callBack ) => {
			if ( 0 !== parseInt( lpData.user_id ) ) {
				params = { ...params, ...{ headers: { 'X-WP-Nonce': lpData.nonce } } };
			}

			lpFetchAPI( url, params, callBack );
		},
		getElements: () => {

		},
	};
} );

if ( 'undefined' === typeof window.lpAJAXG ) {
	window.lpAJAXG = lpAJAX;
}
const m = window.lpAJAXG();
const n = lpAJAX();
export default lpAJAX;

m.fetchAPI('12312', {}, {});
