import {combineReducers} from '@wordpress/data';

const {omit, flow, isArray} = lodash;
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
    const newAnswer = {[action.questionId]: action.answers};

    return {
        ...state,
        answered: {...(answered || {}), ...newAnswer}
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
        return question.id == action.questionId ? {...question, hint: action.hint_content} : question;
    });

    return {
        ...state,
        questions: [...questions],
        show_hint: action.count,
        hinted_questions: [...state.hinted_questions, action.questionId]
    }
};

const checkAnswer = (state, action) => {
    const questions = state.questions.map((question) => {
        return question.id == action.questionId ? {...question, explanation: action.explanation_content} : question;
    });

    return {
        ...state,
        questions: [...questions],
        show_check_answers: action.count,
        checked_questions: [...state.checked_questions, action.questionId]
    }
};

export const userQuiz = (state = STORE_DATA, action) => {
    switch (action.type) {
        case 'SET_QUIZ_DATA':
            if (action.key) {
                return {
                    ...state,
                    [action.key]: action.data
                }
            }

            return {
                ...state,
                ...action.data
            };

        case 'START_QUIZ':
        case 'START_QUIZ_SUCCESS':
            return resetCurrentQuestion(state, {
                status: 'started'
            });
        case 'SET_CURRENT_QUESTION':
            return {
                ...state,
                currentQuestion: action.questionId
            };
        case 'SUBMIT_QUIZ_SUCCESS':
            return resetCurrentQuestion(state, {
                status: 'completed',
                attempts: updateAttempt(state.attempts, action.results),
                answered: false
            });
        case 'UPDATE_USER_QUESTION_ANSWERS':
            return state.status === 'started' ? updateUserQuestionAnswer(state, action) : state;
        case 'MARK_QUESTION_RENDERED':
            return markQuestionRendered(state, action);
        case 'SET_QUIZ_MODE':
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