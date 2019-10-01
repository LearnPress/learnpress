import {select} from '@wordpress/data';
const {get, isArray} = lodash;

const getQuestionOptions = function getQuestionOptions(state, id){
    console.time('parseOptions');

    const question = getQuestion(state, id);
    let options = question.options;

    options = !isArray(options) ? JSON.parse(CryptoJS.AES.decrypt(options.data, options.key, {format: CryptoJSAesJson}).toString(CryptoJS.enc.Utf8)):options;
    options = !isArray(options) ? JSON.parse(options) : options;

    console.timeEnd('parseOptions')
    return options;
};

export {getQuestionOptions}

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
        course_id: userQuiz.courseId
    }
}

export function getQuestionAnswered(state, id) {
    const {userQuiz} = state;
    let answered;

    if (userQuiz.status === 'started') {
        answered = get(userQuiz, 'answered');
    } else {
        answered = get(userQuiz, 'attempts[0].answered');
    }

    return answered ? answered[id] : undefined;
}


export function getCurrentQuestion(state) {
    const {userQuiz} = state;
    const {currentQuestion} = userQuiz;

    return getQuestion(state, currentQuestion)
}

const getQuestion = function getQuestion(state, theId) {
    const {userQuiz} = state;
    const s = select('learnpress/quiz');
    const questions = s.getQuestions();

    return questions.find((q) => {
        return q.id == theId;
    })
};

export {getQuestion};

export function isCheckedAnswer(state, id) {
    const checkedQuestions = get(state, 'userQuiz.checkedQuestions') || [];

    return checkedQuestions.indexOf(id) !== -1;
}