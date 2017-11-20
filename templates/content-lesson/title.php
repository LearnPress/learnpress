<?php
/**
 * Template for displaying title of lesson.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-lesson/title.php.
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

<?php $lesson = LP_Global::course_item(); ?>

<?php
if ( ! $title = $lesson->get_title() ) {
	return;
}
?>

<h3 class="course-item-title quiz-title"><?php echo $title; ?></h3>