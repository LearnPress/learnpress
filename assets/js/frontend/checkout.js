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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/checkout.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/checkout.js":
/*!********************************************!*\
  !*** ./assets/src/js/frontend/checkout.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function ($, settings) {
  'use strict';

  if (window.LP === undefined) {
    window.LP = {};
  }
  /**
   * Checkout
   *
   * @param options
   */


  var Checkout = LP.Checkout = function (options) {
    var $formCheckout = $('#learn-press-checkout-form'),
        $formLogin = $('#learn-press-checkout-login'),
        $formRegister = $('#learn-press-checkout-register'),
        $payments = $('.payment-methods'),
        $buttonCheckout = $('#learn-press-checkout-place-order'),
        $checkoutEmail = $('input[name="guest_email"]');
    var selectedMethod = '';

    if (String.prototype.isEmail === undefined) {
      String.prototype.isEmail = function () {
        return new RegExp('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$').test(this);
      };
    }

    var needPayment = function needPayment() {
      return $payments.length > 0;
    };

    var selectedPayment = function selectedPayment() {
      return $payments.find('input[name="payment_method"]:checked').val();
    };

    var isLoggedIn = function isLoggedIn() {
      return $formCheckout.find('input[name="checkout-account-switch-form"]:checked').length = 0;
    };

    var getActiveFormData = function getActiveFormData() {
      var formName = $formCheckout.find('input[name="checkout-account-switch-form"]:checked').val();
      var $form = $('#checkout-account-' + formName);
      return $form.serializeJSON();
    };

    var getPaymentData = function getPaymentData() {
      return $('#checkout-payment').serializeJSON();
    };

    var showErrors = function showErrors(errors) {
      showMessage(errors);
      var firstId = Object.keys(errors)[0];
      $('input[name="' + firstId + '"]:visible').focus();
    };

    var _formSubmit = function _formSubmit(e) {
      e.preventDefault();

      if (needPayment() && !selectedPayment()) {
        showMessage('Please select payment method', true);
        return false;
      }

      var formData = {};

      if (!isLoggedIn()) {
        formData = $.extend(formData, getActiveFormData());
      }

      formData = $.extend(formData, getPaymentData());
      removeMessage();
      var btnText = $buttonCheckout.text();
      $.ajax({
        url: options.ajaxurl + '/?lp-ajax=checkout',
        dataType: 'html',
        data: formData,
        type: 'POST',
        beforeSend: function beforeSend() {
          $('#learn-press-checkout-place-order').addClass('loading');
          $buttonCheckout.html(options.i18n_processing);
        },
        success: function success(response) {
          response = LP.parseJSON(response);

          if (response.messages) {
            showErrors(response.messages);
          }

          $('#learn-press-checkout-place-order').removeClass('loading');

          if ('success' === response.result) {
            if (response.redirect.match(/https?/)) {
              $buttonCheckout.html(options.i18n_redirecting);
              window.location = response.redirect;
            }
          } else {
            $buttonCheckout.html(btnText);
          }
        },
        error: function error(jqXHR, textStatus, errorThrown) {
          $('#learn-press-checkout-place-order').removeClass('loading');
          showMessage('<div class="learn-press-message error">' + errorThrown + '</div>');
          $buttonCheckout.html(btnText);
          LP.unblockContent();
        }
      });
      return false;
    };

    var _selectPaymentChange = function _selectPaymentChange() {
      var id = $(this).val(),
          $selected = $payments.children().filter('.selected').removeClass('selected'),
          buttonText = $selected.find('#payment_method_' + selectedMethod).data('order_button_text');
      $selected.find('.payment-method-form').slideUp();
      $selected.end().filter('#learn-press-payment-method-' + id).addClass('selected').find('.payment-method-form').hide().slideDown();
      selectedMethod = $selected.find('payment_method').val();

      if (buttonText) {
        $buttonCheckout.html(buttonText);
      }
    };
    /**
     * Button to switch between mode login/register or place order
     * in case user is not logged in and guest checkout is enabled.
     */


    var _guestCheckoutClick = function _guestCheckoutClick() {
      var showOrHide = $formCheckout.toggle().is(':visible');
      $formLogin.toggle(!showOrHide);
      $formRegister.toggle(!showOrHide);
      $('#learn-press-button-guest-checkout').toggle(!showOrHide);
    };
    /**
     * Append messages into document.
     *
     * @param message
     * @param wrap
     */


    var showMessage = function showMessage(message) {
      var wrap = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      removeMessage();

      if ($.isPlainObject(message)) {
        Object.keys(message).reverse().forEach(function (id) {
          var m = message[id];
          var msg = Array.isArray(m) ? m[0] : m;
          var type = Array.isArray(m) ? m[1] : '';
          msg = '<div class="learn-press-message ' + (typeof type === 'string' ? type : '') + '">' + msg + '</div>';
          $formCheckout.prepend(msg);
        });
        return;
      }

      if (wrap) {
        message = '<div class="learn-press-message ' + (typeof wrap === 'string' ? wrap : '') + '">' + message + '</div>';
      }

      $formCheckout.prepend(message);
      $('html, body').animate({
        scrollTop: $formCheckout.offset().top - 100
      }, 1000);
      $(document).trigger('learn-press/checkout-error');
    };
    /**
     * Callback function for guest email.
     *
     * @private
     */


    var _checkEmail = function _checkEmail() {
      if (!this.value.isEmail()) {
        return;
      }

      this.timer && clearTimeout(this.timer);
      $checkoutEmail.addClass('loading');
      this.timer = setTimeout(function () {
        $.post({
          url: window.location.href,
          data: {
            'lp-ajax': 'checkout-user-email-exists',
            email: $checkoutEmail.val()
          },
          success: function success(response) {
            var res = LP.parseJSON(response);
            $checkoutEmail.removeClass('loading');
            $('.lp-guest-checkout-output').remove();

            if (res && res.output) {
              $checkoutEmail.after(res.output);
            }
          }
        });
      }, 500);
    };
    /**
     * Remove all messages
     */


    var removeMessage = function removeMessage() {
      $('.learn-press-error, .learn-press-notice, .learn-press-message').remove();
    };
    /**
     * Callback function for showing/hiding register form.
     *
     * @param e
     * @param toggle
     */


    var _toggleRegisterForm = function _toggleRegisterForm(e, toggle) {
      toggle = $formRegister.find('.learn-press-form-register').toggle(toggle).is(':visible');
      $formRegister.find('.checkout-form-register-toggle[data-toggle="show"]').toggle(!toggle);
      e && (e.preventDefault(), _toggleLoginForm(null, !toggle));
    };
    /**
     * Callback function for showing/hiding login form.
     *
     * @param e {Event}
     * @param toggle {boolean}
     * @private
     */


    var _toggleLoginForm = function _toggleLoginForm(e, toggle) {
      toggle = $formLogin.find('.learn-press-form-login').toggle(toggle).is(':visible');
      $formLogin.find('.checkout-form-login-toggle[data-toggle="show"]').toggle(!toggle);
      e && (e.preventDefault(), _toggleRegisterForm(null, !toggle));
    };
    /**
     * Place order action
     */


    $buttonCheckout.on('click', function (e) {});
    $('.lp-button-guest-checkout').on('click', _guestCheckoutClick);
    $('#learn-press-button-cancel-guest-checkout').on('click', _guestCheckoutClick);
    $checkoutEmail.on('keyup changex', _checkEmail).trigger('changex');
    $payments.on('change select', 'input[name="payment_method"]', _selectPaymentChange);
    $formCheckout.on('submit', _formSubmit);
    $payments.children('.selected').find('input[name="payment_method"]').trigger('select');
    $formLogin.on('click', '.checkout-form-login-toggle', _toggleLoginForm);
    $formRegister.on('click', '.checkout-form-register-toggle', _toggleRegisterForm);
    $formRegister.find('input').each(function () {
      if (-1 !== $.inArray($(this).attr('type').toLowerCase(), ['text', 'email', 'number']) && $(this).val()) {
        _toggleRegisterForm();

        return false;
      }
    });
    $formLogin.find('input:not([type="hidden"])').each(function () {
      if (-1 !== $.inArray($(this).attr('type').toLowerCase(), ['text', 'email', 'number']) && $(this).val()) {
        _toggleLoginForm();

        return false;
      }
    }); // Show form if there is only one form Register or Login

    if ($formRegister.length && !$formLogin.length) {
      _toggleRegisterForm();
    } else if (!$formRegister.length && $formLogin.length) {
      _toggleLoginForm();
    }

    $formCheckout.on('change', 'input[name="checkout-account-switch-form"]', function () {
      $(this).next().find('input:not([type="hidden"]):visible').first().trigger('focus');
    }).on('change', '#guest_email', function () {
      $formCheckout.find('#reg_email').val(this.value);
    }).on('change', '#reg_email', function () {
      $formCheckout.find('#guest_email').val(this.value);
    });
    setTimeout(function () {
      $formCheckout.find('input:not([type="hidden"]):visible').first().trigger('focus');
    }, 300);
  };

  $(document).ready(function () {
    if (typeof lpCheckoutSettings !== 'undefined') {
      LP.$checkout = new Checkout(lpCheckoutSettings);
    }
  });
})(jQuery);

/***/ })

/******/ });
//# sourceMappingURL=checkout.js.map