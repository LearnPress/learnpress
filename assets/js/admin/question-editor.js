this["LP"] = this["LP"] || {}; this["LP"]["questionEditor"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/admin/question-editor.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/admin/editor/question/index.js":
/*!******************************************************!*\
  !*** ./assets/src/js/admin/editor/question/index.js ***!
  \******************************************************/
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
/* harmony import */ var _store__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./store */ "./assets/src/js/admin/editor/question/store/index.js");
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





var _lodash = lodash,
    debounce = _lodash.debounce;


var stripSlashes = function stripSlashes(str) {
  return (str + '').replace(/\\(.?)/g, function (s, n1) {
    switch (n1) {
      case '\\':
        return '\\';

      case '0':
        return "\0";

      case '':
        return '';

      default:
        return n1;
    }
  });
};

var Editor =
/*#__PURE__*/
function (_Component) {
  _inherits(Editor, _Component);

  function Editor(props) {
    var _this;

    _classCallCheck(this, Editor);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Editor).apply(this, arguments));

    _defineProperty(_assertThisInitialized(_this), "setContent", function (option, text) {
      var optionBlanks = _this.getBlanks(text);

      var _this$props = _this.props,
          blankOptions = _this$props.blankOptions,
          blanks = _this$props.blanks,
          updateOption = _this$props.updateOption,
          setData = _this$props.setData,
          id = _this$props.id;
      var newState = {
        blankOptions: blankOptions.map(function (opt) {
          return opt.question_answer_id == option.question_answer_id ? _objectSpread({}, opt, {
            text: text
          }) : opt;
        }),
        blanks: _objectSpread({}, blanks, _defineProperty({}, option.question_answer_id, optionBlanks ? optionBlanks[0] : []))
      };
      setData(newState, 'question');
      _this.queue = _this.queue || {};
      _this.queue[option.question_answer_id] = [{
        text: text,
        blanks: newState.blanks[option.question_answer_id]
      }, option.question_answer_id, id];

      _this.updateOption(); //updateOption();

    });

    _defineProperty(_assertThisInitialized(_this), "updateOption", debounce(function () {
      var updateOption = _this.props.updateOption;
      var queue = _this.queue ? Object.values(_this.queue) : [];
      queue.map(function (item) {
        if (item) {
          updateOption(item[0], item[1], item[2]);
          delete _this.queue[item[1]];
        }
      });
    }, 1000));

    _defineProperty(_assertThisInitialized(_this), "getBlanks", function (content) {
      // if (undefined === content) {
      //     content = this.state.passage;
      // }
      var blanks = [];
      var shortcodes = content.match(/\{\{([^\{\"\'].*?)\}\}/g);

      if (shortcodes) {
        shortcodes.map(function (shortcode) {
          blanks.push(_this.getBlank(shortcode));
        });
      }

      return blanks;
    });

    _defineProperty(_assertThisInitialized(_this), "getBlank", function (blank) {
      var contents = blank.match(/\{\{(.*)\}\}/);
      var words = contents && contents[1] ? contents[1].split('/') : [];
      var matchTip = words.length ? words[words.length - 1].match(/(.*)(\s(\"(.*)\"|\'(.*)\'))/) : false;
      var corrects = []; // Remove tip from last word. (last-word "This is tip of the blank")

      if (matchTip) {
        words[words.length - 1] = matchTip[1];
      }

      words.map(function (word, i) {
        // Match the word wrapped by [ and ] is CORRECT word
        var matchCorrect = word.match(/\[(.*)\]/);

        if (matchCorrect) {
          // Remove the "[" and "]"
          matchCorrect[1] = stripSlashes(matchCorrect[1]);
          corrects.push(matchCorrect[1]);
          words[i] = matchCorrect[1];
        } else {
          words[i] = stripSlashes(word);
        }
      });
      return {
        words: words.filter(function (w) {
          return w && w.length;
        }),
        tip: matchTip ? stripSlashes(matchTip[4] || matchTip[5] || '') : '',
        corrects: corrects.filter(function (w) {
          return w && w.length;
        })
      };
    });

    _defineProperty(_assertThisInitialized(_this), "onChangeOption", function (answer) {
      return function (event) {
        var instantParseBlanks = _this.props.instantParseBlanks;

        if (instantParseBlanks) {
          _this.setContent(answer, event.target.value);
        }
      };
    });

    _defineProperty(_assertThisInitialized(_this), "addBlank", function (event) {
      var _this$props2 = _this.props,
          addOption = _this$props2.addOption,
          id = _this$props2.id;
      addOption(id, {
        text: 'asdasdasd'
      });
    });

    _defineProperty(_assertThisInitialized(_this), "removeBlank", function (option) {
      return function () {
        var _this$props3 = _this.props,
            id = _this$props3.id,
            removeOption = _this$props3.removeOption;
        removeOption(id, option.question_answer_id);
      };
    });

    _defineProperty(_assertThisInitialized(_this), "getPreview", function () {
      var _this$props4 = _this.props,
          blankOptions = _this$props4.blankOptions,
          blankFillsStyle = _this$props4.blankFillsStyle,
          blanksStyle = _this$props4.blanksStyle;

      if (!blankOptions) {
        return '';
      }

      var preview = blankOptions.map(function (answer) {
        var blanks = _this.getBlanks(answer.text);

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

    _this.state = {
      blanks: []
    };
    return _this;
  }

  _createClass(Editor, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      var _this$props5 = this.props,
          setData = _this$props5.setData,
          initSettings = _this$props5.initSettings;
      var blankOptions = initSettings.blankOptions;
      var newBlanks = {};
      blankOptions.map(function (option) {
        var optionBlanks = _this2.getBlanks(option.text);

        newBlanks[option.question_answer_id] = optionBlanks ? optionBlanks[0] : [];
      });
      setData({
        question: _objectSpread({}, initSettings, {
          blanks: newBlanks
        })
      });
    }
    /**
     * Parse blanks options from content and update state.
     *
     * @param option
     * @param text
     */

  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      var _this$props6 = this.props,
          blanks = _this$props6.blanks,
          blankOptions = _this$props6.blankOptions;
      return React.createElement(React.Fragment, null, React.createElement("div", {
        className: "blank-options"
      }, blankOptions && React.createElement("ul", {
        className: "blanks"
      }, blankOptions.map(function (answer) {
        var blankOptions = blanks[answer.question_answer_id] || {};
        return React.createElement("li", {
          className: "blank",
          key: answer.question_answer_id
        }, React.createElement("textarea", {
          className: "blank-content",
          onChange: _this3.onChangeOption(answer),
          value: answer.text
        }), blankOptions.words && React.createElement("div", {
          className: "blank-words"
        }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Words fill', 'learnpress')), React.createElement("p", null, blankOptions.words && blankOptions.words.map(function (word, i) {
          var className = ['word'];
          (blankOptions.corrects || []).indexOf(word) !== -1 && className.push('correct');
          return React.createElement("code", {
            key: "word-".concat(word, "-").concat(i),
            className: className.join(' ')
          }, word);
        }))), blankOptions.tip && React.createElement("div", {
          className: "blank-tip"
        }, React.createElement("label", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Tip', 'learnpress')), React.createElement("p", null, blankOptions.tip)), React.createElement("button", {
          className: "button button-remove",
          onClick: _this3.removeBlank(answer)
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Remove', 'learnpress')));
      })), React.createElement("button", {
        className: "button",
        onClick: this.addBlank
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Add Blank', 'learnpress'))), React.createElement("div", {
        className: "passage-preview",
        dangerouslySetInnerHTML: {
          __html: this.getPreview()
        }
      }));
    }
  }]);

  return Editor;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])([Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withSelect"])(function (select) {
  var _select = select('learnpress/question'),
      getData = _select.getData;

  return getData('question');
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('learnpress/question'),
      setData = _dispatch.setData,
      addOption = _dispatch.addOption,
      removeOption = _dispatch.removeOption,
      updateOption = _dispatch.updateOption;

  return {
    setData: setData,
    addOption: addOption,
    removeOption: removeOption,
    updateOption: updateOption
  };
})])(Editor));

/***/ }),

/***/ "./assets/src/js/admin/editor/question/store/actions.js":
/*!**************************************************************!*\
  !*** ./assets/src/js/admin/editor/question/store/actions.js ***!
  \**************************************************************/
/*! exports provided: setData, __addOption, addOption, __removeOption, removeOption, __updateOption, updateOption */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setData", function() { return setData; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__addOption", function() { return __addOption; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "addOption", function() { return addOption; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__removeOption", function() { return __removeOption; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "removeOption", function() { return removeOption; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__updateOption", function() { return __updateOption; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateOption", function() { return updateOption; });
/* harmony import */ var _learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @learnpress/data-controls */ "@learnpress/data-controls");
/* harmony import */ var _learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

var _marked =
/*#__PURE__*/
regeneratorRuntime.mark(addOption),
    _marked2 =
/*#__PURE__*/
regeneratorRuntime.mark(removeOption),
    _marked3 =
/*#__PURE__*/
regeneratorRuntime.mark(updateOption);




var getEditorNonce = function getEditorNonce() {
  return lp_question_editor.root.nonce;
};
/**
 * Set user data for app.
 * @param key
 * @param data
 * @return {{type: string, data: *}}
 */


function setData(data, key) {
  // if (typeof key === 'string') {
  //     data = {[key]: data}
  // } else {
  //     data = key;
  // }
  return {
    type: 'SET_DATA',
    data: data,
    key: key
  };
}
function __addOption(id, option) {
  return {
    type: 'ADD_OPTION',
    id: id,
    option: option
  };
}
/**
 * Add new blank and send to rest
 */

function addOption(questionId, option) {
  var results;
  return regeneratorRuntime.wrap(function addOption$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: 'lp/a/v1/question/' + questionId + '/add-option',
            method: 'POST',
            data: {
              nonce: getEditorNonce(),
              type: 'new-answer',
              id: questionId,
              option: option
            }
          });

        case 2:
          results = _context.sent;
          _context.next = 5;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('learnpress/question', '__addOption', questionId, results.result);

        case 5:
        case "end":
          return _context.stop();
      }
    }
  }, _marked);
}
function __removeOption(questionId, optionId) {
  return {
    type: 'REMOVE_OPTION',
    id: questionId,
    optionId: optionId
  };
}
/**
 * Send a rest request to remove answer option.
 *
 * @param questionId
 * @param optionId
 */

function removeOption(questionId, optionId) {
  var results;
  return regeneratorRuntime.wrap(function removeOption$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.next = 2;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: 'lp/a/v1/question/' + questionId + '/remove-option',
            method: 'POST',
            data: {
              nonce: getEditorNonce(),
              type: 'delete-answer',
              id: questionId,
              answer_id: optionId
            }
          });

        case 2:
          results = _context2.sent;
          _context2.next = 5;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('learnpress/question', '__removeOption', questionId, optionId);

        case 5:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2);
}
function __updateOption(option, optionId, questionId) {
  return {
    type: 'UPDATE_OPTION',
    id: questionId,
    optionId: optionId,
    option: option
  };
}
function updateOption(option, optionId, questionId) {
  var results;
  return regeneratorRuntime.wrap(function updateOption$(_context3) {
    while (1) {
      switch (_context3.prev = _context3.next) {
        case 0:
          _context3.next = 2;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: 'lp/a/v1/question/' + questionId + '/update-option',
            method: 'POST',
            data: {
              type: 'update-answer-title',
              answer: _objectSpread({}, option, {
                question_answer_id: optionId
              }),
              id: questionId,
              nonce: getEditorNonce()
            }
          });

        case 2:
          results = _context3.sent;
          _context3.next = 5;
          return Object(_learnpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('learnpress/question', '__updateOption', option);

        case 5:
        case "end":
          return _context3.stop();
      }
    }
  }, _marked3);
}

/***/ }),

/***/ "./assets/src/js/admin/editor/question/store/index.js":
/*!************************************************************!*\
  !*** ./assets/src/js/admin/editor/question/store/index.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _reducer__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./reducer */ "./assets/src/js/admin/editor/question/store/reducer.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./actions */ "./assets/src/js/admin/editor/question/store/actions.js");
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./selectors */ "./assets/src/js/admin/editor/question/store/selectors.js");
/* harmony import */ var _middlewares__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./middlewares */ "./assets/src/js/admin/editor/question/store/middlewares.js");
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }






var dataControls = LP.dataControls.controls;
var store = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["registerStore"])('learnpress/question', {
  reducer: _reducer__WEBPACK_IMPORTED_MODULE_1__["default"],
  selectors: _selectors__WEBPACK_IMPORTED_MODULE_3__,
  actions: _actions__WEBPACK_IMPORTED_MODULE_2__,
  controls: _objectSpread({}, dataControls)
});
Object(_middlewares__WEBPACK_IMPORTED_MODULE_4__["default"])(store);
/* harmony default export */ __webpack_exports__["default"] = (store);

/***/ }),

/***/ "./assets/src/js/admin/editor/question/store/middlewares.js":
/*!******************************************************************!*\
  !*** ./assets/src/js/admin/editor/question/store/middlewares.js ***!
  \******************************************************************/
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

/***/ "./assets/src/js/admin/editor/question/store/reducer.js":
/*!**************************************************************!*\
  !*** ./assets/src/js/admin/editor/question/store/reducer.js ***!
  \**************************************************************/
/*! exports provided: storeData, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "storeData", function() { return storeData; });
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
    remove = _lodash.remove,
    get = _lodash.get,
    set = _lodash.set;
var STORE_DATA = {
  question: false
};
/**
 * Remove an option from question answers.
 *
 * @param {object} state
 * @param {int} optionId
 * @param {int} id
 */

var removeOptionById = function removeOptionById(state, optionId, id) {
  var blankOptions = state.question.blankOptions;
  remove(blankOptions, function (a) {
    return a.question_answer_id == optionId;
  });
  return blankOptions;
};

var setData = function setData(state, data, key) {
  if (!key) {
    return _objectSpread({}, state, {}, data);
  }

  var oldData = get(state, key);
  set(state, key, _objectSpread({}, oldData, {}, data));
  return _objectSpread({}, state);
};

var updateOptionById = function updateOptionById(state, option, optionId, id) {
  var blankOptions = state.question.blankOptions;
  return blankOptions.map(function (opt) {
    return opt.question_answer_id == optionId ? _objectSpread({}, opt, {}, option) : opt;
  });
};

var storeData = function storeData() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : STORE_DATA;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'SET_DATA':
      return setData(state, action.data, action.key);

    case 'ADD_OPTION':
      return _objectSpread({}, state, {
        question: _objectSpread({}, state.question, {
          blankOptions: [].concat(_toConsumableArray(state.question.blankOptions), [action.option])
        })
      });

    case 'REMOVE_OPTION':
      return _objectSpread({}, state, {
        question: _objectSpread({}, state.question, {
          blankOptions: _toConsumableArray(removeOptionById(state, action.optionId, action.id))
        })
      });

    case 'UPDATE_OPTION':
      return _objectSpread({}, state, {
        question: _objectSpread({}, state.question, {
          blankOptions: _toConsumableArray(updateOptionById(state, action.option, action.optionId, action.id))
        })
      });
  }

  return state;
};
/* harmony default export */ __webpack_exports__["default"] = (storeData); //combineReducers({userQuiz});

/***/ }),

/***/ "./assets/src/js/admin/editor/question/store/selectors.js":
/*!****************************************************************!*\
  !*** ./assets/src/js/admin/editor/question/store/selectors.js ***!
  \****************************************************************/
/*! exports provided: getData, getDefaultRestArgs */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getData", function() { return getData; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getDefaultRestArgs", function() { return getDefaultRestArgs; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);

var _lodash = lodash,
    get = _lodash.get,
    set = _lodash.set,
    isArray = _lodash.isArray;
/**
 * Get property of store data.
 *
 * @param state - Store data
 * @param prop - Optional. NULL will return all data.
 * @return {*}
 */

function getData(state, prop) {
  if (prop) {
    if (isArray(prop)) {
      var ret = {};

      for (var i = 0; i < prop.length; i++) {
        set(ret, prop, get(state, prop));
      }

      return ret;
    }

    return get(state, prop);
  }

  return state;
}
function getDefaultRestArgs(state) {
  var userQuiz = state.userQuiz;
  return {
    item_id: userQuiz.id,
    course_id: userQuiz.courseId
  };
}

/***/ }),

/***/ "./assets/src/js/admin/question-editor.js":
/*!************************************************!*\
  !*** ./assets/src/js/admin/question-editor.js ***!
  \************************************************/
/*! exports provided: default, init */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "init", function() { return init; });
/* harmony import */ var _editor_question_index__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./editor/question/index */ "./assets/src/js/admin/editor/question/index.js");
/* harmony import */ var _editor_question_store__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./editor/question/store */ "./assets/src/js/admin/editor/question/store/index.js");


/* harmony default export */ __webpack_exports__["default"] = (_editor_question_index__WEBPACK_IMPORTED_MODULE_0__["default"]);
var init = function init(elem, settings) {
  wp.element.render(React.createElement(_editor_question_index__WEBPACK_IMPORTED_MODULE_0__["default"], {
    initSettings: settings
  }), jQuery(elem)[0]);
  var $ = jQuery;
  $(document).on('change', '#_lp_blanks_style, #_lp_blank_fills_style', function () {
    wp.data.dispatch('learnpress/question').setData({
      blanksStyle: $('#_lp_blanks_style').val(),
      blankFillsStyle: $('#_lp_blank_fills_style').val()
    }, 'question');
  });
};

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
//# sourceMappingURL=question-editor.js.map