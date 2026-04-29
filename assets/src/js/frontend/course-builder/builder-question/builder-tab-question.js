import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';
import { SWAL_ICON_DELETE, SWAL_ICON_DUPLICATE, SWAL_ICON_TRASH_DRAFT } from '../swal-icons.js';

export class BuilderTabQuestion {
	constructor() {
		this.init();
	}

	static selectors = {
		elQuestionItem: '.question-item',
		elQuestionExpandedItems: '.question-action-expanded__items',
		elQuestionDuplicate: '.question-action-expanded__duplicate',
		elQuestionTrash: '.question-action-expanded__trash',
		elQuestionRestore: '.question-action-expanded__restore',
		elQuestionPublish: '.question-action-expanded__publish',
		elQuestionDelete: '.question-action-expanded__delete',
		elQuestionActionExpanded: '.question-action-expanded',
		elQuestionStatus: '.question-status',
		elBtnEditQuestion: '.btn-edit-question',
	};

	init() {
		this.events();
	}

	events() {
		if ( BuilderTabQuestion._loadedEvents ) {
			return;
		}
		BuilderTabQuestion._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderTabQuestion.selectors.elQuestionDuplicate,
				class: this,
				callBack: this.duplicateQuestion.name,
			},
			{
				selector: BuilderTabQuestion.selectors.elQuestionTrash,
				class: this,
				callBack: this.trashQuestion.name,
			},
			{
				selector: BuilderTabQuestion.selectors.elQuestionPublish,
				class: this,
				callBack: this.publishQuestion.name,
			},
			{
				selector: BuilderTabQuestion.selectors.elQuestionRestore,
				class: this,
				callBack: this.restoreQuestion.name,
			},
			{
				selector: BuilderTabQuestion.selectors.elQuestionDelete,
				class: this,
				callBack: this.deleteQuestion.name,
			},
			{
				selector: BuilderTabQuestion.selectors.elQuestionActionExpanded,
				class: this,
				callBack: this.toggleExpandedAction.name,
			},
			{
				selector: BuilderTabQuestion.selectors.elBtnEditQuestion,
				class: this,
				callBack: this.editQuestion.name,
			},
		] );

		document.addEventListener( 'click', ( e ) => {
			if ( ! e.target.closest( BuilderTabQuestion.selectors.elQuestionActionExpanded ) ) {
				this.closeAllExpanded();
			}
		} );
	}

	editQuestion( args ) {
		const { target } = args;
		const elBtnEditQuestion = target.closest( BuilderTabQuestion.selectors.elBtnEditQuestion );

		if ( ! elBtnEditQuestion ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnEditQuestion, 1 );
	}

	duplicateQuestion( args ) {
		const { target } = args;
		const elQuestionDuplicate = target.closest( BuilderTabQuestion.selectors.elQuestionDuplicate );
		const elQuestionItem = elQuestionDuplicate.closest(
			BuilderTabQuestion.selectors.elQuestionItem
		);

		if ( ! elQuestionItem ) {
			return;
		}

		const elActionExpanded = elQuestionItem.querySelector(
			BuilderTabQuestion.selectors.elQuestionActionExpanded
		);
		const questionId = elQuestionItem.dataset.questionId || '';

		SweetAlert.fire( {
			title: elQuestionDuplicate.dataset.title || 'Duplicate Question',
			text:
				elQuestionDuplicate.dataset.content || 'Are you sure you want to duplicate this question?',
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
					action: 'duplicate_question',
					args: {
						id_url: 'duplicate-question',
					},
					question_id: questionId,
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

	trashQuestion( args ) {
		const { target } = args;
		const elQuestionTrash = target.closest( BuilderTabQuestion.selectors.elQuestionTrash );
		const elQuestionItem = elQuestionTrash.closest( BuilderTabQuestion.selectors.elQuestionItem );

		if ( ! elQuestionItem ) {
			return;
		}

		const elActionExpanded = elQuestionItem.querySelector(
			BuilderTabQuestion.selectors.elQuestionActionExpanded
		);
		const questionId = elQuestionItem.dataset.questionId || '';

		SweetAlert.fire( {
			title: elQuestionTrash.dataset.title || 'Trash Question',
			text:
				elQuestionTrash.dataset.content || 'Are you sure you want to move this question to trash?',
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
					action: 'move_trash_question',
					args: {
						id_url: 'move-trash-question',
					},
					question_id: questionId,
				};

				const callBack = {
					success: ( response ) => {
						const { status, message, data } = response;
						lpToastify.show( message, status );

						if ( data?.html ) {
							this.replaceItemHtml( elQuestionTrash.closest( '.question' ), data.html );
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

	publishQuestion( args ) {
		const { target } = args;
		const elQuestionPublish = target.closest( BuilderTabQuestion.selectors.elQuestionPublish );
		const elQuestionItem = elQuestionPublish.closest( BuilderTabQuestion.selectors.elQuestionItem );

		if ( ! elQuestionItem ) {
			return;
		}

		const elActionExpanded = elQuestionItem.querySelector(
			BuilderTabQuestion.selectors.elQuestionActionExpanded
		);
		lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

		const questionId = elQuestionItem.dataset.questionId || '';

		const dataSend = {
			action: 'move_trash_question',
			args: {
				id_url: 'move-trash-question',
			},
			question_id: questionId,
			status: 'publish',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.html ) {
					this.replaceItemHtml( elQuestionPublish.closest( '.question' ), data.html );
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

	restoreQuestion( args ) {
		const { target } = args;
		const elQuestionRestore = target.closest( BuilderTabQuestion.selectors.elQuestionRestore );
		const elQuestionItem = elQuestionRestore.closest( BuilderTabQuestion.selectors.elQuestionItem );

		if ( ! elQuestionItem ) {
			return;
		}

		const elActionExpanded = elQuestionItem.querySelector(
			BuilderTabQuestion.selectors.elQuestionActionExpanded
		);
		lpUtils.lpSetLoadingEl( elActionExpanded, 1 );

		const questionId = elQuestionItem.dataset.questionId || '';

		const dataSend = {
			action: 'move_trash_question',
			args: {
				id_url: 'move-trash-question',
			},
			question_id: questionId,
			status: 'draft',
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.html ) {
					this.replaceItemHtml( elQuestionRestore.closest( '.question' ), data.html );
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

	deleteQuestion( args ) {
		const { target } = args;
		const elQuestionDelete = target.closest( BuilderTabQuestion.selectors.elQuestionDelete );
		const elQuestionItem = elQuestionDelete.closest( BuilderTabQuestion.selectors.elQuestionItem );

		if ( ! elQuestionItem ) {
			return;
		}

		const elActionExpanded = elQuestionItem.querySelector(
			BuilderTabQuestion.selectors.elQuestionActionExpanded
		);
		const questionId = elQuestionItem.dataset.questionId || '';

		if ( ! questionId ) {
			return;
		}

		SweetAlert.fire( {
			title: elQuestionDelete.dataset.title || 'Delete Question',
			text:
				elQuestionDelete.dataset.content ||
				'Are you sure you want to permanently delete this question?',
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
					action: 'move_trash_question',
					args: {
						id_url: 'move-trash-question',
					},
					question_id: questionId,
					status: 'delete',
				};

				const callBack = {
					success: ( response ) => {
						const { status, message } = response;
						lpToastify.show( message, status );

						if ( status === 'success' ) {
							const elQuestion = elQuestionDelete.closest( '.question' );
							elQuestion.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
							elQuestion.style.opacity = '0';
							elQuestion.style.transform = 'translateX(160px)';

							setTimeout( () => {
								elQuestion.remove();
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
		const elQuestionActionExpanded = target.closest(
			BuilderTabQuestion.selectors.elQuestionActionExpanded
		);

		if ( ! elQuestionActionExpanded ) {
			return;
		}

		const elQuestionItem = elQuestionActionExpanded.closest(
			BuilderTabQuestion.selectors.elQuestionItem
		);

		if ( ! elQuestionItem ) {
			return;
		}

		const elExpandedItems = elQuestionItem.querySelector(
			BuilderTabQuestion.selectors.elQuestionExpandedItems
		);

		if ( ! elExpandedItems ) {
			return;
		}

		this.closeAllExpanded( elExpandedItems );
		const willOpen = ! elExpandedItems.classList.contains( 'active' );

		if ( willOpen ) {
			elExpandedItems.classList.add( 'active' );
			elQuestionActionExpanded.classList.add( 'active' );
			this.setExpandedDirection( elExpandedItems );
		} else {
			elExpandedItems.classList.remove( 'active' );
			elExpandedItems.classList.remove( 'is-dropup' );
			elQuestionActionExpanded.classList.remove( 'active' );
		}
	}

	closeAllExpanded( excludeElement = null ) {
		const allExpandedItems = document.querySelectorAll(
			`${ BuilderTabQuestion.selectors.elQuestionExpandedItems }.active`
		);
		allExpandedItems.forEach( ( item ) => {
			if ( item === excludeElement ) {
				return;
			}

			item.classList.remove( 'active' );
			item.classList.remove( 'is-dropup' );

			const questionItem = item.closest( BuilderTabQuestion.selectors.elQuestionItem );

			if ( ! questionItem ) {
				return;
			}

			const expandedBtn = questionItem.querySelector(
				BuilderTabQuestion.selectors.elQuestionActionExpanded
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

		const elQuestion = elExpandedItems.closest( '.question' );
		if ( ! elQuestion ) {
			return;
		}

		if ( elQuestion.matches( ':last-child' ) ) {
			elExpandedItems.classList.add( 'is-dropup' );
		}
	}

	replaceItemHtml( elQuestion, html ) {
		if ( ! elQuestion || ! html ) {
			return;
		}
		const tmp = document.createElement( 'div' );
		tmp.innerHTML = html;
		const newEl = tmp.firstElementChild;
		if ( newEl ) {
			elQuestion.replaceWith( newEl );
		}
	}
}
