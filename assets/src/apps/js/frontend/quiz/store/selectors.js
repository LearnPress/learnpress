import { select } from '@wordpress/data';
const { get, isArray } = lodash;

const getQuestionOptions = function getQuestionOptions( state, id ) {
	console.time( 'parseOptions' );

	const question = getQuestion( state, id );
	let options = question.options;

	options = ! isArray( options ) ? JSON.parse( CryptoJS.AES.decrypt( options.data, options.key, { format: CryptoJSAesJson } ).toString( CryptoJS.enc.Utf8 ) ) : options;
	options = ! isArray( options ) ? JSON.parse( options ) : options;

	console.timeEnd( 'parseOptions' );
	return options;
};

export { getQuestionOptions };

/**
 * Get current status of an item in course.
 *
 * @param state
 * @param itemId
 */
export function getItemStatus( state, itemId ) {
	const item = select( 'course-learner/user' ).getItemById( itemId );
	return item ? get( item, 'userSettings.status' ) : '';
}

export function getProp( state, prop, defaultValue ) {
	return state[ prop ] || defaultValue;
}

/**
 * Get quiz attempted.
 *
 * @param state
 * @param itemId
 * @return {Array}
 */
export function getQuizAttempts( state, itemId ) {
	const item = select( 'course-learner/user' ).getItemById( itemId );
	return item ? get( item, 'userSettings.attempts' ) : [];
}

/**
 * Get answers for a quiz user has did.
 *
 * @param state
 * @param itemId
 * @return {{}}
 */
export function getQuizAnswered( state, itemId ) {
	const item = select( 'course-learner/user' ).getItemById( itemId );
	return item ? get( item, 'userSettings.answered', {} ) : {};
}

/**
 * Get all questions in quiz.
 *
 * @param state
 * @return {*}
 */
export function getQuestions( state ) {
	const { userQuiz } = state;
	const questions = get( userQuiz, 'questions' );
	return questions ? Object.values( questions ) : [];
}

/**
 * Get property of store data.
 *
 * @param state - Store data
 * @param prop - Optional. NULL will return all data.
 * @return {*}
 */
export function getData( state, prop ) {
	const { userQuiz } = state;

	if ( prop ) {
		return get( userQuiz, prop );
	}

	return userQuiz;
}

export function getDefaultRestArgs( state ) {
	const { userQuiz } = state;

	return {
		itemId: userQuiz.id,
		courseId: userQuiz.courseId,
	};
}

export function getQuestionAnswered( state, id ) {
	const { userQuiz } = state;

	return get( userQuiz, `answered.${ id }.answered` ) || undefined;
}

export function getQuestionMark( state, id ) {
	const { userQuiz } = state;

	return get( userQuiz, `answered.${ id }.mark` ) || undefined;
}

/**
 * Get current question is doing.
 *
 * @param {Object} state
 * @param {string} ret
 * @return {*}
 */
export function getCurrentQuestion( state, ret = '' ) {
	const questionsPerPage = get( state, 'userQuiz.questionsPerPage' ) || 1;

	if ( questionsPerPage > 1 ) {
		return false;
	}

	const currentPage = get( state, 'userQuiz.currentPage' ) || 1;
	return ret === 'object' ? get( state, `userQuiz.questions[${ currentPage - 1 }]` ) : get( state, `userQuiz.questionIds[${ currentPage - 1 }]` );
}

/**
 * Return a question contains fully data with title, content, ...
 *
 * @param state
 * @param theId
 */
const getQuestion = function getQuestion( state, theId ) {
	const { userQuiz } = state;
	const s = select( 'learnpress/quiz' );
	const questions = s.getQuestions();

	return questions.find( ( q ) => {
		return q.id == theId;
	} );
};

export { getQuestion };

/**
 * If user has used 'Instant check' for a question.
 *
 * @param {Object} state - Global state for app.
 * @param {number} id
 * @return {boolean}
 */
export function isCheckedAnswer( state, id ) {
	const checkedQuestions = get( state, 'userQuiz.checkedQuestions' ) || [];

	return checkedQuestions.indexOf( id ) !== -1;
}

export function isCorrect( state, id ) {

}

/**
 * Get questions user has selected answers.
 *
 * @param {Object} state. Global app state
 * @param state
 * @param {number} questionId
 * @return {{}}
 */
const getQuestionsSelectedAnswers = function( state, questionId ) {
	const data = get( state, 'userQuiz.answered' );
	const returnData = {};

	for ( const loopId in data ) {
		if ( ! data.hasOwnProperty( loopId ) ) {
			continue;
		}

		if ( lpQuizSettings.checknorequizenroll == '1' ) {
			// If specific a question then return it only.
			if ( questionId && loopId === questionId ) {
				return data[ loopId ].answered;
			}
			returnData[ loopId ] = data[ loopId ].answered;
		} else {
			// Answer filled by user
			if ( ( data[ loopId ].temp || data[ loopId ].blanks ) ) {
				// If specific a question then return it only.
				if ( questionId && loopId === questionId ) {
					return data[ loopId ].answered;
				}

				returnData[ loopId ] = data[ loopId ].answered;
			}
		}
	}

	return returnData;
};

export { getQuestionsSelectedAnswers };

/**
 * Get mark user earned.
 * Just for questions user has used 'Instant check' button.
 *
 * @param state
 * @return {number}
 */
export function getUserMark( state ) {
	const userQuiz = state.userQuiz || {};
	const {
		answered,
		negativeMarking,
		questions,
		checkedQuestions,
	} = userQuiz;

	let totalMark = 0;

	for ( let id in answered ) {
		if ( ! answered.hasOwnProperty( id ) ) {
			continue;
		}

		id = parseInt( id );
		const data = answered[ id ];
		const questionMark = data.questionMark ? data.questionMark : ( function() {
			const question = questions.find( ( q ) => {
				return q.id === id;
			} );

			return question ? question.point : 0;
		}() );

		const isChecked = checkedQuestions.indexOf( id ) !== -1;

		if ( data.temp ) {
			continue;
		}

		if ( negativeMarking ) {
			if ( data.answered ) {
				totalMark = data.correct ? totalMark + data.mark : totalMark - questionMark;
			}
		} else if ( data.answered && data.correct ) {
			totalMark += data.mark;
		}
	}

	return totalMark > 0 ? totalMark : 0;
}
