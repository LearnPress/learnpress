import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { LpPopupSelectItemToAdd } from 'lpAssetsJsPath/lpPopupSelectItemToAdd.js';
import { EditQuestion } from 'lpAssetsJsPath/admin/edit-question.js';
import Sortable from 'sortablejs';
import SweetAlert from 'sweetalert2';

export class BuilderEditQuiz {
	constructor() {
		this.elEditQuizWrap = null;
		this.elEditListQuestions = null;
		this.quizID = null;
		this.lpPopupSelectItemToAdd = null;
		this.sortableInstance = null;
		this.sortableAnswerInstances = [];
		this.editQuestion = null; // Instance of EditQuestion for answer management
	}

	static selectors = {
		elDataQuiz: '.cb-section__quiz-edit',
		elBtnUpdateQuiz: '.cb-btn-update__quiz',
		elBtnTrashQuiz: '.cb-btn-trash__quiz',
		elQuizStatus: '.quiz-status',
		idTitle: 'title',
		idDescEditor: 'quiz_description_editor',
		elFormSetting: '.lp-form-setting-quiz',
		// Quiz questions tab selectors
		elEditQuizWrap: '.lp-edit-quiz-wrap',
		elQuestionEditMain: '.lp-question-edit-main',
		elEditListQuestions: '.lp-edit-list-questions',
		elQuestionItem: '.lp-question-item',
		elQuestionToggle: '.lp-question-toggle',
		elQuestionToggleAll: '.lp-question-toggle-all',
		elBtnAddQuestion: '.lp-btn-add-question',
		elBtnRemoveQuestion: '.lp-btn-remove-question',
		elBtnUpdateQuestionTitle: '.lp-btn-update-question-title',
		elBtnCancelUpdateQuestionTitle: '.lp-btn-cancel-update-question-title',
		elQuestionTitleNewInput: '.lp-question-title-new-input',
		elQuestionTitleInput: '.lp-question-title-input',
		elQuestionTypeNew: '.lp-question-type-new',
		elAddNewQuestion: 'add-new-question',
		LPTarget: '.lp-target',
		elCollapse: 'lp-collapse',
		elAnswersConfig: '.lp-answers-config',
	};

	init( container = null ) {
		console.log( '[BuilderEditQuiz] init() called, container:', container );
		this.initQuizQuestionsTab( container );
		this.events();
	}

	/**
	 * Reinitialize for a new quiz context (e.g., when popup loads different quiz)
	 */
	reinit( container = null ) {
		console.log( '[BuilderEditQuiz] reinit() called, container:', container );
		
		// Reset state
		this.elEditQuizWrap = null;
		this.elEditListQuestions = null;
		this.quizID = null;

		// Destroy old sortable instances
		if ( this.sortableInstance ) {
			this.sortableInstance.destroy();
			this.sortableInstance = null;
		}

		// Destroy answer sortable instances
		this.sortableAnswerInstances.forEach( ( instance ) => {
			if ( instance && typeof instance.destroy === 'function' ) {
				instance.destroy();
			}
		} );
		this.sortableAnswerInstances = [];

		 // Register events first (they use document delegation)
		this.events();

		// Use async init to wait for element when content is loaded via AJAX
		this.initQuizQuestionsTabAsync( container ).then( ( el ) => {
			console.log( '[BuilderEditQuiz] initQuizQuestionsTabAsync resolved, element found:', el );
		} ).catch( ( err ) => {
			console.log( '[BuilderEditQuiz] initQuizQuestionsTabAsync rejected:', err.message );
		} );
	}

	/**
	 * Initialize Quiz Questions Tab
	 */
	initQuizQuestionsTab( container = null ) {
		console.log( '[BuilderEditQuiz] initQuizQuestionsTab() called' );
		const searchContainer = container || document;
		const elEditQuizWrap = searchContainer.querySelector( BuilderEditQuiz.selectors.elEditQuizWrap );

		console.log( '[BuilderEditQuiz] Looking for .lp-edit-quiz-wrap, found:', elEditQuizWrap );

		if ( ! elEditQuizWrap ) {
			return;
		}

		this._initQuizQuestionsTabElement( elEditQuizWrap );
	}

	/**
	 * Initialize Quiz Questions Tab with waiting for element
	 * Used when content is loaded via AJAX
	 */
	initQuizQuestionsTabAsync( container = null, maxAttempts = 50, interval = 200 ) {
		console.log( '[BuilderEditQuiz] initQuizQuestionsTabAsync() called, maxAttempts:', maxAttempts, 'interval:', interval );
		
		return new Promise( ( resolve, reject ) => {
			let attempts = 0;
			const searchContainer = container || document;

			const checkElement = () => {
				attempts++;
				const elEditQuizWrap = searchContainer.querySelector( BuilderEditQuiz.selectors.elEditQuizWrap );

				console.log( `[BuilderEditQuiz] Attempt ${ attempts }/${ maxAttempts }, element found:`, !!elEditQuizWrap );

				if ( elEditQuizWrap ) {
					this._initQuizQuestionsTabElement( elEditQuizWrap );
					resolve( elEditQuizWrap );
				} else if ( attempts >= maxAttempts ) {
					// Element not found after max attempts, reject silently
					reject( new Error( 'Quiz questions tab element not found after ' + maxAttempts + ' attempts' ) );
				} else {
					setTimeout( checkElement, interval );
				}
			};

			checkElement();
		} );
	}

	/**
	 * Internal method to initialize quiz questions tab element
	 */
	_initQuizQuestionsTabElement( elEditQuizWrap ) {
		console.log( '[BuilderEditQuiz] _initQuizQuestionsTabElement() called' );
		
		this.elEditQuizWrap = elEditQuizWrap;
		this.elEditListQuestions = elEditQuizWrap.querySelector(
			BuilderEditQuiz.selectors.elEditListQuestions
		);

		console.log( '[BuilderEditQuiz] elEditListQuestions found:', !!this.elEditListQuestions );

		// Get quiz ID
		this._getQuizID( elEditQuizWrap );

		// Initialize popup select items
		this.lpPopupSelectItemToAdd = new LpPopupSelectItemToAdd();
		this.lpPopupSelectItemToAdd.init();

		// Init sortable for questions list
		this.sortAbleQuestion();

		// Init sortable for question answers (non-blocking)
		this._initAnswerSortables( elEditQuizWrap );

		// Initialize TinyMCE for question editors (non-blocking, chunked)
		this._initTinyMCEAsync( elEditQuizWrap );

		// Initialize EditQuestion for answer management (add/delete/update answers)
		this._initEditQuestion( elEditQuizWrap );
	}

	/**
	 * Initialize EditQuestion instance for answer management in quiz popup
	 * This enables add/delete/auto-save answer functionality
	 */
	_initEditQuestion( elEditQuizWrap ) {
		console.log( 'BuilderEditQuiz: _initEditQuestion called' );
		// Create EditQuestion instance if not exists
		if ( ! this.editQuestion ) {
			this.editQuestion = new EditQuestion();
		}

		// Initialize sortable for each question's answers
		const elQuestionEditMains = elEditQuizWrap.querySelectorAll(
			BuilderEditQuiz.selectors.elQuestionEditMain
		);

		elQuestionEditMains.forEach( ( elQuestionEditMain ) => {
			if ( this.editQuestion ) {
				this.editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
			}
		} );

		// Always register EditQuestion events for quiz popup
		// Events use document delegation, so they only need to be registered once
		// but we need to ensure they ARE registered in the frontend context
		console.log( 'BuilderEditQuiz: _initEditQuestion called, EditQuestion._loadedEvents =', EditQuestion._loadedEvents );

		if ( ! EditQuestion._loadedEvents ) {
			console.log( 'BuilderEditQuiz: Registering EditQuestion events...' );
			this.editQuestion.events();
			console.log( 'BuilderEditQuiz: EditQuestion events registered, _loadedEvents =', EditQuestion._loadedEvents );
		} else {
			console.log( 'BuilderEditQuiz: EditQuestion events already registered' );
		}

		// Initialize TinyMCE for question answer editors
		this._initEditQuestionTinyMCE( elEditQuizWrap );
	}

	/**
	 * Initialize TinyMCE for question answer editors using EditQuestion's method
	 */
	_initEditQuestionTinyMCE( elEditQuizWrap ) {
		if ( ! this.editQuestion || typeof window.tinymce === 'undefined' ) {
			return;
		}

		const elTextareas = elEditQuizWrap.querySelectorAll(
			'.lp-question-edit-main .lp-editor-tinymce'
		);

		elTextareas.forEach( ( elTextarea ) => {
			const id = elTextarea.id;
			if ( id ) {
				try {
					this.editQuestion.reInitTinymce( id );
				} catch ( e ) {
					console.warn( 'TinyMCE init error for', id, e );
				}
			}
		} );
	}

	/**
	 * Get Quiz ID from various sources
	 */
	_getQuizID( elEditQuizWrap ) {
		// Try from lp-target
		const elLPTarget = elEditQuizWrap.closest( BuilderEditQuiz.selectors.LPTarget );
		if ( elLPTarget && window.lpAJAXG ) {
			const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
			this.quizID = dataSend?.args?.quiz_id || 0;
		}

		// Try from popup data attribute
		if ( ! this.quizID ) {
			const popup = elEditQuizWrap.closest( '.lp-builder-popup' );
			if ( popup ) {
				this.quizID = popup.dataset.quizId || 0;
			}
		}

		// Try from wrapper data
		if ( ! this.quizID ) {
			const wrapper = elEditQuizWrap.closest( '[data-quiz-id]' );
			if ( wrapper ) {
				this.quizID = wrapper.dataset.quizId || 0;
			}
		}
	}

	/**
	 * Initialize answer sortables without blocking
	 */
	_initAnswerSortables( elEditQuizWrap ) {
		const elQuestionEditMains = elEditQuizWrap.querySelectorAll(
			BuilderEditQuiz.selectors.elQuestionEditMain
		);

		elQuestionEditMains.forEach( ( elQuestionEditMain ) => {
			this._sortAbleQuestionAnswer( elQuestionEditMain );
		} );
	}

	/**
	 * Sortable for question answers (popup-specific, simpler version)
	 */
	_sortAbleQuestionAnswer( elQuestionEditMain ) {
		const elAnswersConfig = elQuestionEditMain.querySelector(
			BuilderEditQuiz.selectors.elAnswersConfig
		);

		if ( ! elAnswersConfig ) {
			return;
		}

		const instance = new Sortable( elAnswersConfig, {
			handle: '.drag',
			animation: 150,
			onEnd: ( evt ) => {
				// Trigger change event for auto-save
				const elAutoSaveAnswer = evt.item.querySelector( '.lp-auto-save-question-answer' );
				if ( elAutoSaveAnswer ) {
					elAutoSaveAnswer.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				}
			},
		} );

		this.sortableAnswerInstances.push( instance );
	}

	/**
	 * Initialize TinyMCE asynchronously to avoid blocking
	 */
	_initTinyMCEAsync( elEditQuizWrap ) {
		const elTextareas = elEditQuizWrap.querySelectorAll(
			'.lp-question-edit-main .lp-editor-tinymce'
		);

		if ( elTextareas.length === 0 ) {
			return;
		}

		// Process editors in chunks to avoid blocking
		const textareaArray = Array.from( elTextareas );
		let index = 0;
		const chunkSize = 2; // Process 2 editors at a time

		const processChunk = () => {
			const chunk = textareaArray.slice( index, index + chunkSize );

			chunk.forEach( ( elTextarea ) => {
				const id = elTextarea.id;
				if ( id ) {
					this._reInitTinymce( id );
				}
			} );

			index += chunkSize;

			if ( index < textareaArray.length ) {
				// Use requestIdleCallback if available, otherwise setTimeout
				if ( window.requestIdleCallback ) {
					window.requestIdleCallback( processChunk, { timeout: 100 } );
				} else {
					setTimeout( processChunk, 50 );
				}
			}
		};

		// Start processing after a short delay to let UI render first
		if ( window.requestIdleCallback ) {
			window.requestIdleCallback( processChunk, { timeout: 100 } );
		} else {
			setTimeout( processChunk, 50 );
		}
	}

	/**
	 * Reinitialize a single TinyMCE editor
	 */
	_reInitTinymce( id ) {
		if ( ! window.tinymce ) {
			return;
		}

		const elTextarea = document.getElementById( id );
		if ( ! elTextarea ) {
			return;
		}

		// Verify it's inside question edit context
		const elQuestionEditMain = elTextarea.closest( '.lp-question-edit-main' );
		if ( ! elQuestionEditMain ) {
			return;
		}

		try {
			window.tinymce.execCommand( 'mceRemoveEditor', true, id );
			window.tinymce.execCommand( 'mceAddEditor', true, id );

			// Active tab visual
			const wrapEditor = document.querySelector( `#wp-${ id }-wrap` );
			if ( wrapEditor ) {
				wrapEditor.classList.add( 'tmce-active' );
				wrapEditor.classList.remove( 'html-active' );
			}
		} catch ( e ) {
			console.warn( 'TinyMCE init error for', id, e );
		}
	}

	/**
	 * Re-initialize when new questions are added
	 */
	reinitQuestionHandlers( elQuestionEditMain ) {
		if ( elQuestionEditMain ) {
			this._sortAbleQuestionAnswer( elQuestionEditMain );

			// Init TinyMCE for new question using EditQuestion's method
			if ( this.editQuestion ) {
				const elTextareas = elQuestionEditMain.querySelectorAll( '.lp-editor-tinymce' );
				elTextareas.forEach( ( elTextarea ) => {
					if ( elTextarea.id ) {
						try {
							this.editQuestion.reInitTinymce( elTextarea.id );
						} catch ( e ) {
							console.warn( 'TinyMCE init error for', elTextarea.id, e );
						}
					}
				} );

				// Init sortable for answers
				this.editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
			} else {
				// Fallback to internal method
				const elTextareas = elQuestionEditMain.querySelectorAll( '.lp-editor-tinymce' );
				elTextareas.forEach( ( elTextarea ) => {
					if ( elTextarea.id ) {
						this._reInitTinymce( elTextarea.id );
					}
				} );
			}
		}
	}

	events() {
		if ( BuilderEditQuiz._loadedEvents ) {
			return;
		}
		BuilderEditQuiz._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderEditQuiz.selectors.elBtnUpdateQuiz,
				class: this,
				callBack: this.updateQuiz.name,
			},
			{
				selector: BuilderEditQuiz.selectors.elBtnTrashQuiz,
				class: this,
				callBack: this.trashQuiz.name,
			},
			{
				selector: BuilderEditQuiz.selectors.elQuestionToggleAll,
				class: this,
				callBack: this.toggleQuestionAll.name,
			},
			{
				selector: BuilderEditQuiz.selectors.elBtnAddQuestion,
				class: this,
				callBack: this.addQuestion.name,
			},
			{
				selector: BuilderEditQuiz.selectors.elBtnRemoveQuestion,
				class: this,
				callBack: this.removeQuestion.name,
			},
			{
				selector: BuilderEditQuiz.selectors.elBtnUpdateQuestionTitle,
				class: this,
				callBack: this.updateQuestionTitle.name,
			},
			{
				selector: BuilderEditQuiz.selectors.elBtnCancelUpdateQuestionTitle,
				class: this,
				callBack: this.cancelChangeTitleQuestion.name,
			},
		] );

		// Keydown
		lpUtils.eventHandlers( 'keydown', [
			{
				selector: BuilderEditQuiz.selectors.elQuestionTitleInput,
				class: this,
				callBack: this.updateQuestionTitle.name,
				checkIsEventEnter: true,
			},
			{
				selector: BuilderEditQuiz.selectors.elQuestionTitleNewInput,
				class: this,
				callBack: this.addQuestion.name,
				checkIsEventEnter: true,
			},
		] );

		// Keyup
		lpUtils.eventHandlers( 'keyup', [
			{
				selector: BuilderEditQuiz.selectors.elQuestionTitleInput,
				class: this,
				callBack: this.changeTitleQuestion.name,
			},
			{
				selector: `${ BuilderEditQuiz.selectors.elQuestionTitleNewInput }, ${ BuilderEditQuiz.selectors.elQuestionTypeNew }`,
				class: this,
				callBack: this.checkCanAddQuestion.name,
			},
		] );

		// Change
		lpUtils.eventHandlers( 'change', [
			{
				selector: BuilderEditQuiz.selectors.elQuestionTypeNew,
				class: this,
				callBack: this.checkCanAddQuestion.name,
			},
		] );

		// Toggle collapse
		document.addEventListener( 'click', ( e ) => {
			const target = e.target;
			lpUtils.toggleCollapse( e, target, BuilderEditQuiz.selectors.elQuestionToggle, [], () => this.checkAllQuestionsCollapsed() );
		} );
	}

	// Toggle all questions
	toggleQuestionAll( args ) {
		const { target } = args;
		const elQuestionToggleAll = target.closest( BuilderEditQuiz.selectors.elQuestionToggleAll );
		if ( ! elQuestionToggleAll || ! this.elEditQuizWrap ) {
			return;
		}

		const elQuestionItems = this.elEditQuizWrap.querySelectorAll(
			`${ BuilderEditQuiz.selectors.elQuestionItem }:not(.clone)`
		);

		elQuestionToggleAll.classList.toggle( BuilderEditQuiz.selectors.elCollapse );

		elQuestionItems.forEach( ( el ) => {
			const shouldCollapse = elQuestionToggleAll.classList.contains( BuilderEditQuiz.selectors.elCollapse );
			el.classList.toggle( BuilderEditQuiz.selectors.elCollapse, shouldCollapse );
		} );
	}

	checkAllQuestionsCollapsed() {
		if ( ! this.elEditQuizWrap ) {
			return;
		}

		const elQuestionItems = this.elEditQuizWrap.querySelectorAll(
			`${ BuilderEditQuiz.selectors.elQuestionItem }:not(.clone)`
		);
		const elQuestionToggleAll = this.elEditQuizWrap.querySelector(
			BuilderEditQuiz.selectors.elQuestionToggleAll
		);

		if ( ! elQuestionToggleAll ) {
			return;
		}

		let isAllExpand = true;
		elQuestionItems.forEach( ( el ) => {
			if ( el.classList.contains( BuilderEditQuiz.selectors.elCollapse ) ) {
				isAllExpand = false;
			}
		} );

		if ( isAllExpand ) {
			elQuestionToggleAll.classList.remove( BuilderEditQuiz.selectors.elCollapse );
		} else {
			elQuestionToggleAll.classList.add( BuilderEditQuiz.selectors.elCollapse );
		}
	}

	updateCountItems() {
		if ( ! this.elEditQuizWrap ) {
			return;
		}

		const elCountItemsAll = this.elEditQuizWrap.querySelector( '.total-items' );
		const elItemsAll = this.elEditQuizWrap.querySelectorAll(
			`${ BuilderEditQuiz.selectors.elQuestionItem }:not(.clone)`
		);
		const itemsAllCount = elItemsAll.length;

		if ( elCountItemsAll ) {
			elCountItemsAll.dataset.count = itemsAllCount;
			const countEl = elCountItemsAll.querySelector( '.count' );
			if ( countEl ) {
				countEl.textContent = itemsAllCount;
			}
		}
	}

	// Add question to quiz
	addQuestion( args ) {
		const { e, target, callBackNest } = args;
		e.preventDefault();

		const elAddNewQuestion = target.closest( `.${ BuilderEditQuiz.selectors.elAddNewQuestion }` );
		if ( ! elAddNewQuestion || ! this.elEditListQuestions ) {
			return;
		}

		const elQuestionTitleNewInput = elAddNewQuestion.querySelector(
			BuilderEditQuiz.selectors.elQuestionTitleNewInput
		);
		const questionTitle = elQuestionTitleNewInput.value.trim();
		if ( ! questionTitle ) {
			lpToastify.show( elQuestionTitleNewInput.dataset.messEmptyTitle, 'error' );
			return;
		}

		const elQuestionType = elAddNewQuestion.querySelector( BuilderEditQuiz.selectors.elQuestionTypeNew );
		const questionType = elQuestionType.value;
		if ( ! questionType ) {
			lpToastify.show( elQuestionType.dataset.messEmptyType, 'error' );
			return;
		}

		const elQuestionClone = this.elEditListQuestions.querySelector(
			`${ BuilderEditQuiz.selectors.elQuestionItem }.clone`
		);
		const newQuestionItem = elQuestionClone.cloneNode( true );
		const elQuestionTitleInput = newQuestionItem.querySelector(
			BuilderEditQuiz.selectors.elQuestionTitleInput
		);

		elQuestionTitleInput.value = questionTitle;
		elQuestionTitleNewInput.value = '';
		newQuestionItem.classList.remove( 'clone' );
		lpUtils.lpShowHideEl( newQuestionItem, 1 );
		elQuestionClone.insertAdjacentElement( 'beforebegin', newQuestionItem );
		lpUtils.lpSetLoadingEl( newQuestionItem, 1 );

		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;
				const { question, html_edit_question } = data;

				if ( status === 'error' ) {
					throw `Error: ${ message }`;
				} else if ( status === 'success' ) {
					newQuestionItem.dataset.questionId = question.ID;
					newQuestionItem.dataset.questionType = question.meta_data._lp_type;
					newQuestionItem.outerHTML = html_edit_question;

					const elQuestionItemCreated = this.elEditListQuestions.querySelector(
						`${ BuilderEditQuiz.selectors.elQuestionItem }[data-question-id="${ question.ID }"]`
					);
					elQuestionItemCreated.classList.remove( BuilderEditQuiz.selectors.elCollapse );
					this.updateCountItems();

					// Reinit handlers for new question
					const elQuestionEditMain = elQuestionItemCreated.querySelector(
						BuilderEditQuiz.selectors.elQuestionEditMain
					);
					this.reinitQuestionHandlers( elQuestionEditMain );

					if ( callBackNest && typeof callBackNest.success === 'function' ) {
						callBackNest.success( { response, elQuestionItemCreated } );
					}
				}

				lpToastify.show( message, status );
			},
			error: ( error ) => {
				newQuestionItem.remove();
				lpToastify.show( error, 'error' );

				if ( callBackNest && typeof callBackNest.error === 'function' ) {
					callBackNest.error( { error, newQuestionItem } );
				}
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( newQuestionItem, 0 );
				this.checkCanAddQuestion( { e, target: elQuestionTitleNewInput } );

				if ( callBackNest && typeof callBackNest.completed === 'function' ) {
					callBackNest.completed( { newQuestionItem } );
				}
			},
		};

		let dataSend = JSON.parse( elQuestionTitleNewInput.dataset.send );
		dataSend = {
			...dataSend,
			question_title: questionTitle,
			question_type: questionType,
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	checkCanAddQuestion( args ) {
		const { target } = args;
		const elTrigger = target.closest( BuilderEditQuiz.selectors.elQuestionTitleNewInput ) ||
			target.closest( BuilderEditQuiz.selectors.elQuestionTypeNew );
		if ( ! elTrigger ) {
			return;
		}

		const elAddNewQuestion = elTrigger.closest( `.${ BuilderEditQuiz.selectors.elAddNewQuestion }` );
		if ( ! elAddNewQuestion ) {
			return;
		}

		const elBtnAddQuestion = elAddNewQuestion.querySelector( BuilderEditQuiz.selectors.elBtnAddQuestion );
		if ( ! elBtnAddQuestion ) {
			return;
		}

		const elQuestionTitleInput = elAddNewQuestion.querySelector(
			BuilderEditQuiz.selectors.elQuestionTitleNewInput
		);
		const elQuestionTypeNew = elAddNewQuestion.querySelector(
			BuilderEditQuiz.selectors.elQuestionTypeNew
		);

		const questionTitle = elQuestionTitleInput.value.trim();
		const questionType = elQuestionTypeNew.value;

		if ( questionTitle && questionType ) {
			elBtnAddQuestion.classList.add( 'active' );
		} else {
			elBtnAddQuestion.classList.remove( 'active' );
		}
	}

	removeQuestion( args ) {
		const { target } = args;
		const elBtnRemoveQuestion = target.closest( BuilderEditQuiz.selectors.elBtnRemoveQuestion );
		if ( ! elBtnRemoveQuestion ) {
			return;
		}

		const elQuestionItem = elBtnRemoveQuestion.closest( BuilderEditQuiz.selectors.elQuestionItem );
		if ( ! elQuestionItem ) {
			return;
		}

		const questionId = elQuestionItem.dataset.questionId;
		const i18n = window.lpDataAdmin?.i18n || window.lpData?.i18n || { cancel: 'Cancel', yes: 'Yes' };

		SweetAlert.fire( {
			title: elBtnRemoveQuestion.dataset.title,
			text: elBtnRemoveQuestion.dataset.content,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: i18n.cancel,
			confirmButtonText: i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						lpToastify.show( message, status );

						if ( status === 'success' ) {
							elQuestionItem.remove();
							this.updateCountItems();
						}
					},
					error: ( error ) => {
						lpToastify.show( error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
					},
				};

				const dataSend = {
					quiz_id: this.quizID,
					action: 'remove_question_from_quiz',
					question_id: questionId,
					args: { id_url: 'edit-quiz-questions' },
				};
				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}
		} );
	}

	updateQuestionTitle( args ) {
		const { e, target } = args;
		let canHandle = false;

		if ( target.closest( BuilderEditQuiz.selectors.elBtnUpdateQuestionTitle ) ) {
			canHandle = true;
		} else if ( target.closest( BuilderEditQuiz.selectors.elQuestionTitleInput ) && e.key === 'Enter' ) {
			canHandle = true;
		}

		if ( ! canHandle ) {
			return;
		}

		e.preventDefault();

		const elQuestionItem = target.closest( BuilderEditQuiz.selectors.elQuestionItem );
		if ( ! elQuestionItem ) {
			return;
		}

		const elQuestionTitleInput = elQuestionItem.querySelector(
			BuilderEditQuiz.selectors.elQuestionTitleInput
		);
		if ( ! elQuestionTitleInput ) {
			return;
		}

		const questionId = elQuestionItem.dataset.questionId;
		const questionTitleValue = elQuestionTitleInput.value.trim();
		const titleOld = elQuestionTitleInput.dataset.old;
		const message = elQuestionTitleInput.dataset.messEmptyTitle;

		if ( questionTitleValue.length === 0 ) {
			lpToastify.show( message, 'error' );
			return;
		}

		if ( questionTitleValue === titleOld ) {
			return;
		}

		elQuestionTitleInput.blur();
		lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

		const callBack = {
			success: ( response ) => {
				const { message, status } = response;

				if ( status === 'success' ) {
					elQuestionTitleInput.dataset.old = questionTitleValue;
				} else {
					elQuestionTitleInput.value = titleOld;
				}

				lpToastify.show( message, status );
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
				elQuestionItem.classList.remove( 'editing' );
			},
		};

		const dataSend = {
			quiz_id: this.quizID,
			action: 'update_question',
			question_id: questionId,
			question_title: questionTitleValue,
			args: { id_url: 'edit-quiz-questions' },
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	changeTitleQuestion( args ) {
		const { target } = args;
		const elQuestionTitleInput = target.closest( BuilderEditQuiz.selectors.elQuestionTitleInput );
		if ( ! elQuestionTitleInput ) {
			return;
		}

		const elQuestionItem = elQuestionTitleInput.closest( BuilderEditQuiz.selectors.elQuestionItem );
		const titleValue = elQuestionTitleInput.value.trim();
		const titleValueOld = elQuestionTitleInput.dataset.old || '';

		if ( titleValue === titleValueOld ) {
			elQuestionItem.classList.remove( 'editing' );
		} else {
			elQuestionItem.classList.add( 'editing' );
		}
	}

	cancelChangeTitleQuestion( args ) {
		const { target } = args;
		const elBtnCancelUpdateQuestionTitle = target.closest(
			BuilderEditQuiz.selectors.elBtnCancelUpdateQuestionTitle
		);
		if ( ! elBtnCancelUpdateQuestionTitle ) {
			return;
		}

		const elQuestionItem = elBtnCancelUpdateQuestionTitle.closest(
			BuilderEditQuiz.selectors.elQuestionItem
		);
		const elQuestionTitleInput = elQuestionItem.querySelector(
			BuilderEditQuiz.selectors.elQuestionTitleInput
		);
		elQuestionTitleInput.value = elQuestionTitleInput.dataset.old || '';
		elQuestionItem.classList.remove( 'editing' );
	}

	sortAbleQuestion() {
		if ( ! this.elEditListQuestions ) {
			return;
		}

		let isUpdateSectionPosition = 0;
		let timeout;

		this.sortableInstance = new Sortable( this.elEditListQuestions, {
			handle: '.drag',
			animation: 150,
			onEnd: ( evt ) => {
				const elQuestionItem = evt.item;
				if ( ! isUpdateSectionPosition ) {
					return;
				}

				clearTimeout( timeout );
				timeout = setTimeout( () => {
					lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

					const questionIds = [];
					const elQuestionItems = this.elEditListQuestions.querySelectorAll(
						`${ BuilderEditQuiz.selectors.elQuestionItem }:not(.clone)`
					);
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
								lpToastify.show( message, status );
							} else {
								throw `Error: ${ message }`;
							}
						},
						error: ( error ) => {
							lpToastify.show( error, 'error' );
						},
						completed: () => {
							lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
							isUpdateSectionPosition = 0;
						},
					};

					const dataSend = {
						quiz_id: this.quizID,
						action: 'update_questions_position',
						question_ids: questionIds,
						args: { id_url: 'edit-quiz-questions' },
					};
					window.lpAJAXG.fetchAJAX( dataSend, callBack );
				}, 1000 );
			},
			onMove: () => {
				clearTimeout( timeout );
			},
			onUpdate: () => {
				isUpdateSectionPosition = 1;
			},
		} );
	}

	getQuizDataForUpdate() {
		const data = {};
		const wrapperEl = document.querySelector( BuilderEditQuiz.selectors.elDataQuiz );
		data.quiz_id = wrapperEl ? parseInt( wrapperEl.dataset.quizId ) || 0 : 0;

		const titleInput = document.getElementById( BuilderEditQuiz.selectors.idTitle );
		data.quiz_title = titleInput ? titleInput.value : '';

		const descEditor = document.getElementById( BuilderEditQuiz.selectors.idDescEditor );
		data.quiz_description = descEditor ? descEditor.value : '';

		if ( typeof tinymce !== 'undefined' ) {
			const editor = tinymce.get( BuilderEditQuiz.selectors.idDescEditor );
			if ( editor ) {
				data.quiz_description = editor.getContent();
			}
		}

		const elFormSetting = document.querySelector( BuilderEditQuiz.selectors.elFormSetting );

		if ( elFormSetting ) {
			data.quiz_settings = true;
			const formElements = elFormSetting.querySelectorAll( 'input, select, textarea' );

			formElements.forEach( ( element ) => {
				const name = element.name || element.id;

				if ( ! name || name === 'learnpress_meta_box_nonce' || name === '_wp_http_referer' ) {
					return;
				}

				if ( element.type === 'checkbox' ) {
					const fieldName = name.replace( '[]', '' );
					if ( ! data.hasOwnProperty( fieldName ) ) {
						data[ fieldName ] = element.checked ? 'yes' : 'no';
					}
				} else if ( element.type === 'radio' ) {
					if ( element.checked ) {
						const fieldName = name.replace( '[]', '' );
						data[ fieldName ] = element.value;
					}
				} else if ( element.type === 'file' ) {
					const fieldName = name.replace( '[]', '' );
					if ( element.files && element.files.length > 0 ) {
						data[ fieldName ] = element.files;
					}
				} else {
					const fieldName = name.replace( '[]', '' );

					if ( name.endsWith( '[]' ) ) {
						if ( ! data.hasOwnProperty( fieldName ) ) {
							data[ fieldName ] = [];
						}

						if ( Array.isArray( data[ fieldName ] ) ) {
							data[ fieldName ].push( element.value );
						}
					} else {
						if ( ! data.hasOwnProperty( fieldName ) ) {
							data[ fieldName ] = element.value;
						}
					}
				}
			} );

			Object.keys( data ).forEach( ( key ) => {
				if ( Array.isArray( data[ key ] ) ) {
					data[ key ] = data[ key ].join( ',' );
				}
			} );
		}

		return data;
	}

	updateQuiz( args ) {
		const { target } = args;
		const elBtnUpdateQuiz = target.closest( BuilderEditQuiz.selectors.elBtnUpdateQuiz );

		if ( ! elBtnUpdateQuiz ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnUpdateQuiz, 1 );

		const quizData = this.getQuizDataForUpdate();

		const dataSend = {
			...quizData,
			action: 'builder_update_quiz',
			args: {
				id_url: 'builder-update-quiz',
			},
			quiz_status: 'publish',
		};

		if ( typeof lpQuizBuilder !== 'undefined' && lpQuizBuilder.nonce ) {
			dataSend.nonce = lpQuizBuilder.nonce;
		}

		if ( quizData.quiz_categories && quizData.quiz_categories.length > 0 ) {
			dataSend.quiz_categories = quizData.quiz_categories.join( ',' );
		}

		if ( quizData.quiz_tags && quizData.quiz_tags.length > 0 ) {
			dataSend.quiz_tags = quizData.quiz_tags.join( ',' );
		}

		if ( quizData.quiz_thumbnail_id ) {
			dataSend.quiz_thumbnail_id = quizData.quiz_thumbnail_id;
		}

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.button_title ) {
					elBtnUpdateQuiz.textContent = data.button_title;
				}

				if ( data?.quiz_id_new ) {
					const currentUrl = window.location.href;
					window.location.href = currentUrl.replace( /post-new\/?/, `${ data.quiz_id_new }/` );
				}

				if ( data?.status ) {
					const elStatus = document.querySelector( BuilderEditQuiz.selectors.elQuizStatus );
					if ( elStatus ) {
						elStatus.className = 'quiz-status ' + data.status;
						elStatus.textContent = data.status;
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnUpdateQuiz, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	trashQuiz( args ) {
		const { target } = args;
		const elBtnTrashQuiz = target.closest( BuilderEditQuiz.selectors.elBtnTrashQuiz );
		if ( ! elBtnTrashQuiz ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnTrashQuiz, 1 );

		const quizData = this.getQuizDataForUpdate();
		const dataSend = {
			action: 'move_trash_quiz',
			args: {
				id_url: 'move-trash-quiz',
			},
			quiz_id: quizData.quiz_id || 0,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.button_title ) {
					const elBtnUpdateQuiz = document.querySelector(
						BuilderEditQuiz.selectors.elBtnUpdateQuiz
					);
					if ( elBtnUpdateQuiz ) {
						elBtnUpdateQuiz.textContent = data.button_title;
					}
				}

				if ( data?.status ) {
					const elStatus = document.querySelector( BuilderEditQuiz.selectors.elQuizStatus );
					if ( elStatus ) {
						elStatus.className = 'quiz-status ' + data.status;
						elStatus.textContent = data.status;
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnTrashQuiz, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
