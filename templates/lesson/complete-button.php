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

if ( !( $lesson = $course->current_lesson ) ) {
	return;
}
?>

<?php if ( LP()->user->has('completed-lesson', $lesson->id) ) { ?>

	<?php
	echo apply_filters(
		'learn_press_user_completed_lesson_button',
		sprintf(
			'<span class="complete-lesson-button completed" data-id="%s" data-nonce="%s" disabled="disabled"><span class="dashicons dashicons-yes"></span>%s</span>',
				$lesson->id,
				wp_create_nonce( 'learn-press-complete-lesson-' . $lesson->id ),
				__( 'Completed', 'learnpress' )
		)
	);
	?>

<?php } else { ?>

	<?php if ( !LP()->user->has( 'finished-course', $course->id ) && LP()->user->has( 'enrolled-course', $course->id ) ) { ?>

		<button class="complete-lesson-button" data-id="<?php print_r( $lesson->id );?>" data-nonce="<?php echo wp_create_nonce( 'learn-press-complete-lesson-' . $lesson->id );?>"><?php _e( 'Complete Lesson', 'learnpress' );?></button>

	<?php } ?>

<?php } ?>