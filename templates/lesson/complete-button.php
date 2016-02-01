<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

global $course;
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

print_r( $course->evaluate_course_results() );
if ( !( $lesson = $course->current_lesson ) ) {
	return;
}
?>

<?php if ( LP()->user->has('completed-lesson', $lesson->id) ) { ?>

	<?php learn_press_display_message( __( 'Congratulations! You have completed this lesson.', 'learn_press' ) ); ?>

<?php } else { ?>

	<?php if ( !LP()->user->has( 'finished-course', $course->id ) && LP()->user->has( 'enrolled-course', $course->id ) ) { ?>

		<button class="complete-lesson-button" data-id="<?php print_r( $lesson->id );?>" data-nonce="<?php echo wp_create_nonce( 'learn-press-complete-lesson-' . $lesson->id );?>"><?php _e( 'Complete Lesson', 'learn_press' );?></button>

	<?php } ?>

<?php } ?>