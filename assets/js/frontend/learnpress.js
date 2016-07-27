/**
 * @author ThimPress
 * @package LearnPress/Javascript
 * @version 1.0
 */
;if (typeof window.LP == 'undefined') {
	window.LP = {};
}
;(function ($) {
	"use strict";
	LP = $.extend({
		setUrlx  : function (url, title) {

			if (url) {
				history.pushState({}, title, url);
			}
		},
		reload   : function (url) {
			if (!url) {
				url = window.location.href;
			}
			window.location.href = url;
		},
		parseJSON: function (data) {
			var m = data.match(/<!-- LP_AJAX_START -->(.*)<!-- LP_AJAX_END -->/);
			try {
				if (m) {
					data = $.parseJSON(m[1]);
				} else {
					data = $.parseJSON(data);
				}
			} catch (e) {
				LP.log(e);
				data = {};
			}
			return data;
		},
		toElement: function (element, args) {
			args = $.extend({
				delay   : 300,
				duration: 'slow',
				offset  : 50,
				callback: null
			}, args || {});
			$('body, html')
				.fadeIn(10)
				.delay(args.delay)
				.animate({
					scrollTop: $(element).offset().top - args.offset
				}, args.duration, args.callback);
		}
	}, LP);

	$(document).on('submit', '#learn-press-form-login', function (e) {
		LP.doAjax({
			data   : {
				'lp-ajax': 'login',
				data     : $(this).serialize()
			},
			success: function (response, raw) {
				if (response.message) {
					LP.alert(response.message, function () {
						if (response.redirect) {
							LP.reload(response.redirect);
						}
					});
				} else {
					if (response.redirect) {
						LP.reload(response.redirect);
					}
				}
			}
		})
		return false;
	});
})(jQuery);