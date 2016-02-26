<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();

if( !isset( $question_id ) ){
	return;
}

if( !learn_press_is_yes( LP()->quiz->show_result ) || !LP()->user->has( 'completed-quiz', get_the_ID() ) ){
	return;
}

if( !has_action( 'learn_press_quiz_question_display_hint_' . $question_id ) ){
	return;
}
?>
<div class="learn-press-question-answer">

	<?php do_action( 'learn_press_quiz_question_display_hint_' . $question_id, get_the_ID(), get_current_user_id() ); ?>

</div>
