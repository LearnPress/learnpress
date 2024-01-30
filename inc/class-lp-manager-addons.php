<?php
/**
 * Class LP_Manager_Addons
 *
 * @author  ThimPress
 * @version 1.0.0
 * @since 4.2.1
 */

defined( 'ABSPATH' ) || exit;

class LP_Manager_Addons {
	protected static $_instance;
	/**
	 * @var string Link get list addons.
	 */
	public $url_list_addons = 'https://learnpress.github.io/learnpress/version-addons.json';
	//public $url_list_addons   = LP_PLUGIN_URL . '/version-addons.json';
	/**
	 * @var string $link_addon_action Link download plugin from Thimpress.
	 */
	private $link_addon_action    = 'https://updates.thimpress.com/thim-addon-market/download-addon';
	public $link_addons_purchased = 'https://updates.thimpress.com/thim-addon-market/info-addons-purchased';
	//public $link_addon_action = 'http://updates/thim-addon-market/download-addon';
	/**
	 * @var string $link_addon_action Link active site.
	 */
	private $link_active_site = 'https://updates.thimpress.com/thim-addon-market/active-site';
	/**
	 * @var string link download plugin from org.
	 */
	public $link_org = 'https://downloads.wordpress.org/plugin/';
	/**
	 * @var WP_Ajax_Upgrader_Skin $upgrader_skin
	 */
	public $upgrader_skin;
	/**
	 * @var Plugin_Upgrader $plugin_upgrader
	 */
	public $plugin_upgrader;
	/**
	 *
	 */
	public $key_purchase_addons = 'purchase_addons';

	/**
	 * Constructor
	 */
	protected function __construct() {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

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
			'user-agent' => site_url(),
		];

		if ( 0 == $addon['is_free'] ) {
			$key_purchase                   = LP_Settings::get_option( $this->key_purchase_addons, [] );
			$key_purchase[ $addon['slug'] ] = $purchase_code;
			LP_Settings::update_option( $this->key_purchase_addons, $key_purchase );

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
		if ( isset( $addon['dependency'] ) ) {
			foreach ( $addon['dependency'] as $addon_slug => $addon_label ) {
				if ( ! is_plugin_active( $addon_slug ) ) {
					throw new Exception( sprintf( 'Please activate "%s" plugin before activate this add-on', $addon_label ) );
				}
			}
		}

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

	/**
	 * Active site if install plugin via upload zip has "purchase code".
	 *
	 * @param $addon_slug
	 * @param $purchase_code
	 *
	 * @return void
	 */
	public function active_site( $addon_slug, $purchase_code ) {
		try {
			$args = [
				'method'     => 'POST',
				'body'       => [
					'addon'         => $addon_slug,
					'purchase_code' => $purchase_code,
				],
				'user-agent' => site_url(),
			];

			$result = wp_remote_post( $this->link_active_site, $args );
			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			$data = wp_remote_retrieve_body( $result );
			if ( preg_match( '/^Error.*/', $data ) ) {
				throw new Exception( $data );
			}

			// Save keys purchase code of addons to table WP Options.
			$key_purchases                = LP_Settings::get_option( LP_Manager_Addons::instance()->key_purchase_addons, [] );
			$key_purchases[ $addon_slug ] = $purchase_code;
			LP_Settings::update_option( LP_Manager_Addons::instance()->key_purchase_addons, $key_purchases );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * Get list addon have new version.
	 *
	 * @return array
	 */
	public function list_addon_new_version(): array {
		$addon_contr = new LP_REST_Addon_Controller();
		$request     = new WP_REST_Request();
		$request->set_param( 'return_obj', true );
		$addons_rs          = $addon_contr->list_addons( $request );
		$addons_new_version = [];
		if ( 'success' === $addons_rs->status ) {
			$addons  = $addons_rs->data;
			$plugins = get_plugins();

			foreach ( $addons as $addon ) {
				if ( isset( $plugins[ $addon->basename ] ) ) {
					$version = $plugins[ $addon->basename ]['Version'];
					if ( version_compare( $version, $addon->version, '<' ) ) {
						$addons_new_version[] = $addon;
					}
				}
			}
		}

		return $addons_new_version;
	}

	/**
	 * Check addons purchased need extend.
	 *
	 * @return bool
	 * @throws Exception
	 * @since 4.2.5.9
	 * @version 1.0.0
	 */
	public function check_addons_purchased_need_extend(): bool {
		$addon_contr = new LP_REST_Addon_Controller();
		$request     = new WP_REST_Request();
		$request->set_param( 'return_obj', true );
		$addons_rs = $addon_contr->list_addons( $request );
		foreach ( $addons_rs->data as $addon ) {
			if ( isset( $addon->purchase_info ) ) {
				$addon_purchased  = $addon->purchase_info;
				$date_expired_str = $addon_purchased->date_expire ?? '';
				// Test
				//$date_expired_str = '2024-02-01';
				//$date_expired_str = '2023-01-12';
				// End
				$date_expired          = new DateTime( $date_expired_str );
				$date_now              = new DateTime( gmdate( 'Y-m-d' ) );
				$date_diff             = date_diff( $date_now, $date_expired );
				$number_days_remaining = $date_diff->days;
				if ( $date_diff->invert ) {
					$number_days_remaining = 0;
				}

				if ( $number_days_remaining <= 60 ) {
					return true;
				}
			}
		}

		return false;
	}
}
