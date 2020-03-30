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
 * Choose quiz items modal store.
 *
 * @since 3.0.0
 */
var LP_Choose_Quiz_Items_Modal_Store = (function (exports, helpers, data) {

    var state = helpers.cloneObject(data.chooseItems);
    state.quizId = false;
    state.pagination = '';
    state.status = '';

    var getters = {
        status: function (state) {
            return state.status;
        },
        pagination: function (state) {
            return state.pagination;
        },
        items: function (state, _getters) {
            return state.items.map(function (item) {
                var find = _getters.addedItems.find(function (_item) {
                    return item.id === _item.id;
                });

                item.added = !!find;

                return item;
            });
        },
        code: function (state) {
            return Date.now();
        },
        addedItems: function (state) {
            return state.addedItems;
        },
        isOpen: function (state) {
            return state.open;
        },
        quiz: function (state) {
            return state.quizId;
        }
    };

    var mutations = {
        'TOGGLE': function (state) {
            state.open = !state.open;
        },
        'SET_QUIZ': function (state, quizId) {
            state.quizId = quizId;
        },
        'SET_LIST_ITEMS': function (state, items) {
            state.items = items;
        },
        'ADD_ITEM': function (state, item) {
            state.addedItems.push(item);
        },
        'REMOVE_ADDED_ITEM': function (state, item) {
            state.addedItems.forEach(function (_item, index) {
                if (_item.id === item.id) {
                    state.addedItems.splice(index, 1);
                }
            })
        },
        'RESET': function (state) {
            state.addedItems = [];
            state.items = [];
        },
        'UPDATE_PAGINATION': function (state, pagination) {
            state.pagination = pagination;
        },
        'SEARCH_ITEM_REQUEST': function (state) {
            state.status = 'loading';
        },
        'SEARCH_ITEM_SUCCESS': function (state) {
            state.status = 'successful';
        },
        'SEARCH_ITEM_FAIL': function (state) {
            state.status = 'fail';
        }
    };

    var actions = {

        toggle: function (context) {
            context.commit('TOGGLE');
        },

        // open modal
        open: function (context, quizId) {
            context.commit('SET_QUIZ', quizId);
            context.commit('RESET');
            context.commit('TOGGLE');
        },

        // query available question
        searchItems: function (context, payload) {
            context.commit('SEARCH_ITEM_REQUEST');

            LP.Request({
                type: 'search-items',
                query: payload.query,
                page: payload.page,
                exclude: JSON.stringify([])
            }).then(
                function (response) {
                    var result = response.body;

                    if (!result.success) {
                        return;
                    }

                    var data = result.data;

                    context.commit('SET_LIST_ITEMS', data.items);
                    context.commit('UPDATE_PAGINATION', data.pagination);
                    context.commit('SEARCH_ITEM_SUCCESS');
                },
                function (error) {
                    context.commit('SEARCH_ITEMS_FAIL');

                    console.log(error);
                }
            );
        },

        // add question
        addItem: function (context, item) {
            context.commit('ADD_ITEM', item);
        },

        // remove question
        removeItem: function (context, index) {
            context.commit('REMOVE_ADDED_ITEM', index);
        },

        addQuestionsToQuiz: function (context, quiz) {
            var items = context.getters.addedItems;

            if (items.length > 0) {
                LP.Request({
                    type: 'add-questions-to-quiz',
                    items: JSON.stringify(items),
                    draft_quiz: JSON.stringify(quiz)
                }).then(
                    function (response) {
                        var result = response.body;

                        if (result.success) {
                            var questions = result.data;

                            // update quiz list questions
                            context.commit('lqs/SET_QUESTIONS', questions, {root: true});
                            context.commit('TOGGLE');
                        }
                    },
                    function (error) {
                        console.log(error);
                    }
                )
            }
        }
    };

    return {
        namespaced: true,
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions
    }

})(window, LP_Helpers, lp_quiz_editor);

/**
 * I18n Store
 *
 * @since 3.0.0
 */
var LP_Quiz_i18n_Store = (function (helpers, data) {

    var state = helpers.cloneObject(data.i18n);

    var getters = {
        all: function (state) {
            return state;
        }
    };

    return {
        namespaced: true,
        state: state,
        getters: getters
    }

})(LP_Helpers, lp_quiz_editor);

/**
 * List quiz questions store.
 *
 * @since 3.0.0
 */
var LP_List_Quiz_Questions_Store = (function (helpers, data, $) {

    var state = helpers.cloneObject(data.listQuestions);

    state.statusUpdateQuestions = {};
    state.statusUpdateQuestionItem = {};
    state.statusUpdateQuestionAnswer = {};

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

    var mutations = {
        'SORT_QUESTIONS': function (state, orders) {
            state.questions = state.questions.map(function (question) {
                question.order = orders[question.id];
                return question;
            });
        },
        'SORT_QUESTION_ANSWERS': function (state, orders) {
            state.questions = state.questions.map(function (question) {
                question.answers.answer_order = orders[question.answers.question_answer_id];
                return question;
            })
        },
        'ADD_QUESTION_ANSWER': function (state, payload) {
            state.questions = state.questions.map(function (question) {
                if (question.id === payload.question_id) {
                    var found = false;
                    if (payload.answer.temp_id) {
                        for (var i = 0, n = question.answers.length; i < n; i++) {
                            if (question.answers[i].question_answer_id == payload.answer.temp_id) {
                                found = true;
                                $Vue.set(question.answers, i, payload.answer);
                            }
                        }
                    }

                    !found && question.answers.push(payload.answer);
                    return question;
                } else {
                    return question;
                }
            })
        },
        'CHANGE_QUESTION_CORRECT_ANSWERS': function (state, data) {
            state.questions = state.questions.map(function (question) {
                if (parseInt(question.id) === data.id) {
                    question.answers = data.answers;
                }
                return question;
            });
        },
        'SET_QUESTIONS': function (state, questions) {
            state.questions = questions;
        },
        'ADD_NEW_QUESTION': function (state, question) {
            var found = false;
            if (question.temp_id) {
                for (var i = 0, n = state.questions.length; i < n; i++) {
                    if (state.questions[i].id === question.temp_id) {
                        $Vue.set(state.questions, i, question);
                        found = true;
                        break;
                    }
                }
            }
            if (!found) {
                var _last_child = $('.lp-list-questions .main > div:last-child');
                if (_last_child.length) {
                    var _offset = _last_child.offset().top;
                    $('html,body').animate({scrollTop: _offset});
                }

                state.questions.push(question);
            }
        },
        'CHANGE_QUESTION_TYPE': function (state, data) {
            state.questions = state.questions.map(function (question) {
                if (parseInt(question.id) === data.id) {
                    question.answers = data.answers;
                    question.type = data.type;
                    question.open = true;
                }
                return question;
            });
        },
        'REMOVE_QUESTION': function (state, item) {
            var questions = state.questions,
                index = questions.indexOf(item);

            if (item.temp_id) {
                state.questions[index].id = item.temp_id;
            } else {
                state.questions.splice(index, 1);
            }
        },
        'DELETE_QUESTION_ANSWER': function (state, payload) {
            var question_id = payload.question_id,
                answer_id = payload.answer_id;

            state.questions = state.questions.map(function (question) {
                if (question.id === question_id) {
                    var answers = question.answers;
                    answers.forEach(function (answer) {
                        if (answer.question_answer_id === answer_id) {
                            var index = answers.indexOf(answer);
                            answers.splice(index, 1);
                        }
                    })
                }
                return question;
            });
        },
        'REMOVE_QUESTIONS': function () {
            // code
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
                if ((question.id) === _question.id) {
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
        'UPDATE_QUESTION_REQUEST': function (state, questionId) {
            $Vue.set(state.statusUpdateQuestionItem, questionId, 'updating');
        },
        'UPDATE_QUESTION_SUCCESS': function (state, questionID) {
            $Vue.set(state.statusUpdateQuestionItem, questionID, 'successful');
        },
        'UPDATE_QUESTION_FAILURE': function (state, questionID) {
            $Vue.set(state.statusUpdateQuestionItem, questionID, 'failed')
        },

        'UPDATE_QUESTION_ANSWER_REQUEST': function (state, question_id) {
            $Vue.set(state.statusUpdateQuestionAnswer, question_id, 'updating');
        },
        'UPDATE_QUESTION_ANSWER_SUCCESS': function (state, question_id) {
            $Vue.set(state.statusUpdateQuestionAnswer, question_id, 'successful');
        },
        'UPDATE_QUESTION_ANSWER_FAIL': function (state, question_id) {
            $Vue.set(state.statusUpdateQuestionAnswer, question_id, 'failed');
        },
        'DELETE_ANSWER': function (state, data) {
            state.questions.map(function (question, index) {
                if (question.id == data.question_id) {
                    for (var i = 0, n = question.answers.length; i < n; i++) {
                        if (question.answers[i].question_answer_id == data.answer_id) {
                            question.answers[i].question_answer_id = data.temp_id;
                            //state.questions[index].answers.splice(i, 1);
                            break;
                        }
                    }
                    return false;
                }
            })

        }
    };

    var actions = {

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

    return {
        namespaced: true,
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions
    }

})(LP_Helpers, lp_quiz_editor, jQuery);

/**
 * Root Store
 *
 * @since 3.0.0
 */
(function (exports, helpers, data) {

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
        defaultNewQuestionType: function (state) {
            return state.default_new
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

        'UPDATE_NEW_QUESTION_TYPE': function (state, type) {
            state.default_new = type;
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
            LP.Request({
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

    exports.LP_Quiz_Store = new $Vuex.Store({
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions,
        modules: {
            cqi: LP_Choose_Quiz_Items_Modal_Store,
            i18n: LP_Quiz_i18n_Store,
            lqs: LP_List_Quiz_Questions_Store
        }
    });

})(window, LP_Helpers, lp_quiz_editor);


/**
 * HTTP
 *
 * @since 3.0.0
 */
(function (exports, $store) {
    var $ = jQuery,
        $publishingAction = null;

    LP.Request = function (payload) {
        payload['id'] = $store.getters.id;
        payload['nonce'] = $store.getters.nonce;
        payload['lp-ajax'] = $store.getters.action;
        payload['code'] = Date.now();

        $publishingAction = $('#publishing-action');

        $publishingAction.find('#publish').addClass('disabled');
        $publishingAction.find('.spinner').addClass('is-active');
        $publishingAction.addClass('code-' + payload['code']);

        return $VueHTTP.post($store.getters.urlAjax,
            payload, {
                emulateJSON: true,
                params: {
                    namespace: 'LPListQuizQuestionsRequest',
                    code: payload['code'],
                }
            });
    };

    $VueHTTP.interceptors.push(function (request, next) {
        if (request.params['namespace'] !== 'LPListQuizQuestionsRequest') {
            next();
            return;
        }

        $store.dispatch('newRequest');

        next(function (response) {

            if (!jQuery.isPlainObject(response.body)) {
                response.body = LP.parseJSON(response.body);
            }

            var body = response.body,
                result = body.success || false;

            if (result) {
                $store.dispatch('requestComplete', 'success');
            } else {
                $store.dispatch('requestComplete', 'fail');
            }

            $publishingAction.removeClass('code-' + request.params.code);
            if (!$publishingAction.attr('class')) {
                $publishingAction.find('#publish').removeClass('disabled');
                $publishingAction.find('.spinner').removeClass('is-active');
            }

        });
    });

})(window, LP_Quiz_Store);

/**
 * Init app.
 *
 * @since 3.0.0
 */
(function ($, $store) {
    $(document).ready(function () {
        window.LP_Quiz_Editor = new $Vue({
            el: '#admin-editor-lp_quiz',
            template: '<lp-quiz-editor></lp-quiz-editor>'
        });
    });
})(jQuery, LP_Quiz_Store);