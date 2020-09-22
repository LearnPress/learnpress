<?php
/**
 * Template for displaying title of question.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-question/title.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $title ) ) {
	return;
}
?>

<h4 class="question-title"><?php echo $title; ?></h4>
