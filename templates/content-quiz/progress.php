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

$user      = LP_Global::user();
$quiz_data = $user->get_quiz_data( $quiz->get_id() );
$result    = $quiz_data->get_result();
$percent   = $quiz_data->get_questions_answered( true );
?>
<div class="quiz-progress">
    <div class="quiz-point-achieved">
        <i class="fa fa-trophy"></i>
        <div class="progress-number"><?php echo $quiz_data->get_mark(); ?></div>
    </div>
    <div class="quiz-current-question">
        <i class="fa fa-question"></i>
        <div class="progress-number"><?php echo sprintf( '%d/%d', $quiz->get_question_index( $quiz_data->get_current_question(), 1 ), $quiz_data->get_total_questions() ); ?></div>
    </div>
    <div class="quiz-countdown">
        <i class="fa fa-hourglass-start"></i>
        <div class="progress-number">
			<?php
			if($duration = $quiz_data->get_time_remaining()) {
				echo $duration->to_timer();
			}else{
			    echo '--:--';
            }
			?>
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