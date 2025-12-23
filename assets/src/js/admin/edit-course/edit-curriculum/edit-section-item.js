/**
 * Edit Section item Script on Curriculum
 *
 * @version 1.0.3
 * @since 4.2.8.6
 */
import SweetAlert from 'sweetalert2';
import Sortable from 'sortablejs';
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { EditCourseCurriculum } from '../edit-curriculum.js';
import { EditSection } from './edit-section.js';
import { LpPopupSelectItemToAdd } from 'lpAssetsJsPath/lpPopupSelectItemToAdd.js';

const idUrlHandle = 'edit-course-curriculum';

const lpPopupSelectItemToAdd = new LpPopupSelectItemToAdd();

export class EditSectionItem {
	constructor() {
		this.courseId = null;
		this.elCurriculumSections = null;
		this.sectionIdSelected = null;
	}

	static selectors = {
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
		elBtnSetPreviewItem: '.lp-btn-set-preview-item',
	};

	init() {
		this.elEditCurriculum = document.querySelector(
			`${ EditCourseCurriculum.selectors.idElEditCurriculum }`
		);
		this.elCurriculumSections = this.elEditCurriculum.querySelector(
			`${ EditCourseCurriculum.selectors.elCurriculumSections }`
		);
		const elLPTarget = this.elEditCurriculum.closest(
			`${ EditCourseCurriculum.selectors.LPTarget }`
		);
		const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		this.courseId = dataSend.args.course_id;

		this.events();
		this.sortAbleItem();
		lpPopupSelectItemToAdd.init();
	}

	/* Events */
	events() {
		// Check and attach events only once
		if ( EditSectionItem._loadedEvents ) {
			return;
		}

		EditSectionItem._loadedEvents = this;

		// Click events
		lpUtils.eventHandlers( 'click', [
			{
				selector: EditSectionItem.selectors.elBtnSelectItemType,
				class: this,
				callBack: this.addItemType.name,
			},
			{
				selector: EditSectionItem.selectors.elBtnAddItem,
				class: this,
				callBack: this.addItemToSection.name,
			},
			{
				selector: EditSectionItem.selectors.elBtnAddItemCancel,
				class: this,
				callBack: this.cancelAddItemType.name,
			},
			{
				selector: EditSectionItem.selectors.elBtnUpdateItemTitle,
				class: this,
				callBack: this.updateTitle.name,
			},
			{
				selector: EditSectionItem.selectors.elBtnCancelUpdateTitle,
				class: this,
				callBack: this.cancelUpdateTitle.name,
			},
			{
				selector: EditSectionItem.selectors.elBtnDeleteItem,
				class: this,
				callBack: this.deleteItem.name,
			},
			{
				selector:
					LpPopupSelectItemToAdd.selectors
						.elBtnShowPopupItemsToSelect,
				callBack: ( args ) => {
					const { e, target } = args;
					const elSection = target.closest(
						EditSection.selectors.elSection
					);
					this.sectionIdSelected = elSection.dataset.sectionId;
				},
			},
			{
				selector:
					LpPopupSelectItemToAdd.selectors.elBtnAddItemsSelected,
				class: lpPopupSelectItemToAdd,
				callBack: lpPopupSelectItemToAdd.addItemsSelectedToSection.name,
				callBackHandle: this.addItemsSelectedToSection.bind( this ),
			},
			{
				selector: EditSectionItem.selectors.elBtnSetPreviewItem,
				class: this,
				callBack: this.updatePreviewItem.name,
			},
		] );

		// Keyup events
		lpUtils.eventHandlers( 'keyup', [
			{
				selector: EditSectionItem.selectors.elItemTitleInput,
				class: this,
				callBack: this.changeTitle.name,
			},
			{
				selector: EditSectionItem.selectors.elAddItemTypeTitleInput,
				class: this,
				callBack: this.changeTitleAddNew.name,
			},
		] );

		// Keydown events
		lpUtils.eventHandlers( 'keydown', [
			{
				selector: EditSectionItem.selectors.elAddItemTypeTitleInput,
				class: this,
				callBack: this.addItemToSection.name,
				checkIsEventEnter: true,
			},
			{
				selector: EditSectionItem.selectors.elItemTitleInput,
				class: this,
				callBack: this.updateTitle.name,
				checkIsEventEnter: true,
			},
		] );

		// Focusin events
		lpUtils.eventHandlers( 'focusin', [
			{
				selector: EditSectionItem.selectors.elItemTitleInput,
				class: this,
				callBack: this.focusTitleInput.name,
			},
		] );

		// Focusout events
		lpUtils.eventHandlers( 'focusout', [
			{
				selector: EditSectionItem.selectors.elItemTitleInput,
				class: this,
				callBack: this.focusTitleInput.name,
				focusIn: false,
			},
		] );
	}

	/* Add item type */
	addItemType( args ) {
		const { e, target } = args;

		const elBtnSelectItemType = target;

		const itemType = elBtnSelectItemType.dataset.itemType;
		const itemPlaceholder = elBtnSelectItemType.dataset.placeholder;
		const itemBtnAddText = elBtnSelectItemType.dataset.buttonAddText;

		const elSection = elBtnSelectItemType.closest(
			`${ EditSection.selectors.elSection }`
		);
		const elSectionActions = elSection.querySelector(
			`${ EditSectionItem.selectors.elSectionActions }`
		);

		// Insert input item type to add
		const elAddItemTypeClone = elSectionActions.querySelector(
			`${ EditSectionItem.selectors.elAddItemTypeClone }`
		);
		const elNewItemByType = elAddItemTypeClone.cloneNode( true );
		const elAddItemTypeInput = elNewItemByType.querySelector(
			`${ EditSectionItem.selectors.elAddItemTypeTitleInput }`
		);
		const elBtnAddItem = elNewItemByType.querySelector(
			`${ EditSectionItem.selectors.elBtnAddItem }`
		);

		elNewItemByType.classList.remove( 'clone' );
		elNewItemByType.classList.add( itemType );
		lpUtils.lpShowHideEl( elNewItemByType, 1 );
		elAddItemTypeInput.setAttribute( 'placeholder', itemPlaceholder );
		elAddItemTypeInput.dataset.itemType = itemType;
		elBtnAddItem.textContent = itemBtnAddText;
		elSectionActions.insertAdjacentElement(
			'beforebegin',
			elNewItemByType
		);
		elAddItemTypeInput.focus();
	}

	/* Cancel add item type */
	cancelAddItemType( args ) {
		const { e, target } = args;
		const elAddItemType = target.closest(
			`${ EditSectionItem.selectors.elAddItemType }`
		);
		if ( elAddItemType ) {
			elAddItemType.remove();
		}
	}

	/* Add item to section */
	addItemToSection( args ) {
		const { e, target, callBackNest } = args;
		e.preventDefault();

		const elAddItemType = target.closest(
			`${ EditSectionItem.selectors.elAddItemType }`
		);
		const elSection = elAddItemType.closest(
			`${ EditSection.selectors.elSection }`
		);
		const sectionId = elSection.dataset.sectionId;
		const elAddItemTypeTitleInput = elAddItemType.querySelector(
			`${ EditSectionItem.selectors.elAddItemTypeTitleInput }`
		);
		const titleValue = elAddItemTypeTitleInput.value.trim();
		const typeValue = elAddItemTypeTitleInput.dataset.itemType;
		const message = elAddItemTypeTitleInput.dataset.messEmptyTitle;

		if ( titleValue.length === 0 ) {
			lpToastify.show( message, 'error' );
			return;
		}

		// Clone new section item
		const elItemClone = elSection.querySelector(
			`${ EditSectionItem.selectors.elItemClone }`
		);
		const elItemNew = elItemClone.cloneNode( true );
		const elItemTitleInput = elItemNew.querySelector(
			`${ EditSectionItem.selectors.elItemTitleInput }`
		);

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

				lpToastify.show( message, status );

				if ( status === 'error' ) {
					elItemNew.remove();
				} else if ( status === 'success' ) {
					const { section_item, item_link } = data || {};
					elItemNew.dataset.itemId = section_item.item_id || 0;
					elItemNew
						.querySelector( '.edit-link' )
						.setAttribute( 'href', item_link || '' );

					// Call callback nest if exists
					if (
						callBackNest &&
						typeof callBackNest.success === 'function'
					) {
						args.elItemNew = elItemNew;
						callBackNest.success( args );
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
				elItemNew.remove();
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elItemNew, 0 );
				this.updateCountItems( elSection );

				// Call callback nest if exists
				if (
					callBackNest &&
					typeof callBackNest.completed === 'function'
				) {
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
	changeTitle( args ) {
		const { target } = args;
		const elItemTitleInput = target.closest(
			`${ EditSectionItem.selectors.elItemTitleInput }`
		);
		if ( ! elItemTitleInput ) {
			return;
		}

		const elSectionItem = elItemTitleInput.closest(
			`${ EditSectionItem.selectors.elSectionItem }`
		);
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
	focusTitleInput( args ) {
		const { target, focusIn = true } = args;

		const elItemTitleInput = target.closest(
			`${ EditSectionItem.selectors.elItemTitleInput }`
		);
		if ( ! elItemTitleInput ) {
			return;
		}

		const elSectionItem = elItemTitleInput.closest(
			`${ EditSectionItem.selectors.elSectionItem }`
		);
		if ( ! elSectionItem ) {
			return;
		}

		if ( focusIn ) {
			elSectionItem.classList.add( 'focus' );
		} else {
			elSectionItem.classList.remove( 'focus' );
		}
	}

	changeTitleAddNew( args ) {
		const { target } = args;
		const elAddItemTypeTitleInput = target.closest(
			`${ EditSectionItem.selectors.elAddItemTypeTitleInput }`
		);
		if ( ! elAddItemTypeTitleInput ) {
			return;
		}

		const elAddItemType = elAddItemTypeTitleInput.closest(
			`${ EditSectionItem.selectors.elAddItemType }`
		);
		if ( ! elAddItemType ) {
			return;
		}

		const elBtnAddItem = elAddItemType.querySelector(
			`${ EditSectionItem.selectors.elBtnAddItem }`
		);
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
	updateTitle( args ) {
		const { e, target } = args;

		e.preventDefault();

		const elSectionItem = target.closest(
			`${ EditSectionItem.selectors.elSectionItem }`
		);
		if ( ! elSectionItem ) {
			return;
		}

		const elSection = elSectionItem.closest(
			`${ EditSection.selectors.elSection }`
		);
		if ( ! elSection ) {
			return;
		}

		const elItemTitleInput = elSectionItem.querySelector(
			`${ EditSectionItem.selectors.elItemTitleInput }`
		);
		if ( ! elItemTitleInput ) {
			return;
		}

		const itemId = elSectionItem.dataset.itemId;
		const itemType = elSectionItem.dataset.itemType;
		const itemTitleValue = elItemTitleInput.value.trim();
		const titleOld = elItemTitleInput.dataset.old;
		const message = elItemTitleInput.dataset.messEmptyTitle;
		if ( itemTitleValue.length === 0 ) {
			lpToastify.show( message, 'error' );
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

				if ( status === 'success' ) {
					elItemTitleInput.dataset.old = itemTitleValue;
				} else {
					elItemTitleInput.value = titleOld;
				}

				lpToastify.show( message, status );
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elSectionItem, 0 );
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
	cancelUpdateTitle( args ) {
		const { e, target } = args;
		const elBtnCancelUpdateTitle = target.closest(
			`${ EditSectionItem.selectors.elBtnCancelUpdateTitle }`
		);
		if ( ! elBtnCancelUpdateTitle ) {
			return;
		}

		const elSectionItem = elBtnCancelUpdateTitle.closest(
			`${ EditSectionItem.selectors.elSectionItem }`
		);
		const elItemTitleInput = elSectionItem.querySelector(
			`${ EditSectionItem.selectors.elItemTitleInput }`
		);
		elItemTitleInput.value = elItemTitleInput.dataset.old || '';
		elSectionItem.classList.remove( 'editing' );
	}

	/* Delete item from section */
	deleteItem( args ) {
		const { e, target } = args;
		const elBtnDeleteItem = target.closest(
			`${ EditSectionItem.selectors.elBtnDeleteItem }`
		);
		if ( ! elBtnDeleteItem ) {
			return;
		}

		const elSectionItem = elBtnDeleteItem.closest(
			`${ EditSectionItem.selectors.elSectionItem }`
		);
		if ( ! elSectionItem ) {
			return;
		}

		const itemId = elSectionItem.dataset.itemId;
		const elSection = elSectionItem.closest(
			`${ EditSection.selectors.elSection }`
		);
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

						lpToastify.show( message, status );

						if ( status === 'success' ) {
							elSectionItem.remove();
						}
					},
					error: ( error ) => {
						lpToastify.show( error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elSectionItem, 0 );
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
		const elSectionListItems = this.elCurriculumSections.querySelectorAll(
			`${ EditSectionItem.selectors.elSectionListItems }`
		);
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
					sectionIdEnd = elItemDragged.closest(
						`${ EditSection.selectors.elSection }`
					).dataset.sectionId;

					const dataSend = {
						course_id: this.courseId,
						args: { id_url: idUrlHandle },
					};

					dataSend.action = 'update_item_section_and_position';
					dataSend.item_id_change = itemIdChoose;
					dataSend.section_id_new_of_item = sectionIdEnd;
					dataSend.section_id_old_of_item = sectionIdChoose;

					// Send list items position
					const section = this.elCurriculumSections.querySelector(
						`.section[data-section-id="${ sectionIdEnd }"]`
					);
					const items = section.querySelectorAll(
						`${ EditSectionItem.selectors.elSectionItem }`
					);
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

							lpToastify.show( message, status );
						},
						error: ( error ) => {
							lpToastify.show( error, 'error' );
						},
						completed: () => {
							lpUtils.lpSetLoadingEl( elItemDragged, 0 );
							this.updateCountItems( section );
							if ( sectionIdChoose !== sectionIdEnd ) {
								this.updateCountItems( elSectionChoose );
							}
						},
					};

					lpUtils.lpSetLoadingEl( elItemDragged, 1 );
					window.lpAJAXG.fetchAJAX( dataSend, callBack );
				},
				onMove: ( /*evt*/ ) => {},
				onChoose: ( evt ) => {
					const elChooseItem = evt.item;
					itemIdChoose = elChooseItem.dataset.itemId;
					elSectionChoose = elChooseItem.closest(
						`${ EditSection.selectors.elSection }`
					);
					sectionIdChoose = elSectionChoose.dataset.sectionId;
				},
				onUpdate: ( /*evt*/ ) => {},
			} );
		} );
	}

	/* Add items selected to section */
	addItemsSelectedToSection( itemsSelectedData ) {
		const elSection = document.querySelector(
			`.section[data-section-id="${ this.sectionIdSelected }"]`
		);
		const elItemClone = elSection.querySelector(
			`${ EditSectionItem.selectors.elItemClone }`
		);

		itemsSelectedData.forEach( ( item ) => {
			const elItemNew = elItemClone.cloneNode( true );
			const elInputTitleNew = elItemNew.querySelector(
				`${ EditSectionItem.selectors.elItemTitleInput }`
			);

			elItemNew.dataset.itemId = item.id;
			elItemNew.classList.add( item.type );
			elItemNew.classList.remove( 'clone' );
			elItemNew.dataset.itemType = item.type;
			elItemNew
				.querySelector( '.edit-link' )
				.setAttribute( 'href', item.editLink || '' );
			elInputTitleNew.value = item.titleSelected || '';
			lpUtils.lpSetLoadingEl( elItemNew, 1 );
			lpUtils.lpShowHideEl( elItemNew, 1 );
			elItemClone.insertAdjacentElement( 'beforebegin', elItemNew );
		} );

		SweetAlert.close();

		const dataSend = {
			course_id: this.courseId,
			action: 'add_items_to_section',
			section_id: this.sectionIdSelected,
			items: itemsSelectedData,
			args: { id_url: idUrlHandle },
		};
		window.lpAJAXG.fetchAJAX( dataSend, {
			success: ( response ) => {
				const { message, status, data } = response;
				const { html } = data || '';
				lpToastify.show( message, status );

				itemsSelectedData.forEach( ( item ) => {
					const elItemAdded = elSection.querySelector(
						`${ EditSectionItem.selectors.elSectionItem }[data-item-id="${ item.id }"]`
					);
					if ( elItemAdded ) {
						elItemAdded.remove();
					}
				} );

				if ( status === 'success' ) {
					elItemClone.insertAdjacentHTML( 'beforebegin', html );
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				this.updateCountItems( elSection );
			},
		} );
	}

	/* Enable/disable preview item */
	updatePreviewItem( args ) {
		const { e, target } = args;
		const elBtnSetPreviewItem = target.closest(
			`${ EditSectionItem.selectors.elBtnSetPreviewItem }`
		);
		if ( ! elBtnSetPreviewItem ) {
			return;
		}

		const elSectionItem = elBtnSetPreviewItem.closest(
			`${ EditSectionItem.selectors.elSectionItem }`
		);
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

				lpToastify.show( message, status );

				if ( status === 'error' ) {
					icon.classList.toggle( 'lp-icon-eye' );
					icon.classList.toggle( 'lp-icon-eye-slash' );
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
				icon.classList.toggle( 'lp-icon-eye' );
				icon.classList.toggle( 'lp-icon-eye-slash' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elSectionItem, 0 );
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
		const elEditCurriculum = this.elEditCurriculum;
		const elCountItemsAll =
			elEditCurriculum.querySelector( '.total-items' );
		const elItemsAll = elEditCurriculum.querySelectorAll(
			`${ EditSectionItem.selectors.elSectionItem }:not(.clone)`
		);
		const itemsAllCount = elItemsAll.length;

		elCountItemsAll.dataset.count = itemsAllCount;
		elCountItemsAll.querySelector( '.count' ).textContent = itemsAllCount;

		// Count items in section
		const elSectionItemsCount = elSection.querySelector(
			'.section-items-counts'
		);

		const elItems = elSection.querySelectorAll(
			`${ EditSectionItem.selectors.elSectionItem }:not(.clone)`
		);
		const itemsCount = elItems.length;

		elSectionItemsCount.dataset.count = itemsCount;
		elSectionItemsCount.querySelector( '.count' ).textContent = itemsCount;
	}
}
