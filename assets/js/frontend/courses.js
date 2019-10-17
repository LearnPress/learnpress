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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/courses.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/courses.js":
/*!*******************************************!*\
  !*** ./assets/src/js/frontend/courses.js ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

;

(function ($) {
  var fetchCourses = function fetchCourses(args) {
    var url = args.url || 'http://localhost/learnpress/dev/courses-2/';
    var $wrapElement = args.wrapElement || '#lp-archive-courses';
    delete args.url;
    delete args.wrapElement;
    LP.setUrl(url);
    return new Promise(function (resolve, reject) {
      $.ajax({
        url: url,
        data: $.extend({}, args || {}),
        type: 'post',
        success: function success(response) {
          var newEl = $(response).contents().find($wrapElement);

          if (newEl.length) {
            $($wrapElement).replaceWith(newEl);
          } else {
            $($wrapElement).html('');
          }

          bindEventCoursesLayout();
          $.scrollTo($wrapElement);
          resolve(newEl);
        },
        error: function error(response) {
          reject();
        }
      });
    });
  };
  /**
   * Ajax searching when user typing on search-box.
   *
   * @param event
   */


  var searchCourseHandler = function searchCourseHandler(event) {
    event.preventDefault();
    fetchCourses({
      s: $(this).find('input[name="s"]').val()
    });
  };
  /**
   * Switch layout between Grid and List.
   *
   * @param event
   */


  var switchCoursesLayoutHandler = function switchCoursesLayoutHandler(event) {
    var $target;
    var $parent = $(this).parent();

    while (!$target || !$target.length) {
      $target = $parent.find('.learn-press-courses');
      $parent = $parent.parent();
    }

    $target.attr('data-layout', this.value);
    LP.Cookies.set('courses-layout', this.value);
  };

  var selectCoursesLayout = function selectCoursesLayout() {
    var coursesLayout = LP.Cookies.get('courses-layout');
    var switches = $('.lp-courses-bar .switch-layout').find('[name="lp-switch-layout-btn"]');

    if (coursesLayout) {
      switches.filter('[value="' + coursesLayout + '"]').prop('checked', true).trigger('change');
    }
  };

  var coursePaginationHandler = function coursePaginationHandler(event) {
    event.preventDefault();
    var permalink = $(event.target).attr('href');

    if (!permalink) {
      return;
    }

    fetchCourses({
      url: permalink
    });
  };

  var bindEventCoursesLayout = function bindEventCoursesLayout() {
    $('#lp-archive-courses').on('submit', '.search-courses', searchCourseHandler).on('change', 'input[name="lp-switch-layout-btn"]', switchCoursesLayoutHandler).on('click', '.learn-press-pagination .page-numbers', coursePaginationHandler);
  };

  $(document).ready(function () {
    bindEventCoursesLayout(); //

    selectCoursesLayout();
  });
})(jQuery);

/***/ })

/******/ });
//# sourceMappingURL=courses.js.map