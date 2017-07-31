<?php

class LP_Shortcode_Popular_Courses extends LP_Abstract_Shortcode_Courses {
	/**
	 * LP_Popular_Courses_Shortcode constructor.
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

		$query = $wpdb->prepare(
			"SELECT po.*, count(*) as number_enrolled
					FROM {$wpdb->prefix}learnpress_user_items ui
					INNER JOIN {$wpdb->posts} po ON po.ID = ui.item_id
					WHERE ui.item_type = %s
						AND ( ui.status = %s OR ui.status = %s )
						AND po.post_status = %s
					GROUP BY ui.item_id
					ORDER BY ui.item_id {$order}
					LIMIT %d
				",
			LP_COURSE_CPT,
			'enrolled',
			'finished',
			'publish',
			absint( $limit )
		);

		$post_ids = $wpdb->get_col( $query );

		$this->_query = new LP_Query_Course( array( 'post__in' => $post_ids ) );
	}
}