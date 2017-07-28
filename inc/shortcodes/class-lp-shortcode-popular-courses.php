<?php

class LP_Shortcode_Popular_Courses extends LP_Abstract_Shortcode {
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
	public function output() {
		ob_start();

		$limit = $order_by = $order = '';

		$atts = shortcode_atts( array(
			'limit' => 10,
			'order' => 'DESC' // select on of [DESC, ASC]
		), $atts );

		extract( $atts );

		// Validation date
		$arr_orders = array( 'DESC', 'ASC' );
		$order      = strtoupper( $order );

		if ( ! in_array( $order, $arr_orders ) ) {
			$order = 'DESC';
		}
		if ( ! absint( $limit ) ) {
			$limit = 10;
		}

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

		$posts = $wpdb->get_results(
			$query
		);


		global $post;
		if ( ! empty( $posts ) ) {
			do_action( 'learn_press_before_courses_loop' );

			learn_press_begin_courses_loop();

			foreach ( $posts as $post ) {
				setup_postdata( $post );
				learn_press_get_template_part( 'content', 'course' );
			}

			learn_press_end_courses_loop();
		} else {
			learn_press_display_message( __( 'No course found.', 'learnpress' ), 'error' );

		}

		wp_reset_postdata();

		$output = ob_get_clean();

		return $output;
	}
}