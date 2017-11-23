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

if ( ! class_exists( 'LP_Background_Clear_Temp_Users' ) ) {
	/**
	 * Class LP_Background_Clear_Temp_Users
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Clear_Temp_Users extends WP_Background_Process {

		/**
		 * @var LP_Background_Clear_Temp_Users
		 */
		protected static $_instance = null;

		/**
		 * @var int
		 */
		protected $queue_lock_time = 60;

		/**
		 * @var string
		 */
		protected $action = 'lp_clear_temp_users';

		protected $transient_key = 'lp_schedule_complete_items';


		/**
		 * LP_Background_Clear_Temp_Users constructor.
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
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function task( $data ) {
			global $wpdb;

			if ( ! empty( $data['action'] ) && 'clear_temp_users' == $data['action'] ) {
				$query = $wpdb->prepare( "
					DELETE a.*, b.*
					FROM {$wpdb->prefix}learnpress_user_items a
					INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta b
					WHERE a.user_item_id = b.learnpress_user_item_id
					AND a.user_id = %d
				", $data['users'] );
				$wpdb->query( $query );
			}

			return false;
		}

		protected function _get_items() {
		}


		/**
		 * @return LP_Background_Clear_Temp_Users
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
}