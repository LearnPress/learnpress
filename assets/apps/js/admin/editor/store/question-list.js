import getters from '../getters/question-list';
import mutations from '../mutations/question-list';
import actions from '../actions/question-list';

const $ = window.jQuery;
const QuestionList = function QuestionList(data) {
    const listQuestions = data.listQuestions;
    var state = $.extend({
        statusUpdateQuestions: {},
        statusUpdateQuestionItem: {},
        statusUpdateQuestionAnswer: {},
        questions: listQuestions.questions.map(function (question) {
            var hiddenQuestions = listQuestions.hidden_questions;
            var find = hiddenQuestions.find(function (questionId) {
                return parseInt(question.id) === parseInt(questionId);
            });

            question.open = !find;

            return question;
        })
    }, listQuestions);

    return {
        namespaced: true,
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions
    }
};

export default QuestionList;