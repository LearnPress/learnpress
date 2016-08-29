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
				data   : $.extend({
					'lp-ajax'      : 'check-question',
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
		url       : 'admin-ajax.php',
		len       : 0,
		model     : Model_Question,
		initialize: function () {
			this.on('add', function (model) {
				this.listenTo(model, 'change', this.onChange);
				model.set('index', this.len++);
			}, this)
		},
		onChange  : function (a, b) {
			if (a.get('current') == 'yes') {
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
				LP.$Course.view.updateUrl();
			}
		}
	});

	Quiz.Model = Backbone.Model.extend({
		_args              : null,
		questions          : null,
		initialize         : function (args) {
			this._args = args || {};
			this._initQuestions();
			this.set('remainingTime', args.totalTime - args.userTime);
		},
		_initQuestions     : function () {
			this.questions = new List_Questions();
			_.forEach(this._args.questions, function (q) {
				this.questions.add(q);
			}, this);
		},
		_secondsToDHMS     : function (t) {
			var d = Math.floor(t / (24 * 3600)), t = t - d * 24 * 3600, h = Math.floor(t / 3600), t = t - h * 3600, m = Math.floor(t / 60), s = Math.floor(t - m * 60);
			return {d: d, h: h, m: m, s: s}
		},
		getRemainingTime   : function (format) {
			var t = this.get('remainingTime');
			if (format == 'dhms') {
				t = this._secondsToDHMS(t);
			}
			return t;
		},
		getTotalTime       : function (format) {
			var t = this.get('totalTime');
			if (format == 'dhms') {
				t = this._secondsToDHMS(t);
			}
			return t;
		},
		getUserTime        : function (format) {
			var t = this.get('userTime');
			if (format == 'dhms') {
				t = this._secondsToDHMS(t);
			}
			return t;
		},
		inc                : function () {
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
		fetchCurrent       : function (callback) {
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
		next               : function (callback) {
			var next = this.findNext(),
				that = this;
			if (!next) {
				return;
			}
			next.set('current', 'yes');
			if (next.get('response')) {
				$.isFunction(callback) && callback(next.get('response'), that);
			} else {
				$.ajax({
					url     : next.get('url'),
					dataType: 'html',
					success : function (response) {
						next.set('response', response);
						$.isFunction(callback) && callback(response, that);
					}
				});
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
		prev               : function (callback) {
			var prev = this.findPrev(),
				that = this;
			if (!prev) {
				return;
			}
			prev.set('current', 'yes');
			if (prev.get('response')) {
				$.isFunction(callback) && callback(prev.get('response'), that);
			} else {
				$.ajax({
					url     : prev.get('url'),
					dataType: 'html',
					success : function (response) {
						prev.set('response', response);
						$.isFunction(callback) && callback(response, that);
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
		select             : function (id, callback) {
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
		getQuestionPosition: function (question_id) {
			question_id = question_id || this.get('question_id');
			return _.indexOf(this.getIds(), question_id);
		},
		countQuestions     : function () {
			return this.questions.length;
		},
		isLast             : function (question_id) {
			question_id = question_id || this.getCurrent('id');
			var q = this.questions.findWhere({id: parseInt(question_id)});
			return q ? q.get('index') == this.questions.length - 1 : false;//this.getQuestionPosition(question_id) == (this.countQuestions() - 1);
		},
		isFirst            : function (question_id) {
			question_id = question_id || this.getCurrent('id');
			var q = this.questions.findWhere({id: parseInt(question_id)});
			return q ? q.get('index') == 0 : false;// this.getQuestionPosition(question_id) == 0;
		},
		findNext           : function (question_id) {
			question_id = question_id || this.getCurrent('id');
			var q = this.questions.findWhere({id: parseInt(question_id)}),
				next = false;
			if (q) {
				var index = q.get('index');
				next = this.questions.at(index + 1);
			}
			return next;
		},
		findPrev           : function (question_id) {
			question_id = question_id || this.getCurrent('id');
			var q = this.questions.findWhere({id: parseInt(question_id)}),
				prev = false;
			if (q) {
				var index = q.get('index');
				prev = this.questions.at(index - 1);
			}
			return prev;
		},
		getCurrent         : function (_field, _default) {
			var current = this.current(),
				r = _default;
			if (current) {
				r = current.get(_field);
			}
			return r;
		},
		current            : function () {
			return this.questions.findWhere({current: 'yes'});
		},
		getIds             : function () {
			return $.map(this.get('questions'), function (i, v) {
				return parseInt(i.id)
			});
		},
		showHint           : function (callback, args) {
			this.current().showHint({
				complete: callback,
				data    : this.getRequestParams(args)
			});
		},
		checkAnswer        : function (callback) {
			$.isFunction(callback) && callback.call(this, 'asdsdasdxxxxxxxxxxsadsadsad')
		},
		getRequestParams   : function (args) {
			var defaults = LP.Hook.applyFilters('learn_press_request_quiz_params', {
				quiz_id  : this.get('id'),
				user_id  : this.get('user_id'),
				course_id: this.get('courseId')
			});

			return $.extend(defaults, args || {});
		}
	});
	Quiz.View = Backbone.View.extend({
		el                    : '.single-quiz',
		events                : {
			'click .button-prev-question': '_prevQuestion',
			'click .button-next-question': '_nextQuestion',
			'click .button-hint'         : '_showHint',
			'click .button-check-answer' : '_checkAnswer'
		},
		timeout               : 0,
		initialize            : function () {
			_.bindAll(this, '_onTick', 'itemUrl', '_loadQuestionCompleted');
			LP.Hook.addFilter('learn_press_get_current_item_url', this.itemUrl);
			this._initCountDown();
			this.updateButtons();
		},
		_initCountDown        : function () {
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
			this.refreshCountdown();
			var timer = this.model.inc();
			if (timer.remainingTime == 0) {
				this.$('.button-finish-quiz').trigger('click');
				return;
			}
			this.timeout = setTimeout(this._onTick, 1000);
		},
		_prevQuestion         : function (e) {
			e.preventDefault();
			LP.$Course.view.blockContent();
			this.model.prev(this._loadQuestionCompleted);
		},
		_nextQuestion         : function (e) {
			e.preventDefault();
			LP.$Course.view.blockContent();
			this.model.next(this._loadQuestionCompleted);
		},
		_loadQuestionCompleted: function (response, model) {
			var $html = $(response),
				$newElement = $html.contents().find('.question-content');
			$('.question-content').replaceWith($newElement);
			this.updateButtons();
			$(window).trigger('load');
			$(document).trigger('resize');
			LP.$Course.view.unblockContent();

		},
		_showHint             : function (e) {
			e.preventDefault();
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
			LP.$Course.view.blockContent();
			this.model.checkAnswer(this._checkAnswerCompleted);
		},
		_checkAnswerCompleted : function (response) {
			console.log('check', response);
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
			var totalTime = this.model.getTotalTime('dhms'),
				remainingTime = this.model.getRemainingTime('dhms'),
				strTime = [];


			if (totalTime.d) {
				strTime.push(this._addLeadingZero(remainingTime.d));
			}
			if (totalTime.h) {
				strTime.push(this._addLeadingZero(remainingTime.h));
			}
			strTime.push(this._addLeadingZero(remainingTime.m));
			strTime.push(this._addLeadingZero(remainingTime.s));

			var t = this.model.get('remainingTime') / this.model.get('totalTime') * 360;
			this.$('.countdown').html(strTime.join(':'));
			if (t < 180) {
				this.$('.progress-circle').removeClass('gt-50');
			}
			this.$('.fill').css({
				transform: 'rotate(' + t + 'deg)'
			});
		},
		itemUrl               : function (url, item) {
			if (item.get('id') == this.model.get('id')) {
				var questionName = this.model.getCurrent('name'), reg;
				if (questionName && this.model.get('status') !== 'completed') {
					reg = new RegExp(questionName, '');
					if (!url.match(reg)) {
						url = url.replace(/\/$/, '') + '/' + questionName + '/';
					}
					console.log(url)
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