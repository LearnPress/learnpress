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

        var ifr = $('#ifr-course-item').on('load', function () {
            console.log('loaded');
        })

        //if (parent.window) {
        var beingRedirect = '';
        var $win = parent.window,
            $doc = parent.window.document;
        window.onunload = function (e) {
            console.log(beingRedirect)
            // Notify top window of the unload event
            window.top.postMessage('iframe_change', '*');
            if (beingRedirect) {
                window.top.location.href = beingRedirect;

                return false;
            }
        };

        var receiveMessage = function receiveMessage(e) {
            if (ifr.length === 0) {
                return;
            }
            var url = $win.location.href,
                url_parts = url.split("/"),
                allowed = url_parts[0] + "//" + url_parts[2];

            // Only react to messages from same domain as current document
            if (e.origin !== allowed) return;
            // Handle the message
            switch (e.data) {
                case 'iframe_change':
                    window.top.location.href = (ifr.attr('src'));
            }
        };
        //$win.addEventListener("message", receiveMessage, false);

        $(document).on('click', '.content-item-description a', function (e) {
            //e.preventDefault();
            console.log('add')
        })
        $(document).on('click', '.content-item-description', function (e) {
            // e.preventDefault();

            var el = $(e.target),
                link = el.attr('href');
            if (link) {
                beingRedirect = link;
                //parent.window && (parent.window.open(link, '_blank').focus());
            }
        });




        //}

        window.xxxx = function () {
            alert();
        }
        $(document).ready(function () {
            function prepareForm(form) {
                var data = $('.answer-options').serializeJSON(),
                    $form = $(form),
                    $hidden = $('<input type="hidden" name="question-data" />').val(JSON.stringify(data));
                $form.find('input[name="question-data"]').remove();
                return $form.append($hidden);
            }

            $(document).on('submit', 'form.lp-form', function () {
                prepareForm(this);
            });

            var $content = $('.content-item-scrollable');
            $content.addClass('scrollbar-light')
                .scrollbar({
                    scrollx: false
                });

            $content.parent().css({
                position: 'absolute',
                top: 0,
                bottom: $('#course-item-content-footer:visible').outerHeight() || 0,
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