this["LP"] = this["LP"] || {}; this["LP"]["questionTypes"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/question-types.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/question-types.js":
/*!**************************************************!*\
  !*** ./assets/src/js/frontend/question-types.js ***!
  \**************************************************/
/*! exports provided: default, QuestionBase, SingleChoice, MultipleChoices, TrueOrFalse, FillInBlanks */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _question_types_index__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./question-types/index */ "./assets/src/js/frontend/question-types/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "QuestionBase", function() { return _question_types_index__WEBPACK_IMPORTED_MODULE_0__["QuestionBase"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "SingleChoice", function() { return _question_types_index__WEBPACK_IMPORTED_MODULE_0__["SingleChoice"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "MultipleChoices", function() { return _question_types_index__WEBPACK_IMPORTED_MODULE_0__["MultipleChoices"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "TrueOrFalse", function() { return _question_types_index__WEBPACK_IMPORTED_MODULE_0__["TrueOrFalse"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "FillInBlanks", function() { return _question_types_index__WEBPACK_IMPORTED_MODULE_0__["FillInBlanks"]; });



/* harmony default export */ __webpack_exports__["default"] = (_question_types_index__WEBPACK_IMPORTED_MODULE_0__["default"]);

/***/ }),

/***/ "./assets/src/js/frontend/question-types/components/index.js":
/*!*******************************************************************!*\
  !*** ./assets/src/js/frontend/question-types/components/index.js ***!
  \*******************************************************************/
/*! exports provided: QuestionBase, SingleChoice, MultipleChoices, TrueOrFalse, FillInBlanks */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _question_base__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./question-base */ "./assets/src/js/frontend/question-types/components/question-base/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "QuestionBase", function() { return _question_base__WEBPACK_IMPORTED_MODULE_0__["default"]; });

/* harmony import */ var _questions_single_choice__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./questions/single-choice */ "./assets/src/js/frontend/question-types/components/questions/single-choice/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "SingleChoice", function() { return _questions_single_choice__WEBPACK_IMPORTED_MODULE_1__["default"]; });

/* harmony import */ var _questions_multiple_choices__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./questions/multiple-choices */ "./assets/src/js/frontend/question-types/components/questions/multiple-choices/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "MultipleChoices", function() { return _questions_multiple_choices__WEBPACK_IMPORTED_MODULE_2__["default"]; });

/* harmony import */ var _questions_true_or_false__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./questions/true-or-false */ "./assets/src/js/frontend/question-types/components/questions/true-or-false/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "TrueOrFalse", function() { return _questions_true_or_false__WEBPACK_IMPORTED_MODULE_3__["default"]; });

/* harmony import */ var _questions_fill_in_blanks__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./questions/fill-in-blanks */ "./assets/src/js/frontend/question-types/components/questions/fill-in-blanks/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "FillInBlanks", function() { return _questions_fill_in_blanks__WEBPACK_IMPORTED_MODULE_4__["default"]; });







/***/ }),

/***/ "./assets/src/js/frontend/question-types/components/question-base/index.js":
/*!*********************************************************************************!*\
  !*** ./assets/src/js/frontend/question-types/components/question-base/index.js ***!
  \*********************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
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
    isArray = _lodash.isArray,
    get = _lodash.get,
    set = _lodash.set;

var QuestionBase =
/*#__PURE__*/
function (_Component) {
  _inherits(QuestionBase, _Component);

  function QuestionBase(props) {
    var _this;

    _classCallCheck(this, QuestionBase);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(QuestionBase).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "maybeShowCorrectAnswer", function () {
      var _this$props = _this.props,
          answered = _this$props.answered,
          status = _this$props.status,
          isCheckedAnswer = _this$props.isCheckedAnswer;
      return answered && status === 'completed' || isCheckedAnswer;
    });

    _defineProperty(_assertThisInitialized(_this), "maybeDisabledOption", function (option) {
      var _this$props2 = _this.props,
          answered = _this$props2.answered,
          status = _this$props2.status,
          isCheckedAnswer = _this$props2.isCheckedAnswer;
      return isCheckedAnswer || status !== 'started';
    });

    _defineProperty(_assertThisInitialized(_this), "setAnswerChecked", function () {
      return function (event) {
        var _this$props3 = _this.props,
            updateUserQuestionAnswers = _this$props3.updateUserQuestionAnswers,
            question = _this$props3.question,
            status = _this$props3.status;

        if (status !== 'started') {
          return 'can not set answers';
        }

        var $options = _this.$wrap.find('.option-check');

        var answered = [];
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

    _defineProperty(_assertThisInitialized(_this), "maybeCheckedAnswer", function (value) {
      var answered = _this.props.answered;

      if (isArray(answered)) {
        return !!answered.find(function (a) {
          return a == value;
        });
      }

      return value == answered;
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

    _defineProperty(_assertThisInitialized(_this), "isDefaultType", function () {
      return _this.props.supportOptions;
    });

    _defineProperty(_assertThisInitialized(_this), "getWarningMessage", function () {
      return React.createElement(React.Fragment, null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Render function should be overwritten from base.', 'learnpress'));
    });

    _defineProperty(_assertThisInitialized(_this), "getOptionClass", function (option) {
      var answered = _this.props.answered;
      var classes = ['answer-option'];
      return classes;
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

    _defineProperty(_assertThisInitialized(_this), "getOptions", function () {
      return _this.state.options || [];
    });

    _defineProperty(_assertThisInitialized(_this), "isCorrect", function () {
      var answered = _this.props.answered;

      if (!answered) {
        ///}  answered === undefined || answered === '') {
        return false;
      }

      var i, option, options;

      for (i = 0, options = _this.getOptions(); i < options.length; i++) {
        option = options[i];

        if (option.isTrue === 'yes') {
          if (answered == option.value) {
            return true;
          }
        }
      }

      return false;
    });

    _defineProperty(_assertThisInitialized(_this), "getChecker", function () {//const checker = LP['questionChecker'][]
    });

    _defineProperty(_assertThisInitialized(_this), "isChecked", function () {
      var question = _this.props.question;
      return Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["select"])('learnpress/quiz').isCheckedAnswer(question.id);
    });

    _defineProperty(_assertThisInitialized(_this), "getCorrectLabel", function () {
      var _this$props4 = _this.props,
          status = _this$props4.status,
          answered = _this$props4.answered,
          question = _this$props4.question;
      var checker = LP['config']['isQuestionCorrect'][question.type] || _this.isCorrect;
      var isCorrect = checker.call(_assertThisInitialized(_this));
      return _this.maybeShowCorrectAnswer() && React.createElement("div", {
        className: "question-response" + (isCorrect ? ' correct' : ' incorrect')
      }, React.createElement("span", {
        className: "label"
      }, isCorrect ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Correct', 'learnpress') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Incorrect', 'learnpress')), React.createElement("span", {
        className: "point"
      }, sprintf(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('%d/%d point', 'learnpress'), isCorrect ? question.point : 0, question.point)));
    });

    var _question = props.question;
    _this.state = {
      optionClass: ['answer-option'],
      options: _question ? _this.parseOptions(_question.options) : []
    };

    if (props.$wrap) {
      _this.$wrap = props.$wrap;
    }

    return _this;
  }

  _createClass(QuestionBase, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.componentWillReceiveProps(this.props);
    }
  }, {
    key: "componentWillReceiveProps",
    value: function componentWillReceiveProps(nextProps) {
      if (nextProps.$wrap) {
        this.$wrap = nextProps.$wrap;
      }

      if (nextProps.question) {
        this.setState({
          options: this.parseOptions(nextProps.question.options)
        });
      }
    }
    /**
     *
     * @return {Component.props.answered|boolean|Component.props.isCheckedAnswer}
     */

  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props5 = this.props,
          question = _this$props5.question,
          status = _this$props5.status;
      return React.createElement("div", {
        className: "question-answers"
      }, this.isDefaultType() && React.createElement("ul", {
        id: "answer-options-".concat(question.id),
        className: "answer-options"
      }, this.getOptions().map(function (option) {
        var ID = "learn-press-answer-option-".concat(option.uid);
        return React.createElement("li", {
          className: _this2.getOptionClass(option).join(' '),
          key: "answer-option-".concat(option.uid)
        }, React.createElement("input", {
          type: _this2.getOptionType(question.type, option),
          className: "option-check",
          name: status === 'started' ? "learn-press-question-".concat(question.id) : '',
          id: ID,
          onChange: _this2.setAnswerChecked(),
          disabled: _this2.maybeDisabledOption(option),
          checked: _this2.maybeCheckedAnswer(option.value),
          value: status === 'started' ? option.value : ''
        }), React.createElement("label", {
          htmlFor: ID,
          className: "option-title",
          dangerouslySetInnerHTML: {
            __html: option.title || option.value
          }
        }));
      })), !this.isDefaultType() && this.getWarningMessage(), this.getCorrectLabel());
    }
  }]);

  return QuestionBase;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (QuestionBase);

/***/ }),

/***/ "./assets/src/js/frontend/question-types/components/questions/fill-in-blanks/index.js":
/*!********************************************************************************************!*\
  !*** ./assets/src/js/frontend/question-types/components/questions/fill-in-blanks/index.js ***!
  \********************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _question_base__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../question-base */ "./assets/src/js/frontend/question-types/components/question-base/index.js");
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }



var QuestionFillInBlanks =
/*#__PURE__*/
function (_QuestionBase) {
  _inherits(QuestionFillInBlanks, _QuestionBase);

  function QuestionFillInBlanks() {
    var _this;

    _classCallCheck(this, QuestionFillInBlanks);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(QuestionFillInBlanks).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "getOptionClass", function (option) {
      var answered = _this.props.answered;
      var optionClass = [].concat(_toConsumableArray(_this.state.optionClass), ["XYZ"]);

      if (!answered && _this.maybeShowCorrectAnswer()) {
        if (option.isTrue === 'yes') {
          optionClass.push('answer-correct');
          answered === option.value && optionClass.push('answered-correct');
        } else {
          answered === option.value && optionClass.push('answered-wrong');
        }
      }

      return optionClass;
    });

    _defineProperty(_assertThisInitialized(_this), "getPassageContent", function () {
      var _this$props$question = _this.props.question,
          options = _this$props$question.options,
          blankFillsStyle = _this$props$question.blankFillsStyle,
          blanksStyle = _this$props$question.blanksStyle;

      if (!options) {
        return '';
      }

      var preview = options.map(function (answer) {
        var blanks = []; //this.getBlanks(answer.text);

        var blank = blanks ? blanks[0] : {};
        var html = '';

        if (blank && blank.words.length) {
          html = '<span class="blank-input"></span>';

          if (blank.words.length > 1) {
            switch (blankFillsStyle) {
              case 'select':
                html += '<select>' + blank.words.map(function (word) {
                  return "<option value=".concat(word, ">").concat(word, "</option>");
                }).join('') + '</select>';
                break;

              case 'enumeration':
                html += '(' + blank.words.map(function (word) {
                  return "<code>".concat(word, "</code>");
                }).join(', ') + ')';
                break;
            }
          }

          if (blank.tip) {
            html += '?';
          }
        }

        return ('' + answer.text).replace(/\{\{(.*)\}\}/, html);
      }).join(blanksStyle === 'paragraphs' ? '</div><div>' : blanksStyle === 'ordered' ? '</li><li>' : ' ');
      return blanksStyle === 'paragraphs' ? "<div>".concat(preview, "</div>") : blanksStyle === 'ordered' ? "<ol><li>".concat(preview, "</li></ol>") : preview;
    });

    _defineProperty(_assertThisInitialized(_this), "setBlankWord", function (blank, word) {
      return function (event) {
        console.log(blank, word, event.target.value);
      };
    });

    _defineProperty(_assertThisInitialized(_this), "getBlankHtml", function (blank) {
      var _this$props$question2 = _this.props.question,
          blankFillsStyle = _this$props$question2.blankFillsStyle,
          options = _this$props$question2.options;

      var BlankOption = function BlankOption(props) {
        var len = props.blank.words.length;
        return props.blank.words.map(function (word, i) {
          switch (blankFillsStyle) {
            case 'select':
              return React.createElement("option", {
                value: word,
                key: word
              }, word);

            case 'enumeration':
              return React.createElement(React.Fragment, null, React.createElement("code", {
                key: word,
                onClick: _this.setBlankWord(blank, word)
              }, word), i === len - 1 ? '' : ',');
          }
        });
      };

      var textMatch = blank.text.split(/\{\{BLANK\}\}/);
      return React.createElement(React.Fragment, null, textMatch ? textMatch[0] : '', React.createElement("div", {
        className: "blank-input-wrap"
      }, React.createElement("div", {
        contentEditable: true,
        className: "blank-input"
      })), blank.tip && React.createElement("span", null, "help"), blankFillsStyle === 'select' && React.createElement("select", {
        className: "blank-select",
        onChange: _this.setBlankWord(blank)
      }, React.createElement(BlankOption, {
        blank: blank
      })), blankFillsStyle === 'enumeration' && React.createElement("div", {
        className: "blank-fills"
      }, "(", React.createElement(BlankOption, {
        blank: blank
      }), ")"), textMatch ? textMatch[1] : '');
    });

    return _this;
  }

  _createClass(QuestionFillInBlanks, [{
    key: "componentDidMount",
    value: function componentDidMount() {// this.setState({
      //     optionClass: [...this.state.optionClass, "new-class"]
      // })
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props$question3 = this.props.question,
          options = _this$props$question3.options,
          blankFillsStyle = _this$props$question3.blankFillsStyle,
          blanksStyle = _this$props$question3.blanksStyle;

      var Wrap = function Wrap(props) {
        var className = props.blanksStyle ? props.blanksStyle : ' one-paragraph';
        return props.blanksStyle === 'ordered' ? React.createElement("ol", {
          className: "blanks ".concat(className)
        }, props.children) : React.createElement("div", {
          className: "blanks ".concat(className)
        }, props.children);
      };

      return React.createElement(React.Fragment, null, React.createElement(Wrap, {
        blanksStyle: blanksStyle
      }, this.getOptions().map(function (option) {
        var blankHtml = _this2.getBlankHtml(option);

        var key = "blank-".concat(option.uid);
        return blanksStyle === 'ordered' ? React.createElement("li", {
          key: key,
          className: "blank-block"
        }, blankHtml) : blanksStyle === 'paragraphs' ? React.createElement("div", {
          key: key,
          className: "blank-block"
        }, blankHtml) : blankHtml;
      })));
    }
  }]);

  return QuestionFillInBlanks;
}(_question_base__WEBPACK_IMPORTED_MODULE_0__["default"]);

/* harmony default export */ __webpack_exports__["default"] = (QuestionFillInBlanks);

/***/ }),

/***/ "./assets/src/js/frontend/question-types/components/questions/multiple-choices/index.js":
/*!**********************************************************************************************!*\
  !*** ./assets/src/js/frontend/question-types/components/questions/multiple-choices/index.js ***!
  \**********************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _question_base__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../question-base */ "./assets/src/js/frontend/question-types/components/question-base/index.js");
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }




var _lodash = lodash,
    isBoolean = _lodash.isBoolean;

var QuestionMultipleChoices =
/*#__PURE__*/
function (_QuestionBase) {
  _inherits(QuestionMultipleChoices, _QuestionBase);

  function QuestionMultipleChoices() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, QuestionMultipleChoices);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(QuestionMultipleChoices)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_this), "isCorrect", function () {
      var answered = _this.props.answered;

      if (isBoolean(answered) || !answered) {
        return false;
      }

      var i, option, options;

      for (i = 0, options = _this.getOptions(); i < options.length; i++) {
        option = options[i];

        if (option.isTrue === 'yes') {
          if (answered.indexOf(option.value) === -1) {
            return false;
          }
        } else {
          if (answered.indexOf(option.value) !== -1) {
            return false;
          }
        }
      }

      return true;
    });

    _defineProperty(_assertThisInitialized(_this), "getOptionClass", function (option) {
      var answered = _this.props.answered;

      var optionClass = _toConsumableArray(_this.state.optionClass);

      if (_this.maybeShowCorrectAnswer()) {
        if (option.isTrue === 'yes') {
          optionClass.push('answer-correct');
        }

        if (answered) {
          if (option.isTrue === 'yes') {
            answered.indexOf(option.value) !== -1 && optionClass.push('answered-correct');
          } else {
            answered.indexOf(option.value) !== -1 && optionClass.push('answered-wrong');
          }
        }
      }

      return optionClass;
    });

    return _this;
  } // render(){
  //     {super.render()}
  // }


  return QuestionMultipleChoices;
}(_question_base__WEBPACK_IMPORTED_MODULE_2__["default"]);

/* harmony default export */ __webpack_exports__["default"] = (QuestionMultipleChoices);

/***/ }),

/***/ "./assets/src/js/frontend/question-types/components/questions/single-choice/index.js":
/*!*******************************************************************************************!*\
  !*** ./assets/src/js/frontend/question-types/components/questions/single-choice/index.js ***!
  \*******************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _question_base__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../question-base */ "./assets/src/js/frontend/question-types/components/question-base/index.js");
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }



var QuestionSingleChoice =
/*#__PURE__*/
function (_QuestionBase) {
  _inherits(QuestionSingleChoice, _QuestionBase);

  function QuestionSingleChoice() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, QuestionSingleChoice);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(QuestionSingleChoice)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_this), "getOptionClass", function (option) {
      var answered = _this.props.answered;

      var optionClass = _toConsumableArray(_this.state.optionClass);

      if (_this.maybeShowCorrectAnswer()) {
        if (option.isTrue === 'yes') {
          optionClass.push('answer-correct');
        }

        if (answered) {
          if (option.isTrue === 'yes') {
            answered === option.value && optionClass.push('answered-correct');
          } else {
            answered === option.value && optionClass.push('answered-wrong');
          }
        }
      }

      return optionClass;
    });

    return _this;
  }

  return QuestionSingleChoice;
}(_question_base__WEBPACK_IMPORTED_MODULE_0__["default"]);

/* harmony default export */ __webpack_exports__["default"] = (QuestionSingleChoice);

/***/ }),

/***/ "./assets/src/js/frontend/question-types/components/questions/true-or-false/index.js":
/*!*******************************************************************************************!*\
  !*** ./assets/src/js/frontend/question-types/components/questions/true-or-false/index.js ***!
  \*******************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _question_base__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../question-base */ "./assets/src/js/frontend/question-types/components/question-base/index.js");
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }



var QuestionTrueOrFalse =
/*#__PURE__*/
function (_QuestionBase) {
  _inherits(QuestionTrueOrFalse, _QuestionBase);

  function QuestionTrueOrFalse() {
    var _this;

    _classCallCheck(this, QuestionTrueOrFalse);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(QuestionTrueOrFalse).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "getOptionClass", function (option) {
      var answered = _this.props.answered;
      var optionClass = [].concat(_toConsumableArray(_this.state.optionClass), ["XYZ"]);

      if (_this.maybeShowCorrectAnswer()) {
        if (option.isTrue === 'yes') {
          optionClass.push('answer-correct');
        }

        if (answered) {
          if (option.isTrue === 'yes') {
            answered === option.value && optionClass.push('answered-correct');
          } else {
            answered === option.value && optionClass.push('answered-wrong');
          }
        } else {}
      }

      return optionClass;
    });

    return _this;
  }

  _createClass(QuestionTrueOrFalse, [{
    key: "componentDidMount",
    value: function componentDidMount() {// this.setState({
      //     optionClass: [...this.state.optionClass, "new-class"]
      // })
    }
  }]);

  return QuestionTrueOrFalse;
}(_question_base__WEBPACK_IMPORTED_MODULE_0__["default"]);

/* harmony default export */ __webpack_exports__["default"] = (QuestionTrueOrFalse);

/***/ }),

/***/ "./assets/src/js/frontend/question-types/index.js":
/*!********************************************************!*\
  !*** ./assets/src/js/frontend/question-types/index.js ***!
  \********************************************************/
/*! exports provided: QuestionBase, SingleChoice, MultipleChoices, TrueOrFalse, FillInBlanks, default */
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
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components */ "./assets/src/js/frontend/question-types/components/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "QuestionBase", function() { return _components__WEBPACK_IMPORTED_MODULE_4__["QuestionBase"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "SingleChoice", function() { return _components__WEBPACK_IMPORTED_MODULE_4__["SingleChoice"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "MultipleChoices", function() { return _components__WEBPACK_IMPORTED_MODULE_4__["MultipleChoices"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "TrueOrFalse", function() { return _components__WEBPACK_IMPORTED_MODULE_4__["TrueOrFalse"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "FillInBlanks", function() { return _components__WEBPACK_IMPORTED_MODULE_4__["FillInBlanks"]; });

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







var QuestionTypes =
/*#__PURE__*/
function (_Component) {
  _inherits(QuestionTypes, _Component);

  function QuestionTypes() {
    var _getPrototypeOf2;

    var _this;

    _classCallCheck(this, QuestionTypes);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _possibleConstructorReturn(this, (_getPrototypeOf2 = _getPrototypeOf(QuestionTypes)).call.apply(_getPrototypeOf2, [this].concat(args)));

    _defineProperty(_assertThisInitialized(_this), "getQuestion", function () {
      var question = _this.props.question;
      var types = LP.Hook.applyFilters('question-types', {
        single_choice: LP.questionTypes.SingleChoice,
        multi_choice: LP.questionTypes.MultipleChoices,
        true_or_false: LP.questionTypes.TrueOrFalse,
        fill_in_blanks: LP.questionTypes.FillInBlanks
      });
      var questionComponent = types[question.type];
      return questionComponent;
    });

    return _this;
  }

  _createClass(QuestionTypes, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          question = _this$props.question,
          supportOptions = _this$props.supportOptions;

      var childProps = _objectSpread({}, this.props);

      childProps.supportOptions = supportOptions.indexOf(question.type) !== -1;

      var TheQuestion = this.getQuestion() || function () {
        return React.createElement("div", {
          className: "question-types",
          dangerouslySetInnerHTML: {
            __html: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Question <code>%s</code> invalid!', 'learnpress'), question.type)
          }
        });
      };

      return React.createElement(React.Fragment, null, React.createElement(TheQuestion, childProps));
    }
  }]);

  return QuestionTypes;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["withSelect"])(function (select, _ref) {
  var id = _ref.question.id;

  var _select = select('learnpress/quiz'),
      getData = _select.getData,
      isCheckedAnswer = _select.isCheckedAnswer;

  return {
    supportOptions: getData('supportOptions'),
    isCheckedAnswer: isCheckedAnswer(id)
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["withDispatch"])(function () {
  return {};
}))(QuestionTypes));

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
//# sourceMappingURL=question-types.js.map