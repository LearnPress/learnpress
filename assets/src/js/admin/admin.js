;(function ($) {
	var updateItemPreview = function updateItemPreview() {
		$.ajax({
			url: '',
			data: {
				'lp-ajax': 'toggle_item_preview',
				item_id: this.value,
				previewable: this.checked ? 'yes' : 'no',
				nonce: $(this).attr('data-nonce')
			},
			dataType: 'text',
			success: function success(response) {
				response = LP.parseJSON(response);
			}
		});
	};
	/**
	 * Callback event for button to creating pages inside error message.
	 *
	 * @param {Event} e
	 */

	var createPages = function createPages(e) {
		var $button = $(this).addClass('disabled');
		e.preventDefault();
		$.post({
			url: $button.attr('href'),
			data: {
				'lp-ajax': 'create-pages'
			},
			dataType: 'text',
			success: function success(res) {
				var $message = $button.closest('.lp-notice').html('<p>' + res + '</p>');
				setTimeout(function () {
					$message.fadeOut();
				}, 2000);
			}
		});
	};

	var hideUpgradeMessage = function hideUpgradeMessage(e) {
		e.preventDefault();
		var $btn = $(this);
		$btn.closest('.lp-upgrade-notice').fadeOut();
		$.post({
			url: '',
			data: {
				'lp-hide-upgrade-message': 'yes'
			},
			success: function success(res) {}
		});
	};

	var pluginActions = function pluginActions(e) {
		// Premium addon
		if ($(e.target).hasClass('buy-now')) {
			return;
		}

		e.preventDefault();
		var $plugin = $(this).closest('.plugin-card');

		if ($(this).hasClass('updating-message')) {
			return;
		}

		$(this).addClass('updating-message button-working disabled');
		$.ajax({
			url: $(this).attr('href'),
			data: {},
			success: function success(r) {
				$.ajax({
					url: window.location.href,
					success: function success(r) {
						var $p = $(r).find('#' + $plugin.attr('id'));

						if ($p.length) {
							$plugin.replaceWith($p);
						} else {
							$plugin.find('.plugin-action-buttons a').removeClass('updating-message button-working').html(learn_press_admin_localize.plugin_installed);
						}
					}
				});
			}
		});
	};

	var preventDefault = function preventDefault(e) {
		e.preventDefault();
		return false;
	};

	$.fn._filter_post = function () {
		var $input = $('#post-search-input')

		if (!$input.length) {
			return
		}

		var $form = $($input[0].form),
				$select = $('<select name="author" id="author"></select>').insertAfter($input).select2({
					ajax: {
						url: window.location.href + '&lp-ajax=search-authors',
						dataType: 'json',
						s: ''
					},
					placeholder: 'Search by user',
					minimumInputLength: 3,
					allowClear: true
				}).on('select2:select', function () {
					$('input[name="author"]').val($select.val())
				})

		$form.on('submit', function () {
			var url = window.location.href.removeQueryVar('author').addQueryVar('author', $select.val())
		})
	}

	var onReady = function onReady() {
		$('.learn-press-dropdown-pages').LP('DropdownPages');
		$('.learn-press-advertisement-slider').LP('Advertisement', 'a', 's').appendTo($('#wpbody-content'));
		$('.learn-press-toggle-item-preview').on('change', updateItemPreview);
		$('.learn-press-tip').LP('QuickTip'); //$('.learn-press-tabs').LP('AdminTab');

		$(document).on('click', '#learn-press-create-pages', createPages).on('click', '.lp-upgrade-notice .close-notice', hideUpgradeMessage).on('click', '.plugin-action-buttons a', pluginActions).on('click', '[data-remove-confirm]', preventDefault).on('mousedown', '.lp-sortable-handle', function (e) {
			$('html, body').addClass('lp-item-moving');
			$(e.target).closest('.lp-sortable-handle').css('cursor', 'inherit');
		}).on('mouseup', function (e) {
			$('html, body').removeClass('lp-item-moving');
			$('.lp-sortable-handle').css('cursor', '');
		});

		/**
		 * Function Export invoice LP Order
		 * @author hungkv
		 * @since 3.2.7.8
		 */
		if($('#order-export__section').length){
			const tabs = document.querySelectorAll(".tabs");
			const tab = document.querySelectorAll(".tab");
			const panel = document.querySelectorAll(".panel");

			function onTabClick(event) {

				// deactivate existing active tabs and panel

				for (let i = 0; i < tab.length; i++) {
					tab[i].classList.remove("active");
				}

				for (let i = 0; i < panel.length; i++) {
					panel[i].classList.remove("active");
				}


				// activate new tabs and panel
				event.target.classList.add('active');
				let classString = event.target.getAttribute('data-target');
				document.getElementById('panels').getElementsByClassName(classString)[0].classList.add("active");
			}

			for (let i = 0; i < tab.length; i++) {
				tab[i].addEventListener('click', onTabClick, false);
			}

			// modal export order to pdf

			// Get the modal
			var modal = document.getElementById("myModal");

			// Get the button that opens the modal
			var btn = document.getElementById("order-export__button");

			// Get the <span> element that closes the modal
			var span = document.getElementsByClassName("close")[0];

			// When the user clicks on the button, open the modal
			btn.onclick = function() {
				modal.style.display = "block";
			}

			// When the user clicks on <span> (x), close the modal
			span.onclick = function () {
				modal.style.display = "none";
			}

			// When the user clicks anywhere outside of the modal, close it
			window.onclick = function (event) {
				if (event.target == modal) {
					modal.style.display = "none";
				}
			}

			if ($('#lp-invoice__content').length) {
				$('#lp-invoice__export').click(function(){
					var doc = new jsPDF('p', 'pt', 'letter');

					// We'll make our own renderer to skip this editor
					var specialElementHandlers = {
						'#bypassme': function (element, renderer) {
							return true;
						}
					};
					var margins = {
						top: 80,
						bottom: 60,
						left: 40,
						width: 522
					};

					doc.fromHTML(
							$('#lp-invoice__content')[0],
							margins.left, // x coord
							margins.top, { // y coord
								'width': margins.width, // max width of content on PDF
								'elementHandlers': specialElementHandlers
							},
							function (dispose) {
								// dispose: object with X, Y of the last line add to the PDF
								//          this allow the insertion of new lines after html
								var blob = doc.output("blob");
								window.open(URL.createObjectURL(blob));
							}, margins);
				});
			}

			// Script update option export to pdf
			$('#lp-invoice__update').click(function () {
				var order_id = $(this).data('id'),
						site_title = $('input[name="site_title"]'),
						order_date = $('input[name="order_date"]'),
						invoice_no = $('input[name="invoice_no"]'),
						order_customer = $('input[name="order_customer"]'),
						order_email = $('input[name="order_email"]'),
						order_payment = $('input[name="order_payment"]');
				if(site_title.is(':checked')){
					site_title = 'check';
				}else{
					site_title = 'uncheck';
				}
				if(order_date.is(':checked')){
					order_date = 'check';
				}else{
					order_date = 'uncheck';
				}
				if(invoice_no.is(':checked')){
					invoice_no = 'check';
				}else{
					invoice_no = 'uncheck';
				}
				if(order_customer.is(':checked')){
					order_customer = 'check';
				}else{
					order_customer = 'uncheck';
				}
				if(order_email.is(':checked')){
					order_email = 'check';
				}else{
					order_email = 'uncheck';
				}
				if(order_payment.is(':checked')){
					order_payment = 'check';
				}else{
					order_payment = 'uncheck';
				}

				$.ajax({
					type: "post",
					dataType: "html",
					url: 'admin-ajax.php',
					data: {
						site_title: site_title,
						order_date: order_date,
						invoice_no: invoice_no,
						order_customer: order_customer,
						order_email: order_email,
						order_id: order_id,
						order_payment: order_payment,
						action: "learnpress_update_order_exports",
					},
					beforeSend: function () {
						$('.export-options__loading').addClass('active');
					},
					success: function (response) {
						$("#lp-invoice__content").html("");
						$('#lp-invoice__content').append(response);
						$('.export-options__loading').removeClass('active');
						$('.options-tab').removeClass('active');
						$('.preview-tab').addClass('active');
						$('#panels .export-options').removeClass('active');
						$('#panels .pdf-preview').addClass('active');
					},
					error: function (jqXHR, textStatus, errorThrown) {
						console.log('The following error occured: ' + textStatus, errorThrown);
					}
				});
			});
		}
		$.fn._filter_post()
	};

	$(document).ready(onReady);
})(jQuery);
