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
	 * @var array|mixed|void .
	 */
	public $db_map_version = array();
	/**
	 * Array of update patches.
	 *
	 * @var array
	 */
	protected $_update_files = array();

	/**
	 * LP_Updater constructor.
	 */
	protected function __construct() {
		/**
		 * When modify or create new Tables, need change version.
		 * Ex: 5.x.x => 6.0.0
		 */
		//      $this->db_map_version = apply_filters(
		//          'lp/upgrade/db/map_version',
		//          array(
		//              '4' => '3', // 4.0.0 <=> 3.x.x
		//              '5' => '4', // 5.0.0 <=> 4.x.x
		//          )
		//      );

		$this->db_map_version = apply_filters(
			'lp/upgrade/db/map_version',
			array(
				'3' => 4, // DB v3 need up DB v4
				'4' => 5, // DB v4 need up DB v5
			)
		);
		//      add_action( 'admin_init', array( $this, 'do_update' ) );
		add_action( 'admin_notices', array( $this, 'check_update_database_message' ), 0 );

		//      if ( 'yes' === get_option( 'do-update-learnpress' ) ) {
		//          add_action( 'admin_notices', array( $this, 'update_message' ) );
		//      }

		//LP_Request::register_ajax( 'check-updated', array( $this, 'check_updated' ) );

		//add_action( 'wp_ajax_lp_update_database', array( $this, 'update_database_ajax' ) );
	}

	/*public function update_database_ajax() {
		$this->_do_update();

		$db_version     = get_option( 'learnpress_db_version' );
		$latest_version = $this->get_latest_version();
		$response       = array();

		if ( version_compare( $db_version, $latest_version, '>=' ) ) {
			$response['status']  = 'success';
			$response['message'] = esc_html__( 'LearnPress has just updated to latest version.', 'learnpress' );
		} else {
			$response['status'] = 'error';
		}

		wp_send_json( $response );
		die();
	}*/

	//  public function check_updated() {
	//      $this->do_update();
	//
	//      $db_version     = get_option( 'learnpress_db_version' );
	//      $latest_version = $this->get_latest_version();
	//      $response       = array();
	//      $next_step      = get_option( 'learnpress_updater_step' );
	//
	//      if ( version_compare( $db_version, $latest_version, '>=' ) ) {
	//          $response['result']  = 'success';
	//          $response['message'] = learn_press_admin_view_content( 'updates/html-updated-latest-message' );
	//      } else {
	//          $response['step'] = $next_step;
	//      }
	//
	//      learn_press_send_json( $response );
	//
	//      die();
	//  }

	//  public function update_message() {
	//      learn_press_admin_view( 'updates/html-updating-message' );
	//  }

	/*public function do_update() {
		if ( 'yes' === get_option( 'do-update-learnpress' ) ) {
			return $this->_do_update();
		}

		if ( 'yes' !== LP_Request::get_string( 'do-update-learnpress' ) ) {
			return false;
		}

		update_option( 'do-update-learnpress', 'yes', 'yes' );

		if ( ! learn_press_message_count() ) {
			$this->update_message();
		} else {
			learn_press_print_messages();
		}

		wp_die();
	}*/

	/*protected function _do_update() {
		try {
			$db_version     = get_option( 'learnpress_db_version' );
			$latest_version = true;

			foreach ( $this->get_update_files() as $version => $file ) {

				if ( $db_version && version_compare( $db_version, $version, '>=' ) ) {
					continue;
				}

				include_once LP_PLUGIN_PATH . '/inc/updates/' . $file;

				update_option( 'learnpress_updater', $version, 'yes' );

				$latest_version = false;

				break;
			}

			if ( $latest_version ) {
				delete_option( 'do-update-learnpress' );
				delete_option( 'learnpress_updater' );
				LP_Install::update_version();
				remove_action( 'admin_notices', array( $this, 'update_message' ), 10 );
				LP()->session->set( 'do-update-learnpress', 'yes' );
			}
		} catch ( Exception $ex ) {
			learn_press_add_message( $ex->getMessage(), 'error' );
		}

		return true;
	}*/

	/**
	 * Includes all update patches by version priority.
	 */
	//  public function include_update() {
	//      if ( ! $this->get_update_files() ) {
	//          return;
	//      }
	//
	//      $versions       = array_keys( $this->_update_files );
	//      $latest_version = end( $versions );
	//
	//      if ( version_compare( learn_press_get_current_version(), $latest_version, '=' ) ) {
	//          learn_press_include( 'updates/' . $this->_update_files[ $latest_version ] );
	//      }
	//  }

	/**
	 * Get latest version from updates
	 *
	 * @return bool|mixed
	 */
	/*public function get_latest_version() {
		if ( ! $this->get_update_files() ) {
			return false;
		}

		$versions       = array_keys( $this->_update_files );
		$latest_version = end( $versions );

		return $latest_version;
	}*/

	/**
	 * Scan folder updates to get update patches.
	 */
	//  public function get_update_files() {
	//      if ( ! $this->_update_files ) {
	//          require_once ABSPATH . 'wp-admin/includes/file.php';
	//
	//          if ( WP_Filesystem() ) {
	//              global $wp_filesystem;
	//
	//              $files = $wp_filesystem->dirlist( LP_PLUGIN_PATH . 'inc/updates' );
	//              if ( $files ) {
	//                  foreach ( $files as $file ) {
	//                      if ( preg_match( '!learnpress-update-([0-9.]+).php!', $file['name'], $matches ) ) {
	//                          $this->_update_files [ $matches[1] ] = $file['name'];
	//                      }
	//                  }
	//              }
	//          }
	//
	//          /**
	//           * Sort files by version
	//           */
	//          if ( $this->_update_files ) {
	//              ksort( $this->_update_files );
	//          }
	//      }
	//
	//      return $this->_update_files;
	//  }

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

		$db_current_version = (int) get_option( 'learnpress_db_version' );
		$lp_version         = (int) LP()->version;

		if ( array_key_exists( $db_current_version, $this->db_map_version )
			 && $this->db_map_version[ $db_current_version ] === $lp_version ) {
			return $this->db_map_version[ $db_current_version ];
		}

		/**
		 * For case not have key "learnpress_db_version" on DB, still have columns of learnpress.
		 * After along time, need remove fix fast
		 */
		$lp_db                                = LP_Database::getInstance();
		$check_tb_lp_order_items_exists       = $lp_db->check_table_exists( $lp_db->tb_lp_order_items );
		$check_tb_lp_user_item_results_exists = $lp_db->check_table_exists( $lp_db->tb_lp_user_item_results );
		$check_col_item_id_on_lp_order_items  = $lp_db->check_col_table( $lp_db->tb_lp_order_items, 'item_id' );

		if ( $check_tb_lp_order_items_exists && ( ! $check_tb_lp_user_item_results_exists || ! $check_col_item_id_on_lp_order_items ) ) {
			update_option( 'learnpress_db_version', 3 );

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
		$class_handle = null;

		$db_version_up_to = get_option( 'lp_db_need_upgrade' );

		if ( ! $db_version_up_to && $this->check_lp_db_need_upgrade() ) {
			$db_version_up_to = $this->check_lp_db_need_upgrade();
		}

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

		//      if ( $this->check_lp_db_need_upgrade() ) {
		//          $file_plugin_version = $this->check_lp_db_need_upgrade();
		//
		//          $file_update = LP_PLUGIN_PATH . 'inc/updates/learnpress-upgrade-' . $file_plugin_version . '.php';
		//
		//          if ( file_exists( $file_update ) ) {
		//              include_once $file_update;
		//              $name_class_handle_upgrade = 'LP_Upgrade_' . $file_plugin_version;
		//
		//              if ( class_exists( $name_class_handle_upgrade )
		//                   && is_callable( array( $name_class_handle_upgrade, 'get_instance' ) ) ) {
		//                  $class_handle = $name_class_handle_upgrade::get_instance();
		//              }
		//          }
		//      }

		return $class_handle;
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
	 * Get singleton instance of the class.
	 *
	 * @return bool|LP_Updater
	 */
	public static function instance() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}

return LP_Updater::instance();
