<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LP_Background_Sync_Data' ) ) {
	/**
	 * Class LP_Background_Sync_Data
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Sync_Data extends LP_Abstract_Background_Process {

		/**
		 * @var int
		 */
		protected $queue_lock_time = 60;

		/**
		 * @var string
		 */
		protected $action = 'lp_sync_data';

		/**
		 * @var string
		 */
		protected $transient_key = 'lp_schedule_complete_items';


		/**
		 * LP_Background_Sync_Data constructor.
		 */
		public function __construct() {
			parent::__construct();
		}

		public function test() {
			$this->task( 0 );
		}

		/**
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function task( $data ) {
			$queue_user_ids = get_option( $data['option_key'] );

			if ( ! $queue_user_ids ) {
				delete_option( $data['option_key'] );
				delete_option( 'doing-sync-user-course-results' );

				return false;
			} else {
				update_option( $data['option_key'], $queue_user_ids, 'no' );
			}

			return $data;
		}

		public function is_running() {
			return false === $this->is_queue_empty();
		}

	}
}

return LP_Background_Sync_Data::instance();
