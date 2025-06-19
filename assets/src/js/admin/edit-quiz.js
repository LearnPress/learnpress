/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.1
 */
import * as lpUtils from '../utils.js';

let elEditQuiz;
let elListQuestions;

const className = {
	idEditQuiz: '#admin-editor-lp_quiz',
	elCurriculumSections: '.curriculum-sections',
	elSection: '.section',
	elToggleAllSections: '.course-toggle-all-sections',
	elSectionItem: '.section-item',
	LPTarget: '.lp-target',
	elCollapse: 'lp-collapse',
};
const argsToastify = {
	text: '',
	gravity: lpDataAdmin.toast.gravity, // `top` or `bottom`
	position: lpDataAdmin.toast.position, // `left`, `center` or `right`
	className: `${ lpDataAdmin.toast.classPrefix }`,
	close: lpDataAdmin.toast.close == 1,
	stopOnFocus: lpDataAdmin.toast.stopOnFocus == 1,
	duration: lpDataAdmin.toast.duration,
};
const showToast = ( message, status = 'success' ) => {
	const toastify = new Toastify( {
		...argsToastify,
		text: message,
		className: `${ lpDataAdmin.toast.classPrefix } ${ status }`,
	} );
	toastify.showToast();
};

// Toggle all sections
const toggleSectionAll = ( e, target ) => {
	const elToggleAllSections = target.closest( `${ className.elToggleAllSections }` );
	if ( ! elToggleAllSections ) {
		return;
	}

	const elSections = lpEditCurriculumShare.elEditCurriculum.querySelectorAll( `${ className.elSection }:not(.clone)` );

	elToggleAllSections.classList.toggle( `${ className.elCollapse }` );

	if ( elToggleAllSections.classList.contains( `${ className.elCollapse }` ) ) {
		elSections.forEach( ( el ) => {
			if ( ! el.classList.contains( `${ className.elCollapse }` ) ) {
				el.classList.add( `${ className.elCollapse }` );
			}
		} );
	} else {
		elSections.forEach( ( el ) => {
			if ( el.classList.contains( `${ className.elCollapse }` ) ) {
				el.classList.remove( `${ className.elCollapse }` );
			}
		} );
	}
};

// Update count items in each section and all sections
const updateCountItems = ( elSection ) => {
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
	// End count items in section
};

// Get section by id
const initTinyMCE = () => {
	const elTextareas = elListQuestions.querySelectorAll( '.lp-editor-tinymce' );

	elTextareas.forEach( ( elTextarea ) => {
		const elParent = elTextarea.closest( '.lp-question-item' );
		const idTextarea = elParent.dataset.questionId;
		window.tinymce.execCommand( 'mceRemoveEditor', true, 'lp-question-description-' + idTextarea );
		window.tinymce.execCommand( 'mceAddEditor', true, 'lp-question-description-' + idTextarea );

		window.tinymce.execCommand( 'mceRemoveEditor', true, 'lp-question-hint-' + idTextarea );
		window.tinymce.execCommand( 'mceAddEditor', true, 'lp-question-hint-' + idTextarea );

		window.tinymce.execCommand( 'mceRemoveEditor', true, 'lp-question-explanation-' + idTextarea );
		window.tinymce.execCommand( 'mceAddEditor', true, 'lp-question-explanation-' + idTextarea );
	} );
};

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	/*** Event of Section ***/

	// Collapse/Expand all sections
	//toggleSectionAll( e, target );
} );
document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
} );
document.addEventListener( 'keyup', ( e ) => {

} );
// Event focus in
document.addEventListener( 'focusin', ( e ) => {

} );
// Event focus out
document.addEventListener( 'focusout', ( e ) => {

} );

// Element root ready.
lpUtils.lpOnElementReady( `${ className.idEditQuiz }`, ( elEditQuiz ) => {
	elListQuestions = elEditQuiz.querySelector( '.lp-list-questions' );
	initTinyMCE();
} );

