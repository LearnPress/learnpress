<?php
/**
 * Template for displaying content of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>
<div class="course-content">
	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_course_content_summary' );

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/course-content-summary' );
	?>
</div>
