<?php
/**
 * Template for displaying quiz's introduction
 *
 * @package LearnPress/Templates
 * @author ThimPress
 * @version 1.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];
$quiz   = LP()->global['course-item'];
$user   = learn_press_get_current_user();
if ( !$quiz ) {
	return;
}
if ( $user->has( 'quiz-status', array( 'started', 'completed' ), $quiz->id, $course->id ) ) {
	return;
}
?>

<ul class="quiz-intro">
	<li>
		<label><?php _e( 'Attempts allowed:', 'learnpress' ); ?></label>
		<?php echo $quiz->retake_count; ?>
	</li>
	<li>
		<label><?php _e( 'Duration:', 'learnpress' ); ?></label>
		<?php echo $quiz->get_duration_html(); ?>
	</li>
	<li>
		<label><?php _e( 'Passing grade:', 'learnpress' ); ?></label>
		<?php 
		if( 'point' == $quiz->passing_grade_type ) {
			echo $quiz->passing_grade; 
			echo '&nbsp;'; 
			echo __('Point','learnpress');
		} elseif( 'percentage' == $quiz->passing_grade_type ) {
			echo sprintf( '%d%%', $quiz->passing_grade ); 
		} elseif( 'no' == $quiz->passing_grade_type ) {
			echo __('None', 'learnpress');
		}
		?>
	</li>
	<li>
		<label><?php _e( 'Questions:', 'learnpress' ); ?></label>
		<?php echo $quiz->get_total_questions(); ?>
	</li>
</ul>
