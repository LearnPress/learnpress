/**
 * Builder Standalone Quiz Handler
 * Handles standalone quiz edit page (not popup)
 *
 * Important separation note:
 * - This file handles standalone quiz page orchestration (header actions, publish/draft/trash, permalink, tabs).
 * - Question-tab internals are delegated to BuilderEditQuiz.
 * - Do not merge the two responsibilities into one file unless there is a strong architectural reason.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';
import { BuilderEditQuiz } from './builder-edit-quiz.js';
import { SWAL_ICON_DUPLICATE, SWAL_ICON_TRASH_DRAFT } from '../swal-icons.js';
import { getFormState } from '../builder-form-state.js';

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
		elBtnDuplicate: '.cb-btn-duplicate-quiz',
		elBtnTrash: '.cb-btn-trash',
		// Status badge
		elQuizStatus: '.quiz-status, .quizze-status',
		// Form fields
		idTitle: 'title',
		idDescEditor: 'quiz_description_editor',
		elPublishStatusSelect: '#cb-quiz-publish-status',
		elFormSetting: '.lp-form-setting-quiz',
		// Permalink component
		elPermalinkDisplay: '.cb-permalink-display',
		elPermalinkEditor: '.cb-permalink-editor',
		elPermalinkEditBtn: '.cb-permalink-edit-btn',
		elPermalinkOkBtn: '.cb-permalink-ok-btn',
		elPermalinkCancelBtn: '.cb-permalink-cancel-btn',
		elPermalinkSlugInput: '.cb-permalink-slug-input',
		elPermalinkUrl: '.cb-permalink-url',
		elPermalinkBaseUrl: '#cb-permalink-base-url',
		elPermalinkRoot: '.cb-item-edit-permalink, .cb-course-edit-permalink',
		elPermalinkPlaceholder: '.cb-item-edit-permalink__placeholder',
		// Tab handling selectors
		elCBHorizontalTabs: '.lp-cb-tabs__item',
		elCBTabPanels: '.lp-cb-tab-panel',
		// Dropdown selectors
		elDropdownToggle: '.cb-btn-dropdown-toggle',
		elDropdownMenu: '.cb-dropdown-menu',
		elDropdownItem: '.cb-dropdown-item',
		elHeaderActionsDropdown: '.cb-header-actions-dropdown',
		elQuizActionExpanded: '.course-action-expanded',
	};

	init() {
		this.initTabs();
		this.initHeaderActionsDropdown();
		this.initQuestionTabHandler();
		this.syncHeaderActionWithPublishPanel();
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
			const dropdown = document.querySelector(
				BuilderStandaloneQuiz.selectors.elHeaderActionsDropdown
			);
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
		if ( this.hasPublishDrivenSingleAction() ) {
			return;
		}

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

		if ( this.hasPublishDrivenSingleAction() ) {
			return;
		}

		const { target } = args;
		const dropdownItem = target.closest( BuilderStandaloneQuiz.selectors.elDropdownItem );

		if ( ! dropdownItem ) {
			return;
		}

		// Skip if this is trash button - it has its own handler
		if (
			dropdownItem.classList.contains( 'cb-btn-trash' ) ||
			dropdownItem.classList.contains( 'cb-btn-duplicate-quiz' )
		) {
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
	async saveQuizWithStatus( btnEl, status ) {
		const canContinue = await this.confirmUnpublishIfNeeded( status, btnEl );
		if ( ! canContinue ) {
			return;
		}

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
					this.syncPublishPanelStatus( data?.status || status );

					// Reset form state before potential redirect.
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );

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

					this.updatePermalinkUIAfterSave( data );
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
		const activeTab = document.querySelector(
			`${ BuilderStandaloneQuiz.selectors.elCBHorizontalTabs }.is-active`
		);
		if ( ! activeTab && tabs.length > 0 ) {
			tabs[ 0 ].classList.add( 'is-active' );
			const section = tabs[ 0 ].getAttribute( 'data-tab-section' );
			if ( section ) {
				const panel = document.querySelector(
					`${ BuilderStandaloneQuiz.selectors.elCBTabPanels }[data-section="${ section }"]`
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
		allTabs.forEach( ( tab ) => tab.classList.remove( 'is-active' ) );
		tabLink.classList.add( 'is-active' );

		const url = new URL( window.location.href );
		url.searchParams.set( 'tab', section );
		window.history.replaceState( {}, '', url );

		// Show/hide panels
		const allPanels = document.querySelectorAll( BuilderStandaloneQuiz.selectors.elCBTabPanels );
		allPanels.forEach( ( panel ) => {
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
				selector: BuilderStandaloneQuiz.selectors.elBtnDuplicate,
				class: this,
				callBack: this.duplicateQuiz.name,
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
			{
				selector: BuilderStandaloneQuiz.selectors.elPermalinkEditBtn,
				class: this,
				callBack: this.handlePermalinkEdit.name,
			},
			{
				selector: BuilderStandaloneQuiz.selectors.elPermalinkOkBtn,
				class: this,
				callBack: this.handlePermalinkOk.name,
			},
			{
				selector: BuilderStandaloneQuiz.selectors.elPermalinkCancelBtn,
				class: this,
				callBack: this.handlePermalinkCancel.name,
			},
		] );

		lpUtils.eventHandlers( 'change', [
			{
				selector: BuilderStandaloneQuiz.selectors.elPublishStatusSelect,
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

		const permalinkInput = document.querySelector(
			BuilderStandaloneQuiz.selectors.elPermalinkSlugInput
		);
		if ( permalinkInput && permalinkInput.value ) {
			data.quiz_permalink = permalinkInput.value;
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

	getStatusFromPublishPanel( fallbackStatus = 'publish' ) {
		const statusSelect = document.querySelector(
			BuilderStandaloneQuiz.selectors.elPublishStatusSelect
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
		if ( ! document.querySelector( BuilderStandaloneQuiz.selectors.elPublishStatusSelect ) ) {
			return false;
		}

		const wrapperEl = document.querySelector( BuilderStandaloneQuiz.selectors.elDataQuiz );
		const quizId = wrapperEl ? parseInt( wrapperEl.dataset.quizId ) || 0 : 0;

		return quizId > 0;
	}

	syncHeaderActionWithPublishPanel() {
		if ( ! this.hasPublishDrivenSingleAction() ) {
			return;
		}

		const dropdown = document.querySelector(
			BuilderStandaloneQuiz.selectors.elHeaderActionsDropdown
		);
		if ( ! dropdown ) {
			return;
		}

		const mainBtn = dropdown.querySelector( '.cb-btn-main-action' );
		if ( ! mainBtn ) {
			return;
		}

		const toggleBtn = dropdown.querySelector( BuilderStandaloneQuiz.selectors.elDropdownToggle );
		const dropdownMenu = dropdown.querySelector( BuilderStandaloneQuiz.selectors.elDropdownMenu );
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
			BuilderStandaloneQuiz.selectors.elPublishStatusSelect
		);
		if ( ! statusSelect ) {
			return;
		}

		statusSelect.value = status === 'publish' ? 'publish' : 'draft';
		this.syncHeaderActionWithPublishPanel();
	}

	updatePermalinkUIAfterSave( data = {} ) {
		const permalinkRoot = this.getPermalinkRoot();
		if ( ! permalinkRoot ) {
			return;
		}

		const { slugInput, urlLink, baseUrlInput, display, placeholder } =
			this.getPermalinkElements( permalinkRoot );
		const permalinkDisplayUrl = this.buildPermalinkDisplayUrl(
			baseUrlInput ? baseUrlInput.value : '',
			data?.quiz_slug,
			data?.quiz_permalink
		);

		if ( slugInput && data?.quiz_slug ) {
			slugInput.value = data.quiz_slug;
			slugInput.dataset.originalValue = data.quiz_slug;
		}

		const shouldShowUnavailable =
			data?.permalink_available === false ||
			data?.status === 'draft' ||
			data?.status === 'trash' ||
			! data?.quiz_permalink;

		if ( shouldShowUnavailable ) {
			this.showPermalinkUnavailable( permalinkRoot, data?.permalink_notice );
			return;
		}

		if ( placeholder ) {
			placeholder.classList.add( 'lp-hidden' );
		}

		if ( display ) {
			display.classList.remove( 'lp-hidden' );
		}

		if ( urlLink && data?.quiz_permalink ) {
			urlLink.href = data.quiz_permalink;
			urlLink.textContent = permalinkDisplayUrl || data.quiz_permalink;
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

	getPermalinkRoot( target = null ) {
		if ( target?.closest ) {
			const fromTarget = target.closest( BuilderStandaloneQuiz.selectors.elPermalinkRoot );
			if ( fromTarget ) {
				return fromTarget;
			}
		}

		return document.querySelector(
			`.cb-section__quiz-edit ${ BuilderStandaloneQuiz.selectors.elPermalinkRoot }`
		);
	}

	getPermalinkElements( permalinkRoot ) {
		if ( ! permalinkRoot ) {
			return {};
		}

		return {
			display: permalinkRoot.querySelector( BuilderStandaloneQuiz.selectors.elPermalinkDisplay ),
			editor: permalinkRoot.querySelector( BuilderStandaloneQuiz.selectors.elPermalinkEditor ),
			placeholder: permalinkRoot.querySelector(
				BuilderStandaloneQuiz.selectors.elPermalinkPlaceholder
			),
			input: permalinkRoot.querySelector( BuilderStandaloneQuiz.selectors.elPermalinkSlugInput ),
			slugInput: permalinkRoot.querySelector(
				BuilderStandaloneQuiz.selectors.elPermalinkSlugInput
			),
			urlLink: permalinkRoot.querySelector( BuilderStandaloneQuiz.selectors.elPermalinkUrl ),
			baseUrlInput: permalinkRoot.querySelector(
				BuilderStandaloneQuiz.selectors.elPermalinkBaseUrl
			),
		};
	}

	showPermalinkUnavailable( permalinkRoot, message = '' ) {
		if ( ! permalinkRoot ) {
			return;
		}

		const label =
			permalinkRoot.querySelector( '.cb-item-edit-permalink__label' ) ||
			permalinkRoot.querySelector( '.cb-permalink-label' );
		const { display, editor } = this.getPermalinkElements( permalinkRoot );
		let placeholder = permalinkRoot.querySelector(
			BuilderStandaloneQuiz.selectors.elPermalinkPlaceholder
		);

		if ( ! placeholder ) {
			placeholder = document.createElement( 'span' );
			placeholder.className = 'cb-item-edit-permalink__placeholder';

			if ( label ) {
				label.insertAdjacentElement( 'afterend', placeholder );
			} else {
				permalinkRoot.prepend( placeholder );
			}
		}

		placeholder.textContent =
			message || 'Permalink is only available if the item is already assigned to a course.';
		placeholder.classList.remove( 'lp-hidden' );

		if ( display ) {
			display.classList.add( 'lp-hidden' );
		}

		if ( editor ) {
			editor.classList.add( 'lp-hidden' );
		}
	}

	handlePermalinkEdit( args ) {
		if ( ! this.isQuizContext() ) {
			return;
		}

		const { e, target } = args;
		if ( e ) e.preventDefault();

		const permalinkRoot = this.getPermalinkRoot( target );
		const { display, editor, input } = this.getPermalinkElements( permalinkRoot );

		if ( ! display || ! editor || ! input ) return;

		input.dataset.originalValue = input.value;
		display.classList.add( 'lp-hidden' );
		editor.classList.remove( 'lp-hidden' );
		input.focus();
		input.select();
	}

	handlePermalinkOk( args ) {
		if ( ! this.isQuizContext() ) {
			return;
		}

		const { e, target } = args;
		if ( e ) e.preventDefault();

		const permalinkRoot = this.getPermalinkRoot( target );
		const { display, editor, input, urlLink, baseUrlInput } =
			this.getPermalinkElements( permalinkRoot );

		if ( ! display || ! editor || ! input || ! urlLink ) return;

		let newSlug = this.slugify( input.value.trim() );
		if ( ! newSlug ) {
			newSlug = input.dataset.originalValue || 'quiz';
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
		if ( ! this.isQuizContext() ) {
			return;
		}

		const { e, target } = args;
		if ( e ) e.preventDefault();

		const permalinkRoot = this.getPermalinkRoot( target );
		const { display, editor, input } = this.getPermalinkElements( permalinkRoot );

		if ( ! display || ! editor || ! input ) return;

		input.value = input.dataset.originalValue || '';
		editor.classList.add( 'lp-hidden' );
		display.classList.remove( 'lp-hidden' );
	}

	/**
	 * Update action buttons after status change, matching course edit logic.
	 * Updates both main button and dropdown items based on new status.
	 * @param {string} newStatus - The new quiz status
	 */
	updateActionButtons( newStatus ) {
		const dropdown = document.querySelector(
			BuilderStandaloneQuiz.selectors.elHeaderActionsDropdown
		);
		if ( ! dropdown ) return;

		const mainBtn = dropdown.querySelector( '.cb-btn-main-action' );
		const dropdownMenu = dropdown.querySelector( BuilderStandaloneQuiz.selectors.elDropdownMenu );
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

	async updateQuiz( args ) {
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

		// Resolve status from Overview publish panel first, fallback to button data-status.
		const targetStatus = this.getStatusFromPublishPanel(
			elBtnUpdateQuiz.dataset.status || 'publish'
		);
		const canContinue = await this.confirmUnpublishIfNeeded( targetStatus, elBtnUpdateQuiz );
		if ( ! canContinue ) {
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
			quiz_status: targetStatus,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Update action buttons with actual status from server
					this.updateActionButtons( data?.status || targetStatus );
					this.syncPublishPanelStatus( data?.status || targetStatus );

					// Reset form state before potential redirect.
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );

					if ( data?.redirect_url ) {
						window.location.href = data.redirect_url;
					}

					if ( data?.status ) {
						const elStatus = document.querySelector( BuilderStandaloneQuiz.selectors.elQuizStatus );
						if ( elStatus ) {
							elStatus.className = 'quizze-status ' + data.status;
							elStatus.textContent = data.status;
						}

						if ( data.status === 'trash' || data.status === 'draft' ) {
							const curriculumItem = document.querySelector(
								`.quiz-item[data-quiz-id="${ quizData.quiz_id }"]`
							);
							if ( curriculumItem ) {
								const elCurriculumQuiz = curriculumItem.closest( '.quiz' );
								if ( elCurriculumQuiz ) {
									elCurriculumQuiz.remove();
								}
							}
						}
					}

					this.updatePermalinkUIAfterSave( data );
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

	async confirmUnpublishIfNeeded( targetStatus, triggerEl ) {
		if ( targetStatus !== 'draft' ) {
			return true;
		}

		const statusEl = document.querySelector( BuilderStandaloneQuiz.selectors.elQuizStatus );
		const isPublished = statusEl && statusEl.className.includes( 'publish' );
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

	async saveDraftQuiz( args ) {
		// Context check: only handle if on quiz edit page
		if ( ! this.isQuizContext() ) {
			return;
		}

		const { target } = args;
		const elBtnDraftQuiz = target.closest( BuilderStandaloneQuiz.selectors.elBtnDraft );

		if ( ! elBtnDraftQuiz ) {
			return;
		}

		// Check if published to show confirm unpublish modal
		const statusEl = document.querySelector( BuilderStandaloneQuiz.selectors.elQuizStatus );
		// Status might use quiz-status or quizze-status class based on element logic
		const isPublished = statusEl && statusEl.className.includes( 'publish' );
		if ( isPublished ) {
			const confirmMsg =
				elBtnDraftQuiz.dataset.confirmUnpublish ||
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

		const elQuizActionExpanded = document.querySelector(
			BuilderStandaloneQuiz.selectors.elQuizActionExpanded
		);

		lpUtils.lpSetLoadingEl( elQuizActionExpanded, 1 );

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
					this.syncPublishPanelStatus( data?.status || 'draft' );

					// Reset form state before potential redirect.
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );

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

						if ( data.status === 'trash' || data.status === 'draft' ) {
							const curriculumItem = document.querySelector(
								`.quiz-item[data-quiz-id="${ quizData.quiz_id }"]`
							);
							if ( curriculumItem ) {
								const elCurriculumQuiz = curriculumItem.closest( '.quiz' );
								if ( elCurriculumQuiz ) {
									elCurriculumQuiz.remove();
								}
							}
						}
					}

					this.updatePermalinkUIAfterSave( data );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elQuizActionExpanded, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	async trashQuiz( args ) {
		// Context check: only handle if on quiz edit page
		if ( ! this.isQuizContext() ) {
			return;
		}

		const { target } = args;
		const elBtnTrashQuiz = target.closest( BuilderStandaloneQuiz.selectors.elBtnTrash );

		if ( ! elBtnTrashQuiz ) {
			return;
		}

		const result = await SweetAlert.fire( {
			title: 'Are you sure you want to trash this quiz?',
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

		const elQuizActionExpanded = document.querySelector(
			BuilderStandaloneQuiz.selectors.elQuizActionExpanded
		);

		lpUtils.lpSetLoadingEl( elQuizActionExpanded, 1 );

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

						if ( data.status === 'trash' || data.status === 'draft' ) {
							const curriculumItem = document.querySelector(
								`.quiz-item[data-quiz-id="${ quizId }"]`
							);
							if ( curriculumItem ) {
								const elCurriculumQuiz = curriculumItem.closest( '.quiz' );
								if ( elCurriculumQuiz ) {
									elCurriculumQuiz.remove();
								}
							}
						}
						// Update action buttons to show correct state for trash status
						this.updateActionButtons( data.status );
					}

					this.updatePermalinkUIAfterSave( data );

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
				lpUtils.lpSetLoadingEl( elQuizActionExpanded, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	async duplicateQuiz( args ) {
		// Context check: only handle if on quiz edit page
		if ( ! this.isQuizContext() ) {
			return;
		}

		const { target } = args;
		const elBtnDuplicateQuiz = target.closest( BuilderStandaloneQuiz.selectors.elBtnDuplicate );
		if ( ! elBtnDuplicateQuiz ) {
			return;
		}

		const wrapperEl = document.querySelector( BuilderStandaloneQuiz.selectors.elDataQuiz );
		const quizId = wrapperEl ? parseInt( wrapperEl.dataset.quizId, 10 ) || 0 : 0;
		if ( ! quizId ) {
			return;
		}

		const result = await SweetAlert.fire( {
			title: elBtnDuplicateQuiz.dataset.title || 'Are you sure?',
			text: elBtnDuplicateQuiz.dataset.content || 'Are you sure you want to duplicate this quiz?',
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

		const elQuizActionExpanded = document.querySelector(
			BuilderStandaloneQuiz.selectors.elQuizActionExpanded
		);

		lpUtils.lpSetLoadingEl( elQuizActionExpanded, 1 );

		const dataSend = {
			quiz_id: quizId,
			action: 'duplicate_quiz',
			args: {
				id_url: 'duplicate-quiz',
			},
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
				lpUtils.lpSetLoadingEl( elQuizActionExpanded, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
