/**
 *  LearnPress Popup Select Item
 *
 *  Handles load(search) item from API, show in popup and select item.
 */

import * as lpUtils from './utils.js';
import Toastify from 'toastify-js';
import SweetAlert from 'sweetalert2-neutral';

let lpSettings = {};
if ( 'undefined' !== typeof lpDataAdmin ) {
	lpSettings = lpDataAdmin;
} else if ( 'undefined' !== typeof lpData ) {
	lpSettings = lpData;
}

const className = {
	elBtnShowPopupItemsToSelect: '.lp-btn-show-popup-items-to-select',
	elBtnAddItemsSelected: '.lp-btn-add-items-selected',
	elBtnCountItemsSelected: '.lp-btn-count-items-selected',
	elHeaderCountItemSelected: '.header-count-items-selected',
	elSelectItem: '.lp-select-item',
	elListItems: '.list-items',
	elSearchTitleItem: '.lp-search-title-item',
	elBtnBackListItems: '.lp-btn-back-to-select-items',
	elListItemsWrap: '.list-items-wrap',
	elListItemsSelected: '.list-items-selected',
	elItemSelectedClone: '.li-item-selected.clone',
	elItemSelected: '.li-item-selected',
	LPTarget: '.lp-target',
};

const argsToastify = {
	text: '',
	gravity: lpSettings.toast.gravity, // `top` or `bottom`
	position: lpSettings.toast.position, // `left`, `center` or `right`
	className: `${ lpSettings.toast.classPrefix }`,
	close: lpSettings.toast.close == 1,
	stopOnFocus: lpSettings.toast.stopOnFocus == 1,
	duration: lpSettings.toast.duration,
};

// Show popup items to select
const showPopupItemsToSelect = ( e, target, elPopupSelectItems, callBack ) => {
	const elBtnShowPopupItemsToSelect = target.closest( `${ className.elBtnShowPopupItemsToSelect }` );
	if ( ! elBtnShowPopupItemsToSelect ) {
		return;
	}

	SweetAlert.fire( {
		html: elPopupSelectItems,
		showConfirmButton: false,
		showCloseButton: true,
		width: '60%',
		customClass: {
			popup: 'lp-select-items-popup',
			htmlContainer: 'lp-select-items-html-container',
			container: 'lp-select-items-container',
		},
		willOpen: () => {
			if ( callBack && typeof callBack.willOpen === 'function' ) {
				callBack.willOpen( itemsSelectedData || [] );
			}
		},
	} ).then( ( result ) => {
		if ( result.isDismissed ) {
		}
	} );
};

// Choose tab items type
const chooseTabItemsType = ( e, target, callBack ) => {
	const elTabType = target.closest( '.tab' );
	if ( ! elTabType ) {
		return;
	}
	e.preventDefault();

	const elTabs = elTabType.closest( '.tabs' );
	if ( ! elTabs ) {
		return;
	}

	const elSelectItemsToAdd = elTabs.closest( `${ className.elPopupItemsToSelect }` );
	const elInputSearch = elSelectItemsToAdd.querySelector( `${ className.elSearchTitleItem }` );

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

	if ( typeof callBack === 'function' ) {
		callBack( itemType );
	}
};

let itemsSelectedData = [];
// Choice items to add list items selected before adding to section
const selectItemsFromList = ( e, target, elPopupSelectItems, callBack ) => {
	const elItemAttend = target.closest( `${ className.elSelectItem }` );
	if ( ! elItemAttend ) {
		return;
	}

	const elInput = elItemAttend.querySelector( 'input[type="checkbox"]' );
	if ( target.tagName !== 'INPUT' ) {
		elInput.click();
		return;
	}

	const elUl = elItemAttend.closest( `${ className.elListItems }` );
	if ( ! elUl ) {
		return;
	}

	const itemSelected = { ...elInput.dataset };
	//console.log( 'itemSelected', itemSelected );

	if ( elInput.checked ) {
		const exists = itemsSelectedData.some( ( item ) => item.id === itemSelected.id );
		if ( ! exists ) {
			itemsSelectedData.push( itemSelected );
		}
	} else {
		const index = itemsSelectedData.findIndex( ( item ) => item.id === itemSelected.id );
		if ( index !== -1 ) {
			itemsSelectedData.splice( index, 1 );
		}
	}

	if ( typeof callBack === 'function' ) {
		callBack( itemsSelectedData );
	}

	watchItemsSelectedDataChange( elPopupSelectItems );
};

// Search title item
let timeSearchTitleItem;
const searchTitleItemToSelect = ( e, target, elPopupItemsToSelect ) => {
	const elInputSearch = target.closest( '.lp-search-title-item' );
	if ( ! elInputSearch ) {
		return;
	}

	const elLPTarget = elPopupItemsToSelect.querySelector( `${ className.LPTarget }` );

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
				showToast( error, 'error' );
			},
			completed: () => {
				window.lpAJAXG.showHideLoading( elLPTarget, 0 );
			},
		} );
	}, 800 );
};

// Show list of items, to choose items to add to section
const showItemsSelected = ( e, target, elPopupItemsToSelect ) => {
	const elBtnCountItemsSelected = target.closest( `${ className.elBtnCountItemsSelected }` );
	if ( ! elBtnCountItemsSelected ) {
		return;
	}

	const elBtnBack = elPopupItemsToSelect.querySelector( `${ className.elBtnBackListItems }` );
	const elTabs = elPopupItemsToSelect.querySelector( '.tabs' );
	const elListItemsWrap = elPopupItemsToSelect.querySelector( `${ className.elListItemsWrap }` );
	const elHeaderItemsSelected = elPopupItemsToSelect.querySelector( `${ className.elHeaderCountItemSelected }` );
	const elListItemsSelected = elPopupItemsToSelect.querySelector( `${ className.elListItemsSelected }` );
	const elItemClone = elListItemsSelected.querySelector( `${ className.elItemSelectedClone }` );
	elHeaderItemsSelected.innerHTML = elBtnCountItemsSelected.innerHTML;

	lpUtils.lpShowHideEl( elListItemsWrap, 0 );
	lpUtils.lpShowHideEl( elBtnCountItemsSelected, 0 );
	lpUtils.lpShowHideEl( elTabs, 0 );
	lpUtils.lpShowHideEl( elBtnBack, 1 );
	lpUtils.lpShowHideEl( elHeaderItemsSelected, 1 );
	lpUtils.lpShowHideEl( elListItemsSelected, 1 );

	elListItemsSelected.querySelectorAll( `${ className.elItemSelected }:not(.clone)` ).forEach( ( elItem ) => {
		elItem.remove();
	} );
	itemsSelectedData.forEach( ( item ) => {
		const elItemSelected = elItemClone.cloneNode( true );
		elItemSelected.classList.remove( 'clone' );
		Object.entries( item ).forEach( ( [ key, value ] ) => {
			elItemSelected.dataset[ key ] = value;
		} );
		const elTitleDisplay = elItemSelected.querySelector( '.title-display' );
		elTitleDisplay.innerHTML = item.title;

		lpUtils.lpShowHideEl( elItemSelected, 1 );

		elItemClone.insertAdjacentElement( 'beforebegin', elItemSelected );
	} );
};

// Back to list of items
const backToSelectItems = ( e, target, elPopupSelectItems ) => {
	const elBtnBack = target.closest( `${ className.elBtnBackListItems }` );
	if ( ! elBtnBack ) {
		return;
	}

	const elBtnCountItemsSelected = elPopupSelectItems.querySelector( `${ className.elBtnCountItemsSelected }` );
	const elTabs = elPopupSelectItems.querySelector( '.tabs' );
	const elListItemsWrap = elPopupSelectItems.querySelector( `${ className.elListItemsWrap }` );
	const elHeaderCountItemSelected = elPopupSelectItems.querySelector( `${ className.elHeaderCountItemSelected }` );
	const elListItemsSelected = elPopupSelectItems.querySelector( `${ className.elListItemsSelected }` );
	lpUtils.lpShowHideEl( elBtnCountItemsSelected, 1 );
	lpUtils.lpShowHideEl( elListItemsWrap, 1 );
	lpUtils.lpShowHideEl( elTabs, 1 );
	lpUtils.lpShowHideEl( elBtnBack, 0 );
	lpUtils.lpShowHideEl( elHeaderCountItemSelected, 0 );
	lpUtils.lpShowHideEl( elListItemsSelected, 0 );
};

// Remove item selected from list items selected
const removeItemSelected = ( e, target, elPopupSelectItems ) => {
	const elRemoveItemSelected = target.closest( `${ className.elItemSelected }` );
	if ( ! elRemoveItemSelected ) {
		return;
	}

	const itemRemove = elRemoveItemSelected.dataset;
	const index = itemsSelectedData.findIndex( ( item ) => item.id === itemRemove.id );
	if ( index !== -1 ) {
		itemsSelectedData.splice( index, 1 );
	}

	elRemoveItemSelected.remove();

	watchItemsSelectedDataChange( elPopupSelectItems );
};

// Watch items selected when data change
const watchItemsSelectedDataChange = ( elPopupSelectItems ) => {
	// Update count items selected, disable/enable buttons
	const elBtnAddItemsSelected = elPopupSelectItems.querySelector( `${ className.elBtnAddItemsSelected }` );
	const elBtnCountItemsSelected = elPopupSelectItems.querySelector( `${ className.elBtnCountItemsSelected }` );
	const elSpanCount = elBtnCountItemsSelected.querySelector( 'span' );
	const elHeaderCount = elPopupSelectItems.querySelector( `${ className.elHeaderCountItemSelected }` );
	const elTarget = elPopupSelectItems.querySelector( `${ className.LPTarget }` );

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
	const elListItems = elPopupSelectItems.querySelector( `${ className.elListItems }` );
	const elInputs = elListItems.querySelectorAll( 'input[type="checkbox"]' );
	elInputs.forEach( ( elInputItem ) => {
		const itemSelected = elInputItem.dataset;
		const exists = itemsSelectedData.some( ( item ) => item.id === itemSelected.id );
		elInputItem.checked = exists;
	} );

	// Set item selecting data to dataset for query.
	const dataSet = window.lpAJAXG.getDataSetCurrent( elTarget );
	dataSet.args.item_selecting = itemsSelectedData;
	window.lpAJAXG.setDataSetCurrent( elTarget, dataSet );
};

// Add items selected to section
const addItemsSelectedToSection = ( e, target, elPopupItemsToSelect, callBack ) => {
	const elBtnAddItems = target.closest( `${ className.elBtnAddItemsSelected }` );
	if ( ! elBtnAddItems ) {
		return;
	}

	if ( ! elPopupItemsToSelect ) {
		return;
	}

	SweetAlert.close();

	if ( typeof callBack === 'function' ) {
		callBack( itemsSelectedData );
		itemsSelectedData = [];
	}
};

const showToast = ( message, status = 'success' ) => {
	const toastify = new Toastify( {
		...argsToastify,
		text: message,
		className: `${ lpSettings.toast.classPrefix } ${ status }`,
	} );
	toastify.showToast();
};

export {
	showPopupItemsToSelect,
	chooseTabItemsType,
	selectItemsFromList,
	searchTitleItemToSelect,
	showItemsSelected,
	backToSelectItems,
	removeItemSelected,
	watchItemsSelectedDataChange,
	addItemsSelectedToSection,
	showToast,
};
