<?php
/**
 * Template for displaying course content within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-course.php
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<li id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="course-item">
		<?php

		/**
		 * LP Hook
		 *
		 * @since 3.0.0
		 *
		 * @called loop/course/thumbnail.php
		 * @echo DIV tag
		 */
		do_action( 'learn-press/before-courses-loop-item' );
        ?>


        <?php
        /**
         * @since  3.0.0
         *
         * @called loop/course/title.php
         */
        do_action('learn-press/courses-loop-item-title');
        ?>


        <?php

		/**
		 * LP Hook
		 *
		 * @since 3.0.0
		 *
		 * @see LP_Template_Course::courses_loop_item_meta()
		 * @see LP_Template_Course::courses_loop_item_info_begin()
		 * @see LP_Template_Course::clearfix()
		 * @see LP_Template_Course::courses_loop_item_price()
		 * @see LP_Template_Course::courses_loop_item_info_end()
		 */
		do_action( 'learn-press/after-courses-loop-item' );

		?>
	</div>
</li>
