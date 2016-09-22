/**
 * Single Quiz functions
 *
 * @author ThimPress
 * @package LearnPress/JS
 * @version 1.1
 */
;(function ($) {

	var Quiz = function (args) {
		this.quiz = new Quiz.View({
			model: new Quiz.Model(args)
		});
	}, Model_Question, List_Questions;

	Quiz.Model_Question = Model_Question = Backbone.Model.extend({
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
				data    : $.extend({
					'lp-ajax'  : 'check-question',
					question_id: this.get('id')
					//question_answer: $('form#learn-press-quiz-question').serializeJSON()
				}, args.data || {}),
				dataType: 'html',
				success : function (response, raw) {
					var $content = that.get('response');
					if ($content) {
						$content.find('.learn-press-question-options').replaceWith($(response).filter('.learn-press-question-options'));
					}
					console.log($content)
					that.set({checked: 'yes', response: $content});
					$.isFunction(args.complete) && args.complete.call(that, response)
				}
			})
		},
		showHint  : function (args) {
			var that = this;
			LP.doAjax({
				data   : $.extend({
					'lp-ajax'      : 'get-question-hint',
					user_id        : this.get('user_id'),
					quiz_id        : this.get('quiz_id'),
					question_id    : this.get('id'),
					question_answer: $('form#learn-press-quiz-question').serializeJSON()
				}, args.data || {}),
				success: function (response, raw) {
					that.set('checked', response.checked);
					$.isFunction(args.complete) && args.complete.call(that, response)
				}
			})
		}
	});
	/**
	 * List Questions
	 */
	Quiz.List_Questions = List_Questions = Backbone.Collection.extend({
		url          : 'admin-ajax.php',
		len          : 0,
		model        : Model_Question,
		initialize   : function () {
			this.on('add', function (model) {
				this.listenTo(model, 'change', this.onChange);
				this.listenTo(model, 'change:hasShowedHint', this.onChangedHint);
				model.set('index', this.len++);
			}, this)
		},
		onChangedHint: function (a, b) {

		},
		onChange     : function (a, b) {

			if (a.changed['current'] && a.get('current') == 'yes') {
				this.current = a;
				for (var i = 0; i < this.length; i++) {
					var e = this.at(i);
					if (e.get('id') == a.get('id')) {
						continue;
					}
					if (e.get('current') != 'yes') {
						continue;
					}
					this.stopListening(e, 'change', this.onChange);
					e.set('current', 'no');
					this.listenTo(e, 'change', this.onChange);
				}
				try {
					this.view.updateUrl();
				} catch (e) {
				}
			}
		}
	});

	Quiz.Model = Backbone.Model.extend({
		_args                : null,
		questions            : null,
		initialize           : function (args) {
			_.bindAll(this, 'getQuizData');
			this._args = args || {};
			this.on('change:view', function () {
				this.questions.view = this.get('view')
			});
			this._initQuestions();
			this.set('remainingTime', args.totalTime - args.userTime);
			LP.Hook.addFilter('learn_press_finish_quiz_data', this.getQuizData);

		},
		_initQuestions       : function () {
			this.questions = new List_Questions();
			_.forEach(this._args.questions, function (q) {
				this.questions.add(q);
			}, this);
		},
		_secondsToDHMS       : function (t) {
			var d = Math.floor(t / (24 * 3600)), t = t - d * 24 * 3600, h = Math.floor(t / 3600), t = t - h * 3600, m = Math.floor(t / 60), s = Math.floor(t - m * 60);
			return {d: d, h: h, m: m, s: s}
		},
		getRemainingTime     : function (format) {
			var t = this.get('remainingTime');
			if (format == 'dhms') {
				t = this._secondsToDHMS(t);
			}
			return t;
		},
		getTotalTime         : function (format) {
			var t = this.get('totalTime');
			if (format == 'dhms') {
				t = this._secondsToDHMS(t);
			}
			return t;
		},
		getUserTime          : function (format) {
			var t = this.get('userTime');
			if (format == 'dhms') {
				t = this._secondsToDHMS(t);
			}
			return t;
		},
		inc                  : function () {
			var userTime = this.get('userTime') + 1,
				remainingTime = Math.max(this.get('totalTime') - userTime, 0);
			this.set({
				userTime     : userTime,
				remainingTime: remainingTime
			});
			return {
				userTime     : userTime,
				remainingTime: remainingTime
			}
		},
		fetchCurrent         : function (callback) {
			var current = this.getCurrent(),
				that = this;
			if (!current) {
				return;
			}
			$.ajax({
				url     : current.get('url'),
				dataType: 'html',
				success : function (response) {
					that.set('response', response);
					$.isFunction(callback) && callback(response, that);
				}
			});
		},
		next                 : function (callback) {
			var next = this.findNext(),
				that = this;
			if (!next) {
				return;
			}
			next.set('current', 'yes');
			if (next.get('response')) {
				next.set('loaded', true);
				$.isFunction(callback) && callback(next, that);
			} else {
				new (function (a, b, c) {
					$.ajax({
						url     : next.get('url'),
						dataType: 'html',
						success : function (response) {
							var $html = $(response).contents().find('.learn-press-content-item-summary')
							a.set('response', $html);
							$.isFunction(c) && c(a, b);
						}
					})
				})(next, that, callback);
			}
			return;
			if (!this.isLast()) {
				var next = this.findNext(),
					that = this;
				if (!next) {
					return;
				}
				return;
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
		}
		,
		prev                 : function (callback) {
			var prev = this.findPrev(),
				that = this;
			if (!prev) {
				return;
			}
			prev.set('current', 'yes');
			if (prev.get('response')) {
				prev.set('loaded', true);
				$.isFunction(callback) && callback(prev, that);
			} else {
				$.ajax({
					url     : prev.get('url'),
					dataType: 'html',
					success : function (response) {
						var $html = $(response).contents().find('.learn-press-content-item-summary')
						prev.set('response', $html);
						$.isFunction(callback) && callback(prev, that);
					}
				})
			}

			return;
			if (!this.isFirst()) {


				return;
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
		}
		,
		select               : function (id, callback) {
			var question = this.questions.findWhere({id: id}),
				that = this;
			return;
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
		getQuestionPosition  : function (question_id) {
			question_id = question_id || this.get('question_id');
			return _.indexOf(this.getIds(), question_id);
		},
		countQuestions       : function () {
			return this.questions.length;
		},
		isLast               : function (question_id) {
			question_id = question_id || this.getCurrent('id');
			var q = this.questions.findWhere({id: parseInt(question_id)});
			return q ? q.get('index') == this.questions.length - 1 : false;//this.getQuestionPosition(question_id) == (this.countQuestions() - 1);
		},
		isFirst              : function (question_id) {
			question_id = question_id || this.getCurrent('id');
			var q = this.questions.findWhere({id: parseInt(question_id)});
			return q ? q.get('index') == 0 : false;// this.getQuestionPosition(question_id) == 0;
		},
		findNext             : function (question_id) {
			question_id = question_id || this.getCurrent('id');
			var q = this.questions.findWhere({id: parseInt(question_id)}),
				next = false;
			if (q) {
				var index = q.get('index');
				next = this.questions.at(index + 1);
			}
			return next;
		},
		findPrev             : function (question_id) {
			question_id = question_id || this.getCurrent('id');
			var q = this.questions.findWhere({id: parseInt(question_id)}),
				prev = false;
			if (q) {
				var index = q.get('index');
				prev = this.questions.at(index - 1);
			}
			return prev;
		},
		getCurrent           : function (_field, _default) {
			var current = this.current(),
				r = _default;
			if (current) {
				r = current.get(_field);
			}
			return r;
		},
		current              : function (create) {
			var current = this.questions.findWhere({current: 'yes'});
			if (!current && create) {
				current = new Model_Question();
			}
			return current;
		},
		getIds               : function () {
			return $.map(this.get('questions'), function (i, v) {
				return parseInt(i.id)
			});
		},
		showHint             : function (callback, args) {
			this.current().showHint({
				complete: callback,
				data    : this.getRequestParams(args)
			});
		},
		checkAnswer          : function (callback, args) {
			if (!args) {
				args = {};
			}
			args.question_answer = this.getQuestionAnswerData(this.current());
			this.current().check({
				complete: callback,
				data    : this.getRequestParams(args)
			});
		},
		getRequestParams     : function (args) {
			var defaults = LP.Hook.applyFilters('learn_press_request_quiz_params', {
				quiz_id  : this.get('id'),
				user_id  : this.get('user_id'),
				course_id: this.get('courseId')
			});

			return $.extend(defaults, args || {});
		},
		getQuestion          : function (thing) {
			var question = false;
			if ($.isNumeric(thing)) {
				question = this.questions.findWhere({id: parseInt(thing)});
			} else if ($.isPlainObject(thing)) {
				question = this.questions.findWhere(thing);
			} else if ($.type(thing) == 'undefined' || $.type(thing) == null) {
				question = this.current();
			} else if (thing instanceof Model_Question) {
				question = thing;
			}
			return question;
		},
		getQuizData          : function (data) {
			data.answers = {};
			this.questions.forEach(function (model) {
				data.answers[model.get('id')] = this.getQuestionAnswerData(model);
			}, this);
			return data;
		},
		getQuestionAnswerData: function (question) {
			question = this.getQuestion(question);
			if (!question) {
				return undefined;
			}
			var $html = question.get('response'),
				$form = $('<form />'),
				answer = {};
			if ($html) {
				$form.html($html.clone());
				answer = $form.serializeJSON();
			}
			return LP.Hook.applyFilters('learn_press_question_answer_data', answer, $form, question, this);
		}
	});
	Quiz.View = Backbone.View.extend({
		el                    : function () {
			return 'body';
		},
		events                : {
			'click .button-prev-question': '_prevQuestion',
			'click .button-next-question': '_nextQuestion',
			'click .button-hint'         : '_showHint',
			'click .button-check-answer' : '_checkAnswer'
		},
		timeout               : 0,
		initialize            : function () {
			var $el = $('#content-item-' + this.model.get('id'));
			//this.setElement($el);
			_.bindAll(this, '_onTick', 'itemUrl', '_loadQuestionCompleted', '_checkAnswer');
			LP.Hook.addFilter('learn_press_get_current_item_url', this.itemUrl);
			this.model.current(true).set('response', this.$('.learn-press-content-item-summary'));
			this.model.set('view', this);
			this._initCountDown();
			this.updateButtons();

		},
		_initCountDown        : function () {
			if (this.model.get('status') != 'started' || this.model.get('totalTime') <= 0) {
				return;
			}
			this.refreshCountdown();
			$.inArray(this.model.get('status'), ['started']) >= 0 && setTimeout($.proxy(function () {
				this.start();
			}, this), 500);
		},
		_addLeadingZero       : function (n) {
			return n < 10 ? "0" + n : "" + n;
		},
		_onTick               : function () {
			this.timeout && clearTimeout(this.timeout);
			var timer = this.model.inc();
			this.refreshCountdown();
			if (timer.remainingTime == 0) {
				LP.Hook.doAction('learn_press_quiz_timeout', this);
				this.$('.button-finish-quiz').trigger('click');
				return;
			}
			this.timeout = setTimeout(this._onTick, 1000);
		},
		_prevQuestion         : function (e) {
			e.preventDefault();
			LP.$Course.view.blockContent();
			this.model.current(true).set('response', this.$('.learn-press-content-item-summary'));
			this.model.prev(this._loadQuestionCompleted);
		},
		_nextQuestion         : function (e) {
			e.preventDefault();
			LP.$Course.view.blockContent();
			this.model.current(true).set('response', this.$('.learn-press-content-item-summary'));
			this.model.next(this._loadQuestionCompleted);
		},
		_loadQuestionCompleted: function (question, model) {
			var loaded = model.get('loaded'),
				$newElement = question.get('response'),
				$oldElement = this.$('.learn-press-content-item-summary');
			$newElement.show().insertAfter($oldElement);
			$oldElement.detach();
			this.updateButtons();
			if (model.getCurrent('hasShowedHint') == 'yes') {
				this.$('.button-hint').attr('disabled', true);
				this.$('.question-hint-content').removeClass('hide-if-js');
			} else {
				this.$('.button-hint').attr('disabled', false);
				this.$('.question-hint-content').addClass('hide-if-js');
			}

			$(window).trigger('load');
			$(document).trigger('resize');
			LP.$Course.view.unblockContent();
			LP.setUrl(question.get('url'));
		},
		_showHint             : function (e) {
			e.preventDefault();
			this.model.current().set('hasShowedHint', 'yes');
			this.$('.button-hint').attr('disabled', true);
			this.$('.question-hint-content').removeClass('hide-if-js');
			return;
			LP.$Course.view.blockContent();
			this.model.showHint(this._showHintCompleted, {
				security: $(e.target).data('security')
			});

		},
		_showHintCompleted    : function (response) {
			//$(response.html).this.
			LP.$Course.view.unblockContent();
		},
		_checkAnswer          : function (e) {
			e.preventDefault();
			var that = this,
				$button = $(e.target),
				security = $button.data('security');
			LP.$Course.view.blockContent();
			this.model.checkAnswer(this._checkAnswerCompleted, {
				security: security
			});
		},
		_checkAnswerCompleted : function (question) {
			LP.$Course.view.unblockContent();
		},
		updateButtons         : function () {
			if (this.model.questions.length < 2) {
				return;
			}
			if (this.model.get('status') == 'started') {
				this.$('.button-prev-question').toggleClass('hide-if-js', this.model.isFirst());
				this.$('.button-next-question').toggleClass('hide-if-js', this.model.isLast());
				var current = this.model.current();
				if (current) {
					this.$('.button-check-answer').toggleClass('hide-if-js', current.get('hasCheckAnswer') != 'yes');
					this.$('.button-hint').toggleClass('hide-if-js', current.get('hasHint') != 'yes');
				}
			}
		},
		start                 : function () {
			this._onTick();
		},
		pause                 : function () {
			this.timeout && clearTimeout(this.timeout);
		},
		refreshCountdown      : function () {
			var localTimeZone = -(new Date().getTimezoneOffset()) / 60,
				timeOffset = this.model.get('serverTime') - localTimeZone;
			var startTime = this.model.get('startTime'),
				endTime = startTime + this.model.get('totalTime'),
				totalTime = this.model.getTotalTime('dhms'),
				remainingTime = this.model.getRemainingTime('dhms'),
				strTime = [],
				nowTime = new Date().getTime() / 1000;
			endTime += timeOffset * 3600;
			remainingTime = this.model._secondsToDHMS(endTime - nowTime);
			if (totalTime.d) {
				strTime.push(this._addLeadingZero(remainingTime.d));
			}
			if (totalTime.h) {
				strTime.push(this._addLeadingZero(remainingTime.h));
			}
			strTime.push(this._addLeadingZero(remainingTime.m));
			strTime.push(this._addLeadingZero(remainingTime.s));

			var t = parseInt(this.model.get('remainingTime') / this.model.get('totalTime') * 100);// * 360;
			this.$('.quiz-countdown').attr('data-value', t).toggleClass('xxx', t < 10).find('.countdown').html(strTime.join(':'));
			/**if (t < 180) {
				this.$('.progress-circle').removeClass('gt-50');
			}
			 this.$('.fill').css({
				transform: 'rotate(' + t + 'deg)'
			});*/
		},
		itemUrl               : function (url, item) {
			if (item.get('id') == this.model.get('id')) {
				var questionName = this.model.getCurrent('name'), reg;
				if (questionName && this.model.get('status') !== 'completed') {
					reg = new RegExp(questionName, '');
					if (!url.match(reg)) {
						url = url.replace(/\/$/, '') + '/' + questionName + '/';
					}
				}
			}
			return url;
		}
	});

	window.LP_Quiz = Quiz;
	// DOM ready
	LP.Hook.addAction('learn_press_course_initialize', function ($course) {
		if (typeof Quiz_Params != 'undefined') {
			window.$Quiz = new LP_Quiz($.extend({course: $course}, Quiz_Params));
			$course.view.updateUrl();
		}
	});

})(jQuery);