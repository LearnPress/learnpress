<?php
/*
 * Template for displaying the status of course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $course;

$user = learn_press_get_current_user();

if ( !$user->has( 'purchased-course', $course->id ) ) {
	return;
}
?>
<span class="learn-press-course-status"><?php echo $user->get_course_status( $course->id ); ?></span>
