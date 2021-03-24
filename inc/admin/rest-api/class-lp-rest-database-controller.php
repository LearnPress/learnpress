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
			'upgrade'   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upgrade' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'get_steps' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_steps' ),
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
		$lp_updater   = LP_Updater::instance();
		$result       = new LP_REST_Response();
		$class_handle = $lp_updater->load_file_version_upgrade_db();

		if ( empty( $class_handle ) ) {
			$result->message = sprintf(
				'%s %s',
				__( 'The LP Database is Latest:', 'learnpress' ),
				'v' . get_option( 'learnpress_db_version' )
			);
			wp_send_json( $result );
		}

		$params = $request->get_params();

		wp_send_json( $class_handle->handle( $params ) );
	}

	/**
	 * Load file upgrade database.
	 *
	 * @return null|object
	 */
//	public function load_file_version_upgrade_db() {
//		// Check version DB need update.
//		$lp_updater = LP_Updater::instance();
//
//		$class_handle = null;
//
//		if ( $lp_updater->check_lp_db_need_upgrade() ) {
//			$file_plugin_version = $lp_updater->check_lp_db_need_upgrade();
//
//			$file_update = LP_PLUGIN_PATH . 'inc/updates/learnpress-upgrade-' . $file_plugin_version . '.php';
//
//			if ( file_exists( $file_update ) ) {
//				include_once $file_update;
//				$name_class_handle_upgrade = 'LP_Upgrade_' . $file_plugin_version;
//				$class_handle              = $name_class_handle_upgrade::get_instance();
//			}
//		}
//
//		return $class_handle;
//	}

	/**
	 * Get Steps upgrade completed.
	 */
	public function get_steps() {
		$lp_updater      = LP_Updater::instance();
		$lp_db           = LP_Database::getInstance();
		$steps_completed = array();
		$steps_default   = array();

		$class_handle = $lp_updater->load_file_version_upgrade_db();

		if ( ! empty( $class_handle ) ) {
			$steps_default = $class_handle->group_steps;

			$tb_lp_upgrade_db_exists = $lp_db->check_table_exists( $lp_db->tb_lp_upgrade_db );

			if ( $tb_lp_upgrade_db_exists ) {
				$steps_completed = $lp_db->get_steps_completed();
			}
		}

		$steps = array(
			'steps_default'   => $steps_default,
			'steps_completed' => $steps_completed,
		);

		wp_send_json( $steps );
	}
}
