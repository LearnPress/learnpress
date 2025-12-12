import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { EditCourseCurriculum } from 'lpAssetsJsPath/admin/edit-course/edit-curriculum';
import { MetaboxExtraInfo } from './extra-info.js';

export class BuilderEditCourse {
	constructor() {
		this.init();
	}

	static selectors = {
		elTabLinks: '.lp-meta-box__course-tab__tabs li a',
		elTabItems: '.lp-meta-box__course-tab__tabs li',
		elTabPanels: '.lp-meta-box-course-panels',

		elDataCourse: '.cb-section__course-edit',
		elBtnUpdateCourse: '.cb-btn-update',
		elBtnDraftCourse: '.cb-btn-darft',
		elBtnTrashCourse: '.cb-btn-trash',
		elTitleInput: '#title',
		elDescEditor: '#course_description_editor',
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
		elBtnSetFeatured: '.cb-set-featured-image',
		elFeaturedImagePreview: '.cb-featured-image-preview',
		elThumbnailInput: '#course_thumbnail_id',
		elFeatureImagePlaceholder: '.cb-featured-image-placeholder',

		elPriceCourseData: '#price_course_data',
		elSaleDatesFields: '.lp_sale_dates_fields',
		elSalePriceScheduleBtn: '.lp_sale_price_schedule',
		elCancelSaleScheduleBtn: '.lp_cancel_sale_schedule',
		elRegularPriceInput: '#_lp_regular_price',
		elSalePriceInput: '#_lp_sale_price',
		elFormField: '.form-field',
		elTipFloating: '.learn-press-tip-floating',

		elCategoryDiv: '#taxonomy-course_category',
		elCategoryTabs: '#course_category-tabs li a',
		elCategoryPanels: '#taxonomy-course_category .tabs-panel',

		elBtnToggleAddCategory: '#course_category-add-toggle',
		elFormCategoryWrapper: '#course_category-add',
		elInputNewCategory: '#newcourse_category',
		elSelectParentCategory: '#newcourse_category_parent',
		elBtnSubmitCategory: '#course_category-add-submit',
		elCategoryChecklist: '#course_categorychecklist',
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
				selector: BuilderEditCourse.selectors.elBtnUpdateCourse,
				class: this,
				callBack: this.updateCourse.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnDraftCourse,
				class: this,
				callBack: this.updateCourse.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnTrashCourse,
				class: this,
				callBack: this.trashCourse.name,
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
			{
				selector: BuilderEditCourse.selectors.elCategoryTabs,
				class: this,
				callBack: this.handleCategoryTabClick.name,
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

	addNewCategory( args ) {
		const { e } = args;
		if ( e ) e.preventDefault();

		const elInput = document.querySelector( BuilderEditCourse.selectors.elInputNewCategory );
		const elParent = document.querySelector( BuilderEditCourse.selectors.elSelectParentCategory );
		const btnSave = document.querySelector( BuilderEditCourse.selectors.elBtnSubmitCategory );

		if ( ! elInput ) return;
		const categoryName = elInput.value?.trim();

		if ( ! categoryName ) {
			lpToastify.show( 'Please enter category name', 'error' );
			return;
		}

		lpUtils.lpSetLoadingEl( btnSave, 1 );

		const dataSend = {
			action: 'add_course_category',
			args: { id_url: 'add-course-category' },
			name: categoryName,
			parent: elParent ? elParent.value : -1,
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
						checklist.insertAdjacentHTML( 'afterbegin', data.html );
					}

					elInput.value = '';
					if ( elParent ) elParent.value = '-1';
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

	initCategoryTree() {
		const wrapper = document.querySelector( '#taxonomy-course_category' );
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

		if ( ! elInput ) return;

		const categoryName = elInput.value?.trim();
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
					if ( elParent ) elParent.value = '-1';
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
			if ( targetId === '_lp_price' ) {
				tip.innerHTML = i18n.notice_price;
			} else if ( targetId === '_lp_sale_price' ) {
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

	getCourseDataForUpdate() {
		const data = {};
		const wrapperEl = document.querySelector( BuilderEditCourse.selectors.elDataCourse );
		data.course_id = wrapperEl ? parseInt( wrapperEl.dataset.courseId ) || 0 : 0;
		const titleInput = document.querySelector( BuilderEditCourse.selectors.elTitleInput );
		data.course_title = titleInput ? titleInput.value : '';
		const descEditor = document.querySelector( BuilderEditCourse.selectors.elDescEditor );
		data.course_description = descEditor ? descEditor.value : '';
		if ( typeof tinymce !== 'undefined' ) {
			const editor = tinymce.get( 'course_description_editor' );
			if ( editor ) {
				data.course_description = editor.getContent();
			}
		}
		data.course_categories = [];
		document
			.querySelectorAll( '#taxonomy-course_category input[name*="course_category"]:checked' )
			.forEach( ( checkbox ) => data.course_categories.push( checkbox.value ) );
		data.course_tags = [];
		document
			.querySelectorAll( 'input[name="course_tags[]"]:checked' )
			.forEach( ( checkbox ) => data.course_tags.push( checkbox.value ) );
		const thumbnailInput = document.querySelector( BuilderEditCourse.selectors.elThumbnailInput );
		data.course_thumbnail_id = thumbnailInput ? thumbnailInput.value : '0';
		const elFormSetting = document.querySelector( BuilderEditCourse.selectors.elFormSetting );
		if ( elFormSetting ) {
			data.course_settings = true;
			const formElements = elFormSetting.querySelectorAll( 'input, select, textarea' );
			formElements.forEach( ( element ) => {
				const name = element.name || element.id;
				if ( ! name ) return;
				if ( name === 'learnpress_meta_box_nonce' || name === '_wp_http_referer' ) return;
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
		const { e, target } = args;
		if ( ! this.validatePricingBeforeUpdate() ) return;
		const elBtnUpdateCourse = target.closest( BuilderEditCourse.selectors.elBtnUpdateCourse );
		const elBtnDraftCourse = target.closest( BuilderEditCourse.selectors.elBtnDraftCourse );
		let status = 'publish';
		let elBtn = elBtnUpdateCourse;
		if ( elBtnDraftCourse ) {
			status = 'draft';
			elBtn = elBtnDraftCourse;
		}
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
		if ( courseData.course_categories.length > 0 ) {
			dataSend.course_categories = courseData.course_categories.join( ',' );
		}
		if ( courseData.course_tags.length > 0 ) {
			dataSend.course_tags = courseData.course_tags.join( ',' );
		}
		if ( courseData.course_thumbnail_id ) {
			dataSend.course_thumbnail_id = courseData.course_thumbnail_id;
		}
		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );
				if ( data?.button_title ) {
					const updateBtn = document.querySelector( BuilderEditCourse.selectors.elBtnUpdateCourse );
					if ( updateBtn ) updateBtn.textContent = data.button_title;
				}
				if ( data?.course_id_new ) {
					const currentUrl = window.location.href;
					window.location.href = currentUrl.replace( /post-new\/?/, `${ data.course_id_new }/` );
				}
				if ( data?.status ) {
					const elStatus = document.querySelector( BuilderEditCourse.selectors.elStatus );
					if ( elStatus ) {
						elStatus.className = 'course-status ' + data.status;
						elStatus.textContent = data.status;
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

	trashCourse( args ) {
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
		if ( ! elInput ) return;
		lpUtils.lpSetLoadingEl( btnSave, 1 );
		const tagName = elInput.value?.trim() ?? '';
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
		const previewContainer = document.querySelector(
			BuilderEditCourse.selectors.elFeaturedImagePreview
		);
		const thumbnailInput = document.querySelector( BuilderEditCourse.selectors.elThumbnailInput );
		const placeholder = previewContainer.querySelector(
			BuilderEditCourse.selectors.elFeatureImagePlaceholder
		);
		if ( ! previewContainer || ! thumbnailInput ) return;
		thumbnailInput.value = attachment.id;
		const imgUrl =
			attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || attachment.url;
		if ( placeholder ) placeholder.remove();
		const oldImg = previewContainer.querySelector( 'img' );
		if ( oldImg ) oldImg.remove();
		const img = document.createElement( 'img' );
		img.src = imgUrl;
		previewContainer.appendChild( img );
		const elRemoveButton = document.querySelector(
			BuilderEditCourse.selectors.elBtnRemoveFeatured
		);
		if ( elRemoveButton ) elRemoveButton.style.display = 'inline-block';
	}

	removeFeaturedImage( args ) {
		const previewContainer = document.querySelector(
			BuilderEditCourse.selectors.elFeaturedImagePreview
		);
		const thumbnailInput = document.querySelector( BuilderEditCourse.selectors.elThumbnailInput );
		const elRemoveButton = document.querySelector(
			BuilderEditCourse.selectors.elBtnRemoveFeatured
		);
		const img = previewContainer.querySelector( 'img' );
		if ( img ) img.remove();
		const placeholder = document.createElement( 'div' );
		placeholder.className = BuilderEditCourse.selectors.elFeatureImagePlaceholder.replace(
			'.',
			''
		);
		placeholder.textContent = previewContainer.dataset.contentPlacholder || 'No image selected';
		previewContainer.appendChild( placeholder );
		thumbnailInput.value = '0';
		if ( elRemoveButton ) elRemoveButton.style.display = 'none';
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
}
