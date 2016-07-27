;(function ($) {
	"use strict";
	return;
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
			var that = this,
				_completed = function (response, success) {
					var $html = $(response || ''),
						$lesson = $html.find('#learn-press-course-lesson');
					if ($lesson.length == 0) {
						$lesson = $('<div id="learn-press-course-lesson" />');
					}
					if (LP.Hook.applyFilters('learn_press_update_item_content', $lesson, that) !== false) {
						that.set('content', $lesson);
						$(document).trigger('learn_press_course_item_content_replaced', $lesson, that);
						$('.course-item.item-current')
							.removeClass('item-current');
						$('.course-item.course-item-' + that.get('id'))
							.addClass('item-current');
					}
					$.isFunction(callback) && callback.call(that, response);
				};
			$.ajax({
				url     : this.url(),
				dataType: 'html',
				success : function (response) {
					_completed(response, true);
				},
				error   : function () {
					_completed('', false)
				}
			});
		},
		complete  : function (args) {
			var that = this;
			args = $.extend({
				data   : null,
				success: null
			}, args || {});
			LP.ajax({
				dataType: 'html',
				action  : 'complete_lesson',
				data    : $.extend({
					id: this.get('id')
				}, args.data || {}),
				success : function (response) {
					response = LP.parseJSON(response);
					$.isFunction(args.success) && args.success.call(that, $.extend(response, {id: that.get('id')}))
				}
			});
			return;
			$.ajax({
				url     : '',
				dataType: 'html',
				data    : $.extend({
					action: 'learnpress_complete_lesson',
					id    : this.get('id')
				}, args.data || {}),
				success : function (response) {
					response = LP.parseJSON(response);
					$.isFunction(args.success) && args.success.call(that, $.extend(response, {id: that.get('id')}))
				}
			})
		}
	});

	$.LP_Course_Item.View = Backbone.View.extend({
		el             : '#learn-press-course-lesson',
		events         : {
			'click .complete-lesson-button': '_completeLesson'
		},
		initialize     : function () {
			_.bindAll(this, 'updateItem', '_completeLesson');
			this.model.on('change', this.updateItem, this);

			if (LP.Hook.applyFilters('learn_press_before_load_item', this) !== false) {
				if (this.model.get('id') /*&& this.$('input[name="learn-press-lesson-viewing"]').val() != this.model.get('id')*/) {
					if (this.model.get('content') == 'no-cache') {
						this.updateItem();
					} else {
						this.model.load();
					}
				} else if (this.model.get('content')) {
					LP.Hook.doAction('learn_press_item_content_loaded', this.model.get('content'), this);
				}
			}
		},
		updateItem     : function () {
			var $content = this.model.get('content');
			this.$el.replaceWith($content);
			this.setElement($content.show());
			var url = LP.Hook.applyFilters('learn_press_set_item_url', this.model.get('rootUrl'), this);
			if (url) {
				LP.setUrl(url);
			}
			$content.closest('#learn-press-content-item').show();
			LP.Hook.doAction('learn_press_item_content_loaded', $content, this);
		},
		_autoNextItem  : function (item, delay) {
			var $link = this.$('.course-item-next a[data-id="' + item + '"]');
			if (!$link.length) {
				return;
			}
			var duration = 3,
				$span = $('<span>Auto next in ' + duration + 's</span>').insertAfter(this.$('.complete-lesson-button'));
			setInterval(function () {
				duration--;
				$span.html('Auto next in ' + duration + 's');
				if (duration == 0) {
					$link.trigger('click')
				}
			}, 1000);
		},
		_completeLesson: function (e) {
			var that = this;
			this.model.complete({
				data   : $(e.target).data(),
				success: function (response) {
					response = LP.Hook.applyFilters('learn_press_user_complete_lesson_response', response);
					if (response.next_item) {
						//that._autoNextItem(response.next_item, 3);
					}
					LP.Hook.doAction('learn_press_user_completed_lesson', response, that);
				}
			});
		}
	});
	$.LP_Course_Item.Collection = Backbone.Collection.extend({
		model     : $.LP_Course_Item.Model,
		current   : 0,
		lastLoaded: null,

		initialize: function () {
			var that = this;
			_.bindAll(this, 'initItems', 'loadItem');
			this.initItems();
		},
		initItems : function () {
			var that = this;
			$('.section-content .course-item').each(function () {
				var $li = $(this),
					$link = $li.find('a'),
					id = parseInt($link.attr('data-id')),
					args = {
						id     : id,
						nonce  : {
							complete: $link.attr('data-complete-nonce')
						},
						rootUrl: $link.attr('href'),
						type   : $li.data('type')
					};
				if ($li.hasClass('item-current')) {
					that.current = id;
					//args.content = $('#learn-press-course-lesson')
				}
				var model = new $.LP_Course_Item.Model(args);
				that.add(model);
			});

		},
		loadItem  : function (item, link) {

			if ($.isNumeric(item)) {
				item = this.findWhere({id: item});
			} else if ($.type(item) == 'string') {
				item = this.findWhere({rootUrl: item});
			} else {

			}
			if (LP.Hook.applyFilters('learn_press_load_item_content', true, item, link) !== false) {
				if (item) {
					if (this.view) {
						this.view.undelegateEvents();
						//this.view.model.set('content', this.view.$el);
						$('.course-item.item-current')
							.removeClass('item-current');
						$('.course-item.course-item-' + item.get('id'))
							.addClass('item-current');
					}
					if (link) {
						item.set('rootUrl', link);
						item.rootUrl = link;
					}
					this.view = new $.LP_Course_Item.View({model: item});
				}
			}
		}
	});

	$.LP_Course_Item_List_View = Backbone.View.extend({
		model     : $.LP_Course_Item.Collection,
		el        : 'body',
		events    : {
			'click .section-content .course-item a': '_loadItem',
			'click .course-item-nav a'             : '_loadItem'
		},
		initialize: function (args) {
			_.bindAll(this, '_loadItem');
		},
		_loadItem : function (e) {
			e.preventDefault();
			var $item = $(e.target).closest('a'),
				id = parseInt($item.attr('data-id')),
				link = $item.attr('href');
			var item = id ? id : link;
			if (this.lastLoaded == item) {
				return;
			}
			this.model.loadItem(item, link);
		}
	});

})(jQuery);
