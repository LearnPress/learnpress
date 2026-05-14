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
			{
				selector: BuilderEditLesson.selectors.elPermalinkEditBtn,
				class: this,
				callBack: this.handlePermalinkEdit.name,
			},
			{
				selector: BuilderEditLesson.selectors.elPermalinkOkBtn,
				class: this,
				callBack: this.handlePermalinkOk.name,
			},
			{
				selector: BuilderEditLesson.selectors.elPermalinkCancelBtn,
				class: this,
				callBack: this.handlePermalinkCancel.name,
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
			const fromTarget = target.closest( BuilderEditLesson.selectors.elPermalinkRoot );
			if ( fromTarget ) {
				return fromTarget;
			}
		}

		return document.querySelector(
			`.cb-section__lesson-edit ${ BuilderEditLesson.selectors.elPermalinkRoot }`
		);
	}

	showPermalinkUnavailable( permalinkRoot, message = '' ) {
		if ( ! permalinkRoot ) {
			return;
		}

		const label =
			permalinkRoot.querySelector( '.cb-item-edit-permalink__label' ) ||
			permalinkRoot.querySelector( '.cb-permalink-label' );
		const display = permalinkRoot.querySelector( BuilderEditLesson.selectors.elPermalinkDisplay );
		const editor = permalinkRoot.querySelector( BuilderEditLesson.selectors.elPermalinkEditor );
		let placeholder = permalinkRoot.querySelector(
			BuilderEditLesson.selectors.elPermalinkPlaceholder
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

		const permalinkInput = document.querySelector(
			`input[name="lesson_permalink"], #lesson_permalink, ${ BuilderEditLesson.selectors.elPermalinkSlugInput }`
		);
		if ( permalinkInput && permalinkInput.value ) {
			data.lesson_permalink = permalinkInput.value;
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

	updatePermalinkUIAfterSave( data = {} ) {
		const permalinkRoot = this.getPermalinkRoot();
		if ( ! permalinkRoot ) {
			return;
		}

		const slugInput = permalinkRoot.querySelector( BuilderEditLesson.selectors.elPermalinkSlugInput );
		const urlLink = permalinkRoot.querySelector( BuilderEditLesson.selectors.elPermalinkUrl );
		const baseUrlInput = permalinkRoot.querySelector( BuilderEditLesson.selectors.elPermalinkBaseUrl );
		const display = permalinkRoot.querySelector( BuilderEditLesson.selectors.elPermalinkDisplay );
		const placeholder = permalinkRoot.querySelector(
			BuilderEditLesson.selectors.elPermalinkPlaceholder
		);
		const permalinkDisplayUrl = this.buildPermalinkDisplayUrl(
			baseUrlInput ? baseUrlInput.value : '',
			data?.lesson_slug,
			data?.lesson_permalink
		);

		if ( slugInput && data?.lesson_slug ) {
			slugInput.value = data.lesson_slug;
			slugInput.dataset.originalValue = data.lesson_slug;
		}

		const shouldShowUnavailable =
			data?.permalink_available === false ||
			data?.status === 'draft' ||
			data?.status === 'trash' ||
			! data?.lesson_permalink;

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

		if ( urlLink && data?.lesson_permalink ) {
			urlLink.href = data.lesson_permalink;
			urlLink.textContent = permalinkDisplayUrl || data.lesson_permalink;
		} else if ( urlLink && permalinkDisplayUrl ) {
			urlLink.textContent = permalinkDisplayUrl;
		}
	}

	handlePermalinkEdit( args ) {
		if ( ! this.isLessonContext() ) {
			return;
		}

		const { e, target } = args;
		if ( e ) e.preventDefault();

		const trigger = target?.closest( BuilderEditLesson.selectors.elPermalinkEditBtn );
		const lessonRoot = trigger?.closest( BuilderEditLesson.selectors.elDataLesson ) ||
			document.querySelector( BuilderEditLesson.selectors.elDataLesson );

		if ( ! lessonRoot ) {
			return;
		}

		const display = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkDisplay );
		const editor = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkEditor );
		const input = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkSlugInput );

		if ( ! display || ! editor || ! input ) {
			return;
		}

		input.dataset.originalValue = input.value;
		display.classList.add( 'lp-hidden' );
		editor.classList.remove( 'lp-hidden' );
		input.focus();
		input.select();
	}

	handlePermalinkOk( args ) {
		if ( ! this.isLessonContext() ) {
			return;
		}

		const { e, target } = args;
		if ( e ) e.preventDefault();

		const trigger = target?.closest( BuilderEditLesson.selectors.elPermalinkOkBtn );
		const lessonRoot = trigger?.closest( BuilderEditLesson.selectors.elDataLesson ) ||
			document.querySelector( BuilderEditLesson.selectors.elDataLesson );

		if ( ! lessonRoot ) {
			return;
		}

		const display = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkDisplay );
		const editor = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkEditor );
		const input = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkSlugInput );
		const urlLink = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkUrl );
		const baseUrlInput = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkBaseUrl );

		if ( ! display || ! editor || ! input || ! urlLink ) {
			return;
		}

		let newSlug = this.slugify( input.value.trim() );
		if ( ! newSlug ) {
			newSlug = input.dataset.originalValue || 'lesson';
		}

		input.value = newSlug;
		const baseUrl = baseUrlInput ? baseUrlInput.value : '';
		const newUrl = this.buildPermalinkDisplayUrl( baseUrl, newSlug, urlLink.textContent || '' );

		// Keep href as the current saved link and only update display text.
		urlLink.textContent = newUrl;
		editor.classList.add( 'lp-hidden' );
		display.classList.remove( 'lp-hidden' );
	}

	handlePermalinkCancel( args ) {
		if ( ! this.isLessonContext() ) {
			return;
		}

		const { e, target } = args;
		if ( e ) e.preventDefault();

		const trigger = target?.closest( BuilderEditLesson.selectors.elPermalinkCancelBtn );
		const lessonRoot = trigger?.closest( BuilderEditLesson.selectors.elDataLesson ) ||
			document.querySelector( BuilderEditLesson.selectors.elDataLesson );

		if ( ! lessonRoot ) {
			return;
		}

		const display = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkDisplay );
		const editor = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkEditor );
		const input = lessonRoot.querySelector( BuilderEditLesson.selectors.elPermalinkSlugInput );

		if ( ! display || ! editor || ! input ) {
			return;
		}

		input.value = input.dataset.originalValue || '';
		editor.classList.add( 'lp-hidden' );
		display.classList.remove( 'lp-hidden' );
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

		const targetStatus = elBtnUpdateLesson.dataset.status || 'publish';

		const dataSend = {
			...lessonData,
			action: 'builder_update_lesson',
			args: {
				id_url: 'builder-update-lesson',
			},
			lesson_status: targetStatus,
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
					this.updateActionButtons( targetStatus );

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
						if ( data.status === 'trash' || data.status === 'draft' ) {
							const curriculumItem = document.querySelector( `.lesson-item[data-lesson-id="${lessonData.lesson_id}"]` );
							if ( curriculumItem ) {
								const elCurriculumLesson = curriculumItem.closest( '.lesson' );
								if ( elCurriculumLesson ) {
									elCurriculumLesson.remove();
								}
							}
						}
					}

					this.updatePermalinkUIAfterSave( data );

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

		// Check if published to show confirm unpublish modal
		const statusEl = document.querySelector( BuilderEditLesson.selectors.elLessonStatus );
		const isPublished = statusEl && statusEl.classList.contains( 'publish' );
		if ( isPublished ) {
			const confirmMsg = elBtnDraftLesson.dataset.confirmUnpublish || 'Saving as draft will unpublish this item. Are you sure?';
			if ( ! confirm( confirmMsg ) ) {
				return;
			}
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
						if ( data.status === 'trash' || data.status === 'draft' ) {
							const curriculumItem = document.querySelector( `.lesson-item[data-lesson-id="${lessonData.lesson_id}"]` );
							if ( curriculumItem ) {
								const elCurriculumLesson = curriculumItem.closest( '.lesson' );
								if ( elCurriculumLesson ) {
									elCurriculumLesson.remove();
								}
							}
						}
					}

					this.updatePermalinkUIAfterSave( data );

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
						if ( data.status === 'trash' || data.status === 'draft' ) {
							const curriculumItem = document.querySelector( `.lesson-item[data-lesson-id="${lessonData.lesson_id}"]` );
							if ( curriculumItem ) {
								const elCurriculumLesson = curriculumItem.closest( '.lesson' );
								if ( elCurriculumLesson ) {
									elCurriculumLesson.remove();
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
				lpUtils.lpSetLoadingEl( elBtnTrashLesson, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
