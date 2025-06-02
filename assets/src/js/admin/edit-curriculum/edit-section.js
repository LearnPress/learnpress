/**
 * Edit Section Script on Curriculum
 *
 * @version 1.0.0
 * @since 4.2.8.6
 */
// import * as lpUtils from '../../utils.js';
import * as lpEditCurriculumShare from './share.js';

// Add new section
let elEditCurriculum;
let elCurriculumSections;
let dataSend;
let className;
let showToast;

const addSection = ( e, target ) => {
	elCurriculumSections = lpEditCurriculumShare.elCurriculumSections;
	const elAddNewSection = target.closest( '.add-new-section' );
	if ( ! elAddNewSection ) {
		return;
	}

	console.log(elCurriculumSections);return;

	e.preventDefault();

	const elSectionClone = elCurriculumSections.querySelector( `.${ className.elSectionClone }` );

	console.log(elSectionClone);return;

	const elSectionTitleInput = elAddNewSection.querySelector( `${ className.elSectionNewInput }` );
	const titleSectionValue = elSectionTitleInput.value.trim();
	const message = elSectionTitleInput.dataset.messEmptyTitle;
	if ( titleSectionValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	elSectionTitleInput.value = ''; // Clear input after add

	// Add and set data for new section
	const newSection = elSectionClone.cloneNode( true );
	lpUtils.lpShowHideEl( newSection, 1 );
	lpUtils.lpSetLoadingEl( newSection, 1 );
	const titleNewSection = newSection.querySelector( `${ className.elSectionTitleInput }` );
	titleNewSection.value = titleSectionValue;
	elCurriculumSections.insertAdjacentElement( 'beforeend', newSection );
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
			newSection.classList.remove( `${ className.elSectionClone }` );
			lpUtils.lpSetLoadingEl( newSection, 0 );
			newSection.classList.remove( `${ className.elCollapse }` );
		},
	};

	dataSend.callback.method = 'handle_edit_course_curriculum';
	dataSend.args.action = 'add_section';
	dataSend.args.title = titleSectionValue;
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

export { addSection };

