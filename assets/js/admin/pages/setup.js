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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/admin/pages/setup.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/admin/pages/setup.js":
/*!********************************************!*\
  !*** ./assets/src/js/admin/pages/setup.js ***!
  \********************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_email_validator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/email-validator */ "./assets/src/js/utils/email-validator.js");

;

(function ($) {
  "use strict";

  var $main, $setupForm;

  var checkForm = function checkForm($form) {
    var $emails = $form.find('input[type="email"]'),
        valid = true;
    $emails.each(function () {
      var $this = $(this);
      $this.css('border-color', '');

      switch ($this.attr('name')) {
        case 'settings[paypal][paypal_email]':
        case 'settings[paypal][paypal_sandbox_email]':
          if (!$this.closest('tr').prev().find('input[type="checkbox"]').is(':checked')) {
            return;
          }

          break;
      }

      if (!Object(_utils_email_validator__WEBPACK_IMPORTED_MODULE_0__["default"])(this.value)) {
        valid = false;
        $this.css('border-color', '#FF0000');
      }
    });
    return valid;
  };

  var blockContent = function blockContent(block) {
    $main.toggleClass('loading', block === undefined ? true : block);
  };

  var getFormData = function getFormData(more) {
    var data = $setupForm.serializeJSON();
    return $.extend(data, more || {});
  };

  var replaceMainContent = function replaceMainContent(newContent) {
    var $newContent = $(newContent);
    $main.replaceWith($newContent);
    $main = $newContent;
  };

  var navPages = function navPages(e) {
    e.preventDefault();
    var loadUrl = $(this).attr('href');

    if (!checkForm($setupForm)) {
      return;
    }

    $main.addClass('loading');
    $.post({
      url: loadUrl,
      data: getFormData(),
      success: function success(res) {
        var $html = $(res);
        replaceMainContent($html.contents().filter('#main'));
        LP.setUrl(loadUrl);
        $('.learn-press-dropdown-pages').LP('DropdownPages');
        $('.learn-press-tip').LP('QuickTip');
        $main.removeClass('loading');
      }
    });
  };

  var updateCurrency = function updateCurrency() {
    var m = $(this).children(':selected').html().match(/\((.*)\)/),
        symbol = m ? m[1] : '';
    $('#currency-pos').children().each(function () {
      var $option = $(this),
          text = $option.html();

      switch ($option.val()) {
        case 'left':
          text = text.replace(/\( (.*)69/, '( ' + symbol + '69');
          break;

        case 'right':
          text = text.replace(/9([^0-9]*) \)/, '9' + symbol + ' )');
          break;

        case 'left_with_space':
          text = text.replace(/\( (.*) 6/, '( ' + symbol + ' 6');
          break;

        case 'right_with_space':
          text = text.replace(/9 (.*) \)/, '9 ' + symbol + ' )');
          break;
      }

      $option.html(text);
    });
  };

  var updatePrice = function updatePrice() {
    $.post({
      url: '',
      dataType: 'html',
      data: getFormData({
        'lp-ajax': 'get-price-format'
      }),
      success: function success(res) {
        $('#preview-price').html(res);
      }
    });
  };

  var createPages = function createPages(e) {
    e.preventDefault();
    blockContent();
    $.post({
      url: $(this).attr('href'),
      dataType: 'html',
      data: getFormData({
        'lp-ajax': 'setup-create-pages'
      }),
      success: function success(res) {
        replaceMainContent($(res).contents().filter('#main'));
        $('.learn-press-dropdown-pages').LP('DropdownPages');
        blockContent(false);
      }
    });
  };

  var installSampleCourse = function installSampleCourse(e) {
    e.preventDefault();
    var $button = $(this);
    blockContent();
    $.post({
      url: $(this).attr('href'),
      dataType: 'html',
      data: {},
      success: function success(res) {
        blockContent(false);
        $button.replaceWith($(res).find('a:first').addClass('button button-primary'));
      }
    });
  };

  function onReady() {
    $main = $('#main');
    $setupForm = $('#learn-press-setup-form');
    $(document).on('click', '.buttons .button', navPages).on('change', '#currency', updateCurrency).on('change', 'input, select', updatePrice).on('click', '#create-pages', createPages).on('click', '#install-sample-course', installSampleCourse);
  }

  $(document).ready(onReady);
})(jQuery);

/***/ }),

/***/ "./assets/src/js/utils/email-validator.js":
/*!************************************************!*\
  !*** ./assets/src/js/utils/email-validator.js ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return isEmail; });
/**
 * Validate is an email.
 *
 * @param email
 * @return {boolean}
 */
function isEmail(email) {
  var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email);
}
;

/***/ })

/******/ });
//# sourceMappingURL=setup.js.map