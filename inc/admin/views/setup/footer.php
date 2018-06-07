<?php
/**
 * Template for displaying footer of setup wizard.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;
do_action('admin_print_footer_scripts');
do_action('admin_footer');
?>
<footer>
	<?php printf( __( 'LearnPress %s. Designed by @ThimPress.', 'learnpress' ), LEARNPRESS_VERSION ); ?>
</footer>
</div>
</body>
</html>