/**
 * Builder Popup Handler
 * Handles AJAX popup loading for lesson, quiz, and question builders.
 *
 * @since 4.3.0
 * @version 1.0.0
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
		this.savedData = null; // Store saved data for list update on close
		this.builderEditQuiz = null; // Instance of BuilderEditQuiz for quiz popup
		this.builderEditQuestion = null; // Instance of BuilderEditQuestion for question popup
		this.builderMaterial = null; // Instance of BuilderMaterial for lesson popup
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
		// List containers
		lessonList: '.lp-list-lessons',
		quizList: '.lp-list-quizzes',
		questionList: '.lp-list-questions',
		// Status elements
		statusLesson: '.lesson-status',
		statusQuiz: '.quiz-status',
		statusQuestion: '.question-status',
	};

	init() {
		this.createPopupContainer();
		this.loadedTabAssets = new Set();
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

		// Trigger events for opening popups (edit existing)
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

		// Tab switching with asset loading
		document.addEventListener( 'click', ( e ) => {
			const tab = e.target.closest( BuilderPopup.selectors.tab );
			if ( tab ) {
				this.switchTab( tab );
			}
		} );

		// Save button events
		document.addEventListener( 'click', ( e ) => {
			const saveBtn = e.target.closest( BuilderPopup.selectors.saveBtn );
			if ( saveBtn ) {
				this.handleSave( saveBtn );
			}
		} );

		 // Trash button events
		document.addEventListener( 'click', ( e ) => {
			const trashBtn = e.target.closest( BuilderPopup.selectors.trashBtn );
			if ( trashBtn ) {
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
	 * Add new lesson
	 */
	addNewLesson( args ) {
		const { target } = args;
		if ( ! target.closest( BuilderPopup.selectors.addNewLesson ) ) {
			return;
		}
		this.loadPopup( 'lesson', 0 );
	}

	/**
	 * Add new quiz
	 */
	addNewQuiz( args ) {
		const { target } = args;
		if ( ! target.closest( BuilderPopup.selectors.addNewQuiz ) ) {
			return;
		}
		this.loadPopup( 'quiz', 0 );
	}

	/**
	 * Add new question
	 */
	addNewQuestion( args ) {
		const { target } = args;
		if ( ! target.closest( BuilderPopup.selectors.addNewQuestion ) ) {
			return;
		}
		this.loadPopup( 'question', 0 );
	}

	/**
	 * Open lesson popup
	 */
	openLessonPopup( args ) {
		const { target } = args;
		const triggerEl = target.closest( BuilderPopup.selectors.triggerLesson );
		if ( ! triggerEl ) {
			return;
		}

		const lessonId = parseInt( triggerEl.dataset.popupLesson ) || 0;
		this.loadPopup( 'lesson', lessonId );
	}

	/**
	 * Open quiz popup
	 */
	openQuizPopup( args ) {
		const { target } = args;
		const triggerEl = target.closest( BuilderPopup.selectors.triggerQuiz );
		if ( ! triggerEl ) {
			return;
		}

		const quizId = parseInt( triggerEl.dataset.popupQuiz ) || 0;
		this.loadPopup( 'quiz', quizId );
	}

	/**
	 * Open question popup
	 */
	openQuestionPopup( args ) {
		const { target } = args;
		const triggerEl = target.closest( BuilderPopup.selectors.triggerQuestion );
		if ( ! triggerEl ) {
			return;
		}

		const questionId = parseInt( triggerEl.dataset.popupQuestion ) || 0;
		this.loadPopup( 'question', questionId );
	}

	/**
	 * Load popup content via AJAX
	 */
	loadPopup( type, id ) {
		this.currentType = type;
		this.currentId = id;
		this.isNewItem = id === 0;

		 // Ensure popup container exists and is fresh
		this.ensurePopupContainer();

		// Show loading state
		this.showLoading();

		const idKey = `${ type }_id`;
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
				[ idKey ]: id,
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
	 * Ensure popup container exists and reference is valid
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
		// Ensure we have valid container reference
		this.ensurePopupContainer();
		
		if ( ! this.popupContainer ) {
			console.error( 'BuilderPopup: popupContainer is null' );
			return;
		}

		this.popupContainer.innerHTML = html;
		this.popupContainer.classList.add( 'active' );
		document.body.classList.add( 'lp-popup-open' );

		this.loadedTabAssets = new Set();
		this.loadActiveTabAssets();

		 // Reset 'loaded' class from any AJAX elements to ensure fresh load
		// This fixes the issue where questions don't load on 2nd popup open
		this.resetAjaxElements();

		// Initialize TinyMCE for Overview tab
		const activeTab = this.popupContainer.querySelector( `${ BuilderPopup.selectors.tab }.active` );
		const activeTabName = activeTab?.dataset.tab || 'overview';

		if ( activeTabName === 'overview' ) {
			setTimeout( () => this.initTinyMCE(), 50 );
		}

		// Initialize quiz question handlers and BuilderEditQuiz if quiz popup
		if ( this.currentType === 'quiz' ) {
			this.initQuizQuestionHandlers();
			
			// Create BuilderEditQuiz instance if not exists
			if ( ! this.builderEditQuiz ) {
				this.builderEditQuiz = new BuilderEditQuiz();
			}
			
			// Only init immediately if questions tab is already active
			if ( activeTabName === 'questions' ) {
				setTimeout( () => {
					// Trigger AJAX load for questions tab
					const questionsPane = this.popupContainer.querySelector(
						`${ BuilderPopup.selectors.tabPane }[data-tab="questions"]`
					);
					if ( questionsPane ) {
						this.triggerAjaxLoadForTab( questionsPane );
					}
					this.builderEditQuiz.reinit( this.popupContainer );
				}, 100 );
			}
			// Otherwise, reinit will be called when user switches to questions tab
		}

		// Initialize BuilderEditQuestion for question popup
		if ( this.currentType === 'question' ) {
			// Create BuilderEditQuestion instance if not exists
			if ( ! this.builderEditQuestion ) {
				this.builderEditQuestion = new BuilderEditQuestion();
			}
			
			// Reinit for settings tab - this ensures TinyMCE is properly initialized
			// when popup is opened multiple times
			if ( activeTabName === 'settings' ) {
				setTimeout( () => {
					const settingsPane = this.popupContainer.querySelector(
						`${ BuilderPopup.selectors.tabPane }[data-tab="settings"]`
					);
					if ( settingsPane ) {
						this.triggerAjaxLoadForTab( settingsPane );
					}
					this.builderEditQuestion.reinit( this.popupContainer );
				}, 100 );
			}
		}

		// Initialize BuilderMaterial for lesson popup (material is inside settings tab)
		if ( this.currentType === 'lesson' ) {
			// Create BuilderMaterial instance if not exists
			if ( ! this.builderMaterial ) {
				this.builderMaterial = new BuilderMaterial();
			}

			// Reinit for settings tab (material is a child element inside settings)
			if ( activeTabName === 'settings' ) {
				setTimeout( () => {
					const settingsPane = this.popupContainer.querySelector(
						`${ BuilderPopup.selectors.tabPane }[data-tab="settings"]`
					);
					if ( settingsPane ) {
						this.triggerAjaxLoadForTab( settingsPane );
					}
					this.builderMaterial.reinit( this.popupContainer );
				}, 100 );
			}
		}

		document.dispatchEvent(
			new CustomEvent( 'lp-builder-popup-opened', {
				detail: { type: this.currentType, id: this.currentId, isNew: this.isNewItem },
			} )
		);
	}

	/**
	 * Reset AJAX elements in popup to allow fresh loading
	 * This is needed when popup is opened multiple times
	 */
	resetAjaxElements() {
		if ( ! this.popupContainer ) {
			return;
		}

		// Remove 'loaded' class from all AJAX elements in popup
		const ajaxElements = this.popupContainer.querySelectorAll( '.lp-load-ajax-element.loaded' );
		ajaxElements.forEach( ( el ) => {
			el.classList.remove( 'loaded' );
		} );

		// Trigger getElements to load any visible AJAX content
		if ( window.lpAJAXG ) {
			// Use setTimeout to ensure DOM is ready
			setTimeout( () => {
				window.lpAJAXG.getElements();
			}, 50 );
		}
	}

	/**
	 * Close popup
	 */
	closePopup() {
		 // Store data before clearing
		const closedType = this.currentType;
		const closedId = this.currentId;
		const savedData = this.savedData;

		// Destroy TinyMCE instances
		this.destroyAllTinyMCE();

		// Cleanup tab-specific event listeners
		this.cleanupTabEventListeners();

		this.popupContainer.innerHTML = '';
		this.popupContainer.classList.remove( 'active' );
		document.body.classList.remove( 'lp-popup-open' );

		// Clear loaded assets tracker
		if ( this.loadedTabAssets ) {
			this.loadedTabAssets.clear();
		}

		 // Update list item if data was saved
		if ( savedData && closedId ) {
			this.updateListItemOnClose( closedType, closedId, savedData );
		}

		// Trigger custom event
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

		// Find the item by ID - search in multiple possible containers
		const itemSelectors = [
			`[data-${ type }-id="${ id }"]`,
			`[data-id="${ id }"]`,
			`[data-popup-${ type }="${ id }"]`,
			`[data-item-id="${ id }"]`,
			`.section-item[data-item-id="${ id }"]`,
			`.course-item[data-item-id="${ id }"]`,
			`.lp-${ type }-item[data-id="${ id }"]`,
		];

		let listItem = null;
		for ( const selector of itemSelectors ) {
			listItem = document.querySelector( selector );
			if ( listItem ) {
				break;
			}
		}

		if ( ! listItem ) {
			return;
		}

		// Update title
		this.updateListItemTitle( listItem, type, formData );

		// Update status if available
		this.updateListItemStatus( listItem, type, data );

		// Update type-specific settings
		switch ( type ) {
			case 'lesson':
				this.updateLessonListItem( listItem, formData, data );
				break;
			case 'quiz':
				this.updateQuizListItem( listItem, formData, data );
				break;
			case 'question':
				this.updateQuestionListItem( listItem, formData, data );
				break;
		}

		// Trigger event for other components
		document.dispatchEvent(
			new CustomEvent( 'lp-builder-list-item-updated', {
				detail: { type, id, formData, data },
			} )
		);
	}

	/**
	 * Update title in list item
	 */
	updateListItemTitle( listItem, type, formData ) {
		const newTitle = formData[ `${ type }_title` ] || '';
		if ( ! newTitle ) {
			return;
		}

		const titleSelectors = [
			'.item-title',
			'.lp-item-title',
			`.lp-${ type }-title`,
			'.curriculum-item-title',
			'.course-item-title',
			'.item-name',
			'span.title',
			'a.title',
			'.lp-question-title-input',
			'.section-item-title input',
			'.section-item-title span',
		];

		for ( const selector of titleSelectors ) {
			const titleEl = listItem.querySelector( selector );
			if ( titleEl ) {
				if ( titleEl.tagName === 'INPUT' ) {
					titleEl.value = newTitle;
				} else {
					titleEl.textContent = newTitle;
				}
				break;
			}
		}
	}

	/**
	 * Update status in list item
	 */
	updateListItemStatus( listItem, type, data ) {
		if ( ! data?.status ) {
			return;
		}

		const statusSelectors = [
			`.${ type }-status`,
			'.item-status',
			'.post-status',
		];

		for ( const selector of statusSelectors ) {
			const statusEl = listItem.querySelector( selector );
			if ( statusEl ) {
				statusEl.className = statusEl.className.replace( /\b(publish|draft|pending|trash)\b/g, '' ).trim();
				statusEl.classList.add( `${ type }-status`, data.status );
				statusEl.textContent = data.status;
				break;
			}
		}
	}

	/**
	 * Update lesson-specific list item data
	 */
	updateLessonListItem( listItem, formData, data ) {
		// Update duration
		const duration = formData._lp_duration || data?.duration || '';
		if ( duration ) {
			this.updateListItemDuration( listItem, duration );
		}

		// Update preview status
		const preview = formData._lp_preview || data?.preview || '';
		this.updateLessonPreview( listItem, preview );
	}

	/**
	 * Update quiz-specific list item data
	 */
	updateQuizListItem( listItem, formData, data ) {
		// Update duration
		const duration = formData._lp_duration || data?.duration || '';
		if ( duration ) {
			this.updateListItemDuration( listItem, duration );
		}

		// Update question count
		const questionCount = data?.question_count || data?.questions_count || null;
		if ( questionCount !== null ) {
			this.updateQuizQuestionCount( listItem, questionCount );
		}

		// Update passing grade
		const passingGrade = formData._lp_passing_grade || data?.passing_grade || '';
		if ( passingGrade ) {
			this.updateQuizPassingGrade( listItem, passingGrade );
		}
	}

	/**
	 * Update question-specific list item data
	 */
	updateQuestionListItem( listItem, formData, data ) {
		// Update question type
		const questionType = formData._lp_type || data?.type || '';
		if ( questionType ) {
			this.updateQuestionType( listItem, questionType );
		}

		// Update mark/point
		const mark = formData._lp_mark || data?.mark || '';
		if ( mark ) {
			this.updateQuestionMark( listItem, mark );
		}
	}

	/**
	 * Update duration in list item
	 */
	updateListItemDuration( listItem, duration ) {
		const durationSelectors = [
			'.item-meta.duration',
			'.duration',
			'.course-item-duration',
			'.meta-duration',
			'[class*="duration"]',
		];

		// Parse duration value and format
		const durationStr = this.formatDuration( duration );

		for ( const selector of durationSelectors ) {
			const durationEl = listItem.querySelector( selector );
			if ( durationEl ) {
				durationEl.textContent = durationStr;
				return;
			}
		}

		// If no duration element exists, try to add one in the right place
		const metaContainer = listItem.querySelector( '.course-item__right, .item-meta-container, .course-item-meta' );
		if ( metaContainer && durationStr ) {
			let durationEl = metaContainer.querySelector( '.duration' );
			if ( ! durationEl ) {
				durationEl = document.createElement( 'span' );
				durationEl.className = 'duration';
				metaContainer.insertBefore( durationEl, metaContainer.firstChild );
			}
			durationEl.textContent = durationStr;
		}
	}

	/**
	 * Format duration value to display string
	 */
	formatDuration( duration ) {
		if ( ! duration ) {
			return '';
		}

		// If already formatted string, return as-is
		if ( typeof duration === 'string' && duration.match( /\d+\s+\w+/ ) ) {
			return duration;
		}

		// Parse "X unit" format
		const parts = String( duration ).trim().split( /\s+/ );
		if ( parts.length >= 2 ) {
			const value = parseInt( parts[ 0 ] ) || 0;
			const unit = parts[ 1 ] || 'minute';

			if ( value === 0 ) {
				return '';
			}

			// Pluralize unit
			const unitMap = {
				minute: value === 1 ? 'Minute' : 'Minutes',
				hour: value === 1 ? 'Hour' : 'Hours',
				day: value === 1 ? 'Day' : 'Days',
				week: value === 1 ? 'Week' : 'Weeks',
			};

			const displayUnit = unitMap[ unit.toLowerCase() ] || unit;
			return `${ value } ${ displayUnit }`;
		}

		// Just a number, assume minutes
		const numValue = parseInt( duration ) || 0;
		if ( numValue > 0 ) {
			return `${ numValue } ${ numValue === 1 ? 'Minute' : 'Minutes' }`;
		}

		return '';
	}

	/**
	 * Update lesson preview status in list item
	 */
	updateLessonPreview( listItem, preview ) {
		const isPreview = preview === 'yes' || preview === true || preview === '1';

		// Update preview icon/button
		const previewSelectors = [
			'.lp-btn-set-preview-item a',
			'.course-item-preview',
			'.item-preview',
			'.preview-item',
			'[class*="preview"]',
		];

		for ( const selector of previewSelectors ) {
			const previewEl = listItem.querySelector( selector );
			if ( previewEl ) {
				if ( isPreview ) {
					previewEl.classList.remove( 'lp-icon-eye-slash' );
					previewEl.classList.add( 'lp-icon-eye' );
				} else {
					previewEl.classList.remove( 'lp-icon-eye' );
					previewEl.classList.add( 'lp-icon-eye-slash' );
				}
				break;
			}
		}

		// Update preview checkbox if exists
		const previewCheckbox = listItem.querySelector( 'input[type="checkbox"].preview-checkbox, input[name*="preview"]' );
		if ( previewCheckbox ) {
			previewCheckbox.checked = isPreview;
		}

		// Toggle preview class on list item
		if ( isPreview ) {
			listItem.classList.add( 'is-preview', 'preview-item' );
		} else {
			listItem.classList.remove( 'is-preview', 'preview-item' );
		}

		// Update status icon in curriculum
		const statusIcon = listItem.querySelector( '.course-item__status .course-item-ico' );
		if ( statusIcon && isPreview ) {
			statusIcon.classList.add( 'preview' );
		} else if ( statusIcon ) {
			statusIcon.classList.remove( 'preview' );
		}
	}

	/**
	 * Update quiz question count in list item
	 */
	updateQuizQuestionCount( listItem, count ) {
		const countSelectors = [
			'.question-count',
			'.count-questions',
			'.item-meta.count-questions',
			'[class*="question-count"]',
		];

		const countText = `${ count } ${ count === 1 ? 'Question' : 'Questions' }`;

		for ( const selector of countSelectors ) {
			const countEl = listItem.querySelector( selector );
			if ( countEl ) {
				countEl.textContent = countText;
				return;
			}
		}

		// If no element exists, try to add one
		const metaContainer = listItem.querySelector( '.course-item__right, .item-meta-container' );
		if ( metaContainer ) {
			let countEl = metaContainer.querySelector( '.question-count' );
			if ( ! countEl ) {
				countEl = document.createElement( 'span' );
				countEl.className = 'question-count';
				metaContainer.appendChild( countEl );
			}
			countEl.textContent = countText;
		}
	}

	/**
	 * Update quiz passing grade in list item
	 */
	updateQuizPassingGrade( listItem, passingGrade ) {
		const gradeSelectors = [
			'.passing-grade',
			'.item-meta.passing-grade',
			'[class*="passing-grade"]',
		];

		for ( const selector of gradeSelectors ) {
			const gradeEl = listItem.querySelector( selector );
			if ( gradeEl ) {
				gradeEl.textContent = `${ passingGrade }%`;
				return;
			}
		}
	}

	/**
	 * Update question type in list item
	 */
	updateQuestionType( listItem, questionType ) {
		const typeSelectors = [
			'.question-type',
			'.item-type',
			'[class*="question-type"]',
		];

		// Update type text
		for ( const selector of typeSelectors ) {
			const typeEl = listItem.querySelector( selector );
			if ( typeEl ) {
				typeEl.textContent = this.formatQuestionType( questionType );
				break;
			}
		}

		// Update type class on list item
		const typeClasses = [ 'true_or_false', 'single_choice', 'multi_choice', 'fill_in_blanks' ];
		typeClasses.forEach( ( cls ) => listItem.classList.remove( cls ) );
		if ( questionType ) {
			listItem.classList.add( questionType );
		}
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
	 * Update question mark/point in list item
	 */
	updateQuestionMark( listItem, mark ) {
		const markSelectors = [
			'.question-mark',
			'.item-mark',
			'.point',
			'[class*="mark"]',
		];

		for ( const selector of markSelectors ) {
			const markEl = listItem.querySelector( selector );
			if ( markEl ) {
				markEl.textContent = mark;
				return;
			}
		}
	}

	/**
	 * Check if popup is open
	 */
	isPopupOpen() {
		return this.popupContainer.classList.contains( 'active' );
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

		console.log( '[BuilderPopup] switchTab called, tabName:', tabName, 'currentType:', this.currentType );

		if ( ! popup || ! tabName ) {
			return;
		}

		// Sync current tab's TinyMCE before switching
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
		if ( targetPane ) {
			targetPane.classList.add( 'active' );
			this.loadTabAssets( tabName, targetPane );

			if ( tabName === 'overview' ) {
				setTimeout( () => {
					this.initTinyMCE();
				}, 100 );
			}

			// Initialize BuilderEditQuiz when switching to questions tab
			if ( tabName === 'questions' && this.currentType === 'quiz' ) {
				console.log( '[BuilderPopup] Questions tab activated for quiz' );
				
				// Trigger AJAX loading for any .lp-load-ajax-element that hasn't loaded yet
				this.triggerAjaxLoadForTab( targetPane );

				this.initQuizQuestionsTab( targetPane );
				
				// Create or reinit BuilderEditQuiz
				if ( ! this.builderEditQuiz ) {
					console.log( '[BuilderPopup] Creating new BuilderEditQuiz instance' );
					this.builderEditQuiz = new BuilderEditQuiz();
				}
				
				// Wait for AJAX content to load before reinit
				console.log( '[BuilderPopup] Calling builderEditQuiz.reinit()' );
				this.builderEditQuiz.reinit( this.popupContainer );
			}

			// Initialize BuilderEditQuestion when switching to settings tab in question popup
			if ( tabName === 'settings' && this.currentType === 'question' ) {
				// Trigger AJAX loading for any .lp-load-ajax-element that hasn't loaded yet
				this.triggerAjaxLoadForTab( targetPane );

				// Create or reinit BuilderEditQuestion
				if ( ! this.builderEditQuestion ) {
					this.builderEditQuestion = new BuilderEditQuestion();
				}
				this.builderEditQuestion.reinit( this.popupContainer );
			}

			// Initialize BuilderMaterial when switching to settings tab in lesson popup
			// Material is a child element inside settings, not a separate tab
			if ( tabName === 'settings' && this.currentType === 'lesson' ) {
				// Trigger AJAX loading for any .lp-load-ajax-element that hasn't loaded yet
				this.triggerAjaxLoadForTab( targetPane );

				// Create or reinit BuilderMaterial
				if ( ! this.builderMaterial ) {
					this.builderMaterial = new BuilderMaterial();
				}
				this.builderMaterial.reinit( this.popupContainer );
			}

			document.dispatchEvent(
				new CustomEvent( 'lp-builder-tab-switched', {
					detail: { tabName, type: this.currentType, id: this.currentId },
				} )
			);
		}
	}

	/**
	 * Trigger AJAX loading for elements within a tab pane
	 * This is needed when popup content is loaded dynamically and 
	 * the normal MutationObserver doesn't detect the new elements
	 */
	triggerAjaxLoadForTab( tabPane ) {
		console.log( '[BuilderPopup] triggerAjaxLoadForTab called' );
		
		if ( ! tabPane || ! window.lpAJAXG ) {
			console.log( '[BuilderPopup] No tabPane or lpAJAXG' );
			return;
		}

		// Find all .lp-load-ajax-element that haven't been loaded yet
		const ajaxElements = tabPane.querySelectorAll( '.lp-load-ajax-element:not(.loaded)' );
		
		console.log( '[BuilderPopup] Found AJAX elements to load:', ajaxElements.length );
		
		if ( ajaxElements.length > 0 ) {
			// Remove 'loaded' class to ensure they get processed
			ajaxElements.forEach( ( el ) => {
				el.classList.remove( 'loaded' );
				console.log( '[BuilderPopup] AJAX element:', el.className );
			} );
			
			// Trigger the AJAX loading
			window.lpAJAXG.getElements();
		}
	}

	/**
	 * Initialize TinyMCE for the current popup type
	 */
	initTinyMCE() {
		const editorId = `${ this.currentType }_description_editor`;
		const textarea = document.getElementById( editorId );

		if ( ! textarea || typeof tinymce === 'undefined' ) {
			return;
		}

		// Destroy existing instance first
		this.destroyTinyMCE( editorId );

		if ( typeof wp !== 'undefined' && wp.editor && wp.editor.initialize ) {
			const settings = {
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
				quicktags: {
					buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close',
				},
				mediaButtons: true,
			};
			wp.editor.initialize( editorId, settings );
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
					'undo redo | formatselect | ' +
					'bold italic backcolor | alignleft aligncenter ' +
					'alignright alignjustify | bullist numlist outdent indent | ' +
					'removeformat | help',
			} );
		}
	}

	/**
	 * Sync TinyMCE content to textarea before save
	 */
	syncTinyMCEContent() {
		this.syncAllTinyMCE();
	}

	/**
	 * Sync all TinyMCE instances in popup
	 */
	syncAllTinyMCE() {
		if ( typeof tinymce === 'undefined' ) {
			return;
		}

		const editorId = `${ this.currentType }_description_editor`;
		const editor = tinymce.get( editorId );

		if ( editor ) {
			editor.save();
		}

		// Sync any additional editors in settings tabs
		tinymce.editors.forEach( ( ed ) => {
			if ( ed.id && ed.id.includes( this.currentType ) ) {
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
		// Clean up wp.editor instance if exists
		if ( typeof wp !== 'undefined' && wp.editor && wp.editor.remove ) {
			wp.editor.remove( editorId );
		}
	}

	/**
	 * Destroy all editors related to popup
	 */
	destroyAllTinyMCE() {
		if ( ! this.currentType ) {
			return;
		}

		const editorId = `${ this.currentType }_description_editor`;
		this.destroyTinyMCE( editorId );

		// Also destroy any other editors that might be in the popup
		if ( typeof tinymce !== 'undefined' ) {
			const editorsToRemove = [];
			tinymce.editors.forEach( ( ed ) => {
				if ( ed.id && this.popupContainer && this.popupContainer.querySelector( `#${ ed.id }` ) ) {
					editorsToRemove.push( ed.id );
				}
			} );
			editorsToRemove.forEach( ( id ) => this.destroyTinyMCE( id ) );
		}
	}

	/**
	 * Handle save action
	 */
	handleSave( saveBtn ) {
		if ( ! this.currentType ) {
			return;
		}

		// Sync TinyMCE content before getting form data
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

		const action = actionMap[ this.currentType ] || `builder_update_${ this.currentType }`;
		const wasNewItem = this.isNewItem;

		const dataSend = {
			...formData,
			action,
			args: {
				id_url: `builder-update-${ this.currentType }`,
			},
			[ `${ this.currentType }_status` ]: 'publish',
			return_html: wasNewItem ? 'yes' : 'no',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;

				lpToastify.show( message, status );

				if ( status === 'success' ) {
					if ( data?.button_title ) {
						saveBtn.textContent = data.button_title;
					}

					// Update status in popup header
					if ( data?.status ) {
						const statusSelector = `.${ this.currentType }-status`;
						const elStatus = this.popupContainer.querySelector( statusSelector );
						if ( elStatus ) {
							elStatus.className = `${ this.currentType }-status ${ data.status }`;
							elStatus.textContent = data.status;
						}
					}

					const newIdKey = `${ this.currentType }_id_new`;
					let newId = null;
					
					if ( data?.[ newIdKey ] ) {
						newId = data[ newIdKey ];
						this.currentId = newId;
						this.isNewItem = false;

						const wrapper = this.popupContainer.querySelector( `[data-${ this.currentType }-id]` );
						if ( wrapper ) {
							wrapper.dataset[ `${ this.currentType }Id` ] = newId;
						}

						// Update popup data attribute
						const popup = this.popupContainer.querySelector( BuilderPopup.selectors.popup );
						if ( popup ) {
							popup.dataset[ `${ this.currentType }Id` ] = newId;
						}
					}

					// Store saved data for list update on close
					this.savedData = {
						formData,
						data,
						wasNewItem,
					};

					if ( wasNewItem ) {
						this.handleItemSaved( {
							type: this.currentType,
							id: this.currentId,
							data,
							formData,
							wasNewItem,
							listItemHtml: data?.list_item_html || null,
						} );

						// Reload popup with new ID to show all tabs (Settings, Questions, etc.)
						if ( newId ) {
							this.reloadPopupAfterCreate( this.currentType, newId );
						}
					}

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
	 * Reload popup after creating new item
	 * This ensures all tabs (Settings, Questions for quiz, etc.) are properly loaded
	 * 
	 * @param {string} type - The item type (lesson, quiz, question)
	 * @param {number} newId - The new item ID after creation
	 */
	reloadPopupAfterCreate( type, newId ) {
		// Small delay to let the save toast show first
		setTimeout( () => {
			// Destroy current TinyMCE instances before reload
			this.destroyAllTinyMCE();
			
			// Reload the popup with the new ID
			this.loadPopup( type, newId );
		}, 300 );
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

		const action = actionMap[ this.currentType ] || `move_trash_${ this.currentType }`;

		const dataSend = {
			action,
			args: {
				id_url: `move-trash-${ this.currentType }`,
			},
			[ `${ this.currentType }_id` ]: this.currentId,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Update save button text
					if ( data?.button_title ) {
						const saveBtn = this.popupContainer.querySelector( BuilderPopup.selectors.saveBtn );
						if ( saveBtn ) {
							saveBtn.textContent = data.button_title;
						}
					}

					// Update status label
					if ( data?.status ) {
						const statusSelector = `.${ this.currentType }-status`;
						const elStatus = this.popupContainer.querySelector( statusSelector );
						if ( elStatus ) {
							elStatus.className = `${ this.currentType }-status ${ data.status }`;
							elStatus.textContent = data.status;
						}
					}

					// Store for list update on close
					this.savedData = {
						formData: this.getFormData(),
						data,
						wasNewItem: false,
					};

					// Trigger event for other components
					document.dispatchEvent(
						new CustomEvent( 'lp-builder-popup-trashed', {
							detail: {
								type: this.currentType,
								id: this.currentId,
								data,
							},
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
	 * Validate form data before save
	 */
	validateFormData( formData ) {
		const errors = [];
		const titleKey = `${ this.currentType }_title`;
		const title = formData[ titleKey ] || '';

		if ( ! title.trim() ) {
			errors.push(
				`${ this.currentType.charAt( 0 ).toUpperCase() + this.currentType.slice( 1 ) } title is required`
			);
		}

		if ( title.length > 200 ) {
			errors.push( 'Title must be less than 200 characters' );
		}

		return {
			valid: errors.length === 0,
			errors,
		};
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

		const titleInput =
			popup.querySelector( 'input[name$="_title"]' ) ||
			popup.querySelector( '#title' ) ||
			popup.querySelector( `#${ this.currentType }_title` );
		if ( titleInput ) {
			data[ `${ this.currentType }_title` ] = titleInput.value;
		}

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

		// Get form settings from all tabs
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
				if ( element.files && element.files.length > 0 ) {
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
	 * Load assets for active tab on initial render
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
	 * Load edit-quiz JS for questions tab functionality
	 */
	loadEditQuizJS() {
		if ( typeof window.EditQuiz !== 'undefined' ) {
			this.reinitEditQuizHandlers();
			return;
		}

		document.dispatchEvent(
			new CustomEvent( 'lp-quiz-questions-tab-ready', {
				detail: {
					quizId: this.currentId,
					container: this.popupContainer,
				},
			} )
		);

		this.reinitEditQuizHandlers();
	}

	/**
	 * Re-initialize edit quiz handlers for popup context
	 */
	reinitEditQuizHandlers() {
		const questionsContainer = this.popupContainer.querySelector( '.lp-edit-quiz-wrap' );
		if ( ! questionsContainer ) {
			return;
		}

		if ( typeof Sortable !== 'undefined' ) {
			const questionsList = questionsContainer.querySelector( '.lp-edit-list-questions' );
			if ( questionsList && ! questionsList.sortableInstance ) {
				questionsList.sortableInstance = Sortable.create( questionsList, {
					animation: 150,
					handle: '.drag, .lp-sortable-handle',
					draggable: '.lp-question-item:not(.clone)',
					onEnd: ( evt ) => {
						this.handleQuestionReorder( evt );
					},
				} );
			}
		}

		questionsContainer.querySelectorAll( '.lp-question-toggle' ).forEach( ( toggle ) => {
			toggle.addEventListener( 'click', ( e ) => {
				const questionItem = e.target.closest( '.lp-question-item' );
				if ( questionItem ) {
					questionItem.classList.toggle( 'lp-collapse' );
				}
			} );
		} );

		questionsContainer.querySelectorAll( '.lp-btn-remove-question' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				this.handleRemoveQuestionFromQuiz( btn );
			} );
		} );
	}

	/**
	 * Handle remove question from quiz
	 */
	handleRemoveQuestionFromQuiz( btn ) {
		const questionItem = btn.closest( '.lp-question-item' );
		if ( ! questionItem ) {
			return;
		}

		const questionId = questionItem.dataset.questionId || questionItem.dataset.id;

		if ( ! confirm( 'Are you sure you want to remove this question from the quiz?' ) ) {
			return;
		}

		lpUtils.lpSetLoadingEl( btn, 1 );

		const dataSend = {
			action: 'builder_remove_quiz_question',
			args: {
				id_url: 'builder-remove-quiz-question',
			},
			quiz_id: this.currentId,
			question_id: questionId,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					questionItem.remove();
					this.updateQuestionCount();
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || 'Failed to remove question', 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( btn, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Update question count after add/remove
	 */
	updateQuestionCount() {
		const questionsContainer = this.popupContainer.querySelector( '.lp-edit-quiz-wrap' );
		if ( ! questionsContainer ) {
			return;
		}

		const questionItems = questionsContainer.querySelectorAll( '.lp-question-item:not(.clone)' );
		const countEl = questionsContainer.querySelector( '.total-items' );

		if ( countEl ) {
			const count = questionItems.length;
			countEl.textContent = `${ count } ${ count === 1 ? 'question' : 'questions' }`;
		}
	}

	/**
	 * Handle question reorder in quiz
	 */
	handleQuestionReorder( evt ) {
		const questionList = evt.from;
		const questionIds = [];

		questionList.querySelectorAll( '[data-question-id]' ).forEach( ( item ) => {
			const qId = item.dataset.questionId;
			if ( qId ) {
				questionIds.push( qId );
			}
		} );

		const dataSend = {
			action: 'builder_reorder_quiz_questions',
			args: {
				id_url: 'builder-reorder-quiz-questions',
			},
			quiz_id: this.currentId,
			question_ids: questionIds,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message } = response;
				if ( status === 'success' ) {
					lpToastify.show( message || 'Questions reordered', 'success' );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || 'Failed to reorder questions', 'error' );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Initialize quiz questions tab
	 */
	initQuizQuestionsTab( tabPane ) {
		if ( ! tabPane ) {
			return;
		}

		const questionList = tabPane.querySelector( '.lp-list-questions, [data-questions-list]' );
		if ( ! questionList ) {
			return;
		}

		if ( typeof Sortable !== 'undefined' && questionList.classList.contains( 'sortable' ) ) {
			Sortable.create( questionList, {
				animation: 150,
				handle: '.question-handle',
				onEnd: ( evt ) => {
					this.handleQuestionReorder( evt );
				},
			} );
		}
	}

	/**
	 * Initialize quiz question handlers
	 */
	initQuizQuestionHandlers() {
		const questionList = this.popupContainer.querySelector( BuilderPopup.selectors.questionList );
		if ( ! questionList ) {
			return;
		}

		questionList.addEventListener( 'click', ( e ) => {
			const questionTrigger = e.target.closest( BuilderPopup.selectors.triggerQuestion );
			if ( questionTrigger ) {
				const questionId = parseInt( questionTrigger.dataset.popupQuestion ) || 0;
				BuilderPopup.open( 'question', questionId );
			}

			const addQuestionBtn = e.target.closest( BuilderPopup.selectors.addNewQuestion );
			if ( addQuestionBtn ) {
				BuilderPopup.open( 'question', 0 );
			}
		} );
	}

	/**
	 * Cleanup tab-specific event listeners
	 */
	cleanupTabEventListeners() {
		if ( this.tabEventListeners ) {
			this.tabEventListeners.forEach( ( { element, handler } ) => {
				if ( element && handler ) {
					element.removeEventListener( 'click', handler );
				}
			} );
			this.tabEventListeners.clear();
		}

		document.querySelectorAll( '[data-tab-asset]' ).forEach( ( el ) => {
			const tabKey = el.dataset.tabAsset;
			if ( tabKey && this.currentType && tabKey.startsWith( this.currentType ) ) {
				el.remove();
			}
		} );

		document.querySelectorAll( '[data-quiz-question-asset]' ).forEach( ( el ) => {
			el.remove();
		} );
	}

	/**
	 * Handle item saved - add to list if new
	 */
	handleItemSaved( detail ) {
		const { type, id, wasNewItem, listItemHtml, formData } = detail;

		if ( ! wasNewItem || ! id ) {
			return;
		}

		const listSelectorMap = {
			lesson: BuilderPopup.selectors.lessonList,
			quiz: BuilderPopup.selectors.quizList,
			question: BuilderPopup.selectors.questionList,
		};

		const listSelector = listSelectorMap[ type ];
		if ( ! listSelector ) {
			return;
		}

		const listContainer = document.querySelector( listSelector );
		if ( ! listContainer ) {
			return;
		}

		if ( listItemHtml ) {
			listContainer.insertAdjacentHTML( 'beforeend', listItemHtml );
		} else {
			const itemHtml = this.createListItemHtml( type, id, formData );
			listContainer.insertAdjacentHTML( 'beforeend', itemHtml );
		}

		document.dispatchEvent(
			new CustomEvent( 'lp-builder-list-updated', {
				detail: { type, id, action: 'added' },
			} )
		);
	}

	/**
	 * Create list item HTML (fallback)
	 */
	createListItemHtml( type, id, formData ) {
		const title = formData[ `${ type }_title` ] || `New ${ type }`;
		const dataAttr = `data-popup-${ type }="${ id }"`;

		return `
			<div class="lp-${ type }-item" data-${ type }-id="${ id }">
				<span class="item-title">${ this.escapeHtml( title ) }</span>
				<div class="item-actions">
					<button type="button" class="btn-edit-${ type }" ${ dataAttr } title="Edit">
						<i class="dashicons dashicons-edit"></i>
					</button>
				</div>
			</div>
		`;
	}

	/**
	 * Escape HTML special characters
	 */
	escapeHtml( text ) {
		const div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
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
