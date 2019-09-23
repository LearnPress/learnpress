import {dispatch, select, apiFetch} from '@learnpress/data-controls';
import {select as wpSelect} from '@wordpress/data';

/**
 * Set user data for app.
 *
 * @param data
 * @return {{type: string, data: *}}
 */
export function setQuizData(data){
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
export function setCurrentQuestion(questionId){
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

export function* startQuiz(quizId, courseId, userId) {
    //yield dispatch('learnpress/quiz', '__requestStartQuizStart');

    const quiz = yield apiFetch({
        path: 'wp/v2/taxonomies',
        method: 'GET'
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