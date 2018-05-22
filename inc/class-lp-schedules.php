<?php

/**
 * Class LP_Schedules
 *
 * Manage all schedules
 */
class LP_Schedules {

	/**
	 * @var LP_Background_Schedule_Items
	 */
	protected $background_schedule_items = null;

	/**
	 * LP_Schedules constructor.
	 */
	public function __construct() {

		//$this->background_schedule_items = new LP_Background_Schedule_Items();

		if ( learn_press_get_request( 'action' ) == 'heartbeat' || ! is_admin() ) {
			//$this->_update_user_course_expired();
		}
		//add_filter( 'init', array( $this, 'queue_items' ), 10 );

//		add_filter( 'cron_schedules', array( $this, 'add_custom_cron_intervals' ), 10, 1 );

		return;
		if ( ! wp_next_scheduled( 'learn_press_schedule_update_user_items' ) ) {
			wp_schedule_event( time(), 'ten_minutes', 'learn_press_schedule_update_user_items' );
		}
		add_action( 'learn_press_schedule_update_user_items', array( $this, 'schedule_update_user_items' ) );

		if ( ! wp_next_scheduled( 'learn_press_delete_user_guest_transient' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'learn_press_delete_user_guest_transient' );
		}
		add_action( 'learn_press_delete_user_guest_transient', array( $this, 'delete_user_guest_transient' ) );
	}

	/**
	 * @param int $user_item_id
	 *
	 * @return bool|LP_User_Item_Course
	 */
	protected function _get_item_course( $user_item_id ) {
		$curd = new LP_User_CURD();

		if ( $item = $curd->get_user_item_by_id( $user_item_id ) ) {
			return new LP_User_Item_Course( $item );
		}

		return false;
	}

	/**
	 * Add the items need to mark as completed into queue
	 * for running in background.
	 *
	 * @param string $template
	 *
	 * @return mixed
	 */
	public function queue_items() {
		LP()->add_background_task( array( 'auto-complete-item' => rand() ), 'schedule-items' );

		return false;
		if ( ! $items = $this->_get_items() ) {
			return $template;
		}

		foreach ( $items as $user_item_id => $course_items ) {

			if ( ! $item_course = $this->_get_item_course( $user_item_id ) ) {
				continue;
			}

			if ( $item_course->is_exceeded() <= 0 ) {
				$this->background_schedule_items->push_to_queue( $data );

			}

			$data = array(
				'user_item_id' => $user_item_id,
				'items'        => isset( $course_items['items'] ) ? $course_items['items'] : array()
			);

			/**
			 * if ( isset( $course_items['items'] ) ) {
			 * foreach ( $course_items['items'] as $user_item_id ) {
			 * if ( $item = $item_course->get_item( $user_item_id ) ) {
			 * if ( $item->is_exceeded() ) {
			 * $data['items'][] = $user_item_id;
			 * }
			 * }
			 * }
			 * }*/
			// Add to queue for processing in background
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

		$sql   = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND a.option_name LIKE %s
			AND b.option_value < %d";
		$query = $wpdb->prepare(
			$sql,
			$wpdb->esc_like( '_transient_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_' ) . '%',
			$wpdb->esc_like( '_transient_learn_press_user_guest_' . '%' ),
			$time
		);
		$wpdb->query( $query );

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
					$wpdb->esc_like( '_site_transient_learn_press_user_guest_' . '%' ),
					$time
				)
			);
		}
		learn_press_reset_auto_increment( 'options' );
	}

	public function schedule_update_user_items() {
		$this->_update_user_course_expired();
	}

	/**
	 * Auto finished course when time is expired for users
	 */
	private function _update_user_course_expired() {
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

		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE user_item_id IN(
				SELECT max(user_item_id)
				FROM {$wpdb->prefix}learnpress_user_items
				WHERE end_time = %s
				AND item_type = %s
				AND status <> %s
				GROUP BY item_id, user_id
			  )
			LIMIT 0, 10
		", '0000-00-00 00:00:00', 'lp_course', 'finished' );

		if ( $results = $wpdb->get_results( $query ) ) {
			$ids = array();
			foreach ( $results as $row ) {
				$ids[] = $row->item_id;
			}
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
						return;
					}
					$this->_update_user_course_items_expired( $course, $user );
					$item_meta_id = $user->finish_course( $course->get_id() );
					if ( $item_meta_id ) {
						learn_press_update_user_item_meta( $item_meta_id, '_finish_type', 'automation' );
						do_action( 'learn_press_user_finish_course_automation', $course->get_id(), $item_meta_id, $user->get_id() );
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
		", '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'lp_course', $course->get_id() );
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
							$results = $user->finish_quiz( $row->item_id, $course->get_id() );
							if ( $results && $results->history_id ) {
								learn_press_update_user_item_meta( $results->history_id, '_completed_type', 'automation' );
								do_action( 'learn_press_user_complete_quiz_automation', $row->item_id, $results, $user->get_id() );
							}
						}
						break;
					default:
						do_action( 'learn_press_user_complete_item_automation', $row->item_id, $course->get_id(), $user->get_id() );
				}
			}
		}
		do_action( 'learn_press_update_user_course_expired' );
	}

	public function user_course_expired( $user_id, $course_id ) {
	}
}

return new LP_Schedules();