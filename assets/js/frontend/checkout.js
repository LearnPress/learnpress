if (typeof window.LP === 'undefined') {
	window.LP = {};
}
;
(function ($) {
	"use strict";
	LP.reload = function (url) {
		if (!url) {
			url = window.location.href;
		}
		window.location.href = url;
	};
	LP.Checkout = {
		$form              : null,
		init               : function () {
			var $doc = $(document);
			this.$form = $('form[name="lp-checkout"]');
			$doc.on('click', 'input[name="payment_method"]', this.selectPaymentMethod);
			$doc.on('click', '#learn-press-checkout-login-button', this.login);

			$('input[name="payment_method"]:checked').trigger('click');
			this.$form.on('submit', this.doCheckout);
		},
		selectPaymentMethod: function () {
			var methodId = $(this).attr('id'),
				checkoutButton = $('#learn-press-checkout-place-order'),
				isError = false;
			if ($('.payment-methods input.input-radio').length > 1) {
				var $paymentForm = $('div.payment-method-form.' + methodId);

				if ($(this).is(':checked')) {
					$('div.payment-method-form').filter(':visible').slideUp(250);
					$(this).parents('li:first').find('.payment-method-form.' + methodId).slideDown(250);
				}
			} else {
				$('div.payment-method-form').show();
			}
			isError = $('div.payment-method-form:visible').find('input[name="' + methodId + '-error"]').val() == 'yes';
			if (isError) {
				checkoutButton.attr('disabled', 'disabled');
			} else {
				checkoutButton.removeAttr('disabled');
			}
			var order_button_text = $(this).data('order_button_text');
			if (order_button_text) {
				checkoutButton.val(order_button_text);
			} else {
				checkoutButton.val(checkoutButton.data('value'));
			}
		},
		login              : function () {
			var $form = $(this.form);
			if ($form.triggerHandler('checkout_login') !== false) {
				$.ajax({
					url     : LP_Settings.siteurl + '/?lp-ajax=checkout-login',
					dataType: 'html',
					data    : $form.serialize(),
					type    : 'post',
					success : function (response) {
						response = LP.parseJSON(response);
						if (response.result === 'fail') {
							if (response.messages) {
								LP.Checkout.showErrors(response.messages);
							} else {
								LP.Checkout.showErrors('<div class="learn-press-error">Unknown error!</div>');
							}
						} else {
							if (response.redirect) {
								window.location.href = response.redirect;
							}
						}
					}
				});
			}
			return false;
		},
		doCheckout         : function () {
			var $form = $(this),
				$place_order = $form.find('#learn-press-checkout-place-order'),
				processing_text = $place_order.attr('data-processing-text'),
				text = $place_order.attr('value');
			if ($form.triggerHandler('learn_press_checkout_place_order') !== false && $form.triggerHandler('learn_press_checkout_place_order_' + $('#order_review').find('input[name=payment_method]:checked').val()) !== false) {
				if (processing_text) {
					$place_order.val(processing_text);
				}
				$place_order.prop('disabled', true);
				LP.blockContent();
				$.ajax({
					url     : LP_Settings.siteurl + '/?lp-ajax=checkout',
					dataType: 'html',
					data    : $form.serialize(),
					type    : 'post',
					success : function (response) {
						response = LP.parseJSON(response);
						if (response.result === 'fail') {
							var $error = '';
							if (!response.messages) {
								if (response.code && response.code == 30) {
									LP.Checkout.showErrors('<div class="learn-press-error">' + learn_press_js_localize.invalid_field + '</div>');
								} else {
									LP.Checkout.showErrors('<div class="learn-press-error">' + learn_press_js_localize.unknown_error + '</div>');
								}
							} else {
								LP.Checkout.showErrors(response.messages);
							}

						} else if (response.result === 'success') {
							if (response.redirect) {
								$place_order.val('Redirecting');
								LP.reload(response.redirect);
								return;
							}
						}
						$place_order.val(text);
						$place_order.prop('disabled', false);
						LP.unblockContent();
					},
					error   : function (jqXHR, textStatus, errorThrown) {
						LP.Checkout.showErrors('<div class="learn-press-error">' + errorThrown + '</div>');
						$place_order.val(text);
						$place_order.prop('disabled', false);
						LP.unblockContent();
					}
				});
			}
			return false;
		},
		showErrors         : function (messages) {
			$('.learn-press-error, .learn-press-notice, .learn-press-message').remove();
			this.$form.prepend(messages);
			$('html, body').animate({
				scrollTop: ( LP.Checkout.$form.offset().top - 100 )
			}, 1000);
			$(document).trigger('learnpress_checkout_error');
		}
	};
	$(document).ready(function () {
		LP.Checkout.init();
	});
})(jQuery);