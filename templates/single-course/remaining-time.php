<?php
/**
 * Template for displaying the remaining time for course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.2.0
 */

defined( 'ABSPATH' ) or die();

$user = LP_Global::user();
$course = LP_Global::course();

if ( ! $user->has_enrolled_course( $course->get_id() ) ) {
	return;
}

?>
<div class="course-remaining-time">
    <p>
		<?php learn_press_label_html( __( 'Enrolled', 'learnpress' ), 'enrolled' ); ?>
		<?php
		if ( isset( $remaining_time ) && $course->get_duration() ) {
			echo sprintf( __( 'You have %s remaining for the course', 'learnpress' ), $remaining_time );
		} ?>
    </p>
</div>
