;
/**
 * LearnPress frontend course app.
 *
 * @version 3.x.x
 * @author ThimPress
 * @package LearnPress/JS/Course
 */
(function ($, LP, _, Vue, Vuex) {

    'use strict';

    function LP_Course(settings) {



    }

    $(document).ready(function () {

        if (lpCourseSettings && lpCourseSettings.items) {
            lpCourseSettings.items[18].timeRemaining = 3600;
            lpCourseSettings.items[18].totalTime = 7200;
        }

        new LP_Course(lpCourseSettings);

        $(document).ready(function () {
            var $content = $('.content-item-scrollable');
            $content.addClass('scrollbar-light')
                .scrollbar({
                    scrollx: false
                });

            $content.parent().css({
                position: 'absolute',
                top: 0,
                bottom: 60,
                width: '100%'
            }).css('opacity', 1).end().css('opacity', 1);

            var $curriculum = $('.course-item-popup').find('.curriculum-scrollable');
            $curriculum.addClass('scrollbar-light')
                .scrollbar({
                    scrollx: false
                });

            $curriculum.parent().css({
                position: 'absolute',
                top: 0,
                bottom: 0,
                width: '100%'
            }).css('opacity', 1).end().css('opacity', 1);

            setTimeout(function () {
                var $cs = $('body.course-item-popup').find('.curriculum-sections').parent();
                $cs.scrollTo($cs.find('.course-item.current'), 100);
            }, 300);

            /////$('.course-item-popup').find('#learn-press-course-curriculum').addClass('scrollbar-light').scrollbar({scrollx: false});

            if ($('#wpadminbar').length) {
                $('body').addClass('wpadminbar')
            }

            $('body').css('opacity', 1);
        });

        $(document).on('learn-press/nav-tabs/clicked', function (e, tab) {
            if ($(document.body).hasClass('course-item-popup')) {
                return;
            }
            LP.setUrl($(tab).attr('href'));
        })
    })
})(jQuery, LP, _, Vue, Vuex);