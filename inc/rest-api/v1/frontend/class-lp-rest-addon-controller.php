<?php

use LearnPress\Helpers\Template;

/**
 * REST API LP Widget.
 *
 * @author Nhamdv <daonham95@gmail.com>
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
			'all'    => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_addons' ),
					'permission_callback' => [ $this, 'permission_callback' ],
				),
			),
			'action' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'action' ),
					'permission_callback' => [ $this, 'permission_callback' ],
				),
			),
		);

		parent::register_routes();
	}

	public function permission_callback() {
		return current_user_can( ADMIN_ROLE );
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
		$response = new LP_REST_Response();

		try {
			$params   = $request->get_params();
			$lp_addon = LP_Manager_Addons::instance();
			$res      = wp_remote_get( $lp_addon->url_list_addons );
			if ( is_wp_error( $res ) ) {
				throw new Exception( $res->get_error_message() );
			}

			$addons = json_decode( wp_remote_retrieve_body( $res ) );
			if ( json_last_error() ) {
				throw new Exception( json_last_error_msg() );
			}

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
		$response       = new LP_REST_Response();
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
					$link_download = $path_file = $package = '';

					if ( $addon['is_org'] ) {
						$link_download = "{$this->lp_addons->link_org}{$addon['slug']}.{$addon['version']}.zip";
					} else {
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

					break;
				case 'activate':
					$this->lp_addons->activate( $addon );
					break;
				case 'deactivate':
					$this->lp_addons->deactivate( $addon );
					break;
				case 'update-purchase':
					$key_purchase                   = LP_Settings::get_option( $this->lp_addons->key_purchase_addons, [] );
					$key_purchase[ $addon['slug'] ] = $purchase_code;
					LP_Settings::update_option( $this->lp_addons->key_purchase_addons, $key_purchase );
					break;
				default:
					break;
			}

			$response->status  = 'success';
			$response->message = sprintf( '"%s" %s <strong>%s</strong>', $addon['name'], $action, __( 'successfully', 'learnpress' ) );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}
}
