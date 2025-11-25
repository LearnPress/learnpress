import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';

export class BuilderTabLesson {
	constructor() {
		this.init();
	}

	static selectors = {
		elLessonItem: '.lesson-item',
		elLessonExpandedItems: '.lesson-action-expanded__items',
		elLessonDuplicate: '.lesson-action-expanded__duplicate',
		elLessonTrash: '.lesson-action-expanded__trash',
		elLessonPublish: '.lesson-action-expanded__publish',
		elLessonDelete: '.lesson-action-expanded__delete',
		elLessonActionExpanded: '.lesson-action-expanded',
		elLessonStatus: '.lesson-status',
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
				selector: BuilderTabLesson.selectors.elLessonDelete,
				class: this,
				callBack: this.deleteLesson.name,
			},
			{
				selector: BuilderTabLesson.selectors.elLessonActionExpanded,
				class: this,
				callBack: this.toggleExpandedAction.name,
			},
		] );

		document.addEventListener( 'click', ( e ) => {
			if ( ! e.target.closest( BuilderTabLesson.selectors.elLessonActionExpanded ) ) {
				this.closeAllExpanded();
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

		lpUtils.lpSetLoadingEl( elLessonDuplicate, 1 );

		const lessonId = elLessonItem.dataset.lessonId || '';

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
				lpToastify.show( message, status );

				if ( data?.html ) {
					const elLesson = elLessonDuplicate.closest( '.lesson' );
					elLesson.insertAdjacentHTML( 'afterend', data.html );

					const newLesson = elLesson.nextElementSibling;
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
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elLessonDuplicate, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	trashLesson( args ) {
		const { target } = args;
		const elLessonTrash = target.closest( BuilderTabLesson.selectors.elLessonTrash );
		const elLessonItem = elLessonTrash.closest( BuilderTabLesson.selectors.elLessonItem );

		if ( ! elLessonItem ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elLessonTrash, 1 );

		const lessonId = elLessonItem.dataset.lessonId || '';

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

				if ( data?.status ) {
					const elLesson = elLessonTrash.closest( '.lesson' );
					this.updateStatusUI( elLesson, data.status );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elLessonTrash, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	publishLesson( args ) {
		const { target } = args;
		const elLessonPublish = target.closest( BuilderTabLesson.selectors.elLessonPublish );
		const elLessonItem = elLessonPublish.closest( BuilderTabLesson.selectors.elLessonItem );

		if ( ! elLessonItem ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elLessonPublish, 1 );

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
				if ( data?.status ) {
					const elLesson = elLessonPublish.closest( '.lesson' );
					this.updateStatusUI( elLesson, data.status );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elLessonPublish, 0 );
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

		const lessonId = elLessonItem.dataset.lessonId || '';

		if ( ! lessonId ) {
			return;
		}

		SweetAlert.fire( {
			title: elLessonDelete.dataset.title,
			text: elLessonDelete.dataset.content,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
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
						const elLesson = elLessonDelete.closest( '.lesson' );
						elLesson.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
						elLesson.style.opacity = '0';
						elLesson.style.transform = 'translateX(160px)';

						setTimeout( () => {
							elLesson.remove();
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
		const elLessonActionExpanded = target.closest(
			BuilderTabLesson.selectors.elLessonActionExpanded
		);
		const elLessonItem = elLessonActionExpanded.closest( BuilderTabLesson.selectors.elLessonItem );
		const elExpandedItems = elLessonItem.querySelector(
			BuilderTabLesson.selectors.elLessonExpandedItems
		);

		if ( ! elExpandedItems ) {
			return;
		}

		this.closeAllExpanded( elExpandedItems );

		elExpandedItems.classList.toggle( 'active' );
		elLessonActionExpanded.classList.toggle( 'active' );
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

			const lessonItem = item.closest( BuilderTabLesson.selectors.elLessonItem );
			const expandedBtn = lessonItem.querySelector(
				BuilderTabLesson.selectors.elLessonActionExpanded
			);
			if ( expandedBtn ) {
				expandedBtn.classList.remove( 'active' );
			}
		} );
	}

	updateStatusUI( elLesson, status ) {
		const elStatus = elLesson.querySelector( BuilderTabLesson.selectors.elLessonStatus );
		const elSpanStatus = elLesson.querySelector(
			`${ BuilderTabLesson.selectors.elLessonStatus } span`
		);
		if ( elSpanStatus && elStatus ) {
			elStatus.className = 'lesson-status ' + status;
			elSpanStatus.textContent = status;
		} else if ( elStatus ) {
			elStatus.className = 'lesson-status ' + status;
			elStatus.textContent = status;
		}
	}
}
