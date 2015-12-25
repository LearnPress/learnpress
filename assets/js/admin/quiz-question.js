/**
 * Created by Tu on 30/03/2015.
 */
;
(function ($) {

	$(document).ready(function () {
		if (typeof Backbone == 'undefined') return;
		var LP_Quiz_Question_Model = window.LP_Quiz_Question_Model = Backbone.Model.extend({})

		var LP_Quiz_Question_View = window.LP_Quiz_Question_View = Backbone.View.extend({
			el        : 'body',
			events    : {
				'click #learn-press-button-add-question': 'showListQuestions',
				'keyup #lp-modal-quiz-questions input[name="lp-item-name"]': 'searchItem',
				'click #lp-modal-quiz-questions input[type="checkbox"]': 'toggleAddItemButtonState',
				'change #lp-modal-quiz-questions input[type="checkbox"]': 'toggleAddItemButtonState',
				'click #lp-modal-quiz-questions .lp-add-item': 'addItemToQuiz',
				'keyup input[name="lp-new-question-name"]': 'toggleAddButtonState',
				'keydown .no-submit': 'preventSubmitForm',
				'click .lp-button-add-question': 'addNewItem'
			},
			initialize: function () {
				LearnPress.Hook.addAction( 'learn_press_message_box_before_resize', this.resetModalSearch)
				LearnPress.Hook.addAction( 'learn_press_message_box_resize', this.updateModalSearch)
			},
			updateModalSearch: function(height, $app){
				$('.lp-modal-search ul.lp-list-items').css('height', height - 120).css('overflow', 'auto');
			},
			resetModalSearch: function($app){
				$('.lp-modal-search ul.lp-list-items').css('height', '').css('overflow', '')
			},
			toggleAddButtonState: function(e){
				LearnPress.log(e.target.value)
				if((e.target.value+'').length == 0){
					$('.lp-button-add-question').addClass('disabled')
				}else{
					$('.lp-button-add-question').removeClass('disabled')
				}
				if(e.keyCode == 13){
					$(e.target).siblings('.lp-button-add-question').trigger('click')
				}
			},
			preventSubmitForm: function(e){
				if(e.keyCode == 13){
					return false;
				}
			},
			showListQuestions: function(){
				var $form = $('#lp-modal-quiz-questions'),
					$button = $form.find('.lp-add-item');
				if ($form.length == 0) {
					$form = $(wp.template('lp-modal-quiz-questions')());
				}

				$form.show().find('li').filter(function(){
					var id = parseInt($(this).data('id'));
					/*if( $.inArray( $(this).data('id'), that.removeItemIds ) >= 0 ){
						$(this).removeClass('selected hide-if-js');
					};*/
				});

				LearnPress.MessageBox.show($form);
				$form.find('[name="lp-item-name"]').focus();
				$button.html($button.attr('data-text'))
			},
			searchItem: function(e){
				var $input = $(e.target),
					$form = $input.closest('.lp-modal-search'),
					text = $input.val().replace(/\\q\?[\s]*/ig, ''),
					$lis = $form.find('.lp-list-items li:not(.lp-search-no-results):not(.selected)').addClass('hide-if-js'),
					reg = new RegExp($.grep(text.split(/[\s]+/),function(a){return a.length}).join('|'), "ig"),
					found = 0;
				LearnPress.log(text)
				found = $lis.filter(function () {
					var $el = $(this),
						itemText = $el.data('text')+'',
						ret = itemText.search(reg) >= 0;
					if(ret){
						$el.find('.lp-item-text').html( itemText.replace(reg, "<i class=\"lp-highlight-color\">\$&</i>" ) );
					}else{
						$el.find('.lp-item-text').html( itemText );
					}
					return ret;
				}).removeClass('hide-if-js').length;
				if( ! found ) {
					$form.find('.lp-search-no-results').removeClass('hide-if-js');
				}else{
					$form.find('.lp-search-no-results').addClass('hide-if-js');
				}
			},
			toggleAddItemButtonState: function(e){
				var $form = $(e.target).closest('.lp-modal-search'),
					selected = $form.find('.lp-list-items li:visible input:checked'),
					$button = $form.find('.lp-add-item');
				if( selected.length ){
					$button.removeAttr('disabled').html($button.attr('data-text')+' (+'+selected.length+')');
				}else{
					$button.attr('disabled', true).html($button.attr('data-text'));
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
				var that = this,
					post_data = $.extend({
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
						that.$('#lp-modal-quiz-questions li[data-id="'+response.id+'"]').addClass('selected hide-if-js');
						//LearnPress.Question._hideQuestion( args.id )
						LearnPress.Hook.doAction( 'learn_press_add_quiz_question', $newQuestion, args);
					}
				});
			},
			addNewItem: function(e){
				e.preventDefault();
				var $target = $(e.target),
					$form = $target.closest('.lp-modal-search'),
					$input = this.$('input[name="lp-new-question-name"]'),
					type = null;
				if($target.is('a')){
					type = $target.attr('data-type');
					$target = $target.closest('.lp-button-add-question');
				}else{
					if(!$target.is('.lp-button-add-question')){
						$target = $target.closest('.lp-button-add-question');
					}
					type = $target.find('ul > li > a:first').attr('data-type');
				}
				if($target.hasClass('disabled')){
					return;
				}
				if( ($input.val()+'').length == 0 ){
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
			addItemToQuiz: function(e){
				var that = this,
					$form = $(e.target).closest('.lp-modal-search'),
					selected = $form.find('li:visible input:checked'),
					$section = $form.data('section');
				selected.each(function(){
					var $li = $(this).closest('li').addClass('selected'),
						args = $li.dataToJSON();
						/*$item = that.createItem( args, $section );
					$item.removeClass('lp-item-empty');*/
					that.addQuestion({id: $(this).val()});
				});
				$form.hide().appendTo($(document.body));
				LearnPress.MessageBox.hide();
			},
			createItem: function(args, $section){
				var tmpl = wp.template('quiz-question'),
					$item = $(tmpl(args));
				if( $section ) {
					var $last = $section.find('.curriculum-section-items li:last');
					$item.insertBefore($last);
				}
				return $item;
			}
		});

		new LP_Quiz_Question_View(new LP_Quiz_Question_Model());
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