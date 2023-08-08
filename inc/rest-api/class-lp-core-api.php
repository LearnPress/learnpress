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

		$list_rest_frontend = array(
			'class-lp-rest-settings-controller.php',
			'class-lp-rest-users-controller.php',
			'class-lp-rest-courses-controller.php',
			'class-lp-rest-lazy-load-controller.php',
			'class-lp-rest-profile-controller.php',
			'class-lp-rest-orders-controller.php',
			'class-lp-rest-widgets-controller.php',
			'class-lp-rest-addon-controller.php',
			'class-lp-rest-material-controller.php',
			'class-lp-rest-instructor-controller.php',
		);

		$path_version  = $this->version . '/frontend/';
		$path_rest_api = LP_PLUGIN_PATH . 'inc/rest-api/' . $path_version;

		foreach ( $list_rest_frontend as $rest_frontend ) {
			include_once realpath( $path_rest_api . $rest_frontend );
		}

		do_action( 'learn-press/core-api/includes' );
	}

	public function rest_api_register_routes() {
		$controllers = array(
			'LP_REST_Settings_Controller',
			'LP_REST_Users_Controller',
			'LP_REST_Courses_Controller',
			'LP_REST_Lazy_Load_Controller',
			'LP_REST_Profile_Controller',
			'LP_REST_Orders_Controller',
			'LP_REST_Widgets_Controller',
			'LP_REST_Addon_Controller',
			'LP_Rest_Material_Controller',
			'LP_REST_Instructor_Controller',
		);

		$this->controllers = apply_filters( 'learn-press/core-api/controllers', $controllers );

		parent::rest_api_register_routes();
	}
}
