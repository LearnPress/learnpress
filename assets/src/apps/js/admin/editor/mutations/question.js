const Question = {

    'UPDATE_STATUS': function (state, status) {
        state.status = status;
    },

    'UPDATE_AUTO_DRAFT_STATUS': function (state, status) {
        state.auto_draft = status;
    },

    'CHANGE_QUESTION_TYPE': function (state, question) {
        state.answers = question.answers;
        state.type = question.type;
    },

    'SET_ANSWERS': function (state, answers) {
        state.answers = answers;
    },

    'DELETE_ANSWER': function (state, id) {
        for (var i = 0, n = state.answers.length; i < n; i++) {
            if (state.answers[i].question_answer_id == id) {
                state.answers[i].question_answer_id = LP.uniqueId();
                break;
            }
        }
    },
    'ADD_NEW_ANSWER': function (state, answer) {
        state.answers.push(answer);
    },
    'UPDATE_ANSWERS': function (state, answers) {
        state.answers = answers;
    },

    'INCREASE_NUMBER_REQUEST': function (state) {
        state.countCurrentRequest++;
    },

    'DECREASE_NUMBER_REQUEST': function (state) {
        state.countCurrentRequest--;
    }
};

export default Question;