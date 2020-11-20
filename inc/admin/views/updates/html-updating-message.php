<?php
/**
 * Template for displaying message when LP updating to latest version
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.8
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="notice notice-warning lp-notice-update-database do-updating">
	<p>
		<?php _e( '<strong>LearnPress update</strong> – We are running updater to upgrade your database to the latest version.', 'learnpress' ); ?>
	</p>
</div>

<script>
	(function( win, doc ) {
		var t = null;
		var $ = jQuery;

		function parseJSON( data ) {
			if ( typeof data !== 'string' ) {
				return data;
			}

			var m = String.raw({raw: data}).match(/<-- LP_AJAX_START -->(.*)<-- LP_AJAX_END -->/s);

			try {
				if (m) {
					data = $.parseJSON(m[1].replace(/(?:\r\n|\r|\n)/g, ''));
				} else {
					data = $.parseJSON(data);
				}
			} catch (e) {
				data = {};
			}
			return data;
		}

		function sendRequest() {
			t = setTimeout( function() {
				var $ = jQuery;
				$.ajax({
					url: '',
					data: {
						'lp-ajax': 'check-updated'
					},
					success: function( response ) {
						response = parseJSON( response );

						if ( response.result === 'success' ) {
							clearTimeout(t);
							$('.lp-notice-update-database.do-updating').replaceWith( $( response.message ) );
							window.onbeforeunload = null;
							return;
						}
						sendRequest();
					}
				});
			}, 1000 );

			window.addEventListener( 'beforeunload', function( e ) {
				e.preventDefault();

				e.returnValue = 'Warning! Please don\'t close or reload this page.';
			} );
		}

		if ( document.readyState === "complete" ) {
			sendRequest.apply( win );
		} else {
			window.addEventListener( 'load', sendRequest );
		}
	})( window, document );

</script>
