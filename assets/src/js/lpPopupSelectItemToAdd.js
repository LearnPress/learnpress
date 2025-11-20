/**
 *  LearnPress Popup Select Item
 *
 *  Handles load(search) item from API, show in popup and select item.
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';

let itemsSelectedData = [];
let elPopup;
let timeSearchTitleItem;

export class LpPopupSelectItemToAdd {
	constructor() {
		this.init();
	}

	static selectors = {
		elBtnShowPopupItemsToSelect: '.lp-btn-show-popup-items-to-select',
		elBtnAddItemsSelected: '.lp-btn-add-items-selected',
		elBtnCountItemsSelected: '.lp-btn-count-items-selected',
		elHeaderCountItemSelected: '.header-count-items-selected',
		elSelectItem: '.lp-select-item',
		elListItems: '.list-items',
		elPopupItemsToSelect: '.lp-popup-items-to-select',
		elSearchTitleItem: '.lp-search-title-item',
		elBtnBackListItems: '.lp-btn-back-to-select-items',
		elListItemsWrap: '.list-items-wrap',
		elListItemsSelected: '.list-items-selected',
		elItemSelectedClone: '.li-item-selected.clone',
		elItemSelected: '.li-item-selected',
		LPTarget: '.lp-target',
	};

	init() {
		this.events();
	}

	events = () => {
		if ( LpPopupSelectItemToAdd._loadedEvents ) {
			return;
		}
		LpPopupSelectItemToAdd._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector:
					LpPopupSelectItemToAdd.selectors
						.elBtnShowPopupItemsToSelect,
				callBack: this.showPopupItemsToSelect.name,
				class: this,
			},
			{
				selector: LpPopupSelectItemToAdd.selectors.elSelectItem,
				callBack: this.selectItemsFromList.name,
				class: this,
			},
			{
				selector:
					LpPopupSelectItemToAdd.selectors.elBtnCountItemsSelected,
				callBack: this.showItemsSelected.name,
				class: this,
			},
			{
				selector: LpPopupSelectItemToAdd.selectors.elBtnBackListItems,
				callBack: this.backToSelectItems.name,
				class: this,
			},
			{
				selector: LpPopupSelectItemToAdd.selectors.elItemSelected,
				callBack: this.removeItemSelected.name,
				class: this,
			},
			{
				selector: '.tabs .tab',
				callBack: this.chooseTabItemsType.name,
				class: this,
			},
		] );

		lpUtils.eventHandlers( 'keyup', [
			{
				selector: LpPopupSelectItemToAdd.selectors.elSearchTitleItem,
				callBack: this.searchTitleItemToSelect.name,
				class: this,
			},
		] );
	};

	// Show popup items to select
	showPopupItemsToSelect = ( args ) => {
		const { e, target = false, callBack } = args;
		const elBtnShowPopupItemsToSelect = target.closest(
			`${ LpPopupSelectItemToAdd.selectors.elBtnShowPopupItemsToSelect }`
		);
		if ( ! elBtnShowPopupItemsToSelect ) {
			return;
		}

		const templateId = target.dataset.template || '';
		const modalTemplate = document.querySelector( templateId );

		SweetAlert.fire( {
			html: modalTemplate.innerHTML,
			showConfirmButton: false,
			showCloseButton: true,
			width: '60%',
			customClass: {
				popup: 'lp-select-items-popup',
				htmlContainer: 'lp-select-items-html-container',
				container: 'lp-select-items-container',
			},
			willOpen: () => {
				elPopup = SweetAlert.getPopup();

				const elLPTarget = elPopup.querySelector(
					`${ LpPopupSelectItemToAdd.selectors.LPTarget }`
				);
				if ( elLPTarget ) {
					const dataSend =
						window.lpAJAXG.getDataSetCurrent( elLPTarget );
					dataSend.args.paged = 1;
					dataSend.args.item_selecting = itemsSelectedData || [];
					window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

					window.lpAJAXG.fetchAJAX( dataSend, {
						success: ( response ) => {
							const { data } = response;
							const elSkeleton = elPopup.querySelector(
								'.lp-skeleton-animation'
							);
							elSkeleton.remove();
							elLPTarget.innerHTML = data.content || '';

							this.watchItemsSelectedDataChange();
						},
					} );
				}
			},
		} ).then( ( result ) => {
			if ( result.isDismissed ) {
			}
		} );
	};

	// Choose tab items type
	chooseTabItemsType = ( args ) => {
		const { e, target, callBack } = args;
		const elTabType = target.closest( '.tab' );
		if ( ! elTabType ) {
			return;
		}
		e.preventDefault();

		const elTabs = elTabType.closest( '.tabs' );
		if ( ! elTabs ) {
			return;
		}

		const elSelectItemsToAdd = elTabs.closest(
			`${ LpPopupSelectItemToAdd.selectors.elPopupItemsToSelect }`
		);
		const elInputSearch = elSelectItemsToAdd.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elSearchTitleItem }`
		);

		const itemType = elTabType.dataset.type;
		const elTabLis = elTabs.querySelectorAll( '.tab' );
		elTabLis.forEach( ( elTabLi ) => {
			if ( elTabLi.classList.contains( 'active' ) ) {
				elTabLi.classList.remove( 'active' );
			}
		} );
		elTabType.classList.add( 'active' );
		// Reset search input
		elInputSearch.value = '';

		const elLPTarget = elSelectItemsToAdd.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.LPTarget }`
		);

		const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		dataSend.args.item_type = itemType;
		dataSend.args.paged = 1;
		dataSend.args.item_selecting = itemsSelectedData || [];
		window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

		window.lpAJAXG.showHideLoading( elLPTarget, 1 );

		window.lpAJAXG.fetchAJAX( dataSend, {
			success: ( response ) => {
				const { data } = response;
				elLPTarget.innerHTML = data.content || '';
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				window.lpAJAXG.showHideLoading( elLPTarget, 0 );
				this.watchItemsSelectedDataChange();
			},
		} );
	};

	// Choice items to add list items selected before adding to section
	selectItemsFromList = ( args ) => {
		const { e, target } = args;
		const elItemAttend = target.closest(
			`${ LpPopupSelectItemToAdd.selectors.elSelectItem }`
		);
		if ( ! elItemAttend ) {
			return;
		}

		const elInput = elItemAttend.querySelector( 'input[type="checkbox"]' );
		if ( target.tagName !== 'INPUT' ) {
			elInput.click();
			return;
		}

		const elUl = elItemAttend.closest(
			`${ LpPopupSelectItemToAdd.selectors.elListItems }`
		);
		if ( ! elUl ) {
			return;
		}

		const itemSelected = { ...elInput.dataset };
		//console.log( 'itemSelected', itemSelected );

		if ( elInput.checked ) {
			const exists = itemsSelectedData.some(
				( item ) => item.id === itemSelected.id
			);
			if ( ! exists ) {
				itemsSelectedData.push( itemSelected );
			}
		} else {
			const index = itemsSelectedData.findIndex(
				( item ) => item.id === itemSelected.id
			);
			if ( index !== -1 ) {
				itemsSelectedData.splice( index, 1 );
			}
		}

		this.watchItemsSelectedDataChange();
	};

	// Search title item
	searchTitleItemToSelect = ( args ) => {
		const { e, target } = args;
		const elInputSearch = target.closest(
			LpPopupSelectItemToAdd.selectors.elSearchTitleItem
		);
		if ( ! elInputSearch ) {
			return;
		}

		const elLPTarget = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.LPTarget }`
		);

		clearTimeout( timeSearchTitleItem );

		timeSearchTitleItem = setTimeout( () => {
			const dataSet = window.lpAJAXG.getDataSetCurrent( elLPTarget );
			dataSet.args.search_title = elInputSearch.value.trim();
			dataSet.args.item_selecting = itemsSelectedData;
			dataSet.args.paged = 1;
			window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSet );

			// Show loading
			window.lpAJAXG.showHideLoading( elLPTarget, 1 );

			window.lpAJAXG.fetchAJAX( dataSet, {
				success: ( response ) => {
					const { data } = response;
					elLPTarget.innerHTML = data.content || '';
				},
				error: ( error ) => {
					lpToastify.show( error, 'error' );
				},
				completed: () => {
					window.lpAJAXG.showHideLoading( elLPTarget, 0 );
				},
			} );
		}, 800 );
	};

	// Show list of items, to choose items to add to section
	showItemsSelected = ( args ) => {
		const { e, target } = args;
		const elBtnCountItemsSelected = target.closest(
			`${ LpPopupSelectItemToAdd.selectors.elBtnCountItemsSelected }`
		);
		if ( ! elBtnCountItemsSelected ) {
			return;
		}

		const elBtnBack = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elBtnBackListItems }`
		);
		const elTabs = elPopup.querySelector( '.tabs' );
		const elListItemsWrap = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elListItemsWrap }`
		);
		const elHeaderItemsSelected = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elHeaderCountItemSelected }`
		);
		const elListItemsSelected = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elListItemsSelected }`
		);
		const elItemClone = elListItemsSelected.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elItemSelectedClone }`
		);
		elHeaderItemsSelected.innerHTML = elBtnCountItemsSelected.innerHTML;

		lpUtils.lpShowHideEl( elListItemsWrap, 0 );
		lpUtils.lpShowHideEl( elBtnCountItemsSelected, 0 );
		lpUtils.lpShowHideEl( elTabs, 0 );
		lpUtils.lpShowHideEl( elBtnBack, 1 );
		lpUtils.lpShowHideEl( elHeaderItemsSelected, 1 );
		lpUtils.lpShowHideEl( elListItemsSelected, 1 );

		elListItemsSelected
			.querySelectorAll(
				`${ LpPopupSelectItemToAdd.selectors.elItemSelected }:not(.clone)`
			)
			.forEach( ( elItem ) => {
				elItem.remove();
			} );
		itemsSelectedData.forEach( ( item ) => {
			const elItemSelected = elItemClone.cloneNode( true );
			elItemSelected.classList.remove( 'clone' );
			Object.entries( item ).forEach( ( [ key, value ] ) => {
				elItemSelected.dataset[ key ] = value;
			} );
			const elTitleDisplay =
				elItemSelected.querySelector( '.title-display' );
			elTitleDisplay.innerHTML = item.title;

			lpUtils.lpShowHideEl( elItemSelected, 1 );

			elItemClone.insertAdjacentElement( 'beforebegin', elItemSelected );
		} );
	};

	// Back to list of items
	backToSelectItems = ( args ) => {
		const { e, target } = args;
		const elBtnBack = target.closest(
			`${ LpPopupSelectItemToAdd.selectors.elBtnBackListItems }`
		);
		if ( ! elBtnBack ) {
			return;
		}

		const elBtnCountItemsSelected = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elBtnCountItemsSelected }`
		);
		const elTabs = elPopup.querySelector( '.tabs' );
		const elListItemsWrap = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elListItemsWrap }`
		);
		const elHeaderCountItemSelected = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elHeaderCountItemSelected }`
		);
		const elListItemsSelected = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elListItemsSelected }`
		);
		lpUtils.lpShowHideEl( elBtnCountItemsSelected, 1 );
		lpUtils.lpShowHideEl( elListItemsWrap, 1 );
		lpUtils.lpShowHideEl( elTabs, 1 );
		lpUtils.lpShowHideEl( elBtnBack, 0 );
		lpUtils.lpShowHideEl( elHeaderCountItemSelected, 0 );
		lpUtils.lpShowHideEl( elListItemsSelected, 0 );
	};

	// Remove item selected from list items selected
	removeItemSelected = ( args ) => {
		const { e, target } = args;
		const elRemoveItemSelected = target.closest(
			`${ LpPopupSelectItemToAdd.selectors.elItemSelected }`
		);
		if ( ! elRemoveItemSelected ) {
			return;
		}

		const itemRemove = elRemoveItemSelected.dataset;
		const index = itemsSelectedData.findIndex(
			( item ) => item.id === itemRemove.id
		);
		if ( index !== -1 ) {
			itemsSelectedData.splice( index, 1 );
		}

		elRemoveItemSelected.remove();

		this.watchItemsSelectedDataChange();
	};

	// Watch items selected when data change
	watchItemsSelectedDataChange = () => {
		// Update count items selected, disable/enable buttons
		const elBtnAddItemsSelected = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elBtnAddItemsSelected }`
		);
		const elBtnCountItemsSelected = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elBtnCountItemsSelected }`
		);
		const elSpanCount = elBtnCountItemsSelected.querySelector( 'span' );
		const elHeaderCount = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elHeaderCountItemSelected }`
		);
		const elTarget = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.LPTarget }`
		);

		if ( itemsSelectedData.length !== 0 ) {
			elBtnCountItemsSelected.disabled = false;
			elBtnAddItemsSelected.disabled = false;
			elBtnAddItemsSelected.classList.add( 'active' );
			elSpanCount.textContent = `(${ itemsSelectedData.length })`;
			elHeaderCount.innerHTML = elBtnCountItemsSelected.innerHTML;
		} else {
			elBtnCountItemsSelected.disabled = true;
			elBtnAddItemsSelected.disabled = true;
			elBtnAddItemsSelected.classList.remove( 'active' );
			elSpanCount.textContent = '';
			elHeaderCount.textContent = '';
		}

		// Update list input checked, when items removed, or change tab type
		const elListItems = elPopup.querySelector(
			`${ LpPopupSelectItemToAdd.selectors.elListItems }`
		);
		const elInputs = elListItems.querySelectorAll(
			'input[type="checkbox"]'
		);
		elInputs.forEach( ( elInputItem ) => {
			const itemSelected = elInputItem.dataset;
			const exists = itemsSelectedData.some(
				( item ) => item.id === itemSelected.id
			);
			elInputItem.checked = exists;
		} );

		// Set item selecting data to dataset for query.
		const dataSet = window.lpAJAXG.getDataSetCurrent( elTarget );
		dataSet.args.item_selecting = itemsSelectedData;
		window.lpAJAXG.setDataSetCurrent( elTarget, dataSet );
	};

	// Add items selected to section
	addItemsSelectedToSection = ( args ) => {
		const { e, target, callBackHandle } = args;

		if ( ! elPopup ) {
			return;
		}

		SweetAlert.close();

		if ( typeof callBackHandle === 'function' ) {
			callBackHandle( itemsSelectedData );
			itemsSelectedData = [];
		}
	};
}
