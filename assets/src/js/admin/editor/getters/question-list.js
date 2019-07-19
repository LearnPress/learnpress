const QuestionList = {
    listQuestions: function (state) {
        return state.questions || [];
    },
    questionsOrder: function (state) {
        return state.order || [];
    },
    externalComponent: function (state) {
        return state.externalComponent || [];
    },
    hiddenQuestionsSettings: function (state) {
        return state.hidden_questions_settings || [];
    },
    hiddenQuestions: function (state) {
        return state.questions
            .filter(function (question) {
                return !question.open;
            })
            .map(function (question) {
                return parseInt(question.id);
            })
    },
    isHiddenListQuestions: function (state, getters) {
        var questions = getters['listQuestions'];
        var hiddenQuestions = getters['hiddenQuestions'];

        return questions.length === hiddenQuestions.length;
    },
    disableUpdateList: function (state) {
        return state.disableUpdateList;
    },
    statusUpdateQuestions: function (state) {
        return state.statusUpdateQuestions;
    },
    statusUpdateQuestionItem: function (state) {
        return state.statusUpdateQuestionItem;
    },
    statusUpdateQuestionAnswer: function (state) {
        return state.statusUpdateQuestionAnswer;
    }
};

export default QuestionList;