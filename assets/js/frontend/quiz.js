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
/*! exports provided: default, init */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "init", function() { return init; });
/* harmony import */ var _quiz_index__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./quiz/index */ "./assets/src/js/frontend/quiz/index.js");

/* harmony default export */ __webpack_exports__["default"] = (_quiz_index__WEBPACK_IMPORTED_MODULE_0__["default"]);
var init = function init(elem, settings) {
  wp.element.render(React.createElement(_quiz_index__WEBPACK_IMPORTED_MODULE_0__["default"], {
    settings: settings
  }), jQuery(elem)[0]);
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
      if (!attempt.expiration_time) {
        return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Unlimited', 'learnpress');
      }

      var formatDuration = LP.singleCourse.formatDuration;
      var milliseconds = new Date(attempt.expiration_time).getTime() - new Date(attempt.start_time).getTime();
      return milliseconds ? formatDuration(milliseconds / 1000) : '';
    }
  }, {
    key: "getTimeSpendLabel",
    value: function getTimeSpendLabel(attempt) {
      var formatDuration = LP.singleCourse.formatDuration;
      var milliseconds = new Date(attempt.end_time).getTime() - new Date(attempt.start_time).getTime();
      return milliseconds ? formatDuration(milliseconds / 1000) : '';
    }
  }, {
    key: "render",
    value: function render() {
      var _this = this;

      var _this$props = this.props,
          attempts = _this$props.attempts,
          attemptsCount = _this$props.attemptsCount;
      var hasAttempts = attempts && !!attempts.length;
      return React.createElement(React.Fragment, null, React.createElement("div", {
        className: "quiz-attempts"
      }, React.createElement("h4", {
        className: "attempts-heading"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Attempts', 'learnpress'), " ( ", attempts.length || 0, " / ", attemptsCount, " )"), hasAttempts && React.createElement("table", null, React.createElement("thead", null, React.createElement("tr", null, React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Date', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Questions', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Spend', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Marks', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Passing Grade', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Result', 'learnpress')))), React.createElement("tbody", null, attempts.map(function (row) {
        return React.createElement("tr", {
          key: "attempt-".concat(row.id)
        }, React.createElement("td", null, row.start_time), React.createElement("td", null, row.question_correct, " / ", row.question_count), React.createElement("td", null, _this.getTimeSpendLabel(row), " / ", _this.getDurationLabel(row)), React.createElement("td", null, row.user_mark, " / ", row.mark), React.createElement("td", null, row.passing_grade || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["_x"])('-', 'unknown passing grade value', 'learnpress')), React.createElement("td", null, parseFloat(row.result).toFixed(2), "% ", React.createElement("label", null, row.grade_text)));
      }))), !hasAttempts && React.createElement("p", {
        className: "no-attempts-message"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('There is no attempt now.', 'learnpress'))));
    }
  }]);

  return Attempts;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  return {
    id: getData('id'),
    attempts: getData('attempts'),
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
      return React.createElement("button", {
        className: "lp-button check",
        onClick: this.checkAnswer
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["_x"])('Check answer', 'label of button check answer', 'learnpress'));
    }
  }]);

  return ButtonCheck;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch, _ref) {
  var id = _ref.id;

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
      showHint(question.id);
    });

    return _this;
  }

  _createClass(ButtonHint, [{
    key: "render",
    value: function render() {
      return React.createElement("button", {
        className: "lp-button check",
        onClick: this.showHint
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Hint', 'learnpress'));
    }
  }]);

  return ButtonHint;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch, _ref) {
  var id = _ref.id;

  var _dispatch = dispatch('learnpress/quiz'),
      _showHint = _dispatch.showHint;

  return {
    showHint: function showHint(id) {
      _showHint(id);
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
      event.preventDefault();
      var startQuiz = _this.props.startQuiz;
      startQuiz();
    });

    _defineProperty(_assertThisInitialized(_this), "nav", function (to) {
      return function (event) {
        var _this$props = _this.props,
            questionNav = _this$props.questionNav,
            currentPage = _this$props.currentPage,
            numPages = _this$props.numPages,
            setCurrentPage = _this$props.setCurrentPage;

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

    _defineProperty(_assertThisInitialized(_this), "isLast", function () {
      var _this$props2 = _this.props,
          currentPage = _this$props2.currentPage,
          numPages = _this$props2.numPages;
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
    key: "render",

    /**
     * Render buttons
     *
     * @return {XML}
     */
    value: function render() {
      var _this$props3 = this.props,
          status = _this$props3.status,
          questionNav = _this$props3.questionNav,
          isReviewing = _this$props3.isReviewing,
          showReview = _this$props3.showReview,
          numPages = _this$props3.numPages,
          question = _this$props3.question,
          questionsLayout = _this$props3.questionsLayout;
      return React.createElement("div", {
        className: "quiz-buttons"
      }, React.createElement("div", {
        className: "button-left"
      }, -1 !== ['', 'completed'].indexOf(status) && !isReviewing && React.createElement("button", {
        className: "lp-button start",
        onClick: this.startQuiz
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Start', 'learnpress')), ('started' === status || isReviewing) && numPages > 1 && React.createElement(React.Fragment, null, ('infinity' === questionNav || !this.isFirst()) && React.createElement("button", {
        className: "lp-button nav prev",
        onClick: this.nav('prev')
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Prev', 'learnpress')), ('infinity' === questionNav || !this.isLast()) && React.createElement("button", {
        className: "lp-button nav next",
        onClick: this.nav('next')
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Next', 'learnpress')))), React.createElement("div", {
        className: "button-right"
      }, 'started' === status
      /*|| isReviewing*/
      && React.createElement(React.Fragment, null, questionsLayout === 1 && [React.createElement(MaybeShowButton, {
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
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  return {
    status: getData('status'),
    showHint: getData('showHint'),
    showCheck: getData('showCheckAnswers'),
    checkedQuestions: getData('checkedQuestions'),
    hintedQuestions: getData('hintedQuestions'),
    questionsLayout: getData('questionsLayout')
  };
}))(function (props) {
  var showHint = props.showHint,
      showCheck = props.showCheck,
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
      if (!showHint) {
        return false;
      }

      if (!hintedQuestions) {
        return theButton;
      }

      if (!question.has_hint) {
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
  var _select2 = select('learnpress/quiz'),
      getData = _select2.getData,
      getCurrentQuestion = _select2.getCurrentQuestion;

  var data = {
    id: getData('id'),
    status: getData('status'),
    questionIds: getData('questionIds'),
    questionNav: getData('questionNav'),
    isReviewing: getData('reviewQuestions') && getData('mode') === 'reviewing',
    showReview: getData('reviewQuestions'),
    showHint: getData('showHint'),
    showCheck: getData('showCheckAnswers'),
    checkedQuestions: getData('checkedQuestions'),
    hintedQuestions: getData('hintedQuestions'),
    numPages: getData('numPages'),
    currentPage: getData('currentPage'),
    questionsLayout: getData('questionsLayout')
  };

  if (data.questionsLayout === 1) {
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
        return React.createElement("li", {
          key: "quiz-intro-field-".concat(i)
        }, React.createElement("label", {
          dangerouslySetInnerHTML: {
            __html: field.label
          }
        }), React.createElement("span", {
          dangerouslySetInnerHTML: {
            __html: field.value
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

  return {
    metaFields: LP.Hook.applyFilters('quiz-meta-fields', {
      attemptsCount: {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Attempts allowed', 'learnpress'),
        content: getData('attemptsCount')
      },
      duration: {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Duration', 'learnpress'),
        content: getData('duration')
      },
      passingGrade: {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Passing grade', 'learnpress'),
        content: getData('passingGrade')
      },
      questionsCount: {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Questions', 'learnpress'),
        content: function () {
          var ids = getData('questionsIds');
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
  return React.createElement(React.Fragment, null, React.createElement(_buttons__WEBPACK_IMPORTED_MODULE_3__["MaybeShowButton"], {
    type: "hint",
    Button: _buttons_button_hint__WEBPACK_IMPORTED_MODULE_1__["default"],
    question: question
  }), React.createElement(_buttons__WEBPACK_IMPORTED_MODULE_3__["MaybeShowButton"], {
    type: "check",
    Button: _buttons_button_check__WEBPACK_IMPORTED_MODULE_2__["default"],
    question: question
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
          questionsLayout = _this$props.questionsLayout;
      return currentPage === Math.ceil(index / questionsLayout);
    });

    _this.needToTop = false;
    return _this;
  }

  _createClass(Questions, [{
    key: "componentWillReceiveProps",
    value: function componentWillReceiveProps(nextProps) {
      var checkProps = ['isReviewing', 'currentPage'];

      for (var i = 0; i < checkProps.length; i++) {
        if (this.props[checkProps[i]] !== nextProps[checkProps[i]]) {
          this.needToTop = true;
          return;
        }
      }
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate() {
      if (this.needToTop) {
        jQuery('#popup-content').animate({
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
          questionsLayout = _this$props2.questionsLayout;
      var viewMode = false,
          isShow = true; //if (!showAllQuestions) {

      if (status === 'completed' && !isReviewing) {
        isShow = false;
      } //}


      return React.createElement(React.Fragment, null, React.createElement("div", {
        className: "quiz-questions",
        style: {
          display: isShow ? '' : 'none'
        }
      }, questions.map(function (question, index) {
        var isCurrent = questionsLayout ? false : currentQuestion === question.id;
        var isRendered = questionsRendered && questionsRendered.indexOf(question.id) !== -1;

        var isVisible = _this2.isInVisibleRange(question.id, index + 1);

        return isRendered || !isRendered
        /*&& isCurrent*/
        || isVisible ? React.createElement(_question__WEBPACK_IMPORTED_MODULE_4__["default"], {
          isCurrent: isCurrent,
          key: "loop-question-".concat(question.id),
          isShow: isVisible,
          isShowIndex: questionsLayout ? index + 1 : false,
          questionsLayout: questionsLayout,
          question: question
        }) : '';
      })));
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
    questionsLayout: getData('questionsLayout') || 1
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/quiz'),
      startQuiz = _dispatch.startQuiz;

  return {
    startQuiz: startQuiz
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
    isNumber = _lodash.isNumber;

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
      options = !isArray(options) ? JSON.parse(CryptoJS.AES.decrypt(options.data, options.key, {
        format: CryptoJSAesJson
      }).toString(CryptoJS.enc.Utf8)) : options;
      options = !isArray(options) ? JSON.parse(options) : options;
      return options;
    });

    _defineProperty(_assertThisInitialized(_this), "getWrapperClass", function () {
      var _this$props = _this.props,
          question = _this$props.question,
          answered = _this$props.answered;
      var classes = ['question', 'question-' + question.type];

      if (_this.parseOptions(question.options)[0].is_true !== undefined) {
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
      }

      return a;
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props4 = this.props,
          question = _this$props4.question,
          isShow = _this$props4.isShow,
          isShowIndex = _this$props4.isShowIndex,
          questionsLayout = _this$props4.questionsLayout,
          status = _this$props4.status;
      var QuestionTypes = LP.questionTypes["default"];
      var editPermalink = this.getEditLink();

      if (editPermalink) {
        jQuery('#wp-admin-bar-edit-lp_question').find('.ab-item').attr('href', editPermalink);
      }

      return React.createElement(React.Fragment, null, React.createElement("div", {
        className: this.getWrapperClass().join(' '),
        style: {
          display: isShow ? '' : 'none'
        },
        ref: this.setRef
      }, React.createElement("h4", {
        className: "question-title"
      }, isShowIndex ? React.createElement("span", {
        className: "question-index"
      }, isShowIndex, ".") : '', question.title, editPermalink && React.createElement("span", {
        dangerouslySetInnerHTML: {
          __html: this.editPermalink(editPermalink)
        },
        className: "edit-link"
      })), React.createElement("div", {
        className: "question-content",
        dangerouslySetInnerHTML: {
          __html: question.content
        }
      }), React.createElement(QuestionTypes, _objectSpread({}, this.props, {
        $wrap: this.$wrap
      })), question.explanation && React.createElement(React.Fragment, null, React.createElement("div", {
        className: "question-explanation-content"
      }, React.createElement("strong", {
        className: "explanation-title"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Explanation:', 'learnpress')), React.createElement("div", {
        dangerouslySetInnerHTML: {
          __html: question.explanation
        }
      }))), question.hint && React.createElement(React.Fragment, null, React.createElement("div", {
        className: "question-hint-content"
      }, React.createElement("strong", {
        className: "hint-title"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Hint:', 'learnpress')), React.createElement("div", {
        dangerouslySetInnerHTML: {
          __html: question.hint
        }
      }))), 'started' === status && questionsLayout > 1 && React.createElement(_buttons__WEBPACK_IMPORTED_MODULE_4__["default"], {
        question: question
      })));
    }
  }]);

  return Question;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select, _ref) {
  var id = _ref.question.id;

  var _select = select('learnpress/quiz'),
      getData = _select.getData,
      getQuestionAnswered = _select.getQuestionAnswered,
      isCorrect = _select.isCorrect;

  return {
    status: getData('status'),
    questions: getData('question'),
    answered: getQuestionAnswered(id),
    questionsRendered: getData('questionsRendered'),
    editPermalink: getData('editPermalink'),
    isCorrect: isCorrect(id),
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

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }





var _lodash = lodash,
    get = _lodash.get;

var Result =
/*#__PURE__*/
function (_Component) {
  _inherits(Result, _Component);

  function Result() {
    _classCallCheck(this, Result);

    return _possibleConstructorReturn(this, _getPrototypeOf(Result).apply(this, arguments));
  }

  _createClass(Result, [{
    key: "getResultMessage",
    value: function getResultMessage(results) {
      return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Your grade is <strong>%s</strong>', 'learnpress'), results.grade_text);
    }
  }, {
    key: "getResultPercentage",
    value: function getResultPercentage(results) {
      return parseFloat(results.result).toFixed(2);
    }
  }, {
    key: "render",
    value: function render() {
      var results = this.props.results;
      return React.createElement("div", {
        className: "quiz-result"
      }, React.createElement("h3", {
        className: "result-heading"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Your Result', 'learnpress')), React.createElement("div", {
        className: "result-grade"
      }, React.createElement("span", {
        className: "result-achieved"
      }, this.getResultPercentage(results), "%"), React.createElement("span", {
        className: "result-require"
      }, undefined !== results.passing_grade ? results.passing_grade : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["_x"])('-', 'unknown passing grade value', 'learnpress')), React.createElement("p", {
        className: "result-message",
        dangerouslySetInnerHTML: {
          __html: this.getResultMessage(results)
        }
      })), React.createElement("ul", {
        className: "result-statistic"
      }, React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Time spend', 'learnpress')), React.createElement("p", null, results.time_spend)), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Point', 'learnpress')), React.createElement("p", null, results.user_mark, " / ", results.mark)), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Questions', 'learnpress')), React.createElement("p", null, results.question_count)), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Correct', 'learnpress')), React.createElement("p", null, results.question_correct)), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Wrong', 'learnpress')), React.createElement("p", null, results.question_wrong)), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Skipped', 'learnpress')), React.createElement("p", null, results.question_empty))));
    }
  }]);

  return Result;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  return {
    results: get(getData('attempts'), '0')
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

var Status =
/*#__PURE__*/
function (_Component) {
  _inherits(Status, _Component);

  function Status() {
    var _this;

    _classCallCheck(this, Status);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Status).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "getCurrentQuestionIndex", function () {
      var _this$props = _this.props,
          questionIds = _this$props.questionIds,
          currentQuestion = _this$props.currentQuestion;
      var at = questionIds.indexOf(currentQuestion);
      return at !== false ? at + 1 : 0;
    });

    return _this;
  }

  _createClass(Status, [{
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          content = _this$props2.content,
          questionIds = _this$props2.questionIds;
      var result = {
        timeSpend: 123,
        marks: [],
        questionsCount: 5,
        questionsCorrect: [],
        questionsWrong: [],
        questionsSkipped: []
      };
      var c = this.getCurrentQuestionIndex();
      return React.createElement("div", {
        className: "quiz-status"
      }, React.createElement("div", null, React.createElement("div", null, "".concat(c, " of ").concat(questionIds.length)), React.createElement(_timer__WEBPACK_IMPORTED_MODULE_3__["default"], null)));
    }
  }]);

  return Status;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  return {
    questionIds: getData('questionIds'),
    currentQuestion: getData('currentQuestion')
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/quiz'),
      setQuizData = _dispatch.setQuizData,
      startQuiz = _dispatch.startQuiz;

  return {
    setQuizData: setQuizData,
    startQuiz: startQuiz
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

  function Timer() {
    var _this;

    _classCallCheck(this, Timer);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Timer).apply(this, arguments));

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

    var _t = 1800;
    _this.state = {
      seconds: _t,
      totalTime: 3600,
      remainingSeconds: _t,
      currentTime: parseInt(new Date().getTime() / 1000)
    };
    return _this;
  }

  _createClass(Timer, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      this.myInterval = setInterval(function () {
        var _this2$state = _this2.state,
            seconds = _this2$state.seconds,
            currentTime = _this2$state.currentTime;
        var offset = parseInt(new Date().getTime() / 1000) - currentTime;
        var remainingSeconds = seconds - offset;

        if (remainingSeconds > 0) {
          _this2.setState(function (_ref) {
            var seconds = _ref.seconds;
            return {
              remainingSeconds: remainingSeconds
            };
          });
        }

        if (remainingSeconds === 0) {
          clearInterval(_this2.myInterval);
        }
      }, 500);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      clearInterval(this.myInterval);
    }
  }, {
    key: "render",
    value: function render() {
      var content = this.props.content;
      return React.createElement("div", null, this.formatTime());
    }
  }]);

  return Timer;
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
      console.time('Quiz.componentDidMount');
      var _this$props = this.props,
          settings = _this$props.settings,
          setQuizData = _this$props.setQuizData;
      var sanitizedSettings = {};

      function camelCaseDash(string) {
        return string.replace(/[-_]([a-z])/g, function (match, letter) {
          return letter.toUpperCase();
        });
      }

      for (var prop in settings) {
        if (!settings.hasOwnProperty(prop)) {
          continue;
        }

        sanitizedSettings[camelCaseDash(prop)] = settings[prop];
      }

      var questionIds = sanitizedSettings.questionIds,
          questionsLayout = sanitizedSettings.questionsLayout;
      var chunks = chunk(questionIds, questionsLayout);
      sanitizedSettings.currentPage = 1;
      sanitizedSettings.numPages = chunks.length;
      sanitizedSettings.pages = chunks;
      console.timeEnd('Quiz.componentDidMount');
      console.log(sanitizedSettings);
      setQuizData(sanitizedSettings);
    }
  }, {
    key: "componentWillReceiveProps",
    value: function componentWillReceiveProps(nextProps) {
      console.time('QUIZ');
      var questionIds = nextProps.questionIds,
          questionsLayout = nextProps.questionsLayout,
          setQuizData = nextProps.setQuizData;
      var chunks = chunk(questionIds, questionsLayout); // setQuizData({
      //     numPages: chunks.length,
      //     pages: chunks
      // });
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate() {
      console.timeEnd('QUIZ');
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          status = _this$props2.status,
          isReviewing = _this$props2.isReviewing; // const {
      //     numPages,
      //     currentPage,
      //     pages
      // } = this.state;

      var isA = -1 !== ['', 'completed'].indexOf(status); // Just render content if status !== undefined (meant all data loaded)

      return undefined !== status && React.createElement(React.Fragment, null, !isReviewing && 'completed' === status && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Result"], null), !isReviewing && !status && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Meta"], null), !isReviewing && isA && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Content"], null), 'started' === status && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Status"], null), (-1 !== ['completed', 'started'].indexOf(status) || isReviewing) && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Questions"], null), React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Buttons"], null), isA && !isReviewing && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Attempts"], null));
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
    hintCount: getData('showHint'),
    questionIds: getData('questionIds'),
    checkCount: getData('showCheckAnswers'),
    questionsLayout: getData('questionsLayout') || 1
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
/*! exports provided: setQuizData, setCurrentQuestion, setCurrentPage, __requestStartQuizSuccess, startQuiz, __requestSubmitQuizSuccess, submitQuiz, updateUserQuestionAnswers, __requestShowHintSuccess, showHint, __requestCheckAnswerSuccess, checkAnswer, markQuestionRendered, setQuizMode */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setQuizData", function() { return setQuizData; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setCurrentQuestion", function() { return setCurrentQuestion; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setCurrentPage", function() { return setCurrentPage; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestStartQuizSuccess", function() { return __requestStartQuizSuccess; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "startQuiz", function() { return startQuiz; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestSubmitQuizSuccess", function() { return __requestSubmitQuizSuccess; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "submitQuiz", function() { return submitQuiz; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateUserQuestionAnswers", function() { return updateUserQuestionAnswers; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestShowHintSuccess", function() { return __requestShowHintSuccess; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "showHint", function() { return showHint; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestCheckAnswerSuccess", function() { return __requestCheckAnswerSuccess; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "checkAnswer", function() { return checkAnswer; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "markQuestionRendered", function() { return markQuestionRendered; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setQuizMode", function() { return setQuizMode; });
/* harmony import */ var _learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @learnpress/data-controls */ "@learnpress/data-controls");
/* harmony import */ var _learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var _marked =
/*#__PURE__*/
regeneratorRuntime.mark(startQuiz),
    _marked2 =
/*#__PURE__*/
regeneratorRuntime.mark(submitQuiz),
    _marked3 =
/*#__PURE__*/
regeneratorRuntime.mark(showHint),
    _marked4 =
/*#__PURE__*/
regeneratorRuntime.mark(checkAnswer);

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }



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
    data: data
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
function __requestStartQuizSuccess(data, quizId, courseId, userId) {
  return {
    type: 'START_QUIZ_SUCCESS',
    quizId: quizId,
    courseId: courseId,
    userId: userId,
    data: data
  };
}
function startQuiz() {
  var _wpSelect$getDefaultR, item_id, course_id, quiz;

  return regeneratorRuntime.wrap(function startQuiz$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          //yield dispatch('learnpress/quiz', '__requestStartQuizStart');
          _wpSelect$getDefaultR = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/quiz').getDefaultRestArgs(), item_id = _wpSelect$getDefaultR.item_id, course_id = _wpSelect$getDefaultR.course_id;
          _context.next = 3;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: 'lp/v1/users/start-quiz',
            method: 'POST',
            data: {
              item_id: item_id,
              course_id: course_id
            }
          });

        case 3:
          quiz = _context.sent;
          _context.next = 6;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('learnpress/quiz', '__requestStartQuizSuccess', quiz);

        case 6:
        case "end":
          return _context.stop();
      }
    }
  }, _marked);
}
function __requestSubmitQuizSuccess(results) {
  return {
    type: 'SUBMIT_QUIZ_SUCCESS',
    results: results
  };
}
function submitQuiz() {
  var _wpSelect, getDefaultRestArgs, getData, _getDefaultRestArgs, item_id, course_id, answered, result;

  return regeneratorRuntime.wrap(function submitQuiz$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _wpSelect = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/quiz'), getDefaultRestArgs = _wpSelect.getDefaultRestArgs, getData = _wpSelect.getData;
          _getDefaultRestArgs = getDefaultRestArgs(), item_id = _getDefaultRestArgs.item_id, course_id = _getDefaultRestArgs.course_id;
          answered = getData('answered');
          _context2.next = 5;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: 'lp/v1/users/submit-quiz',
            method: 'POST',
            data: {
              item_id: item_id,
              course_id: course_id,
              answered: answered
            }
          });

        case 5:
          result = _context2.sent;

          if (!result.success) {
            _context2.next = 9;
            break;
          }

          _context2.next = 9;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('learnpress/quiz', '__requestSubmitQuizSuccess', result.results);

        case 9:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2);
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
function __requestShowHintSuccess(id, result) {
  return _objectSpread({
    type: 'SET_QUESTION_HINT',
    questionId: id
  }, result);
}
function showHint(id) {
  var _wpSelect2, getDefaultRestArgs, getData, _getDefaultRestArgs2, item_id, course_id, result;

  return regeneratorRuntime.wrap(function showHint$(_context3) {
    while (1) {
      switch (_context3.prev = _context3.next) {
        case 0:
          _wpSelect2 = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/quiz'), getDefaultRestArgs = _wpSelect2.getDefaultRestArgs, getData = _wpSelect2.getData;
          _getDefaultRestArgs2 = getDefaultRestArgs(), item_id = _getDefaultRestArgs2.item_id, course_id = _getDefaultRestArgs2.course_id;
          _context3.next = 4;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: 'lp/v1/users/hint-answer',
            method: 'POST',
            data: {
              item_id: item_id,
              course_id: course_id,
              question_id: id
            }
          });

        case 4:
          result = _context3.sent;
          _context3.next = 7;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('learnpress/quiz', '__requestShowHintSuccess', id, result);

        case 7:
        case "end":
          return _context3.stop();
      }
    }
  }, _marked3);
}
function __requestCheckAnswerSuccess(id, result) {
  return _objectSpread({
    type: 'CHECK_ANSWER',
    questionId: id
  }, result);
}
function checkAnswer(id) {
  var _wpSelect3, getData, getDefaultRestArgs, getQuestionAnswered, getQuestionOptions, _getDefaultRestArgs3, item_id, course_id, result;

  return regeneratorRuntime.wrap(function checkAnswer$(_context4) {
    while (1) {
      switch (_context4.prev = _context4.next) {
        case 0:
          console.time('checkAnswer');
          _wpSelect3 = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/quiz'), getData = _wpSelect3.getData, getDefaultRestArgs = _wpSelect3.getDefaultRestArgs, getQuestionAnswered = _wpSelect3.getQuestionAnswered, getQuestionOptions = _wpSelect3.getQuestionOptions; // if(getData('crypto')){
          //     const options = getQuestionOptions(id);
          //     console.log(options)
          // }

          _getDefaultRestArgs3 = getDefaultRestArgs(), item_id = _getDefaultRestArgs3.item_id, course_id = _getDefaultRestArgs3.course_id;
          _context4.next = 5;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: 'lp/v1/users/check-answer',
            method: 'POST',
            data: {
              item_id: item_id,
              course_id: course_id,
              question_id: id,
              answered: getQuestionAnswered(id) || ''
            }
          });

        case 5:
          result = _context4.sent;
          _context4.next = 8;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('learnpress/quiz', '__requestCheckAnswerSuccess', id, result);

        case 8:
          console.timeEnd('checkAnswer');

        case 9:
        case "end":
          return _context4.stop();
      }
    }
  }, _marked4);
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

  var newAnswer = _defineProperty({}, action.questionId, action.answers);

  return _objectSpread({}, state, {
    answered: _objectSpread({}, answered || {}, {}, newAnswer)
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

var resetCurrentQuestion = function resetCurrentQuestion(state, args) {
  var questionIds = state.questionIds;
  return _objectSpread({}, state, {}, args, {
    currentQuestion: questionIds ? questionIds[0] : false
  });
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
      hint: action.hint
    }) : question;
  });
  return _objectSpread({}, state, {
    questions: _toConsumableArray(questions),
    hintedQuestions: [].concat(_toConsumableArray(state.hintedQuestions), [action.questionId])
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
    answered: _objectSpread({}, state.answered, _defineProperty({}, action.questionId, action.answered || '')),
    checkedQuestions: [].concat(_toConsumableArray(state.checkedQuestions), [action.questionId])
  });
};

var userQuiz = function userQuiz() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : STORE_DATA;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'SET_QUIZ_DATA':
      if (action.data.questionsLayout) {
        var chunks = chunk(state.questionIds || action.data.questionIds, action.data.questionsLayout);
        action.data.numPages = chunks.length;
        action.data.pages = chunks;
      }

      return _objectSpread({}, state, {}, action.data, {
        currentQuestion: LP.localStorage.get("Q".concat(action.data.id, ".currentQuestion")) || action.data.currentQuestion
      });

    case 'START_QUIZ':
    case 'START_QUIZ_SUCCESS':
      return resetCurrentQuestion(state, {
        status: 'started',
        checkedQuestions: [],
        hintedQuestions: [],
        mode: '',
        answered: {}
      });

    case 'SET_CURRENT_QUESTION':
      LP.localStorage.set("Q".concat(state.id, ".currentQuestion"), action.questionId);
      return _objectSpread({}, state, {
        currentQuestion: action.questionId
      });

    case 'SET_CURRENT_PAGE':
      return _objectSpread({}, state, {
        currentPage: action.currentPage
      });

    case 'SUBMIT_QUIZ_SUCCESS':
      return resetCurrentQuestion(state, {
        status: 'completed',
        attempts: updateAttempt(state.attempts, action.results),
        answered: false
      });

    case 'UPDATE_USER_QUESTION_ANSWERS':
      return state.status === 'started' ? updateUserQuestionAnswer(state, action) : state;

    case 'MARK_QUESTION_RENDERED':
      return markQuestionRendered(state, action);

    case 'SET_QUIZ_MODE':
      if (action.mode == 'reviewing') {
        return resetCurrentQuestion(state, {
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
/*! exports provided: getQuestionOptions, getItemStatus, getProp, getQuizAttempts, getQuizAnswered, getQuestions, getData, getDefaultRestArgs, getQuestionAnswered, getCurrentQuestion, getQuestion, isCheckedAnswer, isCorrect */
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
    item_id: userQuiz.id,
    course_id: userQuiz.courseId
  };
}
function getQuestionAnswered(state, id) {
  var userQuiz = state.userQuiz;
  var answered;

  if (userQuiz.status === 'started') {
    answered = get(userQuiz, 'answered');
  } else {
    answered = get(userQuiz, 'attempts[0].answered');
  }

  return answered ? answered[id] : undefined;
}
function getCurrentQuestion(state) {
  var ret = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  var questionsLayout = get(state, 'userQuiz.questionsLayout') || 1;

  if (questionsLayout > 1) {
    return false;
  }

  var currentPage = get(state, 'userQuiz.currentPage') || 1;
  return ret === 'object' ? get(state, "userQuiz.questions[".concat(currentPage - 1, "]")) : get(state, "userQuiz.questionIds[".concat(currentPage - 1, "]"));
}

var getQuestion = function getQuestion(state, theId) {
  var userQuiz = state.userQuiz;
  var s = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["select"])('learnpress/quiz');
  var questions = s.getQuestions();
  return questions.find(function (q) {
    return q.id == theId;
  });
};


function isCheckedAnswer(state, id) {
  var checkedQuestions = get(state, 'userQuiz.checkedQuestions') || [];
  return checkedQuestions.indexOf(id) !== -1;
}
function isCorrect(state, id) {}

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