import {combineReducers} from '@wordpress/data';

const {omit, flow, isArray, chunk} = lodash;
const {camelCaseDashObjectKeys} = LP;
const STORE_DATA = {};

export const setItemStatus = (item, status) => {
    const userSettings = {
        ...item.userSettings,
        status
    }

    return {
        ...item,
        userSettings
    }
}

const updateUserQuestionAnswer = (state, action) => {
    const {answered} = state;
    const newAnswer = {
        ...(answered[action.questionId] || {}),
        answered: action.answers,
        temp: true
    };

    console.log(newAnswer, action);

    return {
        ...state,
        answered: {
            ...state.answered,
            [action.questionId]: newAnswer
        }
    }
};

const markQuestionRendered = (state, action) => {
    const {
        questionsRendered
    } = state;

    if (isArray(questionsRendered)) {
        questionsRendered.push(action.questionId);
        return {
            ...state,
            questionsRendered: [...questionsRendered]
        }
    } else {
        return {
            ...state,
            questionsRendered: [action.questionId]
        }
    }
};

const resetCurrentQuestion = (state, args) => {
    const {
        questionIds
    } = state;

    return {
        ...state,
        ...args,
        currentQuestion: questionIds ? questionIds[0] : false
    }
};

const updateAttempt = (attempts, newAttempt) => {
    const at = attempts.findIndex((attempt) => {
        return attempt.id == newAttempt.id;
    });

    if (at !== -1) {
        attempts[at] = newAttempt;
    } else {
        attempts.unshift(newAttempt);
    }

    return attempts;
};

const setQuestionHint = (state, action) => {
    const questions = state.questions.map((question) => {
        return question.id == action.questionId ? {...question, showHint: action.showHint} : question;
    });

    return {
        ...state,
        questions: [...questions],
        //hintedQuestions: [...state.hintedQuestions, action.questionId]
    }
};

const checkAnswer = (state, action) => {
    const questions = state.questions.map((question) => {
        if (question.id !== action.questionId) {
            return question;
        }

        const newArgs = {
            explanation: action.explanation
        };

        if (action.options) {
            newArgs.options = action.options;
        }

        return {...question, ...newArgs};
    });

    return {
        ...state,
        questions: [...questions],
        answered: {
            ...state.answered,
            [action.questionId]: action.result
        },
        checkedQuestions: [...state.checkedQuestions, action.questionId]
    }
};

export const userQuiz = (state = STORE_DATA, action) => {
    switch (action.type) {
        case 'SET_QUIZ_DATA':
            if (1 > action.data.questionsPerPage) {
                action.data.questionsPerPage = 1;
            }

            const chunks = chunk(state.questionIds || action.data.questionIds, action.data.questionsPerPage);

            action.data.numPages = chunks.length;
            action.data.pages = chunks;

            return {
                ...state,
                ...action.data,
                currentPage: LP.localStorage.get(`Q${action.data.id}.currentPage`) || action.data.currentPage
            };
        case 'SUBMIT_QUIZ':
            return {
                ...state,
                submitting: true
            }
        case 'START_QUIZ':
        case 'START_QUIZ_SUCCESS':
            console.log(action.results)
            return resetCurrentQuestion(state, {
                checkedQuestions: [],
                hintedQuestions: [],
                mode: '',
                currentPage: 1,
                ...action.results
            });
        case 'SET_CURRENT_QUESTION':
            LP.localStorage.set(`Q${state.id}.currentQuestion`, action.questionId);
            return {
                ...state,
                currentQuestion: action.questionId
            };
        case 'SET_CURRENT_PAGE':
            LP.localStorage.set(`Q${state.id}.currentPage`, action.currentPage);

            return {
                ...state,
                currentPage: action.currentPage
            }
        case 'SUBMIT_QUIZ_SUCCESS':
            return resetCurrentQuestion(state, {
                attempts: updateAttempt(state.attempts, action.results),
                submitting: false,
                currentPage: 1,
                ...action.results
            });
        case 'UPDATE_USER_QUESTION_ANSWERS':
            return state.status === 'started' ? updateUserQuestionAnswer(state, action) : state;
        case 'MARK_QUESTION_RENDERED':
            return markQuestionRendered(state, action);
        case 'SET_QUIZ_MODE':
            if (action.mode == 'reviewing') {
                return resetCurrentQuestion(state, {
                    mode: action.mode
                })
            }
            return {
                ...state,
                mode: action.mode
            };
        case 'SET_QUESTION_HINT':
            return setQuestionHint(state, action);
        case 'CHECK_ANSWER':
            return checkAnswer(state, action);

    }
    return state;
};

export const blocks = flow(
    combineReducers,
    (reducer) => (state, action) => {
        //console.log('1', state)
        return reducer(state, action)
    },
    (reducer) => (state, action) => {
        //console.log('2')
        return reducer(state, action)
    },
    (reducer) => (state, action) => {
        //console.log('3')
        return reducer(state, action)
    }
)({
    a(state = {a: 1}, action){
        //console.log('a', action)
        return state;
    },
    b(state = {b: 2}, action){
        //console.log('b',action);
        return state;
    }
});

export default combineReducers({blocks, userQuiz});