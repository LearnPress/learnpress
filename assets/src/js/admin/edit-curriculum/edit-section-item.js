/**
 * Edit Section item Script on Curriculum
 *
 * @version 1.0.0
 * @since 4.2.8.6
 */
import * as lpEditCurriculumShare from './share.js';
import SweetAlert from 'sweetalert2';
import Sortable from 'sortablejs';

const className = {
	...lpEditCurriculumShare.className,
	elSectionListItems: '.section-list-items',
	elItemClone: '.section-item.clone',
	elSectionItem: '.section-item',
	elBtnSelectItemType: '.lp-btn-select-item-type',
	elAddItemTypeClone: '.lp-add-item-type.clone',
	elSectionActions: '.section-actions',
	elAddItemType: '.lp-add-item-type',
	elAddItemTypeTitleInput: '.lp-add-item-type-title-input',
	elBtnAddItemCancel: '.lp-btn-add-item-cancel',
	elBtnAddItem: '.lp-btn-add-item',
	elItemTitleInput: '.lp-item-title-input',
	elBtnUpdateItemTitle: '.lp-btn-update-item-title',
	elBtnCancelUpdateTitle: '.lp-btn-cancel-update-item-title',
	elBtnDeleteItem: '.lp-btn-delete-item',
	elBtnShowPopupItemsToSelect: '.lp-btn-show-popup-items-to-select',
	elPopupItemsToSelectClone: '.lp-popup-items-to-select.clone',
	elPopupItemsToSelect: '.lp-popup-items-to-select',
	elSelectItem: '.lp-select-item',
	elListItemsWrap: '.list-items-wrap',
	elListItems: '.list-items',
	elBtnAddItemsSelected: '.lp-btn-add-items-selected',
	elBtnCountItemsSelected: '.lp-btn-count-items-selected',
	elBtnBackListItems: '.lp-btn-back-to-select-items',
	elHeaderCountItemSelected: '.header-count-items-selected',
	elListItemsSelected: '.list-items-selected',
	elItemSelectedClone: '.li-item-selected.clone',
	elItemSelected: '.li-item-selected',
	elBtnSetPreviewItem: '.lp-btn-set-preview-item',
};

let {
	courseId,
	elCurriculumSections,
	showToast,
	lpUtils,
	updateCountItems,
} = lpEditCurriculumShare;

const idUrlHandle = 'edit-course-curriculum';

const init = () => {
	( {
		courseId,
		elCurriculumSections,
		showToast,
		lpUtils,
		updateCountItems,
	} = lpEditCurriculumShare );
};

// Add item type
const addItemType = ( e, target ) => {
	const elBtnSelectItemType = target.closest( `${ className.elBtnSelectItemType }` );
	if ( ! elBtnSelectItemType ) {
		return;
	}

	const itemType = elBtnSelectItemType.dataset.itemType;
	const itemPlaceholder = elBtnSelectItemType.dataset.placeholder;
	const itemBtnAddText = elBtnSelectItemType.dataset.buttonAddText;

	const elSection = elBtnSelectItemType.closest( `${ className.elSection }` );
	const elSectionActions = elSection.querySelector( `${ className.elSectionActions }` );

	// Insert input item type to add
	const elAddItemTypeClone = elSectionActions.querySelector( `${ className.elAddItemTypeClone }` );
	const elNewItemByType = elAddItemTypeClone.cloneNode( true );
	const elAddItemTypeInput = elNewItemByType.querySelector( `${ className.elAddItemTypeTitleInput }` );
	const elBtnAddItem = elNewItemByType.querySelector( `${ className.elBtnAddItem }` );

	elNewItemByType.classList.remove( 'clone' );
	elNewItemByType.classList.add( itemType );
	lpUtils.lpShowHideEl( elNewItemByType, 1 );
	elAddItemTypeInput.setAttribute( 'placeholder', itemPlaceholder );
	elAddItemTypeInput.dataset.itemType = itemType;
	elBtnAddItem.textContent = itemBtnAddText;
	elSectionActions.insertAdjacentElement( 'beforebegin', elNewItemByType );
	elAddItemTypeInput.focus();
};

// Cancel add item type
const cancelAddItemType = ( e, target ) => {
	const elBtnAddItemCancel = target.closest( `${ className.elBtnAddItemCancel }` );
	if ( ! elBtnAddItemCancel ) {
		return;
	}

	const elAddItemType = target.closest( `${ className.elAddItemType }` );
	if ( elAddItemType ) {
		elAddItemType.remove();
	}
};

// Add item to section
const addItemToSection = ( e, target ) => {
	let canHandle = false;

	if ( target.closest( `${ className.elBtnAddItem }` ) ) {
		canHandle = true;
	} else if ( target.closest( `${ className.elAddItemTypeTitleInput }` ) &&
		e.key === 'Enter' ) {
		canHandle = true;
	}

	if ( ! canHandle ) {
		return;
	}

	e.preventDefault();

	const elAddItemType = target.closest( `${ className.elAddItemType }` );
	const elSection = elAddItemType.closest( `${ className.elSection }` );
	const sectionId = elSection.dataset.sectionId;
	const elAddItemTypeTitleInput = elAddItemType.querySelector( `${ className.elAddItemTypeTitleInput }` );
	const titleValue = elAddItemTypeTitleInput.value.trim();
	const typeValue = elAddItemTypeTitleInput.dataset.itemType;
	const message = elAddItemTypeTitleInput.dataset.messEmptyTitle;

	if ( titleValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	// Clone new section item
	const elItemClone = elSection.querySelector( `${ className.elItemClone }` );
	const elItemNew = elItemClone.cloneNode( true );
	const elItemTitleInput = elItemNew.querySelector( `${ className.elItemTitleInput }` );

	elItemNew.classList.remove( 'clone' );
	elItemNew.classList.add( typeValue );
	elItemNew.dataset.itemType = typeValue;
	lpUtils.lpShowHideEl( elItemNew, 1 );
	lpUtils.lpSetLoadingEl( elItemNew, 1 );
	elItemTitleInput.value = titleValue;
	elItemTitleInput.dataset.old = titleValue;
	elItemClone.insertAdjacentElement( 'beforebegin', elItemNew );
	elAddItemType.remove();

	// Call ajax to add item to section
	const callBack = {
		success: ( response ) => {
			const { message, status, data } = response;

			showToast( message, status );

			if ( status === 'error' ) {
				elItemNew.remove();
			} else if ( status === 'success' ) {
				const { section_item, item_link } = data || {};
				elItemNew.dataset.itemId = section_item.item_id || 0;
				elItemNew.querySelector( '.edit-link' ).setAttribute( 'href', item_link || '' );
			}
		},
		error: ( error ) => {
			showToast( error, 'error' );
			elItemNew.remove();
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elItemNew, 0 );
			updateCountItems( elSection );
		},
	};

	const dataSend = {
		course_id: courseId,
		action: 'create_item_add_to_section',
		section_id: sectionId,
		item_title: titleValue,
		item_type: typeValue,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Typing in title input
const changeTitle = ( e, target ) => {
	const elItemTitleInput = target.closest( `${ className.elItemTitleInput }` );
	if ( ! elItemTitleInput ) {
		return;
	}

	const elSectionItem = elItemTitleInput.closest( `${ className.elSectionItem }` );
	if ( ! elSectionItem ) {
		return;
	}

	const titleValue = elItemTitleInput.value.trim();
	const titleValueOld = elItemTitleInput.dataset.old || '';

	if ( titleValue === titleValueOld ) {
		elSectionItem.classList.remove( 'editing' );
	} else {
		elSectionItem.classList.add( 'editing' );
	}
};

// Focus in item title input
const focusTitleInput = ( e, target, isFocus = true ) => {
	const elItemTitleInput = target.closest( `${ className.elItemTitleInput }` );
	if ( ! elItemTitleInput ) {
		return;
	}

	const elSectionItem = elItemTitleInput.closest( `${ className.elSectionItem }` );
	if ( ! elSectionItem ) {
		return;
	}

	if ( isFocus ) {
		elSectionItem.classList.add( 'focus' );
	} else {
		elSectionItem.classList.remove( 'focus' );
	}
};

const changeTitleAddNew = ( e, target ) => {
	const elAddItemTypeTitleInput = target.closest( `${ className.elAddItemTypeTitleInput }` );
	if ( ! elAddItemTypeTitleInput ) {
		return;
	}

	const elAddItemType = elAddItemTypeTitleInput.closest( `${ className.elAddItemType }` );
	if ( ! elAddItemType ) {
		return;
	}

	const elBtnAddItem = elAddItemType.querySelector( `${ className.elBtnAddItem }` );
	if ( ! elBtnAddItem ) {
		return;
	}

	const titleValue = elAddItemTypeTitleInput.value.trim();
	if ( titleValue.length === 0 ) {
		elBtnAddItem.classList.remove( 'active' );
	} else {
		elBtnAddItem.classList.add( 'active' );
	}
};

// Update item title
const updateTitle = ( e, target ) => {
	let canHandle = false;

	if ( target.closest( `${ className.elBtnUpdateItemTitle }` ) ) {
		canHandle = true;
	} else if ( target.closest( `${ className.elItemTitleInput }` ) &&
		e.key === 'Enter' ) {
		canHandle = true;
	}

	if ( ! canHandle ) {
		return;
	}

	e.preventDefault();

	const elSectionItem = target.closest( `${ className.elSectionItem }` );
	if ( ! elSectionItem ) {
		return;
	}

	const elSection = elSectionItem.closest( `${ className.elSection }` );
	if ( ! elSection ) {
		return;
	}

	const elItemTitleInput = elSectionItem.querySelector( `${ className.elItemTitleInput }` );
	if ( ! elItemTitleInput ) {
		return;
	}

	const itemId = elSectionItem.dataset.itemId;
	const itemType = elSectionItem.dataset.itemType;
	const itemTitleValue = elItemTitleInput.value.trim();
	const titleOld = elItemTitleInput.dataset.old;
	const message = elItemTitleInput.dataset.messEmptyTitle;
	if ( itemTitleValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	if ( itemTitleValue === titleOld ) {
		return;
	}

	// Un-focus input item title
	elItemTitleInput.blur();
	// show loading
	lpUtils.lpSetLoadingEl( elSectionItem, 1 );
	// Call ajax to update item title
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;

			showToast( message, status );
		},
		error: ( error ) => {
			showToast( error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elSectionItem, 0 );
			elSectionItem.classList.remove( 'editing' ); // Remove editing class
			elItemTitleInput.dataset.old = itemTitleValue; // Update value input
		},
	};

	const dataSend = {
		course_id: courseId,
		action: 'update_item_of_section',
		section_id: elSection.dataset.sectionId,
		item_id: itemId,
		item_type: itemType,
		item_title: itemTitleValue,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Cancel update item title
const cancelUpdateTitle = ( e, target ) => {
	const elBtnCancelUpdateTitle = target.closest( `${ className.elBtnCancelUpdateTitle }` );
	if ( ! elBtnCancelUpdateTitle ) {
		return;
	}

	const elSectionItem = elBtnCancelUpdateTitle.closest( `${ className.elSectionItem }` );
	const elItemTitleInput = elSectionItem.querySelector( `${ className.elItemTitleInput }` );
	elItemTitleInput.value = elItemTitleInput.dataset.old || ''; // Reset to old value
	elSectionItem.classList.remove( 'editing' ); // Remove editing class
};

// Delete item from section
const deleteItem = ( e, target ) => {
	const elBtnDeleteItem = target.closest( `${ className.elBtnDeleteItem }` );
	if ( ! elBtnDeleteItem ) {
		return;
	}

	const elSectionItem = elBtnDeleteItem.closest( `${ className.elSectionItem }` );
	if ( ! elSectionItem ) {
		return;
	}

	const itemId = elSectionItem.dataset.itemId;
	const elSection = elSectionItem.closest( `${ className.elSection }` );
	const sectionId = elSection.dataset.sectionId;

	SweetAlert.fire( {
		title: elBtnDeleteItem.dataset.title,
		text: elBtnDeleteItem.dataset.content,
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		cancelButtonText: lpDataAdmin.i18n.cancel,
		confirmButtonText: lpDataAdmin.i18n.yes,
		reverseButtons: true,
	} ).then( ( result ) => {
		if ( result.isConfirmed ) {
			lpUtils.lpSetLoadingEl( elSectionItem, 1 );

			// Call ajax to delete item from section
			const callBack = {
				success: ( response ) => {
					const { message, status } = response;

					showToast( message, status );

					if ( status === 'success' ) {
						elSectionItem.remove();
					}
				},
				error: ( error ) => {
					showToast( error, 'error' );
				},
				completed: () => {
					lpUtils.lpSetLoadingEl( elSectionItem, 0 );
					updateCountItems( elSection );
				},
			};

			const dataSend = {
				course_id: courseId,
				action: 'delete_item_from_section',
				section_id: sectionId,
				item_id: itemId,
				args: {
					id_url: idUrlHandle,
				},
			};
			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
	} );
};

// Sortable items, can drop on multiple sections
const sortAbleItem = () => {
	const elSectionListItems = elCurriculumSections.querySelectorAll( `${ className.elSectionListItems }` );
	let itemIdChoose = 0;
	let elSectionChoose;
	let sectionIdChoose = 0;
	let sectionIdEnd = 0;
	let timeout;
	elSectionListItems.forEach( ( elItem ) => {
		new Sortable( elItem, {
			handle: '.drag',
			animation: 150,
			group: {
				name: 'shared',
			},
			onEnd: ( evt ) => {
				const dataSectionsItems = [];

				const elItemDragged = evt.item;
				sectionIdEnd = elItemDragged.closest( `${ className.elSection }` ).dataset.sectionId;

				const dataSend = {
					course_id: courseId,
					args: {
						id_url: idUrlHandle,
					},
				};
				if ( sectionIdChoose === sectionIdEnd ) {
					// Update items position in the same section
					dataSend.action = 'update_items_position';
					dataSend.section_id = sectionIdEnd;
				} else {
					// Update section id of item changed
					dataSend.action = 'update_item_section_and_position';
					dataSend.item_id_change = itemIdChoose;
					dataSend.section_id_new_of_item = sectionIdEnd;
					dataSend.section_id_old_of_item = sectionIdChoose;
				}

				// Send list items position
				const section = elCurriculumSections.querySelector( `.section[data-section-id="${ sectionIdEnd }"]` );
				const items = section.querySelectorAll( `${ className.elSectionItem }` );
				items.forEach( ( elItem ) => {
					const itemId = parseInt( elItem.dataset.itemId || 0 );
					if ( itemId === 0 ) {
						return;
					}

					dataSectionsItems.push( itemId );
				} );
				dataSend.items_position = dataSectionsItems;

				// Call ajax to update items position
				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						showToast( message, status );
					},
					error: ( error ) => {
						showToast( error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elItemDragged, 0 );
						updateCountItems( section );
						if ( sectionIdChoose !== sectionIdEnd ) {
							updateCountItems( elSectionChoose );
						}
					},
				};

				/*clearTimeout( timeout );
				timeout = setTimeout( () => {
					lpUtils.lpSetLoadingEl( elItemDragged, 1 );
					window.lpAJAXG.fetchAJAX( dataSend, callBack );
				}, 1000 );*/

				lpUtils.lpSetLoadingEl( elItemDragged, 1 );
				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			},
			onMove: ( evt ) => {
				//clearTimeout( timeout );
			},
			onChoose: ( evt ) => {
				const elChooseItem = evt.item;
				itemIdChoose = elChooseItem.dataset.itemId;
				elSectionChoose = elChooseItem.closest( `${ className.elSection }` );
				sectionIdChoose = elSectionChoose.dataset.sectionId;
			},
			onUpdate: ( evt ) => {},
		} );
	} );
};

let sectionIdSelected;
let elPopupSelectItems;
// Show popup items to select
const showPopupItemsToSelect = ( e, target ) => {
	const elBtnShowPopupItemsToSelect = target.closest( `${ className.elBtnShowPopupItemsToSelect }` );
	if ( ! elBtnShowPopupItemsToSelect ) {
		return;
	}

	const elSection = elBtnShowPopupItemsToSelect.closest( `${ className.elSection }` );
	sectionIdSelected = elSection.dataset.sectionId;

	const elPopupItemsToSelectClone = document.querySelector( `${ className.elPopupItemsToSelectClone }` );
	elPopupSelectItems = elPopupItemsToSelectClone.cloneNode( true );
	elPopupSelectItems.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( elPopupSelectItems, 1 );

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
			// Trigger tab lesson to be active and call AJAX load items
			const tabLesson = elPopupSelectItems.querySelector( 'li[data-type="lp_lesson"]' );
			tabLesson.click();
		},
	} ).then( ( result ) => {
		if ( result.isDismissed ) {
		}
	} );
};

// Choose tab items type
const chooseTabItemsType = ( e, target ) => {
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
	const elInputSearch = elSelectItemsToAdd.querySelector( '.lp-search-title-item' );

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

	const elLPTarget = elSelectItemsToAdd.querySelector( `${ className.LPTarget }` );

	const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
	dataSend.args.item_type = itemType;
	dataSend.args.paged = 1;
	dataSend.args.item_selecting = itemsSelectedData || [];
	window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

	// Show loading
	window.lpAJAXG.showHideLoading( elLPTarget, 1 );
	// End

	window.lpAJAXG.fetchAJAX( dataSend, {
		success: ( response ) => {
			const { data } = response;
			elLPTarget.innerHTML = data.content || '';
		},
		error: ( error ) => {
			showToast( error, 'error' );
		},
		completed: () => {
			window.lpAJAXG.showHideLoading( elLPTarget, 0 );
			// Show button add if there are items selected
			watchItemsSelectedDataChange();
		},
	} );
};

let itemsSelectedData = [];
// Choice items to add list items selected before adding to section
const selectItemsFromList = ( e, target ) => {
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

	watchItemsSelectedDataChange();
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
const watchItemsSelectedDataChange = () => {
	if ( ! elPopupSelectItems ) {
		return;
	}

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

// Enable/disable preview item
const updatePreviewItem = ( e, target ) => {
	const elBtnSetPreviewItem = target.closest( `${ className.elBtnSetPreviewItem }` );
	if ( ! elBtnSetPreviewItem ) {
		return;
	}

	const elSectionItem = elBtnSetPreviewItem.closest( `${ className.elSectionItem }` );
	if ( ! elSectionItem ) {
		return;
	}

	const icon = elBtnSetPreviewItem.querySelector( 'a' );

	icon.classList.toggle( 'lp-icon-eye' );
	icon.classList.toggle( 'lp-icon-eye-slash' );

	const enablePreview = ! icon.classList.contains( 'lp-icon-eye-slash' );

	const itemId = elSectionItem.dataset.itemId;
	const itemType = elSectionItem.dataset.itemType;

	lpUtils.lpSetLoadingEl( elSectionItem, 1 );

	// Call ajax to update item preview
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;

			showToast( message, status );

			if ( status === 'error' ) {
				icon.classList.toggle( 'lp-icon-eye' );
				icon.classList.toggle( 'lp-icon-eye-slash' );
			}
		},
		error: ( error ) => {
			showToast( error, 'error' );
			icon.classList.toggle( 'lp-icon-eye' );
			icon.classList.toggle( 'lp-icon-eye-slash' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elSectionItem, 0 );
		},
	};

	const dataSend = {
		course_id: courseId,
		action: 'update_item_preview',
		item_id: itemId,
		item_type: itemType,
		enable_preview: enablePreview ? 1 : 0,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

export {
	init,
	addItemType,
	cancelAddItemType,
	addItemToSection,
	changeTitle,
	focusTitleInput,
	changeTitleAddNew,
	updateTitle,
	cancelUpdateTitle,
	deleteItem,
	sortAbleItem,
	showPopupItemsToSelect,
	chooseTabItemsType,
	selectItemsFromList,
	showItemsSelected,
	backToSelectItems,
	removeItemSelected,
	searchTitleItemToSelect,
	addItemsSelectedToSection,
	updatePreviewItem,
};

