/**
 * Single course JS
 *
 * @requires jQuery, Backbone
 */
;(function ($) {

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
			this.$doc = $(document),
				this.on('change', this.onChange);
			_.bindAll(this, 'itemLoaded');
		},
		onChange       : function (a, b) {
			var data = {
				item: this
			}
			if (a.changed['current'] !== undefined) {
				$.extend(data, {
					key  : 'current',
					value: a.changed['current']
				});
				this.get('el').find('.lp-label-viewing').toggle(a.changed['current']);
				if (this.onChangeCurrent(data) !== false && a.changed['current']) {
					$.ajax({
						url     : this.get('url'),
						dataType: 'html',
						success : this.itemLoaded
					});
				}
			}
		},
		onChangeCurrent: function (data) {
			return $(document).triggerHandler('learn_press_item_changed', data)
		},
		isCurrent      : function () {
			return this.get('current') == true;
		},
		itemLoaded     : function (response) {
			var $html = $(response);
			if (this.get('type') == 'lp_lesson') {
				console.log($html.find('#learn-press-course-lesson').html())
				$('#learn-press-course-lesson').replaceWith($html.find('#learn-press-course-lesson'));
			} else {
				this._trigger('learn_press_item_loaded', {html: $html, item: this});
			}
		},
		_trigger       : function () {
			this.$doc.trigger(arguments);
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
			}
		}
	});

	CourseModel = LP.CourseModel = Backbone.Model.extend({
		items     : new CourseItems(),
		initialize: function () {
			this.parseItems();
		},
		parseItems: function () {
			var $items = $('#learn-press-course-curriculum .course-item');
			_.forEach($items, function (item) {
				var $item = $(item),
					args = {
						title    : $item.find('.course-item-title').text(),
						type     : $item.data('type'),
						url      : $item.children('a').attr('href'),
						id       : $item.children('a, span').data('id'),
						current  : $item.find('.lp-label-viewing').is(':visible'),
						completed: $item.find('.lp-label-completed').is(':visible'),
						el       : $item
					};
				this.items.add(new CourseItem(args));
			}, this);
		}
	});

	CourseView = LP.CourseView = Backbone.View.extend({
		el          : '.course-summary',
		events      : {
			'click .course-item-title': '_loadItem'
		},
		initialize  : function () {
			_.bindAll(this, '_itemChanged');
			this.initEvents();
		},
		initEvents  : function () {
			$(document).on('learn_press_item_changed', this._itemChanged)
		},
		_itemChanged: function (e, data) {
			switch (data.key) {
				case 'current':
					if (data.value == true) {
						this.loadItem(data.item)
					}
			}
		},
		_loadItem   : function (e) {
			var $tag = $(e.target).closest('.course-item-title');
			if (!$tag.is('a')) {
				return false;
			}
			var id = $tag.data('id'),
				type = $tag.closest('.course-item').data('type'),
				$item = this.model.items.findWhere({id: id});
			if (!$item) {
				return false;
			}
			if ($item.isCurrent()) {
				return false;
			}
			this.model.items.setCurrent($item);
			e.preventDefault();
		},
		loadItem    : function (item) {
			console.log(item)
		}
	});

	$(document).ready(function () {
		LP.Views.Course = new CourseView({
			model: new CourseModel()
		});
	})
})(jQuery);
