;(function ($) {
    var timer = null,
        $wraps = null,
        $cloneWraps = null,
        onSearch = function (keyword) {
            if (!$cloneWraps) {
                $cloneWraps = $wraps.clone();
            }
            keywords = keyword.toLowerCase().split(/\s+/).filter(function (a, b) {
                return a.length >= 3;
            });
            var foundItems = function ($w1, $w2) {
                return $w1.find('.plugin-card').each(function () {
                    var $item = $(this),
                        itemText = $item.find('.item-title').text().toLowerCase(),
                        itemDesc = $item.find('.column-description, .theme-description').text();
                    var found = function () {
                        var reg = new RegExp(keywords.join('|'), 'ig');
                        return itemText.match(reg) || itemDesc.match(reg)
                    }
                    if (keywords.length) {
                        if (found()) {
                            var $clone = $item.clone();
                            $w2.append($clone);
                        }
                    } else {
                        $w2.append($item.clone());
                    }
                });
            }

            $wraps.each(function (i) {
                var $this = $(this).html(''),
                    $items = foundItems($cloneWraps.eq(i), $this),
                    count = $this.children().length;

                $this.prev('h2').find('span').html(count)
            });
        };
    $(document).on('keyup', '.lp-search-addon', function (e) {
        timer && clearTimeout(timer);
        timer = setTimeout(onSearch, 300, e.target.value);
    }).ready(function () {
        $wraps = $('.addons-browse');
    })
})(jQuery);