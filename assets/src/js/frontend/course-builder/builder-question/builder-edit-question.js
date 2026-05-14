import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';
import { EditQuestion } from 'lpAssetsJsPath/admin/edit-question.js';
import { SWAL_ICON_DUPLICATE, SWAL_ICON_TRASH_DRAFT } from '../swal-icons.js';
import { getFormState } from '../builder-form-state.js';

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
		elBtnDuplicateQuestion: '.cb-btn-duplicate-question',
		elBtnTrashQuestion: '.cb-btn-trash',
		elBtnMainAction: '.cb-btn-main-action',
		elQuestionStatus: '.question-status',
		idTitle: 'title',
		idDescEditor: 'question_description_editor',
		elPublishStatusSelect: '#cb-question-publish-status',
		elFormSetting: '.lp-form-setting-question',
		// Permalink component
		elPermalinkDisplay: '.cb-permalink-display',
		elPermalinkEditor: '.cb-permalink-editor',
		elPermalinkEditBtn: '.cb-permalink-edit-btn',
		elPermalinkOkBtn: '.cb-permalink-ok-btn',
		elPermalinkCancelBtn: '.cb-permalink-cancel-btn',
		elPermalinkSlugInput: '.cb-permalink-slug-input',
		elPermalinkUrl: '.cb-permalink-url',
		elPermalinkBaseUrl: '#cb-permalink-base-url',
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
		elQuestionActionExpanded: '.course-action-expanded',
	};

	init() {
		this.initQuestionAnswersSettings();
		this.initTabs();
		this.initHeaderActionsDropdown();
		this.syncHeaderActionWithPublishPanel();
		this.events();
	}

	/**
	 * Initialize header actions dropdown (toggle behavior)
	 */
	initHeaderActionsDropdown() {
		// Close dropdown when clicking outside
		document.addEventListener( 'click', ( e ) => {
			const dropdown = document.querySelector(
				BuilderEditQuestion.selectors.elHeaderActionsDropdown
			);
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
		if ( this.hasPublishDrivenSingleAction() ) {
			return;
		}

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

		if ( this.hasPublishDrivenSingleAction() ) {
			return;
		}

		const { target } = args;
		const dropdownItem = target.closest( BuilderEditQuestion.selectors.elDropdownItem );

		if ( ! dropdownItem ) {
			return;
		}

		// Skip if this is trash button - it has its own handler
		if (
			dropdownItem.classList.contains( 'cb-btn-trash' ) ||
			dropdownItem.classList.contains( 'cb-btn-duplicate-question' )
		) {
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
	async saveQuestionWithStatus( btnEl, status ) {
		const canContinue = await this.confirmUnpublishIfNeeded( status, btnEl );
		if ( ! canContinue ) {
			return;
		}

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
					this.syncPublishPanelStatus( data?.status || status );
					this.updatePermalinkUIAfterSave( data );

					// Reset form state before potential redirect.
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );

					if ( data?.question_id_new ) {
						const currentUrl = window.location.href;
						window.location.href = currentUrl.replace(
							/post-new\/?/,
							`${ data.question_id_new }/`
						);
					}

					if ( data?.status ) {
						const elStatus = document.querySelector(
							BuilderEditQuestion.selectors.elQuestionStatus
						);
						if ( elStatus ) {
							elStatus.className = 'question-status ' + data.status;
							elStatus.textContent = data.status;
						}

						if ( data.status === 'trash' || data.status === 'draft' ) {
							const curriculumItem = document.querySelector(
								`.question-item[data-question-id="${ questionData.question_id }"]`
							);
							if ( curriculumItem ) {
								const elCurriculumQuestion = curriculumItem.closest( '.question' );
								if ( elCurriculumQuestion ) {
									elCurriculumQuestion.remove();
								}
							}
						}
					}
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
		const activeTab = document.querySelector(
			`${ BuilderEditQuestion.selectors.elCBHorizontalTabs }.is-active`
		);
		if ( ! activeTab && tabs.length > 0 ) {
			tabs[ 0 ].classList.add( 'is-active' );
			const section = tabs[ 0 ].getAttribute( 'data-tab-section' );
			if ( section ) {
				const panel = document.querySelector(
					`${ BuilderEditQuestion.selectors.elCBTabPanels }[data-section="${ section }"]`
				);
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
		allTabs.forEach( ( tab ) => tab.classList.remove( 'is-active' ) );
		tabLink.classList.add( 'is-active' );

		const url = new URL( window.location.href );
		url.searchParams.set( 'tab', section );
		window.history.replaceState( {}, '', url );

		// Show/hide panels
		const allPanels = document.querySelectorAll( BuilderEditQuestion.selectors.elCBTabPanels );
		allPanels.forEach( ( panel ) => {
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
				selector: BuilderEditQuestion.selectors.elBtnMainAction,
				class: this,
				callBack: this.updateQuestion.name,
			},
			{
				selector: BuilderEditQuestion.selectors.elBtnDraftQuestion,
				class: this,
				callBack: this.saveDraftQuestion.name,
			},
			{
				selector: BuilderEditQuestion.selectors.elBtnDuplicateQuestion,
				class: this,
				callBack: this.duplicateQuestion.name,
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
			{
				selector: BuilderEditQuestion.selectors.elPermalinkEditBtn,
				class: this,
				callBack: this.handlePermalinkEdit.name,
			},
			{
				selector: BuilderEditQuestion.selectors.elPermalinkOkBtn,
				class: this,
				callBack: this.handlePermalinkOk.name,
			},
			{
				selector: BuilderEditQuestion.selectors.elPermalinkCancelBtn,
				class: this,
				callBack: this.handlePermalinkCancel.name,
			},
		] );

		lpUtils.eventHandlers( 'change', [
			{
				selector: BuilderEditQuestion.selectors.elPublishStatusSelect,
				class: this,
				callBack: this.handlePublishStatusChange.name,
			},
		] );
	}

	handlePublishStatusChange() {
		this.syncHeaderActionWithPublishPanel();
		getFormState().markAsChanged();
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
		const dropdown = document.querySelector(
			BuilderEditQuestion.selectors.elHeaderActionsDropdown
		);
		if ( ! dropdown ) return;

		const mainBtn = dropdown.querySelector( '.cb-btn-main-action' );
		const dropdownMenu = dropdown.querySelector( BuilderEditQuestion.selectors.elDropdownMenu );
		if ( ! mainBtn ) return;

		if ( this.hasPublishDrivenSingleAction() ) {
			this.syncHeaderActionWithPublishPanel();
			return;
		}

		if ( ! dropdownMenu ) return;

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

		const permalinkInput = document.querySelector(
			BuilderEditQuestion.selectors.elPermalinkSlugInput
		);
		if ( permalinkInput && permalinkInput.value ) {
			data.question_permalink = permalinkInput.value;
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

	getStatusFromPublishPanel( fallbackStatus = 'publish' ) {
		const statusSelect = document.querySelector(
			BuilderEditQuestion.selectors.elPublishStatusSelect
		);
		if ( ! statusSelect ) {
			return fallbackStatus;
		}

		const selectedStatus = statusSelect.value;
		if ( selectedStatus === 'publish' || selectedStatus === 'draft' ) {
			return selectedStatus;
		}

		return fallbackStatus;
	}

	hasPublishDrivenSingleAction() {
		if ( ! document.querySelector( BuilderEditQuestion.selectors.elPublishStatusSelect ) ) {
			return false;
		}

		const wrapperEl = document.querySelector( BuilderEditQuestion.selectors.elDataQuestion );
		const questionId = wrapperEl ? parseInt( wrapperEl.dataset.questionId ) || 0 : 0;

		return questionId > 0;
	}

	syncHeaderActionWithPublishPanel() {
		if ( ! this.hasPublishDrivenSingleAction() ) {
			return;
		}

		const dropdown = document.querySelector(
			BuilderEditQuestion.selectors.elHeaderActionsDropdown
		);
		if ( ! dropdown ) {
			return;
		}

		const mainBtn = dropdown.querySelector( '.cb-btn-main-action' );
		if ( ! mainBtn ) {
			return;
		}

		const toggleBtn = dropdown.querySelector( BuilderEditQuestion.selectors.elDropdownToggle );
		const dropdownMenu = dropdown.querySelector( BuilderEditQuestion.selectors.elDropdownMenu );
		const status = this.getStatusFromPublishPanel( mainBtn.dataset.status || 'publish' );

		mainBtn.classList.remove( 'cb-btn-darft', 'cb-btn-publish', 'cb-btn-pending' );
		mainBtn.classList.add( 'cb-btn-update', 'cb-btn-primary', 'cb-btn-main-action' );
		mainBtn.textContent = mainBtn.dataset.titleUpdate || 'Update';
		mainBtn.dataset.status = status;
		dropdown.dataset.currentStatus = status;
		dropdown.classList.add( 'cb-header-actions-dropdown--single' );

		if ( toggleBtn ) {
			toggleBtn.style.display = 'none';
		}
		if ( dropdownMenu ) {
			dropdownMenu.style.display = 'none';
		}
	}

	syncPublishPanelStatus( status ) {
		const statusSelect = document.querySelector(
			BuilderEditQuestion.selectors.elPublishStatusSelect
		);
		if ( ! statusSelect ) {
			return;
		}

		statusSelect.value = status === 'publish' ? 'publish' : 'draft';
		this.syncHeaderActionWithPublishPanel();
	}

	updatePermalinkUIAfterSave( data = {} ) {
		const slugInput = document.querySelector( BuilderEditQuestion.selectors.elPermalinkSlugInput );
		const urlLink = document.querySelector( BuilderEditQuestion.selectors.elPermalinkUrl );
		const baseUrlInput = document.querySelector( BuilderEditQuestion.selectors.elPermalinkBaseUrl );
		const permalinkDisplayUrl = this.buildPermalinkDisplayUrl(
			baseUrlInput ? baseUrlInput.value : '',
			data?.question_slug,
			data?.question_permalink
		);

		if ( slugInput && data?.question_slug ) {
			slugInput.value = data.question_slug;
			slugInput.dataset.originalValue = data.question_slug;
		}

		if ( urlLink && data?.question_permalink ) {
			urlLink.href = data.question_permalink;
			urlLink.textContent = permalinkDisplayUrl || data.question_permalink;
		} else if ( urlLink && permalinkDisplayUrl ) {
			urlLink.textContent = permalinkDisplayUrl;
		}
	}

	slugify( str ) {
		return ( str || '' )
			.toString()
			.normalize( 'NFD' )
			.replace( /[\u0300-\u036f]/g, '' )
			.replace( /đ/g, 'd' )
			.replace( /Đ/g, 'D' )
			.toLowerCase()
			.replace( /\s+/g, '-' )
			.replace( /[^\w-]+/g, '' )
			.replace( /--+/g, '-' )
			.replace( /^-+/, '' )
			.replace( /-+$/, '' );
	}

	buildPermalinkDisplayUrl( baseUrl = '', slug = '', fallbackUrl = '' ) {
		const normalizedBaseUrl = typeof baseUrl === 'string' ? baseUrl : '';
		const normalizedSlug = typeof slug === 'string' ? slug.trim() : '';

		if ( normalizedBaseUrl && normalizedSlug ) {
			return `${ normalizedBaseUrl }${ normalizedSlug }`;
		}

		return typeof fallbackUrl === 'string' ? fallbackUrl : '';
	}

	handlePermalinkEdit( args ) {
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { e } = args;
		if ( e ) e.preventDefault();

		const display = document.querySelector( BuilderEditQuestion.selectors.elPermalinkDisplay );
		const editor = document.querySelector( BuilderEditQuestion.selectors.elPermalinkEditor );
		const input = document.querySelector( BuilderEditQuestion.selectors.elPermalinkSlugInput );

		if ( ! display || ! editor || ! input ) return;

		input.dataset.originalValue = input.value;
		display.classList.add( 'lp-hidden' );
		editor.classList.remove( 'lp-hidden' );
		input.focus();
		input.select();
	}

	handlePermalinkOk( args ) {
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { e } = args;
		if ( e ) e.preventDefault();

		const display = document.querySelector( BuilderEditQuestion.selectors.elPermalinkDisplay );
		const editor = document.querySelector( BuilderEditQuestion.selectors.elPermalinkEditor );
		const input = document.querySelector( BuilderEditQuestion.selectors.elPermalinkSlugInput );
		const urlLink = document.querySelector( BuilderEditQuestion.selectors.elPermalinkUrl );
		const baseUrlInput = document.querySelector( BuilderEditQuestion.selectors.elPermalinkBaseUrl );

		if ( ! display || ! editor || ! input || ! urlLink ) return;

		let newSlug = this.slugify( input.value.trim() );
		if ( ! newSlug ) {
			newSlug = input.dataset.originalValue || 'question';
		}

		input.value = newSlug;
		const baseUrl = baseUrlInput ? baseUrlInput.value : '';
		const newUrl = this.buildPermalinkDisplayUrl( baseUrl, newSlug, urlLink.textContent || '' );

		// Keep href as the current saved link and only update display text.
		urlLink.textContent = newUrl;
		editor.classList.add( 'lp-hidden' );
		display.classList.remove( 'lp-hidden' );

		if ( newSlug !== input.dataset.originalValue ) {
			getFormState().markAsChanged();
		}
	}

	handlePermalinkCancel( args ) {
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { e } = args;
		if ( e ) e.preventDefault();

		const display = document.querySelector( BuilderEditQuestion.selectors.elPermalinkDisplay );
		const editor = document.querySelector( BuilderEditQuestion.selectors.elPermalinkEditor );
		const input = document.querySelector( BuilderEditQuestion.selectors.elPermalinkSlugInput );

		if ( ! display || ! editor || ! input ) return;

		input.value = input.dataset.originalValue || '';
		editor.classList.add( 'lp-hidden' );
		display.classList.remove( 'lp-hidden' );
	}

	async updateQuestion( args ) {
		// Context check: only handle if on question edit page
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { target } = args;
		const elBtnMainAction = target.closest( BuilderEditQuestion.selectors.elBtnMainAction );

		if ( ! elBtnMainAction ) {
			return;
		}

		// Validate title before update
		if ( ! this.validateTitleBeforeUpdate() ) {
			return;
		}

		// Resolve status from Overview publish panel first, fallback to button data-status.
		const targetStatus = this.getStatusFromPublishPanel(
			elBtnMainAction.dataset.status || 'publish'
		);
		const canContinue = await this.confirmUnpublishIfNeeded( targetStatus, elBtnMainAction );
		if ( ! canContinue ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnMainAction, 1 );

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
					this.syncPublishPanelStatus( data?.status || targetStatus );
					this.updatePermalinkUIAfterSave( data );

					// Reset form state before potential redirect.
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );

					if ( data?.redirect_url ) {
						window.location.href = data.redirect_url;
					}

					if ( data?.status ) {
						const elStatus = document.querySelector(
							BuilderEditQuestion.selectors.elQuestionStatus
						);
						if ( elStatus ) {
							elStatus.className = 'question-status ' + data.status;
							elStatus.textContent = data.status;
						}

						if ( data.status === 'trash' || data.status === 'draft' ) {
							const curriculumItem = document.querySelector(
								`.question-item[data-question-id="${ questionData.question_id }"]`
							);
							if ( curriculumItem ) {
								const elCurriculumQuestion = curriculumItem.closest( '.question' );
								if ( elCurriculumQuestion ) {
									elCurriculumQuestion.remove();
								}
							}
						}
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnMainAction, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	async confirmUnpublishIfNeeded( targetStatus, triggerEl ) {
		if ( targetStatus !== 'draft' ) {
			return true;
		}

		const statusEl = document.querySelector( BuilderEditQuestion.selectors.elQuestionStatus );
		const isPublished = statusEl && statusEl.classList.contains( 'publish' );
		if ( ! isPublished ) {
			return true;
		}

		const confirmMsg =
			triggerEl?.dataset?.confirmUnpublish ||
			'Saving as draft will unpublish this item. Are you sure?';
		const result = await SweetAlert.fire( {
			title: confirmMsg,
			iconHtml: SWAL_ICON_TRASH_DRAFT,
			customClass: { icon: 'lp-cb-swal-icon-html' },
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} );

		return !! result.isConfirmed;
	}

	async saveDraftQuestion( args ) {
		// Context check: only handle if on question edit page
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { target } = args;
		const elBtnDraftQuestion = target.closest( BuilderEditQuestion.selectors.elBtnDraftQuestion );

		if ( ! elBtnDraftQuestion ) {
			return;
		}

		// Check if published to show confirm unpublish modal
		const statusEl = document.querySelector( BuilderEditQuestion.selectors.elQuestionStatus );
		const isPublished = statusEl && statusEl.classList.contains( 'publish' );
		if ( isPublished ) {
			const confirmMsg =
				elBtnDraftQuestion.dataset.confirmUnpublish ||
				'Saving as draft will unpublish this item. Are you sure?';
			const result = await SweetAlert.fire( {
				title: confirmMsg,
				iconHtml: SWAL_ICON_TRASH_DRAFT,
				customClass: { icon: 'lp-cb-swal-icon-html' },
				showCloseButton: true,
				showCancelButton: true,
				cancelButtonText: lpData.i18n.cancel,
				confirmButtonText: lpData.i18n.yes,
				reverseButtons: true,
			} );

			if ( ! result.isConfirmed ) {
				return;
			}
		}

		// Validate title before saving draft
		if ( ! this.validateTitleBeforeUpdate() ) {
			return;
		}

		const elActionExpanded = document.querySelector(
			BuilderEditQuestion.selectors.elQuestionActionExpanded
		);

		lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

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
					this.syncPublishPanelStatus( data?.status || 'draft' );
					this.updatePermalinkUIAfterSave( data );

					// Reset form state before potential redirect.
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );

					if ( data?.question_id_new ) {
						const currentUrl = window.location.href;
						window.location.href = currentUrl.replace(
							/post-new\/?/,
							`${ data.question_id_new }/`
						);
					}

					if ( data?.status ) {
						const elStatus = document.querySelector(
							BuilderEditQuestion.selectors.elQuestionStatus
						);
						if ( elStatus ) {
							elStatus.className = 'question-status ' + data.status;
							elStatus.textContent = data.status;
						}

						if ( data.status === 'trash' || data.status === 'draft' ) {
							const curriculumItem = document.querySelector(
								`.question-item[data-question-id="${ questionData.question_id }"]`
							);
							if ( curriculumItem ) {
								const elCurriculumQuestion = curriculumItem.closest( '.question' );
								if ( elCurriculumQuestion ) {
									elCurriculumQuestion.remove();
								}
							}
						}
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elActionExpanded, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	async trashQuestion( args ) {
		// Context check: only handle if on question edit page
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { target } = args;
		const elBtnTrashQuestion = target.closest( BuilderEditQuestion.selectors.elBtnTrashQuestion );
		if ( ! elBtnTrashQuestion ) {
			return;
		}

		const result = await SweetAlert.fire( {
			title: 'Are you sure you want to trash this question?',
			iconHtml: SWAL_ICON_TRASH_DRAFT,
			customClass: { icon: 'lp-cb-swal-icon-html' },
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} );

		if ( ! result.isConfirmed ) {
			return;
		}

		const elActionExpanded = document.querySelector(
			BuilderEditQuestion.selectors.elQuestionActionExpanded
		);
		lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

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
						const elStatus = document.querySelector(
							BuilderEditQuestion.selectors.elQuestionStatus
						);
						if ( elStatus ) {
							elStatus.className = 'question-status ' + data.status;
							elStatus.textContent = data.status;
						}
						if ( data.status === 'trash' || data.status === 'draft' ) {
							const curriculumItem = document.querySelector(
								`.question-item[data-question-id="${ questionData.question_id }"]`
							);
							if ( curriculumItem ) {
								const elCurriculumQuestion = curriculumItem.closest( '.question' );
								if ( elCurriculumQuestion ) {
									elCurriculumQuestion.remove();
								}
							}
						}
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elActionExpanded, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	async duplicateQuestion( args ) {
		// Context check: only handle if on question edit page
		if ( ! this.isQuestionContext() ) {
			return;
		}

		const { target } = args;
		const elBtnDuplicateQuestion = target.closest(
			BuilderEditQuestion.selectors.elBtnDuplicateQuestion
		);
		if ( ! elBtnDuplicateQuestion ) {
			return;
		}

		const questionData = this.getQuestionDataForUpdate();
		const questionId = parseInt( questionData?.question_id, 10 ) || 0;
		if ( ! questionId ) {
			return;
		}

		const result = await SweetAlert.fire( {
			title: elBtnDuplicateQuestion.dataset.title || 'Are you sure?',
			text:
				elBtnDuplicateQuestion.dataset.content ||
				'Are you sure you want to duplicate this question?',
			iconHtml: SWAL_ICON_DUPLICATE,
			customClass: { icon: 'lp-cb-swal-icon-html' },
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} );

		if ( ! result.isConfirmed ) {
			return;
		}

		const elActionExpanded = document.querySelector(
			BuilderEditQuestion.selectors.elQuestionActionExpanded
		);

		lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

		const dataSend = {
			action: 'duplicate_question',
			args: {
				id_url: 'duplicate-question',
			},
			question_id: questionId,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message || 'Duplicated successfully!', status );

				if ( status === 'success' && data?.redirect_url ) {
					window.location.href = data.redirect_url;
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elActionExpanded, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
