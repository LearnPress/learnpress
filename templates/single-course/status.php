<?php
/**
 * Template for displaying status of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/status.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();


$course = learn_press_get_course();
$user   = learn_press_get_current_user();

if ( ! $user->has_purchased_course( $course->get_id() ) ) {
	return;
}
?>

<?php $status = $user->get_course_status( $course->get_id() ); ?>

<span class="course-status <?php echo sanitize_title( $status ); ?>"><?php echo ucfirst( $status ); ?></span>
