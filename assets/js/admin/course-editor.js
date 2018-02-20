;(function ($) {
	var Course_Editor = function (args) {
		this.model = new Course_Editor.Model(args);
		this.view = new Course_Editor.View({model: this.model});
	};

	var xxx = {
		time : 0,
		check: function (v) {
			if (v == undefined || this.time == 0) {
				this.time = (new Date()).getTime();
			} else {
				var old = this.time;
				this.time = 0;
				return (new Date()).getTime() - old < v;
			}
			return true;
		}
	};

	Course_Editor.View = Backbone.View.extend({
		$editor          : null,
		el               : 'body',
		events           : {
			//'focus .item-name'                   : '_selectItem',
			'focus .section-name'                : '_selectSection',
			'click .button-add-section'          : '_addNewSection',
			'click .button-add-content'          : '_showContentTypes',
			'click .section-content-types > span': '_addNewItem',
			'click .course-row-actions a'        : '_rowActions'
		},
		initialize       : function () {
			this.render();
		},
		render           : function () {
			this.$editor = this.template('course-editor');
			var $curriculum = this.$editor.find('#lp-course-curriculum');
			this.model.sections.forEach(function (section) {
				var $section = this._createSection(section);
				$curriculum.append($section);
			}, this);

			this.addEmptySection({}, $curriculum);

			$curriculum.sortable({
				axis  : 'y',
				handle: '.section-move'
			});


			$('#postbox-container-2').prepend(this.$editor);
		},
		_createSection   : function (section, options) {
			var that = this,
				sectionArgs = section.toJSON(),
				$section = this.template('course-section', sectionArgs),
				$container = $section.find('.section-items');
			options = $.extend({}, options || {})
			//section.$el = $section;
			section.$el = $section;
			section.items.forEach(function (item) {
				var $item = this._createItem(item, section);
				$container.append($item);
			}, this);
			if (!options.addEmptyItem) {
				this.addEmptyItem({section: sectionArgs}, section);
			}
			$container.sortable({
				axis       : 'y',
				handle     : '.section-item-move',
				connectWith: '.section-items',
				stop       : function (e, ui) {
					if (!ui.item.hasClass('ui-draggable')) {
						return;
					}
					var temp_id = parseInt($(this).closest('.course-section').data('temp_id')),
						args = ui.item.data(),
						section = that.model.findSection({temp_id: temp_id});
					var $item = that.addEmptyItem(args, section);
					$item.insertBefore(ui.item);
					$item.find('.item-name').focus();
					ui.item.remove();
					//ui.draggable.remove();
					console.log('DROPPED')
				}
			});

			$section.find('.section-content-types li').draggable({
				revert           : 'invalid',
				connectToSortable: '.section-items',
				helper           : function () {

					var $helper = $(this).clone();
					$helper.get(0).className = 'ui-draggable ui-draggable-handle course-section-item';
					return $helper[0];
				},
			});
			$section.find('.section-items').droppable({
				accept: '.dashicons',
				drop  : function (e, ui) {

					if (!xxx.check(10)) {
						//return;
					}


				}
			});

			return $section;
		},
		_addNewItem      : function (e) {
			e.preventDefault();
			var temp_id = parseInt($(e.target).closest('.course-section').data('temp_id')),
				args = $(e.target).data(),
				section = this.model.findSection({temp_id: temp_id});
			var $item = this.addEmptyItem(args, section);
			$item.find('.item-name').focus();
		},
		_createItem      : function (item, section) {
			var itemArgs = {},
				sectionArgs = {};
			if (!$.isPlainObject(item)) {
				itemArgs = item.toJSON();
			} else {
				itemArgs = item;
			}

			if (!$.isPlainObject(section)) {
				sectionArgs = section.toJSON();
			} else {
				sectionArgs = section;
			}

			item.set('$el', $item);

			var $item = this.template('course-section-item', $.extend(itemArgs, {section: sectionArgs}));
			return $item;
		},
		_selectItem      : function (e) {

		},
		_selectSection   : function (e) {
			var $el = $(e.target);
			$el.closest('.course-section').addClass('active').siblings().removeClass('active');
		},
		_addNewSection   : function (e) {
			var section = new Course_Editor.Section({});
			this.model.sections.add(section);

			var $section = this._createSection(section);
			this.$editor.find('#lp-course-curriculum').append($section);
			$section.find('.section-name').focus();
		},
		_showContentTypes: function (e) {
			e.preventDefault();
			var that = this,
				$content = $(e.target).siblings('.section-content-types');
			if (!$content.is(':visible')) {
				that.$('.section-content-types').not($content).slideUp();
			}
			$content.slideToggle();

		},
		_rowActions      : function (e) {
			e.preventDefault();
			var $el = $(e.target),
				action = $el.data('action'),
				temp_id = parseInt($el.closest('.course-section-item').data('temp_id'));
			switch (action) {
				case 'quick-edit':
					this.showQuickEdit(temp_id);
					break;
				case 'remove-item':
					this.model.remove({temp_id: temp_id});
					break;
				case 'toggle':
					$el.closest('.course-section').find('.section-body').slideToggle();
			}
		},
		showQuickEdit    : function (temp_id) {
			var that = this;
			//this.$('.course-section-item').removeClass('active');
			var /*temp_id = parseInt($el.closest('.course-section-item').addClass('active').data('temp_id')),*/
				model = this.model.findItem({temp_id: temp_id});
			if (!model) {
				return;
			}
			var $tmpl = this.template('course-item-editor', model.toJSON());
			this.$('#course-item-editor .course-item-editor').remove();
			$tmpl.appendTo(this.$('#course-item-editor'));

			( function (editor_id) {
				var init, id, $wrap;
				tinymce.execCommand('mceRemoveEditor', true, that.editor_id);
				if (typeof tinymce !== 'undefined') {
					init = tinyMCEPreInit.mceInit['content'];
					init.selector = '#' + editor_id;
					init.tabfocus_elements = init.tabfocus_elements.replace('content', editor_id);
					init.body_class = init.body_class.replace('content', editor_id);
					init.setup = function (ed) {
						ed.on('change', function (e) {
							model.set('post_content', ed.getContent());
						});
					}

					$wrap = tinymce.$('#wp-' + editor_id + '-wrap');
					tinyMCEPreInit.mceInit[editor_id] = init;

					if (( $wrap.hasClass('tmce-active') || !tinyMCEPreInit.qtInit.hasOwnProperty(editor_id) ) && !init.wp_skip_init) {
						tinymce.init(init);

						if (!window.wpActiveEditor) {
							window.wpActiveEditor = editor_id;
						}
					}
					$wrap.css('visibility', '');
				}

				if (typeof quicktags !== 'undefined') {
					var xxx = tinyMCEPreInit.qtInit['content'];
					xxx.id = editor_id;

					tinyMCEPreInit.qtInit[editor_id] = xxx;

					quicktags(xxx);

					if (!window.wpActiveEditor) {
						window.wpActiveEditor = editor_id;
					}
				}
				///tinymce.execCommand('mceAddEditor', true, editor_id);
				QTags._buttonsInit();
				that.editor_id = editor_id;
			}('course-item-content-' + temp_id));
		},
		addEmptySection  : function (args, $curriculum) {
			// add empty section
			var section = new Course_Editor.Section(args);
			this.model.sections.add(section);

			var $section = this._createSection(section);
			if ($curriculum) {
				$curriculum.append($section);
			}
			return $section;
		},
		addEmptyItem     : function (args, section) {
			// add empty section
			var item = new Backbone.Model(args);
			section.items.add(item);
			var $item = this.template('course-section-item', $.extend(item.toJSON()));
			if (section.$el) {
				section.$el.find('.section-items').append($item);
			}
			item.set('$el', $item);
			return $item;
		},
		template         : function (id, args) {
			return $(wp.template(id)(args));
		}
	});

	Course_Editor.Model = Backbone.Model.extend({
		sections     : null,
		rawArgs      : null,
		initialize   : function (args) {
			this.rawArgs = args;
			this.initSections();
		},
		initSections : function () {
			this.sections = new Course_Editor.Sections();
			_.forEach(this.rawArgs.sections, function (section) {
				this.sections.add(new Course_Editor.Section(section))
			}, this);
		},
		findItemWhere: function (atts) {
			var items = [];
			this.sections.forEach(function (section) {
				var find = section.items.findWhere(atts);
				if (find) {
					_.union(items, find);
				}
			});
			return items;
		},
		findItem     : function (atts) {
			var item = undefined;
			this.sections.forEach(function (section) {
				if (item) {
					return true;
				}
				item = section.items.find(atts);

			});
			return item;
		},
		findSection  : function (atts) {
			return this.sections.find(atts);
		},
		remove       : function (atts) {
			var section = this.findSection(atts);
			if (section) {
				this.sections.remove(section);
			} else {
				//var items = this.findItem(atts);
				this.sections.forEach(function (section) {
					var item = section.items.find(atts);
					if (item) {
						section.items.remove(item);
					}
				})
			}
		}
	});

	Course_Editor.Section = Backbone.Model.extend({
		items     : null,
		initialize: function (args) {
			this.items = new Course_Editor.Items();
			_.forEach(args.items, function (v) {
				this.items.add(v);
			}, this);
		},
		toJSON    : function () {
			var keys = ['section_id', 'section_name', 'section_course_id', 'section_order', 'section_description', 'temp_id'],
				json = {};
			_.forEach(keys, function (key) {
				json[key] = this.get(key);
			}, this);
			return json;
		}
	});

	Course_Editor.Sections = Backbone.Collection.extend({
		temp_id   : 1,
		model     : function (args, b) {
			return new Course_Editor.Section(args);
		},
		initialize: function (args) {
			_.bindAll(this, 'incTempId');
			this.on('add', this.incTempId);
			this.on('remove', function (a, b) {
				console.log('remove section: ', a, b)
			})
			/*_.forEach(args, function(v, k){
			 this.sections.add(v);
			 }, this);*/
		},
		incTempId : function (model) {
			model.set('temp_id', Course_Editor.temp_id);
			Course_Editor.temp_id++;
		}
	});

	Course_Editor.Items = Backbone.Collection.extend({
		temp_id   : 1,
		initialize: function (args) {
			_.bindAll(this, 'incTempId');
			this.on('add', this.incTempId);
			this.on('remove', function (a, b) {
				console.log(a, a.get('$el'))
				if (a.get('$el')) {
					a.get('$el').remove();
				}
			})
		},
		incTempId : function (model) {
			model.set('temp_id', Course_Editor.temp_id);
			Course_Editor.temp_id++;
		}
	});

	Course_Editor.temp_id = 1;
	$(document).ready(function () {
		new Course_Editor(Course_Settings);
	});
})(jQuery);