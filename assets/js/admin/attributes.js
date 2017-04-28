;(function ($) {
	var select2Options = {
		formatNoMatches: function () {
			$(document).off('keyup.addNewAttributeValueEvent');
			$(document).on('keyup.addNewAttributeValueEvent', '.select2-input', addNewAttributeValueEvent);
			return 'No match found, <code>Ctrl + Enter</code> to add new attribute';
		},
		formatMatches  : function () {
			$(document).off('keyup.addNewAttributeValueEvent');
		}
	}, postId = 0;

	function getPostId() {
		if (!postId) {
			postId = $('input[name="post_ID"]').val();
		}
		return postId;
	}

	function addAttributeToCourse(button) {
		$(button).addClass('disabled');
		$.post({
			url    : window.location.href.addQueryVar('add-attribute-to-course', getPostId()),
			data   : $().extend({}, $(button).data()),
			success: function (response) {
				var $html = $('.course-attributes'),
					$newHtml = $(response);
				$newHtml.appendTo($html);
				$newHtml.find('.course-attribute-values').select2(select2Options)
			}
		})
	}

	function addNewAttributeValue(name, taxonomy, el) {
		var $li = $(el).closest('.learn-press-attribute');
		$.post({
			url     : window.location.href.addQueryVar('add-attribute-value', getPostId()),
			data    : {
				name    : name,
				taxonomy: taxonomy
			},
			dataType: 'text',
			success : function (response) {
				response = LP.parseJSON(response);
				if (response.result == 'success') {
					$li.find('select.course-attribute-values').append('<option value="' + response.slug + '" selected="selected">' + response.name + '</option>').change();
				} else {
					if (response.message) {
						alert(response.message);
					}
				}
			}
		})
	}

	function addNewAttributeValueEvent(e) {
		if (e.ctrlKey && e.keyCode == 13) {
			var $sel = $('.select2-focused');
			if ($sel.length == 0) {
				return;
			}
			addNewAttributeValue($sel.val(), $sel.closest('.learn-press-attribute').data('taxonomy'), this)
		}
	}

	function saveAttributesEvent(e) {
		$.post({
			url    : window.location.href.addQueryVar('save-attributes', getPostId()),
			data   : {
				data: $('.course-attributes').find('input, select, textarea').serialize(),
			},
			success: function () {

			}
		});
	}

	function removeAttributesEvent (e, this_btn) {
		e.preventDefault();

		var $button = $(e.target);

        LP.MessageBox.quickConfirm($button, {
            onOk: function (a) {
                var btn_add_attr = $('.course-attribute-taxonomy li[data-taxonomy="' + this_btn.closest('li.learn-press-attribute').data('taxonomy') + '"]');

                // Enable btn
                btn_add_attr.removeClass('disabled');
                // Remove attribute in client
                this_btn.closest('li.learn-press-attribute').remove();

                $.post({
                    url    : window.location.href.addQueryVar('remove-attributes', getPostId()),
                    data   : $().extend({}, $(btn_add_attr).data()),
                    success: function (response) {

                    }
                })
            }
        });

	}

	$(document).ready(function () {
		$(document)
			.on('click', '.add-attribute:not(.disabled)', function () {
				addAttributeToCourse(this);
			})
			.on('click', '#save-attributes', saveAttributesEvent)
			.on('click', '.learn-press-remove-attribute', function(e){
				removeAttributesEvent(e, $(this))
			})
			.on('keyup.addNewAttributeValueEvent', '.select2-input', addNewAttributeValueEvent);

		var $courseAttributes = $('.course-attribute-values');

		if ($courseAttributes.length) {
			$courseAttributes.select2(select2Options);
		}


	});
})(jQuery);
