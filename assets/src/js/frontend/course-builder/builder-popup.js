/**
 * Builder Popup Handler
 * Handles AJAX popup loading for lesson, quiz, and question builders.
 *
 * @since 4.3.0
 * @version 1.0.1
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { BuilderEditQuiz } from './builder-quiz/builder-edit-quiz.js';
import { BuilderEditQuestion } from './builder-question/builder-edit-question.js';
import { BuilderMaterial } from './builder-lesson/builder-material.js';

export class BuilderPopup {
	constructor() {
		this.popupContainer = null;
		this.currentType = null;
		this.currentId = null;
		this.isNewItem = false;
		this.savedData = null;
		this.builderEditQuiz = null;
		this.builderEditQuestion = null;
		this.builderMaterial = null;
		this.loadedTabAssets = new Set();
		this.initializedTabs = new Map();
		this.init();
	}

	static selectors = {
		popupContainer: '#lp-builder-popup-container',
		popupOverlay: '.lp-builder-popup-overlay',
		popup: '.lp-builder-popup',
		closeBtn: '.lp-builder-popup__close',
		cancelBtn: '.lp-builder-popup__btn--cancel',
		saveBtn: '.lp-builder-popup__btn--save',
		trashBtn: '.lp-builder-popup__btn--trash',
		tabs: '.lp-builder-popup__tabs',
		tab: '.lp-builder-popup__tab',
		tabPane: '.lp-builder-popup__tab-pane',
		// Trigger buttons
		triggerLesson: '[data-popup-lesson]',
		triggerQuiz: '[data-popup-quiz]',
		triggerQuestion: '[data-popup-question]',
		// Add new buttons
		addNewLesson: '[data-add-new-lesson]',
		addNewQuiz: '[data-add-new-quiz]',
		addNewQuestion: '[data-add-new-question]',
	};

	init() {
		this.createPopupContainer();
		this.events();
	}

	createPopupContainer() {
		if ( ! document.querySelector( BuilderPopup.selectors.popupContainer ) ) {
			const container = document.createElement( 'div' );
			container.id = 'lp-builder-popup-container';
			document.body.appendChild( container );
		}
		this.popupContainer = document.querySelector( BuilderPopup.selectors.popupContainer );
	}

	events() {
		if ( BuilderPopup._loadedEvents ) {
			return;
		}
		BuilderPopup._loadedEvents = true;

		// Open popup events
		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderPopup.selectors.triggerLesson,
				class: this,
				callBack: 'openLessonPopup',
			},
			{
				selector: BuilderPopup.selectors.triggerQuiz,
				class: this,
				callBack: 'openQuizPopup',
			},
			{
				selector: BuilderPopup.selectors.triggerQuestion,
				class: this,
				callBack: 'openQuestionPopup',
			},
			{
				selector: BuilderPopup.selectors.addNewLesson,
				class: this,
				callBack: 'addNewLesson',
			},
			{
				selector: BuilderPopup.selectors.addNewQuiz,
				class: this,
				callBack: 'addNewQuiz',
			},
			{
				selector: BuilderPopup.selectors.addNewQuestion,
				class: this,
				callBack: 'addNewQuestion',
			},
		] );

		// Close popup events
		document.addEventListener( 'click', ( e ) => {
			if (
				e.target.closest( BuilderPopup.selectors.closeBtn ) ||
				e.target.closest( BuilderPopup.selectors.cancelBtn ) ||
				e.target.matches( BuilderPopup.selectors.popupOverlay )
			) {
				this.closePopup();
			}
		} );

		// Tab switching
		document.addEventListener( 'click', ( e ) => {
			const tab = e.target.closest( BuilderPopup.selectors.tab );
			if ( tab && this.isPopupOpen() ) {
				this.switchTab( tab );
			}
		} );

		// Save and trash button events
		document.addEventListener( 'click', ( e ) => {
			const saveBtn = e.target.closest( BuilderPopup.selectors.saveBtn );
			if ( saveBtn && this.isPopupOpen() ) {
				this.handleSave( saveBtn );
			}

			const trashBtn = e.target.closest( BuilderPopup.selectors.trashBtn );
			if ( trashBtn && this.isPopupOpen() ) {
				this.handleTrash( trashBtn );
			}
		} );

		// Close on Escape key
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Escape' && this.isPopupOpen() ) {
				this.closePopup();
			}
		} );
	}

	/**
	 * Add new item handlers
	 */
	addNewLesson( args ) {
		const { target } = args;
		if ( target.closest( BuilderPopup.selectors.addNewLesson ) ) {
			this.loadPopup( 'lesson', 0 );
		}
	}

	addNewQuiz( args ) {
		const { target } = args;
		if ( target.closest( BuilderPopup.selectors.addNewQuiz ) ) {
			this.loadPopup( 'quiz', 0 );
		}
	}

	addNewQuestion( args ) {
		const { target } = args;
		if ( target.closest( BuilderPopup.selectors.addNewQuestion ) ) {
			this.loadPopup( 'question', 0 );
		}
	}

	/**
	 * Open popup handlers
	 */
	openLessonPopup( args ) {
		const { target } = args;
		const triggerEl = target.closest( BuilderPopup.selectors.triggerLesson );
		if ( triggerEl ) {
			const lessonId = parseInt( triggerEl.dataset.popupLesson ) || 0;
			this.loadPopup( 'lesson', lessonId );
		}
	}

	openQuizPopup( args ) {
		const { target } = args;
		const triggerEl = target.closest( BuilderPopup.selectors.triggerQuiz );
		if ( triggerEl ) {
			const quizId = parseInt( triggerEl.dataset.popupQuiz ) || 0;
			this.loadPopup( 'quiz', quizId );
		}
	}

	openQuestionPopup( args ) {
		const { target } = args;
		const triggerEl = target.closest( BuilderPopup.selectors.triggerQuestion );
		if ( triggerEl ) {
			const questionId = parseInt( triggerEl.dataset.popupQuestion ) || 0;
			this.loadPopup( 'question', questionId );
		}
	}

	/**
	 * Load popup content via AJAX
	 */
	loadPopup( type, id ) {
		this.currentType = type;
		this.currentId = id;
		this.isNewItem = id === 0;

		this.ensurePopupContainer();
		this.showLoading();

		const methodMap = {
			lesson: 'render_lesson_popup',
			quiz: 'render_quiz_popup',
			question: 'render_question_popup',
		};

		const dataSend = {
			callback: {
				class: 'LearnPress\\TemplateHooks\\CourseBuilder\\BuilderPopupTemplate',
				method: methodMap[ type ],
			},
			args: {
				[ `${ type }_id` ]: id,
			},
		};

		const callBack = {
			success: ( response ) => {
				const { status, data } = response;
				if ( status === 'success' && data?.content ) {
					this.renderPopup( data.content );
				} else {
					lpToastify.show( response.message || 'Failed to load popup', 'error' );
					this.hideLoading();
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || 'Failed to load popup', 'error' );
				this.hideLoading();
			},
			completed: () => {
				// Loading hidden in success/error
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Ensure popup container exists
	 */
	ensurePopupContainer() {
		let container = document.querySelector( BuilderPopup.selectors.popupContainer );

		if ( ! container ) {
			container = document.createElement( 'div' );
			container.id = 'lp-builder-popup-container';
			document.body.appendChild( container );
		}

		this.popupContainer = container;
	}

	/**
	 * Render popup HTML
	 */
	renderPopup( html ) {
		this.ensurePopupContainer();

		if ( ! this.popupContainer ) {
			console.error( 'BuilderPopup: popupContainer is null' );
			return;
		}

		this.popupContainer.innerHTML = html;
		this.popupContainer.classList.add( 'active' );
		document.body.classList.add( 'lp-popup-open' );

		this.loadedTabAssets.clear();
		this.initializedTabs.clear(); // Clear initialized tabs cache
		this.resetAjaxElements();
		this.loadActiveTabAssets();

		// Initialize type-specific handlers
		this.initTypeSpecificHandlers();

		document.dispatchEvent(
			new CustomEvent( 'lp-builder-popup-opened', {
				detail: { type: this.currentType, id: this.currentId, isNew: this.isNewItem },
			} )
		);
	}

	/**
	 * Initialize type-specific handlers based on current popup type
	 */
	initTypeSpecificHandlers() {
		const activeTab = this.popupContainer.querySelector( `${ BuilderPopup.selectors.tab }.active` );
		const activeTabName = activeTab?.dataset.tab || 'overview';

		// Initialize TinyMCE for overview tab
		if ( activeTabName === 'overview' ) {
			setTimeout( () => this.initTinyMCE(), 50 );
		}

		// Type-specific initialization
		switch ( this.currentType ) {
			case 'quiz':
				this.initQuizHandlers( activeTabName );
				break;
			case 'question':
				this.initQuestionHandlers( activeTabName );
				break;
			case 'lesson':
				this.initLessonHandlers( activeTabName );
				break;
		}
	}

	/**
	 * Initialize quiz-specific handlers
	 */
	initQuizHandlers( activeTabName ) {
		if ( ! this.builderEditQuiz ) {
			this.builderEditQuiz = new BuilderEditQuiz();
		}

		if ( activeTabName === 'questions' ) {
			const tabKey = `${ this.currentType }-${ activeTabName }`;

			setTimeout( () => {
				const questionsPane = this.popupContainer.querySelector(
					`${ BuilderPopup.selectors.tabPane }[data-tab="questions"]`
				);
				if ( questionsPane ) {
					this.triggerAjaxLoadForTab( questionsPane );
					this.builderEditQuiz.reinit( this.popupContainer );
				}

				// Only init once per popup instance
				if ( ! this.initializedTabs.has( tabKey ) ) {
					this.builderEditQuiz.reinit( this.popupContainer );
					this.initializedTabs.set( tabKey, true );
				}
			}, 100 );
		}
	}

	/**
	 * Initialize question-specific handlers
	 */
	initQuestionHandlers( activeTabName ) {
		if ( ! this.builderEditQuestion ) {
			this.builderEditQuestion = new BuilderEditQuestion();
		}

		if ( activeTabName === 'settings' ) {
			const tabKey = `${ this.currentType }-${ activeTabName }`;

			setTimeout( () => {
				const settingsPane = this.popupContainer.querySelector(
					`${ BuilderPopup.selectors.tabPane }[data-tab="settings"]`
				);
				if ( settingsPane ) {
					this.triggerAjaxLoadForTab( settingsPane );
				}

				// Only init once per popup instance
				if ( ! this.initializedTabs.has( tabKey ) ) {
					this.builderEditQuestion.reinit( this.popupContainer );
					this.initializedTabs.set( tabKey, true );
				}
			}, 100 );
		}
	}

	/**
	 * Initialize lesson-specific handlers
	 */
	initLessonHandlers( activeTabName ) {
		if ( ! this.builderMaterial ) {
			this.builderMaterial = new BuilderMaterial();
		}

		if ( activeTabName === 'settings' ) {
			const tabKey = `${ this.currentType }-${ activeTabName }`;

			setTimeout( () => {
				const settingsPane = this.popupContainer.querySelector(
					`${ BuilderPopup.selectors.tabPane }[data-tab="settings"]`
				);
				if ( settingsPane ) {
					this.triggerAjaxLoadForTab( settingsPane );
				}

				// Only init once per popup instance
				if ( ! this.initializedTabs.has( tabKey ) ) {
					this.builderMaterial.reinit( this.popupContainer );
					this.initializedTabs.set( tabKey, true );
				}
			}, 100 );
		}
	}

	/**
	 * Reset AJAX elements to allow fresh loading
	 */
	resetAjaxElements() {
		if ( ! this.popupContainer ) {
			return;
		}

		const ajaxElements = this.popupContainer.querySelectorAll( '.lp-load-ajax-element.loaded' );
		ajaxElements.forEach( ( el ) => el.classList.remove( 'loaded' ) );

		if ( window.lpAJAXG ) {
			setTimeout( () => window.lpAJAXG.getElements(), 50 );
		}
	}

	/**
	 * Close popup
	 */
	closePopup() {
		const closedType = this.currentType;
		const closedId = this.currentId;
		const savedData = this.savedData;

		this.destroyAllTinyMCE();

		this.popupContainer.innerHTML = '';
		this.popupContainer.classList.remove( 'active' );
		document.body.classList.remove( 'lp-popup-open' );

		this.loadedTabAssets.clear();
		this.initializedTabs.clear(); // Clear initialized tabs cache

		if ( savedData && closedId ) {
			this.updateListItemOnClose( closedType, closedId, savedData );
		}

		document.dispatchEvent(
			new CustomEvent( 'lp-builder-popup-closed', {
				detail: { type: closedType, id: closedId, savedData },
			} )
		);

		this.currentType = null;
		this.currentId = null;
		this.isNewItem = false;
		this.savedData = null;
	}

	/**
	 * Update list item when popup closes after save
	 */
	updateListItemOnClose( type, id, savedData ) {
		if ( ! type || ! id || ! savedData ) {
			return;
		}

		const { formData, data } = savedData;
		const listItem = this.findListItem( type, id );

		if ( ! listItem ) {
			return;
		}

		// Update title
		const newTitle = formData[ `${ type }_title` ];
		if ( newTitle ) {
			this.updateElementText(
				listItem,
				[
					'.item-title',
					'.lp-item-title',
					`.lp-${ type }-title`,
					'.curriculum-item-title',
					'.item-name',
					'span.title',
					'.lp-question-title-input',
					'.section-item-title input',
					'.section-item-title span',
				],
				newTitle
			);
		}

		// Update status
		if ( data?.status ) {
			this.updateElementClass(
				listItem,
				[ `.${ type }-status`, '.item-status', '.post-status' ],
				data.status
			);
		}

		// Type-specific updates
		const typeUpdaters = {
			lesson: () => this.updateLessonListItem( listItem, formData, data ),
			quiz: () => this.updateQuizListItem( listItem, formData, data ),
			question: () => this.updateQuestionListItem( listItem, formData, data ),
		};

		if ( typeUpdaters[ type ] ) {
			typeUpdaters[ type ]();
		}

		document.dispatchEvent(
			new CustomEvent( 'lp-builder-list-item-updated', {
				detail: { type, id, formData, data },
			} )
		);
	}

	/**
	 * Find list item by type and ID
	 */
	findListItem( type, id ) {
		const selectors = [
			`[data-${ type }-id="${ id }"]`,
			`[data-id="${ id }"]`,
			`[data-popup-${ type }="${ id }"]`,
			`[data-item-id="${ id }"]`,
			`.section-item[data-item-id="${ id }"]`,
			`.lp-${ type }-item[data-id="${ id }"]`,
		];

		for ( const selector of selectors ) {
			const item = document.querySelector( selector );
			if ( item ) {
				return item;
			}
		}

		return null;
	}

	/**
	 * Update element text (input value or textContent)
	 */
	updateElementText( parent, selectors, newText ) {
		for ( const selector of selectors ) {
			const el = parent.querySelector( selector );
			if ( el ) {
				if ( el.tagName === 'INPUT' ) {
					el.value = newText;
				} else {
					el.textContent = newText;
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Update element class
	 */
	updateElementClass( parent, selectors, newClass ) {
		for ( const selector of selectors ) {
			const el = parent.querySelector( selector );
			if ( el ) {
				const baseClass = selector.replace( '.', '' );
				el.className = el.className.replace( /\b(publish|draft|pending|trash)\b/g, '' ).trim();
				el.classList.add( baseClass, newClass );
				el.textContent = newClass;
				return true;
			}
		}
		return false;
	}

	/**
	 * Update lesson-specific data
	 */
	updateLessonListItem( listItem, formData, data ) {
		const duration = formData._lp_duration || data?.duration;
		if ( duration ) {
			this.updateDuration( listItem, duration );
		}

		const preview = formData._lp_preview || data?.preview;
		this.updateLessonPreview( listItem, preview );
	}

	/**
	 * Update quiz-specific data
	 */
	updateQuizListItem( listItem, formData, data ) {
		const duration = formData._lp_duration || data?.duration;
		if ( duration ) {
			this.updateDuration( listItem, duration );
		}

		const questionCount = data?.question_count || data?.questions_count;
		if ( questionCount !== null && questionCount !== undefined ) {
			this.updateMeta(
				listItem,
				'.question-count',
				`${ questionCount } ${ questionCount === 1 ? 'Question' : 'Questions' }`
			);
		}

		const passingGrade = formData._lp_passing_grade || data?.passing_grade;
		if ( passingGrade ) {
			this.updateMeta( listItem, '.passing-grade', `${ passingGrade }%` );
		}
	}

	/**
	 * Update question-specific data
	 */
	updateQuestionListItem( listItem, formData, data ) {
		const questionType = formData._lp_type || data?.type;
		if ( questionType ) {
			this.updateElementText(
				listItem,
				[ '.question-type', '.item-type' ],
				this.formatQuestionType( questionType )
			);

			const typeClasses = [ 'true_or_false', 'single_choice', 'multi_choice', 'fill_in_blanks' ];
			typeClasses.forEach( ( cls ) => listItem.classList.remove( cls ) );
			listItem.classList.add( questionType );
		}

		const mark = formData._lp_mark || data?.mark;
		if ( mark ) {
			this.updateMeta( listItem, '.question-mark', mark );
		}
	}

	/**
	 * Update duration meta
	 */
	updateDuration( listItem, duration ) {
		const durationStr = this.formatDuration( duration );
		const updated = this.updateElementText(
			listItem,
			[ '.item-meta.duration', '.duration', '.course-item-duration', '.meta-duration' ],
			durationStr
		);

		if ( ! updated && durationStr ) {
			const metaContainer = listItem.querySelector(
				'.course-item__right, .item-meta-container, .course-item-meta'
			);
			if ( metaContainer ) {
				let durationEl = metaContainer.querySelector( '.duration' );
				if ( ! durationEl ) {
					durationEl = document.createElement( 'span' );
					durationEl.className = 'duration';
					metaContainer.insertBefore( durationEl, metaContainer.firstChild );
				}
				durationEl.textContent = durationStr;
			}
		}
	}

	/**
	 * Update meta element
	 */
	updateMeta( listItem, selector, value ) {
		const el = listItem.querySelector( selector );
		if ( el ) {
			el.textContent = value;
		}
	}

	/**
	 * Format duration value
	 */
	formatDuration( duration ) {
		if ( ! duration ) {
			return '';
		}

		if ( typeof duration === 'string' && duration.match( /\d+\s+\w+/ ) ) {
			return duration;
		}

		const parts = String( duration ).trim().split( /\s+/ );
		if ( parts.length >= 2 ) {
			const value = parseInt( parts[ 0 ] ) || 0;
			const unit = parts[ 1 ].toLowerCase();

			if ( value === 0 ) {
				return '';
			}

			const unitMap = {
				minute: value === 1 ? 'Minute' : 'Minutes',
				hour: value === 1 ? 'Hour' : 'Hours',
				day: value === 1 ? 'Day' : 'Days',
				week: value === 1 ? 'Week' : 'Weeks',
			};

			return `${ value } ${ unitMap[ unit ] || unit }`;
		}

		const numValue = parseInt( duration ) || 0;
		return numValue > 0 ? `${ numValue } ${ numValue === 1 ? 'Minute' : 'Minutes' }` : '';
	}

	/**
	 * Update lesson preview status
	 */
	updateLessonPreview( listItem, preview ) {
		const isPreview = preview === 'yes' || preview === true || preview === '1';

		// Update preview icon
		const previewEl = listItem.querySelector( '.lp-btn-set-preview-item a, .course-item-preview' );
		if ( previewEl ) {
			if ( isPreview ) {
				previewEl.classList.remove( 'lp-icon-eye-slash' );
				previewEl.classList.add( 'lp-icon-eye' );
			} else {
				previewEl.classList.remove( 'lp-icon-eye' );
				previewEl.classList.add( 'lp-icon-eye-slash' );
			}
		}

		// Update preview checkbox
		const checkbox = listItem.querySelector( 'input[type="checkbox"].preview-checkbox' );
		if ( checkbox ) {
			checkbox.checked = isPreview;
		}

		// Toggle preview class
		listItem.classList.toggle( 'is-preview', isPreview );
		listItem.classList.toggle( 'preview-item', isPreview );
	}

	/**
	 * Format question type for display
	 */
	formatQuestionType( type ) {
		const typeMap = {
			true_or_false: 'True or False',
			single_choice: 'Single Choice',
			multi_choice: 'Multi Choice',
			fill_in_blanks: 'Fill in Blanks',
		};
		return typeMap[ type ] || type;
	}

	/**
	 * Check if popup is open
	 */
	isPopupOpen() {
		return this.popupContainer?.classList.contains( 'active' );
	}

	/**
	 * Show loading state
	 */
	showLoading() {
		this.popupContainer.innerHTML = `
			<div class="lp-builder-popup-overlay"></div>
			<div class="lp-builder-popup lp-builder-popup--loading">
				<div class="lp-builder-popup__loader">
					<div class="lp-loading-circle"></div>
					<span>Loading...</span>
				</div>
			</div>
		`;
		this.popupContainer.classList.add( 'active' );
		document.body.classList.add( 'lp-popup-open' );
	}

	/**
	 * Hide loading state
	 */
	hideLoading() {
		this.popupContainer.innerHTML = '';
		this.popupContainer.classList.remove( 'active' );
		document.body.classList.remove( 'lp-popup-open' );
	}

	/**
	 * Switch tab with dynamic asset loading
	 */
	switchTab( tabEl ) {
		const tabName = tabEl.dataset.tab;
		const popup = tabEl.closest( BuilderPopup.selectors.popup );

		if ( ! popup || ! tabName ) {
			return;
		}

		// Sync TinyMCE before switching
		this.syncAllTinyMCE();

		// Update tab states
		popup.querySelectorAll( BuilderPopup.selectors.tab ).forEach( ( tab ) => {
			tab.classList.remove( 'active' );
		} );
		tabEl.classList.add( 'active' );

		// Update pane states
		popup.querySelectorAll( BuilderPopup.selectors.tabPane ).forEach( ( pane ) => {
			pane.classList.remove( 'active' );
		} );

		const targetPane = popup.querySelector(
			`${ BuilderPopup.selectors.tabPane }[data-tab="${ tabName }"]`
		);

		if ( ! targetPane ) {
			return;
		}

		targetPane.classList.add( 'active' );
		this.loadTabAssets( tabName, targetPane );

		// Handle tab-specific initialization
		this.handleTabSwitch( tabName, targetPane );

		document.dispatchEvent(
			new CustomEvent( 'lp-builder-tab-switched', {
				detail: { tabName, type: this.currentType, id: this.currentId },
			} )
		);
	}

	/**
	 * Handle tab switch for specific types
	 */
	handleTabSwitch( tabName, targetPane ) {
		const tabKey = `${ this.currentType }-${ tabName }`;

		// Check if tab already initialized
		if ( this.initializedTabs.has( tabKey ) ) {
			// Already initialized, just show the tab - no need to reinit
			return;
		}

		if ( tabName === 'overview' ) {
			setTimeout( () => this.initTinyMCE(), 100 );
			this.initializedTabs.set( tabKey, true );
		}

		// Type-specific tab handling - only init if not already initialized
		if ( tabName === 'questions' && this.currentType === 'quiz' ) {
			this.triggerAjaxLoadForTab( targetPane );
			if ( this.builderEditQuiz ) {
				setTimeout( () => {
					this.builderEditQuiz.reinit( this.popupContainer );
					this.initializedTabs.set( tabKey, true );
				}, 100 );
			}
		} else if ( tabName === 'settings' && this.currentType === 'question' ) {
			this.triggerAjaxLoadForTab( targetPane );
			if ( this.builderEditQuestion ) {
				setTimeout( () => {
					this.builderEditQuestion.reinit( this.popupContainer );
					this.initializedTabs.set( tabKey, true );
				}, 100 );
			}
		} else if ( tabName === 'settings' && this.currentType === 'lesson' ) {
			this.triggerAjaxLoadForTab( targetPane );
			if ( this.builderMaterial ) {
				setTimeout( () => {
					this.builderMaterial.reinit( this.popupContainer );
					this.initializedTabs.set( tabKey, true );
				}, 100 );
			}
		}
	}

	/**
	 * Trigger AJAX loading for tab elements
	 */
	triggerAjaxLoadForTab( tabPane ) {
		if ( ! tabPane || ! window.lpAJAXG ) {
			return;
		}

		const ajaxElements = tabPane.querySelectorAll( '.lp-load-ajax-element:not(.loaded)' );

		if ( ajaxElements.length > 0 ) {
			ajaxElements.forEach( ( el ) => el.classList.remove( 'loaded' ) );
			window.lpAJAXG.getElements();
		}
	}

	/**
	 * Initialize TinyMCE for current popup type
	 */
	initTinyMCE() {
		const editorId = `${ this.currentType }_description_editor`;
		const textarea = document.getElementById( editorId );

		if ( ! textarea || typeof tinymce === 'undefined' ) {
			return;
		}

		this.destroyTinyMCE( editorId );

		if ( typeof wp !== 'undefined' && wp.editor?.initialize ) {
			wp.editor.initialize( editorId, {
				tinymce: {
					wpautop: true,
					plugins:
						'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wplink wptextpattern',
					toolbar1:
						'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,spellchecker,wp_adv',
					toolbar2:
						'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
					wordpress_adv_hidden: false,
				},
				quicktags: { buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close' },
				mediaButtons: true,
			} );
		} else {
			tinymce.init( {
				selector: '#' + editorId,
				height: 300,
				menubar: false,
				plugins: [
					'advlist autolink lists link image charmap print preview anchor',
					'searchreplace visualblocks code fullscreen',
					'insertdatetime media table paste code help wordcount',
				],
				toolbar:
					'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
			} );
		}
	}

	/**
	 * Sync all TinyMCE instances
	 */
	syncAllTinyMCE() {
		if ( typeof tinymce === 'undefined' || ! this.currentType ) {
			return;
		}

		const editorId = `${ this.currentType }_description_editor`;
		const editor = tinymce.get( editorId );

		if ( editor ) {
			editor.save();
		}

		// Sync additional editors
		tinymce.editors.forEach( ( ed ) => {
			if ( ed.id?.includes( this.currentType ) ) {
				ed.save();
			}
		} );
	}

	/**
	 * Destroy specific TinyMCE instance
	 */
	destroyTinyMCE( editorId ) {
		if ( typeof tinymce !== 'undefined' ) {
			const editor = tinymce.get( editorId );
			if ( editor ) {
				editor.remove();
			}
		}

		if ( typeof wp !== 'undefined' && wp.editor?.remove ) {
			wp.editor.remove( editorId );
		}
	}

	/**
	 * Destroy all TinyMCE editors in popup
	 */
	destroyAllTinyMCE() {
		if ( ! this.currentType || typeof tinymce === 'undefined' ) {
			return;
		}

		const editorId = `${ this.currentType }_description_editor`;
		this.destroyTinyMCE( editorId );

		const editorsToRemove = [];
		tinymce.editors.forEach( ( ed ) => {
			if ( ed.id && this.popupContainer?.querySelector( `#${ ed.id }` ) ) {
				editorsToRemove.push( ed.id );
			}
		} );
		editorsToRemove.forEach( ( id ) => this.destroyTinyMCE( id ) );
	}

	/**
	 * Handle save action
	 */
	handleSave( saveBtn ) {
		if ( ! this.currentType ) {
			return;
		}

		this.syncAllTinyMCE();

		const formData = this.getFormData();
		const validation = this.validateFormData( formData );

		if ( ! validation.valid ) {
			lpToastify.show( validation.errors.join( '. ' ), 'error' );
			return;
		}

		lpUtils.lpSetLoadingEl( saveBtn, 1 );

		const actionMap = {
			lesson: 'builder_update_lesson',
			quiz: 'builder_update_quiz',
			question: 'builder_update_question',
		};

		const wasNewItem = this.isNewItem;

		const dataSend = {
			...formData,
			action: actionMap[ this.currentType ] || `builder_update_${ this.currentType }`,
			args: { id_url: `builder-update-${ this.currentType }` },
			[ `${ this.currentType }_status` ]: 'publish',
			return_html: wasNewItem ? 'yes' : 'no',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;

				lpToastify.show( message, status );

				if ( status === 'success' ) {
					this.handleSaveSuccess( saveBtn, data, formData, wasNewItem );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || 'Save failed', 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( saveBtn, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Handle save success
	 */
	handleSaveSuccess( saveBtn, data, formData, wasNewItem ) {
		if ( data?.button_title ) {
			saveBtn.textContent = data.button_title;
		}

		// Update status
		if ( data?.status ) {
			const statusEl = this.popupContainer.querySelector( `.${ this.currentType }-status` );
			if ( statusEl ) {
				statusEl.className = `${ this.currentType }-status ${ data.status }`;
				statusEl.textContent = data.status;
			}
		}

		// Handle new item
		const newIdKey = `${ this.currentType }_id_new`;
		if ( data?.[ newIdKey ] ) {
			const newId = data[ newIdKey ];
			this.currentId = newId;
			this.isNewItem = false;

			const wrapper = this.popupContainer.querySelector( `[data-${ this.currentType }-id]` );
			if ( wrapper ) {
				wrapper.dataset[ `${ this.currentType }Id` ] = newId;
			}

			const popup = this.popupContainer.querySelector( BuilderPopup.selectors.popup );
			if ( popup ) {
				popup.dataset[ `${ this.currentType }Id` ] = newId;
			}
		}

		// Store saved data
		this.savedData = { formData, data, wasNewItem };

		// Handle new item creation
		if ( wasNewItem && this.currentId ) {
			document.dispatchEvent(
				new CustomEvent( 'lp-builder-popup-saved', {
					detail: {
						type: this.currentType,
						id: this.currentId,
						data,
						formData,
						wasNewItem,
						listItemHtml: data?.list_item_html || null,
					},
				} )
			);

			// Reload popup to show all tabs
			setTimeout( () => {
				this.destroyAllTinyMCE();
				this.loadPopup( this.currentType, this.currentId );
			}, 300 );
		} else {
			document.dispatchEvent(
				new CustomEvent( 'lp-builder-popup-saved', {
					detail: { type: this.currentType, id: this.currentId, data, formData, wasNewItem: false },
				} )
			);
		}
	}

	/**
	 * Handle trash action
	 */
	handleTrash( trashBtn ) {
		if ( ! this.currentType || ! this.currentId ) {
			return;
		}

		if ( ! confirm( `Are you sure you want to move this ${ this.currentType } to trash?` ) ) {
			return;
		}

		lpUtils.lpSetLoadingEl( trashBtn, 1 );

		const actionMap = {
			lesson: 'move_trash_lesson',
			quiz: 'move_trash_quiz',
			question: 'move_trash_question',
		};

		const dataSend = {
			action: actionMap[ this.currentType ] || `move_trash_${ this.currentType }`,
			args: { id_url: `move-trash-${ this.currentType }` },
			[ `${ this.currentType }_id` ]: this.currentId,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					if ( data?.button_title ) {
						const saveBtn = this.popupContainer.querySelector( BuilderPopup.selectors.saveBtn );
						if ( saveBtn ) {
							saveBtn.textContent = data.button_title;
						}
					}

					if ( data?.status ) {
						const statusEl = this.popupContainer.querySelector( `.${ this.currentType }-status` );
						if ( statusEl ) {
							statusEl.className = `${ this.currentType }-status ${ data.status }`;
							statusEl.textContent = data.status;
						}
					}

					this.savedData = { formData: this.getFormData(), data, wasNewItem: false };

					document.dispatchEvent(
						new CustomEvent( 'lp-builder-popup-trashed', {
							detail: { type: this.currentType, id: this.currentId, data },
						} )
					);
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || 'Trash failed', 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( trashBtn, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Validate form data
	 */
	validateFormData( formData ) {
		const errors = [];
		const titleKey = `${ this.currentType }_title`;
		const title = formData[ titleKey ] || '';

		if ( ! title.trim() ) {
			errors.push(
				`${
					this.currentType.charAt( 0 ).toUpperCase() + this.currentType.slice( 1 )
				} title is required`
			);
		}

		if ( title.length > 200 ) {
			errors.push( 'Title must be less than 200 characters' );
		}

		return { valid: errors.length === 0, errors };
	}

	/**
	 * Get form data from popup
	 */
	getFormData() {
		const data = {};
		const popup = this.popupContainer.querySelector( BuilderPopup.selectors.popup );

		if ( ! popup ) {
			return data;
		}

		const idKey = `${ this.currentType }_id`;
		data[ idKey ] = this.currentId || 0;

		// Get title
		const titleInput = popup.querySelector(
			'input[name$="_title"], #title, #' + this.currentType + '_title'
		);
		if ( titleInput ) {
			data[ `${ this.currentType }_title` ] = titleInput.value;
		}

		// Get description
		const editorId = `${ this.currentType }_description_editor`;
		let descContent = '';

		if ( typeof tinymce !== 'undefined' && tinymce.get( editorId ) ) {
			descContent = tinymce.get( editorId ).getContent();
		} else {
			const descTextarea = popup.querySelector( `#${ editorId }` );
			if ( descTextarea ) {
				descContent = descTextarea.value;
			}
		}

		data[ `${ this.currentType }_description` ] = descContent;

		// Get form settings
		const formSettings = popup.querySelector( `.lp-form-setting-${ this.currentType }` );
		if ( formSettings ) {
			data[ `${ this.currentType }_settings` ] = true;
			this.collectFormData( formSettings, data );
		}

		return data;
	}

	/**
	 * Collect form data from form element
	 */
	collectFormData( form, data ) {
		const formElements = form.querySelectorAll( 'input, select, textarea' );

		formElements.forEach( ( element ) => {
			const name = element.name || element.id;

			if ( ! name || name === 'learnpress_meta_box_nonce' || name === '_wp_http_referer' ) {
				return;
			}

			const fieldName = name.replace( '[]', '' );

			if ( element.type === 'checkbox' ) {
				if ( ! data.hasOwnProperty( fieldName ) ) {
					data[ fieldName ] = element.checked ? 'yes' : 'no';
				}
			} else if ( element.type === 'radio' ) {
				if ( element.checked ) {
					data[ fieldName ] = element.value;
				}
			} else if ( element.type === 'file' ) {
				if ( element.files?.length > 0 ) {
					data[ fieldName ] = element.files;
				}
			} else if ( name.endsWith( '[]' ) ) {
				if ( ! data.hasOwnProperty( fieldName ) ) {
					data[ fieldName ] = [];
				}
				if ( Array.isArray( data[ fieldName ] ) ) {
					data[ fieldName ].push( element.value );
				}
			} else if ( ! data.hasOwnProperty( fieldName ) ) {
				data[ fieldName ] = element.value;
			}
		} );

		// Convert arrays to comma-separated strings
		Object.keys( data ).forEach( ( key ) => {
			if ( Array.isArray( data[ key ] ) ) {
				data[ key ] = data[ key ].join( ',' );
			}
		} );
	}

	/**
	 * Load active tab assets on initial render
	 */
	loadActiveTabAssets() {
		const popup = this.popupContainer.querySelector( BuilderPopup.selectors.popup );
		if ( ! popup ) {
			return;
		}

		const activeTab = popup.querySelector( `${ BuilderPopup.selectors.tab }.active` );
		const activeTabName = activeTab?.dataset.tab || 'overview';
		const activePane = popup.querySelector(
			`${ BuilderPopup.selectors.tabPane }[data-tab="${ activeTabName }"]`
		);

		if ( activePane ) {
			this.loadTabAssets( activeTabName, activePane );
		}
	}

	/**
	 * Load tab-specific assets (CSS/JS)
	 */
	loadTabAssets( tabName, tabPane ) {
		const tabKey = `${ this.currentType }-${ tabName }`;

		if ( this.loadedTabAssets.has( tabKey ) ) {
			return;
		}

		const assetsData = tabPane.dataset.tabAssets;
		if ( ! assetsData ) {
			this.loadedTabAssets.add( tabKey );
			return;
		}

		try {
			const assets = JSON.parse( assetsData );

			// Load CSS
			if ( assets.css && Array.isArray( assets.css ) ) {
				assets.css.forEach( ( cssUrl ) => {
					if ( ! document.querySelector( `link[href="${ cssUrl }"]` ) ) {
						const link = document.createElement( 'link' );
						link.rel = 'stylesheet';
						link.href = cssUrl;
						link.dataset.tabAsset = tabKey;
						document.head.appendChild( link );
					}
				} );
			}

			// Load JS
			if ( assets.js && Array.isArray( assets.js ) ) {
				assets.js.forEach( ( jsUrl ) => {
					if ( ! document.querySelector( `script[src="${ jsUrl }"]` ) ) {
						const script = document.createElement( 'script' );
						script.src = jsUrl;
						script.dataset.tabAsset = tabKey;
						document.head.appendChild( script );
					}
				} );
			}

			this.loadedTabAssets.add( tabKey );
		} catch ( e ) {
			console.warn( `Failed to load assets for tab "${ tabName }":`, e );
			this.loadedTabAssets.add( tabKey );
		}
	}

	/**
	 * Static method to open popup programmatically
	 */
	static open( type, id = 0 ) {
		if ( ! BuilderPopup._instance ) {
			BuilderPopup._instance = new BuilderPopup();
		}
		BuilderPopup._instance.loadPopup( type, id );
	}

	/**
	 * Static method to close popup programmatically
	 */
	static close() {
		if ( BuilderPopup._instance ) {
			BuilderPopup._instance.closePopup();
		}
	}
}

// Auto-initialize
document.addEventListener( 'DOMContentLoaded', () => {
	BuilderPopup._instance = new BuilderPopup();
} );

export default BuilderPopup;
