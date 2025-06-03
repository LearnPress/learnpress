/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */

import * as lpUtils from '../utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import Sortable from 'sortablejs';
import SweetAlert from 'sweetalert2';
import * as lpEditCurriculumShare from './edit-curriculum/share.js';
import * as sectionEdit from './edit-curriculum/edit-section.js';

const idElEditCurriculum = '#lp-course-edit-curriculum';
let elEditCurriculum;
let elCurriculumSections;
let elLPTarget;
let elNewSectionItem;
let dataSend;
let elPopupSelectItems;

/**
 * Select item type to add to section
 * @param e
 * @param target
 */
const selectItemTypeToAddSection = ( e, target ) => {
	const elBtnSelectItem = target.closest( `${ className.btnSelectItemType }` );
	if ( ! elBtnSelectItem ) {
		return;
	}

	const itemType = elBtnSelectItem.dataset.itemType;
	const itemPlaceholder = elBtnSelectItem.dataset.placeholder;
	const itemBtnAddText = elBtnSelectItem.dataset.buttonAddText;

	// Insert input item type to add
	const elSection = elBtnSelectItem.closest( '.section' );
	const elSectionActions = elSection.querySelector( `${ className.elSectionActions }` );
	const elNewSectionItem = elSectionActions.querySelector( `${ className.elNewSectionItem }` );
	const elSectionItems = elSection.querySelector( `${ className.elSectionListItems }` );
	const elNewSectionItemClone = elNewSectionItem.cloneNode( true );
	const elNewSectionItemCloneType = elNewSectionItemClone.querySelector( '.item-type' );
	const elNewItemInput = elNewSectionItemClone.querySelector( `${ className.elItemNewInput }` );

	lpUtils.lpShowHideEl( elNewSectionItemClone, 1 );
	elNewSectionItemCloneType.classList.add( itemType );

	elNewItemInput.setAttribute( 'placeholder', itemPlaceholder );
	elNewItemInput.dataset.itemType = itemType;

	const elBtnAddItem = elNewSectionItemClone.querySelector( `.${ className.btnAddItem }` );
	elBtnAddItem.textContent = itemBtnAddText;
	elSectionItems.insertAdjacentElement( 'beforeend', elNewSectionItemClone );
};
/**
 * Add item to section
 * @param e
 * @param target
 */
const addItemToSection = ( e, target ) => {
	const elNewSectionItem = target.closest( `${ className.elNewSectionItem }` );
	if ( ! elNewSectionItem ) {
		return;
	}

	e.preventDefault();

	const elSection = elNewSectionItem.closest( `${ className.elSection }` );
	const sectionId = elSection.dataset.sectionId;
	const elItemNewInput = elNewSectionItem.querySelector( `${ className.elItemNewInput }` );
	const titleItemValue = elItemNewInput.value.trim();
	const typeItemValue = elItemNewInput.dataset.itemType;
	const message = elItemNewInput.dataset.messEmptyTitle;

	if ( titleItemValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	// Clone new section item
	const elItemClone = elSection.querySelector( `.${ className.elItemClone }` );
	const elItemNew = elItemClone.cloneNode( true );
	const elItemCloneInput = elItemNew.querySelector( `${ className.elItemTitleInput }` );

	lpUtils.lpShowHideEl( elItemNew, 1 );
	elItemCloneInput.value = titleItemValue;
	elItemNew.classList.add( typeItemValue );
	lpUtils.lpSetLoadingEl( elItemNew, 1 );
	elItemClone.insertAdjacentElement( 'beforebegin', elItemNew );
	elNewSectionItem.remove();

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
			elItemNew.classList.remove( `${ className.elItemClone }` );
			lpUtils.lpSetLoadingEl( elItemNew, 0 );
			updateCountItems( elSection );
		},
	};

	dataSend.callback.method = 'handle_edit_course_curriculum';
	dataSend.args.section_id = sectionId;
	dataSend.args.action = 'add_item_to_section';
	dataSend.args.item_title = titleItemValue;
	dataSend.args.item_type = typeItemValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
const updateItemTitle = ( e, target ) => {
	const elInputItemTitle = target.closest( 'input[name="item-title-input"]' );
	if ( ! elInputItemTitle ) {
		return;
	}

	const elSectionItem = elInputItemTitle.closest( '.section-item' );
	if ( ! elSectionItem ) {
		return;
	}

	const itemId = elSectionItem.dataset.itemId;
	const itemType = elSectionItem.dataset.itemType;
	const itemTitleValue = elInputItemTitle.value.trim();
	const message = elInputItemTitle.dataset.messEmptyTitle;
	if ( itemTitleValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	// Un-focus input item title
	elInputItemTitle.blur();
	// show loading
	lpUtils.lpSetLoadingEl( elSectionItem, 1 );
	// Call ajax to update item title
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
		},
	};

	dataSend.args.action = 'update_item';
	dataSend.args.item_id = itemId;
	dataSend.args.item_type = itemType;
	dataSend.args.item_title = itemTitleValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
let sectionIdSelected;
const showSelectItemFromList = ( e, target ) => {
	const elBtnSelectItems = target.closest( `.${ className.elBtnSelectItems }` );
	if ( ! elBtnSelectItems ) {
		return;
	}

	const elSection = elBtnSelectItems.closest( '.section' );
	sectionIdSelected = elSection.dataset.sectionId;

	const elSelectItems = document.querySelector( '.lp-select-items-to-add' );
	elPopupSelectItems = elSelectItems.cloneNode( true );
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
const chooseItemType = ( e, target ) => {
	const elTabType = target.closest( '.tab' );
	if ( ! elTabType ) {
		return;
	}
	e.preventDefault();

	const elTabs = elTabType.closest( '.tabs' );
	if ( ! elTabs ) {
		return;
	}

	const elSelectItemsToAdd = elTabs.closest( '.lp-select-items-to-add' );
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
const deleteItemFromSection = ( e, target ) => {
	const elBtnDeleteItem = target.closest( '.lp-btn-delete-item-from-section' );
	if ( ! elBtnDeleteItem ) {
		return;
	}

	const elSectionItem = elBtnDeleteItem.closest( '.section-item' );
	if ( ! elSectionItem ) {
		return;
	}

	const itemId = elSectionItem.dataset.itemId;

	const elSection = elSectionItem.closest( '.section' );
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
let timeout;
const sortAbleSection = () => {
	let isUpdateSectionPosition = 0;

	new Sortable( elCurriculumSections, {
		handle: '.drag',
		animation: 150,
		onEnd: ( evt ) => {
			const target = evt.item;
			if ( ! isUpdateSectionPosition ) {
				// No change in section position, do nothing
				return;
			}

			const elSection = target.closest( `${ className.elSection }` );
			const elSections = elCurriculumSections.querySelectorAll( `${ className.elSection }` );
			const sectionIds = [];

			elSections.forEach( ( elSection, index ) => {
				const sectionId = elSection.dataset.sectionId;
				sectionIds.push( sectionId );
			} );

			// Call ajax to update section position
			const callBack = {
				success: ( response ) => {
					const { message, status } = response;
					const { content } = response.data;

					showToast( message, status );
				},
				error: ( error ) => {
					showToast( error, 'error' );
				},
				completed: () => {
					lpUtils.lpSetLoadingEl( elSection, 0 );
					isUpdateSectionPosition = 0;
				},
			};

			dataSend.callback.method = 'handle_edit_course_curriculum';
			dataSend.args.action = 'update_section_position';
			dataSend.args.new_position = sectionIds;

			clearTimeout( timeout );
			timeout = setTimeout( () => {
				lpUtils.lpSetLoadingEl( elSection, 1 );
				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}, 1000 );
		},
		onMove: ( evt ) => {
			clearTimeout( timeout );
		},
		onUpdate: ( evt ) => {
			isUpdateSectionPosition = 1;
		},
	} );
};
const sortAbleItem = () => {
	const elSectionListItems = elCurriculumSections.querySelectorAll( '.section-list-items' );
	let itemIdChoose = 0;
	let sectionIdChoose = 0;
	let sectionIdEnd = 0;
	elSectionListItems.forEach( ( elSectionListItem ) => {
		new Sortable( elSectionListItem, {
			handle: '.drag',
			animation: 150,
			group: {
				name: 'shared',
			},
			onEnd: ( evt ) => {
				const dataSectionsItems = [];

				sectionIdEnd = evt.to.closest( '.section' ).dataset.sectionId;

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
				const items = section.querySelectorAll( '.section-item' );
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
						const { content } = response.data;

						showToast( message, status );
					},
					error: ( error ) => {
						console.log( error );
					},
					completed: () => {
						//console.log( 'completed' );
						lpUtils.lpSetLoadingEl( elSectionListItem, 0 );
					},
				};

				clearTimeout( timeout );
				timeout = setTimeout( () => {
					lpUtils.lpSetLoadingEl( elSectionListItem, 1 );
					window.lpAJAXG.fetchAJAX( dataSend, callBack );
				}, 1000 );
			},
			onMove: ( evt ) => {
				clearTimeout( timeout );
			},
			onChoose: ( evt ) => {
				const elChooseItem = evt.item;
				itemIdChoose = elChooseItem.dataset.itemId;
				sectionIdChoose = elChooseItem.closest( '.section' ).dataset.sectionId;
			},
			onUpdate: ( evt ) => {},
		} );
	} );
};
const highlightItem = ( e, target ) => {
	elCurriculumSections.querySelectorAll( '.section-item' ).forEach( ( elSectionItem ) => {
		if ( elSectionItem.classList.contains( 'editing' ) ) {
			elSectionItem.classList.remove( 'editing' );
		}
	} );

	if ( target.closest( 'input[ name = "item-title-input" ]' ) ) {
		target.closest( '.section-item' ).classList.add( 'editing' );
	}
};
const showToast = ( message, status = 'success' ) => {
	const toastify = new Toastify( {
		...argsToastify,
		text: message,
		className: `${ lpDataAdmin.toast.classPrefix } ${ status }`,
	} );
	toastify.showToast();
};
const toggleSection = ( e, target ) => {
	const elSectionToggle = target.closest( '.section-toggle' );
	if ( ! elSectionToggle ) {
		return;
	}

	const elSection = elSectionToggle.closest( `${ className.elSection }` );

	const elCurriculum = elSection.closest( `${ idElEditCurriculum }` );
	if ( ! elCurriculum ) {
		return;
	}

	// Toggle section
	elSection.classList.toggle( 'lp-collapse' );

	// Check all sections collapsed
	checkAllSectionsCollapsed( elCurriculum );
};
const toggleSectionAll = ( e, target ) => {
	const elToggleAllSections = target.closest( `${ className.elToggleAllSections }` );
	if ( ! elToggleAllSections ) {
		return;
	}

	const elCurriculum = elToggleAllSections.closest( `${ idElEditCurriculum }` );
	const elSections = elCurriculum.querySelectorAll( `${ className.elSection }` );

	elToggleAllSections.classList.toggle( 'lp-collapse' );

	if ( elToggleAllSections.classList.contains( 'lp-collapse' ) ) {
		elSections.forEach( ( el ) => {
			if ( ! el.classList.contains( 'lp-collapse' ) ) {
				el.classList.add( 'lp-collapse' );
			}
		} );
	} else {
		elSections.forEach( ( el ) => {
			if ( el.classList.contains( 'lp-collapse' ) ) {
				el.classList.remove( 'lp-collapse' );
			}
		} );
	}
};

const updateCountItems = ( elSection ) => {
	elEditCurriculum = elCurriculumSections.closest( `${ idElEditCurriculum }` );
	const elCountItemsAll = elEditCurriculum.querySelector( '.total-items' );
	const elItemsAll = elCurriculumSections.querySelectorAll( `${ className.elSectionItem }:not(.${ className.elItemClone })` );
	elCountItemsAll.innerHTML = elItemsAll.length;

	// Count items in section
	const elCountItems = elSection.querySelector( '.section-items-counts' );
	const elItems = elSection.querySelectorAll( `${ className.elSectionItem }:not(.${ className.elItemClone })` );
	elCountItems.textContent = elItems.length;

	console.log( elItemsAll.length, elItems.length );
};

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	// Add new section
	sectionEdit.addSection( e, target );

	// Collapse/Expand section
	sectionEdit.toggleSection( e, target );

	// Click button to update section description
	sectionEdit.updateSectionDescription( e, target );

	// Cancel update section description
	sectionEdit.cancelSectionDescription( e, target );

	// Delete section
	sectionEdit.deleteSection( e, target );

	// Update section title
	sectionEdit.updateSectionTitle( e, target );

	// Cancel update section title
	sectionEdit.cancelSectionTitle( e, target );
	return;

	// Select items from list to add to section
	showSelectItemFromList( e, target );

	// Choose item type
	chooseItemType( e, target );

	// Select items from list
	selectItemsFromList( e, target );

	// Add items selected to section
	addItemsSelectedToSection( e, target );

	// Delete item from section
	deleteItemFromSection( e, target );

	// Show items choice
	showItemsChoice( e, target );

	// Back to select items
	backToSelectItems( e, target );

	// Remove item selected
	removeItemSelected( e, target );

	// Collapse/Expand all sections
	toggleSectionAll( e, target );

	// Select item type to add
	selectItemTypeToAddSection( e, target );
	// Add item to section
	if ( target.classList.contains( `${ className.btnAddItem }` ) ) {
		addItemToSection( e, target );
	}

	// Cancel add item to section
	if ( target.classList.contains( 'lp-btn-add-item-cancel' ) ) {
		const elNewSectionItem = target.closest( `${ className.elNewSectionItem }` );
		if ( elNewSectionItem ) {
			elNewSectionItem.remove();
		}
	}

	// highlight section
	highlightItem( e, target );
} );

document.addEventListener( 'submit', ( e ) => {
	const target = e.target;
} );

document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
	// Event enter
	if ( e.key === 'Enter' ) {
		// e.preventDefault();
		sectionEdit.addSection( e, target );
		sectionEdit.updateSectionTitle( e, target );
		sectionEdit.updateSectionDescription( e, target );
		// updateSectionTitle( e, target );
		// updateItemTitle( e, target );
	}
} );

document.addEventListener( 'keyup', ( e ) => {
	const target = e.target;
	sectionEdit.changeTitleDescription( e, target );
	sectionEdit.changeSectionDescription( e, target );
} );

// Element root ready.
lpUtils.lpOnElementReady( `${ idElEditCurriculum }`, ( elEditCurriculum ) => {
	const { className } = lpEditCurriculumShare;

	elCurriculumSections = elEditCurriculum.querySelector( `${ className.elCurriculumSections }` );
	elLPTarget = elEditCurriculum.closest( `${ className.LPTarget }` );
	dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );

	lpEditCurriculumShare.setVariables(
		{
			elEditCurriculum,
			elCurriculumSections,
			elLPTarget,
			dataSend,
		}
	);

	// Set variables use for section edit
	sectionEdit.init();

	// checkAllSectionsCollapsed( elEditCurriculum );
	//
	// sortAbleSection();
	// sortAbleItem();
} );
