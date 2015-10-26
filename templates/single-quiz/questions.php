<?php
/**
 * Template for displaying the list of questions for the quiz
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $quiz;
?>

<?php if( $quiz->has( 'questions' ) ): ?>

	<div class="quiz-questions" id="learn-press-quiz-questions">

		<?php do_action( 'learn_press_quiz_question_nav' ); ?>
		<ul>
			<?php for($i = 1; $i <= 10; $i++ ){?>
				<li>Question #<?php echo $i;?></li>
			<?php }?>
		</ul>
	</div>

<?php else: ?>

	<?php learn_press_display_message( apply_filters( 'learn_press_quiz_no_questions_notice', __( 'This quiz hasn\'t got any questions', 'learn_press' ) ) ); ?>

<?php endif; ?>






