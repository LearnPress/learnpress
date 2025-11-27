import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';

export class BuilderTabQuestion {
	constructor() {
		this.init();
	}

	static selectors = {
		elQuestionItem: '.question-item',
		elQuestionExpandedItems: '.question-action-expanded__items',
		elQuestionDuplicate: '.question-action-expanded__duplicate',
		elQuestionTrash: '.question-action-expanded__trash',
		elQuestionPublish: '.question-action-expanded__publish',
		elQuestionDelete: '.question-action-expanded__delete',
		elQuestionActionExpanded: '.question-action-expanded',
		elQuestionStatus: '.question-status',
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
				selector: BuilderTabQuestion.selectors.elQuestionDelete,
				class: this,
				callBack: this.deleteQuestion.name,
			},
			{
				selector: BuilderTabQuestion.selectors.elQuestionActionExpanded,
				class: this,
				callBack: this.toggleExpandedAction.name,
			},
		] );

		document.addEventListener( 'click', ( e ) => {
			if ( ! e.target.closest( BuilderTabQuestion.selectors.elQuestionActionExpanded ) ) {
				this.closeAllExpanded();
			}
		} );
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

		lpUtils.lpSetLoadingEl( elQuestionDuplicate, 1 );

		const questionId = elQuestionItem.dataset.questionId || '';

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
				lpToastify.show( message, status );

				if ( data?.html ) {
					const elQuestion = elQuestionDuplicate.closest( '.question' );
					elQuestion.insertAdjacentHTML( 'afterend', data.html );

					const newQuestion = elQuestion.nextElementSibling;
					if ( newQuestion ) {
						newQuestion.scrollIntoView( {
							behavior: 'smooth',
							block: 'nearest',
						} );

						newQuestion.classList.add( 'highlight-new-question' );
						setTimeout( () => {
							newQuestion.classList.remove( 'highlight-new-question' );
						}, 1500 );
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elQuestionDuplicate, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	trashQuestion( args ) {
		const { target } = args;
		const elQuestionTrash = target.closest( BuilderTabQuestion.selectors.elQuestionTrash );
		const elQuestionItem = elQuestionTrash.closest( BuilderTabQuestion.selectors.elQuestionItem );

		if ( ! elQuestionItem ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elQuestionTrash, 1 );

		const questionId = elQuestionItem.dataset.questionId || '';

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

				if ( data?.status ) {
					const elQuestion = elQuestionTrash.closest( '.question' );
					this.updateStatusUI( elQuestion, data.status );
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elQuestionTrash, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	publishQuestion( args ) {
		const { target } = args;
		const elQuestionPublish = target.closest( BuilderTabQuestion.selectors.elQuestionPublish );
		const elQuestionItem = elQuestionPublish.closest( BuilderTabQuestion.selectors.elQuestionItem );

		if ( ! elQuestionItem ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elQuestionPublish, 1 );

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

				if ( data?.status ) {
					const elQuestion = elQuestionPublish.closest( '.question' );
					this.updateStatusUI( elQuestion, data.status );
				}
			},
			error: ( error ) => {
				this.showToast( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elQuestionPublish, 0 );
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

		const questionId = elQuestionItem.dataset.questionId || '';

		if ( ! questionId ) {
			return;
		}

		SweetAlert.fire( {
			title: elQuestionDelete.dataset.title,
			text: elQuestionDelete.dataset.content,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpData.i18n.cancel,
			confirmButtonText: lpData.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
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
						const elQuestion = elQuestionDelete.closest( '.question' );
						elQuestion.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
						elQuestion.style.opacity = '0';
						elQuestion.style.transform = 'translateX(160px)';

						setTimeout( () => {
							elQuestion.remove();
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
		const elQuestionActionExpanded = target.closest(
			BuilderTabQuestion.selectors.elQuestionActionExpanded
		);
		const elQuestionItem = elQuestionActionExpanded.closest(
			BuilderTabQuestion.selectors.elQuestionItem
		);
		const elExpandedItems = elQuestionItem.querySelector(
			BuilderTabQuestion.selectors.elQuestionExpandedItems
		);

		if ( ! elExpandedItems ) {
			return;
		}

		this.closeAllExpanded( elExpandedItems );

		elExpandedItems.classList.toggle( 'active' );
		elQuestionActionExpanded.classList.toggle( 'active' );
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

			const questionItem = item.closest( BuilderTabQuestion.selectors.elQuestionItem );
			const expandedBtn = questionItem.querySelector(
				BuilderTabQuestion.selectors.elQuestionActionExpanded
			);
			if ( expandedBtn ) {
				expandedBtn.classList.remove( 'active' );
			}
		} );
	}

	updateStatusUI( elQuestion, status ) {
		const elStatus = elQuestion.querySelector( BuilderTabQuestion.selectors.elQuestionStatus );
		const elSpanStatus = elQuestion.querySelector(
			`${ BuilderTabQuestion.selectors.elQuestionStatus } span`
		);
		if ( elSpanStatus && elStatus ) {
			elStatus.className = 'question-status ' + status;
			elSpanStatus.textContent = status;
		} else if ( elStatus ) {
			elStatus.className = 'question-status ' + status;
			elStatus.textContent = status;
		}
	}
}
