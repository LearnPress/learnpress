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
/******/ ({

/***/ 2:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _fn = __webpack_require__(3);

var _fn2 = _interopRequireDefault(_fn);

var _quickTip = __webpack_require__(4);

var _quickTip2 = _interopRequireDefault(_quickTip);

var _jquery = __webpack_require__(57);

var jplugins = _interopRequireWildcard(_jquery);

function _interopRequireWildcard(obj) { if (obj && obj.__esModule) { return obj; } else { var newObj = {}; if (obj != null) { for (var key in obj) { if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key]; } } newObj.default = obj; return newObj; } }

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

exports.default = {
  fn: _fn2.default,
  QuickTip: _quickTip2.default
}; /**
    * Utility functions may use for both admin and frontend.
    *
    * @version 3.x.x
    */

/***/ }),

/***/ 3:
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

/***/ 4:
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

/***/ 57:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

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

        console.log(content);
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
    $(this).attr({ height: h, 'line-height': lh });
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

exports.default = {
    serializeJSON: serializeJSON,
    LP_Tooltip: LP_Tooltip,
    hasEvent: hasEvent,
    dataToJSON: dataToJSON,
    rows: rows,
    checkLines: checkLines,
    findNext: findNext,
    findPrev: findPrev,
    progress: progress
};

/***/ })

/******/ });
//# sourceMappingURL=utils.js.map