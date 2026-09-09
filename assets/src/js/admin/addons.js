/**
 * Script handle admin notices.
 *
 * @since 4.1.7.3.2
 * @version 1.0.1
 */
import API from '../api';
let elAddonsPage;
let dataHtml;
let dataAddons;
let elLPAddons;
const queryString = window.location.search;
const urlParams = new URLSearchParams( queryString );
const tab = urlParams.get( 'tab' );
let elNotifyActionWrapper;
const isHandling = [];

// API get list addons.
const getAddons = ( set = '' ) => {
	const params = tab ? `?tab=${ tab }` : `?${ set }`;
	fetch( API.admin.apiAddons + params, {
		method: 'GET',
		headers: {
			'X-WP-Nonce': lpDataAdmin.nonce,
		},
	} ).then( ( res ) =>
		res.json()
	).then( ( res ) => {
		// console.log(data);
		const { status, message, data } = res;
		if ( status === 'success' ) {
			dataHtml = data.html;
			dataAddons = data.addons;
		} else {
			dataHtml = message;
		}
	} ).catch( ( err ) => {
		console.log( err );
	} );
};
// API send action install, update, activate, deactivate.
const addonsAction = ( data, callBack ) => {
	const addonSlug = data.addon.slug;

	if ( isHandling.indexOf( addonSlug ) !== -1 ) {
		return;
	}
	isHandling.push( addonSlug );

	fetch( API.admin.apiAddonAction, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': lpDataAdmin.nonce,
		},
		body: JSON.stringify( { ...data } ),
	} ).then( ( res ) =>
		res.json()
	).then( ( res ) => {
		const indexAddonHanding = isHandling.indexOf( addonSlug );
		if ( indexAddonHanding !== -1 ) {
			isHandling.splice( indexAddonHanding, 1 );
		}

		const { status, message, data } = res;

		if ( callBack ) {
			callBack( status, message, data );
		}

		handleNotify( status, message );
	} ).catch( ( err ) => {
		handleNotify( 'error', `error js: ${ err }` );
		console.log( err );
	} );
};
// Show notify.
const handleNotify = ( status, message ) => {
	const elNotifyAction = elNotifyActionWrapper.querySelector( '.lp-notify-action' );
	const elNotifyActionNew = elNotifyAction.cloneNode( true );
	elNotifyActionNew.classList.remove( 'clone' );
	elNotifyActionWrapper.insertBefore( elNotifyActionNew, elNotifyActionWrapper[ 0 ] );
	const elSuccess = elNotifyActionNew.querySelector( `.${ elNotifyActionNew.classList.value }__success` );
	const elFailed = elNotifyActionNew.querySelector( `.${ elNotifyActionNew.classList.value }__error` );

	if ( status === 'success' ) {
		elSuccess.classList.add( 'show' );
		elSuccess.querySelector( '.message' ).innerHTML = message;
	} else {
		elFailed.classList.add( 'show' );
		elFailed.querySelector( '.message' ).innerHTML = message;
	}

	elNotifyActionWrapper.classList.add( 'show' );
	setTimeout( () => {
		elNotifyActionNew.remove();

		const elNotifyAction = elNotifyActionWrapper.querySelectorAll( '.lp-notify-action' );
		if ( elNotifyAction.length === 1 ) {
			elNotifyActionWrapper.classList.remove( 'show' );
		}
	}, status === 'success' ? 3000 : 4000 );
};
// Get addons when js loaded.
getAddons();
// Filter add-ons by status, category, and search query.
const filterAddons = () => {
	const elAddonItems = elAddonsPage.querySelectorAll( '.lp-addon-item' );
	const elActiveTab = elAddonsPage.querySelector( '.nav-tab.nav-tab-active' );
	const elActiveCategory = elAddonsPage.querySelector( '.lp-addons-category.active' );
	const elSearch = elAddonsPage.querySelector( '#lp-search-addons__input' );
	const tabName = elActiveTab ? elActiveTab.dataset.tab : 'all';
	const category = elActiveCategory ? elActiveCategory.dataset.category : 'all';
	const keyword = elSearch ? elSearch.value.trim().toLowerCase() : '';
	const categoryCounts = { all: 0 };
	let totalItems = 0;

	elAddonItems.forEach( ( elAddonItem ) => {
		const addonName = elAddonItem.querySelector( 'a' ).textContent.toLowerCase();
		const addonCategory = elAddonItem.dataset.category || '';
		const matchesTab = 'all' === tabName || elAddonItem.classList.contains( tabName );

		if ( matchesTab ) {
			categoryCounts.all++;
			categoryCounts[ addonCategory ] = ( categoryCounts[ addonCategory ] || 0 ) + 1;
		}

		const matchesCategory = 'all' === category || addonCategory === category;
		const matchesSearch = addonName.includes( keyword );
		const isVisible = matchesTab && matchesCategory && matchesSearch;

		elAddonItem.classList.toggle( 'hide', ! matchesTab );
		elAddonItem.classList.toggle( 'search-not-found', matchesTab && ! isVisible );

		if ( isVisible ) {
			totalItems++;
		}
	} );

	elAddonsPage.querySelectorAll( '.lp-addons-category' ).forEach( ( elCategory ) => {
		const count = categoryCounts[ elCategory.dataset.category ] || 0;
		const elCount = elCategory.querySelector( '.lp-addons-category__count' );

		if ( elCount ) {
			elCount.textContent = `(${ count })`;
		}

		elCategory.hidden = 'all' !== elCategory.dataset.category && 0 === count;
	} );

	setGridItems( totalItems );
};
// Set grid style items.
const setGridItems = ( totalItems ) => {
	if ( totalItems < 4 ) {
		elLPAddons.classList.add( 'max-3-items' );
	} else {
		elLPAddons.classList.remove( 'max-3-items' );
	}
};
// Check element loaded and data API returned.
const loadElData = setInterval( () => {
	if ( ! elAddonsPage || ! elNotifyActionWrapper ) {
		elAddonsPage = document.querySelector( '.lp-addons-page' );
		elNotifyActionWrapper = document.querySelector( '.lp-notify-action-wrapper' );
	} else if ( dataHtml && elAddonsPage && elNotifyActionWrapper ) {
		elAddonsPage.innerHTML = dataHtml;
		elLPAddons = elAddonsPage.querySelector( '#lp-addons' );
		const elAddonsControls = document.querySelector( '.lp-addons-controls' );
		if ( ! elAddonsControls ) {
			clearInterval( loadElData );
			return;
		}

		const elAddonsControlsClone = elAddonsControls.cloneNode( true );
		elAddonsPage.insertBefore( elAddonsControlsClone, elAddonsPage.children[ 0 ] );
		elAddonsControlsClone.hidden = false;
		elAddonsControls.remove();
		filterAddons();

		clearInterval( loadElData );
	}
}, 1 );

document.addEventListener( 'DOMContentLoaded', ( e ) => {
} );

/*** Events ***/
document.addEventListener( 'click', ( e ) => {
	let el = e.target;
	const tagName = el.tagName.toLowerCase();
	if ( tagName === 'span' ) {
		e.preventDefault();
		const elBtnAction = el.closest( '.btn-addon-action' );
		if ( elBtnAction ) {
			elBtnAction.click();
		}
	}

	// Events actions: activate, deactivate.
	/*if ( el.classList.contains( 'lp-toggle-switch-label' ) ) {
		//e.preventDefault();

		const elAddonItem = el.closest( '.lp-addon-item' );
		const idLabel = el.getAttribute( 'for' );
		const elInput = document.querySelector( `#${ idLabel }` );
		const action = elInput.getAttribute( 'data-action' );
		const addon = dataAddons[ elAddonItem.dataset.slug ];
		const addonSlug = addon.slug;
		const parent = el.closest( '.lp-toggle-switch' );
		const label = parent.querySelector( `label[for=${ idLabel }]` );
		const dashicons = parent.querySelector( '.dashicons-update' );
		dashicons.style.display = 'inline-block';
		label.style.display = 'none';
		const data = { action, addon };
		addonsAction( data, function( status, message, data ) {
			const elAddon = document.querySelector( `#${ addonSlug }` );
			if ( elAddon ) {
				const parent = elAddon.closest( '.lp-toggle-switch' );
				if ( parent ) {
					const dashicons = parent.querySelector( '.dashicons-update' );
					dashicons.style.display = 'none';
					if ( action === 'deactivate' ) {
						elAddon.setAttribute( 'data-action', 'activate' );
					} else if ( action === 'activate' ) {
						elAddon.setAttribute( 'data-action', 'deactivate' );
					}
					const label = parent.querySelector( `label[for=${ addonSlug }]` );
					label.style.display = 'inline-flex';
				}
			}

			if ( status === 'success' ) {
				if ( action === 'deactivate' ) {
					elAddonItem.classList.remove( 'activated' );
				}
				if ( action === 'activate' ) {
					elAddonItem.classList.add( 'activated' );
				}
			}
		} );
	}*/

	// Events actions: install, update, delete.
	if ( el.closest( '.btn-addon-action' ) ) {
		el = el.closest( '.btn-addon-action' );
		e.preventDefault();
		el.classList.add( 'handling' );
		let purchaseCode = '';
		const elAddonItem = el.closest( '.lp-addon-item' );
		const addon = dataAddons[ elAddonItem.dataset.slug ];
		const action = el.dataset.action;
		const elItemPurchase = elAddonItem.querySelector( '.lp-addon-item__purchase' );
		//const elToggleSwitchInput = elAddonItem.querySelector( '.lp-toggle-switch-input' );

		if ( action === 'purchase' ) {
			elItemPurchase.style.display = 'block';
			const elPurchaseInstall = elItemPurchase.querySelector( '.purchase-install' );
			elPurchaseInstall.style.display = 'flex';
			elPurchaseInstall.querySelector( '.enter-purchase-code' ).focus();
			el.classList.remove( 'handling' );
			return;
		} else if ( action === 'update-purchase-code' ) {
			const elPurchaseUpdate = elItemPurchase.querySelector( '.purchase-update' );
			elPurchaseUpdate.style.display = 'flex';
			elItemPurchase.style.display = 'block';
			elPurchaseUpdate.querySelector( '.enter-purchase-code' ).focus();
			el.classList.remove( 'handling' );
			return;
		} else if ( action === 'buy' ) {
			const link = el.dataset.link;
			window.open( link, '_blank' );
			el.classList.remove( 'handling' );
			return;
		} else if ( action === 'cancel' ) {
			elItemPurchase.style.display = 'none';
			elItemPurchase.querySelectorAll( '.purchase-install, .purchase-update' ).forEach( ( panel ) => {
				panel.style.display = 'none';
			} );
			el.classList.remove( 'handling' );
			return;
		} else if ( action === 'install' ) {
			if ( el.dataset.link ) {
				el.classList.remove( 'handling' );
				const link = el.dataset.link;
				window.open( link, '_blank' );
				return;
			}
		}

		// Send request to server.
		if ( elItemPurchase ) {
			purchaseCode = elItemPurchase.querySelector( 'input[name=purchase-code]' ).value;
		}

		const data = { purchase_code: purchaseCode, action, addon };
		addonsAction( data, function( status, message, data ) {
			if ( status === 'success' ) {
				if ( action === 'install' ) {
					elAddonItem.classList.add( 'installed', 'activated' );
					elAddonItem.classList.remove( 'not_installed' );
					elItemPurchase.style.display = 'none';
					/*elToggleSwitchInput.setAttribute( 'checked', 'checked' );
					elToggleSwitchInput.setAttribute( 'data-action', 'deactivate' );*/
					const elNavInstalled = document.querySelector( '.nav-tab[data-tab=installed] span' );
					elNavInstalled.textContent = parseInt( elNavInstalled.textContent ) + 1;
					const elNavNoInstalled = document.querySelector( '.nav-tab[data-tab=not_installed] span' );
					elNavNoInstalled.textContent = parseInt( elNavNoInstalled.textContent ) - 1;
					elItemPurchase.querySelector( '.purchase-install' ).style.display = 'none';
					elItemPurchase.querySelector( '.purchase-update' ).querySelector( '.enter-purchase-code' ).value = purchaseCode;
				} else if ( action === 'update' ) {
					const elAddonVersionCurrent = elAddonItem.querySelector( '.addon-version-current' );
					elAddonVersionCurrent.innerHTML = addon.version;
					elAddonItem.classList.remove( 'update' );
				} else if ( action === 'activate' ) {
					elAddonItem.classList.add( 'activated' );
				} else if ( action === 'deactivate' ) {
					elAddonItem.classList.remove( 'activated' );
				} else if ( action === 'update-purchase' ) {
					elItemPurchase.style.display = 'none';
				}

				filterAddons();
			}

			el.classList.remove( 'handling' );
		} );
	}

	if ( el.classList.contains( 'nav-tab' ) ) {
		e.preventDefault();
		const elTabs = elAddonsPage.querySelectorAll( '.nav-tab' );
		elTabs.forEach( function( elTab ) {
			elTab.classList.remove( 'nav-tab-active' );
			elTab.setAttribute( 'aria-pressed', 'false' );
		} );
		el.classList.add( 'nav-tab-active' );
		el.setAttribute( 'aria-pressed', 'true' );
		const tabName = el.dataset.tab;
		const elSearch = elAddonsPage.querySelector( '#lp-search-addons__input' );
		elSearch.value = '';
		elAddonsPage.querySelectorAll( '.lp-addons-category' ).forEach( ( elCategory ) => {
			const isActive = 'all' === elCategory.dataset.category;
			elCategory.classList.toggle( 'active', isActive );
			elCategory.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
		} );

		urlParams.set( 'tab', tabName );
		window.history.pushState( {}, '', `${ window.location.pathname }?${ urlParams.toString() }` );
		filterAddons();
	}

	if ( el.closest( '.lp-addons-category' ) ) {
		const elCategory = el.closest( '.lp-addons-category' );
		e.preventDefault();
		elAddonsPage.querySelectorAll( '.lp-addons-category' ).forEach( ( item ) => {
			const isActive = item === elCategory;
			item.classList.toggle( 'active', isActive );
			item.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
		} );
		filterAddons();
	}
} );

/*** Event search addons. ***/
document.addEventListener( 'input', ( e ) => {
	const el = e.target;

	if ( 'lp-search-addons__input' === el.id ) {
		filterAddons();
	}

	// Events change input purchase code.
	if ( el.classList.contains( 'enter-purchase-code' ) ) {
		e.preventDefault();
		const purchaseCode = el.value;
		const elItemPurchase = el.closest( '.lp-addon-item__purchase' );

		if ( elItemPurchase ) {
			const input = elItemPurchase.querySelector( 'input[name=purchase-code]' );
			input.value = purchaseCode;
		}
	}
} );
