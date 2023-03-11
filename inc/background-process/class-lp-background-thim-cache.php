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

if ( ! class_exists( 'LP_Background_Thim_Cache' ) ) {
	class LP_Background_Thim_Cache extends LP_Async_Request {
		protected $action = 'background_thim_cache';
		protected static $instance;

		/**
		 * Get params via $_POST and handle
		 */
		protected function handle() {
			$key  = LP_Helper::sanitize_params_submitted( $_POST['key'] ?? '' );
			$data = LP_Helper::sanitize_params_submitted( $_POST['data'] ?? '' );
			if ( empty( $key ) ) {
				return;
			}

			Thim_Cache_DB::instance()->set_value( $key, wp_unslash( $data ) );
			die();
		}

		/**
		 * @return LP_Background_Thim_Cache
		 */
		public static function instance(): self {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	// Must run instance to register ajax.
	LP_Background_Thim_Cache::instance();
}
