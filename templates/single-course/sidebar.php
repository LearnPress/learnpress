<?php
/**
 * Template for displaying course sidebar.
 *
 * @version 4.0.0
 * @author  ThimPress
 * @package LearnPress/Templates
 */

defined( 'ABSPATH' ) or die;
?>
<aside class="course-summary-sidebar">
	<?php
	ob_start();
	dynamic_sidebar( 'course-sidebar' );
	$output = ob_get_clean();

	do_action( 'learn-press/before-course-summary-sidebar' );

	if ( ! $output ) {
		do_action( 'learn-press/course-summary-sidebar' );
	} else {
		echo $output;
	}

	do_action( 'learn-press/after-course-summary-sidebar' );
	?>
</aside>
