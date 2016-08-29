<?php
/**
 * Template for displaying content of the quiz
 *
 * @author ThimPress
 */
$course = LP()->global['course'];
$user   = learn_press_get_current_user();
$item   = isset( $item ) ? $item : LP()->global['course-item'];
$force  = isset( $force ) ? $force : false;
if ( !$item ) {
	return;
}
$quiz = LP_Quiz::get_quiz( $item->ID );
?>

<h4 class="learn-press-content-item-title"><?php echo $item->title; ?></h4>
<div class=""><?php echo $item->content;?></div>
<div itemscope id="quiz-<?php echo $item->ID; ?>" <?php learn_press_quiz_class( 'learn-press-content-item-summary' ); ?>>

	<?php if ( $user->has( 'quiz-status', 'completed', $item->id, $course->id ) ): ?>
		<?php learn_press_get_template( 'quiz/result.php', array( 'force' => $force, 'quiz' => $quiz ) ); ?>
	<?php elseif ( $user->has( 'quiz-status', 'started', $item->id, $course->id ) ): ?>
		<?php learn_press_get_template( 'quiz/question-content.php', array( 'force' => $force, 'quiz' => $quiz ) ); ?>
		<?php learn_press_get_template( 'quiz/countdown.php', array( 'force' => $force ) ); ?>
	<?php else: ?>
		<?php learn_press_get_template( 'quiz/description.php' ); ?>
		<?php learn_press_get_template( 'quiz/intro.php', array( 'force' => $force ) ); ?>
	<?php endif; ?>
	<?php learn_press_get_template( 'quiz/buttons.php', array( 'force' => $force ) ); ?>
	<?php learn_press_get_template( 'quiz/questions.php', array( 'force' => $force ) ); ?>

</div>
<script>
	window.Quiz_Params = <?php echo json_encode( $quiz->get_settings(), LP()->settings->get( 'debug' ) == 'yes' ? JSON_PRETTY_PRINT : '' );?>;
</script>

