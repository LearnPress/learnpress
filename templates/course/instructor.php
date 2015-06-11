<?php
/**
 * Template for displaying the instructor of a course
 */

learn_press_prevent_access_directly();
do_action( 'learn_press_before_course_instructor' );
printf(
	'<span class="author" aria-hidden="true">
		%s<a href="%s">%s</a>%s
	</span>',
	apply_filters('before_instructor_link', __('Instructor: ', 'learn_press')),
	apply_filters( 'learn_press_instructor_profile_link', '#', get_the_ID() ),
	get_the_author(),
	apply_filters('after_instructor_link', '')
);
do_action( 'learn_press_after_course_instructor' );
