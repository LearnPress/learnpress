<?php
/**
 * Class LP_Background_Single_Course
 *
 * Single to run not schedule, run one time and done when be call
 *
 * @since 4.1.1
 * @author tungnx
 * @version 1.0.1
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Background_Single_Course' ) ) {
	class LP_Background_Single_Course extends WP_Async_Request {
		protected $prefix = 'lp';
		protected $action = 'background_single_course';
		protected static $instance;
		/**
		 * @var $lp_course_db LP_Course_DB
		 */
		protected $lp_course_db;
		/**
		 * @var $lp_course LP_Course
		 */
		protected $lp_course;

		/**
		 * Get params via $_POST and handle
		 * @in_array
		 * @see LP_Course_Post_Type::save
		 */
		protected function handle() {
			$this->lp_course_db = LP_Course_DB::getInstance();

			try {
				if ( ! isset( $_POST['handle_name'] ) || empty( $_POST['handle_name'] )
					|| ! isset( $_POST['course_id'] ) || empty( $_POST['course_id'] ) ) {
					return;
				}

				$course_id = (int) $_POST['course_id'];

				$this->lp_course = learn_press_get_course( $course_id );

				if ( empty( $this->lp_course ) ) {
					return;
				}

				switch ( LP_Helper::sanitize_params_submitted( $_POST['handle_name'] ) ) {
					case 'save_post':
						$this->save_post();
						break;
					default:
						break;
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Save course post data
		 *
		 * @throws Exception
		 */
		protected function save_post() {
			$lp_course_db = $this->lp_course_db;
			$lp_course    = $this->lp_course;

			$this->save_extra_info();

			$this->review_post_author();

			// Clear cache
			$lp_course_cache = LP_Course_Cache::instance();
			$key_cache_arr   = [];
			foreach ( $key_cache_arr as $key_cache ) {
				$lp_course_cache->clear( $key_cache );
			}
			// End
		}

		/**
		 * Save Extra info of course
		 *
		 * @author tungnx
		 * @since 4.1.4.1
		 * @version 1.0.0
		 */
		protected function save_extra_info() {
			$lp_course_db    = $this->lp_course_db;
			$lp_course       = $this->lp_course;
			$lp_course_cache = LP_Course_Cache::instance();
			$course_id       = $lp_course->get_id();

			try {
				$extra_info = $this->lp_course->get_info_extra_for_fast_query();

				// Get and set first item id
				// Clean cache
				$key_cache_first_item_id = "$course_id/first_item_id";
				$lp_course_cache->clear( $key_cache_first_item_id );
				$first_item_id             = $lp_course_db->get_first_item_id( $lp_course->get_id() );
				$extra_info->first_item_id = $first_item_id;

				// Get and set total items courses
				// Clean cache
				$key_cache_total_items = "$course_id/total_items";
				$lp_course_cache->clear( $key_cache_total_items );
				$total_items             = $lp_course_db->get_total_items( $lp_course->get_id() );
				$extra_info->total_items = $total_items;

				// Save post meta
				$lp_course->set_info_extra_for_fast_query( $extra_info );
				// End set first item id
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Check user is Instructor and enable review post of Instructor
		 *
		 * @author tungnx
		 * @since 4.1.4.1
		 * @version 1.0.0
		 */
		protected function review_post_author() {
			$lp_course = $this->lp_course;

			$user            = learn_press_get_current_user();
			$required_review = LP_Settings::get_option( 'required_review', 'yes' ) === 'yes';

			if ( $user->is_instructor() && $required_review ) {
				wp_update_post(
					array(
						'ID'          => $lp_course->get_id(),
						'post_status' => 'pending',
					),
					array( '%d', '%s' )
				);
			}
			// End
		}

		/**
		 * @return LP_Background_Single_Course
		 */
		public static function instance(): self {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	// Must run instance to register ajax.
	LP_Background_Single_Course::instance();
}
