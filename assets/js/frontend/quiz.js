this["LP"] = this["LP"] || {}; this["LP"]["quiz"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/quiz.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/quiz.js":
/*!****************************************!*\
  !*** ./assets/src/js/frontend/quiz.js ***!
  \****************************************/
/*! exports provided: default, init, MyContext */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "init", function() { return init; });
/* harmony import */ var _quiz_index__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./quiz/index */ "./assets/src/js/frontend/quiz/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "MyContext", function() { return _quiz_index__WEBPACK_IMPORTED_MODULE_0__["MyContext"]; });


var _LP = LP,
    Modal = _LP.modal["default"];
/* harmony default export */ __webpack_exports__["default"] = (_quiz_index__WEBPACK_IMPORTED_MODULE_0__["default"]);
var init = function init(elem, settings) {
  wp.element.render(React.createElement(Modal, null, React.createElement(_quiz_index__WEBPACK_IMPORTED_MODULE_0__["default"], {
    settings: settings
  })), jQuery(elem)[0]);
};


/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/attempts/index.js":
/*!******************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/attempts/index.js ***!
  \******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }





var _lodash = lodash,
    uniqueId = _lodash.uniqueId;
/**
 * Displays list of all attempt from a quiz.
 */

var Attempts =
/*#__PURE__*/
function (_Component) {
  _inherits(Attempts, _Component);

  function Attempts() {
    _classCallCheck(this, Attempts);

    return _possibleConstructorReturn(this, _getPrototypeOf(Attempts).apply(this, arguments));
  }

  _createClass(Attempts, [{
    key: "getDurationLabel",
    value: function getDurationLabel(attempt) {
      if (!attempt.expirationTime) {
        return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Unlimited', 'learnpress');
      }

      var formatDuration = LP.singleCourse.formatDuration;
      var milliseconds = new Date(attempt.expirationTime).getTime() - new Date(attempt.startTime).getTime();
      return milliseconds ? formatDuration(milliseconds / 1000) : '';
    }
  }, {
    key: "getTimeSpendLabel",
    value: function getTimeSpendLabel(attempt) {
      var formatDuration = LP.singleCourse.formatDuration;
      var milliseconds = new Date(attempt.endTime).getTime() - new Date(attempt.startTime).getTime();
      return milliseconds ? formatDuration(milliseconds / 1000) : '';
    }
  }, {
    key: "render",
    value: function render() {
      var _this = this;

      var attempts = this.props.attempts;
      var hasAttempts = attempts && !!attempts.length;
      return !hasAttempts ? false : React.createElement(React.Fragment, null, React.createElement("div", {
        className: "quiz-attempts"
      }, React.createElement("h4", {
        className: "attempts-heading"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Last Attempted', 'learnpress')), hasAttempts && React.createElement("table", null, React.createElement("thead", null, React.createElement("tr", null, React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Date', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Questions', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Spend', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Marks', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Passing Grade', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Result', 'learnpress')))), React.createElement("tbody", null, attempts.map(function (row) {
        return React.createElement("tr", {
          key: "attempt-".concat(row.id)
        }, React.createElement("td", null, row.startTime), React.createElement("td", null, row.questionCorrect, " / ", row.questionCount), React.createElement("td", null, _this.getTimeSpendLabel(row), " / ", _this.getDurationLabel(row)), React.createElement("td", null, row.userMark, " / ", row.mark), React.createElement("td", null, row.passingGrade || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["_x"])('-', 'unknown passing grade value', 'learnpress')), React.createElement("td", null, parseFloat(row.result).toFixed(2), "% ", React.createElement("label", null, row.graduationText)));
      })))));
    }
  }]);

  return Attempts;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  var attempts = getData('attempts') || [];
  return {
    id: getData('id'),
    attempts: attempts,
    attemptsCount: getData('attemptsCount'),
    status: getData('status'),
    questionIds: getData('questionIds'),
    questionNav: getData('questionNav'),
    currentQuestion: getData('currentQuestion')
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch, _ref) {
  var id = _ref.id;

  var _dispatch = dispatch('learnpress/quiz'),
      startQuiz = _dispatch.startQuiz,
      setCurrentQuestion = _dispatch.setCurrentQuestion,
      _submitQuiz = _dispatch.submitQuiz;

  return {
    startQuiz: startQuiz,
    setCurrentQuestion: setCurrentQuestion,
    submitQuiz: function submitQuiz() {
      _submitQuiz(id);
    }
  };
})])(Attempts));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/buttons/button-check.js":
/*!************************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/buttons/button-check.js ***!
  \************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }






var ButtonCheck =
/*#__PURE__*/
function (_Component) {
  _inherits(ButtonCheck, _Component);

  function ButtonCheck() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, ButtonCheck);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(ButtonCheck)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_this), "checkAnswer", function () {
      var _this$props = _this.props,
          checkAnswer = _this$props.checkAnswer,
          question = _this$props.question;
      checkAnswer(question.id);
    });

    return _this;
  }

  _createClass(ButtonCheck, [{
    key: "render",
    value: function render() {
      var answered = this.props.answered;
      return React.createElement("button", {
        className: "lp-button instant-check",
        onClick: this.checkAnswer,
        disabled: !answered
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["_x"])('Check answer', 'label of button check answer', 'learnpress'));
    }
  }]);

  return ButtonCheck;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select, _ref) {
  var id = _ref.question.id;

  var _select = select('learnpress/quiz'),
      getQuestionAnswered = _select.getQuestionAnswered;

  return {
    answered: getQuestionAnswered(id)
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch, _ref2) {
  var id = _ref2.id;

  var _dispatch = dispatch('learnpress/quiz'),
      _checkAnswer = _dispatch.checkAnswer;

  return {
    checkAnswer: function checkAnswer(id) {
      _checkAnswer(id);
    }
  };
}))(ButtonCheck));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/buttons/button-hint.js":
/*!***********************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/buttons/button-hint.js ***!
  \***********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }






var ButtonHint =
/*#__PURE__*/
function (_Component) {
  _inherits(ButtonHint, _Component);

  function ButtonHint() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, ButtonHint);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(ButtonHint)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_this), "showHint", function () {
      var _this$props = _this.props,
          showHint = _this$props.showHint,
          question = _this$props.question;
      showHint(question.id, !question.showHint);
    });

    return _this;
  }

  _createClass(ButtonHint, [{
    key: "render",
    value: function render() {
      var question = this.props.question;
      return question.hint ? React.createElement("button", {
        className: "btn-show-hint",
        onClick: this.showHint
      }) : '';
    }
  }]);

  return ButtonHint;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch, _ref) {
  var id = _ref.id;

  var _dispatch = dispatch('learnpress/quiz'),
      _showHint = _dispatch.showHint;

  return {
    showHint: function showHint(id, show) {
      _showHint(id, show);
    }
  };
}))(ButtonHint));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/buttons/index.js":
/*!*****************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/buttons/index.js ***!
  \*****************************************************************/
/*! exports provided: MaybeShowButton, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "MaybeShowButton", function() { return MaybeShowButton; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _buttons_button_check__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../buttons/button-check */ "./assets/src/js/frontend/quiz/components/buttons/button-check.js");
/* harmony import */ var _buttons_button_hint__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../buttons/button-hint */ "./assets/src/js/frontend/quiz/components/buttons/button-hint.js");
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }








var Buttons =
/*#__PURE__*/
function (_Component) {
  _inherits(Buttons, _Component);

  function Buttons() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, Buttons);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(Buttons)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_this), "startQuiz", function (event) {
      event && event.preventDefault();
      var _this$props = _this.props,
          startQuiz = _this$props.startQuiz,
          status = _this$props.status;

      if (status === 'completed') {
        var _select = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/modal'),
            confirm = _select.confirm;

        if ('no' === confirm('Are you sure you want to retry quiz?', _this.startQuiz)) {
          return;
        }
      }

      startQuiz();
    });

    _defineProperty(_assertThisInitialized(_this), "nav", function (to) {
      return function (event) {
        var _this$props2 = _this.props,
            questionNav = _this$props2.questionNav,
            currentPage = _this$props2.currentPage,
            numPages = _this$props2.numPages,
            setCurrentPage = _this$props2.setCurrentPage;

        switch (to) {
          case 'prev':
            currentPage = currentPage > 1 ? currentPage - 1 : questionNav === 'infinity' ? numPages : 1;
            break;

          default:
            currentPage = currentPage < numPages ? currentPage + 1 : questionNav === 'infinity' ? 1 : numPages;
        }

        setCurrentPage(currentPage);
      };
    });

    _defineProperty(_assertThisInitialized(_this), "moveTo", function (pageNum) {
      return function (event) {
        event.preventDefault();
        var _this$props3 = _this.props,
            numPages = _this$props3.numPages,
            setCurrentPage = _this$props3.setCurrentPage;

        if (pageNum < 1 || pageNum > numPages) {
          return;
        }

        setCurrentPage(pageNum);
      };
    });

    _defineProperty(_assertThisInitialized(_this), "isLast", function () {
      var _this$props4 = _this.props,
          currentPage = _this$props4.currentPage,
          numPages = _this$props4.numPages;
      return currentPage === numPages;
    });

    _defineProperty(_assertThisInitialized(_this), "isFirst", function () {
      var currentPage = _this.props.currentPage;
      return currentPage === 1;
    });

    _defineProperty(_assertThisInitialized(_this), "submit", function () {
      var submitQuiz = _this.props.submitQuiz;
      submitQuiz();
    });

    _defineProperty(_assertThisInitialized(_this), "setQuizMode", function (mode) {
      return function () {
        var setQuizMode = _this.props.setQuizMode;
        setQuizMode(mode);
      };
    });

    _defineProperty(_assertThisInitialized(_this), "isReviewing", function () {
      var isReviewing = _this.props.isReviewing;
      return isReviewing;
    });

    return _this;
  }

  _createClass(Buttons, [{
    key: "pageNumbers",
    // componentWillReceiveProps(nextProps) {
    //     if (nextProps.keyPressed === this.props.keyPressed) {
    //         return;
    //     }
    //     switch (nextProps.keyPressed) {
    //         case 'left':
    //             return this.nav('prev')();
    //         case 'right':
    //             return this.nav('next')()
    //     }
    // }

    /**
     * Displays pagination with numbers from min to max.
     *
     * @return {string}
     */
    value: function pageNumbers(args) {
      var _this2 = this;

      var _this$props5 = this.props,
          numPages = _this$props5.numPages,
          currentPage = _this$props5.currentPage;

      if (numPages < 2) {
        return '';
      }

      args = _objectSpread({
        numPages: numPages,
        currentPage: currentPage,
        midSize: 1,
        endSize: 1,
        prevNext: true
      }, args || {});

      if (args.endSize < 1) {
        args.endSize = 1;
      }

      if (args.midSize < 0) {
        args.midSize = 1;
      }

      var numbers = _toConsumableArray(Array(numPages).keys()),
          dots = false;

      return React.createElement("div", {
        className: "nav-links"
      }, args.prevNext && !this.isFirst() && React.createElement("a", {
        className: "page-numbers prev",
        "data-type": "question-navx",
        onClick: this.nav('prev')
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('', 'learnpress')), numbers.map(function (number) {
        number = number + 1;

        if (number === args.currentPage) {
          dots = true;
          return React.createElement("span", {
            key: "page-number-".concat(number),
            className: "page-numbers current"
          }, number);
        } else {
          if (number <= args.endSize || args.currentPage && number >= args.currentPage - args.midSize && number <= args.currentPage + args.midSize || number > args.numPages - args.endSize) {
            dots = true;
            return React.createElement("a", {
              key: "page-number-".concat(number),
              className: "page-numbers",
              onClick: _this2.moveTo(number)
            }, number);
          } else if (dots) {
            dots = false;
            return React.createElement("span", {
              key: "page-number-".concat(number),
              className: "page-numbers dots"
            }, "\u2026");
          }
        }
      }), args.prevNext && !this.isLast() && React.createElement("a", {
        className: "page-numbers next",
        "data-type": "question-navx",
        onClick: this.nav('next')
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('', 'learnpress')));
    }
    /**
     * Render buttons
     *
     * @return {XML}
     */

  }, {
    key: "render",
    value: function render() {
      var _this$props6 = this.props,
          status = _this$props6.status,
          questionNav = _this$props6.questionNav,
          isReviewing = _this$props6.isReviewing,
          showReview = _this$props6.showReview,
          numPages = _this$props6.numPages,
          question = _this$props6.question,
          questionsPerPage = _this$props6.questionsPerPage,
          canRetry = _this$props6.canRetry;
      var classNames = ['quiz-buttons align-center'];

      if (questionNav === 'questionNav') {
        classNames.push('infinity');
      }

      if (this.isFirst()) {
        classNames.push('is-first');
      }

      if (this.isLast()) {
        classNames.push('is-last');
      }

      return React.createElement("div", {
        className: classNames.join(' ')
      }, React.createElement("div", {
        className: "button-left" + (status === 'started' ? ' fixed' : '')
      }, -1 !== ['', 'completed', 'viewed'].indexOf(status) && !isReviewing && canRetry && React.createElement("button", {
        className: "lp-button start",
        onClick: this.startQuiz
      }, status === 'completed' ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["_x"])('Retry', 'label button retry quiz', 'learnpress') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["_x"])('Start', 'label button start quiz', 'learnpress')), ('started' === status || isReviewing) && numPages > 1 && React.createElement(React.Fragment, null, React.createElement("div", {
        className: "questions-pagination"
      }, this.pageNumbers()))), React.createElement("div", {
        className: "button-right"
      }, 'started' === status
      /*|| isReviewing*/
      && React.createElement(React.Fragment, null, questionsPerPage === 1 && [React.createElement(MaybeShowButton, {
        key: "button-hint",
        type: "hint",
        Button: _buttons_button_hint__WEBPACK_IMPORTED_MODULE_5__["default"],
        question: question
      }), React.createElement(MaybeShowButton, {
        key: "button-check",
        type: "check",
        Button: _buttons_button_check__WEBPACK_IMPORTED_MODULE_4__["default"],
        question: question
      })], ('infinity' === questionNav || this.isLast()) && !isReviewing && React.createElement("button", {
        className: "lp-button submit-quiz",
        onClick: this.submit
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Submit', 'learnpress'))), isReviewing && showReview && React.createElement("button", {
        className: "lp-button back-quiz",
        onClick: this.setQuizMode('')
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Result', 'learnpress')), 'completed' === status && showReview && !isReviewing && React.createElement("button", {
        className: "lp-button review-quiz",
        onClick: this.setQuizMode('reviewing')
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Review', 'learnpress'))));
    }
  }]);

  return Buttons;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);
/**
 * Helper function to check a button should be show or not.
 *
 * Buttons [hint, check]
 */


var MaybeShowButton = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select2 = select('learnpress/quiz'),
      getData = _select2.getData;

  return {
    status: getData('status'),
    showCheck: getData('instantCheck'),
    checkedQuestions: getData('checkedQuestions'),
    hintedQuestions: getData('hintedQuestions'),
    questionsPerPage: getData('questionsPerPage')
  };
}))(function (props) {
  var showCheck = props.showCheck,
      checkedQuestions = props.checkedQuestions,
      hintedQuestions = props.hintedQuestions,
      question = props.question,
      status = props.status,
      type = props.type,
      Button = props.Button;

  if (status !== 'started') {
    return false;
  }

  var theButton = React.createElement(Button, {
    question: question
  });

  switch (type) {
    case 'hint':
      if (!hintedQuestions) {
        return theButton;
      }

      if (!question.hasHint) {
        return false;
      }

      return hintedQuestions.indexOf(question.id) === -1 && theButton;

    case 'check':
      if (!showCheck) {
        return false;
      }

      if (!checkedQuestions) {
        return theButton;
      }

      return checkedQuestions.indexOf(question.id) === -1 && theButton;
  }
});
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select3 = select('learnpress/quiz'),
      getData = _select3.getData,
      getCurrentQuestion = _select3.getCurrentQuestion;

  var data = {
    id: getData('id'),
    status: getData('status'),
    questionIds: getData('questionIds'),
    questionNav: getData('questionNav'),
    isReviewing: getData('reviewQuestions') && getData('mode') === 'reviewing',
    showReview: getData('reviewQuestions'),
    showCheck: getData('instantCheck'),
    checkedQuestions: getData('checkedQuestions'),
    hintedQuestions: getData('hintedQuestions'),
    numPages: getData('numPages'),
    pages: getData('pages'),
    currentPage: getData('currentPage'),
    questionsPerPage: getData('questionsPerPage'),
    pageNumbers: getData('pageNumbers'),
    keyPressed: getData('keyPressed'),
    canRetry: (getData('attempts') || []).length < getData('attemptsCount')
  };

  if (data.questionsPerPage === 1) {
    data.question = getCurrentQuestion('object');
  }

  return data;
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch, _ref) {
  var id = _ref.id;

  var _dispatch = dispatch('learnpress/quiz'),
      startQuiz = _dispatch.startQuiz,
      setCurrentQuestion = _dispatch.setCurrentQuestion,
      _submitQuiz = _dispatch.submitQuiz,
      setQuizMode = _dispatch.setQuizMode,
      _showHint = _dispatch.showHint,
      _checkAnswer = _dispatch.checkAnswer,
      setCurrentPage = _dispatch.setCurrentPage;

  return {
    startQuiz: startQuiz,
    setCurrentQuestion: setCurrentQuestion,
    setQuizMode: setQuizMode,
    setCurrentPage: setCurrentPage,
    submitQuiz: function submitQuiz(id) {
      _submitQuiz(id);
    },
    showHint: function showHint(id) {
      _showHint(id);
    },
    checkAnswer: function checkAnswer(id) {
      _checkAnswer(id);
    }
  };
})])(Buttons));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/content/index.js":
/*!*****************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/content/index.js ***!
  \*****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }





var Content =
/*#__PURE__*/
function (_Component) {
  _inherits(Content, _Component);

  function Content() {
    _classCallCheck(this, Content);

    return _possibleConstructorReturn(this, _getPrototypeOf(Content).apply(this, arguments));
  }

  _createClass(Content, [{
    key: "render",
    value: function render() {
      var content = this.props.content;
      return React.createElement("div", {
        className: "quiz-content",
        dangerouslySetInnerHTML: {
          __html: content
        }
      });
    }
  }]);

  return Content;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  return {
    content: getData('content')
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/quiz'),
      setQuizData = _dispatch.setQuizData,
      startQuiz = _dispatch.startQuiz;

  return {
    setQuizData: setQuizData,
    startQuiz: startQuiz
  };
})])(Content));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/index.js":
/*!*********************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/index.js ***!
  \*********************************************************/
/*! exports provided: Title, Content, Meta, Buttons, Questions, Attempts, Timer, Result, Status */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _title__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./title */ "./assets/src/js/frontend/quiz/components/title/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Title", function() { return _title__WEBPACK_IMPORTED_MODULE_0__["default"]; });

/* harmony import */ var _content__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./content */ "./assets/src/js/frontend/quiz/components/content/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Content", function() { return _content__WEBPACK_IMPORTED_MODULE_1__["default"]; });

/* harmony import */ var _meta__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./meta */ "./assets/src/js/frontend/quiz/components/meta/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Meta", function() { return _meta__WEBPACK_IMPORTED_MODULE_2__["default"]; });

/* harmony import */ var _buttons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./buttons */ "./assets/src/js/frontend/quiz/components/buttons/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Buttons", function() { return _buttons__WEBPACK_IMPORTED_MODULE_3__["default"]; });

/* harmony import */ var _questions__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./questions */ "./assets/src/js/frontend/quiz/components/questions/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Questions", function() { return _questions__WEBPACK_IMPORTED_MODULE_4__["default"]; });

/* harmony import */ var _attempts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./attempts */ "./assets/src/js/frontend/quiz/components/attempts/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Attempts", function() { return _attempts__WEBPACK_IMPORTED_MODULE_5__["default"]; });

/* harmony import */ var _timer__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./timer */ "./assets/src/js/frontend/quiz/components/timer/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Timer", function() { return _timer__WEBPACK_IMPORTED_MODULE_6__["default"]; });

/* harmony import */ var _result__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./result */ "./assets/src/js/frontend/quiz/components/result/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Result", function() { return _result__WEBPACK_IMPORTED_MODULE_7__["default"]; });

/* harmony import */ var _status__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./status */ "./assets/src/js/frontend/quiz/components/status/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Status", function() { return _status__WEBPACK_IMPORTED_MODULE_8__["default"]; });











/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/meta/index.js":
/*!**************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/meta/index.js ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }





var _LP = LP,
    Hook = _LP.Hook;

var Meta =
/*#__PURE__*/
function (_Component) {
  _inherits(Meta, _Component);

  function Meta() {
    _classCallCheck(this, Meta);

    return _possibleConstructorReturn(this, _getPrototypeOf(Meta).apply(this, arguments));
  }

  _createClass(Meta, [{
    key: "render",
    value: function render() {
      var metaFields = this.props.metaFields;
      return metaFields && React.createElement(React.Fragment, null, React.createElement("ul", {
        className: "quiz-intro"
      }, Object.values(metaFields).map(function (field, i) {
        var id = field.name || i;
        return React.createElement("li", {
          key: "quiz-intro-field-".concat(i),
          className: "quiz-intro-item quiz-intro-item__".concat(id)
        }, React.createElement("label", {
          dangerouslySetInnerHTML: {
            __html: field.title
          }
        }), React.createElement("span", {
          dangerouslySetInnerHTML: {
            __html: field.content
          }
        }));
      })));
    }
  }]);

  return Meta;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  var _LP2 = LP,
      singleCourse = _LP2.singleCourse;
  return {
    metaFields: Hook.applyFilters('quiz-meta-fields', {
      // attemptsCount: {
      //     label: __('Attempts allowed', 'learnpress'),
      //     content: getData('attemptsCount')
      // },
      duration: {
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Duration', 'learnpress'),
        name: 'duration',
        content: singleCourse.formatDuration(getData('duration'))
      },
      passingGrade: {
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Passing grade', 'learnpress'),
        name: 'passing-grade',
        content: getData('passingGrade')
      },
      questionsCount: {
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Questions', 'learnpress'),
        name: 'questions-count',
        content: function () {
          var ids = getData('questionIds');
          return ids ? ids.length : 0;
        }()
      }
    })
  };
}))(Meta));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/questions/buttons.js":
/*!*********************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/questions/buttons.js ***!
  \*********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _buttons_button_hint__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../buttons/button-hint */ "./assets/src/js/frontend/quiz/components/buttons/button-hint.js");
/* harmony import */ var _buttons_button_check__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../buttons/button-check */ "./assets/src/js/frontend/quiz/components/buttons/button-check.js");
/* harmony import */ var _buttons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../buttons */ "./assets/src/js/frontend/quiz/components/buttons/index.js");





var Buttons = function Buttons(props) {
  var question = props.question;
  var buttons = {
    'instant-check': function instantCheck() {
      return React.createElement(_buttons__WEBPACK_IMPORTED_MODULE_3__["MaybeShowButton"], {
        type: "check",
        Button: _buttons_button_check__WEBPACK_IMPORTED_MODULE_2__["default"],
        question: question
      });
    },
    'hint': function hint() {
      return React.createElement(_buttons__WEBPACK_IMPORTED_MODULE_3__["MaybeShowButton"], {
        type: "hint",
        Button: _buttons_button_hint__WEBPACK_IMPORTED_MODULE_1__["default"],
        question: question
      });
    }
  };
  return React.createElement(React.Fragment, null, LP.config.questionFooterButtons().map(function (name) {
    return React.createElement(React.Fragment, {
      key: "button-".concat(name)
    }, buttons[name] && buttons[name]());
  }));
};

/* harmony default export */ __webpack_exports__["default"] = (Buttons);

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/questions/index.js":
/*!*******************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/questions/index.js ***!
  \*******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _question__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./question */ "./assets/src/js/frontend/quiz/components/questions/question.js");
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }






var _lodash = lodash,
    isNumber = _lodash.isNumber,
    chunk = _lodash.chunk;

var Questions =
/*#__PURE__*/
function (_Component) {
  _inherits(Questions, _Component);

  function Questions(props) {
    var _this;

    _classCallCheck(this, Questions);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Questions).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "startQuiz", function (event) {
      event.preventDefault();
      var startQuiz = _this.props.startQuiz;
      startQuiz();
    });

    _defineProperty(_assertThisInitialized(_this), "isInVisibleRange", function (id, index) {
      var _this$props = _this.props,
          currentPage = _this$props.currentPage,
          questionsPerPage = _this$props.questionsPerPage;
      return currentPage === Math.ceil(index / questionsPerPage);
    });

    _defineProperty(_assertThisInitialized(_this), "nav", function (event) {
      var sendKey = _this.props.sendKey;
      console.log(event.keyCode);

      switch (event.keyCode) {
        case 37:
          // left
          return sendKey('left');

        case 38:
          // up
          return;

        case 39:
          // right
          return sendKey('right');

        case 40:
          // down
          return;

        default:
          // 1 ... 9
          if (event.keyCode >= 49 && event.keyCode <= 57) {
            sendKey(event.keyCode - 48);
          }

      }
    });

    _this.needToTop = false;
    _this.state = {
      isReviewing: null,
      currentPage: 0,
      self: _assertThisInitialized(_this)
    };
    return _this;
  }

  _createClass(Questions, [{
    key: "componentDidUpdate",
    // componentWillReceiveProps(nextProps){
    //     const checkProps = ['isReviewing', 'currentPage'];
    //
    //     for(let i = 0; i < checkProps.length; i++){
    //         if(this.props[checkProps[i]] !== nextProps[checkProps[i]]){
    //             this.needToTop = true;
    //             return;
    //         }
    //     }
    //
    // }
    // componentWillUpdate() {
    //     this.needToTop = this.state.needToTop;
    //     this.setState({needToTop: false});
    // }
    value: function componentDidUpdate() {
      if (this.needToTop) {
        jQuery('#popup-content').animate({
          scrollTop: 0
        }).find('.content-item-scrollable:last').animate({
          scrollTop: 0
        });
        this.needToTop = false;
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props2 = this.props,
          status = _this$props2.status,
          currentQuestion = _this$props2.currentQuestion,
          questions = _this$props2.questions,
          questionsRendered = _this$props2.questionsRendered,
          isReviewing = _this$props2.isReviewing,
          questionsPerPage = _this$props2.questionsPerPage;
      var viewMode = false,
          isShow = true; //if (!showAllQuestions) {

      if (status === 'completed' && !isReviewing) {
        isShow = false;
      } //}


      return React.createElement(React.Fragment, null, React.createElement("div", {
        tabIndex: 100,
        onKeyUp: this.nav
      }, React.createElement("div", {
        className: "quiz-questions",
        style: {
          display: isShow ? '' : 'none'
        }
      }, questions.map(function (question, index) {
        var isCurrent = questionsPerPage ? false : currentQuestion === question.id;
        var isRendered = questionsRendered && questionsRendered.indexOf(question.id) !== -1;

        var isVisible = _this2.isInVisibleRange(question.id, index + 1);

        return isRendered || !isRendered
        /*&& isCurrent*/
        || isVisible ? React.createElement(_question__WEBPACK_IMPORTED_MODULE_4__["default"], {
          isCurrent: isCurrent,
          key: "loop-question-".concat(question.id),
          isShow: isVisible,
          isShowIndex: questionsPerPage ? index + 1 : false,
          questionsPerPage: questionsPerPage,
          question: question
        }) : '';
      }))));
    }
  }], [{
    key: "getDerivedStateFromProps",
    value: function getDerivedStateFromProps(props, state) {
      var checkProps = ['isReviewing', 'currentPage'];
      var changedProps = {};

      for (var i = 0; i < checkProps.length; i++) {
        if (props[checkProps[i]] !== state[checkProps[i]]) {
          changedProps[checkProps[i]] = props[checkProps[i]];
        }
      } // If has prop changed then update state and re-render UI


      if (Object.values(changedProps).length) {
        state.self.needToTop = true;
        return changedProps;
      } // No state update necessary


      return null;
    }
  }]);

  return Questions;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select, a, b) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData,
      getQuestions = _select.getQuestions;

  return {
    status: getData('status'),
    currentQuestion: getData('currentQuestion'),
    questions: getQuestions(),
    questionsRendered: getData('questionsRendered'),
    isReviewing: getData('mode') === 'reviewing',
    numPages: getData('numPages'),
    currentPage: getData('currentPage'),
    questionsPerPage: getData('questionsPerPage') || 1
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/quiz'),
      startQuiz = _dispatch.startQuiz,
      sendKey = _dispatch.sendKey;

  return {
    startQuiz: startQuiz,
    sendKey: sendKey
  };
}))(Questions));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/questions/question.js":
/*!**********************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/questions/question.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _buttons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./buttons */ "./assets/src/js/frontend/quiz/components/questions/buttons.js");
/* harmony import */ var _buttons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../buttons */ "./assets/src/js/frontend/quiz/components/buttons/index.js");
/* harmony import */ var _buttons_button_hint__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../buttons/button-hint */ "./assets/src/js/frontend/quiz/components/buttons/button-hint.js");
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }








var $ = window.jQuery;
var _lodash = lodash,
    uniqueId = _lodash.uniqueId,
    isArray = _lodash.isArray,
    isNumber = _lodash.isNumber,
    bind = _lodash.bind;

var Question =
/*#__PURE__*/
function (_Component) {
  _inherits(Question, _Component);

  function Question() {
    var _this;

    _classCallCheck(this, Question);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Question).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "setRef", function (el) {
      _this.$wrap = $(el);
    });

    _defineProperty(_assertThisInitialized(_this), "parseOptions", function (options) {
      if (options) {
        options = !isArray(options) ? JSON.parse(CryptoJS.AES.decrypt(options.data, options.key, {
          format: CryptoJSAesJson
        }).toString(CryptoJS.enc.Utf8)) : options;
        options = !isArray(options) ? JSON.parse(options) : options;
      }

      return options || [];
    });

    _defineProperty(_assertThisInitialized(_this), "getWrapperClass", function () {
      var _this$props = _this.props,
          question = _this$props.question,
          answered = _this$props.answered;
      var classes = ['question', 'question-' + question.type];

      var options = _this.parseOptions(question.options);

      if (options.length && options[0].isTrue !== undefined) {
        classes.push('question-answered');
      }

      return classes;
    });

    _defineProperty(_assertThisInitialized(_this), "getEditLink", function () {
      var _this$props2 = _this.props,
          question = _this$props2.question,
          editPermalink = _this$props2.editPermalink;
      return editPermalink ? editPermalink.replace(/[0-9]+/, question.id) : '';
    });

    _defineProperty(_assertThisInitialized(_this), "editPermalink", function (editPermalink) {
      return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["sprintf"])('<a href="%s">%s</a>', editPermalink, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Edit', 'learnpress'));
    });

    _this.state = {
      time: null,
      showHint: false
    };
    _this.$wrap = null;
    return _this;
  }

  _createClass(Question, [{
    key: "componentDidMount",
    value: function componentDidMount(a) {
      var _this$props3 = this.props,
          question = _this$props3.question,
          isCurrent = _this$props3.isCurrent,
          markQuestionRendered = _this$props3.markQuestionRendered;

      if (isCurrent) {
        markQuestionRendered(question.id);
      } // Refresh render function to pass $wrap to child


      if (!this.state.time) {
        this.setState({
          time: new Date()
        });
      }

      return a;
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props4 = this.props,
          question = _this$props4.question,
          isShow = _this$props4.isShow,
          isShowIndex = _this$props4.isShowIndex,
          isShowHint = _this$props4.isShowHint,
          status = _this$props4.status;
      var QuestionTypes = LP.questionTypes["default"];

      var _editPermalink = this.getEditLink();

      if (_editPermalink) {
        jQuery('#wp-admin-bar-edit-lp_question').find('.ab-item').attr('href', _editPermalink);
      }

      var titleParts = {
        'index': function index() {
          return isShowIndex ? React.createElement("span", {
            className: "question-index"
          }, isShowIndex, ".") : '';
        },
        'title': function title() {
          return question.title;
        },
        'hint': function hint() {
          return React.createElement(_buttons_button_hint__WEBPACK_IMPORTED_MODULE_6__["default"], {
            question: question
          });
        },
        'edit-permalink': function editPermalink() {
          return _editPermalink && React.createElement("span", {
            dangerouslySetInnerHTML: {
              __html: _this2.editPermalink(_editPermalink)
            },
            className: "edit-link"
          });
        }
      };
      var blocks = {
        title: function title() {
          return React.createElement("h4", {
            className: "question-title"
          }, LP.config.questionTitleParts().map(function (name) {
            return React.createElement(React.Fragment, {
              key: "title-part-".concat(name)
            }, titleParts[name] && titleParts[name]());
          }));
        },
        content: function content() {
          return React.createElement("div", {
            className: "question-content",
            dangerouslySetInnerHTML: {
              __html: question.content
            }
          });
        },
        'answer-options': function answerOptions() {
          return _this2.$wrap && React.createElement(QuestionTypes, _objectSpread({}, _this2.props, {
            $wrap: _this2.$wrap
          }));
        },
        explanation: function explanation() {
          return question.explanation && React.createElement(React.Fragment, null, React.createElement("div", {
            className: "question-explanation-content"
          }, React.createElement("strong", {
            className: "explanation-title"
          }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Explanation:', 'learnpress')), React.createElement("div", {
            dangerouslySetInnerHTML: {
              __html: question.explanation
            }
          })));
        },
        hint: function hint() {
          return question.hint && !question.explanation && question.showHint && React.createElement(React.Fragment, null, React.createElement("div", {
            className: "question-hint-content"
          }, React.createElement("strong", {
            className: "hint-title"
          }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Hint:', 'learnpress')), React.createElement("div", {
            dangerouslySetInnerHTML: {
              __html: question.hint
            }
          })));
        },
        buttons: function buttons() {
          return 'started' === status &&
          /*&& (questionsPerPage > 1)*/
          React.createElement(_buttons__WEBPACK_IMPORTED_MODULE_4__["default"], {
            question: question
          });
        }
      };
      var configBlocks = LP.config.questionBlocks();
      return React.createElement(React.Fragment, null, React.createElement("div", {
        className: this.getWrapperClass().join(' '),
        style: {
          display: isShow ? '' : 'none'
        },
        "data-id": question.id,
        ref: this.setRef
      }, configBlocks.map(function (name) {
        return React.createElement(React.Fragment, {
          key: "block-".concat(name)
        }, blocks[name] ? blocks[name]() : '');
      })));
    }
  }]);

  return Question;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select, _ref) {
  var id = _ref.question.id;

  var _select = select('learnpress/quiz'),
      getData = _select.getData,
      getQuestionAnswered = _select.getQuestionAnswered;

  return {
    status: getData('status'),
    questions: getData('question'),
    answered: getQuestionAnswered(id),
    questionsRendered: getData('questionsRendered'),
    editPermalink: getData('editPermalink'),
    //isCorrect: isCorrect(id),
    numPages: getData('numPages')
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/quiz'),
      updateUserQuestionAnswers = _dispatch.updateUserQuestionAnswers,
      markQuestionRendered = _dispatch.markQuestionRendered;

  return {
    markQuestionRendered: markQuestionRendered,
    updateUserQuestionAnswers: updateUserQuestionAnswers
  };
})])(Question));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/result/index.js":
/*!****************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/result/index.js ***!
  \****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }





var _lodash = lodash,
    get = _lodash.get,
    debounce = _lodash.debounce;

var Result =
/*#__PURE__*/
function (_Component) {
  _inherits(Result, _Component);

  function Result() {
    var _this;

    _classCallCheck(this, Result);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Result).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "getResultMessage", function (results) {
      return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Your grade is <strong>%s</strong>', 'learnpress'), results.graduationText);
    });

    _defineProperty(_assertThisInitialized(_this), "getResultPercentage", function (results) {
      // const {
      //     percent
      // } = this.state;
      //
      // const maxPercent = results.result;
      return results.result === 100 ? results.result : parseFloat(results.result).toFixed(2);
    });

    _this.state = {
      percentage: 0,
      done: false
    };
    return _this;
  }
  /**
   * Get result message.
   *
   * @param results
   * @return {*|string}
   */


  _createClass(Result, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.animate();
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var results = this.props.results;

      if (prevProps.results.result === results.result) {
        return;
      }

      this.animate();
    }
  }, {
    key: "animate",
    value: function animate() {
      var _this2 = this;

      var results = this.props.results;
      this.setState({
        percentage: 0,
        done: false
      });

      jQuery.easing['_customEasing'] = function (e, f, a, h, g) {
        return h * Math.sqrt(1 - (f = f / g - 1) * f) + a;
      };
      /*function(e, f, a, h, g) {
       return (f == g) ? a + h : h * (-Math.pow(2, -10 * f / g) + 1) + a
       }*/


      debounce(function () {
        var $el = jQuery('<span />').css({
          width: 1,
          height: 1
        }).appendTo(document.body);
        $el.css('left', 0).animate({
          left: results.result
        }, {
          duration: 1500,
          step: function step(now, fx) {
            _this2.setState({
              percentage: now
            });
          },
          done: function done() {
            _this2.setState({
              done: true
            });

            $el.remove();
            jQuery('#quizResultGrade').css({
              transform: 'scale(1.3)',
              transition: 'all 0.25s'
            });
            debounce(function () {
              jQuery('#quizResultGrade').css({
                transform: 'scale(1)'
              });
            }, 500)();
          },
          easing: '_customEasing'
        });
      }, results.result > 0 ? 1000 : 10)();
    }
    /**
     * Render HTML elements.
     *
     * @return {XML}
     */

  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          results = _this$props.results,
          passingGrade = _this$props.passingGrade;
      var _this$state = this.state,
          percentage = _this$state.percentage,
          done = _this$state.done;

      if (percentage < 100) {
        percentage = parseFloat(percentage).toFixed(2);
      }

      var classNames = ['quiz-result', results.graduation];
      var border = 10;
      var width = 200;
      var percent = this.getResultPercentage(results);
      var radius = width / 2;
      var r = (width - border) / 2;
      var circumference = r * 2 * Math.PI;
      var offset = circumference - percentage / 100 * circumference;
      var styles = {
        strokeDasharray: "".concat(circumference, " ").concat(circumference),
        strokeDashoffset: offset
      };
      var passingGradeValue = results.passingGrade || passingGrade;
      return React.createElement("div", {
        className: classNames.join(' ')
      }, React.createElement("h3", {
        className: "result-heading"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Your Result', 'learnpress')), React.createElement("div", {
        id: "quizResultGrade",
        className: "result-grade"
      }, React.createElement("svg", {
        className: "circle-progress-bar",
        width: width,
        height: width
      }, React.createElement("circle", {
        className: "circle-progress-bar__circle",
        stroke: "",
        strokeWidth: border,
        style: styles,
        fill: "transparent",
        r: r,
        cx: radius,
        cy: radius
      })), React.createElement("span", {
        className: "result-achieved"
      }, percentage, "%"), React.createElement("span", {
        className: "result-require"
      }, passingGradeValue ? passingGradeValue : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["_x"])('-', 'unknown passing grade value', 'learnpress'))), done && React.createElement("p", {
        className: "result-message"
      }, results.graduationText), React.createElement("ul", {
        className: "result-statistic"
      }, React.createElement("li", {
        className: "result-statistic-field result-time-spend"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Time spend', 'learnpress')), React.createElement("p", null, results.timeSpend)), React.createElement("li", {
        className: "result-statistic-field result-point"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Point', 'learnpress')), React.createElement("p", null, results.userMark, " / ", results.mark)), React.createElement("li", {
        className: "result-statistic-field result-questions"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Questions', 'learnpress')), React.createElement("p", null, results.questionCount)), React.createElement("li", {
        className: "result-statistic-field result-questions-correct"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Correct', 'learnpress')), React.createElement("p", null, results.questionCorrect)), React.createElement("li", {
        className: "result-statistic-field result-questions-wrong"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Wrong', 'learnpress')), React.createElement("p", null, results.questionWrong)), React.createElement("li", {
        className: "result-statistic-field result-questions-skipped"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Skipped', 'learnpress')), React.createElement("p", null, results.questionEmpty))));
    }
  }]);

  return Result;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  return {
    results: getData('results'),
    passingGrade: getData('passingGrade')
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/quiz'),
      setQuizData = _dispatch.setQuizData,
      startQuiz = _dispatch.startQuiz;

  return {
    setQuizData: setQuizData,
    startQuiz: startQuiz
  };
})])(Result));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/status/index.js":
/*!****************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/status/index.js ***!
  \****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _timer__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../timer */ "./assets/src/js/frontend/quiz/components/timer/index.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }






var $ = jQuery;
var _lodash = lodash,
    debounce = _lodash.debounce;

var Status =
/*#__PURE__*/
function (_Component) {
  _inherits(Status, _Component);

  function Status() {
    var _this;

    _classCallCheck(this, Status);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Status).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "submit", function () {
      var _select = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/modal'),
          confirm = _select.confirm;

      var title = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/quiz').getData('title');

      if ('no' === confirm(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Are you sure to submit quiz?', 'learnpress'), _this.submit)) {
        return;
      }

      var submitQuiz = _this.props.submitQuiz;
      submitQuiz();
    });

    _defineProperty(_assertThisInitialized(_this), "getMark", function () {
      var answered = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/quiz').getData('answered');
      return Object.values(answered).reduce(function (m, r) {
        return m + r.mark;
      }, 0);
    });

    _this.state = {
      submitting: false
    };
    return _this;
  }

  _createClass(Status, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var $pc = $('#popup-content');
      var $sc = $pc.find('.content-item-scrollable:eq(1)');
      var $ciw = $pc.find('.content-item-wrap');
      var $qs = $pc.find('.quiz-status');
      var pcTop = $qs.offset().top - 92;
      var isFixed = false;
      var marginLeft = '-' + $ciw.css('margin-left');
      $(window).on('resize.refresh-quiz-stauts-bar', debounce(function () {
        marginLeft = '-' + $ciw.css('margin-left');
        $qs.css({
          'margin-left': marginLeft,
          'margin-right': marginLeft
        });
      }, 100)).trigger('resize.refresh-quiz-stauts-bar');
      /**
       * Check when status bar is stopped in the top
       * to add new class into html
       */

      $sc.scroll(function () {
        if ($sc.scrollTop() >= pcTop) {
          if (isFixed) {
            return;
          }

          isFixed = true;
        } else {
          if (!isFixed) {
            return;
          }

          isFixed = false;
        }

        if (isFixed) {
          $pc.addClass('fixed-quiz-status');
        } else {
          $pc.removeClass('fixed-quiz-status');
        }
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          currentPage = _this$props.currentPage,
          questionsPerPage = _this$props.questionsPerPage,
          questionsCount = _this$props.questionsCount,
          submitting = _this$props.submitting,
          totalTime = _this$props.totalTime,
          duration = _this$props.duration,
          userMark = _this$props.userMark; // const {
      //     submitting
      // } = this.state;

      var classNames = ['quiz-status'];
      var start = (currentPage - 1) * questionsPerPage + 1;
      var end = start + questionsPerPage - 1;
      var indexHtml = '';
      end = Math.min(end, questionsCount);

      if (submitting) {
        classNames.push('submitting');
      }

      indexHtml = end < questionsCount ? questionsPerPage > 1 ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Question <span>%d to %d of %d</span>', 'learnpress'), start, end, questionsCount) : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Question <span>%d of %d</span>', 'learnpress'), start, questionsCount) : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Question <span>%d to %d</span>', 'learnpress'), start, end);
      return React.createElement("div", {
        className: classNames.join(' ')
      }, React.createElement("div", null, React.createElement("div", {
        className: "questions-index",
        dangerouslySetInnerHTML: {
          __html: indexHtml
        }
      }), React.createElement("div", {
        className: "current-point"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Earned Point: %s', 'learnpress'), userMark)), React.createElement("div", null, React.createElement("div", {
        className: "submit-quiz"
      }, React.createElement("button", {
        className: "lp-button",
        id: "button-submit-quiz",
        onClick: this.submit
      }, !submitting ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Submit', 'learnpress') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Submitting...', 'learnpress'))), totalTime && duration && React.createElement(_timer__WEBPACK_IMPORTED_MODULE_3__["default"], null))));
    }
  }]);

  return Status;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select2 = select('learnpress/quiz'),
      getData = _select2.getData,
      getUserMark = _select2.getUserMark;

  return {
    currentPage: getData('currentPage'),
    numPages: getData('numPages'),
    questionsPerPage: getData('questionsPerPage'),
    questionsCount: getData('questionIds').length,
    submitting: getData('submitting'),
    totalTime: getData('totalTime'),
    duration: getData('duration'),
    userMark: getUserMark()
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/quiz'),
      submitQuiz = _dispatch.submitQuiz;

  return {
    //setQuizData,
    submitQuiz: submitQuiz //startQuiz

  };
})])(Status));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/timer/index.js":
/*!***************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/timer/index.js ***!
  \***************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }




var _React = React,
    useState = _React.useState;

var Timer =
/*#__PURE__*/
function (_Component) {
  _inherits(Timer, _Component);

  function Timer(_props) {
    var _this;

    _classCallCheck(this, Timer);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Timer).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "init", function (props) {
      var endTime = props.endTime,
          totalTime = props.totalTime;
      var d1 = new Date(endTime);
      var d2 = new Date();
      var tz = new Date().getTimezoneOffset();
      var t = parseInt(d1.getTime() / 1000 - (d2.getTime() / 1000 + tz * 60));
      _this.state = {
        seconds: t,
        totalTime: totalTime,
        remainingSeconds: t > 0 ? t : 0,
        currentTime: parseInt(new Date().getTime() / 1000),
        percent: 100
      };
    });

    _defineProperty(_assertThisInitialized(_this), "submit", function () {
      var submitQuiz = _this.props.submitQuiz;
      submitQuiz();
    });

    _defineProperty(_assertThisInitialized(_this), "formatTime", function () {
      var separator = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : ':';
      var _this$state = _this.state,
          seconds = _this$state.remainingSeconds,
          totalTime = _this$state.totalTime;
      var t = [];
      var m;

      if (totalTime < 3600) {
        t.push((seconds - seconds % 60) / 60);
        t.push(seconds % 60);
      } else if (totalTime) {
        t.push((seconds - seconds % 3600) / 3600);
        m = seconds % 3600;
        t.push((m - m % 60) / 60);
        t.push(m % 60);
      }

      return t.map(function (a) {
        return a < 10 ? "0".concat(a) : a;
      }).join(separator);
    });

    _defineProperty(_assertThisInitialized(_this), "getCircle", function () {
      var percent = _this.state.percent;
      var width = 40;
      var border = 4;
      var radius = width / 2;
      var r = (width - border) / 2;
      var circumference = r * 2 * Math.PI;
      var offset = circumference - percent / 100 * circumference;
      var styles = {
        strokeDasharray: "".concat(circumference, " ").concat(circumference),
        strokeDashoffset: offset
      };
      var className = ['clock'];

      if (percent <= 5) {
        className.push('x');
      }

      return React.createElement("div", {
        className: className.join(' ')
      }, React.createElement("svg", {
        className: "circle-progress-bar",
        width: width,
        height: width
      }, React.createElement("circle", {
        className: "circle-progress-bar__circle",
        strokeWidth: border,
        style: styles,
        fill: "transparent",
        r: r,
        cx: radius,
        cy: radius
      })));
    });

    _this.init(_props);

    return _this;
  }

  _createClass(Timer, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      this.myInterval = setInterval(function () {
        var _this2$state = _this2.state,
            seconds = _this2$state.seconds,
            currentTime = _this2$state.currentTime,
            totalTime = _this2$state.totalTime; //const offset = parseInt(new Date().getTime() / 1000) - currentTime;
        //let remainingSeconds = seconds - offset;

        var remainingSeconds = _this2.state.remainingSeconds;
        remainingSeconds -= 1;

        if (remainingSeconds > 0) {
          _this2.setState(function (_ref) {
            var seconds = _ref.seconds;
            return {
              remainingSeconds: remainingSeconds,
              percent: remainingSeconds / totalTime * 100
            };
          });
        }

        if (remainingSeconds <= 0) {
          clearInterval(_this2.myInterval);

          _this2.submit();
        }
      }, 1000);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      clearInterval(this.myInterval);
    }
    /**
     * Submit question to record results.
     */

  }, {
    key: "render",
    value: function render() {
      var content = this.props.content;
      return React.createElement("div", {
        className: "countdown"
      }, React.createElement("i", {
        className: "fas fa-stopwatch"
      }), React.createElement("span", null, this.formatTime()));
    }
  }]);

  return Timer;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  return {
    submitting: getData('submitting'),
    totalTime: getData('totalTime') ? getData('totalTime') : getData('duration'),
    endTime: getData('endTime')
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/quiz'),
      setQuizData = _dispatch.setQuizData,
      submitQuiz = _dispatch.submitQuiz;

  return {
    setQuizData: setQuizData,
    submitQuiz: submitQuiz
  };
})])(Timer));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/components/title/index.js":
/*!***************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/title/index.js ***!
  \***************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }



var Title =
/*#__PURE__*/
function (_Component) {
  _inherits(Title, _Component);

  function Title() {
    _classCallCheck(this, Title);

    return _possibleConstructorReturn(this, _getPrototypeOf(Title).apply(this, arguments));
  }

  _createClass(Title, [{
    key: "render",
    value: function render() {
      return React.createElement("h3", null, "The title");
    }
  }]);

  return Title;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Title);

/***/ }),

/***/ "./assets/src/js/frontend/quiz/index.js":
/*!**********************************************!*\
  !*** ./assets/src/js/frontend/quiz/index.js ***!
  \**********************************************/
/*! exports provided: MyContext, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "MyContext", function() { return MyContext; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components */ "./assets/src/js/frontend/quiz/components/index.js");
/* harmony import */ var _store__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./store */ "./assets/src/js/frontend/quiz/store/index.js");
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }






var _lodash = lodash,
    chunk = _lodash.chunk,
    isNumber = _lodash.isNumber;
var $ = jQuery;
var MyContext = React.createContext({
  status: -1
});


var Quiz =
/*#__PURE__*/
function (_Component) {
  _inherits(Quiz, _Component);

  function Quiz(props) {
    var _this;

    _classCallCheck(this, Quiz);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Quiz).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "startQuiz", function (event) {
      _this.props.startQuiz();
    });

    _this.state = {
      currentPage: 1,
      numPages: 0,
      pages: []
    };
    return _this;
  }

  _createClass(Quiz, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          settings = _this$props.settings,
          setQuizData = _this$props.setQuizData;
      var question_ids = settings.question_ids,
          questions_per_page = settings.questions_per_page;
      var chunks = chunk(question_ids, questions_per_page);
      settings.currentPage = 1;
      settings.numPages = chunks.length;
      settings.pages = chunks;
      setQuizData(settings);
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate() {}
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          status = _this$props2.status,
          isReviewing = _this$props2.isReviewing;
      var isA = -1 !== ['', 'completed', 'viewed'].indexOf(status) || !status;
      var notStarted = -1 !== ['', 'viewed', undefined].indexOf(status) || !status; // Just render content if status !== undefined (meant all data loaded)

      return undefined !== status && React.createElement(React.Fragment, null, React.createElement(MyContext.Provider, {
        value: this.props
      }),  true && React.createElement("div", null, !isReviewing && 'completed' === status && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Result"], null), !isReviewing && notStarted && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Meta"], null), !isReviewing && notStarted && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Content"], null), 'started' === status && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Status"], null), (-1 !== ['completed', 'started'].indexOf(status) || isReviewing) && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Questions"], null), React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Buttons"], null), isA && !isReviewing && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Attempts"], null)));
    }
  }]);

  return Quiz;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getQuestions = _select.getQuestions,
      getData = _select.getData;

  return {
    questions: getQuestions(),
    status: getData('status'),
    store: getData(),
    answered: getData('answered'),
    isReviewing: getData('mode') === 'reviewing',
    //hintCount: getData('showHint'),
    questionIds: getData('questionIds'),
    checkCount: getData('instantCheck'),
    questionsPerPage: getData('questionsPerPage') || 1
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/quiz'),
      setQuizData = _dispatch.setQuizData,
      startQuiz = _dispatch.startQuiz;

  return {
    setQuizData: setQuizData,
    startQuiz: startQuiz
  };
})])(Quiz));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/store/actions.js":
/*!******************************************************!*\
  !*** ./assets/src/js/frontend/quiz/store/actions.js ***!
  \******************************************************/
/*! exports provided: setQuizData, setCurrentQuestion, setCurrentPage, __requestBeforeStartQuiz, __requestStartQuizSuccess, startQuiz, __requestSubmitQuiz, __requestSubmitQuizSuccess, submitQuiz, updateUserQuestionAnswers, __requestShowHintSuccess, showHint, __requestCheckAnswerSuccess, checkAnswer, markQuestionRendered, setQuizMode, sendKey */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setQuizData", function() { return setQuizData; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setCurrentQuestion", function() { return setCurrentQuestion; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setCurrentPage", function() { return setCurrentPage; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestBeforeStartQuiz", function() { return __requestBeforeStartQuiz; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestStartQuizSuccess", function() { return __requestStartQuizSuccess; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "startQuiz", function() { return startQuiz; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestSubmitQuiz", function() { return __requestSubmitQuiz; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestSubmitQuizSuccess", function() { return __requestSubmitQuizSuccess; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "submitQuiz", function() { return submitQuiz; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateUserQuestionAnswers", function() { return updateUserQuestionAnswers; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestShowHintSuccess", function() { return __requestShowHintSuccess; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "showHint", function() { return showHint; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestCheckAnswerSuccess", function() { return __requestCheckAnswerSuccess; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "checkAnswer", function() { return checkAnswer; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "markQuestionRendered", function() { return markQuestionRendered; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setQuizMode", function() { return setQuizMode; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "sendKey", function() { return sendKey; });
/* harmony import */ var _learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @learnpress/data-controls */ "@learnpress/data-controls");
/* harmony import */ var _learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var _marked =
/*#__PURE__*/
regeneratorRuntime.mark(submitQuiz),
    _marked2 =
/*#__PURE__*/
regeneratorRuntime.mark(showHint),
    _marked3 =
/*#__PURE__*/
regeneratorRuntime.mark(checkAnswer);

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }




function _dispatch() {
  var args = [].slice.call(arguments, 2);
  var d = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["dispatch"])(arguments[0]);
  var f = arguments[1];
  d[f].apply(d, _toConsumableArray(args));
}

var _LP = LP,
    camelCaseDashObjectKeys = _LP.camelCaseDashObjectKeys,
    Hook = _LP.Hook;
/**
 * Set user data for app.
 * @param key
 * @param data
 * @return {{type: string, data: *}}
 */

function setQuizData(key, data) {
  if (typeof key === 'string') {
    data = _defineProperty({}, key, data);
  } else {
    data = key;
  }

  return {
    type: 'SET_QUIZ_DATA',
    data: camelCaseDashObjectKeys(data)
  };
}
/**
 * Set question will display.
 *
 * @param questionId
 * @return {{type: string, data: *}}
 */

function setCurrentQuestion(questionId) {
  return {
    type: 'SET_CURRENT_QUESTION',
    questionId: questionId
  };
}
function setCurrentPage(currentPage) {
  return {
    type: 'SET_CURRENT_PAGE',
    currentPage: currentPage
  };
}
function __requestBeforeStartQuiz(quizId, courseId, userId) {
  return {
    type: 'BEFORE_START_QUIZ'
  };
}
function __requestStartQuizSuccess(results, quizId, courseId, userId) {
  Hook.doAction('quiz-started', results, quizId, courseId, userId);
  return {
    type: 'START_QUIZ_SUCCESS',
    quizId: quizId,
    courseId: courseId,
    userId: userId,
    results: results
  };
}
/**
 * Request to api for starting a quiz.
 */

var startQuiz =
/*#__PURE__*/
regeneratorRuntime.mark(function startQuiz() {
  var _wpSelect$getDefaultR, itemId, courseId, doStart, response;

  return regeneratorRuntime.wrap(function startQuiz$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _wpSelect$getDefaultR = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/quiz').getDefaultRestArgs(), itemId = _wpSelect$getDefaultR.itemId, courseId = _wpSelect$getDefaultR.courseId;
          doStart = Hook.applyFilters('before-start-quiz', true, itemId, courseId); // Allow third-party can ignore core action

          if (!(true !== doStart)) {
            _context.next = 4;
            break;
          }

          return _context.abrupt("return");

        case 4:
          _context.next = 6;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: 'lp/v1/users/start-quiz',
            method: 'POST',
            data: {
              item_id: itemId,
              course_id: courseId
            }
          });

        case 6:
          response = _context.sent;
          response = Hook.applyFilters('request-start-quiz-response', response, itemId, courseId);
          _context.next = 10;
          return _dispatch('learnpress/quiz', '__requestStartQuizSuccess', camelCaseDashObjectKeys(response['results']), itemId, courseId);

        case 10:
        case "end":
          return _context.stop();
      }
    }
  }, startQuiz);
});

function __requestSubmitQuiz() {
  return {
    type: 'SUBMIT_QUIZ'
  };
}
function __requestSubmitQuizSuccess(results, quizId, courseId) {
  Hook.doAction('quiz-submitted', results, quizId, courseId);
  return {
    type: 'SUBMIT_QUIZ_SUCCESS',
    results: results
  };
}
function submitQuiz() {
  var _wpSelect, getDefaultRestArgs, getQuestionsSelectedAnswers, _getDefaultRestArgs, itemId, courseId, doSubmit, answered, response;

  return regeneratorRuntime.wrap(function submitQuiz$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _wpSelect = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/quiz'), getDefaultRestArgs = _wpSelect.getDefaultRestArgs, getQuestionsSelectedAnswers = _wpSelect.getQuestionsSelectedAnswers;
          _getDefaultRestArgs = getDefaultRestArgs(), itemId = _getDefaultRestArgs.itemId, courseId = _getDefaultRestArgs.courseId;
          doSubmit = Hook.applyFilters('before-submit-quiz', true);

          if (!(true !== doSubmit)) {
            _context2.next = 5;
            break;
          }

          return _context2.abrupt("return");

        case 5:
          answered = getQuestionsSelectedAnswers();
          _context2.next = 8;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: 'lp/v1/users/submit-quiz',
            method: 'POST',
            data: {
              item_id: itemId,
              course_id: courseId,
              answered: answered
            }
          });

        case 8:
          response = _context2.sent;
          response = Hook.applyFilters('request-submit-quiz-response', response, itemId, courseId);

          if (!response.success) {
            _context2.next = 13;
            break;
          }

          _context2.next = 13;
          return _dispatch('learnpress/quiz', '__requestSubmitQuizSuccess', camelCaseDashObjectKeys(response.results), itemId, courseId);

        case 13:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked);
}
function updateUserQuestionAnswers(questionId, answers, quizId) {
  var courseId = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 0;
  var userId = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : 0;
  return {
    type: 'UPDATE_USER_QUESTION_ANSWERS',
    questionId: questionId,
    answers: answers
  };
}
function __requestShowHintSuccess(id, showHint) {
  return {
    type: 'SET_QUESTION_HINT',
    questionId: id,
    showHint: showHint
  };
}
function showHint(id, showHint) {
  return regeneratorRuntime.wrap(function showHint$(_context3) {
    while (1) {
      switch (_context3.prev = _context3.next) {
        case 0:
          _context3.next = 2;
          return _dispatch('learnpress/quiz', '__requestShowHintSuccess', id, showHint);

        case 2:
        case "end":
          return _context3.stop();
      }
    }
  }, _marked2);
}
function __requestCheckAnswerSuccess(id, result) {
  return _objectSpread({
    type: 'CHECK_ANSWER',
    questionId: id
  }, result);
}
function checkAnswer(id) {
  var _wpSelect2, getDefaultRestArgs, getQuestionAnswered, _getDefaultRestArgs2, itemId, courseId, result;

  return regeneratorRuntime.wrap(function checkAnswer$(_context4) {
    while (1) {
      switch (_context4.prev = _context4.next) {
        case 0:
          _wpSelect2 = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/quiz'), getDefaultRestArgs = _wpSelect2.getDefaultRestArgs, getQuestionAnswered = _wpSelect2.getQuestionAnswered;
          _getDefaultRestArgs2 = getDefaultRestArgs(), itemId = _getDefaultRestArgs2.itemId, courseId = _getDefaultRestArgs2.courseId;
          _context4.next = 4;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: 'lp/v1/users/check-answer',
            method: 'POST',
            data: {
              item_id: itemId,
              course_id: courseId,
              question_id: id,
              answered: getQuestionAnswered(id) || ''
            }
          });

        case 4:
          result = _context4.sent;
          _context4.next = 7;
          return _dispatch('learnpress/quiz', '__requestCheckAnswerSuccess', id, camelCaseDashObjectKeys(result));

        case 7:
        case "end":
          return _context4.stop();
      }
    }
  }, _marked3);
}
function markQuestionRendered(questionId) {
  return {
    type: 'MARK_QUESTION_RENDERED',
    questionId: questionId
  };
}
function setQuizMode(mode) {
  return {
    type: 'SET_QUIZ_MODE',
    mode: mode
  };
}
function sendKey(keyPressed) {
  setTimeout(function () {
    _dispatch('learnpress/quiz', 'sendKey', '');
  }, 300);
  return {
    type: 'SEND_KEY',
    keyPressed: keyPressed
  };
}

/***/ }),

/***/ "./assets/src/js/frontend/quiz/store/index.js":
/*!****************************************************!*\
  !*** ./assets/src/js/frontend/quiz/store/index.js ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _reducer__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./reducer */ "./assets/src/js/frontend/quiz/store/reducer.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./actions */ "./assets/src/js/frontend/quiz/store/actions.js");
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./selectors */ "./assets/src/js/frontend/quiz/store/selectors.js");
/* harmony import */ var _middlewares__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./middlewares */ "./assets/src/js/frontend/quiz/store/middlewares.js");
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }






var dataControls = LP.dataControls.controls;
var store = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["registerStore"])('learnpress/quiz', {
  reducer: _reducer__WEBPACK_IMPORTED_MODULE_1__["default"],
  selectors: _selectors__WEBPACK_IMPORTED_MODULE_3__,
  actions: _actions__WEBPACK_IMPORTED_MODULE_2__,
  controls: _objectSpread({}, dataControls)
});
Object(_middlewares__WEBPACK_IMPORTED_MODULE_4__["default"])(store);
/* harmony default export */ __webpack_exports__["default"] = (store);

/***/ }),

/***/ "./assets/src/js/frontend/quiz/store/middlewares.js":
/*!**********************************************************!*\
  !*** ./assets/src/js/frontend/quiz/store/middlewares.js ***!
  \**********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var refx__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! refx */ "./node_modules/refx/refx.js");
/* harmony import */ var refx__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(refx__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
//import effects from './effects';

var effects = {
  ENROLL_COURSE_X: function ENROLL_COURSE_X(action, store) {
    enrollCourse: (function (action, store) {
      var dispatch = store.dispatch; //dispatch()
    });
  }
};
/**
 * Applies the custom middlewares used specifically in the editor module.
 *
 * @param {Object} store Store Object.
 *
 * @return {Object} Update Store Object.
 */

function applyMiddlewares(store) {
  var enhancedDispatch = function enhancedDispatch() {
    throw new Error('Dispatching while constructing your middleware is not allowed. ' + 'Other middleware would not be applied to this dispatch.');
  };

  var middlewareAPI = {
    getState: store.getState,
    dispatch: function dispatch() {
      return enhancedDispatch.apply(void 0, arguments);
    }
  };
  enhancedDispatch = refx__WEBPACK_IMPORTED_MODULE_0___default()(effects)(middlewareAPI)(store.dispatch);
  store.dispatch = enhancedDispatch;
  return store;
}

/* harmony default export */ __webpack_exports__["default"] = (applyMiddlewares);

/***/ }),

/***/ "./assets/src/js/frontend/quiz/store/reducer.js":
/*!******************************************************!*\
  !*** ./assets/src/js/frontend/quiz/store/reducer.js ***!
  \******************************************************/
/*! exports provided: setItemStatus, userQuiz, blocks, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setItemStatus", function() { return setItemStatus; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "userQuiz", function() { return userQuiz; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "blocks", function() { return blocks; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }


var _lodash = lodash,
    omit = _lodash.omit,
    flow = _lodash.flow,
    isArray = _lodash.isArray,
    chunk = _lodash.chunk;
var _LP = LP,
    camelCaseDashObjectKeys = _LP.camelCaseDashObjectKeys;
var _LP$localStorage = LP.localStorage,
    storageGet = _LP$localStorage.get,
    storageSet = _LP$localStorage.set;
var STORE_DATA = {};
var setItemStatus = function setItemStatus(item, status) {
  var userSettings = _objectSpread({}, item.userSettings, {
    status: status
  });

  return _objectSpread({}, item, {
    userSettings: userSettings
  });
};

var updateUserQuestionAnswer = function updateUserQuestionAnswer(state, action) {
  var answered = state.answered;

  var newAnswer = _objectSpread({}, answered[action.questionId] || {}, {
    answered: action.answers,
    temp: true
  });

  return _objectSpread({}, state, {
    answered: _objectSpread({}, state.answered, _defineProperty({}, action.questionId, newAnswer))
  });
};

var markQuestionRendered = function markQuestionRendered(state, action) {
  var questionsRendered = state.questionsRendered;

  if (isArray(questionsRendered)) {
    questionsRendered.push(action.questionId);
    return _objectSpread({}, state, {
      questionsRendered: _toConsumableArray(questionsRendered)
    });
  } else {
    return _objectSpread({}, state, {
      questionsRendered: [action.questionId]
    });
  }
};

var resetCurrentPage = function resetCurrentPage(state, args) {
  if (args.currentPage) {
    storageSet("Q".concat(state.id, ".currentPage"), args.currentPage);
  }

  return _objectSpread({}, state, {}, args);
};

var updateAttempt = function updateAttempt(attempts, newAttempt) {
  var at = attempts.findIndex(function (attempt) {
    return attempt.id == newAttempt.id;
  });

  if (at !== -1) {
    attempts[at] = newAttempt;
  } else {
    attempts.unshift(newAttempt);
  }

  return attempts;
};

var setQuestionHint = function setQuestionHint(state, action) {
  var questions = state.questions.map(function (question) {
    return question.id == action.questionId ? _objectSpread({}, question, {
      showHint: action.showHint
    }) : question;
  });
  return _objectSpread({}, state, {
    questions: _toConsumableArray(questions) //hintedQuestions: [...state.hintedQuestions, action.questionId]

  });
};

var checkAnswer = function checkAnswer(state, action) {
  var questions = state.questions.map(function (question) {
    if (question.id !== action.questionId) {
      return question;
    }

    var newArgs = {
      explanation: action.explanation
    };

    if (action.options) {
      newArgs.options = action.options;
    }

    return _objectSpread({}, question, {}, newArgs);
  });
  return _objectSpread({}, state, {
    questions: _toConsumableArray(questions),
    answered: _objectSpread({}, state.answered, _defineProperty({}, action.questionId, action.result)),
    checkedQuestions: [].concat(_toConsumableArray(state.checkedQuestions), [action.questionId])
  });
};

var userQuiz = function userQuiz() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : STORE_DATA;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'SET_QUIZ_DATA':
      if (1 > action.data.questionsPerPage) {
        action.data.questionsPerPage = 1;
      }

      var chunks = chunk(state.questionIds || action.data.questionIds, action.data.questionsPerPage);
      action.data.numPages = chunks.length;
      action.data.pages = chunks;
      return _objectSpread({}, state, {}, action.data, {
        currentPage: storageGet("Q".concat(action.data.id, ".currentPage")) || action.data.currentPage
      });

    case 'SUBMIT_QUIZ':
      return _objectSpread({}, state, {
        submitting: true
      });

    case 'START_QUIZ':
    case 'START_QUIZ_SUCCESS':
      return resetCurrentPage(state, _objectSpread({
        checkedQuestions: [],
        hintedQuestions: [],
        mode: '',
        currentPage: 1
      }, action.results));

    case 'SET_CURRENT_QUESTION':
      storageSet("Q".concat(state.id, ".currentQuestion"), action.questionId);
      return _objectSpread({}, state, {
        currentQuestion: action.questionId
      });

    case 'SET_CURRENT_PAGE':
      storageSet("Q".concat(state.id, ".currentPage"), action.currentPage);
      return _objectSpread({}, state, {
        currentPage: action.currentPage
      });

    case 'SUBMIT_QUIZ_SUCCESS':
      return resetCurrentPage(state, _objectSpread({
        attempts: updateAttempt(state.attempts, action.results),
        submitting: false,
        currentPage: 1
      }, action.results));

    case 'UPDATE_USER_QUESTION_ANSWERS':
      return state.status === 'started' ? updateUserQuestionAnswer(state, action) : state;

    case 'MARK_QUESTION_RENDERED':
      return markQuestionRendered(state, action);

    case 'SET_QUIZ_MODE':
      if (action.mode == 'reviewing') {
        return resetCurrentPage(state, {
          mode: action.mode
        });
      }

      return _objectSpread({}, state, {
        mode: action.mode
      });

    case 'SET_QUESTION_HINT':
      return setQuestionHint(state, action);

    case 'CHECK_ANSWER':
      return checkAnswer(state, action);

    case 'SEND_KEY':
      return _objectSpread({}, state, {
        keyPressed: action.keyPressed
      });
  }

  return state;
};
var blocks = flow(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["combineReducers"], function (reducer) {
  return function (state, action) {
    //console.log('1', state)
    return reducer(state, action);
  };
}, function (reducer) {
  return function (state, action) {
    //console.log('2')
    return reducer(state, action);
  };
}, function (reducer) {
  return function (state, action) {
    //console.log('3')
    return reducer(state, action);
  };
})({
  a: function a() {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
      a: 1
    };
    var action = arguments.length > 1 ? arguments[1] : undefined;
    //console.log('a', action)
    return state;
  },
  b: function b() {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
      b: 2
    };
    var action = arguments.length > 1 ? arguments[1] : undefined;
    //console.log('b',action);
    return state;
  }
});
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["combineReducers"])({
  blocks: blocks,
  userQuiz: userQuiz
}));

/***/ }),

/***/ "./assets/src/js/frontend/quiz/store/selectors.js":
/*!********************************************************!*\
  !*** ./assets/src/js/frontend/quiz/store/selectors.js ***!
  \********************************************************/
/*! exports provided: getQuestionOptions, getItemStatus, getProp, getQuizAttempts, getQuizAnswered, getQuestions, getData, getDefaultRestArgs, getQuestionAnswered, getCurrentQuestion, getQuestion, isCheckedAnswer, isCorrect, getQuestionsSelectedAnswers, getUserMark */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getQuestionOptions", function() { return getQuestionOptions; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getItemStatus", function() { return getItemStatus; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getProp", function() { return getProp; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getQuizAttempts", function() { return getQuizAttempts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getQuizAnswered", function() { return getQuizAnswered; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getQuestions", function() { return getQuestions; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getData", function() { return getData; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getDefaultRestArgs", function() { return getDefaultRestArgs; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getQuestionAnswered", function() { return getQuestionAnswered; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getCurrentQuestion", function() { return getCurrentQuestion; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getQuestion", function() { return getQuestion; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isCheckedAnswer", function() { return isCheckedAnswer; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isCorrect", function() { return isCorrect; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getQuestionsSelectedAnswers", function() { return getQuestionsSelectedAnswers; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getUserMark", function() { return getUserMark; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);

var _lodash = lodash,
    get = _lodash.get,
    isArray = _lodash.isArray;

var getQuestionOptions = function getQuestionOptions(state, id) {
  console.time('parseOptions');
  var question = getQuestion(state, id);
  var options = question.options;
  options = !isArray(options) ? JSON.parse(CryptoJS.AES.decrypt(options.data, options.key, {
    format: CryptoJSAesJson
  }).toString(CryptoJS.enc.Utf8)) : options;
  options = !isArray(options) ? JSON.parse(options) : options;
  console.timeEnd('parseOptions');
  return options;
};


/**
 * Get current status of an item in course.
 *
 * @param state
 * @param itemId
 */

function getItemStatus(state, itemId) {
  var item = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["select"])('course-learner/user').getItemById(itemId);
  return item ? get(item, 'userSettings.status') : '';
}
function getProp(state, prop, defaultValue) {
  return state[prop] || defaultValue;
}
/**
 * Get quiz attempted.
 *
 * @param state
 * @param itemId
 * @return {Array}
 */

function getQuizAttempts(state, itemId) {
  var item = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["select"])('course-learner/user').getItemById(itemId);
  return item ? get(item, 'userSettings.attempts') : [];
}
/**
 * Get answers for a quiz user has did.
 *
 * @param state
 * @param itemId
 * @return {{}}
 */

function getQuizAnswered(state, itemId) {
  var item = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["select"])('course-learner/user').getItemById(itemId);
  return item ? get(item, 'userSettings.answered', {}) : {};
}
/**
 * Get all questions in quiz.
 *
 * @param state
 * @return {*}
 */

function getQuestions(state) {
  var userQuiz = state.userQuiz;
  var questions = get(userQuiz, 'questions');
  return questions ? Object.values(questions) : [];
}
/**
 * Get property of store data.
 *
 * @param state - Store data
 * @param prop - Optional. NULL will return all data.
 * @return {*}
 */

function getData(state, prop) {
  var userQuiz = state.userQuiz;

  if (prop) {
    return get(userQuiz, prop);
  }

  return userQuiz;
}
function getDefaultRestArgs(state) {
  var userQuiz = state.userQuiz;
  return {
    itemId: userQuiz.id,
    courseId: userQuiz.courseId
  };
}
function getQuestionAnswered(state, id) {
  var userQuiz = state.userQuiz;
  return get(userQuiz, "answered.".concat(id, ".answered")) || undefined;
}
/**
 * Get current question is doing.
 *
 * @param {object} state
 * @param {string} ret
 * @return {*}
 */

function getCurrentQuestion(state) {
  var ret = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  var questionsPerPage = get(state, 'userQuiz.questionsPerPage') || 1;

  if (questionsPerPage > 1) {
    return false;
  }

  var currentPage = get(state, 'userQuiz.currentPage') || 1;
  return ret === 'object' ? get(state, "userQuiz.questions[".concat(currentPage - 1, "]")) : get(state, "userQuiz.questionIds[".concat(currentPage - 1, "]"));
}
/**
 * Return a question contains fully data with title, content, ...
 *
 * @param state
 * @param theId
 */

var getQuestion = function getQuestion(state, theId) {
  var userQuiz = state.userQuiz;
  var s = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["select"])('learnpress/quiz');
  var questions = s.getQuestions();
  return questions.find(function (q) {
    return q.id == theId;
  });
};


/**
 * If user has used 'Instant check' for a question.
 *
 * @param {object} state - Global state for app.
 * @param {number} id
 * @return {boolean}
 */

function isCheckedAnswer(state, id) {
  var checkedQuestions = get(state, 'userQuiz.checkedQuestions') || [];
  return checkedQuestions.indexOf(id) !== -1;
}
function isCorrect(state, id) {}
/**
 * Get questions user has selected answers.
 *
 * @param {object} state. Global app state
 * @param {number} questionId
 * @return {{}}
 */

var getQuestionsSelectedAnswers = function getQuestionsSelectedAnswers(state, questionId) {
  var data = get(state, 'userQuiz.answered');
  var returnData = {};

  for (var loopId in data) {
    if (!data.hasOwnProperty(loopId)) {
      continue;
    } // Answer filled by user


    if (data[loopId].temp) {
      // If specific a question then return it only.
      if (questionId && loopId === questionId) {
        return data[loopId].answered;
      }

      returnData[loopId] = data[loopId].answered;
    }
  }

  return returnData;
};


/**
 * Get mark user earned.
 * Just for questions user has used 'Instant check' button.
 *
 * @param state
 * @return {number}
 */

function getUserMark(state) {
  var userQuiz = state.userQuiz || {};
  var answered = userQuiz.answered,
      negativeMarking = userQuiz.negativeMarking,
      questions = userQuiz.questions,
      checkedQuestions = userQuiz.checkedQuestions;
  var totalMark = 0;

  var _loop = function _loop(_id) {
    if (!answered.hasOwnProperty(_id)) {
      id = _id;
      return "continue";
    }

    _id = parseInt(_id);
    var data = answered[_id];
    var questionMark = data.questionMark ? data.questionMark : function () {
      var question = questions.find(function (q) {
        id = _id;
        return q.id === _id;
      });
      id = _id;
      return question ? question.point : 0;
    }();
    var isChecked = checkedQuestions.indexOf(_id) !== -1; // User checked option but not submit or check

    if (data.temp) {
      id = _id;
      return "continue";
    }

    if (negativeMarking) {
      if (data.answered) {
        totalMark = data.correct ? totalMark + data.mark : totalMark - questionMark;
      }
    } else {
      if (data.answered && data.correct) {
        totalMark += data.mark;
      }
    }

    id = _id;
  };

  for (var id in answered) {
    var _ret = _loop(id);

    if (_ret === "continue") continue;
  }

  return totalMark > 0 ? totalMark : 0;
}

/***/ }),

/***/ "./node_modules/refx/refx.js":
/*!***********************************!*\
  !*** ./node_modules/refx/refx.js ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function flattenIntoMap( map, effects ) {
	var i;
	if ( Array.isArray( effects ) ) {
		for ( i = 0; i < effects.length; i++ ) {
			flattenIntoMap( map, effects[ i ] );
		}
	} else {
		for ( i in effects ) {
			map[ i ] = ( map[ i ] || [] ).concat( effects[ i ] );
		}
	}
}

function refx( effects ) {
	var map = {},
		middleware;

	flattenIntoMap( map, effects );

	middleware = function( store ) {
		return function( next ) {
			return function( action ) {
				var handlers = map[ action.type ],
					result = next( action ),
					i, handlerAction;

				if ( handlers ) {
					for ( i = 0; i < handlers.length; i++ ) {
						handlerAction = handlers[ i ]( action, store );
						if ( handlerAction ) {
							store.dispatch( handlerAction );
						}
					}
				}

				return result;
			};
		};
	};

	middleware.effects = map;

	return middleware;
}

module.exports = refx;


/***/ }),

/***/ "@learnpress/data-controls":
/*!***********************************************!*\
  !*** external {"this":["LP","dataControls"]} ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["LP"]["dataControls"]; }());

/***/ }),

/***/ "@wordpress/compose":
/*!******************************************!*\
  !*** external {"this":["wp","compose"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["compose"]; }());

/***/ }),

/***/ "@wordpress/data":
/*!***************************************!*\
  !*** external {"this":["wp","data"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["data"]; }());

/***/ }),

/***/ "@wordpress/element":
/*!******************************************!*\
  !*** external {"this":["wp","element"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

/***/ }),

/***/ "@wordpress/i18n":
/*!***************************************!*\
  !*** external {"this":["wp","i18n"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["i18n"]; }());

/***/ })

/******/ });
//# sourceMappingURL=quiz.js.map