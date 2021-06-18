<?php
/**
 * Class LP_Core_API
 *
 * @author Thimpress
 * @editor tungnx, nhamdv
 * @version 1.0.1
 * @since 4.0.0
 */
defined( 'ABSPATH' ) || exit;

class LP_Core_API extends LP_Abstract_API {
	public function __construct() {

		parent::__construct();
	}

	/**
	 * Includes files
	 */
	public function rest_api_includes() {
		parent::rest_api_includes();

		$path_version = DIRECTORY_SEPARATOR . $this->version . DIRECTORY_SEPARATOR . 'frontend';

		include_once dirname( __FILE__ ) . $path_version . '/class-lp-rest-settings-controller.php';
		include_once dirname( __FILE__ ) . $path_version . '/class-lp-rest-users-controller.php';
		include_once dirname( __FILE__ ) . $path_version . '/class-lp-rest-courses-controller.php';
		include_once dirname( __FILE__ ) . $path_version . '/class-lp-rest-lazy-load-controller.php';
		include_once dirname( __FILE__ ) . $path_version . '/class-lp-rest-profile-controller.php';

		do_action( 'learn-press/core-api/includes' );
	}

	public function rest_api_register_routes() {
		$controllers = array(
			'LP_REST_Settings_Controller',
			'LP_REST_Users_Controller',
			'LP_REST_Courses_Controller',
			'LP_REST_Lazy_Load_Controller',
			'LP_REST_Profile_Controller',
		);

		$this->controllers = apply_filters( 'learn-press/core-api/controllers', $controllers );

		parent::rest_api_register_routes();
	}
}
