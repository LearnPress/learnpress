import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { EditCourseCurriculum } from 'lpAssetsJsPath/admin/edit-course/edit-curriculum';
import { MetaboxExtraInfo } from './extra-info.js';
import { getFormState } from '../builder-form-state.js';

export class BuilderEditCourse {
	constructor() {
		// Only initialize on course edit pages, not quiz/question/lesson edit pages
		const isCourseEditPage = document.querySelector( '.cb-section__course-edit' );
		if ( ! isCourseEditPage ) {
			return;
		}
		this.init();
	}

	static selectors = {
		elTabLinks: '.lp-meta-box__course-tab__tabs li a',
		elTabItems: '.lp-meta-box__course-tab__tabs li',
		elTabPanels: '.lp-meta-box-course-panels',

		elDataCourse: '.cb-section__course-edit',
		elBtnUpdateCourse: '.cb-btn-update',
		elBtnHeaderSave: '.lp-cb-save-btn',
		elBtnDraftCourse: '.cb-btn-darft',
		elBtnPublishCourse: '.cb-btn-publish',
		elBtnMainAction: '.cb-btn-main-action',
		elBtnTrashCourse: '.cb-btn-trash',
		elBtnSaveSettings: '.cb-btn-save-settings',
		elDropdownToggle: '.cb-btn-dropdown-toggle',
		elDropdownMenu: '.cb-dropdown-menu',
		elHeaderActionsDropdown: '.cb-header-actions-dropdown',
		elTitleInput: '#title',
		elTitleCharCount: '.cb-course-edit-title__char-count',
		elDescEditor: '#course_description_editor',
		elDescWordCount: '.cb-course-edit-desc__word-count',
		elStatus: '.course-status',
		elFormSetting: '.lp-form-setting-course',

		elCategoryTabs: '#course_category-tabs li a',
		elCategoryPanels: '#taxonomy-course_category .tabs-panel',
		elBtnToggleAddCategory: '#course_category-add-toggle',
		elFormCategoryWrapper: '#course_category-add',
		elInputNewCategory: '#newcourse_category',
		elSelectParentCategory: '#newcourse_category_parent',
		elBtnSubmitCategory: '#course_category-add-submit',
		elCategoryChecklist: '#course_categorychecklist',

		elWrapperCheckBoxTag: '.cb-course-edit-tags__checkbox-wrapper',
		elFormTagAddNew: '.cb-course-edit-terms__form-add-tag',
		elBtnAddTagNew: '.cb-course-edit-tag__btn-add-new',
		elBtnCancelTagNew: '.cb-course-edit-tag__btn-cancel',
		elBtnSaveTag: '.cb-course-edit-tags__btn-save',
		elInputAddTag: '.cb-course-edit-tags__input',

		elBtnRemoveFeatured: '.cb-remove-featured-image',
		elBtnSetFeatured: '.cb-featured-image-dropzone:not(.has-image)',
		elBtnChangeFeatured: '.cb-change-featured-image',
		elFeaturedImageDropzone: '.cb-featured-image-dropzone',
		elFeaturedImageLink: '.cb-featured-image-link',
		elThumbnailInput: '#course_thumbnail_id',
		elFeaturedImageContainer: '.cb-featured-image-container',

		elPriceCourseData: '#price_course_data',
		elSaleDatesFields: '.lp_sale_dates_fields',
		elSalePriceScheduleBtn: '.lp_sale_price_schedule',
		elCancelSaleScheduleBtn: '.lp_cancel_sale_schedule',
		elRegularPriceInput: '#_lp_regular_price',
		elSalePriceInput: '#_lp_sale_price',
		elPriceInput: '#_lp_price',
		elFormField: '.form-field',
		elTipFloating: '.learn-press-tip-floating',
		elCategoryDiv: '#taxonomy-course_category',

		elCBHorizontalTabs: '.lp-cb-tabs__item',
		elCBTabPanels: '.lp-cb-tab-panel',

		// Permalink component
		elPermalinkDisplay: '.cb-permalink-display',
		elPermalinkEditor: '.cb-permalink-editor',
		elPermalinkEditBtn: '.cb-permalink-edit-btn',
		elPermalinkOkBtn: '.cb-permalink-ok-btn',
		elPermalinkCancelBtn: '.cb-permalink-cancel-btn',
		elPermalinkSlugInput: '.cb-permalink-slug-input',
		elPermalinkUrl: '.cb-permalink-url',
		elPermalinkBaseUrl: '#cb-permalink-base-url',

		// Preview and Admin link elements
		elBtnPreview: '.cb-button.cb-btn-preview',
		elAdminLink: '.lp-cb-admin-link',
	};

	init() {
		const editCourseCurriculum = new EditCourseCurriculum();
		const metaboxExtraInfo = new MetaboxExtraInfo();
		editCourseCurriculum.init();
		metaboxExtraInfo.init();

		this.initTabs();
		this.initTabTitles();
		this.initCategoryTabs();
		this.initCategoryTree();
		this.initSalePriceLayout();
		this.initTitleCharCount();
		this.initDescWordCount();
		this.initHeaderActionsDropdown();
		this.events();
	}

	events() {
		if ( BuilderEditCourse._loadedEvents ) {
			return;
		}
		BuilderEditCourse._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderEditCourse.selectors.elTabLinks,
				class: this,
				callBack: this.handleTabClick.name,
			},
			{
				selector: BuilderEditCourse.selectors.elCategoryTabs,
				class: this,
				callBack: this.handleCategoryTabClick.name,
			},
			{
				selector: BuilderEditCourse.selectors.elCBHorizontalTabs,
				class: this,
				callBack: this.handleCBHorizontalTabClick.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnToggleAddCategory,
				class: this,
				callBack: this.toggleAddCategoryForm.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnSubmitCategory,
				class: this,
				callBack: this.addNewCategory.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnMainAction,
				class: this,
				callBack: this.updateCourse.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnHeaderSave,
				class: this,
				callBack: this.updateCourse.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnDraftCourse,
				class: this,
				callBack: this.updateCourse.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnPublishCourse,
				class: this,
				callBack: this.updateCourse.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnTrashCourse,
				class: this,
				callBack: this.trashCourse.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnSaveSettings,
				class: this,
				callBack: this.saveSettings.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnAddTagNew,
				class: this,
				callBack: this.toggleAddTagForm.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnCancelTagNew,
				class: this,
				callBack: this.toggleAddTagForm.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnSaveTag,
				class: this,
				callBack: this.addNewTag.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnSetFeatured,
				class: this,
				callBack: this.openMediaUploader.name,
			},
			{
				selector: BuilderEditCourse.selectors.elFeaturedImageLink,
				class: this,
				callBack: this.openMediaUploader.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnChangeFeatured,
				class: this,
				callBack: this.openMediaUploader.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnRemoveFeatured,
				class: this,
				callBack: this.removeFeaturedImage.name,
			},
			{
				selector: BuilderEditCourse.selectors.elSalePriceScheduleBtn,
				class: this,
				callBack: this.handleScheduleClick.name,
			},
			{
				selector: BuilderEditCourse.selectors.elCancelSaleScheduleBtn,
				class: this,
				callBack: this.handleCancelSchedule.name,
			},
			// Permalink component events
			{
				selector: BuilderEditCourse.selectors.elPermalinkEditBtn,
				class: this,
				callBack: this.handlePermalinkEdit.name,
			},
			{
				selector: BuilderEditCourse.selectors.elPermalinkOkBtn,
				class: this,
				callBack: this.handlePermalinkOk.name,
			},
			{
				selector: BuilderEditCourse.selectors.elPermalinkCancelBtn,
				class: this,
				callBack: this.handlePermalinkCancel.name,
			},
		] );

		lpUtils.eventHandlers( 'change', [
			{
				selector: '.lp-meta-box input, .forminp input',
				class: this,
				callBack: this.showHideOptionsDependency.name,
			},
			{
				selector: '#course_category-pop input[type="checkbox"]',
				class: this,
				callBack: this.handleMostUsedChange.name,
			},
		] );

		lpUtils.eventHandlers( 'input', [
			{
				selector: BuilderEditCourse.selectors.elPriceCourseData,
				class: this,
				callBack: this.validateSalePrice.name,
			},
			{
				selector: BuilderEditCourse.selectors.elTitleInput,
				class: this,
				callBack: this.handleTitleInput.name,
			},
		] );

		lpUtils.eventHandlers( 'keydown', [
			{
				selector: BuilderEditCourse.selectors.elInputNewCategory,
				class: this,
				callBack: this.addNewCategory.name,
				checkIsEventEnter: true,
			},
			{
				selector: BuilderEditCourse.selectors.elInputAddTag,
				class: this,
				callBack: this.addNewTag.name,
				checkIsEventEnter: true,
			},
		] );
	}

	initCategoryTabs() {
		const allTab = document.querySelector( '#course_category-tabs a[href="#course_category-all"]' );
		if ( allTab ) {
			allTab.closest( 'li' ).classList.add( 'tabs' );
			const panelAll = document.querySelector( '#course_category-all' );
			if ( panelAll ) panelAll.style.display = 'block';
		}
	}

	handleCategoryTabClick( args ) {
		const { e, target } = args;
		e.preventDefault();
		const link = target.closest( 'a' );
		if ( ! link ) return;

		const wrapper = document.querySelector( BuilderEditCourse.selectors.elCategoryDiv );
		const tabs = wrapper.querySelectorAll( '.category-tabs li' );
		const panels = wrapper.querySelectorAll( '.tabs-panel' );
		const targetId = link.getAttribute( 'href' );

		tabs.forEach( ( t ) => t.classList.remove( 'tabs', 'active' ) );
		panels.forEach( ( p ) => ( p.style.display = 'none' ) );

		link.closest( 'li' ).classList.add( 'tabs' );
		const targetPanel = wrapper.querySelector( targetId );
		if ( targetPanel ) targetPanel.style.display = 'block';

		if ( targetId === '#course_category-pop' ) {
			this.syncMostUsedTabs();
		}
	}

	syncMostUsedTabs() {
		const allPanel = document.querySelector( '#course_category-all' );
		const popPanel = document.querySelector( '#course_category-pop' );

		if ( ! allPanel || ! popPanel ) return;

		const popInputs = popPanel.querySelectorAll( 'input[type="checkbox"]' );

		popInputs.forEach( ( popInput ) => {
			const termId = popInput.value;
			const allInput = allPanel.querySelector( `input[value="${ termId }"]` );

			if ( allInput ) {
				popInput.checked = allInput.checked;
			}
		} );
	}

	handleMostUsedChange( args ) {
		const { target } = args;
		const termId = target.value;
		const isChecked = target.checked;

		const allInput = document.querySelector( `#course_category-all input[value="${ termId }"]` );

		if ( allInput ) {
			allInput.checked = isChecked;

			if ( isChecked ) {
				const parentLi = allInput.closest( 'li' );
				if ( parentLi ) parentLi.classList.add( 'children-visible' );

				let current = parentLi;
				while ( current && current.parentElement.closest( 'li' ) ) {
					current = current.parentElement.closest( 'li' );
					current.classList.add( 'children-visible' );
				}
			}
		}
	}

	toggleAddCategoryForm( args ) {
		const { e } = args;
		if ( e ) e.preventDefault();

		const form = document.querySelector( BuilderEditCourse.selectors.elFormCategoryWrapper );
		const input = document.querySelector( BuilderEditCourse.selectors.elInputNewCategory );

		if ( form ) {
			const isHidden = window.getComputedStyle( form ).display === 'none';

			if ( isHidden ) {
				form.style.display = 'block';
				if ( input )
					setTimeout( () => {
						input.focus();
						input.value = '';
					}, 100 );
			} else {
				form.style.display = 'none';
			}
		}
	}

	initCategoryTree() {
		const wrapper = document.querySelector( BuilderEditCourse.selectors.elCategoryDiv );
		if ( ! wrapper ) return;

		const childLists = wrapper.querySelectorAll( 'ul.children' );

		childLists.forEach( ( ul ) => {
			const parentLi = ul.parentElement;
			if ( parentLi && parentLi.tagName === 'LI' ) {
				this.addToggleBtnToLi( parentLi );
			}
		} );

		if ( ! BuilderEditCourse._treeEventAttached ) {
			wrapper.addEventListener( 'click', ( e ) => {
				if ( e.target.classList.contains( 'lp-cat-toggle' ) ) {
					e.preventDefault();
					e.stopPropagation();
					const li = e.target.closest( 'li' );
					li.classList.toggle( 'children-visible' );
				}
			} );

			wrapper.addEventListener( 'change', ( e ) => {
				if ( e.target.type === 'checkbox' ) {
					const li = e.target.closest( 'li' );
					if ( li && e.target.checked ) {
						li.classList.add( 'children-visible' );
					}
				}
			} );

			BuilderEditCourse._treeEventAttached = true;
		}

		this.expandCheckedCategories( wrapper );
	}

	expandCheckedCategories( wrapper ) {
		const checkedInputs = wrapper.querySelectorAll( 'input[type="checkbox"]:checked' );

		checkedInputs.forEach( ( input ) => {
			let currentLi = input.closest( 'li' );

			while ( currentLi ) {
				const parentUl = currentLi.closest( 'ul' );
				if ( parentUl && parentUl.classList.contains( 'children' ) ) {
					const parentCategoryLi = parentUl.closest( 'li' );

					if ( parentCategoryLi ) {
						parentCategoryLi.classList.add( 'children-visible' );
						currentLi = parentCategoryLi;
					} else {
						currentLi = null;
					}
				} else {
					currentLi = null;
				}
			}
		} );
	}

	addToggleBtnToLi( li ) {
		const label = li.querySelector( 'label' );
		if ( ! label || label.querySelector( '.lp-cat-toggle' ) ) return;

		const toggleBtn = document.createElement( 'span' );
		toggleBtn.className = 'lp-cat-toggle';
		toggleBtn.title = 'Toggle sub-categories';
		label.appendChild( toggleBtn );
	}

	addNewCategory( args ) {
		const { e } = args;
		if ( e ) e.preventDefault();

		const elInput = document.querySelector( BuilderEditCourse.selectors.elInputNewCategory );
		const elParent = document.querySelector( BuilderEditCourse.selectors.elSelectParentCategory );
		const btnSave = document.querySelector( BuilderEditCourse.selectors.elBtnSubmitCategory );

		const categoryName = elInput?.value?.trim();
		if ( ! categoryName ) {
			lpToastify.show( 'Please enter category name', 'error' );
			return;
		}

		const parentId = elParent ? parseInt( elParent.value ) : 0;

		lpUtils.lpSetLoadingEl( btnSave, 1 );

		const dataSend = {
			action: 'add_course_category',
			args: { id_url: 'add-course-category' },
			name: categoryName,
			parent: parentId,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.html ) {
					const checklist = document.querySelector(
						BuilderEditCourse.selectors.elCategoryChecklist
					);

					if ( data.parent && data.parent > 0 ) {
						const parentInput = checklist.querySelector( `input[value="${ data.parent }"]` );
						if ( parentInput ) {
							const parentLi = parentInput.closest( 'li' );
							parentLi.classList.add( 'children-visible' );
							let ulChildren = parentLi.querySelector( ':scope > ul.children' );
							if ( ! ulChildren ) {
								ulChildren = document.createElement( 'ul' );
								ulChildren.className = 'children';
								parentLi.appendChild( ulChildren );
								this.addToggleBtnToLi( parentLi );
							}

							ulChildren.insertAdjacentHTML( 'beforeend', data.html );
						} else {
							checklist.insertAdjacentHTML( 'afterbegin', data.html );
						}
					} else {
						checklist.insertAdjacentHTML( 'afterbegin', data.html );
					}

					elInput.value = '';
					if ( elParent ) elParent.value = '0';
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( btnSave, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	initSalePriceLayout() {
		const wrap = document.querySelector( BuilderEditCourse.selectors.elPriceCourseData );
		if ( ! wrap ) return;

		const saleDatesFields = wrap.querySelectorAll( BuilderEditCourse.selectors.elSaleDatesFields );
		const scheduleBtn = wrap.querySelector( BuilderEditCourse.selectors.elSalePriceScheduleBtn );
		const cancelBtn = wrap.querySelector( BuilderEditCourse.selectors.elCancelSaleScheduleBtn );

		let saleScheduleSet = false;
		const allInputs = wrap.querySelectorAll(
			`${ BuilderEditCourse.selectors.elSaleDatesFields } input`
		);

		allInputs.forEach( ( input ) => {
			if ( input.value && input.value.trim() !== '' ) {
				saleScheduleSet = true;
			}
		} );

		if ( saleScheduleSet ) {
			if ( scheduleBtn ) scheduleBtn.style.display = 'none';
			if ( cancelBtn ) cancelBtn.style.display = 'inline-block';
			saleDatesFields.forEach( ( field ) => ( field.style.display = 'block' ) );
		} else {
			if ( scheduleBtn ) scheduleBtn.style.display = 'inline-block';
			if ( cancelBtn ) cancelBtn.style.display = 'none';
			saleDatesFields.forEach( ( field ) => ( field.style.display = 'none' ) );
		}
	}

	handleScheduleClick( args ) {
		const { e, target } = args;
		e.preventDefault();
		const btn = target.closest( BuilderEditCourse.selectors.elSalePriceScheduleBtn );
		const wrap = btn.closest( BuilderEditCourse.selectors.elPriceCourseData );
		if ( ! wrap ) return;
		const cancelBtn = wrap.querySelector( BuilderEditCourse.selectors.elCancelSaleScheduleBtn );
		const saleDatesFields = wrap.querySelectorAll( BuilderEditCourse.selectors.elSaleDatesFields );
		btn.style.display = 'none';
		if ( cancelBtn ) cancelBtn.style.display = 'inline-block';
		saleDatesFields.forEach( ( field ) => ( field.style.display = 'block' ) );
	}

	handleCancelSchedule( args ) {
		const { e, target } = args;
		e.preventDefault();
		const btn = target.closest( BuilderEditCourse.selectors.elCancelSaleScheduleBtn );
		const wrap = btn.closest( BuilderEditCourse.selectors.elPriceCourseData );
		if ( ! wrap ) return;
		const scheduleBtn = wrap.querySelector( BuilderEditCourse.selectors.elSalePriceScheduleBtn );
		const saleDatesFields = wrap.querySelectorAll( BuilderEditCourse.selectors.elSaleDatesFields );
		const allInputs = wrap.querySelectorAll(
			`${ BuilderEditCourse.selectors.elSaleDatesFields } input`
		);
		btn.style.display = 'none';
		if ( scheduleBtn ) scheduleBtn.style.display = 'inline-block';
		saleDatesFields.forEach( ( field ) => ( field.style.display = 'none' ) );
		allInputs.forEach( ( input ) => ( input.value = '' ) );
	}

	validateSalePrice( args ) {
		const { target } = args;
		const wrapper = target.closest( BuilderEditCourse.selectors.elPriceCourseData );
		if ( ! wrapper ) return;
		const regularPriceInput = wrapper.querySelector(
			BuilderEditCourse.selectors.elRegularPriceInput
		);
		const salePriceInput = wrapper.querySelector( BuilderEditCourse.selectors.elSalePriceInput );
		const existingTips = wrapper.querySelectorAll( BuilderEditCourse.selectors.elTipFloating );
		existingTips.forEach( ( tip ) => tip.remove() );
		if ( ! regularPriceInput || ! salePriceInput ) return;
		const regularVal = parseFloat( regularPriceInput.value ) || 0;
		const saleVal = parseFloat( salePriceInput.value ) || 0;
		if ( salePriceInput.value !== '' && saleVal > regularVal ) {
			const targetId = target.getAttribute( 'id' );
			const formField = target.closest( BuilderEditCourse.selectors.elFormField );
			const i18n =
				typeof lpAdminCourseEditorSettings !== 'undefined' && lpAdminCourseEditorSettings.i18n
					? lpAdminCourseEditorSettings.i18n
					: {
							notice_price: 'Regular price must be greater than sale price.',
							notice_sale_price: 'Sale price must be less than regular price.',
					  };
			const tip = document.createElement( 'div' );
			tip.className = 'learn-press-tip-floating';
			if ( targetId === BuilderEditCourse.selectors.elPriceInput ) {
				tip.innerHTML = i18n.notice_price;
			} else if ( targetId === BuilderEditCourse.selectors.elSalePriceInput ) {
				tip.innerHTML = i18n.notice_sale_price;
			}
			if ( formField && tip.innerHTML ) {
				formField.appendChild( tip );
			}
		}
	}

	showHideOptionsDependency( args ) {
		const { target } = args;
		if ( target.tagName === 'INPUT' ) {
			if ( target.closest( '.forminp ' ) ) {
				const nameInput = target.name;
				const classDependency = nameInput.replace( 'learn_press_', '' );
				const elClassDependency = document.querySelectorAll( `.show_if_${ classDependency }` );
				if ( elClassDependency ) {
					elClassDependency.forEach( ( el ) => {
						el.classList.toggle( 'lp-option-disabled' );
					} );
				}
			} else if ( target.closest( '.lp-meta-box' ) ) {
				const elLPMetaBox = target.closest( '.lp-meta-box' );
				const nameInput = target.name;
				const elClassDependency = elLPMetaBox.querySelectorAll(
					`[data-dependency="${ nameInput }"]`
				);
				if ( elClassDependency ) {
					elClassDependency.forEach( ( el ) => {
						el.classList.toggle( 'lp-option-disabled' );
					} );
				}
			}
		}
	}

	initTabs() {
		const tabLinks = document.querySelectorAll( BuilderEditCourse.selectors.elTabLinks );
		if ( tabLinks.length > 0 ) {
			this.activateTab( tabLinks[ 0 ] );
		}
	}

	handleTabClick( args ) {
		const { e, target } = args;
		e.preventDefault();
		const linkElement = target.closest( 'a' );
		if ( linkElement ) {
			this.activateTab( linkElement );
		}
	}

	activateTab( linkElement ) {
		const tabItems = document.querySelectorAll( BuilderEditCourse.selectors.elTabItems );
		const panels = document.querySelectorAll( BuilderEditCourse.selectors.elTabPanels );
		const targetId = linkElement.getAttribute( 'href' ).substring( 1 );
		const targetPanel = document.getElementById( targetId );
		if ( ! targetPanel ) return;
		tabItems.forEach( ( li ) => li.classList.remove( 'active' ) );
		panels.forEach( ( panel ) => ( panel.style.display = 'none' ) );
		linkElement.parentElement.classList.add( 'active' );
		targetPanel.style.display = 'block';
	}

	/**
	 * Handle horizontal tab click for client-side tab switching.
	 * Uses lpShowHideEl with lp-hidden class.
	 *
	 * @param {Object} args - Event args containing e and target
	 */
	handleCBHorizontalTabClick( args ) {
		const { e, target } = args;
		e.preventDefault();

		const tab = target.closest( BuilderEditCourse.selectors.elCBHorizontalTabs );
		if ( ! tab ) return;

		const sectionSlug = tab.dataset.tabSection;
		if ( ! sectionSlug ) return;

		// Update active tab
		const allTabs = document.querySelectorAll( BuilderEditCourse.selectors.elCBHorizontalTabs );
		allTabs.forEach( ( t ) => t.classList.remove( 'is-active' ) );
		tab.classList.add( 'is-active' );

		// Show/hide panels using lpShowHideEl
		const allPanels = document.querySelectorAll( BuilderEditCourse.selectors.elCBTabPanels );
		allPanels.forEach( ( panel ) => {
			const isTarget = panel.dataset.section === sectionSlug;
			lpUtils.lpShowHideEl( panel, isTarget ? 1 : 0 );
		} );
	}

	/**
	 * Collect course data from all tabs for update.
	 * Since all tabs are now rendered in DOM (client-side tab switching),
	 * this method collects data from Overview tab (title, desc, categories, tags, thumbnail)
	 * and Settings tab (form fields) when present.
	 *
	 * @return {Object} Course data object
	 */
	getCourseDataForUpdate() {
		const data = {};

		// Get course ID from wrapper (could be in any tab panel)
		const wrapperEl = document.querySelector( BuilderEditCourse.selectors.elDataCourse );
		data.course_id = wrapperEl ? parseInt( wrapperEl.dataset.courseId ) || 0 : 0;

		// --- Overview Tab Data ---
		// Title
		const titleInput = document.querySelector( BuilderEditCourse.selectors.elTitleInput );
		data.course_title = titleInput ? titleInput.value : '';

		// Description (TinyMCE or textarea)
		const descEditor = document.querySelector( BuilderEditCourse.selectors.elDescEditor );
		data.course_description = descEditor ? descEditor.value : '';
		if ( typeof tinymce !== 'undefined' ) {
			const editor = tinymce.get( 'course_description_editor' );
			if ( editor ) {
				data.course_description = editor.getContent();
			}
		}

		// Categories
		data.course_categories = [];
		document
			.querySelectorAll( '#taxonomy-course_category input[name*="course_category"]:checked' )
			.forEach( ( checkbox ) => data.course_categories.push( checkbox.value ) );

		// Tags
		data.course_tags = [];
		document
			.querySelectorAll( 'input[name="course_tags[]"]:checked' )
			.forEach( ( checkbox ) => data.course_tags.push( checkbox.value ) );

		// Thumbnail
		const thumbnailInput = document.querySelector( BuilderEditCourse.selectors.elThumbnailInput );
		data.course_thumbnail_id = thumbnailInput ? thumbnailInput.value : '0';

		// Permalink/Slug
		const permalinkInput = document.querySelector( BuilderEditCourse.selectors.elPermalinkSlugInput );
		if ( permalinkInput && permalinkInput.value ) {
			data.course_permalink = permalinkInput.value;
		}

		// --- Settings Tab Data ---
		const elFormSetting = document.querySelector( BuilderEditCourse.selectors.elFormSetting );
		if ( elFormSetting ) {
			data.course_settings = true;
			const formElements = elFormSetting.querySelectorAll( 'input, select, textarea' );
			formElements.forEach( ( element ) => {
				const name = element.name || element.id;
				if ( ! name ) return;
				// Skip WP nonce and referer fields
				if ( name === 'learnpress_meta_box_nonce' || name === '_wp_http_referer' ) return;

				const isArray = name.endsWith( '[]' );
				const fieldName = name.replace( '[]', '' );

				if ( element.type === 'checkbox' ) {
					if ( isArray ) {
						if ( ! data[ fieldName ] ) data[ fieldName ] = [];
						if ( element.checked ) {
							data[ fieldName ].push( element.value );
						}
					} else {
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
				} else {
					if ( isArray ) {
						if ( ! data.hasOwnProperty( fieldName ) ) {
							data[ fieldName ] = [];
						}
						if ( Array.isArray( data[ fieldName ] ) ) {
							data[ fieldName ].push( element.value );
						}
					} else {
						// Only set if not already set (first value wins)
						if ( ! data.hasOwnProperty( fieldName ) ) {
							data[ fieldName ] = element.value;
						}
					}
				}
			} );
		}

		// Convert settings arrays to comma-separated strings for API
		// Exclude course_categories and course_tags - they're handled separately
		const excludeFromConversion = [ 'course_categories', 'course_tags' ];
		Object.keys( data ).forEach( ( key ) => {
			if ( Array.isArray( data[ key ] ) && ! excludeFromConversion.includes( key ) ) {
				data[ key ] = data[ key ].join( ',' );
			}
		} );

		return data;
	}

	/**
	 * Validate title is not empty before update.
	 *
	 * @return {boolean} True if valid, false if invalid
	 */
	validateTitleBeforeUpdate() {
		const titleInput = document.querySelector( BuilderEditCourse.selectors.elTitleInput );
		if ( ! titleInput ) return true;

		const title = titleInput.value.trim();
		if ( ! title ) {
			const i18n =
				typeof lpAdminCourseEditorSettings !== 'undefined' && lpAdminCourseEditorSettings.i18n
					? lpAdminCourseEditorSettings.i18n
					: { notice_title_required: 'Course title is required.' };
			lpToastify.show( i18n.notice_title_required || 'Course title is required.', 'error' );
			titleInput.focus();
			return false;
		}
		return true;
	}

	validatePricingBeforeUpdate() {
		const regularPriceInput = document.querySelector(
			BuilderEditCourse.selectors.elRegularPriceInput
		);
		const salePriceInput = document.querySelector( BuilderEditCourse.selectors.elSalePriceInput );
		if ( ! regularPriceInput || ! salePriceInput ) return true;
		const regularVal = parseFloat( regularPriceInput.value ) || 0;
		const saleVal = parseFloat( salePriceInput.value ) || 0;
		if ( salePriceInput.value !== '' && saleVal > regularVal ) {
			const i18n =
				typeof lpAdminCourseEditorSettings !== 'undefined' && lpAdminCourseEditorSettings.i18n
					? lpAdminCourseEditorSettings.i18n
					: { notice_sale_price: 'Sale price must be less than regular price.' };
			lpToastify.show( i18n.notice_sale_price, 'error' );
			const priceTabLink = document.querySelector( '.price_tab a' );
			if ( priceTabLink ) priceTabLink.click();
			salePriceInput.focus();
			return false;
		}
		return true;
	}

	updateCourse( args ) {
		// Context check: only handle if on course edit page
		if ( ! document.querySelector( BuilderEditCourse.selectors.elDataCourse ) ) {
			return;
		}

		const { e, target } = args;
		// Validate title is not empty
		if ( ! this.validateTitleBeforeUpdate() ) return;
		if ( ! this.validatePricingBeforeUpdate() ) return;

		// Find which button was clicked and determine status from data attribute
		const elBtnMainAction = target.closest( BuilderEditCourse.selectors.elBtnMainAction );
		const elBtnHeaderSave = target.closest( BuilderEditCourse.selectors.elBtnHeaderSave );
		const elBtnDraftCourse = target.closest( BuilderEditCourse.selectors.elBtnDraftCourse );
		const elBtnPublishCourse = target.closest( BuilderEditCourse.selectors.elBtnPublishCourse );

		let status = 'publish';
		let elBtn = null;

		// Determine status from the clicked button's data-status attribute or class
		if ( elBtnMainAction ) {
			status = elBtnMainAction.dataset.status || 'publish';
			elBtn = elBtnMainAction;
		} else if ( elBtnPublishCourse ) {
			status = elBtnPublishCourse.dataset.status || 'publish';
			elBtn = elBtnPublishCourse;
		} else if ( elBtnDraftCourse ) {
			status = elBtnDraftCourse.dataset.status || 'draft';
			elBtn = elBtnDraftCourse;
		} else if ( elBtnHeaderSave ) {
			// Header save button uses current main action status
			const mainBtn = document.querySelector( BuilderEditCourse.selectors.elBtnMainAction );
			status = mainBtn?.dataset.status || 'publish';
			elBtn = elBtnHeaderSave;
		}

		if ( ! elBtn ) return;

		lpUtils.lpSetLoadingEl( elBtn, 1 );
		const courseData = this.getCourseDataForUpdate();
		const dataSend = {
			...courseData,
			course_status: status,
			action: 'save_courses',
			args: { id_url: 'save-courses' },
		};
		if ( typeof lpCourseBuilder !== 'undefined' && lpCourseBuilder.nonce ) {
			dataSend.nonce = lpCourseBuilder.nonce;
		}
		// Handle course_categories - may be array or already a string
		if ( courseData.course_categories ) {
			dataSend.course_categories = Array.isArray( courseData.course_categories )
				? courseData.course_categories.join( ',' )
				: courseData.course_categories;
		}
		// Handle course_tags - may be array or already a string
		if ( courseData.course_tags ) {
			dataSend.course_tags = Array.isArray( courseData.course_tags )
				? courseData.course_tags.join( ',' )
				: courseData.course_tags;
		}
		if ( courseData.course_thumbnail_id ) {
			dataSend.course_thumbnail_id = courseData.course_thumbnail_id;
		}
		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );
				if ( status === 'success' ) {
					this.updateHeaderTitle( courseData.course_title );
					// Dispatch event to reset form state (remove unsaved changes warning)
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );
				}
				// Update action buttons based on new status
				if ( data?.status ) {
					this.updateActionButtons( data.status );
					// Update status badge
					const elStatus = document.querySelector( BuilderEditCourse.selectors.elStatus );
					if ( elStatus ) {
						elStatus.className = 'course-status ' + data.status;
						elStatus.textContent = data.status;
					}
					// Toggle preview/admin link visibility for trash status
					this.toggleTrashElements( data.status );
				}
				// Use redirect_url from backend if available (for new courses)
				if ( data?.redirect_url ) {
					window.location.href = data.redirect_url;
				} else if ( data?.course_id_new ) {
					// Fallback: build redirect URL manually
					const currentUrl = window.location.href;
					const newUrl = currentUrl.replace(
						/\/post-new\/?(\\?.*)?$/,
						`/${ data.course_id_new }/overview/`
					);
					if ( newUrl !== currentUrl ) {
						window.location.href = newUrl;
					}
				}
				// Update permalink display with actual saved slug (handles duplicate slug resolution)
				if ( data?.course_slug ) {
					const slugInput = document.querySelector( BuilderEditCourse.selectors.elPermalinkSlugInput );
					const urlLink = document.querySelector( BuilderEditCourse.selectors.elPermalinkUrl );
					if ( slugInput ) {
						slugInput.value = data.course_slug;
					}
					if ( urlLink && data?.course_permalink ) {
						urlLink.href = data.course_permalink;
						urlLink.textContent = data.course_permalink;
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtn, 0 );
			},
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Update action buttons after status change.
	 * Swaps main button and dropdown item based on new status.
	 *
	 * @param {string} newStatus - The new course status
	 */
	updateActionButtons( newStatus ) {
		const dropdown = document.querySelector( BuilderEditCourse.selectors.elHeaderActionsDropdown );
		if ( ! dropdown ) return;

		const mainBtn = dropdown.querySelector( '.cb-btn-main-action' );
		const dropdownMenu = dropdown.querySelector( BuilderEditCourse.selectors.elDropdownMenu );
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
	 * Toggle visibility of preview button and admin link based on status.
	 * Hide when status is 'trash', show otherwise.
	 *
	 * @param {string} status - Current course status
	 */
	toggleTrashElements( status ) {
		const elBtnPreview = document.querySelector( BuilderEditCourse.selectors.elBtnPreview );
		const elAdminLink = document.querySelector( BuilderEditCourse.selectors.elAdminLink );
		const isTrash = status === 'trash';

		if ( elBtnPreview ) {
			elBtnPreview.style.display = isTrash ? 'none' : '';
		}
		if ( elAdminLink ) {
			elAdminLink.style.display = isTrash ? 'none' : '';
		}
	}

	trashCourse( args ) {
		// Context check: only handle if on course edit page
		if ( ! document.querySelector( BuilderEditCourse.selectors.elDataCourse ) ) {
			return;
		}

		const { target } = args;
		const elBtnTrashCourse = target.closest( BuilderEditCourse.selectors.elBtnTrashCourse );
		lpUtils.lpSetLoadingEl( elBtnTrashCourse, 1 );
		const courseData = this.getCourseDataForUpdate();
		const dataSend = {
			action: 'move_trash_course',
			args: { id_url: 'move-trash-course' },
			course_id: courseData.course_id,
		};
		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );
				if ( data?.button_title ) {
					const elBtnUpdate = document.querySelector(
						BuilderEditCourse.selectors.elBtnUpdateCourse
					);
					if ( elBtnUpdate ) elBtnUpdate.textContent = data.button_title;
				}
				if ( data?.status ) {
					const elStatus = document.querySelector( BuilderEditCourse.selectors.elStatus );
					if ( elStatus ) {
						elStatus.className = 'course-status ' + data.status;
						elStatus.textContent = data.status;
					}
					// Toggle preview/admin link visibility for trash status
					this.toggleTrashElements( data.status );
					// Update action buttons to show "Save Draft" for trash status
					this.updateActionButtons( data.status );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnTrashCourse, 0 );
			},
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	saveSettings( args ) {
		const { target } = args;
		if ( ! this.validatePricingBeforeUpdate() ) return;

		const elBtnSaveSettings = target.closest( BuilderEditCourse.selectors.elBtnSaveSettings );
		lpUtils.lpSetLoadingEl( elBtnSaveSettings, 1 );

		const courseData = this.getCourseDataForUpdate();
		const dataSend = {
			...courseData,
			action: 'save_course_settings',
			args: { id_url: 'save-course-settings' },
		};

		if ( typeof lpCourseBuilder !== 'undefined' && lpCourseBuilder.nonce ) {
			dataSend.nonce = lpCourseBuilder.nonce;
		}

		const callBack = {
			success: ( response ) => {
				const { status, message } = response;
				lpToastify.show( message, status );
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnSaveSettings, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	toggleAddTagForm( args ) {
		const { target } = args;
		const elBtnAdd = document.querySelector( BuilderEditCourse.selectors.elBtnAddTagNew );
		const elBtnCancel = document.querySelector( BuilderEditCourse.selectors.elBtnCancelTagNew );
		const form = document.querySelector( BuilderEditCourse.selectors.elFormTagAddNew );
		const isOpening = target.closest( BuilderEditCourse.selectors.elBtnAddTagNew );
		if ( form ) {
			if ( isOpening ) {
				form.style.display = 'flex';
				if ( elBtnAdd ) elBtnAdd.style.display = 'none';
				if ( elBtnCancel ) elBtnCancel.style.display = 'inline-block';
				const input = form.querySelector( BuilderEditCourse.selectors.elInputAddTag );
				if ( input ) setTimeout( () => input.focus(), 100 );
			} else {
				form.style.display = 'none';
				if ( elBtnCancel ) elBtnCancel.style.display = 'none';
				if ( elBtnAdd ) elBtnAdd.style.display = 'inline-block';
			}
		}
	}

	addNewTag( args ) {
		const { e } = args;
		const elInput = document.querySelector( BuilderEditCourse.selectors.elInputAddTag );
		const btnSave = document.querySelector( BuilderEditCourse.selectors.elBtnSaveTag );
		const tagName = elInput?.value?.trim() ?? '';

		if ( ! tagName ) {
			lpToastify.show( 'Please enter tag name', 'error' );
			return;
		}

		lpUtils.lpSetLoadingEl( btnSave, 1 );

		const dataSend = {
			action: 'add_course_tag',
			args: { id_url: 'add-course-tag' },
			name: tagName,
		};
		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );
				if ( data?.html ) {
					const wrapper = document.querySelector(
						BuilderEditCourse.selectors.elWrapperCheckBoxTag
					);
					wrapper.insertAdjacentHTML( 'beforeend', data.html );
					elInput.value = '';
					const elBtnCancel = document.querySelector(
						BuilderEditCourse.selectors.elBtnCancelTagNew
					);
					if ( elBtnCancel ) elBtnCancel.click();
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( btnSave, 0 );
			},
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	openMediaUploader( args ) {
		if ( typeof wp === 'undefined' || typeof wp.media === 'undefined' ) return;
		const mediaUploader = wp.media( {
			title: 'Select Featured Image',
			button: { text: 'Use this image' },
			multiple: false,
			library: { type: 'image' },
		} );
		mediaUploader.on( 'select', () => {
			const attachment = mediaUploader.state().get( 'selection' ).first().toJSON();
			this.setFeaturedImage( attachment );
		} );
		mediaUploader.open();
	}

	setFeaturedImage( attachment ) {
		const dropzone = document.querySelector( BuilderEditCourse.selectors.elFeaturedImageDropzone );
		const thumbnailInput = document.querySelector( BuilderEditCourse.selectors.elThumbnailInput );
		const actionsContainer = document.querySelector( '.cb-featured-image-actions' );

		if ( ! dropzone || ! thumbnailInput ) return;

		thumbnailInput.value = attachment.id;

		// Mark form as having unsaved changes
		getFormState().markAsChanged();
		const imgUrl =
			attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || attachment.url;

		// Clear dropzone content
		dropzone.innerHTML = '';

		// Add image
		const img = document.createElement( 'img' );
		img.src = imgUrl;
		img.className = 'cb-featured-image-preview__img';
		img.alt = attachment.alt || '';
		dropzone.appendChild( img );
		dropzone.classList.add( 'has-image' );

		// Show/create action buttons (order must match PHP: Remove icon first, Replace text second)
		if ( actionsContainer ) {
			actionsContainer.innerHTML = `
				<button type="button" class="cb-remove-featured-image"><svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_5385_4628)"><path d="M11.9 1.66699C12.2498 1.66708 12.5907 1.77723 12.8744 1.98183C13.1581 2.18643 13.3703 2.47512 13.4808 2.80699L13.9333 4.16699H16.6667C16.8877 4.16699 17.0996 4.25479 17.2559 4.41107C17.4122 4.56735 17.5 4.77931 17.5 5.00033C17.5 5.22134 17.4122 5.4333 17.2559 5.58958C17.0996 5.74586 16.8877 5.83366 16.6667 5.83366L16.6642 5.89283L15.9417 16.012C15.8966 16.6425 15.6143 17.2325 15.1517 17.6633C14.6891 18.0941 14.0805 18.3336 13.4483 18.3337H6.55167C5.91955 18.3336 5.31092 18.0941 4.84831 17.6633C4.38569 17.2325 4.10342 16.6425 4.05833 16.012L3.33583 5.89199C3.33433 5.87258 3.33349 5.85313 3.33333 5.83366C3.11232 5.83366 2.90036 5.74586 2.74408 5.58958C2.5878 5.4333 2.5 5.22134 2.5 5.00033C2.5 4.77931 2.5878 4.56735 2.74408 4.41107C2.90036 4.25479 3.11232 4.16699 3.33333 4.16699H6.06667L6.51917 2.80699C6.62975 2.47498 6.84203 2.1862 7.12592 1.98159C7.4098 1.77697 7.75089 1.66691 8.10083 1.66699H11.9ZM14.9975 5.83366H5.0025L5.72083 15.8928C5.73579 16.103 5.82981 16.2997 5.98397 16.4433C6.13812 16.587 6.34096 16.6669 6.55167 16.667H13.4483C13.659 16.6669 13.8619 16.587 14.016 16.4433C14.1702 16.2997 14.2642 16.103 14.2792 15.8928L14.9975 5.83366ZM8.33333 8.33366C8.53744 8.33369 8.73445 8.40862 8.88698 8.54425C9.03951 8.67989 9.13695 8.86678 9.16083 9.06949L9.16667 9.16699V13.3337C9.16643 13.5461 9.0851 13.7504 8.93929 13.9048C8.79349 14.0592 8.59421 14.1522 8.38217 14.1646C8.17014 14.1771 7.96135 14.1081 7.79847 13.9718C7.6356 13.8354 7.53092 13.6421 7.50583 13.4312L7.5 13.3337V9.16699C7.5 8.94598 7.5878 8.73402 7.74408 8.57774C7.90036 8.42146 8.11232 8.33366 8.33333 8.33366ZM11.6667 8.33366C11.8877 8.33366 12.0996 8.42146 12.2559 8.57774C12.4122 8.73402 12.5 8.94598 12.5 9.16699V13.3337C12.5 13.5547 12.4122 13.7666 12.2559 13.9229C12.0996 14.0792 11.8877 14.167 11.6667 14.167C11.4457 14.167 11.2337 14.0792 11.0774 13.9229C10.9211 13.7666 10.8333 13.5547 10.8333 13.3337V9.16699C10.8333 8.94598 10.9211 8.73402 11.0774 8.57774C11.2337 8.42146 11.4457 8.33366 11.6667 8.33366ZM11.9 3.33366H8.1L7.8225 4.16699H12.1775L11.9 3.33366Z" fill="currentColor"/></g><defs><clipPath id="clip0_5385_4628"><rect width="20" height="20" fill="white"/></clipPath></defs></svg></button>
				<button type="button" class="cb-change-featured-image">${
					window.lpCourseBuilder?.i18n?.replace_image || 'Replace'
				}</button>
			`;
		}
	}

	removeFeaturedImage( args ) {
		const { e } = args;
		if ( e ) e.preventDefault();

		const dropzone = document.querySelector( BuilderEditCourse.selectors.elFeaturedImageDropzone );
		const thumbnailInput = document.querySelector( BuilderEditCourse.selectors.elThumbnailInput );
		const actionsContainer = document.querySelector( '.cb-featured-image-actions' );

		if ( ! dropzone ) return;

		// Clear dropzone and show upload content
		dropzone.innerHTML = `
			<div class="cb-featured-image-upload-content">
				<span class="cb-featured-image-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.29095 14.5488C3.29099 14.8637 3.33747 15.1785 3.44915 15.5527L3.48138 15.6592C3.95542 17.0735 5.26094 18.0234 6.72845 18.0234H20.0361L19.2099 20.6787C18.9761 21.5815 18.1423 22.1942 17.2294 22.1943C17.0513 22.1942 16.8735 22.1714 16.7011 22.126L2.5263 18.2881C1.43276 17.9832 0.78131 16.8383 1.06732 15.7344L3.29095 8.22949V14.5488ZM20.7079 1.80469C21.9711 1.80474 22.9998 2.84505 22.9999 4.12207V14.3164C22.9999 15.5936 21.9712 16.6337 20.7079 16.6338H6.95794C5.69489 16.6338 4.66595 15.5936 4.66595 14.3164V4.12207C4.66604 2.84502 5.69495 1.80469 6.95794 1.80469H20.7079ZM6.95794 3.65918C6.70507 3.65918 6.49903 3.86625 6.49896 4.12207V12.8701L9.02923 10.3135C9.65534 9.67964 10.6757 9.67964 11.3027 10.3135L12.412 11.4316L15.8163 7.2998C16.1206 6.93103 16.5663 6.71857 17.041 6.71582C17.5185 6.72681 17.9633 6.92104 18.2704 7.28516L21.166 10.7012V4.12207C21.1659 3.8663 20.961 3.65923 20.7079 3.65918H6.95794ZM9.24896 4.58496C10.2601 4.58496 11.0829 5.4172 11.0829 6.43945C11.0827 7.4615 10.26 8.29297 9.24896 8.29297C8.23818 8.29274 7.4162 7.46132 7.41595 6.43945C7.41595 5.41738 8.23798 4.58519 9.24896 4.58496Z" fill="#CFCFCF"/></svg></span>
				<p class="cb-featured-image-text"><a href="#" class="cb-featured-image-link">${
					window.lpCourseBuilder?.i18n?.click_to_upload || 'Click to upload'
				}</a></p>
				<p class="cb-featured-image-hint">${
					window.lpCourseBuilder?.i18n?.image_hint || 'JPG, JPEG, PNG less than 1MB'
				}</p>
			</div>
		`;
		dropzone.classList.remove( 'has-image' );

		// Clear thumbnail ID
		if ( thumbnailInput ) {
			thumbnailInput.value = '';

			// Mark form as having unsaved changes
			getFormState().markAsChanged();
		}

		// Hide action buttons
		if ( actionsContainer ) {
			actionsContainer.innerHTML = '';
		}
	}

	/**
	 * Slugify a string - convert to URL-safe slug.
	 * Handles Vietnamese diacritics, special characters, spaces.
	 *
	 * @param {string} str - Input string
	 * @return {string} URL-safe slug
	 */
	slugify( str ) {
		// Vietnamese diacritics mapping
		const vietnameseMap = {
			à: 'a', á: 'a', ạ: 'a', ả: 'a', ã: 'a',
			â: 'a', ầ: 'a', ấ: 'a', ậ: 'a', ẩ: 'a', ẫ: 'a',
			ă: 'a', ằ: 'a', ắ: 'a', ặ: 'a', ẳ: 'a', ẵ: 'a',
			è: 'e', é: 'e', ẹ: 'e', ẻ: 'e', ẽ: 'e',
			ê: 'e', ề: 'e', ế: 'e', ệ: 'e', ể: 'e', ễ: 'e',
			ì: 'i', í: 'i', ị: 'i', ỉ: 'i', ĩ: 'i',
			ò: 'o', ó: 'o', ọ: 'o', ỏ: 'o', õ: 'o',
			ô: 'o', ồ: 'o', ố: 'o', ộ: 'o', ổ: 'o', ỗ: 'o',
			ơ: 'o', ờ: 'o', ớ: 'o', ợ: 'o', ở: 'o', ỡ: 'o',
			ù: 'u', ú: 'u', ụ: 'u', ủ: 'u', ũ: 'u',
			ư: 'u', ừ: 'u', ứ: 'u', ự: 'u', ử: 'u', ữ: 'u',
			ỳ: 'y', ý: 'y', ỵ: 'y', ỷ: 'y', ỹ: 'y',
			đ: 'd',
			À: 'A', Á: 'A', Ạ: 'A', Ả: 'A', Ã: 'A',
			Â: 'A', Ầ: 'A', Ấ: 'A', Ậ: 'A', Ẩ: 'A', Ẫ: 'A',
			Ă: 'A', Ằ: 'A', Ắ: 'A', Ặ: 'A', Ẳ: 'A', Ẵ: 'A',
			È: 'E', É: 'E', Ẹ: 'E', Ẻ: 'E', Ẽ: 'E',
			Ê: 'E', Ề: 'E', Ế: 'E', Ệ: 'E', Ể: 'E', Ễ: 'E',
			Ì: 'I', Í: 'I', Ị: 'I', Ỉ: 'I', Ĩ: 'I',
			Ò: 'O', Ó: 'O', Ọ: 'O', Ỏ: 'O', Õ: 'O',
			Ô: 'O', Ồ: 'O', Ố: 'O', Ộ: 'O', Ổ: 'O', Ỗ: 'O',
			Ơ: 'O', Ờ: 'O', Ớ: 'O', Ợ: 'O', Ở: 'O', Ỡ: 'O',
			Ù: 'U', Ú: 'U', Ụ: 'U', Ủ: 'U', Ũ: 'U',
			Ư: 'U', Ừ: 'U', Ứ: 'U', Ự: 'U', Ử: 'U', Ữ: 'U',
			Ỳ: 'Y', Ý: 'Y', Ỵ: 'Y', Ỷ: 'Y', Ỹ: 'Y',
			Đ: 'D',
		};

		// Replace Vietnamese characters
		let result = str.split( '' ).map( ( c ) => vietnameseMap[ c ] || c ).join( '' );

		// Lowercase, replace spaces with dashes, remove special characters
		result = result
			.toLowerCase()
			.replace( /\s+/g, '-' ) // Replace spaces with -
			.replace( /[^\w-]+/g, '' ) // Remove non-word chars except -
			.replace( /--+/g, '-' ) // Replace multiple - with single -
			.replace( /^-+/, '' ) // Trim - from start
			.replace( /-+$/, '' ); // Trim - from end

		return result;
	}

	/**
	 * Handle permalink Edit button click.
	 * Shows editor mode, hides display mode.
	 */
	handlePermalinkEdit( args ) {
		const { e } = args;
		if ( e ) e.preventDefault();

		const display = document.querySelector( BuilderEditCourse.selectors.elPermalinkDisplay );
		const editor = document.querySelector( BuilderEditCourse.selectors.elPermalinkEditor );
		const input = document.querySelector( BuilderEditCourse.selectors.elPermalinkSlugInput );

		if ( ! display || ! editor || ! input ) return;

		// Store original value for cancel
		input.dataset.originalValue = input.value;

		// Toggle visibility
		display.classList.add( 'lp-hidden' );
		editor.classList.remove( 'lp-hidden' );

		// Focus input and select text
		input.focus();
		input.select();
	}

	/**
	 * Handle permalink OK button click.
	 * Validates and sanitizes slug, updates display.
	 */
	handlePermalinkOk( args ) {
		const { e } = args;
		if ( e ) e.preventDefault();

		const display = document.querySelector( BuilderEditCourse.selectors.elPermalinkDisplay );
		const editor = document.querySelector( BuilderEditCourse.selectors.elPermalinkEditor );
		const input = document.querySelector( BuilderEditCourse.selectors.elPermalinkSlugInput );
		const urlLink = document.querySelector( BuilderEditCourse.selectors.elPermalinkUrl );
		const baseUrlInput = document.querySelector( BuilderEditCourse.selectors.elPermalinkBaseUrl );

		if ( ! display || ! editor || ! input || ! urlLink ) return;

		// Sanitize the slug
		let newSlug = this.slugify( input.value.trim() );

		// If empty after sanitizing, restore original
		if ( ! newSlug ) {
			newSlug = input.dataset.originalValue || 'course';
		}

		// Update input value with sanitized slug
		input.value = newSlug;

		// Get base URL
		const baseUrl = baseUrlInput ? baseUrlInput.value : '';
		const newUrl = baseUrl + newSlug;

		// Update the display link
		urlLink.href = newUrl;
		urlLink.textContent = newUrl;

		// Toggle visibility back to display mode
		editor.classList.add( 'lp-hidden' );
		display.classList.remove( 'lp-hidden' );

		// Mark form as changed if slug differs from original
		if ( newSlug !== input.dataset.originalValue ) {
			getFormState().markAsChanged();
		}
	}

	/**
	 * Handle permalink Cancel button click.
	 * Restores original value and returns to display mode.
	 */
	handlePermalinkCancel( args ) {
		const { e } = args;
		if ( e ) e.preventDefault();

		const display = document.querySelector( BuilderEditCourse.selectors.elPermalinkDisplay );
		const editor = document.querySelector( BuilderEditCourse.selectors.elPermalinkEditor );
		const input = document.querySelector( BuilderEditCourse.selectors.elPermalinkSlugInput );

		if ( ! display || ! editor || ! input ) return;

		// Restore original value
		input.value = input.dataset.originalValue || '';

		// Toggle visibility back to display mode
		editor.classList.add( 'lp-hidden' );
		display.classList.remove( 'lp-hidden' );
	}

	initTabTitles() {
		const tabLinks = document.querySelectorAll( BuilderEditCourse.selectors.elTabLinks );
		tabLinks.forEach( ( link ) => {
			const textSpan = link.querySelector( 'span' );
			const title = textSpan ? textSpan.textContent.trim() : link.textContent.trim();
			const href = link.getAttribute( 'href' );
			if ( ! href ) return;
			const targetId = href.substring( 1 );
			const panel = document.getElementById( targetId );
			if ( panel ) {
				panel.setAttribute( 'data-tab-title', title );
			}
		} );
	}

	initTitleCharCount() {
		const titleInput = document.querySelector( BuilderEditCourse.selectors.elTitleInput );
		if ( titleInput ) {
			this.updateTitleCharCount( titleInput.value );
		}
	}

	updateTitleCharCount( text ) {
		const charCountEl = document.querySelector( BuilderEditCourse.selectors.elTitleCharCount );
		if ( ! charCountEl ) return;

		const charCount = text.length;
		const charText = charCount === 1 ? 'character' : 'characters';
		charCountEl.textContent = `${ charCount } ${ charText }`;
	}

	handleTitleInput( args ) {
		const { target } = args;
		this.updateTitleCharCount( target.value );
	}

	initDescWordCount() {
		// Wait for TinyMCE to be ready
		if ( typeof tinymce !== 'undefined' ) {
			tinymce.on( 'AddEditor', ( e ) => {
				if ( e.editor.id === 'course_description_editor' ) {
					e.editor.on( 'init', () => {
						this.updateDescWordCount( e.editor );

						// Listen for content changes
						e.editor.on( 'keyup change input NodeChange', () => {
							this.updateDescWordCount( e.editor );
						} );
					} );
				}
			} );

			// If editor already exists
			const existingEditor = tinymce.get( 'course_description_editor' );
			if ( existingEditor ) {
				this.updateDescWordCount( existingEditor );
				existingEditor.on( 'keyup change input NodeChange', () => {
					this.updateDescWordCount( existingEditor );
				} );
			}
		}

		// Also handle text mode (quicktags)
		const textarea = document.querySelector( BuilderEditCourse.selectors.elDescEditor );
		if ( textarea ) {
			textarea.addEventListener( 'input', () => {
				this.updateDescWordCountFromText( textarea.value );
			} );
		}
	}

	updateDescWordCount( editor ) {
		const wordCountEl = document.querySelector( BuilderEditCourse.selectors.elDescWordCount );
		if ( ! wordCountEl ) return;

		// Use TinyMCE's built-in word count plugin
		const wordcount = editor.plugins.wordcount;
		let count = 0;

		if ( wordcount && typeof wordcount.body !== 'undefined' ) {
			count = wordcount.body.getWordCount();
		} else if ( wordcount && typeof wordcount.getCount !== 'undefined' ) {
			count = wordcount.getCount();
		} else {
			// Fallback: manual count
			const content = editor.getContent( { format: 'text' } );
			count = this.countWords( content );
		}

		const wordText = count === 1 ? 'word' : 'words';
		wordCountEl.textContent = `${ count } ${ wordText }`;
	}

	updateDescWordCountFromText( text ) {
		const wordCountEl = document.querySelector( BuilderEditCourse.selectors.elDescWordCount );
		if ( ! wordCountEl ) return;

		const count = this.countWords( text );
		const wordText = count === 1 ? 'word' : 'words';
		wordCountEl.textContent = `${ count } ${ wordText }`;
	}

	updateHeaderTitle( title ) {
		const headerTitle = document.querySelector( '.lp-cb-header__title' );
		if ( headerTitle && title ) {
			headerTitle.textContent = title;
		}
	}

	countWords( text ) {
		const trimmedText = text.replace( /<[^>]*>/g, '' ).trim();
		if ( trimmedText.length === 0 ) return 0;
		const words = trimmedText.split( /\s+/ ).filter( ( word ) => word.length > 0 );
		return words.length;
	}

	/**
	 * Initialize Header Actions Dropdown
	 * Handles toggle open/close for dropdown menu in header actions
	 */
	initHeaderActionsDropdown() {
		const dropdownWrapper = document.querySelector(
			BuilderEditCourse.selectors.elHeaderActionsDropdown
		);
		if ( ! dropdownWrapper ) return;

		const toggleBtn = dropdownWrapper.querySelector( BuilderEditCourse.selectors.elDropdownToggle );
		const dropdownMenu = dropdownWrapper.querySelector(
			BuilderEditCourse.selectors.elDropdownMenu
		);

		if ( ! toggleBtn || ! dropdownMenu ) return;

		// Toggle dropdown on button click
		toggleBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			const isOpen = dropdownMenu.classList.contains( 'is-open' );

			if ( isOpen ) {
				this.closeHeaderDropdown( toggleBtn, dropdownMenu );
			} else {
				this.openHeaderDropdown( toggleBtn, dropdownMenu );
			}
		} );

		// Close dropdown when clicking outside
		document.addEventListener( 'click', ( e ) => {
			if ( ! dropdownWrapper.contains( e.target ) ) {
				this.closeHeaderDropdown( toggleBtn, dropdownMenu );
			}
		} );

		// Close dropdown on Escape key
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Escape' ) {
				this.closeHeaderDropdown( toggleBtn, dropdownMenu );
			}
		} );

		// Close dropdown after clicking an item (except when it triggers an action that keeps page)
		const dropdownItems = dropdownMenu.querySelectorAll( '.cb-dropdown-item' );
		dropdownItems.forEach( ( item ) => {
			item.addEventListener( 'click', () => {
				// Small delay to allow action to process before closing
				setTimeout( () => {
					this.closeHeaderDropdown( toggleBtn, dropdownMenu );
				}, 100 );
			} );
		} );
	}

	openHeaderDropdown( toggleBtn, dropdownMenu ) {
		dropdownMenu.classList.add( 'is-open' );
		toggleBtn.setAttribute( 'aria-expanded', 'true' );
	}

	closeHeaderDropdown( toggleBtn, dropdownMenu ) {
		dropdownMenu.classList.remove( 'is-open' );
		toggleBtn.setAttribute( 'aria-expanded', 'false' );
	}
}
