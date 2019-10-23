this["LP"] = this["LP"] || {}; this["LP"]["config"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/config.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/config.js":
/*!******************************************!*\
  !*** ./assets/src/js/frontend/config.js ***!
  \******************************************/
/*! exports provided: classNames, isQuestionCorrect, questionBlocks, questionFooterButtons, questionTitleParts, questionChecker, quizStartBlocks */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "classNames", function() { return classNames; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isQuestionCorrect", function() { return isQuestionCorrect; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "questionBlocks", function() { return questionBlocks; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "questionFooterButtons", function() { return questionFooterButtons; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "questionTitleParts", function() { return questionTitleParts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "questionChecker", function() { return questionChecker; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "quizStartBlocks", function() { return quizStartBlocks; });
var _LP = LP,
    Hook = _LP.Hook;
var classNames = {
  Quiz: {
    Result: ['quiz-result'],
    Content: ['quiz-content'],
    Questions: ['quiz-questions'],
    Buttons: ['quiz-buttons'],
    Attempts: ['quiz-attempts']
  }
};
var questionCheckers = {
  single_choice: function single_choice() {},
  multi_choice: function multi_choice() {},
  true_or_false: function true_or_false() {}
};
var isQuestionCorrect = {
  'fill_in_blank': function fill_in_blank() {
    return true;
  }
};
/**
 * Question blocks.
 *
 * Allow to sort the blocks of question
 */

var questionBlocks = function questionBlocks() {
  return LP.Hook.applyFilters('question-blocks', ['title', 'content', 'answer-options', 'explanation', 'hint', 'buttons']);
};
var questionFooterButtons = function questionFooterButtons() {
  return LP.Hook.applyFilters('question-footer-buttons', ['instant-check']);
};
var questionTitleParts = function questionTitleParts() {
  return LP.Hook.applyFilters('question-title-parts', ['index', 'title', 'hint', 'edit-permalink']);
};
var questionChecker = function questionChecker(type) {
  var c = LP.Hook.applyFilters('question-checkers', questionCheckers);
  return type && c[type] ? c[type] : function () {
    return {};
  };
};
var quizStartBlocks = function quizStartBlocks() {
  var blocks = Hook.applyFilters('quiz-start-blocks', {
    meta: true,
    description: true,
    custom: "Hello"
  });
};

/***/ })

/******/ });
//# sourceMappingURL=config.js.map