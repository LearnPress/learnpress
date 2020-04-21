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
		//Todo: can remove this class
		if ( learn_press_get_request( 'action' ) == 'heartbeat' || ! is_admin() ) {
			//add_filter( 'init', array( $this, '_update_current_user_course_expired' ) );
			//add_filter( 'init', array( $this, 'fix_bug_auto_finish_not_enrolled_course' ) );// remove this code on LP 3.2.3
		}
	}

	/**
	 * since version 3.2.2
	 * Temp method use to fix bug. It will be remove in next version
	 */
	public function fix_bug_auto_finish_not_enrolled_course(){
		global $wpdb;
		$user_id = get_current_user_id();
		if ( empty( $wpdb->learnpress_user_items ) ) {
			return;
		}
		$query = $wpdb->prepare( "
						SELECT user_item_id
						FROM `{$wpdb->learnpress_user_items}`
						WHERE 
							`user_id` = %s
							AND `ref_type` = %s
							AND `status` = %s
							AND `start_time` = %s 
						", 
						$user_id, LP_ORDER_CPT, 'finished','0000-00-00 00:00:00' );

		$user_item_ids = $wpdb->get_col( $query );

		// $user_item_ids = array(99991,99992,99993,99994,99995);// test data
		if(!empty($user_item_ids)){

			// clear item metas
			$sql_delete = $wpdb->prepare("
							DELETE
							FROM `{$wpdb->learnpress_user_itemmeta}`
							WHERE `learnpress_user_item_id` IN (".implode(', ', array_fill(0, count($user_item_ids), '%s')).")
							",
							$user_item_ids
						);
			$wpdb->query( $sql_delete );

			// update status of item to purchased
			$sql_update  = $wpdb->prepare("
							UPDATE `{$wpdb->learnpress_user_items}`
							SET `start_time_gmt` = %s,
								`end_time` = %s, 
								`end_time_gmt` = %s,
								`status` = %s
							WHERE `user_item_id` IN (".implode(', ', array_fill(0, count($user_item_ids), '%s')).")
							",
							array_merge( array('0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'purchased'), $user_item_ids)
						);
			$wpdb->query( $sql_update );
		}
	}

	/**
	 * Auto finished course when time is expired for users
	 */
	public function _update_current_user_course_expired() {
		global $wpdb, $post;
		$user_id = get_current_user_id();
		if ( empty( $wpdb->learnpress_user_items ) ) {
			return;
		}

		$query = $wpdb->prepare( "
			SELECT * 
			FROM ( 
				SELECT * 
				FROM {$wpdb->learnpress_user_items}
				WHERE item_type = %s
				AND user_id = %d AND status = %s
				AND  end_time = %s
				AND  start_time <> %s
				ORDER BY item_id, user_item_id DESC 
			) X 
			GROUP BY item_id
			LIMIT 0, 10
		", LP_COURSE_CPT, $user_id, 'enrolled', '0000-00-00 00:00:00', '0000-00-00 00:00:00' );

		$results = $wpdb->get_results( $query );

		if ( ! empty( $results ) ) {
			$course_ids = wp_list_pluck( $results, 'item_id' );
			$order_ids  = wp_list_pluck( $results, 'ref_id' );

			LP_Helper_CURD::cache_posts( array_merge( $course_ids, $order_ids ) );

			foreach ( $results as $row ) {
				$course = learn_press_get_course( $row->item_id );

				if ( ! $course ) {
					// Delete data from table user-items if the course does not exist.
					if ( get_post_type( $row->item_id ) !== LP_COURSE_CPT ) {
						LP_Repair_Database::instance()->remove_user_items_by_user_item_id( $row->user_item_id );
					}

					continue;
				}

				$check_args = array(
					'start_time' => strtotime( $row->start_time )
				);

				$expired = $course->is_expired( $row->user_id, $check_args );
				if ( $expired && 0 >= $expired ) {

					$user = learn_press_get_user( $row->user_id );
					if ( ! $user ) {
						return;
					}
					$this->_update_user_course_items_expired( $course, $user );
					$user_course = $user->get_course_data( $course->get_id() );
					$item_meta_id = $user_course->finish();
					if ( $item_meta_id ) {
						learn_press_update_user_item_meta( $item_meta_id, 'finishing_type', 'automation' );
						do_action( 'learn_press_user_finish_course_automation', $course->get_id(), $item_meta_id, $user->get_id() );
					}
				}
			}
		}
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

		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE user_item_id IN(
				SELECT max(user_item_id)
				FROM {$wpdb->prefix}learnpress_user_items
				WHERE end_time = %s
				AND item_type = %s
				AND status = %s
				AND start_time <> %s
				GROUP BY item_id, user_id
			  )
			LIMIT 0, 10
		", '0000-00-00 00:00:00', 'lp_course', 'enrolled', '0000-00-00 00:00:00' );

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
					
					// finish course without check permission
					$user_course = $user->get_course_data( $course->get_id() );
					$item_meta_id = $user_course->finish();

					if ( $item_meta_id ) {
// 						learn_press_update_user_item_meta( $item_meta_id, '_finish_type', 'automation' ); // worked with old version
						learn_press_update_user_item_meta( $item_meta_id, 'finishing_type', 'automation' );
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
							continue 2;
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
