<?php

class LP_Shortcode_Recent_Courses extends LP_Abstract_Shortcode_Courses {
	/**
	 * LP_Recent_Courses_Shortcode constructor.
	 *
	 * @param mixed $atts
	 */
	public function __construct( $atts = '' ) {
		parent::__construct( $atts );
	}

	/**
	 * Shortcode content.
	 *
	 * @return string
	 */
	public function query_courses() {

		$limit = $order_by = $order = '';

		$atts = $this->get_atts();

		extract( $atts );

		global $wpdb;
		$query    = $wpdb->prepare( "
			SELECT DISTINCT p.*
				FROM $wpdb->posts AS p
				WHERE p.post_type = %s
				AND p.post_status = %s
				ORDER BY p.{$order_by} {$order}
				LIMIT %d
			", LP_COURSE_CPT, 'publish', absint( $limit )
		);
		$post_ids = $wpdb->get_col( $query );

		$this->_query = new LP_Query_Course(
			array(
				'post__in' => $post_ids
			)
		);

		return $this->_query;
	}
}