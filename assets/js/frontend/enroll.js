if (typeof window.LP == 'undefined') {
	window.LP = {};
}
;
(function ($) {
	"use strict";
	LP.Enroll = {
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
					url     : LP.getUrl(),
					dataType: 'html',
					data    : $form.serialize(),
					type    : 'post',
					success : function (response) {
						response = LP.parseJSON(response);
						if (response.result == 'fail') {
							if (LP.Hook.applyFilters('learn_press_user_enroll_course_failed', course_id) !== false) {
								if (response.redirect) {
									LP.reload(response.redirect);
								}
							}
						} else {
							if (LP.Hook.applyFilters('learn_press_user_enrolled_course', course_id) !== false) {
								if (response.redirect) {
									LP.reload(response.redirect);
								}
							}
						}
					},
					error   :	function( jqXHR, textStatus, errorThrown ) {
						LP.Hook.doAction('learn_press_user_enroll_course_failed', course_id);
						$button.removeClass('loading').addClass('failed');
						LP.Enroll.showErrors('<div class="learn-press-error">' + errorThrown + '</div>');
					}
				})
			}
			return false;
		},
		showErrors         : function (messages) {
			this.removeErrors();
			this.$form.prepend(messages);
			$('html, body').animate({
				scrollTop: ( LP.Enroll.$form.offset().top - 100 )
			}, 1000);
			$(document.body).trigger('learn_press_enroll_error');
		},
		removeErrors: function(){
			$('.learn-press-error, .learn-press-notice, .learn-press-message').remove();
		}
	}
	$(document).ready(function () {
		LP.Enroll.init()
	});
})(jQuery);