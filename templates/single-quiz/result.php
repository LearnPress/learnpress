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
if ( !LP()->user->has( 'completed-quiz', $quiz->id ) ) {
	return;
}

if( !is_user_logged_in() ){
	learn_press_display_message( sprintf( __( 'You are not logged in! Please <a href="%s">login</a> to save the results. The results will be deleted after your session destroyed', 'learnpress' ), learn_press_get_login_url() ), 'error' );
}

$history = LP()->user->get_quiz_results( $quiz->id );

?>
<div class="quiz-result">
	<h4 class="result-title"><?php _e( 'Your result', 'learnpress' ); ?></h4>

	<div class="quiz-result-mark">
		<span class="quiz-mark"><?php echo $history->results['mark']; ?>
			<small>/ <?php echo $history->results['quiz_mark']; ?></small></span>
		<small><?php _e( 'point', 'learnpress' ); ?></small>
	</div>
	<div class="quiz-result-summary">
		<div class="quiz-result-field correct">
			<label><?php echo apply_filters( 'learn_press_quiz_result_correct_text', __( 'Correct', 'learnpress' ) ); ?></label>
			<?php printf( "%d (%0.2f%%)", $history->results['correct'], $history->results['correct_percent'] ); ?>
		</div>
		<div class="quiz-result-field wrong">
			<label><?php echo apply_filters( 'learn_press_quiz_result_wrong_text', __( 'Wrong', 'learnpress' ) ); ?></label>
			<?php printf( "%d (%0.2f%%)", $history->results['wrong'], $history->results['wrong_percent'] ); ?>
		</div>
		<div class="quiz-result-field empty">
			<label><?php echo apply_filters( 'learn_press_quiz_result_empty_text', __( 'Empty', 'learnpress' ) ); ?></label>
			<?php printf( "%d (%0.2f%%)", $history->results['empty'], $history->results['empty_percent'] ); ?>
		</div>
		<div class="quiz-result-field time">
			<label><?php echo apply_filters( 'learn_press_quiz_result_time_text', __( 'Time', 'learnpress' ) ); ?></label>
			<?php echo learn_press_seconds_to_time( $history->results['user_time'] ); ?>
		</div>
	</div>
	<div class="clearfix"></div>
</div>
<?php
//learn_press_debug($history);
