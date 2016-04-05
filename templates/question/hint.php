<?php
$quiz = LP()->quiz;
$user = LP()->user;

if ( !$quiz || $user->get_quiz_status( $quiz->id ) != 'started' ) {
	return;
}

if ( empty( $quiz->current_question ) ) {
	return;
}

$hint = apply_filters( 'learn_press_question_hint', get_post_meta( $quiz->current_question->id, '_lp_hint', true ) );

if ( !$hint ) {
	return;
}
?>
<div id="learn-press-question-hint-<?php echo $quiz->current_question->id; ?>" class="question-hint-content hide-if-js" data-title="<?php echo esc_attr( __( 'Hint', 'learnpress' ) ); ?>">
	<?php echo $hint; ?>
</div>