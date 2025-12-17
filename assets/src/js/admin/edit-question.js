/**
 * Edit question JS handler.
 *
 * @since 4.2.9
 * @version 1.0.0
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify';
import SweetAlert from 'sweetalert2';
import Sortable from 'sortablejs';

const idUrlHandle = 'edit-question';
let fibSelection;
let timeoutAutoUpdateAnswer, timeoutAutoUpdateFib, timeoutAutoUpdateQuestion;

// EditQuestion class
export class EditQuestion {
	static selectors = {
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

	constructor() {}

	init() {
		this.events();
		this.initTinyMCE().then();
	}

	events() {
		if ( EditQuestion._loadedEvents ) {
			return;
		}
		EditQuestion._loadedEvents = true;

		// Sortable answers's question
		const elQuestionEditMains = document.querySelectorAll(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);
		elQuestionEditMains.forEach( ( elQuestionEditMain ) => {
			this.sortAbleQuestionAnswer( elQuestionEditMain );
		} );
		// End sortable

		// Event click
		lpUtils.eventHandlers( 'click', [
			{
				selector: EditQuestion.selectors.elBtnQuestionCreateType,
				callBack: this.createQuestionType.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elBtnAddAnswer,
				callBack: this.addQuestionAnswer.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elBtnDeleteAnswer,
				callBack: this.deleteQuestionAnswer.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elBtnFibInsertBlank,
				callBack: this.fibInsertBlank.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elBtnFibDeleteAllBlanks,
				callBack: this.fibDeleteAllBlanks.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elBtnFibSaveContent,
				callBack: this.fibSaveContent.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elBtnFibClearAllContent,
				callBack: this.fibClearContent.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elBtnFibOptionDelete,
				callBack: this.fibDeleteBlank.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elFibOptionMatchCaseInput,
				callBack: this.fibShowHideMatchCaseOption.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elFibOptionComparisonInput,
				callBack: ( args ) => {
					const { e, target } = args;
					const elQuestionEditMain = target.closest(
						`${ EditQuestion.selectors.elQuestionEditMain }`
					);

					const elBtnFibSaveContent =
						elQuestionEditMain.querySelector(
							`${ EditQuestion.selectors.elBtnFibSaveContent }`
						);

					elBtnFibSaveContent.click();
				},
			},
		] );

		// Toggle collapse
		document.addEventListener( 'click', ( e ) => {
			const target = e.target;
			lpUtils.toggleCollapse(
				e,
				target,
				EditQuestion.selectors.elTriggerToggle
			);
		} );

		// Event keyup
		lpUtils.eventHandlers( 'keyup', [
			{
				selector: EditQuestion.selectors.elQuestionAnswerTitleNewInput,
				callBack: this.checkCanAddAnswer.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elFibOptionTitleInput,
				callBack: this.fibOptionTitleInputChange.name,
				class: this,
			},
			{
				selector: EditQuestion.selectors.elAutoSaveQuestion,
				callBack: this.autoUpdateQuestion.name,
				class: this,
			},
		] );

		// Event keydown
		lpUtils.eventHandlers( 'keydown', [
			{
				selector: EditQuestion.selectors.elQuestionAnswerTitleNewInput,
				callBack: this.addQuestionAnswer.name,
				class: this,
				checkIsEventEnter: true,
			},
		] );

		// Event change
		lpUtils.eventHandlers( 'change', [
			{
				selector: EditQuestion.selectors.elAutoSaveAnswer,
				callBack: this.autoUpdateAnswer.name,
				class: this,
			},
		] );

		// TinyMCE events
		this.eventEditorTinymce();
	}

	// Run async to re-init all TinyMCE editors, because it slow if have many editors
	async initTinyMCE() {
		const elTextareas = document.querySelectorAll( '.lp-editor-tinymce' );

		elTextareas.forEach( ( elTextarea ) => {
			const idTextarea = elTextarea.id;

			this.reInitTinymce( idTextarea );
		} );
	}

	reInitTinymce( id ) {
		window.tinymce.execCommand( 'mceRemoveEditor', true, id );
		window.tinymce.execCommand( 'mceAddEditor', true, id );
	}

	// Events for TinyMCE editor
	eventEditorTinymce() {
		window.tinymce.on( 'AddEditor', ( eEditor ) => {
			const id = eEditor.editor.id;
			const editor = window.tinymce.get( id );
			if ( ! editor ) {
				return;
			}

			if ( id === 'content' ) {
				return;
			}

			// Active tab visual
			const wrapEditor = document.querySelector( `#wp-${ id }-wrap` );
			if ( wrapEditor ) {
				wrapEditor.classList.add( 'tmce-active' );
				wrapEditor.classList.remove( 'html-active' );
			}

			const elTextarea = document.getElementById( id );
			const elQuestionEditMain = elTextarea.closest(
				`${ EditQuestion.selectors.elQuestionEditMain }`
			);
			const questionId = elQuestionEditMain.dataset.questionId;
			editor.settings.force_p_newlines = false;
			editor.settings.forced_root_block = '';
			editor.settings.force_br_newlines = true;

			// Config use absolute url
			editor.settings.relative_urls = false;
			editor.settings.remove_script_host = false;
			editor.settings.convert_urls = true;
			editor.settings.document_base_url = lpData.site_url;
			// End config use absolute url

			// Events focus in TinyMCE editor
			editor.on( 'change keyup', ( e ) => {
				// Auto save if it has class lp-auto-save
				elTextarea.value = editor.getContent();
				this.autoUpdateQuestion( {
					e,
					target: elTextarea,
				} );
			} );

			editor.on( 'blur', ( e ) => {
				//console.log( 'Editor blurred:', e.target.id );
			} );
			editor.on( 'focusin', ( e ) => {} );
			editor.on( 'init', () => {
				// Add style
				editor.dom.addStyle( `
				body {
					line-height: 2.2 !important;
				}
				.${ EditQuestion.selectors.elQuestionFibInput } {
					border: 1px dashed rebeccapurple;
					padding: 5px;
				}
			` );
			} );
			editor.on( 'setcontent', ( e ) => {
				const uniquid = this.randomString();
				const elementg = editor.dom.select(
					`.${ EditQuestion.selectors.elQuestionFibInput }[data-id="${ uniquid }"]`
				);
				if ( elementg[ 0 ] ) {
					elementg[ 0 ].focus();
				}

				editor.dom.bind( elementg[ 0 ], 'input', ( e ) => {
					//console.log( 'Input changed:', e.target.value );
				} );
			} );
			editor.on( 'selectionchange', ( e ) => {
				fibSelection = editor.selection;

				// Check selection is blank, check empty blank content
				if (
					fibSelection
						.getNode()
						.classList.contains(
							`${ EditQuestion.selectors.elQuestionFibInput }`
						)
				) {
					const blankId = fibSelection.getNode().dataset.id;
					const textBlank = fibSelection.getNode().textContent.trim();
					if ( textBlank.length === 0 ) {
						const editorId = editor.id;
						const questionId = editorId.replace(
							`${ EditQuestion.selectors.elQuestionFibInput }-`,
							''
						);
						const elQuestionEditMain = document.querySelector(
							`${ EditQuestion.selectors.elQuestionEditMain }[data-question-id="${ questionId }"]`
						);
						const elQuestionBlankOptions =
							elQuestionEditMain.querySelector(
								`${ EditQuestion.selectors.elFibBlankOptions }`
							);
						const elFibBlankOptionItem =
							elQuestionBlankOptions.querySelector(
								`${ EditQuestion.selectors.elFibBlankOptionItem }[data-id="${ blankId }"]`
							);
						if ( elFibBlankOptionItem ) {
							lpUtils.lpShowHideEl( elFibBlankOptionItem, 0 );
						}
					} else {
						const elTextarea = document.getElementById( id );
						const elAnswersConfig = elTextarea.closest(
							`${ EditQuestion.selectors.elAnswersConfig }`
						);
						const elFibBlankOptionItem =
							elAnswersConfig.querySelector(
								`${ EditQuestion.selectors.elFibBlankOptionItem }[data-id="${ blankId }"]`
							);
						if ( elFibBlankOptionItem ) {
							const elFibOptionTitleInput =
								elFibBlankOptionItem.querySelector(
									`${ EditQuestion.selectors.elFibOptionTitleInput }`
								);
							if ( elFibOptionTitleInput ) {
								elFibOptionTitleInput.value = textBlank;
							}
						}
					}
				}
			} );
			editor.on( 'Undo', ( e ) => {
				const contentUndo = editor.getContent();
				const selection = editor.selection;
				const nodeUndo = selection.getNode();

				if (
					nodeUndo.classList.contains(
						`${ EditQuestion.selectors.elQuestionFibInput }`
					)
				) {
					const blankId = nodeUndo.dataset.id;
					const elFibBlankOptionItem = document.querySelector(
						`${ EditQuestion.selectors.elFibBlankOptionItem }[data-id="${ blankId }"]`
					);

					if ( elFibBlankOptionItem ) {
						lpUtils.lpShowHideEl( elFibBlankOptionItem, 1 );
					}
				}
			} );
			editor.on( 'Redo', ( e ) => {} );
		} );
	}

	autoUpdateQuestion( args ) {
		let { e, target, key, value } = args;
		const elAutoSave = target.closest(
			`${ EditQuestion.selectors.elAutoSaveQuestion }`
		);
		if ( ! elAutoSave ) {
			return;
		}

		const elQuestionEditMain = elAutoSave.closest(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);
		const questionId = elQuestionEditMain.dataset.questionId;

		clearTimeout( timeoutAutoUpdateQuestion );
		timeoutAutoUpdateQuestion = setTimeout( () => {
			// Call ajax to update question description
			const callBack = {
				success: ( response ) => {
					const { message, status } = response;

					if ( status === 'success' ) {
						lpToastify.show( message, status );
					} else {
						throw `Error: ${ message }`;
					}
				},
				error: ( error ) => {
					lpToastify.show( error, 'error' );
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
					if (
						! elAutoSave.classList.contains( 'lp-editor-tinymce' )
					) {
						return;
					}

					const textAreaId = elAutoSave.id;
					key = textAreaId
						.replace( /lp-/g, '' )
						.replace( `-${ questionId }`, '' )
						.replace( /-/g, '_' );
					if ( ! key ) {
						return;
					}
				}

				value = elAutoSave.value;
			}

			dataSend[ key ] = value;

			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}, 700 );
	}
	// Create question type
	createQuestionType( args ) {
		const { e, target } = args;
		const elBtnQuestionCreateType = target.closest(
			`${ EditQuestion.selectors.elBtnQuestionCreateType }`
		);
		if ( ! elBtnQuestionCreateType ) {
			return;
		}

		const elQuestionEditMain = elBtnQuestionCreateType.closest(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);
		if ( ! elQuestionEditMain ) {
			return;
		}

		const questionId = elQuestionEditMain.dataset.questionId;
		const elQuestionTypeNew = elQuestionEditMain.querySelector(
			`${ EditQuestion.selectors.elQuestionTypeNew }`
		);
		if ( ! elQuestionTypeNew ) {
			return;
		}

		const questionType = elQuestionTypeNew.value.trim();
		if ( ! questionType ) {
			const message = elQuestionTypeNew.dataset.messEmptyType;
			lpToastify.show( message, 'error' );
			return;
		}

		// Call ajax to create new question type
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				if ( status === 'success' ) {
					const { html_option_answers } = data;
					const elAnswersConfig = elQuestionEditMain.querySelector(
						`${ EditQuestion.selectors.elAnswersConfig }`
					);
					elAnswersConfig.outerHTML = html_option_answers;
					this.initTinyMCE();
					this.sortAbleQuestionAnswer( elQuestionEditMain );

					lpToastify.show( message, status );
				} else {
					throw `Error: ${ message }`;
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
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
	}

	addQuestionAnswer( args ) {
		const { e, target } = args;
		const elQuestionAnswerItemAddNew = target.closest(
			`${ EditQuestion.selectors.elQuestionAnswerItemAddNew }`
		);
		if ( ! elQuestionAnswerItemAddNew ) {
			return;
		}

		e.preventDefault();

		const elQuestionAnswerTitleNewInput =
			elQuestionAnswerItemAddNew.querySelector(
				`${ EditQuestion.selectors.elQuestionAnswerTitleNewInput }`
			);

		if ( ! elQuestionAnswerTitleNewInput.value.trim() ) {
			const message =
				elQuestionAnswerTitleNewInput.dataset.messEmptyTitle;
			lpToastify.show( message, 'error' );
			return;
		}

		const elQuestionEditMain = target.closest(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);
		const elQuestionAnswerClone = elQuestionEditMain.querySelector(
			`${ EditQuestion.selectors.elQuestionAnswerItem }.clone`
		);
		const elQuestionAnswerNew = elQuestionAnswerClone.cloneNode( true );
		const elQuestionAnswerTitleInputNew = elQuestionAnswerNew.querySelector(
			`${ EditQuestion.selectors.elQuestionAnswerTitleInput }`
		);

		elQuestionAnswerNew.classList.remove( 'clone' );
		lpUtils.lpShowHideEl( elQuestionAnswerNew, 1 );
		lpUtils.lpSetLoadingEl( elQuestionAnswerNew, 1 );
		elQuestionAnswerClone.insertAdjacentElement(
			'beforebegin',
			elQuestionAnswerNew
		);

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
					elQuestionAnswerNew.dataset.answerId =
						question_answer.question_answer_id;
					lpUtils.lpSetLoadingEl( elQuestionAnswerNew, 0 );

					// Set data lp-answers-config
					const dataAnswers =
						this.getDataAnswersConfig( elQuestionEditMain );
					dataAnswers.push( question_answer );
					this.setDataAnswersConfig(
						elQuestionEditMain,
						dataAnswers
					);
				} else {
					throw `Error: ${ message }`;
				}

				lpToastify.show( message, status );
			},
			error: ( error ) => {
				elQuestionAnswerNew.remove();
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				this.checkCanAddAnswer( null, elQuestionAnswerTitleNewInput );
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
	}

	// Check to enable or disable add new question button
	checkCanAddAnswer( args ) {
		const { e, target } = args;
		const elTrigger = target.closest(
			EditQuestion.selectors.elQuestionAnswerTitleNewInput
		);
		if ( ! elTrigger ) {
			return;
		}

		const elQuestionAnswerItemAddNew = elTrigger.closest(
			`${ EditQuestion.selectors.elQuestionAnswerItemAddNew }`
		);
		if ( ! elQuestionAnswerItemAddNew ) {
			return;
		}

		const elBtnAddAnswer = elQuestionAnswerItemAddNew.querySelector(
			`${ EditQuestion.selectors.elBtnAddAnswer }`
		);
		if ( ! elBtnAddAnswer ) {
			return;
		}

		const titleValue = elTrigger.value.trim();
		if ( titleValue ) {
			elBtnAddAnswer.classList.add( 'active' );
		} else {
			elBtnAddAnswer.classList.remove( 'active' );
		}
	}

	// Auto update question answer
	autoUpdateAnswer( args ) {
		const { e, target } = args;
		const elAutoSaveAnswer = target.closest(
			`${ EditQuestion.selectors.elAutoSaveAnswer }`
		);
		if ( ! elAutoSaveAnswer ) {
			return;
		}

		const elQuestionAnswerItem = elAutoSaveAnswer.closest(
			`${ EditQuestion.selectors.elQuestionAnswerItem }`
		);

		clearTimeout( timeoutAutoUpdateAnswer );
		timeoutAutoUpdateAnswer = setTimeout( () => {
			const elQuestionEditMain = elAutoSaveAnswer.closest(
				`${ EditQuestion.selectors.elQuestionEditMain }`
			);

			const questionId = elQuestionEditMain.dataset.questionId;
			const dataAnswers = this.getDataAnswersConfig( elQuestionEditMain );
			const elAnswersConfig = elQuestionEditMain.querySelector(
				`${ EditQuestion.selectors.elAnswersConfig }`
			);

			// For both radio and checkbox.
			const dataAnswersOld = structuredClone( dataAnswers );

			// Get position of answers
			const elQuestionAnswerItems = elAnswersConfig.querySelectorAll(
				`${ EditQuestion.selectors.elQuestionAnswerItem }:not(.clone)`
			);
			const answersPosition = {};
			elQuestionAnswerItems.forEach( ( elQuestionAnswerItem, index ) => {
				answersPosition[ elQuestionAnswerItem.dataset.answerId ] =
					index + 1; // Start from 1
			} );

			//console.log( 'answersPosition', answersPosition );

			dataAnswers.map( ( answer, k ) => {
				const elQuestionAnswerItem = elQuestionEditMain.querySelector(
					`${ EditQuestion.selectors.elQuestionAnswerItem }[data-answer-id="${ answer.question_answer_id }"]`
				);
				const elInputAnswerSetTrue = elQuestionAnswerItem.querySelector(
					`${ EditQuestion.selectors.elInputAnswerSetTrue }`
				);
				const elInputAnswerTitle = elQuestionAnswerItem.querySelector(
					`${ EditQuestion.selectors.elQuestionAnswerTitleInput }`
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

					lpToastify.show( message, status );
				},
				error: ( error ) => {
					// rollback changes to old data
					dataAnswersOld.forEach( ( answer ) => {
						const elAnswerItem = elQuestionEditMain.querySelector(
							`${ EditQuestion.selectors.elQuestionAnswerItem }[data-answer-id="${ answer.question_answer_id }"]`
						);
						const inputAnswerSetTrue = elAnswerItem.querySelector(
							`${ EditQuestion.selectors.elInputAnswerSetTrue }`
						);
						if ( answer.is_true === 'yes' ) {
							inputAnswerSetTrue.checked = true;
						}

						return answer;
					} );
					lpToastify.show( error, 'error' );
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
	}

	// Sortable answers's question
	sortAbleQuestionAnswer( elQuestionEditMain ) {
		let isUpdateSectionPosition = 0;
		let timeout;

		const elQuestionAnswers = elQuestionEditMain.querySelectorAll(
			`${ EditQuestion.selectors.elAnswersConfig }`
		);

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
						const elAutoSaveAnswer =
							elQuestionAnswerItem.querySelector(
								`${ EditQuestion.selectors.elAutoSaveAnswer }`
							);
						this.autoUpdateAnswer( {
							e: null,
							target: elAutoSaveAnswer,
						} );
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
	}

	// Delete question answer
	deleteQuestionAnswer( args ) {
		const { e, target } = args;
		const elBtnDeleteAnswer = target.closest(
			`${ EditQuestion.selectors.elBtnDeleteAnswer }`
		);
		if ( ! elBtnDeleteAnswer ) {
			return;
		}

		const elQuestionAnswerItem = elBtnDeleteAnswer.closest(
			`${ EditQuestion.selectors.elQuestionAnswerItem }`
		);
		if ( ! elQuestionAnswerItem ) {
			return;
		}

		const elQuestionEditMain = elBtnDeleteAnswer.closest(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);

		const questionId = elQuestionEditMain.dataset.questionId;
		const questionAnswerId = elQuestionAnswerItem.dataset.answerId;
		if ( ! questionId || ! questionAnswerId ) {
			return;
		}

		SweetAlert.fire( {
			title: elBtnDeleteAnswer.dataset.title || 'Are you sure?',
			text:
				elBtnDeleteAnswer.dataset.content ||
				'Do you want to delete this answer?',
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				lpUtils.lpSetLoadingEl( elQuestionAnswerItem, 1 );

				// Call ajax to delete item from section
				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						lpToastify.show( message, status );

						if ( status === 'success' ) {
							const elQuestionAnswerId = parseInt(
								elQuestionAnswerItem.dataset.answerId
							);
							elQuestionAnswerItem.remove();

							const dataAnswers =
								this.getDataAnswersConfig( elQuestionEditMain );
							if ( dataAnswers ) {
								const updatedAnswers = dataAnswers.filter(
									( answer ) =>
										parseInt(
											answer.question_answer_id
										) !== elQuestionAnswerId
								);
								this.setDataAnswersConfig(
									elQuestionEditMain,
									updatedAnswers
								);
							}
						}
					},
					error: ( error ) => {
						lpToastify.show( error, 'error' );
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
	}

	// Get data answers config
	getDataAnswersConfig( elQuestionEditMain ) {
		const elAnswersConfig = elQuestionEditMain.querySelector(
			`${ EditQuestion.selectors.elAnswersConfig }`
		);
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
	}

	// Set data answers config
	setDataAnswersConfig( elQuestionEditMain, dataAnswers ) {
		const elAnswersConfig = elQuestionEditMain.querySelector(
			`${ EditQuestion.selectors.elAnswersConfig }`
		);
		if ( ! elAnswersConfig ) {
			return;
		}

		if ( ! dataAnswers || typeof dataAnswers !== 'object' ) {
			dataAnswers = {};
		}

		elAnswersConfig.dataset.answers = JSON.stringify( dataAnswers );
	}

	/***** Fill in the blank question type *****/
	// For FIB question type
	fibInsertBlank = ( args ) => {
		const { e, target } = args;
		const elBtnFibInsertBlank = target.closest(
			EditQuestion.selectors.elBtnFibInsertBlank
		);
		if ( ! elBtnFibInsertBlank ) {
			return;
		}

		const textPlaceholder = elBtnFibInsertBlank.dataset.defaultText;
		const elQuestionEditMain = elBtnFibInsertBlank.closest(
			EditQuestion.selectors.elQuestionEditMain
		);
		const questionId = elQuestionEditMain.dataset.questionId;
		const messErrInserted = elBtnFibInsertBlank.dataset.messInserted;
		const messErrRequireSelectText =
			elBtnFibInsertBlank.dataset.messRequireSelectText;
		const idEditor = `${ EditQuestion.selectors.elQuestionFibInput }-${ questionId }`;

		const uniquid = this.randomString();
		let selectedText;
		if ( fibSelection ) {
			const elNode = fibSelection.getNode();
			if ( ! elNode ) {
				lpToastify.show(
					'Event insert blank has error, please try again',
					'error'
				);
				return;
			}

			const findParent = elNode.closest(
				`body[data-id="${ idEditor }"]`
			);
			if ( ! findParent ) {
				lpToastify.show( messErrRequireSelectText, 'error' );
				return;
			}

			if (
				elNode.classList.contains(
					`${ EditQuestion.selectors.elQuestionFibInput }`
				)
			) {
				lpToastify.show( messErrInserted, 'error' );
				return;
			}

			selectedText = fibSelection.getContent();
			if ( selectedText.length === 0 ) {
				selectedText = textPlaceholder;
			}

			const elInputNew = `<span class="${ EditQuestion.selectors.elQuestionFibInput }" data-id="${ uniquid }">${ selectedText }</span>`;

			fibSelection.setContent( elInputNew );
		} else {
			lpToastify.show( messErrRequireSelectText, 'error' );
			return;
		}

		const dataAnswers = this.getDataAnswersConfig( elQuestionEditMain );
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

		this.setDataAnswersConfig( elQuestionEditMain, dataAnswers );

		// Clone blank options
		const elFibBlankOptions = elQuestionEditMain.querySelector(
			`${ EditQuestion.selectors.elFibBlankOptions }`
		);
		const elFibBlankOptionItemClone = elQuestionEditMain.querySelector(
			`${ EditQuestion.selectors.elFibBlankOptionItemClone }`
		);
		const elFibBlankOptionItemNew =
			elFibBlankOptionItemClone.cloneNode( true );
		const countOptions = elFibBlankOptions.querySelectorAll(
			`${ EditQuestion.selectors.elFibBlankOptionItem }:not(.clone)`
		).length;
		const elFibBlankOptionIndex = elFibBlankOptionItemNew.querySelector(
			`${ EditQuestion.selectors.elFibBlankOptionIndex }`
		);
		const elFibOptionTitleInput = elFibBlankOptionItemNew.querySelector(
			`${ EditQuestion.selectors.elFibOptionTitleInput }`
		);
		const elFibOptionMatchCaseInput = elFibBlankOptionItemNew.querySelector(
			`${ EditQuestion.selectors.elFibOptionMatchCaseInput }`
		);
		const elFibOptionComparisonInput =
			elFibBlankOptionItemNew.querySelectorAll(
				`${ EditQuestion.selectors.elFibOptionComparisonInput }`
			);

		elFibBlankOptionItemNew.dataset.id = uniquid;
		elFibOptionTitleInput.name = `${ EditQuestion.selectors.elFibOptionTitleInput }-${ uniquid }`;
		elFibOptionTitleInput.value = this.decodeHtml( selectedText );
		elFibBlankOptionIndex.textContent = countOptions + 1 + '.';
		elFibOptionMatchCaseInput.name =
			`${ EditQuestion.selectors.elFibOptionMatchCaseInput }-${ uniquid }`.replace(
				/\./g,
				''
			);
		elFibOptionComparisonInput.forEach( ( elInput ) => {
			elInput.name =
				`${ EditQuestion.selectors.elFibOptionComparisonInput }-${ uniquid }`.replace(
					/\./g,
					''
				);
			if ( elInput.value === 'equal' ) {
				elInput.checked = true;
			}
		} );
		elFibBlankOptionItemClone.insertAdjacentElement(
			'beforebegin',
			elFibBlankOptionItemNew
		);
		elFibBlankOptionItemNew.classList.remove( 'clone' );
		lpUtils.lpShowHideEl( elFibBlankOptionItemNew, 1 );
		// End clone blank options

		const elBtnFibSaveContent = elQuestionEditMain.querySelector(
			`${ EditQuestion.selectors.elBtnFibSaveContent }`
		);
		lpUtils.lpSetLoadingEl( elBtnFibInsertBlank, 1 );
		this.fibSaveContent( {
			e: null,
			target: elBtnFibSaveContent,
			callBackCompleted: () => {
				lpUtils.lpSetLoadingEl( elBtnFibInsertBlank, 0 );
			},
		} );
	};

	// Delete all blanks
	fibDeleteAllBlanks( args ) {
		const { e, target } = args;
		const elBtnFibDeleteAllBlanks = target.closest(
			`${ EditQuestion.selectors.elBtnFibDeleteAllBlanks }`
		);
		if ( ! elBtnFibDeleteAllBlanks ) {
			return;
		}

		const elQuestionEditMain = elBtnFibDeleteAllBlanks.closest(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);
		if ( ! elQuestionEditMain ) {
			return;
		}

		const questionId = elQuestionEditMain.dataset.questionId;
		const dataAnswers = this.getDataAnswersConfig( elQuestionEditMain );

		SweetAlert.fire( {
			title: elBtnFibDeleteAllBlanks.dataset.title,
			text: elBtnFibDeleteAllBlanks.dataset.content,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				const editor = window.tinymce.get(
					`${ EditQuestion.selectors.elQuestionFibInput }-${ questionId }`
				);
				const elBlanks = editor.dom.select(
					`.${ EditQuestion.selectors.elQuestionFibInput }`
				);
				elBlanks.forEach( ( elBlank ) => {
					editor.dom.remove( elBlank, true );
				} );

				dataAnswers.meta_data = {};
				this.setDataAnswersConfig( elQuestionEditMain, dataAnswers );

				const elFibBlankOptions = elQuestionEditMain.querySelector(
					`${ EditQuestion.selectors.elFibBlankOptions }`
				);
				const elFibBlankOptionItems =
					elFibBlankOptions.querySelectorAll(
						`${ EditQuestion.selectors.elFibBlankOptionItem }:not(.clone)`
					);
				elFibBlankOptionItems.forEach( ( elFibBlankOptionItem ) => {
					elFibBlankOptionItem.remove();
				} );

				const elBtnFibSaveContent = elQuestionEditMain.querySelector(
					`${ EditQuestion.selectors.elBtnFibSaveContent }`
				);
				lpUtils.lpSetLoadingEl( elBtnFibDeleteAllBlanks, 1 );
				this.fibSaveContent( {
					e: null,
					target: elBtnFibSaveContent,
					callBackCompleted: () => {
						lpUtils.lpSetLoadingEl( elBtnFibDeleteAllBlanks, 0 );
					},
				} );
			}
		} );
	}
	// Clear content FIB question
	fibClearContent( args ) {
		const { e, target } = args;
		const elBtnFibClearAllContent = target.closest(
			`${ EditQuestion.selectors.elBtnFibClearAllContent }`
		);
		if ( ! elBtnFibClearAllContent ) {
			return;
		}

		const elQuestionEditMain = elBtnFibClearAllContent.closest(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);
		if ( ! elQuestionEditMain ) {
			return;
		}

		const questionId = elQuestionEditMain.dataset.questionId;
		const dataAnswers = this.getDataAnswersConfig( elQuestionEditMain );

		SweetAlert.fire( {
			title: elBtnFibClearAllContent.dataset.title,
			text: elBtnFibClearAllContent.dataset.content,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				const editor = window.tinymce.get(
					`lp-question-fib-input-${ questionId }`
				);
				editor.setContent( '' );

				dataAnswers.meta_data = {};
				this.setDataAnswersConfig( elQuestionEditMain, dataAnswers );

				const elFibBlankOptions = elQuestionEditMain.querySelector(
					`${ EditQuestion.selectors.elFibBlankOptions }`
				);
				const elFibBlankOptionItems =
					elFibBlankOptions.querySelectorAll(
						`${ EditQuestion.selectors.elFibBlankOptionItem }:not(.clone)`
					);
				elFibBlankOptionItems.forEach( ( elFibBlankOptionItem ) => {
					elFibBlankOptionItem.remove();
				} );

				const elBtnFibSaveContent = elQuestionEditMain.querySelector(
					`${ EditQuestion.selectors.elBtnFibSaveContent }`
				);
				lpUtils.lpSetLoadingEl( elBtnFibClearAllContent, 1 );
				this.fibSaveContent( {
					e: null,
					target: elBtnFibSaveContent,
					callBackCompleted: () => {
						lpUtils.lpSetLoadingEl( elBtnFibClearAllContent, 0 );
					},
				} );
			}
		} );
	}

	// Remove blank
	fibDeleteBlank( args ) {
		const { e, target } = args;
		const elBtnFibOptionDelete = target.closest(
			`${ EditQuestion.selectors.elBtnFibOptionDelete }`
		);
		if ( ! elBtnFibOptionDelete ) {
			return;
		}

		const elQuestionEditMain = elBtnFibOptionDelete.closest(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);
		if ( ! elQuestionEditMain ) {
			return;
		}

		const questionId = elQuestionEditMain.dataset.questionId;
		const elAnswersConfig = elQuestionEditMain.querySelector(
			`${ EditQuestion.selectors.elAnswersConfig }`
		);
		const dataAnswers = this.getDataAnswersConfig( elQuestionEditMain );
		const elFibBlankOptionItem = elBtnFibOptionDelete.closest(
			`${ EditQuestion.selectors.elFibBlankOptionItem }`
		);
		const blankId = elFibBlankOptionItem.dataset.id;

		SweetAlert.fire( {
			title: elBtnFibOptionDelete.dataset.title,
			text: elBtnFibOptionDelete.dataset.content,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				// Find span with id on editor and remove it
				const editor = window.tinymce.get(
					`${ EditQuestion.selectors.elQuestionFibInput }-${ questionId }`
				);
				const elBlank = editor.dom.select(
					`.${ EditQuestion.selectors.elQuestionFibInput }[data-id="${ blankId }"]`
				);
				if ( elBlank[ 0 ] ) {
					// Remove tag html but keep content
					editor.dom.remove( elBlank[ 0 ], true );
				}

				elFibBlankOptionItem.remove();

				dataAnswers.meta_data = dataAnswers.meta_data || {};
				if ( dataAnswers.meta_data[ blankId ] ) {
					delete dataAnswers.meta_data[ blankId ];
				}

				this.setDataAnswersConfig( elQuestionEditMain, dataAnswers );

				const elBtnFibSaveContent = elQuestionEditMain.querySelector(
					`${ EditQuestion.selectors.elBtnFibSaveContent }`
				);
				lpUtils.lpSetLoadingEl( elFibBlankOptionItem, 1 );
				this.fibSaveContent( {
					e: null,
					target: elBtnFibSaveContent,
					callBackCompleted: () => {
						lpUtils.lpSetLoadingEl( elFibBlankOptionItem, 0 );
					},
				} );
			}
		} );
	}

	// Change title of blank option
	fibOptionTitleInputChange( args ) {
		const { e, target } = args;
		const elFibOptionTitleInput = target.closest(
			`${ EditQuestion.selectors.elFibOptionTitleInput }`
		);
		if ( ! elFibOptionTitleInput ) {
			return;
		}

		const elQuestionFibOptionItem = elFibOptionTitleInput.closest(
			`${ EditQuestion.selectors.elFibBlankOptionItem }`
		);
		if ( ! elQuestionFibOptionItem ) {
			return;
		}

		const elQuestionEditMain = elFibOptionTitleInput.closest(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);
		if ( ! elQuestionEditMain ) {
			return;
		}

		const value = elFibOptionTitleInput.value.trim();
		const blankId = elQuestionFibOptionItem.dataset.id;
		const questionId = elQuestionEditMain.dataset.questionId;
		const editor = window.tinymce.get(
			`lp-question-fib-input-${ questionId }`
		);
		const elBlank = editor.dom.select(
			`.lp-question-fib-input[data-id="${ blankId }"]`
		);
		if ( elBlank[ 0 ] ) {
			// Update content of blank
			elBlank[ 0 ].textContent = value;
		}

		clearTimeout( timeoutAutoUpdateFib );
		timeoutAutoUpdateFib = setTimeout( () => {
			// Call ajax to update question description
			const elBtnFibSaveContent = elQuestionEditMain.querySelector(
				`${ EditQuestion.selectors.elBtnFibSaveContent }`
			);
			this.fibSaveContent( {
				e: null,
				target: elBtnFibSaveContent,
			} );
		}, 700 );
	}

	// Save content FIB question
	fibSaveContent( args ) {
		const { e, target, callBackCompleted = null } = args;
		const elBtnFibSaveContent = target.closest(
			`${ EditQuestion.selectors.elBtnFibSaveContent }`
		);
		if ( ! elBtnFibSaveContent ) {
			return;
		}

		const elQuestionEditMain = elBtnFibSaveContent.closest(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);
		const questionId = elQuestionEditMain.dataset.questionId;

		const dataAnswers = this.getDataAnswersConfig( elQuestionEditMain );
		if ( ! dataAnswers ) {
			return;
		}

		const editor = window.tinymce.get(
			`${ EditQuestion.selectors.elQuestionFibInput }-${ questionId }`
		);
		dataAnswers.title = editor.getContent();

		const elFibBlankOptionItems = elQuestionEditMain.querySelectorAll(
			`${ EditQuestion.selectors.elFibBlankOptionItem }:not(.clone)`
		);
		if ( elFibBlankOptionItems ) {
			elFibBlankOptionItems.forEach( ( elFibBlankOptionItem ) => {
				const blankId = elFibBlankOptionItem.dataset.id;
				const elFibOptionMatchCaseInput =
					elFibBlankOptionItem.querySelector(
						`${ EditQuestion.selectors.elFibOptionMatchCaseInput }`
					);
				const elFibOptionComparisonInput =
					elFibBlankOptionItem.querySelector(
						`${ EditQuestion.selectors.elFibOptionComparisonInput }:checked`
					);

				dataAnswers.meta_data[ blankId ].match_case =
					elFibOptionMatchCaseInput.checked ? 1 : 0;
				dataAnswers.meta_data[ blankId ].comparison =
					elFibOptionComparisonInput.value;
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
					this.setDataAnswersConfig(
						elQuestionEditMain,
						dataAnswers
					);
				} else {
					throw `Error: ${ message }`;
				}

				lpToastify.show( message, status );
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				if (
					callBackCompleted &&
					typeof callBackCompleted === 'function'
				) {
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
	}
	// Show/hide match case option
	fibShowHideMatchCaseOption( args ) {
		const { e, target } = args;
		const elFibOptionMatchCaseInput = target.closest(
			`${ EditQuestion.selectors.elFibOptionMatchCaseInput }`
		);
		if ( ! elFibOptionMatchCaseInput ) {
			return;
		}

		const elQuestionFibOptionDetail = elFibOptionMatchCaseInput.closest(
			`${ EditQuestion.selectors.elQuestionFibOptionDetail }`
		);
		const elFibOptionMatchCaseWrap =
			elQuestionFibOptionDetail.querySelector(
				`${ EditQuestion.selectors.elFibOptionMatchCaseWrap }`
			);
		if ( ! elQuestionFibOptionDetail || ! elFibOptionMatchCaseWrap ) {
			return;
		}

		if ( elFibOptionMatchCaseInput.checked ) {
			lpUtils.lpShowHideEl( elFibOptionMatchCaseWrap, 1 );
		} else {
			lpUtils.lpShowHideEl( elFibOptionMatchCaseWrap, 0 );
		}

		const elQuestionEditMain = elFibOptionMatchCaseInput.closest(
			`${ EditQuestion.selectors.elQuestionEditMain }`
		);

		const elBtnFibSaveContent = elQuestionEditMain.querySelector(
			`${ EditQuestion.selectors.elBtnFibSaveContent }`
		);

		elBtnFibSaveContent.click();
	}
	/***** End Fill in the blank question type *****/

	// Generate a random string of specified length, for set unique id
	randomString( length = 10 ) {
		const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
		let result = '';
		for ( let i = 0; i < length; i++ ) {
			result += chars.charAt(
				Math.floor( Math.random() * chars.length )
			);
		}
		return result;
	}
	// Decode HTML entities
	decodeHtml( html ) {
		const txt = document.createElement( 'textarea' );
		txt.innerHTML = html;
		return txt.value;
	}
}

const editQuestion = new EditQuestion();
lpUtils.lpOnElementReady(
	EditQuestion.selectors.elEditQuestionWrap,
	( elEditQuestionWrap ) => {
		const findClass = EditQuestion.selectors.elQuestionEditMain.replace(
			'.',
			''
		);
		if ( ! elEditQuestionWrap.classList.contains( findClass ) ) {
			return;
		}

		editQuestion.init();
	}
);
