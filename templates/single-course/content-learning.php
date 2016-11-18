<?php
/**
 * Template for displaying content of learning course
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'learn_press_before_content_learning' ); ?>

	<div class="course-learning-summary">

		<?php do_action( 'learn_press_content_learning_summary' ); ?>

	</div>

<?php do_action( 'learn_press_after_content_learning' ); ?>

