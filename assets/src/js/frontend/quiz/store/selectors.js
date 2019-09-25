import {select} from '@wordpress/data';
const {get} = lodash;

/**
 * Get current status of an item in course.
 *
 * @param state
 * @param itemId
 */
export function getItemStatus(state, itemId) {
    const item = select('course-learner/user').getItemById(itemId);
    return item ? get(item, 'userSettings.status') : '';
}

export function getProp(state, prop, defaultValue) {
    return state[prop] || defaultValue;
}

/**
 * Get quiz attempted.
 *
 * @param state
 * @param itemId
 * @return {Array}
 */
export function getQuizAttempts(state, itemId) {
    const item = select('course-learner/user').getItemById(itemId);
    return item ? get(item, 'userSettings.attempts') : [];
}

/**
 * Get answers for a quiz user has did.
 *
 * @param state
 * @param itemId
 * @return {{}}
 */
export function getQuizAnswered(state, itemId) {
    const item = select('course-learner/user').getItemById(itemId);
    return item ? get(item, 'userSettings.answered', {}) : {};
}

export function getQuestions(state) {
    const {userQuiz} = state;
    const questions = get(userQuiz, 'questions');
    return questions ? Object.values(questions) : [];
}

/**
 * Get property of store data.
 *
 * @param state - Store data
 * @param prop - Optional. NULL will return all data.
 * @return {*}
 */
export function getData(state, prop) {
    const {userQuiz} = state;

    if (prop) {
        return get(userQuiz, prop);
    }

    return userQuiz;
}

export function getDefaultRestArgs(state) {
    const {userQuiz} = state;

    return {
        item_id: userQuiz.id,
        course_id: userQuiz.course_id
    }
}