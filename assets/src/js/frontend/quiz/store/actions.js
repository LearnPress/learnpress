import {dispatch, select, apiFetch} from '@learnpress/data-controls';
import {select as wpSelect} from '@wordpress/data';

/**
 * Set user data for app.
 *
 * @param data
 * @return {{type: string, data: *}}
 */
export function setQuizData(data) {
    return {
        type: 'SET_QUIZ_DATA',
        data
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

    yield dispatch('learnpress/quiz', '__requestStartQuizSuccess', quiz);
}

export function submitQuiz(quizId, courseId, userId) {
    return {
        type: 'SUBMIT_QUIZ',
        quizId,
        courseId,
        userId
    }
}

export function updateUserQuestionAnswers(questionId, answers, quizId, courseId = 0, userId = 0) {
    return {
        type: 'UPDATE_USER_QUESTION_ANSWERS',
        questionId,
        answers,
        quizId,
        courseId,
        userId
    }
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