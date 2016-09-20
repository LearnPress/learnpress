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
$user   = learn_press_get_current_user();
$course = LP()->global['course'];
$quiz   = LP()->global['course-item'];
if ( !$user->has( 'completed-quiz', $quiz->id ) ) {
	return;
}

if ( !is_user_logged_in() ) {
	learn_press_display_message( sprintf( __( 'You are not logged in! Please <a href="%s">login</a> to save the results. The results will be deleted after your session destroyed', 'learnpress' ), learn_press_get_login_url() ), 'error' );
}

$history = LP()->user->get_quiz_results( $quiz->id );
?>
<div class="quiz-result">
	<h4 class="result-title"><?php _e( 'Your result', 'learnpress' ); ?></h4>
	<!--<div class="quiz-result-mark">
		<div class="progress-circle">
			<div class="background">
				<div class="fill"></div>
			</div>
			<div class="inside">
				<span class="quiz-mark">
					<?php echo $history->mark; ?>
					<small>/ <?php echo $quiz->get_mark(); ?></small>
				</span>
				<small><?php _e( 'point', 'learnpress' ); ?></small>
			</div>
		</div>
	</div>-->
	<h4><?php echo esc_html( sprintf( __( 'You have reached %d of %d points', 'learnpress' ), $history->mark, $quiz->get_mark() ) ); ?></h4>
	<?php
	$fields = array(
		'correct' => sprintf( apply_filters( 'learn_press_quiz_result_correct_text', __( 'Correct %d (%0.0f%%)', 'learnpress' ) ), $history->correct, $history->correct_percent ),
		'wrong'   => sprintf( apply_filters( 'learn_press_quiz_result_wrong_text', __( 'Wrong %d (%0.0f%%)', 'learnpress' ) ), $history->wrong, $history->wrong_percent ),
		'empty'   => sprintf( apply_filters( 'learn_press_quiz_result_empty_text', __( 'Empty %d (%0.0f%%)', 'learnpress' ) ), $history->empty, $history->empty_percent )
	)
	?>
	<div class="quiz-result-summary">
		<?php foreach ( $fields as $class => $text ): ?>
			<?php $value = apply_filters( 'learn_press_quiz_result_' . $class . '_value', $history->{$class . '_percent'}, $history, $quiz, $course ); ?>
			<div class="quiz-result-field <?php echo $class; ?>" data-value="<?php echo $value; ?>">
				<?php echo $text; ?>
				<span data-text="<?php echo esc_attr( $text ); ?>"></span>
			</div>
		<?php endforeach; ?>
		<div class="quiz-result-field time">
			<label><?php echo apply_filters( 'learn_press_quiz_result_time_text', __( 'Time', 'learnpress' ) ); ?></label>
			<?php echo learn_press_seconds_to_time( $history->time ); ?>
		</div>
	</div>
	<div class="clearfix"></div>
</div>
