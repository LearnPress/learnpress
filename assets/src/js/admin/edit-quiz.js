/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.1
 */
import * as lpUtils from '../utils.js';
import SweetAlert from 'sweetalert2';
import Sortable from 'sortablejs';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import * as lpPopupSelectItemToAdd from '../lpPopupSelectItemToAdd.js';
import * as editQuestion from './edit-question.js';

let elEditQuizWrap;
let elEditListQuestions;

const className = {
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
let quizID;
const idUrlHandle = 'edit-quiz-questions';
const argsToastify = {
	text: '',
	gravity: lpDataAdmin.toast.gravity, // `top` or `bottom`
	position: lpDataAdmin.toast.position, // `left`, `center` or `right`
	className: `${ lpDataAdmin.toast.classPrefix }`,
	close: lpDataAdmin.toast.close == 1,
	stopOnFocus: lpDataAdmin.toast.stopOnFocus == 1,
	duration: lpDataAdmin.toast.duration,
};
const showToast = ( message, status = 'success' ) => {
	const toastify = new Toastify( {
		...argsToastify,
		text: message,
		className: `${ lpDataAdmin.toast.classPrefix } ${ status }`,
	} );
	toastify.showToast();
};

// Toggle all sections
const toggleQuestionAll = ( e, target ) => {
	const elQuestionToggleAll = target.closest(
		`${ className.elQuestionToggleAll }`
	);
	if ( ! elQuestionToggleAll ) {
		return;
	}

	const elQuestionItems = elEditQuizWrap.querySelectorAll(
		`${ className.elQuestionItem }:not(.clone)`
	);

	elQuestionToggleAll.classList.toggle( `${ className.elCollapse }` );

	elQuestionItems.forEach( ( el ) => {
		const shouldCollapse = elQuestionToggleAll.classList.contains(
			`${ className.elCollapse }`
		);
		el.classList.toggle( `${ className.elCollapse }`, shouldCollapse );
	} );
};

// Check if all sections are collapsed
const checkAllQuestionsCollapsed = () => {
	const elQuestionItems = elEditQuizWrap.querySelectorAll(
		`${ className.elQuestionItem }:not(.clone)`
	);
	const elQuestionToggleAll = elEditQuizWrap.querySelector(
		`${ className.elQuestionToggleAll }`
	);

	let isAllExpand = true;
	elQuestionItems.forEach( ( el ) => {
		if ( el.classList.contains( `${ className.elCollapse }` ) ) {
			isAllExpand = false;
			return false; // Break the loop
		}
	} );

	if ( isAllExpand ) {
		elQuestionToggleAll.classList.remove( `${ className.elCollapse }` );
	} else {
		elQuestionToggleAll.classList.add( `${ className.elCollapse }` );
	}
};

let elPopupSelectItems;

// Update count items in each section and all sections
const updateCountItems = ( elSection ) => {
	const elCountItemsAll = elEditQuizWrap.querySelector( '.total-items' );
	const elItemsAll = elEditQuizWrap.querySelectorAll(
		`${ className.elQuestionItem }:not(.clone)`
	);
	const itemsAllCount = elItemsAll.length;

	elCountItemsAll.dataset.count = itemsAllCount;
	elCountItemsAll.querySelector( '.count' ).textContent = itemsAllCount;
};

const addQuestion = ( e, target ) => {
	let canHandle = false;

	if ( target.closest( `${ className.elBtnAddQuestion }` ) ) {
		canHandle = true;
	} else if (
		target.closest( `${ className.elQuestionTitleNewInput }` ) &&
		e.key === 'Enter'
	) {
		canHandle = true;
	}

	if ( ! canHandle ) {
		return;
	}

	e.preventDefault();

	const elAddNewQuestion = target.closest(
		`.${ className.elAddNewQuestion }`
	);
	if ( ! elAddNewQuestion ) {
		return;
	}

	const elQuestionTitleNewInput = elAddNewQuestion.querySelector(
		`${ className.elQuestionTitleNewInput }`
	);
	const questionTitle = elQuestionTitleNewInput.value.trim();
	if ( ! questionTitle ) {
		showToast( elQuestionTitleNewInput.dataset.messEmptyTitle, 'error' );
		return;
	}

	const elQuestionType = elAddNewQuestion.querySelector(
		`${ className.elQuestionTypeNew }`
	);
	const questionType = elQuestionType.value;
	if ( ! questionType ) {
		showToast( elQuestionType.dataset.messEmptyType, 'error' );
		return;
	}

	const elQuestionClone = elEditListQuestions.querySelector(
		`${ className.elQuestionItem }.clone`
	);
	const newQuestionItem = elQuestionClone.cloneNode( true );
	const elQuestionTitleInput = newQuestionItem.querySelector(
		`${ className.elQuestionTitleInput }`
	);

	elQuestionTitleInput.value = questionTitle;
	elQuestionTitleNewInput.value = '';
	newQuestionItem.classList.remove( 'clone' );
	lpUtils.lpShowHideEl( newQuestionItem, 1 );
	elQuestionClone.insertAdjacentElement( 'beforebegin', newQuestionItem );
	lpUtils.lpSetLoadingEl( newQuestionItem, 1 );

	// Call ajax to add new question
	const callBack = {
		success: ( response ) => {
			const { message, status, data } = response;
			const {
				question,
				html_edit_question,
			} = data;

			if ( status === 'error' ) {
				throw `Error: ${ message }`;
			} else if ( status === 'success' ) {
				newQuestionItem.dataset.questionId = question.ID;
				newQuestionItem.dataset.questionType = question.meta_data._lp_type;
				newQuestionItem.outerHTML = html_edit_question;
				const elQuestionItemCreated = elEditListQuestions.querySelector(
					`${ className.elQuestionItem }[data-question-id="${ question.ID }"]`
				);
				elQuestionItemCreated.classList.remove( className.elCollapse );
				updateCountItems();
				editQuestion.initTinyMCE();
				const elQuestionEditMain = elQuestionItemCreated.querySelector( `${ className.elQuestionEditMain }` );
				editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
			}

			showToast( message, status );
		},
		error: ( error ) => {
			newQuestionItem.remove();
			showToast( error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( newQuestionItem, 0 );
			checkCanAddQuestion( e, elQuestionTitleNewInput );
		},
	};

	const dataSend = {
		action: 'create_question_add_to_quiz',
		quiz_id: quizID,
		question_title: questionTitle,
		question_type: questionType,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Check to enable or disable add new question button
const checkCanAddQuestion = ( e, target ) => {
	const elTrigger = target.closest( className.elQuestionTitleNewInput ) ||
		target.closest( className.elQuestionTypeNew );
	if ( ! elTrigger ) {
		return;
	}

	const elAddNewQuestion = elTrigger.closest( `.${ className.elAddNewQuestion }` );
	if ( ! elAddNewQuestion ) {
		return;
	}

	const elBtnAddQuestion = elAddNewQuestion.querySelector( `${ className.elBtnAddQuestion }` );
	if ( ! elBtnAddQuestion ) {
		return;
	}

	const elQuestionTitleInput = elAddNewQuestion.querySelector( `${ className.elQuestionTitleNewInput }` );
	const elQuestionTypeNew = elAddNewQuestion.querySelector( `${ className.elQuestionTypeNew }` );

	const questionTitle = elQuestionTitleInput.value.trim();
	const questionType = elQuestionTypeNew.value;

	if ( questionTitle && questionType ) {
		elBtnAddQuestion.classList.add( 'active' );
	} else {
		elBtnAddQuestion.classList.remove( 'active' );
	}
};

// Remove question
const removeQuestion = ( e, target ) => {
	const elBtnRemoveQuestion = target.closest(
		`${ className.elBtnRemoveQuestion }`
	);
	if ( ! elBtnRemoveQuestion ) {
		return;
	}

	const elQuestionItem = elBtnRemoveQuestion.closest(
		`${ className.elQuestionItem }`
	);
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

			// Call ajax to delete item from section
			const callBack = {
				success: ( response ) => {
					const { message, status } = response;

					showToast( message, status );

					if ( status === 'success' ) {
						elQuestionItem.remove();
						updateCountItems();
					}
				},
				error: ( error ) => {
					showToast( error, 'error' );
				},
				completed: () => {
					lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
				},
			};

			const dataSend = {
				quiz_id: quizID,
				action: 'remove_question_from_quiz',
				question_id: questionId,
				args: {
					id_url: idUrlHandle,
				},
			};
			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
	} );
};

// Update item title
const updateQuestionTitle = ( e, target ) => {
	let canHandle = false;

	if ( target.closest( `${ className.elBtnUpdateQuestionTitle }` ) ) {
		canHandle = true;
	} else if (
		target.closest( `${ className.elQuestionTitleInput }` ) &&
		e.key === 'Enter'
	) {
		canHandle = true;
	}

	if ( ! canHandle ) {
		return;
	}

	e.preventDefault();

	const elQuestionItem = target.closest( `${ className.elQuestionItem }` );
	if ( ! elQuestionItem ) {
		return;
	}

	const elQuestionTitleInput = elQuestionItem.querySelector(
		`${ className.elQuestionTitleInput }`
	);
	if ( ! elQuestionTitleInput ) {
		return;
	}

	const questionId = elQuestionItem.dataset.questionId;
	const questionTitleValue = elQuestionTitleInput.value.trim();
	const titleOld = elQuestionTitleInput.dataset.old;
	const message = elQuestionTitleInput.dataset.messEmptyTitle;
	if ( questionTitleValue.length === 0 ) {
		showToast( message, 'error' );
		return;
	}

	if ( questionTitleValue === titleOld ) {
		return;
	}

	// Un-focus input item title
	elQuestionTitleInput.blur();
	// show loading
	lpUtils.lpSetLoadingEl( elQuestionItem, 1 );
	// Call ajax to update item title
	const callBack = {
		success: ( response ) => {
			const { message, status } = response;

			if ( status === 'success' ) {
				elQuestionTitleInput.dataset.old = questionTitleValue; // Update value input
			} else {
				elQuestionTitleInput.value = titleOld;
			}

			showToast( message, status );
		},
		error: ( error ) => {
			showToast( error, 'error' );
		},
		completed: () => {
			lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
			elQuestionItem.classList.remove( 'editing' ); // Remove editing class
		},
	};

	const dataSend = {
		quiz_id: quizID,
		action: 'update_question',
		question_id: questionId,
		question_title: questionTitleValue,
		args: {
			id_url: idUrlHandle,
		},
	};
	window.lpAJAXG.fetchAJAX( dataSend, callBack );
};

// Typing in description input
const changeTitleQuestion = ( e, target ) => {
	const elQuestionTitleInput = target.closest( `${ className.elQuestionTitleInput }` );
	if ( ! elQuestionTitleInput ) {
		return;
	}

	const elQuestionItem = elQuestionTitleInput.closest( `${ className.elQuestionItem }` );
	const titleValue = elQuestionTitleInput.value.trim();
	const titleValueOld = elQuestionTitleInput.dataset.old || '';

	if ( titleValue === titleValueOld ) {
		elQuestionItem.classList.remove( 'editing' );
	} else {
		elQuestionItem.classList.add( 'editing' );
	}
};

// Cancel updating section description
const cancelChangeTitleQuestion = ( e, target ) => {
	const elBtnCancelUpdateQuestionTitle = target.closest( `${ className.elBtnCancelUpdateQuestionTitle }` );
	if ( ! elBtnCancelUpdateQuestionTitle ) {
		return;
	}

	const elQuestionItem = elBtnCancelUpdateQuestionTitle.closest( `${ className.elQuestionItem }` );
	const elQuestionTitleInput = elQuestionItem.querySelector( `${ className.elQuestionTitleInput }` );
	elQuestionTitleInput.value = elQuestionTitleInput.dataset.old || ''; // Reset to old value
	elQuestionItem.classList.remove( 'editing' ); // Remove editing class
};

// Sortable questions
const sortAbleQuestion = () => {
	let isUpdateSectionPosition = 0;
	let timeout;

	new Sortable( elEditListQuestions, {
		handle: '.drag',
		animation: 150,
		onEnd: ( evt ) => {
			const elQuestionItem = evt.item;
			if ( ! isUpdateSectionPosition ) {
				// No change in section position, do nothing
				return;
			}

			clearTimeout( timeout );
			timeout = setTimeout( () => {
				lpUtils.lpSetLoadingEl( elQuestionItem, 1 );

				const questionIds = [];
				const elQuestionItems = elEditListQuestions.querySelectorAll( `${ className.elQuestionItem }:not(.clone)` );
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
							showToast( message, status );
							editQuestion.initTinyMCE();
						} else {
							throw `Error: ${ message }`;
						}
					},
					error: ( error ) => {
						showToast( error, 'error' );
					},
					completed: () => {
						lpUtils.lpSetLoadingEl( elQuestionItem, 0 );
						isUpdateSectionPosition = 0; // Reset position update flag
					},
				};

				const dataSend = {
					quiz_id: quizID,
					action: 'update_questions_position',
					question_ids: questionIds,
					args: {
						id_url: idUrlHandle,
					},
				};
				window.lpAJAXG.fetchAJAX( dataSend, callBack );
			}, 1000 );
		},
		onMove: ( evt ) => {
			clearTimeout( timeout );
		},
		onUpdate: ( evt ) => {
			isUpdateSectionPosition = 1;
		},
	} );
};

// Events
document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	toggleQuestionAll( e, target );
	lpUtils.toggleCollapse( e, target, className.elQuestionToggle, [], checkAllQuestionsCollapsed );
	addQuestion( e, target );
	removeQuestion( e, target );
	updateQuestionTitle( e, target );
	cancelChangeTitleQuestion( e, target );

	// Events for Popup Select Items to add
	const callBackPopupSelectItems = {
		willOpen: ( itemsSelectedData ) => {
			// Trigger tab lesson to be active and call AJAX load items
			const elLPTarget = elPopupSelectItems.querySelector(
				`${ className.LPTarget }`
			);

			const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
			dataSend.args.paged = 1;
			dataSend.args.item_selecting = itemsSelectedData || [];
			window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

			// Show loading
			window.lpAJAXG.showHideLoading( elLPTarget, 1 );
			// End

			window.lpAJAXG.fetchAJAX( dataSend, {
				success: ( response ) => {
					const { data } = response;
					elLPTarget.innerHTML = data.content || '';
				},
				error: ( error ) => {
					showToast( error, 'error' );
				},
				completed: () => {
					window.lpAJAXG.showHideLoading( elLPTarget, 0 );
					// Show button add if there are items selected
					lpPopupSelectItemToAdd.watchItemsSelectedDataChange( elPopupSelectItems );
				},
			} );
		},
	};
	lpPopupSelectItemToAdd.showPopupItemsToSelect( e, target, elPopupSelectItems, callBackPopupSelectItems );
	lpPopupSelectItemToAdd.selectItemsFromList( e, target, elPopupSelectItems );
	lpPopupSelectItemToAdd.addItemsSelectedToSection( e, target, elPopupSelectItems, ( itemsSelected ) => {
		//console.log( 'Items selected to add:', itemsSelected );
		const questionIds = [];
		itemsSelected.forEach( ( item ) => {
			const elQuestionItemClone = elEditQuizWrap.querySelector( `${ className.elQuestionItem }.clone` );
			if ( ! elQuestionItemClone ) {
				return;
			}

			questionIds.push( item.id );
			const elQuestionItemNew = elQuestionItemClone.cloneNode( true );
			const elQuestionItemTitleInput = elQuestionItemNew.querySelector( `${ className.elQuestionTitleInput }` );
			const elQuestionTypeLabel = elQuestionItemNew.querySelector( `${ className.elQuestionTypeLabel }` );
			elQuestionItemNew.classList.remove( 'clone' );
			elQuestionItemNew.dataset.questionId = item.id;
			elQuestionItemTitleInput.value = item.titleSelected;

			lpUtils.lpSetLoadingEl( elQuestionItemNew, 1 );
			lpUtils.lpShowHideEl( elQuestionItemNew, 1 );
			elQuestionItemClone.insertAdjacentElement( 'beforebegin', elQuestionItemNew );
			lpUtils.lpSetLoadingEl( elQuestionItemNew, 1 );
		} );

		// Ajax to add items to quiz
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				if ( status === 'success' ) {
					showToast( message, status );

					const { html_edit_question } = data;
					if ( html_edit_question ) {
						Object.entries( html_edit_question ).forEach( ( [ question_id, item_html ] ) => {
							const elQuestionItemNew = elEditQuizWrap.querySelector( `${ className.elQuestionItem }[data-question-id="${ question_id }"]` );
							elQuestionItemNew.outerHTML = item_html;
						} );
					}
					updateCountItems();
					editQuestion.initTinyMCE();
				} else {
					throw `Error: ${ message }`;
				}
			},
			error: ( error ) => {
				showToast( error, 'error' );
			},
			completed: () => {
			},
		};

		const dataSend = {
			quiz_id: quizID,
			action: 'add_questions_to_quiz',
			question_ids: questionIds,
			args: {
				id_url: idUrlHandle,
			},
		};
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	} );
	lpPopupSelectItemToAdd.showItemsSelected( e, target, elPopupSelectItems );
	lpPopupSelectItemToAdd.backToSelectItems( e, target, elPopupSelectItems );
	lpPopupSelectItemToAdd.removeItemSelected( e, target, elPopupSelectItems );
	// End events for Popup Select Items to add
} );
// Event keydown
document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;
	// Event enter
	if ( e.key === 'Enter' ) {
		// Add new section
		updateQuestionTitle( e, target );
		addQuestion( e, target );
	}
} );
// Event keyup
document.addEventListener( 'keyup', ( e ) => {
	const target = e.target;
	changeTitleQuestion( e, target );
	checkCanAddQuestion( e, target );
	lpPopupSelectItemToAdd.searchTitleItemToSelect( e, target, elPopupSelectItems );
} );
// Event change
document.addEventListener( 'change', ( e ) => {
	const target = e.target;
	checkCanAddQuestion( e, target );
} );

// Element root ready.
lpUtils.lpOnElementReady(
	`${ className.elEditQuizWrap }`,
	( elEditQuizWrapFound ) => {
		elEditQuizWrap = elEditQuizWrapFound;
		elEditListQuestions = elEditQuizWrap.querySelector(
			`${ className.elEditListQuestions }`
		);
		const elLPTarget = elEditQuizWrap.closest( `${ className.LPTarget }` );
		const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		quizID = dataSend.args.quiz_id;

		const elPopupItemsToSelectClone = elEditQuizWrap.querySelector(
			`${ className.elPopupItemsToSelectClone }`
		);
		elPopupSelectItems = elPopupItemsToSelectClone.cloneNode( true );
		elPopupSelectItems.classList.remove( 'clone', 'lp-hidden' );

		sortAbleQuestion();
		editQuestion.events();
		editQuestion.initTinyMCE();
		const elQuestionEditMains = elEditQuizWrapFound.querySelectorAll( `${ className.elQuestionEditMain }` );
		elQuestionEditMains.forEach( ( elQuestionEditMain ) => {
			editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
		} );
	}
);
