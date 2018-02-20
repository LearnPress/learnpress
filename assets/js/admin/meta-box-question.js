/**
 * @author ThimPress
 * @package LearnPress/Javascript
 * @version 1.0
 */
;
if (typeof window.LP == 'undefined') {
	window.LP = window.LearnPress = {};
}

;(function ($) {
	var $doc = $(document);
	LP.Hook.addFilter('before_add_question_option', function ($el, args) {
		return true;
	}).addAction('question_option_added', function($el){
		//$el.find('input[type="text"]').focus();
	});

	LP.Question = {
		_getEmptyOption: function(question_id){
			var $options = $('#learn-press-list-options-' + question_id + ' tbody .lp-list-option-empty');
			return $options.length ? $options : false;
		},
		addOption  : function (question_id, args) {
			var $newOption = this._getEmptyOption(question_id);
			args = $.extend({autoFocus: true}, args || {});
			if( $newOption ){

			}else {
				var templateArgs = {
						question_id: question_id,
						text       : '',
						value      : LP.uniqueId()
					},
					tmpl = wp.template($('#learn-press-question-' + question_id).attr('data-type') + '-option'),
					$list = $('#learn-press-list-options-' + question_id + ' tbody');
				$newOption = $(tmpl(templateArgs));
				if (LP.Hook.applyFilters('before_add_question_option', $newOption, args) !== false) {
					$list.append($newOption);
					LP.Hook.doAction('question_option_added', $newOption, args);
				}
			}
			if($newOption && args.autoFocus){
				$newOption.find('.lp-answer-text').focus();
			}
		},
		removeOption: function(theOption){
			var $theOption = null;
			if($.type( theOption ) == 'integer' ){
				$theOption = $('lp-list-option-'+theOption);
			}else{
				$theOption = $(theOption);
			}
			if (LP.Hook.applyFilters('before_remove_question_option', true, $theOption) !== false) {
				$theOption.remove();
				LP.Hook.doAction('question_option_removed', $theOption);
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
					LP.Question._hideQuestion(args.id);
					LP.Hook.doAction('learn_press_add_quiz_question', $newQuestion, args);
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
				question = $('#learn-press-dropdown-questions .question a[data-id="' + question + '"]').parent();
			}
			$(question).removeClass('added');
		}
	};
	function updateHiddenQuestions(hidden){
		if( hidden == undefined ) {
			hidden = [];
			var len = $('.quiz-question-content').each(function () {
				if ($(this).is(':hidden')) {
					hidden.push($('.learn-press-question', this).attr('data-id'));
				}
			}).length;
			if( hidden.length == 0 ){
				$('.questions-toggle a[data-action="collapse"]')
					.show()
					.siblings('a[data-action="expand"]')
					.hide();
			}else if( hidden.length == len ){
				$('.questions-toggle a[data-action="collapse"]')
					.hide()
					.siblings('a[data-action="expand"]')
					.show();
			}
		}

		$.ajax({
			url    : LP_Settings.ajax,
			data   : {
				action: 'learnpress_update_quiz_question_state',
				quiz_id: $('#post_ID').val(),
				hidden: hidden
			},
			success: function(){

			}
		});
		return hidden;
	}

	LP.sortableQuestionAnswers = function ($questions, args) {
		args = $.extend({
			stop: function(){}
		}, args || {});
		$questions.find('.lp-list-options tbody').sortable({
			handle: '.lp-move-list-option',
			axis: 'y',
			start: function(e, ui){
				var $heads = ui.item.parent().closest('table').find('tr > th');
				ui.item.children().each(function(i){
					$(this).css({
						width: $heads.eq(i).outerWidth()
					});
				})
				var $this = $(this),
					cols = $this.find('tr:first').children().length;
				$this.find('.ui-sortable-placeholder td:gt(0)').remove();
				$this.find('.ui-sortable-placeholder td:eq(0)').attr('colspan', cols)
			},
			stop: function(e, ui){
				$.isFunction( args.stop ) && args.stop.apply( this, [e, ui] );
			}
		});
	}
	function _ready() {
		$('#learn-press-toggle-questions').on('click', function () {
			$(this).siblings('ul').toggle();
		});

		$doc.on('click', '#learn-press-dropdown-questions ul li a', function (e) {
			e.preventDefault();
			LP.Question.addQuestion({id: $(this).data('id')});
			$(this).closest('ul').hide();
		});

		$('#learn-press-button-add-question').on('click', function () {
			//LP.Question.addQuestion({name: $('#learn-press-question-name').val(), type: 'true_or_false'});
		});

		$doc.on('click', '.add-question-option-button', function (e, data) {
			var question_id = $(this).attr('data-id');
			LP.Question.addOption(question_id, data);
		}).on('click', '.lp-remove-list-option', function () {
			var $option = $(this).closest('tr');
			LP.MessageBox.quickConfirm($('i', this), {
				message: 'Are you sure you want to remove this option?',
				onOk: function (a) {
					LP.Question.removeOption($option);
				}
			});
		}).on('change', '.lp-dropdown-question-types', function(){
			var $select = $(this),
				$wrap = $select.closest('.learn-press-question'),
				questionId = $wrap.data('id'),
				from = $select.data('selected'),
				to = this.value,
				_do = function(){
					LP.MessageBox.blockUI();
					$.ajax({
						url     : LP_Settings.ajax,
						type    : 'post',
						dataType: 'html',
						data    : {
							action: 'learnpress_convert_question_type',
							question_id: questionId,
							from: from,
							to: to,
							data: $('#post').serialize()//$wrap.find('input, select, textarea').
						},
						success : function(response){
							response = LP.parseJSON(response);
							var $newOptions = $(response.html),
								$question = $('#learn-press-question-'+questionId),
								$icon = $question.closest('.quiz-question').find('.quiz-question-icon img');
							$question.replaceWith($newOptions);
							if($icon.length){
								$icon.replaceWith(response.icon)
							}
							LP.Hook.doAction('learn_press_convert_question_type', questionId, from, to, $newOptions);
							LP.MessageBox.hide();
						}
					});
				};

			LP.MessageBox.show('Are you sure you want to convert to new type?', {
				buttons: 'yesNo',
				events: {
					onYes: function () {
						_do();
					},
					onNo : function () {
						//revert
						$select.val(from);//find('option:selected').prop('selected', false).siblings('[value="' + from + '"]').prop('selected', true);
					}
				}
			});
			return;

		}).on('click', '.questions-toggle a', function(e){
			e.preventDefault();
			var action = $(this).attr('data-action');
			switch (action){
				case 'expand':
					var $items = $('.quiz-question'),
						len = $items.length, i = 0;
					$(this)
						.hide()
						.siblings('a[data-action="collapse"]')
						.show();
					$items
						.removeClass('is-hidden')
						.find('.quiz-question-content').slideDown(function(){
							if(++i == len){
								updateHiddenQuestions([]);
							}
						});
					$items.find('a[data-action="collapse"]').show();
					$items.find('a[data-action="expand"]').hide();
					break;
				case 'collapse':
					var $items = $('.quiz-question'),
						len = $items.length, i = 0,
						hidden = [];
					$(this)
						.hide()
						.siblings('a[data-action="expand"]')
						.show();
					$items
						.addClass('is-hidden')
						.find('.quiz-question-content').slideUp(function(){
							hidden.push($('.learn-press-question', this).attr('data-id'));
							if(++i == len){
								updateHiddenQuestions(hidden);
							}
						});
					$items.find('a[data-action="collapse"]').hide();
					$items.find('a[data-action="expand"]').show();
					break;
			}
		}).on('click', '.quiz-question-actions a', function(e){
			var $link = $(this),
				action = $link.attr('data-action');

			switch (action){
				case 'expand':
					$(this)
						.hide()
						.siblings('a[data-action="collapse"]')
						.show()
						.closest('.quiz-question')
						.removeClass('is-hidden')
						.find('.quiz-question-content').slideDown(function(){
							if( updateHiddenQuestions().length == 0 ){

							}
						});
					break;
				case 'collapse':
					$(this)
						.hide()
						.siblings('a[data-action="expand"]')
						.show()
						.closest('.quiz-question')
						.addClass('is-hidden')
						.find('.quiz-question-content').slideUp(function(){
							updateHiddenQuestions();
						});
					break;
				case 'duplicate':
					if ( jConfirm( learn_press_admin_localize.duplicate_question.message, learn_press_admin_localize.duplicate_question.title, function( confirm ){
                                            if ( confirm ) {
                                                var buttons = $('#popup_panel').find( 'button' );
                                                $.ajax({
                                                    url: LP_Settings.ajax,
                                                    type: 'POST',
                                                    dataType: 'html',
                                                    data: {
                                                        'question-id' : $link.attr( 'data-id' ),
                                                        'quiz-id'     : $link.attr( 'data-quiz' ),
                                                        'action'      : 'learnpress_duplicate_question',
                                                        '_nonce'      : learn_press_admin_localize.nonces.duplicate_question
                                                    },
                                                    beforeSend: function(){
                                                        buttons.attr( 'disabled', true );
                                                    }
                                                }).done( function( response ){
                                                    response = LP.parseJSON(response);
                                                    var $newQuestion = $(response.html);
                                                    $('#learn-press-list-questions').append($newQuestion);
                                                } );
                                            }
                                        } ) );
					break;
				case 'remove':
					LP.MessageBox.quickConfirm($link, {
						onOk: function(a){
							var $question = $(a);
							$.ajax({
								url: $link.attr('href'),
								/*data: {
								 action: 'learnpress_remove_quiz_question',
								 question_id: $question.attr('data-id'),
								 quiz_id: $('#post_ID').val()
								 },*/
								success: function(){
									$question.fadeOut(function(){$(this).remove()});
								}
							});
						},
						onCancel: function(a){

						},
						data: $(this).closest('.quiz-question')
					});
					break;
					LP.MessageBox.show( learn_press_admin_localize.remove_question, {
						buttons: 'yesNo',
						data: $(this).closest('.quiz-question'),
						events: {
							onYes: function (instance) {
								var $question = $(instance.data);
								$.ajax({
									url: $link.attr('href'),
									/*data: {
										action: 'learnpress_remove_quiz_question',
										question_id: $question.attr('data-id'),
										quiz_id: $('#post_ID').val()
									},*/
									success: function(){
										$question.fadeOut(function(){$(this).remove()});
									}
								});
							}
						}
					})
					break;
				//case 'edit':
				//LP.MessageBox.show('<iframe src="'+$(this).attr('href')+'" />');

			}
			if( action ){
				e.preventDefault();
			}
		}).on('keydown', '.no-submit', function(e){
			if(e.keyCode == 13) {
				e.preventDefault();
				LP.log('no submit form');
			}
		}).on('change keyup', '.lp-answer-text', function(e){
			var $input = $(this),
				$option = $input.closest('.lp-list-option'),
				value = $input.val()+'';
			if(e.keyCode != 13){
				switch (e.keyCode){
					case 38:
					case 40:
					case 8:
					case 46:
						var pressed = $input.data('key-'+ e.keyCode) || 1;
						LP.log('pressed:' + pressed)
						if(pressed > 1){
							if(e.keyCode == 38){
								( $prev = $input.findPrev('.key-nav') ) && $prev.focus();
								pressed = 0;
							}else if(e.keyCode == 40){
								( $next = $input.findNext('.key-nav') ) && $next.focus();
								pressed = 0;
							}else if(e.keyCode == 8 && value.length == 0){
								var $row = $input.closest('tr'),
									$prev = $row.prev();
								if( $prev.length ){
									$prev.find('.lp-answer-text').focus();
									$row.remove();
								}
							}else if(e.keyCode == 46 && value.length == 0){
								var $row = $input.closest('tr'),
									$next = $row.next();
								if( !$next.hasClass('lp-list-option-empty') ){
									$next.find('.lp-answer-text').focus();
									$row.remove();
								}
							}
							$input.data('key-'+ e.keyCode, 0 );
						}else {
							if( e.keyCode == 8 || e.keyCode == 46 ){
								if( value.length == 0 ){
									$input.data('key-' + e.keyCode, pressed + 1);
								}
							} else {
								$input.data('key-' + e.keyCode, pressed + 1);
							}
						}
						break;
				}
				if( value.length ){
					$option.removeClass('lp-list-option-empty');
				}else{
					$option.addClass('lp-list-option-empty');
					return;
				}
			}
			var $wrap = $(this).closest('.learn-press-question'),
				$button = $wrap.find('.add-question-option-button');
			$button.trigger('click', {autoFocus: e.keyCode == 13});
		});


	}

	$(document).ready(_ready);

})
(jQuery);