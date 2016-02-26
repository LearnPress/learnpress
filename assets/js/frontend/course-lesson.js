;(function ($) {
	$.LP_Course_Item = function () {

	}
	$.LP_Course_Item.Model = Backbone.Model.extend({
		url       : function () {
			return this.rootUrl
		},
		rootUrl   : '',
		initialize: function (data) {
			this.rootUrl = data.rootUrl;
		},
		load      : function (callback) {
			var that = this;
			$.ajax({
				url     : this.url(),
				dataType: 'html',
				success : function (response) {
					var $html = $(response);
					if ($(document).triggerHandler('learn_press_course_lesson_content_loaded', that) !== false) {
						that.set('content', $html.find('#learn-press-course-lesson'));
						$(document).trigger('learn_press_course_lesson_content_replaced', that);

						$('.course-item.item-current')
							.removeClass('item-current');
						$('.course-item.course-item-' + that.get('id'))
							.addClass('item-current');
					}

					$.isFunction(callback) && callback.call(that, response);
				}
			})
		},
		complete  : function (callback) {
			var that = this;
			$.ajax({
				url     : LearnPress_Settings.ajax,
				dataType: 'html',
				data    : {
					action: 'learnpress_complete_lesson',
					id    : this.get('id'),
					nonce : this.get('nonce').complete
				},
				success : function (response) {
					response = LearnPress.parseJSON(response);
					callback && callback.call(that, $.extend(response, {id: that.get('id')}))
				}
			})
		}
	});

	$.LP_Course_Item.View = Backbone.View.extend({
		el            : '#learn-press-course-lesson',
		events        : {
			'click .complete-lesson-button': 'completeLesson'
		},
		initialize    : function () {
			_.bindAll(this, 'updateLesson', 'completeLesson');
			this.model.on('change', this.updateLesson, this);
			if (this.model.get('content')) {
				this.updateLesson();
			} else {
				this.model.load();
			}
		},
		updateLesson  : function () {
			//if(this.model.hasChanged('content')) {
			var $content = this.model.get('content');
			this.$el.replaceWith($content);
			this.setElement($content);
			LearnPress.setUrl(this.model.get('rootUrl'))
			//}
		},
		completeLesson: function (e) {
			var that = this;
			this.model.complete(function (response) {
				response = LearnPress.Hook.applyFilters('learn_press_user_complete_lesson_response', response)
				if (response && response.result == 'success') {
					that.$('.complete-lesson-button').replaceWith(response.message);
					$('.course-item-' + response.id).addClass('item-completed');
					if(response.course_result){
						that.updateProgress(response);
					}
				}
				LearnPress.Hook.doAction('learn_press_user_completed_lesson', response);
				console.log(response)
			});
		},
		updateProgress: function(data){
			$('.lp-course-progress')
				.attr({
					'data-value': data.course_result
				})
			if( LearnPress.$Course ){
				LearnPress.$Course._sanitizeProgress();
			}
		}
	});
	$.LP_Course_Item.Collection = Backbone.Collection.extend({
		model     : $.LP_Course_Item.Model,
		current   : 0,
		initialize: function () {
			var that = this;
			_.bindAll(this, 'initItems', 'loadItem');
			this.initItems();
		},
		initItems : function () {
			var that = this;
			$('.section-content .course-lesson.course-item').each(function () {
				var $li = $(this),
					$link = $li.find('a'),
					id = parseInt($link.attr('data-id')),
					model = new $.LP_Course_Item.Model({
						id     : id,
						nonce  : {
							complete: $link.attr('data-complete-nonce')
						},
						rootUrl: $link.attr('href')
					});
				that.add(model);
				if( $li.hasClass( 'item-current' ) ){
					that.current = id;
				}
			});
		},
		loadItem  : function (item) {
			if ($.isNumeric(item)) {
				item = this.findWhere({id: item});
			} else if ($.type(item) == 'string') {
				item = this.findWhere({rootUrl: item});
			}
			if (item) {
				if (this.view) {
					this.view.undelegateEvents();
					this.view.model.set('content', this.view.$el);
					$('.course-item.item-current')
						.removeClass('item-current');
					$('.course-item.course-item-' + item.get('id'))
						.addClass('item-current');
				}
				this.view = new $.LP_Course_Item.View({model: item});
			}
		}
	});

	$.LP_Course_Item_List_View = Backbone.View.extend({
		model     : $.LP_Course_Item.Collection,
		el        : 'body',
		events    : {
			'click .section-content .course-lesson.course-item': '_loadLesson',
			'click .course-item-nav a'                         : '_loadLesson'
		},
		initialize: function (args) {
			_.bindAll(this, '_loadLesson');
			this.model.loadItem(this.model.current);
		},
		_loadLesson: function (e) {
			e.preventDefault();
			var $item = $(e.target),
				id = parseInt($item.attr('data-id')),
				link = $item.attr('href');
			this.model.loadItem(id ? id : link);
		}
	});
	function _init() {
		var courseItems = new $.LP_Course_Item.Collection(),
			courseItemsView = new $.LP_Course_Item_List_View({model: courseItems});
	}

	$(document).ready(_init)
})(jQuery);
