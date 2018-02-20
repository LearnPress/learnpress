<?php
/**
 * Template for displaying the instructor of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];

printf(
	'<span class="course-author" aria-hidden="true" itemprop="author">
		%s %s</a>%s
	</span>',
	apply_filters( 'before_instructor_link', __( 'Instructor: ', 'learnpress' ) ),
	apply_filters( 'learn_press_instructor_profile_link', $course->get_instructor_html(), null, $course->id ),
	apply_filters( 'after_instructor_link', '' )
);