if (typeof window.LearnPress == 'undefined') {
	window.LearnPress = {};
}
;
(function ($) {
	LearnPress.reload = function (url) {
		if (!url) {
			url = window.location.href;
		}
		window.location.href = url;
	}
	LearnPress.Checkout = {
		$form              : null,
		init               : function () {
			var $doc = $(document);
			this.$form = $('form[name="lp-checkout"]');
			$doc.on('click', 'input[name="payment_method"]', this.selectPaymentMethod);
			$doc.on('click', '#learn-press-checkout-login-button', this.login);
			this.$form.on('submit', this.doCheckout);
		},
		selectPaymentMethod: function () {
			if ($('.payment-methods input.input-radio').length > 1) {
				var $paymentForm = $('div.payment-method-form.' + $(this).attr('id'));
				if ($(this).is(':checked') && !$paymentForm.is(':visible')) {
					$('div.payment-method-form').filter(':visible').slideUp(250);

					$('div.payment-method-form.' + $(this).attr('id')).slideDown(250);
				}
			} else {
				$('div.payment-method-form ').show();
			}

			if ($(this).data('order_button_text')) {
				$('#place_order').val($(this).data('order_button_text'));
			} else {
				$('#place_order').val($('#place_order').data('value'));
			}
		},
		login              : function () {
			var $form = $(this.form);
			if ($form.triggerHandler('checkout_login') !== false) {
				$.ajax({
					url     : LearnPress_Settings.siteurl +'/?lp-ajax=checkout-login',
					dataType: 'html',
					data    : $form.serialize(),
					type    : 'post',
					success : function (response) {
						response = LearnPress.parseJSON(response);
						if (response.result == 'fail') {
							if (response.messages) {
								LearnPress.Checkout.showErrors(response.messages);
							} else {
								LearnPress.Checkout.showErrors('<div class="learn-press-error">Unknown error!</div>');
							}
						} else {
							if (response.redirect) {
								window.location.href = response.redirect;
							}
						}
					}
				})
			}
			return false;
		},
		doCheckout         : function () {
			var $form = $(this);
			if ($form.triggerHandler('checkout_place_order') !== false && $form.triggerHandler('checkout_place_order_' + $('#order_review').find('input[name=payment_method]:checked').val()) !== false) {
				$.ajax({
					url     : LearnPress_Settings.siteurl +'/lp-checkout/?lp-ajax=checkout',
					dataType: 'html',
					data    : $form.serialize(),
					type    : 'post',
					success : function (response) {
						response = LearnPress.parseJSON(response);
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
					},
					error:	function( jqXHR, textStatus, errorThrown ) {
						LearnPress.Checkout.showErrors('<div class="learn-press-error">'+errorThrown+'</div>');
					}
				})
			}
			return false;
		},
		showErrors         : function (messages) {
			$('.learn-press-error, .learn-press-notice, .learn-press-message').remove();
			this.$form.prepend(messages);
			$('html, body').animate({
				scrollTop: ( LearnPress.Checkout.$form.offset().top - 100 )
			}, 1000);
			$(document).trigger('learnpress_checkout_error');
		}
	}
	$(document).ready(function () {
		LearnPress.Checkout.init()
	});
})(jQuery);