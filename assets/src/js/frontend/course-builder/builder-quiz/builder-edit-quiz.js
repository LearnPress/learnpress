/**
 * Builder Edit Quiz Handler
 *
 * @since 4.3.0
 * @version 1.0.0
 */

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
		this.editQuestion = null;
		this.initPromise = null;
		this.isInitialized = false;
	}

	static selectors = {
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
		this.initQuizQuestionsTab( container );
		this.events();
	}

	/**
	 * Reinitialize for new quiz context
	 */
	reinit( container = null ) {
		this.cleanup();
		this.events();
		
		// Use async init with proper error handling
		this.initQuizQuestionsTabAsync( container ).catch( ( error ) => {
			// Silently handle error - element might not exist yet
			console.debug( 'BuilderEditQuiz: Quiz questions tab not found', error.message );
		} );
	}

	/**
	 * Cleanup all instances and state
	 */
	cleanup() {
		// Cancel pending promise if exists
		if ( this.initPromise && typeof this.initPromise === 'object' ) {
			this.initPromise.cancelled = true;
		}
		this.initPromise = null;

		this.elEditQuizWrap = null;
		this.elEditListQuestions = null;
		this.quizID = null;
		this.isInitialized = false;

		// Destroy sortable instances
		if ( this.sortableInstance?.destroy ) {
			try {
				this.sortableInstance.destroy();
			} catch ( e ) {
				console.warn( 'Error destroying sortable instance:', e );
			}
			this.sortableInstance = null;
		}

		// Destroy answer sortable instances
		this.sortableAnswerInstances.forEach( ( instance ) => {
			if ( instance?.destroy ) {
				try {
					instance.destroy();
				} catch ( e ) {
					console.warn( 'Error destroying answer sortable:', e );
				}
			}
		} );
		this.sortableAnswerInstances = [];
	}

	/**
	 * Initialize Quiz Questions Tab
	 */
	initQuizQuestionsTab( container = null ) {
		const searchContainer = container || document;
		const elEditQuizWrap = searchContainer.querySelector( BuilderEditQuiz.selectors.elEditQuizWrap );

		if ( elEditQuizWrap ) {
			this._initQuizQuestionsTabElement( elEditQuizWrap );
		}
	}

	/**
	 * Initialize Quiz Questions Tab asynchronously
	 */
	initQuizQuestionsTabAsync( container = null, maxAttempts = 50, interval = 200 ) {
		// Cancel previous promise if exists
		if ( this.initPromise && typeof this.initPromise === 'object' ) {
			this.initPromise.cancelled = true;
		}

		// Create new promise
		this.initPromise = new Promise( ( resolve, reject ) => {
			let attempts = 0;
			const searchContainer = container || document;

			const checkElement = () => {
				// Check if cancelled
				if ( this.initPromise && this.initPromise.cancelled ) {
					reject( new Error( 'Init cancelled' ) );
					return;
				}

				attempts++;
				const elEditQuizWrap = searchContainer.querySelector( BuilderEditQuiz.selectors.elEditQuizWrap );

				if ( elEditQuizWrap ) {
					this._initQuizQuestionsTabElement( elEditQuizWrap );
					resolve( elEditQuizWrap );
				} else if ( attempts >= maxAttempts ) {
					reject( new Error( `Quiz questions tab not found after ${ maxAttempts } attempts` ) );
				} else {
					setTimeout( checkElement, interval );
				}
			};

			checkElement();
		} );

		// Add cancelled flag to promise object
		this.initPromise.cancelled = false;

		return this.initPromise;
	}

	/**
	 * Initialize quiz questions tab element
	 */
	_initQuizQuestionsTabElement( elEditQuizWrap ) {
		if ( ! elEditQuizWrap ) {
			return;
		}

		// Prevent double initialization
		if ( this.isInitialized && this.elEditQuizWrap === elEditQuizWrap ) {
			return;
		}

		this.elEditQuizWrap = elEditQuizWrap;
		this.elEditListQuestions = elEditQuizWrap.querySelector( BuilderEditQuiz.selectors.elEditListQuestions );

		this._getQuizID( elEditQuizWrap );

		// Initialize popup select items
		if ( ! this.lpPopupSelectItemToAdd ) {
			this.lpPopupSelectItemToAdd = new LpPopupSelectItemToAdd();
			this.lpPopupSelectItemToAdd.init();
		}

		// Init sortables
		this.sortAbleQuestion();
		this._initAnswerSortables( elEditQuizWrap );

		// Init EditQuestion
		this._initEditQuestion( elEditQuizWrap );

		// Init TinyMCE asynchronously
		this._initTinyMCEAsync( elEditQuizWrap );

		this.isInitialized = true;
	}

	/**
	 * Get Quiz ID from various sources
	 */
	_getQuizID( elEditQuizWrap ) {
		// Try from lp-target
		const elLPTarget = elEditQuizWrap.closest( BuilderEditQuiz.selectors.LPTarget );
		if ( elLPTarget && window.lpAJAXG ) {
			try {
				const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
				this.quizID = dataSend?.args?.quiz_id || 0;
			} catch ( e ) {
				console.warn( 'Error getting quiz ID from lpAJAXG:', e );
			}
		}

		// Try from popup
		if ( ! this.quizID ) {
			const popup = elEditQuizWrap.closest( '.lp-builder-popup' );
			this.quizID = popup?.dataset.quizId || 0;
		}

		// Try from wrapper
		if ( ! this.quizID ) {
			const wrapper = elEditQuizWrap.closest( '[data-quiz-id]' );
			this.quizID = wrapper?.dataset.quizId || 0;
		}
	}

	/**
	 * Initialize EditQuestion for answer management
	 */
	_initEditQuestion( elEditQuizWrap ) {
		if ( ! elEditQuizWrap ) {
			return;
		}

		// Create EditQuestion instance if not exists
		if ( ! this.editQuestion ) {
			this.editQuestion = new EditQuestion();
		}

		// Initialize sortable for answers
		const elQuestionEditMains = elEditQuizWrap.querySelectorAll( BuilderEditQuiz.selectors.elQuestionEditMain );
		elQuestionEditMains.forEach( ( elQuestionEditMain ) => {
			if ( elQuestionEditMain && this.editQuestion?.sortAbleQuestionAnswer ) {
				try {
					this.editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
				} catch ( e ) {
					console.warn( 'Error initializing answer sortable:', e );
				}
			}
		} );

		// Register events if not loaded
		if ( ! EditQuestion._loadedEvents && this.editQuestion?.events ) {
			try {
				this.editQuestion.events();
			} catch ( e ) {
				console.warn( 'Error registering EditQuestion events:', e );
			}
		}

		// Init TinyMCE for question editors
		this._initEditQuestionTinyMCE( elEditQuizWrap );
	}

	/**
	 * Initialize TinyMCE for question editors
	 */
	_initEditQuestionTinyMCE( elEditQuizWrap ) {
		if ( ! this.editQuestion || ! elEditQuizWrap || typeof window.tinymce === 'undefined' ) {
			return;
		}

		const elTextareas = elEditQuizWrap.querySelectorAll( '.lp-question-edit-main .lp-editor-tinymce' );
		elTextareas.forEach( ( elTextarea ) => {
			if ( elTextarea?.id && this.editQuestion?.reInitTinymce ) {
				try {
					this.editQuestion.reInitTinymce( elTextarea.id );
				} catch ( e ) {
					console.warn( 'TinyMCE init error:', e );
				}
			}
		} );
	}

	/**
	 * Initialize answer sortables
	 */
	_initAnswerSortables( elEditQuizWrap ) {
		if ( ! elEditQuizWrap ) {
			return;
		}

		const elQuestionEditMains = elEditQuizWrap.querySelectorAll( BuilderEditQuiz.selectors.elQuestionEditMain );
		elQuestionEditMains.forEach( ( elQuestionEditMain ) => {
			if ( elQuestionEditMain ) {
				this._sortAbleQuestionAnswer( elQuestionEditMain );
			}
		} );
	}

	/**
	 * Make question answers sortable
	 */
	_sortAbleQuestionAnswer( elQuestionEditMain ) {
		if ( ! elQuestionEditMain ) {
			return;
		}

		const elAnswersConfig = elQuestionEditMain.querySelector( BuilderEditQuiz.selectors.elAnswersConfig );
		if ( ! elAnswersConfig ) {
			return;
		}

		try {
			const instance = new Sortable( elAnswersConfig, {
				handle: '.drag',
				animation: 150,
				onEnd: ( evt ) => {
					if ( ! evt?.item ) {
						return;
					}
					const elAutoSaveAnswer = evt.item.querySelector( '.lp-auto-save-question-answer' );
					if ( elAutoSaveAnswer ) {
						elAutoSaveAnswer.dispatchEvent( new Event( 'change', { bubbles: true } ) );
					}
				},
			} );
			this.sortableAnswerInstances.push( instance );
		} catch ( e ) {
			console.warn( 'Error creating answer sortable:', e );
		}
	}

	/**
	 * Initialize TinyMCE asynchronously
	 */
	_initTinyMCEAsync( elEditQuizWrap ) {
		if ( ! elEditQuizWrap ) {
			return;
		}

		const elTextareas = elEditQuizWrap.querySelectorAll( '.lp-question-edit-main .lp-editor-tinymce' );
		if ( elTextareas.length === 0 ) {
			return;
		}

		const textareaArray = Array.from( elTextareas );
		const chunkSize = 2;
		let index = 0;

		const processChunk = () => {
			const chunk = textareaArray.slice( index, index + chunkSize );
			chunk.forEach( ( elTextarea ) => {
				if ( elTextarea?.id ) {
					this._reInitTinymce( elTextarea.id );
				}
			} );

			index += chunkSize;
			if ( index < textareaArray.length ) {
				if ( window.requestIdleCallback ) {
					window.requestIdleCallback( processChunk, { timeout: 100 } );
				} else {
					setTimeout( processChunk, 50 );
				}
			}
		};

		if ( window.requestIdleCallback ) {
			window.requestIdleCallback( processChunk, { timeout: 100 } );
		} else {
			setTimeout( processChunk, 50 );
		}
	}

	/**
	 * Reinitialize single TinyMCE editor
	 */
	_reInitTinymce( id ) {
		if ( ! window.tinymce || ! id ) {
			return;
		}

		const elTextarea = document.getElementById( id );
		if ( ! elTextarea?.closest( '.lp-question-edit-main' ) ) {
			return;
		}

		try {
			window.tinymce.execCommand( 'mceRemoveEditor', true, id );
			window.tinymce.execCommand( 'mceAddEditor', true, id );

			const wrapEditor = document.querySelector( `#wp-${ id }-wrap` );
			if ( wrapEditor ) {
				wrapEditor.classList.add( 'tmce-active' );
				wrapEditor.classList.remove( 'html-active' );
			}
		} catch ( e ) {
			console.warn( 'TinyMCE init error:', e );
		}
	}

	/**
	 * Reinitialize handlers for new question
	 */
	reinitQuestionHandlers( elQuestionEditMain ) {
		if ( ! elQuestionEditMain ) {
			return;
		}

		// Init answer sortable
		this._sortAbleQuestionAnswer( elQuestionEditMain );

		// Init TinyMCE
		if ( this.editQuestion?.reInitTinymce ) {
			const elTextareas = elQuestionEditMain.querySelectorAll( '.lp-editor-tinymce' );
			elTextareas.forEach( ( elTextarea ) => {
				if ( elTextarea?.id ) {
					try {
						this.editQuestion.reInitTinymce( elTextarea.id );
					} catch ( e ) {
						console.warn( 'TinyMCE init error:', e );
					}
				}
			} );
		}

		// Init answer sortable via EditQuestion
		if ( this.editQuestion?.sortAbleQuestionAnswer ) {
			try {
				this.editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
			} catch ( e ) {
				console.warn( 'Error initializing answer sortable:', e );
			}
		}
	}

	events() {
		if ( BuilderEditQuiz._loadedEvents ) {
			return;
		}
		BuilderEditQuiz._loadedEvents = true;

		// Click events
		lpUtils.eventHandlers( 'click', [
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
			{
				selector: LpPopupSelectItemToAdd.selectors.elBtnShowPopupItemsToSelect,
				class: this,
				callBack: this.handleShowPopupQuestionBank.name,
			},
			{
				selector: LpPopupSelectItemToAdd.selectors.elBtnAddItemsSelected,
				class: this,
				callBack: this.handleAddItemsSelected.name,
			},
		] );

		// Keydown events
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

		// Keyup events
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

		// Change events
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

	/**
	 * Handle show popup question bank - track quiz context
	 */
	handleShowPopupQuestionBank( args ) {
		const { target } = args;
		// Only handle if button is inside Quiz popup context
		const elQuizWrap = target.closest( BuilderEditQuiz.selectors.elEditQuizWrap );
		const elBuilderPopup = target.closest( '.lp-builder-popup' );
		
		if ( elQuizWrap || elBuilderPopup ) {
			// Store reference that we're in quiz context
			BuilderEditQuiz._isQuizPopupContext = true;
			
			// Store quiz wrap reference for later use
			if ( elQuizWrap ) {
				this.elEditQuizWrap = elQuizWrap;
				this._getQuizID( elQuizWrap );
				this.elEditListQuestions = elQuizWrap.querySelector( BuilderEditQuiz.selectors.elEditListQuestions );
			}
		} else {
			BuilderEditQuiz._isQuizPopupContext = false;
		}
	}

	/**
	 * Handle add items selected from Question Bank popup
	 */
	handleAddItemsSelected( args ) {
		const { target } = args;
		
		// Only handle if we're in quiz context
		if ( ! BuilderEditQuiz._isQuizPopupContext ) {
			return;
		}

		// Get items selected from popup
		const elPopup = SweetAlert.getPopup();
		if ( ! elPopup ) {
			return;
		}

		// Get selected items from checkboxes
		const itemsSelected = [];
		const elListItems = elPopup.querySelector( '.list-items' );
		if ( elListItems ) {
			const elCheckedInputs = elListItems.querySelectorAll( 'input[type="checkbox"]:checked' );
			elCheckedInputs.forEach( ( elInput ) => {
				itemsSelected.push( { ...elInput.dataset } );
			} );
		}

		// Also check from list-items-selected (if user is viewing selected items)
		const elListItemsSelected = elPopup.querySelector( '.list-items-selected' );
		if ( elListItemsSelected ) {
			const elSelectedItems = elListItemsSelected.querySelectorAll( '.li-item-selected:not(.clone)' );
			elSelectedItems.forEach( ( elItem ) => {
				const itemData = { ...elItem.dataset };
				// Avoid duplicates
				if ( ! itemsSelected.some( ( item ) => item.id === itemData.id ) ) {
					itemsSelected.push( itemData );
				}
			} );
		}

		// If still no items, try to get from LpPopupSelectItemToAdd internal state
		if ( itemsSelected.length === 0 ) {
			// Get from data attribute on lp-target
			const elLPTarget = elPopup.querySelector( '.lp-target' );
			if ( elLPTarget && window.lpAJAXG ) {
				try {
					const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
					if ( dataSend?.args?.item_selecting && Array.isArray( dataSend.args.item_selecting ) ) {
						itemsSelected.push( ...dataSend.args.item_selecting );
					}
				} catch ( e ) {
					console.warn( 'Error getting item_selecting:', e );
				}
			}
		}

		if ( itemsSelected.length === 0 ) {
			console.warn( 'BuilderEditQuiz: No items selected' );
			return;
		}

		// Close popup and add questions
		SweetAlert.close();
		
		// Reset context flag
		BuilderEditQuiz._isQuizPopupContext = false;

		// Add questions to quiz
		this.addQuestionsSelectedToQuiz( itemsSelected );
	}

	/**
	 * Add questions selected from Question Bank popup to quiz
	 */
	addQuestionsSelectedToQuiz( itemsSelected ) {
		if ( ! itemsSelected || itemsSelected.length === 0 ) {
			console.warn( 'BuilderEditQuiz: No items to add' );
			return;
		}

		// Ensure elEditQuizWrap is available - try to find it
		if ( ! this.elEditQuizWrap ) {
			// Try to find from builder popup first
			const builderPopup = document.querySelector( '.lp-builder-popup' );
			if ( builderPopup ) {
				this.elEditQuizWrap = builderPopup.querySelector( BuilderEditQuiz.selectors.elEditQuizWrap );
			}
			
			// Fallback to document
			if ( ! this.elEditQuizWrap ) {
				this.elEditQuizWrap = document.querySelector( BuilderEditQuiz.selectors.elEditQuizWrap );
			}
		}

		if ( ! this.elEditQuizWrap ) {
			console.error( 'BuilderEditQuiz: elEditQuizWrap not found' );
			return;
		}

		// Ensure quizID is available
		if ( ! this.quizID ) {
			this._getQuizID( this.elEditQuizWrap );
		}

		if ( ! this.quizID ) {
			console.error( 'BuilderEditQuiz: quizID not found' );
			return;
		}

		// Ensure elEditListQuestions is available
		if ( ! this.elEditListQuestions ) {
			this.elEditListQuestions = this.elEditQuizWrap.querySelector( BuilderEditQuiz.selectors.elEditListQuestions );
		}

		if ( ! this.elEditListQuestions ) {
			console.error( 'BuilderEditQuiz: elEditListQuestions not found' );
			return;
		}

		const questionIds = [];
		const placeholderItems = [];

		// Create placeholder items
		itemsSelected.forEach( ( item ) => {
			const elQuestionItemClone = this.elEditQuizWrap.querySelector(
				`${ BuilderEditQuiz.selectors.elQuestionItem }.clone`
			);

			if ( ! elQuestionItemClone ) {
				console.error( 'BuilderEditQuiz: Question clone element not found' );
				return;
			}

			questionIds.push( item.id );
			const elQuestionItemNew = elQuestionItemClone.cloneNode( true );
			const elQuestionItemTitleInput = elQuestionItemNew.querySelector(
				BuilderEditQuiz.selectors.elQuestionTitleInput
			);

			elQuestionItemNew.classList.remove( 'clone' );
			elQuestionItemNew.dataset.questionId = item.id;

			// Use title from dataset
			const questionTitle = item.title || '';
			if ( elQuestionItemTitleInput ) {
				elQuestionItemTitleInput.value = questionTitle;
			}

			lpUtils.lpSetLoadingEl( elQuestionItemNew, 1 );
			lpUtils.lpShowHideEl( elQuestionItemNew, 1 );
			elQuestionItemClone.insertAdjacentElement( 'beforebegin', elQuestionItemNew );

			placeholderItems.push( elQuestionItemNew );
		} );

		if ( questionIds.length === 0 ) {
			console.warn( 'BuilderEditQuiz: No questions to add' );
			return;
		}

		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				if ( status !== 'success' ) {
					throw new Error( message || 'Failed to add questions' );
				}

				lpToastify.show( message, status );

				const { html_edit_question } = data;

				if ( ! html_edit_question || typeof html_edit_question !== 'object' ) {
					throw new Error( 'Invalid response: missing html_edit_question' );
				}

				// Replace placeholder items with actual HTML
				Object.entries( html_edit_question ).forEach( ( [ question_id, item_html ] ) => {
					if ( ! item_html ) {
						console.warn( `Empty HTML for question ${ question_id }` );
						return;
					}

					const elQuestionItemPlaceholder = this.elEditQuizWrap.querySelector(
						`${ BuilderEditQuiz.selectors.elQuestionItem }[data-question-id="${ question_id }"]`
					);

					if ( ! elQuestionItemPlaceholder ) {
						console.warn( `Placeholder not found for question ${ question_id }` );
						return;
					}

					// Replace with actual HTML
					elQuestionItemPlaceholder.outerHTML = item_html;

					// Get the newly created element after outerHTML replacement
					const elQuestionItemCreated = this.elEditQuizWrap.querySelector(
						`${ BuilderEditQuiz.selectors.elQuestionItem }[data-question-id="${ question_id }"]`
					);

					// Initialize handlers for new question
					if ( elQuestionItemCreated ) {
						const elQuestionEditMain = elQuestionItemCreated.querySelector(
							BuilderEditQuiz.selectors.elQuestionEditMain
						);
						this.reinitQuestionHandlers( elQuestionEditMain );
					}
				} );

				this.updateCountItems();
			},
			error: ( error ) => {
				console.error( 'Error adding questions:', error );

				// Remove placeholder items on error
				placeholderItems.forEach( ( elPlaceholder ) => {
					if ( elPlaceholder && elPlaceholder.parentNode ) {
						elPlaceholder.remove();
					}
				} );

				lpToastify.show( error?.message || error || 'Failed to add questions', 'error' );
			},
			completed: () => {
					// Remove loading state from all items (if still exist)
				questionIds.forEach( ( question_id ) => {
					const elQuestionItem = this.elEditQuizWrap.querySelector(
						`${ BuilderEditQuiz.selectors.elQuestionItem }[data-question-id="${ question_id }"]`
					);
					if ( elQuestionItem ) {
						lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
					}
				} );
			},
		};

		const dataSend = {
			action: 'add_questions_to_quiz',
			quiz_id: this.quizID,
			question_ids: questionIds,
			args: { id_url: 'edit-quiz-questions' },
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Toggle all questions
	 */
	toggleQuestionAll( args ) {
		const { target } = args;
		const elQuestionToggleAll = target.closest( BuilderEditQuiz.selectors.elQuestionToggleAll );
		if ( ! elQuestionToggleAll || ! this.elEditQuizWrap ) {
			return;
		}

		const elQuestionItems = this.elEditQuizWrap.querySelectorAll( `${ BuilderEditQuiz.selectors.elQuestionItem }:not(.clone)` );
		elQuestionToggleAll.classList.toggle( BuilderEditQuiz.selectors.elCollapse );

		const shouldCollapse = elQuestionToggleAll.classList.contains( BuilderEditQuiz.selectors.elCollapse );
		elQuestionItems.forEach( ( el ) => {
			if ( el ) {
				el.classList.toggle( BuilderEditQuiz.selectors.elCollapse, shouldCollapse );
			}
		} );
	}

	/**
	 * Check if all questions are collapsed
	 */
	checkAllQuestionsCollapsed() {
		if ( ! this.elEditQuizWrap ) {
			return;
		}

		const elQuestionItems = this.elEditQuizWrap.querySelectorAll( `${ BuilderEditQuiz.selectors.elQuestionItem }:not(.clone)` );
		const elQuestionToggleAll = this.elEditQuizWrap.querySelector( BuilderEditQuiz.selectors.elQuestionToggleAll );

		if ( ! elQuestionToggleAll ) {
			return;
		}

		const isAllExpand = Array.from( elQuestionItems ).every( ( el ) => el && ! el.classList.contains( BuilderEditQuiz.selectors.elCollapse ) );

		elQuestionToggleAll.classList.toggle( BuilderEditQuiz.selectors.elCollapse, ! isAllExpand );
	}

	/**
	 * Update question count
	 */
	updateCountItems() {
		if ( ! this.elEditQuizWrap ) {
			return;
		}

		const elCountItemsAll = this.elEditQuizWrap.querySelector( '.total-items' );
		const elItemsAll = this.elEditQuizWrap.querySelectorAll( `${ BuilderEditQuiz.selectors.elQuestionItem }:not(.clone)` );
		const itemsAllCount = elItemsAll.length;

		if ( elCountItemsAll ) {
			elCountItemsAll.dataset.count = itemsAllCount;
			const countEl = elCountItemsAll.querySelector( '.count' );
			if ( countEl ) {
				countEl.textContent = itemsAllCount;
			}
		}
	}

	/**
	 * Add question to quiz
	 */
	addQuestion( args ) {
		const { e, target, callBackNest } = args;
		e.preventDefault();

		const elAddNewQuestion = target.closest( `.${ BuilderEditQuiz.selectors.elAddNewQuestion }` );
		if ( ! elAddNewQuestion || ! this.elEditListQuestions ) {
			return;
		}

		const elQuestionTitleNewInput = elAddNewQuestion.querySelector( BuilderEditQuiz.selectors.elQuestionTitleNewInput );
		const questionTitle = elQuestionTitleNewInput?.value?.trim();
		if ( ! questionTitle ) {
			lpToastify.show( elQuestionTitleNewInput?.dataset?.messEmptyTitle || 'Title is required', 'error' );
			return;
		}

		const elQuestionType = elAddNewQuestion.querySelector( BuilderEditQuiz.selectors.elQuestionTypeNew );
		const questionType = elQuestionType?.value;
		if ( ! questionType ) {
			lpToastify.show( elQuestionType?.dataset?.messEmptyType || 'Type is required', 'error' );
			return;
		}

		const elQuestionClone = this.elEditListQuestions.querySelector( `${ BuilderEditQuiz.selectors.elQuestionItem }.clone` );
		if ( ! elQuestionClone ) {
			lpToastify.show( 'Question template not found', 'error' );
			return;
		}

		const newQuestionItem = elQuestionClone.cloneNode( true );
		const elQuestionTitleInput = newQuestionItem.querySelector( BuilderEditQuiz.selectors.elQuestionTitleInput );

		if ( elQuestionTitleInput ) {
			elQuestionTitleInput.value = questionTitle;
		}
		elQuestionTitleNewInput.value = '';
		newQuestionItem.classList.remove( 'clone' );
		lpUtils.lpShowHideEl( newQuestionItem, 1 );
		elQuestionClone.insertAdjacentElement( 'beforebegin', newQuestionItem );
		lpUtils.lpSetLoadingEl( newQuestionItem, 1 );

		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				if ( status === 'error' ) {
					throw new Error( message );
				}

				if ( status === 'success' && data?.question ) {
					const { question, html_edit_question } = data;
					newQuestionItem.dataset.questionId = question.ID;
					newQuestionItem.dataset.questionType = question.meta_data?._lp_type || '';
					newQuestionItem.outerHTML = html_edit_question;

					const elQuestionItemCreated = this.elEditListQuestions.querySelector(
						`${ BuilderEditQuiz.selectors.elQuestionItem }[data-question-id="${ question.ID }"]`
					);

					if ( elQuestionItemCreated ) {
						elQuestionItemCreated.classList.remove( BuilderEditQuiz.selectors.elCollapse );
						this.updateCountItems();

						const elQuestionEditMain = elQuestionItemCreated.querySelector( BuilderEditQuiz.selectors.elQuestionEditMain );
						this.reinitQuestionHandlers( elQuestionEditMain );

						if ( callBackNest?.success ) {
							callBackNest.success( { response, elQuestionItemCreated } );
						}
					}
				}

				lpToastify.show( message, status );
			},
			error: ( error ) => {
				newQuestionItem.remove();
				lpToastify.show( error?.message || error || 'Failed to add question', 'error' );

				if ( callBackNest?.error ) {
					callBackNest.error( { error, newQuestionItem } );
				}
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( newQuestionItem, 0 );
				this.checkCanAddQuestion( { e, target: elQuestionTitleNewInput } );

				if ( callBackNest?.completed ) {
					callBackNest.completed( { newQuestionItem } );
				}
			},
		};

		try {
			let dataSend = JSON.parse( elQuestionTitleNewInput.dataset.send || '{}' );
			dataSend = { ...dataSend, question_title: questionTitle, question_type: questionType };
			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		} catch ( e ) {
			console.error( 'Error adding question:', e );
			newQuestionItem.remove();
			lpToastify.show( 'Failed to add question', 'error' );
		}
	}

	/**
	 * Check if can add question
	 */
	checkCanAddQuestion( args ) {
		const { target } = args;
		const elTrigger = target?.closest( BuilderEditQuiz.selectors.elQuestionTitleNewInput ) ||
			target?.closest( BuilderEditQuiz.selectors.elQuestionTypeNew );
		if ( ! elTrigger ) {
			return;
		}

		const elAddNewQuestion = elTrigger.closest( `.${ BuilderEditQuiz.selectors.elAddNewQuestion }` );
		const elBtnAddQuestion = elAddNewQuestion?.querySelector( BuilderEditQuiz.selectors.elBtnAddQuestion );
		if ( ! elBtnAddQuestion ) {
			return;
		}

		const elQuestionTitleInput = elAddNewQuestion.querySelector( BuilderEditQuiz.selectors.elQuestionTitleNewInput );
		const elQuestionTypeNew = elAddNewQuestion.querySelector( BuilderEditQuiz.selectors.elQuestionTypeNew );

		const questionTitle = elQuestionTitleInput?.value?.trim();
		const questionType = elQuestionTypeNew?.value;

		elBtnAddQuestion.classList.toggle( 'active', !! ( questionTitle && questionType ) );
	}

	/**
	 * Remove question from quiz
	 */
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
		if ( ! questionId ) {
			return;
		}

		const i18n = window.lpDataAdmin?.i18n || window.lpData?.i18n || { cancel: 'Cancel', yes: 'Yes' };

		SweetAlert.fire( {
			title: elBtnRemoveQuestion.dataset.title || 'Are you sure?',
			text: elBtnRemoveQuestion.dataset.content || 'Do you want to remove this question?',
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
						lpToastify.show( error?.message || error || 'Failed to remove question', 'error' );
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

	/**
	 * Update question title
	 */
	updateQuestionTitle( args ) {
		const { e, target } = args;
		const canHandle = target.closest( BuilderEditQuiz.selectors.elBtnUpdateQuestionTitle ) ||
			( target.closest( BuilderEditQuiz.selectors.elQuestionTitleInput ) && e.key === 'Enter' );

		if ( ! canHandle ) {
			return;
		}

		e.preventDefault();

		const elQuestionItem = target.closest( BuilderEditQuiz.selectors.elQuestionItem );
		const elQuestionTitleInput = elQuestionItem?.querySelector( BuilderEditQuiz.selectors.elQuestionTitleInput );
		if ( ! elQuestionTitleInput ) {
			return;
		}

		const questionId = elQuestionItem.dataset.questionId;
		const questionTitleValue = elQuestionTitleInput.value.trim();
		const titleOld = elQuestionTitleInput.dataset.old;

		if ( ! questionTitleValue ) {
			lpToastify.show( elQuestionTitleInput.dataset.messEmptyTitle || 'Title is required', 'error' );
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
				lpToastify.show( error?.message || error || 'Failed to update title', 'error' );
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

	/**
	 * Handle title change
	 */
	changeTitleQuestion( args ) {
		const { target } = args;
		const elQuestionTitleInput = target.closest( BuilderEditQuiz.selectors.elQuestionTitleInput );
		if ( ! elQuestionTitleInput ) {
			return;
		}

		const elQuestionItem = elQuestionTitleInput.closest( BuilderEditQuiz.selectors.elQuestionItem );
		if ( ! elQuestionItem ) {
			return;
		}

		const titleValue = elQuestionTitleInput.value.trim();
		const titleValueOld = elQuestionTitleInput.dataset.old || '';

		elQuestionItem.classList.toggle( 'editing', titleValue !== titleValueOld );
	}

	/**
	 * Cancel title change
	 */
	cancelChangeTitleQuestion( args ) {
		const { target } = args;
		const elBtnCancel = target.closest( BuilderEditQuiz.selectors.elBtnCancelUpdateQuestionTitle );
		if ( ! elBtnCancel ) {
			return;
		}

		const elQuestionItem = elBtnCancel.closest( BuilderEditQuiz.selectors.elQuestionItem );
		if ( ! elQuestionItem ) {
			return;
		}

		const elQuestionTitleInput = elQuestionItem.querySelector( BuilderEditQuiz.selectors.elQuestionTitleInput );
		if ( elQuestionTitleInput ) {
			elQuestionTitleInput.value = elQuestionTitleInput.dataset.old || '';
		}
		elQuestionItem.classList.remove( 'editing' );
	}

	/**
	 * Make questions sortable
	 */
	sortAbleQuestion() {
		if ( ! this.elEditListQuestions ) {
			return;
		}

		// Destroy existing instance first
		if ( this.sortableInstance?.destroy ) {
			try {
				this.sortableInstance.destroy();
			} catch ( e ) {
				console.warn( 'Error destroying sortable:', e );
			}
			this.sortableInstance = null;
		}

		let isUpdateSectionPosition = 0;
		let timeout;

		try {
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
						const elQuestionItems = this.elEditListQuestions.querySelectorAll( `${ BuilderEditQuiz.selectors.elQuestionItem }:not(.clone)` );
						elQuestionItems.forEach( ( elItem ) => {
							const questionId = elItem?.dataset?.questionId;
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
									throw new Error( message );
								}
							},
							error: ( error ) => {
								lpToastify.show( error?.message || error || 'Failed to update order', 'error' );
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
		} catch ( e ) {
			console.error( 'Error creating sortable:', e );
		}
	}
}
