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
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		parent::__construct();
	}

	/**
	 * Includes files
	 */
	public function rest_api_includes() {
		parent::rest_api_includes();

		$path_version = DIRECTORY_SEPARATOR . $this->version . DIRECTORY_SEPARATOR . 'admin';

		include_once dirname( __FILE__ ) . $path_version . '/class-lp-admin-rest-question-controller.php';
		include_once dirname( __FILE__ ) . $path_version . '/class-lp-admin-rest-database-controller.php';
		include_once dirname( __FILE__ ) . $path_version . '/class-lp-admin-rest-course-controller.php';
		include_once dirname( __FILE__ ) . $path_version . '/class-lp-admin-rest-tools-controller.php';
		include_once dirname( __FILE__ ) . $path_version . '/class-lp-admin-rest-reset-data-controller.php';

		do_action( 'learn-press/admin/core-api/includes' );
	}

	public function rest_api_register_routes() {
		$controllers = array(
			'LP_REST_Admin_Question_Controller',
			'LP_REST_Admin_Database_Controller',
			'LP_REST_Admin_Course_Controller',
			'LP_REST_Admin_Tools_Controller',
			'LP_REST_Admin_Reset_Data_Controller',
		);

		$this->controllers = apply_filters( 'learn-press/core-api/controllers', $controllers );

		parent::rest_api_register_routes();
	}
}
