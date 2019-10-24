<?php

class LP_Core_API extends LP_Abstract_API {
	public function __construct() {

		parent::__construct();
	}

	/**
	 * Includes files
	 */
	public function rest_api_includes() {
		parent::rest_api_includes();

		include_once dirname( __FILE__ ) . '/class-lp-rest-settings-controller.php';
		include_once dirname( __FILE__ ) . '/class-lp-rest-users-controller.php';
		include_once dirname( __FILE__ ) . '/class-lp-rest-courses-controller.php';

		do_action( 'learn-press/core-api/includes' );
	}

	public function rest_api_register_routes() {
		$controllers = array(
			'LP_REST_Settings_Controller',
			'LP_REST_Users_Controller',
			'LP_REST_Courses_Controller',
		);

		$this->controllers = apply_filters( 'learn-press/core-api/controllers', $controllers );

		parent::rest_api_register_routes();
	}
}