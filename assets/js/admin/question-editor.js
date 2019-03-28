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

window.$Vue = window.$Vue || Vue;
window.$Vuex = window.$Vuex || Vuex;

var $VueHTTP = Vue.http;

/**
 * Root Store
 *
 * @since 3.0.0
 */
(function (exports, helpers, data) {

    var state = helpers.cloneObject(data.root),
        i18n = helpers.cloneObject(data.i18n);

    state.status = 'successful';
    state.countCurrentRequest = 0;

    var getters = {
        id: function (state) {
            return state.id;
        },
        type: function (state) {
            return state.type;
        },
        code: function (state) {
            return Date.now();
        }
        ,
        autoDraft: function (state) {
            return state.auto_draft;
        },
        answers: function (state) {
            return Object.values(state.answers) || [];
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
        },
        externalComponent: function (state) {
            return state.externalComponent || [];
        },
        state: function (state) {
            return state;
        },
        i18n: function (state) {
            return i18n;
        }
    };

    var mutations = {

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

    var actions = {

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

    exports.LP_Question_Store = new $Vuex.Store({
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions
    });

})(window, LP_Helpers, lp_question_editor);


/**
 * HTTP
 *
 * @since 3.0.0
 */
(function (exports, $store) {
    var $ = jQuery,
        $publishingAction = null;

    LP.Request = function (payload) {
        $publishingAction = $('#publishing-action');

        payload['id'] = $store.getters.id;
        payload['nonce'] = $store.getters.nonce;
        payload['lp-ajax'] = $store.getters.action;
        payload['code'] = $store.getters.code;

        $publishingAction.find('#publish').addClass('disabled');
        $publishingAction.find('.spinner').addClass('is-active');
        $publishingAction.addClass('code-' + payload['code']);

        return $VueHTTP.post($store.getters.urlAjax,
            payload,
            {
                emulateJSON: true,
                params: {
                    namespace: 'LPQuestionEditorRequest',
                    code: payload['code'],
                }
            });
    };

    $VueHTTP.interceptors.push(function (request, next) {
        if (request.params['namespace'] !== 'LPQuestionEditorRequest') {
            next();
            return;
        }

        $store.dispatch('newRequest');

        next(function (response) {
            if (!jQuery.isPlainObject(response.body)) {
                response.body = LP.parseJSON(response.body);
            }

            var body = response.body;
            var result = body.success || false;

            if (result) {
                $store.dispatch('requestCompleted', 'successful');
            } else {
                $store.dispatch('requestCompleted', 'failed');
            }
            $publishingAction.removeClass('code-' + request.params.code);
            if (!$publishingAction.attr('class')) {
                $publishingAction.find('#publish').removeClass('disabled');
                $publishingAction.find('.spinner').removeClass('is-active');
            }

        });
    });
})(window, LP_Question_Store);


/**
 * Init app.
 *
 * @since 3.0.0
 */
(function ($, $store) {
    $(document).ready(function () {
        window.LP_Question_Editor = new $Vue({
            el: '#admin-editor-lp_question',
            template: '<lp-question-editor></lp-question-editor>'
        });
    });
})(jQuery, LP_Question_Store);