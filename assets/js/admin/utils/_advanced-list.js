;(function ($) {
    /**
     * Advanced list.
     *
     * @param el
     * @param options
     */
    var advancedList = function (el, options) {
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
            remove($el.children().index($(this).closest('li')) + 1)
        }

        /**
         *
         * @param e
         * @private
         */
        function _add(e) {

        }

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

            // Replace placeholders with related variables
            for (var prop in options) {
                var reg = new RegExp('\{\{' + prop + '\}\}', 'g')
                template = template.replace(reg, options[prop]);
            }

            template = $(template);

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
    }

    // Export
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

            // Try to calling to methods of class
            if (typeof options === 'string') {
                if ($.isFunction($advancedList[options])) {
                    return $advancedList[options].apply($advancedList, args);
                }
            }
            return this;
        })
    }
})(jQuery);