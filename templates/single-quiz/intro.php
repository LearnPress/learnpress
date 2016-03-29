<?php
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$quiz = LP()->quiz;
if ( !$quiz ) {
	return;
}
if ( LP()->user->get_quiz_status( $quiz->id ) != '' ) {
	return;
}
?>
<div class="quiz-intro">
	<form method="post" name="learn-press-quiz-intro">
		<ul>
			<li>
				<label><?php _e( 'Attempts allowed:', 'learnpress' ); ?></label>
				<?php echo $quiz->retake_count; ?>
			</li>
			<li>
				<label><?php _e( 'Duration:', 'learnpress' ); ?></label>
				<?php echo $quiz->get_duration_html(); ?>
			</li>
			<li>
				<label><?php _e( 'Questions:', 'learnpress' ); ?></label>
				<?php echo $quiz->get_total_questions(); ?>
			</li>
		</ul>
		<?php do_action( 'learn_press_quiz_intro_fields' ); ?>
	</form>
</div>
