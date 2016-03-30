<?php
/**
 * Template for displaying the countdown timer
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$quiz = LP()->quiz;
$user = LP()->user;
if ( !$quiz ) {
	return;
}
$remaining_time = $user->get_quiz_status() != 'started' ? $quiz->duration : $user->get_quiz_time_remaining( $quiz->id );
?>
<div class="quiz-countdown<?php echo !$user->get_quiz_status( $quiz->id ) ? ' hide-if-js' : ''; ?> ">
	<div id="quiz-countdown-value">
		<?php echo $remaining_time > 59 ? date( 'G:i:s', $remaining_time ) : date( 'i:s', $remaining_time ); ?>
	</div>
	<p class="quiz-countdown-label">
		<?php
		echo apply_filters(
			'learn_press_quiz_time_label',
			$remaining_time > 59 ? sprintf( '%s/%s/%s', __( 'hours', 'learnpress' ), __( 'mins', 'learnpress' ), __( 'secs', 'learnpress' ) ) : sprintf( '%s/%s', __( 'mins', 'learnpress' ), __( 'secs', 'learnpress' ) )
		);
		?>
	</p>
</div>
