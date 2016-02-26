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

global $quiz;

if ( !$quiz->has( 'questions' ) ) {
	return;
}
?>
<div class="quiz-countdown">
	<div id="quiz-countdown-value">0:00:00</div>
	<p class="quiz-countdown-label">
		<?php
		echo apply_filters(
			'learn_press_quiz_time_label',
			$quiz->duration > 59 ? sprintf( '%s/%s/%s', __( 'hours', 'learn_press' ), __( 'mins', 'learn_press' ), __( 'secs', 'learn_press' ) ) : sprintf( '%s/%s', __( 'mins', 'learn_press' ), __( 'secs', 'learn_press' ) )
		);
		?>
	</p>
</div>
