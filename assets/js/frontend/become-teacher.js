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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/become-teacher.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/become-teacher.js":
/*!**************************************************!*\
  !*** ./assets/src/js/frontend/become-teacher.js ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * Become a Teacher form handler
 *
 * @author ThimPress
 * @package LearnPress/JS
 * @version 3.0.0
 */
if (typeof jQuery === 'undefined') {
  console.log('jQuery is not defined');
} else {
  (function ($) {
    $(document).ready(function () {
      $('form[name="become-teacher-form"]').each(function () {
        var $form = $(this),
            $submit = $form.find('button[type="submit"]'),
            hideMessages = function hideMessages() {
          $('.learn-press-error, .learn-press-message').fadeOut('fast', function () {
            $(this).remove();
          });
        },
            showMessages = function showMessages(messages) {
          var m = [];

          if ($.isPlainObject(messages)) {
            for (var i in messages) {
              m.push($(messages[i]));
            }
          } else if ($.isArray(messages)) {
            m = messages.reverse();
          } else {
            m = [messages];
          }

          for (var i = 0; i < m.length; i++) {
            $(m[i]).insertBefore($form);
          }
        },
            blockForm = function blockForm(block) {
          return $form.find('input, select, button, textarea').prop('disabled', !!block);
        },
            beforeSend = function beforeSend() {
          hideMessages();
          blockForm(true).filter($submit).data('origin-text', $submit.text()).html($submit.data('text'));
        },
            ajaxSuccess = function ajaxSuccess(response) {
          response = LP.parseJSON(response);

          if (response.message) {
            showMessages(response.message);
          }

          blockForm().filter($submit).html($submit.data('origin-text'));

          if (response.result === 'success') {
            $form.remove();
          } else {
            $submit.prop('disabled', false);
            $submit.html($submit.data('text'));
          }
        },
            ajaxError = function ajaxError(response) {
          response = LP.parseJSON(response);

          if (response.message) {
            showMessages(response.message);
          }

          blockForm().filter($submit).html($submit.data('origin-text'));
        };

        $form.submit(function () {
          if ($form.triggerHandler('become_teacher_send') !== false) {
            $.ajax({
              url: window.location.href.addQueryVar('lp-ajax', 'request-become-a-teacher'),
              data: $form.serialize(),
              dataType: 'text',
              type: 'post',
              beforeSend: beforeSend,
              success: ajaxSuccess,
              error: ajaxError
            });
          }

          return false;
        });
      });
    });
  })(jQuery);
}

/***/ })

/******/ });
//# sourceMappingURL=become-teacher.js.map