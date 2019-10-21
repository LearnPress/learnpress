/**
 * Utility functions may use for both admin and frontend.
 *
 * @version 3.2.6
 */

import extend from './extend';
import fn from './fn';
import QuickTip from './quick-tip';
import MessageBox from './message-box';
import Event_Callback from './event-callback';
import Hook from './hook';
import Cookies from './cookies';
import _localStorage from './local-storage';
import jQueryScrollbar from '../vendor/jquery/jquery.scrollbar';
import * as jplugins from './jquery.plugins';

const $ = jQuery;

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
        if ((url.indexOf('&' + name + '=') != -1) || (url.indexOf('?' + name + '=') != -1)) {
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

const _default = {
    Hook: Hook,
    setUrl: function (url, ember, title) {
        if (url) {
            history.pushState({}, title, url);
            LP.Hook.doAction('learn_press_set_location_url', url);
        }
    },
    toggleGroupSection: function (el, target) {
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
    overflow: function (el, v) {
        var $el = $(el),
            overflow = $el.css('overflow');
        if (v) {
            $el.css('overflow', v).data('overflow', overflow);
        } else {
            $el.css('overflow', $el.data('overflow'));
        }
    },
    getUrl: function () {
        return window.location.href;
    },
    addQueryVar: function (name, value, url) {
        return (url === undefined ? window.location.href : url).addQueryVar(name, value);
    },
    removeQueryVar: function (name, url) {
        return (url === undefined ? window.location.href : url).removeQueryVar(name);
    },
    reload: function (url) {
        if (!url) {
            url = window.location.href;
        }
        window.location.href = url;
    },

    parseResponse: function (response, type) {
        var m = response.match(/<-- LP_AJAX_START -->(.*)<-- LP_AJAX_END -->/);
        if (m) {
            response = m[1];
        }
        return (type || "json") === "json" ? this.parseJSON(response) : response;
    },
    parseJSON: function (data) {
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
    ajax: function (args) {
        var type = args.type || 'post',
            dataType = args.dataType || 'json',
            data = args.action ? $.extend(args.data, {'lp-ajax': args.action}) : args.data,
            beforeSend = args.beforeSend || function () {
                },
            url = args.url || window.location.href;
//                        console.debug( beforeSend );
        $.ajax({
            data: data,
            url: url,
            type: type,
            dataType: 'html',
            beforeSend: beforeSend.apply(null, args),
            success: function (raw) {
                var response = LP.parseResponse(raw, dataType);
                $.isFunction(args.success) && args.success(response, raw);
            },
            error: function () {
                $.isFunction(args.error) && args.error.apply(null, LP.funcArgs2Array());
            }
        });
    },
    doAjax: function (args) {
        var type = args.type || 'post',
            dataType = args.dataType || 'json',
            action = ((args.prefix === undefined) || 'learnpress_') + args.action,
            data = args.action ? $.extend(args.data, {action: action}) : args.data;

        $.ajax({
            data: data,
            url: (args.url || window.location.href),
            type: type,
            dataType: 'html',
            success: function (raw) {
                var response = LP.parseResponse(raw, dataType);
                $.isFunction(args.success) && args.success(response, raw);
            },
            error: function () {
                $.isFunction(args.error) && args.error.apply(null, LP.funcArgs2Array());
            }
        });
    },

    funcArgs2Array: function (args) {
        var arr = [];
        for (var i = 0; i < args.length; i++) {
            arr.push(args[i]);
        }
        return arr;
    },
    addFilter: function (action, callback) {
        var $doc = $(document),
            event = 'LP.' + action;
        $doc.on(event, callback);
        LP.log($doc.data('events'));
        return this;
    },
    applyFilters: function () {
        var $doc = $(document),
            action = arguments[0],
            args = this.funcArgs2Array(arguments);
        if ($doc.hasEvent(action)) {
            args[0] = 'LP.' + action;
            return $doc.triggerHandler.apply($doc, args);
        }
        return args[1];
    },
    addAction: function (action, callback) {
        return this.addFilter(action, callback);
    },
    doAction: function () {
        var $doc = $(document),
            action = arguments[0],
            args = this.funcArgs2Array(arguments);
        if ($doc.hasEvent(action)) {
            args[0] = 'LP.' + action;
            $doc.trigger.apply($doc, args);
        }
    },
    toElement: function (element, args) {
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
        var to = ($(element).offset().top + $container.scrollTop()) - rootTop - args.offset;

        function isElementInView(element, fullyInView) {
            var pageTop = $container.scrollTop();
            var pageBottom = pageTop + $container.height();
            var elementTop = $(element).offset().top - $container.offset().top;
            var elementBottom = elementTop + $(element).height();

            if (fullyInView === true) {
                return ((pageTop < elementTop) && (pageBottom > elementBottom));
            } else {
                return ((elementTop <= pageBottom) && (elementBottom >= pageTop));
            }
        }

        if (args.invisible && isElementInView(element, true)) {
            return;
        }
        $container.fadeIn(10)
            .delay(args.delay)
            .animate({
                scrollTop: to
            }, args.duration, args.callback);
    },
    uniqueId: function (prefix, more_entropy) {
        if (typeof prefix === 'undefined') {
            prefix = '';
        }

        var retId;
        var formatSeed = function (seed, reqWidth) {
            seed = parseInt(seed, 10)
                .toString(16); // to hex str
            if (reqWidth < seed.length) { // so long we split
                return seed.slice(seed.length - reqWidth);
            }
            if (reqWidth > seed.length) { // so short we pad
                return new Array(1 + (reqWidth - seed.length))
                        .join('0') + seed;
            }
            return seed;
        };

        // BEGIN REDUNDANT
        if (!this.php_js) {
            this.php_js = {};
        }
        // END REDUNDANT
        if (!this.php_js.uniqidSeed) { // init seed with big random int
            this.php_js.uniqidSeed = Math.floor(Math.random() * 0x75bcd15);
        }
        this.php_js.uniqidSeed++;

        retId = prefix; // start with prefix, add current milliseconds hex string
        retId += formatSeed(parseInt(new Date()
                .getTime() / 1000, 10), 8);
        retId += formatSeed(this.php_js.uniqidSeed, 5); // add seed hex string
        if (more_entropy) {
            // for more entropy we add a float lower to 10
            retId += (Math.random() * 10)
                .toFixed(8)
                .toString();
        }

        return retId;
    },
    log: function () {
        //if (typeof LEARN_PRESS_DEBUG != 'undefined' && LEARN_PRESS_DEBUG && console) {
        for (var i = 0, n = arguments.length; i < n; i++) {
            console.log(arguments[i]);
        }
        //}
    },
    blockContent: function () {
        if ($('#learn-press-block-content').length === 0) {
            $(LP.template('learn-press-template-block-content', {})).appendTo($('body'));
        }
        LP.hideMainScrollbar().addClass('block-content');
        $(document).trigger('learn_press_block_content');
    },
    unblockContent: function () {
        setTimeout(function () {
            LP.showMainScrollbar().removeClass('block-content');
            $(document).trigger('learn_press_unblock_content');
        }, 350);
    },
    hideMainScrollbar: function (el) {
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
    showMainScrollbar: function (el) {
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

        var tmpl = function (data) {
            compiled = compiled || _.template($('#' + id).html(), null, options);
            return compiled(data);
        };
        return data ? tmpl(data) : tmpl;
    }, function (a, b) {
        return a + '-' + JSON.stringify(b);
    }) : function () {
        return '';
    },
    alert: function (localize, callback) {
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
    confirm: function (localize, callback) {
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
    _on_alert_show: function () {
        var $container = $('#popup_container'),
            $placeholder = $('<span id="popup_container_placeholder" />').insertAfter($container).data('xxx', $container);
        $container.stop().css('top', '-=50').css('opacity', '0').animate({
            top: '+=50',
            opacity: 1
        }, 250);
    },
    _on_alert_hide: function () {
        var $holder = $("#popup_container_placeholder"),
            $container = $holder.data('xxx');
        if ($container) {
            $container.replaceWith($holder);
        }
        $container.appendTo($(document.body))
        $container.stop().animate({
            top: '+=50',
            opacity: 0
        }, 250, function () {
            $(this).remove();
        });
    },
    sendMessage: function (data, object, targetOrigin, transfer) {
        if ($.isPlainObject(data)) {
            data = JSON.stringify(data);
        }
        object = object || window;
        targetOrigin = targetOrigin || '*';
        object.postMessage(data, targetOrigin, transfer);
    },
    receiveMessage: function (event, b) {
        var target = event.origin || event.originalEvent.origin,
            data = event.data || event.originalEvent.data || '';
        if (typeof data === 'string' || data instanceof String) {
            if (data.indexOf('{') === 0) {
                data = LP.parseJSON(data);
            }
        }
        LP.Hook.doAction('learn_press_receive_message', data, target);
    },

    camelCaseDashObjectKeys: function (obj, deep = true) {
        const self = LP;
        const isArray = function (a) {
            return Array.isArray(a);
        };
        const isObject = function (o) {
            return o === Object(o) && !isArray(o) && typeof o !== 'function';
        };
        const toCamel = (s) => {
            return s.replace(/([-_][a-z])/ig, ($1) => {
                return $1.toUpperCase()
                    .replace('-', '')
                    .replace('_', '');
            });
        };

        if (isObject(obj)) {
            const n = {};

            Object.keys(obj)
                .forEach((k) => {
                    n[toCamel(k)] = deep ? self.camelCaseDashObjectKeys(obj[k]) : obj[k];
                });

            return n;
        } else if (isArray(obj)) {
            return obj.map((i) => {
                return self.camelCaseDashObjectKeys(i);
            });
        }

        return obj;
    }
}

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

    // $('body')
    //     .on('click', '.learn-press-nav-tabs li a', function (e) {
    //         e.preventDefault();
    //         var $tab = $(this), url = '';
    //         $tab.closest('li').addClass('active').siblings().removeClass('active');
    //         $($tab.attr('data-tab')).addClass('active').siblings().removeClass('active');
    //         $(document).trigger('learn-press/nav-tabs/clicked', $tab);
    //     });

    setTimeout(function () {
        $('.learn-press-nav-tabs li.active:not(.default) a').trigger('click');
    }, 300);

    $('body.course-item-popup').parent().css('overflow', 'hidden');

    (function () {
        var timer = null,
            callback = function () {
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

    $('.learn-press-tooltip, .lp-passing-conditional').LP_Tooltip({offset: [24, 24]});

    $('.learn-press-icon').LP_Tooltip({offset: [30, 30]});

    $('.learn-press-message[data-autoclose]').each(function () {
        var $el = $(this), delay = parseInt($el.data('autoclose'));
        if (delay) {
            setTimeout(function ($el) {
                $el.fadeOut();
            }, delay, $el);
        }
    });

    $(document).on('click', function () {
        $(document).trigger('learn-press/close-all-quick-tip')
    })
});

extend({
    Event_Callback,
    MessageBox,
    Cookies,
    localStorage: _localStorage,
    ..._default
});

export default {
    fn,
    QuickTip,
    Cookies,
    localStorage: _localStorage
}