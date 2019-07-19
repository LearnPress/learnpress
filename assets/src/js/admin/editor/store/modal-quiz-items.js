import getters from '../getters/modal-quiz-items';
import mutations from '../mutations/modal-quiz-items';
import actions from '../actions/modal-quiz-items';

const $ = window.jQuery;
const Quiz = function (data) {
    var state = $.extend({
        quizId: false,
        pagination: '',
        status: ''
    }, data.chooseItems);

    return {
        namespaced: true,
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions
    }
};

export default Quiz;