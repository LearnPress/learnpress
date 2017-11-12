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
 * Choose quiz items modal store.
 *
 * @since 3.0.0
 */
var LP_Choose_Quiz_Items_Modal_Store = (function (exports, Vue, helpers, data) {

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

        open: function (context, quizId) {
            context.commit('SET_QUIZ', quizId);
            context.commit('RESET');
            context.commit('TOGGLE');
        },

        addItem: function (context, item) {
            context.commit('ADD_ITEM', item);
        },

        removeItem: function (context, index) {
            context.commit('REMOVE_ADDED_ITEM', index);
        },

        searchItems: function (context, payload) {
            context.commit('SEARCH_ITEM_REQUEST');

            Vue.http
                .LPRequest({
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

        addItemsToQuiz: function (context) {
            var items = context.getters.addedItems;

            if (items.length > 0) {
                Vue.http
                    .LPRequest({
                        type: 'add-items-to-quiz',
                        'quiz-id': context.getters.quiz,
                        items: JSON.stringify(items)
                    }).then(
                    function (response) {
                        var result = response.body;

                        if (result.success) {
                            var items = result.data;
                            context.commit('lqs/UPDATE_QUIZ_QUESTIONS', items, {root: true});
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

})(window, Vue, LP_Helpers, lp_quiz_editor);

/**
 * I18n Store
 *
 * @since 3.0.0
 */
var LP_Quiz_i18n_Store = (function (Vue, helpers, data) {

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

})(Vue, LP_Helpers, lp_quiz_editor);

/**
 * List quiz questions store.
 *
 * @since 3.0.0
 */
var LP_List_Quiz_Questions_Store = (function (Vue, helpers, data) {

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
        'ADD_QUESTION_ANSWER': function (state, answer) {
            state.questions = state.questions.map(function (question) {
                question.answers.push(answer);
                return question;
            })
        },
        'SET_QUESTIONS': function (state, questions) {
            state.questions = questions;
        },
        'ADD_NEW_QUESTION': function (state, question) {
            state.questions.push(question);
        },
        'UPDATE_QUIZ_QUESTIONS': function (state, questions) {
            questions.forEach(function (question) {
                state.questions.push(question);
            });
        },
        'CHANGE_QUESTION_TYPE': function (state, data) {
            state.questions = state.questions.map(function (question) {
                if (question.id === data.id) {
                    question.answers = data.answers;
                    question.type = data.type;
                }
                return question;
            });
        },
        'REMOVE_QUESTION': function (state, item) {

            var questions = state.questions,
                index = questions.indexOf(item);

            questions.splice(index, 1);
        },
        'DELETE_QUESTION_ANSWER': function (state, payload) {
            var question_id = payload.question_id,
                answer_id = payload.answer_id;

            state.questions = state.questions.map(function (question) {
                if (question.id === question_id) {
                    var answers = question.answers;
                    answers.forEach(function (answer) {
                        if (parseInt(answer.question_answer_id) === answer_id) {
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
        'UPDATE_QUESTION_REQUEST': function (state, questionId) {
            Vue.set(state.statusUpdateQuestionItem, questionId, 'updating');
        },
        'UPDATE_QUESTION_SUCCESS': function (state, questionID) {
            Vue.set(state.statusUpdateQuestionItem, questionID, 'successful');
        },
        'UPDATE_QUESTION_FAILURE': function (state, questionID) {
            Vue.set(state.statusUpdateQuestionItem, questionID, 'failed')
        },

        'UPDATE_QUESTION_ANSWER_REQUEST': function (state, question_id) {
            Vue.set(state.statusUpdateQuestionAnswer, question_id, 'updating');
        },
        'UPDATE_QUESTION_ANSWER_SUCCESS': function (state, question_id) {
            Vue.set(state.statusUpdateQuestionAnswer, question_id, 'successful');
        },
        'UPDATE_QUESTION_ANSWER_FAIL': function (state, question_id) {
            Vue.set(state.statusUpdateQuestionAnswer, question_id, 'failed');
        }
    };

    var actions = {

        newQuestion: function (context, question) {

            Vue.http
                .LPRequest({
                    type: 'new-question',
                    'question': JSON.stringify(question)
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

        cloneQuestion: function (context, question) {
            Vue.http
                .LPRequest({
                    type: 'clone-question',
                    question: JSON.stringify(question)
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
                )
        },

        removeQuestion: function (context, question) {

            Vue.http
                .LPRequest({
                    type: 'remove-question',
                    question: question
                })
                .then(
                    function (response) {
                        var result = response.body;

                        if (result.success) {
                            context.commit('REMOVE_QUESTION', question);
                        }
                    },
                    function (error) {
                        console.error(error);
                    }
                )
        },

        deleteQuestion: function (context, question) {

            Vue.http
                .LPRequest({
                    type: 'delete-question',
                    question: question
                })
                .then(function () {
                    context.commit('REMOVE_QUESTION', question);
                    context.commit('UPDATE_QUESTION_SUCCESS', question.id);
                })
                .catch(function () {
                    context.commit('UPDATE_QUESTION_FAILURE', question.id);
                })
        },

        updateQuestionTitle: function (context, question) {

            context.commit('UPDATE_QUESTION_REQUEST', question.id);

            Vue.http.LPRequest({
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

        updateQuestionContent: function (context, question) {

            context.commit('UPDATE_QUESTION_REQUEST', question.id);

            Vue.http.LPRequest({
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

            Vue.http.LPRequest({
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
        },

        changeQuestionType: function (context, payload) {

            context.commit('UPDATE_QUESTION_REQUEST', payload.question.id);

            Vue.http
                .LPRequest({
                    type: 'change-question-type',
                    question: JSON.stringify(payload.question),
                    'new-type': payload.newType
                })
                .then(function (response) {
                    var result = response.body;

                    if (result.success) {
                        var question = result.data;
                        context.commit('CHANGE_QUESTION_TYPE', question);
                        context.commit('UPDATE_QUESTION_SUCCESS', payload.question.id);
                        context.commit('OPEN_QUESTION', question);
                    }
                })
                .catch(function () {
                    context.commit('UPDATE_QUESTION_FAILURE', payload.question.id);

                })
        },

        newQuestionAnswer: function (context, question_id) {
            context.commit('UPDATE_QUESTION_REQUEST', question_id);

            Vue.http
                .LPRequest({
                    type: 'new-question-answer',
                    question_id: question_id
                })
                .then(
                    function (response) {

                        var result = response.body;

                        if (result.success) {
                            var answer = result.data;
                            context.commit('UPDATE_QUESTION_SUCCESS', question_id);
                        }
                    },
                    function (error) {
                        context.commit('UPDATE_QUESTION_FAILURE', question_id);
                        console.error(error);
                    }
                )
        },

        updateQuestionsOrder: function (context, order) {
            Vue.http
                .LPRequest({
                    type: 'sort-questions',
                    order: JSON.stringify(order)
                })
                .then(
                    function (response) {
                        context.commit('SORT_QUESTIONS', order);
                    },
                    function (error) {
                        console.log(error);
                    }
                );
        },

        updateQuestionCorrectAnswer: function (context, payload) {
            context.commit('UPDATE_QUESTION_REQUEST', payload.question_id);

            Vue.http.LPRequest({
                type: 'change-question-correct-answer',
                question_id: payload.question_id,
                correct: JSON.stringify(payload.correct)
            }).then(
                function (response) {
                    var result = response.body;
                    if (result.success) {
                        context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
                    }
                },
                function (error) {
                    context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);
                    console.log(error);
                }
            )
        },

        updateQuestionAnswerTitle: function (context, payload) {

            Vue.http.LPRequest({
                type: 'update-question-answer-title',
                question_id: parseInt(payload.question_id),
                answer: JSON.stringify(payload.answer)
            }).then(
                function () {
                    context.commit('UPDATE_QUESTION_ANSWER_SUCCESS', parseInt(payload.question_id));
                }
            ).catch(
                function () {
                    context.commit('UPDATE_QUESTION_ANSWER_FAILURE', parserInt(payload.question_id));
                })
        },

        updateQuestionAnswer: function (context, payload) {
            Vue.http
                .LPRequest({
                    type: 'update-question-answer',
                    questionId: parseInt(payload.questionId),
                    answer: JSON.stringify(payload.answer)
                })
                .then(function () {
                    context.commit('UPDATE_QUESTION_ANSWER_SUCCESS', parseInt(payload.questionId));
                })
                .catch(function () {
                    context.commit('UPDATE_QUESTION_ANSWER_FAILURE', parserInt(payload.questionId));
                })

        },

        updateQuestionAnswersOrder: function (context, payload) {
            Vue.http.LPRequest({
                type: 'sort-question-answers',
                question_id: payload.question_id,
                order: JSON.stringify(payload.order)
            }).then(
                function (response) {
                    var result = response.body,
                        order = result.data;
                    context.commit('SORT_QUESTION_ANSWERS', order);
                },
                function (error) {
                    console.log(error);
                }
            )
        },

        deleteQuestionAnswer: function (context, payload) {
            Vue.http.LPRequest({
                type: 'delete-question-answer',
                question_id: payload.question_id,
                answer_id: payload.answer_id
            }).then(
                function (response) {
                    var result = response.body;

                    if (result.success) {
                        var data = result.data;

                        context.commit('DELETE_QUESTION_ANSWER', {
                            question_id: payload.question_id,
                            answer_id: payload.answer_id
                        });
                    }
                },
                function (error) {
                    console.log(error);
                }
            )
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
                        // console.log(result);
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

        toggleAll: function (context) {
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
            cqi: LP_Choose_Quiz_Items_Modal_Store,
            i18n: LP_Quiz_i18n_Store,
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
        payload['id'] = $store.getters.id;
        payload['nonce'] = $store.getters.nonce;
        payload['lp-ajax'] = $store.getters.action;

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
                result = body.success || false;

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
            el: '#admin-quiz-editor',
            template: '<lp-quiz-editor></lp-quiz-editor>'
        });
    });
})(jQuery, Vue, LP_Quiz_Store);