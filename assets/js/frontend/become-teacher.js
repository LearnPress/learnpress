;(function ($) {
	$(document).ready(function () {
		$('form[name="become-teacher-form"]').submit(function () {
			var $form = $(this),
				$submit = $form.find('button[type="submit"]');
			$form.find('.learn-press-error, .learn-press-message').fadeOut('fast', function () {
				$(this).remove()
			});
			$submit.prop('disabled', true).html($submit.data('text-process'));
			if ($form.triggerHandler('become_teacher_send') !== false) {
				$.ajax({
					url         : $form.attr('action'),
					data        : $form.serialize(),
					dataType    : 'html',
					type        : 'post',
					beforeSubmit: function () {
						$form.find('input, select, textarea, button').prop('disabled', true);
					},
					success     : function (code) {
						var response = LP.parseJSON(code);

						if (response.result == 'success') {
							$form.find('.become-teacher-fields').remove();//find('input, select, textarea, button').prop('disabled', true);
						} else {
							$submit.prop('disabled', false);
							$submit.html($submit.data('text'));
						}
						if (response.message) {
							for (var n = response.message.length, i = n - 1; i >= 0; i--) {
								$form.prepend($(response.message[i]))
							}
						}
					}
				});
			}
			return false;
		});
	});
})(jQuery);