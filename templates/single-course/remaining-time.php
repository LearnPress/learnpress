<?php
/**
 * Template for displaying the remaining time for course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.4
 */

defined( 'ABSPATH' ) or die();

$course = LP_Global::course();
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
