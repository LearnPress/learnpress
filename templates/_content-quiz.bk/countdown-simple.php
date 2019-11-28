<?php
/**
 * Template for displaying simple countdown quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/countdown-simple.php.
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

<div id="quiz-countdown" class="quiz-countdown hide-if-js" data-value="100">

    <div class="countdown"><span><?php echo $quiz->get_duration_html(); ?></span></div>

</div>