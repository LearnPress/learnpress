if (!Object.prototype.watchChange) {
    var isFunction = function (fn) {
        return fn && {}.toString.call(fn) === '[object Function]';
    };
    Object.defineProperty(
        Object.prototype,
        'watchChange',
        {
            enumerable: false,
            configurable: true,
            writable: false,
            value: function (prop, handler) {
                var obj = this;

                function x(prop, handler) {
                    var oldval = obj[prop],
                        newval = oldval,
                        getter = function () {
                            return newval;
                        },
                        setter = function (val) {
                            return newval = handler.call(obj, prop, oldval, val);
                        };

                    if (delete obj[prop]) {
                        Object.defineProperty(
                            obj,
                            prop,
                            {
                                get: getter,
                                set: setter,
                                enumerable: true,
                                configurable: true
                            }
                        );
                    }
                }

                if (isFunction(prop)) {
                    for (var k in this) {
                        new x(k, prop);
                    }
                } else {
                    new x(prop, handler)
                }
            }
        });
}

if (!Object.prototype.unwatchChange) {
    Object.defineProperty(
        Object.prototype,
        'unwatchChange',
        {
            enumerable: false,
            configurable: true,
            writable: false,
            value: function (prop) {
                var val = this[prop];
                delete this[prop];
                this[prop] = val;
            }
        }
    );
}
/*!
 * jQuery.scrollTo
 * Copyright (c) 2007-2015 Ariel Flesler - aflesler ○ gmail • com | http://flesler.blogspot.com
 * Licensed under MIT
 * http://flesler.blogspot.com/2007/10/jqueryscrollto.html
 * @projectDescription Lightweight, cross-browser and highly customizable animated scrolling with jQuery
 * @author Ariel Flesler
 * @version 2.1.2
 */
;(function(factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        // AMD
        define(['jquery'], factory);
    } else if (typeof module !== 'undefined' && module.exports) {
        // CommonJS
        module.exports = factory(require('jquery'));
    } else {
        // Global
        factory(jQuery);
    }
})(function($) {
    'use strict';

    var $scrollTo = $.scrollTo = function(target, duration, settings) {
        return $(window).scrollTo(target, duration, settings);
    };

    $scrollTo.defaults = {
        axis:'xy',
        duration: 0,
        limit:true
    };

    function isWin(elem) {
        return !elem.nodeName ||
            $.inArray(elem.nodeName.toLowerCase(), ['iframe','#document','html','body']) !== -1;
    }

    $.fn.scrollTo = function(target, duration, settings) {
        if (typeof duration === 'object') {
            settings = duration;
            duration = 0;
        }
        if (typeof settings === 'function') {
            settings = { onAfter:settings };
        }
        if (target === 'max') {
            target = 9e9;
        }

        settings = $.extend({}, $scrollTo.defaults, settings);
        // Speed is still recognized for backwards compatibility
        duration = duration || settings.duration;
        // Make sure the settings are given right
        var queue = settings.queue && settings.axis.length > 1;
        if (queue) {
            // Let's keep the overall duration
            duration /= 2;
        }
        settings.offset = both(settings.offset);
        settings.over = both(settings.over);

        return this.each(function() {
            // Null target yields nothing, just like jQuery does
            if (target === null) return;

            var win = isWin(this),
                elem = win ? this.contentWindow || window : this,
                $elem = $(elem),
                targ = target,
                attr = {},
                toff;

            switch (typeof targ) {
                // A number will pass the regex
                case 'number':
                case 'string':
                    if (/^([+-]=?)?\d+(\.\d+)?(px|%)?$/.test(targ)) {
                        targ = both(targ);
                        // We are done
                        break;
                    }
                    // Relative/Absolute selector
                    targ = win ? $(targ) : $(targ, elem);
                /* falls through */
                case 'object':
                    if (targ.length === 0) return;
                    // DOMElement / jQuery
                    if (targ.is || targ.style) {
                        // Get the real position of the target
                        toff = (targ = $(targ)).offset();
                    }
            }

            var offset = $.isFunction(settings.offset) && settings.offset(elem, targ) || settings.offset;

            $.each(settings.axis.split(''), function(i, axis) {
                var Pos	= axis === 'x' ? 'Left' : 'Top',
                    pos = Pos.toLowerCase(),
                    key = 'scroll' + Pos,
                    prev = $elem[key](),
                    max = $scrollTo.max(elem, axis);

                if (toff) {// jQuery / DOMElement
                    attr[key] = toff[pos] + (win ? 0 : prev - $elem.offset()[pos]);

                    // If it's a dom element, reduce the margin
                    if (settings.margin) {
                        attr[key] -= parseInt(targ.css('margin'+Pos), 10) || 0;
                        attr[key] -= parseInt(targ.css('border'+Pos+'Width'), 10) || 0;
                    }

                    attr[key] += offset[pos] || 0;

                    if (settings.over[pos]) {
                        // Scroll to a fraction of its width/height
                        attr[key] += targ[axis === 'x'?'width':'height']() * settings.over[pos];
                    }
                } else {
                    var val = targ[pos];
                    // Handle percentage values
                    attr[key] = val.slice && val.slice(-1) === '%' ?
                        parseFloat(val) / 100 * max
                        : val;
                }

                // Number or 'number'
                if (settings.limit && /^\d+$/.test(attr[key])) {
                    // Check the limits
                    attr[key] = attr[key] <= 0 ? 0 : Math.min(attr[key], max);
                }

                // Don't waste time animating, if there's no need.
                if (!i && settings.axis.length > 1) {
                    if (prev === attr[key]) {
                        // No animation needed
                        attr = {};
                    } else if (queue) {
                        // Intermediate animation
                        animate(settings.onAfterFirst);
                        // Don't animate this axis again in the next iteration.
                        attr = {};
                    }
                }
            });

            animate(settings.onAfter);

            function animate(callback) {
                var opts = $.extend({}, settings, {
                    // The queue setting conflicts with animate()
                    // Force it to always be true
                    queue: true,
                    duration: duration,
                    complete: callback && function() {
                        callback.call(elem, targ, settings);
                    }
                });
                $elem.animate(attr, opts);
            }
        });
    };

    // Max scrolling position, works on quirks mode
    // It only fails (not too badly) on IE, quirks mode.
    $scrollTo.max = function(elem, axis) {
        var Dim = axis === 'x' ? 'Width' : 'Height',
            scroll = 'scroll'+Dim;

        if (!isWin(elem))
            return elem[scroll] - $(elem)[Dim.toLowerCase()]();

        var size = 'client' + Dim,
            doc = elem.ownerDocument || elem.document,
            html = doc.documentElement,
            body = doc.body;

        return Math.max(html[scroll], body[scroll]) - Math.min(html[size], body[size]);
    };

    function both(val) {
        return $.isFunction(val) || $.isPlainObject(val) ? val : { top:val, left:val };
    }

    // Add special hooks so that window scroll properties can be animated
    $.Tween.propHooks.scrollLeft =
        $.Tween.propHooks.scrollTop = {
            get: function(t) {
                return $(t.elem)[t.prop]();
            },
            set: function(t) {
                var curr = this.get(t);
                // If interrupt is true and user scrolled, stop animating
                if (t.options.interrupt && t._last && t._last !== curr) {
                    return $(t.elem).stop();
                }
                var next = Math.round(t.now);
                // Don't waste CPU
                // Browsers don't render floating point scroll
                if (curr !== next) {
                    $(t.elem)[t.prop](next);
                    t._last = this.get(t);
                }
            }
        };

    // AMD requirement
    return $scrollTo;
});
(function (e) {
	e.backward_timer = function (t) {
		var n = {seconds: 5, step: 1, format: "h%:m%:s%", value_setter: undefined, on_exhausted: function (e) {
		}, on_tick      : function (e) {
		}}, r = this;
		r.seconds_left = 0;
		r.target = e(t);
		r.timeout = undefined;
		r.settings = {};
		r.methods = {init     : function (t) {
			r.settings = e.extend({}, n, t);
			if (r.settings.value_setter == undefined) {
				if (r.target.is("input")) {
					r.settings.value_setter = "val"
				} else {
					r.settings.value_setter = "text"
				}
			}
			r.methods.reset()
		}, start              : function () {
			if (r.timeout == undefined) {
				var e = r.seconds_left == r.settings.seconds ? 0 : r.settings.step * 1e3;
				setTimeout(r.methods._on_tick, e, e)
			}
		}, cancel             : function () {
			if (r.timeout != undefined) {
				clearTimeout(r.timeout);
				r.timeout = undefined
			}
		}, reset              : function () {
			r.seconds_left = r.settings.seconds;
			r.methods._render_seconds()
		}, _on_tick           : function (e) {
			if (e != 0) {
				r.settings.on_tick(r)
			}
			r.methods._render_seconds();
			if (r.seconds_left > 0) {
				if (r.seconds_left < r.settings.step) {
					var t = r.seconds_left
				} else {
					var t = r.settings.step
				}
				r.seconds_left -= t;
				var n = t * 1e3;
				r.timeout = setTimeout(r.methods._on_tick, n, n)
			} else {
				r.timeout = undefined;
				r.settings.on_exhausted(r)
			}
		}, _render_seconds    : function () {
			var e = r.methods._seconds_to_dhms(r.seconds_left), t = r.settings.format;
			if (t.indexOf("d%") !== -1) {
				t = t.replace("d%", e.d).replace("h%", r.methods._check_leading_zero(e.h))
			} else {
				t = t.replace("h%", e.d * 24 + e.h)
			}
			t = t.replace("m%", r.methods._check_leading_zero(e.m)).replace("s%", r.methods._check_leading_zero(e.s));
			r.target[r.settings.value_setter](t)
		}, _seconds_to_dhms   : function (e) {
			var t = Math.floor(e / (24 * 3600)), e = e - t * 24 * 3600, n = Math.floor(e / 3600), e = e - n * 3600, r = Math.floor(e / 60), i = Math.floor(e - r * 60);
			return{d: t, h: n, m: r, s: i}
		}, _check_leading_zero: function (e) {
			return e < 10 ? "0" + e : "" + e
		}}
	};
	e.fn.backward_timer = function (t) {
		var n = arguments;
		return this.each(function () {
			var r = e(this).data("backward_timer");
			if (r == undefined) {
				r = new e.backward_timer(this);
				e(this).data("backward_timer", r)
			}
			if (r.methods[t]) {
				return r.methods[t].apply(this, Array.prototype.slice.call(n, 1))
			} else if (typeof t === "object" || !t) {
				return r.methods.init.apply(this, n)
			} else {
				e.error("Method " + t + " does not exist on jQuery.backward_timer")
			}
		})
	}
})(jQuery);
