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

if ( ! class_exists( 'LP_Abstract_Background_Process' ) ) {
	/**
	 * Class LP_Abstract_Background_Process
	 *
	 * @since 3.0.0
	 */
	class LP_Abstract_Background_Process extends WP_Background_Process {

		/**
		 * @var int
		 */
		protected $queue_lock_time = 60;

		/**
		 * @var string
		 */
		protected $action = '';

		/**
		 * @var bool
		 */
		protected $safe = true;

		/**
		 * @var string
		 */
		protected $_safe = '';

		/**
		 * @var string
		 */
		protected $prefix = 'lp';

		/**
		 * LP_Abstract_Background_Process constructor.
		 */
		public function __construct() {
			parent::__construct();
			/**
			 * Priority is important that will fix issue with WC cart doesnt remove
			 * after completing checkout and get order details
			 *
			 * @since 3.0.8
			 */
			//add_action( 'shutdown', array( $this, 'dispatch_queue' ), 1000 );
		}

		public function dispatch_queue() {
			if ( ! empty( $this->data ) ) {
				$this->save()->dispatch();
			}
		}

		public function is_safe( $safe = null ) {
			if ( is_bool( $safe ) ) {
				if ( $this->_safe === '' ) {
					$this->_safe = $this->safe;
				}
				$this->safe = $safe;
			}

			return $this->safe;
		}

		public function reset_safe() {
			$this->safe = $this->_safe;
		}

		/**
		 * @param mixed $data
		 *
		 * @return mixed
		 */
		public function push_to_queue( $data ) {
			// Check to preventing loop
			if ( $this->safe ) {
				if ( learn_press_is_ajax() || ! empty( $_REQUEST['action'] ) ) {
					///return $this;
				}
			}

			return parent::push_to_queue( $data );
		}

		/**
		 * Schedule fallback event.
		 */
		protected function schedule_event() {
			if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
				wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
			}
		}

		protected function task( $item ) {
			ob_start();
			print_r( $item );
			print_r( $_REQUEST );
			$msg = ob_get_clean();
			////LP_Debug::instance()->add( $msg, 'background-process-task', false, true );

			return false;
		}

		public function clear_queue() {
			$this->data = array();
			return $this;
		}

		/**
		 * Array of singleton classes.
		 *
		 * @var array
		 */
		protected static $instances = array();

		/**
		 * @return LP_Abstract_Background_Process|mixed
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