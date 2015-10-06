if (typeof LearnPress == 'undefined') var LearnPress = {};


(function ($) {

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
		next               : function () {
			if (!this.isLast()) {
				var next_id = this.findNext(),
					question = this.questions.findWhere({id: next_id}),
					that = this;
				question.submit({
					data    : {
						save_id        : that.get('question_id'),
						question_answer: $('input, select, textarea', this.view.$('form')).toJSON(),
						time_remaining: that.get('time_remaining')
					},
					complete: function () {
						that.set('question_id', next_id);
					}
				});
			}
		},
		prev               : function () {
			if (!this.isFirst()) {
				var prev_id = this.findPrev(),
					question = this.questions.findWhere({id: prev_id}),
					that = this;
				if (!question.get('content')) {
					question.submit({
						data    : {
							save_id: that.get('question_id'),
							time_remaining: that.get('time_remaining')
						},
						complete: function () {
							that.set('question_id', prev_id);
						}
					});
				} else {
					this.set('question_id', prev_id);
				}
			}
		},
		getQuestionPosition: function (question_id) {
			question_id = question_id || this.get('question_id');
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
			return this.questions.findWhere({id: this.get('question_id')});
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
					var response = LearnPress.parse_json(e.responseText);
					if(response.result == 'success') {
						if (!that.get('content')) {
							that.set('content', $(response.content));
							if(response.permalink){
								LearnPress.pushHistory( response.permalink );
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
			'click .button-start-quiz' : 'startQuiz',
			'click .button-finish-quiz': 'finishQuiz',
			'click .button-retake-quiz': 'retakeQuiz',
			'click .next-question'     : 'nextQuestion',
			'click .prev-question'     : 'prevQuestion'
		},
		el             : '.lpr_quiz',
		isRendered     : false,
		$buttons       : {},
		initialize     : function (model) {
			this.model = model;
			this.model.view = this;
			this.listenTo(this.model, 'change', this.render);
			_.bindAll(this, 'render');
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
				var old = this.$('.lp-question-wrap');
				if (old.length) {
					old.replaceWith($question.element());
				} else {
					this.$('#nav-question-form').prepend($question.element());
				}
			}
			this.setButtonsState();
			if (this.model.get('status') == 'started') {
				this.$('.quiz-questions .sibdebar-quiz-question-' + $question.get('id'))
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
			args = $.extend({}, args || {});
			var that = this,
				data = $.extend({
					action : 'learnpress_start_quiz',
					quiz_id: this.model.get('id')
				}, $('input, textarea, select', '#nav-question-form').toJSON() || {});
			$.ajax({
				url     : this.model.get('ajaxurl'),
				data    : data,
				dataType: 'html',
				type    : 'post',
				success : function (code) {
					var res = LearnPress.parse_json(code);
					if (res.result == 'success' && res.question_url) {
						that.model.current().set('content', $(res.question_content));
						that.model.set('status', 'started');
						LearnPress.pushHistory( res.question_url );
					}else if(res.message){
						alert(res.message)
					}
					that.unblock_page();
				}
			});
		},
		finishQuiz     : function (args) {
			var that = this;

			args = $.extend({
				confirm: true
			}, args || {});
			if( args.confirm && !confirm(learn_press_js_localize.confirm_finish_quiz) ){
				return;
			}
			this.pause();
			that.block_page();
			$.ajax({
				url     : this.model.get('ajaxurl'),
				dataType: 'html',
				data    : {
					action         : 'learnpress_finish_quiz',
					save_id        : this.model.get('question_id'),
					question_answer: $('input, select, textarea', this.$('form')).toJSON(),
					quiz_id        : this.model.get('id')
				},
				success : function (response) {
					var json = LearnPress.parse_json(response);
					if (json.redirect) {
						that.loadPage(json.redirect);
					}else if(json.message){
						alert(json.message);
					}
				}
			});
		},
		retakeQuiz     : function (args) {
			if (!confirm(learn_press_js_localize.confirm_retake_quiz)) return;
			var that = this;
			that.block_page();
			args = $.extend({}, args || {});
			$.ajax({
				url     : this.model.get('ajaxurl'),
				dataType: 'html',
				data    : {
					action : 'learnpress_retake_quiz',
					quiz_id: this.model.get('id')
				},
				success : function (response) {
					var json = LearnPress.parse_json(response);
					if (json.redirect) {
						that.loadPage(json.redirect);
					}else if(json.message){
						alert(json.message);
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
		initCountdown  : function () {
			var that = this,
				$countdown = this.$countdown;
			if (!this.isRendered || !$countdown) {
				this.$countdown = $("#quiz-countdown");
				this.$countdown.backward_timer({
					seconds     : this.model.get('time_remaining'),
					format      : 'm%:s%',
					on_exhausted: function (timer) {
						jAlert(learn_press_js_localize.quiz_time_is_over_message, learn_press_js_localize.quiz_time_is_over_title, function () {
							that.finishQuiz({confirm: false});
						});
					},
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
		block_page: function(){
			this.$el.block_ui();
		},
		unblock_page: function(){
			this.$el.unblock_ui();
		}
	});
	LearnPress = $.extend(
		LearnPress, {
			pushHistory: function(url){
				history.pushState({}, '', url);
			},
			initQuiz: function (data) {
				var model = new LearnPress_Model_Quiz(data);
				new LearnPress_View_Quiz(model);
			}
		}
	);

})(jQuery);
