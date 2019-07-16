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
/******/ 	return __webpack_require__(__webpack_require__.s = 2);
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

/***/ })
/******/ ]);
//# sourceMappingURL=utils.js.map