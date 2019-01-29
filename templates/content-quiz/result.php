<?php
/**
 * Template for displaying quiz result.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/result.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$user      = LP_Global::user();
$quiz      = LP_Global::course_item_quiz();
$quiz_data = $user->get_quiz_data( $quiz->get_id() );
$result    = $quiz_data->get_results( false );

if ( $quiz_data->is_review_questions() ) {
	return;
} ?>

<div class="quiz-result <?php echo esc_attr( $result['grade'] ); ?>">

    <h3><?php _e( 'Your Result', 'learnpress' ); ?></h3>

    <div class="result-grade">
        <span class="result-achieved"><?php echo $quiz_data->get_percent_result(); ?></span>
        <span class="result-require"><?php echo $quiz->get_passing_grade(); ?></span>
        <p class="result-message"><?php echo sprintf( __( 'Your grade is <strong>%s</strong>', 'learnpress' ), $result['grade'] == '' ? __( 'Ungraded', 'learnpress' ) : $result['grade_text'] ); ?> </p>
    </div>

    <ul class="result-statistic">
        <li class="result-statistic-field">
            <label><?php echo _x( 'Time spend', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $result['time_spend']; ?></p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Point', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $result['user_mark'] . ' / ' . $result['mark']; ?></p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Questions', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $quiz->count_questions(); ?></p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Correct', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $result['question_correct']; ?></p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Wrong', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $result['question_wrong']; ?></p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Skipped', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $result['question_empty']; ?></p>
        </li>
    </ul>

</div>