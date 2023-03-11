<?php
/**
 * Class LP_Admin_Core_API
 *
 * @author Thimpress
 * @editor tungnx, nhamdv
 * @version 1.0.1
 * @since 4.0.0
 */
class LP_Admin_Core_API extends LP_Abstract_API {
	public function __construct() {
		if ( ! LP_Helper::isRestApiLP() || ! current_user_can( 'administrator' ) ) {
			return;
		}

		parent::__construct();
	}

	/**
	 * Includes files
	 */
	public function rest_api_includes() {
		parent::rest_api_includes();

		$list_rest_admin = array(
			// 'class-lp-admin-rest-question-controller.php',
			'class-lp-admin-rest-database-controller.php',
			'class-lp-admin-rest-course-controller.php',
			'class-lp-admin-rest-tools-controller.php',
			'class-lp-admin-rest-reset-data-controller.php',
		);

		$path_version  = $this->version . '/admin/';
		$path_rest_api = LP_PLUGIN_PATH . 'inc/rest-api/' . $path_version;

		foreach ( $list_rest_admin as $rest_admin ) {
			include_once realpath( $path_rest_api . $rest_admin );
		}

		do_action( 'learn-press/admin/core-api/includes' );
	}

	public function rest_api_register_routes() {
		$controllers = array(
			//'LP_REST_Admin_Question_Controller',
			'LP_REST_Admin_Database_Controller',
			'LP_REST_Admin_Course_Controller',
			'LP_REST_Admin_Tools_Controller',
			'LP_REST_Admin_Reset_Data_Controller',
		);

		$this->controllers = apply_filters( 'learn-press/core-api/controllers', $controllers );

		parent::rest_api_register_routes();
	}
}
