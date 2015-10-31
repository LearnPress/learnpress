<?php
/**
 * Template for displaying the buttons of a quiz
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $quiz;

$buttons = $quiz->get_buttons();
?>

<?php if ( $buttons ): ?>

	<div class="quiz-buttons">

		<?php echo join( "\n", $buttons ); ?>

	</div>

<?php endif; ?>
