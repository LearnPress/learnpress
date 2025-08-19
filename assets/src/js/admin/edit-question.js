/**
 * Edit question JS handler.
 *
 * @since 4.2.9
 * @version 1.0.0
 */

import * as lpUtils from '../utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import SweetAlert from 'sweetalert2-neutral';
import Sortable from 'sortablejs';

const className = {
	elQuestionEditMain: '.question-edit-main',
	elQuestionToggleAll: '.lp-question-toggle-all',
	elEditListQuestions: '.lp-edit-list-questions',
	elQuestionToggle: '.lp-question-toggle',
	elBtnShowPopupItemsToSelect: '.lp-btn-show-popup-items-to-select',
	elPopupItemsToSelectClone: '.lp-popup-items-to-select.clone',
	elBtnAddQuestion: '.lp-btn-add-question',
	elBtnRemoveQuestion: '.lp-btn-remove-question',
	elBtnUpdateQuestionTitle: '.lp-btn-update-question-title',
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
	elQuestionAnswerTitleNewInput: '.lp-question-answer-title-new-input',
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
	elAutoSaveQuestion: '.lp-auto-save-question',
	elAutoSaveAnswer: '.lp-auto-save-question-answer',
};

const idUrlHandle = 'edit-question';
let lpSettings = {};
if ( 'undefined' !== typeof lpDataAdmin ) {
	lpSettings = lpDataAdmin;
} else if ( 'undefined' !== typeof lpData ) {
	lpSettings = lpData;
}
let fibSelection;

// Get section by id
const initTinyMCE = () => {
	const elTextareas =
		document.querySelectorAll( '.lp-editor-tinymce' );

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
	eventEditorTinymce( id );

	// Active tab visual
	const wrapEditor = document.querySelector( `#wp-${ id }-wrap` );
	if ( wrapEditor ) {
		wrapEditor.classList.add( 'tmce-active' );
		wrapEditor.classList.remove( 'html-active' );
	}
};

// Events for TinyMCE editor
const eventEditorTinymce = ( id ) => {
	const editor = window.tinymce.get( id );
	const elTextarea = document.getElementById( id );
	const elQuestionEditMain = elTextarea.closest( `${ className.elQuestionEditMain }` );
	const questionId = elQuestionEditMain.dataset.questionId;
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
		const uniquid = randomString();
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
				const elQuestionEditMain = document.querySelector( `${ className.elQuestionEditMain }[data-question-id="${ questionId }"]` );
				const elQuestionBlankOptions = elQuestionEditMain.querySelector( `${ className.elFibBlankOptions }` );
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

let timeoutAutoUpdateQuestion;
const autoUpdateQuestion = ( e, target, key, value ) => {
	const elAutoSave = target.closest( `${ className.elAutoSaveQuestion }` );
	if 	( ! elAutoSave ) {
		return;
	}

	const elQuestionEditMain = elAutoSave.closest( `${ className.elQuestionEditMain }` );
	const questionId = elQuestionEditMain.dataset.questionId;

	clearTimeout( timeoutAutoUpdateQuestion );
	timeoutAutoUpdateQuestion = setTimeout( () => {
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

		dataSend[ key ] = value;

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}, 700 );
};

const addQuestionAnswer = ( e, target ) => {
	const elBtnAddAnswer = target.closest( `${ className.elBtnAddAnswer }` );
	if ( ! elBtnAddAnswer ) {
		return;
	}

	const elQuestionAnswerItemAddNew = elBtnAddAnswer.closest( `${ className.elQuestionAnswerItemAddNew }` );
	const elQuestionAnswerTitleNewInput = elQuestionAnswerItemAddNew.querySelector( `${ className.elQuestionAnswerTitleNewInput }` );

	if ( ! elQuestionAnswerTitleNewInput.value.trim() ) {
		const message = elQuestionAnswerTitleNewInput.dataset.messEmptyTitle;
		showToast( message, 'error' );
		return;
	}

	const elQuestionEditMain = elBtnAddAnswer.closest( `${ className.elQuestionEditMain }` );
	const elQuestionAnswerClone = elQuestionEditMain.querySelector( `${ className.elQuestionAnswerItem }.clone` );
	const elQuestionAnswerNew = elQuestionAnswerClone.cloneNode( true );
	const elQuestionAnswerTitleInputNew = elQuestionAnswerNew.querySelector( `${ className.elQuestionAnswerTitleInput }` );

	elQuestionAnswerNew.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( elQuestionAnswerNew, 1 );
	lpUtils.lpSetLoadingEl( elQuestionAnswerNew, 1 );
	elQuestionAnswerClone.insertAdjacentElement( 'beforebegin', elQuestionAnswerNew );

	const answerTitle = elQuestionAnswerTitleNewInput.value.trim();
	elQuestionAnswerTitleInputNew.value = answerTitle;
	elQuestionAnswerTitleNewInput.value = '';
	const questionId = elQuestionEditMain.dataset.questionId;

	// Call ajax to add new question answer
	const callBack = {
		success: ( response ) => {
			const { message, status, data } = response;

			if ( status === 'success' ) {
				const { question_answer } = data;
				elQuestionAnswerNew.dataset.answerId = question_answer.question_answer_id;
				lpUtils.lpSetLoadingEl( elQuestionAnswerNew, 0 );

				// Set data lp-answers-config
				const dataAnswers = getDataAnswersConfig( elQuestionEditMain );
				dataAnswers.push( question_answer );
				setDataAnswersConfig( elQuestionEditMain, dataAnswers );
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
		action: 'add_question_answer',
		question_id: questionId,
		answer_title: answerTitle,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Auto update question answer
let timeoutAutoUpdateAnswer;
const autoUpdateAnswer = ( e, target ) => {
	const elAutoSaveAnswer = target.closest( `${ className.elAutoSaveAnswer }` );
	if ( ! elAutoSaveAnswer ) {
		return;
	}

	const elQuestionAnswerItem = elAutoSaveAnswer.closest( `${ className.elQuestionAnswerItem }` );

	clearTimeout( timeoutAutoUpdateAnswer );
	timeoutAutoUpdateAnswer = setTimeout( () => {
		const elQuestionEditMain = elAutoSaveAnswer.closest(
			`${ className.elQuestionEditMain }`
		);

		const questionId = elQuestionEditMain.dataset.questionId;
		const dataAnswers = getDataAnswersConfig( elQuestionEditMain );
		const elAnswersConfig = elQuestionEditMain.querySelector(
			`${ className.elAnswersConfig }`
		);

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
			const elQuestionAnswerItem = elQuestionEditMain.querySelector(
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

		lpUtils.lpSetLoadingEl( elQuestionAnswerItem, 1 );

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
					const elAnswerItem = elQuestionEditMain.querySelector(
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
				lpUtils.lpSetLoadingEl( elQuestionAnswerItem, 0 );
			},
		};

		const dataSend = {
			action: 'update_question_answers_config',
			question_id: questionId,
			answers: dataAnswers,
			args: {
				id_url: idUrlHandle,
			},
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}, 700 );
};

// Sortable answers's question
const sortAbleQuestionAnswer = ( elQuestionEditMain ) => {
	let isUpdateSectionPosition = 0;
	let timeout;

	const elQuestionAnswers = elQuestionEditMain.querySelectorAll( `${ className.elAnswersConfig }` );

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
					const elAutoSaveAnswer = elQuestionAnswerItem.querySelector( `${ className.elAutoSaveAnswer }` );
					autoUpdateAnswer( null, elAutoSaveAnswer );
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

// Delete question answer
const deleteQuestionAnswer = ( e, target ) => {
	const elBtnDeleteAnswer = target.closest( `${ className.elBtnDeleteAnswer }` );
	if ( ! elBtnDeleteAnswer ) {
		return;
	}

	const elQuestionAnswerItem = elBtnDeleteAnswer.closest( `${ className.elQuestionAnswerItem }` );
	if ( ! elQuestionAnswerItem ) {
		return;
	}

	const elQuestionEditMain = elBtnDeleteAnswer.closest( `${ className.elQuestionEditMain }` );

	const questionId = elQuestionEditMain.dataset.questionId;
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
			lpUtils.lpSetLoadingEl( elQuestionAnswerItem, 1 );

			// Call ajax to delete item from section
			const callBack = {
				success: ( response ) => {
					const { message, status } = response;

					showToast( message, status );

					if ( status === 'success' ) {
						const elQuestionAnswerId = parseInt( elQuestionAnswerItem.dataset.answerId );
						elQuestionAnswerItem.remove();

						const dataAnswers = getDataAnswersConfig( elQuestionEditMain );
						if ( dataAnswers ) {
							const updatedAnswers = dataAnswers.filter( ( answer ) => parseInt( answer.question_answer_id ) !== elQuestionAnswerId );
							setDataAnswersConfig( elQuestionEditMain, updatedAnswers );
						}
					}
				},
				error: ( error ) => {
					showToast( error, 'error' );
				},
				completed: () => {
					lpUtils.lpSetLoadingEl( elQuestionAnswerItem, 0 );
				},
			};

			const dataSend = {
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

// Get data answers config
const getDataAnswersConfig = ( elQuestionEditMain ) => {
	const elAnswersConfig = elQuestionEditMain.querySelector( `${ className.elAnswersConfig }` );
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
const setDataAnswersConfig = ( elQuestionEditMain, dataAnswers ) => {
	const elAnswersConfig = elQuestionEditMain.querySelector( `${ className.elAnswersConfig }` );
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

const argsToastify = {
	text: '',
	gravity: lpSettings.toast.gravity, // `top` or `bottom`
	position: lpSettings.toast.position, // `left`, `center` or `right`
	className: `${ lpSettings.toast.classPrefix }`,
	close: lpSettings.toast.close == 1,
	stopOnFocus: lpSettings.toast.stopOnFocus == 1,
	duration: lpSettings.toast.duration,
};
const showToast = ( message, status = 'success' ) => {
	const toastify = new Toastify( {
		...argsToastify,
		text: message,
		className: `${ lpSettings.toast.classPrefix } ${ status }`,
	} );
	toastify.showToast();
};

// Event click
document.addEventListener( 'click', ( e ) => {
	const target = e.target;
	deleteQuestionAnswer( e, target );
	addQuestionAnswer( e, target );
} );
// Event change
document.addEventListener( 'change', ( e ) => {
	const target = e.target;
	autoUpdateQuestion( e, target );
	autoUpdateAnswer( e, target );
} );
// Event keyup
document.addEventListener( 'keyup', ( e ) => {
	const target = e.target;
	autoUpdateAnswer( e, target );
} );

// Element root ready.
lpUtils.lpOnElementReady(
	`${ className.elQuestionEditMain }`,
	( elQuestionEditMain ) => {
		initTinyMCE( elQuestionEditMain );
		sortAbleQuestionAnswer( elQuestionEditMain );
	}
);
