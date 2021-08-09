<?php

/**
 * Class LP_REST_Users_Controller
 *
 * @since 3.3.0
 */
class LP_REST_Admin_Widgets_Controller extends LP_Abstract_REST_Controller {

	public function __construct() {
		$this->namespace = 'lp/v1/admin';
		$this->rest_base = 'widgets';

		parent::__construct();
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'autocomplete' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_autocomplete_data' ),
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
	 * Get Final Quiz in Course Settings.
	 *
	 * @return void
	 */
	public function get_autocomplete_data( WP_REST_Request $request ) {
		$params         = $request->get_params();
		$course_id      = isset( $params['courseId'] ) ? $params['courseId'] : false;
		$response       = new LP_REST_Response();
		$response->data = '';
		$final_quiz     = '';

		return rest_ensure_response( $response );
	}
}
