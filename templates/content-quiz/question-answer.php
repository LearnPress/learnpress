<?php
/**
 * Template for displaying question answer in quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/question-answer.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
if ( ! isset( $question_id ) ) {
	return;
}

if ( ! learn_press_is_yes( LP()->global['course-item']->show_result ) || ! LP()->user->has( 'completed-quiz', LP()->global['course-item']->id ) ) {
	return;
}

if ( ! has_action( 'learn_press_quiz_question_display_hint_' . $question_id ) ) {
	return;
}
?>

<div class="learn-press-question-answer">

	<?php do_action( 'learn_press_quiz_question_display_hint_' . $question_id, get_the_ID(), get_current_user_id() ); ?>

</div>
