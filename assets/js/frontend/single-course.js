this["LP"] = this["LP"] || {}; this["LP"]["singleCourse"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/single-course.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/data-controls.js":
/*!*************************************************!*\
  !*** ./assets/src/js/frontend/data-controls.js ***!
  \*************************************************/
/*! exports provided: apiFetch, select, dispatch, controls */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "apiFetch", function() { return apiFetch; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "select", function() { return select; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "dispatch", function() { return dispatch; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "controls", function() { return controls; });
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__);
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

 //import { createRegistryControl } from '@wordpress/data';

var createRegistryControl = function createRegistryControl(registryControl) {
  registryControl.isRegistryControl = true;
  return registryControl;
};

var apiFetch = function apiFetch(request) {
  return {
    type: 'API_FETCH',
    request: request
  };
};
function select(storeKey, selectorName) {
  for (var _len = arguments.length, args = new Array(_len > 2 ? _len - 2 : 0), _key = 2; _key < _len; _key++) {
    args[_key - 2] = arguments[_key];
  }

  return {
    type: 'SELECT',
    storeKey: storeKey,
    selectorName: selectorName,
    args: args
  };
}
function dispatch(storeKey, actionName) {
  for (var _len2 = arguments.length, args = new Array(_len2 > 2 ? _len2 - 2 : 0), _key2 = 2; _key2 < _len2; _key2++) {
    args[_key2 - 2] = arguments[_key2];
  }

  return {
    type: 'DISPATCH',
    storeKey: storeKey,
    actionName: actionName,
    args: args
  };
}

var resolveSelect = function resolveSelect(registry, _ref) {
  var storeKey = _ref.storeKey,
      selectorName = _ref.selectorName,
      args = _ref.args;
  return new Promise(function (resolve) {
    var hasFinished = function hasFinished() {
      return registry.select('').hasFinishedResolution(storeKey, selectorName, args);
    };

    var getResult = function getResult() {
      return registry.select(storeKey)[selectorName].apply(null, args);
    }; // trigger the selector (to trigger the resolver)


    var result = getResult();

    if (hasFinished()) {
      return resolve(result);
    }

    var unsubscribe = registry.subscribe(function () {
      if (hasFinished()) {
        unsubscribe();
        resolve(getResult());
      }
    });
  });
};

var controls = {
  API_FETCH: function API_FETCH(_ref2) {
    var request = _ref2.request;
    return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()(request);
  },
  SELECT: createRegistryControl(function (registry) {
    return function (_ref3) {
      var _registry$select;

      var storeKey = _ref3.storeKey,
          selectorName = _ref3.selectorName,
          args = _ref3.args;
      return registry.select(storeKey)[selectorName].hasResolver ? resolveSelect(registry, {
        storeKey: storeKey,
        selectorName: selectorName,
        args: args
      }) : (_registry$select = registry.select(storeKey))[selectorName].apply(_registry$select, _toConsumableArray(args));
    };
  }),
  DISPATCH: createRegistryControl(function (registry) {
    return function (_ref4) {
      var _registry$dispatch;

      var storeKey = _ref4.storeKey,
          actionName = _ref4.actionName,
          args = _ref4.args;
      return (_registry$dispatch = registry.dispatch(storeKey))[actionName].apply(_registry$dispatch, _toConsumableArray(args));
    };
  })
};

/***/ }),

/***/ "./assets/src/js/frontend/single-course.js":
/*!*************************************************!*\
  !*** ./assets/src/js/frontend/single-course.js ***!
  \*************************************************/
/*! exports provided: formatDuration, toggleSidebarHandler, initCourseTabs, initCourseSidebar */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "formatDuration", function() { return formatDuration; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "toggleSidebarHandler", function() { return toggleSidebarHandler; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "initCourseTabs", function() { return initCourseTabs; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "initCourseSidebar", function() { return initCourseSidebar; });
/* harmony import */ var _single_course_index__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./single-course/index */ "./assets/src/js/frontend/single-course/index.js");
/* harmony import */ var _data_controls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./data-controls */ "./assets/src/js/frontend/data-controls.js");
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }



var $ = jQuery;
var _lodash = lodash,
    debounce = _lodash.debounce;
var _x = wp.i18n._x;
function formatDuration(seconds) {
  var html;
  var x, d;
  var day_in_seconds = 3600 * 24;

  if (seconds > day_in_seconds) {
    d = (seconds - seconds % day_in_seconds) / day_in_seconds;
    seconds = seconds % day_in_seconds;
  } else if (seconds == day_in_seconds) {
    return '24:00';
  }

  x = new Date(seconds * 1000).toUTCString().match(/\d{2}:\d{2}:\d{2}/)[0].split(':');

  if (x[2] === '00') {
    x.splice(2, 1);
  }

  if (x[0] === '00') {
    x[0] = 0;
  }

  if (d) {
    x[0] = parseInt(x[0]) + d * 24;
  }

  html = x.join(':');
  return html;
}

var toggleSidebarHandler = function toggleSidebarHandler(event) {
  LP.Cookies.set('sidebar-toggle', event.target.checked);
};



var createCustomScrollbar = function createCustomScrollbar(element) {
  [].map.call(arguments, function (element) {
    $(element).each(function () {
      $(this).addClass('scrollbar-light').css({
        opacity: 1
      }).scrollbar({
        scrollx: false
      }).parent().css({
        position: 'absolute',
        top: 0,
        bottom: 0,
        width: '100%',
        opacity: 1
      });
    });
  });
};

var AjaxSearchCourses = function AjaxSearchCourses(el) {
  var $form = $(el);
  var $ul = $('<ul class="search-results"></ul>').appendTo($form);
  var $input = $form.find('input[name="s"]');
  var paged = 1;

  var submit =
  /*#__PURE__*/
  function () {
    var _ref = _asyncToGenerator(
    /*#__PURE__*/
    regeneratorRuntime.mark(function _callee(e) {
      var response, _response$results, courses, num_pages, page;

      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              e.preventDefault();
              _context.next = 3;
              return wp.apiFetch({
                path: 'lp/v1/courses/search?s=' + $input.val() + '&page=' + paged
              });

            case 3:
              response = _context.sent;
              _response$results = response.results, courses = _response$results.courses, num_pages = _response$results.num_pages, page = _response$results.page;
              $ul.html('');

              if (courses.length) {
                courses.map(function (course) {
                  $ul.append("<li class=\"search-results__item\">\n                    <a href=\"".concat(course.url, "\">\n                    ") + (course.thumbnail.small ? "<img src=\"".concat(course.thumbnail.small, "\" />") : '') + "\n                        <h4 class=\"search-results__item-title\">".concat(course.title, "</h4>\n                        <span class=\"search-results__item-author\">").concat(course.author, "</span>\n                        ").concat(course.price_html, "\n                        </a>\n                    </li>"));
                });

                if (num_pages > 1) {
                  $ul.append("<li class=\"search-results__pagination\">\n                  " + _toConsumableArray(Array(num_pages).keys()).map(function (i) {
                    return i === paged - 1 ? '<span>' + (i + 1) + '</span>' : '<a data-page="' + (i + 1) + '">' + (i + 1) + '</a>';
                  }).join('') + "\n                </li>");
                }
              } else {
                $ul.append('<li class="search-results__not-found">' + _x('No course found!', 'ajax search course not found', 'learnpress') + '</li>');
              }

              $form.addClass('searching');
              return _context.abrupt("return", false);

            case 9:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));

    return function submit(_x2) {
      return _ref.apply(this, arguments);
    };
  }();

  $input.on('keyup', debounce(function (e) {
    paged = 1;

    if (e.target.value.length < 3) {
      return;
    }

    submit(e);
  }, 300));
  $form.on('click', '.clear', function () {
    $form.removeClass('searching');
    $input.val('');
  }).on('click', '.search-results__pagination a', function (e) {
    e.preventDefault();
    paged = $(e.target).data('page');
    submit(e);
  });
};

var AjaxSearchCourseContent = function AjaxSearchCourseContent(el) {
  var $form = $(el);
  var $list = $('#learn-press-course-curriculum');
  var $input = $form.find('input[name="s"]');
  var paged = 1;
  var $sections = $list.find('.section');
  var $items = $list.find('.course-item');
  var isSearching = false;
  var oldSearch = '';

  var submit =
  /*#__PURE__*/
  function () {
    var _ref2 = _asyncToGenerator(
    /*#__PURE__*/
    regeneratorRuntime.mark(function _callee2(e) {
      var response;
      return regeneratorRuntime.wrap(function _callee2$(_context2) {
        while (1) {
          switch (_context2.prev = _context2.next) {
            case 0:
              e.preventDefault();

              if (!isSearching) {
                _context2.next = 3;
                break;
              }

              return _context2.abrupt("return", false);

            case 3:
              if (!($input.val().length < 3)) {
                _context2.next = 7;
                break;
              }

              $items.removeClass('hide-if-js');
              $sections.removeClass('hide-if-js');
              return _context2.abrupt("return");

            case 7:
              isSearching = true;
              oldSearch = $input.val();
              $form.addClass('searching');
              _context2.next = 12;
              return wp.apiFetch({
                path: 'lp/v1/courses/' + lpGlobalSettings.post_id + '/search-content?s=' + $input.val()
              });

            case 12:
              response = _context2.sent;
              $items.each(function () {
                var $it = $(this);

                if (response.items.indexOf($it.data('id')) !== -1) {
                  $it.removeClass('hide-if-js');
                } else {
                  $it.addClass('hide-if-js');
                }
              });
              $sections.each(function () {
                var $section = $(this);

                if ($section.find('.course-item:not(.hide-if-js)').length === 0) {
                  $section.addClass('hide-if-js');
                } else {
                  $section.removeClass('hide-if-js');
                }
              });
              isSearching = false;

              if (!(oldSearch !== $input.val())) {
                _context2.next = 18;
                break;
              }

              return _context2.abrupt("return", submit(e));

            case 18:
              return _context2.abrupt("return", false);

            case 19:
            case "end":
              return _context2.stop();
          }
        }
      }, _callee2);
    }));

    return function submit(_x3) {
      return _ref2.apply(this, arguments);
    };
  }();

  $input.on('keyup', debounce(function (e) {
    paged = 1;
    submit(e);
  }, 300));
  $form.on('submit', submit);
  $form.on('click', '.clear', function (e) {
    $form.removeClass('searching');
    $input.val('');
    submit(e);
  }).on('click', '.search-results__pagination a', function (e) {
    e.preventDefault();
    paged = $(e.target).data('page');
    submit(e);
  });
};

var initCourseTabs = function initCourseTabs() {
  $('#learn-press-course-tabs').on('change', 'input[name="learn-press-course-tab-radio"]', function () {
    var selectedTab = $('input[name="learn-press-course-tab-radio"]:checked').val();
    LP.Cookies.set('course-tab', selectedTab);
    $('label[for="' + $(event.target).attr('id') + '"]').closest('li').addClass('active').siblings().removeClass('active');
  });
};

var initCourseSidebar = function initCourseSidebar() {
  var $sidebar = $('.course-summary-sidebar');

  if (!$sidebar.length) {
    return;
  }

  var $window = $(window);
  var $scrollable = $sidebar.children();
  var offset = $sidebar.offset();
  var scrollTop = 0;
  var maxHeight = $sidebar.height();
  var scrollHeight = $scrollable.height();
  var options = {
    offsetTop: 32
  };

  var onScroll = function onScroll() {
    scrollTop = $window.scrollTop();
    var top = scrollTop - offset.top + options.offsetTop;

    if (top < 0) {
      $sidebar.removeClass('slide-top slide-down');
      $scrollable.css('top', '');
      return;
    }

    if (top > maxHeight - scrollHeight) {
      $sidebar.removeClass('slide-down').addClass('slide-top');
      $scrollable.css('top', maxHeight - scrollHeight);
    } else {
      $sidebar.removeClass('slide-top').addClass('slide-down');
      $scrollable.css('top', options.offsetTop);
    }
  };

  $window.on('scroll.fixed-course-sidebar', onScroll).trigger('scroll.fixed-course-sidebar');
};

var initItemComments = function initItemComments() {
  var $toggle = $('#learn-press-item-comments-toggle');
  $toggle.on('change',
  /*#__PURE__*/
  _asyncToGenerator(
  /*#__PURE__*/
  regeneratorRuntime.mark(function _callee3() {
    var response;
    return regeneratorRuntime.wrap(function _callee3$(_context3) {
      while (1) {
        switch (_context3.prev = _context3.next) {
          case 0:
            console.log(this.checked);

            if ($toggle[0].checked) {
              _context3.next = 3;
              break;
            }

            return _context3.abrupt("return");

          case 3:
            _context3.next = 5;
            return wp.apiFetch({
              path: 'lp/v1/courses/14242/item-comments/14266'
            });

          case 5:
            response = _context3.sent;
            $('.learn-press-comments').html(response.comments);
            new LP.IframeSubmit('#commentform');

          case 8:
          case "end":
            return _context3.stop();
        }
      }
    }, _callee3, this);
  })));
};


$(window).on('load', function () {
  var $popup = $('#popup-course');
  var timerClearScroll;
  var $curriculum = $('#learn-press-course-curriculum'); // Popup only

  if ($popup.length) {
    $curriculum.scroll(lodash.throttle(function () {
      var $self = $(this);
      $self.addClass('scrolling');
      timerClearScroll && clearTimeout(timerClearScroll);
      timerClearScroll = setTimeout(function () {
        $self.removeClass('scrolling');
      }, 1000);
    }, 500));
    $('#sidebar-toggle').on('change', toggleSidebarHandler);
    new AjaxSearchCourseContent($popup.find('.search-course'));
    createCustomScrollbar($curriculum.find('.curriculum-scrollable'), $('#popup-content').find('.content-item-scrollable'));
    LP.toElement('.course-item.current', {
      container: '.curriculum-scrollable:eq(1)',
      offset: 100,
      duration: 1
    });
  }

  $curriculum.find('.section-desc').each(function (i, el) {
    var a = $('<span class="show-desc"></span>').on('click', function () {
      b.toggleClass('c');
    });
    var b = $(el).siblings('.section-title').append(a);
  });
  initCourseTabs();
  initCourseSidebar();
  initItemComments();
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
      LP.Cookies.set('closed-section-' + lpGlobalSettings.post_id, _toConsumableArray(new Set(sections))); //$section.find('.section-content').slideToggle();
    });
  });
  $('.learn-press-progress').each(function () {
    var $progress = $(this);
    var $active = $progress.find('.learn-press-progress__active');
    var value = $active.data('value');

    if (value === undefined) {
      return;
    }

    $active.css('left', -(100 - parseInt(value)) + '%');
  });
  LP.Hook.doAction('course-ready');
  $(window).on('resize.popup-nav', debounce(function () {
    var marginLeft = $('#popup-sidebar').width() / 2;
    var width = $('#learn-press-quiz-app').width();
    $('.quiz-buttons .button-left.fixed').css({
      width: width,
      marginLeft: marginLeft
    });
  }, 300)).trigger('resize.popup-nav'); // if (window.location.hash) {
  //     $('.content-item-scrollable:last').scrollTo($(window.location.hash));
  // }
});

/***/ }),

/***/ "./assets/src/js/frontend/single-course/index.js":
/*!*******************************************************!*\
  !*** ./assets/src/js/frontend/single-course/index.js ***!
  \*******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _learnpress_quiz__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @learnpress/quiz */ "@learnpress/quiz");
/* harmony import */ var _learnpress_quiz__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_learnpress_quiz__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _store__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./store */ "./assets/src/js/frontend/single-course/store/index.js");
/* harmony import */ var _store__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_store__WEBPACK_IMPORTED_MODULE_2__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }





var SingleCourse =
/*#__PURE__*/
function (_Component) {
  _inherits(SingleCourse, _Component);

  function SingleCourse() {
    _classCallCheck(this, SingleCourse);

    return _possibleConstructorReturn(this, _getPrototypeOf(SingleCourse).apply(this, arguments));
  }

  _createClass(SingleCourse, [{
    key: "render",
    value: function render() {
      return React.createElement(React.Fragment, null, "this is course", React.createElement(_learnpress_quiz__WEBPACK_IMPORTED_MODULE_1___default.a, null));
    }
  }]);

  return SingleCourse;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (SingleCourse);

/***/ }),

/***/ "./assets/src/js/frontend/single-course/store/index.js":
/*!*************************************************************!*\
  !*** ./assets/src/js/frontend/single-course/store/index.js ***!
  \*************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * Created by tu on 9/19/19.
 */

/***/ }),

/***/ "@learnpress/quiz":
/*!***************************************!*\
  !*** external {"this":["LP","quiz"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["LP"]["quiz"]; }());

/***/ }),

/***/ "@wordpress/api-fetch":
/*!*******************************************!*\
  !*** external {"this":["wp","apiFetch"]} ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["apiFetch"]; }());

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
//# sourceMappingURL=single-course.js.map