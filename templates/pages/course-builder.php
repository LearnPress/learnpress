<?php
/**
 * Template for displaying main course builder page.
 *
 * @author   VuxMinhThanh
 * @package  Learnpress/Templates
 * @version  4.3.0
 */

defined( 'ABSPATH' ) || exit();
wp_head();

?>
	<div id="lp-course-builder">
		<div class="lp-course-builder_layout">
			<?php do_action( 'learn-press/course-builder/layout' ); ?>
		</div>
	</div>
<?php
wp_footer();
