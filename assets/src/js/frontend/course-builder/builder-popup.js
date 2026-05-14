/**
 * Builder Popup Handler
 * Handles AJAX popup loading for lesson, quiz, and question builders.
 *
 * @since 4.3.0
 * @version 1.0.1
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';
import { SWAL_ICON_TRASH_DRAFT } from './swal-icons.js';
import { BuilderEditQuiz } from './builder-quiz/builder-edit-quiz.js';
import { BuilderEditQuestion } from './builder-question/builder-edit-question.js';
import { BuilderMaterial } from './builder-lesson/builder-material.js';

export class BuilderPopup {
	constructor() {
		this.popupContainer = null;
		this.currentType = null;
		this.currentId = null;
		this.currentTemplate = '';
		this.isNewItem = false;
		this.savedData = null;
		this.openContext = this.getDefaultOpenContext();
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
		resizeBtn: '.lp-builder-popup__resize',
		cancelBtn: '.lp-builder-popup__btn--cancel',
		saveBtn: '.lp-builder-popup__btn--save',
		draftBtn: '.lp-builder-popup__btn--draft',
		trashBtn: '.lp-builder-popup__btn--trash',
		tabs: '.lp-builder-popup__tabs',
		tab: '.lp-builder-popup__tab',
		tabPane: '.lp-builder-popup__tab-pane',
		permalinkSlugInput: '.cb-permalink-slug-input',
		permalinkUrl: '.cb-permalink-url',
		permalinkBaseUrl: '#cb-permalink-base-url',
		permalinkDisplay: '.cb-permalink-display',
		permalinkEditor: '.cb-permalink-editor',
		permalinkRoot: '.cb-item-edit-permalink, .cb-course-edit-permalink',
		permalinkPlaceholder: '.cb-item-edit-permalink__placeholder',
		// Trigger buttons
		popupTrigger:
			'[data-popup-lesson], [data-popup-quiz], [data-popup-question], [data-add-new-lesson], [data-template][data-popup-type]',
		triggerLesson: '[data-popup-lesson]',
		triggerQuiz: '[data-popup-quiz]',
		triggerQuestion: '[data-popup-question]',
		// Add new buttons
		addNewLesson: '[data-add-new-lesson]',
	};

	init() {
		let popupContainer = document.querySelector( BuilderPopup.selectors.popupContainer );

		if ( ! popupContainer ) {
			popupContainer = document.createElement( 'div' );
			popupContainer.id = 'lp-builder-popup-container';
			document.body.appendChild( popupContainer );
		}

		this.popupContainer = popupContainer;
		this.events();
	}

	events() {
		if ( BuilderPopup._loadedEvents ) {
			return;
		}
		BuilderPopup._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderPopup.selectors.popupTrigger,
				class: this,
				callBack: this.openPopup.name,
			},
			{
				selector: `${ BuilderPopup.selectors.closeBtn }, ${ BuilderPopup.selectors.cancelBtn }, ${ BuilderPopup.selectors.popupOverlay }`,
				class: this,
				callBack: this.closePopup.name,
				conditionBeforeCallBack: () => this.isPopupOpen(),
			},
			{
				selector: BuilderPopup.selectors.resizeBtn,
				class: this,
				callBack: this.toggleFullscreen.name,
				conditionBeforeCallBack: () => this.isPopupOpen(),
			},
			{
				selector: BuilderPopup.selectors.tab,
				class: this,
				callBack: this.switchTab.name,
				conditionBeforeCallBack: () => this.isPopupOpen(),
			},
			{
				selector: BuilderPopup.selectors.saveBtn,
				class: this,
				callBack: this.handleSave.name,
				conditionBeforeCallBack: () => this.isPopupOpen(),
			},
			{
				selector: BuilderPopup.selectors.draftBtn,
				class: this,
				callBack: this.handleDraft.name,
				conditionBeforeCallBack: () => this.isPopupOpen(),
			},
			{
				selector: BuilderPopup.selectors.trashBtn,
				class: this,
				callBack: this.handleTrash.name,
				conditionBeforeCallBack: () => this.isPopupOpen(),
			},
		] );

		lpUtils.eventHandlers( 'keydown', [
			{
				selector: 'body',
				class: this,
				callBack: this.closePopup.name,
				conditionBeforeCallBack: ( args ) => args.e.key === 'Escape' && this.isPopupOpen(),
			},
		] );
	}

	/**
	 * Toggle fullscreen mode for popup
	 */
	toggleFullscreen() {
		const popup = this.popupContainer.querySelector( BuilderPopup.selectors.popup );
		if ( ! popup ) {
			return;
		}

		popup.classList.toggle( 'lp-builder-popup--fullscreen' );

		// Update resize button icon
		const resizeBtn = popup.querySelector( BuilderPopup.selectors.resizeBtn );
		if ( resizeBtn ) {
			const icon = resizeBtn.querySelector( 'i' );
			if ( icon ) {
				const isFullscreen = popup.classList.contains( 'lp-builder-popup--fullscreen' );
				icon.classList.toggle( 'lp-icon-expand', ! isFullscreen );
				icon.classList.toggle( 'lp-icon-compress', isFullscreen );
			}
		}

		document.dispatchEvent(
			new CustomEvent( 'lp-builder-popup-fullscreen-toggled', {
				detail: {
					isFullscreen: popup.classList.contains( 'lp-builder-popup--fullscreen' ),
					type: this.currentType,
					id: this.currentId,
				},
			} )
		);
	}

	openPopup( args ) {
		const { target } = args;
		const triggerEl = target.closest( BuilderPopup.selectors.popupTrigger );
		if ( ! triggerEl ) {
			return;
		}

		let type = '';
		let id = 0;

		if ( triggerEl.matches( BuilderPopup.selectors.addNewLesson ) ) {
			type = 'lesson';
		} else if ( triggerEl.dataset.popupType ) {
			type = triggerEl.dataset.popupType;
			id = parseInt( triggerEl.dataset.popupId ) || 0;
		} else if ( triggerEl.dataset.popupLesson !== undefined ) {
			type = 'lesson';
			id = parseInt( triggerEl.dataset.popupLesson ) || 0;
		} else if ( triggerEl.dataset.popupQuiz !== undefined ) {
			type = 'quiz';
			id = parseInt( triggerEl.dataset.popupQuiz ) || 0;
		} else if ( triggerEl.dataset.popupQuestion !== undefined ) {
			type = 'question';
			id = parseInt( triggerEl.dataset.popupQuestion ) || 0;
		}

		if ( ! type ) {
			return;
		}

		this.showPopup( triggerEl, type, id, this.resolveOpenContext( triggerEl ) );
	}

	getDefaultOpenContext() {
		return {
			isCurriculum: false,
			courseId: 0,
		};
	}

	resolveOpenContext( triggerEl ) {
		const isCurriculumContainer =
			!! triggerEl?.closest( '#lp-course-edit-curriculum' ) ||
			!! triggerEl?.closest( '.lp-edit-curriculum-wrap' );
		const courseId = parseInt( triggerEl?.dataset?.courseId ) || 0;
		const isCurriculum = isCurriculumContainer && courseId > 0;

		return {
			isCurriculum,
			courseId,
		};
	}

	showPopup( triggerEl, type, id, openContext = null ) {
		const templateId = triggerEl?.dataset?.template || '';
		const templateEl = document.querySelector( templateId );
		if ( ! templateId || ! templateEl ) {
			return;
		}

		this.currentType = type;
		this.currentId = id;
		this.currentTemplate = templateId;
		this.isNewItem = id === 0;
		this.openContext = openContext ? { ...openContext } : this.getDefaultOpenContext();

		if ( ! this.popupContainer ) {
			return;
		}

		this.popupContainer.innerHTML = templateEl.innerHTML;
		this.popupContainer.classList.add( 'active' );
		document.body.classList.add( 'lp-popup-open' );

		const elLPTarget = this.popupContainer.querySelector( '.lp-target' );
		if ( ! elLPTarget || ! window.lpAJAXG ) {
			return;
		}

		const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		dataSend.args = dataSend.args || {};
		dataSend.args[ `${ type }_id` ] = id;
		window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

		this.requestPopupContent( dataSend );
	}

	reloadCurrentPopup() {
		if ( ! this.currentTemplate || ! this.currentType ) {
			return;
		}

		const templateEl = document.querySelector( this.currentTemplate );
		if ( ! templateEl ) {
			return;
		}

		if ( ! this.popupContainer ) {
			return;
		}

		this.popupContainer.innerHTML = templateEl.innerHTML;
		this.popupContainer.classList.add( 'active' );
		document.body.classList.add( 'lp-popup-open' );

		const elLPTarget = this.popupContainer.querySelector( '.lp-target' );
		if ( ! elLPTarget || ! window.lpAJAXG ) {
			return;
		}

		const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		dataSend.args = dataSend.args || {};
		dataSend.args[ `${ this.currentType }_id` ] = this.currentId;
		window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

		this.requestPopupContent( dataSend );
	}

	requestPopupContent( dataSend ) {
		if ( ! this.popupContainer || ! window.lpAJAXG ) {
			return;
		}

		const callBack = {
			success: ( response ) => {
				const { status, data } = response;
				if ( status === 'success' && data?.content ) {
					this.popupContainer.innerHTML = data.content;
					this.popupContainer.classList.add( 'active' );
					document.body.classList.add( 'lp-popup-open' );

					this.loadedTabAssets.clear();
					this.initializedTabs.clear(); // Clear initialized tabs cache
					const ajaxElements = this.popupContainer.querySelectorAll(
						'.lp-load-ajax-element.loaded'
					);
					ajaxElements.forEach( ( el ) => el.classList.remove( 'loaded' ) );

					setTimeout( () => window.lpAJAXG.getElements(), 50 );

					const popup = this.popupContainer.querySelector( BuilderPopup.selectors.popup );
					const activeTab = popup?.querySelector( `${ BuilderPopup.selectors.tab }.active` );
					const activeTabName = activeTab?.dataset.tab || 'overview';
					const activePane = popup?.querySelector(
						`${ BuilderPopup.selectors.tabPane }[data-tab="${ activeTabName }"]`
					);

					if ( activePane ) {
						this.loadTabAssets( activeTabName, activePane );
					}

					if ( activeTabName === 'overview' ) {
						setTimeout( () => this.initTinyMCE(), 50 );
					}

					if ( this.currentType === 'quiz' ) {
						if ( ! this.builderEditQuiz ) {
							this.builderEditQuiz = new BuilderEditQuiz();
						}

						if ( activeTabName === 'questions' ) {
							const tabKey = `${ this.currentType }-${ activeTabName }`;
							setTimeout( () => {
								this.triggerAjaxLoadForTab( activePane );
								this.builderEditQuiz.reinit( this.popupContainer );
								this.initializedTabs.set( tabKey, true );
							}, 100 );
						}
					} else if ( this.currentType === 'question' ) {
						if ( ! this.builderEditQuestion ) {
							this.builderEditQuestion = new BuilderEditQuestion();
						}

						if ( activeTabName === 'settings' ) {
							const tabKey = `${ this.currentType }-${ activeTabName }`;
							setTimeout( () => {
								this.triggerAjaxLoadForTab( activePane );
								this.builderEditQuestion.reinit( this.popupContainer );
								this.initializedTabs.set( tabKey, true );
							}, 100 );
						}
					} else if ( this.currentType === 'lesson' ) {
						if ( ! this.builderMaterial ) {
							this.builderMaterial = new BuilderMaterial();
						}

						if ( activeTabName === 'settings' ) {
							const tabKey = `${ this.currentType }-${ activeTabName }`;
							setTimeout( () => {
								this.triggerAjaxLoadForTab( activePane );
								this.builderMaterial.reinit( this.popupContainer );
								this.initializedTabs.set( tabKey, true );
							}, 100 );
						}
					}

					document.dispatchEvent(
						new CustomEvent( 'lp-builder-popup-opened', {
							detail: { type: this.currentType, id: this.currentId, isNew: this.isNewItem },
						} )
					);
				} else {
					lpToastify.show( response.message || 'Failed to load popup', 'error' );
					this.popupContainer.innerHTML = '';
					this.popupContainer.classList.remove( 'active' );
					document.body.classList.remove( 'lp-popup-open' );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || 'Failed to load popup', 'error' );
				this.popupContainer.innerHTML = '';
				this.popupContainer.classList.remove( 'active' );
				document.body.classList.remove( 'lp-popup-open' );
			},
			completed: () => {
				// Loading hidden in success/error
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
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
			this.updateListItem( closedType, closedId, savedData );
		}

		document.dispatchEvent(
			new CustomEvent( 'lp-builder-popup-closed', {
				detail: { type: closedType, id: closedId, savedData },
			} )
		);

		this.currentType = null;
		this.currentId = null;
		this.currentTemplate = '';
		this.isNewItem = false;
		this.savedData = null;
		this.openContext = this.getDefaultOpenContext();
	}

	/**
	 * Update list item in the background list
	 */
	updateListItem( type, id, savedData ) {
		if ( ! type || ! id || ! savedData ) {
			return;
		}

		const { formData, data, wasNewItem } = savedData;
		let listItems = this.findListItems( type, id );

		// New items created from popup need to be inserted into the list first.
		if ( ( ! listItems || listItems.length === 0 ) && wasNewItem ) {
			const newItem = this.insertNewListItem( type, id, data?.list_item_html );
			if ( newItem ) {
				listItems = [ newItem ];
			}
		}

		if ( ! listItems || listItems.length === 0 ) {
			return;
		}

		listItems.forEach( ( listItem ) => {
			let currentItem = listItem;

			// Replace the entire item HTML if returned from the server
			if ( data?.section_item_html && currentItem.classList.contains( 'section-item' ) ) {
				const template = document.createElement( 'template' );
				template.innerHTML = data.section_item_html.trim();
				const newListItem = template.content.firstElementChild;
				if ( newListItem ) {
					currentItem.replaceWith( newListItem );
					currentItem = newListItem;
				}
			} else if ( data?.list_item_html && ! currentItem.classList.contains( 'section-item' ) ) {
				const template = document.createElement( 'template' );
				template.innerHTML = data.list_item_html.trim();
				const newListItem = template.content.firstElementChild;
				if ( newListItem ) {
					currentItem.replaceWith( newListItem );
					currentItem = newListItem;
				}
			} else {
				// Fallback: manually update elements if HTML replacement isn't used
				// Update title
				const newTitle = formData[ `${ type }_title` ];
				if ( newTitle ) {
					this.updateElementText(
						currentItem,
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
							'.lp-item-title-input',
						],
						newTitle
					);
				}

				// Update status
				if ( data?.status ) {
					this.updateElementClass(
						currentItem,
						[ `.${ type }-status`, '.item-status', '.post-status' ],
						data.status
					);
				}

				if ( type === 'lesson' ) {
					const duration = formData._lp_duration || data?.duration;
					if ( duration ) {
						this.updateDuration( currentItem, duration );
					}

					const preview = formData._lp_preview || data?.preview;
					const isPreview = preview === 'yes' || preview === true || preview === '1';
					const previewEl = currentItem.querySelector(
						'.lp-btn-set-preview-item a, .course-item-preview'
					);
					if ( previewEl ) {
						if ( isPreview ) {
							previewEl.classList.remove( 'lp-icon-eye-slash' );
							previewEl.classList.add( 'lp-icon-eye' );
						} else {
							previewEl.classList.remove( 'lp-icon-eye' );
							previewEl.classList.add( 'lp-icon-eye-slash' );
						}
					}

					const checkbox = currentItem.querySelector( 'input[type="checkbox"].preview-checkbox' );
					if ( checkbox ) {
						checkbox.checked = isPreview;
					}

					currentItem.classList.toggle( 'is-preview', isPreview );
					currentItem.classList.toggle( 'preview-item', isPreview );
				} else if ( type === 'quiz' ) {
					const duration = formData._lp_duration || data?.duration;
					if ( duration ) {
						this.updateDuration( currentItem, duration );
					}

					const questionCount = data?.question_count || data?.questions_count;
					if ( questionCount !== null && questionCount !== undefined ) {
						const questionCountEl = currentItem.querySelector( '.question-count' );
						if ( questionCountEl ) {
							questionCountEl.textContent = `${ questionCount } ${
								questionCount === 1 ? 'Question' : 'Questions'
							}`;
						}
					}

					const passingGrade = formData._lp_passing_grade || data?.passing_grade;
					if ( passingGrade ) {
						const passingGradeEl = currentItem.querySelector( '.passing-grade' );
						if ( passingGradeEl ) {
							passingGradeEl.textContent = `${ passingGrade }%`;
						}
					}
				} else if ( type === 'question' ) {
					const questionType = formData._lp_type || data?.type;
					if ( questionType ) {
						const typeMap = {
							true_or_false: 'True or False',
							single_choice: 'Single Choice',
							multi_choice: 'Multi Choice',
							fill_in_blanks: 'Fill in Blanks',
						};

						this.updateElementText(
							currentItem,
							[ '.question-type', '.item-type' ],
							typeMap[ questionType ] || questionType
						);

						const typeClasses = [
							'true_or_false',
							'single_choice',
							'multi_choice',
							'fill_in_blanks',
						];
						typeClasses.forEach( ( cls ) => currentItem.classList.remove( cls ) );
						currentItem.classList.add( questionType );
					}

					const mark = formData._lp_mark || data?.mark;
					if ( mark ) {
						const questionMarkEl = currentItem.querySelector( '.question-mark' );
						if ( questionMarkEl ) {
							questionMarkEl.textContent = mark;
						}
					}
				}
			}
		} );

		document.dispatchEvent(
			new CustomEvent( 'lp-builder-list-item-updated', {
				detail: { type, id, formData, data },
			} )
		);
	}

	/**
	 * Find all instances of a list item by type and ID
	 */
	findListItems( type, id ) {
		const selectors = [
			`[data-${ type }-id="${ id }"]`,
			`[data-id="${ id }"]`,
			`[data-popup-${ type }="${ id }"]`,
			`[data-item-id="${ id }"]`,
			`.section-item[data-item-id="${ id }"]`,
			`.lp-${ type }-item[data-id="${ id }"]`,
		];

		const foundItems = new Set();

		for ( const selector of selectors ) {
			const items = document.querySelectorAll( selector );
			for ( const item of items ) {
				// Ensure we don't select elements inside the popup itself
				if ( ! item.closest( '#lp-builder-popup-container' ) ) {
					// Exclude elements that are merely trigger buttons but not the list item container itself
					// If it's just a generic button to open a popup, it might not be the actual item container.
					// However, some UI lists use the trigger button as the container. We rely on the DOM structure.
					// We can assume `.section-item` and `.lp-lesson-item` and `.cb-list-item` are containers.
					if (
						item.classList.contains( 'section-item' ) ||
						item.classList.contains( `lp-${ type }-item` ) ||
						item.classList.contains( 'list-item' ) ||
						item.classList.contains( 'cb-list-item' ) ||
						item.tagName === 'LI'
					) {
						foundItems.add( item );
					} else {
						// If it's a wrapper, like a div in Content Bank
						if ( item.closest( 'ul' ) ) {
							foundItems.add( item );
						}
					}
				}
			}
		}

		return Array.from( foundItems );
	}

	/**
	 * Insert a newly created list item into the current tab list.
	 */
	insertNewListItem( type, id, listItemHtml ) {
		if ( ! listItemHtml ) {
			return null;
		}

		const existingListItems = this.findListItems( type, id );
		if ( existingListItems && existingListItems.length > 0 ) {
			return existingListItems[ 0 ];
		}

		const listContainer = this.findListContainer( type );
		if ( ! listContainer ) {
			return null;
		}

		const template = document.createElement( 'template' );
		template.innerHTML = listItemHtml.trim();
		const newListItem = template.content.firstElementChild;

		if ( ! newListItem ) {
			return null;
		}

		listContainer.prepend( newListItem );
		const highlightClassByType = {
			lesson: 'highlight-new-lesson',
			quiz: 'highlight-new-quiz',
			question: 'highlight-new-question',
		};
		const highlightClass = highlightClassByType[ type ];

		if ( highlightClass ) {
			newListItem.classList.add( highlightClass );
			newListItem.scrollIntoView( {
				behavior: 'smooth',
				block: 'nearest',
			} );

			setTimeout( () => {
				newListItem.classList.remove( highlightClass );
			}, 1500 );
		}

		const finalListItems = this.findListItems( type, id );
		return finalListItems && finalListItems.length > 0 ? finalListItems[ 0 ] : newListItem;
	}

	/**
	 * Find list container for type; create one if tab currently shows empty message.
	 */
	findListContainer( type ) {
		const listSelectorByType = {
			lesson: '.cb-list-lesson',
			quiz: '.cb-list-quiz',
			question: '.cb-list-question',
		};

		const tabSelectorByType = {
			lesson: '.courses-builder__lesson-tab',
			quiz: '.courses-builder__quiz-tab',
			question: '.courses-builder__question-tab',
		};

		const listClassByType = {
			lesson: 'cb-list-lesson',
			quiz: 'cb-list-quiz',
			question: 'cb-list-question',
		};

		const listSelector =
			listSelectorByType[ type ] || `.cb-list-${ type }, [data-builder-list="${ type }"]`;
		if ( ! listSelector ) {
			return null;
		}

		const existingList = document.querySelector( listSelector );
		if ( existingList ) {
			return existingList;
		}

		const tabContainer = document.querySelector(
			tabSelectorByType[ type ] || `.courses-builder__${ type }-tab, [data-builder-tab="${ type }"]`
		);
		const listClass = listClassByType[ type ] || `cb-list-${ type }`;

		if ( ! tabContainer || ! listClass ) {
			return null;
		}

		const emptyMessage = tabContainer.querySelector( '.learn-press-message' );
		if ( emptyMessage ) {
			emptyMessage.remove();
		}

		const listContainer = document.createElement( 'ul' );
		listContainer.className = listClass;
		tabContainer.appendChild( listContainer );

		return listContainer;
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
	 * Check if popup is open
	 */
	isPopupOpen() {
		return this.popupContainer?.classList.contains( 'active' );
	}

	/**
	 * Switch tab with dynamic asset loading
	 */
	switchTab( args ) {
		const tabEl = args?.target ? args.target.closest( BuilderPopup.selectors.tab ) : args;
		if ( ! tabEl ) {
			return;
		}

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
		const tabKey = `${ this.currentType }-${ tabName }`;

		if ( ! this.initializedTabs.has( tabKey ) ) {
			if ( tabName === 'overview' ) {
				setTimeout( () => this.initTinyMCE(), 100 );
				this.initializedTabs.set( tabKey, true );
			} else if ( tabName === 'questions' && this.currentType === 'quiz' ) {
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

		document.dispatchEvent(
			new CustomEvent( 'lp-builder-tab-switched', {
				detail: { tabName, type: this.currentType, id: this.currentId },
			} )
		);
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
					content_style:
						"body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; font-size: 14px; line-height: 1.6; color: #1e1e1e; }",
					plugins:
						'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wplink wptextpattern',
					toolbar1:
						'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,spellchecker,wp_adv',
					toolbar2:
						'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
					wordpress_adv_hidden: true,
				},
				quicktags: { buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close' },
				mediaButtons: true,
			} );
		} else {
			tinymce.init( {
				selector: '#' + editorId,
				height: 300,
				menubar: false,
				content_style:
					"body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; font-size: 14px; line-height: 1.6; color: #1e1e1e; }",
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

	getStatusFromPublishPanel( fallbackStatus = 'publish' ) {
		if ( ! this.currentType || ! this.popupContainer ) {
			return fallbackStatus;
		}

		const statusSelect = this.popupContainer.querySelector(
			`#cb-${ this.currentType }-publish-status`
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

	syncPublishPanelStatus( status ) {
		if ( ! this.currentType || ! this.popupContainer ) {
			return;
		}

		const statusSelect = this.popupContainer.querySelector(
			`#cb-${ this.currentType }-publish-status`
		);
		if ( ! statusSelect ) {
			return;
		}

		statusSelect.value = status === 'publish' ? 'publish' : 'draft';
	}

	/**
	 * Handle save action
	 */
	handleSave( args ) {
		const saveBtn = args?.target ? args.target.closest( BuilderPopup.selectors.saveBtn ) : args;
		if ( ! saveBtn ) {
			return;
		}

		if ( ! this.currentType ) {
			return;
		}

		const publishLabel = ( saveBtn?.dataset?.titlePublish || '' ).toString().trim().toLowerCase();
		const currentLabel = ( saveBtn?.textContent || '' ).toString().trim().toLowerCase();
		const forcePublish = !! publishLabel && currentLabel === publishLabel;
		const targetStatus = forcePublish ? 'publish' : this.getStatusFromPublishPanel( 'publish' );
		if ( forcePublish ) {
			this.syncPublishPanelStatus( 'publish' );
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
			[ `${ this.currentType }_status` ]: targetStatus,
			return_html: 'yes',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;

				lpToastify.show( message, status );

				if ( status === 'success' ) {
					this.handleSaveSuccess( data, formData, wasNewItem );
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
	 * Handle save as draft action
	 */
	async handleDraft( args ) {
		const draftBtn = args?.target ? args.target.closest( BuilderPopup.selectors.draftBtn ) : args;
		if ( ! draftBtn ) {
			return;
		}

		if ( ! this.currentType ) {
			return;
		}

		// Check if published to show confirm unpublish modal
		const statusEl = this.popupContainer.querySelector( `.${ this.currentType }-status` );
		const isPublished = statusEl && statusEl.classList.contains( 'publish' );
		if ( isPublished ) {
			const confirmMsg =
				draftBtn.dataset.confirmUnpublish ||
				'Saving as draft will unpublish this item from the course.';
			const result = await SweetAlert.fire( {
				title: 'Are you sure?',
				text: confirmMsg,
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

		this.syncAllTinyMCE();

		const formData = this.getFormData();
		const validation = this.validateFormData( formData );

		if ( ! validation.valid ) {
			lpToastify.show( validation.errors.join( '. ' ), 'error' );
			return;
		}

		lpUtils.lpSetLoadingEl( draftBtn, 1 );

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
			[ `${ this.currentType }_status` ]: 'draft',
			return_html: 'yes',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;

				lpToastify.show( message, status );

				if ( status === 'success' ) {
					this.handleSaveSuccess( data, formData, wasNewItem );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || 'Save draft failed', 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( draftBtn, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Handle save success
	 */
	handleSaveSuccess( data, formData, wasNewItem ) {
		if ( data?.button_title ) {
			const primarySaveBtn = this.popupContainer.querySelector( BuilderPopup.selectors.saveBtn );
			if ( primarySaveBtn ) {
				primarySaveBtn.textContent = data.button_title;
			}
		}

		// Update status
		if ( data?.status ) {
			this.syncPublishPanelStatus( data.status );

			const statusEl = this.popupContainer.querySelector( `.${ this.currentType }-status` );
			if ( statusEl ) {
				statusEl.className = `${ this.currentType }-status ${ data.status }`;
				statusEl.textContent = data.status;
			}

			if ( this.shouldRemoveFromCurriculum( data.status ) ) {
				this.removeItemFromCurriculum( this.currentId );
			}

			if ( this.shouldRemoveQuestionFromAssignedQuiz( data.status ) ) {
				this.removeQuestionFromAssignedQuiz( this.currentId );
			}
		}

		this.updatePermalinkUIAfterSave( data );

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

		// Update the list item immediately
		this.updateListItem( this.currentType, this.currentId, this.savedData );

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
				this.reloadCurrentPopup();
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
	async handleTrash( args ) {
		const trashBtn = args?.target ? args.target.closest( BuilderPopup.selectors.trashBtn ) : args;
		if ( ! trashBtn ) {
			return;
		}

		if ( ! this.currentType || ! this.currentId ) {
			return;
		}

		const confirmMsg =
			trashBtn.dataset.confirmTrash ||
			'Moving it to the trash will cause this item to be removed from the course.';
		const result = await SweetAlert.fire( {
			title: 'Are you sure?',
			text: confirmMsg,
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
		if (
			!! this.openContext?.isCurriculum &&
			( parseInt( this.openContext?.courseId ) || 0 ) > 0
		) {
			dataSend.course_id = parseInt( this.openContext.courseId ) || 0;
		}

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

						if ( this.shouldRemoveFromCurriculum( data.status ) ) {
							this.removeItemFromCurriculum( this.currentId );
						}

						if ( this.shouldRemoveQuestionFromAssignedQuiz( data.status ) ) {
							this.removeQuestionFromAssignedQuiz( this.currentId );
						}
					}

					this.updatePermalinkUIAfterSave( data );

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

	shouldRemoveFromCurriculum( status ) {
		const normalizedStatus = ( status || '' ).toString().toLowerCase();
		const removableStatuses = [ 'draft', 'trash' ];

		return (
			!! this.openContext?.isCurriculum &&
			removableStatuses.includes( normalizedStatus ) &&
			( parseInt( this.currentId ) || 0 ) > 0
		);
	}

	shouldRemoveQuestionFromAssignedQuiz( status ) {
		const normalizedStatus = ( status || '' ).toString().toLowerCase();
		return (
			this.currentType === 'question' &&
			[ 'draft', 'trash' ].includes( normalizedStatus ) &&
			( parseInt( this.currentId ) || 0 ) > 0
		);
	}

	removeItemFromCurriculum( itemId ) {
		const parsedItemId = parseInt( itemId ) || 0;
		if ( parsedItemId <= 0 ) {
			return;
		}

		const curriculumRoot =
			document.querySelector( '#lp-course-edit-curriculum' ) ||
			document.querySelector( '.lp-edit-curriculum-wrap' );
		if ( ! curriculumRoot ) {
			return;
		}

		const items = curriculumRoot.querySelectorAll(
			`.section-item[data-item-id="${ parsedItemId }"]`
		);
		if ( ! items.length ) {
			return;
		}

		const sectionsToUpdate = new Set();

		items.forEach( ( item ) => {
			const section = item.closest( '.section' );
			if ( section ) {
				sectionsToUpdate.add( section );
			}

			item.remove();
		} );

		this.syncCurriculumCounters( curriculumRoot, sectionsToUpdate );
	}

	removeQuestionFromAssignedQuiz( questionId ) {
		const parsedQuestionId = parseInt( questionId ) || 0;
		if ( parsedQuestionId <= 0 ) {
			return;
		}

		const questionItems = document.querySelectorAll(
			`.lp-question-item[data-question-id="${ parsedQuestionId }"]`
		);

		questionItems.forEach( ( item ) => item.remove() );
	}

	syncCurriculumCounters( curriculumRoot, sectionsToUpdate = new Set() ) {
		if ( ! curriculumRoot ) {
			return;
		}

		const allItems = curriculumRoot.querySelectorAll( '.section-item:not(.clone)' );
		const totalItemsCount = allItems.length;
		const totalItemsEl = curriculumRoot.querySelector( '.total-items' );

		if ( totalItemsEl ) {
			totalItemsEl.dataset.count = totalItemsCount;

			const totalItemsCountEl = totalItemsEl.querySelector( '.count' );
			if ( totalItemsCountEl ) {
				totalItemsCountEl.textContent = totalItemsCount;
			}
		}

		const sections =
			sectionsToUpdate.size > 0
				? Array.from( sectionsToUpdate )
				: Array.from( curriculumRoot.querySelectorAll( '.section' ) );

		sections.forEach( ( section ) => {
			const sectionItemsCountEl = section.querySelector( '.section-items-counts' );
			if ( ! sectionItemsCountEl ) {
				return;
			}

			const sectionItemsCount = section.querySelectorAll( '.section-item:not(.clone)' ).length;
			sectionItemsCountEl.dataset.count = sectionItemsCount;

			const countEl = sectionItemsCountEl.querySelector( '.count' );
			if ( countEl ) {
				countEl.textContent = sectionItemsCount;
			}
		} );
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
		if (
			!! this.openContext?.isCurriculum &&
			( parseInt( this.openContext?.courseId ) || 0 ) > 0
		) {
			data.course_id = parseInt( this.openContext.courseId ) || 0;
		}

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

		// Capture permalink slug in overview tab (quiz/question popup).
		const permalinkInput = popup.querySelector(
			`input[name="${ this.currentType }_permalink"], #${ this.currentType }_permalink, ${ BuilderPopup.selectors.permalinkSlugInput }`
		);
		if ( permalinkInput && permalinkInput.value ) {
			data[ `${ this.currentType }_permalink` ] = permalinkInput.value;
		}

		return data;
	}

	updatePermalinkUIAfterSave( data = {} ) {
		if ( ! this.currentType || ! this.popupContainer ) {
			return;
		}

		const popup = this.popupContainer.querySelector( BuilderPopup.selectors.popup );
		if ( ! popup ) {
			return;
		}

		const slugInput = popup.querySelector(
			`input[name="${ this.currentType }_permalink"], #${ this.currentType }_permalink, ${ BuilderPopup.selectors.permalinkSlugInput }`
		);
		const permalinkRoot = popup.querySelector( BuilderPopup.selectors.permalinkRoot );
		const permalinkPlaceholder = permalinkRoot?.querySelector(
			BuilderPopup.selectors.permalinkPlaceholder
		);

		const responseSlug = data?.[ `${ this.currentType }_slug` ];
		if ( slugInput && responseSlug ) {
			slugInput.value = responseSlug;
			slugInput.dataset.originalValue = responseSlug;
		}

		const responsePermalink = data?.[ `${ this.currentType }_permalink` ];
		const isCourseItem = [ 'lesson', 'quiz' ].includes( this.currentType );
		const shouldShowUnavailable =
			data?.permalink_available === false ||
			( isCourseItem &&
				( data?.status === 'draft' || data?.status === 'trash' || ! responsePermalink ) );

		if ( shouldShowUnavailable ) {
			if ( ! permalinkRoot ) {
				return;
			}

			const permalinkDisplay = permalinkRoot.querySelector(
				BuilderPopup.selectors.permalinkDisplay
			);
			const label =
				permalinkRoot.querySelector( '.cb-item-edit-permalink__label' ) ||
				permalinkRoot.querySelector( '.cb-permalink-label' );
			const editor = permalinkRoot.querySelector( BuilderPopup.selectors.permalinkEditor );
			let placeholder = permalinkPlaceholder;

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
				data?.permalink_notice ||
				'Permalink is only available if the item is already assigned to a course.';
			placeholder.classList.remove( 'lp-hidden' );

			if ( permalinkDisplay ) {
				permalinkDisplay.classList.add( 'lp-hidden' );
			}

			if ( editor ) {
				editor.classList.add( 'lp-hidden' );
			}

			return;
		}

		const urlLink = popup.querySelector( BuilderPopup.selectors.permalinkUrl );
		const permalinkDisplay = permalinkRoot?.querySelector(
			BuilderPopup.selectors.permalinkDisplay
		);
		const baseUrlInput = popup.querySelector( BuilderPopup.selectors.permalinkBaseUrl );
		const normalizedBaseUrl = typeof baseUrlInput?.value === 'string' ? baseUrlInput.value : '';
		const normalizedSlug = typeof responseSlug === 'string' ? responseSlug.trim() : '';
		let permalinkDisplayUrl = '';

		if ( normalizedBaseUrl && normalizedSlug ) {
			permalinkDisplayUrl = `${ normalizedBaseUrl }${ normalizedSlug }`;
		} else if ( typeof responsePermalink === 'string' ) {
			permalinkDisplayUrl = responsePermalink;
		}

		if ( permalinkPlaceholder ) {
			permalinkPlaceholder.classList.add( 'lp-hidden' );
		}

		if ( permalinkDisplay ) {
			permalinkDisplay.classList.remove( 'lp-hidden' );
		}

		if ( urlLink && responsePermalink ) {
			urlLink.href = responsePermalink;
			urlLink.textContent = permalinkDisplayUrl || responsePermalink;
		} else if ( urlLink && permalinkDisplayUrl ) {
			urlLink.textContent = permalinkDisplayUrl;
		}
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

		const selectors = {
			lesson: id ? `[data-popup-lesson="${ id }"]` : BuilderPopup.selectors.addNewLesson,
			quiz: id ? `[data-popup-quiz="${ id }"]` : '',
			question: id ? `[data-popup-question="${ id }"]` : '',
		};
		const triggerSelector =
			selectors[ type ] ||
			( id
				? `[data-popup-type="${ type }"][data-popup-id="${ id }"]`
				: `[data-popup-type="${ type }"][data-template]` );
		if ( ! triggerSelector ) {
			return;
		}

		const triggerEl = document.querySelector( triggerSelector );
		if ( ! triggerEl ) {
			return;
		}

		BuilderPopup._instance.showPopup(
			triggerEl,
			type,
			id,
			BuilderPopup._instance.resolveOpenContext( triggerEl )
		);
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
