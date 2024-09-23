import { getHtmlQuestionApi, changeQuestionTypeApi, changeTitleAnswerApi, addNewAnswerApi, changeCorrectAnswerApi, removeAnswerApi, sortAnswerApi } from './apiRequests';
import { Sortable } from 'sortablejs';

const getParentElByTagName = ( tag, el ) => {
	const newEl = el.parentElement;

	if ( newEl.tagName.toLowerCase() === tag ) {
		return newEl;
	}

	if ( newEl.tagName.toLowerCase() === 'html' ) {
		return false;
	}

	return getParentElByTagName( tag, newEl );
};

const getQuestionId = ( el ) => {
	let questionId = 0;
	if ( ! el ) {
		const urlParams = new URLSearchParams( window.location.search );
		questionId = urlParams.get( 'post' );
		return questionId;
	}

	questionId = el.getAttribute( 'data-question-id' );
	return questionId;
};

const getAnswerId = ( el ) => {
	if ( ! el ) {
		return;
	}

	const answerEl = getParentElByTagName( 'tr', el );
	const answerId = answerEl?.dataset?.answerId ?? 0;
	return answerId;
};

const checkHiddenRemoveAnswer = ( questionEditEl ) => {
	const answerEls = Array.from( questionEditEl.querySelectorAll( '.answer-option' ) );
	if ( ! answerEls.length ) {
		return;
	}

	if ( answerEls.length < 3 ) {
		answerEls.forEach( ( answerEl ) => {
			const removeEl = answerEl.querySelector( '.lp-btn-remove' );
			if ( ! removeEl ) {
				return;
			}
			removeEl.style.display = 'none';
		} );
	} else {
		const checkedInputs = questionEditEl.querySelectorAll( 'input[type="checkbox"]:checked, input[type="radio"]:checked' );
		answerEls.forEach( ( answerEl ) => {
			const removeEl = answerEl.querySelector( '.lp-btn-remove' );
			if ( ! removeEl ) {
				return;
			}
			const checkEl = answerEl.querySelector( '.lp-answer-check input' );

			if ( checkEl?.checked && checkedInputs?.length < 2 ) {
				removeEl.style.display = 'none';
			} else {
				removeEl.style.display = 'inline-block';
			}
		} );
	}
};

const renderQuestion = async ( questionEditEl, questionId ) => {
	await getHtmlQuestionApi( questionEditEl, questionId );
};

const changeQuestionType = ( questionEditEl ) => {
	if ( ! questionEditEl ) {
		return;
	}

	const typeEls = Array.from( questionEditEl.querySelectorAll( '.question-types li' ) );

	if ( ! typeEls.length ) {
		return;
	}

	const contentCurrentEl = questionEditEl.querySelector( '.question-types a' );
	typeEls.forEach( ( typeEl ) => {
		typeEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			const type = typeEl.dataset.type ?? '';
			const contentCurrentType = contentCurrentEl?.dataset?.type ?? '';

			if ( contentCurrentType === type ) {
				return;
			}

			const content = typeEl.querySelector( 'a' )?.innerText ?? '';
			if ( content && contentCurrentEl ) {
				contentCurrentEl.innerText = content;
				contentCurrentEl.dataset.type = type;
			}

			if ( type ) {
				const questionId = getQuestionId( questionEditEl );
				const data = {
					questionId,
					type,
				};
				changeQuestionTypeApi( data, questionEditEl, questionId );
			}
		} );
	} );
};

const changeTitleAnswer = ( questionEditEl ) => {
	const answerOptionEls = Array.from( questionEditEl.querySelectorAll( '.answer-option' ) );
	if ( ! answerOptionEls.length ) {
		return;
	}

	answerOptionEls.forEach( ( answerOptionEl ) => {
		const inputTitleEl = answerOptionEl.querySelector( '.answer-text input' );
		if ( ! inputTitleEl ) {
			return;
		}

		const questionId = getQuestionId( questionEditEl );
		const valueChecked = answerOptionEl.value ?? '';
		let previousValue = inputTitleEl.value;
		const checkedEl = answerOptionEl.querySelector( '.lp-answer-check input' );
		const checked = checkedEl?.checked ? 'yes' : 'no';
		const answerId = answerOptionEl?.dataset?.answerId;
		inputTitleEl.addEventListener( 'keydown', function( event ) {
			if ( event.key === 'Enter' ) {
				event.preventDefault();
				const currentValue = inputTitleEl.value;
				if ( previousValue !== currentValue && currentValue !== '' ) {
					previousValue = currentValue;
					const data = {
						questionId,
						answer: {
							title: currentValue,
							value: valueChecked,
							is_true: checked,
							question_answer_id: answerId,
						},
					};
					changeTitleAnswerApi( data, questionEditEl );
				}
			}
		} );

		inputTitleEl.addEventListener( 'blur', () => {
			const currentValue = inputTitleEl.value;
			if ( previousValue !== currentValue && currentValue !== '' ) {
				previousValue = currentValue;
				const data = {
					questionId,
					answer: {
						title: currentValue,
						value: valueChecked,
						is_true: checked,
						question_answer_id: answerId,
					},
				};
				changeTitleAnswerApi( data, questionEditEl );
			}
		} );
	} );
};

const changeCorrectAnswer = ( questionEditEl ) => {
	if ( ! questionEditEl ) {
		return;
	}

	const answerOptionEls = Array.from( questionEditEl.querySelectorAll( '.answer-option' ) );

	if ( ! answerOptionEls.length ) {
		return;
	}
	const questionId = getQuestionId( questionEditEl );
	answerOptionEls.forEach( ( answerOptionEl ) => {
		const answerCorrectEl = answerOptionEl.querySelector( '.lp-answer-check input' );
		answerCorrectEl.addEventListener( 'click', () => {
			const checked = answerCorrectEl.checked ? 'yes' : 'no';
			const answerId = getAnswerId( answerCorrectEl );
			const data = {
				questionId,
				answer: {
					question_answer_id: answerId,
					is_true: checked,
				},
			};
			changeCorrectAnswerApi( data, questionEditEl );
			checkHiddenRemoveAnswer( questionEditEl );
		} );
	} );
};

const addNewAnswer = ( questionEditEl ) => {
	if ( ! questionEditEl ) {
		return;
	}
	const addNewAnswerEl = questionEditEl.querySelector( '.add-question-option-button' );

	if ( ! addNewAnswerEl ) {
		return;
	}

	addNewAnswerEl.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		const questionId = getQuestionId( questionEditEl );
		const data = {
			questionId,
		};

		addNewAnswerApi( data, questionEditEl );
	} );
};

const deleteAnswer = ( questionEditEl ) => {
	if ( ! questionEditEl ) {
		return;
	}

	const deleteEls = Array.from( questionEditEl.querySelectorAll( '.lp-btn-remove.remove-answer' ) );
	if ( deleteEls.length ) {
		deleteEls.forEach( ( deleteEl ) => {
			deleteEl.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const answerEl = getParentElByTagName( 'tr', deleteEl );
				const questionId = getQuestionId( questionEditEl );
				const answerId = getAnswerId( deleteEl );
				const data = {
					questionId,
					answerId,
				};
				removeAnswerApi( data, questionEditEl );
				if ( answerEl ) {
					answerEl.remove();
				}
				checkHiddenRemoveAnswer( questionEditEl );
			} );
		} );
	}
};

const sortableAnswer = ( questionEditEl ) => {
	if ( ! questionEditEl ) {
		return;
	}

	const sortableEl = questionEditEl.querySelector( '.ui-sortable' );
	if ( ! sortableEl ) {
		return;
	}

	const questionId = getQuestionId( questionEditEl );

	new Sortable( sortableEl, {
		selectedClass: 'lp-selected',
		handle: '.lp-sortable-handle',
		axis: 'y',
		animation: 150,
		onUpdate( evt ) {
			const answerEls = Array.from( sortableEl.querySelectorAll( ':scope > .answer-option' ) );
			const answerIds = answerEls.map( ( answerEl ) => {
				return answerEl.dataset.answerId ?? null;
			} );

			const data = {
				questionId,
				answerIds,
			};
			sortAnswerApi( data, questionEditEl );
		},
	} );
};

const singleQuestion = async ( questionEditEl, questionId ) => {
	await renderQuestion( questionEditEl, questionId );
	changeQuestionType( questionEditEl );
};

export { renderQuestion, changeQuestionType, changeTitleAnswer, changeCorrectAnswer, addNewAnswer, deleteAnswer, sortableAnswer, getQuestionId, checkHiddenRemoveAnswer, singleQuestion };
