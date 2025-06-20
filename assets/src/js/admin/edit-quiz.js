/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.1
 */
import * as lpUtils from '../utils.js';
import SweetAlert from 'sweetalert2';
import Sortable from 'sortablejs';

let elEditQuizWrap;
let elEditListQuestions;

const className = {
	elEditQuizWrap: '.lp-edit-quiz-wrap',
	elQuestionToggleAll: '.lp-question-toggle-all',
	elEditListQuestions: '.lp-edit-list-questions',
	elQuestionItem: '.lp-question-item',
	elQuestionToggle: '.lp-question-toggle',
	elBtnShowPopupItemsToSelect: '.lp-btn-show-popup-items-to-select',
	elPopupItemsToSelectClone: '.lp-popup-items-to-select.clone',
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
const toggleQuestionAll = ( e, target ) => {
	const elQuestionToggleAll = target.closest( `${ className.elQuestionToggleAll }` );
	if ( ! elQuestionToggleAll ) {
		return;
	}

	const elQuestionItems = elEditQuizWrap.querySelectorAll( `${ className.elQuestionItem }:not(.clone)` );

	elQuestionToggleAll.classList.toggle( `${ className.elCollapse }` );

	elQuestionItems.forEach( ( el ) => {
		const shouldCollapse = elQuestionToggleAll.classList.contains( `${ className.elCollapse }` );
		el.classList.toggle( `${ className.elCollapse }`, shouldCollapse );
	} );
};

// Toggle section
const toggleQuestion = ( e, target ) => {
	const elSectionToggle = target.closest( `${ className.elQuestionToggle }` );
	if ( ! elSectionToggle ) {
		return;
	}

	const elQuestionItem = elSectionToggle.closest( `${ className.elQuestionItem }` );

	// Toggle section
	elQuestionItem.classList.toggle( `${ className.elCollapse }` );

	// Check all sections collapsed
	checkAllQuestionsCollapsed();
};

// Check if all sections are collapsed
const checkAllQuestionsCollapsed = () => {
	const elQuestionItems = elEditQuizWrap.querySelectorAll( `${ className.elQuestionItem }:not(.clone)` );
	const elQuestionToggleAll = elEditQuizWrap.querySelector( `${ className.elQuestionToggleAll }` );

	let isAllExpand = true;
	elQuestionItems.forEach( ( el ) => {
		if ( el.classList.contains( `${ className.elCollapse }` ) ) {
			isAllExpand = false;
			return false; // Break the loop
		}
	} );

	if ( isAllExpand ) {
		elQuestionToggleAll.classList.remove( `${ className.elCollapse }` );
	} else {
		elQuestionToggleAll.classList.add( `${ className.elCollapse }` );
	}
};

let elPopupSelectItems;
let itemsSelectedData = [];
// Show popup items to select
const showPopupItemsToSelect = ( e, target ) => {
	const elBtnShowPopupItemsToSelect = target.closest( `${ className.elBtnShowPopupItemsToSelect }` );
	if ( ! elBtnShowPopupItemsToSelect ) {
		return;
	}

	const elPopupItemsToSelectClone = elEditQuizWrap.querySelector( `${ className.elPopupItemsToSelectClone }` );
	elPopupSelectItems = elPopupItemsToSelectClone.cloneNode( true );
	elPopupSelectItems.classList.remove( 'clone' );
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
			// Trigger tab lesson to be active and call AJAX load items
			const elLPTarget = elPopupSelectItems.querySelector( `${ className.LPTarget }` );

			const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
			dataSend.args.paged = 1;
			dataSend.args.item_selecting = itemsSelectedData || [];
			window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

			// Show loading
			window.lpAJAXG.showHideLoading( elLPTarget, 1 );
			// End

			window.lpAJAXG.fetchAJAX( dataSend, {
				success: ( response ) => {
					const { data } = response;
					elLPTarget.innerHTML = data.content || '';
				},
				error: ( error ) => {
					showToast( error, 'error' );
				},
				completed: () => {
					window.lpAJAXG.showHideLoading( elLPTarget, 0 );
					// Show button add if there are items selected
					//watchItemsSelectedDataChange();
				},
			} );
		},
	} ).then( ( result ) => {
		//if ( result.isDismissed ) {}
	} );
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
	const elTextareas = elEditListQuestions.querySelectorAll( '.lp-editor-tinymce' );

	elTextareas.forEach( ( elTextarea ) => {
		// const elParent = elTextarea.closest( '.lp-question-item' );
		const idTextarea = elTextarea.id;

		reInitTinymce( idTextarea );
	} );
};

// Re-initialize TinyMCE editor
const reInitTinymce = ( id ) => {
	window.tinymce.execCommand( 'mceRemoveEditor', true, id );
	window.tinymce.execCommand( 'mceAddEditor', true, id );
	eventEditorTinymceChange( id );
};

// Events for TinyMCE editor
const eventEditorTinymceChange = ( id, callBack ) => {
	const editor = window.tinymce.get( id );
	// Event change content in TinyMCE editor
	editor.on( 'change', ( e ) => {
		// Get editor content
		const content = e.target.getContent();
		console.log( 'Editor content changed:', content );

		// Save content automatically
		//window.tinymce.triggerSave();
	} );
	// Event focus in TinyMCE editor
	editor.on( 'focusin', ( e ) => {
		console.log( 'Editor focused:', e.target.id );
	} );
};

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	toggleQuestionAll( e, target );
	toggleQuestion( e, target );
	showPopupItemsToSelect( e, target );
} );
document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
} );
document.addEventListener( 'keyup', ( e ) => {
	const target = e.target;
	console.log( 'keyup', target );
	if ( target.classList.contains( 'lp-editor-tinymce' ) ) {
		//window.tinymce.triggerSave();
		console.log( 'keyup', target.value );
	}
} );
// Event focus in
document.addEventListener( 'focusin', ( e ) => {
	//console.log( 'focusin', e.target );
} );
// Event focus out
document.addEventListener( 'focusout', ( e ) => {

} );

// Element root ready.
lpUtils.lpOnElementReady( `${ className.elEditQuizWrap }`, ( elEditQuizWrapFound ) => {
	elEditQuizWrap = elEditQuizWrapFound;
	elEditListQuestions = elEditQuizWrap.querySelector( `${ className.elEditListQuestions }` );
	initTinyMCE();
} );

