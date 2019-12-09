<?php
/**
 * Template for displaying course sidebar.
 *
 * @version 4.0.0
 * @author  ThimPress
 * @package LearnPress/Templates
 */

defined( 'ABSPATH' ) or die;

ob_start();
dynamic_sidebar( 'course-sidebar' );
$output = ob_get_clean();

/**
 * Hide sidebar if there is no content
 */
if ( ! $output && ! LP()->template( 'course' )->has_sidebar() ) {
	return;
}

?>
<aside class="course-summary-sidebar">

    <div class="course-summary-sidebar__inner">
        <div class="course-sidebar-top">
            <?php

            do_action( 'learn-press/before-course-summary-sidebar' );

            if ( ! $output ) {
                do_action( 'learn-press/course-summary-sidebar' );
            } else {
                echo $output;
            }
            ?>
        </div>

        <?php
		    do_action( 'learn-press/after-course-summary-sidebar' );
		?>

    </div>
</aside>
