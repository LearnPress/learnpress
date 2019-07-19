import getters from '../getters/quiz';
import mutations from '../mutations/quiz';
import actions from '../actions/quiz';

import ModalQuizItems from '../store/modal-quiz-items';
import i18n from '../store/i18n';
import QuestionList from '../store/question-list';

const $ = window.jQuery;
const Quiz = function Quiz(data) {
    const state = $.extend({
        status: 'success',
        heartbeat: true,
        countCurrentRequest: 0,
    }, data.root);

    return {
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions,
        modules: {
            cqi: ModalQuizItems(data),
            i18n: i18n(data.i18n),
            lqs: QuestionList(data)
        }
    }
};

export default Quiz;
