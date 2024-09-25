import { Sortable } from 'sortablejs';
import { getQuestionId, singleQuestion } from '../question/eventHandlers';
import { changeQuestionTitleApi, removeQuestionApi, deleteQuestionApi, duplicateQuestionApi, addNewQuestionApi, sortQuestionApi } from './apiRequests';
import { popupSelectItem } from './popupQuiz';

const getQuizId = () => {
	const urlParams = new URLSearchParams( window.location.search );
	const quizId = urlParams.get( 'post' );
	return quizId;
};

const resetQuestionOrder = ( el ) => {
	const questionItemEls = Array.from( el.querySelectorAll( '.ui-sortable > .question-item' ) );
	if ( ! questionItemEls.length ) {
		return;
	}

	questionItemEls.forEach( ( questionItemEl, index ) => {
		const oderEl = questionItemEl.querySelector( '.question-actions .order' );
		if ( ! oderEl ) {
			return;
		}
		oderEl.innerText = index + 1;
	} );
};

const handleActionQuestion = ( questionEl, quizEditorEl ) => {
	const quizId = getQuizId();
	const removeEl = questionEl.querySelector( '.lp-btn-remove .remove' );
	const questionId = getQuestionId( questionEl );
	if ( removeEl ) {
		removeEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			const data = {
				quizId,
				questionId,
			};
			removeQuestionApi( data, quizEditorEl );
			questionEl.remove();
			updateTotalItem( quizEditorEl, -1 );
			resetQuestionOrder( quizEditorEl );
		} );
	}

	const deleteEl = questionEl.querySelector( '.lp-btn-remove .delete' );
	if ( deleteEl ) {
		deleteEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			const textConfirmed = deleteEl.dataset.confirmed ?? '';
			const isConfirmed = confirm( textConfirmed );
			if ( ! isConfirmed ) {
				return;
			}
			const data = {
				quizId,
				questionId,
			};
			deleteQuestionApi( data, quizEditorEl );
			questionEl.remove();
			updateTotalItem( quizEditorEl, -1 );
			resetQuestionOrder( quizEditorEl );
		} );
	}

	const duplicateEl = questionEl.querySelector( '.lp-btn-duplicate' );
	if ( duplicateEl ) {
		duplicateEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			const data = {
				quizId,
				questionId,
			};
			duplicateQuestionApi( data, quizEditorEl );
		} );
	}

	const toggleOpenEl = questionEl.querySelector( '.lp-btn-toggle' );
	const questionSettingEl = questionEl.querySelector( '.question-settings' );
	if ( toggleOpenEl && questionSettingEl ) {
		toggleOpenEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			if ( toggleOpenEl.classList.contains( 'close' ) ) {
				toggleOpenEl.classList.remove( 'close' );
				toggleOpenEl.classList.add( 'open' );
				questionSettingEl.classList.add( 'table-row' );
				questionSettingEl.classList.remove( 'hide-if-js' );
			} else {
				toggleOpenEl.classList.add( 'close' );
				toggleOpenEl.classList.remove( 'open' );
				questionSettingEl.classList.remove( 'table-row' );
				questionSettingEl.classList.add( 'hide-if-js' );
			}

			const collapseQuestionsEl = quizEditorEl.querySelector( '.collapse-list-questions' );
			if ( ! collapseQuestionsEl ) {
				return;
			}
			const notAllClose = quizEditorEl.querySelector( '.question-item > .question-settings.table-row' );
			if ( notAllClose ) {
				collapseQuestionsEl.classList.remove( 'dashicons-arrow-down' );
				collapseQuestionsEl.classList.add( 'dashicons-arrow-up' );
			} else {
				collapseQuestionsEl.classList.remove( 'dashicons-arrow-up' );
				collapseQuestionsEl.classList.add( 'dashicons-arrow-down' );
			}
		} );
	}

	const titleEl = questionEl.querySelector( 'input.question-title' );
	if ( titleEl ) {
		let previousValue = titleEl.value;
		titleEl.addEventListener( 'blur', ( e ) => {
			const currentValue = titleEl.value;

			if ( previousValue === currentValue && currentValue !== '' ) {
				return;
			}
			previousValue = currentValue;
			const data = {
				title: currentValue,
				questionId,
			};

			changeQuestionTitleApi( data, quizEditorEl );
		} );

		titleEl.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
				const currentValue = titleEl.value;
				if ( previousValue !== currentValue && currentValue !== '' ) {
					previousValue = currentValue;
					const data = {
						title: currentValue,
						questionId,
					};

					changeQuestionTitleApi( data, quizEditorEl );
				}
			}
		} );
	}
};

const renderQuestion = ( html, el ) => {
	if ( ! html || ! el ) {
		return;
	}

	const sortableEl = el.querySelector( '.lp-list-questions .ui-sortable' );
	if ( ! sortableEl ) {
		return;
	}

	sortableEl.insertAdjacentHTML( 'beforeend', html );
	const newElement = sortableEl.lastElementChild;
	resetQuestionOrder( el );
	handleActionQuestion( newElement, el );
	const questionId = getQuestionId( newElement );
	singleQuestion( newElement, questionId );
};

const updateTotalItem = ( el, value, elRemove ) => {
	if ( ! el ) {
		return;
	}

	let changeValue = 0;
	if ( value ) {
		changeValue = value;
	}

	if ( elRemove ) {
		const countEl = elRemove.querySelector( '.section-item-counts span' );
		const contentRemove = countEl.textContent;
		const numberRemove = parseInt( contentRemove );
		changeValue = -numberRemove;
	}

	const sectionItemCounts = el.querySelector( '.section-item-counts span' );
	const content = sectionItemCounts.textContent;
	const number = parseInt( content );
	const words = content.replace( /[0-9]/g, '' ).trim();
	const total = number + changeValue;
	const result = total + ' ' + words;
	sectionItemCounts.textContent = result;
};

const addNewQuestion = ( quizEditorEl ) => {
	if ( ! quizEditorEl ) {
		return;
	}

	const quizId = getQuizId();
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
};

const sortableQuestion = ( el ) => {
	if ( ! el ) {
		return;
	}
	const sortableEl = el.querySelector( '.ui-sortable' );
	if ( ! sortableEl ) {
		return;
	}

	const quizId = getQuizId();

	new Sortable( sortableEl, {
		selectedClass: 'lp-selected',
		handle: '.lp-sortable-handle',
		axis: 'y',
		animation: 150,
		onUpdate( evt ) {
			const questionEls = Array.from( sortableEl.querySelectorAll( ':scope > .question-item' ) );
			const questionIds = questionEls.map( ( questionEl ) => {
				return questionEl.dataset.questionId ?? null;
			} );

			const data = {
				quizId,
				questionIds,
			};
			sortQuestionApi( data, el );
			resetQuestionOrder( el );
		},
	} );
};

const collapseQuestion = ( quizEditorEl ) => {
	const collapseQuestionsEl = quizEditorEl.querySelector( '.collapse-list-questions' );
	const questionEls = Array.from( quizEditorEl.querySelectorAll( '.question-item' ) );

	if ( collapseQuestionsEl ) {
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
};
export { getQuizId, handleActionQuestion, renderQuestion, updateTotalItem, addNewQuestion, collapseQuestion, sortableQuestion };
