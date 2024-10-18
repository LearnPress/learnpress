import { getHtmlQuestionApi, changeQuestionTypeApi, changeTitleAnswerApi, addNewAnswerApi, changeCorrectAnswerApi, removeAnswerApi, sortAnswerApi, getQuestionOptionApi, changeOptionApi } from './apiRequests';
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
	let remove = true;
	if ( ! answerEls.length ) {
		return remove;
	}

	if ( answerEls.length < 3 ) {
		remove = false;

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

	return remove;
};

const checkBeforeChangeCorrect = ( els ) => {
	let changeCorrect = false;
	if ( ! els || ! els.length ) {
		return;
	}
	els.forEach( ( el ) => {
		const answerCorrectEl = el.querySelector( '.lp-answer-check input' );
		if ( answerCorrectEl.checked ) {
			changeCorrect = true;
		}
	} );
	return changeCorrect;
};

const renderQuestion = async ( questionEditEl, questionId ) => {
	await getHtmlQuestionApi( questionEditEl, questionId );
};

const renderQuestionOption = async ( questionEditEl, questionId ) => {
	await getQuestionOptionApi( questionEditEl, questionId );
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

const handleChangeQuestionOption = ( el, questionId ) => {
	const pointEl = el.querySelector( '#_lp_mark_' + questionId );
	addTinyMCEEvent( el, '_lp_hint_' + questionId, 'hint', questionId );
	addTinyMCEEvent( el, '_lp_description_' + questionId, 'description', questionId );
	addTinyMCEEvent( el, '_lp_explanation_' + questionId, 'explanation', questionId );
	if ( pointEl ) {
		let previousValue = pointEl.value;
		pointEl.addEventListener( 'keydown', function( event ) {
			if ( event.key === 'Enter' ) {
				event.preventDefault();
				const currentValue = pointEl.value;
				if ( previousValue !== currentValue ) {
					previousValue = currentValue;
					const data = {
						questionId,
						description: currentValue,
					};
					changeOptionApi( data, el );
				}
			}
		} );

		pointEl.addEventListener( 'blur', () => {
			const currentValue = pointEl.value;
			if ( previousValue !== currentValue ) {
				previousValue = currentValue;
				const data = {
					questionId,
					mark: currentValue,
				};
				changeOptionApi( data, el );
			}
		} );
	}
};

function addTinyMCEEvent( el, editorId, typeData, questionId ) {
	const textareaEl = document.getElementById( editorId );
	if ( ! textareaEl ) {
		return;
	}

	tinymce.init( {
		selector: '#' + editorId,
		plugins: 'wordpress wpautoresize lists link paste fullscreen colorpicker textcolor charmap hr',
		toolbar1: 'formatselect bold italic bullist numlist blockquote alignleft aligncenter alignright link wp_more fullscreen toolbar wp_adv',
		toolbar2: 'strikethrough hr forecolor pastetext clearformat removeformat charmap outdent indent undo redo wp_help',
		menubar: false,
		wpautop: false,
		toolbar_sticky: true,
		indent: false,
		wpeditimage_disable_captions: false,
		paste_as_text: false,
		wp_keep_scroll_position: true,
		resize: true,
		wp_adv_enabled: true,
		init_instance_callback( editor ) {
			// console.log( 'Editor initialized successfully:', editor.id );
		},
		setup( editor ) {
			editor.on( 'init', function() {
				// console.log( 'TinyMCE editor initialized:', editor.id );
			} );
			editor.on( 'change', function() {
				tinymce.triggerSave();
			} );
		},
	} );
	const content = textareaEl.value ?? '';
	const interval = setInterval( function() {
		const editor = tinymce.get( editorId );

		if ( editor ) {
			clearInterval( interval );
			editor.setContent( content );
			let previousValue = editor.getContent();
			editor.on( 'blur', function() {
				const currentValue = editor.getContent();
				if ( previousValue !== currentValue ) {
					previousValue = currentValue;
					const data = {
						questionId,
						[ typeData ]: currentValue,
					};
					changeOptionApi( data, el );
				}
			} );
		}
	}, 500 );
}

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
			if ( ! answerCorrectEl.checked && ! checkBeforeChangeCorrect( answerOptionEls ) ) {
				answerCorrectEl.checked = true;
				return;
			}

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
				if ( ! checkHiddenRemoveAnswer( questionEditEl ) ) {
					return;
				}
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

const singleQuestionOption = async ( questionOptionEl, questionId ) => {
	await renderQuestionOption( questionOptionEl, questionId );
};

export { renderQuestion, changeQuestionType, changeTitleAnswer, changeCorrectAnswer, addNewAnswer, deleteAnswer, sortableAnswer, getQuestionId, checkHiddenRemoveAnswer, singleQuestion, singleQuestionOption, handleChangeQuestionOption };
