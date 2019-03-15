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

			LP_Debug::instance()->add( [ $_REQUEST ], 'a.' . date( 'Y.m.d-H.i.s' ) . '-' . microtime( true ) );
			$t = date( 'H.i.s' );
			sleep( 15 );
			LP_Debug::instance()->add( $_REQUEST, date( 'Y.m.d-H.i.s' ) . '__' . $t );
		}

		public function cron_schedules( $schedules ) {
			$schedules['lp_cron_schedule'] = array(
				'interval' => 15,
				'display'  => __( 'Every 3 Minutes', 'learnpress' )
			);

			return $schedules;
		}

		public function test() {
			$this->task( 0 );
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

//		public function run() {
//
//			return;
//			$requestId   = LP_Request::get( 'lp-background-process' );
//			$scheduleId  = $this->get_id();
//			$lockTime    = get_option( $scheduleId );
//			$currentTime = microtime( true );
//			$exceedTime  = $currentTime - $lockTime;
//
////			LP_Debug::instance()->add( [
////				$requestId,
////				$scheduleId,
////				$exceedTime
////			], '11111.' . date( 'Y.m.d.H.i.s' ) . '-' . microtime( true ) );
//
//			//if ( $requestId !== $scheduleId ) {
//			if ( ! $lockTime || ( $exceedTime >= 15 ) ) {
//				update_option( $scheduleId, $currentTime, false );
//
//				//LP_Debug::instance()->add( $_REQUEST, '22222-' . date( 'Y.m.d.H.i.s' ) . '-' . microtime( true ) );
//
//				if ( ! $this->has_queued() ) {
//					$this->push_to_queue(
//						array( 'x' => 100 )
//					)->save()->dispatch();
//				} else {
//					$this->dispatch();
//				}
//
//			} else {
//				$this->next();
//			}
//
//
//			//} else {
//			//$this->next();
//			//}
//		}
//
//		protected function next() {
//			$requestId   = LP_Request::get( 'lp-background-process' );
//			$scheduleId  = $this->get_id();
//			$next        = get_option( "{$scheduleId}-next" );
//			$currentTime = microtime( true );
//
//
//			if ( $next !== 'yes' ) {
//				update_option( "{$this->get_id()}-next", 'yes', false );
//
//				$lockTime   = get_option( $scheduleId );
//				$exceedTime = $currentTime - $lockTime;
//
//				//LP_Debug::instance()->add( [15 - $exceedTime], '33333.' . date( 'Y.m.d.H.i.s' ) . '-' . microtime( true ) );
//
//				//if($exceedTime >=15) {
//				if ( $requestId !== $scheduleId ) {
//					$exceedTime = $exceedTime % 15;
//
//					sleep( 15 - $exceedTime );
//					$this->run();
//				} else {
//					if ( $exceedTime >= 15 ) {
//						$this->run();
//					}
//				}
//
//
//				//
//				//}
//			}
//		}


		/**
		 * Update user-item status.
		 * This function called in background by schedule event.
		 *
		 * @since 3.x.x
		 *
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function task( $data ) {


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
					'limit'         => 100
				)
			);

			if ( ! $course_items ) {
				die();
			}

			// Force auto completing course items if turn on.
			$complete_items = 'yes' === $settings->get( 'force_complete_course_items', 'yes' );

			foreach ( $course_items as $course_item ) {

				$user        = learn_press_get_user( $course_item->user_id );
				$course_data = $user->get_course_data( $course_item->course_id );

				$course_data->finish( $complete_items );
				LP_Debug::instance()->add( '', $course_data->get_user_id() . '-' . $course_data->get_course_id() . '.completed' );
			}

			//update_option( '_lp_schedule_x', absint( get_option( '_lp_schedule_x', 0 ) ) + 1 );
			//update_option( '_lp_schedule_z', [ $_REQUEST, $_SERVER ] );

			return false;

			parent::task( $data );

			$x = ! empty( $_REQUEST['xxx'] );
			if ( $x ) {
				$this->_get_items();
				if ( ! $items = get_transient( $this->transient_key ) ) {
					return false;
				}

				$curd = new LP_User_CURD();

				foreach ( $items as $course_item_id => $item_data ) {

					if ( ! $item_course = $curd->get_user_item_course( $course_item_id ) ) {
						continue;
					}

					$course_exceeded = $item_course->is_exceeded() <= 0;

					if ( ! empty( $item_data ) ) {
						foreach ( $item_data as $user_item_id ) {

							if ( ! $user_item = $item_course->get_item_by_user_item_id( $user_item_id ) ) {
								continue;
							}
							switch ( $user_item->get_post_type() ) {
								case LP_QUIZ_CPT:
									if ( $user_item->get_status() == 'started' ) {
										if ( ( $item_course->is_finished() || $course_exceeded ) || $user_item->is_exceeded() <= 0 ) {
											$user_item->complete();
										}
									}
									break;
								case LP_LESSON_CPT:
									if ( $user_item->is_exceeded() <= 0 ) {
										$user_item->complete();
									}
									break;
								default:
									do_action( 'learn-press/schedule/auto-complete-item', $user_item_id );
							}
						}
					}

					if ( ( ( $exceeded = $item_course->is_exceeded() ) <= 0 ) && ( $item_course->get_status() === 'enrolled' ) ) {
						$item_course->finish();

						$start_time = $item_course->get_start_time()->getTimestamp();
						$duration   = $item_course->get_course()->get_duration();

						learn_press_update_user_item_meta( $item_course->get_user_item_id(), 'via', 'schedule' );
						learn_press_update_user_item_meta( $item_course->get_user_item_id(), 'exceeded', $exceeded );
					}
				}

				remove_action( 'shutdown', array( $this, 'dispatch_queue' ) );
			}

			//LP_Debug::instance()->add( 'Auto completing item', 'auto-complete-items', false, true );

			return false;
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
				SELECT user_item_id, user_id
				FROM {$wpdb->learnpress_user_items}
				WHERE item_type = %s
				AND ( end_time IS NULL OR end_time = %s OR status <> %s )
				{$exclude_items}
				LIMIT 0, 1
			", LP_COURSE_CPT, $null_time, 'finished' );

			if ( ! $item_courses = $wpdb->get_results( $query ) ) {
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