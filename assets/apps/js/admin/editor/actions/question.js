const Question = {

    changeQuestionType: function (context, payload) {
        LP.Request({
            type: 'change-question-type',
            question_type: payload.type,
            draft_question: context.getters.autoDraft ? JSON.stringify(payload.question) : ''
        }).then(function (response) {
            var result = response.body;

            if (result.success) {
                context.commit('UPDATE_AUTO_DRAFT_STATUS', false);
                context.commit('CHANGE_QUESTION_TYPE', result.data);
            }
        })
    },

    updateAnswersOrder: function (context, order) {
        LP.Request({
            type: 'sort-answer',
            order: order
        }).then(
            function (response) {
                var result = response.body;
                if (result.success) {
                    // context.commit('SET_ANSWERS', result.data);
                }
            }
        )
    },

    updateAnswerTitle: function (context, answer) {
        if (typeof answer.question_answer_id == 'undefined') {
            return;
        }
        answer = JSON.stringify(answer);
        LP.Request({
            type: 'update-answer-title',
            answer: answer
        })
    },

    updateCorrectAnswer: function (context, correct) {
        LP.Request({
            type: 'change-correct',
            correct: JSON.stringify(correct)
        }).then(
            function (response) {
                var result = response.body;
                if (result.success) {
                    context.commit('UPDATE_ANSWERS', result.data);
                    context.commit('UPDATE_AUTO_DRAFT_STATUS', false);
                }
            }
        )
    },

    deleteAnswer: function (context, payload) {

        context.commit('DELETE_ANSWER', payload.id);
        LP.Request({
            type: 'delete-answer',
            answer_id: payload.id
        }).then(
            function (response) {
                var result = response.body;

                if (result.success) {
                    context.commit('SET_ANSWERS', result.data);
                } else {
                    // notice error
                }
            })
    },

    newAnswer: function (context, data) {
        context.commit('ADD_NEW_ANSWER', data.answer);
        LP.Request({
            type: 'new-answer'
        }).then(
            function (response) {
                var result = response.body;

                if (result.success) {
                    context.commit('UPDATE_ANSWERS', result.data);
                } else {
                    // notice error
                }
            })
    },

    newRequest: function (context) {
        context.commit('INCREASE_NUMBER_REQUEST');
        context.commit('UPDATE_STATUS', 'loading');

        window.onbeforeunload = function () {
            return '';
        }
    },

    requestCompleted: function (context, status) {
        context.commit('DECREASE_NUMBER_REQUEST');

        if (context.getters.currentRequest === 0) {
            context.commit('UPDATE_STATUS', status);
            window.onbeforeunload = null;
        }
    }
};

export default Question;