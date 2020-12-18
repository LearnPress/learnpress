"use strict";
var get_post_thimpress = {
	get_content: function () {
		var post_html = jQuery('.list_post_thimpress .content_list_post_thimpress article .entry-content').get();
		jQuery('.list_post_thimpress .show_content_post_thimpress').html(post_html);
	},

};
jQuery(document).ready(function () {
	get_post_thimpress.get_content();
});