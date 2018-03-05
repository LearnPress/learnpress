<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LP_Background_Installer' ) ) {
	/**
	 * Class LP_Background_Installer
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Installer extends LP_Abstract_Background_Process {

		/**
		 * @var int
		 */

		/**
		 * @var string
		 */
		protected $action = 'lp_installer';

		/**
		 * LP_Background_Installer constructor.
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'wp_loaded', array( $this, 'check' ), 100 );
		}

		public function check() {
			$this->push_to_queue(
				array(
					'check_tables' => 'yes'
				)
			);
			///LP_Install::create_tables();
		}

		/**
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function task( $data ) {

			if ( ! isset( $data['check_tables'] ) ) {
				return false;
			}

			LP_Install::create_tables();

			return false;
		}

		/**
		 * @return LP_Background_Installer
		 */
		public static function instance() {
			return parent::instance();
		}
	}
}

LP_Background_Installer::instance();