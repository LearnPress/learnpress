<<<<<<< HEAD
<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();

if ( !isset( $question_id ) ) {
	return;
}

if ( !learn_press_is_yes( LP()->global['course-item']->show_result ) || !LP()->user->has( 'completed-quiz', LP()->global['course-item']->id ) ) {
	return;
}

if ( !has_action( 'learn_press_quiz_question_display_hint_' . $question_id ) ) {
	return;
}
?>
<div class="learn-press-question-answer">

	<?php do_action( 'learn_press_quiz_question_display_hint_' . $question_id, get_the_ID(), get_current_user_id() ); ?>

</div>
=======
<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();

if ( !isset( $question_id ) ) {
	return;
}

if ( !learn_press_is_yes( LP()->global['course-item']->show_result ) || !LP()->user->has( 'completed-quiz', LP()->global['course-item']->id ) ) {
	return;
}

if ( !has_action( 'learn_press_quiz_question_display_hint_' . $question_id ) ) {
	return;
}
?>
<div class="learn-press-question-answer">

	<?php do_action( 'learn_press_quiz_question_display_hint_' . $question_id, get_the_ID(), get_current_user_id() ); ?>

</div>
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
