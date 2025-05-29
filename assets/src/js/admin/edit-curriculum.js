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

let elLPTarget;
let elSectionCreating;
let elNewSectionItem;
let dataSend;
let elCurriculumSections;
let elStatusChange;
let elPopupSelectItems;

const argsToastify = {
	text: '',
	gravity: lpDataAdmin.toast.gravity, // `top` or `bottom`
	position: lpDataAdmin.toast.position, // `left`, `center` or `right`
	className: `${ lpDataAdmin.toast.classPrefix }`,
	close: lpDataAdmin.toast.close == 1,
	stopOnFocus: lpDataAdmin.toast.stopOnFocus == 1,
	duration: lpDataAdmin.toast.duration,
};

const className = {
	btnNewSection: 'lp-btn-add-section',
	elBtnDeleteSection: 'lp-btn-delete-section',
	elBtnSelectItems: 'lp-btn-select-items',
	btnSelectItemType: 'lp-btn-select-item-type',
	btnAddItem: 'lp-btn-add-item',
	elNewSectionItem: 'new-section-item',
	elItemClone: '.section-item-clone',
	elSectionListItems: 'section-list-items',
	elSectionDesInput: 'section-description-input',
	LPTarget: '.lp-target',
};

// Add new section
const addSection = ( e, target ) => {
	const elAddNewSection = target.closest( '.add-new-section' );
	if ( ! elAddNewSection ) {
		return;
	}

	e.preventDefault();

	const elInputTitleSection = elAddNewSection.querySelector( 'input[name="new_section"]' );
	const titleSectionValue = elInputTitleSection.value.trim();
	const message = elInputTitleSection.dataset.messEmptyTitle;
	if ( titleSectionValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	// Add and set data for new section
	const newSection = elSectionCreating.cloneNode( true );
	newSection.classList.add( 'empty-section' );
	const titleNewSection = newSection.querySelector( 'input[name="section-title-input"]' );
	titleNewSection.value = titleSectionValue;
	elAddNewSection.insertAdjacentElement( 'beforebegin', newSection );
	// End

	// Call ajax to add new section
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
			newSection.classList.remove( 'empty-section' );
		},
	};

	dataSend.args.action = 'add_section';
	dataSend.args.title = titleSectionValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
const deleteSection = ( e, target ) => {
	const elBtnDeleteSection = target.closest( `.${ className.elBtnDeleteSection }` );
	if ( ! elBtnDeleteSection ) {
		return;
	}

	SweetAlert.fire( {
		title: elBtnDeleteSection.dataset.title,
		text: elBtnDeleteSection.dataset.content,
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		cancelButtonText: lpDataAdmin.i18n.cancel,
		confirmButtonText: lpDataAdmin.i18n.yes,
		reverseButtons: true,
	} ).then( ( result ) => {
		if ( result.isConfirmed ) {
			const elSection = elBtnDeleteSection.closest( '.section' );
			const sectionId = elSection.dataset.sectionId;

			// Call ajax to delete section
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
					elSection.remove();
				},
			};

			dataSend.args.action = 'delete_section';
			dataSend.args.section_id = sectionId;
			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
	} );
};
/**
 * Select item type to add to section
 * @param e
 * @param target
 */
const selectItemToAddSection = ( e, target ) => {
	const elBtnSelectItem = target.closest( `.${ className.btnSelectItemType }` );
	if ( ! elBtnSelectItem ) {
		return;
	}

	const itemType = elBtnSelectItem.dataset.itemType;
	const itemPlaceholder = elBtnSelectItem.dataset.placeholder;
	const itemBtnAddText = elBtnSelectItem.dataset.buttonAddText;

	// Insert input item type to add
	const elSection = elBtnSelectItem.closest( '.section' );
	const elSectionItems = elSection.querySelector( '.section-list-items' );
	const elNewSectionItemClone = elNewSectionItem.cloneNode( true );
	lpUtils.lpShowHideEl( elNewSectionItemClone, 1 );
	elNewSectionItemClone.dataset.itemType = itemType;
	const elLabelItemType = elNewSectionItemClone.querySelector( 'label' );
	elLabelItemType.classList.add( itemType );
	const elInputItemType = elNewSectionItemClone.querySelector( 'input[name="new_item"]' );
	elInputItemType.setAttribute( 'placeholder', itemPlaceholder );
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
	const elNewSectionItem = target.closest( `.${ className.elNewSectionItem }` );
	if ( ! elNewSectionItem ) {
		return;
	}

	e.preventDefault();

	const elSection = elNewSectionItem.closest( '.section' );
	const sectionId = elSection.dataset.sectionId;
	const elInputTitleItem = elNewSectionItem.querySelector( 'input[name="new_item"]' );
	const titleItemValue = elInputTitleItem.value.trim();
	const typeItemValue = elNewSectionItem.dataset.itemType;
	const message = elInputTitleItem.dataset.messEmptyTitle;

	if ( titleItemValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	// Clone new section item
	const elItemCloneEx = elSection.querySelector( `${ className.elItemClone }` );
	const elItemClone = elItemCloneEx.cloneNode( true );
	elItemClone.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( elItemClone, 1 );
	elItemClone.querySelector( 'input[name="item-title-input"]' ).value = titleItemValue;
	elItemClone.classList.add( typeItemValue );
	elNewSectionItem.insertAdjacentElement( 'beforebegin', elItemClone );
	elNewSectionItem.remove();

	// Call ajax to add item to section
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;
			const { content } = response.data;

			showToast( message, status );

			if ( status === 'error' ) {
				elItemClone.remove();
			}
		},
		error: ( error ) => {
			console.log( error );
		},
		completed: () => {
			elItemClone.classList.remove( 'empty-item' );
		},
	};

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

	lpUtils.lpSetLoadingEl( elStatusChange, 1 );
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
			lpUtils.lpSetLoadingEl( elStatusChange, 0 );
		},
	};

	dataSend.args.action = 'update_item';
	dataSend.args.item_id = itemId;
	dataSend.args.item_type = itemType;
	dataSend.args.item_title = itemTitleValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
const changeItemTitle = ( e, target ) => {

};
const updateSectionDescription = ( e, target ) => {
	const elSectionDesInput = target.closest( `.${ className.elSectionDesInput }` );
	if ( ! elSectionDesInput ) {
		return;
	}

	const elSection = elSectionDesInput.closest( '.section' );
	const sectionId = elSection.dataset.sectionId;
	const sectionDesValue = elSectionDesInput.value.trim();
	const message = elSectionDesInput.dataset.messEmptyDescription;
	if ( sectionDesValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	// Call ajax to update section description
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
		},
	};

	dataSend.args.section_id = sectionId;
	dataSend.args.action = 'update_section';
	dataSend.args.section_description = sectionDesValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
const updateSectionTitle = ( e, target ) => {
	const elSectionTitleInput = target.closest( 'input[name="section-title-input"]' );
	if ( ! elSectionTitleInput ) {
		return;
	}

	const elSection = elSectionTitleInput.closest( '.section' );
	const sectionId = elSection.dataset.sectionId;
	const sectionTitleValue = elSectionTitleInput.value.trim();
	const message = elSectionTitleInput.dataset.messEmptyTitle;
	if ( sectionTitleValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	lpUtils.lpSetLoadingEl( elStatusChange, 1 );

	// Call ajax to update section title
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
			lpUtils.lpSetLoadingEl( elStatusChange, 0 );
		},
	};

	dataSend.args.section_id = sectionId;
	dataSend.args.action = 'update_section';
	dataSend.args.section_name = sectionTitleValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
const toggleSection = ( e, target ) => {
	const elBtnCollapse = target.closest( '.collapse' );
	if ( ! elBtnCollapse ) {
		return;
	}

	const elSection = elBtnCollapse.closest( '.section' );
	const elSectionCollapse = elSection.querySelector( '.section-collapse' );

	if ( elSection.classList.contains( 'open' ) ) {
		elSection.classList.remove( 'open' );
		elSection.classList.add( 'close' );
		lpUtils.lpShowHideEl( elSectionCollapse, 0 );
	} else {
		elSection.classList.remove( 'close' );
		elSection.classList.add( 'open' );
		lpUtils.lpShowHideEl( elSectionCollapse, 1 );
	}
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
	const elBtnAddItems = target.closest( '.lp-btn-add-items-selected' );
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

	dataSend.args.action = 'add_items_to_section';
	dataSend.args.items = itemsSelectedData;
	dataSend.args.section_id = sectionIdSelected;

	// Show loading
	window.lpAJAXG.showHideLoading( elLPTarget, 1 );
	// End

	const elSection = document.querySelector( `.section[data-section-id="${ sectionIdSelected }"]` );
	const elItemEmpty = elSection.querySelector( '.empty-item' );

	itemsSelectedData.forEach( ( item ) => {
		const elItemClone = elItemEmpty.cloneNode( true );
		elItemClone.setAttribute( 'data-item-id', item.item_id );
		const elInputTitleClone = elItemClone.querySelector( 'input[name="item-title-input"]' );
		elInputTitleClone.value = item.item_title || '';
		lpUtils.lpShowHideEl( elItemClone, 1 );
		elItemEmpty.insertAdjacentElement( 'beforebegin', elItemClone );
	} );

	SweetAlert.close();

	window.lpAJAXG.fetchAJAX( dataSend, {
		success: ( response ) => {
			const { message, status, data } = response;
			showToast( message, status );
		},
		error: ( error ) => {
			console.log( error );
		},
		completed: () => {
			window.lpAJAXG.showHideLoading( elLPTarget, 0 );

			itemsSelectedData.forEach( ( item ) => {
				const elItemAdded = elSection.querySelector( `.section-item[data-item-id="${ item.item_id }"]` );
				elItemAdded.classList.remove( 'empty-item' );
				elItemAdded.classList.add( item.item_type );
			} );

			itemsSelectedData = []; // Clear selected items data
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
			elSectionItem.classList.add( 'empty-item' );

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
					elSectionItem.remove();
				},
			};

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
const sortAbleSection = ( elCurriculumSections ) => {
	let isUpdateSectionPosition = 0;

	new Sortable( elCurriculumSections, {
		handle: '.movable',
		animation: 150,
		onEnd: ( evt ) => {
			if ( ! isUpdateSectionPosition ) {
				// No change in section position, do nothing
				return;
			}

			const elSections = elCurriculumSections.querySelectorAll( '.section' );
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
					console.log( error );
				},
				completed: () => {
					//console.log( 'completed' );
					lpUtils.lpSetLoadingEl( elStatusChange, 0 );
					isUpdateSectionPosition = 0;
				},
			};

			dataSend.args.action = 'update_section_position';
			dataSend.args.new_position = sectionIds;

			clearTimeout( timeout );
			timeout = setTimeout( () => {
				lpUtils.lpSetLoadingEl( elStatusChange, 1 );
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
const sortAbleItem = ( elCurriculumSections ) => {
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
						lpUtils.lpSetLoadingEl( elStatusChange, 0 );
					},
				};

				clearTimeout( timeout );
				timeout = setTimeout( () => {
					lpUtils.lpSetLoadingEl( elStatusChange, 1 );
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
const showToast = ( message, status = 'success' ) => {
	const toastify = new Toastify( {
		...argsToastify,
		text: message,
		className: `${ lpDataAdmin.toast.classPrefix } ${ status }`,
	} );
	toastify.showToast();
};

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	if ( target.classList.contains( `${ className.btnNewSection }` ) ) {
		addSection( e, target );
	}

	// Delete section
	deleteSection( e, target );

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

	// Collapse/Expand section
	toggleSection( e, target );
	// Select item type to add
	selectItemToAddSection( e, target );
	// Add item to section
	if ( target.classList.contains( `${ className.btnAddItem }` ) ) {
		addItemToSection( e, target );
	}

	// Cancel add item to section
	if ( target.classList.contains( 'lp-btn-add-item-cancel' ) ) {
		const elNewSectionItem = target.closest( `.${ className.elNewSectionItem }` );
		if ( elNewSectionItem ) {
			elNewSectionItem.remove();
		}
	}

	// collapse section
	if ( target.closest( '.collapse-sections' ) ) {
		elCurriculumSections.querySelectorAll( '.section' ).forEach( ( elSection ) => {
			if ( elSection.classList.contains( 'open' ) ) {
				elSection.classList.remove( 'open' );
				elSection.classList.add( 'close' );
				lpUtils.lpShowHideEl( elSection.querySelector( '.section-collapse' ), 0 );
			}
		} );
	}
} );

document.addEventListener( 'submit', ( e ) => {
	const target = e.target;
} );

document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
	// Event enter
	if ( e.key === 'Enter' ) {
		addSection( e, target );
		addItemToSection( e, target );
		updateSectionDescription( e, target );
		updateSectionTitle( e, target );
		updateItemTitle( e, target );

		e.preventDefault();
	}
} );

document.addEventListener( 'focus', ( e ) => {
	console.log( 'focus', e.target );
} );

// Element root ready.
lpUtils.lpOnElementReady( '#admin-editor-lp_course', ( elAdminEditor ) => {
	elLPTarget = elAdminEditor.closest( `${ className.LPTarget }` );
	dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );

	elSectionCreating = elAdminEditor.querySelector( '.section' );
	elNewSectionItem = elAdminEditor.querySelector( `.${ className.elNewSectionItem }` );
	elCurriculumSections = elAdminEditor.querySelector( '.curriculum-sections' );
	elStatusChange = elAdminEditor.querySelector( '.status' );

	sortAbleSection( elCurriculumSections );
	sortAbleItem( elCurriculumSections );
} );
