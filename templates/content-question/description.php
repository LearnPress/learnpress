<?php
/**
 * Template for displaying description of question.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-question/description.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $content ) ) {
	return;
}
?>

<div class="quiz-question-desc"><?php echo $content; ?></div>
