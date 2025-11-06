/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.1
 */

import * as lpEditCurriculumShare from './edit-curriculum/share.js';
import { EditSection } from './edit-curriculum/edit-section.js';
import { EditSectionItem } from './edit-curriculum/edit-section-item.js';
import * as lpUtils from '../../utils.js';

const sectionEdit = new EditSection();
const sectionItemEdit = new EditSectionItem();

const { className } = lpEditCurriculumShare;

export class EditCourseCurriculum {
	constructor() {
		this.init();
	}

	init() {
		lpEditCurriculumShare.lpUtils.lpOnElementReady( `${ className.idElEditCurriculum }`, ( elEditCurriculum ) => {
			const elCurriculumSections = elEditCurriculum.querySelector( `${ className.elCurriculumSections }` );
			const elLPTarget = elEditCurriculum.closest( `${ className.LPTarget }` );
			const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );

			lpEditCurriculumShare.setVariables(
				{
					courseId: dataSend.args.course_id,
					elEditCurriculum,
					elCurriculumSections,
					elLPTarget,
					updateCountItems: this.updateCountItems,
					hasChange: {},
				}
			);

			// Set variables use for section edit
			sectionEdit.init();
			sectionEdit.sortAbleSection();

			// Set variables use for edit section item
			sectionItemEdit.init();
			sectionItemEdit.sortAbleItem();
			// Share sortAbleItem function, for when create new section, will call this function to sort items in section.
			lpEditCurriculumShare.setVariable( 'sortAbleItem', sectionItemEdit.sortAbleItem );
		} );

		this.attachEvents();
	}

	attachEvents() {
		lpUtils.eventHandlers( 'click', [
			{
				selector: `${ sectionEdit.className.elBtnAddSection }`,
				class: sectionEdit,
				callBack: sectionEdit.addSection.name,
				className: sectionEdit.className,
			},
			{
				selector: `${ sectionEdit.className.elBtnUpdateDes }`,
				class: sectionEdit,
				callBack: sectionEdit.updateSectionDescription.name,
				className: sectionEdit.className,
			},
			{
				selector: `${ sectionItemEdit.className.elBtnSelectItemType }`,
				class: sectionItemEdit,
				callBack: sectionItemEdit.addItemType.name,
				className: sectionItemEdit.className,
			},
			{
				selector: `${ sectionItemEdit.className.elBtnAddItem }`,
				class: sectionItemEdit,
				callBack: sectionItemEdit.addItemToSection.name,
				className: sectionItemEdit.className,
			},
		] );
		lpUtils.eventHandlers( 'keydown', [
			{
				selector: `${ sectionEdit.className.elSectionTitleNewInput }`,
				class: sectionEdit,
				callBack: sectionEdit.addSection.name,
				checkIsEventEnter: true,
			},
			{
				selector: `${ sectionEdit.className.elSectionDesInput }`,
				class: sectionEdit,
				callBack: sectionEdit.updateSectionDescription.name,
				checkIsEventEnter: true,
			},
			{
				selector: `${ sectionItemEdit.className.elBtnAddItem }`,
				class: sectionItemEdit,
				callBack: sectionItemEdit.addItemToSection.name,
				checkIsEventEnter: true,
			},
		] );
		document.addEventListener( 'click', ( e ) => this.handleClick( e ) );
		document.addEventListener( 'keydown', ( e ) => this.handleKeyDown( e ) );
		document.addEventListener( 'keyup', ( e ) => this.handleKeyUp( e ) );
		document.addEventListener( 'focusin', ( e ) => this.handleFocusIn( e ) );
		document.addEventListener( 'focusout', ( e ) => this.handleFocusOut( e ) );
		window.addEventListener( 'beforeunload', ( e ) => this.handleBeforeUnload( e ) );
	}

	handleClick( e ) {
		const target = e.target;

		/*** Event of Section ***/
		sectionEdit.setFocusTitleInput( e, target );
		sectionEdit.toggleSection( e, target );
		sectionEdit.cancelSectionDescription( e, target );
		sectionEdit.deleteSection( e, target );
		sectionEdit.updateSectionTitle( e, target );
		sectionEdit.cancelSectionTitle( e, target );
		/*** End Event of Section ***/

		/*** Event of Section Item ***/
		//sectionItemEdit.addItemType( e, target );
		sectionItemEdit.cancelAddItemType( e, target );
		//sectionItemEdit.addItemToSection( e, target );
		sectionItemEdit.showPopupItemsToSelect( e, target );
		sectionItemEdit.showItemsSelected( e, target );
		sectionItemEdit.chooseTabItemsType( e, target );
		sectionItemEdit.updateTitle( e, target );
		sectionItemEdit.cancelUpdateTitle( e, target );
		sectionItemEdit.deleteItem( e, target );
		sectionItemEdit.selectItemsFromList( e, target );
		sectionItemEdit.addItemsSelectedToSection( e, target );
		sectionItemEdit.backToSelectItems( e, target );
		sectionItemEdit.removeItemSelected( e, target );
		sectionItemEdit.updatePreviewItem( e, target );
		/*** End Event of Section Item ***/

		this.toggleSectionAll( e, target );
	}

	handleKeyDown( e ) {
		const target = e.target;
		if ( e.key === 'Enter' ) {
			sectionEdit.updateSectionTitle( e, target );
			sectionEdit.updateSectionDescription( e, target );
			sectionItemEdit.addItemToSection( e, target );
			sectionItemEdit.updateTitle( e, target );
		}
	}

	handleKeyUp( e ) {
		const target = e.target;
		sectionEdit.changeTitleBeforeAdd( e, target );
		sectionEdit.changeTitle( e, target );
		sectionEdit.changeDescription( e, target );
		sectionItemEdit.changeTitle( e, target );
		sectionItemEdit.changeTitleAddNew( e, target );
		sectionItemEdit.searchTitleItemToSelect( e, target );
	}

	handleFocusIn( e ) {
		sectionEdit.focusTitleNewInput( e, e.target );
		sectionEdit.focusTitleInput( e, e.target );
		sectionItemEdit.focusTitleInput( e, e.target );
	}

	handleFocusOut( e ) {
		sectionEdit.focusTitleNewInput( e, e.target, false );
		sectionEdit.focusTitleInput( e, e.target, false );
		sectionItemEdit.focusTitleInput( e, e.target, false );
	}

	handleBeforeUnload( e ) {
		if ( Object.keys( lpEditCurriculumShare.hasChange ).length === 0 ) {
			return;
		}

		e.preventDefault();
		e.returnValue = '';
	}

	toggleSectionAll( e, target ) {
		const elToggleAllSections = target.closest( `${ className.elToggleAllSections }` );
		if ( ! elToggleAllSections ) {
			return;
		}

		const elSections = lpEditCurriculumShare.elEditCurriculum.querySelectorAll( `${ className.elSection }:not(.clone)` );

		elToggleAllSections.classList.toggle( `${ className.elCollapse }` );

		elSections.forEach( ( el ) => {
			const shouldCollapse = elToggleAllSections.classList.contains( `${ className.elCollapse }` );
			el.classList.toggle( `${ className.elCollapse }`, shouldCollapse );
		} );
	}

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
