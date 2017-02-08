;(function ($) {
	function addAttribute(button) {
		$(button).addClass('disabled');
		$.post({
			url    : window.location.href.addQueryVar('add-course-attribute', $('input[name="post_ID"]').val()),
			data   : $().extend({}, $(button).data()),
			success: function (response) {
				var $html = $('.course-attributes');
				$(response).appendTo($html);
				$html.find('.course-attribute-values').select2()
			}
		})
	}

	function addNewAttributeValue(name, taxonomy) {
		$.post({
			url    : window.location.href.addQueryVar('add-attribute-value', $('input[name="post_ID"]').val()),
			data   : {
				name    : name,
				taxonomy: taxonomy
			},
			success: function (response) {
				var $html = $('.course-attributes');
				$(response).appendTo($html);
				$html.find('.course-attribute-values').select2()
			}
		})
	}

	function addNewAttributeValueEvent(e) {
		if (e.ctrlKey && e.keyCode == 13) {
			var $sel = $('.select2-focused');
			if ($sel.length == 0) {
				return;
			}
			addNewAttributeValue($sel.val(), $sel.closest('.learn-press-attribute').data('taxonomy'))
		}
	}

	$(document).ready(function () {
		$(document).on('click', '.add-attribute:not(.disabled)', function () {
			addAttribute(this);
		}).on('keyup.addNewAttributeValueEvent', '.select2-input', addNewAttributeValueEvent);
		$('.course-attribute-values').select2({
			formatNoMatches: function () {
				$(document).off('keyup.addNewAttributeValueEvent');
				$(document).on('keyup.addNewAttributeValueEvent', '.select2-input', addNewAttributeValueEvent);
				return 'No match found, <code>Ctrl + Enter</code> to add new attribute';
			},
			formatMatches  : function () {
				$(document).off('keyup.addNewAttributeValueEvent');
			}
		});

	});
})(jQuery);
