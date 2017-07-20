/**
 * Plugin: Tabs
 */
;(function ($) {
    var adminTabs = function ($el, options) {
        var $tabs = $el.find('.tabs-nav').find('li'),
            $contents = $el.find('.tabs-content-container > li'),
            $currentTab = null,
            $currentContent = null;

        function selectTab($tab) {
            var index = $tabs.index($tab),
                $tabsWrap = $tabs.parent(),
                url = $tab.find('a').attr('href');

            $currentContent = $contents.eq(index);

            $tab.addClass('active').siblings('li.active').removeClass('active');
            $currentContent.show().css({visibility: 'hidden'});

            var contentHeight = $currentContent.height(),
                tabsHeight = $tabsWrap.outerHeight();

            if (contentHeight < tabsHeight) {
                contentHeight = tabsHeight + parseInt($tabsWrap.css('margin')) * 2;
            } else {
                contentHeight = undefined;
            }

            $currentContent.css('visibility', '').css({height: contentHeight}).hide();

            $currentContent.show();
            $currentContent.siblings('li.active').fadeOut(0, function () {
                $currentContent.addClass('active').siblings('li.active').removeClass('active');
            });

            LP.setUrl(url);
        }

        function selectDefaultTab() {
            $currentTab = $tabs.filter('.active');
            if (!$currentTab.length) {
                $currentTab = $tabs.first();
            }
            $currentTab.find('a').trigger('click');
        }

        function initEvents() {
            $el.on('click', '.tabs-nav a', function (event) {
                event.preventDefault();
                $currentTab = $(this).parent();
                selectTab($currentTab);
            });
        }

        function init() {
            initEvents();
            selectDefaultTab();
        }

        init();
    };
    $.fn.lpAdminTab = function (options) {
        options = $.extend({}, options || {});
        return $.each(this, function () {
            var $el = $(this),
                tabs = $el.data('learn-press/tabs');

            if (!tabs) {
                tabs = new adminTabs($el, options);
                $el.data('learn-press/tabs', tabs)
            }
            return $el;
        })
    };

    var $doc = $(document);
    $doc.ready($.fn.lpAdminTab);
})(jQuery);