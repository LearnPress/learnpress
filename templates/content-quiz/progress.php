<?php
/**
 * Template for displaying progress of current quiz user are doing.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/progress.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.8
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$user        = LP_Global::user();
$quiz        = LP_Global::course_item_quiz();
$course_data = $user->get_course_data( get_the_ID() );
$quiz_item   = $course_data->get_item_quiz( $quiz->get_id() );
$quiz_data   = $user->get_quiz_data( $quiz->get_id() );

if ( $quiz_data->is_review_questions() ) {
	return;
}

if ( false === $quiz->get_duration() ) {
	return;
}

$result  = $quiz_data->get_results();
$percent = $quiz_data->get_questions_answered( true );

?>

<div class="quiz-progress">
    <div class="progress-items">
        <div class="progress-item quiz-current-question">
            <span class="progress-number">
				<?php echo sprintf( __( '%d/%d', 'learnpress' ), $quiz->get_question_index( $quiz_data->get_current_question(), 1 ), $quiz_data->get_total_questions() ); ?>
            </span>
            <span class="progress-label">
				<?php _e( 'Question', 'learnpress' ); ?>
            </span>
        </div>
        <div class="progress-item quiz-countdown">
            <span class="progress-number"> <?php _e( 'Unlimited', 'learnpress' ); ?> </span>
            <span class="progress-label">
				<?php if ( $quiz->get_duration()->get_seconds() ) {
					_e( 'Time remaining', 'learnpress' );
				} else {
					_e( 'Duration Unlimited', 'learnpress' );
				} ?>
            </span>
        </div>
    </div>
</div>