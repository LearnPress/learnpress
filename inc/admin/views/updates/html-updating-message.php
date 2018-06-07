<?php
/**
 * Template for displaying message when LP updating to latest version
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.8
 */
defined( 'ABSPATH' ) or die();

?>
<div class="notice notice-warning lp-notice-update-database do-updating">
    <p>
		<?php _e( '<strong>LearnPress update</strong> – We are running updater to upgrade your database to the latest version.', 'learnpress' ); ?>
    </p>
</div>

<script type="text/javascript">
    (function (win, doc) {
        var t = null;

        function sendRequest() {
            t = setTimeout(function () {
                var $ = jQuery;
                $.ajax({
                    url: '',
                    data: {
                        'lp-ajax': 'check-updated'
                    },
                    success: function (response) {
                        response = LP.parseJSON(response);
                        if (response.result === 'success') {
                            clearInterval(t);
                            $('.lp-notice-update-database.do-updating').replaceWith($(response.message));
                            return;
                        }

                        sendRequest();
                    }
                });
            }, 3000);
        }

        if (document.readyState === "complete") {
            sendRequest.apply(win);
        } else {
            window.addEventListener('load', sendRequest);
        }
    })(window, document)

</script>