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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/admin/partial/meta-box-order.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/admin/partial/meta-box-order.js":
/*!*******************************************************!*\
  !*** ./assets/src/js/admin/partial/meta-box-order.js ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

;

(function ($) {
  "use strict";

  window.$Vue = window.$Vue || Vue;
  $(document).ready(function () {
    var $listItems = $('.list-order-items').find('tbody'),
        $listUsers = $('#list-users'),
        template = function template(templateHTML, data) {
      return _.template(templateHTML, {
        evaluate: /<#([\s\S]+?)#>/g,
        interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
        escape: /\{\{([^\}]+?)\}\}(?!\})/g
      })(data);
    },
        advancedListOptions = {
      template: '#tmpl-order-advanced-list-item',
      onRemove: function onRemove() {
        if (this.$el.children().length === 0) {
          this.$el.append('<li class="user-guest">' + orderOptions.i18n_guest + '</li>');
        }

        console.log(this.$el);
      },
      onAdd: function onAdd() {
        this.$el.find('.user-guest').remove();
      }
    },
        orderOptions = lpMetaBoxOrderSettings;

    function getAddedUsers() {
      return $('#list-users').children().map(function () {
        return $(this).data('id');
      }).get();
    }

    function getAddedItems() {
      return $('.list-order-items tbody').children('.order-item-row').map(function () {
        return $(this).data('id');
      }).get();
    }

    if ($listUsers.length) {
      $listUsers.LP('AdvancedList', advancedListOptions);

      if (orderOptions.users) {
        _.forEach(orderOptions.users, function (userData, userId) {
          $listUsers.LP('AdvancedList', 'add', [template(orderOptions.userTextFormat, userData), userId]);
        });
      }
    }

    $listItems.on('click', '.remove-order-item', function (e) {
      e.preventDefault();
      var $item = $(this).closest('tr'),
          item_id = $item.data('item_id');
      $item.remove();

      if ($listItems.children(':not(.no-order-items)').length === 0) {
        $listItems.find('.no-order-items').show();
      }

      $Vue.http.post(window.location.href, {
        order_id: $('#post_ID').val(),
        items: [item_id],
        'lp-ajax': 'remove_items_from_order'
      }, {
        emulateJSON: true,
        params: {}
      }).then(function (response) {
        var result = LP.parseJSON(response.body || response.bodyText);
        $('.order-subtotal').html(result.order_data.subtotal_html);
        $('.order-total').html(result.order_data.total_html);
      });
    });
    $('.order-date.date-picker-backendorder').on('change', function () {
      var m = this.value.split('-');
      ['aa', 'mm', 'jj'].forEach(function (v, k) {
        $('input[name="' + v + '"]').val(m[k]);
      });
    }).datepicker({
      dateFormat: 'yy-mm-dd',
      numberOfMonths: 1,
      showButtonPanel: true,
      select: function select() {
        console.log(arguments);
      }
    });
    $('#learn-press-add-order-item').on('click', function () {
      LP.$modalSearchItems.open({
        data: {
          postType: 'lp_course',
          context: 'order-items',
          contextId: $('#post_ID').val(),
          exclude: getAddedItems(),
          show: true
        },
        callbacks: {
          addItems: function addItems() {
            var that = this;
            $Vue.http.post(window.location.href, {
              order_id: this.contextId,
              items: this.selected,
              'lp-ajax': 'add_items_to_order'
            }, {
              emulateJSON: true,
              params: {}
            }).then(function (response) {
              var result = LP.parseJSON(response.body || response.bodyText),
                  $noItem = $listItems.find('.no-order-items').hide();
              $(result.item_html).insertBefore($noItem);
              $('.order-subtotal').html(result.order_data.subtotal_html);
              $('.order-total').html(result.order_data.total_html);
            });
            this.close();
          }
        }
      });
    });
    $(document).on('click', '.change-user', function (e) {
      e.preventDefault();
      LP.$modalSearchUsers.open({
        data: {
          context: 'order-items',
          contextId: $('#post_ID').val(),
          show: true,
          multiple: $(this).data('multiple') === 'yes',
          exclude: getAddedUsers(),
          textFormat: orderOptions.userTextFormat
        },
        callbacks: {
          addUsers: function addUsers(data) {
            if (this.multiple) {
              if (!$listUsers.length) {
                $listUsers = $(LP.template('tmpl-order-data-user')({
                  multiple: true
                }));
                $listUsers.LP('AdvancedList', advancedListOptions);
                $('.order-data-user').replaceWith($listUsers);
              }

              for (var i = 0; i < this.selected.length; i++) {
                $listUsers.LP('AdvancedList', 'add', [template(this.textFormat, this.selected[i]), this.selected[i].id]);
              }
            } else {
              var $html = LP.template('tmpl-order-data-user')({
                name: template(this.textFormat, this.selected[0]),
                id: this.selected[0].id
              });
              $('.order-data-user').replaceWith($html);
            }

            this.close();
          }
        }
      });
    });
  });
})(jQuery);

/***/ })

/******/ });
//# sourceMappingURL=meta-box-order.js.map