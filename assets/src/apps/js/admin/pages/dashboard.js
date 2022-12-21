/**
 * Scripts run on the Dashboard of WordPress
 *
 * @since 3.0.0
 * @version 1.0.1
 */

import adminAPI from '../api';
let dataOrderStatic = null;
let elOrderStatic = null;

const callAPIOrderStatic = () => {
	if ( ! lpGlobalSettings.is_admin ) {
		return;
	}

	fetch( adminAPI.apiAdminOrderStatic, {
		method: 'GET',
	} ).then( ( res ) => {
		return res.json();
	} ).then( ( res ) => {
		dataOrderStatic = res;
	} ).catch( ( err ) => {
		console.log( err );
	} );
};
callAPIOrderStatic();

const interval = setInterval( () => {
	elOrderStatic = document.querySelector( 'ul.lp-order-statuses' );

	if ( elOrderStatic && dataOrderStatic !== null ) {
		if ( dataOrderStatic.status === 'success' && dataOrderStatic.data ) {
			elOrderStatic.innerHTML = dataOrderStatic.data;
		} else {
			elOrderStatic.innerHTML = `<div class="error">${ dataOrderStatic.message }</div>`;
		}

		clearInterval( interval );
	}
}, 1 );
