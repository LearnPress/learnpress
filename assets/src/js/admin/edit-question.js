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
	elEditQuestionWrap: '.lp-edit-question-wrap',
	elQuestionEditMain: '.lp-question-edit-main',
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
	elFibOptionTitleInput: '.lp-question-fib-option-title-input',
	elFibBlankOptions: '.lp-question-fib-blank-options',
	elFibBlankOptionItem: '.lp-question-fib-blank-option-item',
	elFibBlankOptionItemClone: '.lp-question-fib-blank-option-item.clone',
	elFibBlankOptionIndex: '.lp-question-fib-option-index',
	elBtnFibOptionDelete: '.lp-btn-fib-option-delete',
	elFibOptionMatchCaseWrap: '.lp-question-fib-option-match-case-wrap',
	elFibOptionMatchCaseInput: '.lp-question-fib-option-match-case-input',
	elQuestionFibOptionDetail: '.lp-question-fib-option-detail',
	elFibOptionComparisonInput: '.lp-question-fib-option-comparison-input',
	elAutoSaveFib: '.lp-auto-save-fib',
	LPTarget: '.lp-target',
	elCollapse: 'lp-collapse',
	elSectionToggle: '.lp-section-toggle',
	elTriggerToggle: '.lp-trigger-toggle',
	elAutoSaveQuestion: '.lp-auto-save-question',
	elAutoSaveAnswer: '.lp-auto-save-question-answer',
	elQuestionFibInput: 'lp-question-fib-input',
	elBtnQuestionCreateType: '.lp-btn-question-create-type',
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
	const elTextareas = document.querySelectorAll( '.lp-editor-tinymce' );

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
	// Events focus in TinyMCE editor
	editor.on( 'change', ( e ) => {
		//console.log( 'Editor changed:', e.target.id );
		// Auto save if it has class lp-auto-save
		elTextarea.value = editor.getContent();
		autoUpdateQuestion( e, elTextarea );
	} );
	editor.on( 'keyup', ( e ) => {
		//console.log( 'Editor keyup:', e.target.id );
		// Auto save if it has class lp-auto-save
		elTextarea.value = editor.getContent();
		autoUpdateQuestion( e, elTextarea );
	} );
	editor.on( 'blur', ( e ) => {
		//console.log( 'Editor blurred:', e.target.id );
	} );
	editor.on( 'focusin', ( e ) => {} );
	editor.on( 'init', () => {
		// Add style
		editor.dom.addStyle( `
			body {
				line-height: 2.2;
			}
			.${ className.elQuestionFibInput } {
				border: 1px dashed rebeccapurple;
				padding: 5px;
			}
		` );
	} );
	editor.on( 'setcontent', ( e ) => {
		const uniquid = randomString();
		const elementg = editor.dom.select( `.${ className.elQuestionFibInput }[data-id="${ uniquid }"]` );
		if ( elementg[ 0 ] ) {
			elementg[ 0 ].focus();
		}

		editor.dom.bind( elementg[ 0 ], 'input', function( e ) {
			//console.log( 'Input changed:', e.target.value );
		} );
	} );
	editor.on( 'selectionchange', ( e ) => {
		fibSelection = editor.selection;

		// Check selection is blank, check empty blank content
		if ( fibSelection.getNode().classList.contains( `${ className.elQuestionFibInput }` ) ) {
			const blankId = fibSelection.getNode().dataset.id;
			const textBlank = fibSelection.getNode().textContent.trim();
			if ( textBlank.length === 0 ) {
				const editorId = editor.id;
				const questionId = editorId.replace( `${ className.elQuestionFibInput }-`, '' );
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
				if ( elFibBlankOptionItem ) {
					const elFibOptionTitleInput = elFibBlankOptionItem.querySelector( `${ className.elFibOptionTitleInput }` );
					if ( elFibOptionTitleInput ) {
						elFibOptionTitleInput.value = textBlank;
					}
				}
			}
		}
	} );
	editor.on( 'Undo', function( e ) {
		const contentUndo = editor.getContent();
		const selection = editor.selection;
		const nodeUndo = selection.getNode();

		if ( nodeUndo.classList.contains( `${ className.elQuestionFibInput }` ) ) {
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
			completed: () => {},
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
// Create question type
const createQuestionType = ( e, target ) => {
	const elBtnQuestionCreateType = target.closest( `${ className.elBtnQuestionCreateType }` );
	if ( ! elBtnQuestionCreateType ) {
		return;
	}

	const elQuestionEditMain = elBtnQuestionCreateType.closest( `${ className.elQuestionEditMain }` );
	if ( ! elQuestionEditMain ) {
		return;
	}

	const questionId = elQuestionEditMain.dataset.questionId;
	const elQuestionTypeNew = elQuestionEditMain.querySelector( `${ className.elQuestionTypeNew }` );
	if ( ! elQuestionTypeNew ) {
		return;
	}

	const questionType = elQuestionTypeNew.value.trim();
	if ( ! questionType ) {
		const message = elQuestionTypeNew.dataset.messEmptyType;
		showToast( message, 'error' );
		return;
	}

	// Call ajax to create new question type
	const callBack = {
		success: ( response ) => {
			const { message, status, data } = response;

			if ( status === 'success' ) {
				const { html_option_answers } = data;
				const elAnswersConfig = elQuestionEditMain.querySelector( `${ className.elAnswersConfig }` );
				elAnswersConfig.outerHTML = html_option_answers;
				initTinyMCE();
				sortAbleQuestionAnswer( elQuestionEditMain );

				showToast( message, status );
			} else {
				throw `Error: ${ message }`;
			}
		},
		error: ( error ) => {
			showToast( error, 'error' );
		},
		completed: () => {},
	};

	const dataSend = {
		action: 'update_question',
		question_id: questionId,
		question_type: questionType,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
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
		completed: () => {
			checkCanAddAnswer( null, elQuestionAnswerTitleNewInput );
		},
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

// Check to enable or disable add new question button
const checkCanAddAnswer = ( e, target ) => {
	const elTrigger = target.closest( className.elQuestionAnswerTitleNewInput );
	if ( ! elTrigger ) {
		return;
	}

	const elQuestionAnswerItemAddNew = elTrigger.closest( `${ className.elQuestionAnswerItemAddNew }` );
	if ( ! elQuestionAnswerItemAddNew ) {
		return;
	}

	const elBtnAddAnswer = elQuestionAnswerItemAddNew.querySelector( `${ className.elBtnAddAnswer }` );
	if ( ! elBtnAddAnswer ) {
		return;
	}

	const titleValue = elTrigger.value.trim();
	if ( titleValue ) {
		elBtnAddAnswer.classList.add( 'active' );
	} else {
		elBtnAddAnswer.classList.remove( 'active' );
	}
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
			if ( elInputAnswerSetTrue ) {
				if ( elInputAnswerSetTrue.checked ) {
					answer.is_true = 'yes';
				} else {
					answer.is_true = '';
				}
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

/***** Fill in the blank question type *****/
// For FIB question type
const fibInsertBlank = ( e, target ) => {
	const elBtnFibInsertBlank = target.closest( `${ className.elBtnFibInsertBlank }` );
	if ( ! elBtnFibInsertBlank ) {
		return;
	}

	const textPlaceholder = elBtnFibInsertBlank.dataset.defaultText;
	const elQuestionEditMain = elBtnFibInsertBlank.closest( `${ className.elQuestionEditMain }` );
	const questionId = elQuestionEditMain.dataset.questionId;
	const messErrInserted = elBtnFibInsertBlank.dataset.messInserted;
	const messErrRequireSelectText = elBtnFibInsertBlank.dataset.messRequireSelectText;
	const idEditor = `${ className.elQuestionFibInput }-${ questionId }`;

	const uniquid = randomString();
	let selectedText;
	if ( fibSelection ) {
		const elNode = fibSelection.getNode();
		if ( ! elNode ) {
			showToast( 'Event insert blank has error, please try again', 'error' );
			return;
		}

		const findParent = elNode.closest( `body[data-id="${ idEditor }"]` );
		if ( ! findParent ) {
			showToast( messErrRequireSelectText, 'error' );
			return;
		}

		if ( elNode.classList.contains( `${ className.elQuestionFibInput }` ) ) {
			showToast( messErrInserted, 'error' );
			return;
		}

		selectedText = fibSelection.getContent();
		if ( selectedText.length === 0 ) {
			selectedText = textPlaceholder;
		}

		const elInputNew = `<span class="${ className.elQuestionFibInput }" data-id="${ uniquid }">${ selectedText }</span>`;

		fibSelection.setContent( elInputNew );
	} else {
		showToast( messErrRequireSelectText, 'error' );
		return;
	}

	const dataAnswers = getDataAnswersConfig( elQuestionEditMain );
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

	setDataAnswersConfig( elQuestionEditMain, dataAnswers );

	// Clone blank options
	const elFibBlankOptions = elQuestionEditMain.querySelector( `${ className.elFibBlankOptions }` );
	const elFibBlankOptionItemClone = elQuestionEditMain.querySelector( `${ className.elFibBlankOptionItemClone }` );
	const elFibBlankOptionItemNew = elFibBlankOptionItemClone.cloneNode( true );
	const countOptions = elFibBlankOptions.querySelectorAll( `${ className.elFibBlankOptionItem }:not(.clone)` ).length;
	const elFibBlankOptionIndex = elFibBlankOptionItemNew.querySelector( `${ className.elFibBlankOptionIndex }` );
	const elFibOptionTitleInput = elFibBlankOptionItemNew.querySelector( `${ className.elFibOptionTitleInput }` );
	const elFibOptionMatchCaseInput = elFibBlankOptionItemNew.querySelector( `${ className.elFibOptionMatchCaseInput }` );
	const elFibOptionComparisonInput = elFibBlankOptionItemNew.querySelectorAll( `${ className.elFibOptionComparisonInput }` );

	elFibBlankOptionItemNew.dataset.id = uniquid;
	elFibOptionTitleInput.name = `${ className.elFibOptionTitleInput }-${ uniquid }`;
	elFibOptionTitleInput.value = decodeHtml( selectedText );
	elFibBlankOptionIndex.textContent = countOptions + 1 + '.';
	elFibOptionMatchCaseInput.name = `${ className.elFibOptionMatchCaseInput }-${ uniquid }`.replace( /\./g, '' );
	elFibOptionComparisonInput.forEach( ( elInput ) => {
		elInput.name = `${ className.elFibOptionComparisonInput }-${ uniquid }`.replace( /\./g, '' );
		if ( elInput.value === 'equal' ) {
			elInput.checked = true;
		}
	} );
	elFibBlankOptionItemClone.insertAdjacentElement( 'beforebegin', elFibBlankOptionItemNew );
	elFibBlankOptionItemNew.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( elFibBlankOptionItemNew, 1 );
	// End clone blank options

	const elBtnFibSaveContent = elQuestionEditMain.querySelector( `${ className.elBtnFibSaveContent }` );
	lpUtils.lpSetLoadingEl( elBtnFibInsertBlank, 1 );
	fibSaveContent( null, elBtnFibSaveContent, () => {
		lpUtils.lpSetLoadingEl( elBtnFibInsertBlank, 0 );
	} );
};
// Delete all blanks
const fibDeleteAllBlanks = ( e, target ) => {
	const elBtnFibDeleteAllBlanks = target.closest( `${ className.elBtnFibDeleteAllBlanks }` );
	if ( ! elBtnFibDeleteAllBlanks ) {
		return;
	}

	const elQuestionEditMain = elBtnFibDeleteAllBlanks.closest( `${ className.elQuestionEditMain }` );
	if ( ! elQuestionEditMain ) {
		return;
	}

	const questionId = elQuestionEditMain.dataset.questionId;
	const dataAnswers = getDataAnswersConfig( elQuestionEditMain );

	SweetAlert.fire( {
		title: elBtnFibDeleteAllBlanks.dataset.title,
		text: elBtnFibDeleteAllBlanks.dataset.content,
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		cancelButtonText: lpSettings.i18n.cancel,
		confirmButtonText: lpSettings.i18n.yes,
		reverseButtons: true,
	} ).then( ( result ) => {
		if ( result.isConfirmed ) {
			const editor = window.tinymce.get( `${ className.elQuestionFibInput }-${ questionId }` );
			const elBlanks = editor.dom.select( `.${ className.elQuestionFibInput }` );
			elBlanks.forEach( ( elBlank ) => {
				editor.dom.remove( elBlank, true );
			} );

			dataAnswers.meta_data = {};
			setDataAnswersConfig( elQuestionEditMain, dataAnswers );

			const elFibBlankOptions = elQuestionEditMain.querySelector( `${ className.elFibBlankOptions }` );
			const elFibBlankOptionItems = elFibBlankOptions.querySelectorAll( `${ className.elFibBlankOptionItem }:not(.clone)` );
			elFibBlankOptionItems.forEach( ( elFibBlankOptionItem ) => {
				elFibBlankOptionItem.remove();
			} );

			const elBtnFibSaveContent = elQuestionEditMain.querySelector( `${ className.elBtnFibSaveContent }` );
			lpUtils.lpSetLoadingEl( elBtnFibDeleteAllBlanks, 1 );
			fibSaveContent( null, elBtnFibSaveContent, () => {
				lpUtils.lpSetLoadingEl( elBtnFibDeleteAllBlanks, 0 );
			} );
		}
	} );
};
// Clear content FIB question
const fibClearContent = ( e, target ) => {
	const elBtnFibClearAllContent = target.closest( `${ className.elBtnFibClearAllContent }` );
	if ( ! elBtnFibClearAllContent ) {
		return;
	}

	const elQuestionEditMain = elBtnFibClearAllContent.closest( `${ className.elQuestionEditMain }` );
	if ( ! elQuestionEditMain ) {
		return;
	}

	const questionId = elQuestionEditMain.dataset.questionId;
	const dataAnswers = getDataAnswersConfig( elQuestionEditMain );

	SweetAlert.fire( {
		title: elBtnFibClearAllContent.dataset.title,
		text: elBtnFibClearAllContent.dataset.content,
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		cancelButtonText: lpSettings.i18n.cancel,
		confirmButtonText: lpSettings.i18n.yes,
		reverseButtons: true,
	} ).then( ( result ) => {
		if ( result.isConfirmed ) {
			const editor = window.tinymce.get( `lp-question-fib-input-${ questionId }` );
			editor.setContent( '' );

			dataAnswers.meta_data = {};
			setDataAnswersConfig( elQuestionEditMain, dataAnswers );

			const elFibBlankOptions = elQuestionEditMain.querySelector( `${ className.elFibBlankOptions }` );
			const elFibBlankOptionItems = elFibBlankOptions.querySelectorAll(
				`${ className.elFibBlankOptionItem }:not(.clone)`
			);
			elFibBlankOptionItems.forEach( ( elFibBlankOptionItem ) => {
				elFibBlankOptionItem.remove();
			} );

			const elBtnFibSaveContent = elQuestionEditMain.querySelector( `${ className.elBtnFibSaveContent }` );
			lpUtils.lpSetLoadingEl( elBtnFibClearAllContent, 1 );
			fibSaveContent( null, elBtnFibSaveContent, () => {
				lpUtils.lpSetLoadingEl( elBtnFibClearAllContent, 0 );
			} );
		}
	} );
};
// Remove blank
const fibDeleteBlank = ( e, target ) => {
	const elBtnFibOptionDelete = target.closest( `${ className.elBtnFibOptionDelete }` );
	if ( ! elBtnFibOptionDelete ) {
		return;
	}

	const elQuestionEditMain = elBtnFibOptionDelete.closest( `${ className.elQuestionEditMain }` );
	if ( ! elQuestionEditMain ) {
		return;
	}

	const questionId = elQuestionEditMain.dataset.questionId;
	const elAnswersConfig = elQuestionEditMain.querySelector( `${ className.elAnswersConfig }` );
	const dataAnswers = getDataAnswersConfig( elQuestionEditMain );
	const elFibBlankOptionItem = elBtnFibOptionDelete.closest( `${ className.elFibBlankOptionItem }` );
	const blankId = elFibBlankOptionItem.dataset.id;

	SweetAlert.fire( {
		title: elBtnFibOptionDelete.dataset.title,
		text: elBtnFibOptionDelete.dataset.content,
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		cancelButtonText: lpSettings.i18n.cancel,
		confirmButtonText: lpSettings.i18n.yes,
		reverseButtons: true,
	} ).then( ( result ) => {
		if ( result.isConfirmed ) {
			// Find span with id on editor and remove it
			const editor = window.tinymce.get( `${ className.elQuestionFibInput }-${ questionId }` );
			const elBlank = editor.dom.select( `.${ className.elQuestionFibInput }[data-id="${ blankId }"]` );
			if ( elBlank[ 0 ] ) {
				// Remove tag html but keep content
				editor.dom.remove( elBlank[ 0 ], true );
			}

			elFibBlankOptionItem.remove();

			dataAnswers.meta_data = dataAnswers.meta_data || {};
			if ( dataAnswers.meta_data[ blankId ] ) {
				delete dataAnswers.meta_data[ blankId ];
			}

			setDataAnswersConfig( elQuestionEditMain, dataAnswers );

			const elBtnFibSaveContent = elQuestionEditMain.querySelector( `${ className.elBtnFibSaveContent }` );
			lpUtils.lpSetLoadingEl( elFibBlankOptionItem, 1 );
			fibSaveContent( null, elBtnFibSaveContent, () => {

			} );
		}
	} );
};
// Change title of blank option
let timeoutAutoUpdateFib;
const fibOptionTitleInputChange = ( e, target ) => {
	const elFibOptionTitleInput = target.closest( `${ className.elFibOptionTitleInput }` );
	if ( ! elFibOptionTitleInput ) {
		return;
	}

	const elQuestionFibOptionItem = elFibOptionTitleInput.closest( `${ className.elFibBlankOptionItem }` );
	if ( ! elQuestionFibOptionItem ) {
		return;
	}

	const elQuestionEditMain = elFibOptionTitleInput.closest( `${ className.elQuestionEditMain }` );
	if ( ! elQuestionEditMain ) {
		return;
	}

	const value = elFibOptionTitleInput.value.trim();
	const blankId = elQuestionFibOptionItem.dataset.id;
	const questionId = elQuestionEditMain.dataset.questionId;
	const editor = window.tinymce.get( `lp-question-fib-input-${ questionId }` );
	const elBlank = editor.dom.select( `.lp-question-fib-input[data-id="${ blankId }"]` );
	if ( elBlank[ 0 ] ) {
		// Update content of blank
		elBlank[ 0 ].textContent = value;
	}

	clearTimeout( timeoutAutoUpdateFib );
	timeoutAutoUpdateFib = setTimeout( () => {
		// Call ajax to update question description
		const elBtnFibSaveContent = elQuestionEditMain.querySelector( `${ className.elBtnFibSaveContent }` );
		fibSaveContent( null, elBtnFibSaveContent );
	}, 700 );
};
// Save content FIB question
const fibSaveContent = ( e, target, callBackCompleted ) => {
	const elBtnFibSaveContent = target.closest( `${ className.elBtnFibSaveContent }` );
	if ( ! elBtnFibSaveContent ) {
		return;
	}

	const elQuestionEditMain = elBtnFibSaveContent.closest( `${ className.elQuestionEditMain }` );
	const questionId = elQuestionEditMain.dataset.questionId;

	const dataAnswers = getDataAnswersConfig( elQuestionEditMain );
	if ( ! dataAnswers ) {
		return;
	}

	const editor = window.tinymce.get( `${ className.elQuestionFibInput }-${ questionId }` );
	dataAnswers.title = editor.getContent();

	const elFibBlankOptionItems = elQuestionEditMain.querySelectorAll( `${ className.elFibBlankOptionItem }:not(.clone)` );
	if ( elFibBlankOptionItems ) {
		elFibBlankOptionItems.forEach( ( elFibBlankOptionItem ) => {
			const blankId = elFibBlankOptionItem.dataset.id;
			const elFibOptionMatchCaseInput = elFibBlankOptionItem.querySelector( `${ className.elFibOptionMatchCaseInput }` );
			const elFibOptionComparisonInput = elFibBlankOptionItem.querySelector( `${ className.elFibOptionComparisonInput }:checked` );

			dataAnswers.meta_data[ blankId ].match_case = elFibOptionMatchCaseInput.checked ? 1 : 0;
			dataAnswers.meta_data[ blankId ].comparison = elFibOptionComparisonInput.value;
		} );
	}

	//console.log( 'dataAnswers', dataAnswers );

	if ( ! callBackCompleted ) {
		lpUtils.lpSetLoadingEl( elBtnFibSaveContent, 1 );
	}

	// Call ajax to update answers config
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;

			if ( status === 'success' ) {
				setDataAnswersConfig( elQuestionEditMain, dataAnswers );
			} else {
				throw `Error: ${ message }`;
			}

			showToast( message, status );
		},
		error: ( error ) => {
			showToast( error, 'error' );
		},
		completed: () => {
			if ( callBackCompleted && typeof callBackCompleted === 'function' ) {
				callBackCompleted();
			} else {
				lpUtils.lpSetLoadingEl( elBtnFibSaveContent, 0 );
			}
		},
	};

	//console.log( 'dataAnswers', dataAnswers );

	const dataSend = {
		action: 'update_question_answers_config',
		question_id: questionId,
		answers: dataAnswers,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};
// Show/hide match case option
const fibShowHideMatchCaseOption = ( e, target ) => {
	const elFibOptionMatchCaseInput = target.closest( `${ className.elFibOptionMatchCaseInput }` );
	if ( ! elFibOptionMatchCaseInput ) {
		return;
	}

	const elQuestionFibOptionDetail = elFibOptionMatchCaseInput.closest( `${ className.elQuestionFibOptionDetail }` );
	const elFibOptionMatchCaseWrap = elQuestionFibOptionDetail.querySelector( `${ className.elFibOptionMatchCaseWrap }` );
	if ( ! elQuestionFibOptionDetail || ! elFibOptionMatchCaseWrap ) {
		return;
	}

	if ( elFibOptionMatchCaseInput.checked ) {
		lpUtils.lpShowHideEl( elFibOptionMatchCaseWrap, 1 );
	} else {
		lpUtils.lpShowHideEl( elFibOptionMatchCaseWrap, 0 );
	}
};
/***** End Fill in the blank question type *****/

/**
 * Toggle section
 *
 * @param e
 * @param target
 * @param el_trigger  is class name or id name, to find of element to trigger toggle
 * @param els_exclude
 */
const toggleSection = ( e, target, el_trigger = '', els_exclude = [] ) => {
	if ( ! el_trigger ) {
		el_trigger = className.elTriggerToggle;
	}

	if ( els_exclude && els_exclude.length > 0 ) {
		for ( const elExclude of els_exclude ) {
			if ( target.closest( elExclude ) ) {
				return;
			}
		}
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

// Generate a random string of specified length, for set unique id
const randomString = ( length = 10 ) => {
	const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
	let result = '';
	for ( let i = 0; i < length; i++ ) {
		result += chars.charAt( Math.floor( Math.random() * chars.length ) );
	}
	return result;
};
// Decode HTML entities
const decodeHtml = ( html ) => {
	const txt = document.createElement( 'textarea' );
	txt.innerHTML = html;
	return txt.value;
};
// Show toast notification
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

const events = () => {
	// Event click
	document.addEventListener( 'click', ( e ) => {
		const target = e.target;
		deleteQuestionAnswer( e, target );
		addQuestionAnswer( e, target );
		createQuestionType( e, target );
		fibInsertBlank( e, target );
		fibDeleteAllBlanks( e, target );
		fibClearContent( e, target );
		fibDeleteBlank( e, target );
		fibSaveContent( e, target );
		fibShowHideMatchCaseOption( e, target );
		if ( target.closest( `${ className.elFibOptionMatchCaseInput }` ) ||
			target.closest( `${ className.elFibOptionComparisonInput }` ) ) {
			const elQuestionEditMain = target.closest( `${ className.elQuestionEditMain }` );
			const elSaveButton = elQuestionEditMain.querySelector( `${ className.elBtnFibSaveContent }` );
			fibSaveContent( e, elSaveButton );
		}
		lpUtils.toggleCollapse( e, target );
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
		fibOptionTitleInputChange( e, target );
		checkCanAddAnswer( e, target );
	} );
	// Event keydown
	document.addEventListener( 'keydown', ( e ) => {
		const target = e.target;
		// Event enter
		if ( e.key === 'Enter' ) {
			if ( target.closest( `${ className.elQuestionAnswerTitleNewInput }` ) ) {
				const elQuestionAnswerItemAddNew = target.closest( `${ className.elQuestionAnswerItemAddNew }` );
				const elBtnAddAnswer = elQuestionAnswerItemAddNew.querySelector( `${ className.elBtnAddAnswer }` );
				addQuestionAnswer( e, elBtnAddAnswer );
				e.preventDefault();
			} else if ( target.closest( `${ className.elQuestionAnswerTitleInput }` ) ||
				target.closest( '.lp-question-point-input' ) ||
				target.closest( `${ className.elFibOptionTitleInput }` ) ) {
				e.preventDefault();
			}
		}
	} );
};

// Element root ready.
lpUtils.lpOnElementReady(
	`${ className.elEditQuestionWrap }`,
	( elEditQuestionWrap ) => {
		const findClass = className.elQuestionEditMain.replace( '.', '' );
		if ( ! elEditQuestionWrap.classList.contains( findClass ) ) {
			return;
		}

		events();
		initTinyMCE();
		sortAbleQuestionAnswer( elEditQuestionWrap );
	}
);

export {
	events,
	initTinyMCE,
	showToast,
	sortAbleQuestionAnswer,
};
