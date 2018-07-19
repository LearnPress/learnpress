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

			return false;
		}

	}
}
return LP_Background_Sync_Data::instance();