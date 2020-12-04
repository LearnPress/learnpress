<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Background_Schedule_Items' ) ) {
	/**
	 * Class LP_Background_Schedule_Items
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Schedule_Items extends LP_Abstract_Background_Process {

		/**
		 * @var int
		 */
		protected $queue_lock_time = 60;

		/**
		 * @var string
		 */
		protected $action = 'schedule_items';

		/**
		 * @var string
		 */
		protected $transient_key = 'lp_schedule_complete_items';


		/**
		 * LP_Background_Schedule_Items constructor.
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'learn_press_schedule_items', array( $this, 'xxx' ) );

		}

		public function xxx() {
			$this->run();

			$t = date( 'H.i.s' );
			sleep( 15 );
		}

		public function cron_schedules( $schedules ) {
			$schedules['lp_cron_schedule'] = array(
				'interval' => 15,
				'display'  => __( 'Every 3 Minutes', 'learnpress' ),
			);

			return $schedules;
		}

		/**
		 * Run
		 */
		public function run() {
			if ( ! $this->has_queued() ) {
				$this->push_to_queue(
					array( 'x' => 100 )
				)->save()->dispatch();
			} else {
				$this->dispatch();
			}
		}

		/**
		 * Update user-item status.
		 * This function called in background by schedule event.
		 *
		 * @param mixed $data
		 *
		 * @editor tungnx
		 * @reason not use
		 *
		 * @return bool
		 * @since 3.3.0
		 *
		 */
		/*protected function task( $data ) {

			$settings = LP_Settings::instance();

			// If option auto finish course is turn off.
			if ( 'yes' !== $settings->get( 'auto_finish_course', 'yes' ) ) {
				die();
			}

			$curd = new LP_User_CURD();

			// Get all courses in user-items are in-progress but has expired
			$course_items = $curd->get_courses(
				array(
					'status'        => 'in-progress',
					'expired'       => true,
					'paginate'      => false,
					'no_join_users' => true,
					'limit'         => 100,
				)
			);

			if ( ! $course_items ) {
				die();
			}

			// Force auto completing course items if turn on.
			$complete_items = 'yes' === $settings->get( 'force_complete_course_items', 'yes' );

			// Cron-job auto finish course when expiration time (nhamdv).
			foreach ( $course_items as $course_item ) {
				$user        = learn_press_get_user( $course_item->user_id );
				$course_data = $user->get_course_data( $course_item->course_id );

				$finished = $course_data->finish( $complete_items );

				learn_press_update_user_item_meta( $finished, 'finishing_type', 'exceeded' );
			}

			return false;
		}*/

		/**
		 * Get the items.
		 *
		 * @return array|bool
		 */
		protected function _get_items() {
			global $wpdb;
			$queued_items      = get_transient( $this->transient_key );
			$queued_course_ids = $queued_items ? array_keys( $queued_items ) : false;
			$queued_course_ids = array_unique( $queued_course_ids );
			$exclude_items     = $queued_course_ids ? 'AND user_item_id NOT IN(' . join( ',',
					$queued_course_ids ) . ')' : '';

			$null_time = '0000-00-00 00:00:00';
			$query     = $wpdb->prepare(
				"
				SELECT user_item_id, user_id
				FROM {$wpdb->learnpress_user_items}
				WHERE item_type = %s
				AND ( end_time IS NULL OR end_time = %s OR status <> %s )
				{$exclude_items}
				LIMIT 0, 1
			",
				LP_COURSE_CPT,
				$null_time,
				'finished'
			);

			$item_courses = $wpdb->get_results( $query );

			if ( ! $item_courses ) {
				return false;
			}

			if ( ! $queued_items ) {
				$queued_items = array();
			}

			$course_item_types = learn_press_get_course_item_types();
			$format            = array_fill( 0, sizeof( $course_item_types ), '%s' );
			$args              = $course_item_types;
			$new_items         = array();

			die();

			foreach ( $item_courses as $item_course ) {
				$new_items[ $item_course->user_item_id ] = array();

				$args['end_time'] = $null_time;
				$args['status']   = 'completed';
				$args['parent']   = $item_course->user_item_id;
				$query            = $wpdb->prepare(
					"
					SELECT user_item_id
					FROM {$wpdb->learnpress_user_items}
					WHERE item_type IN(" . join( ',', $format ) . ')
					AND ( end_time IS NULL OR end_time = %s OR status <> %s )
					AND parent_id = %d
				',
					$args
				);

				$item_course_items = $wpdb->get_col( $query );
				if ( $item_course_items ) {
					$new_items[ $item_course->user_item_id ] = $item_course_items;
				}
			}

			foreach ( $new_items as $user_item_id => $items ) {
				if ( array_key_exists( $user_item_id, $queued_items ) ) {
					$queued_items[ $user_item_id ] = array_merge( $queued_items[ $user_item_id ], $items );
				} else {
					$queued_items[ $user_item_id ] = $items;
				}
			}

			set_transient( $this->transient_key, $queued_items );

			return $new_items;
		}

		/**
		 * @param array $item
		 */
		protected function finish_course( $item ) {

			$item_course = new LP_User_Item_Course( $item );
			if ( ! $item_course ) {
				return;
			}

			$user = $item_course->get_user();
			if ( ! $user ) {
				return;
			}

		}

		/**
		 * @param array $item
		 */
		protected function complete_lesson( $item ) {
			$item_course = new LP_User_Item_Course( $item );

			if ( ! $item_course ) {
				return;
			}

			$user = $item_course->get_user();

			if ( $user ) {
				return;
			}

			$user->finish_course( $item_course->get_item_id() );
		}

		/**
		 * Schedule fallback event.
		 */
		protected function schedule_event() {
			if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
				wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
			}
		}

		/**
		 * @return LP_Background_Schedule_Items
		 */
		public static function instance() {
			return parent::instance();
		}
	}
}

return LP_Background_Schedule_Items::instance();
