;(function ($) {
	var Course_Editor = function (args) {
		this.model = new Course_Editor.Model(args);
		this.view = new Course_Editor.View({model: this.model});
	}

	Course_Editor.View = Backbone.View.extend({
		$editor        : null,
		el             : 'body',
		events         : {
			'focus .item-name'   : '_selectItem',
			'focus .section-name': '_selectSection',
		},
		initialize     : function () {
			this.render();
		},
		render         : function () {
			this.$editor = this.template('course-editor');
			var $curriculum = this.$editor.find('#course-curriculum');
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
		_createSection : function (section) {
			var sectionArgs = section.toJSON(),
				$section = this.template('course-section', sectionArgs),
				$container = $section.find('.section-items');
			//section.$el = $section;
			section.$el = $section;
			section.items.forEach(function (item) {
				var $item = this._createItem(item, section);
				$container.append($item);
			}, this);

			this.addEmptyItem({section: sectionArgs}, section);

			$container.sortable({
				axis       : 'y',
				handle     : '.section-item-move',
				connectWith: '.section-items'
			});

			return $section;
		},
		_createItem    : function (item, section) {
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

			item.$el = $item;

			var $item = this.template('course-section-item', $.extend(itemArgs, {section: sectionArgs}));
			return $item;
		},
		_selectItem    : function (e) {
			var $el = $(e.target), that = this;
			this.$('.course-section-item').removeClass('active');
			var temp_id = parseInt($el.closest('.course-section-item').addClass('active').data('temp_id')),
				model = this.model.find({temp_id: temp_id});
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
						init.setup = function(ed) {
							ed.on('change', function(e) {
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
		_selectSection : function (e) {
			var $el = $(e.target);
			$el.closest('.course-section').addClass('active').siblings().removeClass('active');
		},
		addEmptySection: function (args, $curriculum) {
			// add empty section
			var section = new Course_Editor.Section(args);
			this.model.sections.add(section);

			var $section = this._createSection(section);
			if ($curriculum) {
				$curriculum.append($section);
			}
			return $section;
		},
		addEmptyItem   : function (args, section) {
			// add empty section
			var item = new Backbone.Model(args);
			section.items.add(item);
			var $item = this.template('course-section-item', $.extend(item.toJSON()));
			if (section.$el) {
				section.$el.find('.section-items').append($item);
			}
			return $item;
		},
		template       : function (id, args) {
			return $(wp.template(id)(args));
		}
	});

	Course_Editor.Model = Backbone.Model.extend({
		sections    : null,
		rawArgs     : null,
		initialize  : function (args) {
			this.rawArgs = args;
			this.initSections();
		},
		initSections: function () {
			this.sections = new Course_Editor.Sections();
			_.forEach(this.rawArgs.sections, function (section) {
				this.sections.add(new Course_Editor.Section(section))
			}, this);
		},
		findWhere   : function (atts) {
			var items = [];
			this.sections.forEach(function (section) {
				var find = section.items.findWhere(atts);
				if (find) {
					_.union(items, find);
				}
			});
			return items;
		},
		find        : function (atts) {
			var item = undefined;
			this.sections.forEach(function (section) {
				if (item) {
					return true;
				}
				item = section.items.find(atts);

			});
			return item;
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