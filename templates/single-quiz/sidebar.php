<?php
/**
 * Template for displaying the sidebar for quiz actions
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $quiz;

if( ! $quiz->has( 'questions' ) ){
	return;
}
?>
<div class="quiz-sidebar">

	<?php do_action( 'learn_press_single_quiz_sidebar' ); ?>

</div>