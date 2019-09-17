<?php
/**
 * Created by PhpStorm.
 * User: tu
 * Date: 9/16/19
 * Time: 5:05 PM
 */
?>
<aside class="course-summary-sidebar">
	<?php
	ob_start();
	dynamic_sidebar( 'course-sidebar' );
	$output = ob_get_clean();

	do_action( 'learn-press/before-course-summary-sidebar' );

	if ( ! $output ) {
		do_action( 'learn-press/course-summary-sidebar' );
	}else {
		echo $output;
	}

	do_action( 'learn-press/after-course-summary-sidebar' );
	?>
</aside>
