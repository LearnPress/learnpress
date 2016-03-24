if (typeof LearnPress == 'undefined') var LearnPress = {};
(function ($) {
	"use strict";
	var LearnPress_Model_Quiz = window.LearnPress_Model_Quiz = Backbone.Model.extend({
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
			this.questions = new LearnPress_Collection_Questions();
			_.each(this.get('questions'), function (question_id, i) {
				var $model = new LearnPress_Model_Question({
					id     : question_id,
					quiz_id: this.get('id'),
					user_id: this.get('user_id')
				});
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
						question_answer: this.view.$('form').serialize(),//$('input, select, textarea', this.view.$('form')).toJSON(),
						time_remaining : that.get('time_remaining')
					},
					complete: function () {
						that.set('question_id', next_id);
						$.isFunction(callback) && callback.apply(that)
					}
				});
			}
		},
		prev               : function (callback) {
			if (!this.isFirst()) {
				var prev_id = this.findPrev(),
					question = this.questions.findWhere({id: prev_id}),
					that = this;
				if (!question.get('content')) {
					question.submit({
						data    : {
							save_id       : that.get('question_id'),
							time_remaining: that.get('time_remaining')
						},
						complete: function () {
							that.set('question_id', prev_id);
							$.isFunction(callback) && callback.apply(that)
						}
					});
				} else {
					this.set('question_id', prev_id);
				}
			}
		},
		select             : function (id, callback) {
			var question = this.questions.findWhere({id: id}),
				that = this;
			question.submit({
				data    : {
					save_id        : that.get('question_id'),
					question_answer: this.view.$('form').serialize(), //$('input, select, textarea', this.view.$('form')).toJSON(),
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
			LearnPress.log(typeof question_id)
			return _.indexOf(this.get('questions'), question_id);
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
			var ids = this.get('questions'),
				pos = this.getQuestionPosition(question_id);
			pos++;
			if (typeof ids[pos] == 'undefined') return false;
			return ids[pos];
		},
		findPrev           : function (question_id) {
			question_id = question_id || this.get('question_id');
			var ids = this.get('questions'),
				pos = this.getQuestionPosition(question_id);
			pos--;
			if (typeof ids[pos] == 'undefined') return false;
			return ids[pos];
		},
		current            : function () {
			return this.questions.findWhere({id: parseInt(this.get('question_id'))});
		}
	});

	var LearnPress_Model_Question = window.LearnPress_Model_Question = Backbone.Model.extend({
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
					var response = LearnPress.parseJSON(e.responseText);
					if (response.result == 'success') {
						if (!that.get('content')) {
							that.set('content', $(response.content));
							if (response.permalink) {
								LearnPress.setUrl(response.permalink);
							}
						}
						$.isFunction(args.complete) && args.complete.call(that, response);
					}
				})
			});
		}
	});

	var LearnPress_Collection_Questions = window.LearnPress_Collection_Questions = Backbone.Collection.extend({
		url  : 'admin-ajax.php',
		model: LearnPress_Model_Question
	});

	var LearnPress_View_Quiz = window.LearnPress_View_Quiz = Backbone.View.extend({
		model          : {},
		events         : {
			'click .button-start-quiz'       : '_startQuiz',
			'click .button-finish-quiz'      : '_finishQuiz',
			'click .button-retake-quiz'      : '_retakeQuiz',
			'click .next-question'           : 'nextQuestion',
			'click .prev-question'           : 'prevQuestion',
			'click .quiz-questions-list li a': 'selectQuestion'
		},
		el             : '.single-quiz',
		isRendered     : false,
		$buttons       : {},
		initialize     : function (model) {
			this.model = model;
			this.model.view = this;
			this.listenTo(this.model, 'change', this.render);
			_.bindAll(this, 'render', '_timeOver');
			this._create();
			this.render();

		},
		_create        : function () {
			this.$buttons = {
				start : this.$('.button-start-quiz'),
				finish: this.$('.button-finish-quiz'),
				retake: this.$('.button-retake-quiz'),
				next  : this.$('.next-question'),
				prev  : this.$('.prev-question')
			};
		},
		render         : function () {
			var $question = this.model.current();
			if ($question && this.isRendered) {
				this._updateQuestion($question.element());
			}
			this.setButtonsState();
			if (this.model.get('status') == 'started' && $question) {
				this.$('#learn-press-quiz-questions li[data-id="' + $question.get('id') + '"]')
					.addClass('current')
					.siblings('.current').removeClass('current');
			}

			this.initCountdown();
			this.isRendered = true;
			this.$el.css('visibility', 'visible');
			this.unblock_page();

		},
		setButtonsState: function () {
			switch (this.model.get('status').toLowerCase()) {
				case 'completed':
					this.$buttons.start.hide();
					this.$buttons.finish.hide();
					this.$buttons.retake.show();
					break;
				case 'started':
					this.$buttons.start.hide();
					this.$buttons.finish.show();
					this.$buttons.retake.hide();

					if (this.model.countQuestions() <= 1) {
						this.$buttons.next.hide();
						this.$buttons.prev.hide();
					} else {
						this.$buttons.next.show();
						this.$buttons.prev.show();
						if (this.model.isLast()) {
							this.$buttons.next.hide();
							this.$buttons.finish.filter('[data-area="nav"]').show();
						} else {
							this.$buttons.finish.filter('[data-area="nav"]').hide();
						}
						if (this.model.isFirst()) {
							this.$buttons.prev.hide();
						}
					}

					break;
				default:
					this.$buttons.next.hide();
					this.$buttons.prev.hide();
					this.$buttons.start.show();
					this.$buttons.finish.hide();
					this.$buttons.retake.hide();
					this.$('.quiz-questions .qq.current').removeClass('current');
			}

		},
		startQuiz      : function (args) {
			this.block_page();
			args = $.extend({
				complete: false
			}, args || {});
			var that = this,
				data = $.extend({
					action : 'learnpress_start_quiz',
					quiz_id: this.model.get('id'),
					data   : this.$('form').serialize()
				}, {});//$('input, textarea, select', '#nav-question-form').toJSON() || {});
			$.ajax({
				url     : this.model.get('ajaxurl'),
				data    : data,
				dataType: 'html',
				type    : 'post',
				success : function (code) {
					var res = LearnPress.parseJSON(code);
					if (res.result == 'success') {
						that.model.current().set('content', $(res.question_content));
						that.model.set('status', 'started');
						LearnPress.setUrl(res.question_url);
						$.isFunction(args.complete) && args.complete.call(that, res)
					} else if (res.message) {
						alert(res.message)
					}
				}
			});
		},
		finishQuiz     : function (args) {
			var that = this;
			this.pause();
			args = $.extend({
				complete: function (response) {

				}
			}, args || {});
			$.ajax({
				url     : this.model.get('ajaxurl'),
				dataType: 'html',
				data    : {
					action         : 'learnpress_finish_quiz',
					save_id        : this.model.get('question_id'),
					question_answer: this.$('form').serialize(),// $('input, select, textarea', this.$('form')).toJSON(),
					quiz_id        : this.model.get('id')
				},
				success : function (response) {
					var json = LearnPress.parseJSON(response),
						callbackReturn = undefined;
					$.isFunction(args.complete) && ( callbackReturn = args.complete.call(LearnPress.Quiz, json) );
					LearnPress.Hook.doAction('learn_press_finish_quiz', that.model.get('id'));
					LearnPress.MessageBox.show('Congrats! You have finished this quiz', {
						autohide: 2000,
						onHide  : function () {
							if (callbackReturn && callbackReturn.redirect) {
								LearnPress.reload(callbackReturn.redirect);
							} else if (callbackReturn == undefined && json.redirect) {
								LearnPress.reload(json.redirect);
							}
						}
					});
				}
			});
		},
		retakeQuiz     : function (args) {
			var that = this;
			args = $.extend({
				complete: function (response) {

				}
			}, args || {});
			$.ajax({
				url     : this.model.get('ajaxurl'),
				dataType: 'html',
				data    : {
					action : 'learnpress_retake_quiz',
					quiz_id: this.model.get('id'),
					nonce  : this._getNonce('retake-quiz')
				},
				success : function (response) {
					var json = LearnPress.parseJSON(response);
					LearnPress.MessageBox.hide();
					if (json.result == 'success') {
						$.isFunction(args.complete) && args.complete.call(LearnPress.Quiz, json);
						LearnPress.MessageBox.show('Congrats! You have re-taken this quiz. Please wait a moment and the page will reload', {
							autohide: 2000,
							onHide  : function () {
								LearnPress.reload();
							}
						});
					} else {
						LearnPress.MessageBox.show(json.message, {buttons: 'ok'});
					}
				}
			});
		},
		nextQuestion   : function () {
			var that = this;
			this.pause();
			this.block_page();
			this.model.next();
		},
		prevQuestion   : function () {
			this.pause();
			this.block_page();
			this.model.prev();
		},
		selectQuestion : function (e) {
			var that = this,
				id = $(e.target).parent().data('id');
			this.pause();
			this.model.select(id, function (response) {
				that._updateQuestion($(response.content));
			});
			e.preventDefault();
		},
		_getNonce      : function (field) {
			return this.$('input#' + field + '-nonce').val();
		},
		_startQuiz     : function () {
			LearnPress.MessageBox.blockUI();
			this.startQuiz({
				complete: function (response) {
					LearnPress.MessageBox.hide();

				}
			});
		},
		_retakeQuiz    : function () {
			var that = this;
			LearnPress.MessageBox.show(single_quiz_localize.confirm_retake_quiz, {
				buttons: 'yesNo',
				events : {
					onYes: function () {
						LearnPress.MessageBox.blockUI();
						that.retakeQuiz({
							complete: function () {
								LearnPress.MessageBox.hide();
							}
						});
					}
				}
			});
		},
		_finishQuiz    : function () {
			var that = this;
			LearnPress.MessageBox.show(single_quiz_localize.confirm_finish_quiz, {
				buttons: 'yesNo',
				events : {
					onYes: function () {
						LearnPress.MessageBox.blockUI('Your quiz will come to finish! Please wait...');
						that.finishQuiz({
							complete: function (response) {
								LearnPress.MessageBox.hide();
							}
						});
					}
				}
			});
		},
		_updateQuestion: function ($newQuestion) {
			var $container = this.$('.quiz-question-content form'),
				$oldQuestion = $container.find('.learn-press-question-wrap');
			if ($oldQuestion.length) {
				$oldQuestion.replaceWith($newQuestion);
			} else {
				$container.append($newQuestion);
			}
			LearnPress.Hook.doAction('learn_press_update_question_content', $newQuestion, $oldQuestion);
			LearnPress.setUrl($newQuestion.find('input[name="learn-press-question-permalink"]').val());
		},
		initCountdown  : function () {
			var that = this,
				$countdown = this.$countdown;
			if (!this.isRendered || !$countdown) {
				this.$countdown = $("#quiz-countdown-value");
				this.$countdown.backward_timer({
					seconds     : parseInt(this.model.get('time_remaining')),
					format      : that.model.get('time_format'),
					on_exhausted: this._timeOver,
					on_tick     : function (timer) {
						var color = (timer.seconds_left <= 5) ? "#F00" : "";
						if (color) timer.target.css('color', color);
						//that.model.set('time_remaining', timer.seconds_left);
					}
				});
			}
			if (this.model.get('status') == 'started') {
				this.$countdown.backward_timer('start');
			}
		},
		pause          : function () {
			this.$countdown.backward_timer('cancel');
		},
		resume         : function () {
			this.$countdown.backward_timer('start');
		},
		loadPage       : function (url) {
			url = url || window.location.href;
			window.location.href = url;
		},
		_timeOver      : function (timer) {
			timer.target.css('color', '#F00');
			LearnPress.MessageBox.blockUI(single_quiz_localize.quiz_time_is_over_message);
			this.finishQuiz({
				complete: function (response) {
					LearnPress.MessageBox.hide();
					if (response.redirect) {
						LearnPress.reload(response.redirect);
					}
				}
			});
		},
		block_page     : function () {
			//this.$el.block_ui();
		},
		unblock_page   : function () {
			//this.$el.unblock_ui();
		}
	});
	LearnPress.Quiz = {
		init: function (data) {
			var model = new LearnPress_Model_Quiz(data);
			new LearnPress_View_Quiz(model);
		}
	}


	$(document).ready(function () {
		var json = JSON.stringify(single_quiz_params);
		json = json.replace(/:\s?[\"|\']([0-9]+)[\"|\']/g, ':$1');
		LearnPress.Quiz.init(JSON.parse(json));
	})
})(jQuery);
