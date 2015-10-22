;(function($) {
	var LearnPress = window.LearnPress = {
		reload: function (url) {
			if (!url) {
				url = window.location.href;
			}
			window.location.href = url;
		},
		parseJSON: function(data){
			var m = data.match(/<!-- LP_AJAX_START -->(.*)<!-- LP_AJAX_END -->/);
			try {
				if (m) {
					data = $.parseJSON(m[1]);
				} else {
					data = $.parseJSON(data);
				}
			}catch(e){
				console.log(e);
				data = {};
			}
			return data;
		},
		parse_json: function (data){
			console.log('LearnPress.parse_json has deprecated, use LearnPress.parseJSON instead of')
			return LearnPress.parseJSON(data);
		}
	};
})(jQuery);