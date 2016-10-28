;(function ($) {
	"use strict";
	$(document).ready(function () {
		$(document).on('click', '.table-orders .cancel-order', function (e) {
			e.preventDefault();
			var _this = $(this),
				_href = _this.attr('href');
			LP.alert(learn_press_js_localize.confirm_cancel_order, function (confirm) {
				if (confirm) {
					window.location.href = _href;
				}
			});
			return false;
		});

		$(document).on('submit', '#learn-press-form-login', function (e) {
			var $form = $(this),
				data = $form.serialize();
			$form.find('.learn-press-error, .learn-press-notice, .learn-press-message').fadeOut();
			$form.find('input').attr('disabled', true);
			LP.doAjax({
				data   : {
					'lp-ajax': 'login',
					data     : data
				},
				success: function (response, raw) {
					LP.showMessages(response.message, $form, 'LOGIN_ERROR');
					if (response.result == 'error') {
						$form.find('input').attr('disabled', false);
						$('#learn-press-form-login input[type="text"]').focus();
					}
					if (response.redirect) {
						LP.reload(response.redirect);
					}
				},
				error  : function () {
					LP.showMessages('', $form, 'LOGIN_ERROR');
					$form.find('input').attr('disabled', false);
					$('#learn-press-form-login input[type="text"]').focus();
				}
			});
			return false;
		});

		$('#learn-press-form-login input[type="text"]').focus();
		
		$('#lp_profile_type_select').on('change', function(){
			$(this).val();
			$('.lp_profile_type_panel').hide();
			$('#profile-picture-'+$(this).val()).show();
		});
	});
})(jQuery);