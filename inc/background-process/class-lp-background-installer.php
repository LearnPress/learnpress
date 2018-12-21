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

			if ( 'yes' !== get_option( 'learn_press_check_tables' ) ) {
				add_action( 'wp_loaded', array( $this, 'check' ), 100 );
			}
		}

		public function check() {
			$this->push_to_queue(
				array(
					'check_tables' => 'yes'
				)
			)->save()->dispatch();
		}

		/**
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function task( $data ) {
			parent::task( $data );

			if ( ! isset( $data['check_tables'] ) ) {
				return false;
			}

			LP_Install::create_tables();

			if ( ! $this->get_missing_tables() ) {
				update_option( 'learn_press_check_tables', 'yes', 'yes' );
			}

			return false;
		}

		/**
		 * Get all the tables are not created
		 *
		 * @return array
		 */
		protected function get_missing_tables() {
			global $wpdb;
			$query = $wpdb->prepare( "
				SHOW TABLES LIKE %s
			", '%' . $wpdb->esc_like( 'learnpress' ) . '%' );

			$tables = $wpdb->get_col( $query );

			$required_tables = get_object_vars( $wpdb );
			$required_tables = array_filter( $required_tables, array( $this, '_filter_tables' ) );

			return array_diff( $required_tables, $tables );
		}

		/**
		 * Filter callback to get all tables of LP assigned to $wpdb.
		 *
		 * @version 3.2.2
		 *
		 * @param string $prop
		 *
		 * @return bool
		 */
		protected function _filter_tables( $prop ) {
			global $wpdb;

			return is_string( $prop ) && strpos( $prop, $wpdb->prefix . 'learnpress' ) === 0;
		}

		/**
		 * @return LP_Background_Installer
		 */
		public static function instance() {
			return parent::instance();
		}
	}
}

return LP_Background_Installer::instance();