<?php
/**
 * Template for displaying the content of current question
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
$user = learn_press_get_current_user();
?>
<div class="quiz-question-content">
	<form method="post" action="">
	<?php
	if ( $quiz->current_question ):
		$question_answers = $user->get_question_answers( $quiz->id, $quiz->current_question->id );

		$quiz->current_question->render( array( 'answered' => $question_answers ) );
	endif;
	?>
	</form>
</div>