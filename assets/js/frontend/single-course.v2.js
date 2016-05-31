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
			this.$doc = $(document);
			this.on('change', this.onChange);
			_.bindAll(this, 'itemLoaded');
		},
		onChange       : function (a, b) {
			var that = this,
				callback = false,
				data = {
					item: this
				};
			if (a.changed['current'] !== undefined) {
				$.extend(data, {
					key  : 'current',
					value: a.changed['current']
				});
				this.get('el').find('.lp-label-viewing').toggle(a.changed['current']);
				data.callback = function () {
					setTimeout(function () {
						if (!callback)return;
						if (that.get('content')) {
							that.itemLoaded.apply(that, [that.get('content')])
						} else {
							$.ajax({
								url     : that.get('url'),
								dataType: 'html',
								success : that.itemLoaded
							});
						}
					}, 350)
				}
				if (this.onChangeCurrent(data) !== false && a.changed['current']) {
					callback = true;
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
					var $content = $html.find('#learn-press-course-lesson');
					$('#learn-press-content-item').html($content);
					break;
				case 'lp_quiz':

					/**var iframe = $('<iframe />', {'title': 'XXXXXXXXXXX'});
					 $('#learn-press-content-item').html(iframe)
					 iframe[0].contentWindow.document.open();
					 iframe[0].contentWindow.document.write(response);
					 iframe[0].contentWindow.document.close();

					 iframe.load(function () {
						$(this).css({
							width : '100%',
							height: $(this).contents().find('body').height()
						})
					})*/
					LearnPress.reload(this.get('url'))
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
			console.log(args)
			this.$doc.triggerHandler.apply(this.$doc[0], args);
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
				LearnPress.setUrl(item.get('url'));
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
			'click .course-item-title': '_loadItem',
			'click .course-item-nav a': '_loadItemNav'
		},
		initialize  : function () {
			_.bindAll(this, '_itemChanged', '_itemLoaded');
			this.initEvents();
		},
		initEvents  : function () {
			$(document)
				.on('learn_press_item_current_changed', this._itemChanged)
				.on('learn_press_item_loaded', this._itemLoaded)

		},
		_itemChanged: function (e, data) {
			var that = this;
			switch (data.key) {
				case 'current':
					if (data.value == true) {
						if (data.item.get('type') == 'lp_quiz') {
							//LearnPress.reload(data.item.get('url'));
							window.open(data.item.get('url'))
							return false;
						}
						LearnPress.toElement('#learn-press-content-item', {
							offset  : 100,
							delay   : 0,
							duration: 0,
							callback: function () {
								that.$('#learn-press-content-item').slideUp('slow', function () {
									data.callback && data.callback();
								});
							}
						});
					}
			}
		},
		_itemLoaded : function (e, data) {
			this.$('#learn-press-content-item').slideDown();
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
		_loadItemNav: function (e) {
			var id = $(e.target).data('id'),
				$item = this.$('.section-content a[data-id="' + id + '"]');
			$item.trigger('click');
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
