<?php
/**
 * Template for displaying content of quiz's question
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 */
$user   = learn_press_get_current_user();
$course = LP()->global['course'];
$quiz   = isset( $item ) ? $item : LP()->global['course-item'];
if ( !$quiz ) {
	return;
}
$question = $quiz->get_current_question();
if ( !$question ) {
	return;
}
?>
<?php if ( false !== ( $title = apply_filters( 'learn_press_quiz_question_title', $question->get_title() ) ) ): ?>
	<h4 class="quiz-question-title"><?php echo $title; ?></h4>
<?php endif; ?>
<div class="quiz-question-content">
	<?php if ( false !== ( $content = apply_filters( 'learn_press_quiz_question_content', $question->get_content() ) ) ): ?>
		<div class="question-content">
			<?php echo $content; ?>
		</div>
	<?php endif; ?>
	<?php
	$question->render();
	?>
	<?php learn_press_get_template( 'question/hint.php', array( 'quiz' => $quiz ) ); ?>
</div>
