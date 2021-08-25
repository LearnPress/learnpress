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

				$course_id = $_POST['course_id'];

				$this->lp_course = new LP_Course( $course_id );

				switch ( $_POST['handle_name'] ) {
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

			// Set first item id
			$first_item_id = $lp_course_db->get_first_item_id( $lp_course->get_id() );
			$extra_info    = $this->lp_course->get_info_extra_for_fast_query();

			$extra_info->first_item_id = $first_item_id;

			// Save post meta
			$lp_course->set_info_extra_for_fast_query( $extra_info );
			// End set first item id

			// Check user is Instructor and enable review post of Instructor
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

			// Clear cache
			$lp_course_cache = LP_Course_Cache::instance();
			$key_cache_arr   = [];
			foreach ( $key_cache_arr as $key_cache ) {
				$lp_course_cache->clear( $key_cache );
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
