import {dispatch, select, apiFetch} from '@learnpress/data-controls';
import {select as wpSelect} from '@wordpress/data';

/**
 * Set user data for app.
 * @param key
 * @param data
 * @return {{type: string, data: *}}
 */
export function setQuizData(key, data) {
    if (typeof key === 'string') {
        data = {[key]: data}
    }else{
        data = key;
    }

    return {
        type: 'SET_QUIZ_DATA',
        data,
    }
}

/**
 * Set question will display.
 *
 * @param questionId
 * @return {{type: string, data: *}}
 */
export function setCurrentQuestion(questionId) {
    return {
        type: 'SET_CURRENT_QUESTION',
        questionId
    }
}

export function setCurrentPage(currentPage) {
    return {
        type: 'SET_CURRENT_PAGE',
        currentPage
    }
}

export function __requestStartQuizSuccess(data, quizId, courseId, userId) {
    return {
        type: 'START_QUIZ_SUCCESS',
        quizId,
        courseId,
        userId,
        data
    }
}

export function* startQuiz() {
    //yield dispatch('learnpress/quiz', '__requestStartQuizStart');

    const {
        item_id,
        course_id
    } = wpSelect('learnpress/quiz').getDefaultRestArgs();

    const quiz = yield apiFetch({
        path: 'lp/v1/users/start-quiz',
        method: 'POST',
        data: {
            item_id: item_id,
            course_id: course_id
        }
    });

    //yield dispatch('course-learner/course', 'startQuiz', quiz);

    yield dispatch('learnpress/quiz', '__requestStartQuizSuccess', quiz.results);
}

export function __requestSubmitQuizSuccess(results) {
    return {
        type: 'SUBMIT_QUIZ_SUCCESS',
        results
    }
}
export function* submitQuiz() {
    const {
        getDefaultRestArgs,
        getData
    } = wpSelect('learnpress/quiz');

    const {
        item_id,
        course_id
    } = getDefaultRestArgs();

    const answered = getData('answered');

    const result = yield apiFetch({
        path: 'lp/v1/users/submit-quiz',
        method: 'POST',
        data: {
            item_id,
            course_id,
            answered
        }
    });

    if (result.success) {
        yield dispatch('learnpress/quiz', '__requestSubmitQuizSuccess', result.results);
    }
}

export function updateUserQuestionAnswers(questionId, answers, quizId, courseId = 0, userId = 0) {
    return {
        type: 'UPDATE_USER_QUESTION_ANSWERS',
        questionId,
        answers,
    }
}

export function __requestShowHintSuccess(id, result) {
    return {
        type: 'SET_QUESTION_HINT',
        questionId: id,
        ...result
    }
}

export function* showHint(id) {
    const {
        getDefaultRestArgs,
        getData
    } = wpSelect('learnpress/quiz');

    const {
        item_id,
        course_id
    } = getDefaultRestArgs();

    const result = yield apiFetch({
        path: 'lp/v1/users/hint-answer',
        method: 'POST',
        data: {
            item_id,
            course_id,
            question_id: id
        }
    });

    yield dispatch('learnpress/quiz', '__requestShowHintSuccess', id, result);
}

export function __requestCheckAnswerSuccess(id, result) {
    return {
        type: 'CHECK_ANSWER',
        questionId: id,
        ...result
    }
}

export function* checkAnswer(id) {
    console.time('checkAnswer');
    const {
        getData,
        getDefaultRestArgs,
        getQuestionAnswered,
        getQuestionOptions
    } = wpSelect('learnpress/quiz');

    // if(getData('crypto')){
    //     const options = getQuestionOptions(id);
    //     console.log(options)
    // }

    const {
        item_id,
        course_id
    } = getDefaultRestArgs();


    const result = yield apiFetch({
        path: 'lp/v1/users/check-answer',
        method: 'POST',
        data: {
            item_id,
            course_id,
            question_id: id,
            answered: getQuestionAnswered(id) || ''
        }
    });

    yield dispatch('learnpress/quiz', '__requestCheckAnswerSuccess', id, result);
    console.timeEnd('checkAnswer');

}

export function markQuestionRendered(questionId) {
    return {
        type: 'MARK_QUESTION_RENDERED',
        questionId
    }
}

export function setQuizMode(mode) {
    return {
        type: 'SET_QUIZ_MODE',
        mode
    }
}
