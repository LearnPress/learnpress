import { handleEventPopup, popupSelectItem } from './quiz-popup';
import { getQuizId, handleActionQuestion, sortableQuestion } from './eventHandlers';
import { addNewQuestionApi } from './apiRequests';

document.addEventListener( 'DOMContentLoaded', () => {
	const quizEditorEl = document.querySelector( '#admin-editor-lp_quiz-refactor' );
	if ( ! quizEditorEl ) {
		return;
	}

	const questionEls = Array.from( quizEditorEl.querySelectorAll( '.question-item' ) );

	if ( ! questionEls.length ) {
		return;
	}

	const quizId = getQuizId();
	const collapseQuestionsEl = quizEditorEl.querySelector( '.collapse-list-questions' );

	if ( collapseQuestionsEl ) {
		const questionEls = Array.from( quizEditorEl.querySelectorAll( '.ui-sortable > .question-item' ) );
		collapseQuestionsEl.addEventListener( 'click', () => {
			if ( collapseQuestionsEl.classList.contains( 'dashicons-arrow-down' ) ) {
				collapseQuestionsEl.classList.remove( 'dashicons-arrow-down' );
				collapseQuestionsEl.classList.add( 'dashicons-arrow-up' );
				if ( questionEls.length ) {
					questionEls.forEach( ( questionEl ) => {
						const toggleOpenEl = questionEl.querySelector( '.lp-btn-toggle' );
						const questionSettingEl = questionEl.querySelector( '.question-settings' );
						if ( ! toggleOpenEl || ! questionSettingEl ) {
							return;
						}

						toggleOpenEl.classList.remove( 'close' );
						toggleOpenEl.classList.add( 'open' );
						questionSettingEl.classList.add( 'table-row' );
						questionSettingEl.classList.remove( 'hide-if-js' );
					} );
				}
			} else {
				collapseQuestionsEl.classList.add( 'dashicons-arrow-down' );
				collapseQuestionsEl.classList.remove( 'dashicons-arrow-up' );
				questionEls.forEach( ( questionEl ) => {
					const toggleOpenEl = questionEl.querySelector( '.lp-btn-toggle' );
					const questionSettingEl = questionEl.querySelector( '.question-settings' );
					if ( ! toggleOpenEl || ! questionSettingEl ) {
						return;
					}

					toggleOpenEl.classList.add( 'close' );
					questionSettingEl.classList.add( 'hide-if-js' );
					toggleOpenEl.classList.remove( 'open' );
					questionSettingEl.classList.remove( 'table-row' );
				} );
			}
		} );
	}

	const addNewQuestionEl = quizEditorEl.querySelector( '.add-new-question' );
	if ( addNewQuestionEl ) {
		const inputAddNewEl = addNewQuestionEl.querySelector( '.title input' );
		const addNewBtnEl = addNewQuestionEl.querySelector( '.add-new button' );
		if ( inputAddNewEl && addNewBtnEl ) {
			inputAddNewEl.addEventListener( 'keyup', ( e ) => {
				const currentValue = inputAddNewEl.value;
				if ( currentValue === '' ) {
					addNewBtnEl.disabled = true;
					return;
				}
				addNewBtnEl.disabled = false;
			} );

			const questionTypeEls = Array.from( addNewQuestionEl.querySelectorAll( '.question-types li a' ) );
			if ( questionTypeEls.length ) {
				questionTypeEls.forEach( ( questionTypeEl ) => {
					questionTypeEl.addEventListener( 'click', ( e ) => {
						e.preventDefault();
						if ( ! inputAddNewEl.value ) {
							addNewBtnEl.disabled = true;
							return;
						}

						const questionType = questionTypeEl.dataset.type;
						const data = {
							quizId,
							questionTitle: inputAddNewEl.value,
							questionType,
						};
						addNewQuestionApi( data, quizEditorEl );
						inputAddNewEl.value = '';
						addNewBtnEl.disabled = true;
					} );
				} );
			}
		}

		const selectItemEl = addNewQuestionEl.querySelector( '.select-item button' );
		if ( selectItemEl ) {
			selectItemEl.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				popupSelectItem();
			} );
		}
	}
	questionEls.forEach( ( questionEl ) => {
		handleActionQuestion( questionEl, quizEditorEl );
	} );

	sortableQuestion( quizEditorEl );
	handleEventPopup();
} );
