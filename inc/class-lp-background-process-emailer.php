<?php
/**
 * Send emails in background
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once( dirname( __FILE__ ) . '/libraries/wp-async-request.php' );
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once( dirname( __FILE__ ) . '/libraries/wp-background-process.php' );
}

if ( ! class_exists( 'LP_Background_Process_Emailer' ) ) {
	/**
	 * Class LP_Background_Process_Emailer
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Process_Emailer extends WP_Background_Process {

		/**
		 * @var string
		 */
		protected $action = 'lp_mailer';

		/**
		 * LP_Background_Process_Emailer constructor.
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'shutdown', array( $this, 'dispatch_queue' ) );
		}

		/**
		 * Dispatch queue emails
		 */
		public function dispatch_queue(){
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
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						trigger_error( 'Transactional email triggered fatal error for callback ' . $callback['filter'], E_USER_WARNING );
					}
				}
			}

			return false;
		}
	}
}