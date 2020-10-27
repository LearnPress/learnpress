const $ = window.jQuery;
const QuestionList = {

    toggleAll: function (context) {
        var hidden = context.getters['isHiddenListQuestions'];

        if (hidden) {
            context.commit('OPEN_LIST_QUESTIONS');
        } else {
            context.commit('CLOSE_LIST_QUESTIONS');
        }

        LP.Request({
            type: 'hidden-questions',
            hidden: context.getters['hiddenQuestions']
        })
    },

    updateQuizQuestionsHidden: function (context, data) {
        LP.Request($.extend({}, data, {
            type: 'update-quiz-questions-hidden'
        }));
    },

    newQuestion: function (context, payload) {
        var newQuestion = JSON.parse(JSON.stringify(payload.question));
        newQuestion.settings = {};
        context.commit('ADD_NEW_QUESTION', newQuestion);
        LP.Request({
            type: 'new-question',
            question: JSON.stringify(payload.question),
            draft_quiz: JSON.stringify(payload.quiz)
        }).then(
            function (response) {
                var result = response.body;

                if (result.success) {
                    // update new question type
                    context.commit('UPDATE_NEW_QUESTION_TYPE', payload.question.type, {root: true});
                    // update list quiz questions
                    context.commit('ADD_NEW_QUESTION', result.data);
                    context.commit('CLOSE_LIST_QUESTIONS');
                    context.commit('OPEN_QUESTION', result.data);
                }
            },
            function (error) {
                console.log(error);
            }
        );
    },

    updateQuestionsOrder: function (context, order) {
        LP.Request({
            type: 'sort-questions',
            order: JSON.stringify(order)
        }).then(
            function (response) {
                context.commit('SORT_QUESTIONS', order);
            },
            function (error) {
                console.log(error);
            }
        );
    },

    updateQuestionTitle: function (context, question) {

        context.commit('UPDATE_QUESTION_REQUEST', question.id);

        LP.Request({
            type: 'update-question-title',
            question: JSON.stringify(question)
        }).then(
            function () {
                context.commit('UPDATE_QUESTION_SUCCESS', question.id);
            }
        ).catch(
            function () {
                context.commit('UPDATE_QUESTION_FAILURE', question.id);
            })
    },

    changeQuestionType: function (context, payload) {

        context.commit('UPDATE_QUESTION_REQUEST', payload.question_id);

        LP.Request({
            type: 'change-question-type',
            question_id: payload.question_id,
            question_type: payload.type
        }).then(function (response) {
            var result = response.body;

            if (result.success) {
                var question = result.data;
                context.commit('CHANGE_QUESTION_TYPE', question);
                context.commit('UPDATE_NEW_QUESTION_TYPE', question.type.key, {root: true});
                context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
            }
        }).catch(function () {
            context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);

        })
    },

    isHiddenQuestionsSettings: function (context, id) {
    },

    cloneQuestion: function (context, question) {
        LP.Request({
            type: 'clone-question',
            question: JSON.stringify(question)
        }).then(
            function (response) {
                var result = response.body;

                if (result.success) {
                    var question = result.data;

                    context.commit('ADD_NEW_QUESTION', result.data);
                    context.commit('UPDATE_NEW_QUESTION_TYPE', question.type.key, {root: true})
                }
            },
            function (error) {
                console.log(error);
            }
        )
    },

    removeQuestion: function (context, question) {
        var question_id = question.id;
        question.temp_id = LP.uniqueId();
        context.commit('REMOVE_QUESTION', question);

        LP.Request({
            type: 'remove-question',
            question_id: question_id
        }).then(
            function (response) {
                var result = response.body;

                if (result.success) {
                    question.id = question.temp_id;
                    question.temp_id = 0;
                    context.commit('REMOVE_QUESTION', question);
                }
            },
            function (error) {
                console.error(error);
            }
        )
    },

    deleteQuestion: function (context, question) {
        var question_id = question.id;
        question.temp_id = LP.uniqueId();
        context.commit('REMOVE_QUESTION', question);
        LP.Request({
            type: 'delete-question',
            question_id: question_id
        })
            .then(function () {
                question.id = question.temp_id;
                question.temp_id = 0;
                context.commit('REMOVE_QUESTION', question);
                context.commit('UPDATE_QUESTION_SUCCESS', question.id);
            })
            .catch(function () {
                context.commit('UPDATE_QUESTION_FAILURE', question.id);
            })
    },

    toggleQuestion: function (context, question) {
        if (question.open) {
            context.commit('CLOSE_QUESTION', question);
        } else {
            context.commit('OPEN_QUESTION', question);
        }

        LP.Request({
                type: 'hidden-questions',
                hidden: context.getters['hiddenQuestions']
            }
        )
    },

    updateQuestionAnswersOrder: function (context, payload) {
        context.commit('UPDATE_QUESTION_REQUEST', payload.question_id);

        LP.Request({
            type: 'sort-question-answers',
            question_id: payload.question_id,
            order: JSON.stringify(payload.order)
        }).then(
            function (response) {
                var result = response.body,
                    order = result.data;
                context.commit('SORT_QUESTION_ANSWERS', order);
                context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
            },
            function (error) {
                context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);
                console.log(error);
            }
        )
    },

    updateQuestionAnswerTitle: function (context, payload) {

        context.commit('UPDATE_QUESTION_REQUEST', payload.question_id);

        LP.Request({
            type: 'update-question-answer-title',
            question_id: parseInt(payload.question_id),
            answer: JSON.stringify(payload.answer)
        }).then(
            function () {
                context.commit('UPDATE_QUESTION_ANSWER_SUCCESS', parseInt(payload.question_id));
                context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
            }
        ).catch(
            function () {
                context.commit('UPDATE_QUESTION_ANSWER_FAILURE', parseInt(payload.question_id));
                context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);
            })
    },

    updateQuestionCorrectAnswer: function (context, payload) {
        context.commit('UPDATE_QUESTION_REQUEST', payload.question_id);

        LP.Request({
            type: 'change-question-correct-answer',
            question_id: payload.question_id,
            correct: JSON.stringify(payload.correct)
        }).then(
            function (response) {
                var result = response.body;
                if (result.success) {
                    context.commit('CHANGE_QUESTION_CORRECT_ANSWERS', result.data);
                    context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
                }
            },
            function (error) {
                context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);
                console.log(error);
            }
        )
    },

    deleteQuestionAnswer: function (context, payload) {
        payload.temp_id = LP.uniqueId();
        context.commit('DELETE_ANSWER', payload);
        context.commit('UPDATE_QUESTION_REQUEST', payload.question_id);

        LP.Request({
            type: 'delete-question-answer',
            question_id: payload.question_id,
            answer_id: payload.answer_id
        }).then(
            function (response) {
                var result = response.body;

                if (result.success) {
                    context.commit('DELETE_QUESTION_ANSWER', {
                        question_id: payload.question_id,
                        answer_id: payload.temp_id
                        //answer_id: payload.answer_id
                    });
                    context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
                }
            },
            function (error) {
                context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);
                console.log(error);
            }
        )
    },

    newQuestionAnswer: function (context, data) {
        var temp_id = LP.uniqueId(),
            question_id = data.question_id;
        context.commit('UPDATE_QUESTION_REQUEST', question_id);
        context.commit('ADD_QUESTION_ANSWER', {
            question_id: question_id,
            answer: {'text': LP_Quiz_Store.getters['i18n/all'].new_option, 'question_answer_id': temp_id}
        });
        LP.Request({
            type: 'new-question-answer',
            question_id: question_id,
            question_answer_id: temp_id
        })
            .then(
                function (response) {
                    var result = response.body;
                    if (result.success) {
                        var answer = result.data;
                        context.commit('ADD_QUESTION_ANSWER', {question_id: question_id, answer: answer});
                        context.commit('UPDATE_QUESTION_SUCCESS', question_id);

                        data.success && setTimeout(function () {
                            data.success.apply(data.context, [answer]);
                        }, 300);
                    }
                },
                function (error) {
                    context.commit('UPDATE_QUESTION_FAILURE', question_id);
                    console.error(error);
                }
            )
    },

    updateQuestionContent: function (context, question) {

        context.commit('UPDATE_QUESTION_REQUEST', question.id);

        LP.Request({
            type: 'update-question-content',
            question: JSON.stringify(question)
        }).then(
            function () {
                context.commit('UPDATE_QUESTION_SUCCESS', question.id);
            }
        ).catch(
            function () {
                context.commit('UPDATE_QUESTION_FAILURE', question.id);
            })
    },

    updateQuestionMeta: function (context, payload) {

        context.commit('UPDATE_QUESTION_REQUEST', payload.question.id);

        LP.Request({
            type: 'update-question-meta',
            question: JSON.stringify(payload.question),
            meta_key: payload.meta_key
        }).then(
            function () {
                context.commit('UPDATE_QUESTION_SUCCESS', payload.question.id);
            }
        ).catch(
            function () {
                context.commit('UPDATE_QUESTION_FAILURE', payload.question.id);
            })
    }
};

export default QuestionList;