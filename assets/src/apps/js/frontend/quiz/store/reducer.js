import { combineReducers } from '@wordpress/data';

const { omit, flow, isArray, chunk } = lodash;
const { camelCaseDashObjectKeys } = LP;
const { get: storageGet, set: storageSet } = LP.localStorage;
const STORE_DATA = {};

export const setItemStatus = ( item, status ) => {
	const userSettings = {
		...item.userSettings,
		status,
	};

	return {
		...item,
		userSettings,
	};
};

const updateUserQuestionAnswer = ( state, action ) => {
	const { answered, id } = state;
	const newAnswer = {
		...( answered[ action.questionId ] || {} ),
		answered: action.answers,
		temp: true,
	};

	if ( id ) {
		localStorage.setItem( `LP_Quiz_${ id }_Answered`, JSON.stringify( {
			...state.answered,
			[ action.questionId ]: newAnswer,
		} ) );
	}

	return {
		...state,
		answered: {
			...state.answered,
			[ action.questionId ]: newAnswer,
		},
	};
};

const markQuestionRendered = ( state, action ) => {
	const {
		questionsRendered,
	} = state;

	if ( isArray( questionsRendered ) ) {
		questionsRendered.push( action.questionId );
		return {
			...state,
			questionsRendered: [ ...questionsRendered ],
		};
	}
	return {
		...state,
		questionsRendered: [ action.questionId ],
	};
};

const resetCurrentPage = ( state, args ) => {
	if ( args.currentPage ) {
		storageSet( `Q${ state.id }.currentPage`, args.currentPage );
	}

	return {
		...state,
		...args,
	};
};

const setQuestionHint = ( state, action ) => {
	const questions = state.questions.map( ( question ) => {
		return question.id == action.questionId ? { ...question, showHint: action.showHint } : question;
	} );

	return {
		...state,
		questions: [ ...questions ],
	};
};

const checkAnswer = ( state, action ) => {
	const questions = state.questions.map( ( question ) => {
		if ( question.id !== action.questionId ) {
			return question;
		}

		const newArgs = {
			explanation: action.explanation,
		};

		if ( action.options ) {
			newArgs.options = action.options;
		}

		return { ...question, ...newArgs };
	} );

	const answered = {
		...state.answered,
		[ action.questionId ]: action.result,
	};

	let newAnswered = localStorage.getItem( `LP_Quiz_${ state.id }_Answered` );

	if ( newAnswered ) {
		newAnswered = {
			...JSON.parse( newAnswered ),
			...answered,
		}

		localStorage.setItem( `LP_Quiz_${ state.id }_Answered`, JSON.stringify( newAnswered ) );
	}

	return {
		...state,
		questions: [ ...questions ],
		answered: answered,
		checkedQuestions: [ ...state.checkedQuestions, action.questionId ],
	};
};

const submitQuiz = ( state, action ) => {
	localStorage.removeItem( `LP_Quiz_${ state.id }_Answered` );

	const questions = state.questions.map( ( question ) => {
		const newArgs = {};
		if ( state.reviewQuestions ) {
			if ( action.results.questions[ question.id ]?.explanation ) {
				newArgs.explanation = action.results.questions[ question.id ].explanation;
			}

			if ( action.results.questions[ question.id ]?.options ) {
				newArgs.options = action.results.questions[ question.id ].options;
			}
		}

		return { ...question, ...newArgs };
	} );

	return resetCurrentPage( state, {
		submitting: false,
		currentPage: 1,
		...action.results,
		questions: [ ...questions ],
	} );
};

const startQuizz = ( state, action ) => {
	const successResponse = ( action.results.success ) !== undefined ? action.results.success : false;
	const messageResponse = action.results.message || false;

	const chunks = chunk( action.results.results.questionIds, state.questionsPerPage );
	state.numPages = chunks.length;

	return resetCurrentPage( state, {
		checkedQuestions: [],
		hintedQuestions: [],
		mode: '',
		currentPage: 1,
		...action.results.results,
		successResponse,
		messageResponse,
	} );
};

export const userQuiz = ( state = STORE_DATA, action ) => {
	switch ( action.type ) {
	case 'SET_QUIZ_DATA':
		if ( 1 > action.data.questionsPerPage ) {
			action.data.questionsPerPage = 1;
		}

		const chunks = chunk( state.questionIds || action.data.questionIds, action.data.questionsPerPage );

		action.data.numPages = chunks.length;
		action.data.pages = chunks;

		return {
			...state,
			...action.data,
			currentPage: storageGet( `Q${ action.data.id }.currentPage` ) || action.data.currentPage,
		};
	case 'SUBMIT_QUIZ':
		return {
			...state,
			submitting: true,
		};
	case 'START_QUIZ':
	case 'START_QUIZ_SUCCESS':
		return startQuizz( state, action );
	case 'SET_CURRENT_QUESTION':
		storageSet( `Q${ state.id }.currentQuestion`, action.questionId );
		return {
			...state,
			currentQuestion: action.questionId,
		};
	case 'SET_CURRENT_PAGE':
		storageSet( `Q${ state.id }.currentPage`, action.currentPage );

		return {
			...state,
			currentPage: action.currentPage,
		};
	case 'SUBMIT_QUIZ_SUCCESS':
		return submitQuiz( state, action );
	case 'UPDATE_USER_QUESTION_ANSWERS':
		return state.status === 'started' ? updateUserQuestionAnswer( state, action ) : state;
	case 'MARK_QUESTION_RENDERED':
		return markQuestionRendered( state, action );
	case 'SET_QUIZ_MODE':
		if ( action.mode == 'reviewing' ) {
			return resetCurrentPage( state, {
				mode: action.mode,
			} );
		}
		return {
			...state,
			mode: action.mode,
		};
	case 'SET_QUESTION_HINT':
		return setQuestionHint( state, action );
	case 'CHECK_ANSWER':
		return checkAnswer( state, action );
	case 'SEND_KEY':
		return {
			...state,
			keyPressed: action.keyPressed,
		};
	}
	return state;
};

export const blocks = flow(
	combineReducers,
	( reducer ) => ( state, action ) => {
		return reducer( state, action );
	},
	( reducer ) => ( state, action ) => {
		return reducer( state, action );
	},
	( reducer ) => ( state, action ) => {
		return reducer( state, action );
	}
)( {
	a( state = { a: 1 }, action ) {
		return state;
	},
	b( state = { b: 2 }, action ) {
		return state;
	},
} );

export default combineReducers( { blocks, userQuiz } );
