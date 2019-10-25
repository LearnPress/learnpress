this["LP"] = this["LP"] || {}; this["LP"]["enroll"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/enroll.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/enroll.js":
/*!******************************************!*\
  !*** ./assets/src/js/frontend/enroll.js ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

if (typeof window.LP == 'undefined') {
  window.LP = {};
}

;

(function ($) {
  "use strict";

  LP.Enroll = {
    $form: null,
    init: function init() {
      var $doc = $(document);
      this.$form = $('form[name="enroll-course"]');
      this.$form.on('submit', this.enroll);
    },
    enroll: function enroll() {
      var $form = $(this),
          $button = $form.find('.button.enroll-button'),
          course_id = $form.find('input[name="enroll-course"]').val();

      if (!$button.hasClass('enrolled') && $form.triggerHandler('learn_press_enroll_course') !== false) {
        $button.removeClass('enrolled failed').addClass('loading');
        $.ajax({
          url: LP.getUrl(),
          dataType: 'html',
          data: $form.serialize(),
          type: 'post',
          success: function success(response) {
            response = LP.parseJSON(response);

            if (response.result == 'fail') {
              if (LP.Hook.applyFilters('learn_press_user_enroll_course_failed', course_id) !== false) {
                if (response.redirect) {
                  LP.reload(response.redirect);
                }
              }
            } else {
              if (LP.Hook.applyFilters('learn_press_user_enrolled_course', course_id) !== false) {
                if (response.redirect) {
                  LP.reload(response.redirect);
                }
              }
            }
          },
          error: function error(jqXHR, textStatus, errorThrown) {
            LP.Hook.doAction('learn_press_user_enroll_course_failed', course_id);
            $button.removeClass('loading').addClass('failed');
            LP.Enroll.showErrors('<div class="learn-press-error">' + errorThrown + '</div>');
          }
        });
      }

      return false;
    },
    showErrors: function showErrors(messages) {
      this.removeErrors();
      this.$form.prepend(messages);
      $('html, body').animate({
        scrollTop: LP.Enroll.$form.offset().top - 100
      }, 1000);
      $(document.body).trigger('learn_press_enroll_error');
    },
    removeErrors: function removeErrors() {
      $('.learn-press-error, .learn-press-notice, .learn-press-message').remove();
    }
  };
  $(document).ready(function () {
    LP.Enroll.init();
  });
})(jQuery);

/***/ })

/******/ });
//# sourceMappingURL=enroll.js.map