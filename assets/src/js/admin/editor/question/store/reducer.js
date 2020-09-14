import {combineReducers} from '@wordpress/data';

const {omit, flow, isArray, remove, get, set} = lodash;
const STORE_DATA = {
    question: false
};

/**
 * Remove an option from question answers.
 *
 * @param {object} state
 * @param {int} optionId
 * @param {int} id
 */
const removeOptionById = function removeOptionById(state, optionId, id) {
    const {question: {blankOptions}} = state;

    remove(blankOptions, function (a) {
        return a.question_answer_id == optionId;
    });

    return blankOptions;
};

const setData = function setData(state, data, key) {
    if (!key) {
        return {
            ...state,
            ...data
        }
    }

    const oldData = get(state, key);
    set(state, key, {...oldData, ...data});

    return {...state};
};

const updateOptionById = function updateOptionById(state, option, optionId, id) {
    const {question: {blankOptions}} = state;

    return blankOptions.map((opt) => {
        return opt.question_answer_id == optionId ? {
            ...opt,
            ...option
        } : opt;
    });
};

export const storeData = (state = STORE_DATA, action) => {
    switch (action.type) {
        case 'SET_DATA':
            return setData(state, action.data, action.key);
        case 'ADD_OPTION':
            return {
                ...state,
                question: {
                    ...state.question,
                    blankOptions: [...state.question.blankOptions, action.option]
                }
            }
        case 'REMOVE_OPTION':
            return {
                ...state,
                question: {
                    ...state.question,
                    blankOptions: [...removeOptionById(state, action.optionId, action.id)]
                }
            }
        case 'UPDATE_OPTION':
            return {
                ...state,
                question: {
                    ...state.question,
                    blankOptions: [...updateOptionById(state, action.option, action.optionId, action.id)]
                }
            }
    }
    return state;
};

export default storeData;
