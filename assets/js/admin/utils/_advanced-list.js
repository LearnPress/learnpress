;(function ($) {
    var advancedList = function (el, options) {
        var self = this,
            $el = $(el);

        this.options = $.extend({
            template: '<li data-id="{{id}}"><span class="remove-item"></span><span>{{text}}</span> </li>'
        }, options || {})
        function _remove(e) {
            e.preventDefault();
            remove($el.children().index($(this).closest('li')) + 1)
        }

        function _add(e) {

        }

        function remove(at) {
            $el.children(':eq(' + (at - 1) + ')').remove();
        }

        function add(data, at) {
            var options = {},
                template = self.options.template;
            if ($.isPlainObject(data)) {
                options = $.extend({id: 0, text: ''}, data)
            } else if (typeof data === 'string') {
                options = {
                    id: '',
                    text: data
                }
            } else if (data[0] !== undefined) {
                options = {
                    id: data[1] ? data[1] : '',
                    text: data[0]
                }
            }
            for (var prop in options) {
                template = template.replace('{{' + prop + '}}', options[prop]);
            }
            template = $("\n"+template+"\n");
            if (at !== undefined) {
                var $e = $el.children(':eq(' + (at - 1) + ')');
                if ($e.length) {
                    template.insertBefore($e);
                } else {
                    $el.append(template)
                }
            } else {
                $el.append(template)
            }
            var $child = $el.children().detach();
            $child.each(function () {
                $el.append("\n").append(this);
            })
        }

        this.add = add;
        this.remove = remove;
        $el.on('click', '.remove-item', _remove);
    }
    $.fn.advancedList = function (options) {
        var args = [];
        for (var i = 1; i < arguments.length; i++) {
            args.push(arguments[i]);
        }
        return $.each(this, function () {
            var $advancedList = $(this).data('advancedList');
            if (!$advancedList) {
                $advancedList = new advancedList(this, options);
                $(this).data('advancedList', $advancedList);
            }

            if (typeof options === 'string') {
                if ($.isFunction($advancedList[options])) {
                    return $advancedList[options].apply($advancedList, args);
                }
            }
            return this;
        })
    }
})(jQuery);