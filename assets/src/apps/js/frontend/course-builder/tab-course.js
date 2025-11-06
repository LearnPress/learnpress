import * as lpUtils from '../../utils/utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import SweetAlert from 'sweetalert2';

const className = {
	elCourseItem: '.course-item',
	elCourseExpandedItems: '.course-action-expanded__items',
	elCourseDuplicate: '.course-action-expanded__duplicate',
	elCourseDelete: '.course-action-expanded__delete',
	elCourseActionExpanded: '.course-action-expanded',
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

const courseDuplicate = ( e, target ) => {
	const elCourseDuplicate = target.closest( className.elCourseDuplicate );

	if ( ! elCourseDuplicate ) {
		return;
	}

	const elCourseItem = elCourseDuplicate.closest( className.elCourseItem );

	if ( ! elCourseItem ) {
		return;
	}

	lpUtils.lpSetLoadingEl( elCourseDuplicate, 1 );

	const courseId = elCourseItem.getAttribute( attributeData.courseId ) ?? '';

	const dataSend = {
		action: 'duplicate_course',
		args: {
			id_url: 'duplicate-course',
		},
		course_id: courseId ?? '',
	};

	const callBack = {
		success: ( response ) => {
			const { status, message, data } = response;
			showToast( message, status );

			if ( data?.html ) {
				const elCourse = elCourseDuplicate.closest( '.course' );
				elCourse.insertAdjacentHTML( 'afterend', data.html );

				const newCourse = elCourse.nextElementSibling;
				if ( newCourse ) {
					newCourse.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );

					newCourse.classList.add( 'highlight-new-course' );
					setTimeout( () => {
						newCourse.classList.remove( 'highlight-new-course' );
					}, 1500 );
				}
			}
		},
		error: ( error ) => {
			showToast( error.message || error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elCourseDuplicate, 0 );
		},
	};

	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

const courseDelete = ( e, target ) => {
	const elCourseDelete = target.closest( className.elCourseDelete );

	if ( ! elCourseDelete ) {
		return;
	}

	const elCourseItem = elCourseDelete.closest( className.elCourseItem );

	if ( ! elCourseItem ) {
		return;
	}

	const courseId = elCourseItem.getAttribute( attributeData.courseId ) ?? '';
	SweetAlert.fire( {
		title: 'u want to delete this course? (Not be recoverable)',
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
				action: 'move_trash_course',
				args: {
					id_url: 'move-trash-course',
				},
				course_id: courseId ?? '',
				status: 'delete',
			};

			const callBack = {
				success: ( response ) => {
					const { status, message } = response;
					showToast( message, status );
					const elCourse = elCourseDelete.closest( '.course' );
					elCourse.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
					elCourse.style.opacity = '0';
					elCourse.style.transform = 'translateX(160px)';

					setTimeout( () => {
						elCourse.remove();
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

const activeCourseExpanded = ( e, target ) => {
	const elCourseActionExpanded = target.closest( className.elCourseActionExpanded );

	if ( ! elCourseActionExpanded ) {
		const allExpandedItems = document.querySelectorAll(
			`${ className.elCourseExpandedItems }.active`
		);
		allExpandedItems.forEach( ( item ) => {
			item.classList.remove( 'active' );

			const courseItem = item.closest( className.elCourseItem );
			const expandedBtn = courseItem.querySelector( className.elCourseActionExpanded );
			if ( expandedBtn ) {
				expandedBtn.classList.remove( 'active' );
			}
		} );
		return;
	}

	const allExpandedItemsActive = document.querySelectorAll(
		`${ className.elCourseExpandedItems }.active`
	);

	if ( allExpandedItemsActive.length > 0 ) {
		allExpandedItemsActive.forEach( ( item ) => item.classList.remove( 'active' ) );
	}

	const elCourseItem = elCourseActionExpanded.closest( className.elCourseItem );
	const elExpandedItems = elCourseItem.querySelector( className.elCourseExpandedItems );

	if ( ! elExpandedItems ) {
		return;
	}

	elExpandedItems.classList.toggle( 'active' );
	elCourseActionExpanded.classList.toggle( 'active' );
};

export { courseDuplicate, courseDelete, activeCourseExpanded };
