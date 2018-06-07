<?php
$output = '<ul class="lp-course-attributes course-attributes">';

$object_terms = array();

foreach ( $attributes as $attribute ) {
	$object_terms[] = LP_COURSE_ATTRIBUTE . '-' . $attribute['name'];
}
$object_terms = ( wp_get_object_terms( $postId, $object_terms ) );
foreach ( $attributes as $attribute ) {
	$taxonomy = get_term_by( 'slug', $attribute['name'], LP_COURSE_ATTRIBUTE );
	$output .= '<li><h4>' . $taxonomy->name . '</h4>';
	$output .= '<ul class="lp-course-attribute-values">';
	foreach ( $object_terms as $value ) {
		if ( $value->taxonomy != LP_COURSE_ATTRIBUTE . '-' . $attribute['name'] ) {
			continue;
		}
		$output .= '<li>' . $value->name . '</li>';
	}
	$output .= '</ul></li>';
}
$output .= '</ul>';
echo $output;