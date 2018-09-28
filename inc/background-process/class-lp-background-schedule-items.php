<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		protected $action = 'lp_schedule_items';

		/**
		 * @var string
		 */
		protected $transient_key = 'lp_schedule_complete_items';


		/**
		 * LP_Background_Schedule_Items constructor.
		 */
		public function __construct() {
			parent::__construct();
			//add_action( 'learn-press/background-process-completed', array( $this, 'run' ) );
			//$this->run();
		}

		public function test() {
			$this->task( 0 );
		}

		public function run() {
			if ( $this->is_queue_empty() ) {
				$this->push_to_queue( array() );
				$this->save()->dispatch();
			}
		}

		public function process() {
			$this->task( array() );
		}

		/**
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function task( $data ) {
			parent::task( $data );
			$queue = $this->get_item_courses();

			if ( $queue['pending'] ) {

				$processed_ids = array();
				$remove_ids    = array();
				$process_ids   = array_splice( $queue['pending'], 0, 1 );
				$curd          = new LP_User_CURD();

				foreach ( $process_ids as $course_item_id ) {

					if ( ! $item_course = $curd->get_user_item_course( $course_item_id ) ) {
						$remove_ids[] = $course_item_id;
						continue;
					}

					$course_exceeded = $item_course->is_exceeded() <= 0;

//					if ( ! empty( $item_data ) ) {
//						foreach ( $item_data as $user_item_id ) {
//
//							if ( ! $user_item = $item_course->get_item_by_user_item_id( $user_item_id ) ) {
//								continue;
//							}
//							switch ( $user_item->get_post_type() ) {
//								case LP_QUIZ_CPT:
//									if ( $user_item->get_status() == 'started' ) {
//										if ( ( $item_course->is_finished() || $course_exceeded ) || $user_item->is_exceeded() <= 0 ) {
//											$user_item->complete();
//										}
//									}
//									break;
//								case LP_LESSON_CPT:
//									if ( $user_item->is_exceeded() <= 0 ) {
//										$user_item->complete();
//									}
//									break;
//								default:
//									do_action( 'learn-press/schedule/auto-complete-item', $user_item_id );
//							}
//						}
//					}
					global $wpdb;
					define( 'SAVEQUERIES', true );
					if ( ( ( $exceeded = $item_course->is_exceeded() ) <= 0 ) && ( $item_course->get_status() === 'enrolled' ) ) {
						$item_course->finish();

						LP_Debug::instance()->add( $item_course, 'def.txt', '', true );

						$start_time = $item_course->get_start_time()->getTimestamp();
						$duration   = $item_course->get_course()->get_duration();

						learn_press_update_user_item_meta( $item_course->get_user_item_id(), 'via', 'schedule' );
						learn_press_update_user_item_meta( $item_course->get_user_item_id(), 'exceeded', $exceeded );

						$processed_ids[] = $course_item_id;
					}
					LP_Debug::instance()->add( $wpdb->queries, 'abc', '', true );
				}

				$queue['processed'] = array_diff( array_merge( $queue['processed'], $processed_ids ), $remove_ids );
				$queue['all']       = array_diff( $queue['all'], $remove_ids );
				$queue['pending']   = array_diff( $queue['pending'], $processed_ids );

				update_option( 'learnpress_schedule_courses', $queue );
				//remove_action( 'shutdown', array( $this, 'dispatch_queue' ) );
			}

			return false;
		}

		public function get_item_courses() {
			global $wpdb;
			$course_ids = $this->get_queued_courses();

			$exclude_items = $course_ids['all'] ? "AND user_item_id NOT IN(" . join( ',', $course_ids['all'] ) . ")" : '';

			$null_time = '0000-00-00 00:00:00';
			$query     = $wpdb->prepare( "
				SELECT user_item_id
				FROM {$wpdb->learnpress_user_items}
				WHERE item_type = %s
				AND ( end_time IS NULL OR end_time = %s OR status <> %s )
				{$exclude_items}
				LIMIT 0, 1
			", LP_COURSE_CPT, $null_time, 'finished' );

			if ( $item_courses = $wpdb->get_col( $query ) ) {
				$course_ids['pending'] = array_merge( $course_ids['pending'], $item_courses );
				$course_ids['all']     = array_merge( $course_ids['pending'], $course_ids['processed'] );
			}

			update_option( 'learnpress_schedule_courses', $course_ids );


			LP_Debug::instance()->add( $course_ids, 'auto-complete-items', false, true );

			return $course_ids;
		}

		public function get_queued_courses() {
			$queued_courses = get_option( 'learnpress_schedule_courses' );
			if ( ! $queued_courses ) {
				$queued_courses = array(
					'processed' => array(),
					'pending'   => array(),
					'all'       => array()
				);
			}

			return $queued_courses;
		}

		protected function _filter_processed_courses( $v, $k ) {
			return $v;
		}

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
			$exclude_items     = $queued_course_ids ? "AND user_item_id NOT IN(" . join( ',', $queued_course_ids ) . ")" : '';

			$null_time = '0000-00-00 00:00:00';
			$query     = $wpdb->prepare( "
				SELECT user_item_id
				FROM {$wpdb->learnpress_user_items}
				WHERE item_type = %s
				AND ( end_time IS NULL OR end_time = %s OR status <> %s )
				{$exclude_items}
				LIMIT 0, 1
			", LP_COURSE_CPT, $null_time, 'finished' );

			if ( ! $item_courses = $wpdb->get_col( $query ) ) {
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
				$query            = $wpdb->prepare( "
					SELECT user_item_id
					FROM {$wpdb->learnpress_user_items}
					WHERE item_type IN(" . join( ',', $format ) . ")
					AND ( end_time IS NULL OR end_time = %s OR status <> %s )
					AND parent_id = %d
				", $args );

				if ( $item_course_items = $wpdb->get_col( $query ) ) {
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

			if ( ! $item_course = new LP_User_Item_Course( $item ) ) {
				return;
			}

			if ( ! $user = $item_course->get_user() ) {
				return;
			}

		}

		/**
		 * @param array $item
		 */
		protected function complete_lesson( $item ) {

			if ( ! $item_course = new LP_User_Item_Course( $item ) ) {
				return;
			}

			if ( $user = $item_course->get_user() ) {
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