/**
 * Script handle admin notices.
 *
 * @since 4.1.7.3.2
 * @version 1.0.1
 */
import adminAPI from '../api';
let elAdminTabContent = null;
let dataHtml = null;
const queryString = window.location.search;
const urlParams = new URLSearchParams( queryString );
const tab = urlParams.get( 'tab' );

const getAddons = ( set = '' ) => {
	const params = tab ? `?tab=${ tab }` : `?${ set }`;
	fetch( adminAPI.apiAddons + params, {
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

const isHandling = [];
const addonsAction = ( data ) => {
	const addonSlug = data.addon.slug;
	const action = data.action;

	if ( isHandling.indexOf( addonSlug ) !== -1 ) {
		return;
	}
	isHandling.push( addonSlug );

	fetch( adminAPI.apiAddonAction, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify( { ...data } ),
	} ).then( ( res ) =>
		res.json()
	).then( ( res ) => {
		const indexAddonHanding = isHandling.indexOf( addonSlug );
		if ( indexAddonHanding !== -1 ) {
			isHandling.splice( indexAddonHanding, 1 );
		}

		const elAddon = document.querySelector( `#${ addonSlug }` );
		const parent = elAddon.closest( '.lp-toggle-switch' );
		const dashicons = parent.querySelector( '.dashicons-update' );
		dashicons.style.display = 'none';
		if ( action === 'deactivate' ) {
			elAddon.setAttribute( 'data-action', 'activate' );
		} else if ( action === 'activate' ) {
			elAddon.setAttribute( 'data-action', 'deactivate' );
		}
		const label = parent.querySelector( `label[for=${ addonSlug }]` );
		label.style.display = 'inline-flex';

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

// Get addons when js loaded.
getAddons();

/*** DOMContentLoaded ***/
document.addEventListener( 'DOMContentLoaded', () => {
	elAdminTabContent = document.querySelector( '.lp-admin-tab-content' );

	const interval = setInterval( () => {
		if ( dataHtml !== null ) {
			if ( dataHtml.length > 0 ) {
				elAdminTabContent.innerHTML = dataHtml;
			}

			const elNavTabWrapper = document.querySelector( '.lp-nav-tab-wrapper' );
			const elNavTabWrapperClone = elNavTabWrapper.cloneNode( true );
			const elxx = document.querySelector( '.lp-admin-tab-content' );
			elxx.insertBefore( elNavTabWrapperClone, elxx.children[ 0 ] );
			elNavTabWrapperClone.style.display = 'flex';
			elNavTabWrapper.remove();

			clearInterval( interval );
		}
	}, 1 );
} );

/*** Events ***/
document.addEventListener( 'click', ( e ) => {
	const el = e.target;

	if ( el.classList.contains( 'lp-toggle-switch-label' ) ) {
		//e.preventDefault();

		const idLabel = el.getAttribute( 'for' );
		const elInput = document.querySelector( `#${ idLabel }` );
		const action = elInput.getAttribute( 'data-action' );
		const addon = JSON.parse( elInput.getAttribute( 'data-addon' ) );
		const parent = el.closest( '.lp-toggle-switch' );
		const label = parent.querySelector( `label[for=${ idLabel }]` );
		const dashicons = parent.querySelector( '.dashicons-update' );
		dashicons.style.display = 'inline-block';
		label.style.display = 'none';
		const data = { action, addon };
		addonsAction( data );
	}

	if ( el.classList.contains( 'btn-addon-action' ) ) {
		e.preventDefault();

		const addon = JSON.parse( el.getAttribute( 'data-addon' ) );
		const action = el.getAttribute( 'data-action' );
		const data = { action, addon };
		addonsAction( data );
	}
} );
