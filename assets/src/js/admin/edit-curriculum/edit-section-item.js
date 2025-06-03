/**
 * Edit Section item Script on Curriculum
 *
 * @version 1.0.0
 * @since 4.2.8.6
 */
import * as lpEditCurriculumShare from './share.js';
import SweetAlert from 'sweetalert2';
import Sortable from 'sortablejs';

let className = {
	elSectionListItems: '.section-list-items',
	elSectionItem: '.section-item',
	elBtnSelectItemType: '.lp-btn-select-item-type',
	elAddItemTypeClone: '.lp-add-item-type.clone',
	elAddItemType: '.lp-add-item-type',
	elAddItemTypeTitleInput: '.lp-add-item-type-title-input',
	elBtnAddItemCancel: '.lp-btn-add-item-cancel',
	elBtnAddItem: '.lp-btn-add-item',
	elItemClone: 'section-item-clone',
	elItemTitleInput: '.lp-item-title-input',
	elBtnUpdateItemTitle: '.lp-btn-update-item-title',
	elBtnCancelUpdateTitle: '.lp-btn-cancel-update-item-title',
	elBtnDeleteItem: '.lp-btn-delete-item',
	elBtnShowPopupItemsToSelect: '.lp-btn-show-popup-items-to-select',
	elPopupItemsToSelectClone: 'lp-popup-items-to-select-clone',
	elPopupItemsToSelect: 'lp-popup-items-to-select',
};
let elCurriculumSections;
let showToast;
let lpUtils;
let dataSend;
let updateCountItems;

const init = () => {
	( { elCurriculumSections, showToast, lpUtils, dataSend, updateCountItems } = lpEditCurriculumShare );
	className = { ...lpEditCurriculumShare.className, ...className };
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
	const elNewItemIcoByType = elNewItemByType.querySelector( '.item-ico-type' );
	const elAddItemTypeInput = elNewItemByType.querySelector( `${ className.elAddItemTypeTitleInput }` );
	const elBtnAddItem = elNewItemByType.querySelector( `${ className.elBtnAddItem }` );

	elNewItemByType.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( elNewItemByType, 1 );
	elNewItemIcoByType.classList.add( itemType );
	elAddItemTypeInput.setAttribute( 'placeholder', itemPlaceholder );
	elAddItemTypeInput.dataset.itemType = itemType;
	elBtnAddItem.textContent = itemBtnAddText;
	elSectionActions.insertAdjacentElement( 'beforebegin', elNewItemByType );
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
	const elBtnAddItem = target.closest( `${ className.elBtnAddItem }` );
	if ( ! elBtnAddItem ) {
		return;
	}

	e.preventDefault();

	const elAddItemType = elBtnAddItem.closest( `${ className.elAddItemType }` );
	const elSection = elBtnAddItem.closest( `${ className.elSection }` );
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
	const elItemClone = elSection.querySelector( `.${ className.elItemClone }` );
	const elItemNew = elItemClone.cloneNode( true );
	const elItemTitleInput = elItemNew.querySelector( `${ className.elItemTitleInput }` );
	const elItemIco = elItemNew.querySelector( '.item-ico-type' );

	elItemNew.classList.remove( `${ className.elItemClone }` );
	elItemNew.classList.add( typeValue );
	lpUtils.lpShowHideEl( elItemNew, 1 );
	elItemTitleInput.value = titleValue;
	lpUtils.lpSetLoadingEl( elItemNew, 1 );
	elItemIco.classList.add( typeValue );
	elItemClone.insertAdjacentElement( 'beforebegin', elItemNew );
	elAddItemType.remove();

	// Call ajax to add item to section
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;
			const { content } = response.data;

			showToast( message, status );

			if ( status === 'error' ) {
				elItemNew.remove();
			}
		},
		error: ( error ) => {
			console.log( error );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elItemNew, 0 );
			//updateCountItems( elSection );
		},
	};

	dataSend.callback.method = 'handle_edit_course_curriculum';
	dataSend.args.section_id = sectionId;
	dataSend.args.action = 'add_item_to_section';
	dataSend.args.item_title = titleValue;
	dataSend.args.item_type = typeValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
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

	console.log( itemTitleValue );

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
			console.log( error );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elSectionItem, 0 );
			elSectionItem.classList.remove( 'editing' ); // Remove editing class
			elItemTitleInput.dataset.old = itemTitleValue; // Update value input
		},
	};

	dataSend.callback.method = 'handle_edit_course_curriculum';
	dataSend.args.action = 'update_item';
	dataSend.args.item_id = itemId;
	dataSend.args.item_type = itemType;
	dataSend.args.item_title = itemTitleValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
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
let sectionIdSelected;
let elPopupSelectItems;
const showPopupItemsToSelect = ( e, target ) => {
	const elBtnShowPopupItemsToSelect = target.closest( `${ className.elBtnShowPopupItemsToSelect }` );
	if ( ! elBtnShowPopupItemsToSelect ) {
		return;
	}

	const elSection = elBtnShowPopupItemsToSelect.closest( `${ className.elSection }` );
	sectionIdSelected = elSection.dataset.sectionId;

	const elPopupItemsToSelectClone = document.querySelector( `.${ className.elPopupItemsToSelectClone }` );
	elPopupSelectItems = elPopupItemsToSelectClone.cloneNode( true );
	elPopupSelectItems.classList.remove( className.elPopupItemsToSelectClone );
	elPopupSelectItems.classList.add( className.elPopupItemsToSelect );
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
			const tabLesson = elPopupSelectItems.querySelector( 'li[data-type="lp_lesson"]' );
			tabLesson.click();
		},
	} ).then( ( result ) => {
		if ( result.isDismissed ) {
		}
	} );
};
let itemsSelectedData = [];
const selectItemsFromList = ( e, target ) => {
	const elItemAttend = target.closest( '.lp-select-item' );
	if ( ! elItemAttend ) {
		return;
	}

	const elInput = elItemAttend.querySelector( 'input[type="checkbox"]' );

	if ( target.tagName !== 'INPUT' ) {
		elInput.click();
	}

	const elUl = elItemAttend.closest( '.list-items' );
	if ( ! elUl ) {
		return;
	}

	const itemSelected = {
		item_id: elInput.value,
		item_type: elInput.dataset.type || '',
		item_title: elInput.dataset.title || '',
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

	const elSelectItemsToAdd = elTabs.closest( `.${ className.elPopupItemsToSelect }` );

	const itemType = elTabType.dataset.type;
	const elTabLis = elTabs.querySelectorAll( '.tab' );
	elTabLis.forEach( ( elTabLi ) => {
		if ( elTabLi.classList.contains( 'active' ) ) {
			elTabLi.classList.remove( 'active' );
		}
	} );
	elTabType.classList.add( 'active' );

	const elLPTarget = elSelectItemsToAdd.querySelector( `${ className.LPTarget }` );

	const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
	dataSend.args.item_type = itemType;
	dataSend.args.paged = 1;

	window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

	// Show loading
	window.lpAJAXG.showHideLoading( elLPTarget, 1 );
	// End

	window.lpAJAXG.fetchAJAX( dataSend, {
		success: ( response ) => {
			const { message, status, data } = response;
			elLPTarget.innerHTML = data.content || '';
		},
		error: ( error ) => {
			console.log( error );
		},
		completed: () => {
			//console.log( 'completed' );
			window.lpAJAXG.showHideLoading( elLPTarget, 0 );
		},
	} );
};
const addItemsSelectedToSection = ( e, target ) => {
	const elBtnAddItems = target.closest( `${ className.elBtnAddItemsSelected }` );
	if ( ! elBtnAddItems ) {
		return;
	}

	const elSelectItemsToAdd = elBtnAddItems.closest( '.lp-select-items-to-add' );
	if ( ! elSelectItemsToAdd ) {
		return;
	}

	if ( itemsSelectedData.length === 0 ) {
		showToast( 'Please select at least one item to add.', 'error' );
		return;
	}

	dataSend.callback.method = 'handle_edit_course_curriculum';
	dataSend.args.action = 'add_items_to_section';
	dataSend.args.items = itemsSelectedData;
	dataSend.args.section_id = sectionIdSelected;

	// Show loading
	window.lpAJAXG.showHideLoading( elLPTarget, 1 );
	// End

	const elSection = document.querySelector( `.section[data-section-id="${ sectionIdSelected }"]` );
	const elItemClone = elSection.querySelector( `.${ className.elItemClone }` );

	itemsSelectedData.forEach( ( item ) => {
		const elItemNew = elItemClone.cloneNode( true );
		const elInputTitleNew = elItemNew.querySelector( `${ className.elItemTitleInput }` );
		const elSectionListItems = elSection.querySelector( `${ className.elSectionListItems }` );

		elItemNew.dataset.itemId = item.item_id;
		elItemNew.classList.add( item.item_type );
		elItemNew.classList.remove( `${ className.elItemClone }` );
		elInputTitleNew.value = item.item_title || '';
		lpUtils.lpSetLoadingEl( elItemNew, 1 );
		lpUtils.lpShowHideEl( elItemNew, 1 );
		elSectionListItems.insertAdjacentElement( 'beforeend', elItemNew );
	} );

	SweetAlert.close();

	window.lpAJAXG.fetchAJAX( dataSend, {
		success: ( response ) => {
			const { message, status, data } = response;
			showToast( message, status );

			if ( status === 'error' ) {
				itemsSelectedData.forEach( ( item ) => {
					const elItemAdded = elSection.querySelector( `.section-item[data-item-id="${ item.item_id }"]` );
					if ( elItemAdded ) {
						elItemAdded.remove();
					}
				} );
			}
		},
		error: ( error ) => {
			console.log( error );
		},
		completed: () => {
			window.lpAJAXG.showHideLoading( elLPTarget, 0 );

			itemsSelectedData.forEach( ( item ) => {
				const elItemAdded = elSection.querySelector( `.section-item[data-item-id="${ item.item_id }"]` );
				lpUtils.lpSetLoadingEl( elItemAdded, 0 );
			} );

			itemsSelectedData = []; // Clear selected items data
			updateCountItems( elSection );
		},
	} );
};
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
					const { content } = response.data;

					showToast( message, status );
				},
				error: ( error ) => {
					console.log( error );
				},
				completed: () => {
					lpUtils.lpSetLoadingEl( elSectionItem, 0 );
					elSectionItem.remove();
					updateCountItems( elSection );
				},
			};

			dataSend.callback.method = 'handle_edit_course_curriculum';
			dataSend.args.action = 'delete_item_from_section';
			dataSend.args.item_id = itemId;
			dataSend.args.section_id = sectionId;
			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
	} );
};
const showItemsChoice = ( e, target ) => {
	const elBtnCountItemsSelected = target.closest( '.lp-btn-count-items-selected' );
	if ( ! elBtnCountItemsSelected ) {
		return;
	}

	const elParent = elBtnCountItemsSelected.closest( '.lp-select-items-to-add' );
	const elBtnBack = elParent.querySelector( '.lp-btn-back-to-select-items' );
	const elTabs = elParent.querySelector( '.tabs' );
	const elListItemsWrap = elParent.querySelector( '.list-items-wrap' );
	const elHeaderItemsSelected = elParent.querySelector( '.header-count-items-selected' );
	const elListItemsSelected = elParent.querySelector( '.list-items-selected' );
	elHeaderItemsSelected.innerHTML = elBtnCountItemsSelected.innerHTML;

	lpUtils.lpShowHideEl( elListItemsWrap, 0 );
	lpUtils.lpShowHideEl( elBtnCountItemsSelected, 0 );
	lpUtils.lpShowHideEl( elTabs, 0 );
	lpUtils.lpShowHideEl( elBtnBack, 1 );
	lpUtils.lpShowHideEl( elHeaderItemsSelected, 1 );
	lpUtils.lpShowHideEl( elListItemsSelected, 1 );

	let htmlLis = '';
	itemsSelectedData.forEach( ( item ) => {
		htmlLis += `<li class="lp-remove-item-selected" data-id="${ item.item_id }" data-type="${ item.item_type }">
						<i class="dashicons dashicons-remove"></i>
						<span>${ item.item_title }</span>
						<span class="item-id">(#${ item.item_id } - ${ item.item_type })</span>
					</li>`;
	} );

	elListItemsSelected.innerHTML = htmlLis;
};
const backToSelectItems = ( e, target ) => {
	const elBtnBack = target.closest( '.lp-btn-back-to-select-items' );
	if ( ! elBtnBack ) {
		return;
	}

	const elParent = elBtnBack.closest( '.lp-select-items-to-add' );
	const elBtnCountItemsSelected = elParent.querySelector( '.lp-btn-count-items-selected' );
	const elTabs = elParent.querySelector( '.tabs' );
	const elListItemsWrap = elParent.querySelector( '.list-items-wrap' );
	const elHeaderItemsSelected = elParent.querySelector( '.header-count-items-selected' );
	const elListItemsSelected = elParent.querySelector( '.list-items-selected' );
	lpUtils.lpShowHideEl( elBtnCountItemsSelected, 1 );
	lpUtils.lpShowHideEl( elListItemsWrap, 1 );
	lpUtils.lpShowHideEl( elTabs, 1 );
	lpUtils.lpShowHideEl( elBtnBack, 0 );
	lpUtils.lpShowHideEl( elHeaderItemsSelected, 0 );
	lpUtils.lpShowHideEl( elListItemsSelected, 0 );
};
const removeItemSelected = ( e, target ) => {
	const elRemoveItemSelected = target.closest( '.lp-remove-item-selected' );
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
const watchItemsSelectedDataChange = () => {
	if ( ! elPopupSelectItems ) {
		return;
	}

	const elListItemsWrap = elPopupSelectItems.querySelector( '.list-items-wrap' );
	const elTarget = elListItemsWrap.querySelector( `${ className.LPTarget }` );

	// Set data for call AJAX
	const dataSet = window.lpAJAXG.getDataSetCurrent( elTarget );
	dataSet.args.item_selecting = itemsSelectedData;
	window.lpAJAXG.setDataSetCurrent( elTarget, dataSet );

	// Update count items selected, disable/enable buttons
	const elBtnAddItemsSelected = elPopupSelectItems.querySelector( '.lp-btn-add-items-selected' );
	const elBtnCountItemsSelected = elPopupSelectItems.querySelector( '.lp-btn-count-items-selected' );
	const elSpanCount = elBtnCountItemsSelected.querySelector( 'span' );
	const elHeaderCount = elPopupSelectItems.querySelector( '.header-count-items-selected' );
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

	const elListItems = elPopupSelectItems.querySelector( '.list-items' );
	const elInputs = elListItems.querySelectorAll( 'input[type="checkbox"]' );
	elInputs.forEach( ( elInputItem ) => {
		const itemSelected = {
			item_id: elInputItem.value,
			item_type: elInputItem.dataset.type || '',
			item_title: elInputItem.dataset.title || '',
		};
		const exists = itemsSelectedData.some( ( item ) => item.item_id === itemSelected.item_id );
		if ( exists ) {
			elInputItem.checked = true;
		} else {
			elInputItem.checked = false;
		}
	} );
};
// Sortable items, can drop on multiple sections
const sortAbleItem = () => {
	const elSectionListItems = elCurriculumSections.querySelectorAll( `${ className.elSectionListItems }` );
	let itemIdChoose = 0;
	let sectionIdChoose = 0;
	let sectionIdEnd = 0;
	let timeout;
	elSectionListItems.forEach( ( elSectionListItem ) => {
		new Sortable( elSectionListItem, {
			handle: '.drag',
			animation: 150,
			group: {
				name: 'shared',
			},
			onEnd: ( evt ) => {
				const dataSectionsItems = [];

				const itemDragged = evt.item;
				sectionIdEnd = evt.to.closest( `${ className.elSection }` ).dataset.sectionId;

				dataSend.callback.method = 'handle_edit_course_curriculum';
				if ( sectionIdChoose === sectionIdEnd ) {
					// Update items position in the same section
					dataSend.args.action = 'update_items_position';
					dataSend.args.section_id = sectionIdEnd;
				} else {
					// Update section id of item changed
					dataSend.args.action = 'update_item_section_and_position';
					dataSend.args.item_id_change = itemIdChoose;
					dataSend.args.section_id_new_of_item = sectionIdEnd;
					dataSend.args.section_id_old_of_item = sectionIdChoose;
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
				dataSend.args.items_position = dataSectionsItems;

				// Call ajax to update items position
				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						showToast( message, status );
					},
					error: ( error ) => {
						console.log( error );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( itemDragged, 0 );
					},
				};

				clearTimeout( timeout );
				timeout = setTimeout( () => {
					lpUtils.lpSetLoadingEl( itemDragged, 1 );
					window.lpAJAXG.fetchAJAX( dataSend, callBack );
				}, 1000 );
			},
			onMove: ( evt ) => {
				clearTimeout( timeout );
			},
			onChoose: ( evt ) => {
				const elChooseItem = evt.item;
				itemIdChoose = elChooseItem.dataset.itemId;
				sectionIdChoose = elChooseItem.closest( `${ className.elSection }` ).dataset.sectionId;
			},
			onUpdate: ( evt ) => {},
		} );
	} );
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

export {
	init,
	addItemType,
	cancelAddItemType,
	addItemToSection,
	showPopupItemsToSelect,
	chooseTabItemsType,
	changeTitle,
	sortAbleItem,
	updateTitle,
	cancelUpdateTitle,
	deleteItem,
};

