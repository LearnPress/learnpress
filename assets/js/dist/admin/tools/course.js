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
/******/ 	return __webpack_require__(__webpack_require__.s = "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/components/admin/tools/course.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "../../../Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/components/admin/tools/course.js":
/*!***********************************************************************************************************************!*\
  !*** E:/Work/Webs/WP/Clouds/Thimpress/Plugins/github.com/learnpress/assets/src/apps/components/admin/tools/course.js ***!
  \***********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

window.$Vue = window.$Vue || Vue;
jQuery(function ($) {
  if (!$('#learn-press-reset-course-users').length) {
    return;
  }

  new $Vue({
    el: '#learn-press-reset-course-users',
    data: {
      s: '',
      status: false,
      courses: []
    },
    methods: {
      resetActionClass: function resetActionClass(course) {
        return {
          'dashicons-trash': !course.status,
          'dashicons-yes': course.status === 'done',
          'dashicons-update': course.status === 'resetting'
        };
      },
      updateSearch: function updateSearch(e) {
        this.s = e.target.value;
        this.status = false;
        e.preventDefault();
      },
      search: function search(e) {
        e.preventDefault();
        var that = this;
        this.s = $(this.$el).find('input[name="s"]').val();

        if (this.s.length < 3) {
          return;
        }

        this.status = 'searching';
        this.courses = [];
        $.ajax({
          url: '',
          data: {
            'lp-ajax': 'rs-search-courses',
            s: this.s
          },
          success: function success(response) {
            that.courses = LP.parseJSON(response);
            that.status = 'result';
          }
        });
      },
      reset: function reset(e, course) {
        e.preventDefault();

        if (!confirm('Are you sure to reset course progress of all users enrolled this course?')) {
          return;
        }

        var that = this;
        course.status = 'resetting';
        $.ajax({
          url: '',
          data: {
            'lp-ajax': 'rs-reset-course-users',
            id: course.id
          },
          success: function success(response) {
            response = LP.parseJSON(response);

            if (response.id == course.id) {
              for (var i = 0, n = that.courses.length; i < n; i++) {
                if (that.courses[i].id === course.id) {
                  that.courses[i].status = 'done';
                  break;
                }
              }
            }
          }
        });
      }
    }
  });
});

/***/ })

/******/ });
//# sourceMappingURL=course.js.map