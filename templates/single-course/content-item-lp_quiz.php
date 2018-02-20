<?php
/**
 * Template for displaying content of the quiz
 *
 * @author ThimPress
 */
$user   = learn_press_get_current_user();
$course = LP()->global['course'];
$quiz   = isset( $item ) ? $item : LP()->global['course-item'];
if ( !$quiz ) {
	return;
}

$have_questions = $quiz->get_questions();
$can_view_item  = $user->can( 'view-item', $quiz->id, $course->id );

?>
<div class="content-item-quiz">
	<div id="content-item-<?php echo $quiz->id; ?>">
		<div class="learn-press-content-item-title content-item-quiz-title">
			<?php if ( false !== ( $item_quiz_title = apply_filters( 'learn_press_item_quiz_title', $quiz->title ) ) ): ?>
				<h4><?php echo $item_quiz_title; ?></h4>
			<?php endif; ?>
			<a href="" class="lp-expand dashicons-editor-expand dashicons"></a>
			<?php $have_questions && learn_press_get_template( 'content-quiz/countdown-simple.php' ); ?>
		</div>

		<div id="quiz-<?php echo $quiz->id; ?>" class="learn-press-content-item-summary">
			<?php if ( $user->has_quiz_status( array( 'completed' ), $quiz->id, $course->id ) ): ?>
				<?php learn_press_get_template( 'content-quiz/description.php' ); ?>
				<?php learn_press_get_template( 'content-quiz/intro.php' ); ?>
				<?php learn_press_get_template( 'content-quiz/result.php' ); ?>

			<?php elseif ( $user->has( 'quiz-status', 'started', $quiz->id, $course->id ) ): ?>
				<?php if ( $have_questions ): ?>
					<?php learn_press_get_template( 'content-quiz/question-content.php' ); ?>
				<?php endif; ?>
			<?php else: ?>

				<?php learn_press_get_template( 'content-quiz/description.php' ); ?>
				<?php learn_press_get_template( 'content-quiz/intro.php' ); ?>

			<?php endif; ?>

			<?php //if ( $have_questions ) { ?>
				<?php learn_press_get_template( 'content-quiz/buttons.php' ); ?>
			<?php // } ?>
		</div>

	</div>
	<?php if ( $have_questions ) { ?>
		<?php learn_press_get_template( 'content-quiz/history.php' ); ?>
		<?php learn_press_get_template( 'content-quiz/questions.php' ); ?>
	<?php } else { ?>
		<?php learn_press_display_message( __( 'No questions', 'learnpress' ) ); ?>
	<?php } ?>

	<?php LP_Assets::add_var( 'LP_Quiz_Params', wp_json_encode( $quiz->get_settings( $user->id, $course->id ) ), '__all' ); ?>

</div>