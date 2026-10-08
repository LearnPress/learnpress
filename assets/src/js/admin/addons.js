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
		this.urlParams = new URLSearchParams( window.location.search );
	}

	static selectors = {
		elAddonsPage: '.lp-addons-page',
		elToolbar: '.lp-addons-toolbar',
		elLPAddons: '#lp-addons',
		elAddonItem: '.lp-addon-item',
		elBtnAction: '.btn-addon-action',
		elNavTab: '.nav-tab',
		elNavTabActive: '.nav-tab.nav-tab-active',
		elFilter: '.lp-addons-filter',
		elFilterToggle: '.lp-addons-filter__toggle',
		elFilterMenu: '.lp-addons-filter__menu',
		elCategory: '.lp-addons-category',
		elCategoryActive: '.lp-addons-category.active',
		elSearch: '.lp-addons-search',
		elSearchBtn: '.lp-addons-search__btn',
		elSearchBtnClear: '.lp-addons-search__btn-clear',
		elSearchClose: '.lp-addons-search__close',
		elSearchInput: '#lp-search-addons__input',
		elItemPurchase: '.lp-addon-item__purchase',
		elPurchaseInstall: '.purchase-install',
		elPurchaseCode: '.enter-purchase-code',
		elLicense: '.lp-addon-license',
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

			const selectors = AdminAddons.selectors;
			const urlTab = this.urlParams.get( 'tab' );
			if ( urlTab ) {
				const elMatchingTab = this.elAddonsPage.querySelector(
					`${ selectors.elNavTab }[data-tab="${ urlTab }"]`
				);
				if ( elMatchingTab ) {
					this.elAddonsPage
						.querySelectorAll( selectors.elNavTab )
						.forEach( ( elTab ) => {
							elTab.classList.remove( 'nav-tab-active' );
							elTab.setAttribute( 'aria-pressed', 'false' );
						} );
					elMatchingTab.classList.add( 'nav-tab-active' );
					elMatchingTab.setAttribute( 'aria-pressed', 'true' );
				}
			}

			let elActiveTab = this.elAddonsPage.querySelector(
				selectors.elNavTabActive
			);
			if ( ! elActiveTab ) {
				elActiveTab =
					this.elAddonsPage.querySelector(
						`${ selectors.elNavTab }[data-tab="all"]`
					) || this.elAddonsPage.querySelector( selectors.elNavTab );
				if ( elActiveTab ) {
					elActiveTab.classList.add( 'nav-tab-active' );
					elActiveTab.setAttribute( 'aria-pressed', 'true' );
				}
			}

			let elActiveCategory = this.elAddonsPage.querySelector(
				selectors.elCategoryActive
			);
			if ( ! elActiveCategory ) {
				elActiveCategory =
					this.elAddonsPage.querySelector(
						`${ selectors.elCategory }[data-category="all"]`
					) || this.elAddonsPage.querySelector( selectors.elCategory );
				if ( elActiveCategory ) {
					elActiveCategory.classList.add( 'active' );
					elActiveCategory.setAttribute( 'aria-pressed', 'true' );
				}
			}

			this.filterAddons();
			this.updateSearchClearBtnState();
			this.updateFilterToggleState();
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
				selector: AdminAddons.selectors.elFilterToggle,
				class: this,
				callBack: this.handleFilterToggleClick.name,
			},
			{
				selector: AdminAddons.selectors.elSearchBtn,
				class: this,
				callBack: this.handleSearchBtnClick.name,
			},
			{
				selector: AdminAddons.selectors.elSearchBtnClear,
				class: this,
				callBack: this.handleSearchClearClick.name,
			},
			{
				selector: AdminAddons.selectors.elSearchClose,
				class: this,
				callBack: this.handleSearchCloseClick.name,
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
		lpUtils.eventHandlers( 'keydown', [
			{
				selector: AdminAddons.selectors.elSearchInput,
				class: this,
				callBack: this.handleSearchKeydown.name,
			},
		] );

		document.addEventListener( 'click', ( e ) => {
			if ( ! this.elAddonsPage ) {
				return;
			}
			const elToolbar = this.elAddonsPage.querySelector(
				AdminAddons.selectors.elToolbar
			);
			if ( elToolbar && elToolbar.classList.contains( 'is-filter-open' ) ) {
				if ( ! elToolbar.contains( e.target ) ) {
					this.toggleFilterMenu( false );
				}
			}
		} );

		document.addEventListener( 'keydown', ( e ) => {
			if ( 'Escape' === e.key ) {
				this.toggleFilterMenu( false );
			}
		} );
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
	 * @param {Object} data { action, addon, purchase_code, el, elAddonItem }.
	 * @return void
	 */
	addonsAction( data ) {
		const { action, addon, el, elAddonItem } = data;
		const selectors = AdminAddons.selectors;

		const params = {
			action: 'addon_action',
			action_type: action,
			addon,
			purchase_code: data.purchase_code || '',
			id_url: 'addon-action',
		};

		const releaseHandling = () => {
			lpUtils.lpSetLoadingEl( el, 0 );
		};

		window.lpAJAXG.fetchAJAX( params, {
			success: ( response ) => {
				releaseHandling();

				const { status, message, data: resData } = response;

				lpToastify.show( message, status );

				if ( status === 'success' && resData?.html ) {
					elAddonItem.outerHTML = resData.html;
				} else if (
					'install' === action ||
					'update-purchase' === action
				) {
					const elPurchasePanel = el.closest(
						selectors.elPurchaseInstall
					);
					const elPurchaseCode = elPurchasePanel
						? elPurchasePanel.querySelector( selectors.elPurchaseCode )
						: null;
					if ( elPurchaseCode ) {
						elPurchaseCode.classList.add( 'is-error' );
						elPurchaseCode.setAttribute( 'aria-invalid', 'true' );
					}
				}

				//this.filterAddons();
			},
			error: ( error ) => {
				releaseHandling();
				lpToastify.show( `error js: ${ error }`, 'error' );
			},
		} );
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
		lpUtils.lpSetLoadingEl( el, 1 );

		let purchaseCode = '';
		const elAddonItem = el.closest( selectors.elAddonItem );
		const addon = this.getAddonData( elAddonItem );
		const action = el.dataset.action;
		const elItemPurchase = elAddonItem.querySelector(
			selectors.elItemPurchase
		);

		if ( ! addon ) {
			lpUtils.lpSetLoadingEl( el, 0 );
			return;
		}

		if ( action === 'purchase' || action === 'update-purchase-code' ) {
			const elPurchaseInstall = elItemPurchase.querySelector(
				selectors.elPurchaseInstall
			);
			const elPurchaseCode = elPurchaseInstall.querySelector(
				selectors.elPurchaseCode
			);
			const elPurchaseSubmit = elPurchaseInstall.querySelector(
				'.lp-addon-purchase__submit'
			);
			const elPurchaseClear = elPurchaseInstall.querySelector(
				'.lp-addon-purchase__clear'
			);
			const elPurchaseCancelClear = elPurchaseInstall.querySelector(
				'.lp-addon-purchase__cancel-clear'
			);
			let hasPurchaseCode = false;

			if ( action === 'update-purchase-code' ) {
				const elLicense = elAddonItem.querySelector( selectors.elLicense );
				elPurchaseCode.value = elLicense
					? elLicense.dataset.purchaseCodeMasked || ''
					: '';
				hasPurchaseCode = '' !== elPurchaseCode.value;
				elPurchaseSubmit.dataset.action = 'update-purchase';
			} else {
				elPurchaseCode.value = '';
				elPurchaseSubmit.dataset.action = 'install';
			}

			elPurchaseCode.disabled = hasPurchaseCode;
			elPurchaseSubmit.classList.toggle( 'lp-hidden', hasPurchaseCode );
			elPurchaseClear.classList.toggle( 'lp-hidden', ! hasPurchaseCode );
			elPurchaseCancelClear.classList.add( 'lp-hidden' );
			elPurchaseCode.classList.remove( 'is-error' );
			elPurchaseCode.removeAttribute( 'aria-invalid' );
			elItemPurchase.querySelector( 'input[name=purchase-code]' ).value =
				'';
			elPurchaseInstall.style.display = 'flex';
			elItemPurchase.style.display = 'block';
			if ( ! hasPurchaseCode ) {
				elPurchaseCode.focus();
			}
			lpUtils.lpSetLoadingEl( el, 0 );
			return;
		} else if ( action === 'clear-license' ) {
			const elPurchaseCode = elItemPurchase.querySelector(
				selectors.elPurchaseCode
			);
			const elPurchaseSubmit = elItemPurchase.querySelector(
				'.lp-addon-purchase__submit'
			);
			const elPurchaseClear = elItemPurchase.querySelector(
				'.lp-addon-purchase__clear'
			);
			const elPurchaseCancelClear = elItemPurchase.querySelector(
				'.lp-addon-purchase__cancel-clear'
			);

			elPurchaseCode.value = '';
			elPurchaseCode.disabled = false;
			elPurchaseCode.classList.remove( 'is-error' );
			elPurchaseCode.removeAttribute( 'aria-invalid' );
			elItemPurchase.querySelector( 'input[name=purchase-code]' ).value =
				'';
			elPurchaseSubmit.classList.remove( 'lp-hidden' );
			elPurchaseClear.classList.add( 'lp-hidden' );
			elPurchaseCancelClear.classList.remove( 'lp-hidden' );
			elPurchaseCode.focus();
			lpUtils.lpSetLoadingEl( el, 0 );
			return;
		} else if ( action === 'cancel-clear-license' ) {
			const elLicense = elAddonItem.querySelector( selectors.elLicense );
			const elPurchaseCode = elItemPurchase.querySelector(
				selectors.elPurchaseCode
			);
			const elPurchaseSubmit = elItemPurchase.querySelector(
				'.lp-addon-purchase__submit'
			);
			const elPurchaseClear = elItemPurchase.querySelector(
				'.lp-addon-purchase__clear'
			);

			elPurchaseCode.value = elLicense
				? elLicense.dataset.purchaseCodeMasked || ''
				: '';
			elPurchaseCode.disabled = true;
			elPurchaseCode.classList.remove( 'is-error' );
			elPurchaseCode.removeAttribute( 'aria-invalid' );
			elItemPurchase.querySelector( 'input[name=purchase-code]' ).value =
				'';
			elPurchaseSubmit.classList.add( 'lp-hidden' );
			elPurchaseClear.classList.remove( 'lp-hidden' );
			el.classList.add( 'lp-hidden' );
			lpUtils.lpSetLoadingEl( el, 0 );
			return;
		} else if ( action === 'cancel' ) {
			elItemPurchase.style.display = 'none';
			elItemPurchase.querySelector(
				selectors.elPurchaseInstall
			).style.display = 'none';
			lpUtils.lpSetLoadingEl( el, 0 );
			return;
		}

		// Send request to server.
		if ( elItemPurchase ) {
			purchaseCode = elItemPurchase.querySelector(
				'input[name=purchase-code]'
			).value;
		}

		this.addonsAction( {
			purchase_code: purchaseCode,
			action,
			addon,
			el,
			elAddonItem,
		} );
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
		if ( elSearch ) {
			elSearch.value = '';
		}
		const elSearchContainer = this.elAddonsPage.querySelector(
			selectors.elSearch
		);
		if ( elSearchContainer ) {
			elSearchContainer.classList.remove( 'is-open' );
			const btn = elSearchContainer.querySelector(
				selectors.elSearchBtn
			);
			if ( btn ) {
				btn.setAttribute( 'aria-expanded', 'false' );
			}
		}
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
		this.updateFilterToggleState();
		this.filterAddons();
	}

	/**
	 * Handle filter toggle button click.
	 *
	 * @param {Object} args Click event arguments.
	 * @return void
	 */
	handleFilterToggleClick( args ) {
		const { e } = args;
		e.preventDefault();
		e.stopPropagation();
		this.toggleFilterMenu();
	}

	/**
	 * Toggle or set filter menu dropdown open state.
	 *
	 * @param {boolean|undefined} open Optional boolean to force state.
	 * @return void
	 */
	toggleFilterMenu( open ) {
		const selectors = AdminAddons.selectors;
		const elToolbar = this.elAddonsPage.querySelector( selectors.elToolbar );
		const elFilter = this.elAddonsPage.querySelector( selectors.elFilter );
		if ( ! elToolbar && ! elFilter ) {
			return;
		}
		const toggleBtn = this.elAddonsPage.querySelector(
			selectors.elFilterToggle
		);
		const shouldOpen =
			undefined !== open
				? open
				: elToolbar
				? ! elToolbar.classList.contains( 'is-filter-open' )
				: ! elFilter.classList.contains( 'is-open' );

		if ( elToolbar ) {
			elToolbar.classList.toggle( 'is-filter-open', shouldOpen );
		}
		if ( elFilter ) {
			elFilter.classList.toggle( 'is-open', shouldOpen );
		}
		if ( toggleBtn ) {
			toggleBtn.setAttribute(
				'aria-expanded',
				shouldOpen ? 'true' : 'false'
			);
		}
	}

	/**
	 * Update filter toggle button has-filter state.
	 *
	 * @return void
	 */
	updateFilterToggleState() {
		const selectors = AdminAddons.selectors;
		const toggleBtn = this.elAddonsPage.querySelector(
			selectors.elFilterToggle
		);
		if ( ! toggleBtn ) {
			return;
		}
		const elActiveTab = this.elAddonsPage.querySelector(
			selectors.elNavTabActive
		);
		const elActiveCategory = this.elAddonsPage.querySelector(
			selectors.elCategoryActive
		);
		const tabName = elActiveTab ? elActiveTab.dataset.tab : 'all';
		const category = elActiveCategory
			? elActiveCategory.dataset.category
			: 'all';
		const hasFilter = 'all' !== tabName || 'all' !== category;
		toggleBtn.classList.toggle( 'has-filter', hasFilter );
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
		this.updateFilterToggleState();
		this.filterAddons();
	}

	/**
	 * Handle search input.
	 *
	 * @return void
	 */
	handleSearchInput() {
		this.updateSearchClearBtnState();
		this.filterAddons();
	}

	/**
	 * Handle search button click to open search overlay.
	 *
	 * @param {Object} args Click event arguments.
	 * @return void
	 */
	handleSearchBtnClick( args ) {
		const { e } = args;
		e.preventDefault();
		this.toggleFilterMenu( false );
		const selectors = AdminAddons.selectors;
		const elSearch = this.elAddonsPage.querySelector(
			selectors.elSearch
		);
		const elSearchInput = this.elAddonsPage.querySelector(
			selectors.elSearchInput
		);
		if ( elSearch ) {
			elSearch.classList.add( 'is-open' );
			const btn = elSearch.querySelector( selectors.elSearchBtn );
			if ( btn ) {
				btn.setAttribute( 'aria-expanded', 'true' );
			}
		}
		if ( elSearchInput ) {
			this.updateSearchClearBtnState();
			elSearchInput.focus();
		}
	}

	/**
	 * Handle search clear button click.
	 *
	 * @param {Object} args Click event arguments.
	 * @return void
	 */
	handleSearchClearClick( args ) {
		const { e } = args;
		e.preventDefault();
		const selectors = AdminAddons.selectors;
		const elSearchInput = this.elAddonsPage.querySelector(
			selectors.elSearchInput
		);
		if ( elSearchInput ) {
			elSearchInput.value = '';
			this.updateSearchClearBtnState();
			this.filterAddons();
			elSearchInput.focus();
		}
	}

	/**
	 * Update disabled state of clear search button.
	 *
	 * @return void
	 */
	updateSearchClearBtnState() {
		const selectors = AdminAddons.selectors;
		const elSearchInput = this.elAddonsPage.querySelector(
			selectors.elSearchInput
		);
		const elClearBtn = this.elAddonsPage.querySelector(
			selectors.elSearchBtnClear
		);
		if ( elClearBtn && elSearchInput ) {
			elClearBtn.disabled = ! elSearchInput.value.trim();
		}
	}

	/**
	 * Handle search close button click.
	 *
	 * @param {Object} args Click event arguments.
	 * @return void
	 */
	handleSearchCloseClick( args ) {
		const { e } = args;
		e.preventDefault();
		this.closeSearch();
	}

	/**
	 * Handle search input keydown.
	 *
	 * @param {Object} args Keydown event arguments.
	 * @return void
	 */
	handleSearchKeydown( args ) {
		const { e } = args;
		if ( 'Escape' === e.key ) {
			e.preventDefault();
			this.closeSearch();
		}
	}

	/**
	 * Close search overlay and reset query if needed.
	 *
	 * @return void
	 */
	closeSearch() {
		const selectors = AdminAddons.selectors;
		const elSearch = this.elAddonsPage.querySelector(
			selectors.elSearch
		);
		const elSearchInput = this.elAddonsPage.querySelector(
			selectors.elSearchInput
		);
		if ( elSearch ) {
			elSearch.classList.remove( 'is-open' );
			const btn = elSearch.querySelector( selectors.elSearchBtn );
			if ( btn ) {
				btn.setAttribute( 'aria-expanded', 'false' );
				btn.focus();
			}
		}
		if ( elSearchInput && elSearchInput.value ) {
			elSearchInput.value = '';
			this.filterAddons();
		}
		this.updateSearchClearBtnState();
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
