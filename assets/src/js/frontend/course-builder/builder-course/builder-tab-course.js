import * as lpUtils from 'lpAssetsJsPath/utils.js';
import SweetAlert from 'sweetalert2';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

export class BuilderTabCourse {
	constructor() {
		this.init();
	}

	static selectors = {
		elCourseItem: '.course-item',
		elCourseExpandedItems: '.course-action-expanded__items',
		elCourseDuplicate: '.course-action-expanded__duplicate',
		elCourseTrash: '.course-action-expanded__trash',
		elCourseDraft: '.course-action-expanded__draft',
		elCourseDelete: '.course-action-expanded__delete',
		elCourseActionExpanded: '.course-action-expanded',
		elCourseStatus: '.course-status',
	};

	init() {
		this.events();
	}

	events() {
		if ( BuilderTabCourse._loadedEvents ) {
			return;
		}
		BuilderTabCourse._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderTabCourse.selectors.elCourseDuplicate,
				class: this,
				callBack: this.duplicateCourse.name,
			},
			{
				selector: BuilderTabCourse.selectors.elCourseTrash,
				class: this,
				callBack: this.trashCourse.name,
			},
			{
				selector: BuilderTabCourse.selectors.elCourseDraft,
				class: this,
				callBack: this.draftCourse.name,
			},
			{
				selector: BuilderTabCourse.selectors.elCourseDelete,
				class: this,
				callBack: this.deleteCourse.name,
			},
			{
				selector: BuilderTabCourse.selectors.elCourseActionExpanded,
				class: this,
				callBack: this.toggleExpandedAction.name,
			},
		] );

		document.addEventListener( 'click', ( e ) => {
			if ( ! e.target.closest( BuilderTabCourse.selectors.elCourseActionExpanded ) ) {
				this.closeAllExpanded();
			}
		} );
	}

	duplicateCourse( args ) {
		const { target } = args;
		const elCourseDuplicate = target.closest( BuilderTabCourse.selectors.elCourseDuplicate );
		const elCourseItem = elCourseDuplicate.closest( BuilderTabCourse.selectors.elCourseItem );

		if ( ! elCourseItem ) return;

		lpUtils.lpSetLoadingEl( elCourseDuplicate, 1 );

		const courseId = elCourseItem.dataset.courseId || '';

		const dataSend = {
			action: 'duplicate_course',
			args: { id_url: 'duplicate-course' },
			course_id: courseId,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

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
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elCourseDuplicate, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	trashCourse( args ) {
		const { target } = args;
		const elCourseTrash = target.closest( BuilderTabCourse.selectors.elCourseTrash );
		const elCourseItem = elCourseTrash.closest( BuilderTabCourse.selectors.elCourseItem );

		if ( ! elCourseItem ) return;

		lpUtils.lpSetLoadingEl( elCourseTrash, 1 );

		const courseId = elCourseItem.dataset.courseId || '';

		const dataSend = {
			action: 'move_trash_course',
			args: { id_url: 'move-trash-course' },
			course_id: courseId,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.status ) {
					const elCourse = elCourseTrash.closest( '.course' );
					this.updateStatusUI( elCourse, data.status );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elCourseTrash, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	draftCourse( args ) {
		const { target } = args;
		const elCourseDraft = target.closest( BuilderTabCourse.selectors.elCourseDraft );
		const elCourseItem = elCourseDraft.closest( BuilderTabCourse.selectors.elCourseItem );

		if ( ! elCourseItem ) return;

		lpUtils.lpSetLoadingEl( elCourseDraft, 1 );

		const courseId = elCourseItem.dataset.courseId || '';

		const dataSend = {
			action: 'move_trash_course',
			args: { id_url: 'move-trash-course' },
			course_id: courseId || 0,
			status: 'draft',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.status ) {
					const elCourse = elCourseDraft.closest( '.course' );
					this.updateStatusUI( elCourse, data.status );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elCourseDraft, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	deleteCourse( args ) {
		const { target } = args;
		const elCourseDelete = target.closest( BuilderTabCourse.selectors.elCourseDelete );
		const elCourseItem = elCourseDelete.closest( BuilderTabCourse.selectors.elCourseItem );

		if ( ! elCourseItem ) return;

		const courseId = elCourseItem.dataset.courseId || '';

		if ( ! courseId ) {
			return;
		}

		SweetAlert.fire( {
			title: elCourseDelete.dataset.title,
			text: elCourseDelete.dataset.content,
			iconHtml: `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_5385_4939)"><path d="M26.5983 6.2959L43.8663 36.2039C44.1297 36.66 44.2683 37.1773 44.2683 37.7039C44.2683 38.2305 44.1297 38.7478 43.8664 39.2039C43.6031 39.6599 43.2244 40.0386 42.7683 40.3019C42.3123 40.5653 41.795 40.7039 41.2683 40.7039H6.73235C6.20574 40.7039 5.68842 40.5653 5.23237 40.3019C4.77633 40.0386 4.39762 39.6599 4.13433 39.2039C3.87103 38.7478 3.73242 38.2305 3.73242 37.7039C3.73243 37.1773 3.87104 36.66 4.13435 36.2039L21.4023 6.2959C22.5563 4.2959 25.4423 4.2959 26.5983 6.2959ZM24.0003 29.9999C23.4699 29.9999 22.9612 30.2106 22.5861 30.5857C22.2111 30.9608 22.0003 31.4695 22.0003 31.9999C22.0003 32.5303 22.2111 33.039 22.5861 33.4141C22.9612 33.7892 23.4699 33.9999 24.0003 33.9999C24.5308 33.9999 25.0395 33.7892 25.4146 33.4141C25.7896 33.039 26.0003 32.5303 26.0003 31.9999C26.0003 31.4695 25.7896 30.9608 25.4146 30.5857C25.0395 30.2106 24.5308 29.9999 24.0003 29.9999ZM24.0003 15.9999C23.5105 16 23.0377 16.1798 22.6716 16.5053C22.3055 16.8308 22.0717 17.2794 22.0143 17.7659L22.0003 17.9999V25.9999C22.0009 26.5097 22.1961 27 22.546 27.3706C22.896 27.7413 23.3743 27.9644 23.8831 27.9942C24.392 28.0241 24.8931 27.8586 25.284 27.5314C25.6749 27.2042 25.9261 26.7401 25.9863 26.2339L26.0003 25.9999V17.9999C26.0003 17.4695 25.7896 16.9608 25.4146 16.5857C25.0395 16.2106 24.5308 15.9999 24.0003 15.9999Z" fill="#E31A1A"/></g><defs><clipPath id="clip0_5385_4939"><rect width="48" height="48" fill="white"/></clipPath></defs></svg>`,
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				const dataSend = {
					action: 'move_trash_course',
					args: { id_url: 'move-trash-course' },
					course_id: courseId,
					status: 'delete',
				};

				const callBack = {
					success: ( response ) => {
						const { status, message } = response;
						lpToastify.show( message, status );
						const elCourse = elCourseDelete.closest( '.course' );

						elCourse.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
						elCourse.style.opacity = '0';
						elCourse.style.transform = 'translateX(160px)';

						setTimeout( () => {
							elCourse.remove();
						}, 400 );
					},
					error: ( error ) => {
						lpToastify.show( error.message || error, 'error' );
					},
				};

				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}
		} );
	}

	toggleExpandedAction( args ) {
		const { target } = args;
		const elCourseActionExpanded = target.closest(
			BuilderTabCourse.selectors.elCourseActionExpanded
		);
		const elCourseItem = elCourseActionExpanded.closest( BuilderTabCourse.selectors.elCourseItem );
		const elExpandedItems = elCourseItem.querySelector(
			BuilderTabCourse.selectors.elCourseExpandedItems
		);

		if ( ! elExpandedItems ) return;

		// Close others
		this.closeAllExpanded( elExpandedItems );

		elExpandedItems.classList.toggle( 'active' );
		elCourseActionExpanded.classList.toggle( 'active' );
	}

	closeAllExpanded( excludeElement = null ) {
		const allExpandedItems = document.querySelectorAll(
			`${ BuilderTabCourse.selectors.elCourseExpandedItems }.active`
		);

		allExpandedItems.forEach( ( item ) => {
			if ( item === excludeElement ) return;

			item.classList.remove( 'active' );

			const courseItem = item.closest( BuilderTabCourse.selectors.elCourseItem );
			const expandedBtn = courseItem.querySelector(
				BuilderTabCourse.selectors.elCourseActionExpanded
			);
			if ( expandedBtn ) {
				expandedBtn.classList.remove( 'active' );
			}
		} );
	}

	updateStatusUI( elCourse, status ) {
		const elStatus = elCourse.querySelector( BuilderTabCourse.selectors.elCourseStatus );
		const elSpanStatus = elCourse.querySelector(
			`${ BuilderTabCourse.selectors.elCourseStatus } span`
		);
		if ( elSpanStatus && elStatus ) {
			elStatus.className = 'course-status ' + status;
			elSpanStatus.textContent = status;
		}
	}
}
