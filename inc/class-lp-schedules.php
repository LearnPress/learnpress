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
		if ( learn_press_get_request( 'action' ) == 'heartbeat' || ! is_admin() ) {
			add_filter( 'init', array( $this, 'fix_bug_auto_finish_not_enrolled_course' ) );// remove this code on LP 3.2.3
		}
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );

		$args = array( false );

		if ( ! wp_next_scheduled( 'learn_press_schedule_', $args ) ) {
			wp_schedule_event( time(), 'lp_cron_schedule', 'learn_press_schedule_', $args );
		}

		// add_action( 'plugins_loaded', array( $this, 'run' ) );
		add_action( 'learn-press/schedule-event-handler', array( $this, 'schedules' ) );

		LP_Request::register_ajax( 'cron:nopriv', array( $this, 'do_cron' ) );
	}

	public function cron_schedules( $schedules ) {
		$schedules['lp_cron_schedule'] = array(
			'interval' => 180,
			'display'  => __( 'Every 3 Minutes', 'learnpress' ),
		);

		return $schedules;
	}

	/**
	 * @since 3.3.0
	 */
	public function schedules() {
		$schedule_items = LP()->background( 'schedule-items' );
		$schedule_items->run();

		LP_Debug::instance()->add( array( $_REQUEST, $_SERVER ), 'x.' . gmdate( 'Y.m.d.H.i.s' ) . '-' . microtime( true ) );
	}

	/**
	 * Loop forever
	 *
	 * @since 3.3.0
	 */
	public function run() {
		if ( 'yes' !== get_option( '_lp_schedule_enable' ) ) {
			return;
		}

		$time      = microtime( true );
		$next_time = get_option( '_lp_schedule_next' );
		$duration  = get_option( '_lp_schedule_event_duration', 15 );// default 15 seconds
		$exceed    = $time - $next_time;

		if ( $exceed >= $duration ) {
			update_option( '_lp_schedule_next', $time );
			// Do what you want here.../ Nhamdv: oh really?
			do_action( 'learn-press/schedule-event-handler' );

			update_option( '_lp_schedule_r', 'no' );
		} else {
			if ( ! empty( $_REQUEST['lp-schedule-event'] ) ) {
				sleep( $duration - ( $exceed % $duration ) );
				update_option( '_lp_schedule_r', 'no' );
			}
		}

		if ( get_option( '_lp_schedule_r' ) !== 'yes' ) {
			update_option( '_lp_schedule_r', 'yes' );
			wp_remote_get( add_query_arg( 'lp-schedule-event', 1, get_site_url() ) );
		}
	}

	/**
	 * Execute task when cron is calling
	 *
	 * @since 3.3.0
	 */
	public function do_cron() {
		$nonce = LP_Request::get( 'sid' );

		if ( $nonce !== get_option( 'learnpress_cron_url_nonce' ) ) {
			wp_die( 'Forbidden access!' );
		}

		$schedule_items = LP()->background( 'schedule-items' );
		$schedule_items->run();

		die();
	}

	/**
	 * since version 3.2.2
	 * Temp method use to fix bug. It will be remove in next version
	 */
	public function fix_bug_auto_finish_not_enrolled_course() {
		global $wpdb;

		$user_id = get_current_user_id();

		if ( empty( $wpdb->learnpress_user_items ) ) {
			return;
		}

		$query = $wpdb->prepare(
			"
			SELECT user_item_id
			FROM `{$wpdb->learnpress_user_items}`
			WHERE
				`user_id` = %s
				AND `ref_type` = %s
				AND `status` = %s
				AND `start_time` = %s
			",
			$user_id,
			LP_ORDER_CPT,
			'finished',
			'0000-00-00 00:00:00'
		);

		$user_item_ids = $wpdb->get_col( $query );

		if ( ! empty( $user_item_ids ) ) {
			$sql_delete = $wpdb->prepare(
				"
				DELETE
				FROM `{$wpdb->learnpress_user_itemmeta}`
				WHERE `learnpress_user_item_id` IN (" . implode( ', ', array_fill( 0, count( $user_item_ids ), '%s' ) ) . ')
				',
				$user_item_ids
			);
			$wpdb->query( $sql_delete );

			// update status of item to purchased
			$sql_update = $wpdb->prepare(
				"
				UPDATE `{$wpdb->learnpress_user_items}`
				SET `start_time_gmt` = %s,
					`end_time` = %s,
					`end_time_gmt` = %s,
					`status` = %s
				WHERE `user_item_id` IN (" . implode( ', ', array_fill( 0, count( $user_item_ids ), '%s' ) ) . ')
				',
				array_merge(
					array(
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00',
						'purchased',
					),
					$user_item_ids
				)
			);

			$wpdb->query( $sql_update );
		}
	}

	/**
	 * @param int $user_item_id
	 *
	 * @return bool|LP_User_Item_Course
	 */
	protected function _get_item_course( $user_item_id ) {
		$curd = new LP_User_CURD();
		$item = $curd->get_user_item_by_id( $user_item_id );

		if ( $item ) {
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
		if ( ! $items = $this->_get_items() ) { // phpcs:ignore
			return $template;
		}

		foreach ( $items as $user_item_id => $course_items ) {

			if ( ! $item_course = $this->_get_item_course( $user_item_id ) ) { // phpcs:ignore
				continue;
			}

			if ( $item_course->is_exceeded() <= 0 ) {
				$this->background_schedule_items->push_to_queue( $data );

			}

			$data = array(
				'user_item_id' => $user_item_id,
				'items'        => isset( $course_items['items'] ) ? $course_items['items'] : array(),
			);
		}

		return $template;
	}


	function add_custom_cron_intervals( $schedules ) {
		$schedules['ten_minutes'] = array(
			'interval' => 600,
			'display'  => 'Once Every 10 Minutes',
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

	private function _update_user_course_items_expired( $course, $user ) {
		global $wpdb;

		/**
		 * Find all courses that user did not finish yet
		 */
		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE end_time = %s
			AND start_time <> %s
			AND item_type <> %s
			AND ref_id = %d
		",
			'0000-00-00 00:00:00',
			'0000-00-00 00:00:00',
			'lp_course',
			$course->get_id()
		);
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
