import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

export class BuilderEditCourse {
	constructor() {
		this.init();
	}

	static selectors = {
		elDataCourse: '.cb-section__course-overview',
		elBtnUpdateCourse: '.cb-btn-update',
		elBtnDraftCourse: '.cb-btn-darft',
		elBtnTrashCourse: '.cb-btn-trash',
		elWrapperCheckBoxCategory: '.cb-course-edit-categories__checkbox-wrapper',
		elFormCategoryAddNew: '.cb-course-edit-terms__form-add-category',
		elBtnAddCategoryNew: '.cb-course-edit-category__btn-add-new',
		elBtnCancelCategoryNew: '.cb-course-edit-category__btn-cancel',
		elBtnSaveCategory: '.cb-course-edit-category__btn-save',
		elInputCategory: '.cb-course-edit-category__input',
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
		elTitleInput: '#title',
		elDescEditor: '#course_description_editor',
		elStatus: '.course-status',
	};

	init() {
		this.events();
	}

	events() {
		if ( BuilderEditCourse._loadedEvents ) {
			return;
		}
		BuilderEditCourse._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
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
				selector: BuilderEditCourse.selectors.elBtnAddCategoryNew,
				class: this,
				callBack: this.toggleAddCategoryForm.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnCancelCategoryNew,
				class: this,
				callBack: this.toggleAddCategoryForm.name,
			},
			{
				selector: BuilderEditCourse.selectors.elBtnSaveCategory,
				class: this,
				callBack: this.addNewCategory.name,
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
		] );

		lpUtils.eventHandlers( 'keydown', [
			{
				selector: BuilderEditCourse.selectors.elInputCategory,
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

		// Categories
		data.course_categories = [];
		document
			.querySelectorAll( 'input[name="course_categories[]"]:checked' )
			.forEach( ( checkbox ) => data.course_categories.push( checkbox.value ) );

		// Tags
		data.course_tags = [];
		document
			.querySelectorAll( 'input[name="course_tags[]"]:checked' )
			.forEach( ( checkbox ) => data.course_tags.push( checkbox.value ) );

		// Thumbnail
		const thumbnailInput = document.querySelector( BuilderEditCourse.selectors.elThumbnailInput );
		data.course_thumbnail_id = thumbnailInput ? thumbnailInput.value : '0';

		return data;
	}

	updateCourse( args ) {
		const { e, target } = args;
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
			action: 'save_courses',
			args: { id_url: 'save-courses' },
			course_title: courseData.course_title,
			course_description: courseData.course_description,
			course_id: courseData.course_id,
			course_status: status,
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

	toggleAddCategoryForm( args ) {
		const { target } = args;
		const elBtnAdd = document.querySelector( BuilderEditCourse.selectors.elBtnAddCategoryNew );
		const elBtnCancel = document.querySelector(
			BuilderEditCourse.selectors.elBtnCancelCategoryNew
		);
		const form = document.querySelector( BuilderEditCourse.selectors.elFormCategoryAddNew );

		// Determine if we are opening or closing based on which button was clicked
		const isOpening = target.closest( BuilderEditCourse.selectors.elBtnAddCategoryNew );

		if ( form ) {
			if ( ! isOpening ) {
				form.style.display = 'none';
				if ( elBtnCancel ) elBtnCancel.style.display = 'none';
				if ( elBtnAdd ) elBtnAdd.style.display = 'inline-block';
			} else {
				if ( elBtnAdd ) elBtnAdd.style.display = 'none';
				if ( elBtnCancel ) elBtnCancel.style.display = 'inline-block';
				form.style.display = 'flex';
				const input = form.querySelector( BuilderEditCourse.selectors.elInputCategory );
				if ( input ) setTimeout( () => input.focus(), 100 );
			}
		}
	}

	addNewCategory( args ) {
		const { e, target } = args;

		const elInput = document.querySelector( BuilderEditCourse.selectors.elInputCategory );
		const btnSave = document.querySelector( BuilderEditCourse.selectors.elBtnSaveCategory );

		if ( ! elInput ) return;

		lpUtils.lpSetLoadingEl( btnSave, 1 );

		const categoryName = elInput.value?.trim() ?? '';

		const dataSend = {
			action: 'add_course_category',
			args: { id_url: 'add-course-category' },
			name: categoryName,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;

				lpToastify.show( message, status );

				if ( data?.html ) {
					const wrapper = document.querySelector(
						BuilderEditCourse.selectors.elWrapperCheckBoxCategory
					);
					wrapper.insertAdjacentHTML( 'beforeend', data.html );
					elInput.value = '';

					// Reuse toggle logic to close
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
		if ( typeof wp === 'undefined' || typeof wp.media === 'undefined' ) {
			return;
		}

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
}
