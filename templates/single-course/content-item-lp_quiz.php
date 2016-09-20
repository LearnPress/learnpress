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
?>
<div class="learn-press-content-item-title content-item-quiz-title">
	<?php if ( false !== ( $item_quiz_title = apply_filters( 'learn_press_item_quiz_title', $quiz->title ) ) ): ?>
		<h4><?php echo $item_quiz_title; ?></h4>
	<?php endif; ?>
	<?php learn_press_get_template( 'quiz/countdown-simple.php' ); ?>
</div>
<div itemscope id="quiz-<?php echo $quiz->id; ?>" <?php learn_press_quiz_class( 'learn-press-content-item-summary' ); ?>>

	<?php if ( $user->has_quiz_status( array( 'completed' ), $quiz->id, $course->id ) ): ?>

		<?php learn_press_get_template( 'quiz/result.php' ); ?>

	<?php elseif ( $user->has( 'quiz-status', 'started', $quiz->id, $course->id ) ): ?>

		<?php learn_press_get_template( 'quiz/question-content.php' ); ?>
		<?php //learn_press_get_template( 'quiz/countdown.php' ); ?>

	<?php else: ?>

		<?php learn_press_get_template( 'quiz/description.php' ); ?>
		<?php learn_press_get_template( 'quiz/intro.php' ); ?>

	<?php endif; ?>
	<?php learn_press_get_template( 'quiz/buttons.php' ); ?>
	<?php learn_press_get_template( 'quiz/questions.php' ); ?>

</div>
<script>
	window.Quiz_Params = <?php echo json_encode( $quiz->get_settings(), LP()->settings->get( 'debug' ) == 'yes' ? JSON_PRETTY_PRINT : '' );?>;
</script>

