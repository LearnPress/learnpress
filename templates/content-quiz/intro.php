<?php
/**
 * Template for displaying quiz's introduction
 *
 * @package LearnPress/Templates
 * @author  ThimPress
 * @version 3.x.x
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP_Global::course();
$quiz   = LP_Global::course_item_quiz();
$count  = $quiz->get_retake_count();
?>

<ul class="quiz-intro">
    <li>
        <label><?php _e( 'Attempts allowed:', 'learnpress' ); ?></label>
		<?php echo ( null == $count || 0 > $count ) ? __( 'Unlimited', 'learnpress' ) : ( $count ? $count : __( 'No', 'learnpress' ) ); ?>
    </li>
    <li>
        <label><?php _e( 'Duration:', 'learnpress' ); ?></label>
		<?php echo $quiz->get_duration_html(); ?>
    </li>
    <li>
        <label><?php _e( 'Passing grade:', 'learnpress' ); ?></label>
		<?php echo sprintf( '%d%%', $quiz->get_passing_grade() ); ?>
    </li>
    <li>
        <label><?php _e( 'Questions:', 'learnpress' ); ?></label>
		<?php echo $quiz->get_total_questions(); ?>
    </li>
</ul>
