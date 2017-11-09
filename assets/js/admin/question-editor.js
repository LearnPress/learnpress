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
 * Root Store
 *
 * @since 3.0.0
 */
(function (exports, Vue, Vuex, helpers, data) {

    var state = helpers.cloneObject(data.root);

    state.status = 'successful';
    state.countCurrentRequest = 0;

    var getters = {
        id: function (state) {
            return state.id;
        },
        type: function (state) {
            return state.type;
        },
        headings: function (state) {
            return state.headings || [];
        },
        answers: function (state) {
            return Object.values(state.answers) || [];
        },
        supportAnswerOption: function (state) {
            return state.supportAnswerOption;
        },
        settings: function (state) {
            return state.setting;
        },
        types: function (state) {
            return state.questionTypes || [];
        },
        numberCorrect: function (state) {
            var correct = 0;
            Object.keys(state.answers).forEach(function (key) {
                if (state.answers[key].is_true === 'yes') {
                    correct += 1;
                }
            });
            return correct;
        },
        status: function (state) {
            return state.status;
        },
        currentRequest: function (state) {
            return state.countCurrentRequest || 0;
        },
        action: function (state) {
            return state.action;
        },
        nonce: function (state) {
            return state.nonce;
        }
    };

    var mutations = {

        'UPDATE_STATUS': function (state, status) {
            state.status = status;
        },

        'SORT_ANSWERS': function (state, order) {
            // code
        },

        'DELETE_ANSWER': function (state, order) {
            //state.answers.splice(order, 1);
        },

        'INCREASE_NUMBER_REQUEST': function (state) {
            state.countCurrentRequest++;
        },

        'DECREASE_NUMBER_REQUEST': function (state) {
            state.countCurrentRequest--;
        }
    };

    var actions = {

        'changeQuestionType': function (context, questionType) {

            Vue.http.LPRequest({
                type: 'change-question-type',
                question_type: questionType
            }).then(
                // code
            )

        },

        updateAnswerTitle: function (context, answer) {

            Vue.http.LPRequest({
                type: 'update-answer-title',
                answer: JSON.stringify(answer)
            }).then(
                // code
            )

        },

        updateCorrectAnswer: function (context, correct) {

            Vue.http.LPRequest({
                type: 'change-correct',
                correct: JSON.stringify(correct)
            })

        },

        updateAnswersOrder: function (context, order) {

            Vue.http.LPRequest({
                type: 'sort-answer',
                order: order
            }).then(
                function (response) {
                    var result = response.body;
                    if (result.success) {
                        context.commit('SORT_ANSWERS', order);
                    }
                }
            )
        },

        newAnswer: function (context) {

            Vue.http.LPRequest({
                type: 'new-answer'
            })

        },

        deleteAnswer: function (context, payload) {

            Vue.http.LPRequest({
                type: 'delete-answer',
                answer_id: payload.id
            }).then(
                function (response) {
                    var result = response.body;

                    if (!result.success) {
                        return;
                    }

                    var data = result.data;
                    if (data.status) {
                        context.commit('DELETE_ANSWER', payload.order - 1);
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

    exports.LP_Question_Store = new Vuex.Store({
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions
    });

})(window, Vue, Vuex, LP_Helpers, lp_question_editor);


/**
 * HTTP
 *
 * @since 3.0.0
 */
(function (exports, Vue, $store) {

    Vue.http.LPRequest = function (payload) {
        payload['id'] = $store.getters.id;
        payload['nonce'] = $store.getters.nonce;
        payload['lp-ajax'] = $store.getters.action;

        return Vue.http.post($store.getters.urlAjax,
            payload,
            {
                emulateJSON: true,
                params: {
                    namespace: 'LPQuestionEditorRequest'
                }
            });
    };

    Vue.http.interceptors.push(function (request, next) {
        if (request.params['namespace'] !== 'LPQuestionEditorRequest') {
            next();
            return;
        }

        $store.dispatch('newRequest');

        next(function (response) {
            var body = response.body;
            var result = body.success || false;

            if (result) {
                $store.dispatch('requestCompleted', 'successful');
            } else {
                $store.dispatch('requestCompleted', 'failed');
            }
        });
    });
})(window, Vue, LP_Question_Store);


/**
 * Init app.
 *
 * @since 3.0.0
 */
(function ($, Vue, $store) {
    $(document).ready(function () {
        window.LP_Question_Editor = new Vue({
            el: '#lp-admin-question-editor',
            template: '<lp-question-editor></lp-question-editor>'
        });
    });
})(jQuery, Vue, LP_Question_Store);