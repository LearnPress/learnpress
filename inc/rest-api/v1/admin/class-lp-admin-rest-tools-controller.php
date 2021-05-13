<?php

/**
 * Class LP_REST_Users_Controller
 *
 * @since 3.3.0
 */
class LP_REST_Admin_Tools_Controller extends LP_Abstract_REST_Controller {
	/**
	 * @var LP_User
	 */
	protected $user = null;

	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'database';
		parent::__construct();
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
				'v' . get_option( 'learnpress_db_version' )
			);
			wp_send_json( $result );
		}

		$params = $request->get_params();

		wp_send_json( $class_handle->handle( $params ) );
	}

}
