<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once( LP_PLUGIN_PATH . '/inc/libraries/wp-async-request.php' );
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once( LP_PLUGIN_PATH . '/inc/libraries/wp-background-process.php' );
}

if ( ! class_exists( 'LP_Background_Process' ) ) {
	/**
	 * Class LP_Background_Process
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Process extends WP_Background_Process {

		/**
		 * @var int
		 */
		protected $queue_lock_time = 60;

		/**
		 * @var string
		 */
		protected $action = '';

		/**
		 * LP_Background_Process constructor.
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'shutdown', array( $this, 'dispatch_queue' ) );
		}

		/**
		 * Dispatch queue emails
		 */
		public function dispatch_queue() {
			if ( ! empty( $this->data ) ) {
				$this->save()->dispatch();
			}
		}

		/**
		 * Schedule fallback event.
		 */
		protected function schedule_event() {
			if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
				wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
			}
		}

		protected function task($item){}

		/**
		 * Array of singleton classes.
		 *
		 * @var array
		 */
		protected static $instances = array();

		/**
		 * @return mixed
		 */
		public static function instance() {

			if ( false === ( $name = self::_get_called_class() ) ) {
				return false;
			}

			if ( empty( self::$instances[ $name ] ) ) {
				self::$instances[ $name ] = new $name();
			}

			return self::$instances[ $name ];
		}

		/**
		 * @return bool|string
		 */
		protected static function _get_called_class() {
			if ( function_exists( 'get_called_class' ) ) {
				return get_called_class();
			}

			$backtrace = debug_backtrace();

			if ( empty( $backtrace[2] ) ) {
				return false;
			}

			if ( empty( $backtrace[2]['args'][0] ) ) {
				return false;
			}

			return $backtrace[2]['args'][0];
		}
	}
}