<?php
/**
 * Class AddonsAjax
 *
 * Handle add-on actions (install, update, activate, deactivate, update-purchase)
 * via the LearnPress AJAX dispatcher.
 *
 * @since 4.4.7
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Helpers\Response;
use LearnPress\Models\UserModel;
use LearnPress\Services\AddonService;
use LearnPress\TemplateHooks\Admin\AdminAddonsPage;
use LP_Helper;
use LP_Request;
use LP_Settings;
use LP_WP_Filesystem;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class AddonsAjax
 */
class AddonsAjax extends AbstractAjax {
	/**
	 * @var AddonService
	 */
	private $addon_service;

	/**
	 * AddonsAjax constructor.
	 */
	public function __construct() {
		$this->addon_service = AddonService::instance();
	}

	/**
	 * Handle addon action: install, update, activate, deactivate, update-purchase.
	 *
	 * @return void
	 */
	public function addon_action() {
		$response       = new Response();
		$lp_file_system = LP_WP_Filesystem::instance();

		try {
			if ( is_multisite() && ! is_super_admin() ) {
				throw new Exception( __( 'You do not have permission.', 'learnpress' ) );
			} elseif ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( __( 'You do not have permission.', 'learnpress' ) );
			}

			$params = LP_Helper::json_decode( LP_Request::get_param( 'data' ), true );
			if ( ! is_array( $params ) ) {
				throw new Exception( __( 'Params is invalid!', 'learnpress' ) );
			}

			$action = sanitize_key( $params['action_type'] ?? '' );
			if ( empty( $action ) ) {
				throw new Exception( __( 'Action is invalid!', 'learnpress' ) );
			}

			$addon = $params['addon'] ?? [];
			if ( empty( $addon ) || ! is_array( $addon ) ) {
				throw new Exception( __( 'Params is invalid!', 'learnpress' ) );
			}

			$purchase_code = sanitize_text_field( $params['purchase_code'] ?? '' );

			switch ( $action ) {
				case 'install':
				case 'update':
					if ( ! current_user_can( 'install_plugins' ) ) {
						throw new Exception( __( 'You do not have permission to install plugins.', 'learnpress' ) );
					}

					$link_download = '';
					$path_file     = '';
					$package       = '';
					$is_paid_addon = empty( $addon['is_free'] ) && empty( $addon['is_org'] );
					$purchase_info = null;

					if ( $is_paid_addon && 'update' === $action && empty( $purchase_code ) ) {
						$purchase_codes = LP_Settings::get_option( $this->addon_service->key_purchase_addons, array() );
						$purchase_code  = $purchase_codes[ $addon['slug'] ] ?? '';
					}

					if ( $is_paid_addon && 'install' === $action ) {
						$purchase_info = $this->addon_service->validate_and_save_purchase_code( $addon['slug'], (string) $purchase_code );
					}

					if ( ! empty( $addon['is_org'] ) ) {
						$link_download = "{$this->addon_service->link_org}{$addon['slug']}.{$addon['version']}.zip";
					} else {
						// Allow active key for site if site active on DB "updates" is empty.
						$this->addon_service->active_site( $addon['slug'], $purchase_code );
						// Download addon from ThimPress server.
						$path_file = $this->addon_service->download_from_thimpress( $addon, $purchase_code );
					}

					if ( ! empty( $link_download ) ) {
						$package = $link_download;
					} elseif ( ! empty( $path_file ) ) {
						$package = $path_file;
					}

					if ( 'update' === $action ) {
						$this->addon_service->update( $addon, $package );
					} else {
						$this->addon_service->install( $addon, $package );
					}

					if ( ! empty( $path_file ) ) {
						$lp_file_system->lp_filesystem->delete( $path_file );
					}

					break;
				case 'activate':
					if ( ! current_user_can( 'activate_plugins' ) ) {
						throw new Exception( __( 'You do not have permission to activate plugins.', 'learnpress' ) );
					}

					$this->addon_service->activate( $addon );
					break;
				case 'deactivate':
					$this->addon_service->deactivate( $addon );
					break;
				case 'update-purchase':
					$purchase_info = $this->addon_service->validate_and_save_purchase_code(
						$addon['slug'],
						(string) $purchase_code
					);
					break;
				default:
					break;
			}

			$response->status  = Response::STATUS_SUCCESS;
			$response->message = sprintf(
				'"%s" %s %s',
				$addon['name'] ?? '',
				$action,
				__( 'successfully', 'learnpress' )
			);

			$addon_obj = json_decode( wp_json_encode( $addon ) );
			if ( ! empty( $purchase_info ) ) {
				$addon_obj->purchase_info = $purchase_info;
			}

			wp_cache_delete( 'plugins', 'plugins' );
			$plugins_installed = get_plugins();
			$plugins_activated = get_option( 'active_plugins', array() );
			$keys_purchase     = LP_Settings::get_option( $this->addon_service->key_purchase_addons, array() );

			$response->data->html = AdminAddonsPage::html_addon_item(
				$addon_obj,
				array(
					'plugins_installed' => $plugins_installed,
					'plugins_activated' => $plugins_activated,
					'keys_purchase'     => $keys_purchase,
				)
			);
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
