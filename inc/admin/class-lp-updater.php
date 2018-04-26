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
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'update_form' ) );
		add_action( 'admin_init', array( $this, 'do_update' ) );

		if ( $version = get_option( 'lp_updater' ) ) {
			$this->include_update_file( $version );
			add_action( 'admin_notices', array( $this, 'update_message' ), 10 );
		}
	}

	public function update_message() {
		remove_action( 'admin_notices', array( 'LP_Install', 'check_update_message' ), 20 );
		?>
        <div class="notice notice-warning lp-notice-update-database">
            <p>
				<?php _e( '<strong>LearnPress update</strong> – We are running updater to upgrade your database to the latest version.', 'learnpress' ); ?>
            </p>
        </div>
		<?php
	}

	public function get_update_file( $version ) {
		$files = $this->get_update_files();
		if ( $files && ! empty( $files[ $version ] ) ) {
			return LP_PLUGIN_PATH . 'inc/updates/' . $files[ $version ];
		}

		return false;
	}

	public function include_update_file( $version ) {
		if ( $file = $this->get_update_file( $version ) ) {
			include_once $file;
		} else {
			update_option( 'lp_updater', $version );
		}
	}

	public function do_update() {

		if ( 'yes' !== LP_Request::get_string( 'do-update-learnpress' ) ) {
			return;
		}

		echo '<div>';
		ob_start();
		try {

			$db_version = get_option( 'learnpress_db_version' );
			foreach ( $this->get_update_files() as $version => $file ) {

				if ( $db_version && version_compare( $db_version, $version, '>=' ) ) {
					continue;
				}

				echo $file = LP_PLUGIN_PATH . '/inc/updates/' . $file;
				include_once $file;

				update_option( 'lp_updater', $version, 'yes' );
				break;
			}

		}
		catch ( Exception $ex ) {
			learn_press_add_message( $ex->getMessage(), 'error' );
		}

		if ( ob_get_length() ) {
			ob_get_clean();
		}

		if ( ! learn_press_message_count() ) {
			echo '<div id="message-success">';
			learn_press_display_message( __( 'Successfully updated your database.', 'learnpress' ) );
			echo '</div>';
		} else {
			learn_press_print_messages();
		}
		echo '</div>';
		die();
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

		// Update LearnPress from 0.9.x to 1.0
		if ( version_compare( learn_press_get_current_version(), $latest_version, '=' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'hide_other_notices' ), - 100 );

			learn_press_include( 'updates/' . $this->_update_files[ $latest_version ] );
		}
	}

	/**
	 * Scan folder updates to get update patches.
	 */
	public function get_update_files() {
		if ( ! $this->_update_files ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( WP_Filesystem() ) {
				global $wp_filesystem;
				if ( $files = $wp_filesystem->dirlist( LP_PLUGIN_PATH . 'inc/updates' ) ) {
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
	 * Add an empty menu for passing permission check
	 */
	public function admin_menu() {
		// Permission
		if ( 'lp-database-updater' !== LP_Request::get_string( 'page' ) || ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		add_dashboard_page( '', '', 'manage_options', 'lp-database-updater', '' );
	}

	public function update_form() {
		if ( 'lp-database-updater' !== LP_Request::get_string( 'page' ) || ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$assets = learn_press_admin_assets();

		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( 'common' );
		wp_enqueue_style( 'forms' );
		wp_enqueue_style( 'themes' );
		wp_enqueue_style( 'dashboard' );
		wp_enqueue_style( 'widgets' );
		wp_enqueue_style( 'lp-admin', $assets->url( 'css/admin/admin.css' ) );
		wp_enqueue_style( 'lp-setup', $assets->url( 'css/admin/setup.css' ) );

		wp_enqueue_script( 'lp-global', $assets->url( 'js/global.js' ), array(
			'jquery',
			'jquery-ui-sortable',
			'underscore'
		) );
		wp_enqueue_script( 'lp-utils', $assets->url( 'js/admin/utils.js' ) );
		wp_enqueue_script( 'lp-admin', $assets->url( 'js/admin/admin.js' ) );
		wp_enqueue_script( 'lp-update', $assets->url( 'js/admin/update.js' ), array(
			'lp-global',
			'lp-admin',
			'lp-utils'
		) );

		learn_press_admin_view( 'updates/update-screen' );
		die(); // Ignore all thing in the rest.
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