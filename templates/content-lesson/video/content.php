<?php
/**
 * Template for displaying content of video lesson.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-lesson/video/content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$lesson = LP_Global::course_item();

if ( ! $lesson->get_content() ) {
	learn_press_get_template( 'content-lesson/no-content.php' );
	return;
}
?>

<div class="content-item-description lesson-description"><?php echo $lesson->get_content(); ?></div>
