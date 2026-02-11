import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { BuilderEditQuiz } from './builder-edit-quiz.js';

/**
 * Builder Standalone Quiz Handler
 * Handles standalone quiz edit page (not popup)
 * 
 * @since 4.3.0
 */
export class BuilderStandaloneQuiz {
	constructor() {
		// BuilderEditQuiz instance for handling Question tab
		this.builderEditQuiz = null;
		// Events use document-level event delegation, so always register them
		// The page context check happens in individual handlers via target.closest()
		this.init();
	}

	static selectors = {
		// Context selector - indicates we're on quiz edit page
		elDataQuiz: '.cb-section__quiz-edit',
		// Shared header action buttons (generic selectors)
		elBtnMainAction: '.cb-btn-main-action',
		elBtnUpdate: '.cb-btn-update, .cb-btn-publish',
		elBtnDraft: '.cb-btn-darft, .cb-dropdown-item[data-status="draft"]',
		elBtnTrash: '.cb-btn-trash',
		// Status badge
		elQuizStatus: '.quiz-status, .quizze-status',
		// Form fields
		idTitle: 'title',
		idDescEditor: 'quiz_description_editor',
		elFormSetting: '.lp-form-setting-quiz',
		// Tab handling selectors
		elCBHorizontalTabs: '.lp-cb-tabs__item',
		elCBTabPanels: '.lp-cb-tab-panel',
		// Dropdown selectors
		elDropdownToggle: '.cb-btn-dropdown-toggle',
		elDropdownMenu: '.cb-dropdown-menu',
		elDropdownItem: '.cb-dropdown-item',
		elHeaderActionsDropdown: '.cb-header-actions-dropdown',
	};

	init() {
		this.initTabs();
		this.initHeaderActionsDropdown();
		this.initQuestionTabHandler();
		this.events();
	}

	/**
	 * Initialize Question tab handler
	 * Listen for AJAX completed to initialize BuilderEditQuiz when Question tab content loads
	 */
	initQuestionTabHandler() {
		// Only run on quiz edit page context
		if ( ! this.isQuizContext() ) {
			return;
		}

		// Listen for WordPress lp-ajax-completed hook when AJAX content loads
		if ( typeof wp !== 'undefined' && wp.hooks ) {
			wp.hooks.addAction( 'lp-ajax-completed', 'lp-course-builder', ( element, dataSend ) => {
				// Check if this is the quiz question tab content
				if ( ! dataSend?.callback?.method || dataSend.callback.method !== 'render_edit_quiz' ) {
					return;
				}

				// Initialize BuilderEditQuiz for the loaded content
				this.initBuilderEditQuiz( element );
			} );
		}
	}

	/**
	 * Initialize BuilderEditQuiz for question editing functionality
	 * @param {HTMLElement} container - The container element where AJAX content was loaded
	 */
	initBuilderEditQuiz( container = null ) {
		if ( ! this.builderEditQuiz ) {
			this.builderEditQuiz = new BuilderEditQuiz();
		}

		// Reinitialize for the new content
		this.builderEditQuiz.reinit( container );
	}

	/**
	 * Initialize header actions dropdown (toggle behavior)
	 */
	initHeaderActionsDropdown() {
		// Close dropdown when clicking outside
		document.addEventListener( 'click', ( e ) => {
			const dropdown = document.querySelector( BuilderStandaloneQuiz.selectors.elHeaderActionsDropdown );
			if ( dropdown && ! dropdown.contains( e.target ) ) {
				const menu = dropdown.querySelector( BuilderStandaloneQuiz.selectors.elDropdownMenu );
				const toggle = dropdown.querySelector( BuilderStandaloneQuiz.selectors.elDropdownToggle );
				if ( menu ) {
					menu.classList.remove( 'is-open' );
				}
				if ( toggle ) {
					toggle.setAttribute( 'aria-expanded', 'false' );
				}
			}
		} );
	}

	/**
	 * Handle dropdown toggle click
	 */
	handleDropdownToggle( args ) {
		const { target } = args;
		const toggleBtn = target.closest( BuilderStandaloneQuiz.selectors.elDropdownToggle );
		
		if ( ! toggleBtn ) {
			return;
		}

		const dropdown = toggleBtn.closest( BuilderStandaloneQuiz.selectors.elHeaderActionsDropdown );
		if ( ! dropdown ) {
			return;
		}

		const menu = dropdown.querySelector( BuilderStandaloneQuiz.selectors.elDropdownMenu );
		if ( menu ) {
			menu.classList.toggle( 'is-open' );
			const isOpen = menu.classList.contains( 'is-open' );
			toggleBtn.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		}
	}

	/**
	 * Handle dropdown item click (Save Draft, Publish from dropdown menu)
	 */
	handleDropdownItemClick( args ) {
		// Context check: only handle if on quiz edit page
		if ( ! this.isQuizContext() ) {
			return;
		}

		const { target } = args;
		const dropdownItem = target.closest( BuilderStandaloneQuiz.selectors.elDropdownItem );
		
		if ( ! dropdownItem ) {
			return;
		}

		// Skip if this is trash button - it has its own handler
		if ( dropdownItem.classList.contains( 'cb-btn-trash' ) ) {
			return;
		}

		const status = dropdownItem.dataset.status;
		if ( ! status ) {
			return;
		}

		// Close the dropdown menu
		const menu = dropdownItem.closest( BuilderStandaloneQuiz.selectors.elDropdownMenu );
		if ( menu ) {
			menu.classList.remove( 'is-open' );
		}

		// Save with the specified status
		this.saveQuizWithStatus( dropdownItem, status );
	}

	/**
	 * Save quiz with specified status (publish/draft)
	 * @param {HTMLElement} btnEl - The button element that was clicked
	 * @param {string} status - The status to save (publish/draft)
	 */
	saveQuizWithStatus( btnEl, status ) {
		// Validate title before saving
		if ( ! this.validateTitleBeforeUpdate() ) {
			return;
		}

		lpUtils.lpSetLoadingEl( btnEl, 1 );

		const quizData = this.getQuizDataForUpdate();

		const dataSend = {
			...quizData,
			action: 'builder_update_quiz',
			args: {
				id_url: 'builder-update-quiz',
			},
			quiz_status: status,
		};

		const callBack = {
			success: ( response ) => {
				const { status: respStatus, message, data } = response;
				lpToastify.show( message, respStatus );

				if ( respStatus === 'success' ) {
					// Update action button text
					this.updateActionButtons( data?.status || status );

					if ( data?.quiz_id_new ) {
						const currentUrl = window.location.href;
						window.location.href = currentUrl.replace( /post-new\/?/, `${ data.quiz_id_new }/` );
					}

					if ( data?.status ) {
						const elStatus = document.querySelector( BuilderStandaloneQuiz.selectors.elQuizStatus );
						if ( elStatus ) {
							elStatus.className = 'quizze-status ' + data.status;
							elStatus.textContent = data.status;
						}
					}

					// Reset form state to prevent "leave site" warning
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( btnEl, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Initialize horizontal tabs for client-side tab switching
	 */
	initTabs() {
		const tabs = document.querySelectorAll( BuilderStandaloneQuiz.selectors.elCBHorizontalTabs );
		if ( tabs.length === 0 ) {
			return;
		}

		// Activate first tab by default if none is active
		const activeTab = document.querySelector( `${ BuilderStandaloneQuiz.selectors.elCBHorizontalTabs }.is-active` );
		if ( ! activeTab && tabs.length > 0 ) {
			tabs[0].classList.add( 'is-active' );
			const section = tabs[0].getAttribute( 'data-tab-section' );
			if ( section ) {
				const panel = document.querySelector( `${ BuilderStandaloneQuiz.selectors.elCBTabPanels }[data-section="${ section }"]` );
				if ( panel ) {
					panel.classList.remove( 'lp-hidden' );
				}
			}
		}
	}

	/**
	 * Handle horizontal tab click for client-side tab switching
	 */
	handleTabClick( args ) {
		const { e, target } = args;
		const tabLink = target.closest( BuilderStandaloneQuiz.selectors.elCBHorizontalTabs );
		
		if ( ! tabLink ) {
			return;
		}

		e.preventDefault();

		const section = tabLink.getAttribute( 'data-tab-section' );
		if ( ! section ) {
			return;
		}

		// Update active tab
		const allTabs = document.querySelectorAll( BuilderStandaloneQuiz.selectors.elCBHorizontalTabs );
		allTabs.forEach( tab => tab.classList.remove( 'is-active' ) );
		tabLink.classList.add( 'is-active' );

		// Show/hide panels
		const allPanels = document.querySelectorAll( BuilderStandaloneQuiz.selectors.elCBTabPanels );
		allPanels.forEach( panel => {
			if ( panel.getAttribute( 'data-section' ) === section ) {
				panel.classList.remove( 'lp-hidden' );
			} else {
				panel.classList.add( 'lp-hidden' );
			}
		} );
	}

	events() {
		if ( BuilderStandaloneQuiz._loadedEvents ) {
			return;
		}
		BuilderStandaloneQuiz._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderStandaloneQuiz.selectors.elBtnMainAction,
				class: this,
				callBack: this.updateQuiz.name,
			},
			{
				selector: BuilderStandaloneQuiz.selectors.elBtnTrash,
				class: this,
				callBack: this.trashQuiz.name,
			},
			{
				selector: BuilderStandaloneQuiz.selectors.elCBHorizontalTabs,
				class: this,
				callBack: this.handleTabClick.name,
			},
			{
				selector: BuilderStandaloneQuiz.selectors.elDropdownToggle,
				class: this,
				callBack: this.handleDropdownToggle.name,
			},
			{
				selector: BuilderStandaloneQuiz.selectors.elDropdownItem,
				class: this,
				callBack: this.handleDropdownItemClick.name,
			},
		] );
	}

	/**
	 * Validate title is not empty before update
	 * @return {boolean} True if valid, false if invalid
	 */
	validateTitleBeforeUpdate() {
		const titleInput = document.getElementById( BuilderStandaloneQuiz.selectors.idTitle );
		if ( ! titleInput ) {
			return true;
		}

		const title = titleInput.value.trim();
		if ( ! title ) {
			lpToastify.show( 'Quiz title is required.', 'error' );
			titleInput.focus();
			return false;
		}
		return true;
	}

	getQuizDataForUpdate() {
		const data = {};

		const wrapperEl = document.querySelector( BuilderStandaloneQuiz.selectors.elDataQuiz );
		data.quiz_id = wrapperEl ? parseInt( wrapperEl.dataset.quizId ) || 0 : 0;

		const titleInput = document.getElementById( BuilderStandaloneQuiz.selectors.idTitle );
		data.quiz_title = titleInput ? titleInput.value : '';

		const descEditor = document.getElementById( BuilderStandaloneQuiz.selectors.idDescEditor );
		data.quiz_description = descEditor ? descEditor.value : '';

		if ( typeof tinymce !== 'undefined' ) {
			const editor = tinymce.get( BuilderStandaloneQuiz.selectors.idDescEditor );
			if ( editor ) {
				data.quiz_description = editor.getContent();
			}
		}

		const elFormSetting = document.querySelector( BuilderStandaloneQuiz.selectors.elFormSetting );

		if ( elFormSetting ) {
			data.quiz_settings = true;
			const formElements = elFormSetting.querySelectorAll( 'input, select, textarea' );

			formElements.forEach( ( element ) => {
				const name = element.name || element.id;

				if ( ! name ) {
					return;
				}

				if ( name === 'learnpress_meta_box_nonce' || name === '_wp_http_referer' ) {
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

	/**
	 * Update action buttons after status change, matching course edit logic.
	 * Updates both main button and dropdown items based on new status.
	 * @param {string} newStatus - The new quiz status
	 */
	updateActionButtons( newStatus ) {
		const dropdown = document.querySelector( BuilderStandaloneQuiz.selectors.elHeaderActionsDropdown );
		if ( ! dropdown ) return;

		const mainBtn = dropdown.querySelector( '.cb-btn-main-action' );
		const dropdownMenu = dropdown.querySelector( BuilderStandaloneQuiz.selectors.elDropdownMenu );
		if ( ! mainBtn || ! dropdownMenu ) return;

		// Status configuration for button labels and classes
		const statusConfig = {
			publish: {
				mainLabel: mainBtn.dataset.titleUpdate || 'Update',
				mainClass: 'cb-btn-update',
				mainStatus: 'publish',
				dropdownLabel: mainBtn.dataset.titleDraft || 'Save Draft',
				dropdownClass: 'cb-btn-darft',
				dropdownStatus: 'draft',
				dropdownIcon: 'dashicons-media-default',
			},
			draft: {
				mainLabel: mainBtn.dataset.titleDraft || 'Save Draft',
				mainClass: 'cb-btn-darft',
				mainStatus: 'draft',
				dropdownLabel: mainBtn.dataset.titlePublish || 'Publish',
				dropdownClass: 'cb-btn-publish',
				dropdownStatus: 'publish',
				dropdownIcon: 'dashicons-visibility',
			},
			pending: {
				mainLabel: 'Submit for Review',
				mainClass: 'cb-btn-pending',
				mainStatus: 'pending',
				dropdownLabel: mainBtn.dataset.titleDraft || 'Save Draft',
				dropdownClass: 'cb-btn-darft',
				dropdownStatus: 'draft',
				dropdownIcon: 'dashicons-media-default',
			},
			trash: {
				mainLabel: mainBtn.dataset.titleDraft || 'Save Draft',
				mainClass: 'cb-btn-darft',
				mainStatus: 'draft',
				dropdownLabel: mainBtn.dataset.titlePublish || 'Publish',
				dropdownClass: 'cb-btn-publish',
				dropdownStatus: 'publish',
				dropdownIcon: 'dashicons-visibility',
			},
		};

		const config = statusConfig[ newStatus ] || statusConfig.draft;

		// Update main button
		mainBtn.className = `${ config.mainClass } cb-btn-primary cb-btn-main-action`;
		mainBtn.dataset.status = config.mainStatus;
		mainBtn.textContent = config.mainLabel;

		// Update dropdown item (first item, excluding trash)
		const dropdownItems = dropdownMenu.querySelectorAll( '.cb-dropdown-item:not(.cb-btn-trash)' );
		if ( dropdownItems.length > 0 ) {
			const firstItem = dropdownItems[ 0 ];
			firstItem.className = `cb-dropdown-item ${ config.dropdownClass }`;
			firstItem.dataset.status = config.dropdownStatus;
			firstItem.innerHTML = `<span class="dashicons ${ config.dropdownIcon }"></span>${ config.dropdownLabel }`;
		}

		// Update dropdown data-current-status
		dropdown.dataset.currentStatus = newStatus;
	}

	/**
	 * Check if we're in quiz edit context
	 * @return {boolean}
	 */
	isQuizContext() {
		return !! document.querySelector( BuilderStandaloneQuiz.selectors.elDataQuiz );
	}

	updateQuiz( args ) {
		// Context check: only handle if on quiz edit page
		if ( ! this.isQuizContext() ) {
			return;
		}

		const { target } = args;
		const elBtnUpdateQuiz = target.closest( BuilderStandaloneQuiz.selectors.elBtnMainAction );

		if ( ! elBtnUpdateQuiz ) {
			return;
		}

		// Validate title before update
		if ( ! this.validateTitleBeforeUpdate() ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnUpdateQuiz, 1 );

		// Get status from the button's data-status attribute
		const targetStatus = elBtnUpdateQuiz.dataset.status || 'publish';

		const quizData = this.getQuizDataForUpdate();

		const dataSend = {
			...quizData,
			action: 'builder_update_quiz',
			args: {
				id_url: 'builder-update-quiz',
			},
			quiz_status: targetStatus,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Update action buttons with actual status from server
					this.updateActionButtons( data?.status || targetStatus );

					if ( data?.quiz_id_new ) {
						const currentUrl = window.location.href;
						window.location.href = currentUrl.replace( /post-new\/?/, `${ data.quiz_id_new }/` );
					}

					if ( data?.status ) {
						const elStatus = document.querySelector( BuilderStandaloneQuiz.selectors.elQuizStatus );
						if ( elStatus ) {
							elStatus.className = 'quizze-status ' + data.status;
							elStatus.textContent = data.status;
						}
					}

					// Reset form state to prevent "leave site" warning
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );
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

	saveDraftQuiz( args ) {
		// Context check: only handle if on quiz edit page
		if ( ! this.isQuizContext() ) {
			return;
		}

		const { target } = args;
		const elBtnDraftQuiz = target.closest( BuilderStandaloneQuiz.selectors.elBtnDraft );

		if ( ! elBtnDraftQuiz ) {
			return;
		}

		// Validate title before saving draft
		if ( ! this.validateTitleBeforeUpdate() ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnDraftQuiz, 1 );

		const quizData = this.getQuizDataForUpdate();

		const dataSend = {
			...quizData,
			action: 'builder_update_quiz',
			args: {
				id_url: 'builder-update-quiz',
			},
			quiz_status: 'draft',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Update action button text
					this.updateActionButtons( 'draft' );

					if ( data?.quiz_id_new ) {
						const currentUrl = window.location.href;
						window.location.href = currentUrl.replace( /post-new\/?/, `${ data.quiz_id_new }/` );
					}

						if ( data?.status ) {
						const elStatus = document.querySelector( BuilderStandaloneQuiz.selectors.elQuizStatus );
						if ( elStatus ) {
							elStatus.className = 'quizze-status ' + data.status;
							elStatus.textContent = data.status;
						}
					}

					// Reset form state to prevent "leave site" warning
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnDraftQuiz, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	trashQuiz( args ) {
		// Context check: only handle if on quiz edit page
		if ( ! this.isQuizContext() ) {
			return;
		}

		const { target } = args;
		const elBtnTrashQuiz = target.closest( BuilderStandaloneQuiz.selectors.elBtnTrash );

		if ( ! elBtnTrashQuiz ) {
			return;
		}

		if ( ! confirm( 'Are you sure you want to trash this quiz?' ) ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnTrashQuiz, 1 );

		const wrapperEl = document.querySelector( BuilderStandaloneQuiz.selectors.elDataQuiz );
		const quizId = wrapperEl ? parseInt( wrapperEl.dataset.quizId ) || 0 : 0;

		const dataSend = {
			quiz_id: quizId,
			action: 'move_trash_quiz',
			args: {
				id_url: 'builder-trash-quiz',
			},
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Update status badge and action buttons if trash was successful
					if ( data?.status ) {
						const elStatus = document.querySelector( BuilderStandaloneQuiz.selectors.elQuizStatus );
						if ( elStatus ) {
							elStatus.className = 'quizze-status ' + data.status;
							elStatus.textContent = data.status;
						}
						// Update action buttons to show correct state for trash status
						this.updateActionButtons( data.status );
					}

					// Redirect if URL is provided, otherwise stay on page with updated UI
					if ( data?.redirect_url ) {
						window.location.href = data.redirect_url;
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
