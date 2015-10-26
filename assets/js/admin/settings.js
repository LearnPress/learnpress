;(function($){
	var $doc = $(document);

	function addPageToAllDropdowns( args ){
		var position = $.inArray( args.ID + "", args.positions );
		$('.learn-press-dropdown-pages').each(function() {
			var $select = $(this),
				$new_option = $('<option value="'+args.ID+'">'+args.name+'</option>')
			if (position == 0) {
				$('option', $select).each(function () {
					if (parseInt($(this).val())) {
						$new_option.insertBefore($(this));
						return false;
					}
				})
			} else if (position == args.positions.length - 1) {
				$select.append($new_option);
			} else {
				$new_option.insertAfter($('option[value="' + args.positions[position - 1] + '"]', $select));
			}
		});
	}

	function _ready(){

		$('.learn-press-dropdown-pages').each(function(){
			$(this).change(function(){
				var $select = $(this),
					thisId = $select.attr('id'),
					$actions = $select.siblings('.learn-press-quick-add-page-actions');

				$actions.addClass('hide-if-js');
				if( this.value != 'add_new_page' ){
					if( parseInt(this.value) ){
						$actions.find('a.edit-page').attr('href', 'post.php?post='+this.value+'&action=edit');
						$actions.find('a.view-page').attr('href', LearnPress_Settings.siteurl + '?page_id='+this.value);
						$actions.removeClass('hide-if-js');
						$select.attr('data-selected', this.value);
					}
					return;
				};
				$select.attr('disabled', true);
				$('.learn-press-quick-add-page-inline.'+thisId).removeClass('hide-if-js').find('input').focus().val('');
			});
		});

		$doc.on('click', '.learn-press-quick-add-page-inline button', function(){
			var $form = $(this).parent(),
				$input = $form.find('input'),
				$select = $form.siblings('select'),
				page_name = $input.val();
			if( ! page_name ){
				alert( 'Please enter the name of page' );
				$input.focus();
				return;
			}

			$.ajax({
				url: LearnPress_Settings.ajax,
				data:{
					action: 'learnpress_create_page',
					page_name: page_name
				},
				type: 'post',
				dataType: 'html',
				success: function(response){
					response = LearnPress.parseJSON(response);
					if( response.page ){
						addPageToAllDropdowns({
							ID: response.page.ID,
							name: response.page.post_title,
							positions: response.positions
						});
						$select.val(response.page.ID).removeAttr('disabled').focus().trigger('change');
						$form.addClass('hide-if-js');
					}else if(response.error){
						alert(response.error);
						$select.removeAttr('disabled')
					}

				}
			});
		}).on('click', '.learn-press-quick-add-page-inline a', function(e){
			e.preventDefault();
			var $select = $(this).parent().addClass('hide-if-js').siblings('select');
			$select.val( $select.attr('data-selected')).removeAttr('disabled').trigger('change');
		})
	}

	$doc.ready(_ready);
})(jQuery);