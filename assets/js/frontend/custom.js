this["LP"] = this["LP"] || {}; this["LP"]["custom"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/custom.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/custom.js":
/*!******************************************!*\
  !*** ./assets/src/js/frontend/custom.js ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance"); }

function _iterableToArrayLimit(arr, i) { if (!(Symbol.iterator in Object(arr) || Object.prototype.toString.call(arr) === "[object Arguments]")) { return; } var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

/**
 * Custom functions for frontend quiz.
 */
var _LP = LP,
    Hook = _LP.Hook;
var $ = jQuery;
Hook.addFilter('question-blocks', function (blocks) {
  return blocks; ///[ 'answer-options', 'title', 'content', 'hint', 'explanation'];
});
Hook.addAction('before-start-quiz', function () {});
Hook.addAction('quiz-started', function (results, id) {
  $(".course-item-".concat(id)).removeClass('status-completed failed passed').addClass('has-status status-started');

  window.onbeforeunload = function () {
    return 'Warning!';
  };
});
Hook.addAction('quiz-submitted', function (response, id) {
  $(".course-item-".concat(id)).removeClass('status-started passed failed').addClass("has-status status-completed ".concat(response.results.graduation));
  window.onbeforeunload = null;
});
$(document).ready(function () {
  var CustomComponent = function CustomComponent() {
    var _React$useState = React.useState(0),
        _React$useState2 = _slicedToArray(_React$useState, 2),
        time = _React$useState2[0],
        setTime = _React$useState2[1];

    var _React$useState3 = React.useState(),
        _React$useState4 = _slicedToArray(_React$useState3, 2),
        t = _React$useState4[0],
        setT = _React$useState4[1];

    if (!t) {
      t = setInterval(function () {
        setTime(new Date().toString());
      }, 1000);
      setT(t);
    }

    return React.createElement("div", null, React.createElement(LP.quiz.MyContext.Consumer, null, function (a) {
      return a.status;
    }), time);
  };

  function CustomComponent2() {
    var _React$useState5 = React.useState(0),
        _React$useState6 = _slicedToArray(_React$useState5, 2),
        time = _React$useState6[0],
        setTime = _React$useState6[1];

    var _React$useState7 = React.useState(),
        _React$useState8 = _slicedToArray(_React$useState7, 2),
        t = _React$useState8[0],
        setT = _React$useState8[1];

    if (!t) {
      t = setInterval(function () {
        setTime(time + 1);
        console.log(time);
      }, 1000);
      setT(t);
    }

    return React.createElement("div", null, React.createElement(LP.quiz.MyContext.Consumer, null, function (a) {
      return a.status;
    }), time);
  } // Hook.addAction('xxxx', () => {
  //     return <CustomComponent key="1"/>
  // })
  // Hook.addAction('xxxx', () => {
  //     return <CustomComponent2 key="2"/>
  // })
  // setTimeout(() => {
  //     //wp.element.render(<CustomComponent />, jQuery('#test-element')[0])
  //
  // }, 1000)

});

/***/ })

/******/ });
//# sourceMappingURL=custom.js.map