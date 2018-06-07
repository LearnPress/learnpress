;(function ($) {
	$(document).ready(function () {
		$('#learn-press-upgrade-course-actions .button').click(function () {
			var $button = $(this),
				action = $button.attr('data-action');
			if (action == 'upgrade') {
				$.ajax({
					url     : LP_Settings.ajax,
					data    : {
						action: 'learn_press_upgrade_courses'
					},
					dataType: 'text',
					success : function (response) {
						response = LP.parseJSON(response);
						if (response.result == 'success') {
							$button.closest('.error').fadeOut();
						}
					}
				});
			} else if (action == 'abort') {
				$button.parent().hide();
				$('#learn-press-confirm-abort-upgrade-course').show();
			}
		});
		$('#learn-press-confirm-abort-upgrade-course .button').click(function () {
			var $button = $(this),
				action = $button.attr('data-action');
			if (action == 'yes') {
				$.ajax({
					url     : LP_Settings.ajax,
					data    : {
						action   : 'learn_press_hide_upgrade_notice',
						ask_again: $('#learn-press-ask-again-abort-upgrade').is(':checked') ? 'no' : 'yes'
					},
					dataType: 'text',
					success : function (response) {
						response = LP.parseJSON(response);
						if (response.result == 'success') {
							if (response.message) {
								$button.closest('.error').html(response.message).removeClass('error').addClass('updated').animate({nothing: 1}).delay(3000).fadeOut();
							} else {
								$button.closest('.error').fadeOut();
							}
						}
					}
				});
			} else if (action == 'no') {
				$('#learn-press-confirm-abort-upgrade-course').hide();
				$('#learn-press-upgrade-course-actions').show();
			}
		});
	});


})(jQuery);

;(function ($) {
	var $doc = $(document);

	function parseJSON(response) {
		var matches = response.match(/<-- LP_AJAX_START -->(.*)<-- LP_AJAX_END -->/),
			json = {};

		if (matches && matches[1]) {
			try {
				json = JSON.parse(matches[1]);
			} catch (e) {
			}
		}
		return json;
	}

	function doRepairDatabase() {
		$('.lp-update-message').show();
		//$('#button-repair-database').attr('disabled', true);
		$.ajax({
			url     : ajaxurl,
			data    : {
				action: 'lp_repair_database'
			},
			type    : 'post',
			dataType: 'html',
			success : function (response) {
				response = parseJSON(response);
				if (response.result == 'success') {
					$('.lp-update-message').html(response.message);
				}
			}
		});
	}

	function doRollbackDatabase() {
		$('.lp-update-message').html('Processing...').show();
		//$('#button-repair-database').attr('disabled', true);
		$.ajax({
			url     : ajaxurl,
			data    : {
				action: 'lp_rollback_database'
			},
			type    : 'post',
			dataType: 'html',
			success : function (response) {
				response = parseJSON(response);
				if (response.result == 'success') {
					$('.lp-update-message').html(response.message);
				}
			}
		});
	}

	$doc.ready(function () {
		$doc.on('click', '#button-repair-database', function (e) {
			e.preventDefault();
			doRepairDatabase();
		}).on('click', '#button-rollback-database', function (e) {
			e.preventDefault();
			doRollbackDatabase();
		}).on('click', '#learn-press-update-button', function (e) {
			e.preventDefault();
			$('.lp-update-actions').addClass('lp-ajaxload');
			$('.upgrade-error').remove();
			$.ajax({
				url     : window.location.href,
				type    : 'post',
				dataType: 'text',
				data    : $(this).closest('form').serialize(),
				success : function (response) {
					$('.lp-update-content').replaceWith($(response).filter('.lp-update-content'));
				},
				error   : function () {
					var $d = $('.lp-update-actions').removeClass('lp-ajaxload');
					$('<p class="upgrade-error">' + 'Upgrade error' + '</p>').insertBefore($d);

				}
			});

		});
	});
})(jQuery);
