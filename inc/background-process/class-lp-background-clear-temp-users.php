<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LP_Background_Clear_Temp_Users' ) ) {
	/**
	 * Class LP_Background_Clear_Temp_Users
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Clear_Temp_Users extends LP_Abstract_Background_Process {

		/**
		 * @var int
		 */
		protected $queue_lock_time = 60;

		/**
		 * @var string
		 */
		protected $action = 'lp_clear_temp_users';

		/**
		 * @var string
		 */
		protected $transient_key = 'lp_schedule_clear_temp_users';


		/**
		 * LP_Background_Clear_Temp_Users constructor.
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function task( $data ) {
			global $wpdb;

			parent::task( $data );

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
	}
}