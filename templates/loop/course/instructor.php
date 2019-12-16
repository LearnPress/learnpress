<?php
/**
 * Template for displaying instructor of course within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/instructor.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.3.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! $course = learn_press_get_course() ) {
	return;
}
?>

<span class="course-instructor">
	<?php echo $course->get_instructor_html(); ?>
</span>
