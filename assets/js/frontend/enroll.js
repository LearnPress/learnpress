if (typeof window.LearnPress == 'undefined') {
	window.LearnPress = {};
}
;
(function ($) {
	LearnPress.Enroll = {
		$form              : null,
		init               : function () {
			var $doc = $(document);
			this.$form = $('form[name="enroll-course"]');
			this.$form.on('submit', this.enroll);
		},
		enroll         : function () {
			var $form = $(this);
			if ($form.triggerHandler('learn_press_enroll_course') !== false ) {
				$.ajax({
					url     : learn_press_url,
					dataType: 'html',
					data    : $form.serialize(),
					type    : 'post',
					success : function (response) {
						response = LearnPress.parse_json(response);
						if (response.result == 'fail') {
							if (response.messages) {
								LearnPress.Checkout.showErrors(response.messages);
							} else {
								LearnPress.Checkout.showErrors('<div class="learn-press-error">Unknown error!</div>');
							}
						} else {
							if (response.redirect) {
								LearnPress.reload(response.redirect);
							}
						}
						$form.unblock_ui();
					},
					error:	function( jqXHR, textStatus, errorThrown ) {
						LearnPress.Checkout.showErrors('<div class="learn-press-error">'+errorThrown+'</div>');
						$form.unblock_ui();
					}
				})
			}
			return false;
		},
		showErrors         : function (messages) {
			$('.learn-press-error, .learn-press-notice, .learn-press-message').remove();
			this.$form.prepend(messages);
			$('html, body').animate({
				scrollTop: ( LearnPress.Enroll.$form.offset().top - 100 )
			}, 1000);
			$(document.body).trigger('learn_press_enroll_error');
		}
	}
	$(document).ready(function () {
		LearnPress.Enroll.init()
	});
})(jQuery);