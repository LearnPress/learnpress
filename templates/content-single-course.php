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

/**
 * If course has set password
 */
if ( post_password_required() ) {
	echo get_the_password_form();

	return;
}

/**
 * LP Hook
 */
do_action( 'learn-press/before-single-course' );

?>
<div id="learn-press-course" class="course-summary">
	<?php
	/**
	 * @since 3.0.0
	 *
	 * @see learn_press_single_course_summary()
	 */
	do_action( 'learn-press/single-course-summary' );
	?>
</div>
<?php

/**
 * LP Hook
 */
do_action( 'learn-press/after-single-course' );