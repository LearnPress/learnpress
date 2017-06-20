;(function ($) {
    var timer = null,
        $items = null,
        onSearch = function (keyword) {
            keywords = keyword.toLowerCase().split(/\s+/).filter(function (a, b) {
                return a.length >= 3;
            });
            var $found = $items.each(function () {
                var $item = $(this),
                    itemText = $item.find('.item-title').text().toLowerCase(),
                    itemDesc = $item.find('.column-description').text();
                var found = function () {
                    var reg = new RegExp(keywords.join('|'), 'ig');

                    return itemText.match(reg) || itemDesc.match(reg)
                }
                if (keywords.length) {
                    $item.toggleClass('hide-if-js', !found());
                } else {
                    $item.removeClass('hide-if-js')
                }
            }).filter(':visible').get();

            $('.addons-browse').each(function () {
                var $this = $(this),
                    $el = $this.find('.plugin-card'),
                    count = $el.filter('.plugin-card:not(.hide-if-js)').length;

                $this.prev('h2').find('span').html(count)
            })
        };
    $(document).on('keyup', '.lp-search-addon', function (e) {
        timer && clearTimeout(timer);
        timer = setTimeout(onSearch, 300, e.target.value);
    }).ready(function () {
        $items = $('.plugin-card');
    })
})(jQuery);