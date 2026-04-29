import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';
import { EditCourseCurriculum } from 'lpAssetsJsPath/admin/edit-course/edit-curriculum';
import { MetaboxExtraInfo } from './extra-info.js';
import { getFormState } from '../builder-form-state.js';
import { SWAL_ICON_DUPLICATE } from '../swal-icons.js';

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
		elCBContent: '.lp-cb-content',
		elTabLinks: '.lp-meta-box__course-tab__tabs li a',
		elTabItems: '.lp-meta-box__course-tab__tabs li',
		elTabPanels: '.lp-meta-box-course-panels',

		elDataCourse: '.cb-section__course-edit',
		elBtnUpdateCourse: '.cb-btn-update',
		elBtnHeaderSave: '.lp-cb-save-btn',
		elBtnDraftCourse: '.cb-btn-darft',
		elBtnPublishCourse: '.cb-btn-publish',
		elBtnPendingCourse: '.cb-btn-pending',
		elBtnDropdownAction: '.cb-dropdown-item[data-status]',
		elBtnMainAction: '.cb-btn-main-action',
		elBtnDuplicateCourse: '.cb-btn-duplicate-course',
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

		// New custom category add form selectors
		elBtnAddCategoryNew: '.cb-course-edit-category__btn-add-new',
		elFormCategoryAddNew: '.cb-course-edit-terms__form-add-category',
		elInputAddCategory: '.cb-course-edit-category__input',
		elSelectAddCategoryParent: '.cb-course-edit-category__select-parent',
		elBtnSaveCategory: '.cb-course-edit-category__btn-save',
		elBtnCancelCategoryNew: '.cb-course-edit-category__btn-cancel',
		elCategorySearchInput: '.cb-course-edit-category__search-input',
		elBtnToggleTermsSearch: '.cb-terms-header__btn-search',
		elTermsSearchToolbar: '.cb-terms-search-toolbar',

		elWrapperCheckBoxTag: '.cb-course-edit-tags__checkbox-wrapper',
		elFormTagAddNew: '.cb-course-edit-terms__form-add-tag',
		elBtnAddTagNew: '.cb-course-edit-tag__btn-add-new',
		elBtnCancelTagNew: '.cb-course-edit-tag__btn-cancel',
		elBtnSaveTag: '.cb-course-edit-tags__btn-save',
		elInputAddTag: '.cb-course-edit-tags__input',
		elTagsWrapper: '.cb-course-edit-tags__wrapper',
		elTagChip: '.cb-tag-chip',
		elTagSearchInput: '.cb-course-edit-tags__search-input',
		elTagEmptyState: '.cb-course-edit-tags__empty',

		elBtnRemoveFeatured: '.cb-remove-featured-image',
		elBtnSetFeatured: '.cb-featured-image-dropzone:not(.has-image)',
		elBtnChangeFeatured: '.cb-change-featured-image',
		elFeaturedImageDropzone: '.cb-featured-image-dropzone',
		elFeaturedImageLink: '.cb-featured-image-link',
		elThumbnailInput: '#course_thumbnail_id',
		elFeaturedImageContainer: '.cb-featured-image-container',
		elPublishPanel: '.cb-course-edit-publish',
		elPublishStatusSelect: '#cb-course-publish-status',
		elPublishVisibilitySelect: '#cb-course-publish-visibility',
		elPublishPasswordRow: '.cb-course-edit-publish__password-row',
		elPublishPasswordInput: '#cb-course-publish-password',
		elPublishDateLabel: '#cb-course-publish-date-label',
		elPublishDateInput: '#cb-course-publish-date',

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
		elCourseResultRadio: 'input[type="radio"][name="_lp_course_result"]',
		elPassingConditionField: '._lp_passing_condition_field',
		elBtnGetFinalQuiz: '.lp-metabox-get-final-quiz',
		elFinalQuizMessage: '.lp-metabox-evaluate-final_quiz',
		elExtraMetaInput: '.lp_course_extra_meta_box__input',
		elFaqMetaInput: '.lp_course_faq_meta_box__field input',

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
		this.isSavingCourse = false;

		const editCourseCurriculum = new EditCourseCurriculum();
		const metaboxExtraInfo = new MetaboxExtraInfo();
		editCourseCurriculum.init();
		metaboxExtraInfo.init();

		this.initTabs();
		this.initTabTitles();
		this.initCategoryTabs();
		this.initCategoryTree();
		this.applyCategorySearch();
		this.initTagManagement();
		this.initSalePriceLayout();
		this.initAssessmentMetaboxFeatures();
		this.initTitleCharCount();
		this.initDescWordCount();
		this.syncPublishStatusOptions();
		this.syncPublishDateLabel();
		this.syncPublishVisibilityControls( false );
		this.initHeaderActionsDropdown();
		this.bindAiFeaturedImageListener();
		this.events();
	}

	bindAiFeaturedImageListener() {
		if ( BuilderEditCourse._boundAiFeaturedImageListener ) {
			return;
		}

		document.addEventListener( 'lp-course-builder/ai-featured-image-applied', ( event ) => {
			this.handleAiFeaturedImageApplied( event );
		} );

		BuilderEditCourse._boundAiFeaturedImageListener = true;
	}

	handleAiFeaturedImageApplied( event ) {
		const attachmentId = parseInt( event?.detail?.attachmentId || 0, 10 );
		const imageSrc = event?.detail?.imageSrc || '';

		if ( ! attachmentId || ! imageSrc ) {
			return;
		}

		this.setFeaturedImage( {
			id: attachmentId,
			url: imageSrc,
			sizes: {
				medium: {
					url: imageSrc,
				},
			},
		} );
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
			// Custom category add form handlers
			{
				selector: BuilderEditCourse.selectors.elBtnAddCategoryNew,
				class: this,
				callBack: this.toggleCustomAddCategoryForm.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnToggleTermsSearch,
				class: this,
				callBack: this.toggleTermsSearchToolbar.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnCancelCategoryNew,
				class: this,
				callBack: this.toggleCustomAddCategoryForm.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnSaveCategory,
				class: this,
				callBack: this.addNewCategoryCustom.name,
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
				selector: BuilderEditCourse.selectors.elBtnDropdownAction,
				class: this,
				callBack: this.updateCourse.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnDuplicateCourse,
				class: this,
				callBack: this.duplicateCourse.name,
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
				selector: BuilderEditCourse.selectors.elBtnGetFinalQuiz,
				class: this,
				callBack: this.getFinalQuiz.name,
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
			{
				selector: 'input[name="course_tags[]"]',
				class: this,
				callBack: this.handleTagSelectionChange.name,
			},
			{
				selector: BuilderEditCourse.selectors.elCourseResultRadio,
				class: this,
				callBack: this.handleCourseResultChange.name,
			},
			{
				selector: BuilderEditCourse.selectors.elPublishVisibilitySelect,
				class: this,
				callBack: this.handlePublishVisibilityChange.name,
			},
			{
				selector: BuilderEditCourse.selectors.elPublishStatusSelect,
				class: this,
				callBack: this.handlePublishStatusChange.name,
			},
			{
				selector: BuilderEditCourse.selectors.elPublishDateInput,
				class: this,
				callBack: this.handlePublishDateChange.name,
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
			{
				selector: BuilderEditCourse.selectors.elTagSearchInput,
				class: this,
				callBack: this.handleTagSearchInput.name,
			},
			{
				selector: BuilderEditCourse.selectors.elCategorySearchInput,
				class: this,
				callBack: this.handleCategorySearchInput.name,
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
				selector: BuilderEditCourse.selectors.elInputAddCategory,
				class: this,
				callBack: this.addNewCategoryCustom.name,
				checkIsEventEnter: true,
			},
			{
				selector: BuilderEditCourse.selectors.elInputAddTag,
				class: this,
				callBack: this.addNewTag.name,
				checkIsEventEnter: true,
			},
			{
				selector: BuilderEditCourse.selectors.elExtraMetaInput,
				class: this,
				callBack: this.preventEnterSubmitInMetaInput.name,
				checkIsEventEnter: true,
			},
			{
				selector: BuilderEditCourse.selectors.elFaqMetaInput,
				class: this,
				callBack: this.preventEnterSubmitInMetaInput.name,
				checkIsEventEnter: true,
			},
		] );
	}

	initAssessmentMetaboxFeatures() {
		this.updatePassingConditionVisibility();
	}

	handleCourseResultChange() {
		this.updatePassingConditionVisibility();
	}

	updatePassingConditionVisibility() {
		const listHides = [ 'evaluate_final_quiz', 'evaluate_final_assignment' ];
		const checkedEvaluation = document.querySelector(
			`${ BuilderEditCourse.selectors.elCourseResultRadio }:checked`
		);
		const shouldHidePassing = checkedEvaluation
			? listHides.includes( checkedEvaluation.value )
			: false;

		document
			.querySelectorAll( BuilderEditCourse.selectors.elPassingConditionField )
			.forEach( ( el ) => {
				el.style.display = shouldHidePassing ? 'none' : '';
			} );
	}

	preventEnterSubmitInMetaInput( args ) {
		const { e, target } = args;
		e.preventDefault();

		if ( target?.blur ) {
			target.blur();
		}
	}

	async getFinalQuiz( args ) {
		const { e, target } = args;
		e.preventDefault();

		const btn = target.closest( BuilderEditCourse.selectors.elBtnGetFinalQuiz );
		if ( ! btn || btn.dataset.loadingState === 'yes' ) {
			return;
		}

		const defaultText = btn.textContent;
		const loadingText = btn.dataset.loading || defaultText;
		const currentMessage =
			btn.parentNode?.querySelector( BuilderEditCourse.selectors.elFinalQuizMessage ) ||
			document.querySelector( BuilderEditCourse.selectors.elFinalQuizMessage );

		if ( currentMessage ) {
			currentMessage.remove();
		}

		btn.dataset.loadingState = 'yes';
		btn.textContent = loadingText;

		try {
			const response = await this.requestFinalQuiz( btn.dataset.postid || '' );
			const message = response?.data || response?.message || '';

			btn.textContent = defaultText;

			const messageNode = document.createElement( 'div' );
			messageNode.className = 'lp-metabox-evaluate-final_quiz';
			messageNode.innerHTML = message;

			btn.parentNode.insertBefore( messageNode, btn.nextSibling );
		} catch ( error ) {
			btn.textContent = defaultText;
			if ( error?.message ) {
				lpToastify.show( error.message, 'error' );
			}
		} finally {
			delete btn.dataset.loadingState;
		}
	}

	async requestFinalQuiz( courseId = '' ) {
		if ( typeof wp !== 'undefined' && typeof wp.apiFetch === 'function' ) {
			return wp.apiFetch( {
				path: 'lp/v1/admin/course/get_final_quiz',
				method: 'POST',
				data: {
					courseId,
					isCourseBuilder: true,
				},
			} );
		}

		const restBase = window.lpGlobalSettings?.rest || window.lpData?.lp_rest_url || '/wp-json/';
		const restNonce = window.lpGlobalSettings?.nonce || window.lpData?.nonce || '';
		const normalizedRestBase = restBase.endsWith( '/' ) ? restBase : `${ restBase }/`;
		const response = await fetch( `${ normalizedRestBase }lp/v1/admin/course/get_final_quiz`, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				...( restNonce ? { 'X-WP-Nonce': restNonce } : {} ),
			},
			body: JSON.stringify( {
				courseId,
				isCourseBuilder: true,
			} ),
		} );

		let responseData = {};
		try {
			responseData = await response.json();
		} catch ( error ) {
			throw new Error( 'Cannot parse final quiz response' );
		}

		if ( ! response.ok ) {
			throw new Error( responseData?.message || `Request failed (${ response.status })` );
		}

		return responseData;
	}

	initTagManagement() {
		this.syncTagManagement();
	}

	getTagManagementElements() {
		const wrapper = document.querySelector( BuilderEditCourse.selectors.elTagsWrapper );
		if ( ! wrapper ) {
			return null;
		}

		return {
			wrapper,
			searchInput: wrapper.querySelector( BuilderEditCourse.selectors.elTagSearchInput ),
			list: wrapper.querySelector( BuilderEditCourse.selectors.elWrapperCheckBoxTag ),
			emptyState: wrapper.querySelector( BuilderEditCourse.selectors.elTagEmptyState ),
		};
	}

	getTagChipName( chip ) {
		return (
			chip.dataset.tagName ||
			chip.querySelector( '.cb-tag-chip__name' )?.textContent?.trim()?.toLowerCase() ||
			''
		);
	}

	updateTagEmptyState( emptyState, searchTerm = '', visibleCount = 0 ) {
		if ( ! emptyState ) {
			return;
		}

		const emptyDefault = emptyState.dataset.emptyDefault || '';
		const emptySearch = emptyState.dataset.emptySearch || emptyDefault;
		emptyState.textContent = searchTerm ? emptySearch : emptyDefault;
		emptyState.classList.toggle( 'lp-hidden', visibleCount > 0 );
	}

	syncTagManagement() {
		const elements = this.getTagManagementElements();
		if ( ! elements || ! elements.list ) {
			return;
		}

		const searchTerm = elements.searchInput?.value?.trim().toLowerCase() || '';
		const chips = Array.from(
			elements.wrapper.querySelectorAll( BuilderEditCourse.selectors.elTagChip )
		).sort( ( chipA, chipB ) => {
			const chipASelected = chipA.querySelector( 'input[name="course_tags[]"]' )?.checked ? 1 : 0;
			const chipBSelected = chipB.querySelector( 'input[name="course_tags[]"]' )?.checked ? 1 : 0;

			if ( chipASelected !== chipBSelected ) {
				return chipBSelected - chipASelected;
			}

			return this.getTagChipName( chipA ).localeCompare( this.getTagChipName( chipB ) );
		} );
		let visibleCount = 0;

		chips.forEach( ( chip ) => {
			const matchesSearch = ! searchTerm || this.getTagChipName( chip ).includes( searchTerm );
			const shouldShow = matchesSearch;

			chip.classList.toggle( 'lp-hidden', ! shouldShow );
			elements.list.appendChild( chip );

			if ( shouldShow ) {
				visibleCount += 1;
			}
		} );

		this.updateTagEmptyState( elements.emptyState, searchTerm, visibleCount );
	}

	handleTagSelectionChange() {
		this.syncTagManagement();
	}

	handleTagSearchInput() {
		this.syncTagManagement();
	}

	handleCategorySearchInput() {
		this.applyCategorySearch();
	}

	toggleTermsSearchToolbar( args ) {
		const { e, target } = args;
		if ( e ) e.preventDefault();

		const btnSearch = target?.closest( BuilderEditCourse.selectors.elBtnToggleTermsSearch );
		if ( ! btnSearch ) {
			return;
		}

		const toolbarTarget = btnSearch.dataset.toggleTarget || '';
		const toolbar = toolbarTarget ? document.querySelector( toolbarTarget ) : null;
		if ( ! toolbar ) {
			return;
		}

		const willOpen = ! toolbar.classList.contains( 'is-open' );
		toolbar.classList.toggle( 'is-open', willOpen );
		btnSearch.setAttribute( 'aria-expanded', willOpen ? 'true' : 'false' );

		const searchInput = toolbar.querySelector( 'input[type="search"]' );

		if ( willOpen ) {
			if ( searchInput ) {
				setTimeout( () => searchInput.focus(), 220 );
			}
			return;
		}

		if ( searchInput ) {
			searchInput.value = '';
		}

		if ( searchInput?.matches( BuilderEditCourse.selectors.elCategorySearchInput ) ) {
			this.applyCategorySearch();
		} else if ( searchInput?.matches( BuilderEditCourse.selectors.elTagSearchInput ) ) {
			this.syncTagManagement();
		}
	}

	getDirectChildByTagName( element, tagName ) {
		return Array.from( element?.children || [] ).find( ( child ) => child.tagName === tagName );
	}

	getCategoryLabelText( li ) {
		const label = this.getDirectChildByTagName( li, 'LABEL' );
		return label?.textContent?.trim()?.toLowerCase() || '';
	}

	filterCategoryItem( li, searchTerm ) {
		const childList = Array.from( li.children ).find(
			( child ) => child.tagName === 'UL' && child.classList.contains( 'children' )
		);
		const childItems = childList
			? Array.from( childList.children ).filter( ( child ) => child.tagName === 'LI' )
			: [];
		const selfMatch = ! searchTerm || this.getCategoryLabelText( li ).includes( searchTerm );
		const childMatch = childItems.some( ( child ) => this.filterCategoryItem( child, searchTerm ) );
		const shouldShow = ! searchTerm || selfMatch || childMatch;

		li.classList.toggle( 'lp-hidden', ! shouldShow );

		if ( searchTerm && childList ) {
			li.classList.toggle( 'children-visible', selfMatch || childMatch );
		}

		return shouldShow;
	}

	applyCategorySearch() {
		const wrapper = document.querySelector( BuilderEditCourse.selectors.elCategoryDiv );
		const searchInput = document.querySelector( BuilderEditCourse.selectors.elCategorySearchInput );
		if ( ! wrapper || ! searchInput ) {
			return;
		}

		const searchTerm = searchInput.value.trim().toLowerCase();
		const panels = wrapper.querySelectorAll( '.tabs-panel' );

		panels.forEach( ( panel ) => {
			const rootLists = Array.from( panel.children ).filter( ( child ) => child.tagName === 'UL' );
			const items = rootLists.reduce( ( result, list ) => {
				return result.concat(
					Array.from( list.children ).filter( ( child ) => child.tagName === 'LI' )
				);
			}, [] );

			if ( items.length ) {
				items.forEach( ( item ) => this.filterCategoryItem( item, searchTerm ) );
			}
		} );

		if ( ! searchTerm ) {
			this.expandCheckedCategories( wrapper );
		}
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

		this.applyCategorySearch();
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
					this.applyCategorySearch();
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

	/**
	 * Toggle custom category add form (between header and WP meta box).
	 * Shows/hides the form and toggles the Add New button.
	 */
	toggleCustomAddCategoryForm( args ) {
		const { target } = args;
		const elBtnAdd = document.querySelector( BuilderEditCourse.selectors.elBtnAddCategoryNew );
		const form = document.querySelector( BuilderEditCourse.selectors.elFormCategoryAddNew );
		const isOpening = target.closest( BuilderEditCourse.selectors.elBtnAddCategoryNew );

		if ( form ) {
			if ( isOpening ) {
				form.style.display = 'flex';
				if ( elBtnAdd ) elBtnAdd.style.display = 'none';
				const input = form.querySelector( BuilderEditCourse.selectors.elInputAddCategory );
				if ( input ) setTimeout( () => input.focus(), 100 );
			} else {
				form.style.display = 'none';
				if ( elBtnAdd ) elBtnAdd.style.display = 'inline-flex';
			}
		}
	}

	/**
	 * Add new category via custom form (outside WP meta box).
	 * Uses the same AJAX endpoint but reads from custom form fields.
	 */
	addNewCategoryCustom( args ) {
		const { e } = args;
		if ( e ) e.preventDefault();

		const elInput = document.querySelector( BuilderEditCourse.selectors.elInputAddCategory );
		const elParent = document.querySelector(
			BuilderEditCourse.selectors.elSelectAddCategoryParent
		);
		const btnSave = document.querySelector( BuilderEditCourse.selectors.elBtnSaveCategory );

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

					if ( checklist ) {
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
					}

					// Also update the parent select dropdown with the new category
					if ( data.term_id && elParent ) {
						const newOption = document.createElement( 'option' );
						newOption.value = data.term_id;
						newOption.textContent = categoryName;
						elParent.appendChild( newOption );
					}

					elInput.value = '';
					if ( elParent ) elParent.value = '0';
					this.applyCategorySearch();

					// Close the form after successful add
					const elBtnCancel = document.querySelector(
						BuilderEditCourse.selectors.elBtnCancelCategoryNew
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
		const tabItems = document.querySelectorAll(
			BuilderEditCourse.selectors.elTabItems,
		);
		const panels = document.querySelectorAll(
			BuilderEditCourse.selectors.elTabPanels,
		);
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

		const url = new URL( window.location.href );
		url.searchParams.set( 'tab', sectionSlug );
		window.history.replaceState( {}, '', url );

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
		const permalinkInput = document.querySelector(
			BuilderEditCourse.selectors.elPermalinkSlugInput
		);
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

	updateCourse( args ) {
		// Context check: only handle if on course edit page
		if ( ! document.querySelector( BuilderEditCourse.selectors.elDataCourse ) ) {
			return;
		}

		// Prevent double submit while request is running.
		if ( this.isSavingCourse ) {
			return;
		}

		const { e, target } = args;
		if ( e ) {
			e.preventDefault();
		}
		// Find which button was clicked and determine status from data attribute
		const elBtnMainAction = target.closest( BuilderEditCourse.selectors.elBtnMainAction );
		const elBtnHeaderSave = target.closest( BuilderEditCourse.selectors.elBtnHeaderSave );
		const elBtnDropdownAction = target.closest( BuilderEditCourse.selectors.elBtnDropdownAction );

		let targetStatus = 'publish';
		let elBtn = null;

		// Determine status from the clicked button's data-status attribute or class
		if ( elBtnMainAction ) {
			targetStatus = elBtnMainAction.dataset.status || 'publish';
			elBtn = elBtnMainAction;
		} else if ( elBtnDropdownAction ) {
			targetStatus = elBtnDropdownAction.dataset.status || 'publish';
			elBtn = elBtnDropdownAction;
		} else if ( elBtnHeaderSave ) {
			// Header save button uses current main action status
			const mainBtn = document.querySelector( BuilderEditCourse.selectors.elBtnMainAction );
			targetStatus = mainBtn?.dataset.status || 'publish';
			elBtn = elBtnHeaderSave;
		}

		if ( ! elBtn ) return;

		// Course publish status is controlled by the Publish panel when available.
		if ( elBtnMainAction || elBtnHeaderSave ) {
			targetStatus = this.getStatusFromPublishPanel( targetStatus );
		}

		const publishVisibilityEl = document.querySelector(
			BuilderEditCourse.selectors.elPublishVisibilitySelect
		);
		const publishPasswordEl = document.querySelector(
			BuilderEditCourse.selectors.elPublishPasswordInput
		);
		const publishVisibility = publishVisibilityEl?.value || '';
		const publishPassword = publishPasswordEl?.value?.trim?.() || '';

		if ( publishVisibility === 'password' && ! publishPassword ) {
			lpToastify.show( 'Password is required when visibility is password protected.', 'error' );
			publishPasswordEl?.focus?.();
			return;
		}

		// Check if drafting a published course
		if ( targetStatus === 'draft' ) {
			const statusEl = document.querySelector( BuilderEditCourse.selectors.elStatus );
			const isPublished = statusEl && statusEl.classList.contains( 'publish' );
			if ( isPublished ) {
				const confirmMsg =
					elBtn.dataset.confirmUnpublish ||
					'Saving as draft will unpublish this item. Are you sure?';
				if ( ! confirm( confirmMsg ) ) {
					return;
				}
			}
		}

		const setActionLoadingState = ( isLoading ) => {
			const elsToToggle = [];
			const elHeaderSave = document.querySelector( BuilderEditCourse.selectors.elBtnHeaderSave );
			const elMainAction = document.querySelector( BuilderEditCourse.selectors.elBtnMainAction );
			const elDropdownToggle = document.querySelector(
				BuilderEditCourse.selectors.elDropdownToggle
			);

			if ( elHeaderSave ) {
				elsToToggle.push( elHeaderSave );
			}

			if ( elMainAction ) {
				elsToToggle.push( elMainAction );
			}

			if ( elDropdownToggle ) {
				elsToToggle.push( elDropdownToggle );
			}

			if ( elBtn ) {
				elsToToggle.push( elBtn );
			}

			// Keep only unique elements.
			const uniqueEls = [ ...new Set( elsToToggle.filter( Boolean ) ) ];

			uniqueEls.forEach( ( el ) => {
				lpUtils.lpSetLoadingEl( el, isLoading ? 1 : 0 );
				el.classList.toggle( 'lp-loading', Boolean( isLoading ) );

				if ( isLoading ) {
					el.setAttribute( 'aria-disabled', 'true' );
				} else {
					el.removeAttribute( 'aria-disabled' );
				}
			} );

			if ( isLoading && elDropdownToggle ) {
				elDropdownToggle.setAttribute( 'aria-expanded', 'false' );
			}
		};

		this.isSavingCourse = true;
		setActionLoadingState(true);
		const courseData = this.getCourseDataForUpdate();
		const dataSend = {
			...courseData,
			course_status: targetStatus,
			action: 'cb_save_course',
			args: { id_url: 'cb-save-course' },
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
		if ( publishVisibility ) {
			dataSend.course_visibility = publishVisibility;
			dataSend.course_password = publishVisibility === 'password' ? publishPassword : '';
		}

		const publishDateInput = document.querySelector(
			BuilderEditCourse.selectors.elPublishDateInput
		);
		if ( publishDateInput && publishDateInput.value ) {
			dataSend.course_post_date = publishDateInput.value;
		}

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );
				if ( status === 'success' ) {
					this.updateHeaderTitle( courseData.course_title );
					// Dispatch event to reset form state (remove unsaved changes warning)
					document.dispatchEvent( new CustomEvent( 'lp-course-builder-saved' ) );

					if ( data?.html ) {
						const elContent = elBtnMainAction.closest( BuilderEditCourse.selectors.elCBContent );
						const newHtml = new DOMParser().parseFromString( data.html, 'text/html' );
						if ( elContent ) {
							// elContent.outerHTML = data.html;
							// Temporary change each elements
							const elements = [
								".lp-cb-header",
								".cb-course-edit-column--left",
							];

							for ( const elementNew of elements ) {
								const el = elContent.querySelector( elementNew );
								if ( el ) {
									el.innerHTML = newHtml.querySelector( elementNew ).innerHTML;
								}
							}
						}
					}
				}

				// Use redirect_url from backend if available (for new courses)
				if ( data?.redirect_url ) {
					window.location.href = data.redirect_url;
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				this.isSavingCourse = false;
				setActionLoadingState( false );
			},
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Resolve immediate UI status after save to avoid stale action labels
	 * in review-only course workflow.
	 *
	 * @param {string|undefined} savedStatus
	 * @param {string} requestedStatus
	 * @return {string}
	 */
	resolveStatusForUIAfterSave( savedStatus, requestedStatus ) {
		const normalizedSavedStatus =
			typeof savedStatus === 'string' ? savedStatus.trim().toLowerCase() : '';

		const normalizedRequestedStatus =
			typeof requestedStatus === 'string' ? requestedStatus.trim().toLowerCase() : '';

		const dropdown = document.querySelector( BuilderEditCourse.selectors.elHeaderActionsDropdown );
		const reviewOnlyCourseAttr = ( dropdown?.dataset?.reviewOnlyCourse || '' ).toLowerCase();
		const isReviewOnlyCourse = [ 'yes', 'true', '1' ].includes( reviewOnlyCourseAttr );

		if (
			isReviewOnlyCourse &&
			[ 'publish', 'private', 'future' ].includes( normalizedRequestedStatus )
		) {
			return 'pending';
		}

		return normalizedSavedStatus || normalizedRequestedStatus || 'draft';
	}

	/**
	 * Resolve target status from Publish panel controls.
	 *
	 * @param {string} fallbackStatus
	 * @return {string}
	 */
	getStatusFromPublishPanel( fallbackStatus = 'publish' ) {
		const statusSelect = document.querySelector(
			BuilderEditCourse.selectors.elPublishStatusSelect
		);
		const visibilitySelect = document.querySelector(
			BuilderEditCourse.selectors.elPublishVisibilitySelect
		);

		let status = statusSelect?.value || fallbackStatus || 'publish';
		const visibility = visibilitySelect?.value || 'public';

		if ( visibility === 'private' && status !== 'pending' ) {
			status = 'private';
		}

		if ( [ 'public', 'password' ].includes( visibility ) && status === 'private' ) {
			status = 'publish';
		}

		const allowedStatuses = [ 'publish', 'future', 'draft', 'pending', 'private' ];
		if ( ! allowedStatuses.includes( status ) ) {
			return 'publish';
		}

		return status;
	}

	/**
	 * Sync Publish panel controls after save/trash.
	 *
	 * @param {string} status
	 */
	syncPublishPanelStatus( status, visibility = '' ) {
		const statusSelect = document.querySelector(
			BuilderEditCourse.selectors.elPublishStatusSelect
		);
		const visibilitySelect = document.querySelector(
			BuilderEditCourse.selectors.elPublishVisibilitySelect
		);

		if ( ! statusSelect && ! visibilitySelect ) {
			return;
		}

		const normalized = ( status || '' ).toString().toLowerCase();
		const currentPrimaryStatus = ( statusSelect?.dataset?.primaryStatus || '' )
			.toString()
			.toLowerCase();
		let primaryStatusToKeep = [ 'future', 'publish' ].includes( currentPrimaryStatus )
			? currentPrimaryStatus
			: 'publish';

		if ( normalized === 'future' ) {
			primaryStatusToKeep = 'future';
		} else if ( [ 'publish', 'private' ].includes( normalized ) ) {
			primaryStatusToKeep = 'publish';
		}

		if ( statusSelect ) {
			statusSelect.dataset.primaryStatus = primaryStatusToKeep;
		}

		if ( statusSelect ) {
			if ( normalized === 'private' ) {
				statusSelect.value = 'publish';
			} else if ( [ 'publish', 'future', 'draft', 'pending' ].includes( normalized ) ) {
				statusSelect.value = normalized;
			}
		}

		this.syncPublishStatusOptions( normalized );

		if ( visibilitySelect ) {
			let visibilityToSet = visibility || visibilitySelect.value || 'public';
			if ( normalized === 'private' ) {
				visibilityToSet = 'private';
			} else if ( visibilityToSet === 'private' ) {
				visibilityToSet = 'public';
			}
			visibilitySelect.value = visibilityToSet;
		}

		this.syncPublishVisibilityControls();
		this.syncPublishDateLabel();
	}

	handlePublishVisibilityChange() {
		this.syncPublishVisibilityControls( true );
	}

	handlePublishStatusChange() {
		this.syncPublishStatusOptions();
		this.syncPublishDateLabel();
	}

	handlePublishDateChange() {
		this.syncPublishDateLabel();
	}

	syncPublishVisibilityControls( focusPassword = false ) {
		const visibilitySelect = document.querySelector(
			BuilderEditCourse.selectors.elPublishVisibilitySelect
		);
		const passwordRow = document.querySelector( BuilderEditCourse.selectors.elPublishPasswordRow );
		const passwordInput = document.querySelector(
			BuilderEditCourse.selectors.elPublishPasswordInput
		);

		if ( ! visibilitySelect || ! passwordRow ) {
			return;
		}

		const isPassword = visibilitySelect.value === 'password';
		passwordRow.classList.toggle( 'lp-hidden', ! isPassword );

		if ( focusPassword && isPassword && passwordInput ) {
			passwordInput.focus();
		}
	}

	syncPublishDateLabel() {
		const dateInput = document.querySelector( BuilderEditCourse.selectors.elPublishDateInput );
		const dateLabel = document.querySelector( BuilderEditCourse.selectors.elPublishDateLabel );

		if ( ! dateLabel ) {
			return;
		}

		const dateValue = ( dateInput?.value || '' ).toString().trim();
		let isFutureDate = false;

		if ( dateValue ) {
			const parsedDate = new Date( dateValue );
			if ( ! Number.isNaN( parsedDate.getTime() ) ) {
				isFutureDate = parsedDate.getTime() > Date.now();
			}
		}

		dateLabel.textContent = isFutureDate ? 'Scheduled for' : 'Published on';
	}

	syncPublishStatusOptions( preferredStatus = '' ) {
		const statusSelect = document.querySelector(
			BuilderEditCourse.selectors.elPublishStatusSelect
		);
		if ( ! statusSelect ) {
			return;
		}

		const publishLabel = statusSelect.dataset.publishLabel || 'Published';
		const futureLabel = statusSelect.dataset.futureLabel || 'Scheduled';
		const selectedStatus = ( preferredStatus || statusSelect.value || '' ).toLowerCase();
		const currentPrimaryStatus = ( statusSelect.dataset.primaryStatus || '' ).toLowerCase();
		const draftLabel =
			statusSelect.querySelector( 'option[value="draft"]' )?.textContent || 'Draft';
		const pendingLabel =
			statusSelect.querySelector( 'option[value="pending"]' )?.textContent || 'Pending Review';
		const hasFutureOption = !! statusSelect.querySelector( 'option[value="future"]' );
		const hasPublishOption = !! statusSelect.querySelector( 'option[value="publish"]' );

		let primaryValue = 'publish';
		if ( [ 'future', 'publish' ].includes( selectedStatus ) ) {
			primaryValue = selectedStatus;
		} else if ( [ 'future', 'publish' ].includes( currentPrimaryStatus ) ) {
			primaryValue = currentPrimaryStatus;
		} else if ( hasFutureOption && ! hasPublishOption ) {
			primaryValue = 'future';
		}

		const primaryLabel = primaryValue === 'future' ? futureLabel : publishLabel;
		const valueToSelect = [ 'draft', 'pending', 'future', 'publish' ].includes( selectedStatus )
			? selectedStatus
			: primaryValue;

		statusSelect.dataset.primaryStatus = primaryValue;

		statusSelect.innerHTML = '';

		[
			{ value: primaryValue, label: primaryLabel },
			{ value: 'draft', label: draftLabel },
			{ value: 'pending', label: pendingLabel },
		].forEach( ( optionData ) => {
			const option = document.createElement( 'option' );
			option.value = optionData.value;
			option.textContent = optionData.label;
			statusSelect.appendChild( option );
		} );

		statusSelect.value = valueToSelect;
	}

	formatStatusLabel( status ) {
		const normalized = ( status || '' ).toString().toLowerCase();
		return normalized === 'future' ? 'scheduled' : normalized;
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
		if ( ! mainBtn ) return;

		const hasSingleCourseAction =
			dropdown.classList.contains( 'cb-header-actions-dropdown--single' ) || ! dropdownMenu;

		if ( hasSingleCourseAction ) {
			mainBtn.className = 'cb-btn-update cb-btn-primary cb-btn-main-action';
			mainBtn.dataset.status = this.getStatusFromPublishPanel( 'publish' );
			mainBtn.textContent = mainBtn.dataset.titleUpdate || 'Update';
			dropdown.dataset.currentStatus = newStatus;
			return;
		}

		const reviewOnlyCourseAttr = ( dropdown.dataset.reviewOnlyCourse || '' ).toLowerCase();
		const isReviewOnlyCourse = [ 'yes', 'true', '1' ].includes( reviewOnlyCourseAttr );

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
				mainLabel: mainBtn.dataset.titlePublish || 'Publish',
				mainClass: 'cb-btn-publish',
				mainStatus: 'publish',
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
		const reviewStatusConfig = {
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
				dropdownLabel: mainBtn.dataset.titleSubmitReview || 'Submit for Review',
				dropdownClass: 'cb-btn-pending',
				dropdownStatus: 'pending',
				dropdownIcon: 'dashicons-clock',
			},
			pending: {
				mainLabel: mainBtn.dataset.titleSubmitReview || 'Submit for Review',
				mainClass: 'cb-btn-pending',
				mainStatus: 'pending',
				dropdownLabel: mainBtn.dataset.titleDraft || 'Save Draft',
				dropdownClass: 'cb-btn-darft',
				dropdownStatus: 'draft',
				dropdownIcon: 'dashicons-media-default',
			},
			'auto-draft': {
				mainLabel: mainBtn.dataset.titleSubmitReview || 'Submit for Review',
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
				dropdownLabel: mainBtn.dataset.titleSubmitReview || 'Submit for Review',
				dropdownClass: 'cb-btn-pending',
				dropdownStatus: 'pending',
				dropdownIcon: 'dashicons-clock',
			},
			private: {
				mainLabel: mainBtn.dataset.titleSubmitReview || 'Submit for Review',
				mainClass: 'cb-btn-pending',
				mainStatus: 'pending',
				dropdownLabel: mainBtn.dataset.titleDraft || 'Save Draft',
				dropdownClass: 'cb-btn-darft',
				dropdownStatus: 'draft',
				dropdownIcon: 'dashicons-media-default',
			},
		};

		const config = isReviewOnlyCourse
			? reviewStatusConfig[ newStatus ] || reviewStatusConfig.draft
			: statusConfig[ newStatus ] || statusConfig.draft;

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

		// Keep dropdown color/state aligned with the main action button.
		dropdown.dataset.currentStatus = config.mainStatus;
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

	async duplicateCourse( args ) {
		// Context check: only handle if on course edit page
		if ( ! document.querySelector( BuilderEditCourse.selectors.elDataCourse ) ) {
			return;
		}

		const { target } = args;
		const elBtnDuplicateCourse = target.closest( BuilderEditCourse.selectors.elBtnDuplicateCourse );

		if ( ! elBtnDuplicateCourse ) {
			return;
		}

		const courseData = this.getCourseDataForUpdate();
		const courseId = parseInt( courseData?.course_id, 10 ) || 0;

		if ( ! courseId ) {
			return;
		}

		const result = await SweetAlert.fire( {
			title: elBtnDuplicateCourse.dataset.title || 'Are you sure?',
			text:
				elBtnDuplicateCourse.dataset.content || 'Are you sure you want to duplicate this course?',
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

		lpUtils.lpSetLoadingEl( elBtnDuplicateCourse, 1 );

		const dataSend = {
			action: 'duplicate_course',
			args: { id_url: 'duplicate-course' },
			course_id: courseId,
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
				lpUtils.lpSetLoadingEl( elBtnDuplicateCourse, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
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
						elStatus.textContent = this.formatStatusLabel( data.status );
					}
					this.syncPublishPanelStatus( data.status );
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
		const form = document.querySelector( BuilderEditCourse.selectors.elFormTagAddNew );
		const isOpening = target.closest( BuilderEditCourse.selectors.elBtnAddTagNew );
		if ( form ) {
			if ( isOpening ) {
				form.style.display = 'flex';
				if ( elBtnAdd ) elBtnAdd.style.display = 'none';
				const input = form.querySelector( BuilderEditCourse.selectors.elInputAddTag );
				if ( input ) setTimeout( () => input.focus(), 100 );
			} else {
				form.style.display = 'none';
				if ( elBtnAdd ) elBtnAdd.style.display = 'inline-flex';
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
					this.syncTagManagement();
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

	buildPermalinkDisplayUrl( baseUrl = '', slug = '', fallbackUrl = '' ) {
		const normalizedBaseUrl = typeof baseUrl === 'string' ? baseUrl : '';
		const normalizedSlug = typeof slug === 'string' ? slug.trim() : '';

		if ( normalizedBaseUrl && normalizedSlug ) {
			return `${ normalizedBaseUrl }${ normalizedSlug }`;
		}

		return typeof fallbackUrl === 'string' ? fallbackUrl : '';
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
		let newSlug = input.value.trim();

		// If empty after sanitizing, restore original
		if ( ! newSlug ) {
			newSlug = input.dataset.originalValue || 'course';
		}

		// Update input value with sanitized slug
		input.value = newSlug;

		// Get base URL
		const baseUrl = baseUrlInput ? baseUrlInput.value : '';
		const newUrl = this.buildPermalinkDisplayUrl( baseUrl, newSlug, urlLink.textContent || '' );

		// Keep href as the current saved link and only update display text.
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
