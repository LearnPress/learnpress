if (typeof window.LearnPress == 'undefined') {
	window.LearnPress = {};
}
;
(function ($) {
	"use strict";
	LearnPress.Enroll = {
		$form              : null,
		init               : function () {
			var $doc = $(document);
			this.$form = $('form[name="enroll-course"]');
			this.$form.on('submit', this.enroll);
		},
		enroll         : function () {
			var $form = $(this),
				$button = $form.find('.button.enroll-button'),
				course_id = $form.find('input[name="enroll-course"]').val();
			if (!$button.hasClass('enrolled') && $form.triggerHandler('learn_press_enroll_course') !== false ) {
				$button.removeClass('enrolled failed').addClass('loading');
				$.ajax({
					url     : LearnPress.getUrl(),
					dataType: 'html',
					data    : $form.serialize(),
					type    : 'post',
					success : function (response) {
						response = LearnPress.parseJSON(response);
						if (response.result == 'fail') {
							if( LearnPress.Hook.applyFilters( 'learn_press_user_enroll_course_failed', course_id ) !== false ) {
								if (response.redirect) {
									LearnPress.reload(response.redirect);
								}
							}
						} else {
							if( LearnPress.Hook.applyFilters( 'learn_press_user_enrolled_course', course_id ) !== false ) {
								if (response.redirect) {
									LearnPress.reload(response.redirect);
								}
							}
						}
					},
					error:	function( jqXHR, textStatus, errorThrown ) {
						LearnPress.Hook.doAction( 'learn_press_user_enroll_course_failed', course_id );
						$button.removeClass('loading').addClass('failed');
						LearnPress.Enroll.showErrors('<div class="learn-press-error">'+errorThrown+'</div>');
					}
				})
			}
			return false;
		},
		showErrors         : function (messages) {
			this.removeErrors();
			this.$form.prepend(messages);
			$('html, body').animate({
				scrollTop: ( LearnPress.Enroll.$form.offset().top - 100 )
			}, 1000);
			$(document.body).trigger('learn_press_enroll_error');
		},
		removeErrors: function(){
			$('.learn-press-error, .learn-press-notice, .learn-press-message').remove();
		}
	}
	$(document).ready(function () {
		LearnPress.Enroll.init()
	});
})(jQuery);