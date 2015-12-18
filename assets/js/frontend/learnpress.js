/**
 * @author ThimPress
 * @package LearnPress/Javascript
 * @version 1.0
 */
;if (typeof window.LearnPress == 'undefined') {
	window.LearnPress = {};
}
;(function ($) {
	LearnPress = $.extend({
		setUrl    : function (url, title) {
			history.pushState({}, title, url);
		},
		reload    : function (url) {
			if (!url) {
				url = window.location.href;
			}
			window.location.href = url;
		},
		parseJSON : function (data) {
			var m = data.match(/<!-- LP_AJAX_START -->(.*)<!-- LP_AJAX_END -->/);
			try {
				if (m) {
					data = $.parseJSON(m[1]);
				} else {
					data = $.parseJSON(data);
				}
			} catch (e) {
				LearnPress.log(e);
				data = {};
			}
			return data;
		},
		toElement : function (element, args) {
			args = $.extend({
				delay   : 300,
				duration: 'slow',
				offset  : 50
			}, args || {});
			$('body, html')
				.fadeIn(10)
				.delay(args.delay)
				.animate({
					scrollTop: $(element).offset().top - args.offset
				}, args.duration);
		},
		parse_json: function (data) {
			LearnPress.log('LearnPress.parse_json has deprecated, use LearnPress.parseJSON instead of')
			return LearnPress.parseJSON(data);
		}
	}, LearnPress);
})(jQuery);