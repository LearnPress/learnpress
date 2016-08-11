<?php

/**
 * Class LP_Schedules
 *
 * Manage all schedules
 */
class LP_Schedules {
	/**
	 * LP_Schedules constructor.
	 */
	public function __construct() {
		//add_action( 'learn_press_user_enrolled_course', array( $this, 'user_enrolled_course' ), 100, 3 );
		$this->_update_user_course_expired();
		//add_action( 'learn_press_update_user_course_schedule', array( $this, 'user_course_schedule' ) );
	}

	/**
	 * Auto finished course when time is expired for users
	 */
	private function _update_user_course_expired() {
		global $wpdb;

		/**
		 * Find all courses that user did not finish yet
		 */
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE end_time = %s
			AND item_type = %s
		", '0000-00-00 00:00:00', 'lp_course' );
		if ( $results = $wpdb->get_results( $query ) ) {

			$when         = array();
			$query_params = array();
			$where        = array();

			foreach ( $results as $row ) {
				$course = learn_press_get_course( $row->item_id );
				$expired = $course->is_expired( $row->user_id );
				if ( 0 >= $expired ) {
					$user        = learn_press_get_user( $row->user_id );
					$course_info = $user->get_course_info( $row->item_id );

					$when[]         = 'WHEN user_item_id = %d THEN %s';
					$query_params[] = $row->user_item_id;
					$query_params[] = date( 'Y-m-d H:i:s', strtotime( $course_info['start'] ) + $course->get_duration() );
					$where[]        = $row->user_item_id;
				}
			}
			if ( sizeof( $when ) ) {
				/**
				 * Update course's end time
				 */
				$query_params = array_merge( $query_params, $where );
				$query_update = $wpdb->prepare( "
					UPDATE {$wpdb->prefix}learnpress_user_items
					SET end_time = CASE
						" . join( "\n", $when ) . "
					END
					WHERE user_item_id IN(" . join( ',', $where ) . ")
				", $query_params );
				$wpdb->query( $query_update );

				// Update course's status to finished
				$query_params = $where;
				array_unshift( $query_params, 'finished' );
				$query = $wpdb->prepare( "
						UPDATE {$wpdb->prefix}learnpress_user_items
						SET status = %s
						WHERE user_item_id IN(" . join( ',', $where ) . ")
				", $query_params );
				$wpdb->query( $query );
			}
		}
		do_action( 'learn_press_update_user_course_expired' );

	}

	public function user_course_expired( $user_id, $course_id ) {
		LP_Debug::instance()->add( sprintf( 'User %d has finish course %s', $user_id, get_the_title( $course_id ) ) );
	}
}

return new LP_Schedules();