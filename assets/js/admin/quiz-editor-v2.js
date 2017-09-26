;

/**
 * Helpers
 *
 * @since 3.0.0
 */
(function (exports) {
    function cloneObject(object) {
        return JSON.parse(JSON.stringify(object));
    }

    exports.LP_Helpers = {
        cloneObject: cloneObject
    };
})(window);

/**
 * List quiz questions store.
 *
 * @since 3.0.0
 */
var LP_List_Quiz_Questions_Store = (function (Vue, helpers, data) {

    var state = helpers.cloneObject(data.listQuestions);

    state.statusUpdateListQuestions = {};
    state.statusUpdateQuestionItem = {};

    state.questions = state.questions.map(function (question) {
        var hiddenQuestions = state.hidden_questions;
        var find = hiddenQuestions.find(function (questionId) {
            return parseInt(question.id) === parseInt(questionId);
        });

        question.open = !find;

        return question;
    });

    var getters = {
        listQuestions: function (state) {
            return state.questions || [];
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
        statusUpdateSection: function (state) {
            return state.statusUpdateListQuestions;
        },
        statusUpdateQuestionItem: function (state) {
            return state.statusUpdateQuestionItem;
        }
    };

    var mutations = {
        'SORT_QUESTIONS': function (state, orders) {
            state.questions = state.questions.map(function (question) {
                question.order = orders[question.id];
                return question;
            });
        },
        'SET_QUESTIONS': function (state, questions) {
            state.questions = questions;
        },
        'ADD_NEW_QUESTION': function (state, question) {
            question.open = true;
            state.questions.push(question);
        },
        'UPDATE_QUESTION': function () {
            // code
        },
        'REMOVE_QUESTION': function (state, index) {
            state.questions.splice(index, 1);
        },
        'REMOVE_QUESTIONS': function () {
            // code
        },
        'DELETE_QUESTION': function (state, index) {
            state.questions.splice(index, 1);
        },
        'CLOSE_QUESTION': function (state, question) {
            state.questions.forEach(function (_question, index) {
                if (question.id === _question.id) {
                    state.questions[index].open = false;
                }
            });
        },
        'OPEN_QUESTION': function (state, question) {
            state.questions.forEach(function (_question, index) {
                if (question.id === _question.id) {
                    state.questions[index].open = true;
                }
            });
        },
        'CLOSE_LIST_QUESTIONS': function (state) {
            state.questions = state.questions.map(function (_question) {
                _question.open = false;

                return _question;
            });
        },
        'OPEN_LIST_QUESTIONS': function (state) {
            state.questions = state.questions.map(function (_question) {
                _question.open = true;

                return _question;
            })
        },
        'UPDATE_LIST_QUESTIONS_REQUEST': function () {
            Vue.set(state.statusUpdateListQuestions, 'updating');
        },
        'UPDATE_LIST_QUESTIONS_SUCCESS': function () {
            Vue.set(state.statusUpdateListQuestions, 'succeeded');
        },
        'UPDATE_LIST_QUESTIONS_FAILURE': function () {
            Vue.set(state.statusUpdateListQuestions, 'failed');
        },
        'UPDATE_QUESTION_REQUEST': function (state, questionId) {
            Vue.set(state.statusUpdateQuestionItem, questionId, 'updating');
        },
        'UPDATE_QUESTION_SUCCESS': function (state, questionID) {
            Vue.set(state.statusUpdateQuestionItem, questionID, 'succeeded');
        },
        'UPDATE_QUESTION_FAILURE': function (state, questionID) {
            Vue.set(state.statusUpdateQuestionItem, questionID, 'failed')
        }
    };

    var actions = {

        addNewQuestion: function (context, questions) {
            Vue.http
                .LPRequest({
                    type: 'new-question',
                    questions: questions
                })
                .then(
                    function (response) {
                        var result = response.body;

                        if (result.success) {
                            context.commit('ADD_NEW_QUESTION', result.data);
                        }
                    },
                    function (error) {
                        console.log(error);
                    }
                );
        },

        removeQuestion: function (context, payload) {
            context.commit('REMOVE_QUESTION', payload);

            Vue.http
                .LPRequest({
                    type: 'remove-question',
                    'question-id': payload.itemId
                })
        },

        updateQuestion: function (context, question) {
            context.commit('UPDATE_QUESTION', question.id);

            Vue.http
                .LPRequest({
                    type: 'update-question',
                    question: JSON.stringify(question)
                })
                .then(function () {
                    context.commit('UPDATE_QUESTION_SUCCESS', question.id);
                })
                .catch(function () {
                    context.commit('UPDATE_QUESTION_FAILURE', question.id);
                })
        },

        sortQuestions: function (context, orders) {
            Vue.http
                .LPRequest({
                    type: 'sort-questions',
                    order: JSON.stringify(orders)
                })
                .then(
                    function (response) {
                        var result = response.body,
                            order = result.data;
                        context.commit('SORT_QUESTIONS', order);
                    },
                    function (error) {
                        console.log(error);
                    }
                );
        },

        updateListQuestionsItems: function (context, payload) {
            Vue.http
                .LPRequest({
                    type: 'update-list-questions',
                    'items': JSON.stringify(payload.items)
                }).then(
                function (response) {
                    var result = response.body;

                    if (result.success) {
                        console.log(result);
                    }
                },
                function (error) {
                    console.log(error);
                }
            )
        },

        toggleQuestion: function (context, question) {
            if (question.open) {
                context.commit('CLOSE_QUESTION', question);
            } else {
                context.commit('OPEN_QUESTION', question);
            }

            Vue.http
                .LPRequest({
                        type: 'hidden-questions',
                        hidden: context.getters['hiddenQuestions']
                    }
                )
        },

        toggleListQuestions: function (context) {
            var hidden = context.getters['isHiddenListQuestions'];

            if (hidden) {
                context.commit('OPEN_LIST_QUESTIONS');
            } else {
                context.commit('CLOSE_LIST_QUESTIONS');
            }

            Vue.http
                .LPRequest({
                    type: 'hidden-questions',
                    hidden: context.getters['hiddenQuestions']
                })
        }
    };

    return {
        namespaced: true,
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions
    }

})(Vue, LP_Helpers, lp_quiz_editor);

/**
 * Root Store
 *
 * @since 3.0.0
 */
(function (exports, Vue, Vuex, helpers, data) {

    var state = helpers.cloneObject(data.root);

    state.status = 'success';
    state.heartbeat = true;
    state.countCurrentRequest = 0;

    var getters = {
        heartbeat: function (state) {
            return state.heartbeat;
        },
        questionTypes: function (state) {
            return state.types;
        },
        action: function (state) {
            return state.action;
        },
        id: function (state) {
            return state.quiz_id;
        },
        status: function (state) {
            return state.status || 'error';
        },
        currentRequest: function (state) {
            return state.countCurrentRequest || 0;
        },
        nonce: function (state) {
            return state.nonce;
        }
    };

    var mutations = {
        'UPDATE_HEART_BEAT': function (state, status) {
            state.heartbeat = !!status;
        },

        'UPDATE_STATUS': function (state, status) {
            state.status = status;
        },

        'INCREASE_NUMBER_REQUEST': function (state) {
            state.countCurrentRequest++;
        },

        'DECREASE_NUMBER_REQUEST': function (state) {
            state.countCurrentRequest--;
        }
    };

    var actions = {
        heartbeat: function (context) {
            Vue.http
                .LPRequest({
                        type: 'heartbeat'
                    }
                )
                .then(
                    function (response) {
                        var result = response.body;
                        context.commit('UPDATE_HEART_BEAT', !!result.success);
                    },
                    function (error) {
                        context.commit('UPDATE_HEART_BEAT', false);
                    }
                );
        },

        newRequest: function (context) {
            context.commit('INCREASE_NUMBER_REQUEST');
            context.commit('UPDATE_STATUS', 'loading');

            window.onbeforeunload = function () {
                return '';
            }
        },

        requestComplete: function (context, status) {
            context.commit('DECREASE_NUMBER_REQUEST');

            if (context.getters.currentRequest === 0) {
                context.commit('UPDATE_STATUS', status);
                window.onbeforeunload = null;
            }
        }
    };

    exports.LP_Quiz_Store = new Vuex.Store({
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions,
        modules: {
            lqs: LP_List_Quiz_Questions_Store
        }
    });

})(window, Vue, Vuex, LP_Helpers, lp_quiz_editor);


/**
 * HTTP
 *
 * @since 3.0.0
 */
(function (exports, Vue, $store) {

    Vue.http.LPRequest = function (payload) {
        payload['nonce'] = $store.getters.nonce;
        payload['lp-ajax'] = $store.getters.action;
        payload['quiz-id'] = $store.getters.id;

        return Vue.http.post($store.getters.urlAjax,
            payload, {
                emulateJSON: true,
                params: {
                    namespace: 'LPListQuizQuestionsRequest'
                }
            });
    };

    Vue.http.interceptors.push(function (request, next) {
        if (request.params['namespace'] !== 'LPListQuizQuestionsRequest') {
            next();
            return;
        }

        $store.dispatch('newRequest');

        next(function (response) {
            var body = response.body,
                result = response.success || false;

            if (result) {
                $store.dispatch('requestComplete', 'success');
            } else {
                $store.dispatch('requestComplete', 'fail');
            }
        });
    });

})(window, Vue, LP_Quiz_Store);

/**
 * Init app.
 *
 * @since 3.0.0
 */
(function ($, Vue, $store) {
    $(document).ready(function () {
        window.LP_Quiz_Editor = new Vue({
            el: '#quiz-editor-v2',
            template: '<lp-quiz-editor></lp-quiz-editor>'
        });
    });
})(jQuery, Vue, LP_Quiz_Store);