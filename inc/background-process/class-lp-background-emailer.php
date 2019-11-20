<?php
/**
 * Send emails in background
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LP_Background_Emailer' ) ) {
	/**
	 * Class LP_Background_Emailer
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Emailer extends LP_Abstract_Background_Process {

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
		}

		/**
		 * @param mixed $callback
		 *
		 * @return bool
		 */
		protected function task( $callback ) {
			parent::task( $callback );

			if ( isset( $callback['filter'], $callback['args'] ) ) {
				try {
					LP_Emails::instance();
					do_action_ref_array( $callback['filter'] . '/notification', $callback['args'] );

				}
				catch ( Exception $e ) {

				}
			}

			return false;
		}
	}
}

return LP_Background_Emailer::instance();