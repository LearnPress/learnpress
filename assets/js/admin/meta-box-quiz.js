/**
 * Created by Tu on 30/03/2015.
 */
;
(function ($) {

	$(document).ready(function () {
		if (typeof Backbone == 'undefined') return;
		var LP_Quiz_Question_Model = window.LP_Quiz_Question_Model = Backbone.Model.extend({})

		var LP_Quiz_Question_View = window.LP_Quiz_Question_View = Backbone.View.extend({
			el                      : 'body',
			events                  : {
				'click #learn-press-button-add-question'                   : 'showListQuestions',
				'keyup #lp-modal-quiz-questions input[name="lp-item-name"]': 'searchItem',
				'click #lp-modal-quiz-questions input[type="checkbox"]'    : 'toggleAddItemButtonState',
				'change #lp-modal-quiz-questions input[type="checkbox"]'   : 'toggleAddItemButtonState',
				'click #lp-modal-quiz-questions .lp-add-item'              : 'addItemToQuiz',
				'keyup input[name="lp-new-question-name"]'                 : 'toggleAddButtonState',
				'keydown .no-submit'                                       : 'preventSubmitForm',
				'click .lp-button-add-question'                            : 'addNewItem'
			},
			initialize              : function () {
				$('#learn-press-list-questions').sortable({
					handle: '.quiz-question-actions .move',
					axis  : 'y'
				});
				_.bindAll(this, 'addItemsToSection', 'getSelectedItems');
				LP.Hook.addAction('learn_press_message_box_before_resize', this.resetModalSearch);
				LP.Hook.addAction('learn_press_message_box_resize', this.updateModalSearch);
				LP.Hook.addFilter('learn_press_modal_search_items_exclude', this.getSelectedItems);

				$(document).on('learn_press_modal_search_items_response', this.addItemsToSection);
			},
			updateModalSearch       : function (height, $app) {
				$('.lp-modal-search ul.lp-list-items').css('height', height - 120).css('overflow', 'auto');
			},
			resetModalSearch        : function ($app) {
				$('.lp-modal-search ul.lp-list-items').css('height', '').css('overflow', '')
			},
			toggleAddButtonState    : function (e) {
				if ((e.target.value + '').length == 0) {
					$('.lp-button-add-question').addClass('disabled')
				} else {
					$('.lp-button-add-question').removeClass('disabled')
				}
				if (e.keyCode == 13) {
					$(e.target).siblings('.lp-button-add-question').trigger('click')
				}
			},
			preventSubmitForm       : function (e) {
				if (e.keyCode == 13) {
					return false;
				}
			},
			showListQuestions       : function () {
				var $form = LP.ModalSearchItems({
					template  : 'tmpl-learn-press-search-items',
					type      : 'lp_question',
					context   : 'quiz-items',
					context_id: $('#post_ID').val(),
					exclude   : this.getSelectedItems()
				});
				LP.MessageBox.show($form.$el);
				$form.$el.find('header input').focus();

				return;
				var $form = $('#lp-modal-quiz-questions'),
					$button = $form.find('.lp-add-item');
				if ($form.length == 0) {
					$form = $(wp.template('lp-modal-quiz-questions')());
				}

				LP.MessageBox.show($form);
				$form.find('[name="lp-item-name"]').focus().trigger('keyup');
				$button.html($button.attr('data-text'));
			},
			searchItem              : function (e) {
				var that = this,
					$input = $(e.target);
				if ($input.val() == $input.data('search-term')) {
					return;
				}
				$input.data('search-term', $input.val());
				var $form = $input.closest('.lp-modal-search'),
					text = $input.val().replace(/\\q\?[\s]*/ig, ''),
					$button = $form.find('.lp-add-item'),
					timer = $input.data('timer'),
					_search = function () {
						$.ajax({
							url     : LP_Settings.ajax,
							data    : {
								action : 'learnpress_search_questions',
								quiz_id: $('#post_ID').val(),
								term   : text,
								exclude: that.getAddedQuestions()
							},
							dataType: 'text',
							success : function (response) {
								response = LP.parseJSON(response);
								LP.log(response);
								$form.find('.lp-list-items').html(response.html).removeClass('lp-ajaxload');
								$(window).trigger('resize');
							}
						});
					};
				$form.find('.lp-list-items').html('').addClass('lp-ajaxload');
				$button.html($button.attr('data-text')).prop('disabled', true);
				timer && clearTimeout(timer);
				timer = setTimeout(_search, 300);
				$input.data('timer', timer);
				$(window).trigger('resize');

			},
			toggleAddItemButtonState: function (e) {
				var $form = $(e.target).closest('.lp-modal-search'),
					selected = $form.find('.lp-list-items li:visible input:checked'),
					$button = $form.find('.lp-add-item');
				if (selected.length) {
					$button.removeAttr('disabled').html($button.attr('data-text') + ' (+' + selected.length + ')');
				} else {
					$button.attr('disabled', true).html($button.attr('data-text'));
				}
			},
			getSelectedItems        : function (exclude) {
				return this.$('.learn-press-question[data-id]').map(function () {
					return ($(this).attr('data-id'))
				}).filter(function (i, c) {
					return c > 0
				}).get();
			},
			addItemsToSection       : function (e, $view, $items) {
				var that = this,
					selected = $items;
				selected.each(function () {
					var $li = $(this);//.closest('li').addClass('selected'),
					args = $li.dataToJSON();
					that.addQuestion(args);
					$li.remove();
				});
			},
			addQuestion             : function (args) {
				args = $.extend({
					id  : 0,
					type: null,
					name: null
				}, args);
				if (!args.id && !args.type) {
					alert('ERROR');
					return;
				}
				var that = this,
					post_data = $.extend({
						action : 'learnpress_add_quiz_question',
						quiz_id: $('#post_ID').val()
					}, args);

				post_data = LP.Hook.applyFilters('LP.add_question_post_data', post_data);

				$.ajax({
					url     : LP_Settings.ajax,
					dataType: 'html',
					type    : 'post',
					data    : post_data,
					success : function (response) {
						response = LP.parseJSON(response);
						var $newQuestion = $(response.html);
						$('#learn-press-list-questions').append($newQuestion);
						that.$('#lp-modal-quiz-questions li[data-id="' + response.id + '"]').addClass('selected hide-if-js');
						//LP.Question._hideQuestion( args.id )
						LP.Hook.doAction('learn_press_add_quiz_question', $newQuestion, args);
					}
				});
			},
			addNewItem              : function (e) {
				e.preventDefault();
				var $target = $(e.target),
					$form = $target.closest('.lp-modal-search'),
					$input = this.$('input[name="lp-new-question-name"]'),
					type = null;
				if ($target.is('a')) {
					type = $target.attr('data-type');
					$target = $target.closest('.lp-button-add-question');
				} else {
					if (!$target.is('.lp-button-add-question')) {
						$target = $target.closest('.lp-button-add-question');
					}
					type = $target.find('ul > li > a:first').attr('data-type');
				}
				if ($target.hasClass('disabled')) {
					return;
				}
				if (($input.val() + '').length == 0) {
					alert('Please enter question name');
					$input.focus();
					return;
				}
				this.addQuestion({
					type: type,
					name: $input.val()
				});
				$input.focus().val('').trigger('keyup')
			},
			addItemToQuiz           : function (e) {
				var that = this,
					$form = $(e.target).closest('.lp-modal-search'),
					selected = $form.find('li:visible input:checked'),
					$section = $form.data('section');
				selected.each(function () {
					var $li = $(this).closest('li').addClass('selected'),
						args = $li.dataToJSON();
					/*$item = that.createItem( args, $section );
					 $item.removeClass('lp-item-empty');*/
					that.addQuestion({id: $(this).val()});
				});
				$form.remove();
				LP.MessageBox.hide();
			},
			createItem              : function (args, $section) {
				var tmpl = wp.template('quiz-question'),
					$item = $(tmpl(args));
				if ($section) {
					var $last = $section.find('.curriculum-section-items li:last');
					$item.insertBefore($last);
				}
				return $item;
			},
			getAddedQuestions       : function () {
				var ids =
					this.$('.learn-press-question')
						.map(function () {
							return parseInt($(this).attr('data-id'))
						}).get();
				return ids;
			}
		});

		new LP_Quiz_Question_View(new LP_Quiz_Question_Model());

		$('input[name="_lp_passing_grade_type"]').change(function () {
			var t = $('input[name="_lp_passing_grade_type"]:checked').val(),
				$el = $('label[for="_lp_passing_grade"]');
			switch (t) {
				case 'percentage':
					$el.closest('.rwmb-field').show();
					t = '%';
					break;
				case 'point':
					$el.closest('.rwmb-field').show();
					break;
				case 'no':
				case '':
					$el.closest('.rwmb-field').hide();
			}
			$el.find('span').html(t);
		}).filter(':checked').trigger('change');
	});

	return
	var $doc = $(document),
		$body = $(document.body);

	function addNewQuestion() {
		var type = $('#lpr-quiz-question-type').val();
		if (!type) {
			// warning
			return;
		}
		var data = {
			action : 'lpr_quiz_question_add',
			quiz_id: lpr_quiz_id,
			type   : type
		};
		$.post(ajaxurl, data, function (res) {
			var $question = $(res)
			$('#lpr-quiz-questions').append($question);
			lprHook.doAction('lpr_admin_quiz_question_html', $question, type);

			$('#lpr-quiz-question-type').val('')
		}, 'text');
	}

	function _ready() {
		$('#lpr-quiz-question-add').click(addNewQuestion);
	}

	$doc.ready(_ready);

})(jQuery)