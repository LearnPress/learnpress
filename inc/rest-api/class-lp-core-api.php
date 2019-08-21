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
	}

	public function rest_api_register_routes() {
		$this->controllers = array(
			'LP_REST_Settings_Controller',
		);

		parent::rest_api_register_routes();
	}
}