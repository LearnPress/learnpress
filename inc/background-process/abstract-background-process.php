<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
		 * @var array
		 *
		 * @since 3.3.0
		 */
		protected $query_args = array();

		/**
		 * LP_Abstract_Background_Process constructor.
		 */
		public function __construct() {
			parent::__construct();

			$this->query_args = array(
				'lp-background-process' => $this->get_id(),
			);

			/**
			 * Priority is important that will fix issue with WC cart doesnt remove
			 * after completing checkout and get order details
			 *
			 * @since 3.0.8
			 */
			// add_action( 'shutdown', array( $this, 'dispatch_queue' ), 1000 );
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
					// return $this;
				}
			}

			return parent::push_to_queue( $data );
		}

		/**
		 * Get unique ID
		 *
		 * @since 3.3.0
		 *
		 * @return mixed|string
		 */
		public function get_id() {
			return $this->identifier;
		}

		/**
		 * Schedule fallback event.
		 */
		protected function schedule_event() {
			if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
				wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
			}
		}

		/**
		 * @since 3.3.0
		 *
		 * @return bool
		 */
		public function has_queued() {
			return ! $this->is_queue_empty();
		}


		protected function task( $item ) {
			ob_start();

			print_r( $item );
			print_r( $_REQUEST );

			$msg = ob_get_clean();

			return false;
		}

		/**
		 * Get query args
		 *
		 * @return array
		 */
		protected function get_query_args() {
			return array_merge(
				array(
					'action' => $this->identifier,
					'nonce'  => wp_create_nonce( $this->identifier ),
				),
				$this->query_args
			);
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
			$name = self::_get_called_class();

			if ( false === $name ) {
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
