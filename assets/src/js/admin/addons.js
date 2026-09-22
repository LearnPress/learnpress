/**
 * Admin Add-ons page JS handler as a class.
 *
 * Add-ons list is rendered server-side via AdminAddonsPage::render_addons()
 * through TemplateAJAX::load_content_via_ajax(). This class only handles
 * UI interactions and addon actions via window.lpAJAXG.
 *
 * @since 4.4.7
 * @version 1.0.0
 */
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import * as lpUtils from 'lpAssetsJsPath/utils.js';

class AdminAddons {
	constructor() {
		this.elAddonsPage = null;
		this.isHandling = [];
		this.urlParams = new URLSearchParams( window.location.search );
	}

	static selectors = {
		elAddonsPage: '.lp-addons-page',
		elLPAddons: '#lp-addons',
		elAddonItem: '.lp-addon-item',
		elBtnAction: '.btn-addon-action',
		elNavTab: '.nav-tab',
		elNavTabActive: '.nav-tab.nav-tab-active',
		elCategory: '.lp-addons-category',
		elCategoryActive: '.lp-addons-category.active',
		elSearchInput: '#lp-search-addons__input',
		elItemPurchase: '.lp-addon-item__purchase',
		elPurchaseInstall: '.purchase-install',
		elPurchaseUpdate: '.purchase-update',
		elPurchaseCode: '.enter-purchase-code',
		elLicense: '.lp-addon-license',
		elAddonVersionCurrent: '.addon-version-current',
	};

	init() {
		// Content is injected via AJAX into .lp-target, wait for #lp-addons.
		lpUtils.lpOnElementReady( AdminAddons.selectors.elLPAddons, () => {
			this.elAddonsPage = document.querySelector(
				AdminAddons.selectors.elAddonsPage
			);
			if ( ! this.elAddonsPage ) {
				return;
			}

			this.filterAddons();
			this.events();
		} );
	}

	/**
	 * Register events.
	 *
	 * @return void
	 */
	events() {
		lpUtils.eventHandlers( 'click', [
			{
				selector: AdminAddons.selectors.elBtnAction,
				class: this,
				callBack: this.handleBtnAction.name,
			},
			{
				selector: AdminAddons.selectors.elNavTab,
				class: this,
				callBack: this.handleTabClick.name,
			},
			{
				selector: AdminAddons.selectors.elCategory,
				class: this,
				callBack: this.handleCategoryClick.name,
			},
			{
				selector: AdminAddons.selectors.elPurchaseCode,
				class: this,
				callBack: this.handlePurchaseCodeClick.name,
			},
		] );
		lpUtils.eventHandlers( 'input', [
			{
				selector: AdminAddons.selectors.elSearchInput,
				class: this,
				callBack: this.handleSearchInput.name,
			},
			{
				selector: AdminAddons.selectors.elPurchaseCode,
				class: this,
				callBack: this.handlePurchaseCodeInput.name,
			},
		] );
	}

	/**
	 * Get addon data embedded on the item element.
	 *
	 * @param {Element} elAddonItem Addon item element.
	 * @return {Object|null}
	 */
	getAddonData( elAddonItem ) {
		try {
			return JSON.parse( elAddonItem.dataset.addon );
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Send addon action to server via lpAJAXG.
	 *
	 * @param {Object}   data     { action_type, addon, purchase_code }
	 * @param {Function} callBack Callback ( status, message, data ).
	 * @return void
	 */
	addonsAction( data, callBack ) {
		const addonSlug = data.addon.slug;

		if ( this.isHandling.indexOf( addonSlug ) !== -1 ) {
			return;
		}
		this.isHandling.push( addonSlug );

		const releaseHandling = () => {
			const index = this.isHandling.indexOf( addonSlug );
			if ( -1 !== index ) {
				this.isHandling.splice( index, 1 );
			}
		};

		const params = {
			action: 'addon_action',
			action_type: data.action,
			addon: data.addon,
			purchase_code: data.purchase_code || '',
			id_url: 'addon-action',
		};

		window.lpAJAXG.fetchAJAX( params, {
			success: ( response ) => {
				releaseHandling();

				const { status, message, data: resData } = response;

				if ( callBack ) {
					callBack( status, message, resData );
				}

				this.handleNotify( status, message );
			},
			error: ( error ) => {
				releaseHandling();
				if ( callBack ) {
					callBack( 'error', error.message );
				}
				this.handleNotify( 'error', `error js: ${ error }` );
				console.log( error );
			},
			completed: () => {},
		} );
	}

	/**
	 * Show notify.
	 *
	 * @param {string} status  Status response.
	 * @param {string} message Message response.
	 * @return void
	 */
	handleNotify( status, message ) {
		lpToastify.show(
			message,
			'success' === status ? 'success' : 'error'
		);
	}

	/**
	 * Update license panel after install/update-purchase.
	 *
	 * @param {Element} elAddonItem Addon item element.
	 * @param {Object}  licenseData License data from server.
	 * @return void
	 */
	updateLicensePanel( elAddonItem, licenseData ) {
		const elLicense = elAddonItem.querySelector(
			AdminAddons.selectors.elLicense
		);
		if ( ! elLicense || ! licenseData ) {
			return;
		}

		const status = licenseData.license_status || 'active';
		elLicense.hidden = false;
		elLicense.classList.remove(
			'lp-addon-license--active',
			'lp-addon-license--not-activated',
			'lp-addon-license--expired',
			'lp-addon-license--deactivated'
		);
		elLicense.classList.add( `lp-addon-license--${ status }` );

		const elStatus = elLicense.querySelector( '.lp-addon-license__status' );
		const elExpiry = elLicense.querySelector( '.lp-addon-license__expiry' );

		if ( elStatus ) {
			elStatus.textContent = 'expired' === status ? 'Expired' : 'Active';
		}

		elLicense.dataset.purchaseCodeMasked =
			licenseData.purchase_code_masked || '';

		if ( elExpiry ) {
			elExpiry.textContent = licenseData.date_expire_formatted
				? `(Updates until ${ licenseData.date_expire_formatted })`
				: '';
			elExpiry.hidden = ! licenseData.date_expire_formatted;
		}
	}

	/**
	 * Filter add-ons by status, category, and search query.
	 *
	 * @return void
	 */
	filterAddons() {
		const selectors = AdminAddons.selectors;
		const elAddonItems = this.elAddonsPage.querySelectorAll(
			selectors.elAddonItem
		);

		// Count items for each tab
		this.updateTabCounts( elAddonItems );
		const elActiveTab = this.elAddonsPage.querySelector(
			selectors.elNavTabActive
		);
		const elActiveCategory = this.elAddonsPage.querySelector(
			selectors.elCategoryActive
		);
		const elSearch = this.elAddonsPage.querySelector(
			selectors.elSearchInput
		);
		const tabName = elActiveTab ? elActiveTab.dataset.tab : 'all';
		const category = elActiveCategory
			? elActiveCategory.dataset.category
			: 'all';
		const keyword = elSearch ? elSearch.value.trim().toLowerCase() : '';
		const searchTerms = keyword.split( /\s+/ ).filter( Boolean );

		// Count items for category by tab
		this.updateCategoryCounts( elAddonItems, tabName );

		elAddonItems.forEach( ( elAddonItem ) => {
			const addonInfo = JSON.parse( elAddonItem.dataset.addon );

			const addonName = elAddonItem
				.querySelector( 'a' )
				.textContent.toLowerCase();
			const textCompare = addonName + addonInfo.description;

			const addonCategories = ( elAddonItem.dataset.category || '' )
				.split( /\s+/ )
				.filter( Boolean );
			const matchesTab =
				'all' === tabName || elAddonItem.classList.contains( tabName );
			const matchesCategory =
				'all' === category || addonCategories.includes( category );
			const matchesSearch = searchTerms.every( ( term ) => textCompare.includes( term ) );
			const isVisible = matchesTab && matchesCategory && matchesSearch;

			elAddonItem.classList.toggle( 'hide', ! matchesTab );
			elAddonItem.classList.toggle(
				'search-not-found',
				! isVisible
			);
		} );
	}

	updateCategoryCounts( elAddonItems, tabName ) {
		const categoryCounts = { all: 0 };

		elAddonItems.forEach( ( elAddonItem ) => {
			const matchesTab =
				'all' === tabName || elAddonItem.classList.contains( tabName );

			if ( ! matchesTab ) {
				return;
			}

			categoryCounts.all++;
			( elAddonItem.dataset.category || '' )
				.split( /\s+/ )
				.filter( Boolean )
				.forEach( ( addonCategory ) => {
					categoryCounts[ addonCategory ] =
						( categoryCounts[ addonCategory ] || 0 ) + 1;
				} );
		} );

		this.elAddonsPage
			.querySelectorAll( AdminAddons.selectors.elCategory )
			.forEach( ( elCategory ) => {
				const count =
					categoryCounts[ elCategory.dataset.category ] || 0;
				const elCount = elCategory.querySelector(
					'.lp-addons-category__count'
				);

				if ( elCount ) {
					elCount.textContent = `(${ count })`;
				}

				elCategory.hidden =
					'all' !== elCategory.dataset.category && 0 === count;
			} );
	}

	updateTabCounts( elAddonItems ) {
		this.elAddonsPage
			.querySelectorAll( AdminAddons.selectors.elNavTab )
			.forEach( ( elNavTab ) => {
				const tabName = elNavTab.dataset.tab;
				const count = Array.from( elAddonItems ).filter(
					( elAddonItem ) =>
						'all' === tabName ||
						elAddonItem.classList.contains( tabName )
				).length;
				const elCount = elNavTab.querySelector( 'span' );

				if ( elCount ) {
					elCount.textContent = count;
				}
			} );
	}

	/**
	 * Handle click events.
	 *
	 * @param {Object} args Click event arguments.
	 * @return void
	 */
	handlePurchaseCodeClick( args ) {
		const { target: el } = args;

		if ( ! el.value.startsWith( '***' ) ) {
			return;
		}

		el.value = '';
		const elItemPurchase = el.closest(
			AdminAddons.selectors.elItemPurchase
		);
		if ( elItemPurchase ) {
			elItemPurchase.querySelector( 'input[name=purchase-code]' ).value = '';
		}
	}

	/**
	 * Handle addon action button click.
	 *
	 * @param {Object} args Click event arguments.
	 * @return void
	 */
	handleBtnAction( args ) {
		const { e, target } = args;
		const selectors = AdminAddons.selectors;
		const el = target.closest( selectors.elBtnAction );

		// Ignore button type link
		if ( 'A' === el.tagName ) {
			return;
		}

		e.preventDefault();
		el.classList.add( 'handling' );

		let purchaseCode = '';
		const elAddonItem = el.closest( selectors.elAddonItem );
		const addon = this.getAddonData( elAddonItem );
		const action = el.dataset.action;
		const elItemPurchase = elAddonItem.querySelector(
			selectors.elItemPurchase
		);

		if ( ! addon ) {
			el.classList.remove( 'handling' );
			return;
		}

		if ( action === 'purchase' ) {
			elItemPurchase.style.display = 'block';
			const elPurchaseInstall = elItemPurchase.querySelector(
				selectors.elPurchaseInstall
			);
			const elPurchaseCode = elPurchaseInstall.querySelector(
				selectors.elPurchaseCode
			);
			elPurchaseCode.classList.remove( 'is-error' );
			elPurchaseCode.removeAttribute( 'aria-invalid' );
			elPurchaseInstall.style.display = 'flex';
			elPurchaseCode.focus();
			el.classList.remove( 'handling' );
			return;
		} else if ( action === 'update-purchase-code' ) {
			const elPurchaseUpdate = elItemPurchase.querySelector(
				selectors.elPurchaseUpdate
			);
			const elPurchaseCode = elPurchaseUpdate.querySelector(
				selectors.elPurchaseCode
			);
			const elLicense = elAddonItem.querySelector( selectors.elLicense );
			elPurchaseCode.value = elLicense
				? elLicense.dataset.purchaseCodeMasked || ''
				: '';
			elPurchaseCode.classList.remove( 'is-error' );
			elPurchaseCode.removeAttribute( 'aria-invalid' );
			elItemPurchase.querySelector( 'input[name=purchase-code]' ).value =
				'';
			elPurchaseUpdate.style.display = 'flex';
			elItemPurchase.style.display = 'block';
			el.classList.remove( 'handling' );
			return;
		} else if ( action === 'cancel' ) {
			elItemPurchase.style.display = 'none';
			elItemPurchase
				.querySelectorAll(
					`${ selectors.elPurchaseInstall }, ${ selectors.elPurchaseUpdate }`
				)
				.forEach( ( panel ) => {
					panel.style.display = 'none';
				} );
			el.classList.remove( 'handling' );
			return;
		}

		// Send request to server.
		if ( elItemPurchase ) {
			purchaseCode = elItemPurchase.querySelector(
				'input[name=purchase-code]'
			).value;
		}

		const data = { purchase_code: purchaseCode, action, addon };
		this.addonsAction( data, ( status, message, resData ) => {
			this.onActionDone( status, resData, {
				action,
				addon,
				el,
				elAddonItem,
				elItemPurchase,
			} );
		} );
	}

	/**
	 * Handle response after addon action.
	 *
	 * @param {string} status  Response status.
	 * @param {Object} resData Response data.
	 * @param {Object} ctx     Context elements.
	 * @return void
	 */
	onActionDone( status, resData, ctx ) {
		const { action, addon, el, elAddonItem, elItemPurchase } = ctx;
		const selectors = AdminAddons.selectors;

		if ( status === 'success' ) {
			if ( action === 'install' ) {
				elAddonItem.classList.add( 'installed', 'activated' );
				elAddonItem.classList.remove( 'not_installed' );
				if ( resData && resData.purchase_code_masked ) {
					elAddonItem.classList.add( 'license' );
					this.updateLicensePanel( elAddonItem, resData );
				}
				elItemPurchase.style.display = 'none';

				elItemPurchase.querySelector(
					selectors.elPurchaseInstall
				).style.display = 'none';
				elItemPurchase
					.querySelector( selectors.elPurchaseUpdate )
					.querySelector( selectors.elPurchaseCode ).value =
					resData && resData.purchase_code_masked
						? resData.purchase_code_masked
						: '';
			} else if ( action === 'update' ) {
				const elAddonVersionCurrent = elAddonItem.querySelector(
					selectors.elAddonVersionCurrent
				);
				elAddonVersionCurrent.innerHTML = addon.version;
				elAddonItem.classList.remove( 'update' );
			} else if ( action === 'activate' ) {
				elAddonItem.classList.add( 'activated' );
			} else if ( action === 'deactivate' ) {
				elAddonItem.classList.remove( 'activated' );
			} else if ( action === 'update-purchase' ) {
				this.updateLicensePanel( elAddonItem, resData );
				elItemPurchase.style.display = 'none';
				elItemPurchase.querySelector(
					selectors.elPurchaseUpdate
				).style.display = 'none';
				elItemPurchase
					.querySelectorAll(
						`${ selectors.elPurchaseCode }, input[name=purchase-code]`
					)
					.forEach( ( input ) => {
						input.value = '';
					} );
			}

			this.filterAddons();
		} else if (
			'install' === action ||
			'update-purchase' === action
		) {
			const elPurchasePanel = el.closest(
				`${ selectors.elPurchaseInstall }, ${ selectors.elPurchaseUpdate }`
			);
			const elPurchaseCode = elPurchasePanel
				? elPurchasePanel.querySelector( selectors.elPurchaseCode )
				: null;
			if ( elPurchaseCode ) {
				elPurchaseCode.classList.add( 'is-error' );
				elPurchaseCode.setAttribute( 'aria-invalid', 'true' );
			}
		}

		el.classList.remove( 'handling' );
	}

	/**
	 * Handle tab click.
	 *
	 * @param {Object} args Click event arguments.
	 * @return void
	 */
	handleTabClick( args ) {
		const { e, target } = args;
		const selectors = AdminAddons.selectors;
		const el = target.closest( selectors.elNavTab );
		e.preventDefault();

		const elTabs = this.elAddonsPage.querySelectorAll( selectors.elNavTab );
		elTabs.forEach( ( elTab ) => {
			elTab.classList.remove( 'nav-tab-active' );
			elTab.setAttribute( 'aria-pressed', 'false' );
		} );
		el.classList.add( 'nav-tab-active' );
		el.setAttribute( 'aria-pressed', 'true' );

		const tabName = el.dataset.tab;
		const elSearch = this.elAddonsPage.querySelector(
			selectors.elSearchInput
		);
		elSearch.value = '';
		this.elAddonsPage
			.querySelectorAll( selectors.elCategory )
			.forEach( ( elCategory ) => {
				const isActive = 'all' === elCategory.dataset.category;
				elCategory.classList.toggle( 'active', isActive );
				elCategory.setAttribute(
					'aria-pressed',
					isActive ? 'true' : 'false'
				);
			} );

		this.urlParams.set( 'tab', tabName );
		window.history.pushState(
			{},
			'',
			`${ window.location.pathname }?${ this.urlParams.toString() }`
		);
		this.filterAddons();
	}

	/**
	 * Handle category click.
	 *
	 * @param {Object} args Click event arguments.
	 * @return void
	 */
	handleCategoryClick( args ) {
		const { e, target } = args;
		const elCategory = target.closest(
			AdminAddons.selectors.elCategory
		);
		e.preventDefault();

		this.elAddonsPage
			.querySelectorAll( AdminAddons.selectors.elCategory )
			.forEach( ( item ) => {
				const isActive = item === elCategory;
				item.classList.toggle( 'active', isActive );
				item.setAttribute(
					'aria-pressed',
					isActive ? 'true' : 'false'
				);
			} );
		this.filterAddons();
	}

	/**
	 * Handle search input.
	 *
	 * @return void
	 */
	handleSearchInput() {
		this.filterAddons();
	}

	/**
	 * Handle purchase code input.
	 *
	 * @param {Object} args Input event arguments.
	 * @return void
	 */
	handlePurchaseCodeInput( args ) {
		const { e, target: el } = args;
		e.preventDefault();
		el.classList.remove( 'is-error' );
		el.removeAttribute( 'aria-invalid' );
		const purchaseCode = el.value;
		const elItemPurchase = el.closest(
			AdminAddons.selectors.elItemPurchase
		);

		if ( elItemPurchase ) {
			const input = elItemPurchase.querySelector(
				'input[name=purchase-code]'
			);
			input.value = purchaseCode;
		}
	}
}

const adminAddons = new AdminAddons();
adminAddons.init();

export default AdminAddons;
