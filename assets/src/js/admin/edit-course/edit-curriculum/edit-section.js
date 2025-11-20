/**
 * Edit Section Script on Curriculum
 *
 * @since 4.2.8.6
 * @version 1.0.3
 */
import SweetAlert from 'sweetalert2';
import Sortable from 'sortablejs';
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { EditSectionItem } from './edit-section-item.js';
import { EditCourseCurriculum } from '../edit-curriculum.js';

export class EditSection {
	constructor() {
		this.courseId = null;
		this.elEditCurriculum = null;
		this.elCurriculumSections = null;
		this.editSectionItem = new EditSectionItem();
	}

	static selectors = {
		elSection: '.section',
		elDivAddNewSection: '.add-new-section',
		elSectionClone: '.section.clone',
		elSectionTitleNewInput: '.lp-section-title-new-input',
		elSectionTitleInput: '.lp-section-title-input',
		etBtnEditTitle: '.lp-btn-edit-section-title',
		elSectionDesInput: '.lp-section-description-input',
		elBtnAddSection: '.lp-btn-add-section',
		elBtnUpdateTitle: '.lp-btn-update-section-title',
		elBtnUpdateDes: '.lp-btn-update-section-description',
		elBtnCancelUpdateTitle: '.lp-btn-cancel-update-section-title',
		elBtnCancelUpdateDes: '.lp-btn-cancel-update-section-description',
		elBtnDeleteSection: '.lp-btn-delete-section',
		elSectionDesc: '.section-description',
		elSectionToggle: '.section-toggle',
		elCountSections: '.count-sections',
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

		this.editSectionItem.init();

		this.events();
		this.sortAbleSection();
	}

	events() {
		// Check and attach events only once
		if ( EditSection._loadedEvents ) {
			return;
		}

		EditSection._loadedEvents = this;

		// Click events
		lpUtils.eventHandlers( 'click', [
			{
				selector: EditSection.selectors.elBtnAddSection,
				class: this,
				callBack: this.addSection.name,
			},
			{
				selector: `${ EditSection.selectors.elBtnUpdateDes }`,
				class: this,
				callBack: this.updateSectionDescription.name,
			},
			{
				selector: `${ EditSection.selectors.etBtnEditTitle }`,
				class: this,
				callBack: this.setFocusTitleInput.name,
			},
			{
				selector: `${ EditSection.selectors.elSectionToggle }`,
				class: this,
				callBack: this.toggleSection.name,
			},
			{
				selector: `${ EditSection.selectors.elBtnCancelUpdateDes }`,
				class: this,
				callBack: this.cancelSectionDescription.name,
			},
			{
				selector: `${ EditSection.selectors.elBtnDeleteSection }`,
				class: this,
				callBack: this.deleteSection.name,
			},
			{
				selector: `${ EditSection.selectors.elBtnUpdateTitle }`,
				class: this,
				callBack: this.updateSectionTitle.name,
			},
			{
				selector: `${ EditSection.selectors.elBtnCancelUpdateTitle }`,
				class: this,
				callBack: this.cancelSectionTitle.name,
			},
			{
				selector: EditCourseCurriculum.selectors.elToggleAllSections,
				class: this,
				callBack: this.toggleSectionAll.name,
			},
		] );

		// Keyup events
		lpUtils.eventHandlers( 'keyup', [
			{
				selector: EditSection.selectors.elSectionTitleNewInput,
				class: this,
				callBack: this.changeTitleBeforeAdd.name,
			},
			{
				selector: EditSection.selectors.elSectionTitleInput,
				class: this,
				callBack: this.changeTitle.name,
			},
			{
				selector: EditSection.selectors.elSectionDesInput,
				class: this,
				callBack: this.changeDescription.name,
			},
		] );

		// Keydown events
		lpUtils.eventHandlers( 'keydown', [
			{
				selector: EditSection.selectors.elSectionTitleNewInput,
				class: this,
				callBack: this.addSection.name,
				checkIsEventEnter: true,
			},
			{
				selector: EditSection.selectors.elSectionDesInput,
				class: this,
				callBack: this.updateSectionDescription.name,
				checkIsEventEnter: true,
			},
			{
				selector: EditSection.selectors.elSectionTitleInput,
				class: this,
				callBack: this.updateSectionTitle.name,
				checkIsEventEnter: true,
			},
		] );

		// Focusin events
		lpUtils.eventHandlers( 'focusin', [
			{
				selector: EditSection.selectors.elSectionTitleNewInput,
				class: this,
				callBack: this.focusTitleNewInput.name,
			},
			{
				selector: EditSection.selectors.elSectionTitleInput,
				class: this,
				callBack: this.focusTitleInput.name,
			},
		] );

		// Focusin events
		lpUtils.eventHandlers( 'focusout', [
			{
				selector: EditSection.selectors.elSectionTitleNewInput,
				class: this,
				callBack: this.focusTitleNewInput.name,
				focusIn: false,
			},
			{
				selector: `${ EditSection.selectors.elSectionTitleInput }`,
				class: this,
				callBack: this.focusTitleInput.name,
				focusIn: false,
			},
		] );
	}

	/* Typing in new section title input */
	changeTitleBeforeAdd( args ) {
		const { e, target } = args;
		const elSectionTitleNewInput = target;

		const elAddNewSection = elSectionTitleNewInput.closest(
			`${ EditSection.selectors.elDivAddNewSection }`
		);
		if ( ! elAddNewSection ) {
			return;
		}

		const elBtnAddSection = elAddNewSection.querySelector(
			`${ EditSection.selectors.elBtnAddSection }`
		);

		const titleValue = elSectionTitleNewInput.value.trim();
		if ( titleValue.length === 0 ) {
			elBtnAddSection.classList.remove( 'active' );
		} else {
			elBtnAddSection.classList.add( 'active' );
		}
	}

	/* Focus on new section title input */
	focusTitleNewInput( args ) {
		const { e, target, focusIn = true } = args;
		const elAddNewSection = target.closest(
			`${ EditSection.selectors.elDivAddNewSection }`
		);
		if ( ! elAddNewSection ) {
			return;
		}

		if ( focusIn ) {
			elAddNewSection.classList.add( 'focus' );
		} else {
			elAddNewSection.classList.remove( 'focus' );
		}
	}

	/* Add new section */
	addSection( args ) {
		const { e, target, callBackNest } = args;

		const elDivAddNewSection = target.closest(
			`${ EditSection.selectors.elDivAddNewSection }`
		);
		if ( ! elDivAddNewSection ) {
			return;
		}

		e.preventDefault();

		const elSectionTitleNewInput = elDivAddNewSection.querySelector(
			`${ EditSection.selectors.elSectionTitleNewInput }`
		);
		const titleValue = elSectionTitleNewInput.value.trim();
		const message = elSectionTitleNewInput.dataset.messEmptyTitle;
		if ( titleValue.length === 0 ) {
			lpToastify.show( message, 'error' );
			return;
		}

		// Clear input after add
		elSectionTitleNewInput.value = '';
		elSectionTitleNewInput.blur();

		// Add and set data for new section
		const elSectionClone = this.elCurriculumSections.querySelector(
			`${ EditSection.selectors.elSectionClone }`
		);
		const newSection = elSectionClone.cloneNode( true );
		newSection.classList.remove( 'clone' );
		lpUtils.lpShowHideEl( newSection, 1 );
		lpUtils.lpSetLoadingEl( newSection, 1 );
		const elSectionTitleInput = newSection.querySelector(
			`${ EditSection.selectors.elSectionTitleInput }`
		);
		elSectionTitleInput.value = titleValue;
		this.elCurriculumSections.insertAdjacentElement(
			'beforeend',
			newSection
		);
		// End

		// Call ajax to add new section
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				if ( status === 'error' ) {
					newSection.remove();
					throw message;
				} else if ( status === 'success' ) {
					const { section } = data;
					newSection.dataset.sectionId = section.section_id || '';

					// Initialize EditSectionItem for the new section to make its items sortable
					this.editSectionItem.sortAbleItem();

					if (
						callBackNest &&
						typeof callBackNest.success === 'function'
					) {
						args.elSection = newSection;
						args.response = response;
						callBackNest.success( args );
					}
				}

				lpToastify.show( message, status );
			},
			error: ( error ) => {
				newSection.remove();
				lpToastify.show( error, 'error' );
				if (
					callBackNest &&
					typeof callBackNest.error === 'function'
				) {
					args.error = error;
					callBackNest.error( args );
				}
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( newSection, 0 );
				newSection.classList.remove(
					`${ EditCourseCurriculum.selectors.elCollapse }`
				);
				const elSectionDesInput = newSection.querySelector(
					`${ EditSection.selectors.elSectionDesInput }`
				);
				elSectionDesInput.focus();
				this.updateCountSections( this.elEditCurriculum );
				if (
					callBackNest &&
					typeof callBackNest.completed === 'function'
				) {
					args.elSection = newSection;
					callBackNest.completed( args );
				}
			},
		};

		const dataSend = JSON.parse( elSectionTitleNewInput.dataset.send );
		dataSend.section_name = titleValue;
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/* Delete section */
	deleteSection( args ) {
		const { e, target } = args;
		const elBtnDeleteSection = target;

		const elEditCurriculum = elBtnDeleteSection.closest(
			`${ EditCourseCurriculum.selectors.idElEditCurriculum }`
		);

		SweetAlert.fire( {
			title: elBtnDeleteSection.dataset.title,
			text: elBtnDeleteSection.dataset.content,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				const elSection = elBtnDeleteSection.closest( '.section' );
				const sectionId = elSection.dataset.sectionId;

				lpUtils.lpSetLoadingEl( elSection, 1 );

				// Call ajax to delete section
				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						lpToastify.show( message, status );
					},
					error: ( error ) => {
						lpToastify.show( error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elSection, 0 );
						elSection.remove();
						this.editSectionItem.updateCountItems( elSection );
						this.updateCountSections( elEditCurriculum );
					},
				};

				const dataSend = JSON.parse( elBtnDeleteSection.dataset.send );
				dataSend.section_id = sectionId;
				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}
		} );
	}

	/* Focus on section title input */
	focusTitleInput( args ) {
		const { e, target, focusIn = true } = args;
		const elSection = target.closest(
			`${ EditSection.selectors.elSection }`
		);
		if ( ! elSection ) {
			return;
		}

		if ( focusIn ) {
			elSection.classList.add( 'focus' );
		} else {
			elSection.classList.remove( 'focus' );
		}
	}

	/* Set focus on section title input */
	setFocusTitleInput( args ) {
		const { e, target } = args;

		const elSection = target.closest(
			`${ EditSection.selectors.elSection }`
		);
		if ( ! elSection ) {
			return;
		}

		const elSectionTitleInput = elSection.querySelector(
			`${ EditSection.selectors.elSectionTitleInput }`
		);
		elSectionTitleInput.setSelectionRange(
			elSectionTitleInput.value.length,
			elSectionTitleInput.value.length
		);
		elSectionTitleInput.focus();
	}

	/* Typing in section title input */
	changeTitle( args ) {
		const { e, target } = args;
		const elSectionTitleInput = target;
		const elSection = elSectionTitleInput.closest(
			`${ EditSection.selectors.elSection }`
		);
		const titleValue = elSectionTitleInput.value.trim();
		const titleValueOld = elSectionTitleInput.dataset.old || '';

		if ( titleValue === titleValueOld ) {
			elSection.classList.remove( 'editing' );
		} else {
			elSection.classList.add( 'editing' );
		}
	}

	/* Update section title to server */
	updateSectionTitle( args ) {
		const { e, target } = args;
		const elSection = target.closest(
			`${ EditSection.selectors.elSection }`
		);
		if ( ! elSection ) {
			return;
		}

		e.preventDefault();

		const elSectionTitleInput = elSection.querySelector(
			`${ EditSection.selectors.elSectionTitleInput }`
		);
		if ( ! elSectionTitleInput ) {
			return;
		}

		const sectionId = elSection.dataset.sectionId;
		const titleValue = elSectionTitleInput.value.trim();
		const titleValueOld = elSectionTitleInput.dataset.old || '';
		const message = elSectionTitleInput.dataset.messEmptyTitle;
		if ( titleValue.length === 0 ) {
			lpToastify.show( message, 'error' );
			return;
		}

		if ( titleValue === titleValueOld ) {
			return;
		}

		elSectionTitleInput.blur();
		lpUtils.lpSetLoadingEl( elSection, 1 );

		// Call ajax to update section title
		const callBack = {
			success: ( response ) => {
				const { message, status } = response;

				lpToastify.show( message, status );

				if ( status === 'success' ) {
					elSectionTitleInput.dataset.old = titleValue;
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elSection, 0 );
				elSection.classList.remove( 'editing' );
			},
		};

		const dataSend = JSON.parse( elSectionTitleInput.dataset.send );
		dataSend.section_id = sectionId;
		dataSend.section_name = titleValue;
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/* Cancel updating section title */
	cancelSectionTitle( args ) {
		const { e, target } = args;
		const elBtnCancelUpdateTitle = target.closest(
			`${ EditSection.selectors.elBtnCancelUpdateTitle }`
		);
		if ( ! elBtnCancelUpdateTitle ) {
			return;
		}

		const elSection = elBtnCancelUpdateTitle.closest(
			`${ EditSection.selectors.elSection }`
		);
		const elSectionTitleInput = elSection.querySelector(
			`${ EditSection.selectors.elSectionTitleInput }`
		);
		elSectionTitleInput.value = elSectionTitleInput.dataset.old || '';
		elSection.classList.remove( 'editing' );
	}

	/* Update section description to server */
	updateSectionDescription( args ) {
		const { e, target, callBackNest } = args;

		const elSectionDesc = target.closest(
			`${ EditSection.selectors.elSectionDesc }`
		);
		if ( ! elSectionDesc ) {
			return;
		}

		const elSectionDesInput = elSectionDesc.querySelector(
			`${ EditSection.selectors.elSectionDesInput }`
		);
		if ( ! elSectionDesInput ) {
			return;
		}

		e.preventDefault();

		const elSection = elSectionDesInput.closest(
			`${ EditSection.selectors.elSection }`
		);
		const sectionId = elSection.dataset.sectionId;
		const descValue = elSectionDesInput.value.trim();
		const descValueOld = elSectionDesInput.dataset.old || '';

		if ( descValue === descValueOld ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elSection, 1 );

		const callBack = {
			success: ( response ) => {
				const { message, status } = response;

				if (
					callBackNest &&
					typeof callBackNest.success === 'function'
				) {
					args.elSection = elSection;
					args.response = response;
					callBackNest.success( args );
				}

				lpToastify.show( message, status );
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
				if (
					callBackNest &&
					typeof callBackNest.error === 'function'
				) {
					callBackNest.error( elSection, error );
				}
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elSection, 0 );
				const elSectionDesc = elSectionDesInput.closest(
					`${ EditSection.selectors.elSectionDesc }`
				);
				elSectionDesc.classList.remove( 'editing' );
				elSectionDesInput.dataset.old = descValue;
				if (
					callBackNest &&
					typeof callBackNest.completed === 'function'
				) {
					callBackNest.completed( elSection );
				}
			},
		};

		const dataSend = JSON.parse( elSectionDesInput.dataset.send );
		dataSend.section_id = sectionId;
		dataSend.section_description = descValue;
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/* Cancel updating section description */
	cancelSectionDescription( args ) {
		const { e, target } = args;
		const elSectionDesc = target.closest(
			`${ EditSection.selectors.elSectionDesc }`
		);
		const elSectionDesInput = elSectionDesc.querySelector(
			`${ EditSection.selectors.elSectionDesInput }`
		);
		elSectionDesInput.value = elSectionDesInput.dataset.old || '';
		elSectionDesc.classList.remove( 'editing' );
	}

	/* Typing in description input */
	changeDescription( ags ) {
		const { e, target } = ags;
		const elSectionDesInput = target.closest(
			`${ EditSection.selectors.elSectionDesInput }`
		);
		if ( ! elSectionDesInput ) {
			return;
		}

		const elSectionDesc = elSectionDesInput.closest(
			`${ EditSection.selectors.elSectionDesc }`
		);
		const descValue = elSectionDesInput.value.trim();
		const descValueOld = elSectionDesInput.dataset.old || '';

		if ( descValue === descValueOld ) {
			elSectionDesc.classList.remove( 'editing' );
		} else {
			elSectionDesc.classList.add( 'editing' );
		}
	}

	toggleSectionAll( args ) {
		const { e, target } = args;
		const elToggleAllSections = target.closest(
			`${ EditCourseCurriculum.selectors.elToggleAllSections }`
		);
		if ( ! elToggleAllSections ) {
			return;
		}

		const elEditCurriculum = elToggleAllSections.closest(
			`${ EditCourseCurriculum.selectors.idElEditCurriculum }`
		);
		const elSections = elEditCurriculum.querySelectorAll(
			`${ EditSection.selectors.elSection }:not(.clone)`
		);

		elToggleAllSections.classList.toggle(
			`${ EditCourseCurriculum.selectors.elCollapse }`
		);

		elSections.forEach( ( el ) => {
			const shouldCollapse = elToggleAllSections.classList.contains(
				`${ EditCourseCurriculum.selectors.elCollapse }`
			);
			el.classList.toggle(
				`${ EditCourseCurriculum.selectors.elCollapse }`,
				shouldCollapse
			);
		} );
	}

	/* Toggle section */
	toggleSection( args ) {
		const { e, target } = args;
		const elSection = target.closest(
			`${ EditSection.selectors.elSection }`
		);

		const elCurriculumSections = elSection.closest(
			`${ EditCourseCurriculum.selectors.elCurriculumSections }`
		);
		if ( ! elCurriculumSections ) {
			return;
		}

		// Toggle section
		elSection.classList.toggle(
			`${ EditCourseCurriculum.selectors.elCollapse }`
		);

		// Check all sections collapsed
		this.checkAllSectionsCollapsed();
	}

	/* Check if all sections are collapsed */
	checkAllSectionsCollapsed() {
		const elSections = this.elEditCurriculum.querySelectorAll(
			`${ EditSection.selectors.elSection }:not(.clone)`
		);
		const elToggleAllSections = this.elEditCurriculum.querySelector(
			`${ EditCourseCurriculum.selectors.elToggleAllSections }`
		);

		let isAllExpand = true;
		elSections.forEach( ( el ) => {
			if (
				el.classList.contains(
					`${ EditCourseCurriculum.selectors.elCollapse }`
				)
			) {
				isAllExpand = false;
				return false; // Break the loop
			}
		} );

		if ( isAllExpand ) {
			elToggleAllSections.classList.remove(
				`${ EditCourseCurriculum.selectors.elCollapse }`
			);
		} else {
			elToggleAllSections.classList.add(
				`${ EditCourseCurriculum.selectors.elCollapse }`
			);
		}
	}

	/* Sortable sections, drag and drop to change section position */
	sortAbleSection() {
		let isUpdateSectionPosition = 0;
		let timeout;

		new Sortable( this.elCurriculumSections, {
			handle: '.drag',
			animation: 150,
			onEnd: ( evt ) => {
				const target = evt.item;
				if ( ! isUpdateSectionPosition ) {
					return;
				}

				const elSection = target.closest(
					`${ EditSection.selectors.elSection }`
				);
				const elSections = this.elCurriculumSections.querySelectorAll(
					`${ EditSection.selectors.elSection }`
				);
				const sectionIds = [];

				elSections.forEach( ( elSection ) => {
					const sectionId = elSection.dataset.sectionId;
					sectionIds.push( sectionId );
				} );

				// Call ajax to update section position
				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						lpToastify.show( message, status );
					},
					error: ( error ) => {
						lpToastify.show( error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elSection, 0 );
						isUpdateSectionPosition = 0;
					},
				};

				const dataSend = {
					action: 'course_update_section_position',
					course_id: this.courseId,
					new_position: sectionIds,
					args: { id_url: 'course-update-section-position' },
				};

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
	}

	/* Update count sections, when add or delete section */
	updateCountSections( elEditCurriculum ) {
		const elCountSections = elEditCurriculum.querySelector(
			`${ EditSection.selectors.elCountSections }`
		);
		const elSections = elEditCurriculum.querySelectorAll(
			`${ EditSection.selectors.elSection }:not(.clone)`
		);
		const sectionsCount = elSections.length;

		elCountSections.dataset.count = sectionsCount;
		elCountSections.querySelector( '.count' ).textContent = sectionsCount;
	}
}
