<?php
/**
 * Template for displaying the content of current question
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 2.0.7
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
/*
if ( !is_user_logged_in() ) {
	learn_press_display_message( sprintf( __( 'You are not logged in and result', 'learnpress' ), learn_press_get_login_url() ), 'error' );
}*/

$history = $user->get_quiz_results( $quiz->id );
?>
<div class="quiz-result lp-group-content-wrap">
	<h4><?php echo esc_html( sprintf( __( 'You have reached %d of %d points (%s)', 'learnpress' ), $history->mark, $quiz->get_mark(), round( $history->mark_percent ) . '%' ) ); ?></h4>
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
			<div class="quiz-result-field <?php echo $class; ?>" data-value="<?php echo absint( $value ); ?>">
				<?php echo $text; ?>
				<span data-text="<?php echo esc_attr( $text ); ?>"></span>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( $quiz->duration > 0 ): ?>
		<p class="quiz-result-time">
			<?php echo sprintf( __( 'Your time: %s', 'learnpress' ), learn_press_seconds_to_time( $history->time ) ); ?>
		</p>
	<?php endif; ?>
	<?php if ( $grade = $user->get_quiz_graduation( $quiz->id, $course->id ) ): ?>
		<?php $grade_text = learn_press_get_graduation_text($grade);?>
		<div class="quiz-grade">
			<p><?php echo sprintf( __( 'Your quiz grade <span class="%s">%s</span>', 'learnpress' ), $grade, $grade_text ); ?></p>
			<?php if ( 'point' == $quiz->passing_grade_type ) { ?>
				<p><?php echo sprintf( __( 'Quiz requirement <span>%s</span>', 'learnpress' ), $quiz->passing_grade ); ?></p>
			<?php } elseif ( 'percentage' == $quiz->passing_grade_type ) { ?>
				<p><?php echo sprintf( __( 'Quiz requirement <span>%s</span>', 'learnpress' ), round( $quiz->passing_grade ) . '%' ); ?></p>
			<?php } ?>
		</div>
	<?php endif; ?>
	<div class="clearfix"></div>
</div>
