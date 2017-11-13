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


?>
<div id="quiz-countdown" class="quiz-countdown hide-if-js" data-value="100">
	<div class="countdown"><span><?php echo $quiz->get_duration_html(); ?></span></div>
</div>