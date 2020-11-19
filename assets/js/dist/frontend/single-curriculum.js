this["LP"] = this["LP"] || {}; this["LP"]["singleCurriculum"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/apps/js/frontend/single-curriculum.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/apps/js/frontend/single-curriculum.js":
/*!**********************************************************!*\
  !*** ./assets/src/apps/js/frontend/single-curriculum.js ***!
  \**********************************************************/
/*! exports provided: default, init */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "init", function() { return init; });
/* harmony import */ var _single_curriculum_index__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./single-curriculum/index */ "./assets/src/apps/js/frontend/single-curriculum/index.js");
var $ = jQuery;

/* harmony default export */ __webpack_exports__["default"] = (_single_curriculum_index__WEBPACK_IMPORTED_MODULE_0__["default"]);
var init = function init() {
  wp.element.render( /*#__PURE__*/React.createElement(_single_curriculum_index__WEBPACK_IMPORTED_MODULE_0__["default"], null));
};
$(window).on('load', function () {
  LP.Hook.doAction('course-ready');
});

/***/ }),

/***/ "./assets/src/apps/js/frontend/single-curriculum/components/progress.js":
/*!******************************************************************************!*\
  !*** ./assets/src/apps/js/frontend/single-curriculum/components/progress.js ***!
  \******************************************************************************/
/*! exports provided: progressBar */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "progressBar", function() { return progressBar; });
var $ = jQuery;
var progressBar = function progressBar() {
  $('.learn-press-progress').each(function () {
    var $progress = $(this);
    var $active = $progress.find('.learn-press-progress__active');
    var value = $active.data('value');

    if (value === undefined) {
      return;
    }

    $active.css('left', -(100 - parseInt(value)) + '%');
  });
};

/***/ }),

/***/ "./assets/src/apps/js/frontend/single-curriculum/components/search.js":
/*!****************************************************************************!*\
  !*** ./assets/src/apps/js/frontend/single-curriculum/components/search.js ***!
  \****************************************************************************/
/*! exports provided: searchCourseContent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "searchCourseContent", function() { return searchCourseContent; });
var searchCourseContent = function searchCourseContent() {
  var popup = document.querySelector('#popup-course');
  var list = document.querySelector('#learn-press-course-curriculum');

  if (popup && list) {
    var items = list.querySelector('.curriculum-sections');
    var form = popup.querySelector('.search-course');
    var search = popup.querySelector('.search-course input[type="text"]');

    if (!search || !items || !form) {
      return;
    }

    var sections = items.querySelectorAll('li.section');
    var dataItems = items.querySelectorAll('li.course-item');
    var dataSearch = [];
    dataItems.forEach(function (item) {
      var itemID = item.dataset.id;
      var name = item.querySelector('.item-name');
      dataSearch.push({
        id: itemID,
        name: name ? name.textContent.toLowerCase() : ''
      });
    });

    var submit = function submit(event) {
      event.preventDefault();
      var inputVal = search.value;
      form.classList.add('searching');

      if (!inputVal) {
        form.classList.remove('searching');
      }

      var outputs = [];
      dataSearch.forEach(function (i) {
        if (!inputVal || i.name.match(inputVal.toLowerCase())) {
          outputs.push(i.id);
          dataItems.forEach(function (c) {
            if (outputs.indexOf(c.dataset.id) !== -1) {
              c.classList.remove('hide-if-js');
            } else {
              c.classList.add('hide-if-js');
            }
          });
        }
      });
      sections.forEach(function (section) {
        var listItem = section.querySelectorAll('.course-item');
        var isTrue = [];
        listItem.forEach(function (a) {
          if (outputs.includes(a.dataset.id)) {
            isTrue.push(a.dataset.id);
          }
        });

        if (isTrue.length === 0) {
          section.classList.add('hide-if-js');
        } else {
          section.classList.remove('hide-if-js');
        }
      });
    };

    var clear = form.querySelector('.clear');

    if (clear) {
      clear.addEventListener('click', function (e) {
        e.preventDefault();
        search.value = '';
        submit(e);
      });
    }

    form.addEventListener('submit', submit);
    search.addEventListener('keyup', submit);
  }
};

/***/ }),

/***/ "./assets/src/apps/js/frontend/single-curriculum/components/sidebar.js":
/*!*****************************************************************************!*\
  !*** ./assets/src/apps/js/frontend/single-curriculum/components/sidebar.js ***!
  \*****************************************************************************/
/*! exports provided: Sidebar */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Sidebar", function() { return Sidebar; });
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

var $ = jQuery;
var _lodash = lodash,
    throttle = _lodash.throttle;
var Sidebar = function Sidebar() {
  var $popup = $('#popup-course');
  var $curriculum = $('#learn-press-course-curriculum');
  var timerClearScroll;
  $('#sidebar-toggle').on('change', function (event) {
    LP.Cookies.set('sidebar-toggle', event.target.checked);
    toggleSidebar(event.target.checked);
  });

  var toggleSidebar = function toggleSidebar(toggle) {
    $('body').removeClass('lp-sidebar-toggle__open');
    $('body').removeClass('lp-sidebar-toggle__close');

    if (toggle) {
      $('body').addClass('lp-sidebar-toggle__close');
    } else {
      $('body').addClass('lp-sidebar-toggle__open');
    }
  };

  toggleSidebar(LP.Cookies.get('sidebar-toggle'));
  $curriculum.find('.section-desc').each(function (i, el) {
    var a = $('<span class="show-desc"></span>').on('click', function () {
      b.toggleClass('c');
    });
    var b = $(el).siblings('.section-title').append(a);
  });
  $('.section').each(function () {
    var $section = $(this),
        $toggle = $section.find('.section-toggle');
    $toggle.on('click', function () {
      var isClose = $section.toggleClass('closed').hasClass('closed');
      var sections = LP.Cookies.get('closed-section-' + lpGlobalSettings.post_id) || [];
      var sectionId = parseInt($section.data('section-id'));
      var at = sections.findIndex(function (id) {
        return id == sectionId;
      });

      if (isClose) {
        sections.push(parseInt($section.data('section-id')));
      } else {
        sections.splice(at, 1);
      }

      LP.Cookies.remove('closed-section-(.*)');
      LP.Cookies.set('closed-section-' + lpGlobalSettings.post_id, _toConsumableArray(new Set(sections)));
    });
  }); // Popup only

  if ($popup.length) {
    $curriculum.on('scroll', throttle(function () {
      var $self = $(this);
      $self.addClass('scrolling');
      timerClearScroll && clearTimeout(timerClearScroll);
      timerClearScroll = setTimeout(function () {
        $self.removeClass('scrolling');
      }, 1000);
    }, 500));
    LP.toElement('.course-item.current', {
      container: '.curriculum-scrollable:eq(1)',
      offset: 100,
      duration: 1
    });
  }
};

/***/ }),

/***/ "./assets/src/apps/js/frontend/single-curriculum/index.js":
/*!****************************************************************!*\
  !*** ./assets/src/apps/js/frontend/single-curriculum/index.js ***!
  \****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_search__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/search */ "./assets/src/apps/js/frontend/single-curriculum/components/search.js");
/* harmony import */ var _components_sidebar__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/sidebar */ "./assets/src/apps/js/frontend/single-curriculum/components/sidebar.js");
/* harmony import */ var _components_progress__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/progress */ "./assets/src/apps/js/frontend/single-curriculum/components/progress.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }






var SingleCurriculums = /*#__PURE__*/function (_Component) {
  _inherits(SingleCurriculums, _Component);

  var _super = _createSuper(SingleCurriculums);

  function SingleCurriculums() {
    _classCallCheck(this, SingleCurriculums);

    return _super.apply(this, arguments);
  }

  _createClass(SingleCurriculums, [{
    key: "render",
    value: function render() {
      return /*#__PURE__*/React.createElement(React.Fragment, null);
    }
  }]);

  return SingleCurriculums;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (SingleCurriculums);
window.addEventListener('DOMContentLoaded', function () {
  Object(_components_search__WEBPACK_IMPORTED_MODULE_1__["searchCourseContent"])();
  Object(_components_sidebar__WEBPACK_IMPORTED_MODULE_2__["Sidebar"])();
  Object(_components_progress__WEBPACK_IMPORTED_MODULE_3__["progressBar"])();
});

/***/ }),

/***/ "@wordpress/element":
/*!******************************************!*\
  !*** external {"this":["wp","element"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

/***/ })

/******/ });
//# sourceMappingURL=single-curriculum.js.map