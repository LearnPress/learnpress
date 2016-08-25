if (typeof LP == 'undefined') var LP = {};
(function ($) {
	"use strict";
return;
	var LP_Model_Quiz = window.LP_Model_Quiz = Backbone.Model.extend({
		defaults           : {
			//question_id: 0
		},
		data               : null,
		view               : false,
		url                : function () {
		},
		urlRoot            : '',
		questions          : null,
		initialize         : function () {

			this.createQuestionsList();

		},
		createQuestionsList: function () {
			this.questions = new LP_Collection_Questions();
			_.each(this.get('questions'), function (args, i) {
				var $model = new LP_Model_Question($.extend({
					quiz_id: this.get('id'),
					user_id: this.get('user_id'),
				}, args));
				$model.urlRoot = this.get('ajaxurl');
				$model.view = this.view;
				this.questions.add($model);
			}, this);
		},
		next               : function (callback) {
			if (!this.isLast()) {
				var next_id = this.findNext(),
					question = this.questions.findWhere({id: next_id}),
					that = this;
				question.submit({
					data    : {
						save_id        : that.get('question_id'),
						question_answer: this.view.$('form').serializeJSON(),
						time_remaining : that.get('time_remaining')
					},
					complete: function () {
						that.set('question_id', next_id);
						$.isFunction(callback) && callback.apply(that);
						LP.Hook.doAction('learn_press_next_question', next_id, that);
					}
				});
			}
		},
		prev               : function (callback) {
			if (!this.isFirst()) {
				var prev_id = this.findPrev(),
					question = this.questions.findWhere({id: prev_id}),
					that = this;
				//if (!question.get('content')) {
				question.submit({
					data    : {
						save_id        : that.get('question_id'),
						question_answer: this.view.$('form').serializeJSON(),
						time_remaining : that.get('time_remaining')
					},
					complete: function () {
						that.set('question_id', prev_id);
						$.isFunction(callback) && callback.apply(that);
						LP.Hook.doAction('learn_press_previous_question', prev_id, that);
					}
				});

			}
		},
		select             : function (id, callback) {
			var question = this.questions.findWhere({id: id}),
				that = this;
			question && question.submit({
				data    : {
					save_id        : that.get('question_id'),
					question_answer: this.view.$('form').serializeJSON(), //$('input, select, textarea', this.view.$('form')).toJSON(),
					time_remaining : that.get('time_remaining')
				},
				complete: function (response) {
					that.set('question_id', id);
					$.isFunction(callback) && callback.apply(that, [response])
				}
			});
		},
		getQuestionPosition: function (question_id) {
			question_id = question_id || this.get('question_id');
			return _.indexOf(this.getIds(), question_id);
		},
		countQuestions     : function () {
			return this.questions.length;
		},
		isLast             : function (question_id) {
			question_id = question_id || this.get('question_id');
			return this.getQuestionPosition(question_id) == (this.countQuestions() - 1);
		},
		isFirst            : function (question_id) {
			question_id = question_id || this.get('question_id');
			return this.getQuestionPosition(question_id) == 0;
		},
		findNext           : function (question_id) {
			question_id = question_id || this.get('question_id');
			var ids = this.getIds(),
				pos = this.getQuestionPosition(question_id);
			pos++;
			if (typeof ids[pos] == 'undefined') return false;
			return ids[pos];
		},
		findPrev           : function (question_id) {
			question_id = question_id || this.get('question_id');
			var ids = this.getIds(),
				pos = this.getQuestionPosition(question_id);
			pos--;
			if (typeof ids[pos] == 'undefined') return false;
			return ids[pos];
		},
		current            : function () {
			return this.questions.findWhere({id: parseInt(this.get('question_id'))});
		},
		getIds             : function () {
			return $.map(this.get('questions'), function (i, v) {
				return parseInt(i.id)
			});
		}
	});

	var LP_Model_Question = window.LP_Model_Question = Backbone.Model.extend({
		defaults  : {
			//question_id: 0
		},
		data      : null,
		view      : false,
		url       : function () {
			return this.urlRoot;
		},
		urlRoot   : '',
		initialize: function () {
		},
		element   : function () {
			return $(this.get('content'));
		},
		submit    : function (args) {
			var that = this;
			args = $.extend({
				complete: null,
				data    : {}
			}, args || {});
			this.fetch({
				data    : $.extend({
					action     : 'learnpress_load_quiz_question',
					user_id    : this.get('user_id'),
					quiz_id    : this.get('quiz_id'),
					question_id: this.get('id')
				}, args.data || {}),
				complete: (function (e) {
					var response = LP.parseJSON(e.responseText);
					if (response.result == 'success') {
						//if (!that.get('content')) {
						that.set(response.question);
						if (response.permalink) {
							LP.setUrl(response.permalink);
						}
						//}
						$.isFunction(args.complete) && args.complete.call(that, response);
					}
				})
			});
		},
		check     : function (args) {
			var that = this;
			if ($.isFunction(args)) {
				args = {
					complete: args
				}
			} else {
				args = $.extend({
					complete: null,
					data    : {}
				}, args || {});
			}
			LP.doAjax({
				data   : $.extend({
					'lp-ajax'      : 'check-question',
					user_id        : this.get('user_id'),
					quiz_id        : this.get('quiz_id'),
					question_id    : this.get('id'),
					save_id        : this.get('id'),
					question_answer: $('form#learn-press-quiz-question').serializeJSON()
				}, args.data || {}),
				success: function (response, raw) {
					that.set('checked', response.checked);
					$.isFunction(args.complete) && args.complete.call(that, response)
				}
			})
		}
	});

	var LP_Collection_Questions = window.LP_Collection_Questions = Backbone.Collection.extend({
		url  : 'admin-ajax.php',
		model: LP_Model_Question
	});

	var LP_View_Quiz = window.LP_View_Quiz = Backbone.View.extend({
		model           : {},
		events          : {
			'click .button-start-quiz'       : '_startQuiz',
			'click .button-finish-quiz'      : '_finishQuiz',
			'click .button-retake-quiz'      : '_retakeQuiz',
			'click .next-question'           : '_nextQuestion',
			'click .prev-question'           : '_prevQuestion',
			'click .check-question'          : '_checkQuestion',
			'click .quiz-questions-list li a': '_selectQuestion',
			'click .hint-question'           : '_hintQuestion',
			'click .explain-question'        : '_explainQuestion'
		},
		el              : '.single-quiz',
		isRendered      : false,
		$buttons        : {},
		initialize      : function (model) {
			this.model = model;
			this.model.view = this;
			this.listenTo(this.model, 'change:question_id', this.changeQuestion);
			this.listenTo(this.model, 'change', this.render);
			this.listenTo(this.model, 'change', this.updateCountdown);

			LP.Hook
				.addAction('learn_press_check_question', function (response, that) {
					that.updateAnswer.apply(that, [response]);
				})
				.addAction('learn_press_update_question_content', function ($newQuestion, $oldQuestion, that) {
					var $current = that.model.current();
					setTimeout(function () {
						if ($current.get('checked')) {
							that.updateAnswer.apply(that, [{checked: $current.get('checked'), result: 'success'}]);
						}
					}, 100)

				});
			_.bindAll(this, 'render', '_timeOver', '_checkQuestion', '_hintQuestion', 'updateAnswer', 'updateCountdown');

			this._create();
			this.render();
			this.updateCountdown(true);
		},
		_create         : function () {
			this.$buttons = {
				start  : this.$('.button-start-quiz'),
				finish : this.$('.button-finish-quiz'),
				retake : this.$('.button-retake-quiz'),
				next   : this.$('.next-question'),
				prev   : this.$('.prev-question'),
				check  : this.$('.check-question'),
				hint   : this.$('.hint-question'),
				explain: this.$('.explain-question'),
			};
			if (this.model.get('status') == 'started') {
				this.initCountdown();

				var $current = this.model.current();
				if ($current) {
					$current.set({
						content: $('#learn-press-quiz-question .question-' + $current.get('id'))
					});
					this._updateQuestion($current.element());
				}
			}
			var that = this;
			this.setButtonsState();
		},
		changeQuestion  : function () {
			var $current = this.model.current();
			if ($current) {
				this._updateQuestion($current.element());
			}
		},
		updateCountdown : function (force) {
			if (!this.model.hasChanged('status') && !force) {
				return;
			}
			if (this.model.get('status') == 'started') {
				if (!this.$countdown) {
					this.initCountdown();
				}
				this.$countdown.backward_timer('start');
			}
		},
		render          : function () {
			if (!this.model.hasChanged('question_id') && !this.model.hasChanged('status')) {
				//return;
			}
			var $question = this.model.current();

			this.setButtonsState();
			switch (this.model.get('status')) {
				case 'started':
					this.$('.quiz-intro').remove();
					this.$('.quiz-countdown').removeClass('hide-if-js');
			}
			if ($question && this.model.get('status') == 'started') {
				this.$('form[name="learn-press-quiz-question"]').html($question.get('content'));
				this.$('#learn-press-quiz-questions li[data-id="' + $question.get('id') + '"]')
					.addClass('current')
					.siblings('.current').removeClass('current');
			}

			this.isRendered = true;
			this.$el.css('visibility', 'visible');
		},
		setButtonsState : function () {
			var hidden = 'hide-if-js',
				current = this.model.current();
			switch (this.model.get('status').toLowerCase()) {
				case 'completed':
					this.$buttons.start.addClass(hidden);
					this.$buttons.finish.addClass(hidden);
					this.$buttons.check.addClass(hidden);
					this.$buttons.retake.removeClass(hidden);
					break;
				case 'started':
					this.$buttons.start.addClass(hidden);
					this.$buttons.finish.removeClass(hidden);
					this.$buttons.retake.addClass(hidden);

					if (this.model.countQuestions() <= 1) {
						this.$buttons.next.addClass(hidden);
						this.$buttons.prev.addClass(hidden);
					} else {
						this.$buttons.next.removeClass(hidden);
						this.$buttons.prev.removeClass(hidden);
						if (this.model.isLast()) {
							this.$buttons.next.addClass(hidden);
							this.$buttons.finish.filter('[data-area="nav"]').removeClass(hidden);
						} else {
							this.$buttons.finish.filter('[data-area="nav"]').addClass(hidden);
						}
						if (this.model.isFirst()) {
							this.$buttons.prev.addClass(hidden);
						}
					}
					if (current && current.get('check_answer')) {
						this.$buttons.check.removeClass(hidden);
					} else {
						this.$buttons.check.addClass(hidden);
					}
					if (current && current.get('hint')) {
						this.$buttons.hint.removeClass(hidden);
					} else {
						this.$buttons.hint.addClass(hidden);
					}
					if (current && current.get('explanation')) {
						this.$buttons.explain.removeClass(hidden);
					} else {
						this.$buttons.explain.addClass(hidden);
					}
					break;
				default:
					this.$buttons.next.addClass(hidden);
					this.$buttons.prev.addClass(hidden);
					this.$buttons.start.removeClass(hidden);
					this.$buttons.finish.addClass(hidden);
					this.$buttons.retake.addClass(hidden);
					this.$('.quiz-questions .qq.current').removeClass('current');
			}

		},
		startQuiz       : function (args) {
			this.block_page();
			args = $.extend({
				complete: false
			}, args || {});

			var that = this,
				data = $.extend({
					'lp-ajax': 'start-quiz',
					quiz_id  : this.model.get('id'),
					nonce    : this.model.get('nonce')
				}, args.data || {});
			LP.doAjax({
				url    : window.location.href,
				data   : data,
				success: function (response, raw) {
					LP.MessageBox.hide();
					var response = LP.Hook.applyFilters('learn_press_start_quiz_results', response, that);
					if (response.result == 'success') {
						that.model.current().set(response.question);
						that.model.set({status: response.data.status, question_id: response.question.id});
						LP.setUrl(response.question.permalink);
					}
					$.isFunction(args.complete) && args.complete.call(that, response)
				}
			});
		},
		finishQuiz      : function (args) {
			this.pause();
			this.block_page();
			args = $.extend({
				complete: false
			}, args || {});

			var that = this,
				data = $.extend({
					'lp-ajax': 'finish-quiz',
					quiz_id  : this.model.get('id'),
					nonce    : this.model.get('nonce')
				}, args.data || {});
			LP.doAjax({
				data   : data,
				success: function (response) {
					var callbackReturn = undefined;
					$.isFunction(args.complete) && ( callbackReturn = args.complete.call(LP.Quiz, response) );
					LP.Hook.doAction('learn_press_finish_quiz', that.model.get('id'), that);
					LP.MessageBox.show(single_quiz_localize.finished_quiz, {
						autohide: 2000,
						onHide  : function () {
							if (callbackReturn && callbackReturn.redirect) {
								LP.reload(callbackReturn.redirect);
							} else if (callbackReturn == undefined && response.redirect) {
								LP.reload(response.redirect);
							}
						}
					});
				}
			});
		},
		retakeQuiz      : function (args) {
			this.block_page();
			args = $.extend({
				complete: false
			}, args || {});

			var that = this,
				data = $.extend({
					'lp-ajax': 'retake-quiz',
					quiz_id  : this.model.get('id'),
					nonce    : this.model.get('nonce')
				}, args.data || {});
			LP.doAjax({
				data   : data,
				success: function (response, raw) {
					LP.MessageBox.hide();
					if (response.result == 'success') {
						$.isFunction(args.complete) && args.complete.call(LP.Quiz, response);
						LP.MessageBox.show(single_quiz_localize.retaken_quiz, {
							autohide: 2000,
							onHide  : function () {
								LP.reload(response.redirect);
							}
						});
					} else {
						LP.alert(response.message);
					}
				}
			});
		},
		updateAnswer    : function (response) {
			if (!response || response.result != 'success') {
				return;
			}
			var $current = this.model.current(),
				$content = $($current.get('content'));
			switch ($current.get('type')) {
				case 'true_or_false':
				case 'single_choice':
				case 'multi_choice':
					$.each(response.checked, function (k, v) {
						var $input = $content.find('input[value="' + v.value + '"]'),
							$li = $input.closest('li').removeClass('answer-true user-answer-false');
						if (v.is_true == 'yes') {
							$li.addClass('answer-true')
						}
						if (response.answered) {
							if (typeof response.answered == 'string') {
								if (response.answered == v.value) {
									$input.prop('checked', true);
								}
							} else if ($.inArray(v.value, response.answered) != -1) {
								$input.prop('checked', true);
							}
						}
						if ($input.is(':checked') && v.is_true == 'yes') {
						} else {
							$li.addClass('user-answer-false');
						}

					});
					$content.addClass('checked').find('input, select, textarea').prop('disabled', true);
					$current.set({
						content: $content
					});
			}
			//LP.Hook.doAction('learn_press_check_question', response, $current);
			this.render();
		},
		_checkQuestion  : function () {
			if (LP.Hook.applyFilters('learn_press_before_check_question', true, this) !== false) {
				var that = this;
				this.$buttons.next.prop('disabled', true);
				this.$buttons.prev.prop('disabled', true);
				this.$buttons.finish.prop('disabled', true);
				this.$buttons.check.prop('disabled', true);
				this.pause();
				this.block_page();
				this.model.current().check({
					complete: function (response) {
						that.$buttons.next.prop('disabled', false);
						that.$buttons.prev.prop('disabled', false);
						that.$buttons.finish.prop('disabled', false);
						that.$buttons.check.prop('disabled', false);
						LP.Hook.doAction('learn_press_check_question', response, that);
						that.resume();
					},
					data    : {nonce: this.model.get('nonce')}
				});
			}
		},
		_hintQuestion   : function (e) {
			e.preventDefault();
			var current = this.model.current();
			if (current && current.get('hint')) {
				$('#learn-press-question-hint-' + current.get('id')).toggleClass('hide-if-js')
			}

		},
		_explainQuestion: function (e) {
			e.preventDefault();
			var current = this.model.current();
			if (current && current.get('explanation')) {
				$('#learn-press-question-explanation-' + current.get('id')).toggleClass('hide-if-js')
			}

		},
		_nextQuestion   : function () {
			if (LP.Hook.applyFilters('learn_press_before_next_question', true, this) !== false) {
				var that = this;
				this.$buttons.next.prop('disabled', true);
				this.$buttons.prev.prop('disabled', true);
				this.$buttons.finish.prop('disabled', true);
				this.pause();
				this.block_page();
				this.model.next(function () {
					that.$buttons.next.prop('disabled', false);
					that.$buttons.prev.prop('disabled', false);
					that.$buttons.finish.prop('disabled', false);
				});
			}
		},
		_prevQuestion   : function () {
			if (LP.Hook.applyFilters('learn_press_before_prev_question', true, this) !== false) {
				var that = this;
				this.$buttons.next.prop('disabled', true);
				this.$buttons.prev.prop('disabled', true);
				this.$buttons.finish.prop('disabled', true);
				this.pause();
				this.block_page();
				this.model.prev(function () {
					that.$buttons.next.prop('disabled', false);
					that.$buttons.prev.prop('disabled', false);
					that.$buttons.finish.prop('disabled', false);
				});
			}
		},
		_selectQuestion : function (e) {
			e.preventDefault();
			var that = this,
				id = $(e.target).closest('.learn-press-question-wrap').data('id');
			if (this.model.get('status') != 'started') {
				return false;
			}
			if (LP.Hook.applyFilters('learn_press_before_select_question', true, that) !== false) {
				this.pause();
				this.$buttons.next.prop('disabled', true);
				this.$buttons.prev.prop('disabled', true);
				this.$buttons.finish.prop('disabled', true);
				this.pause();
				this.model.select(id, function (response) {
					LP.Hook.doAction('learn_press_selected_question', id, that);
					that.$buttons.next.prop('disabled', false);
					that.$buttons.prev.prop('disabled', false);
					that.$buttons.finish.prop('disabled', false);
				});
			}
		},
		_getNonce       : function (field) {
			return this.$('input#' + field + '-nonce').val();
		},
		_startQuiz      : function () {
			var that = this;
			if (LP.Hook.applyFilters('learn_press_before_start_quiz', true, that) !== false) {
				that.$buttons.next.prop('disabled', true);
				that.$buttons.prev.prop('disabled', true);
				that.$buttons.finish.prop('disabled', true);
				LP.MessageBox.blockUI();
				that.startQuiz({
					complete: function (response) {
						LP.MessageBox.hide();
						if (response.message) {
							LP.alert(response.message, function () {
								if (response.redirect) {
									LP.reload(response.redirect)
								}
							});
						} else {
							if (response.redirect) {
								LP.reload(response.redirect)
							}
						}
						that.$buttons.next.prop('disabled', false);
						that.$buttons.prev.prop('disabled', false);
						that.$buttons.finish.prop('disabled', false);
						LP.Hook.doAction('learn_press_start_quiz', response, that);
					}
				});
			}
		},
		_retakeQuiz     : function () {
			var that = this;
			if (LP.Hook.applyFilters('learn_press_before_retake_quiz', true, that) !== false) {
				LP.confirm(single_quiz_localize.confirm_retake_quiz, function (confirm) {
					if (!confirm) {
						return;
					}
					that.$buttons.retake.prop('disabled', true);
					LP.MessageBox.blockUI();
					that.retakeQuiz({
						complete: function (response) {
							LP.MessageBox.hide();
							LP.Hook.doAction('learn_press_user_retaken_quiz', response, that);
						}
					});
				})
			}
		},
		_finishQuiz     : function () {
			var that = this;
			if (LP.Hook.applyFilters('learn_press_before_finish_quiz', true, that) !== false) {
				LP.confirm(single_quiz_localize.confirm_finish_quiz, function (confirm) {
					if (!confirm) {
						return;
					}
					that.$buttons.next.prop('disabled', true);
					that.$buttons.prev.prop('disabled', true);
					that.$buttons.finish.prop('disabled', true);
					LP.MessageBox.blockUI();
					that.finishQuiz({
						data    : {
							save_id        : that.model.get('question_id'),
							question_answer: that.$('form').serializeJSON(),
							time_remaining : that.model.get('time_remaining')
						},
						complete: function (response) {
							LP.MessageBox.hide();
							LP.Hook.doAction('learn_press_user_finished_quiz', response, that);
						}
					});
				});
			}
		},
		_updateQuestion : function ($newQuestion) {
			var $container = this.$('.quiz-question-content form'),
				$oldQuestion = $container.find('.learn-press-question-wrap');
			if ($oldQuestion.length) {
				$oldQuestion.replaceWith($newQuestion);
			} else {
				$container.append($newQuestion);
			}
			LP.Hook.doAction('learn_press_update_question_content', $newQuestion, $oldQuestion, this);
			LP.setUrl($newQuestion.find('input[name="learn-press-question-permalink"]').val());

		},
		initCountdown   : function () {
			var that = this,
				$countdown = this.$countdown;
			if (!$countdown) {
				this.$countdown = $("#quiz-countdown-value");
				this.$countdown.backward_timer({
					seconds     : parseInt(this.model.get('time_remaining')),
					format      : that.model.get('time_format'),
					on_exhausted: function (timer) {
						that._timeOver(timer)
					},
					on_tick     : function (timer) {
						var color = (timer.seconds_left <= 5) ? "#F00" : "";
						if (color) timer.target.css('color', color);
						//that.model.set('time_remaining', timer.seconds_left);
					}
				});
			}
		},
		pause           : function () {
			this.$countdown.backward_timer('cancel');
		},
		resume          : function () {
			this.$countdown.backward_timer('start');
		},
		loadPage        : function (url) {
			url = url || window.location.href;
			window.location.href = url;
		},
		_timeOver       : function (timer) {
			timer.target.css('color', '#F00');
			LP.MessageBox.blockUI(single_quiz_localize.quiz_time_is_over_message);
			this.finishQuiz({
				complete: function (response) {
					LP.MessageBox.hide();
					if (response.redirect) {
						LP.reload(response.redirect);
					}
				}
			});
		},
		block_page      : function () {
			//this.$el.block_ui();
		},
		unblock_page    : function () {
			//this.$el.unblock_ui();
		}
	});
	LP.Quiz = {
		init: function (data) {
			var model = new LP_Model_Quiz(data);
			new LP_View_Quiz(model);
		}
	}


	$(document).ready(function () {
		var json = JSON.stringify(single_quiz_params);
		LP.Quiz.init(single_quiz_params);
	})
})(jQuery);
