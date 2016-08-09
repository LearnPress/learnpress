/**
 * Single course functions
 */
if (typeof LearnPress == 'undefined') {
	window.LearnPress = {}
}
;(function ($) {
	"use strict";
	LearnPress.Course = $.extend(
		LearnPress.Course || {}, {
			finish: function (data, callback) {
				LearnPress.$Course && LearnPress.$Course.finishCourse({data: data, success: callback});
			}
		}
	);
	//var LearnPress_View_Course = window.LearnPress_View_Course = Backbone.View.extend({

	var Course = function (args) {
			this.model = new Course.Model(args);
			this.view = new Course.View({
				model: this.model
			});
		},
		Template = function (id, tmpl_data) {
			var compiled,
				options = {
					evaluate   : /<#([\s\S]+?)#>/g,
					interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
					escape     : /\{\{([^\}]+?)\}\}(?!\})/g,
					variable   : 'data'
				},
				template = function (data) {
					compiled = compiled || _.template($('#learn-press-template-' + id).html(), null, options);
					return compiled(data);
				};
			return tmpl_data ? $(template(tmpl_data)) : template;
		},
		Course_Item = Backbone.Model.extend({
			initialize     : function () {
				this.on('change:content', this._changeContent);
			},
			_changeContent : function () {
				LP.Hook.doAction('learn_press_update_item_content', this);
			},
			request        : function (args) {
				var that = this;
				if (!this.get('url')) {
					return false;
				}
				args = $.extend({
					context : null,
					callback: null
				}, args || {})
				LP.ajax({
					url     : this.get('url'),
					action  : 'load-item',
					data    : $.extend({
						format: 'html'
					}, this.toJSON()),
					dataType: 'html',
					success : function (response) {
						LP.Hook.doAction('learn_press_course_item_loaded', response, that);
						response = LP.Hook.applyFilters('learn_press_course_item_content', response, that);
						$.isFunction(args.callback) && args.callback.call(args.context, response, that);
					}
				});
				return this;
			},
			complete       : function (args) {
				var that = this;
				args = $.extend({
					context : null,
					callback: null,
					format  : 'json'
				}, this.toJSON(), args || {});
				var data = {};

				// Omit unwanted fields
				_.forEach(args, function (v, k) {
					if (($.inArray(k, ['content', 'current', 'title', 'url']) == -1) && !$.isFunction(v)) {
						data[k] = v;
					}
					;
				});
				LP.ajax({
					url     : this.get('url'),
					action  : 'complete-item',
					data    : data,
					dataType: 'json',
					success : function (response) {
						///response = LP.parseJSON(response);
						LP.Hook.doAction('learn_press_course_item_completed', response, that);
						response = LP.Hook.applyFilters('learn_press_course_item_complete_response', response, that);
						$.isFunction(args.callback) && args.callback.call(args.context, response, that);
					}
				});
			},
			start          : function (args) {
				var args = $.extend({
					course_id: 0,
					quiz_id  : this.get('id'),
					security : null,
				}, args || {});
				var data = this._validateObject(args), that = this;
				LP.ajax({
					url     : this.get('url'),
					action  : 'start-quiz',
					data    : data,
					dataType: 'json',
					success : function (response) {
						$.isFunction(args.callback) && args.callback.call(args.context, response, that);
					}
				});
			},
			finishQuiz     : function (args) {
				var args = $.extend({
					course_id: 0,
					quiz_id  : this.get('id'),
					security : null,
				}, args || {});
				var data = this._validateObject(args), that = this;
				LP.ajax({
					url     : this.get('url'),
					action  : 'finish-quiz',
					data    : data,
					dataType: 'json',
					success : function (response) {
						$.isFunction(args.callback) && args.callback.call(args.context, response, that);
					}
				});
			},
			retakeQuiz     : function (args) {
				var args = $.extend({
					course_id: 0,
					quiz_id  : this.get('id'),
					security : null,
				}, args || {});
				var data = this._validateObject(args), that = this;
				LP.ajax({
					url     : this.get('url'),
					action  : 'retake-quiz',
					data    : data,
					dataType: 'json',
					success : function (response) {
						$.isFunction(args.callback) && args.callback.call(args.context, response, that);
					}
				});
			},
			_toJSON        : function () {
				// call parent method
				var json = Course_Item.__super__.toJSON.apply(this, arguments);
				console.log(json);
				alert();
			},
			_validateObject: function (obj) {
				var ret = {};
				for (var i in obj) {
					if (!$.isFunction(obj[i])) {
						ret[i] = obj[i];
					}
				}
				return ret;
			}
		}),
		Course_Items = Backbone.Collection.extend({
			model      : Course_Item,
			current    : null,
			len        : 0,
			initialize : function () {

				this.on('add', function (model) {
					this.listenTo(model, 'change', this.onChange);
					model.set('index', this.len++);
				}, this);

			},
			onChange   : function (a, b) {
				if (a.get('current') == true) {
					this.current = a;
					for (var i = 0; i < this.length; i++) {
						var e = this.at(i);
						if (e.get('id') == a.get('id')) {
							continue;
						}
						if (e.get('current') == false) {
							continue;
						}
						this.stopListening(e, 'change', this.onChange);
						e.set('current', false);
						this.listenTo(e, 'change', this.onChange);

						console.log(a.get('index'));
					}
				}
			},
			getNextItem: function () {
				var next = false;
				if (this.current) {
					var index = this.current.get('index') + 1;
					next = this.at(index);
					while (!next.$el.hasClass('viewable')) {
						index++;
						next = this.at(index);
						if (!next || index > 1000) {
							break;
						}
					}
				}
				return next;
			},
			getPrevItem: function () {
				var prev = false;
				if (this.current) {
					var index = this.current.get('index') - 1;
					if (index >= 0) {
						prev = this.at(index);
						while (!prev.$el.hasClass('viewable')) {
							index--;
							prev = this.at(index);
							if (!prev || index < 0) {
								break;
							}
						}
					}
				}
				return prev;
			}
		});
	Course.View = Backbone.View.extend({
		el               : 'body', //'.course-summary',
		itemEl           : null,
		events           : {
			'click .viewable .button-load-item': '_loadItem',
			'click .prev-item, .next-item'     : '_loadItem',
			'click .viewable'                  : '_loadItem',
			'click .button-complete-item'      : '_completeItem',
			'click .section-header'            : '_toggleSection',
			'click .learn-press-nav-tab'       : '_tabClick',
			'click .button-start-quiz'         : '_startQuiz',
			'click .button-finish-quiz'        : '_finishQuiz',
			'click .button-retake-quiz'        : '_retakeQuiz'

			//'click #learn-press-button-complete-item': '_c'
		},
		itemLoading      : 0,
		currentItem      : null,
		initialize       : function () {
			var $item = this.$('.course-item');
			_.bindAll(this, 'updateItemContent', '_tabClick', '_showPopup', 'removePopup');
			this.itemEl = this.$('#learn-press-content-item');
			this.model.items.forEach(function (v, k) {
				v.course = this;
				v.$el = $item.filter('.course-item-' + v.get('id'));
			}, this);
			this._initHooks();

			/*if (this.$('.course-item.item-current').length) {
			 this.viewItem(this.$('.course-item.item-current .button-load-item').data('id'), {
			 content: this.itemEl.html()
			 });
			 }*/
			if (this.model.get('current_item')) {
				this.viewItem(this.model.get('current_item'), {
					content: this.itemEl.html()
				});
				//this.showPopup();
			}
		},
		_initHooks       : function () {
			LP.Hook.addAction('learn_press_update_item_content', this.updateItemContent);
			$(document).on('learn_press_popup_remove', this.removePopup)
		},
		_loadItem        : function (e) {
			e.preventDefault();
			var that = this,
				$target = $(e.target),
				id = $target.hasClass('button-load-item') ? $target.data('id') : $target.find('.button-load-item').data('id');
			if (!id || $target.closest('.course-item').hasClass('item-current') || this.itemLoading) {
				return;
			}
			this.itemLoading = id;
			console.log(this.itemLoading)
			this.currentItem = this.model.getItem(id).set('content', '');
			this.currentItem.request({
				context : this,
				item    : this.currentItem,
				callback: function (response) {
					that.viewItem(id, {
						content: $(response).html()
					});
					that.itemLoading = 0;
				}
			});
		},
		_completeItem    : function (e) {
			var that = this,
				$button = $(e.target),
				security = $button.data('security'),
				$section = this.getCurrentSection(),
				$item = $button.closest('.course-item');
			if ($item.length) {

			}
			this.currentItem.complete({
				security  : security,
				course_id : this.model.get('id'),
				section_id: $section.length ? $section.data('id') : 0,
				callback  : function (response, item) {
					if (response.result == 'success') {
						// highlight item
						item.$el.removeClass('item-started').addClass('item-completed focus off');
						// then restore back after 3 seconds
						_.delay(function (item) {
							item.$el.removeClass('focus off');
						}, 3000, item);

						that.$('.learn-press-course-results-progress').replaceWith($(response.html.progress));
						$section.find('.section-header').replaceWith($(response.html.section_header));
					}
				}
			});
		},
		_toggleSection   : function (e) {
			var $head = $(e.target).closest('.section-header'),
				$sec = $head.siblings('ul.section-content');
			$sec.slideToggle();
		},
		_tabClick        : function (e) {
			var tab = $(e.target).closest('.learn-press-nav-tab').data('tab');
			console.log(tab)
			if (tab == 'overview') {
				LP.setUrl(this.model.get('url'))
			} else if (tab == 'curriculum') {
				if (this.currentItem) {
					LP.setUrl(this.currentItem.get('url'))
				}
			}
		},
		_startQuiz       : function (e) {
			var that = this,
				$button = $(e.target),
				security = $button.data('security');
			this.currentItem.start({
				security : security,
				course_id: this.model.get('id'),
				callback : function (response, item) {
					that.currentItem.set('content', response.html);
					LP.log(response.html)
				}
			});
		},
		_finishQuiz      : function (e) {
			var that = this,
				$button = $(e.target),
				security = $button.data('security');
			this.currentItem.finishQuiz({
				security : security,
				course_id: this.model.get('id'),
				callback : function (response, item) {
					that.currentItem.set('content', response.html);
					LP.log(response.html)
				}
			});
		},
		_retakeQuiz      : function (e) {
			var that = this,
				$button = $(e.target),
				security = $button.data('security');
			this.currentItem.retakeQuiz({
				security : security,
				course_id: this.model.get('id'),
				callback : function (response, item) {
					that.currentItem.set('content', response.html);
					LP.log(response.html)
				}
			});
		},
		_showPopup       : function (e) {
			e.preventDefault();
			var args = {
				model : new Course.ModelPopup(),
				course: this
			};
		},
		showPopup        : function () {
			if (!this.popup) {
				this.popup = new Course.Popup({
					model : new Course.ModelPopup(),
					course: this
				});
			}
		},
		removePopup      : function () {
			this.popup = null;
		},
		getCurrentSection: function () {
			return this.currentItem.$el.closest('.section');
		},
		updateItemContent: function (item) {
			///this.itemEl.html(item.get('content'));
			this.$('#popup-content-inner').html(item.get('content'))
		},
		updateFooterNav  : function () {
			var prev = this.model.getPrevItem(),
				next = this.model.getNextItem();
			this.$('#popup-footer').find('.prev-item, .next-item').remove();
			if (prev) {
				this.$('#popup-footer').append(Template('course-prev-item', prev.toJSON()));
			}
			if (next) {
				this.$('#popup-footer').append(Template('course-next-item', next.toJSON()));
			}
		},
		viewItem         : function (id, args) {
			var item = this.model.getItem(id);
			if (item) {
				item.set(args);
				item.set('current', true);
			}
			this.showPopup();
			this.itemEl.show();
			this.currentItem = item;
			this.$('.item-current').removeClass('item-current');
			this.$('.course-item [data-id="' + item.get('id') + '"]').parent().addClass('item-current item-has-status');
			LP.setUrl(item.get('url'));
			this.updateItemContent(item);
			this.updateFooterNav();
			return item;
		}

	});
	Course.Model = Backbone.Model.extend({
		items      : null,
		initialize : function () {
			LP.log('Course.Model.initialize');
			this.createItems();
		},
		createItems: function () {
			this.items = new Course_Items();
			this.items.add(this.get('items'));
		},
		getItem    : function (args) {
			return $.isPlainObject(args) ? this.items.findWhere(args) : this.items.findWhere({id: args});
		},
		getNextItem: function () {
			return this.items.getNextItem();
		},
		getPrevItem: function () {
			return this.items.getPrevItem();
		},
		getItems   : function (args) {
			return typeof args == 'undefined' ? this.items : this.items.where(args);
		},
		getCurrent : function () {
		}
	});

	Course.Popup = Backbone.View.extend({
		course              : null,
		events              : {
			'click .popup-close': '_closePopup',
			//'click .button-load-item': '_loadItem'
		},
		initialize          : function (args) {
			_.bindAll(this, '_ajaxLoadItemSuccess');
			console.log(args)
			this.course = args.course;
			this.render();
		},
		render              : function () {
			this.$el.attr('tabindex', '0').append(Template('curriculum-popup', {})).css({}).appendTo($(document.body));
			this.curriculumPlaceholder = $('<span />');
			this.progressPlaceholder = $('<span />');

			var $curriculum = this.course.$('#learn-press-course-curriculum');
			var $progress = this.course.$('.learn-press-course-results-progress');

			this.curriculumPlaceholder.insertAfter($curriculum);
			$curriculum.appendTo(this.$('#popup-sidebar'));

			this.progressPlaceholder.insertAfter($progress);
			this.course.$('#popup-content').prepend($progress);

			$('html').css({overflow: 'hidden'})
		},
		_closePopup         : function (e) {
			e.preventDefault();
			this.curriculumPlaceholder.replaceWith(this.$('#learn-press-course-curriculum'));
			this.progressPlaceholder.replaceWith(this.$('.learn-press-course-results-progress'));

			this.undelegateEvents();
			this.remove();
			$(document).off('focusin');
			$('html').css('overflow', '').trigger('learn_press_popup_remove')
		},
		_loadItem           : function (e) {
			e.preventDefault();
			$.ajax({
				url    : $(e.target).attr('href'),
				success: this._ajaxLoadItemSuccess
			});
		},
		_ajaxLoadItemSuccess: function (response) {
			this.$('#popup-content-inner').html($(response).contents().find('.lp_course'))
		}
	}),
		Course.ModelPopup = Backbone.Model.extend({
			initialize: function () {
				console.log('ModelPopup.initialize')
			}
		});


	LP.Course = Course;
	LP.$Course = new Course(LP_Course_Params);
	return;

	$(document).ready(function () {
		//LearnPress.Course.init( $(this), $(document.body) );
		LearnPress.$Course = new LearnPress_View_Course();

		LearnPress.Hook.addAction('learn_press_item_content_loaded', function (a, b) {
			setTimeout(function () {
				try {
					//console.log(a.html())
					window.wp.mediaelement.initialize();
				} catch (e) {
				}
			}, 300);
		})
	});
})(jQuery);