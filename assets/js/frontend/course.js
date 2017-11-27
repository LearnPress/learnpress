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

    /**
     * LP_Course
     *
     * @param settings
     * @constructor
     */
    function LP_Course(settings) {

        var sectionStorage = new LP_Storage('sections'),
            $body = $('body'),
            $content = $('.content-item-scrollable'),
            $curriculum = $('.course-item-popup').find('.curriculum-scrollable'),
            $courseItems = $curriculum.find('.course-item');

        /**
         * Toggle answer option check/uncheck
         */
        function toggleAnswerOptions() {
            var $el = $(event.target),
                $chk;
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

        /**
         * Show/Hide section content
         */
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

        /**
         * Init sections
         */
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

        /**
         * Prepare form before submitting
         *
         * @param form
         */
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

        /**
         * Tab course event
         *
         * @param e
         * @param tab
         */
        function onTabCourseClick(e, tab) {

            if ($(document.body).hasClass('course-item-popup')) {
                return;
            }

            var $tab = $(tab),
                $parent = $tab.closest('.course-nave');

            if ($tab.siblings().lengt === 0) {
                return;
            }

            LP.setUrl($(tab).attr('href'));
        }

        /**
         * Event on press any key into search
         *
         * @param e
         * @returns {boolean}
         */
        function onSearchInputKeypress(e) {

            if (e.type === 'keypress' && e.keyCode === 13) {
                return false;
            }

            var s = this.value,
                r = new RegExp(s, 'ig');
            $courseItems.map(function () {
                var $item = $(this),
                    itemName = $item.find('.item-name').text();
                if (itemName.match(r) || !s.length) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });

            $('.section').show().each(function () {
                if (s.length) {
                    if (!$(this).find('.section-content').children(':visible').length) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                } else {
                    $(this).show();
                }
            });
            $(this).closest('.course-item-search').toggleClass('has-keyword', !!this.value.length)
        }

        function onClearSearchInputClick(e) {
            var $form = $(this).closest('.course-item-search');
            $form.find('input').val('').trigger('keyup')
        }

        function onClickQM() {
            $('#qm').css({'z-index': 999999999, position: 'relative'});
            $('html, body').css('overflow', 'auto');
        }

        function initEvents() {
            // Live events
            $(document)
                .on('learn-press/nav-tabs/clicked', onTabCourseClick)
                .on('keyup keypress', '.course-item-search input', onSearchInputKeypress)
                .on('click', '.course-item-search button', onClearSearchInputClick)
                .on('click', '#wp-admin-bar-query-monitor', onClickQM)
                .on('click', '.answer-options .answer-option', toggleAnswerOptions)
                .on('click', '.section-header', toggleSection)
                .on('submit', 'form.lp-form', function () {
                    prepareForm(this);
                });
        }

        function initScrollbar() {
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
        }

        /**
         * Init
         */
        function init() {

            setTimeout(function () {
                var $cs = $('body.course-item-popup').find('.curriculum-sections').parent();
                $cs.scrollTo($cs.find('.course-item.current'), 100);
            }, 300);

            if ($('#wpadminbar').length) {
                $body.addClass('wpadminbar')
            }

            initSections();
            initEvents();
            initScrollbar();

            $body.css('opacity', 1);

        }

        init();
    }

    $(document).ready(function () {
        $(document).ready(function () {
            new LP_Course({})
        });
    })
})(jQuery, LP, _);