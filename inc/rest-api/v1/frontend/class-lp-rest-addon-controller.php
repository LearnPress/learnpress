<?php

use LearnPress\Helpers\Response;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;

defined( 'ABSPATH' ) || exit;

/**
 * REST API LP manager addons.
 */
class LP_REST_Addon_Controller extends LP_Abstract_REST_Controller {
	/**
	 * @var LP_Manager_Addons $lp_addons
	 */
	private $lp_addons;

	/**
	 * LP_REST_Addon_Controller constructor.
	 */
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'addon';

		require_once LP_PLUGIN_PATH . 'inc/class-lp-manager-addons.php';
		$this->lp_addons = LP_Manager_Addons::instance();

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'all'      => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_addons' ),
					'permission_callback' => [ $this, 'permission_callback' ],
				),
			),
			'action-n' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'action' ),
					'permission_callback' => [ $this, 'permission_callback' ],
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Check user permission
	 * 1. Multisite: Check super admin
	 * 2. Single site: Check user is administrator
	 *
	 * @return bool
	 */
	public function permission_callback() {
		return is_multisite() ? is_super_admin() : current_user_can( UserModel::ROLE_ADMINISTRATOR );
	}

	/**
	 * Prepare license data returned to the add-ons UI.
	 *
	 * @param object $purchase_info Purchase information returned by ThimPress.
	 * @param string $purchase_code Purchase code.
	 *
	 * @return array
	 */
	private function prepare_license_response( object $purchase_info, string $purchase_code ): array {
		$date_expire    = $purchase_info->date_expire ?? '';
		$license_status = LP_Manager_Addons::get_license_status( $date_expire );

		return array(
			'license_status'        => $license_status,
			'purchase_code_masked'  => LP_Manager_Addons::mask_purchase_code( $purchase_code ),
			'date_expire'           => $date_expire,
			'date_expire_formatted' => empty( $date_expire ) ? '' : date_i18n( get_option( 'date_format' ), strtotime( $date_expire ) ),
		);
	}

	/**
	 * Get list addons
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @version 1.0.1
	 * @since 4.2.1
	 */
	public function list_addons( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params   = $request->get_params();
			$lp_addon = LP_Manager_Addons::instance();
			$addons = LP_Helper::json_decode( $lp_addon->get_addons_data() );

			// Get list addons purchased.
			$addons_purchase = LP_Settings::get_option( $lp_addon->key_purchase_addons, [] );
			if ( ! empty( $addons_purchase ) ) {
				$args = [
					'method'     => 'POST',
					'body'       => [
						'addons_purchase' => $addons_purchase,
					],
					'timeout'    => 30,
					'user-agent' => site_url(),
				];

				$result = wp_remote_post( $lp_addon->link_addons_purchased, $args );
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
			}
			// End get list addons purchased.

			if ( isset( $params['return_obj'] ) ) {
				$response->status = 'success';
				$response->data   = $addons;

				return $response;
			}

			ob_start();
			Template::instance()->get_admin_template( 'addons.php', compact( 'addons' ) );
			$response->data->html   = ob_get_clean();
			$response->data->addons = $addons;

			$response->status  = 'success';
			$response->message = __( 'Get addons successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ':' . $e->getMessage() );
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Action addon
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return Response
	 * @version 1.0.1
	 * @since 4.2.1
	 */
	public function action( WP_REST_Request $request ): Response {
		$response       = new Response();
		$response->data = '';
		$lp_file_system = LP_WP_Filesystem::instance();

		try {
			$action = $request->get_param( 'action' );
			if ( empty( $action ) ) {
				throw new Exception( __( 'Action is invalid!', 'learnpress' ) );
			}

			$addon = $request->get_param( 'addon' );
			if ( empty( $addon ) ) {
				throw new Exception( __( 'Params is invalid!', 'learnpress' ) );
			}

			$purchase_code = $request->get_param( 'purchase_code' );

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
						$purchase_codes = LP_Settings::get_option( $this->lp_addons->key_purchase_addons, array() );
						$purchase_code  = $purchase_codes[ $addon['slug'] ] ?? '';
					}

					if ( $is_paid_addon && 'install' === $action ) {
						$purchase_info = $this->lp_addons->validate_and_save_purchase_code( $addon['slug'], (string) $purchase_code );
					}

					if ( $addon['is_org'] ) {
						$link_download = "{$this->lp_addons->link_org}{$addon['slug']}.{$addon['version']}.zip";
					} else {
						// Allow active key for site if site active on DB "updates" is empty.
						$this->lp_addons->active_site( $addon['slug'], $purchase_code );
						// Download addon from ThimPress server.
						$path_file = $this->lp_addons->download_from_thimpress( $addon, $purchase_code );
					}

					if ( ! empty( $link_download ) ) {
						$package = $link_download;
					} elseif ( ! empty( $path_file ) ) {
						$package = $path_file;
					}

					if ( 'update' === $action ) {
						$this->lp_addons->update( $addon, $package );
					} else {
						$this->lp_addons->install( $addon, $package );
					}

					if ( ! empty( $path_file ) ) {
						$lp_file_system->lp_filesystem->delete( $path_file );
					}

					if ( 'install' === $action && $purchase_info ) {
						$response->data = $this->prepare_license_response( $purchase_info, (string) $purchase_code );
					}

					break;
				case 'activate':
					if ( ! current_user_can( 'activate_plugins' ) ) {
						throw new Exception( __( 'You do not have permission to activate plugins.', 'learnpress' ) );
					}

					$this->lp_addons->activate( $addon );
					break;
				case 'deactivate':
					$this->lp_addons->deactivate( $addon );
					break;
				case 'update-purchase':
					$purchase_info  = $this->lp_addons->validate_and_save_purchase_code(
						$addon['slug'],
						(string) $purchase_code
					);
					$response->data = $this->prepare_license_response( $purchase_info, (string) $purchase_code );
					break;
				default:
					break;
			}

			$response->status  = 'success';
			$response->message = sprintf( '"%s" %s %s', $addon['name'], $action, __( 'successfully', 'learnpress' ) );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
