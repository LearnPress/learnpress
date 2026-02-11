import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { EditQuestion } from 'lpAssetsJsPath/admin/edit-question.js';

export class BuilderEditQuestion {
	constructor() {
		this.editQuestion = null;
		// Events use document-level event delegation, so always register them
		// The page context check happens in individual handlers via target.closest()
		this.init();
	}

	static selectors = {
		elDataQuestion: '.cb-section__question-edit',
		elBtnUpdateQuestion: '.cb-btn-update',
		elBtnDraftQuestion: '.cb-btn-draft',
		elBtnTrashQuestion: '.cb-btn-trash',
		elBtnMainAction: '.cb-btn-main-action',
		elQuestionStatus: '.question-status',
		idTitle: 'title',
		idDescEditor: 'question_description_editor',
		elFormSetting: '.lp-form-setting-question',
		// Question edit selectors
		elEditQuestionWrap: '.lp-edit-question-wrap',
		elQuestionEditMain: '.lp-question-edit-main',
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
		this.initQuestionAnswersSettings();
		this.initTabs();
		this.initHeaderActionsDropdown();
		this.events();
	}

	/**
	 * Initialize header actions dropdown (toggle behavior)
	 */
	initHeaderActionsDropdown() {
		// Close dropdown when clicking outside
		document.addEventListener( 'click', ( e ) => {
			const dropdown = document.querySelector( BuilderEditQuestion.selectors.elHeaderActionsDropdown );
			if ( dropdown && ! dropdown.contains( e.target ) ) {
				const menu = dropdown.querySelector( BuilderEditQuestion.selectors.elDropdownMenu );
				const toggle = dropdown.querySelector( BuilderEditQuestion.selectors.elDropdownToggle );
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
		const toggleBtn = target.closest( BuilderEditQuestion.selectors.elDropdownToggle );
		
		if ( ! toggleBtn ) {
			return;
		}

		const dropdown = toggleBtn.closest( BuilderEditQuestion.selectors.elHeaderActionsDropdown );
		if ( ! dropdown ) {
			return;
		}

		const menu = dropdown.querySelector( BuilderEditQuestion.selectors.elDropdownMenu );
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
		// Context check: only handle if on question edit page
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { target } = args;
		const dropdownItem = target.closest( BuilderEditQuestion.selectors.elDropdownItem );
		
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
		const menu = dropdownItem.closest( BuilderEditQuestion.selectors.elDropdownMenu );
		if ( menu ) {
			menu.classList.remove( 'is-open' );
		}

		// Save with the specified status
		this.saveQuestionWithStatus( dropdownItem, status );
	}

	/**
	 * Save question with specified status (publish/draft)
	 * @param {HTMLElement} btnEl - The button element that was clicked
	 * @param {string} status - The status to save (publish/draft)
	 */
	saveQuestionWithStatus( btnEl, status ) {
		// Validate title before saving
		if ( ! this.validateTitleBeforeUpdate() ) {
			return;
		}

		lpUtils.lpSetLoadingEl( btnEl, 1 );

		const questionData = this.getQuestionDataForUpdate();

		const dataSend = {
			...questionData,
			action: 'builder_update_question',
			args: {
				id_url: 'builder-update-question',
			},
			question_status: status,
		};

		if ( typeof lpQuestionBuilder !== 'undefined' && lpQuestionBuilder.nonce ) {
			dataSend.nonce = lpQuestionBuilder.nonce;
		}

		const callBack = {
			success: ( response ) => {
				const { status: respStatus, message, data } = response;
				lpToastify.show( message, respStatus );

				if ( respStatus === 'success' ) {
					// Update action button text
					this.updateActionButtons( data?.status || status );

					if ( data?.question_id_new ) {
						const currentUrl = window.location.href;
						window.location.href = currentUrl.replace( /post-new\/?/, `${ data.question_id_new }/` );
					}

					if ( data?.status ) {
						const elStatus = document.querySelector( BuilderEditQuestion.selectors.elQuestionStatus );
						if ( elStatus ) {
							elStatus.className = 'question-status ' + data.status;
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
		const tabs = document.querySelectorAll( BuilderEditQuestion.selectors.elCBHorizontalTabs );
		if ( tabs.length === 0 ) {
			return;
		}

		// Activate first tab by default if none is active
		const activeTab = document.querySelector( `${ BuilderEditQuestion.selectors.elCBHorizontalTabs }.is-active` );
		if ( ! activeTab && tabs.length > 0 ) {
			tabs[0].classList.add( 'is-active' );
			const section = tabs[0].getAttribute( 'data-tab-section' );
			if ( section ) {
				const panel = document.querySelector( `${ BuilderEditQuestion.selectors.elCBTabPanels }[data-section="${ section }"]` );
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
		const tabLink = target.closest( BuilderEditQuestion.selectors.elCBHorizontalTabs );
		
		if ( ! tabLink ) {
			return;
		}

		e.preventDefault();

		const section = tabLink.getAttribute( 'data-tab-section' );
		if ( ! section ) {
			return;
		}

		// Update active tab
		const allTabs = document.querySelectorAll( BuilderEditQuestion.selectors.elCBHorizontalTabs );
		allTabs.forEach( tab => tab.classList.remove( 'is-active' ) );
		tabLink.classList.add( 'is-active' );

		// Show/hide panels
		const allPanels = document.querySelectorAll( BuilderEditQuestion.selectors.elCBTabPanels );
		allPanels.forEach( panel => {
			if ( panel.getAttribute( 'data-section' ) === section ) {
				panel.classList.remove( 'lp-hidden' );
			} else {
				panel.classList.add( 'lp-hidden' );
			}
		} );
	}

	/**
	 * Initialize Question Answers Settings
	 * This will init EditQuestion class for the question answer management
	 */
	initQuestionAnswersSettings() {
		lpUtils.lpOnElementReady(
			BuilderEditQuestion.selectors.elQuestionEditMain,
			( elQuestionEditMain ) => {
				// Initialize EditQuestion for question answer editing
				if ( ! this.editQuestion ) {
					this.editQuestion = new EditQuestion();
					this.editQuestion.init();
				}

				// Init sortable for question answers
				if ( this.editQuestion ) {
					this.editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
				}
			}
		);
	}

	/**
	 * Re-initialize when question type changes
	 */
	reinitQuestionHandlers( elQuestionEditMain ) {
		if ( this.editQuestion && elQuestionEditMain ) {
			this.editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
			this.editQuestion.initTinyMCE();
		}
	}

	/**
	 * Re-initialize for popup context
	 * This is called when popup is opened multiple times to ensure
	 * TinyMCE and other handlers are properly re-initialized
	 * 
	 * @param {HTMLElement} container - The popup container element
	 */
	reinit( container ) {
		const elQuestionEditMain = container 
			? container.querySelector( BuilderEditQuestion.selectors.elQuestionEditMain )
			: document.querySelector( BuilderEditQuestion.selectors.elQuestionEditMain );

		if ( ! elQuestionEditMain ) {
			return;
		}

		// Re-create EditQuestion instance to ensure fresh initialization
		// This is necessary because TinyMCE instances were destroyed when popup closed
		if ( this.editQuestion ) {
			// Destroy existing TinyMCE instances in the container first
			if ( typeof tinymce !== 'undefined' && container ) {
				const textareas = container.querySelectorAll( 'textarea.lp-meta-box__editor' );
				textareas.forEach( ( textarea ) => {
					const editorId = textarea.id;
					if ( editorId ) {
						const editor = tinymce.get( editorId );
						if ( editor ) {
							editor.remove();
						}
						if ( typeof wp !== 'undefined' && wp.editor && wp.editor.remove ) {
							wp.editor.remove( editorId );
						}
					}
				} );
			}
		}

		// Create fresh EditQuestion instance
		this.editQuestion = new EditQuestion();
		this.editQuestion.init();

		// Re-init sortable and TinyMCE
		this.editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
		
		// Use setTimeout to ensure DOM is ready for TinyMCE
		setTimeout( () => {
			if ( this.editQuestion ) {
				this.editQuestion.initTinyMCE();
			}
		}, 100 );
	}

	events() {
		if ( BuilderEditQuestion._loadedEvents ) {
			return;
		}
		BuilderEditQuestion._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderEditQuestion.selectors.elBtnUpdateQuestion,
				class: this,
				callBack: this.updateQuestion.name,
			},
			{
				selector: BuilderEditQuestion.selectors.elBtnDraftQuestion,
				class: this,
				callBack: this.saveDraftQuestion.name,
			},
			{
				selector: BuilderEditQuestion.selectors.elBtnTrashQuestion,
				class: this,
				callBack: this.trashQuestion.name,
			},
			{
				selector: BuilderEditQuestion.selectors.elCBHorizontalTabs,
				class: this,
				callBack: this.handleTabClick.name,
			},
			{
				selector: BuilderEditQuestion.selectors.elDropdownToggle,
				class: this,
				callBack: this.handleDropdownToggle.name,
			},
			{
				selector: BuilderEditQuestion.selectors.elDropdownItem,
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
		const titleInput = document.getElementById( BuilderEditQuestion.selectors.idTitle );
		if ( ! titleInput ) {
			return true;
		}

		const title = titleInput.value.trim();
		if ( ! title ) {
			lpToastify.show( 'Question title is required.', 'error' );
			titleInput.focus();
			return false;
		}
		return true;
	}

	/**
	 * Update action buttons after status change, matching course edit logic.
	 * Updates both main button and dropdown items based on new status.
	 * @param {string} newStatus - The new question status
	 */
	updateActionButtons( newStatus ) {
		const dropdown = document.querySelector( BuilderEditQuestion.selectors.elHeaderActionsDropdown );
		if ( ! dropdown ) return;

		const mainBtn = dropdown.querySelector( '.cb-btn-main-action' );
		const dropdownMenu = dropdown.querySelector( BuilderEditQuestion.selectors.elDropdownMenu );
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
	 * Check if we're in question edit context
	 * @return {boolean}
	 */
	isQuestionContext() {
		return !! document.querySelector( BuilderEditQuestion.selectors.elDataQuestion );
	}

	getQuestionDataForUpdate() {
		const data = {};

		const wrapperEl = document.querySelector( BuilderEditQuestion.selectors.elDataQuestion );

		data.question_id = wrapperEl ? parseInt( wrapperEl.dataset.questionId ) || 0 : 0;

		const titleInput = document.getElementById( BuilderEditQuestion.selectors.idTitle );
		data.question_title = titleInput ? titleInput.value : '';

		const descEditor = document.getElementById( BuilderEditQuestion.selectors.idDescEditor );
		data.question_description = descEditor ? descEditor.value : '';

		if ( typeof tinymce !== 'undefined' ) {
			const editor = tinymce.get( BuilderEditQuestion.selectors.idDescEditor );
			if ( editor ) {
				data.question_description = editor.getContent();
			}
		}

		const elFormSetting = document.querySelector( BuilderEditQuestion.selectors.elFormSetting );

		if ( elFormSetting ) {
			data.question_settings = true;
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

	updateQuestion( args ) {
		// Context check: only handle if on question edit page
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { target } = args;
		const elBtnUpdateQuestion = target.closest( BuilderEditQuestion.selectors.elBtnUpdateQuestion );

		if ( ! elBtnUpdateQuestion ) {
			return;
		}

		// Validate title before update
		if ( ! this.validateTitleBeforeUpdate() ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnUpdateQuestion, 1 );

		// Get status from the button's data-status attribute
		const targetStatus = elBtnUpdateQuestion.dataset.status || 'publish';

		const questionData = this.getQuestionDataForUpdate();

		const dataSend = {
			...questionData,
			action: 'builder_update_question',
			args: {
				id_url: 'builder-update-question',
			},
			question_status: targetStatus,
		};

		if ( typeof lpQuestionBuilder !== 'undefined' && lpQuestionBuilder.nonce ) {
			dataSend.nonce = lpQuestionBuilder.nonce;
		}

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Update action button text with actual status from server
					this.updateActionButtons( data?.status || targetStatus );

					if ( data?.question_id_new ) {
						const currentUrl = window.location.href;
						window.location.href = currentUrl.replace( /post-new\/?/, `${ data.question_id_new }/` );
					}

					if ( data?.status ) {
						const elStatus = document.querySelector( BuilderEditQuestion.selectors.elQuestionStatus );
						if ( elStatus ) {
							elStatus.className = 'question-status ' + data.status;
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
				lpUtils.lpSetLoadingEl( elBtnUpdateQuestion, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	saveDraftQuestion( args ) {
		// Context check: only handle if on question edit page
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { target } = args;
		const elBtnDraftQuestion = target.closest( BuilderEditQuestion.selectors.elBtnDraftQuestion );

		if ( ! elBtnDraftQuestion ) {
			return;
		}

		// Validate title before saving draft
		if ( ! this.validateTitleBeforeUpdate() ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnDraftQuestion, 1 );

		const questionData = this.getQuestionDataForUpdate();

		const dataSend = {
			...questionData,
			action: 'builder_update_question',
			args: {
				id_url: 'builder-update-question',
			},
			question_status: 'draft',
		};

		if ( typeof lpQuestionBuilder !== 'undefined' && lpQuestionBuilder.nonce ) {
			dataSend.nonce = lpQuestionBuilder.nonce;
		}

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Update action button text
					this.updateActionButtons( 'draft' );

					if ( data?.question_id_new ) {
						const currentUrl = window.location.href;
						window.location.href = currentUrl.replace( /post-new\/?/, `${ data.question_id_new }/` );
					}

					if ( data?.status ) {
						const elStatus = document.querySelector( BuilderEditQuestion.selectors.elQuestionStatus );
						if ( elStatus ) {
							elStatus.className = 'question-status ' + data.status;
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
				lpUtils.lpSetLoadingEl( elBtnDraftQuestion, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	trashQuestion( args ) {
		// Context check: only handle if on question edit page
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { target } = args;
		const elBtnTrashQuestion = target.closest( BuilderEditQuestion.selectors.elBtnTrashQuestion );
		if ( ! elBtnTrashQuestion ) {
			return;
		}

		if ( ! confirm( 'Are you sure you want to trash this question?' ) ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnTrashQuestion, 1 );

		const questionData = this.getQuestionDataForUpdate();
		const dataSend = {
			action: 'move_trash_question',
			args: {
				id_url: 'move-trash-question',
			},
			question_id: questionData.question_id || 0,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					if ( data?.redirect_url ) {
						window.location.href = data.redirect_url;
					}

					if ( data?.status ) {
						const elStatus = document.querySelector( BuilderEditQuestion.selectors.elQuestionStatus );
						if ( elStatus ) {
							elStatus.className = 'question-status ' + data.status;
							elStatus.textContent = data.status;
						}
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnTrashQuestion, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
