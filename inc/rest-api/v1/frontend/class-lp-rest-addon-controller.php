<?php

use LearnPress\Helpers\Template;

/**
 * REST API LP Widget.
 *
 * @author Nhamdv <daonham95@gmail.com>
 */
class LP_REST_Addon_Controller extends LP_Abstract_REST_Controller {
	private $link_addon_action = 'http://updates/thim-addon-market/download-addon';

	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'addon';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'all'    => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_addons' ),
					'permission_callback' => '__return_true',
				),
			),
			'action' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'action' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}

	public function permission_callback() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get list addons
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @version 1.0.0
	 * @since 4.2.1
	 */
	public function list_addons( WP_REST_Request $request ): LP_REST_Response {
		$response       = new LP_REST_Response();
		$response->data = '';

		//$url = 'https://learnpress.github.io/learnpress/version-addons.json';
		$url = LP_PLUGIN_URL . '/version-addons.json';

		try {
			$res = wp_remote_get( $url );
			if ( is_wp_error( $res ) ) {
				throw new Exception( $res->get_error_message() );
			}

			$addons = json_decode( wp_remote_retrieve_body( $res ) );
			if ( json_last_error() ) {
				throw new Exception( json_last_error_msg() );
			}

			ob_start();
			Template::instance()->get_admin_template( 'addons.php', compact( 'addons' ) );
			$response->data = ob_get_clean();

			$response->status  = 'success';
			$response->message = __( 'Get addons successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Action addon
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @version 1.0.0
	 * @since 4.2.1
	 */
	public function action( WP_REST_Request $request ): LP_REST_Response {
		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
		/**
		 * @var WP_Filesystem_Base $wp_filesystem
		 */
		global $wp_filesystem;

		$response       = new LP_REST_Response();
		$response->data = '';

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

			$link_org = 'https://downloads.wordpress.org/plugin/';

			switch ( $action ) {
				case 'install':
					if ( $addon['is_org'] ) {
						$skin            = new WP_Ajax_Upgrader_Skin();
						$plugin_upgrader = new Plugin_Upgrader( $skin );
						$link_download   = "$link_org{$addon['slug']}.{$addon['version']}.zip";
						$result          = $plugin_upgrader->install( $link_download );
						if ( is_wp_error( $result ) ) {
							throw new Exception( $result->get_error_message() );
						} elseif ( ! $result ) {
							throw new Exception( __( 'Install failed!', 'learnpress' ) );
						}

						activate_plugin( $addon['basename'] );
					} else {
						$link_download = $this->link_addon_action;
						$args          = [
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
						$wp_filesystem->put_contents( $path_file, $data );
						// End

						$skin            = new WP_Ajax_Upgrader_Skin();
						$plugin_upgrader = new Plugin_Upgrader( $skin );
						$result          = $plugin_upgrader->install( $path_file );
						// Remove file addon temp zip
						$wp_filesystem->delete( $path_file );

						if ( is_wp_error( $result ) ) {
							throw new Exception( $result->get_error_message() );
						} elseif ( ! $result ) {
							throw new Exception( __( 'Install failed!', 'learnpress' ) );
						}

						// Activate addon.
						$result_active = activate_plugin( $addon['basename'] );
						if ( is_wp_error( $result_active ) ) {
							throw new Exception( $result_active->get_error_message() );
						}
					}
					break;
				case 'activate':
					$result_active = activate_plugin( $addon['basename'] );
					if ( is_wp_error( $result_active ) ) {
						throw new Exception( $result_active->get_error_message() );
					}
					break;
				case 'deactivate':
					deactivate_plugins( $addon['basename'] );
					break;
				case 'update':
					if ( $addon['is_org'] ) {
						$is_activate = is_plugin_active( $addon['basename'] );
						// Must call this function to upgrade success.
						wp_update_plugins();
						$skin     = new WP_Ajax_Upgrader_Skin();
						$upgrader = new Plugin_Upgrader( $skin );
						$result   = $upgrader->bulk_upgrade( [ $addon['basename'] ] );

						if ( ! $result ) {
							throw new Exception( __( 'Update failed!', 'learnpress' ) );
						}

						if ( $is_activate ) {
							activate_plugin( $addon['basename'] );
						}
					} else {
						if ( $addon['is_free'] ) {

						} else {

						}
					}
					break;
				default:
					break;
			}

			$response->status  = 'success';
			$response->message = sprintf( '%s %s %s', $addon['name'], $action, __( 'successfully', 'learnpress' ) );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
