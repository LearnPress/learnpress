/**
 * Script handle admin notices.
 *
 * @since 4.1.7.3.2
 * @version 1.0.2
 */
import { lpOnElementReady } from '../utils.js';
import API from '../api.js';
let elLPAdminNotices = null;
let dataHtml = null;
const queryString = window.location.search;
const urlParams = new URLSearchParams( queryString );
const tab = urlParams.get( 'tab' );

const notifyAddonsNewVersion = () => {
	try {
		const elAdminMenu = document.querySelector( '#adminmenu' );
		if ( ! elAdminMenu ) {
			return;
		}

		const elTabLP = elAdminMenu.querySelector( '#toplevel_page_learn_press' );
		if ( ! elTabLP ) {
			return;
		}
		const elTabLPName = elTabLP.querySelector( '.wp-menu-name' );
		if ( ! elTabLPName ) {
			return;
		}
		const elAddonsNewVerTotal = document.querySelector( 'input[name=lp-addons-new-version-totals]' );
		if ( ! elAddonsNewVerTotal ) {
			return;
		}
		const htmlNotifyLP = `<span class="tab-lp-admin-notice"></span>`;
		elTabLPName.insertAdjacentHTML( 'beforeend', htmlNotifyLP );
		const elTabLPAddons = elTabLP.querySelector( 'a[href="admin.php?page=learn-press-addons"]' );
		if ( ! elTabLPAddons ) {
			return;
		}

		const total = elAddonsNewVerTotal.value;
		const html = `<span style="margin-left: 5px" class="update-plugins">${ total }</span>`;
		elTabLPAddons.setAttribute( 'href', 'admin.php?page=learn-press-addons&tab=update' );
		elTabLPAddons.insertAdjacentHTML( 'beforeend', html );
	} catch ( e ) {
		console.log( e );
	}
};

const callAdminNotices = ( set = '' ) => {
	if ( ! lpGlobalSettings.is_admin ) {
		return;
	}

	let params = tab ? `?tab=${ tab }` : '';
	params += set ? ( tab ? '&' : '?' ) + `${ set }` : '';

	fetch( API.admin.apiAdminNotice + params, {
		method: 'POST',
		headers: {
			'X-WP-Nonce': lpGlobalSettings.nonce,
		},
	} ).then( ( res ) =>
		res.json()
	).then( ( res ) => {
		// console.log(data);

		const { status, message, data } = res;
		if ( status === 'success' ) {
			if ( 'Dismissed!' !== message ) {
				dataHtml = data.content;

				if ( dataHtml.length === 0 ) {
					elLPAdminNotices.style.display = 'none';
				} else {
					elLPAdminNotices.innerHTML = dataHtml;
					elLPAdminNotices.style.display = 'block';
					// Handle notify addons new version.
					notifyAddonsNewVersion();
				}
			}
		} else {
			dataHtml = message;
		}
	} ).catch( ( err ) => {
		console.log( err );
	} );
};

// Listen element ready
lpOnElementReady( '.lp-admin-notices', () => {
	elLPAdminNotices = document.querySelector( '.lp-admin-notices' );
	callAdminNotices();
} );

/*** Events ***/
document.addEventListener( 'click', ( e ) => {
	const el = e.target;

	if ( el.classList.contains( 'btn-lp-notice-dismiss' ) ) {
		e.preventDefault();

		// eslint-disable-next-line no-alert
		if ( confirm( 'Are you sure you want to dismiss this notice?' ) ) {
			const parent = el.closest( '.lp-notice' );
			callAdminNotices( `dismiss=${ el.getAttribute( 'data-dismiss' ) }` );
			parent.remove();
			if ( elLPAdminNotices.querySelectorAll( '.lp-notice' ).length === 0 ) {
				elLPAdminNotices.style.display = 'none';
			}
		}
	}
} );
