/**
 * Edit Section item Script on Curriculum
 *
 * @version 1.0.2
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

const idUrlHandle = 'edit-course-curriculum';

export class EditSectionItem {
	constructor() {
		this.courseId = null;
		this.elCurriculumSections = null;
		this.showToast = null;
		this.lpUtils = null;

		// runtime state
		this.itemsSelectedData = [];
		this.sectionIdSelected = null;
		this.elPopupSelectItems = null;
		this.timeSearchTitleItem = null;
		this.className = className;
	}

	init() {
		( {
			courseId: this.courseId,
			elCurriculumSections: this.elCurriculumSections,
			showToast: this.showToast,
			lpUtils: this.lpUtils,
		} = lpEditCurriculumShare );
	}

	/* Add item type */
	addItemType( args ) {
		const { e, target } = args;

		const elBtnSelectItemType = target;

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
		this.lpUtils.lpShowHideEl( elNewItemByType, 1 );
		elAddItemTypeInput.setAttribute( 'placeholder', itemPlaceholder );
		elAddItemTypeInput.dataset.itemType = itemType;
		elBtnAddItem.textContent = itemBtnAddText;
		elSectionActions.insertAdjacentElement( 'beforebegin', elNewItemByType );
		elAddItemTypeInput.focus();
	}

	/* Cancel add item type */
	cancelAddItemType( e, target ) {
		const elBtnAddItemCancel = target.closest( `${ className.elBtnAddItemCancel }` );
		if ( ! elBtnAddItemCancel ) {
			return;
		}

		const elAddItemType = target.closest( `${ className.elAddItemType }` );
		if ( elAddItemType ) {
			elAddItemType.remove();
		}
	}

	/* Add item to section */
	addItemToSection( args ) {
		const { e, target, callBackNest } = args;
		e.preventDefault();

		const elAddItemType = target.closest( `${ className.elAddItemType }` );
		const elSection = elAddItemType.closest( `${ className.elSection }` );
		const sectionId = elSection.dataset.sectionId;
		const elAddItemTypeTitleInput = elAddItemType.querySelector( `${ className.elAddItemTypeTitleInput }` );
		const titleValue = elAddItemTypeTitleInput.value.trim();
		const typeValue = elAddItemTypeTitleInput.dataset.itemType;
		const message = elAddItemTypeTitleInput.dataset.messEmptyTitle;

		if ( titleValue.length === 0 ) {
			this.showToast( message, 'error' );
			return;
		}

		// Clone new section item
		const elItemClone = elSection.querySelector( `${ className.elItemClone }` );
		const elItemNew = elItemClone.cloneNode( true );
		const elItemTitleInput = elItemNew.querySelector( `${ className.elItemTitleInput }` );

		elItemNew.classList.remove( 'clone' );
		elItemNew.classList.add( typeValue );
		elItemNew.dataset.itemType = typeValue;
		this.lpUtils.lpShowHideEl( elItemNew, 1 );
		this.lpUtils.lpSetLoadingEl( elItemNew, 1 );
		elItemTitleInput.value = titleValue;
		elItemTitleInput.dataset.old = titleValue;
		elItemClone.insertAdjacentElement( 'beforebegin', elItemNew );
		elAddItemType.remove();

		// Call ajax to add item to section
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				this.showToast( message, status );

				if ( status === 'error' ) {
					elItemNew.remove();
				} else if ( status === 'success' ) {
					const { section_item, item_link } = data || {};
					elItemNew.dataset.itemId = section_item.item_id || 0;
					elItemNew.querySelector( '.edit-link' ).setAttribute( 'href', item_link || '' );

					// Call callback nest if exists
					if ( callBackNest && typeof callBackNest.success === 'function' ) {
						args.elItemNew = elItemNew;
						callBackNest.success( args );
					}
				}
			},
			error: ( error ) => {
				this.showToast( error, 'error' );
				elItemNew.remove();
			},
			completed: () => {
				this.lpUtils.lpSetLoadingEl( elItemNew, 0 );
				this.updateCountItems( elSection );

				// Call callback nest if exists
				console.log( callBackNest.completed );
				if ( callBackNest && typeof callBackNest.completed === 'function' ) {
					args.elItemNew = elItemNew;
					callBackNest.completed( args );
				}
			},
		};

		const dataSend = {
			course_id: this.courseId,
			action: 'create_item_add_to_section',
			section_id: sectionId,
			item_title: titleValue,
			item_type: typeValue,
			args: { id_url: idUrlHandle },
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/* Typing in title input */
	changeTitle( e, target ) {
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
	}

	/* Focus in item title input */
	focusTitleInput( e, target, isFocus = true ) {
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
	}

	changeTitleAddNew( e, target ) {
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
	}

	/* Update item title */
	updateTitle( e, target ) {
		let canHandle = false;

		if ( target.closest( `${ className.elBtnUpdateItemTitle }` ) ) {
			canHandle = true;
		} else if ( target.closest( `${ className.elItemTitleInput }` ) && e.key === 'Enter' ) {
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
			this.showToast( message, 'error' );
			return;
		}

		if ( itemTitleValue === titleOld ) {
			return;
		}

		// Un-focus input item title
		elItemTitleInput.blur();
		// show loading
		this.lpUtils.lpSetLoadingEl( elSectionItem, 1 );
		// Call ajax to update item title
		const callBack = {
			success: ( response ) => {
				const { message, status } = response;

				if ( status === 'success' ) {
					elItemTitleInput.dataset.old = itemTitleValue;
				} else {
					elItemTitleInput.value = titleOld;
				}

				this.showToast( message, status );
			},
			error: ( error ) => {
				this.showToast( error, 'error' );
			},
			completed: () => {
				this.lpUtils.lpSetLoadingEl( elSectionItem, 0 );
				elSectionItem.classList.remove( 'editing' );
			},
		};

		const dataSend = {
			course_id: this.courseId,
			action: 'update_item_of_section',
			section_id: elSection.dataset.sectionId,
			item_id: itemId,
			item_type: itemType,
			item_title: itemTitleValue,
			args: { id_url: idUrlHandle },
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/* Cancel update item title */
	cancelUpdateTitle( e, target ) {
		const elBtnCancelUpdateTitle = target.closest( `${ className.elBtnCancelUpdateTitle }` );
		if ( ! elBtnCancelUpdateTitle ) {
			return;
		}

		const elSectionItem = elBtnCancelUpdateTitle.closest( `${ className.elSectionItem }` );
		const elItemTitleInput = elSectionItem.querySelector( `${ className.elItemTitleInput }` );
		elItemTitleInput.value = elItemTitleInput.dataset.old || '';
		elSectionItem.classList.remove( 'editing' );
	}

	/* Delete item from section */
	deleteItem( e, target ) {
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
				this.lpUtils.lpSetLoadingEl( elSectionItem, 1 );

				// Call ajax to delete item from section
				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						this.showToast( message, status );

						if ( status === 'success' ) {
							elSectionItem.remove();
						}
					},
					error: ( error ) => {
						this.showToast( error, 'error' );
					},
					completed: () => {
						this.lpUtils.lpSetLoadingEl( elSectionItem, 0 );
						this.updateCountItems( elSection );
					},
				};

				const dataSend = {
					course_id: this.courseId,
					action: 'delete_item_from_section',
					section_id: sectionId,
					item_id: itemId,
					args: { id_url: idUrlHandle },
				};
				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}
		} );
	}

	/* Sortable items, can drop on multiple sections */
	sortAbleItem() {
		const elSectionListItems = this.elCurriculumSections.querySelectorAll( `${ className.elSectionListItems }` );
		let itemIdChoose = 0;
		let elSectionChoose;
		let sectionIdChoose = 0;
		let sectionIdEnd = 0;
		let timeout;
		elSectionListItems.forEach( ( elItem ) => {
			new Sortable( elItem, {
				handle: '.drag',
				animation: 150,
				group: { name: 'shared' },
				onEnd: ( evt ) => {
					const dataSectionsItems = [];

					const elItemDragged = evt.item;
					sectionIdEnd = elItemDragged.closest( `${ className.elSection }` ).dataset.sectionId;

					const dataSend = { course_id: this.courseId, args: { id_url: idUrlHandle } };
					if ( sectionIdChoose === sectionIdEnd ) {
						dataSend.action = 'update_items_position';
						dataSend.section_id = sectionIdEnd;
					} else {
						dataSend.action = 'update_item_section_and_position';
						dataSend.item_id_change = itemIdChoose;
						dataSend.section_id_new_of_item = sectionIdEnd;
						dataSend.section_id_old_of_item = sectionIdChoose;
					}

					// Send list items position
					const section = this.elCurriculumSections.querySelector( `.section[data-section-id="${ sectionIdEnd }"]` );
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

							this.showToast( message, status );
						},
						error: ( error ) => {
							this.showToast( error, 'error' );
						},
						completed: () => {
							this.lpUtils.lpSetLoadingEl( elItemDragged, 0 );
							this.updateCountItems( section );
							if ( sectionIdChoose !== sectionIdEnd ) {
								this.updateCountItems( elSectionChoose );
							}
						},
					};

					this.lpUtils.lpSetLoadingEl( elItemDragged, 1 );
					window.lpAJAXG.fetchAJAX( dataSend, callBack );
				},
				onMove: ( /*evt*/ ) => {},
				onChoose: ( evt ) => {
					const elChooseItem = evt.item;
					itemIdChoose = elChooseItem.dataset.itemId;
					elSectionChoose = elChooseItem.closest( `${ className.elSection }` );
					sectionIdChoose = elSectionChoose.dataset.sectionId;
				},
				onUpdate: ( /*evt*/ ) => {},
			} );
		} );
	}

	/* Show popup items to select */
	showPopupItemsToSelect( e, target ) {
		const elBtnShowPopupItemsToSelect = target.closest( `${ className.elBtnShowPopupItemsToSelect }` );
		if ( ! elBtnShowPopupItemsToSelect ) {
			return;
		}

		const elSection = elBtnShowPopupItemsToSelect.closest( `${ className.elSection }` );
		this.sectionIdSelected = elSection.dataset.sectionId;

		const elPopupItemsToSelectClone = document.querySelector( `${ className.elPopupItemsToSelectClone }` );
		this.elPopupSelectItems = elPopupItemsToSelectClone.cloneNode( true );
		this.elPopupSelectItems.classList.remove( 'clone' );
		this.lpUtils.lpShowHideEl( this.elPopupSelectItems, 1 );

		SweetAlert.fire( {
			html: this.elPopupSelectItems,
			showConfirmButton: false,
			showCloseButton: true,
			width: '60%',
			customClass: {
				popup: 'lp-select-items-popup',
				htmlContainer: 'lp-select-items-html-container',
				container: 'lp-select-items-container',
			},
			willOpen: () => {
				const tabLesson = this.elPopupSelectItems.querySelector( 'li[data-type="lp_lesson"]' );
				if ( tabLesson ) {
					tabLesson.click();
				}
			},
		} ).then( ( /*result*/ ) => {} );
	}

	/* Choose tab items type */
	chooseTabItemsType( e, target ) {
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
		elTabLis.forEach( ( elTabLi ) => elTabLi.classList.remove( 'active' ) );
		elTabType.classList.add( 'active' );
		elInputSearch.value = '';

		const elLPTarget = elSelectItemsToAdd.querySelector( `${ className.LPTarget }` );

		const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		dataSend.args.item_type = itemType;
		dataSend.args.paged = 1;
		dataSend.args.item_selecting = this.itemsSelectedData || [];
		window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

		window.lpAJAXG.showHideLoading( elLPTarget, 1 );

		window.lpAJAXG.fetchAJAX( dataSend, {
			success: ( response ) => {
				const { data } = response;
				elLPTarget.innerHTML = data.content || '';
			},
			error: ( error ) => {
				this.showToast( error, 'error' );
			},
			completed: () => {
				window.lpAJAXG.showHideLoading( elLPTarget, 0 );
				this.watchItemsSelectedDataChange();
			},
		} );
	}

	/* Choose item from list */
	selectItemsFromList( e, target ) {
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
			const exists = this.itemsSelectedData.some( ( item ) => item.item_id === itemSelected.item_id );
			if ( ! exists ) {
				this.itemsSelectedData.push( itemSelected );
			}
		} else {
			const index = this.itemsSelectedData.findIndex( ( item ) => item.item_id === itemSelected.item_id );
			if ( index !== -1 ) {
				this.itemsSelectedData.splice( index, 1 );
			}
		}

		this.watchItemsSelectedDataChange();
	}

	/* Search title item */
	searchTitleItemToSelect( e, target ) {
		const elInputSearch = target.closest( '.lp-search-title-item' );
		if ( ! elInputSearch ) {
			return;
		}

		const elPopupItemsToSelect = elInputSearch.closest( `${ className.elPopupItemsToSelect }` );
		if ( ! elPopupItemsToSelect ) {
			return;
		}

		const elLPTarget = elPopupItemsToSelect.querySelector( `${ className.LPTarget }` );

		clearTimeout( this.timeSearchTitleItem );

		this.timeSearchTitleItem = setTimeout( () => {
			const dataSet = window.lpAJAXG.getDataSetCurrent( elLPTarget );
			dataSet.args.search_title = elInputSearch.value.trim();
			dataSet.args.item_selecting = this.itemsSelectedData;

			window.lpAJAXG.showHideLoading( elLPTarget, 1 );

			window.lpAJAXG.fetchAJAX( dataSet, {
				success: ( response ) => {
					const { data } = response;
					elLPTarget.innerHTML = data.content || '';
				},
				error: ( error ) => {
					this.showToast( error, 'error' );
				},
				completed: () => {
					window.lpAJAXG.showHideLoading( elLPTarget, 0 );
				},
			} );
		}, 1000 );
	}

	/* Show list of items selected */
	showItemsSelected( e, target ) {
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

		this.lpUtils.lpShowHideEl( elListItemsWrap, 0 );
		this.lpUtils.lpShowHideEl( elBtnCountItemsSelected, 0 );
		this.lpUtils.lpShowHideEl( elTabs, 0 );
		this.lpUtils.lpShowHideEl( elBtnBack, 1 );
		this.lpUtils.lpShowHideEl( elHeaderItemsSelected, 1 );
		this.lpUtils.lpShowHideEl( elListItemsSelected, 1 );

		elListItemsSelected.querySelectorAll( `${ className.elItemSelected }:not(.clone)` ).forEach( ( elItem ) => elItem.remove() );
		this.itemsSelectedData.forEach( ( item ) => {
			const elItemSelected = elItemClone.cloneNode( true );
			elItemSelected.classList.remove( 'clone' );
			elItemSelected.dataset.id = item.item_id;
			elItemSelected.dataset.type = item.item_type || '';

			elItemSelected.querySelector( '.item-title' ).textContent = item.item_title || '';
			elItemSelected.querySelector( '.item-id' ).textContent = item.item_id || '';
			elItemSelected.querySelector( '.item-type' ).textContent = item.item_type || '';

			this.lpUtils.lpShowHideEl( elItemSelected, 1 );

			elItemClone.insertAdjacentElement( 'beforebegin', elItemSelected );
		} );
	}

	/* Back to list of items */
	backToSelectItems( e, target ) {
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
		this.lpUtils.lpShowHideEl( elBtnCountItemsSelected, 1 );
		this.lpUtils.lpShowHideEl( elListItemsWrap, 1 );
		this.lpUtils.lpShowHideEl( elTabs, 1 );
		this.lpUtils.lpShowHideEl( elBtnBack, 0 );
		this.lpUtils.lpShowHideEl( elHeaderCountItemSelected, 0 );
		this.lpUtils.lpShowHideEl( elListItemsSelected, 0 );
	}

	/* Remove item selected from list items selected */
	removeItemSelected( e, target ) {
		const elRemoveItemSelected = target.closest( `${ className.elItemSelected }` );
		if ( ! elRemoveItemSelected ) {
			return;
		}

		const itemRemove = { item_id: elRemoveItemSelected.dataset.id, item_type: elRemoveItemSelected.dataset.type };
		const index = this.itemsSelectedData.findIndex( ( item ) => item.item_id === itemRemove.item_id );
		if ( index !== -1 ) {
			this.itemsSelectedData.splice( index, 1 );
		}

		elRemoveItemSelected.remove();

		this.watchItemsSelectedDataChange();
	}

	/* Watch items selected when data change */
	watchItemsSelectedDataChange() {
		if ( ! this.elPopupSelectItems ) {
			return;
		}

		const elBtnAddItemsSelected = this.elPopupSelectItems.querySelector( `${ className.elBtnAddItemsSelected }` );
		const elBtnCountItemsSelected = this.elPopupSelectItems.querySelector( `${ className.elBtnCountItemsSelected }` );
		const elSpanCount = elBtnCountItemsSelected.querySelector( 'span' );
		const elHeaderCount = this.elPopupSelectItems.querySelector( `${ className.elHeaderCountItemSelected }` );
		if ( this.itemsSelectedData.length !== 0 ) {
			elBtnCountItemsSelected.disabled = false;
			elBtnAddItemsSelected.disabled = false;
			elSpanCount.textContent = `(${ this.itemsSelectedData.length })`;
			elHeaderCount.innerHTML = elBtnCountItemsSelected.innerHTML;
		} else {
			elBtnCountItemsSelected.disabled = true;
			elBtnAddItemsSelected.disabled = true;
			elSpanCount.textContent = '';
			elHeaderCount.textContent = '';
		}

		const elListItems = this.elPopupSelectItems.querySelector( `${ className.elListItems }` );
		const elInputs = elListItems.querySelectorAll( 'input[type="checkbox"]' );
		elInputs.forEach( ( elInputItem ) => {
			const itemSelected = { item_id: elInputItem.value, item_type: elInputItem.dataset.type || '', item_title: elInputItem.dataset.title || '' };
			const exists = this.itemsSelectedData.some( ( item ) => item.item_id === itemSelected.item_id );
			elInputItem.checked = exists;
		} );
	}

	/* Add items selected to section */
	addItemsSelectedToSection( e, target ) {
		const elBtnAddItems = target.closest( `${ className.elBtnAddItemsSelected }` );
		if ( ! elBtnAddItems ) {
			return;
		}

		const elPopupItemsToSelect = elBtnAddItems.closest( `${ className.elPopupItemsToSelect }` );
		if ( ! elPopupItemsToSelect ) {
			return;
		}

		const elSection = document.querySelector( `.section[data-section-id="${ this.sectionIdSelected }"]` );
		const elItemClone = elSection.querySelector( `${ className.elItemClone }` );

		this.itemsSelectedData.forEach( ( item ) => {
			const elItemNew = elItemClone.cloneNode( true );
			const elInputTitleNew = elItemNew.querySelector( `${ className.elItemTitleInput }` );

			elItemNew.dataset.itemId = item.item_id;
			elItemNew.classList.add( item.item_type );
			elItemNew.classList.remove( 'clone' );
			elItemNew.dataset.itemType = item.item_type;
			elItemNew.querySelector( '.edit-link' ).setAttribute( 'href', item.item_edit_link || '' );
			elInputTitleNew.value = item.item_title || '';
			this.lpUtils.lpSetLoadingEl( elItemNew, 1 );
			this.lpUtils.lpShowHideEl( elItemNew, 1 );
			elItemClone.insertAdjacentElement( 'beforebegin', elItemNew );
		} );

		SweetAlert.close();

		const dataSend = {
			course_id: this.courseId,
			action: 'add_items_to_section',
			section_id: this.sectionIdSelected,
			items: this.itemsSelectedData,
			args: { id_url: idUrlHandle },
		};
		window.lpAJAXG.fetchAJAX( dataSend, {
			success: ( response ) => {
				const { message, status } = response;
				this.showToast( message, status );

				if ( status === 'error' ) {
					this.itemsSelectedData.forEach( ( item ) => {
						const elItemAdded = elSection.querySelector( `${ className.elSectionItem }[data-item-id="${ item.item_id }"]` );
						if ( elItemAdded ) {
							elItemAdded.remove();
						}
					} );
				}
			},
			error: ( error ) => {
				this.showToast( error, 'error' );
			},
			completed: () => {
				this.itemsSelectedData.forEach( ( item ) => {
					const elItemAdded = elSection.querySelector( `${ className.elSectionItem }[data-item-id="${ item.item_id }"]` );
					this.lpUtils.lpSetLoadingEl( elItemAdded, 0 );
				} );

				this.itemsSelectedData = [];
				this.updateCountItems( elSection );
			},
		} );
	}

	/* Enable/disable preview item */
	updatePreviewItem( e, target ) {
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

		this.lpUtils.lpSetLoadingEl( elSectionItem, 1 );

		// Call ajax to update item preview
		const callBack = {
			success: ( response ) => {
				const { message, status } = response;

				this.showToast( message, status );

				if ( status === 'error' ) {
					icon.classList.toggle( 'lp-icon-eye' );
					icon.classList.toggle( 'lp-icon-eye-slash' );
				}
			},
			error: ( error ) => {
				this.showToast( error, 'error' );
				icon.classList.toggle( 'lp-icon-eye' );
				icon.classList.toggle( 'lp-icon-eye-slash' );
			},
			completed: () => {
				this.lpUtils.lpSetLoadingEl( elSectionItem, 0 );
			},
		};

		const dataSend = {
			course_id: this.courseId,
			action: 'update_item_preview',
			item_id: itemId,
			item_type: itemType,
			enable_preview: enablePreview ? 1 : 0,
			args: { id_url: idUrlHandle },
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/* Update count items when item add/delete or section delete */
	updateCountItems( elSection ) {
		const elEditCurriculum = lpEditCurriculumShare.elEditCurriculum;
		const elCountItemsAll = elEditCurriculum.querySelector( '.total-items' );
		const elItemsAll = elEditCurriculum.querySelectorAll( `${ className.elSectionItem }:not(.clone)` );
		const itemsAllCount = elItemsAll.length;

		elCountItemsAll.dataset.count = itemsAllCount;
		elCountItemsAll.querySelector( '.count' ).textContent = itemsAllCount;

		// Count items in section
		const elSectionItemsCount = elSection.querySelector( '.section-items-counts' );

		const elItems = elSection.querySelectorAll( `${ className.elSectionItem }:not(.clone)` );
		const itemsCount = elItems.length;

		elSectionItemsCount.dataset.count = itemsCount;
		elSectionItemsCount.querySelector( '.count' ).textContent = itemsCount;
	}
}
