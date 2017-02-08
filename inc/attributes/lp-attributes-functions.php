<?php
/**
 * Course attributes functions
 */
include_once LP_PLUGIN_PATH . '/inc/attributes/course.php';
define( 'LP_COURSE_ATTRIBUTE', 'course_attribute' );
/**
 * @return array|int|WP_Error
 */
function learn_press_get_attributes() {
	return get_terms( LP_COURSE_ATTRIBUTE, array(
		'hide_empty' => false
	) );
}

/**
 * @param $attribute
 *
 * @return array|int|WP_Error
 */
function learn_press_get_attribute_terms( $attribute ) {
	if ( is_numeric( $attribute ) && !is_wp_error( $term = get_term( $attribute, LP_COURSE_ATTRIBUTE ) ) ) {
		$attribute = $term->slug;
	}
	$terms = get_terms( sprintf( '%s-%s', LP_COURSE_ATTRIBUTE, $attribute ), array( 'hide_empty' => false ) );
	return $terms;
}

/**
 * @param $attribute
 *
 * @return array|bool
 */
function learn_press_delete_attribute_terms( $attribute ) {
	$deleted_terms = array();
	if ( !$terms = learn_press_get_attribute_terms( $attribute ) ) {
		return false;
	}
	foreach ( $terms as $term ) {
		if ( !is_wp_error( $deleted = wp_delete_term( $term->term_id, $term->taxonomy ) ) ) {
			$deleted_terms[] = $term;
		}
	}
	do_action( 'learn_press_deleted_attribute_terms', $attribute, $deleted_terms );
	return $deleted_terms;
}

function learn_press_get_course_attributes( $course_id ) {
	return get_post_meta( $course_id, '_lp_attributes', true );
}

function learn_press_add_course_attribute_value( $name, $taxonomy ) {

	if ( !$name || term_exists( $name, $taxonomy ) ) {
		return false;
	}
	if ( !$term = get_term_by( 'slug', $taxonomy, LP_COURSE_ATTRIBUTE ) ) {
		return false;
	}

	$new_value = wp_insert_term( $name, sprintf( '%s-%s', LP_COURSE_ATTRIBUTE, $term->slug ) );
	return $new_value;
}

function learn_press_add_course_attribute( $name ) {

	if ( !$name || term_exists( $name, LP_COURSE_ATTRIBUTE ) ) {
		return false;
	}

	$new_value = wp_insert_term( $name, LP_COURSE_ATTRIBUTE );
	return $new_value;
}

function learn_press_add_attribute_to_course( $course_id, $taxonomy ) {
	if ( !$term = get_term_by( 'slug', $taxonomy, LP_COURSE_ATTRIBUTE ) ) {
		return false;
	}
	$attributes = get_post_meta( $course_id, '_lp_attributes', true );
	if ( !is_array( $attributes ) ) {
		$attributes = array();
	}
	$attribute               = apply_filters( 'learn_press_update_course_attribute_data', array(
		'name' => $term->slug
	), $course_id, $term );
	$attributes[$term->slug] = $attribute;

	$attributes = apply_filters( 'learn_press_update_course_attributes_data', $attributes, $course_id, $term );

	update_post_meta( $course_id, '_lp_attributes', $attributes );
	return $attribute;
}

/**
 * Register widgets
 */
LP_Widget::register( 'course-attributes' );
LP_Widget::register( 'course-filters' );