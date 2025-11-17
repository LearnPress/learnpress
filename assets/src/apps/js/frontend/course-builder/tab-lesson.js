import * as lpUtils from '../../utils/utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import SweetAlert from 'sweetalert2';

const className = {
	elLessonItem: '.lesson-item',
	elLessonExpandedItems: '.lesson-action-expanded__items',
	elLessonDuplicate: '.lesson-action-expanded__duplicate',
	elLessonTrash: '.lesson-action-expanded__trash',
	elLessonPublish: '.lesson-action-expanded__publish',
	elLessonDelete: '.lesson-action-expanded__delete',
	elLessonActionExpanded: '.lesson-action-expanded',
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

const lessonDuplicate = ( e, target ) => {
	const elLessonDuplicate = target.closest( className.elLessonDuplicate );

	if ( ! elLessonDuplicate ) {
		return;
	}

	const elLessonItem = elLessonDuplicate.closest( className.elLessonItem );

	if ( ! elLessonItem ) {
		return;
	}

	lpUtils.lpSetLoadingEl( elLessonDuplicate, 1 );

	const lessonId = elLessonItem.getAttribute( attributeData.lessonId ) ?? '';

	const dataSend = {
		action: 'duplicate_lesson',
		args: {
			id_url: 'duplicate-lesson',
		},
		lesson_id: lessonId ?? '',
	};

	const callBack = {
		success: ( response ) => {
			const { status, message, data } = response;
			showToast( message, status );

			if ( data?.html ) {
				const elLesson = elLessonDuplicate.closest( '.lesson' );
				elLesson.insertAdjacentHTML( 'afterend', data.html );

				const newLesson = elLesson.nextElementSibling;
				if ( newLesson ) {
					newLesson.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );

					newLesson.classList.add( 'highlight-new-lesson' );
					setTimeout( () => {
						newLesson.classList.remove( 'highlight-new-lesson' );
					}, 1500 );
				}
			}
		},
		error: ( error ) => {
			showToast( error.message || error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elLessonDuplicate, 0 );
		},
	};

	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

const lessonTrash = ( e, target ) => {
	const elLessonTrash = target.closest( className.elLessonTrash );

	if ( ! elLessonTrash ) {
		return;
	}

	const elLessonItem = elLessonTrash.closest( className.elLessonItem );

	if ( ! elLessonItem ) {
		return;
	}

	lpUtils.lpSetLoadingEl( elLessonTrash, 1 );

	const lessonId = elLessonItem.getAttribute( attributeData.lessonId ) ?? '';

	const dataSend = {
		action: 'move_trash_lesson',
		args: {
			id_url: 'move-trash-lesson',
		},
		lesson_id: lessonId ?? '',
	};

	const callBack = {
		success: ( response ) => {
			const { status, message, data } = response;
			showToast( message, status );

			if ( data?.status ) {
				const elLesson = elLessonTrash.closest( '.lesson' );
				const elStatus = elLesson.querySelector( '.lesson-status' );
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
			lpUtils.lpSetLoadingEl( elLessonTrash, 0 );
		},
	};

	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

const lessonPublish = ( e, target ) => {
	const elLessonPublish = target.closest( className.elLessonPublish );

	if ( ! elLessonPublish ) {
		return;
	}

	const elLessonItem = elLessonPublish.closest( className.elLessonItem );

	if ( ! elLessonItem ) {
		return;
	}

	lpUtils.lpSetLoadingEl( elLessonPublish, 1 );

	const lessonId = elLessonItem.getAttribute( attributeData.lessonId ) ?? '';

	const dataSend = {
		action: 'move_trash_lesson',
		args: {
			id_url: 'move-trash-lesson',
		},
		lesson_id: lessonId || 0,
		status: 'publish',
	};

	const callBack = {
		success: ( response ) => {
			const { status, message, data } = response;
			showToast( message, status );
			if ( data?.status ) {
				const elLesson = elLessonPublish.closest( '.lesson' );
				const elStatus = elLesson.querySelector( '.lesson-status' );
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
			lpUtils.lpSetLoadingEl( elLessonPublish, 0 );
		},
	};

	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

const lessonDelete = ( e, target ) => {
	const elLessonDelete = target.closest( className.elLessonDelete );

	if ( ! elLessonDelete ) {
		return;
	}

	const elLessonItem = elLessonDelete.closest( className.elLessonItem );

	if ( ! elLessonItem ) {
		return;
	}

	const lessonId = elLessonItem.getAttribute( attributeData.lessonId ) ?? '';
	SweetAlert.fire( {
		title: 'u want to delete this lesson? (Not be recoverable)',
		text: 'Ok?',
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		cancelButtonText: 'Cancel',
		confirmButtonText: 'Yes',
		reverseButtons: true,
	} ).then( ( result ) => {
		if ( result.isConfirmed ) {
			const dataSend = {
				action: 'move_trash_lesson',
				args: {
					id_url: 'move-trash-lesson',
				},
				lesson_id: lessonId ?? '',
				status: 'delete',
			};

			const callBack = {
				success: ( response ) => {
					const { status, message } = response;
					showToast( message, status );
					const elLesson = elLessonDelete.closest( '.lesson' );
					elLesson.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
					elLesson.style.opacity = '0';
					elLesson.style.transform = 'translateX(160px)';

					setTimeout( () => {
						elLesson.remove();
					}, 400 );
				},
				error: ( error ) => {
					showToast( error.message || error, 'error' );
				},
				completed: () => {},
			};

			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
	} );
};

const activeLessonExpanded = ( e, target ) => {
	const elLessonActionExpanded = target.closest( className.elLessonActionExpanded );

	if ( ! elLessonActionExpanded ) {
		const allExpandedItems = document.querySelectorAll(
			`${ className.elLessonExpandedItems }.active`
		);
		allExpandedItems.forEach( ( item ) => {
			item.classList.remove( 'active' );

			const lessonItem = item.closest( className.elLessonItem );
			const expandedBtn = lessonItem.querySelector( className.elLessonActionExpanded );
			if ( expandedBtn ) {
				expandedBtn.classList.remove( 'active' );
			}
		} );
		return;
	}

	const allExpandedItemsActive = document.querySelectorAll(
		`${ className.elLessonExpandedItems }.active`
	);

	if ( allExpandedItemsActive.length > 0 ) {
		allExpandedItemsActive.forEach( ( item ) => item.classList.remove( 'active' ) );
	}

	const elLessonItem = elLessonActionExpanded.closest( className.elLessonItem );
	const elExpandedItems = elLessonItem.querySelector( className.elLessonExpandedItems );

	if ( ! elExpandedItems ) {
		return;
	}

	elExpandedItems.classList.toggle( 'active' );
	elLessonActionExpanded.classList.toggle( 'active' );
};

export { lessonDuplicate, lessonTrash, lessonPublish, lessonDelete, activeLessonExpanded };
