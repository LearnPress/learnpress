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
						$(document).trigger('learn_press_course_lesson_content_replaced', that)
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
				data: {
					action: 'learnpress_complete_lesson',
					id: this.get('id'),
					nonce: this.get('nonce').complete
				},
				success : function (response) {
					response = LearnPress.parseJSON(response);
					callback && callback.call(that, response)
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
			this.model.complete(function(response){
				console.log(response)
				if( response && response.result == 'success'){
					that.$('.complete-lesson-button').replaceWith(response.message);
				}
			});
		}
	});
	$.LP_Course_Item.Collection = Backbone.Collection.extend({
		model     : $.LP_Course_Item.Model,
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
					this.view.model.set('content', this.view.$el)
				}
				this.view = new $.LP_Course_Item.View({model: item});
			}
		}
	});

	$.LP_Course_Item_List_View = Backbone.View.extend({
		model     : $.LP_Course_Item.Collection,
		el        : 'body',
		events    : {
			'click .section-content .course-lesson.course-item': 'loadLesson',
			'click .course-item-nav a'                         : 'loadLesson'
		},
		initialize: function (args) {
			_.bindAll(this, 'loadLesson');
			this.model.loadItem(args.currentItem);
		},
		loadLesson: function (e) {
			e.preventDefault();
			var $item = $(e.target),
				id = parseInt($item.attr('data-id')),
				link = $item.attr('href');
			this.model.loadItem(id ? id : link);
		}
	});
	function _init() {
		var courseItems = new $.LP_Course_Item.Collection(),
			courseItemsView = new $.LP_Course_Item_List_View({model: courseItems, currentItem: 1733});
	}

	$(document).ready(_init)
})(jQuery);
