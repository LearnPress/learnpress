<?php
$quiz = LP()->quiz;
$user = LP()->user;

if ( !$quiz || $user->get_quiz_status( $quiz->id ) != 'started' ) {
	return;
}

if ( empty( $quiz->current_question ) ) {
	return;
}

$explanation = apply_filters( 'learn_press_question_explanation', get_post_meta( $quiz->current_question->id, '_lp_explanation', true ) );

if ( !$explanation ) {
	return;
}
?>
<div id="learn-press-question-explanation-<?php echo $quiz->current_question->id; ?>" class="question-explanation-content hide-if-js">
	<?php echo $explanation; ?>
</div>