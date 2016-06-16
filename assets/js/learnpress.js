if (typeof LearnPress == 'undefined') window.LearnPress = {};
;
(function ($) {

	$.fn.outerHTML = function () {
		// IE, Chrome & Safari will comply with the non-standard outerHTML, all others (FF) will have a fall-back for cloning
		return (!this.length) ? this : (this[0].outerHTML || (function (el) {
			var div = document.createElement('div');
			div.appendChild(el.cloneNode(true));
			var contents = div.innerHTML;
			div = null;
			return $(contents);
		})(this[0]));

	}
	$.extend(LearnPress, {
		complete_lesson: function (lesson, user_id) {
			$.ajax({
				type    : "POST",
				dataType: 'html',
				url     : ajaxurl,
				data    : {
					action : 'learnpress_complete_lesson',
					lesson : lesson,
					user_id: user_id
				},
				success : function (response) {
					var response = LearnPress.parseJSON(response);
					response = $(document).triggerHandler('learn_press_user_complete_lesson', response);
					if(response.result == 'success'){
						$('.course-item-' + lesson).addClass('item-completed');
					}
					if (response.url) {
						LearnPress.reload(response.url)
					} else {
						LearnPress.reload();
					}
				}
			})
		},
		retake_course  : function (course_id, user_id) {
			$.ajax({
				type   : 'POST',
				url    : '',
				data   : {
					action   : 'learn_press_retake_course',
					course_id: course_id,
					user_id  : user_id
				},
				success: function (response) {
					if (response) {
						if (response.message) alert(response.message);
						if (response.redirect)
							LearnPress.reload(response.redirect)
					}
				}
			});
		},
		finishCourse  : function (course_id, callback) {
			if (!confirm(confirm_finish_course.confirm_finish_course)) return;
		},

		load_lesson: function (permalink, args) {
			args = $.extend({
				success: function () {
				},
				error  : function () {
				}
			}, args || {})
			$.ajax({
				url    : permalink,
				success: function (response) {
					var $response = $(response),
						$new_lesson = $('.course-content', $response);
					if ($new_lesson.length) {
						$('.course-content').replaceWith($new_lesson);
						$('title').html($response.filter('title').text());
						$.isFunction(args.success) && args.success.call(this, response)
					} else {
						if (!( $.isFunction(args.error) && args.error.call(this, response) )) {
							alert("UNKNOW ERROR")
						}
					}
				}
			})
		}
	});

	var $doc = $(document),
		$win = $(window);

	function complete_lesson(event) {
		event.preventDefault();
		if (!confirm(learn_press_js_localize.confirm_complete_lesson)) return;
		var lesson = $(this).attr('data-id');
		LearnPress.complete_lesson(lesson);
	}

	function finish_course() {
		var $button = $(this),
			course_id = $button.data('id');
		LearnPress.finish_course(course_id);
	}

	function retake_course() {
		if (!confirm(learn_press_js_localize.confirm_retake_course)) return;
		var $button = $(this),
			course_id = $button.data('id');
		LearnPress.retake_course(course_id);
	}

	function load_lesson(evt) {
		return;
		evt.preventDefault();
		var $link = $(this),
			$parent = $link.parent(),
			permalink = $link.attr('href');
		if (!$link.data('id')) return false;
		if ($parent.hasClass('current')) return false;
		if ($parent.hasClass('course-lesson')) {
			$('.curriculum-sections .course-lesson.loading').removeClass('loading')
			$parent.addClass('loading')
		}
		history.pushState({}, '', permalink);
		LearnPress.load_lesson(permalink, {
			success: function () {
				$('.curriculum-sections .course-lesson.current').removeClass('current')
				if ($parent.hasClass('course-lesson')) {
					$parent.removeClass('loading');
					$parent.addClass('current');
				} else {
					$('.curriculum-sections .course-lesson a[lesson-id="' + $link.data('id') + '"]').parent().addClass('current')
				}
				$('body, html').fadeIn(100).delay(300).animate({scrollTop: $('.course-content').offset().top - 50}, 'slow');
			}
		});
	}

	function _ready() {
		/*$doc
			.on('click', '.complete-lesson-button', complete_lesson)
			.on('click', '#finish-course', finish_course)
			.on('click', '#learn_press_retake_course', retake_course)
			.on('click', '.course-content-lesson-nav a', load_lesson)
			.on('click', '.section-content .course-lesson a', load_lesson);
*/
		$('.lesson-quiz-icon', '.section-content').each(function () {
			var $target = $(this), $parent = $target.closest('li');
			$target.tipsy({
				title  : function () {
					var tip = undefined;
					if ($parent.hasClass('completed')) {
						tip = $('#lesson-quiz-tip-completed-' + $(this).next().data('id')).html();
					} else if ($parent.hasClass('current')) {
						tip = $('#lesson-quiz-tip-current-' + $(this).next().data('id')).html();
					}
					if (tip == undefined) {
						tip = $('#lesson-quiz-tip-message-' + $(this).next().data('id')).html();
					}
					return tip;
				},
				gravity: 's',
				fade   : true,
				html   : true
			})
		});
	}

	$doc.ready(_ready);

})(jQuery);

;
jQuery(document).ready(function ($) {
	$('#clear_previous_submissions').click(function (event) {
		event.preventDefault();
		var $this = $(this);
		var defaulttxt = $this.html();
		$this.prepend('<i class="fa fa-spinner fa-spin">&nbsp;</i>');

		$.ajax({
			type   : "POST",
			url    : ajaxurl,
			data   : {
				action  : 'clear_previous_submissions',
				id      : $this.attr('data-id'),
				security: $this.attr('data-security')
			},
			cache  : false,
			success: function (html) {
				$this.find('i').remove();
				$this.html(html);
				setTimeout(function () {
					location.reload();
				}, 3000);
			}
		});

	});

	$.ajaxSetup({cache: false});
	$("#btn_roll").click(function () {
		event.preventDefault();
		var post_link = $(this).attr("rel");
		$("#course_main").html("loading...");
		$("#course_main").load(post_link);
		return false;
	});
});

jQuery(document).ready(function ($) {
	$('.quiz-curriculum').click(function (event) {
		event.preventDefault();

		$.ajax({
			type   : "POST",
			url    : ajaxurl,
			data   : {
				action: 'learnpress_list_quiz'
			},
			success: function (html) {
				$('.course-content').html(html);
			}
		})
	})
});

jQuery(document).ready(function ($) {
	$('.list-quiz-question').click(function (event) {
		event.preventDefault();
		var $this = $(this);

		$.ajax({
			type   : "POST",
			url    : ajaxurl,
			data   : {
				action     : 'learnpress_load_quiz_question',
				course_id  : $('.quiz-main').attr('course-id'),
				quiz_id    : window.quiz_id,
				question_id: $this.attr('question-id')
			},
			success: function (html) {
				$('.quiz-question').html(html);
				$('.qq').removeClass('current');
				var cl = ".qq:nth-child(" + $this.attr('question-index') + ")";
				$(cl).addClass('current');
				//$this.attr('question-id').addClass('active');

				if (jQuery('.quiz-questions li').first().hasClass('current')) {
					jQuery('.button-prev-question').addClass('hidden');
				} else if ($('.button-prev-question').hasClass('hidden')) {
					$('.button-prev-question').removeClass('hidden')
				}


				if (jQuery('.quiz-questions li').last().hasClass('current')) {
					jQuery('.button-next-question').addClass('hidden');
				} else if ($('.button-next-question').hasClass('hidden')) {
					$('.button-next-question').removeClass('hidden');
				}
			}
		});
	});
});

jQuery(document).ready(function ($) {
	$('.lesson_curriculum').click(function (event) {
		event.preventDefault();
		var $this = $(this);
		var $url = $(this).attr('href');
		var link = window.location.href;

		$.ajax({
			type   : "POST",
			url    : $url, //ajaxurl,
			data   : {
				action   : 'learnpress_load_lesson_content',
				lesson_id: $(this).attr('lesson-id')
			},
			success: function (html) {
				LearnPress.log($url);
				history.pushState({}, '', $url);
				$('.course-content').html($('.course-content', html).html());
				$('html, body').animate({scrollTop: $('.course-content').offset().top}, 'slow');
				$('.button-prev-lesson').removeClass('hidden');
				$('.button-complete-lesson').removeClass('hidden');
				$('.button-next-lesson').removeClass('hidden');
				$('.course-curriculum').find('.current').removeClass('current');
				$this.addClass('current');
			}
		});
	});
});

jQuery(document).ready(function ($) {
	$('.button-finish-quiz').click(function ($event) {
		return;
		$event.preventDefault();
		var $temp = jQuery(".question-form").serializeArray();
		var $question_answer = 1;
		if (0 < $temp.length) {
			$question_answer = $temp[0].value;
		}
		var $question_id = jQuery('.quiz-questions').find('li.current a').attr('question-id');

		$.ajax({
			type   : "POST",
			url    : ajaxurl,
			data   : {
				action         : 'learnpress_finish_quiz',
				course_id      : $('.quiz-main').attr('course-id'),
				quiz_id        : $(this).attr('quiz-id'),
				question_id    : $question_id,
				question_answer: $question_answer
			},
			success: function (html) {
				LearnPress.log(html);
				location.reload(true);
			}
		})
	})
});

jQuery(document).ready(function ($) {
	$('#lpr-join-event').click(function (event) {
		event.preventDefault();
		var $this = $(this);

		$.ajax({
			type   : "POST",
			url    : ajaxurl,
			data   : {
				action  : 'learnpress_join_event',
				event_id: $(this).attr('event-id')
			},
			success: function (html) {
				$this.html(html);
				$this.removeAttr('id').attr('id', 'lpr-not-going');
			}
		});
	});
});

jQuery(document).ready(function ($) {
	$('#lpr-not-going').click(function (event) {
		event.preventDefault();
		var $this = $(this);

		$.ajax({
			type   : "POST",
			url    : ajaxurl,
			data   : {
				action  : 'learnpress_not_going',
				event_id: $(this).attr('event-id')
			},
			success: function (html) {
				$this.html(html);
				$this.removeAttr('id').attr('id', 'lpr-join-event');
			}
		});
	});
});


jQuery(document).ready(function ($) {
	$('.course-free-button').click(function (event) {
		event.preventDefault();
		var $this = $(this);
		$.ajax({
			type   : "POST",
			url    : ajaxurl,
			data   : {
				action   : 'learnpress_take_free_course',
				course_id: $this.attr('course-id')
			},
			success: function (html) {
				if (html == 'not prerequisite') {
					//jAlert('You have to complete prereqisite courses before taking this course');
					$('.error-notice').html('You have to complete prereqisite courses before taking this course');
				} else if (html == 'not logged in') {
					$('.error-notice').html('You have to log in to take this course');
				} else {
					location.reload(true);
				}
			}
		})
	});

	$('#myModal').on('shown.bs.modal', function () {
		$('#myInput').focus()
	})
});

jQuery(document).ready(function ($) {
	$('.button-prev-lesson').on("click", function (event) {
		event.preventDefault();
		var $lesson_link = $('.course-curriculum').find('.current').parent().prevAll('.course-lesson').first().find('a').attr('href');
		var $current = $('.course-curriculum').find('.current');

		if ($lesson_link != '') {
			window.location.replace($lesson_link);
		} else {
			$.ajax({
				type   : "POST",
				url    : ajaxurl,
				data   : {
					action   : 'learnpress_load_prev_lesson',
					lesson_id: $('.course-curriculum').find('.current').parent().prevAll('.course-lesson').first().find('a').attr('lesson-id')
				},
				success: function (html) {
					$('.course-content').html(html);
					$current.removeClass('current');
					$current.parent().prevAll('.course-lesson').first().find('a').addClass('current');
					var $forum_link = $('.course-curriculum').find('.current').attr('data-link');
					$('.button-forum').removeClass('hidden').attr('href', $forum_link);
				}
			})
		}
	});
});

jQuery(document).ready(function ($) {
	$('.button-next-lesson').on("click", function (event) {
		event.preventDefault();
		var $lesson_link = $('.course-curriculum').find('.current').parent().nextAll('.course-lesson').first().find('a').attr('href');
		var $current = $('.course-curriculum').find('.current');

		if ($lesson_link != '') {
			window.location.replace($lesson_link);
		} else {
			$.ajax({
				type   : "POST",
				url    : ajaxurl,
				data   : {
					action   : 'learnpress_load_next_lesson',
					lesson_id: $('.course-curriculum').find('.current').parent().nextAll('.course-lesson').first().find('a').attr('lesson-id')
				},
				success: function (html) {
					$('.course-content').html(html);
					$current.removeClass('current');
					$current.parent().nextAll('.course-lesson').first().find('a').addClass('current');
				}
			})
		}
	});
});


jQuery(document).ready(function ($) {

	$('.button-finish-course').click(function (event) {
		event.preventDefault();
		var $this = $(this);
		var $certi_link = $this.attr("certi_link");
		LearnPress.log($certi_link);
		$.ajax({
			type   : "POST",
			url    : ajaxurl,
			data   : {
				action   : 'learnpress_finish_course',
				course_id: $this.attr('course-id')
			},
			success: function (html) {
				LearnPress.log(html);
				if (html == 'success') {
					if ($certi_link) {
						window.location.replace($certi_link);
					} else {
						jAlert('Congratulation ! You have finished this course.')
					}
				} else {
					jAlert(html);
				}
			}
		})
	});

	var $payment_form = $('form[name="learn_press_payment_form"]');
	$('input[name="payment_method"]', $payment_form).click(function () {
		var $this = $(this);
		if ($this.is(":checked")) {
			$this.closest('li').find('.learn_press_payment_form').slideDown();
			$('.learn_press_payment_form', $this.closest('li').siblings()).slideUp();
		}
	});
	$('#learn_press_take_course')
		.click(function () {
			var button = $(this),
				payment_methods = $('input[name="payment_method"]', $payment_form),
				take = false,
				payment = payment_methods.filter(":checked").val();
			if (0 == payment_methods.length) {
				take = true;
			} else if (1 == payment_methods.length) {
				payment_methods.attr('checked', true);
				take = true;
			} else {
				if ($payment_form.is(':visible')) {
					if (!payment) {
						alert(learn_press_js_localize.no_payment_method);
						return;
					} else {
						take = true
					}
				} else {
					$payment_form.show();
					return;
				}
			}
			if (!take) return;
			$(this).html($(this).data('loading-text') || 'Processing').attr('disabled', true);
			if ($payment_form.triggerHandler('learn_press_place_order') !== false && $payment_form.triggerHandler('learn_press_place_order_' + payment) !== false) {
				var data = {
					action        : 'learnpress_take_course',
					payment_method: payment_methods.filter(":checked").val(),
					course_id     : button.data('id'),
					data          : $payment_form.serialize()
				}

				$.ajax({
					url     : ajaxurl,
					type    : 'POST',
					dataType: 'html',
					data    : $payment_form.serialize(),
					success : function (res) {
						var matches = res.match(/<!-- LP_AJAX_START -->(.*)<!-- LP_AJAX_END -->/),
							message = '';
						if (matches && matches[1]) {
							var json = JSON.parse(matches[1]);
							if (json) {
								if (json.redirect && (json.result.toLowerCase() == 'success')) {
									window.location.href = json.redirect;
									return;
								} else {
									message = json.message
								}
							} else {
								message = matches[1];
							}
						} else {
							message = res
						}
						if (message) {
							alert(message);
						}
						button.removeAttr('disabled').html(button.data('text'))
					}
				});
			}
			return false;
		});
});

jQuery(document).ready(function ($) {
	$('#wp-admin-bar-be_teacher').click(function () {
		$.ajax({
			url    : ajaxurl,
			data   : {
				action: 'learnpress_be_teacher'
			},
			success: function () {
				alert(learn_press_js_localize.you_are_instructor_now);
				setTimeout(function () {
					location.reload();
				}, 500);
			}
		})
	});
});

// jQuery(document).ready(function ($) {	
// 	$('#question-hint').on('click', function(){		
// 		$('.question-hint-content').fadeToggle();
// 	});
// });
