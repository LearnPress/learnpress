<?php
/**
 * Template for displaying tags of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/tags.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
$tags   = $course->get_tags();

if ( ! $tags ) {
	return;
}
?>

<span class="course-tags"><?php echo $tags; ?></span>