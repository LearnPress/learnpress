
jQuery(document).ready( function($) {
	learn_press_open_pointer(0);
	function learn_press_open_pointer(i) {
		pointer = lpPointer.pointers[i];
		options = $.extend( pointer.options, {
			close: function() {
				$.post( ajaxurl, {
					pointer: pointer.pointer_id,
					action: 'dismiss-wp-pointer'
				});
			}
		});

		$(pointer.target).pointer( options ).pointer('open');
	}
});