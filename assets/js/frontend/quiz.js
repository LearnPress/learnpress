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

var Attempts =
/*#__PURE__*/
function (_Component) {
  _inherits(Attempts, _Component);

  function Attempts() {
    _classCallCheck(this, Attempts);

    return _possibleConstructorReturn(this, _getPrototypeOf(Attempts).apply(this, arguments));
  }

  _createClass(Attempts, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          status = _this$props.status,
          attempts = _this$props.attempts,
          questionNav = _this$props.questionNav,
          attemptsCount = _this$props.attemptsCount;
      var hasAttempts = attempts && !!attempts.length;
      return React.createElement(React.Fragment, null, React.createElement("h4", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Attempts', 'learnpress'), " ( ", attempts.length || 0, " / ", attemptsCount, " )"), hasAttempts && React.createElement("table", {
        className: "quiz-attempts"
      }, React.createElement("thead", null, React.createElement("tr", null, React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Date', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Questions', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Spend', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Marks', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Passing Grade', 'learnpress')), React.createElement("th", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Result', 'learnpress')))), React.createElement("tbody", null, attempts.map(function (row) {
        var rowId = 'attempts-' + uniqueId();
        return React.createElement("tr", {
          key: rowId
        }, React.createElement("td", null, row.time), React.createElement("td", null, row.questions), React.createElement("td", null, row.spendTime[0], " / ", row.spendTime[1]), React.createElement("td", null, row.marks[0], " / ", row.marks[1]), React.createElement("td", null, row.passingGrade), React.createElement("td", null, row.result));
      }))), !hasAttempts && React.createElement("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('There is no attempt now.', 'learnpress')));
    }
  }]);

  return Attempts;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select, a, b) {
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

/***/ "./assets/src/js/frontend/quiz/components/buttons/index.js":
/*!*****************************************************************!*\
  !*** ./assets/src/js/frontend/quiz/components/buttons/index.js ***!
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
            setCurrentQuestion = _this$props.setCurrentQuestion,
            currentQuestion = _this$props.currentQuestion,
            questionIds = _this$props.questionIds,
            questionNav = _this$props.questionNav;
        var currentAt = questionIds.indexOf(currentQuestion);

        switch (to) {
          case 'prev':
            currentAt = currentAt > 0 ? currentAt - 1 : questionNav === 'infinity' ? questionIds.length - 1 : currentAt;
            break;

          default:
            currentAt = currentAt < questionIds.length - 1 ? currentAt + 1 : questionNav === 'infinity' ? 0 : currentAt;
        }

        setCurrentQuestion(questionIds[currentAt]);
      };
    });

    _defineProperty(_assertThisInitialized(_this), "isLast", function () {
      var _this$props2 = _this.props,
          currentQuestion = _this$props2.currentQuestion,
          questionIds = _this$props2.questionIds;
      return questionIds.indexOf(currentQuestion) === questionIds.length - 1;
    });

    _defineProperty(_assertThisInitialized(_this), "isFirst", function () {
      var _this$props3 = _this.props,
          currentQuestion = _this$props3.currentQuestion,
          questionIds = _this$props3.questionIds;
      return questionIds.indexOf(currentQuestion) === 0;
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
    value: function render() {
      var _this$props4 = this.props,
          status = _this$props4.status,
          questionNav = _this$props4.questionNav,
          isReviewing = _this$props4.isReviewing;
      return React.createElement("div", {
        className: "quiz-buttons"
      }, -1 !== ['', 'completed'].indexOf(status) && !isReviewing && React.createElement("button", {
        className: "lp-button start",
        onClick: this.startQuiz
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Start', 'learnpress')), ('started' === status || isReviewing) && React.createElement(React.Fragment, null, ('infinity' === questionNav || !this.isFirst()) && React.createElement("button", {
        className: "lp-button nav prev",
        onClick: this.nav('prev')
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Prev', 'learnpress')), ('infinity' === questionNav || !this.isLast()) && React.createElement("button", {
        className: "lp-button nav next",
        onClick: this.nav('next')
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Next', 'learnpress')), ('infinity' === questionNav || this.isLast()) && !isReviewing && React.createElement("button", {
        className: "lp-button submit-quiz",
        onClick: this.submit
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Submit', 'learnpress'))), isReviewing && React.createElement("button", {
        className: "lp-button back-quiz",
        onClick: this.setQuizMode('')
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Back', 'learnpress')), 'completed' === status && !isReviewing && React.createElement("button", {
        className: "lp-button review-quiz",
        onClick: this.setQuizMode('reviewing')
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Review', 'learnpress')));
    }
  }]);

  return Buttons;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select, a, b) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  return {
    id: getData('id'),
    status: getData('status'),
    questionIds: getData('questionIds'),
    questionNav: getData('questionNav'),
    currentQuestion: getData('currentQuestion'),
    isReviewing: getData('mode') === 'reviewing'
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch, _ref) {
  var id = _ref.id;

  var _dispatch = dispatch('learnpress/quiz'),
      startQuiz = _dispatch.startQuiz,
      setCurrentQuestion = _dispatch.setCurrentQuestion,
      _submitQuiz = _dispatch.submitQuiz,
      setQuizMode = _dispatch.setQuizMode;

  return {
    startQuiz: startQuiz,
    setCurrentQuestion: setCurrentQuestion,
    setQuizMode: setQuizMode,
    submitQuiz: function submitQuiz() {
      _submitQuiz(id);
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
      return React.createElement("div", null, React.createElement("ul", {
        className: "quiz-intro"
      }, React.createElement("li", null, React.createElement("label", null, "Attempts allowed"), React.createElement("span", null, "3")), React.createElement("li", null, React.createElement("label", null, "Duration"), React.createElement("span", null, "00:10:00")), React.createElement("li", null, React.createElement("label", null, "Passing grade"), React.createElement("span", null, "90%")), React.createElement("li", null, React.createElement("label", null, "Questions"), React.createElement("span", null, "5"))));
    }
  }]);

  return Meta;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Meta);

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







var Questions =
/*#__PURE__*/
function (_Component) {
  _inherits(Questions, _Component);

  function Questions() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, Questions);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(Questions)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_this), "startQuiz", function (event) {
      event.preventDefault();
      var startQuiz = _this.props.startQuiz;
      startQuiz();
    });

    return _this;
  }

  _createClass(Questions, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          status = _this$props.status,
          currentQuestion = _this$props.currentQuestion,
          questions = _this$props.questions,
          questionsRendered = _this$props.questionsRendered,
          isReviewing = _this$props.isReviewing;
      var viewMode = false,
          isShow = true;

      if (status === 'completed' && !isReviewing) {
        isShow = false;
      }

      return React.createElement(React.Fragment, null, "[", isReviewing, "]", React.createElement("div", {
        className: "quiz-questions",
        style: {
          display: isShow ? '' : 'none'
        }
      }, questions.map(function (question) {
        var isCurrent = currentQuestion === question.id;
        var isRendered = questionsRendered && questionsRendered.indexOf(question.id) !== -1;
        return isRendered || !isRendered && isCurrent ? React.createElement(_question__WEBPACK_IMPORTED_MODULE_4__["default"], {
          isCurrent: isCurrent,
          key: "loop-question-".concat(question.id),
          question: question
        }) : '';
      })));
    }
  }]);

  return Questions;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData,
      getQuestions = _select.getQuestions;

  return {
    status: getData('status'),
    currentQuestion: getData('currentQuestion'),
    questions: getQuestions(),
    questionsRendered: getData('questionsRendered'),
    isReviewing: getData('mode') === 'reviewing'
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/quiz'),
      startQuiz = _dispatch.startQuiz;

  return {
    startQuiz: startQuiz
  };
})])(Questions));

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





var $ = window.jQuery;
var _lodash = lodash,
    uniqueId = _lodash.uniqueId;

var Question =
/*#__PURE__*/
function (_Component) {
  _inherits(Question, _Component);

  function Question() {
    var _this;

    _classCallCheck(this, Question);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Question).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "setAnswerChecked", function () {
      return function (event) {
        var $options = _this.$wrap.find('.option-check');

        var answered = [];
        var _this$props = _this.props,
            updateUserQuestionAnswers = _this$props.updateUserQuestionAnswers,
            question = _this$props.question;
        var isSingle = question.type !== 'multi_choice';
        $options.each(function (i, option) {
          if (option.checked) {
            answered.push(option.value);

            if (isSingle) {
              return false;
            }
          }
        });
        updateUserQuestionAnswers(question.id, isSingle ? answered[0] : answered);
      };
    });

    _defineProperty(_assertThisInitialized(_this), "getOptionType", function (questionType, option) {
      var type = 'radio';

      switch (questionType) {
        case 'multi_choice':
          type = 'checkbox';
          break;
      }

      return type;
    });

    _defineProperty(_assertThisInitialized(_this), "setRef", function (el) {
      _this.$wrap = $(el);
    });

    _this.$wrap = null;
    return _this;
  }

  _createClass(Question, [{
    key: "componentDidMount",
    value: function componentDidMount(a) {
      var _this$props2 = this.props,
          question = _this$props2.question,
          isCurrent = _this$props2.isCurrent,
          markQuestionRendered = _this$props2.markQuestionRendered;

      if (isCurrent) {
        markQuestionRendered(question.id);
      }

      return a;
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props3 = this.props,
          status = _this$props3.status,
          question = _this$props3.question,
          isCurrent = _this$props3.isCurrent,
          markQuestionRendered = _this$props3.markQuestionRendered,
          questionsRendered = _this$props3.questionsRendered;
      return React.createElement("div", {
        className: "question",
        style: {
          display: isCurrent ? '' : 'none'
        },
        ref: this.setRef
      }, React.createElement("h4", null, question.title), React.createElement("div", {
        dangerouslySetInnerHTML: {
          __html: question.content
        }
      }), "[", JSON.stringify(question.answered), "]", React.createElement("ul", {
        id: "answer-options-".concat(question.id),
        className: "answer-options"
      }, question.options.map(function (option) {
        var optionId = uniqueId();
        return React.createElement("li", {
          className: "answer-option",
          key: "answer-option-".concat(option.question_answer_id)
        }, React.createElement("label", null, React.createElement("input", {
          type: _this2.getOptionType(question.type, option),
          className: "option-check",
          name: "learn-press-question-".concat(question.id),
          id: "learn-press-answer-option-".concat(optionId),
          onChange: _this2.setAnswerChecked(),
          value: option.value
        }), React.createElement("div", {
          className: "option-title"
        }, React.createElement("div", {
          className: "option-title-content",
          htmlFor: "learn-press-answer-option-".concat(optionId),
          dangerouslySetInnerHTML: {
            __html: option.text
          }
        }))));
      })));
    }
  }]);

  return Question;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select = select('learnpress/quiz'),
      getData = _select.getData;

  return {
    status: getData('status'),
    questions: getData('question'),
    answered: getData('answered'),
    questionsRendered: getData('questionsRendered')
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





var _React = React,
    useState = _React.useState;

var Result =
/*#__PURE__*/
function (_Component) {
  _inherits(Result, _Component);

  function Result() {
    _classCallCheck(this, Result);

    return _possibleConstructorReturn(this, _getPrototypeOf(Result).apply(this, arguments));
  }

  _createClass(Result, [{
    key: "componentDidMount",
    value: function componentDidMount() {}
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {}
  }, {
    key: "render",
    value: function render() {
      var content = this.props.content;
      var result = {
        timeSpend: 123,
        marks: [],
        questionsCount: 5,
        questionsCorrect: [],
        questionsWrong: [],
        questionsSkipped: []
      };
      return React.createElement("div", {
        className: "quiz-result"
      }, React.createElement("h3", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Your Result', 'learnpress')), React.createElement("div", {
        className: "result-grade"
      }, React.createElement("span", {
        className: "result-achieved"
      }, "0%"), React.createElement("span", {
        className: "result-require"
      }, "60%"), React.createElement("p", {
        className: "result-message"
      }, "Your grade is ", React.createElement("strong", null, "failed"), " ")), React.createElement("ul", {
        className: "result-statistic"
      }, React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, "Time spend"), React.createElement("p", null, "02:16:26:39")), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, "Point"), React.createElement("p", null, "0 / 5")), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, "Questions"), React.createElement("p", null, "5")), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, "Correct"), React.createElement("p", null, "0")), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, "Wrong"), React.createElement("p", null, "0")), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, "Skipped"), React.createElement("p", null, "5")), React.createElement("li", {
        className: "result-statistic-field"
      }, React.createElement("label", null, "Attempt"), React.createElement("p", null, "5/10"))));
    }
  }]);

  return Result;
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







var Quiz =
/*#__PURE__*/
function (_Component) {
  _inherits(Quiz, _Component);

  function Quiz() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, Quiz);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(Quiz)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_this), "startQuiz", function (event) {
      _this.props.startQuiz();
    });

    return _this;
  }

  _createClass(Quiz, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          settings = _this$props.settings,
          setQuizData = _this$props.setQuizData;
      setQuizData(settings);
      jQuery('#popup-content').scroll(function () {
        jQuery('.quiz-status').css('top', jQuery(this).scrollTop());
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          status = _this$props2.status,
          isReviewing = _this$props2.isReviewing;
      var isA = -1 !== ['', 'completed'].indexOf(status);
      return React.createElement(React.Fragment, null, !isReviewing && 'completed' === status && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Result"], null), !isReviewing && !status && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Meta"], null), !isReviewing && isA && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Content"], null), 'started' === status && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Status"], null), (-1 !== ['completed', 'started'].indexOf(status) || isReviewing) && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Questions"], null), React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Buttons"], null), isA && !isReviewing && React.createElement(_components__WEBPACK_IMPORTED_MODULE_3__["Attempts"], null));
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
    isReviewing: getData('mode') === 'reviewing'
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
/*! exports provided: setQuizData, setCurrentQuestion, __requestStartQuizSuccess, startQuiz, submitQuiz, updateUserQuestionAnswers, markQuestionRendered, setQuizMode */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setQuizData", function() { return setQuizData; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setCurrentQuestion", function() { return setCurrentQuestion; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__requestStartQuizSuccess", function() { return __requestStartQuizSuccess; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "startQuiz", function() { return startQuiz; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "submitQuiz", function() { return submitQuiz; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateUserQuestionAnswers", function() { return updateUserQuestionAnswers; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "markQuestionRendered", function() { return markQuestionRendered; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setQuizMode", function() { return setQuizMode; });
/* harmony import */ var _learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @learnpress/data-controls */ "@learnpress/data-controls");
/* harmony import */ var _learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
var _marked =
/*#__PURE__*/
regeneratorRuntime.mark(startQuiz);



/**
 * Set user data for app.
 *
 * @param data
 * @return {{type: string, data: *}}
 */

function setQuizData(data) {
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
function submitQuiz(quizId, courseId, userId) {
  return {
    type: 'SUBMIT_QUIZ',
    quizId: quizId,
    courseId: courseId,
    userId: userId
  };
}
function updateUserQuestionAnswers(questionId, answers, quizId) {
  var courseId = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 0;
  var userId = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : 0;
  return {
    type: 'UPDATE_USER_QUESTION_ANSWERS',
    questionId: questionId,
    answers: answers,
    quizId: quizId,
    courseId: courseId,
    userId: userId
  };
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
console.log('Create store'); /// sdf sdfsdf s

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
    isArray = _lodash.isArray;
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
  var questions = state.questions;
  var at = questions.findIndex(function (question) {
    return question.id == action.questionId;
  });

  if (at === -1) {
    return state;
  }

  questions[at] = _objectSpread({}, questions[at], {
    answered: action.answers
  });
  return _objectSpread({}, state, {
    questions: _toConsumableArray(questions)
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

var userQuiz = function userQuiz() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : STORE_DATA;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'SET_QUIZ_DATA':
      return _objectSpread({}, state, {}, action.data);

    case 'START_QUIZ':
    case 'START_QUIZ_SUCCESS':
      return _objectSpread({}, state, {
        status: 'started'
      });

    case 'SET_CURRENT_QUESTION':
      return _objectSpread({}, state, {
        currentQuestion: action.questionId
      });

    case 'SUBMIT_QUIZ':
      return _objectSpread({}, state, {
        status: 'completed',
        attempts: [].concat(_toConsumableArray(state.attempts), [{
          time: new Date().toString(),
          questions: 10,
          marks: [4, 10],
          passingGrade: '80%',
          spendTime: [360, 360],
          result: '10%'
        }])
      });

    case 'UPDATE_USER_QUESTION_ANSWERS':
      return updateUserQuestionAnswer(state, action);

    case 'MARK_QUESTION_RENDERED':
      return markQuestionRendered(state, action);

    case 'SET_QUIZ_MODE':
      return _objectSpread({}, state, {
        mode: action.mode
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
/*! exports provided: getItemStatus, getProp, getQuizAttempts, getQuizAnswered, getQuestions, getData, getDefaultRestArgs */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getItemStatus", function() { return getItemStatus; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getProp", function() { return getProp; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getQuizAttempts", function() { return getQuizAttempts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getQuizAnswered", function() { return getQuizAnswered; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getQuestions", function() { return getQuestions; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getData", function() { return getData; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getDefaultRestArgs", function() { return getDefaultRestArgs; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);

var _lodash = lodash,
    get = _lodash.get;
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
    course_id: userQuiz.course_id
  };
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