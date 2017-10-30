<?php
/*
 * Template for displaying the status of course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = learn_press_get_course();
$user   = learn_press_get_current_user();

if ( ! $user->has( 'purchased-course', $course->get_id() ) ) {
	return;
}

$status = $user->get_course_status( $course->get_id() );
?>
<span class="course-status <?php echo sanitize_title( $status ); ?>"><?php echo ucfirst( $status ); ?></span>
