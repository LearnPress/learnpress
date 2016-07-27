/**
 * Single course functions
 */
if (typeof LP == 'undefined') {
	window.LP = {}
}
// wtf
/*
 ;(function ($) {

 var ViewCourse,
 ModelCourse,
 ViewPopup,
 ModelPopup,
 Template,
 Course = LP.Course = {
 initialize: function (args) {
 LP.Views = $.extend(LP.Views || {}, {
 Course: new this.View({
 model: new this.Model(args)
 })
 });
 }
 },
 ViewPopup = Backbone.View.extend({
 events              : {
 'click .popup-close'     : '_closePopup',
 'click .button-load-item': '_loadItem'
 },
 initialize          : function () {
 _.bindAll(this, '_ajaxLoadItemSuccess')
 this.render();
 },
 render              : function () {
 this.$el.attr('tabindex', '0').append(Template('curriculum-popup', {})).css({}).appendTo($(document.body));
 $('html').css({overflow: 'hidden'})
 },
 _closePopup         : function (e) {
 e.preventDefault();
 this.undelegateEvents();
 this.remove();
 $(document).off('focusin');
 $('html').css('overflow', '')
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
 ModelPopup = Backbone.Model.extend({
 initialize: function () {
 console.log('ModelPopup.initialize')
 }
 });

 ViewCourse = Backbone.View.extend({
 el        : '.course-summary',
 events    : {
 'click .button-load-item': '_showPopup'
 },
 initialize: function () {
 console.log('View course initialize');
 _.bindAll(this, '_showPopup')
 },
 _showPopup: function (e) {
 e.preventDefault();
 new ViewPopup({
 model: new ModelPopup()
 })
 }
 });

 ModelCourse = Backbone.Model.extend({
 initialize: function () {
 console.log('Model course initialize');
 }
 });

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
 };

 Course.View = ViewCourse;
 Course.Model = ModelCourse;

 Course.initialize({
 id: 1000
 });

 })(jQuery);
 */

;(function ($) {
	"use strict";
	LP.Course = $.extend(
		LP.Course || {}, {
			finish: function (data, callback) {
				LP.$Course && LP.$Course.finishCourse({data: data, success: callback});
			}
		}
	);
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
			model     : Course_Item,
			initialize: function () {
				this.on('add', function (model) {
				})
			}
		});
	Course.View = Backbone.View.extend({
		el               : 'body', //'.course-summary',
		itemEl           : null,
		events           : {
			'click .button-load-item'    : '_loadItem',
			'click .button-complete-item': '_completeItem',
			'click .section-header'      : '_toggleSection',
			'click .learn-press-nav-tab' : '_tabClick',
			'click .button-start-quiz'   : '_startQuiz',
			'click .button-finish-quiz'  : '_finishQuiz',
			'click .button-retake-quiz'  : '_retakeQuiz'

			//'click #learn-press-button-complete-item': '_c'
		},
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
				id = $(e.target).data('id');
			this.currentItem = this.model.getItem(id).set('content', '');
			this.currentItem.request({
				context : this,
				item    : this.currentItem,
				callback: function (response) {
					that.viewItem(id, {
						content: $(response).html()
					});
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
		viewItem         : function (id, args) {
			var item = this.model.getItem(id);
			if (item) {
				item.set(args);
				item.set('current', true);
			}
			this.showPopup();
			this.itemEl.show();
			this.currentItem = item;
			this.$('.course-item [data-id="' + item.get('id') + '"]').parent().addClass('item-current item-has-status').siblings('.item-current').removeClass('item-current');
			LP.setUrl(item.get('url'));
			this.updateItemContent(item);
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
			var $curriculum = this.course.$('#learn-press-course-curriculum');
			this.curriculumPlaceholder.insertAfter($curriculum);
			$curriculum.appendTo(this.$('#popup-sidebar'));
			$('html').css({overflow: 'hidden'})
		},
		_closePopup         : function (e) {
			e.preventDefault();
			this.curriculumPlaceholder.replaceWith(this.$('#learn-press-course-curriculum'));
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
	var LP_View_Course = window.LP_View_Course = Backbone.View.extend({
		$doc             : null,
		$body            : null,
		courseItems      : null,
		courseItemsView  : null,
		el               : '.course-summary',
		events           : {
			//'click .curriculum-sections .section-content > li a': '_loadLesson',
			//'click .course-item-nav a': '_loadLesson',
			'click #learn-press-finish-course': '_finishCourse'
		},
		initialize       : function (args) {


			var id = parseInt($('.course-item.item-current').find('>a').attr('data-id')),
				item = null;
			if (id) {
				$('[id="learn-press-course-lesson"]').html('')
				//this.model.loadItem(item);
			}
			this.courseItems = new $.LP_Course_Item.Collection();
			this.courseItemsView = new $.LP_Course_Item_List_View({model: this.courseItems});

			if (id) {
				this.courseItems.loadItem(id);
			}

			_.bindAll(this, '_finishCourse', '_sanitizeProgress', 'completeLesson');
			this.$doc = $(document);
			this.$body = $(document.body);
			LP.Hook.addFilter('learn_press_before_load_item', function ($view) {
				LP.MessageBox.blockUI();
				if ($view.model.get('type') == 'lp_quiz') {
					var redirect = LP.Hook.applyFilters('learn_press_course_item_redirect_url', $('.course-item-' + $view.model.get('id') + ' a').prop('href'), $view);
					if (redirect !== false) {
						var win = window.open(redirect, '_blank');
						try {
							win.focus();
						} catch (e) {
						}
					}
				}
				return true;
			});
			LP.Hook
				.addAction('learn_press_item_content_loaded', this.itemLoaded)
				.addAction('learn_press_user_completed_lesson', this.completeLesson)
				.addAction('learn_press_user_passed_course_condition', function () {
				});

			this._sanitizeProgress();

		},
		itemLoaded       : function ($content, $view) {
			LP.toElement('#learn-press-course-lesson-heading');
			LP.MessageBox.hide();
		},
		completeLesson   : function (response, that) {
			if (response && response.result == 'success') {
				var $button = this.$('.complete-lesson-button').addClass('completed').prop('disabled', true).html(response.button_text);
				$('.course-item-' + response.id).addClass('item-completed');
				if (response.course_result) {
					if (response.can_finish) {
						this.$('#learn-press-finish-course').removeClass('hide-if-js');
						LP.Hook.doAction('learn_press_user_passed_course_condition', response, this, that);
					}
					if (response.message) {
						$(response.message).insertBefore($button);
					}
					this.updateProgress(response);
				}
			}
		},
		updateProgress   : function (data) {
			$('.lp-course-progress')
				.attr({
					'data-value': data.course_result
				})
			this._sanitizeProgress();
		},
		_loadLesson      : function (e) {
			this.loadLesson($(e.target).attr('href'));
		},
		loadLesson       : function (permalink, args) {
			var that = this;
			LP.Hook.doAction('learn_press_before_load_lesson', permalink, this);
			args = $.extend({
				success: function () {
					return true;
				},
				error  : function () {
				}
			}, args || {})

			$.ajax({
				url    : permalink,
				success: function (response) {
					var ret = true;
					$.isFunction(args.success) && ( ret = args.success.call(this, response) );
					if (ret === true) {
						var $html = $(response),
							$newLesson = $html.find('#learn-press-course-lesson-summary'),
							$newHeading = $html.find('#learn-press-course-lesson-heading');

						$('title').html($html.filter('title').text());
						$('#learn-press-course-description-heading, #learn-press-course-lesson-heading').replaceWith($newHeading)
						$('#learn-press-course-description, #learn-press-course-lesson-summary').replaceWith($newLesson);

						LP.Hook.doAction('learn_press_load_lesson_completed', permalink, that);
						LP.Hook.doAction('learn_press_lesson_content_loaded', $html, this);

					}
				},
				error  : function () {
					// TODO: handle the error here
					LP.MessageBox.hide();
				}
			})
		},
		_finishCourse    : function (e) {
			var that = this,
				$button = $(e.target),
				data = $button.data();
			data = LP.Hook.applyFilters('learn_press_user_finish_course_data', data);
			if (data && data.id) {
				$button.prop('disabled', true);
				this.finishCourse({
					data   : data,
					success: function (response) {
						LP.Hook.applyFilters('learn_press_finish_course_params', response);

						if (response && response.result == 'success') {
							that.$('#learn-press-finish-course, .complete-lesson-button').remove();
							LP.Hook.doAction('learn_press_finish_course', response);
						}
						if (response.message) {
							LP.alert(response.message, function () {
								if (response.redirect) {
									LP.reload(response.redirect);
								}
							});
						} else {
							if (response.redirect) {
								LP.reload(response.redirect);
							}
						}
					}
				});
			}
		},
		finishCourse     : function (args) {
			args = args || {};
			var _do = function (e) {
					if (e) {
						LP.doAjax({
							prefix : '',
							data   : data,
							success: _success
						});
					}
				},
				_success = function (response) {
					$.isFunction(args.success) && args.success.call(that, response);
				},
				that = this,
				data = $.extend({
					'lp-ajax': 'finish_course'
				}, args.data || {});
			LP.confirm(single_course_localize.confirm_finish_course, _do);
		},
		_sanitizeProgress: function () {
			var $el = $('.lp-course-progress'),
				$progress = $('.lp-progress-value', $el),
				$passing = $('.lp-passing-conditional', $el),
				value = parseFloat($el.attr('data-value')),
				passing_condition = parseFloat($el.attr('data-passing-condition')),
				_done = function () {
					var progress = parseInt($progress.css('width')),
						passing = parseInt($passing.css('left'));

					if (value >= passing_condition) {
						$el.addClass('passed');
					}
				};
			$progress.css('width', value + '%').find('span span').html(value);
			setTimeout(_done, 500);

		}
	});

	$(document).ready(function () {
		//LP.Course.init( $(this), $(document.body) );
		LP.$Course = new LP_View_Course();

		LP.Hook.addAction('learn_press_item_content_loaded', function (a, b) {
			setTimeout(function () {
				try {
					//console.log(a.html())
					window.wp.mediaelement.initialize();
				} catch (e) {
				}
			}, 300);
		})
	});

	///as
})(jQuery);