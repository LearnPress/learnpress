/**
 * Single course JS
 *
 * @requires jQuery, Backbone
 */
;(function ($) {

	window.LP_V2 = true;
	if (typeof jQuery == 'undefined' || typeof Backbone == 'undefined') {
		console.log('Error');
		return;
	}

	if (typeof LP == 'undefined') {
		window.LP = {};
	}

	$.extend(LP, {
		Views: {},
		Model: {}
	});

	var CourseModel,
		CourseView,
		CourseItem,
		CourseItems;

	CourseItem = LP.CourseItem = Backbone.Model.extend({
		$doc           : false,
		initialize     : function () {
			this.$doc = $(document);
			this.on('change', this.onChange);
			_.bindAll(this, 'itemLoaded');
		},
		onChange       : function (a, b) {
			var that = this,
				data = {
					item: this
				};
			if (a.changed['current'] !== undefined) {
				$.extend(data, {
					key  : 'current',
					value: a.changed['current']
				});
				this.get('el').find('.lp-label-viewing').toggle(a.changed['current']);
				if (this.onChangeCurrent(data) !== false && a.changed['current']) {
					if (that.get('content')) {
						that.itemLoaded.apply(that, [that.get('content')])
					} else {
						if (that.get('type') == 'lp_lesson') {
							$.ajax({
								url     : that.get('url'),
								dataType: 'html',
								success : that.itemLoaded
							});
						} else {
							that.itemLoaded()
						}
					}
				}
			}
		},
		onChangeCurrent: function (data) {
			return this.$doc.triggerHandler('learn_press_item_current_changed', data)
		},
		isCurrent      : function () {
			return this.get('current') == true;
		},
		itemLoaded     : function (response) {
			var $html = $(response);
			this.set('content', response);
			switch (this.get('type')) {
				case 'lp_lesson':
					var $content = $html.find('#learn-press-content-item').html();
					$('#learn-press-content-item').html($content).show();
					break;
				case 'lp_quiz':
					var data = this.$doc.triggerHandler('learn_press_item_redirect_url', {
						url : this.get('url'),
						item: this
					});
					if (data !== false) {
						if (data == undefined || data.redirect == undefined) {
							data.redirect = this.get('url');
						}
						LP.reload(data.redirect);
					}
					break;
				default:
					this._trigger('learn_press_item_' + this.get('type') + '_loaded', {html: $html, item: this});
			}
			try {
				window.wp.mediaelement.initialize();
			} catch (e) {
			}
			this.$doc.triggerHandler('learn_press_item_loaded', {html: $html, item: this});
		},
		_trigger       : function () {
			var args = [];
			for (var i = 0, n = arguments.length; i < n; i++) {
				args.push(arguments[i]);
			}
			return this.$doc.triggerHandler.apply(this.$doc[0], args);
		}
	});

	CourseItems = LP.CourseItems = Backbone.Collection.extend({
		getCurrent: function () {
			var item = this.findWhere({current: true});
			return item;
		},
		setCurrent: function (item) {
			if (!item instanceof CourseItem) {
				item = this.findWhere({id: item});
			}
			this.map(function (item) {
				if (item.get('current')) {
					item.set('current', false);
				}
			});
			if (item) {
				item.set('current', true);
				var _r = $(document).triggerHandler('learn_press_set_course_url', {url: item.get('url')});
				if (_r !== false) {
					if (_r == undefined) {
						_r = item.get('url')
					}
					try {
						LP.setUrl(_r);
					} catch (e) {
					}
				}
			}
		}
	});

	CourseModel = LP.CourseModel = Backbone.Model.extend({
		items     : new CourseItems(),
		initialize: function (args) {
			this.parseItems(args);
		},
		parseItems: function (args) {
			var $items = $('.course-curriculum .course-item');
			_.forEach(args.items, function (item, i) {
				item.el = $items.filter('.course-item-' + item.id);
				this.items.add(new CourseItem(item));
			}, this);
		}
	});

	CourseView = LP.CourseView = Backbone.View.extend({
		el           : 'body',
		events       : {
			'click .button-load-item' : '_loadItem',
			'click .course-item-nav a': '_loadItemNav',
			'click .finish-course'    : '_finishCourse'
		},
		initialize   : function () {
			_.bindAll(this, '_itemChanged', '_itemLoaded');
			this.initEvents();
			this.initCurrent();
		},
		initEvents   : function () {
			$(document)
				.on('learn_press_item_current_changed', this._itemChanged)
				.on('learn_press_item_loaded', this._itemLoaded)

		},
		initCurrent  : function () {
			var current = this.model.items.getCurrent();
			if (!current) {
				return;
			}
			current.itemLoaded($("html").html());
		},
		_itemChanged : function (e, data) {
			var that = this;
			switch (data.key) {
				case 'current':
					if (data.value == true) {
						if (data.item.get('type') == 'lp_quiz') {
							data.callback && data.callback();
							return;
						}
						$('#learn-press-content-item').addClass('loading');
						LP.toElement('#learn-press-content-item', {
							delay   : 0,
							duration: 0,
							callback: function () {

							}
						});
					}
			}
		},
		_itemLoaded  : function (e, data) {
			$('.course-description-heading, .course-description').hide();
			$('#learn-press-content-item').removeClass('loading');
			return data;
		},
		_loadItem    : function (e) {
			e.preventDefault();
			var $tag = $(e.target).closest('.button-load-item');
			if (!$tag.is('a')) {
				return false;
			}
			var id = parseInt($tag.data('id')),
				type = $tag.closest('.course-item').data('type'),
				$item = this.model.items.findWhere({id: id});
			if (!$item) {
				return false;
			}
			if ($item.isCurrent()) {
				return false;
			}
			this.model.items.setCurrent($item);
		},
		_loadItemNav : function (e) {
			e.preventDefault();

			var id = $(e.target).attr('data-id'),
				$item = this.$('.section-content a.button-load-item[data-id="' + id + '"]');
			$item.trigger('click');
		},
		_finishCourse: function (e) {

		},
		loadItem     : function (item) {
		},
		extend       : function (obj) {
			for (var i in obj) {
				this[i] = obj[i];
			}
			return this;
		}
	});

	$(document).ready(function () {
		if (typeof SingleCourse_Params != 'undefined') {
			LP.Views.Course = new CourseView({
				model: new CourseModel(SingleCourse_Params)
			});
		}
	})
})(jQuery);
