/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.1
 */
import * as lpUtils from '../utils.js';
import SweetAlert from 'sweetalert2-neutral';
import Sortable from 'sortablejs';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import * as lpEditCurriculumShare from "./edit-quiz/share";

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
	elBtnAddQuestion: '.lp-btn-add-question',
	elBtnRemoveQuestion: '.lp-btn-remove-question',
	elBtnUpdateQuestionTitle: '.lp-btn-update-question-title',
	elQuestionTitleNewInput: '.lp-question-title-new-input',
	elQuestionTitleInput: '.lp-question-title-input',
	elQuestionTypeNew: '.lp-question-type-new',
	elAddNewQuestion: 'add-new-question',
	elQuestionClone: '.lp-question-item.clone',
	elAnswersConfig: '.lp-answers-config',
	LPTarget: '.lp-target',
	elCollapse: 'lp-collapse',
};
let quizID;
const idUrlHandle = 'edit-quiz-questions';
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
const itemsSelectedData = [];
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
	window.tinymce.execCommand( 'mceRemoveEditor', false, id );
	window.tinymce.execCommand( 'mceAddEditor', false, id );
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

const addQuestion = ( e, target ) => {
	const elBtnAddQuestion = target.closest( `${ className.elBtnAddQuestion }` );
	if ( ! elBtnAddQuestion ) {
		return;
	}

	const elAddNewQuestion = elBtnAddQuestion.closest( `.${ className.elAddNewQuestion }` );
	if ( ! elAddNewQuestion ) {
		return;
	}

	const elQuestionTitleNewInput = elAddNewQuestion.querySelector( `${ className.elQuestionTitleNewInput }` );
	const questionType = elAddNewQuestion.querySelector( `${ className.elQuestionTypeNew }` ).value;
	const questionTitle = elQuestionTitleNewInput.value.trim();
	if ( ! questionTitle ) {
		showToast( 'Please enter a question title.', 'error' );
		return;
	}

	const elQuestionClone = elEditListQuestions.querySelector( `${ className.elQuestionItem }.clone` );
	const newQuestionItem = elQuestionClone.cloneNode( true );
	const newQuestionItemTypes = newQuestionItem.querySelectorAll( '.lp-question-answers' );
	const elQuestionTitleInput = newQuestionItem.querySelector( `${ className.elQuestionTitleInput }` );
	newQuestionItemTypes.forEach( ( el ) => {
		const typeQuestion = el.dataset.questionType;
		if ( typeQuestion !== questionType ) {
			el.remove();
		}
	} );
	elQuestionTitleInput.value = questionTitle;

	newQuestionItem.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( newQuestionItem, 1 );
	elQuestionClone.insertAdjacentElement( 'beforebegin', newQuestionItem );
	lpUtils.lpSetLoadingEl( newQuestionItem, 1 );

	// Call ajax to add new question
	const callBack = {
		success: ( response ) => {
			const { message, status, data } = response;
			const { question, quizQuestions, html_question_answers } = data;

			if ( status === 'error' ) {
				newQuestionItem.remove();
			} else if ( status === 'success' ) {
				newQuestionItem.dataset.questionId = question.ID;
				const elAnswersConfig = newQuestionItem.querySelector( `${ className.elAnswersConfig }` );
				elAnswersConfig.innerHTML = html_question_answers || '';
			}

			showToast( message, status );
		},
		error: ( error ) => {
			newQuestionItem.remove();
			showToast( error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( newQuestionItem, 0 );
			newQuestionItem.classList.remove( `${ className.elCollapse }` );
		},
	};

	const dataSend = {
		action: 'add_question_to_quiz',
		quiz_id: quizID,
		question_title: questionTitle,
		question_type: questionType,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

const removeQuestion = ( e, target ) => {
	const elBtnRemoveQuestion = target.closest( `${ className.elBtnRemoveQuestion }` );
	if ( ! elBtnRemoveQuestion ) {
		return;
	}

	const elQuestionItem = elBtnRemoveQuestion.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const questionId = elQuestionItem.dataset.questionId;

	SweetAlert.fire( {
		title: elBtnRemoveQuestion.dataset.title,
		text: elBtnRemoveQuestion.dataset.content,
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		cancelButtonText: lpDataAdmin.i18n.cancel,
		confirmButtonText: lpDataAdmin.i18n.yes,
		reverseButtons: true,
	} ).then( ( result ) => {
		if ( result.isConfirmed ) {
			lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

			// Call ajax to delete item from section
			const callBack = {
				success: ( response ) => {
					const { message, status } = response;

					showToast( message, status );

					if ( status === 'success' ) {
						elQuestionItem.remove();
					}
				},
				error: ( error ) => {
					showToast( error, 'error' );
				},
				completed: () => {
					lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
				},
			};

			const dataSend = {
				quiz_id: quizID,
				action: 'remove_question_from_quiz',
				question_id: questionId,
				args: {
					id_url: idUrlHandle,
				},
			};
			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
	} );
};

// Update item title
const updateQuestionTitle = ( e, target ) => {
	let canHandle = false;

	if ( target.closest( `${ className.elBtnUpdateQuestionTitle }` ) ) {
		canHandle = true;
	} else if ( target.closest( `${ className.elQuestionTitleInput }` ) &&
		e.key === 'Enter' ) {
		canHandle = true;
	}

	if ( ! canHandle ) {
		return;
	}

	e.preventDefault();

	const elQuestionItem = target.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const elQuestionTitleInput = elQuestionItem.querySelector( `${ className.elQuestionTitleInput }` );
	if ( ! elQuestionTitleInput ) {
		return;
	}

	const questionId = elQuestionItem.dataset.questionId;
	const questionTitleValue = elQuestionTitleInput.value.trim();
	const titleOld = elQuestionTitleInput.dataset.old;
	const message = elQuestionTitleInput.dataset.messEmptyTitle;
	if ( questionTitleValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	if ( questionTitleValue === titleOld ) {
		return;
	}

	// Un-focus input item title
	elQuestionTitleInput.blur();
	// show loading
	lpUtils.lpSetLoadingEl( elQuestionItem, 1 );
	// Call ajax to update item title
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;

			if ( status === 'success' ) {
				elQuestionTitleInput.dataset.old = questionTitleValue; // Update value input
			} else {
				elQuestionTitleInput.value = titleOld;
			}

			showToast( message, status );
		},
		error: ( error ) => {
			showToast( error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
			elQuestionItem.classList.remove( 'editing' ); // Remove editing class
		},
	};

	const dataSend = {
		quiz_id: quizID,
		action: 'update_question',
		question_id: questionId,
		question_title: questionTitleValue,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	toggleQuestionAll( e, target );
	toggleQuestion( e, target );
	showPopupItemsToSelect( e, target );
	addQuestion( e, target );
	removeQuestion( e, target );
	updateQuestionTitle( e, target );
} );
// Event keydown
document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
} );
// Event keyup
document.addEventListener( 'keyup', ( e ) => {
	const target = e.target;
	//console.log( 'keyup', target );
	if ( target.classList.contains( 'lp-editor-tinymce' ) ) {
		//window.tinymce.triggerSave();
		//console.log( 'keyup', target.value );
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
	const elLPTarget = elEditQuizWrap.closest( `${ className.LPTarget }` );
	const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
	quizID = dataSend.args.quiz_id;

	initTinyMCE();
} );

