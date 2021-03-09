<?php

/**
 * Class LP_REST_Users_Controller
 *
 * @since 3.3.0
 */
class LP_REST_Admin_Database_Controller extends LP_Abstract_REST_Controller {
	/**
	 * @var LP_User
	 */
	protected $user = null;

	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'database';
		parent::__construct();

		add_filter( 'rest_pre_dispatch', array( $this, 'rest_pre_dispatch' ), 10, 3 );
	}

	/**
	 * Init data prepares for callbacks of rest
	 *
	 * @param                 $null
	 * @param WP_REST_Server  $server
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public function rest_pre_dispatch( $response, $handler, $request ) {

		return $response;
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'upgrade' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upgrade' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
		);

		parent::register_routes();
	}

	public function check_admin_permission() {
		return LP_REST_Authentication::check_admin_permission();
	}

	/**
	 * Upgrade DB
	 *
	 * @return void
	 */
	public function upgrade( WP_REST_Request $request ) {
		// Check version DB need update.
		$lp_updater = LP_Updater::instance();

		$class_handle = '';

		if ( in_array( (int) LEARNPRESS_VERSION, $lp_updater->db_map_version ) ) {
			$file_plugin_version = (int) LEARNPRESS_VERSION;
			$db_version_current  = (int) get_option( 'learnpress_db_version' );
			$db_map              = (int) $lp_updater->db_map_version[ $file_plugin_version ];

			if ( $db_version_current === $db_map ) {
				$file_update = LP_PLUGIN_PATH . 'inc/updates/learnpress-upgrade-' . $file_plugin_version . '.php';

				if ( file_exists( $file_update ) ) {
					include_once $file_update;
					$name_class_handle_upgrade = 'LP_Upgrade_' . $file_plugin_version;
					$class_handle              = $name_class_handle_upgrade::get_instance();
				}
			}
		}

		if ( empty( $class_handle ) ) {
			return;
		}

		$params = $request->get_params();

		wp_send_json( $class_handle->handle( $params ) );
	}
}
