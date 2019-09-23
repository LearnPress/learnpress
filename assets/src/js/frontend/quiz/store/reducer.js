import {combineReducers} from '@wordpress/data';

const {omit, flow} = lodash;
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

export const userQuiz = (state = STORE_DATA, action) => {
    switch (action.type) {
        case 'SET_QUIZ_DATA':
            return {
                ...state,
                ...action.data
            }

        case 'START_QUIZ':
        case 'START_QUIZ_SUCCESS':
            return {
                ...state,
                status: 'started'
            }
        case 'SET_CURRENT_QUESTION':
            return {
                ...state,
                currentQuestion: action.questionId
            }
        case 'SUBMIT_QUIZ':

            return {
                ...state,
                status: 'completed',
                attempts: [...state.attempts, {
                    time: (new Date()).toString(),
                    questions: 10,
                    marks: [4, 10],
                    passingGrade: '80%',
                    spendTime: [360, 360],
                    result: '10%'
                }]
            }
        case 'UPDATE_USER_QUESTION_ANSWERS':
            return {
                ...state,
            }
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