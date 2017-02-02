<?php
include_once LP_PLUGIN_PATH . '/inc/attributes/course.php';
function learn_press_get_course_attributes() {
	return get_terms( 'course_attribute', array(
		'hide_empty' => false
	) );
}

function learn_press_get_attribute_terms( $attribute ) {
	$term  = get_term( $attribute );
	$terms = array();
	if ( $term ) {
		$terms = get_terms( 'course_attribute-' . $term->slug, array( 'hide_empty' => false ) );
	}
	return $terms;
}

LP_Widget::register( 'course-attributes' );