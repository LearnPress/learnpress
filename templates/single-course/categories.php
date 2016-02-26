<?php
/**
 * Template for displaying the categories of a course
 */

defined( 'ABSPATH' ) || exit();

$term_list = get_the_term_list( get_the_ID(), 'course_category', '', ', ', '' );
if ( $term_list ) {
	printf(
		'<span class="cat-links">%s</span>',
		$term_list
	);
}
