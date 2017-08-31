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
		if ( learn_press_get_request( 'action' ) == 'heartbeat' || ! is_admin() ) {
			//$this->_update_user_course_expired();
		}
		add_filter( 'template_include', array( $this, 'auto_complete_course' ), 10 );
		add_filter( 'cron_schedules', array( $this, 'add_custom_cron_intervals' ), 10, 1 );

		if ( ! wp_next_scheduled( 'learn_press_schedule_update_user_items' ) ) {
			wp_schedule_event( time(), 'ten_minutes', 'learn_press_schedule_update_user_items' );
		}
		add_action( 'learn_press_schedule_update_user_items', array( $this, 'schedule_update_user_items' ) );

		if ( ! wp_next_scheduled( 'learn_press_delete_user_guest_transient' ) ) {
			wp_schedule_event( time(), 'daily', 'learn_press_delete_user_guest_transient' );
		}
		add_action( 'learn_press_delete_user_guest_transient', array( $this, 'delete_user_guest_transient' ) );
	}

	public function auto_complete_course( $template ) {
		if ( learn_press_is_course() && is_user_logged_in() ) {
			$course   = learn_press_get_course();
			$user     = learn_press_get_current_user();
			$duration = $course->get_duration();
			if( $duration && $user->has_enrolled_course( $course->id, true ) && !$user->has_finished_course( $course->id, true ) && $course->is_expired( $user->id ) <= 0 ) {
				$user->finish_course($course->id);
				$this->schedule_update_user_items();
				wp_redirect( get_permalink( $course->id ) );
			}
		}
		return $template;
	}

	function add_custom_cron_intervals( $schedules ) {
		$schedules['ten_minutes'] = array(
			'interval' => 600,
			'display'  => 'Once Every 10 Minutes'
		);
		return (array) $schedules;
	}

	public function delete_user_guest_transient() {
		global $wpdb;
		$time = time();
		$sql  = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND b.option_value < %d";
		$wpdb->query(
			$wpdb->prepare(
				$sql,
				$wpdb->esc_like( '_transient_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				$wpdb->esc_like( '_transient_learn_press_user_guest_' ) . '%',
				$time
			)
		);

		if ( is_main_site() && is_main_network() ) {
			$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
				AND a.option_name LIKE %s
				AND b.option_value < %d";
			$wpdb->query(
				$wpdb->prepare(
					$sql,
					$wpdb->esc_like( '_site_transient_' ) . '%',
					$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
					$wpdb->esc_like( '_site_transient_learn_press_user_guest_' ) . '%',
					$time
				)
			);
		}
		learn_press_reset_auto_increment( 'options' );
	}

	public function schedule_update_user_items() {
		$this->_update_user_course_expired();
		LP_Debug::instance()->add( __FUNCTION__ );
	}

	/**
	 * Auto finished course when time is expired for users
	 *
	 * @param int|array $course_id
	 * @param int|array $user_id
	 */
	private function _update_user_course_expired( $course_id = 0, $user_id = 0 ) {
		global $wpdb;
		/**
		 * Find all courses that user did not finish yet
		 */
		if ( empty( $wpdb->learnpress_user_items ) ) {
			return;
		}
		/* $query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE user_item_id IN(
				SELECT user_item_id FROM(
					SELECT user_item_id
					FROM {$wpdb->prefix}learnpress_user_items
					WHERE end_time = %s
					AND item_type = %s
					GROUP BY item_id
					ORDER BY user_item_id DESC
				) AS X
			)
			LIMIT 0, 10
		", '0000-00-00 00:00:00', 'lp_course' );*/
		$where = '';
		if ( $user_id ) {
			$where .= $wpdb->prepare( "AND user_id = %d", $user_id );
		}

		if ( $course_id ) {
			$where .= $wpdb->prepare( " AND item_id = %d", $course_id );
		}

		$groupby = '';
		if ( ! $user_id && ! $course_id ) {
			$groupby = 'GROUP BY item_id, user_id';
		}

		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE user_item_id IN(
				SELECT max(user_item_id)
				FROM {$wpdb->prefix}learnpress_user_items
				WHERE end_time = %s
				AND item_type = %s
				AND status <> %s
			  	{$where}
				{$groupby}
			  )
			LIMIT 0, 10
		", '0000-00-00 00:00:00', 'lp_course', 'finished' );

		if ( $results = $wpdb->get_results( $query ) ) {
			$ids = array();
			foreach ( $results as $row ) {
				$ids[] = $row->item_id;
			}
			_learn_press_get_courses_curriculum( $ids );
			_learn_press_count_users_enrolled_courses( $ids );
			foreach ( $results as $row ) {
				$course = learn_press_get_course( $row->item_id );
				if ( ! $course ) {
					continue;
				}
				$check_args = array(
					'start_time' => strtotime( $row->start_time )
				);
				$expired    = $course->is_expired( $row->user_id, $check_args );
				if ( 0 >= $expired ) {

					$user = learn_press_get_user( $row->user_id );
					if ( ! $user ) {
						continue;
					}
					$this->_update_user_course_items_expired( $course, $user );
					$item_meta_id = $user->finish_course( $course->id );
					if ( $item_meta_id ) {
						learn_press_update_user_item_meta( $item_meta_id, '_finish_type', 'automation' );
						do_action( 'learn_press_user_finish_course_automation', $course->id, $item_meta_id, $user->id );
					}
				}
			}
		}
		do_action( 'learn_press_update_user_course_expired' );

	}

	private function _update_user_course_items_expired( $course, $user ) {
		global $wpdb;

		/**
		 * Find all courses that user did not finish yet
		 */
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE end_time = %s
			AND start_time <> %s
			AND item_type <> %s
			AND ref_id = %d
		", '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'lp_course', $course->id );
		if ( $results = $wpdb->get_results( $query ) ) {
			foreach ( $results as $row ) {
				switch ( $row->item_type ) {
					case LP_QUIZ_CPT:
					case LP_LESSON_CPT:
						$duration = absint( get_post_meta( $row->item_id, '_lp_duration', true ) );
						if ( $duration <= 0 ) {
							continue;
						}
						if ( $row->item_type == LP_QUIZ_CPT ) {
							$results = $user->finish_quiz( $row->item_id, $course->id );
							if ( $results && $results->history_id ) {
								learn_press_update_user_item_meta( $results->history_id, '_completed_type', 'automation' );
								do_action( 'learn_press_user_complete_quiz_automation', $row->item_id, $results, $user->id );
							}
						}
						break;
					default:
						do_action( 'learn_press_user_complete_item_automation', $row->item_id, $course->id, $user->id );
				}
			}
		}
		do_action( 'learn_press_update_user_course_expired' );
	}

	public function user_course_expired( $user_id, $course_id ) {
		LP_Debug::instance()->add( sprintf( 'User %d has finish course %s', $user_id, get_the_title( $course_id ) ) );
	}
}

return new LP_Schedules();