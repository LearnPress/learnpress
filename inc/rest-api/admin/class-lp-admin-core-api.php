<?php

class LP_Admin_Core_API extends LP_Abstract_API {
	public function __construct() {

		parent::__construct();
	}

	/**
	 * Includes files
	 */
	public function rest_api_includes() {
		parent::rest_api_includes();

		include_once dirname( __FILE__ ) . '/class-lp-admin-rest-question-controller.php';
		include_once dirname( __FILE__ ) . '/class-lp-admin-rest-database-controller.php';
		include_once dirname( __FILE__ ) . '/class-lp-admin-rest-course-controller.php';

		do_action( 'learn-press/admin/core-api/includes' );
	}

	public function rest_api_register_routes() {
		$controllers = array(
			'LP_REST_Admin_Question_Controller',
			'LP_REST_Admin_Database_Controller',
			'LP_REST_Admin_Course_Controller',
		);

		$this->controllers = apply_filters( 'learn-press/core-api/controllers', $controllers );

		parent::rest_api_register_routes();
	}
}
