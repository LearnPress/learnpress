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
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
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
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 14);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */,
/* 1 */,
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _fn = __webpack_require__(3);

var _fn2 = _interopRequireDefault(_fn);

var _quickTip = __webpack_require__(4);

var _quickTip2 = _interopRequireDefault(_quickTip);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * Utility functions may use for both admin and frontend.
 *
 * @version 3.x.x
 */

exports.default = {
  fn: _fn2.default,
  QuickTip: _quickTip2.default
};

/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});
/**
 * Auto prepend `LP` prefix for jQuery fn plugin name.
 *
 * Create : $.fn.LP( 'PLUGIN_NAME', func) <=> $.fn.LP_PLUGIN_NAME
 * Usage: $(selector).LP('PLUGIN_NAME') <=> $(selector).LP_PLUGIN_NAME()
 *
 * @version 3.x.x
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

exports.default = exp;

/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


;(function ($) {
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

/***/ }),
/* 5 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = isEmail;
/**
 * Validate is an email.
 *
 * @param email
 * @return {boolean}
 */
function isEmail(email) {
  var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email);
};

/***/ }),
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Conditional Logic for metabox fields
 *
 * @author ThimPress
 * @package LearnPress/JS
 * @version 3.0.0
 */
;(function ($) {
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
            new Conditional_Logic({ conditionals: lp_conditional_logic });
        }
    });
})(jQuery);

/***/ }),
/* 7 */,
/* 8 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

exports.default = function () {
    window.LP = window.LP || {};

    if (typeof arguments[0] === 'string') {
        LP[arguments[0]] = LP[arguments[0]] || {};
        LP[arguments[0]] = jQuery.extend(LP[arguments[0]], arguments[1]);
    } else {
        LP = jQuery.extend(LP, arguments[0]);
    }
};

/***/ }),
/* 9 */,
/* 10 */,
/* 11 */,
/* 12 */,
/* 13 */,
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; // global utilities


// local utilities


var _utils = __webpack_require__(2);

var Utils = _interopRequireWildcard(_utils);

var _adminNotice = __webpack_require__(15);

var _adminNotice2 = _interopRequireDefault(_adminNotice);

var _debugLog = __webpack_require__(16);

var _debugLog2 = _interopRequireDefault(_debugLog);

var _advertisement = __webpack_require__(17);

var _advertisement2 = _interopRequireDefault(_advertisement);

var _dropdownPages = __webpack_require__(18);

var _dropdownPages2 = _interopRequireDefault(_dropdownPages);

var _advancedList = __webpack_require__(19);

var _advancedList2 = _interopRequireDefault(_advancedList);

var _adminTabs = __webpack_require__(20);

var _adminTabs2 = _interopRequireDefault(_adminTabs);

var _emailValidator = __webpack_require__(5);

var _emailValidator2 = _interopRequireDefault(_emailValidator);

var _modalSearchUsers = __webpack_require__(21);

var _modalSearchUsers2 = _interopRequireDefault(_modalSearchUsers);

var _modalSearchItems = __webpack_require__(22);

var _modalSearchItems2 = _interopRequireDefault(_modalSearchItems);

var _searchItems = __webpack_require__(23);

var _searchItems2 = _interopRequireDefault(_searchItems);

var _conditionalLogic = __webpack_require__(6);

var _conditionalLogic2 = _interopRequireDefault(_conditionalLogic);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _interopRequireWildcard(obj) { if (obj && obj.__esModule) { return obj; } else { var newObj = {}; if (obj != null) { for (var key in obj) { if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key]; } } newObj.default = obj; return newObj; } }

exports.default = _extends({}, Utils, {
    dismissNotice: _adminNotice2.default,
    Debug: _debugLog2.default,
    Advertisement: _advertisement2.default,
    DropdownPages: _dropdownPages2.default,
    AdvancedList: _advancedList2.default,
    AdminTabs: _adminTabs2.default,
    isEmail: _emailValidator2.default,
    ModalSearchItems: _modalSearchItems2.default,
    ModalSearchUsers: _modalSearchUsers2.default
});

/***/ }),
/* 15 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _extend = __webpack_require__(8);

var _extend2 = _interopRequireDefault(_extend);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var $ = window.jQuery;

var dismissNotice = function dismissNotice(notice, options) {
    var hooks = wp.hooks;

    options = $.extend({ el: null }, options || {});

    hooks && hooks.doAction('before-dismiss-notice', 'LP', notice, options);
    $.ajax({
        url: '',
        data: $.extend({ 'lp-dismiss-notice': notice }, options.data || {}),
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

        dismissNotice(notice, { data: data, el: $(this).closest('.lp-notice') });
    });
})(window.jQuery);

(0, _extend2.default)('Utils', { dismissNotice: dismissNotice });

exports.default = dismissNotice;

/***/ }),
/* 16 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _extend = __webpack_require__(8);

var _extend2 = _interopRequireDefault(_extend);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var isDebugMode = function isDebugMode() {
    var uri = window.location.href;

    if (uri.match(/debug=true/)) {
        return true;
    }

    if (window['LP_DEBUG'] !== undefined) {
        return !!LP_DEBUG;
    }

    return !!window.location.href.match(/localhost/);
};

var log = function log() {
    if (!isDebugMode()) {
        return;
    }

    console.log.apply(null, arguments);
};

var _export = { isDebugMode: isDebugMode, log: log };

(0, _extend2.default)('Utils', _export);
exports.default = _export;

/***/ }),
/* 17 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


;(function ($) {
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

            activeItem.show();
            // A ha???
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
/* 18 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


;(function () {
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
        });

        // Select 2
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
                    page_name: page_name
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
/* 19 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


;(function ($) {
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
                options = $.extend({ id: 0, text: '' }, data);
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
            }

            // Replace placeholders with related variables
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
            }

            // Append "\n" between li elements
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

        $el.on('click', '.remove-item', _remove);
        // export
        this.add = add;
        this.remove = remove;
    };

    // Export
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
            }

            // Try to calling to methods of class
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
/* 20 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Plugin: Tabs
 */
;(function ($) {
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
            $currentContent.show().css({ visibility: 'hidden' });
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
            $currentContent.css('visibility', '').css({ height: contentHeight });
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
/* 21 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


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
                    pos = _.findLastIndex(this.selected, { id: id });
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
/* 22 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


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
/* 23 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


;(function ($) {
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

/***/ })
/******/ ]);
//# sourceMappingURL=utils.js.map