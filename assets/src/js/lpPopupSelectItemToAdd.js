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

let sectionIdSelected;
let elPopupSelectItems;
// Show popup items to select
const showPopupItemsToSelect = ( e, target, elMain, callBack ) => {
	const elBtnShowPopupItemsToSelect = target.closest( `${ className.elBtnShowPopupItemsToSelect }` );
	if ( ! elBtnShowPopupItemsToSelect ) {
		return;
	}

	SweetAlert.fire( {
		html: elMain,
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
				callBack.willOpen();
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

	const itemSelected = {
		item_id: elInput.value,
		item_type: elInput.dataset.type || '',
		item_title: elInput.dataset.title || '',
		item_edit_link: elInput.dataset.editLink || '',
	};
	if ( elInput.checked ) {
		const exists = itemsSelectedData.some( ( item ) => item.item_id === itemSelected.item_id );
		if ( ! exists ) {
			itemsSelectedData.push( itemSelected );
		}
	} else {
		const index = itemsSelectedData.findIndex( ( item ) => item.item_id === itemSelected.item_id );
		if ( index !== -1 ) {
			itemsSelectedData.splice( index, 1 );
		}
	}

	watchItemsSelectedDataChange( elPopupSelectItems );

	if ( typeof callBack === 'function' ) {
		callBack( itemSelected );
	}
};

// Search title item
let timeSearchTitleItem;
const searchTitleItemToSelect = ( e, target ) => {
	const elInputSearch = target.closest( '.lp-search-title-item' );
	if ( ! elInputSearch ) {
		return;
	}

	const elPopupItemsToSelect = elInputSearch.closest( `${ className.elPopupItemsToSelect }` );
	if ( ! elPopupItemsToSelect ) {
		return;
	}

	const elLPTarget = elPopupItemsToSelect.querySelector( `${ className.LPTarget }` );

	clearTimeout( timeSearchTitleItem );

	timeSearchTitleItem = setTimeout( () => {
		const dataSet = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		dataSet.args.search_title = elInputSearch.value.trim();
		dataSet.args.item_selecting = itemsSelectedData;

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
	}, 1000 );
};

// Show list of items, to choose items to add to section
const showItemsSelected = ( e, target ) => {
	const elBtnCountItemsSelected = target.closest( `${ className.elBtnCountItemsSelected }` );
	if ( ! elBtnCountItemsSelected ) {
		return;
	}

	const elParent = elBtnCountItemsSelected.closest( `${ className.elPopupItemsToSelect }` );
	if ( ! elParent ) {
		return;
	}

	const elBtnBack = elParent.querySelector( `${ className.elBtnBackListItems }` );
	const elTabs = elParent.querySelector( '.tabs' );
	const elListItemsWrap = elParent.querySelector( `${ className.elListItemsWrap }` );
	const elHeaderItemsSelected = elParent.querySelector( `${ className.elHeaderCountItemSelected }` );
	const elListItemsSelected = elParent.querySelector( `${ className.elListItemsSelected }` );
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
		elItemSelected.dataset.id = item.item_id;
		elItemSelected.dataset.type = item.item_type || '';

		elItemSelected.querySelector( '.item-title' ).textContent = item.item_title || '';
		elItemSelected.querySelector( '.item-id' ).textContent = item.item_id || '';
		elItemSelected.querySelector( '.item-type' ).textContent = item.item_type || '';

		lpUtils.lpShowHideEl( elItemSelected, 1 );

		elItemClone.insertAdjacentElement( 'beforebegin', elItemSelected );
	} );
};

// Back to list of items
const backToSelectItems = ( e, target ) => {
	const elBtnBack = target.closest( `${ className.elBtnBackListItems }` );
	if ( ! elBtnBack ) {
		return;
	}

	const elParent = elBtnBack.closest( `${ className.elPopupItemsToSelect }` );
	const elBtnCountItemsSelected = elParent.querySelector( `${ className.elBtnCountItemsSelected }` );
	const elTabs = elParent.querySelector( '.tabs' );
	const elListItemsWrap = elParent.querySelector( `${ className.elListItemsWrap }` );
	const elHeaderCountItemSelected = elParent.querySelector( `${ className.elHeaderCountItemSelected }` );
	const elListItemsSelected = elParent.querySelector( `${ className.elListItemsSelected }` );
	lpUtils.lpShowHideEl( elBtnCountItemsSelected, 1 );
	lpUtils.lpShowHideEl( elListItemsWrap, 1 );
	lpUtils.lpShowHideEl( elTabs, 1 );
	lpUtils.lpShowHideEl( elBtnBack, 0 );
	lpUtils.lpShowHideEl( elHeaderCountItemSelected, 0 );
	lpUtils.lpShowHideEl( elListItemsSelected, 0 );
};

// Remove item selected from list items selected
const removeItemSelected = ( e, target ) => {
	const elRemoveItemSelected = target.closest( `${ className.elItemSelected }` );
	if ( ! elRemoveItemSelected ) {
		return;
	}

	const itemRemove = {
		item_id: elRemoveItemSelected.dataset.id,
		item_type: elRemoveItemSelected.dataset.type,
	};
	const index = itemsSelectedData.findIndex( ( item ) => item.item_id === itemRemove.item_id );
	if ( index !== -1 ) {
		itemsSelectedData.splice( index, 1 );
	}

	elRemoveItemSelected.remove();

	watchItemsSelectedDataChange();
};

// Watch items selected when data change
const watchItemsSelectedDataChange = ( elPopupSelectItems ) => {
	// Update count items selected, disable/enable buttons
	const elBtnAddItemsSelected = elPopupSelectItems.querySelector( `${ className.elBtnAddItemsSelected }` );
	const elBtnCountItemsSelected = elPopupSelectItems.querySelector( `${ className.elBtnCountItemsSelected }` );
	const elSpanCount = elBtnCountItemsSelected.querySelector( 'span' );
	const elHeaderCount = elPopupSelectItems.querySelector( `${ className.elHeaderCountItemSelected }` );
	if ( itemsSelectedData.length !== 0 ) {
		elBtnCountItemsSelected.disabled = false;
		elBtnAddItemsSelected.disabled = false;
		elSpanCount.textContent = `(${ itemsSelectedData.length })`;
		elHeaderCount.innerHTML = elBtnCountItemsSelected.innerHTML;
	} else {
		elBtnCountItemsSelected.disabled = true;
		elBtnAddItemsSelected.disabled = true;
		elSpanCount.textContent = '';
		elHeaderCount.textContent = '';
	}

	const elListItems = elPopupSelectItems.querySelector( `${ className.elListItems }` );
	const elInputs = elListItems.querySelectorAll( 'input[type="checkbox"]' );
	elInputs.forEach( ( elInputItem ) => {
		const itemSelected = {
			item_id: elInputItem.value,
			item_type: elInputItem.dataset.type || '',
			item_title: elInputItem.dataset.title || '',
		};
		const exists = itemsSelectedData.some( ( item ) => item.item_id === itemSelected.item_id );
		elInputItem.checked = exists;
	} );
};

// Add items selected to section
const addItemsSelectedToSection = ( e, target ) => {
	const elBtnAddItems = target.closest( `${ className.elBtnAddItemsSelected }` );
	if ( ! elBtnAddItems ) {
		return;
	}

	const elPopupItemsToSelect = elBtnAddItems.closest( `${ className.elPopupItemsToSelect }` );
	if ( ! elPopupItemsToSelect ) {
		return;
	}

	const elSection = document.querySelector( `.section[data-section-id="${ sectionIdSelected }"]` );
	const elItemClone = elSection.querySelector( `${ className.elItemClone }` );

	itemsSelectedData.forEach( ( item ) => {
		const elItemNew = elItemClone.cloneNode( true );
		const elInputTitleNew = elItemNew.querySelector( `${ className.elItemTitleInput }` );

		elItemNew.dataset.itemId = item.item_id;
		elItemNew.classList.add( item.item_type );
		elItemNew.classList.remove( 'clone' );
		elItemNew.dataset.itemType = item.item_type;
		elItemNew.querySelector( '.edit-link' ).setAttribute( 'href', item.item_edit_link || '' );
		elInputTitleNew.value = item.item_title || '';
		lpUtils.lpSetLoadingEl( elItemNew, 1 );
		lpUtils.lpShowHideEl( elItemNew, 1 );
		elItemClone.insertAdjacentElement( 'beforebegin', elItemNew );
	} );

	SweetAlert.close();

	const dataSend = {
		course_id: courseId,
		action: 'add_items_to_section',
		section_id: sectionIdSelected,
		items: itemsSelectedData,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, {
		success: ( response ) => {
			const { message, status } = response;
			showToast( message, status );

			if ( status === 'error' ) {
				itemsSelectedData.forEach( ( item ) => {
					const elItemAdded = elSection.querySelector( `${ className.elSectionItem }[data-item-id="${ item.item_id }"]` );
					if ( elItemAdded ) {
						elItemAdded.remove();
					}
				} );
			}
		},
		error: ( error ) => {
			showToast( error, 'error' );
		},
		completed: () => {
			itemsSelectedData.forEach( ( item ) => {
				const elItemAdded = elSection.querySelector( `${ className.elSectionItem }[data-item-id="${ item.item_id }"]` );
				lpUtils.lpSetLoadingEl( elItemAdded, 0 );
			} );

			itemsSelectedData = []; // Clear selected items data
			updateCountItems( elSection );
		},
	} );
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
