/**
 * Single course functions
 */
if (typeof LearnPress == 'undefined') {
	window.LearnPress = {}
}
;(function ($) {
	LearnPress.Course = $.extend(
		LearnPress.Course || {}, {
			finish: function (data, callback) {
				data = data || {};
				data['lp-ajax'] = 'finish_course';
				LearnPress.confirm(single_course_localize.confirm_finish_course, function (e) {
					//LearnPress.Hook.applyFilters( 'learn_press_confirm_finish_course', e, data);
					if (e) {
						LearnPress.doAjax({
							prefix : '',
							data   : data,
							success: function (res) {
								console.log(res)
							}
						});
					}
				})
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

			_.bindAll(this, '_sanitizeProgress')
			this.$doc = $(document);
			this.$body = $(document.body);

			LearnPress.Hook.addFilter('learn_press_before_load_item', function ($view) {
				LearnPress.MessageBox.blockUI();
				if ($view.model.get('type') == 'lp_quiz') {
					var redirect = LearnPress.Hook.applyFilters('learn_press_course_item_redirect_url', $('.course-item-'+$view.model.get('id')+' a').prop('href'), $view);
					if ( redirect!== false) {
						var win = window.open(redirect, '_blank');
						win.focus();
					}
				}
				return true;
			});
			LearnPress.Hook.addAction('learn_press_item_content_loaded', function ($content, $view) {
				LearnPress.toElement('#learn-press-course-lesson-heading');
				LearnPress.MessageBox.hide();
			});

			this._sanitizeProgress();


		},
		_loadLesson      : function (e) {
			this.loadLesson($(e.target).attr('href'));
		},
		loadLesson       : function (permalink, args) {
			var that = this;
			console.log('loadLesson')
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
			var $button = $(e.target),
				data = $button.dataToJSON();
			LearnPress.Course.finish(data);
		},
		finishCourse     : function () {
			if (!confirm(confirm_finish_course.confirm_finish_course)) return;
			$.ajax({
				type   : "POST",
				url    : ajaxurl,
				data   : {
					action   : 'finish_course',
					course_id: course_id
				},
				success: function (response) {
					if (response.finish) {
						LearnPress.reload();
					}
				}
			});
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
					if (progress < $('span', $progress).outerWidth()) {
						$progress.addClass('left')
					} else {
						$progress.removeClass('left')
					}
					if (($el.outerWidth() - passing) < $('span', $passing).outerWidth()) {
						$passing.addClass('right')
					} else {
						$passing.removeClass('right')
					}
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