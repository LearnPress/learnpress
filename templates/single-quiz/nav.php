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

global $quiz;

if ( !$quiz->has( 'questions' ) ) {
	return;
}

$status = LP()->user->get_quiz_status( $quiz->id );

?>

<?php if( $status != 'completed' ){ ?>

<div class="quiz-question-nav-buttons">

	<button type="button" data-nav="check" class="check-question hide-if-js">
		<?php echo apply_filters( 'learn_press_button_check_question_text', __( 'Check', 'learnpress' ) ); ?>
	</button>

	<button type="button" data-nav="prev" class="prev-question hide-if-js">
		<?php echo apply_filters( 'learn_press_button_back_question_text', __( 'Back', 'learnpress' ) ); ?>
	</button>

	<button type="button" data-nav="next" class="next-question hide-if-js">
		<?php echo apply_filters( 'learn_press_quiz_question_nav_button_next_text', __( 'Next', 'learnpress' ) ); ?>
	</button>

	<button class="button-finish-quiz btn hide-if-js" quiz-id="<?php echo get_the_ID() ?>" data-area="nav">
		<?php echo apply_filters( 'learn_press_button_finish_quiz_text', __( "Finish", "learn_press" ) );?>
	</button>

</div>

<?php }?>