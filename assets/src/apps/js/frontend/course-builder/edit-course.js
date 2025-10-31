import * as lpUtils from '../../utils/utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

const className = {
	elDataCourse: '.cb-section__course-overview',
	elBtnUpdateCourse: '.cb-btn-update',
	elBtnDraftCourse: '.cb-btn-darft',
	elBtnTrashCourse: '.cb-btn-trash',
	elFormCategoryAddNew: '.cb-course-edit-terms__form-add-category',
	elBtnAddCategoryNew: '.cb-course-edit-category__btn-add-new ',
	elBtnSaveCategory: '.cb-course-edit-category__btn-save',
	elInputCategory: '.cb-course-edit-category__input',
	elFormTermAddNew: '.cb-course-edit-terms__form-add-term',
	elBtnAddTermNew: '.cb-course-edit-term__btn-add-new ',
	elBtnSaveTerm: '.cb-course-edit-terms__btn-save',
	elInputAddTerm: 'cb-course-edit-terms__input',
	elBtnRemoveFeatured: '.cb-remove-featured-image',
	elBtnSetFeatured: '.cb-set-featured-image',
	elFeaturedImagePreview: '.cb-featured-image-preview',
	elThumbnailInput: '#course_thumbnail_id',
	elFeatureImagePlaceholder: '.cb-featured-image-placeholder',
};

const attributeData = {
	courseId: 'data-course-id',
};

const argsToastify = {
	text: '',
	gravity: 'bottom', // `top` or `bottom` or lpDataAdmin.toast.gravity
	position: 'center', // `left`, `center` or `right` or  lpDataAdmin.toast.position
	className: 'lp-toast', // `${ lpDataAdmin.toast.classPrefix }`
	close: true,
	stopOnFocus: true,
	duration: 3000, //lpDataAdmin.toast.duration
};

const showToast = ( message, status = 'success' ) => {
	const toastify = new Toastify( {
		...argsToastify,
		text: message,
		className: `lp-toast ${ status }`,
	} );
	toastify.showToast();
};

const getCourseDataForUpdate = () => {
	const data = {};

	const wrapperEl = document.querySelector( className.elDataCourse );
	if ( wrapperEl ) {
		data.course_id = parseInt( wrapperEl.getAttribute( attributeData.courseId ) ) || 0;
	} else {
		data.course_id = 0;
	}

	const titleInput = document.getElementById( 'title' );
	if ( titleInput ) {
		data.course_title = titleInput.value;
	} else {
		data.course_title = '';
	}

	const descEditor = document.getElementById( 'course_description_editor' );
	if ( descEditor ) {
		data.course_description = descEditor.value;
	} else {
		console.warn( 'Description editor not found' );
		data.course_description = '';
	}

	if ( typeof tinymce !== 'undefined' ) {
		const editor = tinymce.get( 'course_description_editor' );
		if ( editor ) {
			data.course_description = editor.getContent();
		}
	}

	data.course_categories = [];
	const categoryCheckboxes = document.querySelectorAll(
		'input[name="course_categories[]"]:checked'
	);
	categoryCheckboxes.forEach( ( checkbox ) => {
		data.course_categories.push( checkbox.value );
	} );

	data.course_terms = [];
	const tagCheckboxes = document.querySelectorAll( 'input[name="course_terms[]"]:checked' );
	tagCheckboxes.forEach( ( checkbox ) => {
		data.course_terms.push( checkbox.value );
	} );

	const thumbnailInput = document.querySelector( className.elThumbnailInput );
	if ( thumbnailInput ) {
		data.course_thumbnail_id = thumbnailInput.value;
	} else {
		data.course_thumbnail_id = '0';
	}

	return data;
};

const updateCourse = ( e, target ) => {
	const elBtnUpdateCourse = target.closest( `${ className.elBtnUpdateCourse }` );
	const elBtnDraftCourse = target.closest( `${ className.elBtnDraftCourse }` );

	if ( ! elBtnUpdateCourse && ! elBtnDraftCourse ) {
		return;
	}

	let status = 'publish';
	let elBtn;

	if ( elBtnDraftCourse ) {
		status = 'draft';
		elBtn = elBtnDraftCourse;
	} else {
		elBtn = elBtnUpdateCourse;
	}

	lpUtils.lpSetLoadingEl( elBtn, 1 );

	const courseData = getCourseDataForUpdate();

	const dataSend = {
		action: 'save_courses',
		args: {
			id_url: 'save-courses',
		},
		course_title: courseData.course_title || '',
		course_description: courseData.course_description || '',
		course_id: courseData.course_id || 0,
		course_status: status,
	};

	if ( typeof lpCourseBuilder !== 'undefined' && lpCourseBuilder.nonce ) {
		dataSend.nonce = lpCourseBuilder.nonce;
	}

	if ( courseData.course_categories && courseData.course_categories.length > 0 ) {
		dataSend.course_categories = courseData.course_categories.join( ',' );
	}

	if ( courseData.course_terms && courseData.course_terms.length > 0 ) {
		dataSend.course_terms = courseData.course_terms.join( ',' );
	}

	if ( courseData.course_thumbnail_id ) {
		dataSend.course_thumbnail_id = courseData.course_thumbnail_id;
	}

	const callBack = {
		success: ( response ) => {
			const { status, message } = response;
			showToast( message, status );
		},
		error: ( error ) => {
			showToast( error.message || error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elBtn, 0 );
		},
	};

	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

const trashCourse = ( e, target ) => {
	const elBtnTrashCourse = target.closest( `${ className.elBtnTrashCourse }` );

	if ( ! elBtnTrashCourse ) {
		return;
	}

	lpUtils.lpSetLoadingEl( elBtnTrashCourse, 1 );

	const courseData = getCourseDataForUpdate();
	const dataSend = {
		action: 'move_trash_course',
		args: {
			id_url: 'move-trash-course',
		},
		course_id: courseData.course_id || 0,
		status,
	};

	const callBack = {
		success: ( response ) => {
			const { status, message } = response;
			showToast( message, status );
		},
		error: ( error ) => {
			showToast( error.message || error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elBtnTrashCourse, 0 );
		},
	};

	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

const toggleAddCategoryForm = ( e, target ) => {
	const elBtnAddCategoryNew = target.closest( `${ className.elBtnAddCategoryNew }` );
	if ( ! elBtnAddCategoryNew ) {
		return;
	}

	const form = document.querySelector( className.elFormCategoryAddNew );

	if ( form ) {
		const isVisible = form.style.display !== 'none';
		form.style.display = isVisible ? 'none' : 'flex';
		if ( elBtnAddCategoryNew ) {
			elBtnAddCategoryNew.textContent = isVisible ? 'Add New Category' : 'Cancel';
		}

		if ( ! isVisible ) {
			const input = form.querySelector( '.cb-course-edit-category__input' );
			if ( input ) {
				setTimeout( () => input.focus(), 100 );
			}
		}
	}
};

const addNewCategory = ( e, target ) => {
	const elBtnSaveCategory = target.closest( `${ className.elBtnSaveCategory }` );
	const elInputCategory = target.closest( className.elInputCategory );
	if ( ! elBtnSaveCategory && ! elInputCategory ) {
		return;
	}

	const elInputCategory = document.querySelector( className.elInputCategory );
	const categoryValue = elInputCategory ? elInputCategory.value.trim() : '';

	if ( ! categoryName ) {
		return;
	}

	const formData = new FormData();
	formData.append( 'action', 'add_course_category' );
	formData.append( 'category_name', categoryName );

	const postId = document.querySelector( className.elDataCourse )?.getAttribute( 'data-course-id' );
	if ( postId ) {
		formData.append( 'post_id', postId );
	}

	fetch( ajaxurl || '/wp-admin/admin-ajax.php', {
		method: 'POST',
		body: formData,
		credentials: 'same-origin',
	} )
		.then( ( response ) => response.json() )
		.then( ( data ) => {
			console.log( 'Category added:', data );

			if ( data.success && data.data ) {
				const wrapper = document.querySelector( '.cb-course-edit-categories__wrapper' );
				const btnAddNew = wrapper.querySelector( '.btn-add-new' );

				const newCheckbox = document.createElement( 'div' );
				newCheckbox.className = 'cb-course-edit-categories__checkbox';
				newCheckbox.innerHTML = `
          <input type="checkbox" name="course_categories[]" value="${ data.data.term_id }" 
                 id="course_category_${ data.data.term_id }" checked="checked">
          <label for="course_category_${ data.data.term_id }">${ data.data.name }</label>
        `;

				wrapper.insertBefore( newCheckbox, btnAddNew );

				// Reset form
				input.value = '';
				toggleAddCategoryForm();

				alert( 'Category đã được thêm thành công!' );
			} else {
				alert( 'Lỗi: ' + ( data.message || 'Không thể thêm category' ) );
			}
		} )
		.catch( ( error ) => {
			console.error( 'Error adding category:', error );
			alert( 'Có lỗi xảy ra khi thêm category!' );
		} );
};

const toggleAddTagForm = ( e, target ) => {
	const elBtnAddTermNew = target.closest( `${ className.elBtnAddTermNew }` );
	if ( ! elBtnAddTermNew ) {
		return;
	}
	const form = document.querySelector( className.elFormTermAddNew );

	if ( form ) {
		const isVisible = form.style.display !== 'none';
		form.style.display = isVisible ? 'none' : 'flex';
		if ( elBtnAddTermNew ) {
			elBtnAddTermNew.textContent = isVisible ? 'Add New Tag' : 'Cancel';
		}

		if ( ! isVisible ) {
			const input = form.querySelector( className.elInputAddTerm );
			if ( input ) {
				setTimeout( () => input.focus(), 100 );
			}
		}
	}
};

const addNewTag = () => {
	const input = document.querySelector( '.cb-course-edit-terms__input' );
	const tagName = input ? input.value.trim() : '';

	if ( ! tagName ) {
		return;
	}

	const formData = new FormData();
	formData.append( 'action', 'add_course_tag' );
	formData.append( 'tag_name', tagName );

	const postId = document
		.querySelector( className.elBtnSetFeatured )
		?.getAttribute( 'data-post-id' );
	if ( postId ) {
		formData.append( 'post_id', postId );
	}

	fetch( ajaxurl || '/wp-admin/admin-ajax.php', {
		method: 'POST',
		body: formData,
		credentials: 'same-origin',
	} )
		.then( ( response ) => response.json() )
		.then( ( data ) => {
			console.log( 'Tag added:', data );

			if ( data.success && data.data ) {
				const wrapper = document.querySelector( '.cb-course-edit-terms__wrapper' );
				const btnAddNew = wrapper.querySelector( '.btn-add-new' );

				const newCheckbox = document.createElement( 'div' );
				newCheckbox.className = 'cb-course-edit-terms__checkbox';
				newCheckbox.innerHTML = `
          <input type="checkbox" name="course_terms[]" value="${ data.data.term_id }" 
                 id="course_category_${ data.data.term_id }" checked="checked">
          <label for="course_category_${ data.data.term_id }">${ data.data.name }</label>
        `;

				wrapper.insertBefore( newCheckbox, btnAddNew );

				input.value = '';
				toggleAddTagForm();

				alert( 'Tag đã được thêm thành công!' );
			} else {
				alert( 'Lỗi: ' + ( data.message || 'Không thể thêm tag' ) );
			}
		} )
		.catch( ( error ) => {
			console.error( 'Error adding tag:', error );
			alert( 'Có lỗi xảy ra khi thêm tag!' );
		} );
};

const openMediaUploader = ( e, target ) => {
	const elBtnSetFeatured = target.closest( `${ className.elBtnSetFeatured }` );

	if ( ! elBtnSetFeatured ) {
		return;
	}

	if ( typeof wp === 'undefined' || typeof wp.media === 'undefined' ) {
		return;
	}

	let mediaUploader = wp.media( {
		title: 'Select Featured Image',
		button: {
			text: 'Use this image',
		},
		multiple: false,
		library: {
			type: 'image',
		},
	} );

	mediaUploader.on( 'select', function () {
		const attachment = mediaUploader.state().get( 'selection' ).first().toJSON();
		setFeaturedImage( attachment );
	} );

	mediaUploader.open();
};

const setFeaturedImage = ( attachment ) => {
	const previewContainer = document.querySelector( className.elFeaturedImagePreview );
	const thumbnailInput = document.querySelector( className.elThumbnailInput );
	const placeholder = previewContainer.querySelector( className.elFeatureImagePlaceholder );

	if ( ! previewContainer || ! thumbnailInput ) {
		return;
	}

	thumbnailInput.value = attachment.id;

	const imgUrl =
		attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || attachment.url;

	if ( placeholder ) {
		placeholder.remove();
	}

	const oldImg = previewContainer.querySelector( 'img' );
	if ( oldImg ) {
		oldImg.remove();
	}

	const img = document.createElement( 'img' );
	img.src = imgUrl;
	// img.alt = attachment.alt || attachment.title || 'Featured Image';
	// img.style.maxWidth = '100%';
	// img.style.height = 'auto';
	// img.style.display = 'block';

	previewContainer.appendChild( img );

	const setButton = document.querySelector( className.elBtnSetFeatured );
	if ( setButton ) {
		setButton.textContent = 'Change Featured Image';
	}
};

const removeFeaturedImage = ( e, target ) => {
	const elRemoveButton = target.closest( `${ className.elBtnRemoveFeatured }` );

	if ( ! elRemoveButton ) {
		return;
	}

	const previewContainer = document.querySelector( className.elFeaturedImagePreview );
	const thumbnailInput = document.querySelector( className.elThumbnailInput );
	const setButton = document.querySelector( className.elBtnSetFeatured );

	const img = previewContainer.querySelector( 'img' );

	if ( img ) {
		img.remove();
	}

	const placeholder = document.createElement( 'div' );
	placeholder.className = 'cb-featured-image-placeholder';
	placeholder.textContent = 'No image selected';
	previewContainer.appendChild( placeholder );

	thumbnailInput.value = '0';

	if ( setButton ) {
		setButton.textContent = 'Set Featured Image';
	}

	if ( elRemoveButton ) {
		elRemoveButton.remove();
	}
};

export {
	updateCourse,
	trashCourse,
	addNewCategory,
	addNewTag,
	openMediaUploader,
	removeFeaturedImage,
	toggleAddCategoryForm,
	toggleAddTagForm,
};
