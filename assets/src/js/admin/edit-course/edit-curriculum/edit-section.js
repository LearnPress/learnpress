/**
 * Edit Section Script on Curriculum
 *
 * @since 4.2.8.6
 * @version 1.0.2
 */
import * as lpEditCurriculumShare from './share.js';
import SweetAlert from 'sweetalert2';
import Sortable from 'sortablejs';

const className = {
	...lpEditCurriculumShare.className,
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

const idUrlHandle = 'edit-course-curriculum';

class EditSection {
	constructor() {
		this.courseId = null;
		this.elEditCurriculum = null;
		this.elCurriculumSections = null;
		this.showToast = null;
		this.lpUtils = null;
		this.updateCountItems = null;
		this.sortAbleItem = null;
	}

	init() {
		( {
			courseId: this.courseId,
			elEditCurriculum: this.elEditCurriculum,
			elCurriculumSections: this.elCurriculumSections,
			showToast: this.showToast,
			lpUtils: this.lpUtils,
			updateCountItems: this.updateCountItems,
			sortAbleItem: this.sortAbleItem,
		} = lpEditCurriculumShare );
	}

	/* Typing in new section title input */
	changeTitleBeforeAdd( e, target ) {
		const elSectionTitleNewInput = target.closest( `${ className.elSectionTitleNewInput }` );
		if ( ! elSectionTitleNewInput ) {
			return;
		}

		const elAddNewSection = elSectionTitleNewInput.closest( `${ className.elDivAddNewSection }` );
		if ( ! elAddNewSection ) {
			return;
		}

		const elBtnAddSection = elAddNewSection.querySelector( `${ className.elBtnAddSection }` );

		const titleValue = elSectionTitleNewInput.value.trim();
		if ( titleValue.length === 0 ) {
			elBtnAddSection.classList.remove( 'active' );
			delete lpEditCurriculumShare.hasChange.titleNew;
		} else {
			elBtnAddSection.classList.add( 'active' );
			lpEditCurriculumShare.hasChange.titleNew = 1;
		}
	}

	/* Focus on new section title input */
	focusTitleNewInput( e, target, focusIn = true ) {
		const elSectionTitleNewInput = target.closest( `${ className.elSectionTitleNewInput }` );
		if ( ! elSectionTitleNewInput ) {
			return;
		}

		const elAddNewSection = elSectionTitleNewInput.closest( `${ className.elDivAddNewSection }` );
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
	addSection( e, target, callBackNest ) {
		let canHandle = false;

		if ( target.closest( `${ className.elBtnAddSection }` ) ) {
			canHandle = true;
		} else if ( target.closest( `${ className.elSectionTitleNewInput }` ) && e.key === 'Enter' ) {
			canHandle = true;
		}

		if ( ! canHandle ) {
			return;
		}

		const elAddNewSection = target.closest( `${ className.elDivAddNewSection }` );
		if ( ! elAddNewSection ) {
			return;
		}

		e.preventDefault();

		const elSectionTitleNewInput = elAddNewSection.querySelector( `${ className.elSectionTitleNewInput }` );
		const titleValue = elSectionTitleNewInput.value.trim();
		const message = elSectionTitleNewInput.dataset.messEmptyTitle;
		if ( titleValue.length === 0 ) {
			this.showToast( message, 'error' );
			return;
		}

		// Clear input after add
		elSectionTitleNewInput.value = '';
		elSectionTitleNewInput.blur();

		// Add and set data for new section
		const elSectionClone = this.elCurriculumSections.querySelector( `${ className.elSectionClone }` );
		const newSection = elSectionClone.cloneNode( true );
		newSection.classList.remove( 'clone' );
		this.lpUtils.lpShowHideEl( newSection, 1 );
		this.lpUtils.lpSetLoadingEl( newSection, 1 );
		const elSectionTitleInput = newSection.querySelector( `${ className.elSectionTitleInput }` );
		elSectionTitleInput.value = titleValue;
		this.elCurriculumSections.insertAdjacentElement( 'beforeend', newSection );
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

					if ( lpEditCurriculumShare.sortAbleItem ) {
						lpEditCurriculumShare.sortAbleItem();
					}

					if ( callBackNest && typeof callBackNest.success === 'function' ) {
						callBackNest.success( newSection, response );
					}
				}

				this.showToast( message, status );
			},
			error: ( error ) => {
				newSection.remove();
				this.showToast( error, 'error' );
				if ( callBackNest && typeof callBackNest.error === 'function' ) {
					callBackNest.error( newSection, error );
				}
			},
			completed: () => {
				this.lpUtils.lpSetLoadingEl( newSection, 0 );
				newSection.classList.remove( `${ className.elCollapse }` );
				const elSectionDesInput = newSection.querySelector( `${ className.elSectionDesInput }` );
				elSectionDesInput.focus();
				this.updateCountSections();
				delete lpEditCurriculumShare.hasChange.titleNew;
				if ( callBackNest && typeof callBackNest.completed === 'function' ) {
					callBackNest.completed( newSection );
				}
			},
		};

		this.addSectionAPI( { section_name: titleValue }, callBack );
	}

	addSectionAPI( data, callBack ) {
		const dataSend = {
			action: 'add_section',
			course_id: this.courseId,
			section_name: data.section_name || '',
			args: { id_url: idUrlHandle },
			...data,
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/* Delete section */
	deleteSection( e, target ) {
		const elBtnDeleteSection = target.closest( `${ className.elBtnDeleteSection }` );
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

				this.lpUtils.lpSetLoadingEl( elSection, 1 );

				// Call ajax to delete section
				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						this.showToast( message, status );
					},
					error: ( error ) => {
						this.showToast( error, 'error' );
					},
					completed: () => {
						this.lpUtils.lpSetLoadingEl( elSection, 0 );
						elSection.remove();
						this.updateCountItems( elSection );
						this.updateCountSections();
					},
				};

				const dataSend = {
					action: 'delete_section',
					course_id: this.courseId,
					section_id: sectionId,
					args: { id_url: idUrlHandle },
				};
				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}
		} );
	}

	/* Focus on section title input */
	focusTitleInput( e, target, focusIn = true ) {
		const elSectionTitleInput = target.closest( `${ className.elSectionTitleInput }` );
		if ( ! elSectionTitleInput ) {
			return;
		}

		const elSection = elSectionTitleInput.closest( `${ className.elSection }` );
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
	setFocusTitleInput( e, target ) {
		const etBtnEditTitle = target.closest( `${ className.etBtnEditTitle }` );
		if ( ! etBtnEditTitle ) {
			return;
		}

		const elSection = etBtnEditTitle.closest( `${ className.elSection }` );
		if ( ! elSection ) {
			return;
		}

		const elSectionTitleInput = elSection.querySelector( `${ className.elSectionTitleInput }` );
		elSectionTitleInput.setSelectionRange( elSectionTitleInput.value.length, elSectionTitleInput.value.length );
		elSectionTitleInput.focus();
	}

	/* Typing in section title input */
	changeTitle( e, target ) {
		const elSectionTitleInput = target.closest( `${ className.elSectionTitleInput }` );
		if ( ! elSectionTitleInput ) {
			return;
		}

		const elSection = elSectionTitleInput.closest( `${ className.elSection }` );
		const titleValue = elSectionTitleInput.value.trim();
		const titleValueOld = elSectionTitleInput.dataset.old || '';

		if ( titleValue === titleValueOld ) {
			elSection.classList.remove( 'editing' );
			delete lpEditCurriculumShare.hasChange.title;
		} else {
			elSection.classList.add( 'editing' );
			lpEditCurriculumShare.hasChange.title = 1;
		}
	}

	/* Update section title to server */
	updateSectionTitle( e, target ) {
		let canHandle = false;

		if ( target.closest( `${ className.elBtnUpdateTitle }` ) ) {
			canHandle = true;
		} else if ( target.closest( `${ className.elSectionTitleInput }` ) && e.key === 'Enter' ) {
			canHandle = true;
		}

		if ( ! canHandle ) {
			return;
		}

		e.preventDefault();

		const elSection = target.closest( `${ className.elSection }` );
		if ( ! elSection ) {
			return;
		}

		const elSectionTitleInput = elSection.querySelector( `${ className.elSectionTitleInput }` );
		if ( ! elSectionTitleInput ) {
			return;
		}

		const sectionId = elSection.dataset.sectionId;
		const titleValue = elSectionTitleInput.value.trim();
		const titleValueOld = elSectionTitleInput.dataset.old || '';
		const message = elSectionTitleInput.dataset.messEmptyTitle;
		if ( titleValue.length === 0 ) {
			this.showToast( message, 'error' );
			return;
		}

		if ( titleValue === titleValueOld ) {
			return;
		}

		elSectionTitleInput.blur();
		this.lpUtils.lpSetLoadingEl( elSection, 1 );

		// Call ajax to update section title
		const callBack = {
			success: ( response ) => {
				const { message, status } = response;

				this.showToast( message, status );

				if ( status === 'success' ) {
					elSectionTitleInput.dataset.old = titleValue;
				}
			},
			error: ( error ) => {
				this.showToast( error, 'error' );
			},
			completed: () => {
				this.lpUtils.lpSetLoadingEl( elSection, 0 );
				elSection.classList.remove( 'editing' );
				delete lpEditCurriculumShare.hasChange.title;
			},
		};

		const dataSend = {
			action: 'update_section',
			course_id: this.courseId,
			section_id: sectionId,
			section_name: titleValue,
			args: { id_url: idUrlHandle },
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/* Cancel updating section title */
	cancelSectionTitle( e, target ) {
		const elBtnCancelUpdateTitle = target.closest( `${ className.elBtnCancelUpdateTitle }` );
		if ( ! elBtnCancelUpdateTitle ) {
			return;
		}

		const elSection = elBtnCancelUpdateTitle.closest( `${ className.elSection }` );
		const elSectionTitleInput = elSection.querySelector( `${ className.elSectionTitleInput }` );
		elSectionTitleInput.value = elSectionTitleInput.dataset.old || '';
		elSection.classList.remove( 'editing' );
		delete lpEditCurriculumShare.hasChange.title;
	}

	/* Update section description to server */
	updateSectionDescription( e, target, callBackNest ) {
		let canHandle = false;

		if ( target.closest( `${ className.elBtnUpdateDes }` ) ) {
			canHandle = true;
		} else if ( target.closest( `${ className.elSectionDesInput }` ) && e.key === 'Enter' ) {
			canHandle = true;
		}

		if ( ! canHandle ) {
			return;
		}

		e.preventDefault();

		const elSectionDesc = target.closest( `${ className.elSectionDesc }` );
		if ( ! elSectionDesc ) {
			return;
		}

		const elSectionDesInput = elSectionDesc.querySelector( `${ className.elSectionDesInput }` );
		if ( ! elSectionDesInput ) {
			return;
		}

		const elSection = elSectionDesInput.closest( `${ className.elSection }` );
		const sectionId = elSection.dataset.sectionId;
		const descValue = elSectionDesInput.value.trim();
		const descValueOld = elSectionDesInput.dataset.old || '';

		if ( descValue === descValueOld ) {
			return;
		}

		this.lpUtils.lpSetLoadingEl( elSection, 1 );

		const callBack = {
			success: ( response ) => {
				const { message, status } = response;

				if ( callBackNest && typeof callBackNest.success === 'function' ) {
					callBackNest.success( elSection, response );
				}

				this.showToast( message, status );
			},
			error: ( error ) => {
				this.showToast( error, 'error' );
				if ( callBackNest && typeof callBackNest.error === 'function' ) {
					callBackNest.error( elSection, error );
				}
			},
			completed: () => {
				this.lpUtils.lpSetLoadingEl( elSection, 0 );
				const elSectionDesc = elSectionDesInput.closest( `${ className.elSectionDesc }` );
				elSectionDesc.classList.remove( 'editing' );
				elSectionDesInput.dataset.old = descValue;
				if ( callBackNest && typeof callBackNest.completed === 'function' ) {
					callBackNest.completed( elSection );
				}
			},
		};

		const dataSend = {
			action: 'update_section',
			course_id: this.courseId,
			section_id: sectionId,
			section_description: descValue,
			args: { id_url: idUrlHandle },
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/* Cancel updating section description */
	cancelSectionDescription( e, target ) {
		const elBtnCancelUpdateDes = target.closest( `${ className.elBtnCancelUpdateDes }` );
		if ( ! elBtnCancelUpdateDes ) {
			return;
		}

		const elSectionDesc = elBtnCancelUpdateDes.closest( `${ className.elSectionDesc }` );
		const elSectionDesInput = elSectionDesc.querySelector( `${ className.elSectionDesInput }` );
		elSectionDesInput.value = elSectionDesInput.dataset.old || '';
		elSectionDesc.classList.remove( 'editing' );
	}

	/* Typing in description input */
	changeDescription( e, target ) {
		const elSectionDesInput = target.closest( `${ className.elSectionDesInput }` );
		if ( ! elSectionDesInput ) {
			return;
		}

		const elSectionDesc = elSectionDesInput.closest( `${ className.elSectionDesc }` );
		const descValue = elSectionDesInput.value.trim();
		const descValueOld = elSectionDesInput.dataset.old || '';

		if ( descValue === descValueOld ) {
			elSectionDesc.classList.remove( 'editing' );
		} else {
			elSectionDesc.classList.add( 'editing' );
		}
	}

	/* Toggle section */
	toggleSection( e, target ) {
		const elSectionToggle = target.closest( `${ className.elSectionToggle }` );
		if ( ! elSectionToggle ) {
			return;
		}

		const elSection = elSectionToggle.closest( `${ className.elSection }` );

		const elCurriculumSections = elSection.closest( `${ className.elCurriculumSections }` );
		if ( ! elCurriculumSections ) {
			return;
		}

		// Toggle section
		elSection.classList.toggle( `${ className.elCollapse }` );

		// Check all sections collapsed
		this.checkAllSectionsCollapsed();
	}

	/* Check if all sections are collapsed */
	checkAllSectionsCollapsed() {
		const elSections = this.elEditCurriculum.querySelectorAll( `${ className.elSection }:not(.clone)` );
		const elToggleAllSections = this.elEditCurriculum.querySelector( `${ className.elToggleAllSections }` );

		let isAllExpand = true;
		elSections.forEach( ( el ) => {
			if ( el.classList.contains( `${ className.elCollapse }` ) ) {
				isAllExpand = false;
				return false; // Break the loop
			}
		} );

		if ( isAllExpand ) {
			elToggleAllSections.classList.remove( `${ className.elCollapse }` );
		} else {
			elToggleAllSections.classList.add( `${ className.elCollapse }` );
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

				const elSection = target.closest( `${ className.elSection }` );
				const elSections = this.elCurriculumSections.querySelectorAll( `${ className.elSection }` );
				const sectionIds = [];

				elSections.forEach( ( elSection ) => {
					const sectionId = elSection.dataset.sectionId;
					sectionIds.push( sectionId );
				} );

				// Call ajax to update section position
				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						this.showToast( message, status );
					},
					error: ( error ) => {
						this.showToast( error, 'error' );
					},
					completed: () => {
						this.lpUtils.lpSetLoadingEl( elSection, 0 );
						isUpdateSectionPosition = 0;
					},
				};

				const dataSend = {
					action: 'update_section_position',
					course_id: this.courseId,
					new_position: sectionIds,
					args: { id_url: idUrlHandle },
				};

				clearTimeout( timeout );
				timeout = setTimeout( () => {
					this.lpUtils.lpSetLoadingEl( elSection, 1 );
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
	updateCountSections() {
		const elCountSections = this.elEditCurriculum.querySelector( `${ className.elCountSections }` );
		const elSections = this.elCurriculumSections.querySelectorAll( `${ className.elSection }:not(.clone)` );
		const sectionsCount = elSections.length;

		elCountSections.dataset.count = sectionsCount;
		elCountSections.querySelector( '.count' ).textContent = sectionsCount;
	}
}

// Export singleton so other modules can call methods the same way as before
export default new EditSection();
export { EditSection, className };
