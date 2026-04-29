import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';
import { SWAL_ICON_DELETE, SWAL_ICON_DUPLICATE, SWAL_ICON_TRASH_DRAFT } from '../swal-icons.js';

export class BuilderTabQuiz {
	constructor() {
		this.init();
	}

	static selectors = {
		elQuizItem: '.quiz-item',
		elQuizExpandedItems: '.quiz-action-expanded__items',
		elQuizDuplicate: '.quiz-action-expanded__duplicate',
		elQuizTrash: '.quiz-action-expanded__trash',
		elQuizRestore: '.quiz-action-expanded__restore',
		elQuizPublish: '.quiz-action-expanded__publish',
		elQuizDelete: '.quiz-action-expanded__delete',
		elQuizActionExpanded: '.quiz-action-expanded',
		elQuizStatus: '.quiz-status',
		elBtnEditQuiz: '.btn-edit-quiz',
	};

	init() {
		this.events();
	}

	events() {
		if ( BuilderTabQuiz._loadedEvents ) {
			return;
		}
		BuilderTabQuiz._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderTabQuiz.selectors.elQuizDuplicate,
				class: this,
				callBack: this.duplicateQuiz.name,
			},
			{
				selector: BuilderTabQuiz.selectors.elQuizTrash,
				class: this,
				callBack: this.trashQuiz.name,
			},
			{
				selector: BuilderTabQuiz.selectors.elQuizPublish,
				class: this,
				callBack: this.publishQuiz.name,
			},
			{
				selector: BuilderTabQuiz.selectors.elQuizRestore,
				class: this,
				callBack: this.restoreQuiz.name,
			},
			{
				selector: BuilderTabQuiz.selectors.elQuizDelete,
				class: this,
				callBack: this.deleteQuiz.name,
			},
			{
				selector: BuilderTabQuiz.selectors.elQuizActionExpanded,
				class: this,
				callBack: this.toggleExpandedAction.name,
			},
			{
				selector: BuilderTabQuiz.selectors.elBtnEditQuiz,
				class: this,
				callBack: this.editQuiz.name,
			},
		] );

		document.addEventListener( 'click', ( e ) => {
			if ( ! e.target.closest( BuilderTabQuiz.selectors.elQuizActionExpanded ) ) {
				this.closeAllExpanded();
			}
		} );
	}

	editQuiz( args ) {
		const { target } = args;
		const elBtnEditQuiz = target.closest( BuilderTabQuiz.selectors.elBtnEditQuiz );

		if ( ! elBtnEditQuiz ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnEditQuiz, 1 );
	}

	duplicateQuiz( args ) {
		const { target } = args;
		const elQuizDuplicate = target.closest( BuilderTabQuiz.selectors.elQuizDuplicate );
		const elQuizItem = elQuizDuplicate.closest( BuilderTabQuiz.selectors.elQuizItem );

		if ( ! elQuizItem ) {
			return;
		}

		const elActionExpanded = elQuizItem.querySelector(
			BuilderTabQuiz.selectors.elQuizActionExpanded
		);
		const quizId = elQuizItem.dataset.quizId || '';

		SweetAlert.fire( {
			title: elQuizDuplicate.dataset.title || 'Duplicate Quiz',
			text: elQuizDuplicate.dataset.content || 'Are you sure you want to duplicate this quiz?',
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
					action: 'duplicate_quiz',
					args: {
						id_url: 'duplicate-quiz',
					},
					quiz_id: quizId,
				};

				const callBack = {
					success: ( response ) => {
						const { status, message, data } = response;
						lpToastify.show( message || 'Duplicated successfully!', status );

						if ( status === 'success' && data?.redirect_url ) {
							window.location.href = data.redirect_url;
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

	trashQuiz( args ) {
		const { target } = args;
		const elQuizTrash = target.closest( BuilderTabQuiz.selectors.elQuizTrash );
		const elQuizItem = elQuizTrash.closest( BuilderTabQuiz.selectors.elQuizItem );

		if ( ! elQuizItem ) {
			return;
		}

		const elActionExpanded = elQuizItem.querySelector(
			BuilderTabQuiz.selectors.elQuizActionExpanded
		);
		const quizId = elQuizItem.dataset.quizId || '';

		SweetAlert.fire( {
			title: elQuizTrash.dataset.title || 'Trash Quiz',
			text: elQuizTrash.dataset.content || 'Are you sure you want to move this quiz to trash?',
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
					action: 'move_trash_quiz',
					args: {
						id_url: 'move-trash-quiz',
					},
					quiz_id: quizId,
				};

				const callBack = {
					success: ( response ) => {
						const { status, message, data } = response;
						lpToastify.show( message, status );

						if ( data?.html ) {
							this.replaceItemHtml( elQuizTrash.closest( '.quiz' ), data.html );
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

	publishQuiz( args ) {
		const { target } = args;
		const elQuizPublish = target.closest( BuilderTabQuiz.selectors.elQuizPublish );
		const elQuizItem = elQuizPublish.closest( BuilderTabQuiz.selectors.elQuizItem );

		if ( ! elQuizItem ) {
			return;
		}

		const elActionExpanded = elQuizItem.querySelector(
			BuilderTabQuiz.selectors.elQuizActionExpanded
		);
		lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

		const quizId = elQuizItem.dataset.quizId || '';

		const dataSend = {
			action: 'move_trash_quiz',
			args: {
				id_url: 'move-trash-quiz',
			},
			quiz_id: quizId,
			status: 'publish',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );
				if ( data?.html ) {
					this.replaceItemHtml( elQuizPublish.closest( '.quiz' ), data.html );
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

	restoreQuiz( args ) {
		const { target } = args;
		const elQuizRestore = target.closest( BuilderTabQuiz.selectors.elQuizRestore );
		const elQuizItem = elQuizRestore.closest( BuilderTabQuiz.selectors.elQuizItem );

		if ( ! elQuizItem ) {
			return;
		}

		const elActionExpanded = elQuizItem.querySelector(
			BuilderTabQuiz.selectors.elQuizActionExpanded
		);
		lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

		const quizId = elQuizItem.dataset.quizId || '';

		const dataSend = {
			action: 'move_trash_quiz',
			args: {
				id_url: 'move-trash-quiz',
			},
			quiz_id: quizId,
			status: 'draft',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );
				if ( data?.html ) {
					this.replaceItemHtml( elQuizRestore.closest( '.quiz' ), data.html );
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

	deleteQuiz( args ) {
		const { target } = args;
		const elQuizDelete = target.closest( BuilderTabQuiz.selectors.elQuizDelete );
		const elQuizItem = elQuizDelete.closest( BuilderTabQuiz.selectors.elQuizItem );

		if ( ! elQuizItem ) {
			return;
		}

		const elActionExpanded = elQuizItem.querySelector(
			BuilderTabQuiz.selectors.elQuizActionExpanded
		);
		const quizId = elQuizItem.dataset.quizId || '';

		if ( ! quizId ) {
			return;
		}

		SweetAlert.fire( {
			title: elQuizDelete.dataset.title || 'Delete Quiz',
			text:
				elQuizDelete.dataset.content || 'Are you sure you want to permanently delete this quiz?',
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
					action: 'move_trash_quiz',
					args: {
						id_url: 'move-trash-quiz',
					},
					quiz_id: quizId,
					status: 'delete',
				};

				const callBack = {
					success: ( response ) => {
						const { status, message } = response;
						lpToastify.show( message, status );

						if ( status === 'success' ) {
							const elQuiz = elQuizDelete.closest( '.quiz' );
							elQuiz.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
							elQuiz.style.opacity = '0';
							elQuiz.style.transform = 'translateX(160px)';

							setTimeout( () => {
								elQuiz.remove();
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
		const elQuizActionExpanded = target.closest( BuilderTabQuiz.selectors.elQuizActionExpanded );

		if ( ! elQuizActionExpanded ) {
			return;
		}

		const elQuizItem = elQuizActionExpanded.closest( BuilderTabQuiz.selectors.elQuizItem );

		if ( ! elQuizItem ) {
			return;
		}

		const elExpandedItems = elQuizItem.querySelector(
			BuilderTabQuiz.selectors.elQuizExpandedItems
		);

		if ( ! elExpandedItems ) {
			return;
		}

		this.closeAllExpanded( elExpandedItems );
		const willOpen = ! elExpandedItems.classList.contains( 'active' );

		if ( willOpen ) {
			elExpandedItems.classList.add( 'active' );
			elQuizActionExpanded.classList.add( 'active' );
			this.setExpandedDirection( elExpandedItems );
		} else {
			elExpandedItems.classList.remove( 'active' );
			elExpandedItems.classList.remove( 'is-dropup' );
			elQuizActionExpanded.classList.remove( 'active' );
		}
	}

	closeAllExpanded( excludeElement = null ) {
		const allExpandedItems = document.querySelectorAll(
			`${ BuilderTabQuiz.selectors.elQuizExpandedItems }.active`
		);
		allExpandedItems.forEach( ( item ) => {
			if ( item === excludeElement ) {
				return;
			}

			item.classList.remove( 'active' );
			item.classList.remove( 'is-dropup' );

			const quizItem = item.closest( BuilderTabQuiz.selectors.elQuizItem );
			if ( ! quizItem ) {
				return;
			}

			const expandedBtn = quizItem.querySelector( BuilderTabQuiz.selectors.elQuizActionExpanded );
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

		const elQuiz = elExpandedItems.closest( '.quiz' );
		if ( ! elQuiz ) {
			return;
		}

		if ( elQuiz.matches( ':last-child' ) ) {
			elExpandedItems.classList.add( 'is-dropup' );
		}
	}

	replaceItemHtml( elQuiz, html ) {
		if ( ! elQuiz || ! html ) {
			return;
		}
		const tmp = document.createElement( 'div' );
		tmp.innerHTML = html;
		const newEl = tmp.firstElementChild;
		if ( newEl ) {
			elQuiz.replaceWith( newEl );
		}
	}
}
