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
	$terms = array();
	if ( is_array( $attribute ) ) {
		foreach ( $attribute as $attr ) {
			$_terms = learn_press_get_attribute_terms( $attr );
			if ( is_array( $terms ) ) {
				$terms = array_merge( $terms, $_terms );
			}
		}
	} else {
		if ( is_numeric( $attribute ) && ! is_wp_error( $term = get_term( $attribute, LP_COURSE_ATTRIBUTE ) ) ) {
			$attribute = $term->slug;
		}
		$terms = get_terms( sprintf( '%s-%s', LP_COURSE_ATTRIBUTE, $attribute ), array( 'hide_empty' => false ) );
	}

	return $terms;
}

/**
 * @param $attribute
 *
 * @return array|bool
 */
function learn_press_delete_attribute_terms( $attribute ) {
	$deleted_terms = array();
	if ( ! $terms = learn_press_get_attribute_terms( $attribute ) ) {
		return false;
	}
	foreach ( $terms as $term ) {
		if ( ! is_wp_error( $deleted = wp_delete_term( $term->term_id, $term->taxonomy ) ) ) {
			$deleted_terms[] = $term;
		}
	}
	do_action( 'learn_press_deleted_attribute_terms', $attribute, $deleted_terms );

	return $deleted_terms;
}

/**
 * @param $course_id
 *
 * @return mixed
 */
function learn_press_get_course_attributes( $course_id ) {
	return get_post_meta( $course_id, '_lp_attributes', true );
}

/**
 * @param $name
 * @param $taxonomy
 *
 * @return array|bool|WP_Error
 */
function learn_press_add_course_attribute_value( $name, $taxonomy ) {

	if ( ! $name || term_exists( $name, $taxonomy ) ) {
		return false;
	}
	if ( ! $term = get_term_by( 'slug', $taxonomy, LP_COURSE_ATTRIBUTE ) ) {
		return false;
	}

	$new_value = wp_insert_term( $name, sprintf( '%s-%s', LP_COURSE_ATTRIBUTE, $term->slug ) );

	return $new_value;
}

/**
 * @param $name
 *
 * @return array|bool|WP_Error
 */
function learn_press_add_course_attribute( $name ) {

	if ( ! $name || term_exists( $name, LP_COURSE_ATTRIBUTE ) ) {
		return false;
	}

	$new_value = wp_insert_term( $name, LP_COURSE_ATTRIBUTE );

	return $new_value;
}

/**
 * @param $course_id
 * @param $taxonomy
 *
 * @return bool|mixed
 */
function learn_press_add_attribute_to_course( $course_id, $taxonomy ) {
	if ( ! $term = get_term_by( 'slug', $taxonomy, LP_COURSE_ATTRIBUTE ) ) {
		return false;
	}
	$attributes = get_post_meta( $course_id, '_lp_attributes', true );
	if ( ! is_array( $attributes ) ) {
		$attributes = array();
	}
	$attribute                 = apply_filters( 'learn_press_update_course_attribute_data', array(
		'name' => $term->slug
	), $course_id, $term );
	$attributes[ $term->slug ] = $attribute;

	$attributes = apply_filters( 'learn_press_update_course_attributes_data', $attributes, $course_id, $term );

	update_post_meta( $course_id, '_lp_attributes', $attributes );

	return $attribute;
}

/**
 * @param WP_Query $q
 *
 * @return mixed
 */
function learn_press_filter_courses_by_attributes( $q ) {
	global $lp_tax_query;
	if ( empty( $_REQUEST['course-filter'] ) ) {
		return $q;
	}

	if ( ! $q->is_main_query() ) {
		return $q;
	}
	if ( LP_COURSE_CPT != $q->get( 'post_type' ) ) {
		return $q;
	}

	if ( $attribute_taxonomies = learn_press_get_attributes() ) {
		$attribute_operator = 'and' === strtolower( learn_press_get_request( 'attribute_operator' ) ) ? 'AND' : 'OR';
		$value_operator     = 'and' === strtolower( learn_press_get_request( 'value_operator' ) ) ? 'AND' : 'IN';
		$tax_query          = array(
			'relation' => $attribute_operator
		);
		foreach ( $attribute_taxonomies as $tax ) {
			$attribute    = $tax->slug;
			$taxonomy     = LP_COURSE_ATTRIBUTE . '-' . $attribute;
			$filter_terms = ! empty( $_GET[ 'filter_' . $attribute ] ) ? explode( ',', $_GET[ 'filter_' . $attribute ] ) : array();


			if ( empty( $filter_terms ) || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy'         => $taxonomy,
				'field'            => 'slug',
				'terms'            => $filter_terms,
				'operator'         => $value_operator,
				'include_children' => false,
			);
		}
		$lp_tax_query = $tax_query;
		$q->set( 'tax_query', $tax_query );
	}

	return $q;
}

/**
 * @param WP_Query $q
 */
add_filter( 'pre_get_posts', 'learn_press_filter_courses_by_attributes', 1000 );

/**
 * Register widgets
 */
LP_Widget::register( array( 'course-attributes', 'course-filters' ) );
