<?php

namespace LearnPress\Services;

use DateTime;
use Exception;
use LearnPress\Helpers\Singleton;
use LP_Helper;
use LP_Settings;
use LP_WP_Filesystem;
use Plugin_Upgrader;
use Throwable;
use WP_Ajax_Upgrader_Skin;

defined( 'ABSPATH' ) || exit;

/**
 * Class AddonService
 *
 * Manage LearnPress add-ons: list, install, update, activate, deactivate,
 * purchase code validation.
 *
 * @package LearnPress\Services
 * @since 4.2.1
 * @version 1.0.1
 */
class AddonService {
	use Singleton;

	/**
	 * @var string Path to local addons data file.
	 */
	public $path_list_addons = LP_PLUGIN_PATH . 'inc/admin/views/addons/addons-data.json';
	/**
	 * @var string Link download plugin from ThimPress.
	 */
	private $link_addon_action = 'https://updates.thimpress.com/thim-addon-market/download-addon';
	/**
	 * @var string Link get info addons purchased.
	 */
	public $link_addons_purchased = 'https://updates.thimpress.com/thim-addon-market/info-addons-purchased';
	/**
	 * @var string Link active site.
	 */
	private $link_active_site = 'https://updates.thimpress.com/thim-addon-market/active-site';
	/**
	 * @var string Link download plugin from org.
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
	 * @var string Option key stores purchase codes of addons.
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
	 * Init hooks.
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Read the bundled add-ons data without making a loopback HTTP request.
	 *
	 * @return string
	 * @throws Exception When the local data file cannot be read.
	 */
	public function get_addons_data(): string {
		if ( ! is_readable( $this->path_list_addons ) ) {
			throw new Exception( __( 'Add-ons data file is not readable.', 'learnpress' ) );
		}

		$data = file_get_contents( $this->path_list_addons );
		if ( false === $data || '' === trim( $data ) ) {
			throw new Exception( __( 'Add-ons data file is empty.', 'learnpress' ) );
		}

		return $data;
	}

	/**
	 * Get list addons with purchase info attached.
	 *
	 * @return object List addons (stdClass map keyed by slug).
	 * @throws Exception
	 */
	public function get_addons(): object {
		$addons = LP_Helper::json_decode( $this->get_addons_data() );

		return $this->get_addons_purchased( $addons );
	}

	/**
	 * Get list addons purchased and attach purchase_info to each addon.
	 *
	 * @param object $addons List addons keyed by slug.
	 *
	 * @return object
	 * @throws Exception
	 */
	public function get_addons_purchased( object $addons ): object {
		$addons_purchase = LP_Settings::get_option( $this->key_purchase_addons, [] );
		if ( empty( $addons_purchase ) ) {
			return $addons;
		}

		$args = [
			'method'     => 'POST',
			'body'       => [
				'addons_purchase' => $addons_purchase,
			],
			'timeout'    => 30,
			'user-agent' => site_url(),
		];

		$result = wp_remote_post( $this->link_addons_purchased, $args );
		if ( is_wp_error( $result ) ) {
			throw new Exception( $result->get_error_message() );
		}

		$data_str = wp_remote_retrieve_body( $result );
		if ( preg_match( '/^Error.*/', $data_str ) ) {
			throw new Exception( $data_str );
		}

		$data = LP_Helper::json_decode( $data_str );

		foreach ( $addons as $key => $addon ) {
			if ( isset( $data->{$key} ) ) {
				$addons->{$key}->purchase_info = $data->{$key};
			}
		}

		return $addons;
	}

	/**
	 * Validate a purchase code with the add-ons service before storing it.
	 *
	 * @param string $addon_slug    Add-on slug.
	 * @param string $purchase_code Purchase code.
	 *
	 * @return object Purchase information returned by the service.
	 * @throws Exception When the code cannot be validated.
	 */
	public function validate_and_save_purchase_code( string $addon_slug, string $purchase_code ): object {
		$purchase_code = trim( $purchase_code );

		if ( empty( $addon_slug ) || empty( $purchase_code ) ) {
			throw new Exception( __( 'Purchase code is invalid!', 'learnpress' ) );
		}

		$response = wp_remote_post(
			$this->link_addons_purchased,
			array(
				'method'     => 'POST',
				'body'       => array(
					'addons_purchase' => array(
						$addon_slug => $purchase_code,
					),
				),
				'timeout'    => 30,
				'user-agent' => site_url(),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		$response_body = wp_remote_retrieve_body( $response );
		if ( empty( $response_body ) || preg_match( '/^Error.*/', $response_body ) ) {
			throw new Exception( __( 'Purchase code is invalid!', 'learnpress' ) );
		}

		$data = LP_Helper::json_decode( $response_body );
		if ( ! is_object( $data ) || ! isset( $data->{$addon_slug} ) || ! is_object( $data->{$addon_slug} ) ) {
			throw new Exception( __( 'Purchase code is invalid!', 'learnpress' ) );
		}

		$key_purchase                = LP_Settings::get_option( $this->key_purchase_addons, array() );
		$key_purchase[ $addon_slug ] = $purchase_code;
		LP_Settings::update_option( $this->key_purchase_addons, $key_purchase );

		return $data->{$addon_slug};
	}

	/**
	 * Mask a purchase code with 16 asterisks, keeping its final four characters visible.
	 *
	 * @param string $purchase_code Purchase code.
	 *
	 * @return string
	 */
	public static function mask_purchase_code( string $purchase_code ): string {
		$purchase_code = trim( $purchase_code );

		if ( empty( $purchase_code ) ) {
			return '';
		}

		return str_repeat( '*', 16 ) . substr( $purchase_code, -4 );
	}

	/**
	 * Get the license status for an expiration date.
	 *
	 * @param string $date_expire  Expiration date.
	 * @param string $current_date Current date in Y-m-d format.
	 *
	 * @return string
	 */
	public static function get_license_status( string $date_expire, string $current_date = '' ): string {
		if ( empty( $date_expire ) ) {
			return 'active';
		}

		$current_date = $current_date ?: gmdate( 'Y-m-d' );

		return strtotime( $date_expire ) <= strtotime( $current_date ) ? 'expired' : 'active';
	}

	/**
	 * Download addon from ThimPress.
	 *
	 * @param array  $addon         Addon data.
	 * @param string $purchase_code Purchase code.
	 *
	 * @return string Path to downloaded zip file.
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

		// For addon must buy.
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

		// Create file temp zip addon to install with.
		$wp_upload_dir = wp_upload_dir( null, false );
		$name          = 'addon.zip';
		$path_file     = $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . $name;
		$lp_file_system->put_contents( $path_file, $data );

		return $path_file;
	}

	/**
	 * Install plugin.
	 *
	 * @param array  $addon   Addon data.
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
	 * @param array  $addon   Addon data.
	 * @param string $package The full local path or URI of the package.
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
	 * @param array $addon Addon data.
	 *
	 * @return bool|int|true|\WP_Error
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
	 * @param array $addon Addon data.
	 *
	 * @return void
	 */
	public function deactivate( array $addon = [] ) {
		deactivate_plugins( $addon['basename'] ?? '' );
	}

	/**
	 * Active site if install plugin via upload zip has "purchase code".
	 *
	 * @param string $addon_slug    Addon slug.
	 * @param string $purchase_code Purchase code.
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
			$key_purchases                = LP_Settings::get_option( $this->key_purchase_addons, [] );
			$key_purchases[ $addon_slug ] = $purchase_code;
			LP_Settings::update_option( $this->key_purchase_addons, $key_purchases );
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
		$addons_new_version = [];

		try {
			$addons  = $this->get_addons();
			$plugins = get_plugins();

			foreach ( $addons as $addon ) {
				if ( isset( $plugins[ $addon->basename ] ) ) {
					$version = $plugins[ $addon->basename ]['Version'];
					if ( version_compare( $version, $addon->version, '<' ) ) {
						$addons_new_version[] = $addon;
					}
				}
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $addons_new_version;
	}

	/**
	 * Check addons purchased need extend.
	 *
	 * @return bool
	 * @since 4.2.5.9
	 * @version 1.0.1
	 */
	public function check_addons_purchased_need_extend(): bool {
		try {
			$addons = $this->get_addons();

			foreach ( $addons as $addon ) {
				if ( isset( $addon->purchase_info ) ) {
					$addon_purchased  = $addon->purchase_info;
					$date_expired_str = $addon_purchased->date_expire ?? '';
					if ( empty( $date_expired_str ) ) {
						continue;
					}

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
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return false;
	}
}
