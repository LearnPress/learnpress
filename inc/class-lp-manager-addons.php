<?php
/**
 * Class LP_Assets
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;

class LP_Manager_Addons {
	protected static $_instance;
	//private $url_list_addons = 'https://learnpress.github.io/learnpress/version-addons.json';
	//private $link_addon_action = 'https://updates.thimpress.com/thim-addon-market/download-addon';
	public $url_list_addons   = LP_PLUGIN_URL . '/version-addons.json';
	public $link_addon_action = 'http://updates/thim-addon-market/download-addon';
	public $link_org          = 'https://downloads.wordpress.org/plugin/';

	/**
	 * @var WP_Ajax_Upgrader_Skin $upgrader_skin
	 */
	public $upgrader_skin;
	/**
	 * @var Plugin_Upgrader $plugin_upgrader
	 */
	public $plugin_upgrader;

	/**
	 * Constructor
	 */
	protected function __construct() {
		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

		$this->upgrader_skin   = new WP_Ajax_Upgrader_Skin();
		$this->plugin_upgrader = new Plugin_Upgrader( $this->upgrader_skin );
	}

	/**
	 * Singleton
	 *
	 * @return LP_Manager_Addons
	 */
	public static function instance(): LP_Manager_Addons {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Download addon from Thimpress.
	 *
	 * return string
	 * @throws Exception
	 */
	public function download_from_thimpress( array $addon = [], string $purchase_code = '' ): string {
		$lp_file_system = LP_WP_Filesystem::instance();
		$link_download  = $this->link_addon_action;
		$args           = [
			'method'     => 'POST',
			'body'       => [
				'addon'   => $addon['slug'],
				'version' => 'lastest',
			],
			'user-agent' => home_url( '/' ),
		];

		if ( 0 == $addon['is_free'] ) {
			$args['body']['purchase_code'] = $purchase_code;
		}

		$result = wp_remote_post( $link_download, $args );
		if ( is_wp_error( $result ) ) {
			throw new Exception( $result->get_error_message() );
		}

		$data = wp_remote_retrieve_body( $result );
		if ( preg_match( '/^Error.*/', $data ) ) {
			throw new Exception( $data );
		}

		// Create file temp zip addon to install with
		$wp_upload_dir = wp_upload_dir( null, false );
		$name          = 'addon.zip';
		$path_file     = $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . $name;
		$lp_file_system->put_contents( $path_file, $data );

		return $path_file;
	}

	/**
	 * Install plugin.
	 *
	 * @param array $addon
	 * @param string $package The full local path or URI of the package.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function install( array $addon = [], string $package = '' ) {
		$result_install = $this->plugin_upgrader->install( $package );
		if ( is_wp_error( $result_install ) ) {
			throw new Exception( $result_install->get_error_message() );
		} elseif ( ! $result_install ) {
			throw new Exception( __( 'Install failed!', 'learnpress' ) );
		}

		$result_active = activate_plugin( $addon['basename'] );
		if ( is_wp_error( $result_active ) ) {
			throw new Exception( $result_active->get_error_message() );
		}
	}

	/**
	 * Update plugin.
	 *
	 * @param array $addon
	 * @param string $package
	 *
	 * @throws Exception
	 */
	public function update( array $addon = [], string $package = '' ) {
		$is_activate = is_plugin_active( $addon['basename'] );
		// Must call this function to upgrade success.
		wp_update_plugins();

		$args_upgrade = [
			'package'                     => $package,
			'destination'                 => WP_PLUGIN_DIR,
			'clear_destination'           => false,
			'clear_working'               => true,
			'hook_extra'                  => [],
			'abort_if_destination_exists' => false,
		];
		$result       = $this->plugin_upgrader->run( $args_upgrade );
		if ( ! $result ) {
			throw new Exception( __( 'Update failed!', 'learnpress' ) );
		}

		if ( $is_activate ) {
			$this->activate( $addon );
		}
	}

	/**
	 * Activate plugin.
	 *
	 * @param array $addon
	 *
	 * @return bool|int|true|WP_Error
	 * @throws Exception
	 */
	public function activate( array $addon = [] ) {
		$result_active = activate_plugin( $addon['basename'] ?? '' );
		if ( is_wp_error( $result_active ) ) {
			throw new Exception( $result_active->get_error_message() );
		}

		return $result_active;
	}

	/**
	 * Deactivate plugin.
	 *
	 * @param array $addon
	 *
	 * @return void
	 */
	public function deactivate( array $addon = [] ) {
		deactivate_plugins( $addon['basename'] ?? '' );
	}
}

