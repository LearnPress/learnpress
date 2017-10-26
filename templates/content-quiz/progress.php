<?php
/**
 * Template for displaying progress of current quiz user are doing.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();

$user        = LP_Global::user();
$quiz        = LP_Global::course_item_quiz();
$course_data = $user->get_course_data( get_the_ID() );

$quiz_item = $course_data->get_item_quiz( $quiz->get_id() );// $user->get_quiz_data( $quiz->get_id() );

$quiz_data = $user->get_quiz_data( $quiz->get_id() );
$result    = $quiz_data->get_results();
$percent   = $quiz_data->get_questions_answered( true );
if($quiz_data->is_review_questions()){
    return;
}
//learn_press_debug($quiz_data);
?>
<div class="quiz-progress">
    <div class="progress-items">
<!--        <div class="progress-item quiz-point-achieved">-->
<!--            <span class="progress-number">-->
<!--                0-->
<!--            </span>-->
<!--            <span class="progress-label" @click="clickX">-->
<!--				--><?php //_e( 'Point', 'learnpress' ); ?>
<!--            </span>-->
<!--        </div>-->
        <div class="progress-item quiz-current-question">
            <span class="progress-number">
				<?php echo sprintf( __( '%d/%d', 'learnpress' ), $quiz->get_question_index( $quiz_data->get_current_question(), 1 ), $quiz_data->get_total_questions() ); ?>
            </span>
            <span class="progress-label" @click="clickX">
				<?php _e( 'Question', 'learnpress' ); ?>
            </span>
        </div>
        <div class="progress-item quiz-countdown">
            <span class="progress-number">
                00:00:24
            </span>
            <span class="progress-label">
				<?php
				if ( $duration = $quiz_data->get_time_remaining() ) {
					_e( 'Time remaining', 'learnpress' );
				} else {
					echo __( 'Unlimited', 'learnpress' );
				}
				?>
            </span>
        </div>
    </div>
</div>

<?php
return;
?>
<div title="0%" class="learn-press-progress quiz-progress">
    <div class="progress-bg">
        <div class="progress-active" style="left: <?php echo $percent; ?>%;"
             data-percent="<?php echo $percent; ?>%"></div>
    </div>
    <span class="xxxx" style="left: <?php echo $percent; ?>%">
        <?php echo round( $percent, 1 ); ?>
    </span>
</div>

<div>
	<?php echo sprintf( '%d/%d', $quiz_data->get_questions_answered(), $quiz_data->get_total_questions() ); ?>
</div>

<div>
	<?php echo sprintf( '%d/%d', $quiz_data->get_mark(), $quiz_data->get_quiz_mark() ); ?>
</div>