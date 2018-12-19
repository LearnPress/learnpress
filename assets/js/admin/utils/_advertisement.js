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
                $(this).append($controls.clone())
            })
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
            }, 500)
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

    $.fn.LP_Advertisement_Slider = function (opts) {
        return $.each(this, function () {
            var $slider = $(this).data('LP_Advertisement_Slider');
            if (!$slider) {
                $slider = new LP_Advertisement_Slider(this, opts);
                $(this).data('LP_Advertisement_Slider', $slider);
            }
            return this;
        })
    };
})(jQuery);

