import * as lpUtils from 'lpAssetsJsPath/utils.js';
import SweetAlert from 'sweetalert2';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { SWAL_ICON_DELETE, SWAL_ICON_DUPLICATE, SWAL_ICON_TRASH_DRAFT } from '../swal-icons.js';

export class BuilderTabCourse {
	constructor() {
		this.init();
	}

	static selectors = {
		elBtnCreateCourse: 'a.cb-btn-add-new[href]',
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
			{
				selector: BuilderTabCourse.selectors.elBtnCreateCourse,
				callBack: ( args ) => {
					const { target } = args;
					const elBtnCreateCourse = target.closest(
						BuilderTabCourse.selectors.elBtnCreateCourse
					);

					if ( ! elBtnCreateCourse ) {
						return;
					}

					lpUtils.lpSetLoadingEl( elBtnCreateCourse, 1 );
				},
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

		if ( ! elCourseItem ) {
			return;
		}

		const courseId = elCourseItem.dataset.courseId || '';

		if ( ! courseId ) {
			return;
		}

		SweetAlert.fire( {
			title: elCourseDuplicate.dataset.title || 'Duplicate Course',
			text: elCourseDuplicate.dataset.content || 'Are you sure you want to duplicate this course?',
			iconHtml: SWAL_ICON_DUPLICATE,
			customClass: { icon: 'lp-cb-swal-icon-html' },
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				lpUtils.lpSetLoadingEl( elCourseDuplicate, 1 );

				const dataSend = {
					action: 'duplicate_course',
					args: { id_url: 'duplicate-course' },
					course_id: courseId,
				};

				const callBack = {
					success: ( response ) => {
						const { status, message, data } = response;
						lpToastify.show( message || 'Duplicated successfully!', status );

						if ( data?.html ) {
							const elCourse = elCourseDuplicate.closest( '.course' );
							const elCourseList = elCourse.closest( '.courses-list' ) || elCourse.parentElement;

							if ( elCourseList ) {
								elCourseList.insertAdjacentHTML( 'afterbegin', data.html );
								const newCourse = elCourseList.firstElementChild;

								if ( newCourse ) {
									newCourse.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
									newCourse.classList.add( 'highlight-new-course' );
									setTimeout( () => {
										newCourse.classList.remove( 'highlight-new-course' );
									}, 1500 );
								}
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
		} );
	}

	trashCourse( args ) {
		const { target } = args;
		const elCourseTrash = target.closest( BuilderTabCourse.selectors.elCourseTrash );
		const elCourseItem = elCourseTrash.closest( BuilderTabCourse.selectors.elCourseItem );

		if ( ! elCourseItem ) {
			return;
		}

		const courseId = elCourseItem.dataset.courseId || '';

		SweetAlert.fire( {
			title: elCourseTrash.dataset.title || 'Trash Course',
			text: elCourseTrash.dataset.content || 'Are you sure you want to move this course to trash?',
			iconHtml: SWAL_ICON_TRASH_DRAFT,
			customClass: { icon: 'lp-cb-swal-icon-html' },
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				lpUtils.lpSetLoadingEl( elCourseTrash, 1 );

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
		} );
	}

	draftCourse( args ) {
		const { target } = args;
		const elCourseDraft = target.closest( BuilderTabCourse.selectors.elCourseDraft );
		const elCourseItem = elCourseDraft.closest( BuilderTabCourse.selectors.elCourseItem );

		if ( ! elCourseItem ) {
			return;
		}

		// Check if published to show confirm unpublish modal
		const statusEl = elCourseItem.querySelector( BuilderTabCourse.selectors.elCourseStatus );
		const isPublished = statusEl && statusEl.classList.contains( 'publish' );
		if ( isPublished ) {
			const confirmMsg =
				elCourseDraft.dataset.confirmUnpublish ||
				'Saving as draft will unpublish this item. Are you sure?';

			SweetAlert.fire( {
				title: 'Draft Course',
				text: confirmMsg,
				iconHtml: SWAL_ICON_TRASH_DRAFT,
				customClass: { icon: 'lp-cb-swal-icon-html' },
				showCloseButton: true,
				showCancelButton: true,
				cancelButtonText: lpData.i18n.cancel,
				confirmButtonText: lpData.i18n.yes,
				reverseButtons: true,
			} ).then( ( result ) => {
				if ( result.isConfirmed ) {
					this.draftCourseExecute( elCourseDraft, elCourseItem );
				}
			} );
			return;
		}

		this.draftCourseExecute( elCourseDraft, elCourseItem );
	}

	draftCourseExecute( elCourseDraft, elCourseItem ) {
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

		if ( ! elCourseItem ) {
			return;
		}

		const courseId = elCourseItem.dataset.courseId || '';

		if ( ! courseId ) {
			return;
		}

		SweetAlert.fire( {
			title: elCourseDelete.dataset.title || 'Delete Course',
			text:
				elCourseDelete.dataset.content ||
				'Are you sure you want to permanently delete this course?',
			iconHtml: SWAL_ICON_DELETE,
			customClass: { icon: 'lp-cb-swal-icon-html' },
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

		if ( ! elCourseActionExpanded ) {
			return;
		}

		const elCourseItem = elCourseActionExpanded.closest( BuilderTabCourse.selectors.elCourseItem );

		if ( ! elCourseItem ) {
			return;
		}

		const elExpandedItems = elCourseItem.querySelector(
			BuilderTabCourse.selectors.elCourseExpandedItems
		);

		if ( ! elExpandedItems ) {
			return;
		}

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
			if ( item === excludeElement ) {
				return;
			}

			item.classList.remove( 'active' );

			const courseItem = item.closest( BuilderTabCourse.selectors.elCourseItem );

			if ( ! courseItem ) {
				return;
			}

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
