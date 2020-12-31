(function ($) {
	'use strict';
	const getBlogPostThimpress = {
		get_content() {
			const el_list_post_thimpress = $('.list_post_thimpress');

			if (!el_list_post_thimpress.length) {
				return;
			}

			const lp_nonce = el_list_post_thimpress.find('#lp-nonce').val();
			// const el_data_crawl = el_list_post_thimpress.find( '.data_crawl' );
			const el_show_content_post_thimpress = el_list_post_thimpress.find('.show_content_post_thimpress');
			const el_place_holder = el_list_post_thimpress.find('.lp-place-holder');

			// Get content page Blog Thimpress
			const params = {
				'lp-ajax': 'lp-get-blog-post-thimpess', 'lp-nonce': lp_nonce,
			};

			$.ajax({
				url     : lpGlobalSettings.ajax,
				method  : 'post',
				data    : params,
				dataType: 'json',
				success(res) {
					if ('success' === res.status) {
						var doc = $.parseXML(res.data),
							$xml = $(doc),
							content_html = [];
						$.each($xml.find('channel item'), function () {
							var title = $(this).find('title').text(),
								link = $(this).find('link').text(),
								description = $(this).find('description').text();
							content_html.push('<div class="item-blog"><h3><a href="' + link + '?utm_source=lp-post&utm_medium=wp-dash&utm_campaign=lp-news-feed" target="_blank">' + title + '</a></h3><p>' + description + '</p></div>');
						});
						el_show_content_post_thimpress.html(content_html);
					}
				},
				complete(res) {
					el_place_holder.hide();
				},
				error(err) {
				},
			});
		},
	};

	$(function () {
		getBlogPostThimpress.get_content();
	});
}(jQuery));
