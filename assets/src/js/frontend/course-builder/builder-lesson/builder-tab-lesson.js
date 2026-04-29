import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';
import { SWAL_ICON_DELETE, SWAL_ICON_DUPLICATE, SWAL_ICON_TRASH_DRAFT } from '../swal-icons.js';

export class BuilderTabLesson {
	constructor() {
		this.init();
	}

	static selectors = {
		elLessonItem: '.lesson-item',
		elLessonExpandedItems: '.lesson-action-expanded__items',
		elLessonDuplicate: '.lesson-action-expanded__duplicate',
		elLessonTrash: '.lesson-action-expanded__trash',
		elLessonRestore: '.lesson-action-expanded__restore',
		elLessonPublish: '.lesson-action-expanded__publish',
		elLessonDelete: '.lesson-action-expanded__delete',
		elLessonActionExpanded: '.lesson-action-expanded',
		elLessonStatus: '.lesson-status',
		elLessonPreview: '.lesson__preview.lp-btn-set-preview-item',
	};

	init() {
		this.events();
	}

	events() {
		if ( BuilderTabLesson._loadedEvents ) {
			return;
		}
		BuilderTabLesson._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderTabLesson.selectors.elLessonDuplicate,
				class: this,
				callBack: this.duplicateLesson.name,
			},
			{
				selector: BuilderTabLesson.selectors.elLessonTrash,
				class: this,
				callBack: this.trashLesson.name,
			},
			{
				selector: BuilderTabLesson.selectors.elLessonPublish,
				class: this,
				callBack: this.publishLesson.name,
			},
			{
				selector: BuilderTabLesson.selectors.elLessonRestore,
				class: this,
				callBack: this.restoreLesson.name,
			},
			{
				selector: BuilderTabLesson.selectors.elLessonDelete,
				class: this,
				callBack: this.deleteLesson.name,
			},
			{
				selector: BuilderTabLesson.selectors.elLessonActionExpanded,
				class: this,
				callBack: this.toggleExpandedAction.name,
			},
			{
				selector: BuilderTabLesson.selectors.elLessonPreview,
				class: this,
				callBack: this.toggleLessonPreview.name,
			},
		] );

		document.addEventListener( 'click', ( e ) => {
			if ( ! e.target.closest( BuilderTabLesson.selectors.elLessonActionExpanded ) ) {
				this.closeAllExpanded();
			}
		} );

		document.addEventListener( 'lp-builder-popup-saved', ( e ) => {
			const { type, id, data } = e.detail;
			if ( type !== 'lesson' || ! data?.list_item_html ) return;
			const elItem = document.querySelector( `.lesson-item[data-lesson-id="${ id }"]` );
			if ( elItem ) {
				this.replaceItemHtml( elItem.closest( '.lesson' ), data.list_item_html );
			}
		} );

		document.addEventListener( 'lp-builder-popup-trashed', ( e ) => {
			const { type, id, data } = e.detail;
			if ( type !== 'lesson' || ! data?.html ) return;
			const elItem = document.querySelector( `.lesson-item[data-lesson-id="${ id }"]` );
			if ( elItem ) {
				this.replaceItemHtml( elItem.closest( '.lesson' ), data.html );
			}
		} );
	}

	duplicateLesson( args ) {
		const { target } = args;
		const elLessonDuplicate = target.closest( BuilderTabLesson.selectors.elLessonDuplicate );
		const elLessonItem = elLessonDuplicate.closest( BuilderTabLesson.selectors.elLessonItem );

		if ( ! elLessonItem ) {
			return;
		}

		const elActionExpanded = elLessonItem.querySelector(
			BuilderTabLesson.selectors.elLessonActionExpanded
		);
		const lessonId = elLessonItem.dataset.lessonId || '';

		SweetAlert.fire( {
			title: elLessonDuplicate.dataset.title || 'Duplicate Lesson',
			text: elLessonDuplicate.dataset.content || 'Are you sure you want to duplicate this lesson?',
			iconHtml: SWAL_ICON_DUPLICATE,
			customClass: { icon: 'lp-cb-swal-icon-html' },
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

				const dataSend = {
					action: 'duplicate_lesson',
					args: {
						id_url: 'duplicate-lesson',
					},
					lesson_id: lessonId,
				};

				const callBack = {
					success: ( response ) => {
						const { status, message, data } = response;
						lpToastify.show( message || 'Duplicated successfully!', status );

						if ( data?.html ) {
							const elLesson = elLessonDuplicate.closest( '.lesson' );
							const elLessonList = elLesson.closest( '.lessons-list' ) || elLesson.parentElement;

							if ( elLessonList ) {
								elLessonList.insertAdjacentHTML( 'afterbegin', data.html );
								const newLesson = elLessonList.firstElementChild;

								if ( newLesson ) {
									newLesson.scrollIntoView( {
										behavior: 'smooth',
										block: 'nearest',
									} );
									newLesson.classList.add( 'highlight-new-lesson' );
									setTimeout( () => {
										newLesson.classList.remove( 'highlight-new-lesson' );
									}, 1500 );
								}
							}
						}
					},
					error: ( error ) => {
						lpToastify.show( error.message || error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elActionExpanded, 0 );
					},
				};

				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}
		} );
	}

	trashLesson( args ) {
		const { target } = args;
		const elLessonTrash = target.closest( BuilderTabLesson.selectors.elLessonTrash );
		const elLessonItem = elLessonTrash.closest( BuilderTabLesson.selectors.elLessonItem );

		if ( ! elLessonItem ) {
			return;
		}

		const elActionExpanded = elLessonItem.querySelector(
			BuilderTabLesson.selectors.elLessonActionExpanded
		);
		const lessonId = elLessonItem.dataset.lessonId || '';

		SweetAlert.fire( {
			title: elLessonTrash.dataset.title || 'Trash Lesson',
			text:
				elLessonTrash.dataset.content ||
				'Are you sure? Moving it to the trash will cause this item to be removed from the course.',
			iconHtml: SWAL_ICON_TRASH_DRAFT,
			customClass: { icon: 'lp-cb-swal-icon-html' },
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

				const dataSend = {
					action: 'move_trash_lesson',
					args: {
						id_url: 'move-trash-lesson',
					},
					lesson_id: lessonId,
				};

				const callBack = {
					success: ( response ) => {
						const { status, message, data } = response;
						lpToastify.show( message, status );

						if ( data?.html ) {
							this.replaceItemHtml( elLessonTrash.closest( '.lesson' ), data.html );
						}
					},
					error: ( error ) => {
						lpToastify.show( error.message || error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elActionExpanded, 0 );
					},
				};

				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}
		} );
	}

	publishLesson( args ) {
		const { target } = args;
		const elLessonPublish = target.closest( BuilderTabLesson.selectors.elLessonPublish );
		const elLessonItem = elLessonPublish.closest( BuilderTabLesson.selectors.elLessonItem );

		if ( ! elLessonItem ) {
			return;
		}

		const elActionExpanded = elLessonItem.querySelector(
			BuilderTabLesson.selectors.elLessonActionExpanded
		);
		lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

		const lessonId = elLessonItem.dataset.lessonId || '';

		const dataSend = {
			action: 'move_trash_lesson',
			args: {
				id_url: 'move-trash-lesson',
			},
			lesson_id: lessonId,
			status: 'publish',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );
				if ( data?.html ) {
					this.replaceItemHtml( elLessonPublish.closest( '.lesson' ), data.html );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elActionExpanded, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	restoreLesson( args ) {
		const { target } = args;
		const elLessonRestore = target.closest( BuilderTabLesson.selectors.elLessonRestore );
		const elLessonItem = elLessonRestore.closest( BuilderTabLesson.selectors.elLessonItem );

		if ( ! elLessonItem ) {
			return;
		}

		const elActionExpanded = elLessonItem.querySelector(
			BuilderTabLesson.selectors.elLessonActionExpanded
		);
		lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

		const lessonId = elLessonItem.dataset.lessonId || '';

		const dataSend = {
			action: 'move_trash_lesson',
			args: {
				id_url: 'move-trash-lesson',
			},
			lesson_id: lessonId,
			status: 'draft',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );
				if ( data?.html ) {
					this.replaceItemHtml( elLessonRestore.closest( '.lesson' ), data.html );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elActionExpanded, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	deleteLesson( args ) {
		const { target } = args;
		const elLessonDelete = target.closest( BuilderTabLesson.selectors.elLessonDelete );
		const elLessonItem = elLessonDelete.closest( BuilderTabLesson.selectors.elLessonItem );

		if ( ! elLessonItem ) {
			return;
		}

		const elActionExpanded = elLessonItem.querySelector(
			BuilderTabLesson.selectors.elLessonActionExpanded
		);
		const lessonId = elLessonItem.dataset.lessonId || '';

		if ( ! lessonId ) {
			return;
		}

		SweetAlert.fire( {
			title: elLessonDelete.dataset.title || 'Delete Lesson',
			text:
				elLessonDelete.dataset.content ||
				'Are you sure you want to permanently delete this lesson?',
			iconHtml: SWAL_ICON_DELETE,
			customClass: { icon: 'lp-cb-swal-icon-html' },
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

				const dataSend = {
					action: 'move_trash_lesson',
					args: {
						id_url: 'move-trash-lesson',
					},
					lesson_id: lessonId,
					status: 'delete',
				};

				const callBack = {
					success: ( response ) => {
						const { status, message } = response;
						lpToastify.show( message, status );

						if ( status === 'success' ) {
							const elLesson = elLessonDelete.closest( '.lesson' );
							elLesson.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
							elLesson.style.opacity = '0';
							elLesson.style.transform = 'translateX(160px)';

							setTimeout( () => {
								elLesson.remove();
							}, 400 );
						}
					},
					error: ( error ) => {
						lpToastify.show( error.message || error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elActionExpanded, 0 );
					},
				};

				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}
		} );
	}

	toggleExpandedAction( args ) {
		const { target } = args;
		const elLessonActionExpanded = target.closest(
			BuilderTabLesson.selectors.elLessonActionExpanded
		);

		if ( ! elLessonActionExpanded ) {
			return;
		}

		const elLessonItem = elLessonActionExpanded.closest( BuilderTabLesson.selectors.elLessonItem );

		if ( ! elLessonItem ) {
			return;
		}

		const elExpandedItems = elLessonItem.querySelector(
			BuilderTabLesson.selectors.elLessonExpandedItems
		);

		if ( ! elExpandedItems ) {
			return;
		}

		this.closeAllExpanded( elExpandedItems );
		const willOpen = ! elExpandedItems.classList.contains( 'active' );

		if ( willOpen ) {
			elExpandedItems.classList.add( 'active' );
			elLessonActionExpanded.classList.add( 'active' );
			this.setExpandedDirection( elExpandedItems );
		} else {
			elExpandedItems.classList.remove( 'active' );
			elExpandedItems.classList.remove( 'is-dropup' );
			elLessonActionExpanded.classList.remove( 'active' );
		}
	}

	closeAllExpanded( excludeElement = null ) {
		const allExpandedItems = document.querySelectorAll(
			`${ BuilderTabLesson.selectors.elLessonExpandedItems }.active`
		);

		allExpandedItems.forEach( ( item ) => {
			if ( item === excludeElement ) {
				return;
			}

			item.classList.remove( 'active' );
			item.classList.remove( 'is-dropup' );

			const lessonItem = item.closest( BuilderTabLesson.selectors.elLessonItem );

			if ( ! lessonItem ) {
				return;
			}

			const expandedBtn = lessonItem.querySelector(
				BuilderTabLesson.selectors.elLessonActionExpanded
			);
			if ( expandedBtn ) {
				expandedBtn.classList.remove( 'active' );
			}
		} );
	}

	setExpandedDirection( elExpandedItems ) {
		if ( ! elExpandedItems ) {
			return;
		}

		elExpandedItems.classList.remove( 'is-dropup' );

		const elLesson = elExpandedItems.closest( '.lesson' );
		if ( ! elLesson ) {
			return;
		}

		if ( elLesson.matches( ':last-child' ) ) {
			elExpandedItems.classList.add( 'is-dropup' );
		}
	}

	/* Toggle preview for a lesson in the lesson list */
	toggleLessonPreview( args ) {
		const { target } = args;
		const elPreviewBtn = target.closest( BuilderTabLesson.selectors.elLessonPreview );
		if ( ! elPreviewBtn ) {
			return;
		}
		if (
			elPreviewBtn.classList.contains( 'loading' ) ||
			elPreviewBtn.classList.contains( 'lp-loading' )
		) {
			return;
		}

		const elLessonItem = elPreviewBtn.closest( BuilderTabLesson.selectors.elLessonItem );
		if ( ! elLessonItem ) {
			return;
		}

		const icon = elPreviewBtn.querySelector( 'a' );
		if ( ! icon ) {
			return;
		}

		const lessonId = elLessonItem.dataset.lessonId || elPreviewBtn.dataset.id || '';
		if ( ! lessonId ) {
			return;
		}

		// Optimistic toggle
		icon.classList.toggle( 'lp-icon-eye' );
		icon.classList.toggle( 'lp-icon-eye-slash' );

		const enablePreview = icon.classList.contains( 'lp-icon-eye' );

		lpUtils.lpSetLoadingEl( elPreviewBtn, 1 );
		elPreviewBtn.classList.add( 'lp-loading' );

		const dataSend = {
			action: 'builder_update_lesson',
			args: { id_url: 'builder-update-lesson' },
			lesson_id: lessonId,
			lesson_settings: true,
			_lp_preview: enablePreview ? 'yes' : '',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message } = response;
				lpToastify.show( message, status );

				if ( status === 'error' ) {
					// Revert on error
					icon.classList.toggle( 'lp-icon-eye' );
					icon.classList.toggle( 'lp-icon-eye-slash' );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
				icon.classList.toggle( 'lp-icon-eye' );
				icon.classList.toggle( 'lp-icon-eye-slash' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elPreviewBtn, 0 );
				elPreviewBtn.classList.remove( 'lp-loading' );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	replaceItemHtml( elLesson, html ) {
		if ( ! elLesson || ! html ) {
			return;
		}
		const tmp = document.createElement( 'div' );
		tmp.innerHTML = html;
		const newEl = tmp.firstElementChild;
		if ( newEl ) {
			elLesson.replaceWith( newEl );
		}
	}
}
