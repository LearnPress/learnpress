import { dispatch, select, apiFetch } from '@learnpress/data-controls';
import { select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';

function _dispatch() {
	const args = [].slice.call( arguments, 2 );
	const d = wpDispatch( arguments[ 0 ] );
	const f = arguments[ 1 ];
	d[ f ]( ...args );
}

const { camelCaseDashObjectKeys, Hook } = LP;
/**
 * Set user data for app.
 *
 * @param  key
 * @param  data
 */
export function setQuizData( key, data ) {
	if ( typeof key === 'string' ) {
		data = { [ key ]: data };
	} else {
		data = key;
	}

	return {
		type: 'SET_QUIZ_DATA',
		data: camelCaseDashObjectKeys( data ),
	};
}

/**
 * Set question will display.
 *
 * @param  questionId
 */
export function setCurrentQuestion( questionId ) {
	return {
		type: 'SET_CURRENT_QUESTION',
		questionId,
	};
}

export function setCurrentPage( currentPage ) {
	return {
		type: 'SET_CURRENT_PAGE',
		currentPage,
	};
}

export function __requestBeforeStartQuiz( quizId, courseId, userId ) {
	return {
		type: 'BEFORE_START_QUIZ',
	};
}

export function __requestStartQuizSuccess( results, quizId, courseId, userId ) {
	Hook.doAction( 'quiz-started', results, quizId, courseId, userId );
	return {
		type: 'START_QUIZ_SUCCESS',
		quizId,
		courseId,
		userId,
		results,
	};
}

/**
 * Request to api for starting a quiz.
 */
const startQuiz = function*() {
	const { itemId, courseId } = wpSelect( 'learnpress/quiz' ).getDefaultRestArgs();

	const doStart = Hook.applyFilters( 'before-start-quiz', true, itemId, courseId );

	if ( true !== doStart ) {
		return;
	}

	let response = yield apiFetch( {
		path: 'lp/v1/users/start-quiz',
		method: 'POST',
		data: {
			item_id: itemId,
			course_id: courseId,
		},
	} );

	const btnStart = document.querySelector( '.lp-button.start' );

	if ( response.status !== 'error' ) {
		response = Hook.applyFilters( 'request-start-quiz-response', response, itemId, courseId );
		const { results } = response;
		const { duration, status, question_ids, questions } = results;

		// No require enroll
		if ( lpQuizSettings.checkNorequizenroll === 1 ) {
			const keyQuizOff = 'quiz_off_' + lpQuizSettings.id;
			window.localStorage.removeItem( keyQuizOff );
			const quizDataOff = {
				endTime: ( Date.now() + ( duration * 1000 ) ),
				status,
				question_ids,
				questions,
			};

			window.localStorage.setItem( keyQuizOff, JSON.stringify( quizDataOff ) );

			// Set Retake quiz
			const keyQuizOffRetaken = 'quiz_off_retaken_' + lpQuizSettings.id;
			let quizOffRetaken = window.localStorage.getItem( keyQuizOffRetaken );

			if ( null === quizOffRetaken ) {
				quizOffRetaken = 0;
			} else {
				quizOffRetaken++;
			}

			window.localStorage.setItem( keyQuizOffRetaken, quizOffRetaken );
			// End
		}

		// Reload when start/retake quiz
		window.localStorage.removeItem( 'LP' );
		window.location.reload();

		//yield _dispatch( 'learnpress/quiz', '__requestStartQuizSuccess', camelCaseDashObjectKeys( response ), itemId, courseId );
	} else {
		const elButtons = document.querySelector( '.quiz-buttons' );
		const message = `<div class="learn-press-message error">${ response.message }</div>`;
		elButtons.insertAdjacentHTML( 'afterend', message );
		btnStart.classList.remove( 'loading' );
	}
};

export { startQuiz };

export function __requestSubmitQuiz() {
	return {
		type: 'SUBMIT_QUIZ',
	};
}

export function __requestSubmitQuizSuccess( results, quizId, courseId ) {
	Hook.doAction( 'quiz-submitted', results, quizId, courseId );

	return {
		type: 'SUBMIT_QUIZ_SUCCESS',
		results,
	};
}

export function* submitQuiz() {
	const {
		getDefaultRestArgs,
		getQuestionsSelectedAnswers,
	} = wpSelect( 'learnpress/quiz' );

	const {
		itemId,
		courseId,
	} = getDefaultRestArgs();

	const doSubmit = Hook.applyFilters( 'before-submit-quiz', true );

	if ( true !== doSubmit ) {
		return;
	}

	const answered = getQuestionsSelectedAnswers();

	if ( lpQuizSettings.checkNorequizenroll === 1 ) {
		const keyQuizOff = `quiz_off_${ lpQuizSettings.id }`;
		const quizDataOffStr = window.localStorage.getItem( keyQuizOff );
		const quizDataOff = JSON.parse( quizDataOffStr );
		const keyAnswer = `LP_Quiz_${ itemId }_Answered`;
		const answerDataStr = localStorage.getItem( keyAnswer );

		if ( null !== answerDataStr ) {
			const data = JSON.parse( answerDataStr );

			for ( const [ k, v ] of Object.entries( data ) ) {
				answered[ k ] = v.answered;
			}
		}

		// Added questions not answered
		quizDataOff.question_ids.forEach( ( question_id ) => {
			if ( ! answered[ question_id ] ) {
				answered[ question_id ] = '';
			}
		} );
	}

	// Get time spend did quiz - tungnx
	let timeSpend = 0;
	const elTimeSpend = document.querySelector( 'input[name=lp-quiz-time-spend]' );

	if ( elTimeSpend ) {
		timeSpend = elTimeSpend.value;
	}
	// End

	let response = yield apiFetch( {
		path: 'lp/v1/users/submit-quiz',
		method: 'POST',
		data: {
			item_id: itemId,
			course_id: courseId,
			answered,
			time_spend: timeSpend,
		},
	} );

	response = Hook.applyFilters( 'request-submit-quiz-response', response, itemId, courseId );

	if ( response.status === 'success' ) {
		if ( lpQuizSettings.checkNorequizenroll === 1 ) {
			const keyQuizOff = 'quiz_off_' + lpQuizSettings.id;
			const quizDataOffStr = window.localStorage.getItem( keyQuizOff );
			if ( null !== quizDataOffStr ) {
				const quizDataOff = JSON.parse( quizDataOffStr );

				quizDataOff.status = response.results.status;
				quizDataOff.results = response.results.results;

				window.localStorage.setItem( keyQuizOff, JSON.stringify( quizDataOff ) );
				window.localStorage.removeItem( 'LP_Quiz_' + lpQuizSettings.id + '_Answered' );
			}
		}

		yield _dispatch( 'learnpress/quiz', '__requestSubmitQuizSuccess', camelCaseDashObjectKeys( response.results ), itemId, courseId );
	}
}

export function updateUserQuestionAnswers( questionId, answers, quizId, courseId = 0, userId = 0 ) {
	return {
		type: 'UPDATE_USER_QUESTION_ANSWERS',
		questionId,
		answers,
	};
}

export function __requestShowHintSuccess( id, showHint ) {
	return {
		type: 'SET_QUESTION_HINT',
		questionId: id,
		showHint,
	};
}

export function* showHint( id, showHint ) {
	yield _dispatch( 'learnpress/quiz', '__requestShowHintSuccess', id, showHint );
}

export function __requestCheckAnswerSuccess( id, result ) {
	return {
		type: 'CHECK_ANSWER',
		questionId: id,
		...result,
	};
}

export function* checkAnswer( id ) {
	const {
		getDefaultRestArgs,
		getQuestionAnswered,
	} = wpSelect( 'learnpress/quiz' );

	const {
		itemId,
		courseId,
	} = getDefaultRestArgs();

	const result = yield apiFetch( {
		path: 'lp/v1/users/check-answer',
		method: 'POST',
		data: {
			item_id: itemId,
			course_id: courseId,
			question_id: id,
			answered: getQuestionAnswered( id ) || '',
		},
	} );

	if ( result.status === 'success' ) {
		// No require enroll
		if ( lpQuizSettings.checkNorequizenroll === 1 ) {
			const keyQuizOff = 'quiz_off_' + lpQuizSettings.id;
			const quizDataOffStr = window.localStorage.getItem( keyQuizOff );

			if ( null !== quizDataOffStr ) {
				const quizDataOff = JSON.parse( quizDataOffStr );

				const questionOptions = result.options;

				if ( undefined === quizDataOff.checked_questions ) {
					quizDataOff.checked_questions = [];
					quizDataOff.checked_questions.push( id );
				} else if ( -1 === quizDataOff.checked_questions.indexOf( id ) ) {
					quizDataOff.checked_questions.push( id );
				}

				if ( undefined === quizDataOff.question_options ) {
					quizDataOff.question_options = {};
					quizDataOff.question_options[ id ] = questionOptions;
				} else if ( undefined === quizDataOff.question_options[ id ] ) {
					quizDataOff.question_options[ id ] = questionOptions;
				}

				window.localStorage.setItem( keyQuizOff, JSON.stringify( quizDataOff ) );

				//console.log( quizDataOff );
			}
		}

		yield _dispatch( 'learnpress/quiz', '__requestCheckAnswerSuccess', id, camelCaseDashObjectKeys( result ) );
	}
}

export function markQuestionRendered( questionId ) {
	return {
		type: 'MARK_QUESTION_RENDERED',
		questionId,
	};
}

export function setQuizMode( mode ) {
	return {
		type: 'SET_QUIZ_MODE',
		mode,
	};
}

export function sendKey( keyPressed ) {
	setTimeout( () => {
		_dispatch( 'learnpress/quiz', 'sendKey', '' );
	}, 300 );

	return {
		type: 'SEND_KEY',
		keyPressed,
	};
}
