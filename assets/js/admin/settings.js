;(function ($) {
	var $doc = $(document),
		oldData = false;

	function addPageToAllDropdowns(args) {
		var position = $.inArray(args.ID + "", args.positions);
		$('.learn-press-dropdown-pages').each(function () {
			var $select = $(this),
				$new_option = $('<option value="' + args.ID + '">' + args.name + '</option>')
			if (position == 0) {
				$('option', $select).each(function () {
					if (parseInt($(this).val())) {
						$new_option.insertBefore($(this));
						return false;
					}
				})
			} else if (position == args.positions.length - 1) {
				$select.append($new_option);
			} else {
				$new_option.insertAfter($('option[value="' + args.positions[position - 1] + '"]', $select));
			}
		});
	}

	function _insertVariableToEditor(edId, variable) {
		var ed = null,
			editorId = null,
			activeEditor = tinyMCE.activeEditor;
		for (editorId in tinyMCE.editors) {
			if (editorId == edId) {
				break;
			}
			editorId = null;
		}
		if (!editorId) {
			_insertVariableToTextarea(edId, variable);
			return;
		}
		if (activeEditor && $(activeEditor.getElement()).attr('id') == editorId) {
			activeEditor.execCommand('insertHTML', false, variable);
			if ($(activeEditor.getElement()).is(':visible')) {
				_insertVariableToTextarea(edId, variable);
			}
		} else {

		}
	}

	function _insertVariableToTextarea(eId, varibale) {
		var $el = $('#' + eId).get(0);
		if (document.selection) {
			$el.focus();
			sel = document.selection.createRange();
			sel.text = varibale;
			$el.focus();
		}
		else if ($el.selectionStart || $el.selectionStart == '0') {
			var startPos = $el.selectionStart;
			var endPos = $el.selectionEnd;
			var scrollTop = $el.scrollTop;
			$el.value = $el.value.substring(0, startPos) + varibale + $el.value.substring(endPos, $el.value.length);
			$el.focus();
			$el.selectionStart = startPos + varibale.length;
			$el.selectionEnd = startPos + varibale.length;
			$el.scrollTop = scrollTop;
		} else {
			$el.value += varibale;
			$el.focus();
		}
	}

	function _ready() {

		var $generate_thumbnail = $('input[name="learn_press_generate_course_thumbnail"]').on('click', function () {
			var toggle = !($(this).is(':checked'));
			console.log(toggle);
			$('.single-course-thumbnail').toggleClass('hide-if-js', toggle);
			$('.archive-course-thumbnail').toggleClass('hide-if-js', toggle);
		});
		$generate_thumbnail.filter(':checked').trigger('change');

		$('#learn_press_email_formats').change(function () {
			$('.learn-press-email-template.' + this.value).removeClass('hide-if-js').siblings().addClass('hide-if-js');
		});
		$('.learn-press-email-variables').each(function () {
			var $list = $(this),
				hasEditor = $list.hasClass('has-editor');
			$list.on('click', 'li', function () {
				if (hasEditor) {
					_insertVariableToEditor($list.attr('data-target'), $(this).data('variable'));
				} else {
					_insertVariableToTextarea($list.attr('data-target'), $(this).data('variable'));
				}
			})
		});

		$('.learn-press-dropdown-pages').each(function () {
			$(this).change(function () {
				var $select = $(this),
					thisId = $select.attr('id'),
					$actions = $select.siblings('.learn-press-quick-add-page-actions');
				$select.siblings('[data-id="' + thisId + '"]').hide();
				$actions.addClass('hide-if-js');
				if (this.value != 'add_new_page') {
					if (parseInt(this.value)) {
						$actions.find('a.edit-page').attr('href', 'post.php?post=' + this.value + '&action=edit');
						$actions.find('a.view-page').attr('href', LP_Settings.siteurl + '?page_id=' + this.value);
						$actions.removeClass('hide-if-js');
						$select.attr('data-selected', this.value);
					}
					return;
				}
				;
				$select.attr('disabled', true);
				$('.learn-press-quick-add-page-inline.' + thisId).removeClass('hide-if-js').find('input').focus().val('');
			});
		});

		$doc.on('click', '.learn-press-quick-add-page-inline button', function () {
			var $form = $(this).parent(),
				$input = $form.find('input'),
				$select = $form.siblings('select'),
				page_name = $input.val();
			if (!page_name) {
				alert('Please enter the name of page');
				$input.focus();
				return;
			}

			$.ajax({
				url     : LP_Settings.ajax,
				data    : {
					action   : 'learnpress_create_page',
					page_name: page_name
				},
				type    : 'post',
				dataType: 'html',
				success : function (response) {
					response = LP.parseJSON(response);
					if (response.page) {
						addPageToAllDropdowns({
							ID       : response.page.ID,
							name     : response.page.post_title,
							positions: response.positions
						});
						$select.val(response.page.ID).removeAttr('disabled').focus().trigger('change');
						$form.addClass('hide-if-js');
					} else if (response.error) {
						alert(response.error);
						$select.removeAttr('disabled')
					}
					$select.siblings('button').show();
				}
			});
		}).on('click', '.learn-press-quick-add-page-inline a', function (e) {
			e.preventDefault();
			var $select = $(this).parent().addClass('hide-if-js').siblings('select');
			$select.val($select.attr('data-selected')).removeAttr('disabled').trigger('change');
			$select.siblings('button').show();
		}).on('click', '.button-quick-add-page', function (e) {
			var $button = $(this),
				id = $button.data('id');
			$button.siblings('select#' + id).val('add_new_page').trigger('change');
		}).on('keypress keydown', '.learn-press-quick-add-page-inline input[type="text"]', function (e) {
			if (e.keyCode == 13 && e.type == 'keypress') {
				e.preventDefault();
				$(this).siblings('button').trigger('click')
			} else if (e.keyCode == 27 && e.type == 'keydown') {
				$(this).siblings('a').trigger('click')
			}
		}).on('change update', '#learn_press_required_review', function (e) {
			var $depend = $('input[name="learn_press_enable_edit_published"]').closest('tr');
			$depend.toggleClass('hide-if-js', !e.target.checked).find('input[type="checkbox"]').prop('disabled', !e.target.checked)
		}).on('change update', '#learn_press_auto_redirect_next_lesson', function (e) {
			var $depend = $('#learn_press_auto_redirect_message, #learn_press_auto_redirect_time').closest('tr');
			$depend.toggleClass('hide-if-js', !e.target.checked);
		}).on('change', '.learn-press-single-course-permalink input[type="radio"]', function () {
			var $check = $(this),
				$row = $check.closest('.learn-press-single-course-permalink');
			if ($row.hasClass('custom-base')) {
				$row.find('input[type="text"]').prop('readonly', false);
			} else {
				$row.siblings('.custom-base').find('input[type="text"]').prop('readonly', true);
			}
		});

		$('#learn_press_required_review, #learn_press_auto_redirect_next_lesson').trigger('update');
		$('#learn-press-admin-settings').on('click', '.nav-tab, .subsubsub > li > a', function (e) {
			e.preventDefault();
			var redirect = $(this).attr('href'),
				data = $('#mainform').serialize();
			if (data != oldData) {
				$('#learn-press-updating-message').show();
				$.ajax({
					url     : window.location.href,
					data    : data,
					type    : 'post',
					dataType: 'html',
					success : function (res) {
						$('#learn-press-updating-message').hide();
						$('#learn-press-updated-message').show();
						window.location.href = redirect;
					}
				});
			} else {
				window.location.href = redirect;
			}
		});
		if ($('#learn-press-admin-settings .subsubsub').length) {
			$('#learn-press-admin-settings').removeClass('no-subtabs');
		}
		$('.learn-press-settings-wrap').addClass('ready')
			.on('click', '#learn-press-reset-settings', function () {
				if (!confirm($(this).data('text'))) {
					return false;
				}
			});

		// hold current settings to know if user changed anything
		oldData = $('#mainform').serialize();

		(function () {
			$('.learn-press-dropdown-pages').each(function () {
				var $sel = $(this);
				$sel.css('width', $sel.width() + 50).find('option').each(function () {
					$(this).html($(this).html().replace(/&nbsp;&nbsp;&nbsp;/g, ''));
				});
				$sel.select2();
			});
		})();
	}

	$doc.ready(_ready);
})(jQuery);