/**
 * Edit Quiz JS handler as a class.
 *
 * @since 4.2.8.6
 * @version 1.0.3
 */
import * as lpUtils from '../utils.js';
import SweetAlert from 'sweetalert2';
import Sortable from 'sortablejs';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import 'toastify-js/src/toastify.css';
import { EditQuestion } from './edit-question.js';
import { LpPopupSelectItemToAdd } from 'lpAssetsJsPath/lpPopupSelectItemToAdd.js';

let editQuestion;

const lpPopupSelectItemToAdd = new LpPopupSelectItemToAdd();
lpPopupSelectItemToAdd.init();

class EditQuiz {
	constructor() {
		this.idUrlHandle = 'edit-quiz-questions';
		this.elEditQuizWrap = null;
		this.elEditListQuestions = null;
		this.quizID = null;
	}

	static selectors = {
		elEditQuizWrap: '.lp-edit-quiz-wrap',
		elQuestionEditMain: '.lp-question-edit-main',
		elQuestionToggleAll: '.lp-question-toggle-all',
		elEditListQuestions: '.lp-edit-list-questions',
		elQuestionItem: '.lp-question-item',
		elQuestionToggle: '.lp-question-toggle',
		elPopupItemsToSelectClone: '.lp-popup-items-to-select.clone',
		elBtnAddQuestion: '.lp-btn-add-question',
		elBtnRemoveQuestion: '.lp-btn-remove-question',
		elBtnUpdateQuestionTitle: '.lp-btn-update-question-title',
		elBtnCancelUpdateQuestionTitle: '.lp-btn-cancel-update-question-title',
		elQuestionTitleNewInput: '.lp-question-title-new-input',
		elQuestionTitleInput: '.lp-question-title-input',
		elQuestionTypeLabel: '.lp-question-type-label',
		elQuestionTypeNew: '.lp-question-type-new',
		elAddNewQuestion: 'add-new-question',
		elQuestionClone: '.lp-question-item.clone',
		LPTarget: '.lp-target',
		elCollapse: 'lp-collapse',
	};

	init() {
		lpUtils.lpOnElementReady( EditQuiz.selectors.elEditQuizWrap, ( elEditQuizWrapFound ) => {
			this.elEditQuizWrap = elEditQuizWrapFound;
			this.elEditListQuestions = this.elEditQuizWrap.querySelector( EditQuiz.selectors.elEditListQuestions );
			const elLPTarget = this.elEditQuizWrap.closest( EditQuiz.selectors.LPTarget );
			const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
			this.quizID = dataSend.args.quiz_id;

			this.sortAbleQuestion();
			editQuestion = new EditQuestion();
			editQuestion.init();
			const elQuestionEditMains = elEditQuizWrapFound.querySelectorAll( `${ EditQuiz.selectors.elQuestionEditMain }` );
			elQuestionEditMains.forEach( ( elQuestionEditMain ) => {
				editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
			} );

			this.events();
		} );
	}

	events() {
		if ( EditQuiz._loadedEvents ) {
			return;
		}
		EditQuiz._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: EditQuiz.selectors.elQuestionToggleAll,
				class: this,
				callBack: this.toggleQuestionAll.name,
			},
			{
				selector: EditQuiz.selectors.elBtnAddQuestion,
				class: this,
				callBack: this.addQuestion.name,
			},
			{
				selector: EditQuiz.selectors.elBtnRemoveQuestion,
				class: this,
				callBack: this.removeQuestion.name,
			},
			{
				selector: EditQuiz.selectors.elBtnUpdateQuestionTitle,
				class: this,
				callBack: this.updateQuestionTitle.name,
			},
			{
				selector: EditQuiz.selectors.elBtnCancelUpdateQuestionTitle,
				class: this,
				callBack: this.cancelChangeTitleQuestion.name,
			},
			{
				selector: LpPopupSelectItemToAdd.selectors.elBtnAddItemsSelected,
				class: lpPopupSelectItemToAdd,
				callBack: lpPopupSelectItemToAdd.addItemsSelectedToSection.name,
				callBackHandle: this.addQuestionsSelectedToQuiz.bind( this ),
			},
		] );

		// Keydown
		lpUtils.eventHandlers( 'keydown', [
			{
				selector: EditQuiz.selectors.elQuestionTitleInput,
				class: this,
				callBack: this.updateQuestionTitle.name,
				checkIsEventEnter: true,
			},
			{
				selector: EditQuiz.selectors.elQuestionTitleNewInput,
				class: this,
				callBack: this.addQuestion.name,
				checkIsEventEnter: true,
			},
		] );

		// Keyup
		lpUtils.eventHandlers( 'keyup', [
			{
				selector: EditQuiz.selectors.elQuestionTitleInput,
				class: this,
				callBack: this.changeTitleQuestion.name,
			},
			{
				selector: `${ EditQuiz.selectors.elQuestionTitleNewInput }, ${ EditQuiz.selectors.elQuestionTypeNew }`,
				class: this,
				callBack: this.checkCanAddQuestion.name,
			},
		] );

		// Change
		lpUtils.eventHandlers( 'change', [
			{
				selector: EditQuiz.selectors.elQuestionTypeNew,
				class: this,
				callBack: this.checkCanAddQuestion.name,
			},
		] );

		// Click
		document.addEventListener( 'click', ( e ) => {
			const target = e.target;

			lpUtils.toggleCollapse( e, target, EditQuiz.selectors.elQuestionToggle, [], () => this.checkAllQuestionsCollapsed() );
		} );
	}

	// Toggle all questions
	toggleQuestionAll( args ) {
		const { e, target } = args;
		const elQuestionToggleAll = target.closest( `${ EditQuiz.selectors.elQuestionToggleAll }` );
		if ( ! elQuestionToggleAll ) {
			return;
		}

		const elQuestionItems = this.elEditQuizWrap.querySelectorAll( `${ EditQuiz.selectors.elQuestionItem }:not(.clone)` );

		elQuestionToggleAll.classList.toggle( `${ EditQuiz.selectors.elCollapse }` );

		elQuestionItems.forEach( ( el ) => {
			const shouldCollapse = elQuestionToggleAll.classList.contains( `${ EditQuiz.selectors.elCollapse }` );
			el.classList.toggle( `${ EditQuiz.selectors.elCollapse }`, shouldCollapse );
		} );
	}

	checkAllQuestionsCollapsed() {
		const elQuestionItems = this.elEditQuizWrap.querySelectorAll( `${ EditQuiz.selectors.elQuestionItem }:not(.clone)` );
		const elQuestionToggleAll = this.elEditQuizWrap.querySelector( `${ EditQuiz.selectors.elQuestionToggleAll }` );

		let isAllExpand = true;
		elQuestionItems.forEach( ( el ) => {
			if ( el.classList.contains( `${ EditQuiz.selectors.elCollapse }` ) ) {
				isAllExpand = false;
				return false; // Break
			}
		} );

		if ( isAllExpand ) {
			elQuestionToggleAll.classList.remove( `${ EditQuiz.selectors.elCollapse }` );
		} else {
			elQuestionToggleAll.classList.add( `${ EditQuiz.selectors.elCollapse }` );
		}
	}

	updateCountItems() {
		const elCountItemsAll = this.elEditQuizWrap.querySelector( '.total-items' );
		const elItemsAll = this.elEditQuizWrap.querySelectorAll( `${ EditQuiz.selectors.elQuestionItem }:not(.clone)` );
		const itemsAllCount = elItemsAll.length;

		elCountItemsAll.dataset.count = itemsAllCount;
		elCountItemsAll.querySelector( '.count' ).textContent = itemsAllCount;
	}

	// Add question to quiz
	addQuestion( args ) {
		const { e, target, callBackNest } = args;
		e.preventDefault();

		const elAddNewQuestion = target.closest( `.${ EditQuiz.selectors.elAddNewQuestion }` );
		if ( ! elAddNewQuestion ) {
			return;
		}

		const elQuestionTitleNewInput = elAddNewQuestion.querySelector( `${ EditQuiz.selectors.elQuestionTitleNewInput }` );
		const questionTitle = elQuestionTitleNewInput.value.trim();
		if ( ! questionTitle ) {
			lpToastify.show( elQuestionTitleNewInput.dataset.messEmptyTitle, 'error' );
			return;
		}

		const elQuestionType = elAddNewQuestion.querySelector( `${ EditQuiz.selectors.elQuestionTypeNew }` );
		const questionType = elQuestionType.value;
		if ( ! questionType ) {
			lpToastify.show( elQuestionType.dataset.messEmptyType, 'error' );
			return;
		}

		const elQuestionClone = this.elEditListQuestions.querySelector( `${ EditQuiz.selectors.elQuestionItem }.clone` );
		const newQuestionItem = elQuestionClone.cloneNode( true );
		const elQuestionTitleInput = newQuestionItem.querySelector( `${ EditQuiz.selectors.elQuestionTitleInput }` );

		elQuestionTitleInput.value = questionTitle;
		elQuestionTitleNewInput.value = '';
		newQuestionItem.classList.remove( 'clone' );
		lpUtils.lpShowHideEl( newQuestionItem, 1 );
		elQuestionClone.insertAdjacentElement( 'beforebegin', newQuestionItem );
		lpUtils.lpSetLoadingEl( newQuestionItem, 1 );

		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;
				const { question, html_edit_question } = data;

				if ( status === 'error' ) {
					throw `Error: ${ message }`;
				} else if ( status === 'success' ) {
					newQuestionItem.dataset.questionId = question.ID;
					newQuestionItem.dataset.questionType = question.meta_data._lp_type;
					newQuestionItem.outerHTML = html_edit_question;
					const elQuestionItemCreated = this.elEditListQuestions.querySelector( `${ EditQuiz.selectors.elQuestionItem }[data-question-id="${ question.ID }"]` );
					elQuestionItemCreated.classList.remove( EditQuiz.selectors.elCollapse );
					this.updateCountItems();
					editQuestion.initTinyMCE();
					const elQuestionEditMain = elQuestionItemCreated.querySelector( `${ EditQuiz.selectors.elQuestionEditMain }` );
					editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );

					// Callback nest
					if ( callBackNest && typeof callBackNest.success === 'function' ) {
						callBackNest.success( { response, elQuestionItemCreated } );
					}
				}

				lpToastify.show( message, status );
			},
			error: ( error ) => {
				newQuestionItem.remove();
				lpToastify.show( error, 'error' );

				if ( callBackNest && typeof callBackNest.error === 'function' ) {
					callBackNest.error( { error, newQuestionItem } );
				}
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( newQuestionItem, 0 );
				this.checkCanAddQuestion( { e, target: elQuestionTitleNewInput } );

				if ( callBackNest && typeof callBackNest.completed === 'function' ) {
					callBackNest.completed( { newQuestionItem } );
				}
			},
		};

		let dataSend = JSON.parse( elQuestionTitleNewInput.dataset.send );
		dataSend = { ...dataSend,
			question_title: questionTitle,
			question_type: questionType,
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	// Add questions selected from popup to quiz
	addQuestionsSelectedToQuiz( itemsSelected ) {
		const questionIds = [];
		itemsSelected.forEach( ( item ) => {
			const elQuestionItemClone = this.elEditQuizWrap.querySelector( `${ EditQuiz.selectors.elQuestionItem }.clone` );
			if ( ! elQuestionItemClone ) {
				return;
			}

			questionIds.push( item.id );
			const elQuestionItemNew = elQuestionItemClone.cloneNode( true );
			const elQuestionItemTitleInput = elQuestionItemNew.querySelector( `${ EditQuiz.selectors.elQuestionTitleInput }` );
			elQuestionItemNew.classList.remove( 'clone' );
			elQuestionItemNew.dataset.questionId = item.id;
			elQuestionItemTitleInput.value = item.titleSelected;

			lpUtils.lpSetLoadingEl( elQuestionItemNew, 1 );
			lpUtils.lpShowHideEl( elQuestionItemNew, 1 );
			elQuestionItemClone.insertAdjacentElement( 'beforebegin', elQuestionItemNew );
			lpUtils.lpSetLoadingEl( elQuestionItemNew, 1 );
		} );

		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				if ( status === 'success' ) {
					lpToastify.show( message, status );

					const { html_edit_question } = data;
					if ( html_edit_question ) {
						Object.entries( html_edit_question ).forEach( ( [ question_id, item_html ] ) => {
							const elQuestionItemNew = this.elEditQuizWrap.querySelector(
								`${ EditQuiz.selectors.elQuestionItem }[data-question-id="${ question_id }"]`
							);
							elQuestionItemNew.outerHTML = item_html;
						} );
					}
					this.updateCountItems();
					editQuestion.initTinyMCE();
				} else {
					throw `Error: ${ message }`;
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				// completed handler intentionally empty
			},
		};

		const dataSend = { action: 'add_questions_to_quiz', quiz_id: this.quizID, question_ids: questionIds, args: { id_url: this.idUrlHandle } };
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	checkCanAddQuestion( args ) {
		const { e, target } = args;
		const elTrigger = target.closest( EditQuiz.selectors.elQuestionTitleNewInput ) || target.closest( EditQuiz.selectors.elQuestionTypeNew );
		if ( ! elTrigger ) {
			return;
		}

		const elAddNewQuestion = elTrigger.closest( `.${ EditQuiz.selectors.elAddNewQuestion }` );
		if ( ! elAddNewQuestion ) {
			return;
		}

		const elBtnAddQuestion = elAddNewQuestion.querySelector( `${ EditQuiz.selectors.elBtnAddQuestion }` );
		if ( ! elBtnAddQuestion ) {
			return;
		}

		const elQuestionTitleInput = elAddNewQuestion.querySelector( `${ EditQuiz.selectors.elQuestionTitleNewInput }` );
		const elQuestionTypeNew = elAddNewQuestion.querySelector( `${ EditQuiz.selectors.elQuestionTypeNew }` );

		const questionTitle = elQuestionTitleInput.value.trim();
		const questionType = elQuestionTypeNew.value;

		if ( questionTitle && questionType ) {
			elBtnAddQuestion.classList.add( 'active' );
		} else {
			elBtnAddQuestion.classList.remove( 'active' );
		}
	}

	removeQuestion( args ) {
		const { e, target } = args;
		const elBtnRemoveQuestion = target.closest( `${ EditQuiz.selectors.elBtnRemoveQuestion }` );
		if ( ! elBtnRemoveQuestion ) {
			return;
		}

		const elQuestionItem = elBtnRemoveQuestion.closest( `${ EditQuiz.selectors.elQuestionItem }` );
		if ( ! elQuestionItem ) {
			return;
		}

		const questionId = elQuestionItem.dataset.questionId;

		SweetAlert.fire( {
			title: elBtnRemoveQuestion.dataset.title,
			text: elBtnRemoveQuestion.dataset.content,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: lpDataAdmin.i18n.cancel,
			confirmButtonText: lpDataAdmin.i18n.yes,
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

				const callBack = {
					success: ( response ) => {
						const { message, status } = response;

						lpToastify.show( message, status );

						if ( status === 'success' ) {
							elQuestionItem.remove();
							this.updateCountItems();
						}
					},
					error: ( error ) => {
						lpToastify.show( error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
					},
				};

				const dataSend = {
					quiz_id: this.quizID,
					action: 'remove_question_from_quiz',
					question_id: questionId,
					args: { id_url: this.idUrlHandle },
				};
				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}
		} );
	}

	updateQuestionTitle( args ) {
		const { e, target } = args;
		let canHandle = false;

		if ( target.closest( `${ EditQuiz.selectors.elBtnUpdateQuestionTitle }` ) ) {
			canHandle = true;
		} else if ( target.closest( `${ EditQuiz.selectors.elQuestionTitleInput }` ) && e.key === 'Enter' ) {
			canHandle = true;
		}

		if ( ! canHandle ) {
			return;
		}

		e.preventDefault();

		const elQuestionItem = target.closest( `${ EditQuiz.selectors.elQuestionItem }` );
		if ( ! elQuestionItem ) {
			return;
		}

		const elQuestionTitleInput = elQuestionItem.querySelector( `${ EditQuiz.selectors.elQuestionTitleInput }` );
		if ( ! elQuestionTitleInput ) {
			return;
		}

		const questionId = elQuestionItem.dataset.questionId;
		const questionTitleValue = elQuestionTitleInput.value.trim();
		const titleOld = elQuestionTitleInput.dataset.old;
		const message = elQuestionTitleInput.dataset.messEmptyTitle;
		if ( questionTitleValue.length === 0 ) {
			lpToastify.show( message, 'error' );
			return;
		}

		if ( questionTitleValue === titleOld ) {
			return;
		}

		elQuestionTitleInput.blur();
		lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

		const callBack = {
			success: ( response ) => {
				const { message, status } = response;

				if ( status === 'success' ) {
					elQuestionTitleInput.dataset.old = questionTitleValue;
				} else {
					elQuestionTitleInput.value = titleOld;
				}

				lpToastify.show( message, status );
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
				elQuestionItem.classList.remove( 'editing' );
			},
		};

		const dataSend = {
			quiz_id: this.quizID,
			action: 'update_question',
			question_id: questionId,
			question_title: questionTitleValue,
			args: { id_url: this.idUrlHandle },
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	changeTitleQuestion( args ) {
		const { e, target } = args;
		const elQuestionTitleInput = target.closest( `${ EditQuiz.selectors.elQuestionTitleInput }` );
		if ( ! elQuestionTitleInput ) {
			return;
		}

		const elQuestionItem = elQuestionTitleInput.closest( `${ EditQuiz.selectors.elQuestionItem }` );
		const titleValue = elQuestionTitleInput.value.trim();
		const titleValueOld = elQuestionTitleInput.dataset.old || '';

		if ( titleValue === titleValueOld ) {
			elQuestionItem.classList.remove( 'editing' );
		} else {
			elQuestionItem.classList.add( 'editing' );
		}
	}

	cancelChangeTitleQuestion( args ) {
		const { e, target } = args;
		const elBtnCancelUpdateQuestionTitle = target.closest( `${ EditQuiz.selectors.elBtnCancelUpdateQuestionTitle }` );
		if ( ! elBtnCancelUpdateQuestionTitle ) {
			return;
		}

		const elQuestionItem = elBtnCancelUpdateQuestionTitle.closest( `${ EditQuiz.selectors.elQuestionItem }` );
		const elQuestionTitleInput = elQuestionItem.querySelector( `${ EditQuiz.selectors.elQuestionTitleInput }` );
		elQuestionTitleInput.value = elQuestionTitleInput.dataset.old || '';
		elQuestionItem.classList.remove( 'editing' );
	}

	sortAbleQuestion() {
		let isUpdateSectionPosition = 0;
		let timeout;

		new Sortable( this.elEditListQuestions, {
			handle: '.drag',
			animation: 150,
			onEnd: ( evt ) => {
				const elQuestionItem = evt.item;
				if ( ! isUpdateSectionPosition ) {
					return;
				}

				clearTimeout( timeout );
				timeout = setTimeout( () => {
					lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

					const questionIds = [];
					const elQuestionItems = this.elEditListQuestions.querySelectorAll( `${ EditQuiz.selectors.elQuestionItem }:not(.clone)` );
					elQuestionItems.forEach( ( elItem ) => {
						const questionId = elItem.dataset.questionId;
						if ( questionId ) {
							questionIds.push( questionId );
						}
					} );

					const callBack = {
						success: ( response ) => {
							const { message, status } = response;

							if ( status === 'success' ) {
								lpToastify.show( message, status );
								editQuestion.initTinyMCE();
							} else {
								throw `Error: ${ message }`;
							}
						},
						error: ( error ) => {
							lpToastify.show( error, 'error' );
						},
						completed: () => {
							lpUtils.lpSetLoadingEl( elQuestionItem, 0 ); isUpdateSectionPosition = 0;
						},
					};

					const dataSend = { quiz_id: this.quizID, action: 'update_questions_position', question_ids: questionIds, args: { id_url: this.idUrlHandle } };
					window.lpAJAXG.fetchAJAX( dataSend, callBack );
				}, 1000 );
			},
			onMove: () => {
				clearTimeout( timeout );
			},
			onUpdate: () => {
				isUpdateSectionPosition = 1;
			},
		} );
	}
}

const editQuiz = new EditQuiz();
editQuiz.init();
