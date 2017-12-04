<?php
/**
 * Template for displaying course content within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-single-course.php
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();


do_action( 'learn-press/before-main-content' );

do_action( 'learn-press/before-single-item' );

?>
<div id="learn-press-course" class="course-summary">
	<?php
	/**
	 * @since 3.0.0
	 *
	 * @see learn_press_course_curriculum_tab()
	 * @see learn_press_single_course_content_item()
	 */
	do_action( 'learn-press/single-item-summary' );
	?>
</div>
<?php

/**
 * @since 3.0.0
 */
do_action( 'learn-press/after-main-content' );

do_action( 'learn-press/after-single-course' );
