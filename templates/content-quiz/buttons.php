<?php
/**
 * Template for displaying buttons of the quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/buttons.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<div class="lp-quiz-buttons">

	<?php do_action( 'learn-press/before-quiz-buttons' ); ?>

	<?php
	/**
	 * @see learn_press_quiz_nav_buttons - 10
	 * @see learn_press_quiz_start_button - 10
	 * @see learn_press_quiz_complete_button - 10
	 * @see learn_press_quiz_redo_button - 10
	 */
	do_action( 'learn-press/quiz-buttons' );
	?>

	<?php do_action( 'learn-press/after-quiz-buttons' ); ?>

</div>
