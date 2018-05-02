<?php
/**
 * Send emails in background
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LP_Background_Global' ) ) {
	/**
	 * Class LP_Background_Global
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Global extends LP_Abstract_Background_Process {

		/**
		 * @var string
		 */
		protected $action = 'lp_background';

		/**
		 * @var int
		 */
		protected $queue_lock_time = 60;

		/**
		 * LP_Background_Global constructor.
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * @param string $action
		 * @param array  $args
		 * @param string $callback
		 */
		public static function add( $action, $args = array(), $callback = '' ) {
			$item = array(
				'action'   => $action,
				'callback' => $callback,
				'args'     => $args
			);

			$instance = self::instance();
			$instance->push_to_queue( $item );
		}

		/**
		 * @param mixed $callback
		 *
		 * @return bool
		 */
		protected function task( $callback ) {
			parent::task( $callback );

			if ( isset( $callback['action'] ) ) {
				$args = isset( $callback['args'] ) ? $callback['args'] : array();
				try {

					if ( is_callable( $callback['callback'] ) ) {
						//call_user_func_array( $callback['callback'], $args );
						call_user_func( $callback['callback'], $callback );
					}

					//do_action_ref_array( 'learn-press/background/' . $callback['action'], $args );
					do_action( 'learn-press/background/' . $callback['action'], $callback );
				}
				catch ( Exception $e ) {

				}
			}

			return false;
		}
	}
}

return LP_Background_Global::instance();