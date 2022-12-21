/**
 * Script handle admin notices.
 *
 * @since 4.1.7.3.2
 * @version 1.0.1
 */
import adminAPI from '../api';
let elAdminTabContent = null;
let elBtnDismiss;
let dataHtml = null;
const queryString = window.location.search;
const urlParams = new URLSearchParams( queryString );
const tab = urlParams.get( 'tab' );

const getAddons = ( set = '' ) => {
	fetch( adminAPI.apiAddons, {
		method: 'GET',
	} ).then( ( res ) =>
		res.json()
	).then( ( res ) => {
		// console.log(data);

		const { status, message, data } = res;
		if ( status === 'success' ) {
			dataHtml = data;
		} else {
			dataHtml = message;
		}
	} ).catch( ( err ) => {
		console.log( err );
	} );
};
getAddons();

/*** DOMContentLoaded ***/
document.addEventListener( 'DOMContentLoaded', () => {
	elAdminTabContent = document.querySelector( '.lp-admin-tab-content' );

	const interval = setInterval( () => {
		if ( dataHtml !== null ) {
			if ( dataHtml.length > 0 ) {
				elAdminTabContent.innerHTML = dataHtml;
			}

			clearInterval( interval );
		}
	}, 1 );
} );

/*** Events ***/
document.addEventListener( 'click', ( e ) => {
	const el = e.target;

	if ( el.classList.contains( 'btn-lp-notice-dismiss' ) ) {
		e.preventDefault();

		// eslint-disable-next-line no-alert
		if ( confirm( 'Are you sure you want to dismiss this notice?' ) ) {
			const parent = el.closest( '.lp-admin-notice' );
			callAdminNotices( `dismiss=${ el.getAttribute( 'data-dismiss' ) }` );
			parent.remove();
			if ( elLPAdminNotices.querySelectorAll( '.lp-admin-notice' ).length === 0 ) {
				elLPAdminNotices.style.display = 'none';
			}
		}
	}
} );
