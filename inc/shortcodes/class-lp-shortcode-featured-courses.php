<?php

class LP_Shortcode_Featured_Courses extends LP_Abstract_Shortcode {
	/**
	 * LP_Featured_Courses_Shortcode constructor.
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
			'limit'    => 10,
			'order_by' => 'date', // select one of [date, title, status, comment_count]
			'order'    => 'DESC' // select on of [DESC, ASC]
		), $atts );

		extract( $atts );

		// Validation date
		$arr_orders_by = array( 'post_date', 'post_title', 'post_status', 'comment_count' );
		$arr_orders    = array( 'DESC', 'ASC' );
		$order         = strtoupper( $order );

		if ( ! in_array( $order_by, $arr_orders_by ) || ! in_array( 'post_' . $order_by, $arr_orders_by ) ) {
			$order_by = 'post_date';
		} else {
			if ( $order_by !== 'comment_count' ) {
				$order_by = 'post_' . $order_by;
			}
		}

		if ( ! in_array( $order, $arr_orders ) ) {
			$order = 'DESC';
		}
		if ( ! absint( $limit ) ) {
			$limit = 10;
		}

		global $wpdb;

		$posts = $wpdb->get_results(
			$wpdb->prepare( "
				SELECT DISTINCT *
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} as pmeta ON p.ID=pmeta.post_id AND pmeta.meta_key = %s
                    WHERE p.post_type = %s
						AND p.post_status = %s
						AND meta_value = %s
                    ORDER BY p.{$order_by} {$order}
                    LIMIT %d
                ", '_lp_featured', LP_COURSE_CPT, 'publish', 'yes', absint( $limit )
			)
		);

		$output = ob_get_clean();

		return $output;
	}
}