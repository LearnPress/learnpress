<?php
/**
 * Template for displaying content of video lesson.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-lesson/video/content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$lesson = LP_Global::course_item();

// lesson no content
if ( ! $content = $lesson->get_content() ) {
	learn_press_get_template( 'content-lesson/no-content.php' );

	return;
}
?>

<div class="content-item-description lesson-description"><?php echo $content; ?></div>