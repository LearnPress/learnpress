import * as lpUtils from '../../utils/utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

const className = {
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

	data.course_tags = [];
	const tagCheckboxes = document.querySelectorAll( 'input[name="course_tags[]"]:checked' );
	tagCheckboxes.forEach( ( checkbox ) => {
		data.course_tags.push( checkbox.value );
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

	if ( courseData.course_tags && courseData.course_tags.length > 0 ) {
		dataSend.course_tags = courseData.course_tags.join( ',' );
	}

	if ( courseData.course_thumbnail_id ) {
		dataSend.course_thumbnail_id = courseData.course_thumbnail_id;
	}

	const callBack = {
		success: ( response ) => {
			const { status, message, data } = response;
			showToast( message, status );

			if ( data?.course_id_new ) {
				const currentUrl = window.location.href;
				const newUrl = currentUrl.replace( /post-new\/?/, `${ data.course_id_new }/` );
				window.location.href = newUrl;
			}
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
	let elBtnAddCategoryNew = target.closest( `${ className.elBtnAddCategoryNew }` );
	let elBtnCancelCategoryNew = target.closest( `${ className.elBtnCancelCategoryNew }` );
	if ( ! elBtnAddCategoryNew && ! elBtnCancelCategoryNew ) {
		return;
	}

	const isVisible = elBtnAddCategoryNew ? true : false;

	if ( ! elBtnAddCategoryNew ) {
		elBtnAddCategoryNew = document.querySelector( className.elBtnAddCategoryNew );
	}

	if ( ! elBtnCancelCategoryNew ) {
		elBtnCancelCategoryNew = document.querySelector( className.elBtnCancelCategoryNew );
	}

	const form = document.querySelector( className.elFormCategoryAddNew );

	if ( form ) {
		if ( ! isVisible ) {
			form.style.display = 'none';
			elBtnCancelCategoryNew.style.display = 'none';
			elBtnAddCategoryNew.style.display = 'inline-block';
		} else {
			elBtnAddCategoryNew.style.display = 'none';
			elBtnCancelCategoryNew.style.display = 'inline-block';
			form.style.display = 'flex';
			const input = form.querySelector( '.cb-course-edit-category__input' );
			if ( input ) {
				setTimeout( () => input.focus(), 100 );
			}
		}
	}
};

const addNewCategory = ( e, target ) => {
	if ( ! target && e ) {
		target = e.target;
	}

	let canHandle = false;

	let elBtnSaveCategory = target.closest( className.elBtnSaveCategory );
	let elInputCategory = target.closest( className.elInputCategory );

	if ( elBtnSaveCategory ) {
		canHandle = true;
	} else if ( elInputCategory && e.key === 'Enter' ) {
		canHandle = true;
	}

	if ( ! canHandle ) {
		return;
	}

	if ( ! elBtnSaveCategory ) {
		elBtnSaveCategory = document.querySelector( className.elBtnSaveCategory );
	}

	if ( ! elInputCategory ) {
		elInputCategory = document.querySelector( className.elInputCategory );
	}

	if ( ! elBtnSaveCategory && ! elInputCategory ) {
		return;
	}

	lpUtils.lpSetLoadingEl( elBtnSaveCategory, 1 );

	const categoryName = elInputCategory.value?.trim() ?? '';

	const dataSend = {
		action: 'add_course_category',
		args: {
			id_url: 'add-course-category',
		},
		name: categoryName ?? '',
	};

	const callBack = {
		success: ( response ) => {
			const { status, message, data } = response;
			showToast( message, status );

			if ( data?.html ) {
				const wrapper = document.querySelector( className.elWrapperCheckBoxCategory );
				wrapper.insertAdjacentHTML( 'beforeend', data.html );
				elInputCategory.value = '';
				const elBtnCancelCategoryNew = document.querySelector( className.elBtnCancelCategoryNew );
				toggleAddCategoryForm( e, elBtnCancelCategoryNew );
			}
		},
		error: ( error ) => {
			showToast( error.message || error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elBtnSaveCategory, 0 );
		},
	};

	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

const toggleAddTagForm = ( e, target ) => {
	let elBtnAddTagNew = target.closest( `${ className.elBtnAddTagNew }` );
	let elBtnCancelTagNew = target.closest( `${ className.elBtnCancelTagNew }` );
	if ( ! elBtnAddTagNew && ! elBtnCancelTagNew ) {
		return;
	}

	const isVisible = elBtnAddTagNew ? true : false;

	if ( ! elBtnAddTagNew ) {
		elBtnAddTagNew = document.querySelector( className.elBtnAddTagNew );
	}

	if ( ! elBtnCancelTagNew ) {
		elBtnCancelTagNew = document.querySelector( className.elBtnCancelTagNew );
	}

	const form = document.querySelector( className.elFormTagAddNew );

	if ( form ) {
		if ( isVisible ) {
			form.style.display = 'flex';
			elBtnAddTagNew.style.display = 'none';
			elBtnCancelTagNew.style.display = 'inline-block';
			const input = form.querySelector( className.elInputAddTag );
			if ( input ) {
				setTimeout( () => input.focus(), 100 );
			}
		} else {
			form.style.display = 'none';
			elBtnCancelTagNew.style.display = 'none';
			elBtnAddTagNew.style.display = 'inline-block';
		}
	}
};

const addNewTag = ( e, target ) => {
	if ( ! target && e ) {
		target = e.target;
	}

	let canHandle = false;

	let elBtnSaveTag = target.closest( className.elBtnSaveTag );
	let elInputAddTag = target.closest( className.elInputAddTag );

	if ( elBtnSaveTag ) {
		canHandle = true;
	} else if ( elInputAddTag && e.key === 'Enter' ) {
		canHandle = true;
	}

	if ( ! canHandle ) {
		return;
	}

	if ( ! elBtnSaveTag ) {
		elBtnSaveTag = document.querySelector( className.elBtnSaveTag );
	}

	if ( ! elInputAddTag ) {
		elInputAddTag = document.querySelector( className.elInputAddTag );
	}

	if ( ! elBtnSaveTag && ! elInputAddTag ) {
		return;
	}

	lpUtils.lpSetLoadingEl( elBtnSaveTag, 1 );

	const tagName = elInputAddTag.value?.trim() ?? '';

	const dataSend = {
		action: 'add_course_tag',
		args: {
			id_url: 'add-course-tag',
		},
		name: tagName ?? '',
	};

	const callBack = {
		success: ( response ) => {
			const { status, message, data } = response;
			showToast( message, status );
			if ( data?.html ) {
				const wrapper = document.querySelector( className.elWrapperCheckBoxTag );
				wrapper.insertAdjacentHTML( 'beforeend', data.html );
				elInputAddTag.value = '';
				const elBtnCancelTagNew = document.querySelector( className.elBtnCancelTagNew );
				toggleAddTagForm( e, elBtnCancelTagNew );
			}
		},
		error: ( error ) => {
			showToast( error.message || error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elBtnSaveTag, 0 );
		},
	};

	window.lpAJAXG.fetchAJAX( dataSend, callBack );
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
	previewContainer.appendChild( img );

	const elRemoveButton = document.querySelector( className.elBtnRemoveFeatured );
	if ( elRemoveButton ) {
		elRemoveButton.style.display = 'inline-block';
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

	if ( elRemoveButton ) {
		elRemoveButton.style.display = 'none';
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
