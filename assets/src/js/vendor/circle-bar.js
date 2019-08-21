;(function ($) {
    $.circleBar = function (el, options) {
        this.options = $.extend({
            value: 0
        }, options || {});

        var that = this,
            $bg = $(el),
            $bg50 = $(el).find('.before'),
            $bg100 = $(el).find('.after'),
            bgColor = '#DDD',
            activeColor = '#FF0000';

        function draw() {
            var deg = that.options.value * 360 / 100;
            $bg.removeClass('bg50 bg100')
            if (that.options.value <= 50) {
                $bg.addClass('bg50');
                $bg50.css('transform', 'rotate(' + (-135 + deg) + 'deg)');
            } else {
                $bg.addClass('bg100');
                $bg100.css('transform', 'rotate(' + (-135 + ((that.options.value - 50) * 180 / 50)) + 'deg)');
            }
        }

        draw();

        this.value = function (val) {
            if (val) {
                that.options.value = val;
                draw();
                return $bg;
            }

            return that.options.value;
        }
    }
    $.fn.circleBar = function (options, val) {
        if (typeof options === 'string') {
            var $circleBar = $(this).data('circleBar');
            if (!$circleBar) {
                return null;
            }
            if ($circleBar[options]) {
                return $circleBar[options].apply($circleBar, [val]);
            }
        }
        return $.each(this, function () {
            var $circleBar = $(this).data('circleBar');

            if (!$circleBar) {
                $circleBar = new $.circleBar(this, options);
                $(this).data('circleBar', $circleBar);
            }
        })
    }

    $(document).ready(function () {
        var i = 0;
        var $c = $('.quiz-result-overall').circleBar({
            value: 45
        });
        var t = setInterval(function () {
            $c.circleBar('value', i++);
            if (i > 100) {
                clearInterval(t);
            }

        }, 40)
    })

})(jQuery);