const $ = window.jQuery;
const QuestionList = {
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
        console.log('A')
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

export default QuestionList;