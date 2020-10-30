/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/quiz.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/actions/modal-quiz-items.js":
/*!**********************************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/actions/modal-quiz-items.js ***!
  \**********************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var ModalQuizItems = {
  toggle: function toggle(context) {
    context.commit('TOGGLE');
  },
  // open modal
  open: function open(context, quizId) {
    context.commit('SET_QUIZ', quizId);
    context.commit('RESET');
    context.commit('TOGGLE');
  },
  // query available question
  searchItems: function searchItems(context, payload) {
    context.commit('SEARCH_ITEM_REQUEST');
    LP.Request({
      type: 'search-items',
      query: payload.query,
      page: payload.page,
      exclude: JSON.stringify([])
    }).then(function (response) {
      var result = response.body;

      if (!result.success) {
        return;
      }

      var data = result.data;
      context.commit('SET_LIST_ITEMS', data.items);
      context.commit('UPDATE_PAGINATION', data.pagination);
      context.commit('SEARCH_ITEM_SUCCESS');
    }, function (error) {
      context.commit('SEARCH_ITEMS_FAIL');
      console.log(error);
    });
  },
  // add question
  addItem: function addItem(context, item) {
    context.commit('ADD_ITEM', item);
  },
  // remove question
  removeItem: function removeItem(context, index) {
    context.commit('REMOVE_ADDED_ITEM', index);
  },
  addQuestionsToQuiz: function addQuestionsToQuiz(context, quiz) {
    var items = context.getters.addedItems;

    if (items.length > 0) {
      LP.Request({
        type: 'add-questions-to-quiz',
        items: JSON.stringify(items),
        draft_quiz: JSON.stringify(quiz)
      }).then(function (response) {
        var result = response.body;

        if (result.success) {
          var questions = result.data; // update quiz list questions

          context.commit('lqs/SET_QUESTIONS', questions, {
            root: true
          });
          context.commit('TOGGLE');
        }
      }, function (error) {
        console.log(error);
      });
    }
  }
};
/* harmony default export */ __webpack_exports__["default"] = (ModalQuizItems);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/actions/question-list.js":
/*!*******************************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/actions/question-list.js ***!
  \*******************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var $ = window.jQuery;
var QuestionList = {
  toggleAll: function toggleAll(context) {
    var hidden = context.getters['isHiddenListQuestions'];

    if (hidden) {
      context.commit('OPEN_LIST_QUESTIONS');
    } else {
      context.commit('CLOSE_LIST_QUESTIONS');
    }

    LP.Request({
      type: 'hidden-questions',
      hidden: context.getters['hiddenQuestions']
    });
  },
  updateQuizQuestionsHidden: function updateQuizQuestionsHidden(context, data) {
    LP.Request($.extend({}, data, {
      type: 'update-quiz-questions-hidden'
    }));
  },
  newQuestion: function newQuestion(context, payload) {
    var newQuestion = JSON.parse(JSON.stringify(payload.question));
    newQuestion.settings = {};
    context.commit('ADD_NEW_QUESTION', newQuestion);
    LP.Request({
      type: 'new-question',
      question: JSON.stringify(payload.question),
      draft_quiz: JSON.stringify(payload.quiz)
    }).then(function (response) {
      var result = response.body;

      if (result.success) {
        // update new question type
        context.commit('UPDATE_NEW_QUESTION_TYPE', payload.question.type, {
          root: true
        }); // update list quiz questions

        context.commit('ADD_NEW_QUESTION', result.data);
        context.commit('CLOSE_LIST_QUESTIONS');
        context.commit('OPEN_QUESTION', result.data);
      }
    }, function (error) {
      console.log(error);
    });
  },
  updateQuestionsOrder: function updateQuestionsOrder(context, order) {
    LP.Request({
      type: 'sort-questions',
      order: JSON.stringify(order)
    }).then(function (response) {
      context.commit('SORT_QUESTIONS', order);
    }, function (error) {
      console.log(error);
    });
  },
  updateQuestionTitle: function updateQuestionTitle(context, question) {
    context.commit('UPDATE_QUESTION_REQUEST', question.id);
    LP.Request({
      type: 'update-question-title',
      question: JSON.stringify(question)
    }).then(function () {
      context.commit('UPDATE_QUESTION_SUCCESS', question.id);
    })["catch"](function () {
      context.commit('UPDATE_QUESTION_FAILURE', question.id);
    });
  },
  changeQuestionType: function changeQuestionType(context, payload) {
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
        context.commit('UPDATE_NEW_QUESTION_TYPE', question.type.key, {
          root: true
        });
        context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
      }
    })["catch"](function () {
      context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);
    });
  },
  isHiddenQuestionsSettings: function isHiddenQuestionsSettings(context, id) {},
  cloneQuestion: function cloneQuestion(context, question) {
    LP.Request({
      type: 'clone-question',
      question: JSON.stringify(question)
    }).then(function (response) {
      var result = response.body;

      if (result.success) {
        var question = result.data;
        context.commit('ADD_NEW_QUESTION', result.data);
        context.commit('UPDATE_NEW_QUESTION_TYPE', question.type.key, {
          root: true
        });
      }
    }, function (error) {
      console.log(error);
    });
  },
  removeQuestion: function removeQuestion(context, question) {
    var question_id = question.id;
    question.temp_id = LP.uniqueId();
    context.commit('REMOVE_QUESTION', question);
    LP.Request({
      type: 'remove-question',
      question_id: question_id
    }).then(function (response) {
      var result = response.body;

      if (result.success) {
        question.id = question.temp_id;
        question.temp_id = 0;
        context.commit('REMOVE_QUESTION', question);
      }
    }, function (error) {
      console.error(error);
    });
  },
  deleteQuestion: function deleteQuestion(context, question) {
    var question_id = question.id;
    question.temp_id = LP.uniqueId();
    context.commit('REMOVE_QUESTION', question);
    LP.Request({
      type: 'delete-question',
      question_id: question_id
    }).then(function () {
      question.id = question.temp_id;
      question.temp_id = 0;
      context.commit('REMOVE_QUESTION', question);
      context.commit('UPDATE_QUESTION_SUCCESS', question.id);
    })["catch"](function () {
      context.commit('UPDATE_QUESTION_FAILURE', question.id);
    });
  },
  toggleQuestion: function toggleQuestion(context, question) {
    if (question.open) {
      context.commit('CLOSE_QUESTION', question);
    } else {
      context.commit('OPEN_QUESTION', question);
    }

    LP.Request({
      type: 'hidden-questions',
      hidden: context.getters['hiddenQuestions']
    });
  },
  updateQuestionAnswersOrder: function updateQuestionAnswersOrder(context, payload) {
    context.commit('UPDATE_QUESTION_REQUEST', payload.question_id);
    LP.Request({
      type: 'sort-question-answers',
      question_id: payload.question_id,
      order: JSON.stringify(payload.order)
    }).then(function (response) {
      var result = response.body,
          order = result.data;
      context.commit('SORT_QUESTION_ANSWERS', order);
      context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
    }, function (error) {
      context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);
      console.log(error);
    });
  },
  updateQuestionAnswerTitle: function updateQuestionAnswerTitle(context, payload) {
    context.commit('UPDATE_QUESTION_REQUEST', payload.question_id);
    LP.Request({
      type: 'update-question-answer-title',
      question_id: parseInt(payload.question_id),
      answer: JSON.stringify(payload.answer)
    }).then(function () {
      context.commit('UPDATE_QUESTION_ANSWER_SUCCESS', parseInt(payload.question_id));
      context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
    })["catch"](function () {
      context.commit('UPDATE_QUESTION_ANSWER_FAILURE', parseInt(payload.question_id));
      context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);
    });
  },
  updateQuestionCorrectAnswer: function updateQuestionCorrectAnswer(context, payload) {
    context.commit('UPDATE_QUESTION_REQUEST', payload.question_id);
    LP.Request({
      type: 'change-question-correct-answer',
      question_id: payload.question_id,
      correct: JSON.stringify(payload.correct)
    }).then(function (response) {
      var result = response.body;

      if (result.success) {
        context.commit('CHANGE_QUESTION_CORRECT_ANSWERS', result.data);
        context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
      }
    }, function (error) {
      context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);
      console.log(error);
    });
  },
  deleteQuestionAnswer: function deleteQuestionAnswer(context, payload) {
    payload.temp_id = LP.uniqueId();
    context.commit('DELETE_ANSWER', payload);
    context.commit('UPDATE_QUESTION_REQUEST', payload.question_id);
    LP.Request({
      type: 'delete-question-answer',
      question_id: payload.question_id,
      answer_id: payload.answer_id
    }).then(function (response) {
      var result = response.body;

      if (result.success) {
        context.commit('DELETE_QUESTION_ANSWER', {
          question_id: payload.question_id,
          answer_id: payload.temp_id //answer_id: payload.answer_id

        });
        context.commit('UPDATE_QUESTION_SUCCESS', payload.question_id);
      }
    }, function (error) {
      context.commit('UPDATE_QUESTION_FAILURE', payload.question_id);
      console.log(error);
    });
  },
  newQuestionAnswer: function newQuestionAnswer(context, data) {
    var temp_id = LP.uniqueId(),
        question_id = data.question_id;
    context.commit('UPDATE_QUESTION_REQUEST', question_id);
    context.commit('ADD_QUESTION_ANSWER', {
      question_id: question_id,
      answer: {
        'text': LP_Quiz_Store.getters['i18n/all'].new_option,
        'question_answer_id': temp_id
      }
    });
    LP.Request({
      type: 'new-question-answer',
      question_id: question_id,
      question_answer_id: temp_id
    }).then(function (response) {
      var result = response.body;

      if (result.success) {
        var answer = result.data;
        context.commit('ADD_QUESTION_ANSWER', {
          question_id: question_id,
          answer: answer
        });
        context.commit('UPDATE_QUESTION_SUCCESS', question_id);
        data.success && setTimeout(function () {
          data.success.apply(data.context, [answer]);
        }, 300);
      }
    }, function (error) {
      context.commit('UPDATE_QUESTION_FAILURE', question_id);
      console.error(error);
    });
  },
  updateQuestionContent: function updateQuestionContent(context, question) {
    context.commit('UPDATE_QUESTION_REQUEST', question.id);
    LP.Request({
      type: 'update-question-content',
      question: JSON.stringify(question)
    }).then(function () {
      context.commit('UPDATE_QUESTION_SUCCESS', question.id);
    })["catch"](function () {
      context.commit('UPDATE_QUESTION_FAILURE', question.id);
    });
  },
  updateQuestionMeta: function updateQuestionMeta(context, payload) {
    context.commit('UPDATE_QUESTION_REQUEST', payload.question.id);
    LP.Request({
      type: 'update-question-meta',
      question: JSON.stringify(payload.question),
      meta_key: payload.meta_key
    }).then(function () {
      context.commit('UPDATE_QUESTION_SUCCESS', payload.question.id);
    })["catch"](function () {
      context.commit('UPDATE_QUESTION_FAILURE', payload.question.id);
    });
  }
};
/* harmony default export */ __webpack_exports__["default"] = (QuestionList);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/actions/quiz.js":
/*!**********************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/actions/quiz.js ***!
  \**********************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Quiz = {
  heartbeat: function heartbeat(context) {
    LP.Request({
      type: 'heartbeat'
    }).then(function (response) {
      var result = response.body;
      context.commit('UPDATE_HEART_BEAT', !!result.success);
    }, function (error) {
      context.commit('UPDATE_HEART_BEAT', false);
    });
  },
  newRequest: function newRequest(context) {
    context.commit('INCREASE_NUMBER_REQUEST');
    context.commit('UPDATE_STATUS', 'loading');

    window.onbeforeunload = function () {
      return '';
    };
  },
  requestCompleted: function requestCompleted(context, status) {
    context.commit('DECREASE_NUMBER_REQUEST');

    if (context.getters.currentRequest === 0) {
      context.commit('UPDATE_STATUS', status);
      window.onbeforeunload = null;
    }
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Quiz);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/getters/modal-quiz-items.js":
/*!**********************************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/getters/modal-quiz-items.js ***!
  \**********************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var ModalQuizItems = {
  status: function status(state) {
    return state.status;
  },
  pagination: function pagination(state) {
    return state.pagination;
  },
  items: function items(state, _getters) {
    return state.items.map(function (item) {
      var find = _getters.addedItems.find(function (_item) {
        return item.id === _item.id;
      });

      item.added = !!find;
      return item;
    });
  },
  code: function code(state) {
    return Date.now();
  },
  addedItems: function addedItems(state) {
    return state.addedItems;
  },
  isOpen: function isOpen(state) {
    return state.open;
  },
  quiz: function quiz(state) {
    return state.quizId;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (ModalQuizItems);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/getters/question-list.js":
/*!*******************************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/getters/question-list.js ***!
  \*******************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var QuestionList = {
  listQuestions: function listQuestions(state) {
    return state.questions || [];
  },
  questionsOrder: function questionsOrder(state) {
    return state.order || [];
  },
  externalComponent: function externalComponent(state) {
    return state.externalComponent || [];
  },
  hiddenQuestionsSettings: function hiddenQuestionsSettings(state) {
    return state.hidden_questions_settings || [];
  },
  hiddenQuestions: function hiddenQuestions(state) {
    return state.questions.filter(function (question) {
      return !question.open;
    }).map(function (question) {
      return parseInt(question.id);
    });
  },
  isHiddenListQuestions: function isHiddenListQuestions(state, getters) {
    var questions = getters['listQuestions'];
    var hiddenQuestions = getters['hiddenQuestions'];
    return questions.length === hiddenQuestions.length;
  },
  disableUpdateList: function disableUpdateList(state) {
    return state.disableUpdateList;
  },
  statusUpdateQuestions: function statusUpdateQuestions(state) {
    return state.statusUpdateQuestions;
  },
  statusUpdateQuestionItem: function statusUpdateQuestionItem(state) {
    return state.statusUpdateQuestionItem;
  },
  statusUpdateQuestionAnswer: function statusUpdateQuestionAnswer(state) {
    return state.statusUpdateQuestionAnswer;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (QuestionList);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/getters/quiz.js":
/*!**********************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/getters/quiz.js ***!
  \**********************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Quiz = {
  heartbeat: function heartbeat(state) {
    return state.heartbeat;
  },
  questionTypes: function questionTypes(state) {
    return state.types;
  },
  defaultNewQuestionType: function defaultNewQuestionType(state) {
    return state.default_new;
  },
  action: function action(state) {
    return state.action;
  },
  id: function id(state) {
    return state.quiz_id;
  },
  status: function status(state) {
    return state.status || 'error';
  },
  currentRequest: function currentRequest(state) {
    return state.countCurrentRequest || 0;
  },
  nonce: function nonce(state) {
    return state.nonce;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Quiz);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/http.js":
/*!**************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/http.js ***!
  \**************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return HTTP; });
function HTTP(options) {
  var $ = window.jQuery;
  var $VueHTTP = Vue.http;
  options = $.extend({
    ns: 'LPRequest',
    store: false
  }, options || {});
  var $publishingAction = null;

  LP.Request = function (payload) {
    $publishingAction = $('#publishing-action');
    payload['id'] = options.store.getters.id;
    payload['nonce'] = options.store.getters.nonce;
    payload['lp-ajax'] = options.store.getters.action;
    payload['code'] = options.store.getters.code;
    $publishingAction.find('#publish').addClass('disabled');
    $publishingAction.find('.spinner').addClass('is-active');
    $publishingAction.addClass('code-' + payload['code']);
    return $VueHTTP.post(options.store.getters.urlAjax, payload, {
      emulateJSON: true,
      params: {
        namespace: options.ns,
        code: payload['code']
      }
    });
  };

  $VueHTTP.interceptors.push(function (request, next) {
    if (request.params['namespace'] !== options.ns) {
      next();
      return;
    }

    options.store.dispatch('newRequest');
    next(function (response) {
      if (!jQuery.isPlainObject(response.body)) {
        response.body = LP.parseJSON(response.body);
      }

      var body = response.body;
      var result = body.success || false;

      if (result) {
        options.store.dispatch('requestCompleted', 'successful');
      } else {
        options.store.dispatch('requestCompleted', 'failed');
      }

      $publishingAction.removeClass('code-' + request.params.code);

      if (!$publishingAction.attr('class')) {
        $publishingAction.find('#publish').removeClass('disabled');
        $publishingAction.find('.spinner').removeClass('is-active');
      }
    });
  });
}

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/mutations/modal-quiz-items.js":
/*!************************************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/mutations/modal-quiz-items.js ***!
  \************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var ModalQuizItems = {
  'TOGGLE': function TOGGLE(state) {
    state.open = !state.open;
  },
  'SET_QUIZ': function SET_QUIZ(state, quizId) {
    state.quizId = quizId;
  },
  'SET_LIST_ITEMS': function SET_LIST_ITEMS(state, items) {
    state.items = items;
  },
  'ADD_ITEM': function ADD_ITEM(state, item) {
    state.addedItems.push(item);
  },
  'REMOVE_ADDED_ITEM': function REMOVE_ADDED_ITEM(state, item) {
    state.addedItems.forEach(function (_item, index) {
      if (_item.id === item.id) {
        state.addedItems.splice(index, 1);
      }
    });
  },
  'RESET': function RESET(state) {
    state.addedItems = [];
    state.items = [];
  },
  'UPDATE_PAGINATION': function UPDATE_PAGINATION(state, pagination) {
    state.pagination = pagination;
  },
  'SEARCH_ITEM_REQUEST': function SEARCH_ITEM_REQUEST(state) {
    state.status = 'loading';
  },
  'SEARCH_ITEM_SUCCESS': function SEARCH_ITEM_SUCCESS(state) {
    state.status = 'successful';
  },
  'SEARCH_ITEM_FAIL': function SEARCH_ITEM_FAIL(state) {
    state.status = 'fail';
  }
};
/* harmony default export */ __webpack_exports__["default"] = (ModalQuizItems);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/mutations/question-list.js":
/*!*********************************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/mutations/question-list.js ***!
  \*********************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var $ = window.jQuery;
var QuestionList = {
  'SORT_QUESTIONS': function SORT_QUESTIONS(state, orders) {
    state.questions = state.questions.map(function (question) {
      question.order = orders[question.id];
      return question;
    });
  },
  'SORT_QUESTION_ANSWERS': function SORT_QUESTION_ANSWERS(state, orders) {
    state.questions = state.questions.map(function (question) {
      question.answers.answer_order = orders[question.answers.question_answer_id];
      return question;
    });
  },
  'ADD_QUESTION_ANSWER': function ADD_QUESTION_ANSWER(state, payload) {
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
    });
  },
  'CHANGE_QUESTION_CORRECT_ANSWERS': function CHANGE_QUESTION_CORRECT_ANSWERS(state, data) {
    state.questions = state.questions.map(function (question) {
      if (parseInt(question.id) === data.id) {
        question.answers = data.answers;
      }

      return question;
    });
  },
  'SET_QUESTIONS': function SET_QUESTIONS(state, questions) {
    state.questions = questions;
  },
  'ADD_NEW_QUESTION': function ADD_NEW_QUESTION(state, question) {
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

        $('html,body').animate({
          scrollTop: _offset
        });
      }

      state.questions.push(question);
    }
  },
  'CHANGE_QUESTION_TYPE': function CHANGE_QUESTION_TYPE(state, data) {
    state.questions = state.questions.map(function (question) {
      if (parseInt(question.id) === data.id) {
        question.answers = data.answers;
        question.type = data.type;
        question.open = true;
      }

      return question;
    });
  },
  'REMOVE_QUESTION': function REMOVE_QUESTION(state, item) {
    var questions = state.questions,
        index = questions.indexOf(item);

    if (item.temp_id) {
      state.questions[index].id = item.temp_id;
    } else {
      state.questions.splice(index, 1);
    }
  },
  'DELETE_QUESTION_ANSWER': function DELETE_QUESTION_ANSWER(state, payload) {
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
        });
      }

      return question;
    });
  },
  'REMOVE_QUESTIONS': function REMOVE_QUESTIONS() {// code
  },
  'CLOSE_QUESTION': function CLOSE_QUESTION(state, question) {
    state.questions.forEach(function (_question, index) {
      if (question.id === _question.id) {
        state.questions[index].open = false;
      }
    });
  },
  'OPEN_QUESTION': function OPEN_QUESTION(state, question) {
    state.questions.forEach(function (_question, index) {
      if (question.id === _question.id) {
        state.questions[index].open = true;
      }
    });
  },
  'CLOSE_LIST_QUESTIONS': function CLOSE_LIST_QUESTIONS(state) {
    state.questions = state.questions.map(function (_question) {
      _question.open = false;
      return _question;
    });
  },
  'OPEN_LIST_QUESTIONS': function OPEN_LIST_QUESTIONS(state) {
    state.questions = state.questions.map(function (_question) {
      _question.open = true;
      return _question;
    });
  },
  'UPDATE_QUESTION_REQUEST': function UPDATE_QUESTION_REQUEST(state, questionId) {
    $Vue.set(state.statusUpdateQuestionItem, questionId, 'updating');
  },
  'UPDATE_QUESTION_SUCCESS': function UPDATE_QUESTION_SUCCESS(state, questionID) {
    $Vue.set(state.statusUpdateQuestionItem, questionID, 'successful');
  },
  'UPDATE_QUESTION_FAILURE': function UPDATE_QUESTION_FAILURE(state, questionID) {
    $Vue.set(state.statusUpdateQuestionItem, questionID, 'failed');
  },
  'UPDATE_QUESTION_ANSWER_REQUEST': function UPDATE_QUESTION_ANSWER_REQUEST(state, question_id) {
    $Vue.set(state.statusUpdateQuestionAnswer, question_id, 'updating');
  },
  'UPDATE_QUESTION_ANSWER_SUCCESS': function UPDATE_QUESTION_ANSWER_SUCCESS(state, question_id) {
    $Vue.set(state.statusUpdateQuestionAnswer, question_id, 'successful');
  },
  'UPDATE_QUESTION_ANSWER_FAIL': function UPDATE_QUESTION_ANSWER_FAIL(state, question_id) {
    $Vue.set(state.statusUpdateQuestionAnswer, question_id, 'failed');
  },
  'DELETE_ANSWER': function DELETE_ANSWER(state, data) {
    console.log('A');
    state.questions.map(function (question, index) {
      if (question.id == data.question_id) {
        for (var i = 0, n = question.answers.length; i < n; i++) {
          if (question.answers[i].question_answer_id == data.answer_id) {
            question.answers[i].question_answer_id = data.temp_id; //state.questions[index].answers.splice(i, 1);

            break;
          }
        }

        return false;
      }
    });
  }
};
/* harmony default export */ __webpack_exports__["default"] = (QuestionList);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/mutations/quiz.js":
/*!************************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/mutations/quiz.js ***!
  \************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Quiz = {
  'UPDATE_HEART_BEAT': function UPDATE_HEART_BEAT(state, status) {
    state.heartbeat = !!status;
  },
  'UPDATE_STATUS': function UPDATE_STATUS(state, status) {
    state.status = status;
  },
  'UPDATE_NEW_QUESTION_TYPE': function UPDATE_NEW_QUESTION_TYPE(state, type) {
    state.default_new = type;
  },
  'INCREASE_NUMBER_REQUEST': function INCREASE_NUMBER_REQUEST(state) {
    state.countCurrentRequest++;
  },
  'DECREASE_NUMBER_REQUEST': function DECREASE_NUMBER_REQUEST(state) {
    state.countCurrentRequest--;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Quiz);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/quiz.js":
/*!**************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/quiz.js ***!
  \**************************************************************************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _store_quiz__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./store/quiz */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/quiz.js");
/* harmony import */ var _http__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./http */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/http.js");


window.$Vue = window.$Vue || Vue;
window.$Vuex = window.$Vuex || Vuex;
/**
 * Init app.
 *
 * @since 3.0.0
 */

window.jQuery(document).ready(function () {
  window.LP_Quiz_Store = new $Vuex.Store(Object(_store_quiz__WEBPACK_IMPORTED_MODULE_0__["default"])(lp_quiz_editor));
  Object(_http__WEBPACK_IMPORTED_MODULE_1__["default"])({
    ns: 'LPListQuizQuestionsRequest',
    store: LP_Quiz_Store
  });
  setTimeout(function () {
    window.LP_Quiz_Editor = new $Vue({
      el: '#admin-editor-lp_quiz',
      template: '<lp-quiz-editor></lp-quiz-editor>'
    });
  }, 100);
});

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/i18n.js":
/*!********************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/i18n.js ***!
  \********************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var $ = window.jQuery;

var i18n = function i18n(i18n) {
  var state = $.extend({}, i18n);
  var getters = {
    all: function all(state) {
      return state;
    }
  };
  return {
    namespaced: true,
    state: state,
    getters: getters
  };
};

/* harmony default export */ __webpack_exports__["default"] = (i18n);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/modal-quiz-items.js":
/*!********************************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/modal-quiz-items.js ***!
  \********************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _getters_modal_quiz_items__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../getters/modal-quiz-items */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/getters/modal-quiz-items.js");
/* harmony import */ var _mutations_modal_quiz_items__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mutations/modal-quiz-items */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/mutations/modal-quiz-items.js");
/* harmony import */ var _actions_modal_quiz_items__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../actions/modal-quiz-items */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/actions/modal-quiz-items.js");



var $ = window.jQuery;

var Quiz = function Quiz(data) {
  var state = $.extend({
    quizId: false,
    pagination: '',
    status: ''
  }, data.chooseItems);
  return {
    namespaced: true,
    state: state,
    getters: _getters_modal_quiz_items__WEBPACK_IMPORTED_MODULE_0__["default"],
    mutations: _mutations_modal_quiz_items__WEBPACK_IMPORTED_MODULE_1__["default"],
    actions: _actions_modal_quiz_items__WEBPACK_IMPORTED_MODULE_2__["default"]
  };
};

/* harmony default export */ __webpack_exports__["default"] = (Quiz);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/question-list.js":
/*!*****************************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/question-list.js ***!
  \*****************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _getters_question_list__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../getters/question-list */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/getters/question-list.js");
/* harmony import */ var _mutations_question_list__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mutations/question-list */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/mutations/question-list.js");
/* harmony import */ var _actions_question_list__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../actions/question-list */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/actions/question-list.js");



var $ = window.jQuery;

var QuestionList = function QuestionList(data) {
  var listQuestions = data.listQuestions;
  var state = $.extend({
    statusUpdateQuestions: {},
    statusUpdateQuestionItem: {},
    statusUpdateQuestionAnswer: {},
    questions: listQuestions.questions.map(function (question) {
      var hiddenQuestions = listQuestions.hidden_questions;
      var find = hiddenQuestions.find(function (questionId) {
        return parseInt(question.id) === parseInt(questionId);
      });
      question.open = !find;
      return question;
    })
  }, listQuestions);
  return {
    namespaced: true,
    state: state,
    getters: _getters_question_list__WEBPACK_IMPORTED_MODULE_0__["default"],
    mutations: _mutations_question_list__WEBPACK_IMPORTED_MODULE_1__["default"],
    actions: _actions_question_list__WEBPACK_IMPORTED_MODULE_2__["default"]
  };
};

/* harmony default export */ __webpack_exports__["default"] = (QuestionList);

/***/ }),

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/quiz.js":
/*!********************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/quiz.js ***!
  \********************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _getters_quiz__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../getters/quiz */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/getters/quiz.js");
/* harmony import */ var _mutations_quiz__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mutations/quiz */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/mutations/quiz.js");
/* harmony import */ var _actions_quiz__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../actions/quiz */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/actions/quiz.js");
/* harmony import */ var _store_modal_quiz_items__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../store/modal-quiz-items */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/modal-quiz-items.js");
/* harmony import */ var _store_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../store/i18n */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/i18n.js");
/* harmony import */ var _store_question_list__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../store/question-list */ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/js/admin/editor/store/question-list.js");






var $ = window.jQuery;

var Quiz = function Quiz(data) {
  var state = $.extend({
    status: 'success',
    heartbeat: true,
    countCurrentRequest: 0
  }, data.root);
  return {
    state: state,
    getters: _getters_quiz__WEBPACK_IMPORTED_MODULE_0__["default"],
    mutations: _mutations_quiz__WEBPACK_IMPORTED_MODULE_1__["default"],
    actions: _actions_quiz__WEBPACK_IMPORTED_MODULE_2__["default"],
    modules: {
      cqi: Object(_store_modal_quiz_items__WEBPACK_IMPORTED_MODULE_3__["default"])(data),
      i18n: Object(_store_i18n__WEBPACK_IMPORTED_MODULE_4__["default"])(data.i18n),
      lqs: Object(_store_question_list__WEBPACK_IMPORTED_MODULE_5__["default"])(data)
    }
  };
};

/* harmony default export */ __webpack_exports__["default"] = (Quiz);

/***/ })

/******/ });
//# sourceMappingURL=quiz.js.map