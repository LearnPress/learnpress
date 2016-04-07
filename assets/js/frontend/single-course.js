/**
 * Single course functions
 */
if (typeof LearnPress == 'undefined') {
	window.LearnPress = {}
}
;(function ($) {
	"use strict";
	LearnPress.Course = $.extend(
		LearnPress.Course || {}, {
			finish: function (data, callback) {
				LearnPress.$Course && LearnPress.$Course.finishCourse({data: data, success: callback});
			}
		}
	);
	var LearnPress_View_Course = window.LearnPress_View_Course = Backbone.View.extend({
		$doc             : null,
		$body            : null,
		courseItems      : null,
		courseItemsView  : null,
		el               : '.course-summary',
		events           : {
			//'click .curriculum-sections .section-content > li a': '_loadLesson',
			//'click .course-item-nav a': '_loadLesson',
			'click #learn-press-finish-course': '_finishCourse'
		},
		initialize       : function (args) {
			this.courseItems = new $.LP_Course_Item.Collection();
			this.courseItemsView = new $.LP_Course_Item_List_View({model: this.courseItems});

			_.bindAll(this, '_finishCourse', '_sanitizeProgress', 'completeLesson');
			this.$doc = $(document);
			this.$body = $(document.body);
			LearnPress.Hook.addFilter('learn_press_before_load_item', function ($view) {
				LearnPress.MessageBox.blockUI();
				if ($view.model.get('type') == 'lp_quiz') {
					var redirect = LearnPress.Hook.applyFilters('learn_press_course_item_redirect_url', $('.course-item-' + $view.model.get('id') + ' a').prop('href'), $view);
					if (redirect !== false) {
						var win = window.open(redirect, '_blank');
						try{win.focus();}catch(e){}
					}
				}
				return true;
			});
			LearnPress.Hook
				.addAction('learn_press_item_content_loaded', this.itemLoaded)
				.addAction('learn_press_user_completed_lesson', this.completeLesson)
				.addAction('learn_press_user_passed_course_condition', function () {
				});

			this._sanitizeProgress();


		},
		itemLoaded       : function ($content, $view) {
			LearnPress.toElement('#learn-press-course-lesson-heading');
			LearnPress.MessageBox.hide();
		},
		completeLesson   : function (response, that) {
			if (response && response.result == 'success') {
				var $button = this.$('.complete-lesson-button').addClass('completed').prop('disabled', true).html(response.button_text);
				$('.course-item-' + response.id).addClass('item-completed');
				if (response.course_result) {
					if (response.can_finish) {
						this.$('#learn-press-finish-course').removeClass('hide-if-js');
						LearnPress.Hook.doAction('learn_press_user_passed_course_condition', response, this, that);
					}
					if (response.message) {
						$(response.message).insertBefore($button);
					}
					this.updateProgress(response);
				}
			}
		},
		updateProgress   : function (data) {
			$('.lp-course-progress')
				.attr({
					'data-value': data.course_result
				})
			this._sanitizeProgress();
		},
		_loadLesson      : function (e) {
			this.loadLesson($(e.target).attr('href'));
		},
		loadLesson       : function (permalink, args) {
			var that = this;
			LearnPress.Hook.doAction('learn_press_before_load_lesson', permalink, this);
			args = $.extend({
				success: function () {
					return true;
				},
				error  : function () {
				}
			}, args || {})

			$.ajax({
				url    : permalink,
				success: function (response) {
					var ret = true;
					$.isFunction(args.success) && ( ret = args.success.call(this, response) );
					if (ret === true) {
						var $html = $(response),
							$newLesson = $html.find('#learn-press-course-lesson-summary'),
							$newHeading = $html.find('#learn-press-course-lesson-heading');

						$('title').html($html.filter('title').text());
						$('#learn-press-course-description-heading, #learn-press-course-lesson-heading').replaceWith($newHeading)
						$('#learn-press-course-description, #learn-press-course-lesson-summary').replaceWith($newLesson);

						LearnPress.Hook.doAction('learn_press_load_lesson_completed', permalink, that);
						LearnPress.Hook.doAction('learn_press_lesson_content_loaded', $html, this);

					}
				},
				error  : function () {
					// TODO: handle the error here
					LearnPress.MessageBox.hide();
				}
			})
		},
		_finishCourse    : function (e) {
			var that = this,
				$button = $(e.target),
				data = $button.data();
			data = LearnPress.Hook.applyFilters('learn_press_user_finish_course_data', data);
			if (data && data.id) {
				$button.prop('disabled', true);
				this.finishCourse({
					data   : data,
					success: function (response) {
						LearnPress.Hook.applyFilters('learn_press_finish_course_params', response);

						if (response && response.result == 'success') {
							that.$('#learn-press-finish-course, .complete-lesson-button').remove();
							LearnPress.Hook.doAction('learn_press_finish_course', response);
						}
						if (response.message) {
							LearnPress.alert(response.message, function () {
								if (response.redirect) {
									LearnPress.reload(response.redirect);
								}
							});
						} else {
							if (response.redirect) {
								LearnPress.reload(response.redirect);
							}
						}
					}
				});
			}
		},
		finishCourse     : function (args) {
			args = args || {};
			var _do = function (e) {
					if (e) {
						LearnPress.doAjax({
							prefix : '',
							data   : data,
							success: _success
						});
					}
				},
				_success = function (response) {
					$.isFunction(args.success) && args.success.call(that, response);
				},
				that = this,
				data = $.extend({
					'lp-ajax': 'finish_course'
				}, args.data || {});
			LearnPress.confirm(single_course_localize.confirm_finish_course, _do);
		},
		_sanitizeProgress: function () {
			var $el = $('.lp-course-progress'),
				$progress = $('.lp-progress-value', $el),
				$passing = $('.lp-passing-conditional', $el),
				value = parseFloat($el.attr('data-value')),
				passing_condition = parseFloat($el.attr('data-passing-condition')),
				_done = function () {
					var progress = parseInt($progress.css('width')),
						passing = parseInt($passing.css('left'));

					if (value >= passing_condition) {
						$el.addClass('passed');
					}
				};
			$progress.css('width', value + '%').find('span span').html(value);
			setTimeout(_done, 500);

		}
	});

	$(document).ready(function () {
		//LearnPress.Course.init( $(this), $(document.body) );
		LearnPress.$Course = new LearnPress_View_Course();
	});
})(jQuery);