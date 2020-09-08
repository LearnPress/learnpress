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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/admin/editor/question.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/admin/editor/actions/question.js":
/*!********************************************************!*\
  !*** ./assets/src/js/admin/editor/actions/question.js ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Question = {
  changeQuestionType: function changeQuestionType(context, payload) {
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
    });
  },
  updateAnswersOrder: function updateAnswersOrder(context, order) {
    LP.Request({
      type: 'sort-answer',
      order: order
    }).then(function (response) {
      var result = response.body;

      if (result.success) {// context.commit('SET_ANSWERS', result.data);
      }
    });
  },
  updateAnswerTitle: function updateAnswerTitle(context, answer) {
    if (typeof answer.question_answer_id == 'undefined') {
      return;
    }

    answer = JSON.stringify(answer);
    LP.Request({
      type: 'update-answer-title',
      answer: answer
    });
  },
  updateCorrectAnswer: function updateCorrectAnswer(context, correct) {
    LP.Request({
      type: 'change-correct',
      correct: JSON.stringify(correct)
    }).then(function (response) {
      var result = response.body;

      if (result.success) {
        context.commit('UPDATE_ANSWERS', result.data);
        context.commit('UPDATE_AUTO_DRAFT_STATUS', false);
      }
    });
  },
  deleteAnswer: function deleteAnswer(context, payload) {
    context.commit('DELETE_ANSWER', payload.id);
    LP.Request({
      type: 'delete-answer',
      answer_id: payload.id
    }).then(function (response) {
      var result = response.body;

      if (result.success) {
        context.commit('SET_ANSWERS', result.data);
      } else {// notice error
      }
    });
  },
  newAnswer: function newAnswer(context, data) {
    context.commit('ADD_NEW_ANSWER', data.answer);
    LP.Request({
      type: 'new-answer'
    }).then(function (response) {
      var result = response.body;

      if (result.success) {
        context.commit('UPDATE_ANSWERS', result.data);
      } else {// notice error
      }
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
/* harmony default export */ __webpack_exports__["default"] = (Question);

/***/ }),

/***/ "./assets/src/js/admin/editor/getters/question.js":
/*!********************************************************!*\
  !*** ./assets/src/js/admin/editor/getters/question.js ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Question = {
  id: function id(state) {
    return state.id;
  },
  type: function type(state) {
    return state.type;
  },
  code: function code(state) {
    return Date.now();
  },
  autoDraft: function autoDraft(state) {
    return state.auto_draft;
  },
  answers: function answers(state) {
    return Object.values(state.answers) || [];
  },
  settings: function settings(state) {
    return state.setting;
  },
  types: function types(state) {
    return state.questionTypes || [];
  },
  numberCorrect: function numberCorrect(state) {
    var correct = 0;
    Object.keys(state.answers).forEach(function (key) {
      if (state.answers[key].is_true === 'yes') {
        correct += 1;
      }
    });
    return correct;
  },
  status: function status(state) {
    return state.status;
  },
  currentRequest: function currentRequest(state) {
    return state.countCurrentRequest || 0;
  },
  action: function action(state) {
    return state.action;
  },
  nonce: function nonce(state) {
    return state.nonce;
  },
  externalComponent: function externalComponent(state) {
    return state.externalComponent || [];
  },
  state: function state(_state) {
    return _state;
  },
  i18n: function i18n(state) {
    return state.i18n;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Question);

/***/ }),

/***/ "./assets/src/js/admin/editor/http.js":
/*!********************************************!*\
  !*** ./assets/src/js/admin/editor/http.js ***!
  \********************************************/
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

/***/ "./assets/src/js/admin/editor/mutations/question.js":
/*!**********************************************************!*\
  !*** ./assets/src/js/admin/editor/mutations/question.js ***!
  \**********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Question = {
  'UPDATE_STATUS': function UPDATE_STATUS(state, status) {
    state.status = status;
  },
  'UPDATE_AUTO_DRAFT_STATUS': function UPDATE_AUTO_DRAFT_STATUS(state, status) {
    state.auto_draft = status;
  },
  'CHANGE_QUESTION_TYPE': function CHANGE_QUESTION_TYPE(state, question) {
    state.answers = question.answers;
    state.type = question.type;
  },
  'SET_ANSWERS': function SET_ANSWERS(state, answers) {
    state.answers = answers;
  },
  'DELETE_ANSWER': function DELETE_ANSWER(state, id) {
    for (var i = 0, n = state.answers.length; i < n; i++) {
      if (state.answers[i].question_answer_id == id) {
        state.answers[i].question_answer_id = LP.uniqueId();
        break;
      }
    }
  },
  'ADD_NEW_ANSWER': function ADD_NEW_ANSWER(state, answer) {
    state.answers.push(answer);
  },
  'UPDATE_ANSWERS': function UPDATE_ANSWERS(state, answers) {
    state.answers = answers;
  },
  'INCREASE_NUMBER_REQUEST': function INCREASE_NUMBER_REQUEST(state) {
    state.countCurrentRequest++;
  },
  'DECREASE_NUMBER_REQUEST': function DECREASE_NUMBER_REQUEST(state) {
    state.countCurrentRequest--;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Question);

/***/ }),

/***/ "./assets/src/js/admin/editor/question.js":
/*!************************************************!*\
  !*** ./assets/src/js/admin/editor/question.js ***!
  \************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _http__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./http */ "./assets/src/js/admin/editor/http.js");
/* harmony import */ var _store_question__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./store/question */ "./assets/src/js/admin/editor/store/question.js");


window.$Vue = window.$Vue || Vue;
window.$Vuex = window.$Vuex || Vuex;
var $ = window.jQuery;
/**
 * Init app.
 *
 * @since 3.0.0
 */

$(document).ready(function () {
  window.LP_Question_Store = new $Vuex.Store(Object(_store_question__WEBPACK_IMPORTED_MODULE_1__["default"])(lp_question_editor));
  Object(_http__WEBPACK_IMPORTED_MODULE_0__["default"])({
    ns: 'LPQuestionEditorRequest',
    store: LP_Question_Store
  });
  setTimeout(function () {
    window.LP_Question_Editor = new $Vue({
      el: '#admin-editor-lp_question',
      template: '<lp-question-editor></lp-question-editor>'
    });
  }, 100);
});

/***/ }),

/***/ "./assets/src/js/admin/editor/store/question.js":
/*!******************************************************!*\
  !*** ./assets/src/js/admin/editor/store/question.js ***!
  \******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _getters_question__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../getters/question */ "./assets/src/js/admin/editor/getters/question.js");
/* harmony import */ var _mutations_question__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mutations/question */ "./assets/src/js/admin/editor/mutations/question.js");
/* harmony import */ var _actions_question__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../actions/question */ "./assets/src/js/admin/editor/actions/question.js");



var $ = window.jQuery;

var Question = function Question(data) {
  var state = $.extend({
    status: 'successful',
    countCurrentRequest: 0,
    i18n: $.extend({}, data.i18n)
  }, data.root);
  return {
    state: state,
    getters: _getters_question__WEBPACK_IMPORTED_MODULE_0__["default"],
    mutations: _mutations_question__WEBPACK_IMPORTED_MODULE_1__["default"],
    actions: _actions_question__WEBPACK_IMPORTED_MODULE_2__["default"]
  };
};

/* harmony default export */ __webpack_exports__["default"] = (Question);

/***/ })

/******/ });