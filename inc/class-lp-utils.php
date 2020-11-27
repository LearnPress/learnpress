<?php

/**
 * Class LP_Utils
 *
 * Utilities.
 */
class LP_Utils {

	/**
	 * @var LP_Utils
	 */
	protected static $instance = null;

	/**
	 * LP_Utils constructor.
	 */
	protected function __construct() {
	}

	/**
	 * Hard flush count data of users enrolled each course
	 * and recount all.
	 *
	 * @since 3.x.x
	 *
	 * @return array
	 */
	public function flush_count_course_users() {
		global $wpdb;

		if ( ! $all_statuses = $wpdb->get_col( "SELECT DISTINCT `status` FROM {$wpdb->learnpress_user_items}" ) ) {
			$all_statuses = array();
		}
		$all_statuses[] = 'total';
		$init_values    = array_fill_keys( $all_statuses, 0 );

		$query  = $wpdb->prepare(
			"
				SELECT item_id id, `status`, COUNT(user_item_id) `count`
				FROM {$wpdb->learnpress_user_items}
				WHERE 1 AND item_type = %s
				GROUP BY item_id, `status`
				ORDER BY item_id
			",
			LP_COURSE_CPT
		);
		$counts = array();

		if ( $rows = $wpdb->get_results( $query ) ) {

			foreach ( $rows as $row ) {
				if ( empty( $counts[ $row->id ] ) ) {
					$counts[ $row->id ] = $init_values;
				}

				$counts[ $row->id ][ $row->status ] = $row->count;
				$counts[ $row->id ]['total']       += $row->count;
			}
		}

		update_option( '_lp_count_course_users', $counts );

		return $counts;
	}

	/**
	 * Counts number of users enrolled to courses.
	 *
	 * @since 3.x.x
	 *
	 * @param array $args {
	 *
	 * }
	 * @param bool  $hard
	 *
	 * @return array
	 */
	public function count_course_users( $args = array(), $hard = false ) {

		$counts = get_option( '_lp_count_course_users' );

		if ( is_numeric( $args ) ) {
			$args = array( 'course_id' => $args );
		} elseif ( is_string( $args ) ) {
			$args = array( 'status' => $args );
		}

		$args = wp_parse_args(
			$args,
			array(
				'course_id'  => null,
				'status'     => '',
				'total_only' => false,
			)
		);

		if ( $args['course_id'] ) {
			$course_ids = (array) $args['course_id'];
		} else {
			$course_ids = array();
		}

		if ( $hard || false === $counts ) {
			$counts = $this->flush_count_course_users();
		}

		if ( $course_ids ) {
			$counts = array_intersect_key( $counts, array_flip( $course_ids ) );
		}

		if ( $args['status'] ) {
			$statuses = (array) $args['status'];
			$_counts  = array();

			foreach ( $statuses as $status ) {
				foreach ( $counts as $k => $value ) {
					if ( empty( $_counts[ $k ] ) ) {
						if ( $args['total_only'] ) {
							$_counts[ $k ] = ! empty( $value[ $status ] ) ? $value[ $status ] : 0;
						} else {
							$_counts[ $k ] = array( $status => ! empty( $value[ $status ] ) ? $value[ $status ] : 0 );
						}
					} else {
						if ( $args['total_only'] ) {
							$_counts[ $k ] += ! empty( $value[ $status ] ) ? $value[ $status ] : 0;
						} else {
							$_counts[ $k ][ $status ] = ! empty( $value[ $status ] ) ? $value[ $status ] : 0;
						}
					}
				}
			}
			$counts = $_counts;
		} else {
			if ( $args['total_only'] ) {
				$counts = wp_list_pluck( $counts, 'total' );
			}
		}

		if ( is_numeric( $args['course_id'] ) ) {
			$counts = reset( $counts );
		}

		return $counts;
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

return LP_Utils::instance();
