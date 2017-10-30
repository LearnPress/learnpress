<?php
/**
 * Template for displaying the tags of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP_Global::course();

$tags = $course->get_tags();
if ( ! $tags ) {
	return;
}
?>

<span class="course-tags"><?php echo $tags; ?></span>