<?php
/**
 * Template for displaying the tags of a course
 */

learn_press_prevent_access_directly();
do_action( 'learn_press_before_course_tags' );
$tags = get_the_term_list( get_the_ID(), 'course_tag', '', ', ', '' );
if( $tags ) {
	printf(
		'<span class="tags-links">%s</span>',	
		$tags
	);
}	
do_action( 'learn_press_after_course_tags' );
