<?php
/**
 * Class LP_Background_Single_Course
 *
 * Single to run not schedule, run one time and done when be call
 *
 * @since 4.1.4
 * @author tungnx
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Background_Order' ) ) {
	class LP_Background_Single_Order extends WP_Async_Request {
		protected $prefix = 'lp';
		protected $action = 'background_single_order';
		protected static $instance;

		/**
		 * @var $lp_order LP_Order
		 */
		protected $lp_order;

		/**
		 * Get params via $_POST and handle
		 * @in_array
		 * @see LP_Course_Post_Type::save
		 */
		protected function handle() {

		}

		/**
		 * Save course post data
		 *
		 * @throws Exception
		 */
		protected function save_post() {

		}

		/**
		 * @return LP_Background_Single_Order
		 */
		public static function instance(): self {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	// Must run instance to register ajax.
	LP_Background_Single_Order::instance();
}
