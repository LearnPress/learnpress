<?php

/**
 * Class LP_Course_Query
 *
 * @version 3.3.0
 */
class LP_Course_Query extends LP_Object_Query {

	protected $course_query_vars = array();

	/**
	 * LP_Course_Query constructor.
	 *
	 * @param string $query
	 */
	public function __construct( $query = '' ) {
		$limit = LP_Settings::get_option( 'archive_course_limit', 6 );
		if ( empty( $limit ) ) {
			$limit = 6;
		}

		$this->course_query_vars = array(
			'post_type'   => LP_COURSE_CPT,
			'post_status' => array( 'draft', 'pending', 'private', 'publish' ),
			'limit'       => $limit,
			'author'      => '',
		);

		parent::__construct( $query );
	}

	/**
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			$this->course_query_vars
		);
	}

	/**
	 * Transform our query vars to wp query vars that
	 * can read from db.
	 *
	 * @param array $query_vars
	 *
	 * @return array|mixed
	 * @since 4.0.0
	 */
	public function get_wp_query_vars( $query_vars = array() ) {
		$query_vars = apply_filters( 'learn-press/course-object-query-args', wp_parse_args( $query_vars, $this->get_query_vars() ) );
		$map_keys   = array(
			'status'         => 'post_status',
			'page'           => 'paged',
			'include'        => 'post__in',
			'return'         => 'fields',
			'parent'         => 'post_parent',
			'parent_exclude' => 'post_parent__not_in',
			'exclude'        => 'post__not_in',
			'limit'          => 'posts_per_page',
			'type'           => 'post_type',
		);

		foreach ( $map_keys as $query_key => $db_key ) {
			if ( isset( $query_vars[ $query_key ] ) ) {
				$query_vars[ $db_key ] = $query_vars[ $query_key ];
				unset( $query_vars[ $query_key ] );
			}
		}

		$custom_keys = array(
			'featured' => '',
		);

		foreach ( $custom_keys as $key => $custom_key ) {
			if ( isset( $query_vars[ $key ] ) ) {
				$custom_keys[ $key ] = $query_vars[ $key ];
				unset( $query_vars[ $key ] );
			}
		}

		// Query by post meta
		if ( ! isset( $query_vars['meta_query'] ) ) {
			$query_vars['meta_query'] = array();
		}

		// Featured
		if ( '' !== $custom_keys['featured'] ) {
			$featured = $custom_keys['featured'];

			if ( $featured === 'yes' || $featured === 1 || $featured === true || $featured === '1' ) {
				$query_vars['meta_query'] = array(
					array(
						'key'     => '_lp_featured',
						'value'   => 'yes',
						'compare' => '=',
					),
				);
			} else {
				$query_vars['meta_query'] = array(
					array(
						'key'     => '_lp_featured',
						'value'   => 'yes',
						'compare' => '!=',
					),
				);
			}
		}

		return apply_filters( 'learn-press/course-object-wp-query-args', $query_vars );
	}

	/**
	 * Applies our query vars to read courses.
	 *
	 * @return array|mixed
	 * @since 4.0.0
	 */
	public function get_courses() {
		global $wpdb;
		$query_vars = $this->get_wp_query_vars();

		$query = new WP_Query( $query_vars );

		$courses = $query->posts;

		if ( isset( $query_vars['return'] ) && 'objects' === $query_vars['return'] ) {
			$courses = array_filter( array_map( 'learn_press_get_course', $courses ) );
		}

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			$courses = (object) array(
				'courses'       => $courses,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return apply_filters( 'learn-press/course-object-query', $courses, $query_vars );
	}
}

// Backward compatibility
class LP_Query_Course extends LP_Course_Query {
}
