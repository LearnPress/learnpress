import {dispatch, select, apiFetch} from '@learnpress/data-controls';
import {select as wpSelect} from '@wordpress/data';

const getEditorNonce = function getEditorNonce() {
    return lp_question_editor.root.nonce;
}

/**
 * Set user data for app.
 * @param key
 * @param data
 * @return {{type: string, data: *}}
 */
export function setData( data, key ) {
    return {
        type: 'SET_DATA',
        data,
        key
    }
}

export function __addOption(id, option) {
    return {
        type: 'ADD_OPTION',
        id,
        option
    }
}

/**
 * Add new blank and send to rest
 */
export function* addOption(questionId, option) {
    const results = yield apiFetch({
        path: 'lp/a/v1/question/' + questionId + '/add-option',
        method: 'POST',
        data: {
            nonce: getEditorNonce(),
            type: 'new-answer',
            id: questionId,
            option
        }
    });

    yield dispatch('learnpress/question', '__addOption', questionId, results.result);
}

export function __removeOption(questionId, optionId) {
    return {
        type: 'REMOVE_OPTION',
        id: questionId,
        optionId
    }
}

/**
 * Send a rest request to remove answer option.
 *
 * @param questionId
 * @param optionId
 */
export function* removeOption(questionId, optionId) {
    const results = yield apiFetch({
        path: 'lp/a/v1/question/' + questionId + '/remove-option',
        method: 'POST',
        data: {
            nonce: getEditorNonce(),
            type: 'delete-answer',
            id: questionId,
            answer_id: optionId
        }
    });

    yield dispatch('learnpress/question', '__removeOption', questionId, optionId);
}

export function __updateOption(option, optionId, questionId) {
    return {
        type: 'UPDATE_OPTION',
        id: questionId,
        optionId,
        option
    }
}

export function* updateOption(option, optionId, questionId) {

    const results = yield apiFetch({
        path: 'lp/a/v1/question/' + questionId + '/update-option',
        method: 'POST',
        data: {
            type: 'update-answer-title',
            answer: {
                ...option,
                question_answer_id: optionId
            },
            id:questionId,
            nonce: getEditorNonce()
        }
    });

    yield dispatch('learnpress/question', '__updateOption', option);

    //yield dispatch('learnpress/quiz', '__requestStartQuizSuccess', quiz);
}
