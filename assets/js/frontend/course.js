;
/**
 * LearnPress frontend course app.
 *
 * @version 3.x.x
 * @author ThimPress
 * @package LearnPress/JS/Course
 */
(function ($, LP, _) {

    'use strict';

    function LP_Storage(key) {
        var storage = window.localStorage;
        this.key = key;
        this.get = function (id) {
            var val = storage.getItem(this.key) || '',
                sections = val.split(',');
            if (id) {
                id = id + '';
                var pos = sections.indexOf(id);
                if (pos >= 0) {
                    return sections[pos];
                }
            }
            return sections;
        }
        this.set = function (sections) {
            if (typeof sections !== 'string') {
                sections = sections.join(',');
            }
            storage.setItem(this.key, sections);
            return sections.split(',');
        }
        this.hasSection = function (id) {
            id = id + '';
            var sections = this.get(),
                at = sections.indexOf(id);

            return at >= 0 ? at : false;
        }
        this.add = function (id) {
            id = id + '';
            var sections = this.get();
            if (this.hasSection(id)) {
                return;
            }
            sections.push(id);
            this.set(sections);
            return sections;
        }
        this.remove = function (id) {
            id = id + '';
            var at = this.hasSection(id);
            if (at !== false) {
                var sections = this.get();
                sections.splice(at, 1);
                this.set(sections);
                return sections;
            }
            return false;
        }
    }

    function LP_Course(settings) {
        var sectionStorage = new LP_Storage('sections');

        function toggleAnswerOptions() {
            var $el = $(event.target),
                $chk = false;
            if ($el.is('input.option-check')) {
                return;
            }

            $chk = $el.closest('.answer-option').find('input.option-check');

            if ($chk.is(':disabled')) {
                return;
            }
            if ($chk.is(':checkbox')) {
                $chk[0].checked = !$chk[0].checked;
            } else {
                $chk[0].checked = true;
            }
        }

        function toggleSection() {
            var id = $(this).closest('.section').data('section-id');
            $(this).siblings('.section-content').slideToggle(function () {
                if ($(this).is(':visible')) {
                    sectionStorage.remove(id);
                } else {
                    sectionStorage.add(id);
                }
            });
        }

        function initSections() {
            var $activeSection = $('.course-item.current').closest('.section'),
                sections = $('.curriculum-sections').find('.section'),
                sectionId = $activeSection.data('section-id'),
                hiddenSections = [];

            if ($activeSection) {
                hiddenSections = sectionStorage.remove(sectionId);
            } else {
                hiddenSections = sectionStorage.get();
            }

            for (var i = 0; i < hiddenSections.length; i++) {
                sections.filter('[data-section-id="' + hiddenSections[i] + '"]').find('.section-content').hide();
            }
        }

        initSections();

        $(document)
            .on('click', '.answer-options .answer-option', toggleAnswerOptions)
            .on('click', '.section-header', toggleSection);
    }

    $(document).ready(function () {

        if (lpCourseSettings && lpCourseSettings.items) {
            lpCourseSettings.items[18].timeRemaining = 3600;
            lpCourseSettings.items[18].totalTime = 7200;
        }

        new LP_Course(lpCourseSettings);


        $(document).ready(function () {
            function prepareForm(form) {
                var data = $('.answer-options').serializeJSON(),
                    $form = $(form),
                    $hidden = $('<input type="hidden" name="question-data" />').val(JSON.stringify(data));
                if (($form.attr('method') + '').toLowerCase() !== 'post') {
                    return;
                }
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
})(jQuery, LP, _);