<?php
$quiz = LP()->global['course-item'];
$user = LP()->user;
if ( !$quiz || $user->get_quiz_status( $quiz->id ) != 'started' ) {
	return;
}
$question = $quiz->get_current_question( $user->id );

if ( !$question ) {
	return;
}

$hint = apply_filters( 'learn_press_question_hint', get_post_meta( $question->id, '_lp_hint', true ) );

if ( !$hint ) {
	return;
}
?>
<div id="learn-press-question-hint-<?php echo $question->id; ?>" class="question-hint-content hide-if-js" data-title="<?php echo esc_attr( __( 'Hint', 'learnpress' ) ); ?>">
	<?php echo $hint; ?>
</div>