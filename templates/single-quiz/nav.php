<?php
/**
 * Template for displaying the questions navigation
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$quiz = LP()->quiz;

if ( !$quiz || !$quiz->has( 'questions' ) ) {
	return;
}

$status = LP()->user->get_quiz_status( $quiz->id );

?>

<?php if ( $status != 'completed' ) { ?>

	<div class="quiz-question-nav-buttons">

		<button type="button" data-nav="prev" class="prev-question hide-if-js">
			<?php echo apply_filters( 'learn_press_button_back_question_text', __( 'Back', 'learnpress' ) ); ?>
		</button>

		<button type="button" data-nav="next" class="next-question hide-if-js">
			<?php echo apply_filters( 'learn_press_quiz_question_nav_button_next_text', __( 'Next', 'learnpress' ) ); ?>
		</button>

		<?php if ( $quiz->show_hint == 'yes' ): ?>
			<button type="button" data-nav="hint" class="hint-question hide-if-js">
				<?php echo apply_filters( 'learn_press_button_hint_question_text', __( 'Hint', 'learnpress' ) ); ?>
			</button>
		<?php endif; ?>

		<?php if ( $quiz->show_explanation == 'yes' ): ?>
			<button type="button" data-nav="explanation" class="explain-question hide-if-js">
				<?php echo apply_filters( 'learn_press_button_explain_question_text', __( 'Explain', 'learnpress' ) ); ?>
			</button>
		<?php endif; ?>

		<?php if ( $quiz->show_check_answer == 'yes' ): ?>
			<button type="button" data-nav="check" class="check-question hide-if-js">
				<?php echo apply_filters( 'learn_press_button_check_question_text', __( 'Check', 'learnpress' ) ); ?>
			</button>
		<?php endif; ?>

		<button class="button-finish-quiz btn hide-if-js" quiz-id="<?php echo get_the_ID() ?>" data-area="nav">
			<?php echo apply_filters( 'learn_press_button_finish_quiz_text', __( "Finish", "learn_press" ) ); ?>
		</button>

	</div>

<?php } ?>