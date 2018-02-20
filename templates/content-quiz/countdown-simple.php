<?php
/**
 * Template for displaying countdown of the quiz
 *
 * @package LearnPress/Templates
 * @author  ThimPress
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$user   = learn_press_get_current_user();
$course = LP()->global['course'];
$quiz   = isset( $item ) ? $item : LP()->global['course-item'];
if ( !$quiz ) {
	return;
}
$duration = $quiz->get_duration_html();
if ( strpos( $duration, ':' ) === false ) {
	return;
}
if ( $user->has_completed_quiz( $quiz->id, $course->id ) || $user->has_finished_course( $course->id ) ) {
	return;
}
?>
<div id="quiz-countdown" class="quiz-countdown hide-if-js" data-value="100">
	<div class="countdown"><span><?php echo $quiz->get_duration_html(); ?></span></div>
</div>