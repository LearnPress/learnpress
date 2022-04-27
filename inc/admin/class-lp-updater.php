<?php

/**
 * Class LP_Updater
 *
 * Update helper class providing update functions
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */
class LP_Updater {
	/**
	 * Map version upgrade.
	 *
	 * @var array
	 */
	public $db_map_version = array();

	/**
	 * LP_Updater constructor.
	 */
	protected function __construct() {
		$this->db_map_version = apply_filters(
			'lp/upgrade/db/map_version',
			array(
				'3' => 4, // DB v3 need up DB v4
				'4' => 5, // DB v4 need up DB v5
			)
		);
		add_action( 'admin_notices', array( $this, 'check_update_database_message' ), 0 );
	}

	/**
	 * Show message needs upgrades database compatible with LP version current.
	 */
	public function check_update_database_message() {
		if ( ! $this->check_lp_db_need_upgrade() ) {
			return;
		}

		learn_press_admin_view( 'updates/html-update-message' );
	}

	/**
	 * Check LP Database need upgrade.
	 *
	 * @return bool|int
	 * @author tungnx
	 * @version 1.0.1
	 * @since 4.0.0
	 */
	public function check_lp_db_need_upgrade() {
		if ( ! current_user_can( 'administrator' ) ) {
			return false;
		}

		$db_current_version = (int) get_option( LP_KEY_DB_VERSION, false );
		if ( ! $db_current_version ) {
			return false;
		}

		$db_require_version = LearnPress::instance()->db_version;
		if ( $db_require_version <= $db_current_version ) {
			return false;
		}

		if ( array_key_exists( $db_current_version, $this->db_map_version ) ) {
			return $this->db_map_version[ $db_current_version ];
		}

		/**
		 * For case not have key "learnpress_db_version" on DB, still have columns of learnpress.
		 * After a long time, need remove fix fast
		 */
		$lp_db                                = LP_Database::getInstance();
		$check_tb_lp_order_items_exists       = $lp_db->check_table_exists( $lp_db->tb_lp_order_items );
		$check_tb_lp_user_item_results_exists = $lp_db->check_table_exists( $lp_db->tb_lp_user_item_results );
		$check_col_item_id_on_lp_order_items  = $lp_db->check_col_table( $lp_db->tb_lp_order_items, 'item_id' );

		if ( $check_tb_lp_order_items_exists && ( ! $check_tb_lp_user_item_results_exists || ! $check_col_item_id_on_lp_order_items ) ) {
			update_option( LP_KEY_DB_VERSION, 3 );

			return $this->db_map_version['3'];
		}

		// End.

		return false;
	}

	/**
	 * Load file upgrade database.
	 *
	 * @return null|object
	 * @author tungnx
	 * @version 1.0.1
	 * @since 4.0.0
	 */
	public function load_file_version_upgrade_db() {
		$class_handle     = null;
		$db_version_up_to = $this->check_lp_db_need_upgrade();

		if ( $db_version_up_to ) {
			$file_update = LP_PLUGIN_PATH . 'inc/updates/learnpress-upgrade-' . $db_version_up_to . '.php';

			if ( file_exists( $file_update ) ) {
				include_once $file_update;
				$name_class_handle_upgrade = 'LP_Upgrade_' . $db_version_up_to;

				if ( class_exists( $name_class_handle_upgrade )
					&& is_callable( array( $name_class_handle_upgrade, 'get_instance' ) ) ) {
					$class_handle = $name_class_handle_upgrade::get_instance();
				}
			}
		}

		return $class_handle;
	}

	/**
	 * Get singleton instance of the class.
	 *
	 * @return bool|LP_Updater
	 */
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}

return LP_Updater::instance();
