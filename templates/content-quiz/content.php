<?php
/**
 * Template for printing js code used for Quiz.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.3.0
 */

defined( 'ABSPATH' ) or die;

$user      = LP_Global::user();
$quiz      = LP_Global::course_item_quiz();
$quiz_data = $user->get_quiz_data( $quiz->get_id() );
$result    = $quiz_data->get_results( false );

if ( $quiz_data->is_review_questions() ) {
    return;
}

?>
<div id="learn-press-quiz-app">
    <div class="learn-press-quiz-meta">
        <div class="meta-item meta-item-quiz">
            <i class="fas fa-puzzle-piece"></i>
            <span class="meta-number"><?php echo $quiz->count_questions(); ?></span> <?php echo esc_html('Questions','learnpress'); ?>
        </div>

        <div class="meta-item meta-item-duration">
            <i class="far fa-clock"></i> <?php echo learn_press_get_post_translated_duration( $quiz->get_id(), __( 'Lifetime access', 'learnpress' ) ); ?>
        </div>

        <div class="meta-item meta-item-quiz">
            <i class="fas fa-signal"></i> <?php echo esc_html('Passing grade: ','learnpress') ?>
            <span class="meta-number"><?php echo $result['passing_grade'] ?></span>
        </div>
    </div>

    <div class="quiz-content">
        <?php echo $quiz->get_content(); ?>
    </div>
</div>


