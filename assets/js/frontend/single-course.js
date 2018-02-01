/**
 * Single course functions
 */
if (typeof LearnPress === 'undefined') {
    window.LP = window.LearnPress = {};
}
;(function ($) {
    "use strict";
    LearnPress.Course = $.extend(
        LearnPress.Course || {}, {
            finish: function (data, callback) {
                LearnPress.$LP_Course && LearnPress.$LP_Course.finishCourse({data: data, success: callback});
            }
        }
    );

    var Course = function (args) {
            this.model = new Course.Model(args);
            this.view = new Course.View({
                model: this.model
            });
        },
        Template = function (id, tmpl_data) {
            var compiled,
                options = {
                    evaluate: /<#([\s\S]+?)#>/g,
                    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                    escape: /\{\{([^\}]+?)\}\}(?!\})/g,
                    variable: 'data'
                },
                template = function (data) {
                    compiled = compiled || _.template($('#learn-press-template-' + id).html(), null, options);
                    return compiled(data);
                };
            return tmpl_data ? $(template(tmpl_data)) : template;
        },
        Course_Item = window.Course_Item = Backbone.Model.extend({
            initialize: function () {
                this.on('change:content', this._changeContent);
                this.on('change:current', this._changeCurrent);
            },
            _changeContent: function () {
                LP.Hook.doAction('learn_press_update_item_content', this);
            },
            _changeCurrent: function (m) {

            },
            get: function () {
                var val = Course_Item.__super__.get.apply(this, arguments);
                if (arguments[0] == 'url') {
                    val = LP_Course_Params.root_url + val;
                }
                return val;
            },
            request: function (args) {
                var that = this;
                if (!this.get('url')) {
                    return false;
                }
                args = $.extend({
                    context: null,
                    callback: null
                }, args || {});
                LP.ajax({
                    url: this.get('url'),
                    action: 'load-item',
                    data: $.extend({
                        format: 'html'
                    }, this.toJSON()),
                    dataType: 'html',
                    success: function (response) {
                        LP.Hook.doAction('learn_press_course_item_loaded', response, that);
                        response = LP.Hook.applyFilters('learn_press_course_item_content', response, that);
                        $.isFunction(args.callback) && args.callback.call(args.context, response, that);
                    }
                });
                return this;
            },
            complete: function (args) {
                var that = this;
                args = $.extend({
                    context: null,
                    callback: null,
                    format: 'json'
                }, this.toJSON(), args || {});
                var data = {};

                // Omit unwanted fields
                _.forEach(args, function (v, k) {
                    if (($.inArray(k, ['content', 'current', 'title', 'url']) == -1) && !$.isFunction(v)) {
                        data[k] = v;
                    }
                    ;
                });
                LP.ajax({
                    url: this.get('url'),
                    action: 'complete-item',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        ///response = LP.parseJSON(response);
                        LP.Hook.doAction('learn_press_course_item_completed', response, that);
                        response = LP.Hook.applyFilters('learn_press_course_item_complete_response', response, that);
                        $.isFunction(args.callback) && args.callback.call(args.context, response, that);
                    }
                });
            },
            start: function (args) {
                var args = $.extend({
                    course_id: 0,
                    quiz_id: this.get('id'),
                    security: null
                }, args || {});
                var data = this._validateObject(args), that = this;
                LP.ajax({
                    url: this.get('url'),
                    action: 'start-quiz',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        $.isFunction(args.callback) && args.callback.call(args.context, response, that);
                    }
                });
            },
            finishQuiz: function (args) {
                var args = $.extend({
                    course_id: 0,
                    quiz_id: this.get('id'),
                    security: null
                }, args || {});
                var data = this._validateObject(args), that = this;
                data = LP.Hook.applyFilters('learn_press_finish_quiz_data', data);
                var beforeSend = args.beforeSend || function () {
                    };
                LP.ajax({
                    url: this.get('url'),
                    action: 'finish-quiz',
                    data: data,
                    dataType: 'json',
                    beforeSend: beforeSend,
                    success: function (response) {
                        LP.unblockContent();
                        $.isFunction(args.callback) && args.callback.call(args.context, response, that);
                    }
                });
            },
            retakeQuiz: function (args) {
                var args = $.extend({
                    course_id: 0,
                    quiz_id: this.get('id'),
                    security: null
                }, args || {});
                var data = this._validateObject(args), that = this;
                LP.ajax({
                    url: this.get('url'),
                    action: 'retake-quiz',
                    data: data,
                    dataType: 'json',
                    beforeSend: function () {
                        LP.blockContent();
                    },
                    success: function (response) {
                        LP.unblockContent();
                        $.isFunction(args.callback) && args.callback.call(args.context, response, that);
                    }
                });
            },
            _toJSON: function () {
                // call parent method
                var json = Course_Item.__super__.toJSON.apply(this, arguments);
            },
            _validateObject: function (obj) {
                var ret = {};
                for (var i in obj) {
                    if (!$.isFunction(obj[i])) {
                        ret[i] = obj[i];
                    }
                }
                return ret;
            }
        }),
        Course_Items = Backbone.Collection.extend({
            model: Course_Item,
            current: null,
            len: 0,
            initialize: function () {

                this.on('add', function (model) {
                    this.listenTo(model, 'change', this.onChange);
                    model.set('index', this.len++);
                }, this);

            },
            onChange: function (a, b) {
                if (a.get('current') == true) {
                    this.current = a;
                    for (var i = 0; i < this.length; i++) {
                        var e = this.at(i);
                        if (e.get('id') == a.get('id')) {
                            continue;
                        }
                        if (e.get('current') == false) {
                            continue;
                        }
                        this.stopListening(e, 'change', this.onChange);
                        e.set('current', false);
                        this.listenTo(e, 'change', this.onChange);
                    }
                }
                a.$el && a.$el.toggleClass('item-current', a.get('current'));
            },
            getNextItem: function () {
                var next = false;
                if (this.current) {
                    var index = this.current.get('index') + 1;
                    next = this.at(index);
                    while (next && !next.$el.hasClass('viewable')) {
                        index++;
                        next = this.at(index);
                        if (!next || index > 1000) {
                            break;
                        }
                    }
                }
                return next;
            },
            getPrevItem: function () {
                var prev = false;
                if (this.current) {
                    var index = this.current.get('index') - 1;
                    if (index >= 0) {
                        prev = this.at(index);
                        while (!prev.$el.hasClass('viewable')) {
                            index--;
                            prev = this.at(index);
                            if (!prev || index < 0) {
                                break;
                            }
                        }
                    }
                }
                return prev;
            }
        });
    Course.View = Backbone.View.extend({
        el: 'body', //'.course-summary',
        itemEl: null,
        cached: {},
        events: {
            'click .viewable .button-load-item': '_loadItem',
            'click .prev-item, .next-item': '_loadItem',
            'click .viewable': '_loadItem',
            'click .section-header': '_toggleSection',
            'click .learn-press-nav-tab': '_tabClick',
            /*'click .button-start-quiz'         : '_startQuiz',
             'click .button-finish-quiz'        : '_finishQuiz',
             'click .button-retake-quiz'        : '_retakeQuiz',
             'click .button-complete-item'      : '_completeItem',*/
            'click .button-retake-course': '_retakeCourse',
            'click .button-finish-course': '_finishCourse',
            'click .learn-press-content-item-title .lp-expand': '_expand'

            //'click #learn-press-button-complete-item': '_c'
        },
        itemLoading: 0,
        currentItem: null,
        initialize: function () {
            var $item = this.$('.course-item');
            _.bindAll(this, 'receiveMessage', 'pushContent', 'onPopState', 'updateItemContent', '_tabClick', '_showPopup', 'removePopup');
            this.itemEl = this.$('#learn-press-content-item');
            this.model.items.forEach(function (v, k) {
                v.course = this;
                v.$el = $item.filter('.course-item-' + v.get('id'));
            }, this);
            this._initHooks();
            this._loadDefaultContent();

            if (typeof window.onpopstate !== 'undefined') {
                $(window).on('popstate', this.onPopState);
            }

            LP.Hook.addAction('learn_press_receive_message', this.receiveMessage);
            if (typeof localStorage != 'undefined') {
                var expanded = localStorage.getItem("lp-item-expanded");
                if (expanded == 'yes') {
                    this.expand(true);
                }
            }
        },
        receiveMessage: function (data) {
            if (data.messageType === 'update-course') {
                this.update(data);
            }
        },
        update: function (data) {
            if (!data) {
                return;
            }
            var itemsCount = data.items ? data.items.length : 0,
                itemsCompleted = 0,
                sections = {},
                $progress = this.$('.course-progress').find('.number, .percentage-sign'),
                $itemProgress = this.$('.items-progress').find('.number, .percentage-sign');

            if ($progress.length == 0) {
                return;
            }

            $progress[0].childNodes[0].nodeValue = parseInt(data.results);

            this.$('.course-progress .lp-progress-value').width(parseInt(data.results) + '%');
            data.items && data.items.forEach(function (item) {
                var $item = this.$('.course-item.course-item-' + item.id),
                    $status = $item.find('.item-status'),
                    statusClass = ($status[0].className + '').replace(/(item-status-[^\s]*)/g, '').trim();
                if (!sections[item.section_id]) {
                    sections[item.section_id] = [0, 0];
                }
                if (item.status) {
                    statusClass += ' item-status-' + item.status;
                }
                if (item.status === 'completed') {
                    $item.addClass('item-has-status item-completed');
                } else if (item.status) {
                    $item.addClass('item-has-status').removeClass('item-completed');
                } else {
                    $item.removeClass('item-has-status').removeClass('item-completed');
                }

                if (item.type === 'lp_quiz') {
                    $item.find('.item-result').html(LP.Hook.applyFilters('item_result_text', item.results));
                }
                $status[0].className = statusClass;
                if ($.inArray(item.status, ['completed', 'failed', 'passed']) != -1) {
                    sections[item.section_id][1]++;
                }
                if (item.status && item.status != 'viewed') {
                    $item.addClass('item-has-result');
                } else {
                    $item.removeClass('item-has-result');
                }
                sections[item.section_id][0]++;
            }, this);
            this.$('.curriculum-sections .section').each(function () {
                var $section = $(this),
                    id = $section.data('id'),
                    data = sections[id];
                if (!data) {
                    return;
                }
                itemsCompleted += data[1];
                $section.find('.section-header span.step').html(LP.Hook.applyFilters('section_header_span_text', data[1] + '/' + data[0]));
            });
            $itemProgress.eq(0).html(data.completed_items_text.replace('%d', itemsCompleted).replace('%d', itemsCount));
            var passingCondition = parseInt(this.$('.course-progress .lp-course-progress').data('passing-condition'));
            if (data.grade) {
                var $grade = this.$('.grade').html(data.grade_html),
                    gradeClass = $grade[0].className.replace(/passed|failed|in-progress/, '') + ' ' + data.grade;
                $grade[0].className = gradeClass;
            }
            this.$('.button-finish-course').toggleClass('hide-if-js', !(data.results >= passingCondition));

            if (data.setUrl) {
                LP.setUrl(data.setUrl);
            }
        },
        getHash: function (url) {
            var courseUrl = this.model.get('url'),
                hash = url.replace(courseUrl, '');
            return hash;
        },
        onPopState: function () {
            var hash = this.getHash(window.location.href);
            if (!hash) {
                return;
            }
        },
        pushContent: function (url) {
            var hash = this.getHash(url);
            if (!hash) {
                return;
            }
            this.cached[hash] = Math.random();
            //console.log('push:' + hash)
        },
        _expand: function (e) {
            e && e.preventDefault();
            this.expand();
        },
        expand: function (expand) {
            if (typeof expand == 'undefined') {
                expand = !window.parent.jQuery('#popup-main').hasClass('expand');
            }
            window.parent.jQuery('#popup-main').toggleClass('expand', expand);
            $('#learn-press-content-item').toggleClass('expand', expand);
            if (localStorage) {
                localStorage.setItem("lp-item-expanded", expand ? 'yes' : 'no');
            }
        },
        _initHooks: function () {
            LP.Hook.addAction('learn_press_update_item_content', this.updateItemContent);
            LP.Hook.addAction('learn_press_set_location_url', this.pushContent);

            $(document).on('learn_press_popup_course_remove', this.removePopup);
        },
        _loadDefaultContent: function () {
            var that = this;
            setTimeout(function () {
                that.$('.course-item.item-current .button-load-item').trigger('click', {force: true});
            }, 500);
        },
        _getItemId: function (el) {
            var id = el.hasClass('button-load-item') ? el.data('id') : el.find('.button-load-item').data('id');
            if (!id) {
                id = el.hasClass('lp-label-preview') ? el.closest('.course-item').find('.button-load-item').data('id') : 0;
            }
            return id;
        },
        _loadItem: function (e, f) {
            e.preventDefault();
            var that = this,
                $target = $(e.target),
                id = this._getItemId($target);
            f = f || {force: false};
            if (!id || this.itemLoading) {
                return;
            }
            if ($target.closest('.course-item').hasClass('item-current') && !f.force) {
                return;
            }
            this.blockContent();
            if (this.currentItem) {
                var $iframe = this.currentItem.get('content');
                $iframe && $iframe.detach();
            }
            this.itemLoading = id;
            this.currentItem = this.model.getItem(id);
            this.showPopup();
            var $content = this.currentItem.get('content'),
                isNew = !($content && $content.length);
            if (!$content) {
                $content = $('<iframe webkitallowfullscreen mozallowfullscreen allowfullscreen />');
                this.currentItem.set('content', $content);
            }

            that.viewItem(id);
            this.$('#popup-content-inner').html($content);
            if (isNew) {
                $content.attr('src', LP.addQueryVar('content-item-only', 'yes', this.currentItem.get('url')));
            }
            $content.unbind('load').load(function () {
                that.itemLoading = 0;
            });
        },
        viewItem: function (id, args) {
            var item = this.model.getItem(id);
            if (item) {
                item.set('current', true);
            }
            this.itemEl.show();
            this.currentItem = item;
            this.$('.item-current').removeClass('item-current');
            this.$('.course-item [data-id="' + item.get('id') + '"]').closest('.course-item').addClass('item-current');
            this.$('#course-curriculum-popup').attr('data-item-id', item.get('id'));
            this.updateUrl();
            //this.updateItemContent(item);
            this.updateFooterNav();
            if (item.get('type') === 'lp_quiz') {
                this.loadQuiz();
            }
            return item;
        },
        _completeItem: function (e) {
            var that = this,
                $button = $(e.target),
                security = $button.data('security'),
                $section = this.getCurrentSection(),
                $item = $button.closest('.course-item');
            LP.blockContent();
            this.currentItem.complete({
                security: security,
                course_id: this.model.get('id'),
                section_id: $section.length ? $section.data('id') : 0,
                callback: function (response, item) {
                    if (response.result === 'success') {
                        // highlight item
                        item.$el.removeClass('item-started').addClass('item-completed focus off');
                        // then restore back after 3 seconds
                        _.delay(function (item) {
                            item.$el.removeClass('focus off');
                        }, 3000, item);

                        that.$('.learn-press-course-results-progress').replaceWith($(response.html.progress));
                        $section.find('.section-header').replaceWith($(response.html.section_header));
                        that.$('.learn-press-course-buttons').replaceWith($(response.html.buttons));
                        that.currentItem.set('content', $(response.html.content));
                    }
                    LP.unblockContent();
                }
            });
        },
        _toggleSection: function (e) {
            var $head = $(e.target).closest('.section-header'),
                $sec = $head.siblings('ul.section-content');
            $head.find('.collapse').toggleClass('plus');
            $sec.slideToggle();
        },
        _tabClick: function (e) {
            var tab = $(e.target).closest('.learn-press-nav-tab').data('tab');
            if (tab === 'overview') {
                //LP.setUrl(this.model.get('url'))
            } else if (tab === 'curriculum') {
                if (this.currentItem) {
                    //LP.setUrl(this.currentItem.get('url'))
                }
            }
        },

        _retakeCourse: function (e) {
            e.preventDefault();
            var that = this;
            jConfirm(learn_press_single_course_localize.confirm_retake_course.message, learn_press_single_course_localize.confirm_retake_course.title, function (confirm) {
                if (confirm) {
                    var $button = $(e.target),
                        data = $button.data(),
                        args = {
                            data: data,
                            callback: function (response) {
                                if (response.result === 'success') {
                                    if (response.redirect) {
                                        LP.reload(response.redirect);
                                    }
                                }
                            }
                        };
                    that.model.retakeCourse(args);
                }
            });
        },
        _finishCourse: function (e) {
            e.preventDefault();
            var _this = this;
            jConfirm(learn_press_single_course_localize.confirm_finish_course.message, learn_press_single_course_localize.confirm_finish_course.title, function (confirm) {
                if (confirm) {
                    var $button = $(e.target),
                        data = $button.data(),
                        args = {
                            data: data,
                            callback: function (response) {
                                if (response.result === 'success') {
                                    if (response.redirect) {
                                        LP.reload(response.redirect);
                                    }
                                }
                            }
                        };
                    _this.model.finishCourse(args);
                }
            });
        },
        _showPopup: function (e) {
            e.preventDefault();
            var args = {
                model: new Course.ModelPopup(),
                course: this
            };
        },
        showPopup: function () {
            if (!this.popup) {
                this.popup = new Course.Popup({
                    model: new Course.ModelPopup(),
                    course: this
                });
            }
        },
        loadQuiz: function () {
            if (window.$Quiz) {
                window.$Quiz.destroy();
            }
            if (typeof window.LP_Quiz_Params !== 'undefined') {
                window.$Quiz = new LP_Quiz(window.LP_Quiz_Params);
            }
        },
        removePopup: function () {
            this.popup = null;
            this.model.items.forEach(function (m) {
                m.set('current', false);
            });
        },
        getCurrentSection: function () {
            return this.currentItem.$el.closest('.section');
        },
        updateItemContent: function (item) {
            ///this.itemEl.html(item.get('content'));
            this.$('#popup-content-inner').html(item.get('content'));
        },
        updateFooterNav: function () {
            var prev = this.model.getPrevItem(),
                next = this.model.getNextItem();
            this.$('#popup-footer').find('.prev-item, .next-item').remove();
            if (prev) {
                this.$('#popup-footer').append(Template('course-prev-item', prev.toJSON()));
            }
            if (next) {
                this.$('#popup-footer').append(Template('course-next-item', next.toJSON()));
            }
        },

        updateUrl: function (url) {
            if (!url && this.currentItem) {
                url = LP.Hook.applyFilters('learn_press_get_current_item_url', this.currentItem.get('url'), this.currentItem);
            }
            if (url) {
                LP.setUrl(url);
            }
        },
        blockContent: function () {
            LP.blockContent();
        },
        unblockContent: function () {
            LP.unblockContent();
        }
    });
    Course.Model = Backbone.Model.extend({
        items: null,
        initialize: function () {
            this.createItems();
        },
        createItems: function () {
            this.items = new Course_Items();
            this.items.add(this.get('items'));
        },
        retakeCourse: function (args) {
            var that = this;
            LP.ajax({
                url: this.get('url'),
                action: 'retake-course',
                data: args.data,
                dataType: 'json',
                beforeSend: function () {
                    LP.blockContent();
                },
                success: function (response) {
                    LP.unblockContent();
                    $.isFunction(args.callback) && args.callback.call(args.context, response, that);
                }
            });
        },
        finishCourse: function (args) {
            var that = this;
            LP.ajax({
                url: this.get('url'),
                action: 'finish-course',
                data: args.data,
                dataType: 'json',
                beforeSend: function () {
                    LP.blockContent();
                },
                success: function (response) {
                    LP.unblockContent();
                    $.isFunction(args.callback) && args.callback.call(args.context, response, that);
                }
            });
        },
        getItem: function (args) {
            return $.isPlainObject(args) ? this.items.findWhere(args) : this.items.findWhere({id: args});
        },
        getNextItem: function () {
            return this.items.getNextItem();
        },
        getPrevItem: function () {
            return this.items.getPrevItem();
        },
        getItems: function (args) {
            return typeof args === 'undefined' ? this.items : this.items.where(args);
        },
        getCurrent: function () {
        }
    });

    Course.Popup = Backbone.View.extend({
        course: null,
        events: {
            'click .popup-close': '_closePopup'
            //'click .button-load-item': '_loadItem'
            , 'click .sidebar-hide-btn': '_closeSidebar'
            , 'click .sidebar-show-btn': '_showSidebar'
        },
        initialize: function (args) {
            _.bindAll(this, '_ajaxLoadItemSuccess');
            this.course = args.course;
            this.render();
        },
        render: function () {
            $('.learn-press-content-item-summary').remove();

            this.$el.attr('tabindex', '0').append(Template('curriculum-popup', {})).css({}).appendTo($(document.body));
            this.curriculumPlaceholder = $('<span />');
            this.progressPlaceholder = $('<span />');

            var $curriculum = this.course.$('#learn-press-course-curriculum');
            var $progress = this.course.$('.learn-press-course-results-progress');

            this.progressPlaceholder.insertAfter($progress);
            $progress.appendTo(this.$('#popup-sidebar'));

            this.curriculumPlaceholder.insertAfter($curriculum);
            $curriculum.appendTo(this.$('#popup-sidebar'));
            //$('html, body').each(function () {
            //	var $root = $(this).addClass('block-content'),
            //		dataOverflow = $root.attr('overflow'),
            //		overflow = dataOverflow != undefined ? dataOverflow : $root.css('overflow');
            //	$root.css('overflow', 'hidden').attr('overflow', overflow);
            //})
            LP.blockContent();
            $(".sidebar-show-btn").hide();
            $(document).on('learn_press_unblock_content', this.hideScrollBar);
        },
        _closePopup: function (e) {
            e.preventDefault();
            LP.setUrl(this.course.model.get('url'));
            $('#learn-press-content-item').hide();
            this.curriculumPlaceholder.replaceWith(this.$('#learn-press-course-curriculum'));
            this.progressPlaceholder.replaceWith(this.$('.learn-press-course-results-progress'));
            this.undelegateEvents();
            this.remove();

            /*var $root = $(this).removeClass('block-content'),
             overflow = $root.attr('overflow');
             $root.css('overflow', overflow).removeAttr('overflow');*/
            LP.unblockContent();
            $('html, body').css('overflow', '');
            $(document).off('focusin').trigger('learn_press_popup_course_remove').unbind('learn_press_unblock_content', this.hideScrollBar);
        },
        hideScrollBar: function () {
            $('html, body').each(function () {
                var $root = $(this).css('overflow', 'hidden');
            })
        },
        _closeSidebar: function (e) {
            e.preventDefault();
            $('#popup-sidebar').stop().animate({'margin-left': -350});
            $('#popup-main').stop().animate({'left': '0px'});
            $('#popup-main .sidebar-show-btn').css('display', 'inline-block');
        },
        _showSidebar: function (e) {
            e.preventDefault();
            $('#popup-sidebar').stop().animate({'margin-left': '0'});
            $('#popup-main').stop().animate({'left': '350px'});
            $('#popup-main .sidebar-show-btn').css('display', 'none');
        },
        _loadItem: function (e) {
            var $iframe = $('<iframe webkitallowfullscreen mozallowfullscreen allowfullscreen />').src($(e.target).attr('href') + '?content-item-only=yes');
            this.$('#popup-content-inner').html($iframe);
            /*return '';
             e.preventDefault();
             $.ajax({
             url    : $(e.target).attr('href'),
             success: this._ajaxLoadItemSuccess
             });*/
        },
        _ajaxLoadItemSuccess: function (response) {
            this.$('#popup-content-inner').html($(response).contents().find('.lp_course'));
        }
    }),
        Course.ModelPopup = Backbone.Model.extend({
            initialize: function () {
            }
        });

    LP.Course = Course;
    $(document).ready(function () {
        // Ensure that all other 'ready' callback have executed
        (typeof window.LP_Course_Params != 'undefined') && $(document).ready(function () {
            LP.$LP_Course = new Course(LP_Course_Params);
            LP.Hook.doAction('learn_press_course_initialize', LP.$LP_Course);
            $(window).trigger('resize.check-lines');
        });

        //parent.window.location.href = parent.window.location.href;

    });

    $(document).ajaxComplete(function (a, b, c) {
        if (c.data && c.data.match(/lp-ajax=/)) {
            $(document).trigger('ready');
            $(window).trigger('resize');
            LP.unblockContent();
            $('.learn-press-tooltip').LP_Tooltip({offset: [24, 24]});
        }
    }).ajaxError(function () {
        LP.unblockContent();
    });

})(jQuery);