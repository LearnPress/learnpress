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

let {
	courseId,
	elEditCurriculum,
	elCurriculumSections,
	showToast,
	lpUtils,
	updateCountItems,
} = lpEditCurriculumShare;

const idUrlHandle = 'edit-course-curriculum';

const init = () => {
	( {
		courseId,
		elEditCurriculum,
		elCurriculumSections,
		showToast,
		lpUtils,
		updateCountItems,
	} = lpEditCurriculumShare );
};

// Typing in new section title input
const changeTitleBeforeAdd = ( e, target ) => {
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
};

// Focus on new section title input
const focusTitleNewInput = ( e, target, focusIn = true ) => {
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
};

// Add new section
const addSection = ( e, target ) => {
	let canHandle = false;

	if ( target.closest( `${ className.elBtnAddSection }` ) ) {
		canHandle = true;
	} else if ( target.closest( `${ className.elSectionTitleNewInput }` ) &&
		e.key === 'Enter' ) {
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
		showToast( message, 'error' );
		return;
	}

	// Clear input after add
	elSectionTitleNewInput.value = '';
	elSectionTitleNewInput.blur();

	// Add and set data for new section
	const elSectionClone = elCurriculumSections.querySelector( `${ className.elSectionClone }` );
	const newSection = elSectionClone.cloneNode( true );
	newSection.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( newSection, 1 );
	lpUtils.lpSetLoadingEl( newSection, 1 );
	const elSectionTitleInput = newSection.querySelector( `${ className.elSectionTitleInput }` );
	elSectionTitleInput.value = titleValue;
	elCurriculumSections.insertAdjacentElement( 'beforeend', newSection );
	// End

	// Call ajax to add new section
	const callBack = {
		success: ( response ) => {
			const { message, status, data } = response;

			if ( status === 'error' ) {
				newSection.remove();
			} else if ( status === 'success' ) {
				const { section } = data;
				newSection.dataset.sectionId = section.section_id || '';

				if ( lpEditCurriculumShare.sortAbleItem ) {
					lpEditCurriculumShare.sortAbleItem();
				}
			}

			showToast( message, status );
		},
		error: ( error ) => {
			newSection.remove();
			showToast( error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( newSection, 0 );
			newSection.classList.remove( `${ className.elCollapse }` );
			const elSectionDesInput = newSection.querySelector( `${ className.elSectionDesInput }` );
			elSectionDesInput.focus();
			updateCountSections();
			delete lpEditCurriculumShare.hasChange.titleNew;
		},
	};

	const dataSend = {
		action: 'add_section',
		course_id: courseId,
		section_name: titleValue,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Delete section
const deleteSection = ( e, target ) => {
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

			lpUtils.lpSetLoadingEl( elSection, 1 );

			// Call ajax to delete section
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
					elSection.remove();
					updateCountItems( elSection );
					updateCountSections();
				},
			};

			const dataSend = {
				action: 'delete_section',
				course_id: courseId,
				section_id: sectionId,
				args: {
					id_url: idUrlHandle,
				},
			};
			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
	} );
};

// Focus on new section title input
const focusTitleInput = ( e, target, focusIn = true ) => {
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
};

// Set focus on section title input
const setFocusTitleInput = ( e, target ) => {
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
};

// Typing in description input
const changeTitle = ( e, target ) => {
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
};

// Update section title to server
const updateSectionTitle = ( e, target ) => {
	let canHandle = false;

	if ( target.closest( `${ className.elBtnUpdateTitle }` ) ) {
		canHandle = true;
	} else if ( target.closest( `${ className.elSectionTitleInput }` ) &&
		e.key === 'Enter' ) {
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
		showToast( message, 'error' );
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

			showToast( message, status );

			if ( status === 'success' ) {
				elSectionTitleInput.dataset.old = titleValue;
			}
		},
		error: ( error ) => {
			showToast( error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elSection, 0 );
			elSection.classList.remove( 'editing' );
			delete lpEditCurriculumShare.hasChange.title;
		},
	};

	const dataSend = {
		action: 'update_section',
		course_id: courseId,
		section_id: sectionId,
		section_name: titleValue,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Cancel updating section description
const cancelSectionTitle = ( e, target ) => {
	const elBtnCancelUpdateTitle = target.closest( `${ className.elBtnCancelUpdateTitle }` );
	if ( ! elBtnCancelUpdateTitle ) {
		return;
	}

	const elSection = elBtnCancelUpdateTitle.closest( `${ className.elSection }` );
	const elSectionTitleInput = elSection.querySelector( `${ className.elSectionTitleInput }` );
	elSectionTitleInput.value = elSectionTitleInput.dataset.old || ''; // Reset to old value
	elSection.classList.remove( 'editing' ); // Remove editing class
	delete lpEditCurriculumShare.hasChange.title;
};

// Update section description to server
const updateSectionDescription = ( e, target ) => {
	let canHandle = false;

	if ( target.closest( `${ className.elBtnUpdateDes }` ) ) {
		canHandle = true;
	} else if ( target.closest( `${ className.elSectionDesInput }` ) &&
		e.key === 'Enter' ) {
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

	lpUtils.lpSetLoadingEl( elSection, 1 );

	// Call ajax to update section description
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;

			showToast( message, status );
		},
		error: ( error ) => {
			showToast( error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elSection, 0 );
			const elSectionDesc = elSectionDesInput.closest( `${ className.elSectionDesc }` );
			elSectionDesc.classList.remove( 'editing' );
			elSectionDesInput.dataset.old = descValue; // Update old value
		},
	};

	const dataSend = {
		action: 'update_section',
		course_id: courseId,
		section_id: sectionId,
		section_description: descValue,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Cancel updating section description
const cancelSectionDescription = ( e, target ) => {
	const elBtnCancelUpdateDes = target.closest( `${ className.elBtnCancelUpdateDes }` );
	if ( ! elBtnCancelUpdateDes ) {
		return;
	}

	const elSectionDesc = elBtnCancelUpdateDes.closest( `${ className.elSectionDesc }` );
	const elSectionDesInput = elSectionDesc.querySelector( `${ className.elSectionDesInput }` );
	elSectionDesInput.value = elSectionDesInput.dataset.old || ''; // Reset to old value
	elSectionDesc.classList.remove( 'editing' ); // Remove editing class
};

// Typing in description input
const changeDescription = ( e, target ) => {
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
};

// Toggle section
const toggleSection = ( e, target ) => {
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
	checkAllSectionsCollapsed();
};

// Check if all sections are collapsed
const checkAllSectionsCollapsed = () => {
	const elSections = elEditCurriculum.querySelectorAll( `${ className.elSection }:not(.clone)` );
	const elToggleAllSections = elEditCurriculum.querySelector( `${ className.elToggleAllSections }` );

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
};

// Sortable sections, drag and drop to change section position
const sortAbleSection = () => {
	let isUpdateSectionPosition = 0;
	let timeout;

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

			const dataSend = {
				action: 'update_section_position',
				course_id: courseId,
				new_position: sectionIds,
				args: {
					id_url: idUrlHandle,
				},
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
};

// Update count sections, when add or delete section
const updateCountSections = () => {
	const elCountSections = elEditCurriculum.querySelector( `${ className.elCountSections }` );
	const elSections = elCurriculumSections.querySelectorAll( `${ className.elSection }:not(.clone)` );
	const sectionsCount = elSections.length;

	elCountSections.dataset.count = sectionsCount;
	elCountSections.querySelector( '.count' ).textContent = sectionsCount;
};

export {
	init,
	changeTitleBeforeAdd,
	focusTitleNewInput,
	addSection,
	deleteSection,
	changeTitle,
	focusTitleInput,
	setFocusTitleInput,
	updateSectionTitle,
	cancelSectionTitle,
	updateSectionDescription,
	cancelSectionDescription,
	changeDescription,
	toggleSection,
	sortAbleSection,
};

