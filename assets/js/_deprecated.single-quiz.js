;(function($) {
	LearnPress.singleQuizInit = function (args) {
		$.alerts.overlayOpacity = 0.3;
		$.alerts.overlayColor = '#000';
		var finish = false;
		var $question_answer = '';
		var defaults = {
			quiz_id       : 0,
			question_id   : 0,
			questions     : [],
			time_remaining: 0,
			quiz_started  : false,
			quiz_url      : null
		};
		this.args = $.extend({}, defaults, args);
		var current_question_id = this.args.question_id,
			quiz_finished = this.args.quiz_completed,
			self = this,
			countdown = undefined;

		function get_next_question_id() {
			var pos = $.inArray(current_question_id, self.args.questions);
			if (pos != -1) {
				pos++;
				if (pos < self.args.questions.length) {
					return self.args.questions[pos];
				}
			}
			return undefined;
		}

		function get_prev_question_id() {
			var pos = $.inArray(current_question_id, self.args.questions);
			if (pos != -1) {
				pos--;
				if (pos >= 0) {
					return self.args.questions[pos];
				}
			}
			return undefined;
		}

		function next_question() {
			$('#nav-question-form').trigger('submit', {
				nav    : 'next',
				next_id: get_next_question_id(),
				url    : $(this).data('url')
			});
		}

		function prev_question() {
			$('#nav-question-form').trigger('submit', {
				nav    : 'prev',
				prev_id: get_prev_question_id(),
				url    : $(this).data('url')
			});
		}

		function update_nav_buttons() {

			if (self.args.quiz_started && !quiz_finished) {
				$('.button-start-quiz').hide();
				if ($.inArray(current_question_id, self.args.questions) == 0) {
					$('.quiz-question-nav-buttons button.prev-question').hide();
				} else {
					$('.quiz-question-nav-buttons button.prev-question').show();
				}
				$('.quiz-question-nav-buttons button.next-question').show();

			} else if (quiz_finished) {
				$('.quiz-question-nav-buttons button').hide();
				$('.quiz-questions .current').removeClass('current');
				$('.button-finish-quiz').hide();
			} else {
				$('.quiz-question-nav-buttons button').hide();
			}
		}

		function finish_quiz() {
			if (!quiz_finished) {
				$('.single-quiz').block_ui();
				var data = {
					action         : 'learn_press_submit_answer',
					quiz_id        : self.args.quiz_id,
					question_id    : current_question_id,
					question_answer: $('input, select, textarea', $('.quiz-question-nav .question-' + current_question_id)).toJSON(),
					data           : $('#nav-question-form').children('input, select, textarea').toJSON(),
					finish         : true
				};
				$.ajax({
					url     : ajaxurl,
					data    : data,
					type    : 'post',
					dataType: 'html',
					success : function (code) {
						var response = LearnPress.parse_json(code);
						if (response && response.redirect) {
							LearnPress.reload(response.redirect);
						}
						$('.single-quiz').unblock_ui();
					}
				});
			}
		}

		function on_timeout() {
			if (!quiz_finished) {
				jAlert(learn_press_js_localize.quiz_time_is_over_message, learn_press_js_localize.quiz_time_is_over_title, function () {
					finish_quiz();
				});
			}
		}

		function init_countdown_timer() {
			countdown = $("#quiz-countdown");
			countdown.backward_timer({
				seconds     : self.args.time_remaining,
				format      : 'm%:s%',
				on_exhausted: function (timer) {
					on_timeout.call(this, timer);
				},
				on_tick     : function (timer) {
					var color = (timer.seconds_left <= 5) ? "#F00" : ""
					timer.target.css('color', color);
				}
			});
			if (self.args.quiz_started) {
				countdown.backward_timer('start');
			}
		}

		function start_quiz() {
			var url = window.location.href,
				data = $.extend({
					action : 'learn_press_start_quiz',
					quiz_id: self.args.quiz_id
				}, $('input, textarea, select', '#nav-question-form').toJSON() || {});
			$('.single-quiz').block_ui();
			$.ajax({
				url     : url,
				data    : data,
				dataType: 'html',
				type    : 'post',
				success : function (code) {
					var res = LearnPress.parse_json(code);

					if (res.redirect) {
						LearnPress.reload(res.redirect);
						return;
					}
					$('.single-quiz').unblock_ui();
					var new_question = $('.quiz-question-nav .question-' + current_question_id, res),
						last_question = $('.quiz-question-nav .lpr-question-wrap:last');
					if (last_question.get(0)) {
						new_question.insertAfter(last_question);
					} else {
						$('#nav-question-form').prepend(new_question);
					}
					$(this).hide().siblings().show();
					$('.button-start-quiz').hide();
					$('.button-finish-quiz').show();
					countdown.backward_timer('start');
					self.args.quiz_started = true;
					$('.single-quiz').addClass('quiz-started');
					update_nav_buttons();
				}
			});
		}

		function retake_quiz(evt) {
			evt.preventDefault();
			if (!confirm(learn_press_js_localize.confirm_retake_quiz)) return;
			$('.single-quiz').block_ui();
			$.ajax({
				type    : "POST",
				url     : ajaxurl,
				data    : {
					action : 'learnpress_retake_quiz',
					quiz_id: $(this).data('id')
				},
				dataType: 'html',
				success : function (response) {
					response = LearnPress.parse_json(response);
					if (response && response.error) {
						alert(response.message)
					} else {
						if (response.redirect) {
							LearnPress.reload(response.redirect);
						}
					}
					$('.single-quiz').unblock_ui();
				}
			});
		}

		function submit_question(evt, params) {
			var next_id = params.id,
				nav = params.nav,
				url = params.url,
				$form = $(this);
			if ($form.triggerHandler('post_question_answer') !== false) {
				$('.single-quiz').block_ui();
				var data = {
					action         : 'learn_press_submit_answer',
					quiz_id        : self.args.quiz_id,
					question_id    : current_question_id,
					question_answer: $('input, select, textarea', this).toJSON(),
					data           : $form.children('input, select, textarea').toJSON(),
					next_id        : nav == 'next' ? params.next_id : params.prev_id
				};
				$.post(url, data, function (data) {
					//var response = LearnPress.parse_json( data );
					// we are in the last of question
					/*if ( nav == 'next' ){
					 if( ! params.next_id ) {
					 countdown.backward_timer("cancel");
					 if (res.quiz_completed) {
					 window.location.href = window.location.href;
					 return;
					 }
					 quiz_finished = true;
					 $('.quiz-questions .current').removeClass('current');
					 $('.quiz-question-nav .lp-question-wrap').hide();
					 $('.quiz-question-nav').append(res.html);
					 countdown.backward_timer("cancel");
					 }
					 }*/
					var $html = $(data);
					$('.quiz-question-nav').replaceWith($html.find('.quiz-question-nav'));
					$('.quiz-questions')
						.find('.sibdebar-quiz-question-' + $html.find('.quiz-questions .current .list-quiz-question').attr('question-id'))
						.addClass('current')
						.siblings('.current').removeClass('current');

					history.pushState({}, '', url);

					current_question_id = ( nav == 'next' ) ? params.next_id : params.prev_id;
					$('.single-quiz').unblock_ui();
					return
					if (url) {
						LearnPress.reload(url);
					}
					update_nav_buttons();
					$block.fadeOut();
				}, 'html');
				return false;
			}
		}

		function show_answer() {
			var data = {
				action         : 'learn_press_show_answer',
				quiz_id        : self.args.quiz_id,
				question_id    : current_question_id,
				question_answer: $('input, select, textarea', $('.quiz-question-nav .question-' + current_question_id)).toJSON(),
			};
			$('.single-quiz').block_ui();
			$.post(ajaxurl, data, function (res) {
				question_answer_show = $(res.html);
				$('.question-' + current_question_id + ' ul').replaceWith(question_answer_show);
				$('.single-quiz').unblock_ui();
			}, 'json');
		}

		$(document).on('click', '.quiz-question-nav-buttons button', function (evt) {
			var $button = $(this),
				nav = $button.data('nav');
			switch (nav) {
				case 'prev':
					prev_question.call(this);
					break;
				case 'next': //
					next_question.call(this);
					break;
			}

		}).on('click', '.button-finish-quiz', function () {
			if (!confirm(learn_press_js_localize.confirm_finish_quiz)) return;
			finish_quiz();
		}).on('click', '.check_answer', function () {
			show_answer();
		}).on('click', '.button-retake-quiz', retake_quiz)
			.on('click', '.button-start-quiz', function () {
				start_quiz.call(this)
			})
			.on('submit', '#nav-question-form', submit_question);
		update_nav_buttons();
		init_countdown_timer();
	}
})(jQuery);