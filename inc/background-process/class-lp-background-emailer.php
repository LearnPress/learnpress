<?php
/**
 * Send emails in background
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once( LP_PLUGIN_PATH . '/inc/libraries/wp-async-request.php' );
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once( LP_PLUGIN_PATH . '/inc/libraries/wp-background-process.php' );
}

if ( ! class_exists( 'LP_Background_Emailer' ) ) {
	/**
	 * Class LP_Background_Emailer
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Emailer extends WP_Background_Process {

		/**
		 * @var string
		 */
		protected $action = 'lp_mailer';

		/**
		 * @var int
		 */
		protected $queue_lock_time = 3600;

		/**
		 * LP_Background_Emailer constructor.
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'shutdown', array( $this, 'dispatch_queue' ) );
		}

		/**
		 * Dispatch queue emails
		 */
		public function dispatch_queue() {
			print_r( $this->data );
			die();

			if ( ! empty( $this->data ) ) {
				$this->save()->dispatch();
			}


		}

		/**
		 * @param mixed $callback
		 *
		 * @return bool
		 */
		protected function task( $callback ) {

			if ( isset( $callback['filter'], $callback['args'] ) ) {
				try {
					LP_Emails::send_email( $callback['filter'], $callback['args'] );
				}
				catch ( Exception $e ) {

				}
			}

			return false;
		}
	}
}