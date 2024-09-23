import { Sortable } from 'sortablejs';
import { getQuestionId, singleQuestion } from '../question/eventHandlers';
import lplistAPI from '../../api';
import { updateStatus } from '../question/apiRequests';
import { lpFetchAPI } from '../../utils';
import { handleEventPopup, popupSelectItem } from './quiz-popup';
// import { popupSelectItem } from './quiz-popup';

const getQuizId = () => {
	const urlParams = new URLSearchParams( window.location.search );
	const quizId = urlParams.get( 'post' );
	return quizId;
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
	handleActionQuestion( newElement );
	const questionId = getQuestionId( newElement );
	singleQuestion( newElement, questionId );
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
		} );
	}

	const deleteEl = questionEl.querySelector( '.lp-btn-remove .delete' );
	if ( deleteEl ) {
		deleteEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			const data = {
				quizId,
				questionId,
			};
			deleteQuestionApi( data, quizEditorEl );
			questionEl.remove();
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

const apiRequest = ( url, method = 'POST', data, callbacks = {}, el ) => {
	if ( ! url ) {
		return;
	}

	let params = {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': lpDataAdmin.nonce,
		},
		method,
		body: JSON.stringify( data ),

	};

	if ( method === 'GET' ) {
		params = {};
	}

	updateStatus( 'loading', el );
	const { success, error, completed } = callbacks;
	lpFetchAPI( url, params, {
		success,
		error,
		completed: () => {
			if ( completed ) {
				completed();
			}
			updateStatus( 'success', el );
		},
	} );
};

const changeQuestionTitleApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiChangeQuestionTitle;
	apiRequest( URL, 'POST', data, {}, el );
};

const removeQuestionApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiRemoveQuestion;
	apiRequest( URL, 'POST', data, {}, el );
};

const deleteQuestionApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiDeleteQuestion;
	apiRequest( URL, 'POST', data, {}, el );
};

const duplicateQuestionApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiDuplicateQuestion;
	const callback = {
		success: ( response ) => {
			const htmls = response.data.html;
			if ( htmls.length ) {
				for ( const html of htmls ) {
					renderQuestion( html, el );
				}
			}
		},
	};
	apiRequest( URL, 'POST', data, callback, el );
};

const addNewQuestionApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiAddNewQuestion;
	const callback = {
		success: ( response ) => {
			const htmls = response.data.html;
			if ( htmls.length ) {
				for ( const html of htmls ) {
					renderQuestion( html, el );
				}
			}
		},
	};

	apiRequest( URL, 'POST', data, callback, el );
};

const sortQuestionApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiSortQuestion;
	apiRequest( URL, 'POST', data, {}, el );
};

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

export { handleActionQuestion, renderQuestion };
