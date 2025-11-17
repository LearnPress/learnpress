import * as lpUtils from '../../utils/utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

const className = {
	elDataLesson: '.cb-section__lesson-overview',
	elBtnUpdateLesson: '.cb-btn-update__lesson',
	elBtnPublishLesson: '.cb-btn-publish__lesson',
	elBtnTrashLesson: '.cb-btn-trash__lesson',
};

const attributeData = {
	lessonId: 'data-lesson-id',
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

const getLessonDataForUpdate = () => {
	const data = {};

	const wrapperEl = document.querySelector( className.elDataLesson );
	if ( wrapperEl ) {
		data.lesson_id = parseInt( wrapperEl.getAttribute( attributeData.lessonId ) ) || 0;
	} else {
		data.lesson_id = 0;
	}

	const titleInput = document.getElementById( 'title' );
	if ( titleInput ) {
		data.lesson_title = titleInput.value;
	} else {
		data.lesson_title = '';
	}

	const descEditor = document.getElementById( 'lesson_description_editor' );
	if ( descEditor ) {
		data.lesson_description = descEditor.value;
	} else {
		console.warn( 'Description editor not found' );
		data.lesson_description = '';
	}

	if ( typeof tinymce !== 'undefined' ) {
		const editor = tinymce.get( 'lesson_description_editor' );
		if ( editor ) {
			data.lesson_description = editor.getContent();
		}
	}

	return data;
};

const updateLesson = ( e, target ) => {
	const elBtnUpdateLesson = target.closest( `${ className.elBtnUpdateLesson }` );

	if ( ! elBtnUpdateLesson ) {
		return;
	}

	const status = 'publish';
	const elBtn = elBtnUpdateLesson;

	lpUtils.lpSetLoadingEl( elBtn, 1 );

	const lessonData = getLessonDataForUpdate();

	const dataSend = {
		action: 'update_lesson',
		args: {
			id_url: 'update-lesson',
		},
		lesson_title: lessonData.lesson_title || '',
		lesson_description: lessonData.lesson_description || '',
		lesson_id: lessonData.lesson_id || 0,
		lesson_status: status,
	};

	if ( typeof lpLessonBuilder !== 'undefined' && lpLessonBuilder.nonce ) {
		dataSend.nonce = lpLessonBuilder.nonce;
	}

	if ( lessonData.lesson_categories && lessonData.lesson_categories.length > 0 ) {
		dataSend.lesson_categories = lessonData.lesson_categories.join( ',' );
	}

	if ( lessonData.lesson_tags && lessonData.lesson_tags.length > 0 ) {
		dataSend.lesson_tags = lessonData.lesson_tags.join( ',' );
	}

	if ( lessonData.lesson_thumbnail_id ) {
		dataSend.lesson_thumbnail_id = lessonData.lesson_thumbnail_id;
	}

	const callBack = {
		success: ( response ) => {
			const { status, message, data } = response;
			showToast( message, status );

			if ( data?.button_title ) {
				if ( elBtnUpdateLesson ) {
					elBtnUpdateLesson.textContent = data?.button_title;
				} else {
					const elBtnUpdate = document.querySelector( className.elBtnUpdateLesson );
					elBtnUpdate.textContent = data?.button_title;
				}
			}

			if ( data?.lesson_id_new ) {
				const currentUrl = window.location.href;
				const newUrl = currentUrl.replace( /post-new\/?/, `${ data.lesson_id_new }/` );
				window.location.href = newUrl;
			}

			if ( data?.status ) {
				const elStatus = document.querySelector( '.lesson-status' );
				if ( elStatus ) {
					elStatus.className = 'lesson-status ' + data.status;
					elStatus.textContent = data.status;
				}
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

const trashLesson = ( e, target ) => {
	const elBtnTrashLesson = target.closest( `${ className.elBtnTrashLesson }` );
	if ( ! elBtnTrashLesson ) {
		return;
	}

	lpUtils.lpSetLoadingEl( elBtnTrashLesson, 1 );

	const lessonData = getLessonDataForUpdate();
	const dataSend = {
		action: 'move_trash_lesson',
		args: {
			id_url: 'move-trash-lesson',
		},
		lesson_id: lessonData.lesson_id || 0,
	};

	const callBack = {
		success: ( response ) => {
			const { status, message, data } = response;
			showToast( message, status );
			if ( data?.status ) {
				const elStatus = document.querySelector( '.lesson-status' );
				if ( elStatus ) {
					elStatus.className = 'lesson-status ' + data.status;
					elStatus.textContent = data.status;
				}
			}
		},
		error: ( error ) => {
			showToast( error.message || error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elBtnTrashLesson, 0 );
		},
	};

	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

export { updateLesson, trashLesson };
