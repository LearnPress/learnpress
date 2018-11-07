<?php
/**
 * Template for displaying title of lesson.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-lesson/title.php.
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

if ( ! $title = $lesson->get_title( 'display' ) ) {
	return;
}
?>

<h3 class="course-item-title question-title"><?php echo $title; ?></h3>