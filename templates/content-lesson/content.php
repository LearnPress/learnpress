<?php
/**
 * Template for displaying content of lesson.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-lesson/content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
if ( ! isset( $item ) || ! isset( $content ) ) {
	return;
}

// lesson no content
if ( ! $content ) {
	learn_press_get_template( 'content-lesson/no-content.php', array( 'lesson' => $item ) );

	return;
}
?>

<div class="content-item-description lesson-description"><?php echo $content; ?></div>