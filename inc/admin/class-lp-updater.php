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
	 * Array of update patches.
	 *
	 * @var array
	 */
	protected $_update_files = array();

	/**
	 * LP_Updater constructor.
	 */
	protected function __construct() {
		add_action( 'admin_init', array( $this, 'do_update' ) );
		add_action( 'admin_notices', array( $this, 'check_update_message' ), 20 );

		if ( 'yes' === get_option( 'do-update-learnpress' ) ) {
			add_action( 'admin_notices', array( $this, 'update_message' ) );
		}

		LP_Request::register_ajax( 'check-updated', array( $this, 'check_updated' ) );

		add_action( 'wp_ajax_lp_update_database', array( $this, 'update_database_ajax' ) );
	}

	public function update_database_ajax() {
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
	}

	public function check_updated() {
		$this->do_update();

		$db_version     = get_option( 'learnpress_db_version' );
		$latest_version = $this->get_latest_version();
		$response       = array();
		$next_step      = get_option( 'learnpress_updater_step' );

		if ( version_compare( $db_version, $latest_version, '>=' ) ) {
			$response['result']  = 'success';
			$response['message'] = learn_press_admin_view_content( 'updates/html-updated-latest-message' );
		} else {
			$response['step'] = $next_step;
		}

		learn_press_send_json( $response );

		die();
	}

	public function update_message() {
		learn_press_admin_view( 'updates/html-updating-message' );
	}

	public function do_update() {
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
	}

	protected function _do_update() {
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
	}

	/**
	 * Includes all update patches by version priority.
	 */
	public function include_update() {
		if ( ! $this->get_update_files() ) {
			return;
		}

		$versions       = array_keys( $this->_update_files );
		$latest_version = end( $versions );

		if ( version_compare( learn_press_get_current_version(), $latest_version, '=' ) ) {
			learn_press_include( 'updates/' . $this->_update_files[ $latest_version ] );
		}
	}

	/**
	 * Get latest version from updates
	 *
	 * @return bool|mixed
	 */
	public function get_latest_version() {
		if ( ! $this->get_update_files() ) {
			return false;
		}

		$versions       = array_keys( $this->_update_files );
		$latest_version = end( $versions );

		return $latest_version;
	}

	/**
	 * Scan folder updates to get update patches.
	 */
	public function get_update_files() {
		if ( ! $this->_update_files ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			if ( WP_Filesystem() ) {
				global $wp_filesystem;

				$files = $wp_filesystem->dirlist( LP_PLUGIN_PATH . 'inc/updates' );
				if ( $files ) {
					foreach ( $files as $file ) {
						if ( preg_match( '!learnpress-update-([0-9.]+).php!', $file['name'], $matches ) ) {
							$this->_update_files [ $matches[1] ] = $file['name'];
						}
					}
				}
			}

			/**
			 * Sort files by version
			 */
			if ( $this->_update_files ) {
				ksort( $this->_update_files );
			}
		}

		return $this->_update_files;
	}

	/**
	 * Show message for new update
	 */
	public function check_update_message() {
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		if ( 'yes' === get_option( 'do-update-learnpress' ) ) {
			return;
		}

		if ( LP()->session->get( 'do-update-learnpress' ) ) {
			learn_press_admin_view( 'updates/html-updated-latest-message' );

			return;
		}

		$latest_version = $this->get_latest_version();
		$db_version     = get_option( 'learnpress_db_version' );

		if ( ! $db_version || version_compare( $db_version, $latest_version, '>=' ) ) {
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
