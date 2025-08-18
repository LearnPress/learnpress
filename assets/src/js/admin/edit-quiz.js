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
import * as lpPopupSelectItemToAdd from '../lpPopupSelectItemToAdd.js';

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
	elBtnCancelUpdateQuestionTitle: '.lp-btn-cancel-update-question-title',
	elBtnUpdateQuestionDes: '.lp-btn-update-question-des',
	elBtnUpdateQuestionHint: '.lp-btn-update-question-hint',
	elBtnUpdateQuestionExplain: '.lp-btn-update-question-explanation',
	elQuestionTitleNewInput: '.lp-question-title-new-input',
	elQuestionTitleInput: '.lp-question-title-input',
	elQuestionTypeLabel: '.lp-question-type-label',
	elQuestionTypeNew: '.lp-question-type-new',
	elAddNewQuestion: 'add-new-question',
	elQuestionClone: '.lp-question-item.clone',
	elAnswersConfig: '.lp-answers-config',
	elBtnAddAnswer: '.lp-btn-add-question-answer',
	elQuestionAnswerItemAddNew: '.lp-question-answer-item-add-new',
	elQuestionAnswerTitleInput: '.lp-question-answer-title-input',
	elBtnDeleteAnswer: '.lp-btn-delete-question-answer',
	elQuestionByType: '.lp-question-by-type',
	elInputAnswerSetTrue: '.lp-input-answer-set-true',
	elQuestionAnswerItem: '.lp-question-answer-item',
	elBtnUpdateQuestionAnswer: '.lp-btn-update-question-answer',
	elBtnFibInsertBlank: '.lp-btn-fib-insert-blank',
	elBtnFibDeleteAllBlanks: '.lp-btn-fib-delete-all-blanks',
	elBtnFibSaveContent: '.lp-btn-fib-save-content',
	elBtnFibClearAllContent: '.lp-btn-fib-clear-all-content',
	elFibInput: '.lp-question-fib-input',
	elFibOptionTitleInput: '.lp-question-fib-option-title-input',
	elFibBlankOptions: '.lp-question-fib-blank-options',
	elFibBlankOptionItem: '.lp-question-fib-blank-option-item',
	elFibBlankOptionItemClone: '.lp-question-fib-blank-option-item.clone',
	elFibBlankOptionIndex: '.lp-question-fib-option-index',
	elBtnFibOptionDelete: '.lp-btn-fib-option-delete',
	elQuestionFibOptionMatchCaseInput: '.lp-question-fib-option-match-case-input',
	elQuestionFibOptionMatchCaseWrap: '.lp-question-fib-option-match-case-wrap',
	elQuestionFibOptionDetail: '.lp-question-fib-option-detail',
	elQuestionFibOptionComparisonInput: '.lp-question-fib-option-comparison-input',
	LPTarget: '.lp-target',
	elCollapse: 'lp-collapse',
	elSectionToggle: '.lp-section-toggle',
	elTriggerToggle: '.lp-trigger-toggle',
	elAutoSave: '.lp-auto-save',
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
	const elQuestionToggleAll = target.closest(
		`${ className.elQuestionToggleAll }`
	);
	if ( ! elQuestionToggleAll ) {
		return;
	}

	const elQuestionItems = elEditQuizWrap.querySelectorAll(
		`${ className.elQuestionItem }:not(.clone)`
	);

	elQuestionToggleAll.classList.toggle( `${ className.elCollapse }` );

	elQuestionItems.forEach( ( el ) => {
		const shouldCollapse = elQuestionToggleAll.classList.contains(
			`${ className.elCollapse }`
		);
		el.classList.toggle( `${ className.elCollapse }`, shouldCollapse );
	} );
};

// Toggle section
const toggleQuestion = ( e, target ) => {
	const elSectionToggle = target.closest( `${ className.elQuestionToggle }` );
	if ( ! elSectionToggle ) {
		return;
	}

	const elQuestionItem = elSectionToggle.closest(
		`${ className.elQuestionItem }`
	);

	// Toggle section
	elQuestionItem.classList.toggle( `${ className.elCollapse }` );

	// Check all sections collapsed
	checkAllQuestionsCollapsed();
};

/**
 * Toggle section
 *
 * @param e
 * @param target
 * @param el_trigger is class name or id name, to find of element to trigger toggle
 */
const toggleSection = ( e, target, el_trigger = '' ) => {
	if ( ! el_trigger ) {
		el_trigger = className.elTriggerToggle;
	}

	if ( target.closest( `${ className.elBtnUpdateQuestionDes }` ) ) {
		return;
	} else if ( target.closest( `${ className.elBtnUpdateQuestionHint }` ) ) {
		return;
	} else if ( target.closest( `${ className.elBtnUpdateQuestionExplain }` ) ) {
		return;
	} else if ( target.closest( `${ className.elBtnUpdateQuestionAnswer }` ) ) {
		return;
	}

	const elTinymceHeader = target.closest( el_trigger );
	if ( ! elTinymceHeader ) {
		return;
	}

	const elSectionToggle = elTinymceHeader.closest( `${ className.elSectionToggle }` );
	if ( ! elSectionToggle ) {
		return;
	}

	elSectionToggle.classList.toggle( `${ className.elCollapse }` );
};

// Check if all sections are collapsed
const checkAllQuestionsCollapsed = () => {
	const elQuestionItems = elEditQuizWrap.querySelectorAll(
		`${ className.elQuestionItem }:not(.clone)`
	);
	const elQuestionToggleAll = elEditQuizWrap.querySelector(
		`${ className.elQuestionToggleAll }`
	);

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

// Update count items in each section and all sections
const updateCountItems = ( elSection ) => {
	const elCountItemsAll = elEditQuizWrap.querySelector( '.total-items' );
	const elItemsAll = elEditQuizWrap.querySelectorAll(
		`${ className.elQuestionItem }:not(.clone)`
	);
	const itemsAllCount = elItemsAll.length;

	elCountItemsAll.dataset.count = itemsAllCount;
	elCountItemsAll.querySelector( '.count' ).textContent = itemsAllCount;
};

// Get section by id
const initTinyMCE = () => {
	const elTextareas =
		elEditListQuestions.querySelectorAll( '.lp-editor-tinymce' );

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

	const wrapEditor = document.querySelector( `#wp-${ id }-wrap` );
	if ( wrapEditor ) {
		wrapEditor.classList.add( 'tmce-active' );
		wrapEditor.classList.remove( 'html-active' );
	}
};

let uniquid;
let fibTextSelection;
let fibSelection;
// Events for TinyMCE editor
const eventEditorTinymceChange = ( id, callBack ) => {
	const editor = window.tinymce.get( id );
	const elTextarea = document.getElementById( id );
	const elQuestionItem = elTextarea.closest( `${ className.elQuestionItem }` );
	const questionId = elQuestionItem.dataset.questionId;
	editor.settings.force_p_newlines = false;
	editor.settings.forced_root_block = '';
	editor.settings.force_br_newlines = true;
	editor.settings.content_style = '' +
		'body{ line-height: 2.2;}  ' +
		'.lp-question-fib-input{border: 1px dashed rebeccapurple;padding: 5px; } ';
	// Events focus in TinyMCE editor
	editor.on( 'change', ( e ) => {

	} );
	editor.on( 'keyup', ( e ) => {
		// Auto save if it has class lp-auto-save
		elTextarea.value = editor.getContent();
		autoUpdateQuestion( e, elTextarea );
	} );
	editor.on( 'blur', ( e ) => {
		console.log( 'Editor blurred:', e.target.id );
	} );
	editor.on( 'focusin', ( e ) => {} );
	editor.on( 'init', () => {} );
	editor.on( 'setcontent', ( e ) => {
		const elementg = editor.dom.select( `.lp-question-fib-input[data-id="${ uniquid }"]` );
		if ( elementg[ 0 ] ) {
			elementg[ 0 ].focus();
		}

		editor.dom.bind( elementg[ 0 ], 'input', function( e ) {
			console.log( 'Input changed:', e.target.value );
		} );
	} );
	editor.on( 'selectionchange', ( e ) => {
		fibSelection = editor.selection;

		// Check selection is blank, check empty blank content
		if ( fibSelection.getNode().classList.contains( 'lp-question-fib-input' ) ) {
			const blankId = fibSelection.getNode().dataset.id;
			const textBlank = fibSelection.getNode().textContent.trim();
			if ( textBlank.length === 0 ) {
				const editorId = editor.id;
				const questionId = editorId.replace( 'lp-question-fib-input-', '' );
				const elQuestionItem = document.querySelector( `${ className.elQuestionItem }[data-question-id="${ questionId }"]` );
				const elQuestionBlankOptions = elQuestionItem.querySelector( `${ className.elFibBlankOptions }` );
				const elFibBlankOptionItem = elQuestionBlankOptions.querySelector( `${ className.elFibBlankOptionItem }[data-id="${ blankId }"]` );
				if ( elFibBlankOptionItem ) {
					lpUtils.lpShowHideEl( elFibBlankOptionItem, 0 );
				}
			} else {
				const elTextarea = document.getElementById( id );
				const elAnswersConfig = elTextarea.closest( `${ className.elAnswersConfig }` );
				const elFibBlankOptionItem = elAnswersConfig.querySelector( `${ className.elFibBlankOptionItem }[data-id="${ blankId }"]` );
				const elFibOptionTitleInput = elFibBlankOptionItem.querySelector( `${ className.elFibOptionTitleInput }` );
				if ( elFibOptionTitleInput ) {
					elFibOptionTitleInput.value = textBlank;
				}
			}
		}
	} );
	editor.on( 'Undo', function( e ) {
		const contentUndo = editor.getContent();
		const selection = editor.selection;
		const nodeUndo = selection.getNode();
		const classNameFind = className.elFibInput.replace( '.', '' );
		if ( nodeUndo.classList.contains( `${ classNameFind }` ) ) {
			const blankId = nodeUndo.dataset.id;
			const elFibBlankOptionItem = document.querySelector( `${ className.elFibBlankOptionItem }[data-id="${ blankId }"]` );

			if ( elFibBlankOptionItem ) {
				lpUtils.lpShowHideEl( elFibBlankOptionItem, 1 );
			}
		}
	} );
	editor.on( 'Redo', function( e ) {

	} );
};

const addQuestion = ( e, target ) => {
	let canHandle = false;

	if ( target.closest( `${ className.elBtnAddQuestion }` ) ) {
		canHandle = true;
	} else if (
		target.closest( `${ className.elQuestionTitleNewInput }` ) &&
		e.key === 'Enter'
	) {
		canHandle = true;
	}

	if ( ! canHandle ) {
		return;
	}

	e.preventDefault();

	const elAddNewQuestion = target.closest(
		`.${ className.elAddNewQuestion }`
	);
	if ( ! elAddNewQuestion ) {
		return;
	}

	const elQuestionTitleNewInput = elAddNewQuestion.querySelector(
		`${ className.elQuestionTitleNewInput }`
	);
	const questionTitle = elQuestionTitleNewInput.value.trim();
	if ( ! questionTitle ) {
		showToast( elQuestionTitleNewInput.dataset.messEmptyTitle, 'error' );
		return;
	}

	const elQuestionType = elAddNewQuestion.querySelector(
		`${ className.elQuestionTypeNew }`
	);
	const questionType = elQuestionType.value;
	if ( ! questionType ) {
		showToast( elQuestionType.dataset.messEmptyType, 'error' );
		return;
	}

	const elQuestionClone = elEditListQuestions.querySelector(
		`${ className.elQuestionItem }.clone`
	);
	const newQuestionItem = elQuestionClone.cloneNode( true );
	const elQuestionTitleInput = newQuestionItem.querySelector(
		`${ className.elQuestionTitleInput }`
	);

	elQuestionTitleInput.value = questionTitle;
	elQuestionTitleNewInput.value = '';
	newQuestionItem.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( newQuestionItem, 1 );
	elQuestionClone.insertAdjacentElement( 'beforebegin', newQuestionItem );
	lpUtils.lpSetLoadingEl( newQuestionItem, 1 );

	// Call ajax to add new question
	const callBack = {
		success: ( response ) => {
			const { message, status, data } = response;
			const {
				question,
				html_edit_question,
			} = data;

			if ( status === 'error' ) {
				throw `Error: ${ message }`;
			} else if ( status === 'success' ) {
				newQuestionItem.dataset.questionId = question.ID;
				newQuestionItem.dataset.questionType = question.meta_data._lp_type;
				newQuestionItem.outerHTML = html_edit_question;
				const elQuestionItemCreated = elEditListQuestions.querySelector(
					`${ className.elQuestionItem }[data-question-id="${ question.ID }"]`
				);
				elQuestionItemCreated.classList.remove( className.elCollapse );
				updateCountItems();
				initTinyMCE();
			}

			showToast( message, status );
		},
		error: ( error ) => {
			newQuestionItem.remove();
			showToast( error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( newQuestionItem, 0 );
		},
	};

	const dataSend = {
		action: 'create_question_add_to_quiz',
		quiz_id: quizID,
		question_title: questionTitle,
		question_type: questionType,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Check to enable or disable add new question button
const checkCanAddQuestion = ( e, target ) => {
	const elTrigger = target.closest( className.elQuestionTitleNewInput ) ||
		target.closest( className.elQuestionTypeNew );
	if ( ! elTrigger ) {
		return;
	}

	const elAddNewQuestion = elTrigger.closest( `.${ className.elAddNewQuestion }` );
	if ( ! elAddNewQuestion ) {
		return;
	}

	const elBtnAddQuestion = elAddNewQuestion.querySelector( `${ className.elBtnAddQuestion }` );
	if ( ! elBtnAddQuestion ) {
		return;
	}

	const elQuestionTitleInput = elAddNewQuestion.querySelector( `${ className.elQuestionTitleNewInput }` );
	const elQuestionTypeNew = elAddNewQuestion.querySelector( `${ className.elQuestionTypeNew }` );

	const questionTitle = elQuestionTitleInput.value.trim();
	const questionType = elQuestionTypeNew.value;

	if ( questionTitle && questionType ) {
		elBtnAddQuestion.classList.add( 'active' );
	} else {
		elBtnAddQuestion.classList.remove( 'active' );
	}
};

// Remove question
const removeQuestion = ( e, target ) => {
	const elBtnRemoveQuestion = target.closest(
		`${ className.elBtnRemoveQuestion }`
	);
	if ( ! elBtnRemoveQuestion ) {
		return;
	}

	const elQuestionItem = elBtnRemoveQuestion.closest(
		`${ className.elQuestionItem }`
	);
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
						updateCountItems();
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
	} else if (
		target.closest( `${ className.elQuestionTitleInput }` ) &&
		e.key === 'Enter'
	) {
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

	const elQuestionTitleInput = elQuestionItem.querySelector(
		`${ className.elQuestionTitleInput }`
	);
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

// Typing in description input
const changeTitleQuestion = ( e, target ) => {
	const elQuestionTitleInput = target.closest( `${ className.elQuestionTitleInput }` );
	if ( ! elQuestionTitleInput ) {
		return;
	}

	const elQuestionItem = elQuestionTitleInput.closest( `${ className.elQuestionItem }` );
	const titleValue = elQuestionTitleInput.value.trim();
	const titleValueOld = elQuestionTitleInput.dataset.old || '';

	if ( titleValue === titleValueOld ) {
		elQuestionItem.classList.remove( 'editing' );
	} else {
		elQuestionItem.classList.add( 'editing' );
	}
};

// Cancel updating section description
const cancelChangeTitleQuestion = ( e, target ) => {
	const elBtnCancelUpdateQuestionTitle = target.closest( `${ className.elBtnCancelUpdateQuestionTitle }` );
	if ( ! elBtnCancelUpdateQuestionTitle ) {
		return;
	}

	const elQuestionItem = elBtnCancelUpdateQuestionTitle.closest( `${ className.elQuestionItem }` );
	const elQuestionTitleInput = elQuestionItem.querySelector( `${ className.elQuestionTitleInput }` );
	elQuestionTitleInput.value = elQuestionTitleInput.dataset.old || ''; // Reset to old value
	elQuestionItem.classList.remove( 'editing' ); // Remove editing class
};

let timeoutAutoUpdate;
const autoUpdateQuestion = ( e, target, key, value ) => {
	const elAutoSave = target.closest( `${ className.elAutoSave }` );
	if 	( ! elAutoSave ) {
		return;
	}

	const elQuestionItem = elAutoSave.closest( `${ className.elQuestionItem }` );
	const questionId = elQuestionItem.dataset.questionId;

	clearTimeout( timeoutAutoUpdate );
	timeoutAutoUpdate = setTimeout( () => {
		//lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

		// Call ajax to update question description
		const callBack = {
			success: ( response ) => {
				const { message, status } = response;

				if ( status === 'success' ) {
					showToast( message, status );
				} else {
					throw `Error: ${ message }`;
				}
			},
			error: ( error ) => {
				showToast( error, 'error' );
			},
			completed: () => {
				//lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
				//lpUtils.lpShowHideEl( elBtnUpdateQuestionDes, 0 );
			},
		};

		const dataSend = {
			action: 'update_question',
			question_id: questionId,
			args: {
				id_url: idUrlHandle,
			},
		};

		if ( undefined === key ) {
			key = elAutoSave.dataset.keyAutoSave;
			if ( ! key ) {
				if ( ! elAutoSave.classList.contains( 'lp-editor-tinymce' ) ) {
					return;
				}

				const textAreaId = elAutoSave.id;
				key = textAreaId.replace( /lp-/g, '' ).replace( `-${ questionId }`, '' ).replace( /-/g, '_' );
				if ( ! key ) {
					return;
				}
			}

			value = elAutoSave.value;
		}

		//console.log( key );

		dataSend[ key ] = value;

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}, 700 );

	//console.log( 'Auto update question triggered' );
};

const addQuestionAnswer = ( e, target ) => {
	const elBtnAddAnswer = target.closest( `${ className.elBtnAddAnswer }` );
	if ( ! elBtnAddAnswer ) {
		return;
	}

	const elQuestionItem = elBtnAddAnswer.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const elQuestionAnswerItemAddNew = elBtnAddAnswer.closest( `${ className.elQuestionAnswerItemAddNew }` );
	if ( ! elQuestionAnswerItemAddNew ) {
		return;
	}

	const elQuestionAnswerTitleInput = elQuestionAnswerItemAddNew.querySelector( `${ className.elQuestionAnswerTitleInput }` );
	if ( ! elQuestionAnswerTitleInput ) {
		return;
	}

	if ( ! elQuestionAnswerTitleInput.value.trim() ) {
		showToast( 'Please enter an answer title.', 'error' );
		return;
	}

	const elQuestionAnswerClone = elQuestionItem.querySelector( `${ className.elQuestionAnswerItem }.clone` );
	if ( ! elQuestionAnswerClone ) {
		return;
	}

	const elQuestionAnswerNew = elQuestionAnswerClone.cloneNode( true );
	const elQuestionAnswerTitleInputNew = elQuestionAnswerNew.querySelector( `${ className.elQuestionAnswerTitleInput }` );

	elQuestionAnswerNew.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( elQuestionAnswerNew, 1 );
	lpUtils.lpSetLoadingEl( elQuestionAnswerNew, 1 );
	elQuestionAnswerClone.insertAdjacentElement( 'beforebegin', elQuestionAnswerNew );

	const answerTitle = elQuestionAnswerTitleInput.value.trim();
	elQuestionAnswerTitleInputNew.value = answerTitle;
	elQuestionAnswerTitleInput.value = '';
	const questionId = elQuestionItem.dataset.questionId;

	// Call ajax to add new question answer
	const callBack = {
		success: ( response ) => {
			const { message, status, data } = response;

			if ( status === 'success' ) {
				const { question_answer } = data;
				elQuestionAnswerNew.dataset.answerId = question_answer.question_answer_id;
				lpUtils.lpSetLoadingEl( elQuestionAnswerNew, 0 );
				elQuestionAnswerNew.querySelector( `.lp-icon-spinner` ).remove();

				// Set data lp-answers-config
				const dataAnswers = getDataAnswersConfig( elQuestionItem );
				dataAnswers.push( question_answer );
				setDataAnswersConfig( elQuestionItem, dataAnswers );
			} else {
				throw `Error: ${ message }`;
			}

			showToast( message, status );
		},
		error: ( error ) => {
			elQuestionAnswerNew.remove();
			showToast( error, 'error' );
		},
		completed: () => {},
	};

	const dataSend = {
		quiz_id: quizID,
		action: 'add_question_answer',
		question_id: questionId,
		answer_title: answerTitle,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

const deleteQuestionAnswer = ( e, target ) => {
	const elBtnDeleteAnswer = target.closest( `${ className.elBtnDeleteAnswer }` );
	if ( ! elBtnDeleteAnswer ) {
		return;
	}

	const elQuestionAnswerItem = elBtnDeleteAnswer.closest( `${ className.elQuestionAnswerItem }` );
	if ( ! elQuestionAnswerItem ) {
		return;
	}

	const elQuestionItem = elBtnDeleteAnswer.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const questionId = elQuestionItem.dataset.questionId;
	const questionAnswerId = elQuestionAnswerItem.dataset.answerId;
	if ( ! questionId || ! questionAnswerId ) {
		return;
	}

	SweetAlert.fire( {
		title: elBtnDeleteAnswer.dataset.title || 'Are you sure?',
		text: elBtnDeleteAnswer.dataset.content || 'Do you want to delete this answer?',
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
						const elQuestionAnswerId = parseInt( elQuestionAnswerItem.dataset.answerId );
						elQuestionAnswerItem.remove();

						const dataAnswers = getDataAnswersConfig( elQuestionItem );
						if ( dataAnswers ) {
							const updatedAnswers = dataAnswers.filter( ( answer ) => parseInt( answer.question_answer_id ) !== elQuestionAnswerId );
							setDataAnswersConfig( elQuestionItem, updatedAnswers );
						}
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
				action: 'delete_question_answer',
				question_id: questionId,
				question_answer_id: questionAnswerId,
				args: {
					id_url: idUrlHandle,
				},
			};
			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
	} );
};

// For answers config
const updateAnswersConfig = ( e, target ) => {
	const elBtnUpdateQuestionAnswer = target.closest(
		`${ className.elBtnUpdateQuestionAnswer }`
	);
	const elInputAnswerSetTrue = target.closest(
		`${ className.elInputAnswerSetTrue }`
	);
	if ( ! elInputAnswerSetTrue && ! elBtnUpdateQuestionAnswer ) {
		return;
	}

	const elTarget = elBtnUpdateQuestionAnswer || elInputAnswerSetTrue;
	const elQuestionItem = elTarget.closest(
		`${ className.elQuestionItem }`
	);
	if ( ! elQuestionItem ) {
		return;
	}

	const questionId = elQuestionItem.dataset.questionId;
	const elAnswersConfig = elQuestionItem.querySelector(
		`${ className.elAnswersConfig }`
	);
	if ( ! elAnswersConfig ) {
		return;
	}

	const dataAnswers = getDataAnswersConfig( elQuestionItem );
	if ( ! dataAnswers ) {
		return;
	}

	// For both radio and checkbox.
	const dataAnswersOld = structuredClone( dataAnswers );

	// Get position of answers
	const elQuestionAnswerItems = elAnswersConfig.querySelectorAll( `${ className.elQuestionAnswerItem }:not(.clone)` );
	const answersPosition = {};
	elQuestionAnswerItems.forEach( ( elQuestionAnswerItem, index ) => {
		answersPosition[ elQuestionAnswerItem.dataset.answerId ] = index + 1; // Start from 1
	} );

	//console.log( 'answersPosition', answersPosition );

	dataAnswers.map( ( answer, k ) => {
		const elQuestionAnswerItem = elQuestionItem.querySelector(
			`${ className.elQuestionAnswerItem }[data-answer-id="${ answer.question_answer_id }"]`
		);
		const elInputAnswerSetTrue = elQuestionAnswerItem.querySelector(
			`${ className.elInputAnswerSetTrue }`
		);
		const elInputAnswerTitle = elQuestionAnswerItem.querySelector(
			`${ className.elQuestionAnswerTitleInput }`
		);

		// Set title
		if ( elInputAnswerTitle ) {
			answer.title = elInputAnswerTitle.value.trim();
		}

		// Set true answer
		if ( elInputAnswerSetTrue.checked ) {
			answer.is_true = 'yes';
		} else {
			answer.is_true = '';
		}

		// Set position
		if ( answersPosition[ answer.question_answer_id ] ) {
			answer.order = answersPosition[ answer.question_answer_id ];
		}

		return answer;
	} );

	//console.log( dataAnswers );

	lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

	// Call ajax to update answers config
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;

			if ( status === 'success' ) {
			} else {
				throw `Error: ${ message }`;
			}

			showToast( message, status );
		},
		error: ( error ) => {
			// rollback changes to old data
			dataAnswersOld.forEach( ( answer ) => {
				const elAnswerItem = elQuestionItem.querySelector(
					`${ className.elQuestionAnswerItem }[data-answer-id="${ answer.question_answer_id }"]`
				);
				const inputAnswerSetTrue = elAnswerItem.querySelector(
					`${ className.elInputAnswerSetTrue }`
				);
				if ( answer.is_true === 'yes' ) {
					inputAnswerSetTrue.checked = true;
				}

				return answer;
			} );
			showToast( error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
		},
	};

	const dataSend = {
		quiz_id: quizID,
		action: 'update_question_answers_config',
		question_id: questionId,
		answers: dataAnswers,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
// End for answers config

// Auto update question answer
let timeoutAutoUpdateAnswer;
const autoUpdateAnswer = ( e, target ) => {
	const elAutoSave = target.closest( `${ className.elQuestionAnswerTitleInput }` );
	if ( ! elAutoSave ) {
		return;
	}

	const elQuestionDataEdit = elAutoSave.closest( '.lp-question-data-edit' );
	const elBtnUpdateQuestionAnswer = elQuestionDataEdit.querySelector( `${ className.elBtnUpdateQuestionAnswer }` );
	if ( ! elBtnUpdateQuestionAnswer ) {
		return;
	}

	clearTimeout( timeoutAutoUpdateAnswer );
	timeoutAutoUpdateAnswer = setTimeout( () => {
		elBtnUpdateQuestionAnswer.click(); // Trigger update answers config
	}, 700 );
};

// Sortable questions
const sortAbleQuestion = () => {
	let isUpdateSectionPosition = 0;
	let timeout;

	new Sortable( elEditListQuestions, {
		handle: '.drag',
		animation: 150,
		onEnd: ( evt ) => {
			const elQuestionItem = evt.item;
			if ( ! isUpdateSectionPosition ) {
				// No change in section position, do nothing
				return;
			}

			clearTimeout( timeout );
			timeout = setTimeout( () => {
				lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

				const questionIds = [];
				const elQuestionItems = elEditListQuestions.querySelectorAll( `${ className.elQuestionItem }:not(.clone)` );
				elQuestionItems.forEach( ( elItem ) => {
					const questionId = elItem.dataset.questionId;
					if ( questionId ) {
						questionIds.push( questionId );
					}
				} );

				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						if ( status === 'success' ) {
							showToast( message, status );
						} else {
							throw `Error: ${ message }`;
						}
					},
					error: ( error ) => {
						showToast( error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
						isUpdateSectionPosition = 0; // Reset position update flag
					},
				};

				const dataSend = {
					quiz_id: quizID,
					action: 'update_questions_position',
					question_ids: questionIds,
					args: {
						id_url: idUrlHandle,
					},
				};
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

// Sortable answers's question
const sortAbleQuestionAnswer = () => {
	let isUpdateSectionPosition = 0;
	let timeout;

	const elQuestionAnswers = elEditListQuestions.querySelectorAll( `${ className.elAnswersConfig }` );

	elQuestionAnswers.forEach( ( elAnswersConfig ) => {
		new Sortable( elAnswersConfig, {
			handle: '.drag',
			animation: 150,
			onEnd: ( evt ) => {
				const elQuestionAnswerItem = evt.item;
				if ( ! isUpdateSectionPosition ) {
					// No change in section position, do nothing
					return;
				}

				clearTimeout( timeout );
				timeout = setTimeout( () => {
					const elQuestionDataEdit = elQuestionAnswerItem.closest( '.lp-question-data-edit' );
					if ( ! elQuestionDataEdit ) {
						return;
					}

					const elBtnUpdateQuestionAnswer = elQuestionDataEdit.querySelector( `${ className.elBtnUpdateQuestionAnswer }` );
					elBtnUpdateQuestionAnswer.click(); // Trigger update answers config
				}, 1000 );
			},
			onMove: ( evt ) => {
				clearTimeout( timeout );
			},
			onUpdate: ( evt ) => {
				isUpdateSectionPosition = 1;
			},
		} );
	} );
};

// For FIB question type
const fibInsertBlank = ( e, target ) => {
	const elBtnFibInsertBlank = target.closest( `${ className.elBtnFibInsertBlank }` );
	if ( ! elBtnFibInsertBlank ) {
		return;
	}

	const elQuestionItem = elBtnFibInsertBlank.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const questionId = elQuestionItem.dataset.questionId;

	uniquid = randomString();

	const idEditor = `lp-question-fib-input-${ questionId }`;
	const dataAnswers = getDataAnswersConfig( elQuestionItem );
	if ( ! dataAnswers ) {
		return;
	}

	let selectedText;
	if ( fibSelection ) {
		const elNode = fibSelection.getNode();
		if ( elNode.classList.contains( 'lp-question-fib-input' ) ) {
			showToast( 'this text inserted to blank', 'error' );
			return;
		}

		selectedText = fibSelection.getContent();
		if ( selectedText.length === 0 ) {
			selectedText = elBtnFibInsertBlank.dataset.defaultText;
		}

		const elInputNew = `<span class="lp-question-fib-input" data-id="${ uniquid }">${ selectedText }</span>&nbsp;`;

		fibSelection.setContent( elInputNew );
	} else {
		const editor = window.tinymce.get( idEditor );
		const elInputNew = `<span class="lp-question-fib-input" data-id="${ uniquid }">Enter answer correct on here</span>&nbsp;`;
		editor.selection.select( editor.getBody(), true );
		editor.selection.collapse( false );
		editor.insertContent( elInputNew );
	}

	dataAnswers.meta_data = dataAnswers.meta_data || {};
	// Convert array to object
	if ( Object.keys( dataAnswers.meta_data ).length === 0 ) {
		dataAnswers.meta_data = {};
	}

	dataAnswers.meta_data[ uniquid ] = {
		id: uniquid,
		match_case: 0,
		comparison: 'equal',
		fill: selectedText,
		index: 1,
		open: false,
	};

	setDataAnswersConfig( elQuestionItem, dataAnswers );

	// Clone blank options
	const elFibBlankOptions = elQuestionItem.querySelector( `${ className.elFibBlankOptions }` );
	const elFibBlankOptionItemClone = elQuestionItem.querySelector( `${ className.elFibBlankOptionItemClone }` );
	const elFibBlankOptionItemNew = elFibBlankOptionItemClone.cloneNode( true );
	const countOptions = elFibBlankOptions.querySelectorAll( `${ className.elFibBlankOptionItem }:not(.clone)` ).length;
	const elFibBlankOptionIndex = elFibBlankOptionItemNew.querySelector( `${ className.elFibBlankOptionIndex }` );
	const elFibOptionTitleInput = elFibBlankOptionItemNew.querySelector( `${ className.elFibOptionTitleInput }` );
	const elQuestionFibOptionMatchCaseInput = elFibBlankOptionItemNew.querySelector( `${ className.elQuestionFibOptionMatchCaseInput }` );
	const elQuestionFibOptionComparisonInputs = elFibBlankOptionItemNew.querySelectorAll( `${ className.elQuestionFibOptionComparisonInput }` );

	elFibBlankOptionItemNew.dataset.id = uniquid;
	elFibOptionTitleInput.name = `${ className.elFibOptionTitleInput }-${ uniquid }`;
	elFibOptionTitleInput.value = decodeHtml( selectedText );
	elFibBlankOptionIndex.textContent = countOptions + 1 + '.';
	elQuestionFibOptionMatchCaseInput.name = `${ className.elQuestionFibOptionMatchCaseInput }-${ uniquid }`.replace( /\./g, '' );
	elQuestionFibOptionComparisonInputs.forEach( ( elInput ) => {
		elInput.name = `${ className.elQuestionFibOptionComparisonInput }-${ uniquid }`.replace( /\./g, '' );
		if ( elInput.value === 'equal' ) {
			elInput.checked = true;
		}
	} );
	elFibBlankOptionItemClone.insertAdjacentElement( 'beforebegin', elFibBlankOptionItemNew );
	elFibBlankOptionItemNew.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( elFibBlankOptionItemNew, 1 );
	// End clone blank options

	const elBtnFibSaveContent = elQuestionItem.querySelector( `${ className.elBtnFibSaveContent }` );
	if ( elBtnFibSaveContent ) {
		elBtnFibSaveContent.click(); // Save changes
	}
};

// Change blank option
const fibChangeBlankOption = ( e, target ) => {
	const elFibBlankOptionItem = target.closest( `${ className.elFibBlankOptionItem }` );
	if ( ! elFibBlankOptionItem ) {
		return;
	}

	const elQuestionItem = elFibBlankOptionItem.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const blankId = elFibBlankOptionItem.dataset.id;
	const dataAnswers = getDataAnswersConfig( elQuestionItem );

	const elFibBlankOptionItemInputs = elFibBlankOptionItem.querySelectorAll( 'input' );
	elFibBlankOptionItemInputs.forEach( ( elInput ) => {
		const key = elInput.dataset.key;

		if ( elInput.checked ) {
			dataAnswers.meta_data[ blankId ][ key ] = elInput.value;
		} else if ( key === 'match_case' ) {
			dataAnswers.meta_data[ blankId ][ key ] = 0;
		}
	} );

	// Save changes to answers config
	setDataAnswersConfig( elQuestionItem, dataAnswers );
};

// Remove blank
const fibDeleteBlank = ( e, target ) => {
	const elBtnFibOptionDelete = target.closest( `${ className.elBtnFibOptionDelete }` );
	if ( ! elBtnFibOptionDelete ) {
		return;
	}

	const elQuestionItem = elBtnFibOptionDelete.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const questionId = elQuestionItem.dataset.questionId;
	const elAnswersConfig = elQuestionItem.querySelector( `${ className.elAnswersConfig }` );
	const dataAnswers = getDataAnswersConfig( elQuestionItem );
	const blankItem = elBtnFibOptionDelete.closest( `${ className.elFibBlankOptionItem }` );
	const blankId = blankItem.dataset.id;

	SweetAlert.fire( {
		title: elBtnFibOptionDelete.dataset.title,
		text: elBtnFibOptionDelete.dataset.content,
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		cancelButtonText: lpDataAdmin.i18n.cancel,
		confirmButtonText: lpDataAdmin.i18n.yes,
		reverseButtons: true,
	} ).then( ( result ) => {
		if ( result.isConfirmed ) {
			// Find span with id on editor and remove it
			const editor = window.tinymce.get( `lp-question-fib-input-${ questionId }` );
			const elBlank = editor.dom.select( `.lp-question-fib-input[data-id="${ blankId }"]` );
			if ( elBlank[ 0 ] ) {
				// Remove tag html but keep content
				editor.dom.remove( elBlank[ 0 ], true );
			}

			blankItem.remove();
			dataAnswers.meta_data = dataAnswers.meta_data || {};
			if ( dataAnswers.meta_data[ blankId ] ) {
				delete dataAnswers.meta_data[ blankId ];
			}

			setDataAnswersConfig( elQuestionItem, dataAnswers );

			const elBtnFibSaveContent = elQuestionItem.querySelector( `${ className.elBtnFibSaveContent }` );
			if ( elBtnFibSaveContent ) {
				elBtnFibSaveContent.click(); // Save changes
			}
		}
	} );
};

// Delete all blanks
const FibDeleteAllBlanks = ( e, target ) => {
	const elBtnFibDeleteAllBlanks = target.closest( `${ className.elBtnFibDeleteAllBlanks }` );
	if ( ! elBtnFibDeleteAllBlanks ) {
		return;
	}

	const elQuestionItem = elBtnFibDeleteAllBlanks.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const questionId = elQuestionItem.dataset.questionId;
	const dataAnswers = getDataAnswersConfig( elQuestionItem );

	SweetAlert.fire( {
		title: elBtnFibDeleteAllBlanks.dataset.title,
		text: elBtnFibDeleteAllBlanks.dataset.content,
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		cancelButtonText: lpDataAdmin.i18n.cancel,
		confirmButtonText: lpDataAdmin.i18n.yes,
		reverseButtons: true,
	} ).then( ( result ) => {
		if ( result.isConfirmed ) {
			const editor = window.tinymce.get( `lp-question-fib-input-${ questionId }` );
			const elBlanks = editor.dom.select( `.lp-question-fib-input` );
			elBlanks.forEach( ( elBlank ) => {
				editor.dom.remove( elBlank, true );
			} );

			dataAnswers.meta_data = {};
			setDataAnswersConfig( elQuestionItem, dataAnswers );

			const elFibBlankOptions = elQuestionItem.querySelector( `${ className.elFibBlankOptions }` );
			elFibBlankOptions.innerHTML = '';
		}
	} );
};

// Clear content FIB question
const FibClearContent = ( e, target ) => {
	const elBtnFibClearAllContent = target.closest( `${ className.elBtnFibClearAllContent }` );
	if ( ! elBtnFibClearAllContent ) {
		return;
	}

	const elQuestionItem = elBtnFibClearAllContent.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const questionId = elQuestionItem.dataset.questionId;
	const elAnswersConfig = elQuestionItem.querySelector( `${ className.elAnswersConfig }` );
	const dataAnswers = getDataAnswersConfig( elQuestionItem );

	SweetAlert.fire( {
		title: elBtnFibClearAllContent.dataset.title,
		text: elBtnFibClearAllContent.dataset.content,
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		cancelButtonText: lpDataAdmin.i18n.cancel,
		confirmButtonText: lpDataAdmin.i18n.yes,
		reverseButtons: true,
	} ).then( ( result ) => {
		if ( result.isConfirmed ) {
			const editor = window.tinymce.get( `lp-question-fib-input-${ questionId }` );
			editor.setContent( '' );

			dataAnswers.meta_data = {};
			setDataAnswersConfig( elQuestionItem, dataAnswers );

			const elFibBlankOptions = elQuestionItem.querySelector( `${ className.elFibBlankOptions }` );
			elFibBlankOptions.innerHTML = '';
		}
	} );
};

// Save content FIB question
const fibSaveContent = ( e, target ) => {
	const elBtnFibSaveContent = target.closest( `${ className.elBtnFibSaveContent }` );
	if ( ! elBtnFibSaveContent ) {
		return;
	}

	const elQuestionItem = elBtnFibSaveContent.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const questionId = elQuestionItem.dataset.questionId;

	const dataAnswers = getDataAnswersConfig( elQuestionItem );
	if ( ! dataAnswers ) {
		return;
	}

	const editor = window.tinymce.get( `lp-question-fib-input-${ questionId }` );
	dataAnswers.title = editor.getContent();

	const elFibBlankOptionItems = elQuestionItem.querySelectorAll( `${ className.elFibBlankOptionItem }:not(.clone)` );
	if ( elFibBlankOptionItems ) {
		elFibBlankOptionItems.forEach( ( elFibBlankOptionItem ) => {
			const blankId = elFibBlankOptionItem.dataset.id;
			const elQuestionFibOptionMatchCaseInput = elFibBlankOptionItem.querySelector( `${ className.elQuestionFibOptionMatchCaseInput }` );
			const elQuestionFibOptionComparisonInput = elFibBlankOptionItem.querySelector( `${ className.elQuestionFibOptionComparisonInput }:checked` );

			dataAnswers.meta_data[ blankId ].match_case = elQuestionFibOptionMatchCaseInput.checked ? 1 : 0;
			dataAnswers.meta_data[ blankId ].comparison = elQuestionFibOptionComparisonInput.value;
		} );
	}

	//console.log( 'dataAnswers', dataAnswers );

	lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

	// Call ajax to update answers config
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;

			if ( status === 'success' ) {
				setDataAnswersConfig( elQuestionItem, dataAnswers );
			} else {
				throw `Error: ${ message }`;
			}

			showToast( message, status );
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
		action: 'update_question_answers_config',
		question_id: questionId,
		answers: dataAnswers,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Change title of blank option
const FibOptionTitleInputChange = ( e, target ) => {
	const elFibOptionTitleInput = target.closest( `${ className.elFibOptionTitleInput }` );
	if ( ! elFibOptionTitleInput ) {
		return;
	}

	const elQuestionFibOptionItem = elFibOptionTitleInput.closest( `${ className.elFibBlankOptionItem }` );
	if ( ! elQuestionFibOptionItem ) {
		return;
	}

	const elQuestionItem = elFibOptionTitleInput.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const value = elFibOptionTitleInput.value.trim();
	const blankId = elQuestionFibOptionItem.dataset.id;
	const questionId = elQuestionItem.dataset.questionId;
	const editor = window.tinymce.get( `lp-question-fib-input-${ questionId }` );
	const elBlank = editor.dom.select( `.lp-question-fib-input[data-id="${ blankId }"]` );
	if ( elBlank[ 0 ] ) {
		// Update content of blank
		elBlank[ 0 ].textContent = value;
	}
};

const FibShowHideMatchCaseOption = ( e, target ) => {
	const elQuestionFibOptionMatchCaseInput = target.closest( `${ className.elQuestionFibOptionMatchCaseInput }` );
	if ( ! elQuestionFibOptionMatchCaseInput ) {
		return;
	}

	const elQuestionFibOptionDetail = elQuestionFibOptionMatchCaseInput.closest( `${ className.elQuestionFibOptionDetail }` );
	const elQuestionFibOptionMatchCaseWrap = elQuestionFibOptionDetail.querySelector( `${ className.elQuestionFibOptionMatchCaseWrap }` );
	if ( ! elQuestionFibOptionDetail || ! elQuestionFibOptionMatchCaseWrap ) {
		return;
	}

	if ( elQuestionFibOptionMatchCaseInput.checked ) {
		lpUtils.lpShowHideEl( elQuestionFibOptionMatchCaseWrap, 1 );
	} else {
		lpUtils.lpShowHideEl( elQuestionFibOptionMatchCaseWrap, 0 );
	}
};

// Get data answers config
const getDataAnswersConfig = ( elQuestionItem ) => {
	const elAnswersConfig = elQuestionItem.querySelector( `${ className.elAnswersConfig }` );
	if ( ! elAnswersConfig ) {
		return null;
	}

	let dataAnswers = elAnswersConfig.dataset.answers || '[]';
	try {
		dataAnswers = JSON.parse( dataAnswers );
	} catch ( e ) {
		dataAnswers = [];
	}

	if ( ! dataAnswers.meta_data ) {
		dataAnswers.meta_data = {};
	}

	return dataAnswers;
};

// Set data answers config
const setDataAnswersConfig = ( elQuestionItem, dataAnswers ) => {
	const elAnswersConfig = elQuestionItem.querySelector( `${ className.elAnswersConfig }` );
	if ( ! elAnswersConfig ) {
		return;
	}

	if ( ! dataAnswers || typeof dataAnswers !== 'object' ) {
		dataAnswers = {};
	}

	elAnswersConfig.dataset.answers = JSON.stringify( dataAnswers );
};

const randomString = ( length = 10 ) => {
	const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
	let result = '';
	for ( let i = 0; i < length; i++ ) {
		result += chars.charAt( Math.floor( Math.random() * chars.length ) );
	}
	return result;
};

function decodeHtml( html ) {
	const txt = document.createElement( 'textarea' );
	txt.innerHTML = html;
	return txt.value;
}

// End FIB question type

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	toggleQuestionAll( e, target );
	toggleQuestion( e, target );
	toggleSection( e, target );
	//showPopupItemsToSelect( e, target );
	addQuestion( e, target );
	removeQuestion( e, target );
	updateQuestionTitle( e, target );
	updateAnswersConfig( e, target );
	fibInsertBlank( e, target );
	fibSaveContent( e, target );
	fibChangeBlankOption( e, target );
	fibDeleteBlank( e, target );
	FibDeleteAllBlanks( e, target );
	FibClearContent( e, target );
	FibShowHideMatchCaseOption( e, target );

	addQuestionAnswer( e, target );
	deleteQuestionAnswer( e, target );
	cancelChangeTitleQuestion( e, target );

	// Events for Popup Select Items to add
	const callBackPopupSelectItems = {
		willOpen: ( itemsSelectedData ) => {
			// Trigger tab lesson to be active and call AJAX load items
			const elLPTarget = elPopupSelectItems.querySelector(
				`${ className.LPTarget }`
			);

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
					lpPopupSelectItemToAdd.watchItemsSelectedDataChange( elPopupSelectItems );
				},
			} );
		},
	};
	lpPopupSelectItemToAdd.showPopupItemsToSelect( e, target, elPopupSelectItems, callBackPopupSelectItems );
	lpPopupSelectItemToAdd.selectItemsFromList( e, target, elPopupSelectItems );
	lpPopupSelectItemToAdd.addItemsSelectedToSection( e, target, elPopupSelectItems, ( itemsSelected ) => {
		//console.log( 'Items selected to add:', itemsSelected );
		const questionIds = [];
		itemsSelected.forEach( ( item ) => {
			const elQuestionItemClone = elEditQuizWrap.querySelector( `${ className.elQuestionItem }.clone` );
			if ( ! elQuestionItemClone ) {
				return;
			}

			questionIds.push( item.id );
			const elQuestionItemNew = elQuestionItemClone.cloneNode( true );
			const elQuestionItemTitleInput = elQuestionItemNew.querySelector( `${ className.elQuestionTitleInput }` );
			const elQuestionTypeLabel = elQuestionItemNew.querySelector( `${ className.elQuestionTypeLabel }` );
			elQuestionItemNew.classList.remove( 'clone' );
			elQuestionItemNew.dataset.questionId = item.id;
			elQuestionItemTitleInput.value = item.titleSelected;

			lpUtils.lpSetLoadingEl( elQuestionItemNew, 1 );
			lpUtils.lpShowHideEl( elQuestionItemNew, 1 );
			elQuestionItemClone.insertAdjacentElement( 'beforebegin', elQuestionItemNew );
			lpUtils.lpSetLoadingEl( elQuestionItemNew, 1 );
		} );

		// Ajax to add items to quiz
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				if ( status === 'success' ) {
					showToast( message, status );

					const { html_edit_question } = data;
					if ( html_edit_question ) {
						Object.entries( html_edit_question ).forEach( ( [ question_id, item_html ] ) => {
							const elQuestionItemNew = elEditQuizWrap.querySelector( `${ className.elQuestionItem }[data-question-id="${ question_id }"]` );
							elQuestionItemNew.outerHTML = item_html;
						} );
					}
					updateCountItems();
				} else {
					throw `Error: ${ message }`;
				}
			},
			error: ( error ) => {
				showToast( error, 'error' );
			},
			completed: () => {
			},
		};

		const dataSend = {
			quiz_id: quizID,
			action: 'add_questions_to_quiz',
			question_ids: questionIds,
			args: {
				id_url: idUrlHandle,
			},
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	} );
	lpPopupSelectItemToAdd.showItemsSelected( e, target, elPopupSelectItems );
	lpPopupSelectItemToAdd.backToSelectItems( e, target, elPopupSelectItems );
	lpPopupSelectItemToAdd.removeItemSelected( e, target, elPopupSelectItems );
	// End events for Popup Select Items to add
} );
// Event keydown
document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
	// Event enter
	if ( e.key === 'Enter' ) {
		// Add new section
		updateQuestionTitle( e, target );
		addQuestion( e, target );
	}
} );
// Event keyup
document.addEventListener( 'keyup', ( e ) => {
	const target = e.target;
	//console.log( 'keyup', target );
	if ( target.classList.contains( 'lp-editor-tinymce' ) ) {
		//window.tinymce.triggerSave();
		//console.log( 'keyup', target.value );
	}

	FibOptionTitleInputChange( e, target );
	autoUpdateQuestion( e, target );
	changeTitleQuestion( e, target );
	checkCanAddQuestion( e, target );
	autoUpdateAnswer( e, target );
	lpPopupSelectItemToAdd.searchTitleItemToSelect( e, target, elPopupSelectItems );
} );
// Event focus in
document.addEventListener( 'focusin', ( e ) => {
	//console.log( 'focusin', e.target );
} );
// Event focus out
document.addEventListener( 'focusout', ( e ) => {} );
document.addEventListener( 'change', ( e ) => {
	const target = e.target;
	autoUpdateQuestion( e, target );
	checkCanAddQuestion( e, target );
} );

// Element root ready.
lpUtils.lpOnElementReady(
	`${ className.elEditQuizWrap }`,
	( elEditQuizWrapFound ) => {
		elEditQuizWrap = elEditQuizWrapFound;
		elEditListQuestions = elEditQuizWrap.querySelector(
			`${ className.elEditListQuestions }`
		);
		const elLPTarget = elEditQuizWrap.closest( `${ className.LPTarget }` );
		const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		quizID = dataSend.args.quiz_id;

		const elPopupItemsToSelectClone = elEditQuizWrap.querySelector(
			`${ className.elPopupItemsToSelectClone }`
		);
		elPopupSelectItems = elPopupItemsToSelectClone.cloneNode( true );
		elPopupSelectItems.classList.remove( 'clone', 'lp-hidden' );

		initTinyMCE();
		sortAbleQuestion();
		sortAbleQuestionAnswer();
	}
);
