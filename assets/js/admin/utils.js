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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/admin/utils/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/admin/utils/admin-notice.js":
/*!***************************************************!*\
  !*** ./assets/src/js/admin/utils/admin-notice.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/extend */ "./assets/src/js/utils/extend.js");

var $ = window.jQuery;

var dismissNotice = function dismissNotice(notice, options) {
  var hooks = wp.hooks;
  options = $.extend({
    el: null
  }, options || {});
  hooks && hooks.doAction('before-dismiss-notice', 'LP', notice, options);
  $.ajax({
    url: '',
    data: $.extend({
      'lp-dismiss-notice': notice
    }, options.data || {}),
    dataType: 'text',
    success: function success(response) {
      response = LP.parseJSON(response);

      if (response.dismissed === notice) {
        $(options.el).fadeOut();
      }

      hooks && hooks.doAction('dismissed-notice', 'LP', notice, options);
    }
  });
};

(function ($) {
  if (typeof $ === 'undefined') {
    return;
  }

  $(document).on('click', '.lp-notice [data-dismiss-notice]', function () {
    var data = $(this).data();
    var notice = data.dismissNotice;
    delete data.dismissNotice;
    dismissNotice(notice, {
      data: data,
      el: $(this).closest('.lp-notice')
    });
  });
})(window.jQuery);

Object(_utils_extend__WEBPACK_IMPORTED_MODULE_0__["default"])('Utils', {
  dismissNotice: dismissNotice
});
/* harmony default export */ __webpack_exports__["default"] = (dismissNotice);

/***/ }),

/***/ "./assets/src/js/admin/utils/admin-tabs.js":
/*!*************************************************!*\
  !*** ./assets/src/js/admin/utils/admin-tabs.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * Plugin: Tabs
 */
;

(function ($) {
  var adminTabs = function adminTabs($el, options) {
    var $tabs = $el.find('.tabs-nav').find('li'),
        $tabsWrap = $tabs.parent(),
        $contents = $el.find('.tabs-content-container > li'),
        $currentTab = null,
        $currentContent = null;

    function selectTab($tab) {
      var index = $tabs.index($tab),
          url = $tab.find('a').attr('href');
      $currentContent = $contents.eq(index);
      $tab.addClass('active').siblings('li.active').removeClass('active');
      $currentContent.show().css({
        visibility: 'hidden'
      });
      calculateHeight($currentContent);
      $currentContent.hide();
      $currentContent.show();
      $currentContent.siblings('li.active').fadeOut(0, function () {
        $currentContent.addClass('active').siblings('li.active').removeClass('active');
      });
      LP.setUrl(url);
    }

    function calculateHeight($currentContent) {
      var contentHeight = $currentContent.height(),
          tabsHeight = $tabsWrap.outerHeight();

      if (contentHeight < tabsHeight) {
        contentHeight = tabsHeight + parseInt($tabsWrap.css('margin')) * 2;
      } else {
        contentHeight = undefined;
      }

      $currentContent.css('visibility', '').css({
        height: contentHeight
      });
    }

    function selectDefaultTab() {
      $currentTab = $tabs.filter('.active');

      if (!$currentTab.length) {
        $currentTab = $tabs.first();
      }

      $currentTab.find('a').trigger('click');
    }

    function initEvents() {
      $el.on('click', '.tabs-nav a', function (event) {
        event.preventDefault();
        $currentTab = $(this).parent();
        selectTab($currentTab);
      });
    }

    function init() {
      initEvents();
      selectDefaultTab();
      $(window).on('resize.calculate-tab', function () {
        var $currentContent = $el.find('.tabs-content-container .active').css('height', '');
        calculateHeight($currentContent);
      });
    }

    init();
  };

  $.fn.LP('AdminTab', function (options) {
    options = $.extend({}, options || {});
    return $.each(this, function () {
      var $el = $(this),
          tabs = $el.data('learn-press/tabs');

      if (!tabs) {
        tabs = new adminTabs($el, options);
        $el.data('learn-press/tabs', tabs);
      }

      return $el;
    });
  });
})(jQuery);

/***/ }),

/***/ "./assets/src/js/admin/utils/advanced-list.js":
/*!****************************************************!*\
  !*** ./assets/src/js/admin/utils/advanced-list.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

;

(function ($) {
  /**
   * Advanced list.
   *
   * @param el
   * @param options
   */
  var AdvancedList = function AdvancedList(el, options) {
    var self = this,
        $el = $(el).hasClass('advanced-list') ? $(el) : $('.advanced-list', el);
    this.options = $.extend({
      template: '<li data-id="{{id}}"><span class="remove-item"></span><span>{{text}}</span> </li>'
    }, options || {});
    this.$el = $el;
    /**
     * Callback for removing event.
     *
     * @param e
     * @private
     */

    function _remove(e) {
      e.preventDefault();
      remove($el.children().index($(this).closest('li')) + 1);
    }
    /**
     *
     * @param e
     * @private
     */


    function _add(e) {}
    /**
     * Remove an element at a position from list.
     *
     * @param at
     */


    function remove(at) {
      $el.children(':eq(' + (at - 1) + ')').remove();
      self.options.onRemove && self.options.onRemove.call(self);
    }
    /**
     * Add new element into list.
     *
     * @param data
     * @param at - Optional. Position where to insert
     */


    function add(data, at) {
      var options = {},
          template = getTemplate();

      if ($.isPlainObject(data)) {
        options = $.extend({
          id: 0,
          text: ''
        }, data);
      } else if (typeof data === 'string') {
        options = {
          id: '',
          text: data
        };
      } else if (data[0] !== undefined) {
        options = {
          id: data[1] ? data[1] : '',
          text: data[0]
        };
      } // Replace placeholders with related variables


      for (var prop in options) {
        var reg = new RegExp('\{\{' + prop + '\}\}', 'g');
        template = template.replace(reg, options[prop]);
      }

      template = $(template);

      if (at !== undefined) {
        var $e = $el.children(':eq(' + (at - 1) + ')');

        if ($e.length) {
          template.insertBefore($e);
        } else {
          $el.append(template);
        }
      } else {
        $el.append(template);
      } // Append "\n" between li elements


      var $child = $el.children().detach();
      $child.each(function () {
        $el.append("\n").append(this);
      });
      self.options.onAdd && self.options.onAdd.call(self);
    }

    function getTemplate() {
      var $container = $(self.options.template);

      if ($container.length) {
        return $container.html();
      }

      return self.options.template;
    }

    $el.on('click', '.remove-item', _remove); // export

    this.add = add;
    this.remove = remove;
  }; // Export


  $.fn.LP('AdvancedList', function (options) {
    var args = [];

    for (var i = 1; i < arguments.length; i++) {
      args.push(arguments[i]);
    }

    return $.each(this, function () {
      var $advancedList = $(this).data('advancedList');

      if (!$advancedList) {
        $advancedList = new AdvancedList(this, options);
        $(this).data('advancedList', $advancedList);
      } // Try to calling to methods of class


      if (typeof options === 'string') {
        if ($.isFunction($advancedList[options])) {
          return $advancedList[options].apply($advancedList, args);
        }
      }

      return this;
    });
  });
})(jQuery);

/***/ }),

/***/ "./assets/src/js/admin/utils/advertisement.js":
/*!****************************************************!*\
  !*** ./assets/src/js/admin/utils/advertisement.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

;

(function ($) {
  function LP_Advertisement_Slider(el, options) {
    this.options = $.extend({}, options || {});
    var $el = $(el),
        $items = $el.find('.slide-item'),
        $controls = $('<div class="slider-controls"><div class="prev-item"></div><div class="next-item"></div></div>'),
        $wrapItems = $('<div class="slider-items"></div>').append($items),
        itemIndex = 0,
        timer = null;

    function init() {
      createHTML();
      bindEvents();
      activeItem();
    }

    function createHTML() {
      $el.append($wrapItems);
      $items.each(function () {
        $(this).append($controls.clone());
      });
    }

    function activeItem(index) {
      index = index !== undefined ? index : itemIndex;
      var activeItem = $items.eq(index);
      activeItem.show(); // A ha???

      setTimeout(function () {
        activeItem.addClass('slide-active');
      }, 1);
      activeItem.siblings().removeClass('slide-active');
      timer && clearTimeout(timer);
      timer = setTimeout(function () {
        activeItem.siblings().hide();
      }, 500);
    }

    function nextItem() {
      if (itemIndex < $items.length - 1) {
        itemIndex++;
      } else {
        itemIndex = 0;
      }

      activeItem(itemIndex);
    }

    function prevItem() {
      if (itemIndex > 0) {
        itemIndex--;
      } else {
        itemIndex = $items.length - 1;
      }

      activeItem(itemIndex);
    }

    function bindEvents() {
      $el.on('click', '.next-item', nextItem);
      $el.on('click', '.prev-item', prevItem);
    }

    init();
  }

  $.fn.LP('Advertisement', function (opts) {
    return $.each(this, function () {
      var $slider = $(this).data('LP_Advertisement_Slider');

      if (!$slider) {
        $slider = new LP_Advertisement_Slider(this, opts);
        $(this).data('LP_Advertisement_Slider', $slider);
      }

      return this;
    });
  });
})(jQuery);

/***/ }),

/***/ "./assets/src/js/admin/utils/conditional-logic.js":
/*!********************************************************!*\
  !*** ./assets/src/js/admin/utils/conditional-logic.js ***!
  \********************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * Conditional Logic for metabox fields
 *
 * @author ThimPress
 * @package LearnPress/JS
 * @version 3.0.0
 */
;

(function ($) {
  window.conditional_logic_gray_state = function (state, field) {
    if (state) {
      $(this).removeClass('disabled');
    } else {
      $(this).addClass('disabled');
    }
  };

  var Conditional_Logic = window.Conditional_Logic = function (options) {
    this.options = $.extend({}, options || {});
    this.updateAll();
  };

  Conditional_Logic.prototype = $.extend(Conditional_Logic.prototype, {
    evaluate: function evaluate(changedId, conditionals) {
      if (!conditionals) {
        return undefined;
      }

      if (!conditionals || !$.isArray(conditionals.conditional)) {
        return undefined;
      }

      var show = undefined,
          controls = conditionals.conditional;

      for (var i in controls) {
        var _show = this.evaluateRequirement(controls[i]),
            operator = (controls[i].combine || 'and').toLowerCase();

        if (_show !== undefined && show !== undefined) {
          if (operator === 'and') {
            show = show && _show;
          } else {
            show = show || _show;
          }
        } else if (show === undefined) {
          show = _show;
        }
      }

      return show;
    },
    evaluateRequirement: function evaluateRequirement(requirement) {
      if (!requirement) {
        return undefined;
      }

      if (!requirement['field']) {
        return undefined;
      }

      if (requirement['compare'] === undefined) {
        requirement['compare'] = '=';
      }

      var control = $('#field-' + requirement.field);

      switch (requirement['state']) {
        case 'show':
          return control.is(':visible');
          break;

        case 'hide':
          return !control.is(':visible');
          break;

        default:
          var value = '';

          switch (this.getFieldType(control)) {
            case 'yes-no':
              var $chk = control.find('input[type="checkbox"]');
              value = $chk.is(':checked') ? $chk.val() : '';
              break;

            case 'radio':
              value = control.find('input:checked').val();
              break;

            default:
              value = control.find('input, select').val();
          }

          return this.compare(requirement['value'], value, requirement['compare']);
      }
    },
    compare: function compare(value2, value1, operator) {
      var show = undefined;

      switch (operator) {
        case '===':
          show = value1 === value2;
          break;

        case '==':
        case '=':
        case 'equals':
        case 'equal':
          show = value1 === value2;
          break;

        case '!==':
          show = value1 !== value2;
          break;

        case '!=':
        case 'not equal':
          show = value1 !== value2;
          break;

        case '>=':
        case 'greater or equal':
        case 'equal or greater':
          show = value1 >= value2;
          break;

        case '<=':
        case 'smaller or equal':
        case 'equal or smaller':
          show = value1 <= value2;
          break;

        case '>':
        case 'greater':
          show = value1 > value2;
          break;

        case '<':
        case 'smaller':
          show = value1 < value2;
          break;

        case 'contains':
        case 'in':
          var _array, _string;

          if ($.isArray(value1) && !$.isArray(value2)) {
            _array = value1;
            _string = value2;
          } else if ($.isArray(value2) && !$.isArray(value1)) {
            _array = value2;
            _string = value1;
          }

          if (_array && _string) {
            if (-1 === $.inArray(_string, _array)) {
              show = false;
            }
          } else {
            if (-1 === value1.indexOf(value2) && -1 === value2.indexOf(value1)) {
              show = false;
            }
          }

          break;

        default:
          show = value1 === value2;
      }

      if (show !== undefined) {
        return show;
      }

      return true;
    },
    hasConditional: function hasConditional(source, target) {
      if (!this.options.conditionals) {
        return;
      }

      if (!this.options.conditionals[target]) {
        return false;
      }

      for (var i in this.options.conditionals[target]['conditional']) {
        if (this.options.conditionals[target]['conditional'][i].field === source) {
          return this.options.conditionals[target];
        }
      }

      return false;
    },
    update: function update(changedField, $fields) {
      var $changedField = $(changedField),
          id = this.getFieldName($changedField);
      $fields = $fields || $('.rwmb-field');

      _.forEach($fields, function (field) {
        var thisField = $(field),
            thisId = this.getFieldName(thisField);

        if (thisId === id) {
          return;
        }

        var conditional = this.hasConditional(id, thisId);

        if (!conditional) {
          return;
        }

        var show = this.evaluate($changedField, conditional);

        if (show !== undefined) {
          if (conditional.state === 'hide') {
            show = !show;
          }

          if ($.isFunction(window[conditional.state_callback])) {
            window[conditional.state_callback].call(thisField, show, thisField);
          } else {
            thisField.toggle(show);
          }
        }
      }, this);
    },
    updateAll: function updateAll() {
      var $fields = $('.rwmb-field'),
          that = this;

      _.forEach($fields, function (field) {
        var $field = $(field),
            type = this.getFieldType($field),
            id = $field.find('.rwmb-field-name').val();

        if (!id) {
          return;
        }

        $field.attr('id', 'field-' + id);

        if (-1 === _.indexOf(this.supportFields, type)) {
          return;
        }

        $field.find('input, select, textarea').on('change', function () {
          that.update($(this).closest('.rwmb-field'), $fields);
        }).trigger('change');
      }, this);
    },
    getFieldType: function getFieldType(field) {
      var $field = $(field);

      if ($field.length === 0) {
        return false;
      }

      var className = $field.get(0).className,
          m = className.match(/rwmb-([^\s]*)-wrapper/);

      if (m) {
        return m[1];
      }

      return false;
    },
    getFieldName: function getFieldName(field) {
      return $(field).find('.rwmb-field-name').val();
    },
    supportFields: ['yes-no', 'text', 'number', 'radio']
  });
  $(document).ready(function () {
    if (window.lp_conditional_logic !== undefined) {
      new Conditional_Logic({
        conditionals: lp_conditional_logic
      });
    }
  });
})(jQuery);

/***/ }),

/***/ "./assets/src/js/admin/utils/dropdown-pages.js":
/*!*****************************************************!*\
  !*** ./assets/src/js/admin/utils/dropdown-pages.js ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

;

(function () {
  var $ = window.jQuery;

  if ($ === undefined) {
    return;
  }

  function DropdownPages(el, options) {
    this.options = $.extend({
      ID: '',
      name: 'Add new page'
    }, options || {});
    var $element = $(el),
        $select = $element.find('select'),
        $listWrap = $element.find('.list-pages-wrapper'),
        $actions = $element.find('.quick-add-page-actions'),
        $form = $element.find('.quick-add-page-inline');

    function addNewPageToList(args) {
      var $new_option = $('<option value="' + args.ID + '">' + args.name + '</option>');
      var position = $.inArray(args.ID + "", args.positions);
      $('.learn-press-dropdown-pages select').each(function () {
        var $sel = $(this),
            $option = $new_option.clone();

        if (position == 0) {
          $('option', $sel).each(function () {
            if (parseInt($(this).val())) {
              $option.insertBefore($(this));
              return false;
            }
          });
        } else if (position == args.positions.length - 1) {
          $sel.append($option);
        } else {
          $option.insertAfter($('option[value="' + args.positions[position - 1] + '"]', $sel));
        }
      });
    }

    $select.change(function () {
      $actions.addClass('hide-if-js');

      if (this.value !== 'add_new_page') {
        if (parseInt(this.value)) {
          $actions.find('a.edit-page').attr('href', 'post.php?post=' + this.value + '&action=edit');
          $actions.find('a.view-page').attr('href', lpGlobalSettings.siteurl + '?page_id=' + this.value);
          $actions.removeClass('hide-if-js');
          $select.attr('data-selected', this.value);
        }

        return;
      }

      $listWrap.addClass('hide-if-js');
      $form.removeClass('hide-if-js').find('input').focus().val('');
    }); // Select 2

    $select.css('width', $select.width() + 50).find('option').each(function () {
      $(this).html($(this).html().replace(/&nbsp;&nbsp;&nbsp;/g, ''));
    });
    $select.select2({
      allowClear: true
    });
    $select.on('select2:select', function (e) {
      var data = e.params.data;
    });
    $element.on('click', '.quick-add-page-inline button', function () {
      var $button = $(this),
          $input = $form.find('input'),
          page_name = $input.val();

      if (!page_name) {
        alert('Please enter the name of page');
        $input.focus();
        return;
      }

      $button.prop('disabled', true);
      $.ajax({
        url: lpGlobalSettings.ajax,
        data: {
          action: 'learnpress_create_page',
          page_name: page_name,
          'lp-settings-nonce': $('input[name=lp-settings-nonce]').val()
        },
        type: 'post',
        dataType: 'html',
        success: function success(response) {
          response = LP.parseJSON(response);

          if (response.page) {
            addNewPageToList({
              ID: response.page.ID,
              name: response.page.post_title,
              positions: response.positions
            });
            $select.val(response.page.ID).focus().trigger('change');
            $form.addClass('hide-if-js');
          } else if (response.error) {
            alert(response.error);
          }

          $button.prop('disabled', false);
          $listWrap.removeClass('hide-if-js');
        }
      });
    }).on('click', '.quick-add-page-inline a', function (e) {
      e.preventDefault();
      $form.addClass('hide-if-js');
      $select.val($select.attr('data-selected') + '').removeAttr('disabled').trigger('change');
      $listWrap.removeClass('hide-if-js');
    }).on('click', '.button-quick-add-page', function (e) {
      $select.val('add_new_page').trigger('change');
    }).on('keypress keydown', '.quick-add-page-inline input[type="text"]', function (e) {
      if (e.keyCode == 13 && e.type == 'keypress') {
        e.preventDefault();
        $(this).siblings('button').trigger('click');
      } else if (e.keyCode == 27 && e.type == 'keydown') {
        $(this).siblings('a').trigger('click');
      }
    });
  }

  $.fn.LP('DropdownPages', function () {
    return $.each(this, function () {
      var $instance = $(this).data('DropdownPages');

      if (!$instance) {
        $instance = new DropdownPages(this, {});
        $(this).data('DropdownPages', $instance);
      }

      return $instance;
    });
  });
})();

/***/ }),

/***/ "./assets/src/js/admin/utils/index.js":
/*!********************************************!*\
  !*** ./assets/src/js/admin/utils/index.js ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils */ "./assets/src/js/utils/index.js");
/* harmony import */ var _admin_notice__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./admin-notice */ "./assets/src/js/admin/utils/admin-notice.js");
/* harmony import */ var _advertisement__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./advertisement */ "./assets/src/js/admin/utils/advertisement.js");
/* harmony import */ var _advertisement__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_advertisement__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _dropdown_pages__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./dropdown-pages */ "./assets/src/js/admin/utils/dropdown-pages.js");
/* harmony import */ var _dropdown_pages__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_dropdown_pages__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _advanced_list__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./advanced-list */ "./assets/src/js/admin/utils/advanced-list.js");
/* harmony import */ var _advanced_list__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_advanced_list__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _admin_tabs__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./admin-tabs */ "./assets/src/js/admin/utils/admin-tabs.js");
/* harmony import */ var _admin_tabs__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_admin_tabs__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _utils_email_validator__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../utils/email-validator */ "./assets/src/js/utils/email-validator.js");
/* harmony import */ var _modal_search_users__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./modal-search-users */ "./assets/src/js/admin/utils/modal-search-users.js");
/* harmony import */ var _modal_search_users__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_modal_search_users__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _modal_search_items__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./modal-search-items */ "./assets/src/js/admin/utils/modal-search-items.js");
/* harmony import */ var _modal_search_items__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_modal_search_items__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _search_items__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./search-items */ "./assets/src/js/admin/utils/search-items.js");
/* harmony import */ var _search_items__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_search_items__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _conditional_logic__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./conditional-logic */ "./assets/src/js/admin/utils/conditional-logic.js");
/* harmony import */ var _conditional_logic__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_conditional_logic__WEBPACK_IMPORTED_MODULE_10__);
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

// global utilities
 // local utilities











/* harmony default export */ __webpack_exports__["default"] = (_objectSpread({}, _utils__WEBPACK_IMPORTED_MODULE_0__, {
  dismissNotice: _admin_notice__WEBPACK_IMPORTED_MODULE_1__["default"],
  Advertisement: _advertisement__WEBPACK_IMPORTED_MODULE_2___default.a,
  DropdownPages: _dropdown_pages__WEBPACK_IMPORTED_MODULE_3___default.a,
  AdvancedList: _advanced_list__WEBPACK_IMPORTED_MODULE_4___default.a,
  AdminTabs: _admin_tabs__WEBPACK_IMPORTED_MODULE_5___default.a,
  isEmail: _utils_email_validator__WEBPACK_IMPORTED_MODULE_6__["default"],
  ModalSearchItems: _modal_search_items__WEBPACK_IMPORTED_MODULE_8___default.a,
  ModalSearchUsers: _modal_search_users__WEBPACK_IMPORTED_MODULE_7___default.a
}));

/***/ }),

/***/ "./assets/src/js/admin/utils/modal-search-items.js":
/*!*********************************************************!*\
  !*** ./assets/src/js/admin/utils/modal-search-items.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

var $ = window.jQuery;
var _ = window._;
window.$Vue = window.$Vue || window.Vue;
$(document).ready(function () {
  var $VueHTTP = $Vue ? $Vue.http : false;
  document.getElementById('vue-modal-search-items') && $Vue && function () {
    $Vue.component('learn-press-modal-search-items', {
      template: '#learn-press-modal-search-items',
      data: function data() {
        return {
          paged: 1,
          term: '',
          hasItems: false,
          selected: []
        };
      },
      watch: {
        show: function show(value) {
          if (value) {
            $(this.$refs.search).focus();
          }
        }
      },
      props: ['postType', 'context', 'contextId', 'show', 'callbacks', 'exclude'],
      created: function created() {},
      mounted: function mounted() {
        this.term = '';
        this.paged = 1;
        this.search();
      },
      methods: {
        doSearch: function doSearch(e) {
          this.term = e.target.value;
          this.paged = 1;
          this.search();
        },
        search: _.debounce(function (term) {
          $('#modal-search-items').addClass('loading');
          var that = this;
          $VueHTTP.post(window.location.href, {
            type: this.postType,
            context: this.context,
            context_id: this.contextId,
            term: term || this.term,
            paged: this.paged,
            exclude: this.exclude,
            'lp-ajax': 'modal_search_items'
          }, {
            emulateJSON: true,
            params: {}
          }).then(function (response) {
            var result = LP.parseJSON(response.body || response.bodyText);
            that.hasItems = !!_.size(result.items);
            $('#modal-search-items').removeClass('loading');
            $(that.$el).find('.search-results').html(result.html).find('input[type="checkbox"]').each(function () {
              var id = parseInt($(this).val());

              if (_.indexOf(that.selected, id) >= 0) {
                this.checked = true;
              }
            });

            _.debounce(function () {
              $(that.$el).find('.search-nav').html(result.nav).find('a, span').addClass('button').filter('span').addClass('disabled');
            }, 10)();
          });
        }, 500),
        loadPage: function loadPage(e) {
          e.preventDefault();
          var $button = $(e.target);

          if ($button.is('span')) {
            return;
          }

          if ($button.hasClass('next')) {
            this.paged++;
          } else if ($button.hasClass('prev')) {
            this.paged--;
          } else {
            var paged = $button.html();
            this.paged = parseInt(paged);
          }

          this.search();
        },
        selectItem: function selectItem(e) {
          var $select = $(e.target).closest('li'),
              $chk = $select.find('input[type="checkbox"]'),
              id = parseInt($chk.val()),
              pos = _.indexOf(this.selected, id);

          if ($chk.is(':checked')) {
            if (pos === -1) {
              this.selected.push(id);
            }
          } else {
            if (pos >= 0) {
              this.selected.splice(pos, 1);
            }
          }
        },
        addItems: function addItems() {
          var close = true;

          if (this.callbacks && this.callbacks.addItems) {
            this.callbacks.addItems.call(this);
          }

          $(document).triggerHandler('learn-press/add-order-items', this.selected);
        },
        close: function close() {
          this.$emit('close');
        }
      }
    });
    window.LP.$modalSearchItems = new $Vue({
      el: '#vue-modal-search-items',
      data: {
        show: false,
        term: '',
        postType: '',
        callbacks: {},
        exclude: '',
        context: ''
      },
      methods: {
        open: function open(options) {
          _.each(options.data, function (v, k) {
            this[k] = v;
          }, this);

          this.callbacks = options.callbacks;
          this.focusSearch();
        },
        close: function close() {
          this.show = false;
        },
        focusSearch: _.debounce(function () {
          $('input[name="search"]', this.$el).focus();
        }, 200)
      }
    });
  }();
});

/***/ }),

/***/ "./assets/src/js/admin/utils/modal-search-users.js":
/*!*********************************************************!*\
  !*** ./assets/src/js/admin/utils/modal-search-users.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

var $ = window.jQuery;
var _ = window._;
window.$Vue = window.$Vue || window.Vue;
$(document).ready(function () {
  var $VueHTTP = $Vue ? $Vue.http : false;
  document.getElementById('vue-modal-search-users') && $Vue && function () {
    $Vue.component('learn-press-modal-search-users', {
      template: '#learn-press-modal-search-users',
      data: function data() {
        return {
          paged: 1,
          term: '',
          hasUsers: false,
          selected: []
        };
      },
      watch: {
        show: function show(value) {
          if (value) {
            $(this.$refs.search).focus();
          }
        }
      },
      props: ['multiple', 'context', 'contextId', 'show', 'callbacks', 'textFormat', 'exclude'],
      created: function created() {},
      methods: {
        doSearch: function doSearch(e) {
          this.term = e.target.value;
          this.paged = 1;
          this.search();
        },
        search: _.debounce(function (term) {
          var that = this;
          $VueHTTP.post(window.location.href, {
            type: this.postType,
            context: this.context,
            context_id: this.contextId,
            term: term || this.term,
            paged: this.paged,
            multiple: this.multiple ? 'yes' : 'no',
            text_format: this.textFormat,
            exclude: this.exclude,
            'lp-ajax': 'modal_search_users'
          }, {
            emulateJSON: true,
            params: {}
          }).then(function (response) {
            var result = LP.parseJSON(response.body || response.bodyText);
            that.hasUsers = !!_.size(result.users);
            $(that.$el).find('.search-results').html(result.html).find('input[type="checkbox"]').each(function () {
              var id = parseInt($(this).val());

              if (_.indexOf(that.selected, id) >= 0) {
                this.checked = true;
              }
            });

            _.debounce(function () {
              $(that.$el).find('.search-nav').html(result.nav).find('a, span').addClass('button').filter('span').addClass('disabled');
            }, 10)();
          });
        }, 500),
        loadPage: function loadPage(e) {
          e.preventDefault();
          var $button = $(e.target);

          if ($button.is('span')) {
            return;
          }

          if ($button.hasClass('next')) {
            this.paged++;
          } else if ($button.hasClass('prev')) {
            this.paged--;
          } else {
            var paged = $button.html();
            this.paged = parseInt(paged);
          }

          this.search();
        },
        selectItem: function selectItem(e) {
          var $select = $(e.target).closest('li'),
              $chk = $select.find('input[type="checkbox"]'),
              id = parseInt($chk.val()),
              //pos = _.indexOf(this.selected, id),
          pos = _.findLastIndex(this.selected, {
            id: id
          });

          if (this.multiple) {
            if ($chk.is(':checked')) {
              if (pos === -1) {
                this.selected.push($select.closest('li').data('data'));
              }
            } else {
              if (pos >= 0) {
                this.selected.splice(pos, 1);
              }
            }
          } else {
            e.preventDefault();
            this.selected = [$select.closest('li').data('data')];
            this.addUsers();
          }
        },
        addUsers: function addUsers() {
          var $els = $(this.$el).find('.lp-result-item');

          if (this.callbacks && this.callbacks.addUsers) {
            this.callbacks.addUsers.call(this, this.selected);
          }

          $(document).triggerHandler('learn-press/modal-add-users', this.selected);
        },
        close: function close() {
          this.$emit('close');
        }
      }
    });
    window.LP.$modalSearchUsers = new $Vue({
      el: '#vue-modal-search-users',
      data: {
        show: false,
        term: '',
        multiple: false,
        callbacks: {},
        textFormat: '{{display_name}} ({{email}})',
        exclude: 0
      },
      methods: {
        open: function open(options) {
          _.each(options.data, function (v, k) {
            this[k] = v;
          }, this);

          this.callbacks = options.callbacks;
          this.focusSearch();
        },
        close: function close() {
          this.show = false;
        },
        focusSearch: _.debounce(function () {
          $('input[name="search"]', this.$el).focus();
        }, 200)
      }
    });
  }();
});

/***/ }),

/***/ "./assets/src/js/admin/utils/search-items.js":
/*!***************************************************!*\
  !*** ./assets/src/js/admin/utils/search-items.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

;

(function ($) {
  var timer = null,
      $wraps = null,
      $cloneWraps = null,
      onSearch = function onSearch(keyword) {
    if (!$cloneWraps) {
      $cloneWraps = $wraps.clone();
    }

    var keywords = keyword.toLowerCase().split(/\s+/).filter(function (a, b) {
      return a.length >= 3;
    });

    var foundItems = function foundItems($w1, $w2) {
      return $w1.find('.plugin-card').each(function () {
        var $item = $(this),
            itemText = $item.find('.item-title').text().toLowerCase(),
            itemDesc = $item.find('.column-description, .theme-description').text();

        var found = function found() {
          var reg = new RegExp(keywords.join('|'), 'ig');
          return itemText.match(reg) || itemDesc.match(reg);
        };

        if (keywords.length) {
          if (found()) {
            var $clone = $item.clone();
            $w2.append($clone);
          }
        } else {
          $w2.append($item.clone());
        }
      });
    };

    $wraps.each(function (i) {
      var $this = $(this).html(''),
          $items = foundItems($cloneWraps.eq(i), $this),
          count = $this.children().length;
      $this.prev('h2').find('span').html(count);
    });
  };

  $(document).on('keyup', '.lp-search-addon', function (e) {
    timer && clearTimeout(timer);
    timer = setTimeout(onSearch, 300, e.target.value);
  }).ready(function () {
    $wraps = $('.addons-browse');
  });
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

/***/ }),

/***/ "./assets/src/js/utils/event-callback.js":
/*!***********************************************!*\
  !*** ./assets/src/js/utils/event-callback.js ***!
  \***********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/**
 * Manage event callbacks.
 * Allow add/remove a callback function into custom event of an object.
 *
 * @constructor
 */
var Event_Callback = function Event_Callback(self) {
  var callbacks = {};
  var $ = window.jQuery;

  this.on = function (event, callback) {
    var namespaces = event.split('.'),
        namespace = '';

    if (namespaces.length > 1) {
      event = namespaces[0];
      namespace = namespaces[1];
    }

    if (!callbacks[event]) {
      callbacks[event] = [[], {}];
    }

    if (namespace) {
      if (!callbacks[event][1][namespace]) {
        callbacks[event][1][namespace] = [];
      }

      callbacks[event][1][namespace].push(callback);
    } else {
      callbacks[event][0].push(callback);
    }

    return self;
  };

  this.off = function (event, callback) {
    var namespaces = event.split('.'),
        namespace = '';

    if (namespaces.length > 1) {
      event = namespaces[0];
      namespace = namespaces[1];
    }

    if (!callbacks[event]) {
      return self;
    }

    var at = -1;

    if (!namespace) {
      if ($.isFunction(callback)) {
        at = callbacks[event][0].indexOf(callback);

        if (at < 0) {
          return self;
        }

        callbacks[event][0].splice(at, 1);
      } else {
        callbacks[event][0] = [];
      }
    } else {
      if (!callbacks[event][1][namespace]) {
        return self;
      }

      if ($.isFunction(callback)) {
        at = callbacks[event][1][namespace].indexOf(callback);

        if (at < 0) {
          return self;
        }

        callbacks[event][1][namespace].splice(at, 1);
      } else {
        callbacks[event][1][namespace] = [];
      }
    }

    return self;
  };

  this.callEvent = function (event, callbackArgs) {
    if (!callbacks[event]) {
      return;
    }

    if (callbacks[event][0]) {
      for (var i = 0; i < callbacks[event][0].length; i++) {
        $.isFunction(callbacks[event][0][i]) && callbacks[event][i][0].apply(self, callbackArgs);
      }
    }

    if (callbacks[event][1]) {
      for (var i in callbacks[event][1]) {
        for (var j = 0; j < callbacks[event][1][i].length; j++) {
          $.isFunction(callbacks[event][1][i][j]) && callbacks[event][1][i][j].apply(self, callbackArgs);
        }
      }
    }
  };
};

/* harmony default export */ __webpack_exports__["default"] = (Event_Callback);

/***/ }),

/***/ "./assets/src/js/utils/extend.js":
/*!***************************************!*\
  !*** ./assets/src/js/utils/extend.js ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function () {
  window.LP = window.LP || {};

  if (typeof arguments[0] === 'string') {
    LP[arguments[0]] = LP[arguments[0]] || {};
    LP[arguments[0]] = jQuery.extend(LP[arguments[0]], arguments[1]);
  } else {
    LP = jQuery.extend(LP, arguments[0]);
  }
});

/***/ }),

/***/ "./assets/src/js/utils/fn.js":
/*!***********************************!*\
  !*** ./assets/src/js/utils/fn.js ***!
  \***********************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/**
 * Auto prepend `LP` prefix for jQuery fn plugin name.
 *
 * Create : $.fn.LP( 'PLUGIN_NAME', func) <=> $.fn.LP_PLUGIN_NAME
 * Usage: $(selector).LP('PLUGIN_NAME') <=> $(selector).LP_PLUGIN_NAME()
 *
 * @version 3.2.6
 */
var $ = window.jQuery;
var exp;

(function () {
  if ($ === undefined) {
    return;
  }

  $.fn.LP = exp = function exp(widget, fn) {
    if ($.isFunction(fn)) {
      $.fn['LP_' + widget] = fn;
    } else if (widget) {
      var args = [];

      if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
          args.push(arguments[i]);
        }
      }

      return $.isFunction($(this)['LP_' + widget]) ? $(this)['LP_' + widget].apply(this, args) : this;
    }

    return this;
  };
})();

/* harmony default export */ __webpack_exports__["default"] = (exp);

/***/ }),

/***/ "./assets/src/js/utils/hook.js":
/*!*************************************!*\
  !*** ./assets/src/js/utils/hook.js ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Hook = {
  hooks: {
    action: {},
    filter: {}
  },
  addAction: function addAction(action, callable, priority, tag) {
    this.addHook('action', action, callable, priority, tag);
    return this;
  },
  addFilter: function addFilter(action, callable, priority, tag) {
    this.addHook('filter', action, callable, priority, tag);
    return this;
  },
  doAction: function doAction(action) {
    this.doHook('action', action, arguments);
    return this;
  },
  applyFilters: function applyFilters(action) {
    return this.doHook('filter', action, arguments);
  },
  removeAction: function removeAction(action, tag) {
    this.removeHook('action', action, tag);
    return this;
  },
  removeFilter: function removeFilter(action, priority, tag) {
    this.removeHook('filter', action, priority, tag);
    return this;
  },
  addHook: function addHook(hookType, action, callable, priority, tag) {
    if (undefined === this.hooks[hookType][action]) {
      this.hooks[hookType][action] = [];
    }

    var hooks = this.hooks[hookType][action];

    if (undefined === tag) {
      tag = action + '_' + hooks.length;
    }

    this.hooks[hookType][action].push({
      tag: tag,
      callable: callable,
      priority: priority
    });
    return this;
  },
  doHook: function doHook(hookType, action, args) {
    // splice args from object into array and remove first index which is the hook name
    args = Array.prototype.slice.call(args, 1);

    if (undefined !== this.hooks[hookType][action]) {
      var hooks = this.hooks[hookType][action],
          hook; //sort by priority

      hooks.sort(function (a, b) {
        return a["priority"] - b["priority"];
      });

      for (var i = 0; i < hooks.length; i++) {
        hook = hooks[i].callable;
        if (typeof hook !== 'function') hook = window[hook];

        if ('action' === hookType) {
          hook.apply(null, args);
        } else {
          args[0] = hook.apply(null, args);
        }
      }
    }

    if ('filter' === hookType) {
      return args[0];
    }

    return this;
  },
  removeHook: function removeHook(hookType, action, priority, tag) {
    if (undefined !== this.hooks[hookType][action]) {
      var hooks = this.hooks[hookType][action];

      for (var i = hooks.length - 1; i >= 0; i--) {
        if ((undefined === tag || tag === hooks[i].tag) && (undefined === priority || priority === hooks[i].priority)) {
          hooks.splice(i, 1);
        }
      }
    }

    return this;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Hook);

/***/ }),

/***/ "./assets/src/js/utils/index.js":
/*!**************************************!*\
  !*** ./assets/src/js/utils/index.js ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./extend */ "./assets/src/js/utils/extend.js");
/* harmony import */ var _fn__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./fn */ "./assets/src/js/utils/fn.js");
/* harmony import */ var _quick_tip__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./quick-tip */ "./assets/src/js/utils/quick-tip.js");
/* harmony import */ var _quick_tip__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_quick_tip__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _message_box__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./message-box */ "./assets/src/js/utils/message-box.js");
/* harmony import */ var _event_callback__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./event-callback */ "./assets/src/js/utils/event-callback.js");
/* harmony import */ var _hook__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./hook */ "./assets/src/js/utils/hook.js");
/* harmony import */ var _jquery_plugins__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./jquery.plugins */ "./assets/src/js/utils/jquery.plugins.js");
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/**
 * Utility functions may use for both admin and frontend.
 *
 * @version 3.2.6
 */







var $ = jQuery;

String.prototype.getQueryVar = function (name) {
  name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
  var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
      results = regex.exec(this);
  return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
};

String.prototype.addQueryVar = function (name, value) {
  var url = this,
      m = url.split('#');
  url = m[0];

  if (name.match(/\[/)) {
    url += url.match(/\?/) ? '&' : '?';
    url += name + '=' + value;
  } else {
    if (url.indexOf('&' + name + '=') != -1 || url.indexOf('?' + name + '=') != -1) {
      url = url.replace(new RegExp(name + "=([^&#]*)", 'g'), name + '=' + value);
    } else {
      url += url.match(/\?/) ? '&' : '?';
      url += name + '=' + value;
    }
  }

  return url + (m[1] ? '#' + m[1] : '');
};

String.prototype.removeQueryVar = function (name) {
  var url = this;
  var m = url.split('#');
  url = m[0];
  name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
  var regex = new RegExp("[\\?&]" + name + "([\[][^=]*)?=([^&#]*)", 'g');
  url = url.replace(regex, '');
  return url + (m[1] ? '#' + m[1] : '');
};

if ($.isEmptyObject("") == false) {
  $.isEmptyObject = function (a) {
    var prop;

    for (prop in a) {
      if (a.hasOwnProperty(prop)) {
        return false;
      }
    }

    return true;
  };
}

var _default = {
  Hook: _hook__WEBPACK_IMPORTED_MODULE_5__["default"],
  setUrl: function setUrl(url, ember, title) {
    if (url) {
      history.pushState({}, title, url);
      LP.Hook.doAction('learn_press_set_location_url', url);
    }
  },
  toggleGroupSection: function toggleGroupSection(el, target) {
    var $el = $(el),
        isHide = $el.hasClass('hide-if-js');

    if (isHide) {
      $el.hide().removeClass('hide-if-js');
    }

    $el.removeClass('hide-if-js').slideToggle(function () {
      var $this = $(this);

      if ($this.is(':visible')) {
        $(target).addClass('toggle-on').removeClass('toggle-off');
      } else {
        $(target).addClass('toggle-off').removeClass('toggle-on');
      }
    });
  },
  overflow: function overflow(el, v) {
    var $el = $(el),
        overflow = $el.css('overflow');

    if (v) {
      $el.css('overflow', v).data('overflow', overflow);
    } else {
      $el.css('overflow', $el.data('overflow'));
    }
  },
  getUrl: function getUrl() {
    return window.location.href;
  },
  addQueryVar: function addQueryVar(name, value, url) {
    return (url === undefined ? window.location.href : url).addQueryVar(name, value);
  },
  removeQueryVar: function removeQueryVar(name, url) {
    return (url === undefined ? window.location.href : url).removeQueryVar(name);
  },
  reload: function reload(url) {
    if (!url) {
      url = window.location.href;
    }

    window.location.href = url;
  },
  parseResponse: function parseResponse(response, type) {
    var m = response.match(/<-- LP_AJAX_START -->(.*)<-- LP_AJAX_END -->/);

    if (m) {
      response = m[1];
    }

    return (type || "json") === "json" ? this.parseJSON(response) : response;
  },
  parseJSON: function parseJSON(data) {
    var m = (data + '').match(/<-- LP_AJAX_START -->(.*)<-- LP_AJAX_END -->/);

    try {
      if (m) {
        data = $.parseJSON(m[1]);
      } else {
        data = $.parseJSON(data);
      }
    } catch (e) {
      data = {};
    }

    return data;
  },
  ajax: function ajax(args) {
    var type = args.type || 'post',
        dataType = args.dataType || 'json',
        data = args.action ? $.extend(args.data, {
      'lp-ajax': args.action
    }) : args.data,
        beforeSend = args.beforeSend || function () {},
        url = args.url || window.location.href; //                        console.debug( beforeSend );


    $.ajax({
      data: data,
      url: url,
      type: type,
      dataType: 'html',
      beforeSend: beforeSend.apply(null, args),
      success: function success(raw) {
        var response = LP.parseResponse(raw, dataType);
        $.isFunction(args.success) && args.success(response, raw);
      },
      error: function error() {
        $.isFunction(args.error) && args.error.apply(null, LP.funcArgs2Array());
      }
    });
  },
  doAjax: function doAjax(args) {
    var type = args.type || 'post',
        dataType = args.dataType || 'json',
        action = (args.prefix === undefined || 'learnpress_') + args.action,
        data = args.action ? $.extend(args.data, {
      action: action
    }) : args.data;
    $.ajax({
      data: data,
      url: args.url || window.location.href,
      type: type,
      dataType: 'html',
      success: function success(raw) {
        var response = LP.parseResponse(raw, dataType);
        $.isFunction(args.success) && args.success(response, raw);
      },
      error: function error() {
        $.isFunction(args.error) && args.error.apply(null, LP.funcArgs2Array());
      }
    });
  },
  funcArgs2Array: function funcArgs2Array(args) {
    var arr = [];

    for (var i = 0; i < args.length; i++) {
      arr.push(args[i]);
    }

    return arr;
  },
  addFilter: function addFilter(action, callback) {
    var $doc = $(document),
        event = 'LP.' + action;
    $doc.on(event, callback);
    LP.log($doc.data('events'));
    return this;
  },
  applyFilters: function applyFilters() {
    var $doc = $(document),
        action = arguments[0],
        args = this.funcArgs2Array(arguments);

    if ($doc.hasEvent(action)) {
      args[0] = 'LP.' + action;
      return $doc.triggerHandler.apply($doc, args);
    }

    return args[1];
  },
  addAction: function addAction(action, callback) {
    return this.addFilter(action, callback);
  },
  doAction: function doAction() {
    var $doc = $(document),
        action = arguments[0],
        args = this.funcArgs2Array(arguments);

    if ($doc.hasEvent(action)) {
      args[0] = 'LP.' + action;
      $doc.trigger.apply($doc, args);
    }
  },
  toElement: function toElement(element, args) {
    if ($(element).length === 0) {
      return;
    }

    args = $.extend({
      delay: 300,
      duration: 'slow',
      offset: 50,
      container: null,
      callback: null,
      invisible: false
    }, args || {});
    var $container = $(args.container),
        rootTop = 0;

    if ($container.length === 0) {
      $container = $('body, html');
    }

    rootTop = $container.offset().top;
    var to = $(element).offset().top + $container.scrollTop() - rootTop - args.offset;

    function isElementInView(element, fullyInView) {
      var pageTop = $container.scrollTop();
      var pageBottom = pageTop + $container.height();
      var elementTop = $(element).offset().top - $container.offset().top;
      var elementBottom = elementTop + $(element).height();

      if (fullyInView === true) {
        return pageTop < elementTop && pageBottom > elementBottom;
      } else {
        return elementTop <= pageBottom && elementBottom >= pageTop;
      }
    }

    if (args.invisible && isElementInView(element, true)) {
      return;
    }

    $container.fadeIn(10).delay(args.delay).animate({
      scrollTop: to
    }, args.duration, args.callback);
  },
  uniqueId: function uniqueId(prefix, more_entropy) {
    if (typeof prefix === 'undefined') {
      prefix = '';
    }

    var retId;

    var formatSeed = function formatSeed(seed, reqWidth) {
      seed = parseInt(seed, 10).toString(16); // to hex str

      if (reqWidth < seed.length) {
        // so long we split
        return seed.slice(seed.length - reqWidth);
      }

      if (reqWidth > seed.length) {
        // so short we pad
        return new Array(1 + (reqWidth - seed.length)).join('0') + seed;
      }

      return seed;
    }; // BEGIN REDUNDANT


    if (!this.php_js) {
      this.php_js = {};
    } // END REDUNDANT


    if (!this.php_js.uniqidSeed) {
      // init seed with big random int
      this.php_js.uniqidSeed = Math.floor(Math.random() * 0x75bcd15);
    }

    this.php_js.uniqidSeed++;
    retId = prefix; // start with prefix, add current milliseconds hex string

    retId += formatSeed(parseInt(new Date().getTime() / 1000, 10), 8);
    retId += formatSeed(this.php_js.uniqidSeed, 5); // add seed hex string

    if (more_entropy) {
      // for more entropy we add a float lower to 10
      retId += (Math.random() * 10).toFixed(8).toString();
    }

    return retId;
  },
  log: function log() {
    //if (typeof LEARN_PRESS_DEBUG != 'undefined' && LEARN_PRESS_DEBUG && console) {
    for (var i = 0, n = arguments.length; i < n; i++) {
      console.log(arguments[i]);
    } //}

  },
  blockContent: function blockContent() {
    if ($('#learn-press-block-content').length === 0) {
      $(LP.template('learn-press-template-block-content', {})).appendTo($('body'));
    }

    LP.hideMainScrollbar().addClass('block-content');
    $(document).trigger('learn_press_block_content');
  },
  unblockContent: function unblockContent() {
    setTimeout(function () {
      LP.showMainScrollbar().removeClass('block-content');
      $(document).trigger('learn_press_unblock_content');
    }, 350);
  },
  hideMainScrollbar: function hideMainScrollbar(el) {
    if (!el) {
      el = 'html, body';
    }

    var $el = $(el);
    $el.each(function () {
      var $root = $(this),
          overflow = $root.css('overflow');
      $root.css('overflow', 'hidden').attr('overflow', overflow);
    });
    return $el;
  },
  showMainScrollbar: function showMainScrollbar(el) {
    if (!el) {
      el = 'html, body';
    }

    var $el = $(el);
    $el.each(function () {
      var $root = $(this),
          overflow = $root.attr('overflow');
      $root.css('overflow', overflow).removeAttr('overflow');
    });
    return $el;
  },
  template: typeof _ !== 'undefined' ? _.memoize(function (id, data) {
    var compiled,
        options = {
      evaluate: /<#([\s\S]+?)#>/g,
      interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
      escape: /\{\{([^\}]+?)\}\}(?!\})/g,
      variable: 'data'
    };

    var tmpl = function tmpl(data) {
      compiled = compiled || _.template($('#' + id).html(), null, options);
      return compiled(data);
    };

    return data ? tmpl(data) : tmpl;
  }, function (a, b) {
    return a + '-' + JSON.stringify(b);
  }) : function () {
    return '';
  },
  alert: function alert(localize, callback) {
    var title = '',
        message = '';

    if (typeof localize === 'string') {
      message = localize;
    } else {
      if (typeof localize['title'] !== 'undefined') {
        title = localize['title'];
      }

      if (typeof localize['message'] !== 'undefined') {
        message = localize['message'];
      }
    }

    $.alerts.alert(message, title, function (e) {
      LP._on_alert_hide();

      callback && callback(e);
    });

    this._on_alert_show();
  },
  confirm: function confirm(localize, callback) {
    var title = '',
        message = '';

    if (typeof localize === 'string') {
      message = localize;
    } else {
      if (typeof localize['title'] !== 'undefined') {
        title = localize['title'];
      }

      if (typeof localize['message'] !== 'undefined') {
        message = localize['message'];
      }
    }

    $.alerts.confirm(message, title, function (e) {
      LP._on_alert_hide();

      callback && callback(e);
    });

    this._on_alert_show();
  },
  _on_alert_show: function _on_alert_show() {
    var $container = $('#popup_container'),
        $placeholder = $('<span id="popup_container_placeholder" />').insertAfter($container).data('xxx', $container);
    $container.stop().css('top', '-=50').css('opacity', '0').animate({
      top: '+=50',
      opacity: 1
    }, 250);
  },
  _on_alert_hide: function _on_alert_hide() {
    var $holder = $("#popup_container_placeholder"),
        $container = $holder.data('xxx');

    if ($container) {
      $container.replaceWith($holder);
    }

    $container.appendTo($(document.body));
    $container.stop().animate({
      top: '+=50',
      opacity: 0
    }, 250, function () {
      $(this).remove();
    });
  },
  sendMessage: function sendMessage(data, object, targetOrigin, transfer) {
    if ($.isPlainObject(data)) {
      data = JSON.stringify(data);
    }

    object = object || window;
    targetOrigin = targetOrigin || '*';
    object.postMessage(data, targetOrigin, transfer);
  },
  receiveMessage: function receiveMessage(event, b) {
    var target = event.origin || event.originalEvent.origin,
        data = event.data || event.originalEvent.data || '';

    if (typeof data === 'string' || data instanceof String) {
      if (data.indexOf('{') === 0) {
        data = LP.parseJSON(data);
      }
    }

    LP.Hook.doAction('learn_press_receive_message', data, target);
  }
};
$(document).ready(function () {
  if (typeof $.alerts !== 'undefined') {
    $.alerts.overlayColor = '#000';
    $.alerts.overlayOpacity = 0.5;
    $.alerts.okButton = lpGlobalSettings.localize.button_ok;
    $.alerts.cancelButton = lpGlobalSettings.localize.button_cancel;
  }

  $('.learn-press-message.fixed').each(function () {
    var $el = $(this),
        options = $el.data();

    (function ($el, options) {
      if (options.delayIn) {
        setTimeout(function () {
          $el.show().hide().fadeIn();
        }, options.delayIn);
      }

      if (options.delayOut) {
        setTimeout(function () {
          $el.fadeOut();
        }, options.delayOut + (options.delayIn || 0));
      }
    })($el, options);
  });
  $('body').on('click', '.learn-press-nav-tabs li a', function (e) {
    e.preventDefault();
    var $tab = $(this),
        url = '';
    $tab.closest('li').addClass('active').siblings().removeClass('active');
    $($tab.attr('data-tab')).addClass('active').siblings().removeClass('active');
    $(document).trigger('learn-press/nav-tabs/clicked', $tab);
  });
  setTimeout(function () {
    $('.learn-press-nav-tabs li.active:not(.default) a').trigger('click');
  }, 300);
  $('body.course-item-popup').parent().css('overflow', 'hidden');

  (function () {
    var timer = null,
        callback = function callback() {
      $('.auto-check-lines').checkLines(function (r) {
        if (r > 1) {
          $(this).removeClass('single-lines');
        } else {
          $(this).addClass('single-lines');
        }

        $(this).attr('rows', r);
      });
    };

    $(window).on('resize.check-lines', function () {
      if (timer) {
        timer && clearTimeout(timer);
        timer = setTimeout(callback, 300);
      } else {
        callback();
      }
    });
  })();

  $('.learn-press-tooltip, .lp-passing-conditional').LP_Tooltip({
    offset: [24, 24]
  });
  $('.learn-press-icon').LP_Tooltip({
    offset: [30, 30]
  });
  $('.learn-press-message[data-autoclose]').each(function () {
    var $el = $(this),
        delay = parseInt($el.data('autoclose'));

    if (delay) {
      setTimeout(function ($el) {
        $el.fadeOut();
      }, delay, $el);
    }
  });
  $(document).on('click', function () {
    $(document).trigger('learn-press/close-all-quick-tip');
  });
});
Object(_extend__WEBPACK_IMPORTED_MODULE_0__["default"])(_objectSpread({
  Event_Callback: _event_callback__WEBPACK_IMPORTED_MODULE_4__["default"],
  MessageBox: _message_box__WEBPACK_IMPORTED_MODULE_3__["default"]
}, _default));
/* harmony default export */ __webpack_exports__["default"] = ({
  fn: _fn__WEBPACK_IMPORTED_MODULE_1__["default"],
  QuickTip: _quick_tip__WEBPACK_IMPORTED_MODULE_2___default.a
});

/***/ }),

/***/ "./assets/src/js/utils/jquery.plugins.js":
/*!***********************************************!*\
  !*** ./assets/src/js/utils/jquery.plugins.js ***!
  \***********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

var $ = window.jQuery;

var serializeJSON = function serializeJSON(path) {
  var isInput = $(this).is('input') || $(this).is('select') || $(this).is('textarea');
  var unIndexed = isInput ? $(this).serializeArray() : $(this).find('input, select, textarea').serializeArray(),
      indexed = {},
      validate = /(\[([a-zA-Z0-9_-]+)?\]?)/g,
      arrayKeys = {},
      end = false;
  $.each(unIndexed, function () {
    var that = this,
        match = this.name.match(/^([0-9a-zA-Z_-]+)/);

    if (!match) {
      return;
    }

    var keys = this.name.match(validate),
        objPath = "indexed['" + match[0] + "']";

    if (keys) {
      if (_typeof(indexed[match[0]]) != 'object') {
        indexed[match[0]] = {};
      }

      $.each(keys, function (i, prop) {
        prop = prop.replace(/\]|\[/g, '');
        var rawPath = objPath.replace(/'|\[|\]/g, ''),
            objExp = '',
            preObjPath = objPath;

        if (prop == '') {
          if (arrayKeys[rawPath] == undefined) {
            arrayKeys[rawPath] = 0;
          } else {
            arrayKeys[rawPath]++;
          }

          objPath += "['" + arrayKeys[rawPath] + "']";
        } else {
          if (!isNaN(prop)) {
            arrayKeys[rawPath] = prop;
          }

          objPath += "['" + prop + "']";
        }

        try {
          if (i == keys.length - 1) {
            objExp = objPath + "=that.value;";
            end = true;
          } else {
            objExp = objPath + "={}";
            end = false;
          }

          var evalString = "" + "if( typeof " + objPath + " == 'undefined'){" + objExp + ";" + "}else{" + "if(end){" + "if(typeof " + preObjPath + "!='object'){" + preObjPath + "={};}" + objExp + "}" + "}";
          eval(evalString);
        } catch (e) {
          console.log('Error:' + e + "\n" + objExp);
        }
      });
    } else {
      indexed[match[0]] = this.value;
    }
  });

  if (path) {
    path = "['" + path.replace('.', "']['") + "']";
    var c = 'try{indexed = indexed' + path + '}catch(ex){console.log(c, ex);}';
    eval(c);
  }

  return indexed;
};

var LP_Tooltip = function LP_Tooltip(options) {
  options = $.extend({}, {
    offset: [0, 0]
  }, options || {});
  return $.each(this, function () {
    var $el = $(this),
        content = $el.data('content');

    if (!content || $el.data('LP_Tooltip') !== undefined) {
      return;
    }

    var $tooltip = null;
    $el.hover(function (e) {
      $tooltip = $('<div class="learn-press-tooltip-bubble"/>').html(content).appendTo($('body')).hide();
      var position = $el.offset();

      if ($.isArray(options.offset)) {
        var top = options.offset[1],
            left = options.offset[0];

        if ($.isNumeric(left)) {
          position.left += left;
        } else {}

        if ($.isNumeric(top)) {
          position.top += top;
        } else {}
      }

      $tooltip.css({
        top: position.top,
        left: position.left
      });
      $tooltip.fadeIn();
    }, function () {
      $tooltip && $tooltip.remove();
    });
    $el.data('tooltip', true);
  });
};

var hasEvent = function hasEvent(name) {
  var events = $(this).data('events');

  if (typeof events.LP == 'undefined') {
    return false;
  }

  for (i = 0; i < events.LP.length; i++) {
    if (events.LP[i].namespace == name) {
      return true;
    }
  }

  return false;
};

var dataToJSON = function dataToJSON() {
  var json = {};
  $.each(this[0].attributes, function () {
    var m = this.name.match(/^data-(.*)/);

    if (m) {
      json[m[1]] = this.value;
    }
  });
  return json;
};

var rows = function rows() {
  var h = $(this).height();
  var lh = $(this).css('line-height').replace("px", "");
  $(this).attr({
    height: h,
    'line-height': lh
  });
  return Math.floor(h / parseInt(lh));
};

var checkLines = function checkLines(p) {
  return this.each(function () {
    var $e = $(this),
        rows = $e.rows();
    p.call(this, rows);
  });
};

var findNext = function findNext(selector) {
  var $selector = $(selector),
      $root = this.first(),
      index = $selector.index($root),
      $next = $selector.eq(index + 1);
  return $next.length ? $next : false;
};

var findPrev = function findPrev(selector) {
  var $selector = $(selector),
      $root = this.first(),
      index = $selector.index($root),
      $prev = $selector.eq(index - 1);
  return $prev.length ? $prev : false;
};

var progress = function progress(v) {
  return this.each(function () {
    var t = parseInt(v / 100 * 360),
        timer = null,
        $this = $(this);

    if (t < 180) {
      $this.find('.progress-circle').removeClass('gt-50');
    } else {
      $this.find('.progress-circle').addClass('gt-50');
    }

    $this.find('.fill').css({
      transform: 'rotate(' + t + 'deg)'
    });
  });
};

$.fn.serializeJSON = serializeJSON;
$.fn.LP_Tooltip = LP_Tooltip;
$.fn.hasEvent = hasEvent;
$.fn.dataToJSON = dataToJSON;
$.fn.rows = rows;
$.fn.checkLines = checkLines;
$.fn.findNext = findNext;
$.fn.findPrev = findPrev;
$.fn.progress = progress;
/* harmony default export */ __webpack_exports__["default"] = ({
  serializeJSON: serializeJSON,
  LP_Tooltip: LP_Tooltip,
  hasEvent: hasEvent,
  dataToJSON: dataToJSON,
  rows: rows,
  checkLines: checkLines,
  findNext: findNext,
  findPrev: findPrev,
  progress: progress
});

/***/ }),

/***/ "./assets/src/js/utils/message-box.js":
/*!********************************************!*\
  !*** ./assets/src/js/utils/message-box.js ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var $ = window.jQuery;
var MessageBox = {
  /*
   *
   */
  $block: null,
  $window: null,
  events: {},
  instances: [],
  instance: null,
  quickConfirm: function quickConfirm(elem, args) {
    var $e = $(elem);
    $('[learn-press-quick-confirm]').each(function () {
      var $ins;
      ($ins = $(this).data('quick-confirm')) && (console.log($ins), $ins.destroy());
    });
    !$e.attr('learn-press-quick-confirm') && $e.attr('learn-press-quick-confirm', 'true').data('quick-confirm', new function (elem, args) {
      var $elem = $(elem),
          $div = $('<span class="learn-press-quick-confirm"></span>').insertAfter($elem),
          //($(document.body)),
      offset = $(elem).position() || {
        left: 0,
        top: 0
      },
          timerOut = null,
          timerHide = null,
          n = 3,
          hide = function hide() {
        $div.fadeOut('fast', function () {
          $(this).remove();
          $div.parent().css('position', '');
        });
        $elem.removeAttr('learn-press-quick-confirm').data('quick-confirm', undefined);
        stop();
      },
          stop = function stop() {
        timerHide && clearInterval(timerHide);
        timerOut && clearInterval(timerOut);
      },
          start = function start() {
        timerOut = setInterval(function () {
          if (--n == 0) {
            hide.call($div[0]);
            $.isFunction(args.onCancel) && args.onCancel(args.data);
            stop();
          }

          $div.find('span').html(' (' + n + ')');
        }, 1000);
        timerHide = setInterval(function () {
          if (!$elem.is(':visible') || $elem.css("visibility") == 'hidden') {
            stop();
            $div.remove();
            $div.parent().css('position', '');
            $.isFunction(args.onCancel) && args.onCancel(args.data);
          }
        }, 350);
      };

      args = $.extend({
        message: '',
        data: null,
        onOk: null,
        onCancel: null,
        offset: {
          top: 0,
          left: 0
        }
      }, args || {});
      $div.html(args.message || $elem.attr('data-confirm-remove') || 'Are you sure?').append('<span> (' + n + ')</span>').css({});
      $div.click(function () {
        $.isFunction(args.onOk) && args.onOk(args.data);
        hide();
      }).hover(function () {
        stop();
      }, function () {
        start();
      }); //$div.parent().css('position', 'relative');

      $div.css({
        left: offset.left + $elem.outerWidth() - $div.outerWidth() + args.offset.left,
        top: offset.top + $elem.outerHeight() + args.offset.top + 5
      }).hide().fadeIn('fast');
      start();

      this.destroy = function () {
        $div.remove();
        $elem.removeAttr('learn-press-quick-confirm').data('quick-confirm', undefined);
        stop();
      };
    }(elem, args));
  },
  show: function show(message, args) {
    //this.hide();
    $.proxy(function () {
      args = $.extend({
        title: '',
        buttons: '',
        events: false,
        autohide: false,
        message: message,
        data: false,
        id: LP.uniqueId(),
        onHide: null
      }, args || {});
      this.instances.push(args);
      this.instance = args;
      var $doc = $(document),
          $body = $(document.body);

      if (!this.$block) {
        this.$block = $('<div id="learn-press-message-box-block"></div>').appendTo($body);
      }

      if (!this.$window) {
        this.$window = $('<div id="learn-press-message-box-window"><div id="message-box-wrap"></div> </div>').insertAfter(this.$block);
        this.$window.click(function () {});
      } //this.events = args.events || {};


      this._createWindow(message, args.title, args.buttons);

      this.$block.show();
      this.$window.show().attr('instance', args.id);
      $(window).bind('resize.message-box', $.proxy(this.update, this)).bind('scroll.message-box', $.proxy(this.update, this));
      this.update(true);

      if (args.autohide) {
        setTimeout(function () {
          LP.MessageBox.hide();
          $.isFunction(args.onHide) && args.onHide.call(LP.MessageBox, args);
        }, args.autohide);
      }
    }, this)();
  },
  blockUI: function blockUI(message) {
    message = (message !== false ? message ? message : 'Wait a moment' : '') + '<div class="message-box-animation"></div>';
    this.show(message);
  },
  hide: function hide(delay, instance) {
    if (instance) {
      this._removeInstance(instance.id);
    } else if (this.instance) {
      this._removeInstance(this.instance.id);
    }

    if (this.instances.length === 0) {
      if (this.$block) {
        this.$block.hide();
      }

      if (this.$window) {
        this.$window.hide();
      }

      $(window).unbind('resize.message-box', this.update).unbind('scroll.message-box', this.update);
    } else {
      if (this.instance) {
        this._createWindow(this.instance.message, this.instance.title, this.instance.buttons);
      }
    }
  },
  update: function update(force) {
    var that = this,
        $wrap = this.$window.find('#message-box-wrap'),
        timer = $wrap.data('timer'),
        _update = function _update() {
      LP.Hook.doAction('learn_press_message_box_before_resize', that);
      var $content = $wrap.find('.message-box-content').css("height", "").css('overflow', 'hidden'),
          width = $wrap.outerWidth(),
          height = $wrap.outerHeight(),
          contentHeight = $content.height(),
          windowHeight = $(window).height(),
          top = $wrap.offset().top;

      if (contentHeight > windowHeight - 50) {
        $content.css({
          height: windowHeight - 25
        });
        height = $wrap.outerHeight();
      } else {
        $content.css("height", "").css('overflow', '');
      }

      $wrap.css({
        marginTop: ($(window).height() - height) / 2
      });
      LP.Hook.doAction('learn_press_message_box_resize', height, that);
    };

    if (force) _update();
    timer && clearTimeout(timer);
    timer = setTimeout(_update, 250);
  },
  _removeInstance: function _removeInstance(id) {
    for (var i = 0; i < this.instances.length; i++) {
      if (this.instances[i].id === id) {
        this.instances.splice(i, 1);
        var len = this.instances.length;

        if (len) {
          this.instance = this.instances[len - 1];
          this.$window.attr('instance', this.instance.id);
        } else {
          this.instance = false;
          this.$window.removeAttr('instance');
        }

        break;
      }
    }
  },
  _getInstance: function _getInstance(id) {
    for (var i = 0; i < this.instances.length; i++) {
      if (this.instances[i].id === id) {
        return this.instances[i];
      }
    }
  },
  _createWindow: function _createWindow(message, title, buttons) {
    var $wrap = this.$window.find('#message-box-wrap').html('');

    if (title) {
      $wrap.append('<h3 class="message-box-title">' + title + '</h3>');
    }

    $wrap.append($('<div class="message-box-content"></div>').html(message));

    if (buttons) {
      var $buttons = $('<div class="message-box-buttons"></div>');

      switch (buttons) {
        case 'yesNo':
          $buttons.append(this._createButton(LP_Settings.localize.button_yes, 'yes'));
          $buttons.append(this._createButton(LP_Settings.localize.button_no, 'no'));
          break;

        case 'okCancel':
          $buttons.append(this._createButton(LP_Settings.localize.button_ok, 'ok'));
          $buttons.append(this._createButton(LP_Settings.localize.button_cancel, 'cancel'));
          break;

        default:
          $buttons.append(this._createButton(LP_Settings.localize.button_ok, 'ok'));
      }

      $wrap.append($buttons);
    }
  },
  _createButton: function _createButton(title, type) {
    var $button = $('<button type="button" class="button message-box-button message-box-button-' + type + '">' + title + '</button>'),
        callback = 'on' + (type.substr(0, 1).toUpperCase() + type.substr(1));
    $button.data('callback', callback).click(function () {
      var instance = $(this).data('instance'),
          callback = instance.events[$(this).data('callback')];

      if ($.type(callback) === 'function') {
        if (callback.apply(LP.MessageBox, [instance]) === false) {// return;
        } else {
          LP.MessageBox.hide(null, instance);
        }
      } else {
        LP.MessageBox.hide(null, instance);
      }
    }).data('instance', this.instance);
    return $button;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (MessageBox);

/***/ }),

/***/ "./assets/src/js/utils/quick-tip.js":
/*!******************************************!*\
  !*** ./assets/src/js/utils/quick-tip.js ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

;

(function ($) {
  function QuickTip(el, options) {
    var $el = $(el),
        uniId = $el.attr('data-id') || LP.uniqueId();
    options = $.extend({
      event: 'hover',
      autoClose: true,
      single: true,
      closeInterval: 1000,
      arrowOffset: null,
      tipClass: ''
    }, options, $el.data());
    $el.attr('data-id', uniId);
    var content = $el.attr('data-content-tip') || $el.html(),
        $tip = $('<div class="learn-press-tip-floating">' + content + '</div>'),
        t = null,
        closeInterval = 0,
        useData = false,
        arrowOffset = options.arrowOffset === 'el' ? $el.outerWidth() / 2 : 8,
        $content = $('#__' + uniId);

    if ($content.length === 0) {
      $(document.body).append($('<div />').attr('id', '__' + uniId).html(content).css('display', 'none'));
    }

    content = $content.html();
    $tip.addClass(options.tipClass);
    $el.data('content-tip', content);

    if ($el.attr('data-content-tip')) {
      //$el.removeAttr('data-content-tip');
      useData = true;
    }

    closeInterval = options.closeInterval;

    if (options.autoClose === false) {
      $tip.append('<a class="close"></a>');
      $tip.on('click', '.close', function () {
        close();
      });
    }

    function show() {
      if (t) {
        clearTimeout(t);
        return;
      }

      if (options.single) {
        $('.learn-press-tip').not($el).LP('QuickTip', 'close');
      }

      $tip.appendTo(document.body);
      var pos = $el.offset();
      $tip.css({
        top: pos.top - $tip.outerHeight() - 8,
        left: pos.left - $tip.outerWidth() / 2 + arrowOffset
      });
    }

    function hide() {
      t && clearTimeout(t);
      t = setTimeout(function () {
        $tip.detach();
        t = null;
      }, closeInterval);
    }

    function close() {
      closeInterval = 0;
      hide();
      closeInterval = options.closeInterval;
    }

    function open() {
      show();
    }

    if (!useData) {
      $el.html('');
    }

    if (options.event === 'click') {
      $el.on('click', function (e) {
        e.stopPropagation();
        show();
      });
    }

    $(document).on('learn-press/close-all-quick-tip', function () {
      close();
    });
    $el.hover(function (e) {
      e.stopPropagation();

      if (options.event !== 'click') {
        show();
      }
    }, function (e) {
      e.stopPropagation();

      if (options.autoClose) {
        hide();
      }
    }).addClass('ready');
    return {
      close: close,
      open: open
    };
  }

  $.fn.LP('QuickTip', function (options) {
    return $.each(this, function () {
      var $tip = $(this).data('quick-tip');

      if (!$tip) {
        $tip = new QuickTip(this, options);
        $(this).data('quick-tip', $tip);
      }

      if ($.type(options) === 'string') {
        $tip[options] && $tip[options].apply($tip);
      }
    });
  });
})(jQuery);

/***/ })

/******/ });