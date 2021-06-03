<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Background_Installer' ) ) {
	/**
	 * Class LP_Background_Installer
	 *
	 * @since 3.0.0
	 * @editor tungnx
	 * @reason fix temporary.
	 */
	class LP_Background_Installer extends WP_Background_Process {
		/**
		 * @var string
		 */
		protected $action = 'installer';

		protected $prefix = 'lp';

		public static $instance;

		protected $cron_interval = 10;

		/**
		 * LP_Background_Installer constructor.
		 */
		public function __construct() {
			parent::__construct();

			if ( 'yes' !== get_option( 'learn_press_check_tables' ) ) {
				$this->push_to_queue( array() )->save()->dispatch();
			}
		}

		/**
		 * @param mixed $item
		 *
		 * @return bool
		 */
		protected function task( $item ): bool {
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
			$query = $wpdb->prepare(
				'
				SHOW TABLES LIKE %s
			',
				'%' . $wpdb->esc_like( 'learnpress' ) . '%'
			);

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
		 * Schedule fallback event.
		 *
		 * Use cronjob if need
		 */
		/*protected function schedule_event() {
			if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
				wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
			}
		}*/

		/**
		 * @return LP_Background_Installer
		 */
		public static function instance() {
			//return parent::instance();
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}

return LP_Background_Installer::instance();
