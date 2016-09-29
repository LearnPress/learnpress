<?php
/**
 * Template for displaying the tags of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];

$tags = apply_filters( 'learn_press_course_tags', get_the_term_list( $course->id, 'course_tag', __( 'Tags: ', 'learnpress' ), ', ', '' ) );
if ( !$tags ) {
	return;
}
?>

<span class="course-tags"><?php echo $tags; ?></span>