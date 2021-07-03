<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Background_Single_Course' ) ) {
	/**
	 * Class LP_Background_Single_Course
	 *
	 * Single to run not schedule, run one time and done when be call
	 *
	 * @since 4.1.1
	 * @author tungnx
	 */
	class LP_Background_Single_Course extends WP_Async_Request {
		protected $prefix = 'lp';
		protected $action = 'background_single_course';
		protected static $instance;

		protected function handle() {
			// Get $_POST and handle on here
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
