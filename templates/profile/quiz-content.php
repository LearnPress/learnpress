<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();

$result = learn_press_get_quiz_result( $user_id, $quiz_id );
?>

<div class="quiz-result">
	<div class="quiz-title">
		<a href="<?php echo get_permalink( $quiz_id ) ?>"><?php echo get_the_title( $quiz_id ); ?></a>
	</div>
	<div class="quiz-result-mark">
		<span class="quiz-mark"><?php echo $result['mark']; ?>
			<small>/ <?php echo $result['mark_total']; ?></small></span>
		<small><?php _e( 'point', 'learnpress' ); ?></small>
	</div>
	<div class="quiz-result-summary">
		<div class="quiz-result-field correct">
			<label><?php echo apply_filters( 'learn_press_quiz_result_correct_text', __( 'Correct', 'learnpress' ) ); ?></label>
			<?php printf( "%d (%0.2f%%)", $result['correct'], $result['correct_percent'] ); ?>
		</div>
		<div class="quiz-result-field wrong">
			<label><?php echo apply_filters( 'learn_press_quiz_result_wrong_text', __( 'Wrong', 'learnpress' ) ); ?></label>
			<?php printf( "%d (%0.2f%%)", $result['wrong'], $result['wrong_percent'] ); ?>
		</div>
		<div class="quiz-result-field empty">
			<label><?php echo apply_filters( 'learn_press_quiz_result_empty_text', __( 'Empty', 'learnpress' ) ); ?></label>
			<?php printf( "%d (%0.2f%%)", $result['empty'], $result['empty_percent'] ); ?>
		</div>
		<div class="quiz-result-field time">
			<label><?php echo apply_filters( 'learn_press_quiz_result_time_text', __( 'Time', 'learnpress' ) ); ?></label>
			<?php echo learn_press_seconds_to_time( $result['quiz_time'] ); ?>
		</div>
	</div>
</div>
