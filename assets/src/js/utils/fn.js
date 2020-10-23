/**
 * Auto prepend `LP` prefix for jQuery fn plugin name.
 *
 * Create : $.fn.LP( 'PLUGIN_NAME', func) <=> $.fn.LP_PLUGIN_NAME
 * Usage: $(selector).LP('PLUGIN_NAME') <=> $(selector).LP_PLUGIN_NAME()
 *
 * @version 3.2.6
 */

const $ = window.jQuery;
var exp;

(function () {

    if ($ === undefined) {
        return;
    }

    $.fn.LP = exp = function (widget, fn) {
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
        return this
    };
})();

export default exp;