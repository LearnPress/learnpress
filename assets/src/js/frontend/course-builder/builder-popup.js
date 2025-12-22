/**
 * Builder Popup Handler
 * Handles AJAX popup loading for lesson, quiz, and question builders.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

export class BuilderPopup {
	constructor() {
		this.popupContainer = null;
		this.currentType = null;
		this.currentId = null;
		this.isNewItem = false;
		this.init();
	}

	static selectors = {
		popupContainer: '#lp-builder-popup-container',
		popupOverlay: '.lp-builder-popup-overlay',
		popup: '.lp-builder-popup',
		closeBtn: '.lp-builder-popup__close',
		cancelBtn: '.lp-builder-popup__btn--cancel',
		saveBtn: '.lp-builder-popup__btn--save',
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
			if ( e.target.closest( BuilderPopup.selectors.closeBtn ) ||
				 e.target.closest( BuilderPopup.selectors.cancelBtn ) ||
				 e.target.matches( BuilderPopup.selectors.popupOverlay ) ) {
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
		this.isNewItem = ( id === 0 );

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
	 * Render popup HTML
	 */
	renderPopup( html ) {
		this.popupContainer.innerHTML = html;
		this.popupContainer.classList.add( 'active' );
		document.body.classList.add( 'lp-popup-open' );

		this.loadedTabAssets = new Set();
		this.loadActiveTabAssets();

		// Initialize TinyMCE for Overview tab
		const activeTab = this.popupContainer.querySelector( `${ BuilderPopup.selectors.tab }.active` );
		const activeTabName = activeTab?.dataset.tab || 'overview';
		
		if ( activeTabName === 'overview' ) {
            setTimeout(() => this.initTinyMCE(), 50);
		}

		if ( this.currentType === 'quiz' ) {
			this.initQuizQuestionHandlers();
		}

		document.dispatchEvent( new CustomEvent( 'lp-builder-popup-opened', {
			detail: { type: this.currentType, id: this.currentId, isNew: this.isNewItem },
		} ) );
	}

	/**
	 * Close popup
	 */
	closePopup() {
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

		// Trigger custom event
		document.dispatchEvent( new CustomEvent( 'lp-builder-popup-closed', {
			detail: { type: this.currentType, id: this.currentId },
		} ) );

		this.currentType = null;
		this.currentId = null;
		this.isNewItem = false;
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
	
		const targetPane = popup.querySelector( `${ BuilderPopup.selectors.tabPane }[data-tab="${ tabName }"]` );
		if ( targetPane ) {
			targetPane.classList.add( 'active' );
			this.loadTabAssets( tabName, targetPane );
	
			if ( tabName === 'overview' ) {
				setTimeout( () => {
					this.initTinyMCE();
				}, 100 );
			}
	
			if ( tabName === 'questions' && this.currentType === 'quiz' ) {
				setTimeout( () => {
					this.initQuizQuestionsTab( targetPane );
				}, 200 );
			}
	
			document.dispatchEvent( new CustomEvent( 'lp-builder-tab-switched', {
				detail: { tabName, type: this.currentType, id: this.currentId },
			} ) );
		}
	}
	
	 /**
     * NEW: Initialize TinyMCE for the current popup type
     */
	 initTinyMCE() {
        const editorId = `${this.currentType}_description_editor_popup`;
        const textarea = document.getElementById(editorId);

        if (!textarea || typeof tinymce === 'undefined') return;
        this.destroyTinyMCE(editorId);
        if ( typeof wp !== 'undefined' && wp.editor && wp.editor.initialize ) {
            const settings = { 
                tinymce: {
                    wpautop: true,
                    plugins : 'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wplink wptextpattern',
                    toolbar1: 'bold italic underline strikethrough | bullist numlist | blockquote hr | alignleft aligncenter alignright | link unlink | wp_more | spellchecker',
                }, 
                quicktags: true, 
                mediaButtons: true 
            };
            wp.editor.initialize(editorId, settings);
        } 
        else {
            tinymce.init({
                selector: '#' + editorId,
                height: 300,
                menubar: false,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            });
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

		const editorId = `${ this.currentType }_description_editor_popup`;
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
     * NEW: Destroy specific TinyMCE instance
     */
    destroyTinyMCE(editorId) {
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get(editorId);
            if (editor) {
                editor.remove();
            }
        }
        // Clean up wp.editor instance if exists
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.remove) {
            wp.editor.remove(editorId);
        }
    }

    /**
     * NEW: Destroy all editors related to popup
     */
    destroyAllTinyMCE() {
        const editorId = `${this.currentType}_description_editor_popup`;
        this.destroyTinyMCE(editorId);
    }

	/**
	 * Validate form data before save
	 * @returns {Object} { valid: boolean, errors: string[] }
	 */
	validateFormData( formData ) {
		const errors = [];
		const titleKey = `${ this.currentType }_title`;
		const title = formData[ titleKey ] || '';

		if ( ! title.trim() ) {
			errors.push( `${ this.currentType.charAt( 0 ).toUpperCase() + this.currentType.slice( 1 ) } title is required` );
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
	 * Handle save action
	 */
	handleSave( saveBtn ) {
		if ( ! this.currentType ) {
			return;
		}
	
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
	
					const newIdKey = `${ this.currentType }_id_new`;
					if ( data?.[ newIdKey ] ) {
						const newId = data[ newIdKey ];
						this.currentId = newId;
						this.isNewItem = false;
	
						const wrapper = this.popupContainer.querySelector( `[data-${ this.currentType }-id]` );
						if ( wrapper ) {
							wrapper.dataset[ `${ this.currentType }Id` ] = newId;
						}
					}
	
					if ( wasNewItem ) {
						this.handleItemSaved( {
							type: this.currentType,
							id: this.currentId,
							data,
							formData,
							wasNewItem,
							listItemHtml: data?.list_item_html || null,
						} );
					}
	
					document.dispatchEvent( new CustomEvent( 'lp-builder-popup-saved', {
						detail: {
							type: this.currentType,
							id: this.currentId,
							data,
							formData,
							wasNewItem,
							listItemHtml: data?.list_item_html || null,
						},
					} ) );
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
	
		const titleInput = popup.querySelector( 'input[name$="_title"]' ) ||
						   popup.querySelector( '#title' ) ||
						   popup.querySelector( `#${ this.currentType }_title` );
		if ( titleInput ) {
			data[ `${ this.currentType }_title` ] = titleInput.value;
		}

		const editorId = `${ this.currentType }_description_editor_popup`;
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
		const activePane = popup.querySelector( `${ BuilderPopup.selectors.tabPane }[data-tab="${ activeTabName }"]` );

		if ( activePane ) {
			this.loadTabAssets( activeTabName, activePane );
		}
	}

	/**
	 * Load tab-specific assets (CSS/JS)
	 */
	loadTabAssets( tabName, tabPane ) {
		// Skip if already loaded
		const tabKey = `${ this.currentType }-${ tabName }`;
		if ( this.loadedTabAssets.has( tabKey ) ) {
			return;
		}

		// Check for data-tab-assets attribute
		const assetsData = tabPane.dataset.tabAssets;
		if ( ! assetsData ) {
			this.loadedTabAssets.add( tabKey );
			return;
		}

		try {
			const assets = JSON.parse( assetsData );

			// Load CSS files
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

			// Load JS files with callback
			if ( assets.js && Array.isArray( assets.js ) ) {
				const loadScripts = ( scripts, index = 0 ) => {
					if ( index >= scripts.length ) {
						this.loadedTabAssets.add( tabKey );
						this.initTabSpecificHandlers( tabName, tabPane );
						return;
					}

					const jsUrl = scripts[ index ];
					if ( document.querySelector( `script[src="${ jsUrl }"]` ) ) {
						loadScripts( scripts, index + 1 );
						return;
					}

					const script = document.createElement( 'script' );
					script.src = jsUrl;
					script.dataset.tabAsset = tabKey;
					script.onload = () => loadScripts( scripts, index + 1 );
					script.onerror = () => loadScripts( scripts, index + 1 );
					document.head.appendChild( script );
				};

				loadScripts( assets.js );
			} else {
				this.loadedTabAssets.add( tabKey );
				this.initTabSpecificHandlers( tabName, tabPane );
			}

			// Enqueue WordPress scripts/styles if specified
			if ( assets.wp_scripts && Array.isArray( assets.wp_scripts ) ) {
				assets.wp_scripts.forEach( ( handle ) => {
					if ( typeof wp !== 'undefined' && wp.enqueue ) {
						wp.enqueue.script( handle );
					}
				} );
			}

			if ( assets.wp_styles && Array.isArray( assets.wp_styles ) ) {
				assets.wp_styles.forEach( ( handle ) => {
					if ( typeof wp !== 'undefined' && wp.enqueue ) {
						wp.enqueue.style( handle );
					}
				} );
			}
		} catch ( e ) {
			console.warn( `Failed to load assets for tab "${ tabName }":`, e );
			this.loadedTabAssets.add( tabKey );
		}
	}

	/**
	 * Initialize tab-specific event handlers
	 */
	initTabSpecificHandlers( tabName, tabPane ) {
		// Settings tab for questions/quizzes
		if ( tabName === 'settings' ) {
			if ( this.currentType === 'question' ) {
				const typeSelector = tabPane.querySelector( '[name="question_type"]' );
				if ( typeSelector ) {
					this.initQuestionTypeHandler( typeSelector, tabPane );
				}
			}
	
			if ( this.currentType === 'quiz' ) {
				this.initQuizSettingsHandlers( tabPane );
			}
		}
	
		// FIX: Questions tab for quiz
		if ( tabName === 'questions' && this.currentType === 'quiz' ) {
			this.initQuizQuestionsTab( tabPane );
		}
	
		// Trigger custom event for external scripts
		document.dispatchEvent( new CustomEvent( 'lp-builder-tab-assets-loaded', {
			detail: { tabName, type: this.currentType, tabPane },
		} ) );
	}

	/**
	 * Initialize quiz questions tab (for quiz popup)
	 */
	initQuizQuestionsTab( tabPane ) {
		if ( ! tabPane ) {
			return;
		}

		// FIX: Enqueue required styles/scripts for questions list
		if ( typeof lpGlobalSettings !== 'undefined' && lpGlobalSettings.assets ) {
			const questionAssets = lpGlobalSettings.assets.questionList || {};
			
			// Load CSS
			if ( questionAssets.css ) {
				questionAssets.css.forEach( ( cssUrl ) => {
					if ( ! document.querySelector( `link[href="${ cssUrl }"]` ) ) {
						const link = document.createElement( 'link' );
						link.rel = 'stylesheet';
						link.href = cssUrl;
						link.dataset.quizQuestionAsset = 'true';
						document.head.appendChild( link );
					}
				} );
			}

			// Load JS
			if ( questionAssets.js ) {
				questionAssets.js.forEach( ( jsUrl ) => {
					if ( ! document.querySelector( `script[src="${ jsUrl }"]` ) ) {
						const script = document.createElement( 'script' );
						script.src = jsUrl;
						script.dataset.quizQuestionAsset = 'true';
						document.head.appendChild( script );
					}
				} );
			}
		}

		// Initialize question list handlers
		const questionList = tabPane.querySelector( '.lp-list-questions' ) || 
							tabPane.querySelector( '[data-questions-list]' );
		
		if ( ! questionList ) {
			return;
		}

		// FIX: Delegate events for question management
		const handleQuestionClick = ( e ) => {
			// Open question popup
			const questionTrigger = e.target.closest( '[data-popup-question]' );
			if ( questionTrigger ) {
				e.preventDefault();
				const questionId = parseInt( questionTrigger.dataset.popupQuestion ) || 0;
				
				// Open question popup in nested mode
				this.openNestedPopup( 'question', questionId );
				return;
			}

			// Add new question
			const addQuestionBtn = e.target.closest( '[data-add-new-question]' );
			if ( addQuestionBtn ) {
				e.preventDefault();
				this.openNestedPopup( 'question', 0 );
				return;
			}

			// Remove question from quiz
			const removeBtn = e.target.closest( '[data-remove-question]' );
			if ( removeBtn ) {
				e.preventDefault();
				this.handleRemoveQuestion( removeBtn );
				return;
			}
		};

		// Remove old listener if exists
		questionList.removeEventListener( 'click', handleQuestionClick );
		questionList.addEventListener( 'click', handleQuestionClick );

		// Store for cleanup
		if ( ! this.tabEventListeners ) {
			this.tabEventListeners = new Map();
		}
		this.tabEventListeners.set( 'quiz-questions', { element: questionList, handler: handleQuestionClick } );

		// Initialize sortable if available
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
 * Open nested popup (for question inside quiz)
 */
openNestedPopup( type, id ) {
    // Store parent popup state
    const parentPopup = {
        type: this.currentType,
        id: this.currentId,
        html: this.popupContainer.innerHTML,
    };

    // Temporarily save parent state
    this._parentPopupState = parentPopup;

    // Load nested popup
    this.loadPopup( type, id );

    // Listen for nested popup close
    const handleNestedClose = ( e ) => {
        if ( e.detail.type === type ) {
            // Restore parent popup
            if ( this._parentPopupState ) {
                this.currentType = this._parentPopupState.type;
                this.currentId = this._parentPopupState.id;
                this.renderPopup( this._parentPopupState.html );
                delete this._parentPopupState;
            }

            document.removeEventListener( 'lp-builder-popup-closed', handleNestedClose );
        }
    };

    document.addEventListener( 'lp-builder-popup-closed', handleNestedClose );
}

/**
 * Handle question removal from quiz
 */
handleRemoveQuestion( removeBtn ) {
    const questionItem = removeBtn.closest( '.question-item' ) || 
                         removeBtn.closest( '[data-question-id]' );
    
    if ( ! questionItem ) {
        return;
    }

    const questionId = questionItem.dataset.questionId;

    if ( ! confirm( 'Are you sure you want to remove this question from the quiz?' ) ) {
        return;
    }

    lpUtils.lpSetLoadingEl( removeBtn, 1 );

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
            }
        },
        error: ( error ) => {
            lpToastify.show( error.message || 'Failed to remove question', 'error' );
        },
        completed: () => {
            lpUtils.lpSetLoadingEl( removeBtn, 0 );
        },
    };

    window.lpAJAXG.fetchAJAX( dataSend, callBack );
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
 * Cleanup tab-specific event listeners
 */
cleanupTabEventListeners() {
    // Remove tab event listeners
    if ( this.tabEventListeners ) {
        this.tabEventListeners.forEach( ( { element, handler } ) => {
            if ( element && handler ) {
                element.removeEventListener( 'click', handler );
            }
        } );
        this.tabEventListeners.clear();
    }

    // Remove dynamically added tab assets
    document.querySelectorAll( '[data-tab-asset]' ).forEach( ( el ) => {
        const tabKey = el.dataset.tabAsset;
        if ( tabKey && tabKey.startsWith( this.currentType ) ) {
            el.remove();
        }
    } );

    // Remove quiz question assets
    document.querySelectorAll( '[data-quiz-question-asset]' ).forEach( ( el ) => {
        el.remove();
    } );
}

	/**
	 * Initialize question type handler
	 */
	initQuestionTypeHandler( typeSelector, tabPane ) {
		const handleTypeChange = () => {
			const selectedType = typeSelector.value;
			const allTypeSettings = tabPane.querySelectorAll( '[data-question-type]' );
			
			allTypeSettings.forEach( ( setting ) => {
				const types = setting.dataset.questionType.split( ',' );
				setting.style.display = types.includes( selectedType ) ? '' : 'none';
			} );
		};

		typeSelector.removeEventListener( 'change', handleTypeChange );
		typeSelector.addEventListener( 'change', handleTypeChange );
		handleTypeChange(); // Initial state
	}

	/**
	 * Initialize quiz settings handlers
	 */
	initQuizSettingsHandlers( tabPane ) {
		// Handle quiz duration toggle
		const durationToggle = tabPane.querySelector( '[name="duration_enable"]' );
		if ( durationToggle ) {
			const handleDurationToggle = () => {
				const durationFields = tabPane.querySelector( '.quiz-duration-fields' );
				if ( durationFields ) {
					durationFields.style.display = durationToggle.checked ? '' : 'none';
				}
			};

			durationToggle.removeEventListener( 'change', handleDurationToggle );
			durationToggle.addEventListener( 'change', handleDurationToggle );
			handleDurationToggle();
		}
	}

	/**
	 * Cleanup tab-specific event listeners
	 */
	cleanupTabEventListeners() {
		// Remove dynamically added tab assets
		document.querySelectorAll( '[data-tab-asset]' ).forEach( ( el ) => {
			const tabKey = el.dataset.tabAsset;
			if ( tabKey && tabKey.startsWith( this.currentType ) ) {
				el.remove();
			}
		} );
	}

	/**
	 * Load popup-specific assets (CSS/JS for quiz questions, etc.)
	 */
	loadPopupAssets() {
		const popup = this.popupContainer.querySelector( BuilderPopup.selectors.popup );
		if ( ! popup ) {
			return;
		}

		// Check if popup has data attributes for additional assets
		const assetsData = popup.dataset.popupAssets;
		if ( ! assetsData ) {
			return;
		}

		try {
			const assets = JSON.parse( assetsData );

			// Load CSS files
			if ( assets.css && Array.isArray( assets.css ) ) {
				assets.css.forEach( ( cssUrl ) => {
					if ( ! document.querySelector( `link[href="${ cssUrl }"]` ) ) {
						const link = document.createElement( 'link' );
						link.rel = 'stylesheet';
						link.href = cssUrl;
						document.head.appendChild( link );
					}
				} );
			}

			// Load JS files
			if ( assets.js && Array.isArray( assets.js ) ) {
				assets.js.forEach( ( jsUrl ) => {
					if ( ! document.querySelector( `script[src="${ jsUrl }"]` ) ) {
						const script = document.createElement( 'script' );
						script.src = jsUrl;
						script.async = false;
						document.head.appendChild( script );
					}
				} );
			}
		} catch ( e ) {
			console.warn( 'Failed to parse popup assets data:', e );
		}
	}


	/**
	 * Initialize quiz question handlers (for managing questions in quiz popup)
	 */
	initQuizQuestionHandlers() {
		const questionList = this.popupContainer.querySelector( BuilderPopup.selectors.questionList );
		if ( ! questionList ) {
			return;
		}

		// Delegate event for opening question popups from quiz
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
	 * Handle item saved - add to list if new
	 */
	handleItemSaved( detail ) {
		const { type, id, wasNewItem, listItemHtml, formData } = detail;

		if ( ! wasNewItem || ! id ) {
			return;
		}

		// Get list container based on type
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

		// If backend returned HTML, use it
		if ( listItemHtml ) {
			listContainer.insertAdjacentHTML( 'beforeend', listItemHtml );
		} else {
			// Fallback: create basic HTML element
			const itemHtml = this.createListItemHtml( type, id, formData );
			listContainer.insertAdjacentHTML( 'beforeend', itemHtml );
		}

		// Trigger event for other components
		document.dispatchEvent( new CustomEvent( 'lp-builder-list-updated', {
			detail: { type, id, action: 'added' },
		} ) );
	}

	/**
	 * Create list item HTML (fallback if backend doesn't return HTML)
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
