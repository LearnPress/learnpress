import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

export class BuilderEditLesson {
	constructor() {
		// Events use document-level event delegation, so always register them
		// The page context check happens in individual handlers via target.closest()
		this.init();
	}

	static selectors = {
		// Context selector - indicates we're on lesson edit page
		elDataLesson: '.cb-section__lesson-edit',
		// Shared header action buttons (generic selectors)
		elBtnMainAction: '.cb-btn-main-action',
		elBtnUpdate: '.cb-btn-update, .cb-btn-publish',
		elBtnDraft: '.cb-btn-darft, .cb-dropdown-item[data-status="draft"]',
		elBtnTrash: '.cb-btn-trash',
		// Status badge
		elLessonStatus: '.lesson-status',
		// Form fields
		idTitle: 'title',
		idDescEditor: 'lesson_description_editor',
		elFormSetting: '.lp-form-setting-lesson',
		// Tab handling selectors
		elCBHorizontalTabs: '.lp-cb-tabs__item',
		elCBTabPanels: '.lp-cb-tab-panel',
		// Dropdown selectors
		elDropdownToggle: '.cb-btn-dropdown-toggle',
		elDropdownMenu: '.cb-dropdown-menu',
		elHeaderActionsDropdown: '.cb-header-actions-dropdown',
	};

	init() {
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
			const dropdown = document.querySelector( BuilderEditLesson.selectors.elHeaderActionsDropdown );
			if ( dropdown && ! dropdown.contains( e.target ) ) {
				const menu = dropdown.querySelector( BuilderEditLesson.selectors.elDropdownMenu );
				const toggle = dropdown.querySelector( BuilderEditLesson.selectors.elDropdownToggle );
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
		const toggleBtn = target.closest( BuilderEditLesson.selectors.elDropdownToggle );
		
		if ( ! toggleBtn ) {
			return;
		}

		const dropdown = toggleBtn.closest( BuilderEditLesson.selectors.elHeaderActionsDropdown );
		if ( ! dropdown ) {
			return;
		}

		const menu = dropdown.querySelector( BuilderEditLesson.selectors.elDropdownMenu );
		if ( menu ) {
			menu.classList.toggle( 'is-open' );
			const isOpen = menu.classList.contains( 'is-open' );
			toggleBtn.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		}
	}

	/**
	 * Initialize horizontal tabs for client-side tab switching
	 */
	initTabs() {
		const tabs = document.querySelectorAll( BuilderEditLesson.selectors.elCBHorizontalTabs );
		if ( tabs.length === 0 ) {
			return;
		}

		// Activate first tab by default if none is active
		const activeTab = document.querySelector( `${ BuilderEditLesson.selectors.elCBHorizontalTabs }.is-active` );
		if ( ! activeTab && tabs.length > 0 ) {
			tabs[0].classList.add( 'is-active' );
			const section = tabs[0].getAttribute( 'data-tab-section' );
			if ( section ) {
				const panel = document.querySelector( `${ BuilderEditLesson.selectors.elCBTabPanels }[data-section="${ section }"]` );
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
		const tabLink = target.closest( BuilderEditLesson.selectors.elCBHorizontalTabs );
		
		if ( ! tabLink ) {
			return;
		}

		e.preventDefault();

		const section = tabLink.getAttribute( 'data-tab-section' );
		if ( ! section ) {
			return;
		}

		// Update active tab
		const allTabs = document.querySelectorAll( BuilderEditLesson.selectors.elCBHorizontalTabs );
		allTabs.forEach( tab => tab.classList.remove( 'is-active' ) );
		tabLink.classList.add( 'is-active' );

		// Show/hide panels
		const allPanels = document.querySelectorAll( BuilderEditLesson.selectors.elCBTabPanels );
		allPanels.forEach( panel => {
			if ( panel.getAttribute( 'data-section' ) === section ) {
				panel.classList.remove( 'lp-hidden' );
			} else {
				panel.classList.add( 'lp-hidden' );
			}
		} );
	}

	events() {
		if ( BuilderEditLesson._loadedEvents ) {
			return;
		}
		BuilderEditLesson._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderEditLesson.selectors.elBtnMainAction,
				class: this,
				callBack: this.updateLesson.name,
			},
			{
				selector: BuilderEditLesson.selectors.elBtnDraft,
				class: this,
				callBack: this.saveDraftLesson.name,
			},
			{
				selector: BuilderEditLesson.selectors.elBtnTrash,
				class: this,
				callBack: this.trashLesson.name,
			},
			{
				selector: BuilderEditLesson.selectors.elCBHorizontalTabs,
				class: this,
				callBack: this.handleTabClick.name,
			},
			{
				selector: BuilderEditLesson.selectors.elDropdownToggle,
				class: this,
				callBack: this.handleDropdownToggle.name,
			},
		] );
	}

	/**
	 * Validate title is not empty before update
	 * @return {boolean} True if valid, false if invalid
	 */
	validateTitleBeforeUpdate() {
		const titleInput = document.getElementById( BuilderEditLesson.selectors.idTitle );
		if ( ! titleInput ) {
			return true;
		}

		const title = titleInput.value.trim();
		if ( ! title ) {
			lpToastify.show( 'Lesson title is required.', 'error' );
			titleInput.focus();
			return false;
		}
		return true;
	}

	/**
	 * Update action button text after status change
	 * @param {string} newStatus - The new lesson status
	 */
	updateActionButtons( newStatus ) {
		const elBtnUpdate = document.querySelector( BuilderEditLesson.selectors.elBtnMainAction );
		if ( ! elBtnUpdate ) {
			return;
		}

		const titleUpdate = elBtnUpdate.getAttribute( 'data-title-update' ) || 'Update';
		const titlePublish = elBtnUpdate.getAttribute( 'data-title-publish' ) || 'Publish';

		if ( newStatus === 'publish' ) {
			elBtnUpdate.textContent = titleUpdate;
		} else {
			elBtnUpdate.textContent = titlePublish;
		}
	}

	/**
	 * Check if we're in lesson edit context
	 * @return {boolean}
	 */
	isLessonContext() {
		return !! document.querySelector( BuilderEditLesson.selectors.elDataLesson );
	}

	getLessonDataForUpdate() {
		const data = {};

		const wrapperEl = document.querySelector( BuilderEditLesson.selectors.elDataLesson );
		data.lesson_id = wrapperEl ? parseInt( wrapperEl.dataset.lessonId ) || 0 : 0;

		const titleInput = document.getElementById( BuilderEditLesson.selectors.idTitle );
		data.lesson_title = titleInput ? titleInput.value : '';

		const descEditor = document.getElementById( BuilderEditLesson.selectors.idDescEditor );
		data.lesson_description = descEditor ? descEditor.value : '';

		if ( typeof tinymce !== 'undefined' ) {
			const editor = tinymce.get( BuilderEditLesson.selectors.idDescEditor );
			if ( editor ) {
				data.lesson_description = editor.getContent();
			}
		}

		const elFormSetting = document.querySelector( BuilderEditLesson.selectors.elFormSetting );

		if ( elFormSetting ) {
			data.lesson_settings = true;
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

	updateLesson( args ) {
		// Context check: only handle if on lesson edit page
		if ( ! this.isLessonContext() ) {
			return;
		}

		const { target } = args;
		const elBtnUpdateLesson = target.closest( BuilderEditLesson.selectors.elBtnMainAction );

		if ( ! elBtnUpdateLesson ) {
			return;
		}

		// Validate title before update
		if ( ! this.validateTitleBeforeUpdate() ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnUpdateLesson, 1 );

		const lessonData = this.getLessonDataForUpdate();

		const dataSend = {
			...lessonData,
			action: 'builder_update_lesson',
			args: {
				id_url: 'builder-update-lesson',
			},
			lesson_status: 'publish',
		};

		if ( typeof lpLessonBuilder !== 'undefined' && lpLessonBuilder.nonce ) {
			dataSend.nonce = lpLessonBuilder.nonce;
		}

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Update action button text
					this.updateActionButtons( 'publish' );

					if ( data?.lesson_id_new ) {
						const currentUrl = window.location.href;
						window.location.href = currentUrl.replace( /post-new\/?/, `${ data.lesson_id_new }/` );
					}

					if ( data?.status ) {
						const elStatus = document.querySelector( BuilderEditLesson.selectors.elLessonStatus );
						if ( elStatus ) {
							elStatus.className = 'lesson-status ' + data.status;
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
				lpUtils.lpSetLoadingEl( elBtnUpdateLesson, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	saveDraftLesson( args ) {
		// Context check: only handle if on lesson edit page
		if ( ! this.isLessonContext() ) {
			return;
		}

		const { target } = args;
		const elBtnDraftLesson = target.closest( BuilderEditLesson.selectors.elBtnDraft );

		if ( ! elBtnDraftLesson ) {
			return;
		}

		// Validate title before saving draft
		if ( ! this.validateTitleBeforeUpdate() ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnDraftLesson, 1 );

		const lessonData = this.getLessonDataForUpdate();

		const dataSend = {
			...lessonData,
			action: 'builder_update_lesson',
			args: {
				id_url: 'builder-update-lesson',
			},
			lesson_status: 'draft',
		};

		if ( typeof lpLessonBuilder !== 'undefined' && lpLessonBuilder.nonce ) {
			dataSend.nonce = lpLessonBuilder.nonce;
		}

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Update action button text
					this.updateActionButtons( 'draft' );

					if ( data?.lesson_id_new ) {
						const currentUrl = window.location.href;
						window.location.href = currentUrl.replace( /post-new\/?/, `${ data.lesson_id_new }/` );
					}

					if ( data?.status ) {
						const elStatus = document.querySelector( BuilderEditLesson.selectors.elLessonStatus );
						if ( elStatus ) {
							elStatus.className = 'lesson-status ' + data.status;
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
				lpUtils.lpSetLoadingEl( elBtnDraftLesson, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	trashLesson( args ) {
		// Context check: only handle if on lesson edit page
		if ( ! this.isLessonContext() ) {
			return;
		}

		const { target } = args;
		const elBtnTrashLesson = target.closest( BuilderEditLesson.selectors.elBtnTrash );

		if ( ! elBtnTrashLesson ) {
			return;
		}

		if ( ! confirm( 'Are you sure you want to trash this lesson?' ) ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnTrashLesson, 1 );

		const lessonData = this.getLessonDataForUpdate();
		const dataSend = {
			action: 'move_trash_lesson',
			args: {
				id_url: 'move-trash-lesson',
			},
			lesson_id: lessonData.lesson_id,
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
						const elStatus = document.querySelector( BuilderEditLesson.selectors.elLessonStatus );
						if ( elStatus ) {
							elStatus.className = 'lesson-status ' + data.status;
							elStatus.textContent = data.status;
						}
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnTrashLesson, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
