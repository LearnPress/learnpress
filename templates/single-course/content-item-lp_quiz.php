<?php
/**
 * Template for displaying content of the quiz
 *
 * @author ThimPress
 */
$course = LP()->global['course'];
$user   = learn_press_get_current_user();
$item   = isset( $item ) ? $item : LP()->global['course-item'];
$force  = isset( $force ) ? $force : false;
if ( !$item ) {
	return;
}

?>

<h4 class="learn-press-content-item-title"><?php echo $item->title; ?></h4>
<div itemscope id="quiz-<?php echo $item->ID; ?>" <?php learn_press_quiz_class( 'learn-press-content-item-summary' ); ?>>
	<?php echo $item->content; ?>

	<?php learn_press_get_template( 'single-course/course-item-quiz/intro.php', array( 'force' => $force ) ); ?>
	<?php learn_press_get_template( 'single-course/course-item-quiz/timer.php', array( 'force' => $force ) ); ?>
	<?php learn_press_get_template( 'single-course/course-item-quiz/buttons.php', array( 'force' => $force ) ); ?>

	<?php if ( $status = $user->has( 'quiz-status', array( 'stared' ), $item->id, $course->id, $force ) ): ?>
		<?php echo $status; ?>
	<?php else: ?>
		<?php learn_press_get_template( 'single-course/course-item-quiz/questions.php', array( 'force' => $force ) ); ?>
	<?php endif; ?>
	<?php //do_action( 'learn_press_single_quiz_summary' ); ?>


</div>

