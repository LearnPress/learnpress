<?php
/**
 * Displaying the description of single quiz
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="quiz-description" id="learn-press-quiz-description">

	<?php do_action( 'learn_press_begin_single_quiz_description' ); ?>

	<?php the_content(); ?>

	<?php do_action( 'learn_press_end_single_quiz_description' ); ?>

</div>