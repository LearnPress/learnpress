/**
 * @author ThimPress
 * @package LearnPress/Javascript
 * @version 1.0
 */
;
if (typeof window.LearnPress == 'undefined') {
	window.LearnPress = {};
}
;(function ($) {
	var $doc = $(document);
	LearnPress.Hook.addFilter('before_add_question_option', function($el, args){
		return true;
	}).addAction('question_option_added', function($el){
		$el.find('input[type="text"]').focus();
	})
	LearnPress.Question = {
		addOption  : function (question_id) {
			var args = {
					question_id: question_id,
					text       : 'New option',
					value      : LearnPress.uniqueId()
				},
				tmpl = wp.template( $('#learn-press-question-'+question_id).attr('data-type')+'-option'),
				$newOption = $(tmpl(args)),
				$list = $('#learn-press-list-options-' + question_id + ' tbody');
			if( LearnPress.Hook.applyFilters('before_add_question_option', $newOption, args ) !== false ) {
				$list.append($newOption);
				LearnPress.Hook.doAction('question_option_added', $newOption, args );
			}

		},
		removeOption: function(theOption){
			var $theOption = null;
			if($.type( theOption ) == 'integer' ){
				$theOption = $('lp-list-option-'+theOption);
			}else{
				$theOption = $(theOption);
			}
			if( LearnPress.Hook.applyFilters('before_remove_question_option', true, $theOption) !== false ) {
				$theOption.remove();
				LearnPress.Hook.doAction('question_option_removed', $theOption);
			}
		},
		addQuestion: function (args) {
			args = $.extend({
				id  : 0,
				type: null,
				name: null
			}, args);
			if (!args.id && !args.type) {
				alert('ERROR');
				return;
			}
			var post_data = $.extend({
				action: 'learnpress_add_question'
			}, args);

			post_data = LearnPress.Hook.applyFilters( 'LearnPress.add_question_post_data', post_data );

			$.ajax({
				url     : LearnPress_Settings.ajax,
				dataType: 'html',
				type    : 'post',
				data    : post_data,
				success : function (response) {
					response = LearnPress.parseJSON(response);
					var $newQuestion = $(response.html);
					$('#learn-press-list-questions').append($newQuestion);
					LearnPress.Question._hideQuestion( args.id )
					LearnPress.Hook.doAction( 'learn_press_add_quiz_question', $newQuestion, args);
				}
			});
		},
		_hideQuestion: function(question){
			if($.type( question ) == 'number' ) {
				question = $('#learn-press-dropdown-questions .question a[data-id="' + question + '"]').parent()
			}
			$(question).addClass('added');
		},
		_showQuestion: function(question){
			if($.type( question ) == 'number' ) {
				question = $('#learn-press-dropdown-questions .question a[data-id="' + question + '"]').parent()
			}
			$(question).removeClass('added');
		}
	};
	function _ready() {
		$('#learn-press-toggle-questions').on('click', function () {
			$(this).siblings('ul').toggle();
		});

		$doc.on('click', '#learn-press-dropdown-questions ul li a', function (e) {
			e.preventDefault();
			LearnPress.Question.addQuestion({id: $(this).data('id')});
			$(this).closest('ul').hide();
		});

		$('#learn-press-button-add-question').on('click', function () {
			LearnPress.Question.addQuestion({name: $('#learn-press-question-name').val(), type: 'true_or_false'});
		});

		$('.lp-list-options tbody').sortable({
			handle: '.lp-move-list-option',
			axis: 'y',
			start: function(e, ui){
				var $heads = ui.item.parent().closest('table').find('tr > th');
				ui.item.children().each(function(i){
					$(this).css({
						width: $heads.eq(i).width()
					});
				})
				var $this = $(this),
					cols = $this.find('tr:first').children().length;
				$this.find('.ui-sortable-placeholder td:gt(0)').remove();
				$this.find('.ui-sortable-placeholder td:eq(0)').attr('colspan', cols)
			},
			stop: function(){

			}
		});

		$doc.on('click', '.add-question-option-button', function () {
			var question_id = $(this).attr('data-id');
			LearnPress.Question.addOption(question_id);
		}).on('click', '.lp-remove-list-option', function () {
			var $option = $(this).closest('tr');
			LearnPress.Question.removeOption( $option );
		}).on('change', '.lp-dropdown-question-types', function(){
			var questionId = $(this).closest('.learn-press-question').data('id'),
				from = $(this).data('selected'),
				to = this.value;
			LearnPress.MessageBox.blockUI();
			$.ajax({
				url: LearnPress_Settings.ajax,
				type: 'post',
				dataType: 'html',
				data: {
					action: 'learnpress_convert_question_type',
					question_id: questionId,
					from: from,
					to: to
				},
				success: function(response){
					response = LearnPress.parseJSON(response);
					var $newOptions = $(response);
					$('#learn-press-question-'+questionId).replaceWith($newOptions);
					LearnPress.Hook.doAction('learn_press_convert_question_type', questionId, from, to, $newOptions );
					LearnPress.MessageBox.hide();
				}
			});
		}).on('click', '.questions-toggle a', function(e){
			e.preventDefault();
			var action = $(this).attr('data-action');
			switch (action){
				case 'expand':
					$('.learn-press-question').slideDown();
					break;
				case 'collapse':
					$('.learn-press-question').slideUp();
					break;
			}
		}).on('click', '.quiz-question-actions a', function(e){
			var action = $(this).attr('data-action');
			switch (action){
				case 'expand':
					$(this).closest('.quiz-question').find('.learn-press-question').slideDown();
					break;
				case 'collapse':
					$(this).closest('.quiz-question').find('.learn-press-question').slideUp();
					break;
				case 'remove':
					LearnPress.MessageBox.show( 'Do you want to remove this question from quiz?', '', 'yesNo', {
						data: $(this).closest('.quiz-question'),
						onYes: function(data){
							var $question = $(data);
							LearnPress.Question._showQuestion( parseInt( $question.find('.learn-press-question').attr('data-id') ) );
							$question.remove();
						}
					})
					break;
				case 'edit':
					LearnPress.MessageBox.show('<iframe src="'+$(this).attr('href')+'" />');

			}
			if( action ){
				e.preventDefault();
			}
		});


	}

	$(document).ready(_ready);

})
(jQuery);