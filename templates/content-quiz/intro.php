<?php
/**
 * Template for displaying introduction of quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/intro.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
$quiz   = LP_Global::course_item_quiz();
$count  = $quiz->get_retake_count();
?>

<ul class="quiz-intro">
    <li>
        <label><?php _e( 'Attempts allowed', 'learnpress' ); ?></label>
        <span><?php echo ( null == $count || 0 > $count ) ? __( 'Unlimited', 'learnpress' ) : ( $count ? $count : __( 'No', 'learnpress' ) ); ?></span>
    </li>
    <li>
        <label><?php _e( 'Duration', 'learnpress' ); ?></label>
        <span><?php echo $quiz->get_duration_html(); ?></span>
    </li>
    <li>
        <label><?php _e( 'Passing grade', 'learnpress' ); ?></label>
        <span><?php echo sprintf( '%d%%', $quiz->get_passing_grade() ); ?></span>
    </li>
    <li>
        <label><?php _e( 'Questions', 'learnpress' ); ?></label>
        <span><?php echo $quiz->count_questions(); ?></span>
    </li>
</ul>
