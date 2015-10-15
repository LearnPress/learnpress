;(function($){
	$(document).ready(function(){
		var LP_Curriculum_Model = window.LP_Curriculum_Model = Backbone.Model.extend({
			defaults           : {
			},
			data               : null,
			view               : false,
			urlRoot            : '',
			initialize         : function () {
			}
		});

		var LP_Curriculum_View = window.LP_Curriculum_View = Backbone.View.extend({
			model          : {},
			events         : {
				'keyup' : 'processKeyEvents',
				'keydown': 'inputKeyDownEvent',
				'keypress': 'inputKeyPressEvent',
				'click .lp-section-item .lp-remove': 'removeItem',
				'click .lp-toggle': 'toggleSection',
				'click .lp-course-curriculum-toggle a': 'toggleSections'
			},
			removeSectionIds : [],
			removeItemIds: [],
			el             : '#lp-course-curriculum',
			initialize: function( model ){
				this.model = model;
				this.model.view = this;
				this.listenTo(this.model, 'change', this.render);
				this.render();
				_.bindAll(this, 'render');
				this.initPage();
			},
			initPage: function(){
				var that = this;
				this.$form = $('#post');
				this.$form.on( 'submit', $.proxy( function(){ return this.onSave()}, this ) );
				$('input[name="_lpr_course_final"]').bind('click change', function(){
					if( $(this).val() == 'yes' ){
						$(this).closest('.rwmb-field').next().show();
					}else{
						$(this).closest('.rwmb-field').next().hide();
					}
				}).filter(":checked").trigger('change');

				$(document).on('mouseover', '.lp-modal-search li', function(){
					$(this).addClass('highlighting').siblings().removeClass('highlighting');
				}).on('click', '.lp-modal-search li', function(e){
					e.keyCode = 13;
					e.target = $(this).closest('.lp-section-item').find('.lp-item-name').get(0)
					that.searchQuizFormKeyEvents(e);
				});
				this.$el
					.on('focus', '.lp-item-name', function(){
						that.$('.lp-section-item').removeClass('hover');
						$(this).parent().addClass('hover')
					})
					.on('blur', '.lp-item-name', function(){
						var $e = $(this);
						setTimeout( function(){
							var $item = $e.closest('.lp-section-item');
							if(that.isShowing != 'searchQuizForm') {
								$item.removeClass('hover');
							}
							if( ($e.val() + '').length == 0 ){
								if( $item.hasClass('lp-item-new') ) {
									$item.remove();
								}else{
									$e.val( $item.attr('data-text' ));
								}
							}
						}, 500);
					});
				this.$('.lp-curriculum-sections').sortable({
					axis: 'y',
					items: 'li:not(.lp-section-empty)',
					handle: '.lp-section-icon',
					start: function(e, ui){
						$('.lp-curriculum-section-content').css('display', 'none');
						$(ui.item).addClass('lp-sorting');
					},
					stop: function(e, ui){
						$('.lp-curriculum-section-content').css('display', '');
						$(ui.item).removeClass('lp-sorting');
					}
				});
				this.$('.lp-curriculum-sections .lp-section-items').sortable({
					axis: 'y',
					items: 'li:not(.lp-item-empty)',
					handle: '.lp-sort-item',
					connectWith: '.lp-section-items'
				});
				if( this.$('.lp-curriculum-section-content:visible').length ){

				}
			},
			toggleSection: function(e){
				var $button = $(e.target),
					$section = $button.closest('.lp-curriculum-section');
				$section.find('.lp-curriculum-section-content').stop().slideToggle(function(){
					if( $(this).is(':visible') ){
						$(this).parent().addClass('open')
					}else{
						$(this).parent().removeClass('open')
					}
				});
			},
			toggleSections: function(e){
				e.preventDefault();
				var $target = $(e.target);
				if($target.attr('data-action') == 'expand' ){
					this.$('.lp-curriculum-section-content').slideDown();
				}else{
					this.$('.lp-curriculum-section-content').slideUp();
				}
			},
			onSave: function( evt ){
				var $title = $('#title'),
					$curriculum = $('.lp-curriculum-section:not(.lp-section-empty)'),
					is_error = false;
				if (0 == $title.val().length) {
					alert( lp_course_params.notice_empty_title );
					$title.focus();
					is_error = true;
				} else if (0 == $curriculum.length) {
					alert( lp_course_params.notice_empty_section );
					$('.lp-curriculum-section .lp-section-name').focus();
					is_error = true;
				} else {
					/*$curriculum.each(function () {
						var $section = $('.lpr-section-name', this);
						if (0 == $section.val().length) {
							alert( lp_course_params.notice_empty_section_name );
							$section.focus();
							is_error = true;
							return false;
						}
					});*/
				}
				if( $( 'input[name="_lpr_course_payment"]:checked').val() == 'not_free' && $('input[name="_lpr_course_price"]').val() <= 0 ){
					alert( lp_course_params.notice_empty_price );
					is_error = true;
					$('input[name="_lpr_course_price"]').focus();
				}
				if (true == is_error) {
					evt.preventDefault();
					return false;
				}

				this._prepareSections();
			},
			_prepareSections: function(){
				var $sections = this.$form.find('.lp-curriculum-section:not(.lp-section-empty)');
				$sections.each(function( i, n ){
					var $section = $(this),
						$items = $section.find('.lp-section-item:not(.lp-item-empty)'),
						$inputs = $('input[name*="__SECTION__"]', $section);
					$inputs.each( function(){
						var $input = $(this),
							name = $input.attr('name');
						name = name.replace(/__SECTION__/, i);
						$input.attr('name', name);
					});
					$items.each(function(j, l){
						$(this).find('input[name*="__ITEM__"]').each( function(){
							var $input = $(this),
								name = $input.attr('name');
							name = name.replace(/__ITEM__/, j);
							$input.attr('name', name);
						});
					});
				});
				if(this.removeItemIds){
					this.$form.append( '<input type="hidden" name="_lp_remove_item_ids" value="' + this.removeItemIds.join(',') + '" />');
				}
			},
			render: function(){

			},
			inputKeyPressEvent: function(e){
				if(this.isShowing && e.keyCode==13){
					return false;
				}
			},
			inputKeyDownEvent: function(e){
				var $input = $(e.target);
				$input.attr('lastCode', e.keyCode);
				$input.attr('caret', $input.caret());
			},
			processKeyEvents: function(e){
				var $target = $(e.target),
					$section = $target.closest('.lp-curriculum-section'),
					field = $target.attr('data-field'),
					lastCode = $target.data('lastCode'),
					keyCode = e.keyCode,
					text = $target.val(),
					caretPos = $target.caret(),
					caretLen = text.length,
					that = this;
				if( field == 'item-name' ){
					var $item = $target.closest('.lp-section-item');
					if(text.match(/\\q\?/i) || this.isShowing == 'searchQuizForm') {
						this.searchQuizForm($item);
					}
					if(text.match(/\\l\?/i)){
						this.searchLessonForm($item);
					}
					if(e.altKey) {
						if (keyCode == 81) {
							$target.closest('.lp-section-item').find('.handle.dashicons').removeClass('dashicons-media-document').addClass('dashicons-format-status');
							$target.siblings('.lp-item-type').attr('value', 'lp_quiz');
						} else if (keyCode == 76) {
							$target.closest('.lp-section-item').find('.handle.dashicons').removeClass('dashicons-format-status').addClass('dashicons-media-document');
							$target.siblings('.lp-item-type').attr('value', 'lp_lesson');
						}
					}
				}
				var xxx = true;
				if( this.isShowing ) {
					xxx = this[this.isShowing + 'KeyEvents'] && this[this.isShowing + 'KeyEvents'].call(this, e);
				}
				if( xxx === false ) return;
				//[33 = page up, 34 = page down, 35 = end, 36 = home, 37 = left, 38 = up, 39 = right, 40 = down, 8 = backspace, enter = 13, 46 = del]
				switch (keyCode){
					case 8: // backspace
					case 46:
						if( text.length == 0 && $target.data('keyRepeat') == 2 ){
							if( field == 'section-name' ){

							}else if( field == 'item-name' ){
								var $item = $target.closest('.lp-section-item').css('visibility', 'hidden');
								var $inputs = this.$('.lp-item-name:visible'),
									pos = $inputs.index($target);
								if( $inputs.length == 1 ){
									$inputs.addClass('lp-item-empty');
									break;
								}
								if( keyCode == 8 ) {
									pos > 0 ? $inputs.eq(pos - 1).focus().caret($inputs.eq(pos - 1).val().length) : '';
								}else{
									pos < $inputs.length - 1 ? $inputs.eq(pos+1).focus().caret(0) : '';
								}
								$item.remove();
							}
						}
						break;
					case 13: // enter

						break;
					case 33: // page up
					case 34: // page down
					case 35: // end
					case 36: // home
					case 37: // left
					case 39: // right
						break;
					case 38: // up
					case 40: // down
						//if( this.isShowing ){
							this[this.isShowing+'KeyEvents'] && this[this.isShowing+'KeyEvents'].call(this, e);
						//}else {
							var isDown = keyCode == 40;
							var $inputs = this.$('.lp-section-name:visible, .lp-item-name:visible'),
								pos = $inputs.index($target);
							if (isDown) {
								pos == $inputs.length - 1 ? $inputs.eq(0).focus() : $inputs.eq(pos + 1).focus();
							} else {
								pos == 0 ? $inputs.eq($inputs.length - 1).focus() : $inputs.eq(pos - 1).focus();
							}
						//}
						break;
					default:
						if( $target.val().length > 0 ){
							if( field == 'section-name' ){
								$section.removeClass('lp-section-empty');
								that.appendSection($section);
							}else if( field == 'item-name' ){
								var $item = $target.closest('.lp-section-item').removeClass('lp-item-empty');
								that.appendSectionItem($item);
							}
						}
				}
				if( lastCode == keyCode && text.length == 0 ){
					var keyRepeat = $target.data('keyRepeat');
					if( ! keyRepeat ){ keyCode = 1}
					$target.data('keyRepeat', keyRepeat+1);
				}else{
					$target.data('keyRepeat', 1);
				}

				$target.data('lastCode', keyCode);

			},
			appendSection: function($section){
				var that = this,
					$sections = this.$('.lp-curriculum-section'),
					$last = $sections.last();
				if(!$last.hasClass('lp-section-empty')){
					this._createSection().insertAfter($last);
				}
			},
			appendSectionItem: function($item, $section){
				if( ! $section ) $section = $item.closest('.lp-curriculum-section');
				var that = this,
					$items = $section.find('.lp-section-item'),
					$last = $items.last();
				if(!$last.hasClass('lp-item-empty')){
					this._createItem().insertAfter($last);
				}
			},
			searchQuizForm: function($item){
				if( this.isShowing == 'searchQuizForm' && this.$modalQuizzes ){
					var $input = $item.find('.lp-item-name'),
						text = $input.val().replace(/\\q\?[\s]*/ig, ''),
						$lis = this.$modalQuizzes.find('li:not(.lp-search-no-results):not(.selected)').addClass('hide-if-js'),
						reg = new RegExp($.grep(text.split(/[\s]+/),function(a){return a.length}).join('|'), "ig"),
						found = 0;
					if( text.length ) {
						found = $lis.filter(function () {
							var $el = $(this),
								itemText = $el.data('text'),
								ret = itemText.search(reg) >= 0;
							if(ret){
								$el.html( itemText.replace(reg, "<i class=\"lp-highlight-color\">\$&</i>" ) );
							}else{
								$el.html( itemText );
							}
							return ret;
						}).removeClass('hide-if-js').length;
					}else{
						found = $lis.removeClass('hide-if-js').length;
					}
					if( ! found ) {
						this.$modalQuizzes.find('.lp-search-no-results').removeClass('hide-if-js');
					}else{
						this.$modalQuizzes.find('.lp-search-no-results').addClass('hide-if-js');
					}
					return;
				}
				if(!this.$modalQuizzes){
					this.$modalQuizzes = $(wp.template('lp-modal-search-quiz')({}))
				}
				var $input = $item.find('.lp-item-name'),
					position = $input.offset();

				this.$modalQuizzes.insertAfter($input).css({
					position: 'absolute',
					//top: position.top + $input.outerHeight(),
					left: $input.position().left,
					width: $input.outerWidth() - 2
				}).show();
				if( this.$modalQuizzes.find('li:not(.lp-search-no-results):not(.selected)').length == 0 ){
					this.$modalQuizzes.find('li.lp-search-no-results').show();
				}
				this.isShowing = 'searchQuizForm';
			},
			searchQuizFormKeyEvents: function(e){
				var $items = this.$modalQuizzes.find('li:visible:not(.lp-search-no-results)'),
					$activeItem = $items.filter('.highlighting'),
					$next = false;
				switch(e.keyCode){
					case 38: // up
						if($activeItem.length){
							$next = $activeItem.prev();
						}
						if(!$next.length) {
							$next = $items.last();
						}
						//.removeClass('highlighting');
						$next.addClass('highlighting').siblings().removeClass('highlighting');
						return false;
						break;
					case 40: // down
						if($activeItem.length){
							$next = $activeItem.next();
						}
						if(!$next.length) {
							$next = $items.first();
						}
						//$activeItem.removeClass('highlighting');
						$next.addClass('highlighting').siblings().removeClass('highlighting');
						return false;
						break;
					case 13:
					case 27:
						this.isShowing = '';
						if(e.keyCode == 13) {
							var $input = $(e.target),
								$item = $input.closest('.lp-section-item');
							$input.val($activeItem.data('text'));
							var id = $activeItem.attr('data-id'),
								type = $activeItem.attr('data-type');
							$item.removeClass('.lp-item-lp_lesson lp-item-empty lp-item-new').addClass('lp-item-'+type).attr({
								'data-type': type,
								'data-id': id
							})
						}else{
							$(e.target).val($(e.target).val().replace(/\\q\?[\s]*/ig, ''));
						}
						this.$modalQuizzes.hide();
						e.preventDefault();
						return false;
						break;
				}

			},
			searchLessonForm: function(){
				alert('search lesson')
			},
			_getNextSection: function($section, loop){
				var $nextSection = $section.next();
				if( $nextSection.length == 0 && ((typeof loop == 'undefined') || (loop == true))){
					$nextSection = $section.parent().children().first();
				}
				return $nextSection;
			},
			_getPrevSection: function($section, loop){
				var $prevSection = $section.prev();
				if( $prevSection.length == 0 && ((typeof loop == 'undefined') || (loop == true))){
					$prevSection = $section.parent().children().last();
				}
				return $prevSection;
			},
			_createSection: function(){
				var sectionTemplate = wp.template('curriculum-section');
				return $(sectionTemplate({}));
			},
			_createItem: function(){
				var itemTemplate = wp.template('section-item');
				return $(itemTemplate({}));
			},
			removeItem: function(e){
				e.preventDefault();
				if( ! confirm( 'Remove curriculum item?' ) ) return;
				var $item = $(e.target).closest('.lp-section-item'),
					id = $item.attr('data-id'),
					type = $item.attr('data-type');
				if( type == 'lp_quiz' ) {
					if (!this.$modalQuizzes) {
						this.$modalQuizzes = $(wp.template('lp-modal-search-quiz')({})).appendTo($(document.body)).hide();
					}
					this.$modalQuizzes.find('li[data-id=' + id + ']').removeClass('selected');
				}else{

				}
				$item.remove();
				this.removeItemIds.push(id);
			}
		});

		new LP_Curriculum_View( new LP_Curriculum_Model() );
	});
})(jQuery);