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

<div class="quiz-question-nav-buttons">

	<?php if( $status != 'completed' ){ ?>

	<button type="button" data-nav="prev" class="prev-question" data-url="<?php //echo $prev; ?>">
		<?php echo apply_filters( 'learn_press_quiz_question_nav_button_back_title', __( 'Back', 'learnpress' ) ); ?>
	</button>

	<button type="button" data-nav="next" class="next-question" data-url="<?php //echo $next; ?>">
		<?php echo apply_filters( 'learn_press_quiz_question_nav_button_next_title', __( 'Next', 'learnpress' ) ); ?>
	</button>

	<button class="button-finish-quiz btn hidden" quiz-id="<?php echo get_the_ID() ?>" data-area="nav">
		<?php echo apply_filters( 'learn_press_finish_quiz_text', __( "Finish", "learn_press" ) );?>
	</button>

	<?php }?>
</div>
