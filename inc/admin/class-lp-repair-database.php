<?php

/**
 * Class LP_Repair_Database
 *
 * Repair database tool
 *
 * @since 3.1.0
 */
class LP_Repair_Database {
	/**
	 * @var LP_Repair_Database
	 */
	protected static $instance = null;

	/**
	 * LP_Repair_Database constructor.
	 *
	 * @access protected
	 */
	protected function __construct() {
	}

	/**
	 * Sync all
	 */
	public function sync_all() {
		$this->sync_course_orders();
		$this->sync_user_courses();
	}

	public function call( $func ) {
		$func = preg_replace( '~[-]+~', '_', $func );

		if ( ! is_callable( array( $this, $func ) ) ) {
			throw new Exception( sprintf( __( 'The method %s is not callable.', 'learnpress' ), $func ) );
		}

		$args = func_get_args();
		unset( $args[0] );

		return sizeof( $args ) ?
			call_user_func_array( array( $this, $func ), $args ) :
			call_user_func( array( $this, $func ) );
	}

	/**
	 * Sync orders for each course
	 *
	 * @since 3.1.0
	 *
	 * @param array|string $courses
	 *
	 * @return bool|array
	 */
	public function sync_course_orders( $courses = '*' ) {
		global $wpdb;

		if ( empty( $courses ) ) {
			return false;
		}

		if ( $courses === '*' ) {
			$query = $wpdb->prepare( "
				SELECT ID
				FROM {$wpdb->posts}
				WHERE post_type = %s
					AND post_status = %s
			", LP_COURSE_CPT, 'publish' );

			$courses = $wpdb->get_col( $query );

			if ( $courses ) {
				return false;
			}
		}

		$statuses = learn_press_get_order_statuses( true, true );
		settype( $courses, 'array' );

		$statuses_format = array_fill( 0, sizeof( $statuses ), '%s' );
		$courses_format  = array_fill( 0, sizeof( $courses ), '%d' );
		$statuses_format = $wpdb->prepare( join( ',', $statuses_format ), $statuses );
		$courses_format  = $wpdb->prepare( join( ',', $courses_format ), $courses );

		$query = $wpdb->prepare( "
				SELECT cid, status, orders
				FROM(
					SELECT oim.meta_value cid, concat(oim.meta_value, ' ', o.post_status)  a, post_status `status`, GROUP_CONCAT(o.ID) orders
					FROM {$wpdb->learnpress_order_itemmeta} oim 
					INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key = %s
					INNER JOIN {$wpdb->posts} o ON o.ID = oi.order_id 
					WHERE o.post_type = %s
					AND o.post_status IN ($statuses_format) 
					AND oim.meta_value IN ($courses_format)
					GROUP BY a, cid
				) X
			", '_course_id', 'lp_order' );

		foreach ( $courses as $course_id ) {
			foreach ( $statuses as $status ) {
				update_post_meta( $course_id, 'order-' . str_replace( 'lp-', '', $status ), array() );
			}
		}

		if ( $results = $wpdb->get_results( $query ) ) {
			foreach ( $results as $result ) {
				update_post_meta( $result->cid, 'order-' . str_replace( 'lp-', '', $result->status ), explode( ',', $result->orders ) );
			}
		}

		return $courses;
	}

	/**
	 * Sync orders for each user
	 *
	 * @param array $users
	 */
	public function sync_user_orders( $users = array() ) {
		global $wpdb;
		$api = new LP_User_CURD();
		LP_Debug::logTime( __FUNCTION__ );
		foreach ( $users as $user ) {
			if ( ! $orders = $api->read_orders( $user ) ) {
				continue;
			}

			update_user_meta( $user, 'orders', $orders );
		}
		LP_Debug::logTime( __FUNCTION__ );

	}

	/**
	 * Sync courses for each user
	 *
	 * @since 3.1.0
	 */
	public function sync_user_courses() {
		//echo __FUNCTION__;

	}

	/**
	 * Sync final quiz for each course.
	 *
	 * @param array $courses
	 */
	public function sync_course_final_quiz( $courses = array() ) {
		settype( $courses, 'array' );
		foreach ( $courses as $course_id ) {

			if ( ! $course = learn_press_get_course( $course_id ) ) {
				continue;
			}

			/**
			 * If course result is not set to final-quiz
			 */
			if ( $course->get_data( 'course_result' ) !== 'evaluate_final_quiz' ) {
				delete_post_meta( $course_id, '_lp_final_quiz' );
				continue;
			}

			$items = $course->get_item_ids();
			if ( $items ) {
				$end = end( $items );
				if ( learn_press_get_post_type( $end ) === LP_QUIZ_CPT ) {
					$final_quiz = $end;
				}
			}

			if ( isset( $final_quiz ) ) {
				update_post_meta( $course_id, '_lp_final_quiz', $final_quiz );
			} else {
				delete_post_meta( $course_id, '_lp_final_quiz' );
				update_post_meta( $course_id, '_lp_course_result', 'evaluate_lesson' );
			}
		}
	}

	public function remove_older_post_meta() {
		global $wpdb;
		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s
		", $wpdb->esc_like( '_lpr_' ) . '%' );
		$wpdb->query( $query );

		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s
		", '%' . $wpdb->esc_like( '_lpr_' ) . '%' );
		$wpdb->query( $query );
	}

	/**
	 * Get all ids of existing courses
	 *
	 * @return array
	 */
	public function get_all_courses() {
		global $wpdb;
		$query = $wpdb->prepare( "
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = %s
                AND post_status = %s
        ", LP_COURSE_CPT, 'publish' );

		return $wpdb->get_col( $query );
	}

	/**
	 * @return LP_Repair_Database
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}