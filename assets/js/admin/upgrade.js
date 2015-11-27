;(function($){
	$(document).ready(function(){
		$('#learn-press-upgrade-course-actions .button').click(function(){
			var $button = $(this),
				action = $button.attr('data-action');
			if( action == 'upgrade' ){
				$.ajax({
					url: LearnPress_Settings.ajax,
					data: {
						action: 'learn_press_upgrade_courses'
					},
					dataType: 'text',
					success: function(response){
						response = LearnPress.parseJSON( response );
						if(response.result == 'success'){
							$button.closest('.error').fadeOut();
						}
					}
				});
			}else if( action == 'abort' ){
				$button.parent().hide();
				$('#learn-press-confirm-abort-upgrade-course').show();
			}
		});
		$('#learn-press-confirm-abort-upgrade-course .button').click(function(){
			var $button = $(this),
				action = $button.attr('data-action');
			if( action == 'yes' ){
				$.ajax({
					url: LearnPress_Settings.ajax,
					data: {
						action: 'learn_press_hide_upgrade_notice',
						ask_again: $('#learn-press-ask-again-abort-upgrade').is(':checked') ? 'no' : 'yes'
					},
					dataType: 'text',
					success: function(response){
						response = LearnPress.parseJSON( response );
						if(response.result == 'success'){
							if( response.message ){
								$button.closest('.error').html(response.message).removeClass('error').addClass('updated').animate({nothing: 1}).delay(3000).fadeOut();
							}else {
								$button.closest('.error').fadeOut();
							}
						}
					}
				});
			}else if( action == 'no' ){
				$('#learn-press-confirm-abort-upgrade-course').hide();
				$('#learn-press-upgrade-course-actions').show();
			}
		});
	});


})(jQuery);
