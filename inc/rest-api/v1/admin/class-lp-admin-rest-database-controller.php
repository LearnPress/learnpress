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
			'upgrade'                   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upgrade' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'get_steps'                 => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_steps' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'agree_terms'               => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'agree_terms_upgrade' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'check-db-valid-re-upgrade' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'check_DB_valid_to_re_upgrade' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
			'del-tb-lp-upgrade-db'      => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'delete_tb_lp_upgrade_db' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),
		);

		parent::register_routes();
	}

	public function check_admin_permission() {
		return LP_Abstract_API::check_admin_permission();
	}

	/**
	 * Set agree terms upgrade database.
	 *
	 * @param WP_REST_Request $request .
	 *
	 * @return void
	 */
	public function agree_terms_upgrade( WP_REST_Request $request ) {
		$result = new LP_REST_Response();

		if ( $request->get_param( 'agree_terms' ) ) {
			LP_Settings::update_option( 'agree_terms', LP_Updater::instance()->check_lp_db_need_upgrade() );
			$result->status = 'success';
		}

		wp_send_json( $result );
	}

	/**
	 * Upgrade DB
	 *
	 * @param WP_REST_Request $request .
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
				'v' . get_option( LP_KEY_DB_VERSION )
			);
			wp_send_json( $result );
		}

		$params = $request->get_params();

		wp_send_json( $class_handle->handle( $params ) );
	}

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

	/**
	 * Check DB valid to re upgrade
	 *
	 * can_re_upgrade | 1: can, 0: can't
	 */
	public function check_DB_valid_to_re_upgrade() {
		$response                       = new LP_REST_Response();
		$lp_db                          = LP_Database::getInstance();
		$response->data->can_re_upgrade = 0;

		$col_start_time_gmt_exist = $lp_db->check_col_table( $lp_db->tb_lp_user_items, 'start_time_gmt' );
		$col_graduation_exist     = $lp_db->check_col_table( $lp_db->tb_lp_user_items, 'graduation' );
		$col_item_id_exist        = $lp_db->check_col_table( $lp_db->tb_lp_order_items, 'item_id' );

		if ( $col_start_time_gmt_exist || ! $col_graduation_exist || ! $col_item_id_exist ) {
			$response->data->can_re_upgrade = 1;
		}

		$response->status = 'success';

		wp_send_json( $response );
	}

	/**
	 * Remove table lp_upgrade_db
	 *
	 * can_re_upgrade | 1: can, 0: can't
	 */
	public function delete_tb_lp_upgrade_db() {
		$response                       = new LP_REST_Response();
		$lp_db                          = LP_Database::getInstance();
		$response->data->can_re_upgrade = 0;
		$tables                         = array(
			$lp_db->tb_lp_user_items,
			$lp_db->tb_lp_user_itemmeta,
			$lp_db->tb_lp_question_answers,
			$lp_db->tb_postmeta,
			$lp_db->tb_options,
		);

		try {
			$result = $lp_db->drop_table( $lp_db->tb_lp_upgrade_db );
			update_option( LP_KEY_DB_VERSION, 3 );

			foreach ( $tables as $table ) {
				// Drop - Rename tables bk.
				$tb_bk        = $table . '_bk';
				$tb_bk_exists = $lp_db->check_table_exists( $tb_bk );
				if ( $tb_bk_exists ) {
					$lp_db->drop_table( $table );
					$lp_db->rename_table( $tb_bk, $table );
				}
			}

			if ( $result ) {
				$response->status    = 'success';
				$response->data->url = admin_url( 'admin.php?page=learn-press-tools&tab=database&action=upgrade-db' );
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
